<?php

namespace App\Library;

use Intervention\Image\ImageManager;
use League\ColorExtractor\Color;
use League\ColorExtractor\ColorExtractor;
use League\ColorExtractor\Palette;
use Carbon\Carbon;
use \BrianMcdo\ImagePalette\ImagePalette;
use Jenssegers\ImageHash\ImageHash;
use Image;

use Share\PiciliFile;
use Share\Tag;
use Share\Task;
Use Share\DropboxFilesource;
use App\Models\Log;
use App\Models\ProcessorLog;
use App\Models\Event;

use SharedLibrary\TagHelper;

use Aws\Rekognition\RekognitionClient;



class Helper {

    public static function addSourceToPiciliFile($iPiciliFileId, $sSourceName, $sSourceValue)
    {
        $oFile = PiciliFile::find($iPiciliFileId);
        $bReturn = false;

        if(isset($oFile))
        {
            switch($sSourceName)
            {
                case 'dropbox':
                    $oFile->dropbox_filesource_id = $sSourceValue;
                    $oFile->save();
                    $bReturn = true;
                    break;
                case 'instagram':
                    $oFile->instagram_filesource_id = $sSourceValue;
                    $oFile->save();
                    $bReturn = true;
                    break;
            }

        }
        return $bReturn;
    }

    public static function removeSourceFromPiciliFile($oFile, $sSourceName)
    {
        $bReturn = false;

        switch($sSourceName)
        {
            case 'dropbox':
                $oFile->dropbox_filesource_id = null;
                $bReturn = true;
                break;
            case 'instagram':
                $oFile->instagram_filesource_id = null;
                $bReturn = true;
                break;
        }
        if($oFile->aSources() === [])
        {
            $oFile->bDeleted = true;
        }
        $oFile->save();

        return $oFile;
    }
    public static function softDeleteIfNoSources($oPiciliFile)
    {
        if($oPiciliFile->aSources() === [])
        {
            $oPiciliFile->bDeleted = true;
            $oPiciliFile->save();
        }
    }



    //
    // Logging
    //
    public static function LogSomething($sEvent, $iRelatedId, $iUserId = null)
    {
        $oLog = new Log;
        $oLog->event = $sEvent;
        $oLog->related_id = $iRelatedId;
        $oLog->dTimeOccured = Carbon::now();

        // if($iUserId !== null)
        // {
        //     $oLog->user_id = $iUserId;
        // }

        $oLog->save();
    }
    public static function LogProcessorRun($sProcessor, $sTaskId, $sRelatedPiciliFileId, $mResult, $mtStarted, $mExtra = null)
    {
        $oLog = new ProcessorLog;
        $oLog->processor = $sProcessor;
        // todo - refactor processors so they all return standardised things..
        if(isset($mResult['success']) && ($mResult['success'] || !$mResult['success']))
        {
            $oLog->result = $mResult['success'];
        } else {
            $oLog->result = false;
            logger(['unexpected mResult: ', $mResult]);
        }
        $oLog->iRunTime = microtime(true) - $mtStarted;
        $oLog->dTimeOccured = Carbon::now();
        $oLog->processTask = $sTaskId;
        $oLog->sRelatedPiciliFileId = $sRelatedPiciliFileId;

        // todo - also 'mExtra', handle in structured way..
        if(!is_null($mExtra))
        {
            $oLog->mExtra = json_encode($mExtra);
        }
        $oLog->save();
    }
    public static function LogFileActivity($sFileActivity, $iUserId, $cFiles, $sSource)
    {
        $oLog = new Event;        
        $oLog->event = "file activity event";

        $oLog->addChangeDelete = $sFileActivity;
        $oLog->source = $sSource;
        $oLog->cFiles = $cFiles;
        $oLog->user_id = $iUserId;

        $oLog->dTimeOccured = Carbon::now();

        $oLog->save();
    }

    //
    // queue stuff
    //
    public static function startedAnItem($oTask)
    {
        // processor just started a task, so lock it for x mins
        $oTask->dDateAfter = Carbon::now()->addMinutes(5);
        if(!isset($oTask->iTimesSeenByProccessor))
        {
            $oTask->iTimesSeenByProccessor = 1;
        }else{
            $oTask->iTimesSeenByProccessor = $oTask->iTimesSeenByProccessor  + 1;
        }
        $oTask->save();
    }
    public static function QueueAnItem(
        $sProcessorName,
        $iRelatedId,
        $iUserId,
        $iTaskDependentOn = null,
        $oDateFrom = null,
        $bImporting = true,
        $iPriority = NULL
    )
    {
        $oTask = new Task;

        $oTask->processor = $sProcessorName;
        $oTask->related_file_id = $iRelatedId;
        $oTask->user_id = $iUserId;

        if(isset($iTaskDependentOn))
        {
            $oTask->iAfter = $iTaskDependentOn;
        }else{
            // not dependent
            $oTask->iAfter = -1;
        }
        if(isset($oDateFrom))
        {
            $oTask->dDateAfter = $oDateFrom;
        }else{
            // asap
            $oTask->dDateAfter = Carbon::now()->addSeconds(-1);
        }
        $oTask->bImporting = $bImporting;

        // task priority
        if($iPriority === null)
        {
            $iPriority = 0;
            // set priority based on processor
            switch($sProcessorName)
            {
                case 'full-dropbox-import':
                    $oTask->priority = 10;
                    break;
                case 'download-dropbox-file':
                case 'import-new-dropbox-file':
                case 'import-changed-dropbox-file':
                    $oTask->priority = 0;
                    break;
                case 'physical-file':
                    $oTask->priority = 1;
                    break;
                case 'subject-recognition':
                    $oTask->priority = 2;
                    break;
                case 'geocode':
                case 'altitude':
                    $oTask->priority = 3;
                    break;
                default:
                    $oTask->priority = $iPriority;
                    break;
            }
        } else {
            $oTask->priority = $iPriority;
        }

        $oTask->save();
        return $oTask->id;
    }

    public static function oAddDropboxFolderSource($sToken, $sFolder, $iUserId)
    {
        // wrap it in a try and catch since there's a unique index on user_id
        try{
            $oFirstDropboxSource = new DropboxFilesource;
            $oFirstDropboxSource->folder = $sFolder;
            $oFirstDropboxSource->user_id = $iUserId;
            $oFirstDropboxSource->save();

            return $oFirstDropboxSource;
        }catch(\Illuminate\Database\QueryException $e)
        {
            return null;
        }
    }
    public static function RemoveFilesTasksIfDead($iPiciliFileId)
    {
        // remove all tasks for this item which are to do with importing it
        $oaTasks = Task::where('related_file_id', $iPiciliFileId)->where('bImporting', true)->get();

        foreach($oaTasks as $oDeleteTask)
        {
            $oDeleteTask->delete();
        }
    }

    public static function completeATask($iTaskId)
    {
        // delete the task and update any tasks dependent on it
        $oTask = Task::find($iTaskId);

        if(isset($oTask)) {
            // some tasks are instantly requeued
            if($oTask->processor === 'full-dropbox-import')
            {
                // queue again for next pull if the users file source doens't have an import task scheduled already
                $iExistingImportTasks = Task::where('processor', '=', 'full-dropbox-import')
                ->where('user_id', '=', $oTask->user_id)
                ->where('related_file_id', '=', $oTask->related_file_id)
                ->count();

                // there should be one at least, the one we are about to complete/delete
                if($iExistingImportTasks < 2) {
                    Helper::QueueAnItem(
                        'full-dropbox-import',
                        $oTask->related_file_id,
                        $oTask->user_id,
                        null,
                        Carbon::now()->addMinutes(1)
                    );
                } else {
                    logger('there was already a dropbox import task for this user and filesource, skipping creating a new import task..');
                }
            }

            $oTask->delete();

            $aoUpdatedTasks = Task::where('iAfter', $iTaskId)->get();
            foreach($aoUpdatedTasks as $oUpdateTask)
            {
                $oUpdateTask->iAfter = -1;
                $oUpdateTask->save();
            }
        } else {
            // throw new \Exception('Helper::completeATask - couldn\'t find task: '.$iTaskId);
            logger("Warning: Helper::completeATask - couldn't find task: ".$iTaskId);
            return null;
        }
    }

    public static function delayATask($oTask, $iDays, $iHours = null, $iMinutes = null)
    {
        if(isset($oTask))
        {
            $dFuture = $oTask->dDateAfter;
            $dFuture->addDays($iDays);
            if(isset($iHours)) {
                $dFuture = Carbon::now()->addHours($iHours);
            }
            if(isset($iMinutes)) {
                $dFuture = Carbon::now()->addMinutes($iMinutes);
            }
            $oTask->dDateAfter = $dFuture;
            $oTask->save();
        }
    }

    public static function delayProcessorsTasks($sProcessorType, $iDays) {
        Task::where('processor', '=', $sProcessorType)
        ->update(['dDateAfter' => Carbon::now()->addDays($iDays)]);
    }

    //
    // file analysis
    //
    public static function bringFileBackFromTheDead($sSignature, $sNewFileSourceName, $iDropboxFileId)
    {
        $oPiciliFile = PiciliFile::where('signature', $sSignature)->first();

        // undelete (set back to false)
        $oPiciliFile->sTempProcessingFilePath = Helper::sTempFilePathForDropboxFile($iDropboxFileId);
        $oPiciliFile->bDeleted = false;
        $oPiciliFile->save();

        self::addSourceToPiciliFile($oPiciliFile->id, $sNewFileSourceName, $iDropboxFileId);

        // schedule re-discovery tasks
        PiciliProcessor::RediscoveryQueueOfPiciliFile($oPiciliFile);
    }
    public static function saSignatureLocalFile($sPath)
	{
        // open file
        // get sha1
        $sShaOne = sha1_file($sPath);
        // get filesize
        $iFilesize = filesize($sPath);
        // return signature
        return [
            'shaone' => $sShaOne,
            'filesize' => $iFilesize,
            'signature' => $sShaOne.'.'.$iFilesize
        ];
	}
    public static function sPicilFileStatusFromSignature($sSignature)
    {
        // take a signature and determine if we have file already etc
        $sStatus = 'new';
        $oPiciliFile = PiciliFile::where('signature', $sSignature)->first();
        if(isset($oPiciliFile))
        {
            if(isset($oPiciliFile->bDeleted) && $oPiciliFile->bDeleted)
            {
                $sStatus = 'deleted';
            }else{
                $sStatus = 'active';
            }
        }
        return $sStatus;
    }
    public static function sExtensionFromPath($sPath)
    {
        $aParts = pathinfo($sPath);
        if(isset($aParts['extension']))
        {
            return strtolower($aParts['extension']);
        }

        return '';
    }

    public static function sTempFilePathForDropboxFile($iDropboxDbId)
    {
        return public_path().DIRECTORY_SEPARATOR.'processing'.DIRECTORY_SEPARATOR.$iDropboxDbId.'.jpg';

    }
    public static function aGetColours($sFullPath)
    {
        $aReturnColours = [];

        // read in file, get main colour and pallette

        $primaryPalette = Palette::fromFilename($sFullPath);
        $extractor = new ColorExtractor($primaryPalette);
        $primaryPalette = null;

        // it defines an extract method which return the most “representative” colors
        $aExtractedColours = $extractor->extract(5);
        $extractor = null;

        $aPallete = [];
        foreach($aExtractedColours as $oSingleColour)
        {
            // array_push($aPallete, Color::fromIntToHex($oSingleColour));
            array_push($aPallete, Color::fromIntToRgb($oSingleColour));
            $oSingleColour = null;
        }
        $aReturnColours['pallette'] = $aPallete;
        $aPallete = null;


        $aMixedPallete = new \BrianMcdo\ImagePalette\ImagePalette($sFullPath);
        $colors = $aMixedPallete->getColors(1);
        $aMixedPallete = null;
        // $aMixed = [];
        if(isset($colors[0]))
        {
            // $aReturnColours['best'] = $colors[0]->toHexString();
            $aReturnColours['best'] = (array)$colors[0];
        }
        $colors = null;

        return $aReturnColours;
    }

    //
    // GPS Stuff
    //
    public static function getGps($exifCoord, $hemi)
    {
        $degrees = count($exifCoord) > 0 ? self::gps2Num($exifCoord[0]) : 0;
        $minutes = count($exifCoord) > 1 ? self::gps2Num($exifCoord[1]) : 0;
        $seconds = count($exifCoord) > 2 ? self::gps2Num($exifCoord[2]) : 0;

        $flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;

        return $flip * ($degrees + $minutes / 60 + $seconds / 3600);
    }

    public static function gps2Num($coordPart)
    {
        $parts = explode('/', $coordPart);

        if (count($parts) <= 0)
            return 0;

        if (count($parts) == 1)
            return $parts[0];

        return floatval($parts[0]) / floatval($parts[1]);
    }
    public static function fAltitudeExifStringToNumber($sLiteral)
    {
        $sTrimmed = trim($sLiteral);

        if (strpos($sLiteral, '/') !== false) {

            $saParts = explode('/', $sTrimmed);
            if(count($saParts) === 2)
            {
                if($saParts[1] === '0')
                {
                    return false;
                }else{
                    return $saParts[0] / $saParts[1];
                }
            }else{
                return false;
            }
        }
        else{
            if(is_numeric($sLiteral))
            {
                return $sLiteral;
            }
        }
        return false;
    }

    //
    // thumbs
    //
    public static function sS3Path($iUserId, $iPiciliFileId, $sThumbSize)
    {
        # https://s3-eu-west-1.amazonaws.com/phpunit-picili-bucket/t/58bee9babf544/l58bee9babfd15.jpg
        switch(env('APP_ENV'))
        {
            case 'production':
            // return 'http://static.picili.com/t/'.$iUserId.'/'.$sThumbSize.$iPiciliFileId.'.jpg';
            return 'https://s3-'.env('AWS_REGION').'.amazonaws.com/'.env('AWS_BUCKET').'/t/'.$iUserId.'/'.$sThumbSize.$iPiciliFileId.'.jpg';
                break;
            case 'local':
            case 'testing':
            default:
                return 'https://s3-eu-west-1.amazonaws.com/'.env('AWS_BUCKET').'/t/'.$iUserId.'/'.$sThumbSize.$iPiciliFileId.'.jpg';
                break;
        }
        // return 'http://static.picili.com/'
    }
    public static function sGetAWSBasePath($iUserId)
    {
        return '/t/'. $iUserId .'/';
    }
    public static function bDeleteThumbsFromAWS($iPiciliFileId)
    {
        $oPiciliFile = PiciliFile::find($iPiciliFileId);
        $sAWSBaseThumbPath = self::sGetAWSBasePath($oPiciliFile->user_id);

        $aFilesToDelete = [
            $sAWSBaseThumbPath.'xl'.$iPiciliFileId.'.jpg',
            $sAWSBaseThumbPath.'l'.$iPiciliFileId.'.jpg',
            $sAWSBaseThumbPath.'m'.$iPiciliFileId.'.jpg',
            $sAWSBaseThumbPath.'s'.$iPiciliFileId.'.jpg',
            $sAWSBaseThumbPath.'i'.$iPiciliFileId.'.jpg'
        ];

        if(self::deleteFilesFromAWS($aFilesToDelete))
        {
            // mark as deleted and return true
            $oPiciliFile->bHasThumbs = false;
            $oPiciliFile->save();
            return true;
        }else{
            return false;
        }
    }
    public static function deleteFilesFromAWS($aFiles)
    {
        $s3 = \Storage::disk('s3');
        $bAWSResponse = $s3->delete($aFiles);
        if(!$bAWSResponse) {
            // check to see if the files are already deleted
            logger('deleting from aws failed: ', $aFiles);
            $aStillExists = [];
            foreach($aFiles as $sAWSFilePath)
            {
                if(\Storage::disk('s3')->has($sAWSFilePath))
                {
                    // file exists..
                    array_push($aStillExists, $sAWSFilePath);
                }
            }

            // 
            if (count($aStillExists) === 0) {
                // these files don't exist on aws
                return true;
            }else{
                logger('deleting files from aws does not work, even though the files are on aws..');
                logger('files to delete', $aFiles);
                logger('files on aws', $aStillExists);
                return false;
            }
        }
        return $bAWSResponse;
    }
    public static function saveStreamToAWS($sPath, $sStream)
    {
        $s3 = \Storage::disk('s3');
        $s3->put($sPath, $sStream, 'public');
    }

    public static function bIsCorrupt($path)
    {
        return !@imagecreatefromjpeg($path);
    }

    public static function bGenerateThumbs($sInputFilePath, $sOutputFolder, $iUserId, $iFileId)
    {
        if(file_exists($sInputFilePath) )
        {
            if(self::bIsCorrupt($sInputFilePath)){
                self::LogSomething("helper::bGenerateThumbs error - FATAL HANDLER", $iFileId, $iUserId);
                
                // mark as corrupt
                $oPiciliFile = PiciliFile::find($iFileId);
                $oPiciliFile->bCorrupt = true;
                $oPiciliFile->save();

                // what else? remove subsequent tasks from queue 
                Task::where('related_file_id', $iFileId)->delete();

                return false;
            }

            try
            {
                ini_set('gd.jpeg_ignore_warning', true);
                $sErrorReportingLevel = error_reporting();
                error_reporting(E_ALL & E_STRICT);
                $oImage = Image::make($sInputFilePath);

                $saCreatedThumbs = [];
                $iMediumWidth = null;
                $iMediumHeight = null;
                $iQuality = 85; // default is 90

                if ($oImage !== false)
                {
                    if(isset($oImage))
                    {
                        $oImage->orientate();

                        $sBaseThumbPath = $sOutputFolder.'/'. $iUserId .'/';
                        $sAWSBaseThumbPath = self::sGetAWSBasePath($iUserId);
                        
                        if(!file_exists($sBaseThumbPath))
                        {
                            mkdir($sBaseThumbPath);
                        }

                        // lightbox (extra large)
                        $oImage->resize(1620, null, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                        $sNewPath = $sBaseThumbPath.$iFileId.'xl.jpg';
                        array_push($saCreatedThumbs, $sNewPath);

                        self::storeFile($oImage, $sAWSBaseThumbPath, 'xl', $iFileId, $iQuality, $sNewPath);

                        // lightbox
                        $oImage->resize(1120, null, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                        $sNewPath = $sBaseThumbPath.$iFileId.'l.jpg';
                        array_push($saCreatedThumbs, $sNewPath);
                        self::storeFile($oImage, $sAWSBaseThumbPath, 'l', $iFileId, $iQuality, $sNewPath);

                        // thumb results
                        $oImage->resize(null, 300, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                        $sNewPath = $sBaseThumbPath.$iFileId.'m.jpg';
                        array_push($saCreatedThumbs, $sNewPath);
                        self::storeFile($oImage, $sAWSBaseThumbPath, 'm', $iFileId, $iQuality, $sNewPath);

                        $iMediumWidth = $oImage->width();
                        $iMediumHeight = $oImage->height();

                        // square
                        $oImage->fit(125, 125);

                        $sNewPath = $sBaseThumbPath.$iFileId.'s.jpg';
                        array_push($saCreatedThumbs, $sNewPath);
                        self::storeFile($oImage, $sAWSBaseThumbPath, 's', $iFileId, $iQuality, $sNewPath);

                        // icon
                        $oImage->fit(32, 32);
                        $sNewPath = $sBaseThumbPath.$iFileId.'i.jpg';
                        array_push($saCreatedThumbs, $sNewPath);
                        self::storeFile($oImage, $sAWSBaseThumbPath, 'i', $iFileId, $iQuality, $sNewPath);

                        $oImage->destroy();
                        unset($oImage);

                        $oPiciliFile = PiciliFile::find($iFileId);
                        $oPiciliFile->medium_width = $iMediumWidth;
                        $oPiciliFile->medium_height = $iMediumHeight;
                        $oPiciliFile->save();

                        return true;

                    }else{
                        // image corrupt?
                        self::LogSomething('helper::bGenerateThumbs error - image corrupt 1 - can\' thumb', $iPiciliFileId);
                        
                        return false;
                    }
                }else{
                    // image corrupt?
                    logger(['iamge corrupt']);
                    self::LogSomething('helper::bGenerateThumbs error - image corrupt 2 - can\' thumb', $iFileId, $iUserId);
                    return false;
                }

                error_reporting($sErrorReportingLevel);
            }
            catch (\Intervention\Image\Exception\NotReadableException $e)
            {
                logger('should never get here as bIsCorrupt should catch unreadable images..');
                self::LogSomething('helper::bGenerateThumbs error - \Intervention\Image\Exception\NotReadableException', $iFileId, $iUserId);

                // mark as corrupt
                $oPiciliFile = PiciliFile::find($iFileId);
                $oPiciliFile->bCorrupt = true;
                $oPiciliFile->save();

                // what else? remove subsequent tasks from queue 
                Task::where('related_file_id', $iFileId)->delete();

                return false;
            }
                
        }else{
            self::LogSomething('helper::bGenerateThumbs error - missing file', $iFileId, $iUserId);
            logger(['helper::bGenerateThumbs error - missing file']);
            logger(['- file_id: '.$iFileId]);
            logger(['- user_id: '.$iUserId]);
            return false;
        }
    }

    private static function storeFile($oImage, $sAWSBaseThumbPath, $sSize, $iFileId, $iQuality, $sNewPath) {
        env('APP_ENV') !== 'testing' ?
        self::saveStreamToAWS(
            $sAWSBaseThumbPath . $sSize.$iFileId.'.jpg',
            $oImage->stream('jpg', $iQuality)->__toString()
        ) : $oImage->save($sNewPath);
    }

    public static function bProcessPhysicalFile($iPiciliFileId)
    {
        try
        {
            $oPiciliFile = PiciliFile::find($iPiciliFileId);
            $sPath = $oPiciliFile->sTempProcessingFilePath;

            if(!isset($oPiciliFile->sTempProcessingFilePath))
            {
                logger('helper::bProcessPhysicalFile error - missing file', $iPiciliFileId, $oPiciliFile->user_id);
                return false;
            }

            // make thumbs

            $sInputFilePath = $sPath;
            $sOutputFolder = public_path('thumbs');
            $iUserId = $oPiciliFile->user_id;
            $iFileId = $oPiciliFile->id;

            if(!self::bGenerateThumbs($sInputFilePath, $sOutputFolder, $iUserId, $iFileId))
            {
                // error generating thumbs
                self::LogSomething('helper::bProcessPhysicalFile error - error generating thumbs', $iPiciliFileId, $iUserId);
                return false;
            }else{
                /*
                reload picili file from db, since it will have been modified when generating thumbnails
                */
                unset($oPiciliFile);
                $oPiciliFile = PiciliFile::find($iPiciliFileId);
                $oPiciliFile->bHasThumbs = true;

                // get exif
                $aExifData = FileTaggingHelper::aExifDataFromImagePath($sPath);

                $aaExifTags = [];
                foreach($aExifData as $sK => $mV) {
                    array_push(
                        $aaExifTags, [
                            'type' => 'exif',
                            'subtype' => $sK,
                            'value' => $mV,
                            'confidence' => 80
                        ]
                    );
                }
                TagHelper::removeTagsOfType($oPiciliFile, 'exif');
                TagHelper::setTagsToFile($oPiciliFile, $aaExifTags);

                // set gps altiude bools, maybe queue altitude processor
                self::ConditionalHandleExifdata($oPiciliFile, $aExifData);
                // if the file had geodata, queue it for geocoder
                if ($oPiciliFile->bHasGPS) {
                    self::QueueAnItem('geocode', $oPiciliFile->id, $oPiciliFile->user_id);
                }

                // get colours
                $aColours = Helper::aGetColours($sPath);

                $aaColourTags = TagHelper::getColourTagsFromColours($aColours, $iPiciliFileId);

                TagHelper::removeTagsOfType($oPiciliFile, 'colour');
                $oPiciliFile->addTags($aaColourTags);

                // phash
                if(file_exists($oPiciliFile->sTempProcessingFilePath))
                {
                    $hasher = new ImageHash;
                    $hash = $hasher->hash($oPiciliFile->sTempProcessingFilePath);
                    $oPiciliFile->phash = $hash;
                }else{
                    logger([
                        "error" => "can't phash image as processing file doesn't exist",
                        "picili id" => $oPiciliFile->id,
                        "picili path" => $oPiciliFile->sTempProcessingFilePath
                    ]);
                    return false;
                }

                $oPiciliFile->save();

                return true;
            }
        } catch(Exception $e)
        {
            logger('exception processing physical file: ');
            logger($e);
            return false;
        }
    }

    public static function ConditionalHandleExifdata($oPiciliFile, $aExifData)
    {
        // set geo on model if set in exif array
        if(isset($aExifData['latitude']) && isset($aExifData['longitude']))
        {
            $oPiciliFile->bHasGPS = true;
            $oPiciliFile->latitude = $aExifData['latitude'];
            $oPiciliFile->longitude = $aExifData['longitude'];

            // if gps and no altitude queue for altitude processor
            if(!isset($aExifData['altitude']))
            {
                $oPiciliFile->bHasAltitude = false;
                Helper::QueueAnItem('altitude', $oPiciliFile->id, $oPiciliFile->user_id);
            }else{
                $oPiciliFile->bHasAltitude = true;
                $oPiciliFile->altitude = $aExifData['altitude'];
            }

        }else{
            $oPiciliFile->bHasGPS = false;
        }

        // date too
        if(isset($aExifData['datetime']))
        {
            $oPiciliFile->datetime = Carbon::parse($aExifData['datetime']);

            // date literals
            $aaDateLiteralTags = [];
            $iPiciliFileId = $oPiciliFile->id;
            $oDate = $oPiciliFile->datetime;

            // day of week
            array_push(
                $aaDateLiteralTags,
                new Tag([
                    'type' => 'dateliteral',
                    'subtype' => 'day',
                    'value' => strtolower($oDate->format('l')),
                    'confidence' => 90,
                    'file_id' => $iPiciliFileId
                ])
            );

            // month
            array_push(
                $aaDateLiteralTags,
                new Tag([
                    'type' => 'dateliteral',
                    'subtype' => 'month',
                    'value' => strtolower($oDate->format('F')),
                    'confidence' => 90,
                    'file_id' => $iPiciliFileId
                ])
            );

            // year
            array_push(
                $aaDateLiteralTags,
                new Tag([
                    'type' => 'dateliteral',
                    'subtype' => 'year',
                    'value' => strtolower($oDate->format('Y')),
                    'confidence' => 90,
                    'file_id' => $iPiciliFileId
                ])
            );

            // am-pm
            array_push(
                $aaDateLiteralTags,
                new Tag([
                    'type' => 'dateliteral',
                    'subtype' => 'meridiem',
                    'value' => strtolower($oDate->format('A')),
                    'confidence' => 90,
                    'file_id' => $iPiciliFileId
                ])
            );

            TagHelper::removeTagsOfType($oPiciliFile, 'dateliteral');
            $oPiciliFile->addTags($aaDateLiteralTags);
        }

            

        $oPiciliFile->save();
    }

    public static function bRemoveProcessingfile($iPiciliFileId)
    {
        // delete the processing file
        $oPiciliFile = PiciliFile::find($iPiciliFileId);

        if(isset($oPiciliFile)) {
            $sPath = $oPiciliFile->sTempProcessingFilePath;


            if(is_file($sPath) && @unlink($sPath)){
                // delete success
                $oPiciliFile->sTempProcessingFilePath = 'deleted';
                $oPiciliFile->save();
                return true;
            } else if (is_file ($sPath)) {
                // unlink failed.
                // you would have got an error if it wasn't suppressed
                logger('bRemoveProcessingfile - is_file and error: ', [$sPath]);
                return false;
            } else {
                // file doesn't exist
                $oPiciliFile->sTempProcessingFilePath = 'deleted';
                $oPiciliFile->save();
                return true;
            }

        }else {
            return true;
        }
    }

    //
    // external apis
    //
    public static function mImagga($iPiciliFileId, $bDebug = false)
    {
        $mReturn = ['status' => 'unknown'];

        try
        {
            $oPiciliFile = PiciliFile::find($iPiciliFileId);
            // get aws path
            $sAWSS3Path = self::sS3Path($oPiciliFile->user_id, $iPiciliFileId, 'xl');

            $iUserId = $oPiciliFile->user_id;

            // echo $sAWSS3Path;

            if($bDebug)
            {
                $iUserId = 'test';
                $iPiciliFileId = 'test';
            }

            $sAwsPathLarge = "https://s3-".env('AWS_REGION').".amazonaws.com/".env('AWS_BUCKET_NAME')."/t/".  $iUserId."/xl" . $iPiciliFileId . ".jpg";

            // make request
            $service_url = 'http://api.imagga.com/v1/tagging?url='.$sAwsPathLarge;

            $sKey = env('API_IMAGGA_KEY');
            $sSecret = env('API_IMAGGA_SECRET');

            $context = stream_context_create(array(
                'http' => array(
                    'header'  => "Authorization: Basic " . base64_encode($sKey.":".$sSecret)
                )
            ));

            $jsonurl = $service_url;
            $json = @file_get_contents($jsonurl, false, $context);

            if($json === FALSE)
            {
                // network connectivity issue
                $mReturn['status'] = "fail - network error";
            }else{
                // got a response, do stuff with it

                list($version,$status_code,$msg) = explode(' ',$http_response_header[0], 3);

                $oObj = json_decode($json);

                switch($status_code)
                {
                    case 200:

                        $aaTags = [];
                        $mReturn = ['status' => 'success'];

                        if(isset($oObj->results))
                        {
                            if(count($oObj->results) > 0)
                            {
                                foreach($oObj->results as $oImageResult)
                                {
                                    if(isset($oImageResult->tags))
                                    {
                                        foreach($oImageResult->tags as $oTag){
                                            $oTag = (array)$oTag;

                                            array_push($aaTags, $oTag);

                                            // print_r($oTag);

                                            // TaggingHelper::_QuickTag(
                                            //     $oFile->user_id,
                                            //     $oFile->id,
                                            //     "imagga",
                                            //     Helper::sStripPunctuation($oTag["tag"]),
                                            //     $oTag["confidence"]);
                                        }

                                        $mReturn['tags'] = $aaTags;
                                    }
                                }


                            }else{
                                // returned okay, but no results, strange..
                                //return "empty";
                            }
                        }else{
                            $oStat = new StatModel();
                            $oStat->name = "no imagga results";
                            $oStat->group = "auto";
                            $oStat->value = 1;
                            $oStat->save();
                        }

                        $sReturn = "true";
                        break;
                    default:
                        $error_status="Undocumented error: " . $status_code;
                        $mReturn['status'] = "throttle";
                        break;
                }
            }
        }catch(Exception $e)
        {
            // handle it
            $mReturn = ['status' => 'fail'];

            // log it
            Helper::logError(
                "ImaggaProcessor",
                "exception",
                [
                    "function" => "process",
                    "exception_message" => $ex
                ]
            );
        }


        return $mReturn;
    }

    public static function mElevationFromLatLon($fLat, $fLon)
    {
        $fReturn = ['status' => 'unknown'];


        $sElevationURL  = 'https://maps.googleapis.com/maps/api/elevation/json?locations='.urlencode($fLat).','.urlencode($fLon);

        $sElevationURL .= "&key=".env('GOOGLE_ELEVATION_KEY');
        // echo "url: ", $sElevationURL."<br/><br/>";

        $json = @file_get_contents($sElevationURL);

        list($version, $status_code, $msg) = explode(' ',$http_response_header[0], 3);

        if(isset($status_code))
        {
            // echo "response: ", $status_code, "<br/>";

            // Check the HTTP Status code
            switch($status_code)
            {
                case 200:
                    $oObj = json_decode($json);

                    if(isset($oObj->status)){

                        switch($oObj->status)
                        {
                            case "OK":
                            case "ZERO_RESULTS":
                            case "INVALID_REQUEST":
                                if(isset($oObj->results))
                                {

                                    $mElevationResult = $oObj->results[0]->elevation;

                                    $fReturn['status'] = 'success';
                                    $fReturn['value'] = floor($mElevationResult * 100) / 100;
                                }
                                break;
                            case "REQUEST_DENIED":
                            case "OVER_QUERY_LIMIT":
                            case "UNKNOWN_ERROR":
                                $fReturn['status'] = 'throttled';
                                $fReturn['value'] = $oObj->status;
                                break;
                            default:
                                $fReturn['status'] = 'default';
                                $fReturn['value'] = 'unknown error, no switch statement handler..';

                                // to do - log an error
                                break;
                        }
                    }else{
                        $fReturn['status'] = 'api error';
                        $fReturn['value'] = 'no response status';

                    }
                    break;
                case 403:
                    $fReturn['status'] = 'throttled';
                    $fReturn['value'] = 'http 403 throttle';
                    break;
                case 400:
                    logger('altitude encode - invalid request: '.$sElevationURL);
                    $fReturn['status'] = 'invalid';
                    $fReturn['value'] = 'invalid';
                    break;

                default:

                    // todo - log this or something
                    logger('altitude encode - unexpected status code: '.$status_code);
                    $fReturn['status'] = 'unexpected http response';
                    $fReturn['value'] = 'unexpected http response';

                    break;
            }
        }else{
            $fReturn['status'] = 'api error';
            $fReturn['value'] = 'no http response';
        }


        return $fReturn;


    }

    public static function mGeoCodeLatLon($fLat, $fLon)
    {

        $mReturn = ['status' => 'unknown'];

        // make request
        $sGeocodeURL  =
        'http://api.opencagedata.com/geocode/v1/json?no_annotations=1&q='.urlencode($fLat).'+'.urlencode($fLon).'&key='.urlencode(env('API_OPEN_CAGE_KEY'));

        // echo $sGeocodeURL."<br/><br/>";

        $json = @file_get_contents($sGeocodeURL);

        list($version, $status_code, $msg) = explode(' ',$http_response_header[0], 3);

        // Check the HTTP Status code
        switch($status_code)
        {
            case 200:
                $oObj = json_decode($json);

                $aGeoData = [];

                if(isset($oObj->results) && isset($oObj->results[0])){

                    $res = $oObj->results[0];
                    $sFormattedAddress = $res->formatted;
                    // printf($sFormattedAddress);

                    $aComponents = [];
                    foreach($res->components as $k => $c)
                    {
                        array_push($aComponents, ['type' => $k, 'value' => $c]);
                    }

                    $aGeoData['formatted'] = $sFormattedAddress;
                    $aGeoData['components'] = $aComponents;

                    $mReturn['status'] = 'success';
                    $mReturn['value'] = $aGeoData;

                }

                break;
            case 402:
                // throttled
                $mReturn['status'] = 'throttled';
                $mReturn['value'] = 'http 402 throttle';
                break;
            default:
                logger(['geocode error, unmapped status response from opencage', $status_code]);
                $mReturn['status'] = 'error';
                $mReturn['value'] = 'non 200/402 status returned';
                break;
        }

        return $mReturn;

    }

    public static function mAWSFaceDetect($iPiciliFileId, $bDebug = false)
    {

        $mReturn = ['status' => 'unknown'];

        $client = new \Aws\Rekognition\RekognitionClient([
            'version' => 'latest',
            'region'  => 'us-east-1',
            'credentials' => [
                'key'    => env('AWS_KEY'),
                'secret' => env('AWS_SECRET')
            ]
        ]);

        $oPiciliFile = PiciliFile::find($iPiciliFileId);
        // $sPath = $oPiciliFile->sTempProcessingFilePath;


        // $handle = fopen($sPath, "rb");
        // $contents = stream_get_contents($handle);
        // fclose($handle);

        $iUserId = $oPiciliFile->user_id;

        if($bDebug)
        {
            $iUserId = 'test';
            $iPiciliFileId = 'lhasa';
        }


        $sXLURL = self::sS3Path($iUserId, $iPiciliFileId, 'xl');
        $sContens = file_get_contents($sXLURL);

        $result = $client->detectFaces([
            'Attributes' => ['ALL'],
            'Image' => [ // REQUIRED
                'Bytes' => $sContens
            ],
        ]);

        // $file = fopen('faces.json',"w");
        // fwrite($file, $result);
        // fclose($file);

        $oFaceDetails = $result['FaceDetails'];

        $mReturn['status'] = 'success';
        $mReturn['value'] = $oFaceDetails;

        return $mReturn;
    }
    public static function aGetFolderComponentsFromDropboxFile($oDropboxFolderSource, $oDropboxFile)
    {
        // parse the path into directories and full directory path minus the file name
        if(isset($oDropboxFile->dropbox_path) && isset($oDropboxFolderSource->folder))
        {
            $sFullPath = $oDropboxFile->dropbox_path;

            // remove folder-source from path
            $sFullPath = str_replace($oDropboxFolderSource->folder, "", $sFullPath);

            // remove leading slash if there
            $sFullPath = ltrim($sFullPath, '/');

            // break by slash
            $saParts = explode('/', $sFullPath);

            // get filename
            $sFileName = array_pop($saParts);

            // get folders (filter out empty)
            $aFolders = array_filter($saParts);

            return [
                'full-path' => $sFullPath,
                'parent-path' => implode('/', $aFolders),
                'folders' => $aFolders,
                'basename' => pathinfo($sFullPath, PATHINFO_BASENAME),
                'extension' => pathinfo($sFullPath, PATHINFO_EXTENSION)
            ];
        }else{
            return [];
        }
    }

    /*
    //
    // tagging
    //
    public static function setTagsToFile($oPiciliFile, $aaTags)
    {
        if(count($aaTags) > 0)
        {
            $aTagsToSet = [];

            foreach($aaTags as $aTag) {
                array_push(
                    $aTagsToSet, 
                    new Tag([
                        'type' => $aTag['type'],
                        'subtype' => isset($aTag['subtype']) ? $aTag['subtype'] : null,
                        'value' => $aTag['value'],
                        'confidence' => $aTag['confidence'],
                        'file_id' => $oPiciliFile->id
                    ])
                );
            }

            $oPiciliFile->addTags($aTagsToSet);
        }
    }
    public static function removeTagsOfType($oPiciliFile, $sType)
    {
		Tag::where('file_id', $oPiciliFile->id)->where('type', $sType)->delete();
    }
    */
}
