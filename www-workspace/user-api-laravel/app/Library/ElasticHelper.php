<?php

namespace App\Library;

use Carbon\Carbon;


use Elasticsearch\ClientBuilder;

class ElasticHelper {

    public static function aSearch($sUserId, $aQuery, $sMode = 'search', $iPage = 1)
    {
        //print_r($aQuery);die();
        /*
        possible args
        [
            'q' => 'trees',
            'filters' => [
                'geo' => [
                    'lat_min' => -10,
                    'lat_max' => 0,
                    'lon_min' => 0,
                    'lon_max' => 10,
                    'zoom' => 3
                ],
                'folder' => 'scotland/glasgow/bowman flat',
                'all' => true,
                'date' => [
                    'mode' => 'day',
                    'start_date' => '21/03/2017'
                ],
                'people_gender' => 'male|female',
                'people_emotion' => 'happy',
                'people_number' => 'single|group'
            ]
            },
            'sort' => 'relevance|date_asc|date_desc|shuffle'
        ]
        */

        if(count($aQuery) === 0 && $sMode === 'search')
        {
            // no search query/filters - return nothing
            return [
                'status' => 'success',
                'results' => []
            ];
        }

		//
		// configure
		//

		$sSearchStatus = 'success';
		$aResults = [];
		$aData = [];
        $iPerPage = 100;

		$client = \Elasticsearch\ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOST')])->build();
        
        //
		// do search
		//

        $sQueryString = isset($aQuery['q']) ? $aQuery['q'] : '';

        // limit to our default pagination size
        if ($iPage < 1) $iPage = 1;
        $iSize = $iPerPage;
        $iStartAt = $iPerPage * ($iPage - 1);

        /*
        it uses two should queries as one can handle fuzzy and and one handle multi word term queries e.g. 'queens park'
        */

        $aFilters = [
            ["term" => [ "user_id" => $sUserId]]
        ];
        $bFolderFilter = false;

        $bDateQuery = false;
        $sCalendarMode = null;
        $sCalendarDate = null;

        $iZoom = null;


        if(isset($aQuery['filters']))
        {
            // print_r($aQuery['filters']);
            foreach($aQuery['filters'] as $aFilter)
            {
                switch ($aFilter['type'])
                {
                    case 'folder':
                        $bFolderFilter = true;
                        array_push(
                            $aFilters,
                            ["term" => [ "parent-path" => $aFilter['value']]]
                        );
                        break;
                    case 'geo':

                        // make sure geo data is valid
                        if($aFilter['value']['lat_max'] <= $aFilter['value']['lat_min'] || !isset($aFilter['value']['zoom']) || !is_numeric($aFilter['value']['zoom']))
                        {
                            // invalid
                            return [
                                'status' => 'fail',
                                'errors' => ['invalid geo bounds'],
                                'results' => []
                            ];
                        }else{
                            // map query
                            $iZoom = $aFilter['value']['zoom'];
                            array_push(
                                $aFilters,
                                [
                                    "geo_bounding_box" => [
                                        "location" => [
                                            "top_left" => [
                                                "lat" => $aFilter['value']['lat_max'],
                                                "lon" => $aFilter['value']['lon_min']
                                            ],
                                            "bottom_right" => [
                                                "lat" => $aFilter['value']['lat_min'],
                                                "lon" => $aFilter['value']['lon_max']
                                            ]
                                        ]
                                    ]
                                ]
                            );
                        }

                        break;
                    case 'date':
                        $bDateQuery = true;
                        $sCalendarMode = $aFilter['value']['mode'];
                        $sCalendarDate = $aFilter['value']['start_date'];

                        $sDateLower = $sCalendarDate;
                        $oUpper = Carbon::createFromFormat('d/m/Y', $sCalendarDate);
                        switch($sCalendarMode)
                        {
                            case 'day':
                                // do nothing
                                break;
                            case 'week':
                                $oUpper->addDays(6);
                                break;
                            case 'month':
                            default:
                                $oUpper->addMonths(1)->addDays(-1);
                                break;
                            case 'year':
                                $oUpper->addYears(1)->addDays(-1);
                                break;
                        }
                        $sDateUpper = $oUpper->format('d/m/Y');

                        // depending on mode, construct a range query accordingly

                        array_push($aFilters,
                            [
                                "range" => [
                                    "datetime" => [
                                        "gte" => $sDateLower.' 00:00:00',
                                        "lte" =>  $sDateUpper.' 23:59:59',
                                        "format" => "dd/MM/yyyy HH:mm:ss"
                                    ]
                                ]
                            ]
                        );
                        break;
                    case 'people_gender':
                        array_push(
                            $aFilters,
                            [
                                "match" => [
                                    "aws_faces.gender" => $aFilter['value']
                                ]
                            ]
                        );
                        break;
                    case 'people_emotion':
                        array_push(
                            $aFilters,
                            [
                                "match" => [
                                    "aws_faces.emotional_state.value" => $aFilter['value']
                                ]
                            ]
                        );
                        break;
                    case 'people_number':
                        array_push(
                            $aFilters,
                            [
                                "match" => [
                                    "aws_faces_grouping" => $aFilter['value']
                                ]
                            ]
                        );
                        break;
                }
            }
        }


        //
        // ask for aggregations before we query but after defining params and filters
        //
        $aAggregationsForQueryBody = [];

        // die($sMode);
        switch($sMode)
        {
            case 'folder':
                // then make an aggregation on folders?
                if(!$bFolderFilter) {
                    // user is searching folders without a folder? so we're on the overview page and just provide aggs
                    
                    $aAggregationsForQueryBody['folders'] = [
                        "terms" => [
                            "field" => "parent-path",
                            "size" => 1000
                        ],
                        "aggs" => [
                            "folder" => [
                                "top_hits" => [
                                    "_source" => [
                                        "include" => [
                                            "_id",
                                            "parent-path",
                                            "datetime"/*,
                                            "longtime",
                                            "collection_id"*/
                                        ]
                                    ],
                                    "size" => 1
                                ]
                            ]
                        ]
                    ];

                    // if there's no text query, we're aggregating folders, so take the first file as the most recent
                    if($sQueryString === '')
                    {
                        $aAggregationsForQueryBody['folders']['aggs']['folder']['top_hits'] = [
                            "sort" => [
                                [
                                    "datetime" => [
                                        "order" => "desc"
                                    ]
                                ]
                            ],
                            "_source" => ["_id", "datetime"],
                            "size" => 1
                        ];
                    }else{
                        // to do, by relevance
                        $aAggregationsForQueryBody['folders']['aggs']['folder']['top_hits'] = [
                            "sort" => [
                                [
                                    "tags.confidence" => [
                                        "order" => "desc",
                                        "mode" => "max",
                                        "nested_path" => "tags",
                                        "nested_filter" => [
                                            "term" => [
                                                "tags.value" => $sQueryString
                                            ]
                                        ]
                                    ],
                                    "datetime" => [
                                        "order" => "desc"
                                    ]
                                ]
                            ],
                            "_source" => ["_id", "datetime"],
                            "size" => 1
                        ];
                    }

                    // search for everything
                    array_push(
                        $aFilters, ["match_all" => new \stdClass()]
                    );

                    // look for nothing - why (because we're just showing folders list - agg results)
                    $iSize = 0;
                }
                break;
            case 'calendar':
                // construct aggs based on specific calendar mode
                switch($sCalendarMode)
                {
                    case 'week':

                        $aAggregationsForQueryBody['week'] = [
                            "date_histogram" => [
                                "field" => "datetime",
                                "interval" => "day",/*
                                "min_doc_count" => 0,
                                "format" => "yyyy-MM-dd",
                                "extended_bounds" => [
                                    "min" => "2017-03-26",
                                    "max" => "2017-04-01"
                                ]*/
                            ],
                            "aggs" => [
                                "top_tag_hits" => [
                                    "top_hits" => [
                                        "sort" => [
                                            [
                                                "datetime" => [
                                                    "order" => "desc"
                                                ]
                                            ]
                                        ],
                                        "_source" => [
                                            "includes" => [
                                                "id"
                                            ]
                                        ],
                                        "size"  => 16
                                    ]
                                ]
                            ]
                        ];
                        break;
                    case 'month':
                        $aAggregationsForQueryBody['month'] = [
                            "date_histogram" => [
                                "field" => "datetime",
                                "interval" => "day"
                            ],
                            "aggs" => [
                                "top_tag_hits" => [
                                    "top_hits" => [
                                        "sort" => [
                                            [
                                                "datetime" => [
                                                    "order" => "desc"
                                                ]
                                            ]
                                        ],
                                        "_source" => [
                                            "includes" => [
                                                "id"
                                            ]
                                        ],
                                        "size"  => 9
                                    ]
                                ]
                            ]
                        ];
                        break;
                    case 'year':
                        $aAggregationsForQueryBody['year'] = [
                            "date_histogram" => [
                                "field" => "datetime",
                                "interval" => "day"
                            ],
                            "aggs" => [
                                "top_tag_hits" => [
                                    "top_hits" => [
                                        "sort" => [
                                            [
                                                "datetime" => [
                                                    "order" => "desc"
                                                ]
                                            ]
                                        ],
                                        "_source" => [
                                            "includes" => [
                                                "id"
                                            ]
                                        ],
                                        "size"  => 1
                                    ]
                                ]
                            ]
                        ];
                        break;
                }

                // also limit results to 0 if not on day mode
                if($sCalendarMode !== 'day') {
                    $iSize = 0;
                }

                break;
            case 'map':
                // add a geohash aggregation
                $iaZoomToPrecision = [
                    1 => [1, 2],
                    2 => [1, 2],
                    3 => [1, 2],
                    4 => [2, 3],
                    5 => [2, 3],
                    6 => [3, 6],
                    7 => [3, 6],
                    8 => [4, 7],
                    9 => [4, 8],
                    10 => [4, 10],
                    11 => [5, 10],
                    
                    12 => [6, 12],
                    13 => [6, 12],
                    14 => [7, 12],
                    15 => [7, 12],
                    16 => [7, 12],
                    17 => [8, 12],
                    18 => [8, 12],
                    19 => [8, 12],
                    20 => [9, 12],
                    21 => [9, 12],
                    22 => [9, 12]
                ];
                $aAggregationsForQueryBody['map'] = [
                    "geohash_grid" => [
                        "field" => "location",
                        "precision" => $iaZoomToPrecision[$iZoom][0]
                    ],
                    "aggs" => [
                        "geo_hash" => [
                            "top_hits" => [
                                "_source" => [
                                    "includes" => [
                                        "_id",
                                        "latitude",
                                        "longitude"
                                    ]
                                ],
                                "size" => 1
                            ]
                        ]
                    ]
                ];
                $aAggregationsForQueryBody['map_dots'] = [
                    "geohash_grid" => [
                        "field" => "location",
                        "precision" => $iaZoomToPrecision[$iZoom][1]
                    ],
                    "aggs" => [
                        "geo_hash" => [
                            "top_hits" => [
                                "_source" => [
                                    "includes" => [
                                        "_id",
                                        "latitude",
                                        "longitude"
                                    ]
                                ],
                                "size" => 1
                            ]
                        ]
                    ]
                ];
                $iSize = $iPerPage;
                break;
        }

        //
        // sort
        //
        $aSorts = [];
        $bShuffle = false;
        if(isset($aQuery['sort']))
        {
            switch($aQuery['sort'])
            {
                case 'date_desc':
                    array_push($aSorts, ["datetime" => [
                        "order" => "desc"
                    ]]);
                    break;
                case 'date_asc':
                    array_push($aSorts, ["datetime" => [
                        "order" => "asc"
                    ]]);
                    break;
                case 'relevance':
                    array_push($aSorts, [
                        "tags.confidence" => [
                            "order" => "desc",
                            "mode" => "max",
                            "nested_path" => "tags",
                            "nested_filter" => [
                                "term" => [
                                    "tags.value" => $sQueryString
                                ]
                            ]
                        ],
                        "datetime" => [
                            "order" => "desc"
                        ]
                    ]);
                    break;
                // not nessecary, it iwll be shuffled by default?
                case 'shuffle':
                    $bShuffle = true;
                    break;
            }
        }

        array_push($aSorts, '_score');

        // echo 'index: '.env('ELASTIC_INDEX');

        $params = [
		    'index' => env('ELASTIC_INDEX'),
		    'type' => 'file',
		    'body' => [
                'sort' => $aSorts,
		        'query' => [
                    "function_score" => [
                        'query' => [
                            "bool" => [
                              "must" => $aFilters
                               /* should here */
                               /* min should matches */
                           ]
                       ],
                       "functions" => [
                           [
                               "random_score" => new \stdClass()
                           ]
                       ]
                   ]
                   /* func score here */
               ]
                /* aggs here */
                /* sorts here */
		    ]
		];

        if(count($aAggregationsForQueryBody) > 0)
        {
            $params['body']['aggs'] = $aAggregationsForQueryBody;
        }

        if($iSize !== null)
        {
            $params['body']['size'] = $iSize;
            $params['body']['from'] = $iStartAt;
        }

        if($sQueryString !== '')
        {
            $params['body']['query']['function_score']['query']['bool']["should"] = [
                [
                    "nested" => [
                        "path" =>  "tags",
                        "score_mode" => "max",
                        "query"=> [
                            "bool"=> [
                                "must" => [
                                    [
                                        "match"=> [
                                            "tags.value" => $sQueryString
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    "nested" => [
                        "path"=> "tags",
                        "score_mode"=> "max",
                        "query"=> [
                            "fuzzy"=> [
                                "tags.value"=>  $sQueryString
                            ]
                        ]
                    ]
                ]
            ];

            $params['body']['query']['function_score']['query']['bool']["minimum_should_match"] = 1;
        }
        /**/

        // echo "SEARCHING ELASTIC";
		// echo json_encode($params);


        // $file = fopen("query.json", "w");
        // fwrite($file, json_encode($params));
        // fclose($file);
		// print_r($params);die();

        //
        // process query
        //

		$response = $client->search($params);

		$aResults = $response;


        // print_r($aResults);die();

		$aFileResults = [];

		foreach ($aResults['hits']['hits'] as $key => $value) {
            $aResult = [
                'id' => $value['_id'],
                'datetime' => isset($value['_source']['datetime']) ? $value['_source']['datetime'] : null,
                /*'score' => $value['_score'],*/
                
                'lat' => isset($value['_source']['latitude']) ? $value['_source']['latitude'] : null,                
                'lon' => isset($value['_source']['longitude']) ? $value['_source']['longitude'] : null,
                
                'm_w' => isset($value['_source']['medium_width']) ? $value['_source']['medium_width'] : null,                
                'm_h' => isset($value['_source']['medium_height']) ? $value['_source']['medium_height'] : null
            ];
            if(
                isset($value['_source']['r']) &&
                isset($value['_source']['g']) &&
                isset($value['_source']['b'])
            )
            {
                $sC = '#';
                $sC .= str_pad(dechex($value['_source']['r']), 2, "0", STR_PAD_LEFT);
                $sC .= str_pad(dechex($value['_source']['g']), 2, "0", STR_PAD_LEFT);
                $sC .= str_pad(dechex($value['_source']['b']), 2, "0", STR_PAD_LEFT);

                $aResult['colour'] = $sC;
            }
			array_push(
				$aFileResults,
				$aResult
			);
		}
        $aAggs = [];
        
        if(isset($aResults['aggregations']))
        {
            //
            // now we have aggregations we parse them differently for each kind of search mode
            //
            switch($sMode)
            {
                case 'folder':
                    // then make an aggregation on folders?
                    $aSort = [];
                    // print_r($aResults['aggregations']);
                    foreach($aResults['aggregations']['folders']['buckets'] as $aFolderAgg)
                    {
                        $aSort[strtotime($aFolderAgg['folder']['hits']['hits'][0]['_source']['datetime'])] = [
                            'name' => $aFolderAgg['key'],
                            'file-id' => $aFolderAgg['folder']['hits']['hits'][0]['_id'],
                            'parent' => basename($aFolderAgg['key']),
                            'count' => $aFolderAgg['doc_count'],
                            'datetime' => $aFolderAgg['folder']['hits']['hits'][0]['_source']['datetime']
                        ];
                    }
                    krsort($aSort);

                    foreach($aSort as $aFolder) {
                        array_push($aAggs, $aFolder);
                    }
                    
                    
                    break;
                case 'calendar':
                    // depending on mode, split out aggs
                    switch($sCalendarMode)
                    {
                        case 'week':
                            $aAggregationsForQueryBody['week'] = [
                                "date_histogram" => [
                                    "field" => "datetime",
                                    "interval" => "day"
                                ]
                            ];
                            foreach($aResults['aggregations']['week']['buckets'] as $aDayAgg)
                            {
                                $aDayAgg['key_as_string'];
                                $aDayAgg['doc_count'];
                                $sKey = explode(' ', $aDayAgg['key_as_string'])[0];

                                $saHitIds = [];

                                foreach($aDayAgg['top_tag_hits']['hits']['hits'] as $aHit)
                                {
                                    array_push($saHitIds, $aHit['_id']);
                                }

                                // $aAggs[$sKey] = [
                                //     'name' => $aDayAgg['key_as_string'],
                                //     'files' => $saHitIds,
                                //     'count' => $aDayAgg['doc_count']
                                // ];
                                array_push($aAggs, [
                                    'name' => $aDayAgg['key_as_string'],
                                    'files' => $saHitIds,
                                    'count' => $aDayAgg['doc_count']
                                ]);
                            }
                            break;
                        case 'month':
                            $aAggregationsForQueryBody['month'] = [
                                "date_histogram" => [
                                    "field" => "datetime",
                                    "interval" => "day"
                                ]
                            ];
                            foreach($aResults['aggregations']['month']['buckets'] as $aDayAgg)
                            {
                                $aDayAgg['key_as_string'];
                                $aDayAgg['doc_count'];
                                $sKey = explode(' ', $aDayAgg['key_as_string'])[0];

                                $saHitIds = [];

                                foreach($aDayAgg['top_tag_hits']['hits']['hits'] as $aHit)
                                {
                                    array_push($saHitIds, $aHit['_id']);
                                }

                                // $aAggs[$sKey] = [
                                //     'name' => $aDayAgg['key_as_string'],
                                //     'files' => $saHitIds,
                                //     'count' => $aDayAgg['doc_count']
                                // ];
                                array_push($aAggs, [
                                    'name' => $aDayAgg['key_as_string'],
                                    'files' => $saHitIds,
                                    'count' => $aDayAgg['doc_count']
                                ]);
                            }
                            break;
                        case 'year':
                            $aAggregationsForQueryBody['year'] = [
                                "date_histogram" => [
                                    "field" => "datetime",
                                    "interval" => "day"
                                ]
                            ];
                            foreach($aResults['aggregations']['year']['buckets'] as $aDayAgg)
                            {
                                $aDayAgg['key_as_string'];
                                $aDayAgg['doc_count'];
                                $sKey = explode(' ', $aDayAgg['key_as_string'])[0];

                                $saHitIds = [];

                                foreach($aDayAgg['top_tag_hits']['hits']['hits'] as $aHit)
                                {
                                    array_push($saHitIds, $aHit['_id']);
                                }

                                // $aAggs[$sKey] = [
                                //     'name' => $aDayAgg['key_as_string'],
                                //     'files' => $saHitIds,
                                //     'count' => $aDayAgg['doc_count']
                                // ];
                                array_push($aAggs, [
                                    'name' => $aDayAgg['key_as_string'],
                                    'files' => $saHitIds,
                                    'count' => $aDayAgg['doc_count']
                                ]);
                            }
                            break;
                    }
                    break;
                case 'map':
                    // print_r($aResults['aggregations']);die();
                    $aMapAggs = [];
                    foreach($aResults['aggregations']['map']['buckets'] as $aGeoAgg)
                    {
                        array_push($aMapAggs,
                            [
                                'name' => $aGeoAgg['key'],
                                'id' => $aGeoAgg['geo_hash']['hits']['hits'][0]['_id'],
                                'lat' => (float)$aGeoAgg['geo_hash']['hits']['hits'][0]['_source']['latitude'],
                                'lon' => (float)$aGeoAgg['geo_hash']['hits']['hits'][0]['_source']['longitude'],
                                'count' => $aGeoAgg['doc_count']
                            ]
                        );
                    }
                    $aMapIconAggs = [];
                    foreach($aResults['aggregations']['map_dots']['buckets'] as $aGeoAgg)
                    {
                        array_push($aMapIconAggs,
                            [
                                'name' => $aGeoAgg['key'],
                                'id' => $aGeoAgg['geo_hash']['hits']['hits'][0]['_id'],
                                'lat' => (float)$aGeoAgg['geo_hash']['hits']['hits'][0]['_source']['latitude'],
                                'lon' => (float)$aGeoAgg['geo_hash']['hits']['hits'][0]['_source']['longitude'],
                                'count' => $aGeoAgg['doc_count']
                            ]
                        );
                    }

                    $aAggs['map_icons'] = $aMapAggs;
                    $aAggs['map_dots'] = $aMapIconAggs;
                    break;
            }
        }

        $iTotalAvailableResultsForSearch = $aResults['hits']['total'];
        $cResultsOfThisSearch = count($aResults['hits']['hits']);

        $bHasMore = ($cResultsOfThisSearch + $iStartAt < $iTotalAvailableResultsForSearch) ? true : false;
        $iTotalAvailablePages = ceil($iTotalAvailableResultsForSearch / $iPerPage);

        // echo "\niPage: $iPage    \n";
        // echo "iTotalAvailablePages: $iTotalAvailablePages    \n";
        // echo "iPerPage: $iPerPage    \n";
        // echo "cResultsOfThisSearch: $cResultsOfThisSearch    \n";
        // echo "iTotalAvailableResultsForSearch: $iTotalAvailableResultsForSearch   \n\n\n";

        if ($iPage > $iTotalAvailablePages) $iPage = null;
        $iRangeMin = $iPage === null ? null : $iStartAt;
        $iRangeMax = $iPage === null ? null : $iStartAt + $cResultsOfThisSearch;

        $aData = [
            'available' => $iTotalAvailableResultsForSearch,
            'speed' => $aResults['took'],
            'more' => $bHasMore,
            'page' => $iPage,
            'range_min' => $iRangeMin,
            'range_max' => $iRangeMax,
            'total_pages' => $iTotalAvailablePages
        ];

		// return stuff
        $aReturn = [
			'status' => $sSearchStatus,
			'results' => $aFileResults,
			'data' => $aData
        ];

        if(count($aAggs) > 0) {
            $aReturn['aggs'] = $aAggs;
        }

        // $file = fopen("query_resp.json", "w");
        // fwrite($file, json_encode($aReturn));
        // fclose($file);

		return $aReturn;

    }

    public static function aHomeAggs($sUserId) {
        // years ago

        $client = \Elasticsearch\ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOST')])->build();

        $oDateFiveYearsAgo = Carbon::now();
        $oDateFiveYearsAgo->addYears(-5);

        $oDateFourYearsAgo = Carbon::now();
        $oDateFourYearsAgo->addYears(-4);

        $oDateThreeYearsAgo = Carbon::now();
        $oDateThreeYearsAgo->addYears(-3);

        $oDateTwoYearsAgo = Carbon::now();
        $oDateTwoYearsAgo->addYears(-2);

        $oDateOneYearAgo = Carbon::now();
        $oDateOneYearAgo->addYears(-1);

        $params = [
            'body' => [
                self::aOnThisDayQueryParts($sUserId, $oDateFiveYearsAgo)[0],
                self::aOnThisDayQueryParts($sUserId, $oDateFiveYearsAgo)[1],
                self::aOnThisDayQueryParts($sUserId, $oDateFourYearsAgo)[0],
                self::aOnThisDayQueryParts($sUserId, $oDateFourYearsAgo)[1],
                self::aOnThisDayQueryParts($sUserId, $oDateThreeYearsAgo)[0],
                self::aOnThisDayQueryParts($sUserId, $oDateThreeYearsAgo)[1],
                self::aOnThisDayQueryParts($sUserId, $oDateTwoYearsAgo)[0],
                self::aOnThisDayQueryParts($sUserId, $oDateTwoYearsAgo)[1],
                self::aOnThisDayQueryParts($sUserId, $oDateOneYearAgo)[0],
                self::aOnThisDayQueryParts($sUserId, $oDateOneYearAgo)[1]
            ]
        ];

        $response = $client->msearch($params);

        $aAggResults = [];

        $aFive = self::aResultIds($response['responses'][0]);
        $aFour = self::aResultIds($response['responses'][1]);
        $aThree = self::aResultIds($response['responses'][2]);
        $aTwo = self::aResultIds($response['responses'][3]);
        $aOne = self::aResultIds($response['responses'][4]);

        return [
            'on_this_day' => [
                '5_years_ago' => $aFive,
                '4_years_ago' => $aFour,
                '3_years_ago' => $aThree,
                '2_years_ago' => $aTwo,
                '1_year_ago' => $aOne
            ]
        ];

    }

    private static function aOnThisDayQueryParts($sUserId, $oDate)
    {
        return [
            [
                'index' => env('ELASTIC_INDEX'),
                'type' => 'file'
            ],
            [
                'sort' => [
                    "datetime" => [
                        "order" => "desc"
                    ]
                ],
                'query' => [
                    "function_score" => [
                        'query' => [
                            "bool" => [
                                "must" => [
                                    [
                                        "term" => [
                                            "user_id" => $sUserId
                                        ]
                                    ],
                                    [
                                        "range" => [
                                            "datetime" => [
                                                "gte" => $oDate->format('d/m/Y').' 00:00:00',
                                                "lte" =>  $oDate->format('d/m/Y').' 23:59:59',
                                                "format" => "dd/MM/yyyy HH:mm:ss"
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        "functions" => [
                            [
                                "random_score" => new \stdClass()
                            ]
                        ]
                    ]
                ],
                'size' => 10
            ]
        ];
    }

    private static function aResultIds($aResults) {
        $aAggResults = [];
        foreach ($aResults['hits']['hits'] as $key => $value) {
            $aResult = [
                'id' => $value['_id']
            ];
            if(
                isset($value['_source']['r']) &&
                isset($value['_source']['g']) &&
                isset($value['_source']['b'])
            )
            {
                $sC = '#';
                $sC .= str_pad(dechex($value['_source']['r']), 2, "0", STR_PAD_LEFT);
                $sC .= str_pad(dechex($value['_source']['g']), 2, "0", STR_PAD_LEFT);
                $sC .= str_pad(dechex($value['_source']['b']), 2, "0", STR_PAD_LEFT);

                $aResult['colour'] = $sC;
            }
			array_push(
				$aAggResults,
				$aResult
			);
        }
        return $aAggResults;
    }
}
