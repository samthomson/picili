<?php

namespace SharedLibrary;

use Carbon\Carbon;


use Shared\PiciliFile;
use Shared\Task;
// use App\Models\Log;

use Aws\Rekognition\RekognitionClient;

use Elasticsearch\ClientBuilder;

class ElasticHelper {

    public static function bDeleteIndex()
    {
        $client = \Elasticsearch\ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOSTS')])->build();
        // delete old index..
        try{
            $params = ['index' => env('ELASTIC_INDEX')];
            if($client->indices()->exists($params))
            {
                $client->indices()->delete($params);
                return true;
            }
        }catch(Exception $e){echo $e;}

        return false;
    }

    public static function bCreateAndPutMapping()
    {
        // creates the index and puts a mapping, only if the index was not created

        // first delete any old index
        self::bDeleteIndex();

        $params = [
            'index' => env('ELASTIC_INDEX'),
            'body' => [
                'mappings' => [
                    "file" => [
                        "properties" => [
                            "user_id" => [
                                "type" => "string",
                                "index" => "not_analyzed"
                            ],
                            "parent-path" => [
                                "type" => "string",
                                "index" => "not_analyzed"
                            ],
                            "medium_width" => [
                                "type" => "integer",
                                "index" => "not_analyzed"
                            ],
                            "medium_height" => [
                                "type" => "integer",
                                "index" => "not_analyzed"
                            ],
                            'datetime' => [
                                'type' => 'date',
                                'format' => 'yyyy-MM-dd HH:mm:ss'
                            ],
                            "tags" => [
                                "type" => "nested",
                                "properties" => [
                                    "type" => [
                                        "type" => "text"
                                    ],
                                    "value" => [
                                        "type" => "text",
                                        "index" => "analyzed"
                                    ],
                                    "confidence" => [
                                        "type" => "integer"
                                    ]
                                ]
                            ],
                            "location" => [
                                "type" => "geo_point"
                            ]
                        ]
                    ]
                ],
                'settings' => [
                    "number_of_shards" =>   1
                ]
            ]
        ];

        // Create the index
        $client = \Elasticsearch\ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOSTS')])->build();

        $response = $client->indices()->create($params);
        // echo json_encode($response);

        return true;
    }
    

    public static function bDeleteFromElastic($iPiciliFileId)
    {
        try
        {
            $client = \Elasticsearch\ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOSTS')])->build();

            $aDeleteParams = [
                'index' => env('ELASTIC_INDEX'),
                'type' => 'file',
                'id' => $iPiciliFileId
            ];

            $oResponse = $client->delete($aDeleteParams);
            // todo, was it succesful? return true/false accordingly, not blindly
            // logger($oResponse);

            $bResponse = false;
            if (isset($oResponse['result']))
            {
                // logger($oResponse['result']);
                $bResponse = $oResponse['result'] === 'deleted';
            }
        
            // logger("returning {$bResponse}");
            return $bResponse;
        }catch(\Elasticsearch\Common\Exceptions\Missing404Exception $e)
        {
            return false;
        }
    }
    public static function mGetDocument($sPiciliFileId)
    {
        $oReturn = null;
        try
        {
            $aGetDocParams = [
                'index' => env('ELASTIC_INDEX'),
                'type' => 'file',
                'id' => $sPiciliFileId
            ];        

            $client = \Elasticsearch\ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOSTS')])->build();
            return $client->get($aGetDocParams)['_source'];
        }
        catch(\ElasticSearch\Common\Exceptions\Missing404Exception $e)
        {
            return null;
        }
    }
    public static function bSaveFileToElastic($oPiciliFile, $sIndexToUse = null)
    {
        $iConfidenceThreshold = env('CONFIDENCE_THRESHOLD');
        if(is_null($sIndexToUse))
        {
            $sIndexToUse = env('ELASTIC_INDEX');
        }
        if(
            $oPiciliFile->aSources() > 0 && 
            $oPiciliFile->bHasThumbs && 
            !$oPiciliFile->bDeleted
        )
        {
            $oDocumentBody = [];

            if(isset($oPiciliFile->medium_width))
            {
                $oDocumentBody['medium_width'] = $oPiciliFile->medium_width;
            }

            if(isset($oPiciliFile->medium_height))
            {
                $oDocumentBody['medium_height'] = $oPiciliFile->medium_height;
            }

            $oDocumentBody['user_id'] = $oPiciliFile->user_id;

            $aTags = [];
            
            foreach($oPiciliFile->tags as $oTag)
            {

                // todo - build selective list of opencage properties we're interested in
                if (
                    $oTag->confidence > $iConfidenceThreshold && 
                    $oTag->type !== 'colour'
                ) 
                {
                    array_push($aTags,
                        [
                            'type' => $oTag->type,
                            'value' => $oTag->value,
                            'confidence' => $oTag->confidence
                        ]
                    );
                } elseif ($oTag->type === 'colour' && $oTag->subtype === 'best') {
                    // split tag value into rgb array
                    $aCompositeColour = explode('.', $oTag->value);
                    
                    $oDocumentBody['r'] = $aCompositeColour[0];
                    $oDocumentBody['g'] = $aCompositeColour[1];
                    $oDocumentBody['b'] = $aCompositeColour[2];
                }
            }

            //
            // physical file stuff
            //
            if(
                isset($oPiciliFile->bInFolder) && 
                $oPiciliFile->bInFolder && 
                isset($oPiciliFile->sParentPath) && 
                isset($oPiciliFile->baseName) && 
                isset($oPiciliFile->extension)
            )
            {
                $oDocumentBody['parent-path'] = $oPiciliFile->sParentPath;

                array_push($aTags,
                    [
                        'type' => 'basename',
                        'value' => $oPiciliFile->baseName,
                        'confidence' => 80
                    ],
                    [
                        'type' => 'extension',
                        'value' => $oPiciliFile->extension,
                        'confidence' => 80
                    ]
                );
            }

            if(isset($oPiciliFile->address))
            {
                $oDocumentBody['address'] = $oPiciliFile->address;
            }

            if(isset($oPiciliFile->signature))
            {
                $oDocumentBody['signature'] = $oPiciliFile->signature;
            }

            if(count($aTags) > 0)
            {
                $oDocumentBody['tags'] = $aTags;
            }

            if($oPiciliFile->bHasGPS)
            {
                $oDocumentBody['location'] = $oPiciliFile->latitude . ',' . $oPiciliFile->longitude;
                $oDocumentBody['latitude'] = $oPiciliFile->latitude;
                $oDocumentBody['longitude'] = $oPiciliFile->longitude;
            }

            if($oPiciliFile->bHasAltitude)
            {
                $oDocumentBody['altitude'] = $oPiciliFile->altitude;
            }

            if($oPiciliFile->datetime)
            {
                $oDocumentBody['datetime'] = Carbon::parse($oPiciliFile->datetime)->toDateTimeString();
                // $oDocumentBody['datetime'] = $oPiciliFile->datetime['date'];
            }

            $params = [
                'index' => $sIndexToUse,
                'type' => 'file',
                'id' => $oPiciliFile->id,
                'body' => $oDocumentBody
            ];

            try
            {
                $client = ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOSTS')])->build();
                $response = $client->index($params);
                return true;
            } 
            catch(\Elasticsearch\Common\Exceptions\NoNodesAvailableException $ex)
            {
                logger("elasticsearch was offline, couldn't save file {$$oPiciliFile->id}");
                return false;
            }
        }else{
            // echo "skippping, no thumbs";
            return false;
        }
    }
}
