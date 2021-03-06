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
        $client = \Elasticsearch\ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOST')])->build();
        // delete old index..
        try{
            $params = ['index' => env('ELASTICSEARCH_INDEX')];
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
            'index' => env('ELASTICSEARCH_INDEX'),
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
                            ],
                            "altitude" => [
                                "type" => "scaled_float",
                                "scaling_factor" => 1000
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
        $client = \Elasticsearch\ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOST')])->build();

        $response = $client->indices()->create($params);
        // echo json_encode($response);

        return true;
    }
    

    public static function bDeleteFromElastic($iPiciliFileId)
    {
        try
        {
            $client = \Elasticsearch\ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOST')])->build();

            $aDeleteParams = [
                'index' => env('ELASTICSEARCH_INDEX'),
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
            // check if file even exists. if it doesn't, then 'deleting' is redundant and we can return a success - the result is the same, file does not exist
            $mFileExists = self::mGetDocument($iPiciliFileId);
            if ($mFileExists === NULL) {
                // null response
                return true;
            }
            return false;
        }
    }
    public static function mGetDocument($sPiciliFileId)
    {
        $oReturn = null;
        try
        {
            $aGetDocParams = [
                'index' => env('ELASTICSEARCH_INDEX'),
                'type' => 'file',
                'id' => $sPiciliFileId
            ];        

            $client = \Elasticsearch\ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOST')])->build();
            return $client->get($aGetDocParams)['_source'];
        }
        catch(\ElasticSearch\Common\Exceptions\Missing404Exception $e)
        {
            return null;
        }
    }
    public static function bSaveFileToElastic($oPiciliFile, $sIndexToUse = null)
    {
		if(is_null($sIndexToUse))
        {
            $sIndexToUse = env('ELASTICSEARCH_INDEX');
        }
		
		if(
            $oPiciliFile->aSources() > 0 && 
            $oPiciliFile->bHasThumbs && 
            !$oPiciliFile->bDeleted
        )
        {
            $oDocumentBody = self::getDocumentForElastic($oPiciliFile);
			
            $params = [
                'index' => $sIndexToUse,
                'type' => 'file',
                'id' => $oPiciliFile->id,
                'body' => $oDocumentBody
            ];

			$oPiciliFile = null;
			$oDocumentBody = null;
			unset($oPiciliFile);
			unset($oDocumentBody);

			$sIndexToUse = null;
			unset($sIndexToUse);

            try
            {
                $client = ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOST')])->build();
                $response = $client->index($params);

				$client = null;
				$params = null;
				$response = null;
				unset($client);
				unset($params);
				unset($response);
                return true;
            } 
            catch(\Elasticsearch\Common\Exceptions\NoNodesAvailableException $ex)
            {
                logger("elasticsearch was offline, couldn't save file {$oPiciliFile->id}");
                return false;
            }
        }else{
            // echo "skippping, no thumbs";
            return false;
        }
	}

	private static function getDocumentForElastic($oPiciliFile) {

		$oDocumentBody = [];
        $iConfidenceThreshold = env('SEARCH_CONFIDENCE_THRESHOLD');


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
				
				$aCompositeColour = null;
				unset($aCompositeColour);
			}
			
			$oTag = null;
			unset($oTag);

			// $oPiciliFile->tags = null;
			unset($oPiciliFile->tags);
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
			$aTags = null;
			unset($aTags);
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

		return $oDocumentBody;
	}

	public static function bBatchSaveToElastic($aoFiles)
	{
		try
		{
			$client = ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOST')])->build();
			
			
			$params = ['body' => []];
			$sIndex = env('ELASTICSEARCH_INDEX');
			gc_enable();

			for ($i = 0; $i <= count($aoFiles); $i++) {
				$params['body'][] = [
					'index' => [
						'_index' => $sIndex,
						'_type' => 'file',
						'_id' => $aoFiles[$i]->id
					]
				];
			
				$params['body'][] = self::getDocumentForElastic($aoFiles[$i]);
			
				// Every 1000 documents stop and send the bulk request
				if ($i % 1000 == 0) {
					$responses = $client->bulk($params);
			
					// erase the old bulk request
					$params = ['body' => []];
			
					// unset the bulk response when you are done to save memory
					$responses = null;
					unset($responses);


					echo "\n\nmem used at $i :", (memory_get_usage(true) / 1024 / 1024).' mb';
				}

				if ($i > 0 && $i % 5000 === 0) {
					return;
				}
				gc_collect_cycles();
			}
			
			// Send the last batch if it exists
			if (!empty($params['body'])) {
				$responses = $client->bulk($params);
			}
			

			$response = $client->bulk($params);

			// $client = null;
			// $params = null;
			// unset($client);
			// unset($params);
			$response = null;
			unset($response);
			return true;
		} 
		catch(\Elasticsearch\Common\Exceptions\NoNodesAvailableException $ex)
		{
			logger("elasticsearch was offline, couldn't save batch of files");
			return false;
		}
	}
	
	// public static function printMem($iLocation) {
	// 	$iMem = memory_get_usage();
	// 	if ($iMem === 0) {
	// 		echo '0';
	// 	}
	// 	if (false) {
	// 		echo "\n".$iLocation,': ', ($iMem / 1024).' kb, '.($iMem / 1024 / 1024).' mb';
	// 	}

	// 	$iMem = null;
	// 	unset($iMem);
	// }
}
