<?php

namespace App\Library;

use App\Library\Helper;
use SharedLibrary\TagHelper;


use Share\DropboxFilesource;
use Share\DropboxFiles;
use Share\PiciliFile;
use Share\Task;

use Carbon\Carbon;

class DropboxHelper {

	public static function checkFileSource($iDropboxSourceId)
    {
		$oFileSource = DropboxFilesource::find($iDropboxSourceId);
        $bSuccess = false;
        $aError = null;

        $oFileSource->user->dropboxToken->access_token;

        if(
            isset($oFileSource) && 
            isset($oFileSource->folder) && 
            isset($oFileSource->user->dropboxToken->access_token)
        )
        {
            $sFolder = $oFileSource->folder;
            $sToken = $oFileSource->user->dropboxToken->access_token;

            // get all files from dropbox folder

            $aRequestDropboxFiles = self::getCompleteDropboxFolderContents($sFolder, $sToken);

            $oDropboxFilesByKey = [];
            if($aRequestDropboxFiles['success'])
            {
                $bSuccess = true;
                $oDropboxFilesByKey = $aRequestDropboxFiles['dropboxFilesByKey'];

                // get files from db - for a dropbox folder
                # change to get all files in db for user
                # and then change back but limit user to one file source
                $aoDatabaseFiles = ($oFileSource->dropboxFiles !== null ? $oFileSource->dropboxFiles : []);
                $aDatabaseFiles = [];

                // turn relation object into array of objects with path as key
                foreach ($aoDatabaseFiles as $oDBFile) {
                    $aDatabaseFiles[$oDBFile->dropbox_path] = $oDBFile->toArray();
                }

                $aaDropboxFileSystemChanges = self::findDifferenceInDropboxFileSystem($aDatabaseFiles, $oDropboxFilesByKey);

                $saNewFilesForSystem = $aaDropboxFileSystemChanges['new'];
                $saLostFilesFromSystem = $aaDropboxFileSystemChanges['deleted'];
                $saChangedFilesFromSystem = $aaDropboxFileSystemChanges['changed'];

                $iUserId = $oFileSource->user_id;

                $cNewFiles = count($saNewFilesForSystem);
                $cChangedFiles = count($saChangedFilesFromSystem);
                $cLostFiles = count($saLostFilesFromSystem);

                if($cLostFiles > 0) {
                    Helper::LogFileActivity('lost', $iUserId, $cLostFiles, 'dropbox');
                }
                if($cNewFiles > 0) {
                    Helper::LogFileActivity('added', $iUserId, $cNewFiles, 'dropbox');
                }
                if($cChangedFiles > 0) {
                    Helper::LogFileActivity('changed', $iUserId, $cChangedFiles, 'dropbox');
                }

                //
                // process new files
                //
                foreach ($saNewFilesForSystem as $sNewFileKey) {
                    $oNewDropboxFile = $oDropboxFilesByKey[$sNewFileKey];

                    self::handleNewFileEvent(
                        $oNewDropboxFile['id'],
                        $oNewDropboxFile['path_lower'],
                        $oNewDropboxFile['name'],
                        $oNewDropboxFile['server_modified'],
                        $oFileSource->id,
                        $oFileSource->user_id,
                        $oNewDropboxFile['size']
                    );
                }

                //
                // process changed files
                //

                foreach ($saChangedFilesFromSystem as $sKeyPath => $oValueDropboxFileArray) {
                    // get the file that has changed

                    // self::handleChangedFileEvent(
                    // 	$sKeyPath,
                    // 	$oValueDropboxFileArray['server_modified']
                    // );
                    self::handleChangedFileEvent(
                        $sKeyPath,
                        $oValueDropboxFileArray['server_modified'],
                        $oValueDropboxFileArray['size']
                    );

                    // instead look up existing dropbox file and use as reference to create new task to import as if brand new
                    // $oDropboxFile = DropboxFiles::where('dropbox_path', $sKeyPath)->first();
                    // Helper::QueueAnItem('download-dropbox-file', $oDropboxFile->id);
                }


                //
                // process lost files
                //
                // todo create an event here of the num files being 'lost'
                foreach ($saLostFilesFromSystem as $sKey) {
                    self::handleDeletedFileEvent($sKey);
                }

                $bSuccess = true;
            } else {
                // problem, escape and requeue later
                $bSuccess = false;
                $aError = $aRequestDropboxFiles['error'];
            }
        }else{
            Helper::LogSomething(
                "folder/user/token etc not found",
                $iDropboxSourceId
            );
            $bSuccess = true;
        }

        $aReturn = ['success' => $bSuccess];
        if($bSuccess) {
            $aReturn['error'] = $aError;
        }

        return $aReturn;
    }

	public static function checkDownloadedDropboxFile(
		$iDropboxFileId
		/*$oDropboxFile,
		$sTempFilename*/
	)
	{
		/*
		reads the local file and make a signature,
		then compares it to existing picili files to
		see if it is totally new, alreayd there and active, or
		already there but deleted.
		*/

		$oDropboxFile = DropboxFiles::with('dropboxFolder')->find($iDropboxFileId);
		$oDropboxFolder = $oDropboxFile->dropboxFolder;

		$oDropboxFile->sTempFileName = $iDropboxFileId.'.jpg';
		$oDropboxFile->save();

		$saSignature = Helper::saSignatureLocalFile(Helper::sTempFilePathForDropboxFile($iDropboxFileId));
		$oDropboxFile->sha1 = $saSignature['shaone'];
		$oDropboxFile->size = $saSignature['filesize'];
		$oDropboxFile->combinedSignature = $saSignature['signature'];
		$oDropboxFile->save();


		$sStatus = Helper::sPicilFileStatusFromSignature($saSignature['signature']);

		return self::sDealWithNewPhysicalDropboxFileBasedOnStatus(
			$oDropboxFile,
			$oDropboxFolder,
			$sStatus,
			$saSignature['signature']
		);
	}

    public static function mergeDownloadedDropboxFile($iDropboxFileId)
    {
        // find and update the dropbox file
        $oDropboxFile = DropboxFiles::with('dropboxFolder')->find($iDropboxFileId);
		$oDropboxFolder = $oDropboxFile->dropboxFolder;

		$oDropboxFile->sTempFileName = $iDropboxFileId.'.jpg';
		$oDropboxFile->save();

		$saSignature = Helper::saSignatureLocalFile(Helper::sTempFilePathForDropboxFile($iDropboxFileId));
		$oDropboxFile->sha1 = $saSignature['shaone'];
		$oDropboxFile->size = $saSignature['filesize'];
		$oDropboxFile->combinedSignature = $saSignature['signature'];
		$oDropboxFile->save();

        // then find and update the picili file
        
        // the picili file will still have the correct dropbox source, but needs its signature to be updated
        // then reschedule for certain processors
        $oPiciliFile = PiciliFile::where('dropbox_filesource_id', $iDropboxFileId)->first();
        $oPiciliFile->signature = $oDropboxFile->combinedSignature;
        $oPiciliFile->sTempProcessingFilePath = Helper::sTempFilePathForDropboxFile($oDropboxFile->id);
        $oPiciliFile->save();

        // physical
        $iPhysicalTaskId = Helper::QueueAnItem('physical-file', $oPiciliFile->id, $oPiciliFile->user_id);
        // subject detection
        $iSubjectRecognitionTask = Helper::QueueAnItem('subject-recognition', $oPiciliFile->id, $oPiciliFile->user_id, $iPhysicalTaskId);

		return ['success' => true];
    }

	public static function sDealWithNewPhysicalDropboxFileBasedOnStatus($oDropboxFile, $oDropboxFolder, $sStatus, $sSignature = null)
	{
        try
        {
            switch($sStatus)
            {
                case 'new':
                    // create a new picili file
                    $oPiciliFile = new PiciliFile;
                    $oPiciliFile->sTempProcessingFilePath = Helper::sTempFilePathForDropboxFile($oDropboxFile->id);
                    $oPiciliFile->signature = $sSignature;
                    $oPiciliFile->user_id = $oDropboxFile->user_id;
                    
                    $oPiciliFile->bInFolder = true;
                    $aPathParts = Helper::aGetFolderComponentsFromDropboxFile(
                        $oDropboxFolder,
                        $oDropboxFile
                    );
                    $oPiciliFile->sParentPath = $aPathParts['parent-path'];
                    $oPiciliFile->baseName = $aPathParts['basename'];
                    $oPiciliFile->extension = $aPathParts['extension'];
                    $oPiciliFile->save();

                    $aaTags = array_map(function($sFolder) {
                        return [
                            'type' => 'folder',
                            'value' => $sFolder,
                            'confidence' => 80
                        ];
                    }, $aPathParts['folders']);
                    
                    TagHelper::removeTagsOfType($oPiciliFile, 'folder');
                                        

                    TagHelper::setTagsToFile($oPiciliFile, $aaTags);
                    
                    $bAddSourceSuccess = Helper::addSourceToPiciliFile($oPiciliFile->id, 'dropbox', $oDropboxFile->id);

                    // then schedule initial import etc
                    PiciliProcessor::FirstQueueOfPiciliFile($oPiciliFile);
                    break;
                case 'deleted':
                    // bring back from dead, with new source
                    Helper::bringFileBackFromTheDead($sSignature, 'dropbox', $oDropboxFile->id);

                    // we shouldn't also add/update sParentPath, baseName, extension - since the dropbox file is deleted and so we can't get these
                    break;
                case 'active':
                    // add dropbox to existing sources
                    $oPiciliFile = PiciliFile::where('signature', $sSignature)->first();
                    Helper::addSourceToPiciliFile($oPiciliFile->id, 'dropbox', $oDropboxFile->id);
                    // we should also add/update sParentPath, baseName, extension
                    $aPathParts = Helper::aGetFolderComponentsFromDropboxFile(
                        $oDropboxFolder,
                        $oDropboxFile
                    );
                    $oPiciliFile->sParentPath = $aPathParts['parent-path'];
                    $oPiciliFile->baseName = $aPathParts['basename'];
                    $oPiciliFile->extension = $aPathParts['extension'];
                    $oPiciliFile->save();
                    break;
            }
            return true;
        }catch(Exception $e)
        {
            logger('DropboxHelper::sDealWithNewPhysicalDropboxFileBasedOnStatus exception: '.$e);
            return false;
        }
	}


	public static function bDownloadDropboxFile($iDropboxDbId/*, $sUserAccessToken, $sTempFilename*/)
    {
        // copy file locally to storage/processing folder
		$oDropboxFile = DropboxFiles::with('dropboxFolder')->find($iDropboxDbId);
        $oDropboxFolder = $oDropboxFile->dropboxFolder;
        
		if(!isset($oDropboxFolder))
		{
			if (isset($oDropboxFile)){
				logger("dropbox file exists");
			}
			return false;
		}

        try
		{
            if(
                isset($oDropboxFile->dropbox_id) && 
                isset($oDropboxFile->dropboxFolder) && 
                isset($oDropboxFile->dropboxFolder->user->dropboxToken->access_token)
            )
            {
                $iDropboxFileId = $oDropboxFile->dropbox_id;
                $sUserAccessToken = $oDropboxFile->dropboxFolder->user->dropboxToken->access_token;

                $data = array("path" => $iDropboxFileId);
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

                $result = curl_exec($curl);

                if(curl_errno($curl))
                {
                    logger('download dropbox file, curl error:' . curl_error($curl));
                    return false;
                }
				curl_close($curl);

                // still here? - we got a file back?
                $my_file = Helper::sTempFilePathForDropboxFile($iDropboxDbId);

                $handle = fopen($my_file, 'w');
                fwrite($handle, $result);



                $oObj = json_decode($result);

                if(isset($oObj->error_summary))
                {
                    logger('download dropbox file - error_summary: '.$oObj->error_summary);
					return false;
                }

				// check file size (integrity)
				if($oDropboxFile->size !== filesize($my_file))
				{
					// todo - log failure
                    logger('download dropbox file - filesize problem, sizes not equal: ');
                    logger($oDropboxFile->size);
                    logger(filesize($my_file));
					return false;
				}

				// downloaded, and filesize matches...
				return true;

            }else{
                logger(["dropbox helper: things not set"]);
				return false;
            }
        } catch(Exception $e)
        {
			// todo - log properly
            // echo $e;
            logger("exception trying to download file from dropbox: ".$e);
            return false;
        }
        return false;
    }


	public static function handleNewFileEvent(
		$sDropboxId,
		$sDropboxLowerPath,
		$sDropboxFilename,
		$sDropboxServerModified,
		$iDropboxFileSourceId,
		$sUserId,
		$iSize
	)
	{
		/*
		 this method is called after the syncer
		  realises a file exists in dropbox and
		   not picili. first we should pull the file locally to find out about it.
		*/

		$oDBXFL = new \Share\DropboxFiles;

		$oDBXFL->dropbox_id = $sDropboxId;
		$oDBXFL->dropbox_path = $sDropboxLowerPath;
		$oDBXFL->dropbox_name = $sDropboxFilename;
		$oDBXFL->server_modified = $sDropboxServerModified;
		$oDBXFL->size = $iSize;


		$oDBXFL->dropbox_folder_id = $iDropboxFileSourceId;
		$oDBXFL->user_id = $sUserId;
		$oDBXFL->sTempFileName = Helper::sTempFilePathForDropboxFile($iDropboxFileSourceId);
		$oDBXFL->save();

        // only look to download jpegs
        $sExtension = Helper::sExtensionFromPath($sDropboxLowerPath);

        if($sExtension === 'jpg' || $sExtension === 'jpeg')
        {
            $iDownloadFileId = Helper::QueueAnItem('download-dropbox-file', $oDBXFL->id, $oDBXFL->user_id);
            Helper::QueueAnItem('import-new-dropbox-file', $oDBXFL->id, $oDBXFL->user_id, $iDownloadFileId);
        }
	}

	public static function handleChangedFileEvent(
		$sDropboxPath,
		$sNewTimestamp,
        $size
	)
	{
		/*
		event is called after noticing a difference in timestamps. need to import the file to check how different it is.

		// and also we will update our the dropbox files timestamp so that it is not flagged again.
		*/


		// update timestamp locally
        logger("file changed: {$sDropboxPath}");

		$oDropboxFileToUpdate = DropboxFiles::where("dropbox_path", $sDropboxPath)->first();


		$oDropboxFileToUpdate->server_modified = $sNewTimestamp;
		$oDropboxFileToUpdate->size = $size;

		$oDropboxFileToUpdate->save();


		// queue to be imported
        
        $iDownloadFileId = Helper::QueueAnItem('download-dropbox-file', $oDropboxFileToUpdate->id, $oDropboxFileToUpdate->user_id);
        $iImportTaskId = Helper::QueueAnItem('import-changed-dropbox-file', $oDropboxFileToUpdate->id, $oDropboxFileToUpdate->user_id, $iDownloadFileId);
	}

	public static function handleDeletedFileEvent($sPath)
	{
		$iDropboxFileId = DropboxFiles::where('dropbox_path', $sPath)->first()->id;
		// log file being removed
		Helper::LogSomething('dropbox file deleted', $iDropboxFileId);

		// remove source from picili file
        // $sPath in next look up - 0r not!
		$oPiciliFile = PiciliFile::where('dropbox_filesource_id', $iDropboxFileId)->first();
		// echo "\nbefore:\n";
		// print_r($oPiciliFile);
        if(isset($oPiciliFile))
        {
            $oPiciliFile = Helper::removeSourceFromPiciliFile($oPiciliFile, 'dropbox');

            // mark picili file as 'deleted'
            Helper::softDeleteIfNoSources($oPiciliFile);

            // schedule removal from elastic
            $iElasticDeleteTaskId = Helper::QueueAnItem('remove-from-elastic', $oPiciliFile->id, $oPiciliFile->user_id, null, null, false);

            // schedule aws thumb deletion, dependent on removal from elastic
            Helper::QueueAnItem('remove-from-s3', $oPiciliFile->id, $oPiciliFile->user_id, $iElasticDeleteTaskId, null, false);

            // remove temp processing file
            Helper::QueueAnItem('delete-processing-file', $oPiciliFile->id, $oPiciliFile->user_id, null, null, false);

            // remove all associated tasks
            Task::where('related_file_id', $oPiciliFile->id)
            ->where('bImporting', true)
            ->where('processor', '<>', 'full-dropbox-import')
            ->delete();

            // remove actual dropbox file
            $oDropboxFileToDelete = DropboxFiles::find($iDropboxFileId);
            if(isset($oDropboxFileToDelete)) {
                $oDropboxFileToDelete->delete();
            }
        }else{
            logger("couldn't find picili file to delete.. already deleted by another task..? dropbox file id was {$iDropboxFileId}, and the path: {$sPath}");
        }
	}

	private static function recursivelyBuildDropboxEntries($sFolderPath, $sOAuthToken)
	{
        $oaEntries = [];
        $aError = null;		
        
        $bComplete = false;
        $sCursor = '';
        $iReqs = 0;

        $bSuccess = true;

        while(!$bComplete)
        {
            $aNewEntries = self::listDropboxFolderContents(
                $sFolderPath,
                $sOAuthToken,
                $sCursor
            );
            $iReqs++;

            // only do the comparison logic if our request succeeded in getting a list of files
            if ($aNewEntries['success']) {

                $aEntries = $aNewEntries['entries'];

                if(isset($aEntries['has_more']) && filter_var($aEntries['has_more'], FILTER_VALIDATE_BOOLEAN))
                    {
                        $bComplete = false;
                        $sCursor = $aEntries['cursor'];
                    }else{
                        $bComplete = true;
                    }
                    $oaEntries = array_merge($oaEntries, $aEntries['entries']);
            } else {
                // propogate the error back
                $aError = $aNewEntries['error'];
                $bComplete = true;
                $bSuccess = false;
            }
        }

        $aReturn = ['success' => $bSuccess];

        if($bSuccess) {
            $aReturn['entries'] = $oaEntries;
        }else{
            $aReturn['error'] = $aError;
        }

		return $aReturn;
	}

	private static function getCompleteDropboxFolderContents($sFolderPath, $sOAuthToken, $bFoldersOnly = false)
    {
        // recursively build list of files
        ini_set('max_execution_time', 600); //300 seconds = 5 minutes

        $time_pre = microtime(true);

		$oaEntriesResponse = self::recursivelyBuildDropboxEntries($sFolderPath, $sOAuthToken);

        $oaDropboxFilesByKey = [];
        $bSuccess = false;
        $aError = null;

        if ($oaEntriesResponse['success'])
        {
            $bSuccess = true;
            foreach ($oaEntriesResponse['entries'] as $oDropboxItem) {
                if($oDropboxItem->{'.tag'} !== 'folder' && $bFoldersOnly === false) {
                    $oaDropboxFilesByKey[$oDropboxItem->path_lower] = (array)$oDropboxItem;
                }
                if($oDropboxItem->{'.tag'} === 'folder' && $bFoldersOnly){
                    array_push($oaDropboxFilesByKey, $oDropboxItem->path_lower);
                }
            }
        } else {
            // something went wrong, react accordingly
            $bSuccess = false;
            $aError = $oaEntriesResponse['error'];
        }

        $time_post = microtime(true);
        $exec_time = $time_post - $time_pre;
        
        
        $aReturn = ['success' => $bSuccess];
        
        if(!$bSuccess) {
            $aReturn['error'] = $oaEntriesResponse['error'];
        }else{
            $aReturn['dropboxFilesByKey'] = $oaDropboxFilesByKey;
        }

        return $aReturn;
    }

	public static function listDropboxFolderContents($sFolderPath, $sOAuthToken, $sCursor = '')
    {
        // get all files from folder
        // can return: success (bool), error, entries, cursor, has_more
        // error (array, containing type) can be: throttle, curl-error, unknown-error, exception

        $bSuccess = false;
        $aError = null;
        $aReturnEntries = null;

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
            curl_setopt($curl, CURLOPT_URL, $sUrl);
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
            // ask for headers
            curl_setopt($curl, CURLOPT_HEADER, 1);

            $result = curl_exec ($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);


            list($headers, $response) = explode("\r\n\r\n", $result, 2);

            
			// $fDropboxResp = fopen("dropbox/".uniqid().".json", "w");
			// fwrite($fDropboxResp, $result);
			// fclose($fDropboxResp);

            if(curl_errno($curl))
            {
                $bSuccess = false;
                $aError = [
                    ['type' => 'curl-error']
                ];
            } else {

                curl_close ($curl);
                $oObj = json_decode($response);

                // 409 is endpoint specific, in this case the folder doesn't exist
                // https://www.dropbox.com/developers/documentation/http/documentation#files-list_folder-continue
                // 429 - too many requests
                // logger($response);

                switch($httpcode)
                {
                    case 429:
                        // throttled
                        logger('THROTTLED - Dropbox throttled our request, we\'ll retry when they say it\'s ok');
                        $bSuccess = false;

                        $iSecs = (int)self::sGetHeaderValue($headers, 'Retry-After');
                        
                        if ($iSecs === null) $iSecs = 300;
                        
                        $aError = [
                            ['type' => 'throttled', 'retry_after' => $iSecs]
                        ];
                        break;
                    case 409:
                        // no folder at this path
                        // todo - report this to user
                        $bSuccess = true;
                        return ['status' => 'success', 'entries' => [], 'cursor' => null, 'has_more' => false];
                        break;
                    case 400:
                        // bad request
                        logger("400 - bad request error from dropbox");
                        logger("data string: $data_string");
                        logger($response);
                        logger("Auth token: ". $sOAuthToken);
                        if(isset($oObj->error_summary))
                        {
                            logger("error_summary: ".$oObj->error_summary);
                        }

                        $bSuccess = false;
                        $aError = [
                            ['type' => '400']
                        ];
                        break;
                    case 200:
                        if(isset($oObj->entries))
                        {
                            #echo "yup";

                            //echo $result;
                            $bSuccess = true;
                            $aReturnEntries = [
                                'entries' => $oObj->entries,
                                'cursor' => $oObj->cursor,
                                'has_more' => $oObj->has_more
                            ];
                        }else{
                            $bSuccess = true;
                            $aReturnEntries = [
                                'entries' => [],
                                'cursor' => null,
                                'has_more' => false
                            ];
                        }
                        break;
                    default:
                        logger("unknown error from dropbox, httpcode: ".$httpcode);
                        logger("had called url: $sUrl");
                        if(isset($oObj->error_summary))
                        {
                            logger("error_summary: ".$oObj->error_summary);
                        }

                        $bSuccess = false;
                        $aError = [
                            ['type' => 'unknown-error']
                        ];
                        break;
                }
            } 
            
        } catch(Exception $e)
        {
            $bSuccess = false;
            $aError = [
                ['type' => 'exception']
            ];
            logger('get dropbox files exception: ');
            logger($e);
        }

        // build response object, bool success and optional entries or error
        $aReturn = ['success' => $bSuccess];

        if(isset($aError)) {
            $aReturn['error'] = $aError;
        }

        if(isset($aReturnEntries)) {
            $aReturn['entries'] = $aReturnEntries;
        }

        return $aReturn;
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
        print_r($aoDatabaseFilesByKey);
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

        // logger('db:');
        // logger($saDatabaseFilesByIdDateKey);
        // logger('dropbox:');
        // logger($saDropboxFilesByIdDateKey);

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

    public static function sGetHeaderValue($aHeaders, $sKey)
    {
        $aHeaders = explode("\n", $aHeaders);

        foreach($aHeaders as $header) {
            if (stripos($header, "$sKey:") !== false) {
                return trim(str_replace("$sKey:", '', $header));
            }
        }
        return null;
    }
}


/*

SAMPLE Response


{
  ".tag": "folder",
  "name": "subfolder",
  "path_lower": "/test pics/subfolder",
  "path_display": "/test pics/subfolder",
  "id": "id:P-PXsF1geaAAAAAAAAAAAQ"
},
{
  ".tag": "file",
  "name": "DSC00716.JPG",
  "path_lower": "/test pics/dsc00716.jpg",
  "path_display": "/test pics/DSC00716.JPG",
  "id": "id:D36KzV4RcRAAAAAAAAAAAQ",
  "client_modified": "2016-04-22T23:48:20Z",
  "server_modified": "2016-04-24T06:30:39Z",
  "rev": "db760d2530c0",
  "size": 4958820,
  "content_hash": "e3484652d3204fdf4be93c1354e781bb397e48c55bd05583f204b3dca28e16b5"
},
{
  ".tag": "file",
  "name": "orange_flower.jpg",
  "path_lower": "/test pics/orange_flower.jpg",
  "path_display": "/test pics/orange_flower.jpg",
  "id": "id:Ee8ScLuL7SAAAAAAAAAAAQ",
  "client_modified": "2016-04-24T08:34:18Z",
  "server_modified": "2016-04-24T08:34:27Z",
  "rev": "db780d2530c0",
  "size": 86369,
  "content_hash": "a3fec122f46b294f7d1fc30dfb6c67060be49ebf04503ff15192c547a9f20680"
},
{
  ".tag": "file",
  "name": "DSC00716 (sam-PC's conflicted copy 2016-10-15).JPG",
  "path_lower": "/test pics/dsc00716 (sam-pc's conflicted copy 2016-10-15).jpg",
  "path_display": "/test pics/DSC00716 (sam-PC's conflicted copy 2016-10-15).JPG",
  "id": "id:qEr0pTQv8p0AAAAAAABeYw",
  "client_modified": "2012-10-27T01:38:24Z",
  "server_modified": "2016-10-15T12:12:06Z",
  "rev": "f1590d2530c0",
  "size": 6684672,
  "content_hash": "8726c253014ccf223e1c90f0725cc57156f7e9e9e81458881eee7c2cecd67377"
},
{
  ".tag": "file",
  "name": "DSC02914.JPG",
  "path_lower": "/test pics/dsc02914.jpg",
  "path_display": "/test pics/DSC02914.JPG",
  "id": "id:qEr0pTQv8p0AAAAAAABkCQ",
  "client_modified": "2016-03-27T11:28:24Z",
  "server_modified": "2017-03-03T14:02:15Z",
  "rev": "fa120d2530c0",
  "size": 8395180,
  "content_hash": "e54cda47b564d649c1cd6968ee1c8f167f917043ccce7695b2baac1de42a4e5e"
},
{
  ".tag": "file",
  "name": "DSC02928.JPG",
  "path_lower": "/test pics/dsc02928.jpg",
  "path_display": "/test pics/DSC02928.JPG",
  "id": "id:qEr0pTQv8p0AAAAAAABkCA",
  "client_modified": "2016-03-28T11:34:50Z",
  "server_modified": "2017-03-03T14:02:15Z",
  "rev": "fa130d2530c0",
  "size": 6527406,
  "content_hash": "8774633a70b42120be001a94d157c5ae59b77ad0ecc66746c88d412d3aebabb3"
},
{
  ".tag": "file",
  "name": "DSC06614.JPG",
  "path_lower": "/test pics/dsc06614.jpg",
  "path_display": "/test pics/DSC06614.JPG",
  "id": "id:qEr0pTQv8p0AAAAAAABkCg",
  "client_modified": "2016-06-27T09:55:16Z",
  "server_modified": "2017-03-03T14:02:23Z",
  "rev": "fa140d2530c0",
  "size": 12615680,
  "content_hash": "560a4b80c5f7eac7fcf84a8d9e5f7d285e87ef04a1faa79450cca73afeaa40b8"
},
{
  ".tag": "file",
  "name": "DSC07562.JPG",
  "path_lower": "/test pics/dsc07562.jpg",
  "path_display": "/test pics/DSC07562.JPG",
  "id": "id:qEr0pTQv8p0AAAAAAABkCw",
  "client_modified": "2016-07-13T11:07:54Z",
  "server_modified": "2017-03-03T15:26:24Z",
  "rev": "fa150d2530c0",
  "size": 6324224,
  "content_hash": "ba206aec9d88a3b0fb8e79f533b0c334e047b41e13d2745c57cdc258d913cedd"
}


*/
