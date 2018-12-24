<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Library\ElasticHelper;

class ElasticTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
     
    public function testGeoQueryBuckets()
    {
        // geo aggs (results and pins)
        $aGeoBuckets = ElasticHelper::aSearch(
            0,
            [
                'filters' =>
                [
                    [
                        'type' => 'geo',
                        'value' => [
                            'lat_min' => -90,
                            'lat_max' => 90,
                            'lon_min' => -180,
                            'lon_max' => 180,
                            'zoom' => 2
                        ]
                    ]
                ],
                'q' => 'park'
            ],
            'map'
        );

        $this->assertEquals(1, count($aGeoBuckets['results']));
        $this->assertEquals(1, count($aGeoBuckets['aggs']['map_icons']));
        $this->assertEquals(1, count($aGeoBuckets['aggs']['map_dots']));
    }
    public function testFolderSearch()
    {
        // folder search/filter
        $aFolderQuery = ElasticHelper::aSearch(
            0,
            [
                'filters' => [
                    [
                        'type' => 'folder',
                        'value' => 'scotland/glasgow/bowman flat'
                    ]
                ]
            ]
        );
        $this->assertEquals(1, count($aFolderQuery['results']));

        // folder search with aggregations
        $aFolderAggQuery = ElasticHelper::aSearch(
            0,
            [
                'filters' =>
                [
                    [
                        'type' => 'folder',
                        'value' => 'scotland/glasgow/bowman flat'
                    ]
                ]
            ],
            'folder'
        );
        $this->assertEquals(1, count($aFolderAggQuery['results']));


        // without folder and a term
        $aNoFolderWithQuery = ElasticHelper::aSearch(
            0,
            ['q' => 'trees'],
            'folder'
        );
        $this->assertEquals(0, count($aNoFolderWithQuery['results']));
        $this->assertTrue(isset($aNoFolderWithQuery['aggs']));
        $this->assertTrue(count($aNoFolderWithQuery['aggs']) === 1);
        $this->assertTrue(isset($aNoFolderWithQuery['aggs'][0]['name']));
        $this->assertTrue(isset($aNoFolderWithQuery['aggs'][0]['parent']));
        $this->assertTrue(isset($aNoFolderWithQuery['aggs'][0]['file-id']));
        $this->assertTrue(isset($aNoFolderWithQuery['aggs'][0]['count']));

        // with folder and a term
        $aWithFolderWithQuery = ElasticHelper::aSearch(
            0,
            [
                'q' => 'trees',
                'filters' =>
                [
                    [
                        'type' => 'folder',
                        'value' => 'scotland/glasgow/bowman flat'
                    ]
                ]
            ],
            'folder'
        );
        $this->assertEquals(0, count($aWithFolderWithQuery['results']));
        $this->assertFalse(isset($aWithFolderWithQuery['aggs']));

        $aAnotherFolderWithQuery = ElasticHelper::aSearch(
            0,
            [
                'q' => 'balvicar',
                'filters' =>
                [
                    [
                        'type' => 'folder',
                        'value' => 'scotland/glasgow/bowman flat'
                    ]
                ]
            ],
            'folder'
        );
        $this->assertEquals(1, count($aAnotherFolderWithQuery['results']));
        $this->assertFalse(isset($aAnotherFolderWithQuery['aggs']));
    }
    public function testFilenameSearch()
    {
        $aFileNameSearchResults = ElasticHelper::aSearch(1, ['q' => 'DSC00145.JPG']);
        $this->assertEquals(1, count($aFileNameSearchResults['results']));
    }

    public function testFolderAggsOrder()
    {
        // without folder and no term - should return aggs of all folders and zero results
        $aNoFolderEmptyQuery = ElasticHelper::aSearch(
            0,
            [],
            'folder'
        );
        $this->assertEquals(0, count($aNoFolderEmptyQuery['results']));
        $this->assertTrue(isset($aNoFolderEmptyQuery['aggs']));
        $this->assertTrue(count($aNoFolderEmptyQuery['aggs']) > 1);

        // folder aggs are sorted
        $this->assertEquals(
            [1009, 1003, 1004],
            $this->aOrderedIds($aNoFolderEmptyQuery['aggs'], 'file-id')
        );
    }
    public function testBasicTextSearchMatching()
    {
        // free text search
        $aTwoResults = ElasticHelper::aSearch(0, ['q' => 'city']);
        $this->assertEquals(5, count($aTwoResults['results']));

        $aOneResult = ElasticHelper::aSearch(0, ['q' => 'tree']);
        $this->assertEquals(count($aOneResult['results']), 1);

        // don't return anyone elses results
        $aStrangersResult = ElasticHelper::aSearch(1, ['q' => 'windmill']);
        $this->assertEquals(1, count($aStrangersResult['results']));

        // return 0 if missing user?
        $aStrangersResult = ElasticHelper::aSearch('unknown', ['q' => 'windmill']);
        $this->assertEquals(0, count($aStrangersResult['results']));

        // fuzzy text search
        $aFuzzyTagResult = ElasticHelper::aSearch(0, ['q' => 'trea']);
        $this->assertEquals(1, count($aFuzzyTagResult['results']));
        $aFuzzyAddressResult = ElasticHelper::aSearch(0, ['q' => 'trea']);
        $this->assertEquals(1, count($aFuzzyTagResult['results']));

        // match one word of a two word tag e.g. 'Balvicar' of 'Balvicar Drive'
        $aQueryHalfResult = ElasticHelper::aSearch(0, ['q' => 'Balvicar']);
        $this->assertEquals(1, count($aQueryHalfResult['results']));

        // fuzzy missing a letter e.g. "queens" instead of "Queen's"
        $aFuzzyCommaResult = ElasticHelper::aSearch(0, ['q' => 'queens']);
        $this->assertEquals(1, count($aFuzzyCommaResult['results']));

        // query with spaces e.g. 'queens park'
        $aWithSpaces = ElasticHelper::aSearch(0, ['q' => 'queens park']);
        $this->assertEquals(1, count($aWithSpaces['results']));

        // geo search/filter
        $aGeo = ElasticHelper::aSearch(
            0,
            [
                'filters' =>
                [
                    [
                        'type' => 'geo',
                        'value' => [
                            'lat_min' => 50.01,
                            'lat_max' => 60.73,
                            'lon_min' => -11,
                            'lon_max' => 2,
                            'zoom' => 2
                        ]
                    ]
                ],
                'q' => 'park'
            ]
        );
        
        $this->assertEquals(1, count($aGeo['results']));

        

        // handle an empty geo query (map query but no search term)
        $aEmptyGeo = ElasticHelper::aSearch(
            0,
            [
                'filters' =>
                [
                    [
                        'type' => 'geo',
                        'value' => [
                            'lat_min' => 50.01,
                            'lat_max' => 60.73,
                            'lon_min' => -11,
                            'lon_max' => 2,
                            'zoom' => 2
                        ]
                    ]
                ]
            ]
        );
        $this->assertEquals(1, count($aEmptyGeo['results']));

        // handle a 0 0 0 0 geo query
        $aInvalidZeroGeo = ElasticHelper::aSearch(
            0,
            [
                'filters' =>
                [
                    [
                        'type' => 'geo',
                        'value' => [
                            'lat_min' => 0,
                            'lat_max' => 0,
                            'lon_min' => 0,
                            'lon_max' => 0
                        ]
                    ]
                ]
            ]
        );
        $this->assertEquals(0, count($aInvalidZeroGeo['results']));
        $this->assertEquals('fail', $aInvalidZeroGeo['status']);


        // handle unexpected zoom value
        $aOutOfBoundsZoom = ElasticHelper::aSearch(
            0,
            [
                'filters' =>
                [
                    [
                        'type' => 'geo',
                        'value' => [
                            'lat_min' => -89.99,
                            'lat_max' => 89.97,
                            'lon_min' => -180,
                            'lon_max' => 180,
                            'zoom' => 0
                        ]
                    ]
                ]
            ]
        );
        $this->assertTrue(count($aOutOfBoundsZoom['results']) > 0);


        // handle an inverted geo query - top is below bottom
        $aInvalidInverseGeo = ElasticHelper::aSearch(
            0,
            [
                'filters' =>
                [
                    [
                        'type' => 'geo',
                        'value' => [
                            'lat_min' => 10,
                            'lat_max' => 0,
                            'lon_min' => 0,
                            'lon_max' => 10
                        ]
                    ]
                ]
            ]
        );
        $this->assertEquals(0, count($aInvalidInverseGeo['results']));
        $this->assertEquals('fail', $aInvalidZeroGeo['status']);

        // handle empty query (no results)
        $aEmptyQuery = ElasticHelper::aSearch(
            0,
            []
        );
        $this->assertEquals(0, count($aEmptyQuery['results']));


        // handle query all
        $aEmptyQuery = ElasticHelper::aSearch(
            0,
            [
                'filters' =>
                [
                    ['type' => 'all', 'value' => true]
                ]
            ]
        );
        $this->assertTrue(count($aEmptyQuery['results']) > 0);

        
        // ordered by date asc
        $aDateOrderedAscResults = ElasticHelper::aSearch(
            0,
            [
                'q' => 'city',
                'filters' => [
                    [
                        'type' => 'all',
                        'value' => true
                    ]
                ],
                'sort' => 'date_asc'
            ],
            'default'
        );

        $this->assertEquals(
            [1002, 1003, 1007, 1005, 1006],
            $this->aOrderedIds($aDateOrderedAscResults['results'])
        );

        // ordered by date desc
        $aDateOrderedDescResults = ElasticHelper::aSearch(
            0,
            [
                'q' => 'city',
                'filters' => [
                    [
                        'type' => 'all',
                        'value' => true
                    ]
                ],
                'sort' => 'date_desc'
            ],
            'default'
        );

        $this->assertEquals(
            [1006, 1005, 1007, 1003, 1002],
            $this->aOrderedIds($aDateOrderedDescResults['results'])
        );

        // min confidence
        $aOneResult = ElasticHelper::aSearch(0, ['q' => 'aboveconfidencethreshold']);
        $this->assertEquals(1, count($aOneResult['results']));

        $aEmptyResult = ElasticHelper::aSearch(0, ['q' => 'belowconfidencethreshold']);
        $this->assertEquals(0, count($aEmptyResult['results']));

        // relevance order
        $aRelevanceOrderedResults = ElasticHelper::aSearch(
            0,
            [
                'q' => 'monkey',
                'sort' => 'relevance'
            ],
            'default'
        );

        $this->assertEquals(
            $this->aOrderedIds($aRelevanceOrderedResults['results']),
            [1005, 1002, 1007, 1003, 1006]
        );

        // relevance then date order
        $aRelevanceDateOrderedResults = ElasticHelper::aSearch(
            0,
            [
                'q' => 'giraffe',
                'sort' => 'relevance'
            ],
            'default'
        );

        $this->assertEquals(
            $this->aOrderedIds($aRelevanceDateOrderedResults['results']),
            [1006, 1003, 1005, 1002, 1007]
        );

        // folder aggregations with query, first agg result should be most relevant
        $aNoFolderWithQuery = ElasticHelper::aSearch(
            0,
            ['q' => 'banana'],
            'folder'
        );
        
        $this->assertEquals(1008, $aNoFolderWithQuery['aggs'][0]['file-id']);

        $aNoFolderWithQuery = ElasticHelper::aSearch(
            0,
            ['q' => 'island'],
            'folder'
        );

        $this->assertEquals(1008, $aNoFolderWithQuery['aggs'][0]['file-id']);

        // folder aggregations no query, first agg result should be most recent
        $aFolderNoQuery = ElasticHelper::aSearch(
            0,
            [],
            'folder'
        );
        $this->assertEquals(1009, $aFolderNoQuery['aggs'][0]['file-id']);

        // results contain stuff
        $aAnyResults = ElasticHelper::aSearch(0, ['q' => 'uniquetofiletwo']);

        $this->assertTrue(isset($aAnyResults['results'][0]['id']));
        $this->assertTrue(isset($aAnyResults['results'][0]['datetime']));
        $this->assertTrue(isset($aAnyResults['results'][0]['colour']));

        // return generic searc data
        $aAnyResults = ElasticHelper::aSearch(0, ['q' => 'city']);
        $this->assertTrue(isset($aAnyResults['data']['available']));
        $this->assertTrue(isset($aAnyResults['data']['speed']));


        // shuffle search
        // do three searches, get ids of each, make sure not equal
        $aShuffleAll = ElasticHelper::aSearch(
            0,
            [
                'filters' =>
                [
                    ['type' => 'all', 'value' => true]
                ],
                'sort' => 'shuffle'
            ],
            'search'
        );
        $aOne = $this->aOrderedIds($aShuffleAll['results']);

        $aShuffleAll = ElasticHelper::aSearch(
            0,
            [
                'filters' =>
                [
                    ['type' => 'all', 'value' => true]
                ],
                'sort' => 'shuffle'
            ],
            'search'
        );
        $aTwo = $this->aOrderedIds($aShuffleAll['results']);

        $aShuffleAll = ElasticHelper::aSearch(
            0,
            [
                'filters' =>
                [
                    ['type' => 'all', 'value' => true]
                ],
                'sort' => 'shuffle'
            ],
            'search'
        );
        $aThree = $this->aOrderedIds($aShuffleAll['results']);
        $this->assertTrue(
            $aOne !== $aTwo ||
            $aOne !== $aThree ||
            $aTwo !== $aThree
        );

        //
        // date search; performs differently if on calendar or non-calendar search mode
        //

        //
        // date search-mode - day
        $aDaySearchResults = ElasticHelper::aSearch(
            0,
            [
                'filters' =>
                [
                    [
                        'type' => 'date',
                        'value' => [
                            'mode' => 'day',
                            'start_date' => '05/06/2015'
                        ]
                    ]
                ]
            ],
            'search'
        );
        $this->assertEquals(1, count($aDaySearchResults['results']));

        //
        // date search-mode - week

        $aWeekSearchResults = ElasticHelper::aSearch(
            0,
            [
                'filters' =>
                [
                    [
                        'type' => 'date',
                        'value' => [
                            'mode' => 'week',
                            'start_date' => '01/08/2016'
                        ]
                    ]
                ]
            ],
            'search'
        );
        $this->assertEquals(1, count($aWeekSearchResults['results']));

        //
        // date search-mode - month

        $aMonthSearchResults = ElasticHelper::aSearch(
            0,
            [
                'filters' =>
                [
                    [
                        'type' => 'date',
                        'value' => [
                            'mode' => 'month',
                            'start_date' => '01/08/2016'
                        ]
                    ]
                ]
            ],
            'search'
        );
        $this->assertEquals(7, count($aMonthSearchResults['results']));

        //
        // date calendar-mode - day (same as on search mode)

        // search on day, get lots of results
        $aDayCalendarResults = ElasticHelper::aSearch(
            0,
            [
                'filters' =>
                [
                    [
                        'type' => 'date',
                        'value' => [
                            'mode' => 'day',
                            'start_date' => '18/08/2016'
                        ]
                    ]
                ]
            ],
            'calendar'
        );
        $this->assertEquals(3, count($aDayCalendarResults['results']));

        //
        // date calendar-mode - week

        // assert result count in data as expected
        $aWeekCalendarResults = ElasticHelper::aSearch(
            0,
            [
                'filters' =>
                [
                    [
                        'type' => 'date',
                        'value' => [
                            'mode' => 'week',
                            'start_date' => '08/08/2016'
                        ]
                    ]
                ]
            ],
            'calendar'
        );
        $this->assertEquals(3, $aWeekCalendarResults['data']['available']);
        // assert results 0
        $this->assertEquals(count($aWeekCalendarResults['results']), 0);
        // assert 7 agg buckets, one at least with some files

        $this->assertTrue(isset($aWeekCalendarResults['aggs']));
        $this->assertTrue(count($aWeekCalendarResults['aggs']) === 2);

        $this->assertTrue(isset($aWeekCalendarResults['aggs'][0]['name']));
        $this->assertTrue(isset($aWeekCalendarResults['aggs'][0]['files']));
        $this->assertEquals($aWeekCalendarResults['aggs'][0]['count'], 2);
        $this->assertEquals($aWeekCalendarResults['aggs'][1]['count'], 1);

        //
        // date calendar-mode - month

        $aMonthCalendarResults = ElasticHelper::aSearch(
            0,
            [
                'filters' =>
                [
                    [
                        'type' => 'date',
                        'value' => [
                            'mode' => 'month',
                            'start_date' => '01/08/2016'
                        ]
                    ]
                ]
            ],
            'calendar'
        );

        // assert result count in data as expected
        $this->assertTrue(is_integer($aMonthCalendarResults['data']['available']));
        // assert results 0
        $this->assertEquals(count($aMonthCalendarResults['results']), 0);
        // assert agg bucket for each day, one at least with some files
        $this->assertTrue(isset($aMonthCalendarResults['aggs']));
        $this->assertTrue(count($aMonthCalendarResults['aggs']) === 13);

        $this->assertTrue(is_integer($aMonthCalendarResults['aggs'][0]['count']));
        // there are thumbs
        $this->assertTrue(is_array($aMonthCalendarResults['aggs'][0]['files']));

        //
        // date calendar-mode - year

        $aYearCalendarResults = ElasticHelper::aSearch(
            0,
            [
                'filters' =>
                [
                    [
                        'type' => 'date',
                        'value' => [
                            'mode' => 'year',
                            'start_date' => '01/01/2016'
                        ]
                    ]
                ]
            ],
            'calendar'
        );

        // assert result count in data as expected
        $this->assertTrue(is_integer($aYearCalendarResults['data']['available']));
        // assert results 0
        $this->assertEquals(count($aYearCalendarResults['results']), 0);
        // assert 365 agg buckets, one at least with some files
        $this->assertTrue(isset($aYearCalendarResults['aggs']));
        $this->assertTrue(count($aYearCalendarResults['aggs']) > 1);


        //
        // face search
        //
        /*

        // search gender - man, get specific docs
        $aPeopleGenderMale = ElasticHelper::aSearch(
            0,
            [
                'q' => '',
                'filters' =>
                [
                    [
                        'type' => 'people_gender',
                        'value' => 'male'
                    ]
                ]
            ],
            'people'
        );
        $aPeopleGenderMaleIds = $this->aOrderedIds($aPeopleGenderMale['results']);
        $this->assertTrue(in_array('one', $aPeopleGenderMaleIds));


        // don't get woman
        $this->assertTrue(!in_array('two', $aPeopleGenderMaleIds));

        // search woman, get specifc docs
        $aPeopleGenderFemale = ElasticHelper::aSearch(
            0,
            [
                'q' => '',
                'filters' =>
                [
                    [
                        'type' => 'people_gender',
                        'value' => 'female'
                    ]
                ]
            ],
            'people'
        );
        $aPeopleGenderFemaleIds = $this->aOrderedIds($aPeopleGenderFemale['results']);
        $this->assertTrue(in_array('two', $aPeopleGenderFemaleIds));


        // don't get man only
        $this->assertTrue(!in_array('three', $aPeopleGenderFemaleIds));

        // search specific mood
        $aPeopleEmotionAngry = ElasticHelper::aSearch(
            0,
            [
                'q' => '',
                'filters' =>
                [
                    [
                        'type' => 'people_emotion',
                        'value' => 'angry'
                    ]
                ]
            ],
            'people'
        );
        $aPeopleEmotionAngryIds = $this->aOrderedIds($aPeopleEmotionAngry['results']);
        $this->assertTrue(in_array('three', $aPeopleEmotionAngryIds));

        // search grouping - single
        $aPeopleNumberSingle = ElasticHelper::aSearch(
            0,
            [
                'q' => '',
                'filters' =>
                [
                    [
                        'type' => 'people_number',
                        'value' => 'single'
                    ]
                ]
            ],
            'people'
        );
        $aPeopleNumberSingleIds = $this->aOrderedIds($aPeopleNumberSingle['results']);
        // only one person in these pics
        $this->assertTrue(in_array('two', $aPeopleNumberSingleIds));
        $this->assertTrue(in_array('three', $aPeopleNumberSingleIds));
        // multiple in next pic
        $this->assertTrue(!in_array('one', $aPeopleNumberSingleIds));

        // search grouping - group
        $aPeopleNumberGroup = ElasticHelper::aSearch(
            0,
            [
                'q' => '',
                'filters' =>
                [
                    [
                        'type' => 'people_number',
                        'value' => 'group'
                    ]
                ]
            ],
            'people'
        );
        $aPeopleNumberGroupIds = $this->aOrderedIds($aPeopleNumberGroup['results']);
        // group pic
        $this->assertTrue(in_array('one', $aPeopleNumberGroupIds));
        // single person pics
        $this->assertTrue(!in_array('two', $aPeopleNumberGroupIds));
        $this->assertTrue(!in_array('three', $aPeopleNumberGroupIds));

        // TODO: sort order of mood
        */



        // // colour search
        // $this->assertTrue(false);
        //
        // // fingerprint/phash search
        // $this->assertTrue(false);
        //
        // // no results if public user searching a private user
        // $this->assertTrue(false);
        //
        // // and search, e.g. mountain lake should match pics on both mountain and lake
        // $this->assertTrue(false);

    }

    public function testHomeAggs()
    {
        $aHomeAggs = ElasticHelper::aHomeAggs(666);

        $this->assertTrue(isset($aHomeAggs['on_this_day']));
        $this->assertEquals(1, count($aHomeAggs['on_this_day']['5_years_ago']));
        $this->assertEquals(1, count($aHomeAggs['on_this_day']['4_years_ago']));
        $this->assertEquals(3, count($aHomeAggs['on_this_day']['3_years_ago']));
        $this->assertEquals(1, count($aHomeAggs['on_this_day']['2_years_ago']));
        $this->assertEquals(2, count($aHomeAggs['on_this_day']['1_year_ago']));
    }

    private function aOrderedIds($aHits, $sId = 'id')
    {
        $saOrderedResults = [];
        foreach ($aHits as $aHit) {
            array_push($saOrderedResults, $aHit[$sId]);
        }
        return $saOrderedResults;
    }
}
