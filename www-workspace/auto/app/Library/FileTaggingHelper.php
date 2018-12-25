<?php

namespace App\Library;

use App\Library\Helper;
use App\Library\DropboxHelper;

use App\Models\DropboxFilesource;
use Share\DropboxFiles;
use App\Models\PiciliFile;

use Carbon\Carbon;
use Jenssegers\ImageHash\ImageHash;

class FileTaggingHelper {

	private static function importDropboxFile($iDropboxId, $sUserAccessToken, $sTempFilename)
    {
        // copy file locally to storage/processing folder
        try{


            if(isset($iDropboxId) && isset($sUserAccessToken))
            {
                $data = array("path" => $iDropboxId);
                $data_string = json_encode($data);

                $aHeaders = [
                    'Authorization' => 'Bearer '.$sUserAccessToken,
                    'Dropbox-API-Arg' => $data_string
                ];

                $sUrl = "https://content.dropboxapi.com/2/files/download";


                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL,$sUrl);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

                curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'Authorization: Bearer '.$sUserAccessToken,
                    'Content-Type: ',
                    'Dropbox-API-Arg: ' . $data_string
                ));

                $result = curl_exec ($curl);

                if(curl_errno($curl))
                {
                    echo 'error:' . curl_error($curl);
                    $bReturn = false;
                }else{
                    // we got a file back?
                    $my_file = self::sTempFilePath($sTempFilename);

                    $handle = fopen($my_file, 'w');
                    fwrite($handle, $result);


                }

                curl_close ($curl);

                $oObj = json_decode($result);

                if(isset($oObj->error_summary))
                {
                    echo "error";
                    $bReturn = false;
                }
                $bReturn = true;
            }else{
                echo "things not set";
                $bReturn = false;
            }
        } catch(Exception $e)
        {
            echo $e;
            Helper::logError(
                "DropboxImportProcessor",
                "exception",
                [
                    "function" => "process",
                    "exception_message" => $e
                ]
            );
            $bReturn = false;
        }
        return $bReturn;
    }

	private static function sTempFilePath($sTempFilename)
	{
		return public_path().DIRECTORY_SEPARATOR.'processing'.DIRECTORY_SEPARATOR.$sTempFilename;
	}

	public static function checkFileSource(DropboxFilesource $oFileSource)
    {

        if(isset($oFileSource) && isset($oFileSource->user_id) && isset($oFileSource->access_token))
        {
            $sFolder = $oFileSource->folder;
            $sToken = $oFileSource->access_token;

            // get all files from dropbox folder
            $oDropboxFilesByKey = self::getCompleteDropboxFolderContents($sFolder, $sToken);

            // get files from db - for a dropbox folder
            # change to get all files in db for user
            # and then change back but limit user to one file source
            $aoDatabaseFiles = ($oFileSource->dropboxFiles !== null ? $oFileSource->dropboxFiles : []);
            $aDatabaseFiles = [];


            // turn relation object into array of objects with path as key
            foreach ($aoDatabaseFiles as $oDBFile) {
                $aDatabaseFiles[$oDBFile->dropbox_path] = $oDBFile->toArray();
            }

            echo "\ndb files: ", count($aDatabaseFiles);

            echo "\ndropbox files: ", count($oDropboxFilesByKey);


            $aaDropboxFileSystemChanges = self::findDifferenceInDropboxFileSystem($aDatabaseFiles, $oDropboxFilesByKey);



            $saNewFilesForSystem = $aaDropboxFileSystemChanges['new'];
            $saLostFilesFromSystem = $aaDropboxFileSystemChanges['deleted'];
            $saChangedFilesFromSystem = $aaDropboxFileSystemChanges['changed'];


            echo "\nnew: ", count($saNewFilesForSystem);

            // print_r($saNewFilesForSystem);


            echo "\nlost: ", count($saLostFilesFromSystem);
			// print_r($saLostFilesFromSystem);


            echo "\nchanged: ", count($saChangedFilesFromSystem);


            //
            // process new files
            //
            foreach ($saNewFilesForSystem as $sNewFileKey) {
                $oNewDropboxFile = $oDropboxFilesByKey[$sNewFileKey];

                //print_r($oNewDropboxFile);die();

                $oDBXFL = new \Share\DropboxFiles;

                $oDBXFL->dropbox_id = $oNewDropboxFile['id'];
                $oDBXFL->dropbox_path = $oNewDropboxFile['path_lower'];
                $oDBXFL->dropbox_name = $oNewDropboxFile['name'];
                $oDBXFL->server_modified = $oNewDropboxFile['server_modified'];
                $oDBXFL->ufo = true;

                $oDBXFL->dropbox_folder_id = $oFileSource->id;
                $oDBXFL->save();

				/*
                $oGenericFile = new File;
                $oGenericFile->user_id = $oUser->id;
                $oGenericFile->file_source_type_id = 0; # 0 = dropbox?
                $oGenericFile->file_source_id = $oDBXFL->id;
                $oGenericFile->save();

                self::initialDropboxFileDiscovery($oGenericFile, $oDBXFL);
				*/

            }


			/*
            $oFolder->load('status');
            $oFileSourceStatus = $oFolder->status;



            if(count($saNewFilesForSystem) > 0)
            {
                $oEvent = new Event;
                $oEvent->event_type_id = 0; # 0 = files found?
                $oEvent->user_id = $oUser->id;
                $oEvent->message = "found ". count($saNewFilesForSystem). " files from dropbox";
                $oEvent->save();


                $oFileSourceStatus->last_pulled = count($saNewFilesForSystem);
            }

            $oFileSourceStatus->save();
			*/



            #print_r($saChangedFilesFromSystem);die();

            //
            // process changed files
            //
			/*
            foreach ($saChangedFilesFromSystem as $sKeyPath => $oValueDropboxFileArray) {
                // get the file that has changed

                $oDropboxFileToUpdate = DropboxFile::with('file')->where("dropbox_path", $sKeyPath)->first();


                $oDropboxFileToUpdate->server_modified = $oValueDropboxFileArray['server_modified'];

                $oDropboxFileToUpdate->save();

                self::initialDropboxFileDiscovery($oDropboxFileToUpdate->file, $oDropboxFileToUpdate);

                //print_r($oChangedFile);die();
            }
			/*

            //
            // process lost files
            //

            /* 'turn off' file deleting since there is a bug,
            files not being removed from elastic and maybe
            shouldn't have been anyway. no logging, a bit iffy.*/

            /*
            foreach ($saLostFilesFromSystem as $sKey) {

                // totally delete the file and everything to do with it

                $oDropboxFileToRemove = DropboxFile::with(['file', 'file.tags', 'file.queueItems'])->where('dropbox_path', $sKey)->first();


                echo "lost: ", $sKey;
                //$oLostFile = $aDatabaseFiles[$sKey];




                # elastic index
                $hosts = [env('ELASTICSEARCH')];

                $clientBuilder = ClientBuilder::create();
                $clientBuilder->setHosts($hosts);
                $client = $clientBuilder->build();

                $params = [
                    'index' => env('ELASTIC_INDEX'),
                    'type' => 'file',
                    'body' => [
                        'query' => [
                            'match' => [
                                'id' => $oDropboxFileToRemove->file->id
                            ]
                        ]
                    ]
                ];

                // Delete doc at /my_index/my_type/my_id
                $response = $client->deleteByQuery($params);


                # aws things
                if($oDropboxFileToRemove->file->bThumbs)
                {
                    $s3 = \Storage::disk('s3');
                    $sBaseThumbPath = '/t/'. $oDropboxFileToRemove->file->user_id .'/';

                    $saSizes = ['i', 's', 'm', 'l', 'xl'];

                    foreach ($saSizes as $key => $value) {
                        $sPotentialThumbPath = $sBaseThumbPath.$value.$oDropboxFileToRemove->file->id.'.jpg';

                        if(\Storage::disk('s3')->exists($sPotentialThumbPath))
                        {
                            \Storage::disk('s3')->delete($sPotentialThumbPath);
                        }else{echo "didnt' exist: ", $sPotentialThumbPath, "<br/>";}
                    }
                }else{
                    echo "no thumbs";
                }





                # tags
                echo "<br/>tags: ", count($oDropboxFileToRemove->file->tags);
                $oDropboxFileToRemove->file->tags()->delete();
                # queue item
                echo "<br/>queuee: ", count($oDropboxFileToRemove->file->queueItems);
                $oDropboxFileToRemove->file->queueItems()->delete();

                # file
                $oDropboxFileToRemove->file()->delete();
                # dropbox file
                $oDropboxFileToRemove->delete();


            }
            */

            # bulk elastic delete

            return true;

        }else{
            echo "checkFileSource: folder/user/token etc not found";

            Helper::logRequiresAttention(
                [
                    "file_source_id" => $iFileSourceId,
                    "file" => "DropboxImportProcessor",
                    "method" => "checkFileSource",
                    "message" => "folder/user/token etc not found"
                ]
            );
            return true;
        }
    }

    public static function mPullAllDropbox($sFolderPath, $sToken)
    {
        $oaFiles = self::getCompleteDropboxFolderContents($sFolderPath, $sToken);
		echo count($oaFiles);

		foreach($oaFiles as $oDropboxFile)
		{
			//print_r($oDropboxFile);

			if(isset($oDropboxFile['.tag']))
			{
				if($oDropboxFile['.tag'] !== 'folder'){

					$data = [
						'name' => $oDropboxFile['name'],
						'path_lower' => $oDropboxFile['path_lower'],
						'id' => $oDropboxFile['id'],
						'date_found' => \Carbon\Carbon::now()
					];

					\DB::collection('dropbox_files')->where('path_lower', $oDropboxFile['path_lower'])
                       ->update($data, ['upsert' => true]);
				}
			}else{
				echo "not set\n";
			}
		}
    }

	private static function getCompleteDropboxFolderContents($sFolderPath, $sOAuthToken, $bFoldersOnly = false)
    {
        // recursively build list of files
        ini_set('max_execution_time', 600); //300 seconds = 5 minutes

        $time_pre = microtime(true);

        $oaEntries = [];
        $oaDropboxFilesByKey = [];

        $bComplete = false;
        $sCursor = '';
        $iReqs = 0;

        while(!$bComplete)
        {
            $oaNewEntries = self::listDropboxFolderContents($sFolderPath, $sOAuthToken, $sCursor);
            $iReqs++;

            echo $iReqs, '<br/>', count($oaNewEntries['entries']), '<hr/>';

            if(isset($oaNewEntries['has_more']) && filter_var($oaNewEntries['has_more'], FILTER_VALIDATE_BOOLEAN))
            {
                $bComplete = false;
                $sCursor = $oaNewEntries['cursor'];
            }else{
                $bComplete = true;
            }
            $oaEntries = array_merge($oaEntries, $oaNewEntries['entries']);

            #echo $iReqs;
            /*
            if($iReqs > 3)
                die();*/
        }
        #echo 'final count', count($oaNewEntries['entries']), '<hr/>';

        #echo $oaNewEntries['entries']
        if(isset($oaEntries))
            foreach ($oaEntries as $oDropboxItem) {
                if($oDropboxItem->{'.tag'} !== 'folder' && $bFoldersOnly === false){
                    $oaDropboxFilesByKey[$oDropboxItem->path_lower] = (array)$oDropboxItem;
                }
                if($oDropboxItem->{'.tag'} === 'folder' && $bFoldersOnly){
                    array_push($oaDropboxFilesByKey, $oDropboxItem->path_lower);
                }
            }



        $time_post = microtime(true);
        $exec_time = $time_post - $time_pre;

        #echo "count: ", count($oaEntries), ", in ", $exec_time, ", over ",$iReqs, "reqs<hr/>";

        #print_r($oaDropboxFilesByKey);
        return $oaDropboxFilesByKey;
    }

	private static function listDropboxFolderContents($sFolderPath, $sOAuthToken, $sCursor = '')
    {
        // get all files from folder
        try{

            $aHeaders = [
                'Authorization' => 'Bearer '.$sOAuthToken,
                'Content-Type' => 'application/json'
            ];


            $data = array("path" => $sFolderPath, "recursive" => true, "include_media_info" => false);

            $sUrl = "https://api.dropboxapi.com/2/files/list_folder";

            if($sCursor !== '')
            {
                // continue from last time instead

                $data = array("cursor" => $sCursor);

                $sUrl = "https://api.dropboxapi.com/2/files/list_folder/continue";
            }


            $data_string = json_encode($data);


            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL,$sUrl);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
            );

            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer '.$sOAuthToken,
                'Content-Type: application/json'
            ));

            $result = curl_exec ($curl);

            if(curl_errno($curl))
            {
                echo 'error:' . curl_error($curl);
                Helper::logError(
                    "FileSourcesController",
                    "curl errors talking to dropbox",
                    [
                        "function" => "listDropboxFolderContents"
                    ]
                );
                die("curl error so shut down");
            }

            curl_close ($curl);

            $oObj = json_decode($result);

            #echo "result: ";
            #print_r($oObj);
            #echo "END";

            if(isset($oObj->error_summary))
            {
                echo "error";
                Helper::logError(
                    "FileSourcesController",
                    "dropbox request errors",
                    [
                        "function" => "listDropboxFolderContents",
                        "summary" => (string)$oObj->error_summary
                    ]
                );
                return [];
            }
            if(isset($oObj->entries))
            {
                #echo "yup";

                //echo $result;
                return ['entries' => $oObj->entries, 'cursor' => $oObj->cursor, 'has_more' => $oObj->has_more];
                //return $oObj->entries;
            }else{
                return ['entries' => [], 'has_more' => false];
            }
        } catch(Exception $e)
        {
            echo $e;
            Helper::logError(
                "FileSourcesController",
                "exception",
                [
                    "function" => "listDropboxFolderContents",
                    "exception_message" => $e
                ]
            );
            die();
        }
    }

	public static function findDifferenceInDropboxFileSystem($aoDatabaseFilesByKey, $aoDropboxFilesByKey)
    {
        /*
        take array of dropbox files and array of db files, compare each to produce three lists; new, deleted, changed
        */

        $saDropboxFilesPathKeys = array_keys($aoDropboxFilesByKey);
        $saDatabaseFilesPathKeys = array_keys($aoDatabaseFilesByKey);


        $saDatabaseFiles = [];

        /*// compare each as file path arrays?
        echo "<br/>files in db #: ", count($aoDatabaseFilesByKey);

        echo "dropbox files<br/>";
        print_r($aoDropboxFilesByKey);
        echo "database files<br/>";
        print_r($aoDatabaseFilesByKey);die();
        */
        # new files
        #$saNewFilesForSystem = array_diff(array_keys($saDropboxFiles), array_keys($saDatabaseFiles));
        $saNewFilesForSystem = array_diff($saDropboxFilesPathKeys, $saDatabaseFilesPathKeys);

        //print_r($saNewFilesForSystem);die();

        # deleted files
        $saLostFilesFromSystem = array_diff($saDatabaseFilesPathKeys, $saDropboxFilesPathKeys);

        # changed files

        // create two new structures, one for database files and dropbox files, arrays of files but with a key made by combining fileid and file server modified date

        $saDatabaseFilesByIdDateKey = [];
        $saDropboxFilesByIdDateKey = [];

        // get paths of files in both places
        $asFilesInBothDropboxAndDatabase = array_intersect($saDropboxFilesPathKeys, $saDatabaseFilesPathKeys);

        $saDatabaseFilesByIdDateKey = [];
        $saDropboxFilesByIdDateKey = [];

        foreach ($asFilesInBothDropboxAndDatabase as $sPathOfFileInBoth) {
            // add it to two lists, with timestamp id for each source
            $oDatabaseFile = $aoDatabaseFilesByKey[$sPathOfFileInBoth];

            $saDatabaseFilesByIdDateKey[$oDatabaseFile['dropbox_id'] . '____' . $oDatabaseFile['server_modified']] = $oDatabaseFile;

            $oDropboxFile = $aoDropboxFilesByKey[$sPathOfFileInBoth];

            $saDropboxFilesByIdDateKey[$oDropboxFile['id'] . '____' . $oDropboxFile['server_modified']] = $oDropboxFile;
        }


        $saChangedFilesFromSystem = array_diff(
            array_keys($saDropboxFilesByIdDateKey),
            array_keys($saDatabaseFilesByIdDateKey)
        );

        $aoChangedFilesFiles = [];
        foreach ($saChangedFilesFromSystem as $key => $value)
        {
            $aoChangedFilesFiles[$saDropboxFilesByIdDateKey[$value]['path_lower']] = $saDropboxFilesByIdDateKey[$value];
        }


        return [
            'new' => $saNewFilesForSystem,
            'deleted' => $saLostFilesFromSystem,
            'changed' => $aoChangedFilesFiles
        ];
    }

	public static function aExifDataFromImagePath($sPath)
	{
		$maAllData = @exif_read_data($sPath);
		$aReturnExifData = [];

        // parse out the properties we want, if they exist
		if(isset($maAllData["Make"]))
        {
			$aReturnExifData['cameramake'] = $maAllData["Make"];
        }
		if(isset($maAllData["Model"]))
        {
			$aReturnExifData['cameramodel'] = $maAllData["Model"];
        }

		if(isset($maAllData["DateTimeOriginal"]))
        {
			$aReturnExifData['datetime'] = $maAllData["DateTimeOriginal"];
        }elseif(isset($maAllData['DateTime'])) {
			$aReturnExifData['datetime'] = $maAllData["DateTime"];            
        }
		if(isset($maAllData["Orientation"]))
        {
			switch($maAllData['Orientation']) {
		        case 1:
		        case 2:
		        case 3:
				case 4:
		            $aReturnExifData['orientation'] = 'landscape';
		            break;
		        case 5:
		        case 6:
		        case 7:
				case 8:
		            $aReturnExifData['orientation'] = 'portrait';
		            break;
    		}
        }

        //
        // geo
        //

		// lat / lon
        if(
			isset($maAllData["GPSLongitude"]) &&
			isset($maAllData["GPSLongitudeRef"]) &&
			isset($maAllData["GPSLatitude"]) &&
			isset($maAllData["GPSLatitudeRef"])
		)
        {
            // if geo, store it on file, and schedule for geo related processors
            $lon = Helper::getGps($maAllData["GPSLongitude"], $maAllData['GPSLongitudeRef']);
            $lat = Helper::getGps($maAllData["GPSLatitude"], $maAllData['GPSLatitudeRef']);

            if($lat >= -90 && $lat <= 90){
			    $aReturnExifData['latitude'] = $lat;
            }
            if($lon >= -180 && $lon <= 180){
			    $aReturnExifData['longitude'] = $lon;
            }
        }

		// altitude
		if(
			isset($maAllData["GPSAltitude"])
		)
        {
            // if geo, store it on file, and schedule for geo related processors
			$aReturnExifData['altitude'] = Helper::fAltitudeExifStringToNumber($maAllData["GPSAltitude"]);
        }

		return $aReturnExifData;
	}
}
