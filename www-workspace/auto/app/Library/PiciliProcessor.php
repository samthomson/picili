<?php

namespace App\Library;

use DB;

use \Carbon\Carbon;

use Share\Task;
use Share\DropboxFilesource;
use Share\PiciliFile;

use App\Library\DropboxHelper;
use SharedLibrary\TagHelper;
use SharedLibrary\ElasticHelper;


class PiciliProcessor {

    //
    // task processing
    //
    public static function bProcessQueue()
    {
        // pick next task
        $oNextTask = self::taskGetNextTaskToProcess();
        $mtStarted = microtime(true);
        $mResult = false;
        $mExtra = null;
        if(isset($oNextTask) && isset($oNextTask->processor))
        {
            switch($oNextTask->processor)
            {
                case 'full-dropbox-import':
                    $mResult = DropboxHelper::checkFileSource($oNextTask->related_file_id);
                    if($mResult['success']){
                        // queue next step - phys
                        Helper::completeATask($oNextTask->id);
                    } else {
                        if(isset($mResult['error']) && isset($mResult['error']['type'])) {
                            switch($mResult['error']['type'])
                            {
                                case 'curl-error':
                                    // probably http error, try again quickly
                                    $iTryInSeconds = $mResult['error']['retry_after'];
                                    $oTask = Task::find($oNextTask->id);

                                    if(isset($oTask
                                    ))
                                    {
                                        $oTask->dDateAfter = Carbon::now()->addSeconds(10);
                                        $oTask->save();
                                    }
                                    break;
                                case 'throttled':
                                    // probably http error, try again quickly
                                    $iTryInSeconds = $mResult['error']['retry_after'];
                                    $oTask = Task::find($oNextTask->id);

                                    if(isset($oTask
                                    ))
                                    {
                                        $oTask->dDateAfter = Carbon::now()->addSeconds($iTryInSeconds);
                                        $oTask->save();
                                    }
                                    break;
                                
                            }
                        }
                    }
                    break;
                case 'download-dropbox-file':
                    $mResult = self::bDownloadDropboxFile($oNextTask->related_file_id);
                    if($mResult['success']){
                        // queue next step - phys
                        Helper::completeATask($oNextTask->id);
                    }
                    break;

                case 'import-new-dropbox-file':
                    $mResult = self::bImportDownloadedDropboxFile($oNextTask->related_file_id);
                    if($mResult['success']){
                        // queue next step - phys
                        Helper::completeATask($oNextTask->id);
                        // Helper::QueueAnItem('physical-file', $iRelatedId, $iTaskDependentOn = null, $oDateFrom = null, $bImporting = true)
                    }
                    break;
                /**/
                case 'import-changed-dropbox-file':
                    $mResult = DropboxHelper::mergeDownloadedDropboxFile($oNextTask->related_file_id);
                    if($mResult['success']){
                        // queue next step - phys
                        Helper::completeATask($oNextTask->id);
                    }
                    break;                

                case 'physical-file':
                    $mResult = self::bProcessPhysicalFile($oNextTask->related_file_id);
                    if($mResult['success']){
                        Helper::completeATask($oNextTask->id);
                    }else{
                        logger('physical file processor was not succesful');
                    }
                    break;

                case 'altitude':
                    $mResult = self::mAltitudeEncode($oNextTask->related_file_id);
                    if($mResult['success']){
                        Helper::completeATask($oNextTask->id);
                    }else{
                        // log error
                        if($mResult['error'] === 'throttled') {
                            logger('altitude api is throttling, so delay all altitude tasks one day');
                            Helper::delayProcessorsTasks('altitude', 1);
                        }else{
                            $mExtra = $mResult['error'];
                            logger('altitude error: '. $mExtra);
                        }
                    }
                    break;

                case 'geocode':
                    $mResult = self::mGeocode($oNextTask->related_file_id);
                    if($mResult['success']){
                        Helper::completeATask($oNextTask->id);
                    }else{
                        // log error
                        if($mResult['error'] === 'throttled') {
                            //// Helper::delayATask($oNextTask, 1);
                            logger('geocode api is throttling, so delay all geocode tasks one day');
                            Helper::delayProcessorsTasks('geocode', 1);
                        }else{
                            $mExtra = $mResult['error'];
                            logger('geocode error: '. $mExtra);
                        }
                    }

                    break;
                case 'subject-recognition':
                    $mResult = self::mSubjectDetect($oNextTask->related_file_id);
                    if($mResult['success']){
                        Helper::completeATask($oNextTask->id);
                    }else{
                        // log error
                        $mExtra = $mResult['error'];
                        logger('subject recognition error: '. $mExtra);
                    }
                    break;
                case 'face-detection':
                    $mResult = self::mFaceDetect($oNextTask->related_file_id);
                    if($mResult['success']){
                        Helper::completeATask($oNextTask->id);
                    }else{
                        // log error
                        $mExtra = $mResult['error'];
                    }
                    break;
                    
                case 'remove-from-s3':
                    $mResult = self::bRemoveFromS3($oNextTask->related_file_id);
                    if($mResult['success']){
                        Helper::completeATask($oNextTask->id);
                    }
                    break;

                case 'remove-from-elastic':
                    $mResult = self::bRemoveFromElastic($oNextTask->related_file_id);
                    if($mResult['success']){
                        Helper::completeATask($oNextTask->id);
                    }
                    break;

                case 'delete-processing-file':
                    $bResult = Helper::bRemoveProcessingfile($oNextTask->related_file_id);

                    $mResult = [
                        'success' => ($bResult ? true : false)
                    ];

                    if($bResult){
                        Helper::completeATask($oNextTask->id);
                    }
                    break;
                    
                default:
                    // do nothing
                    break;
            }
            Helper::LogProcessorRun($oNextTask->processor, $oNextTask->id, $oNextTask->related_file_id, $mResult, $mtStarted, $mExtra);
            return true;
        }else{
            return false;
        }

        unset($oNextTask);
    }

    public static function taskGetNextTaskToProcess()
    {
        // get AND 'start' the task
        // get items that haven't been started yet and are not scheduled in the future
        // $oTask = DB::connection('mysql-queue')->transaction(function () {

        $dNow = Carbon::now();
        $dFiveMins = Carbon::now()->addMinutes(5);

        /**/
        usleep(rand(0,1000000));
        $oQuery = Task::where("dDateAfter", "<", $dNow)
            ->where(function($query){
                // not dependent on anything
                $query->where("iAfter", '=', -1);
            });

        $oTask = $oQuery
            ->orderBy("priority", "desc")
            ->orderBy("bImporting", "asc")
            ->orderBy("dDateAfter", "asc")
            ->first();

        if ($oTask !== null) {
            $oTask->dDateAfter = $dFiveMins;
            if(!isset($oTask->iTimesSeenByProccessor))
            {
                $oTask->iTimesSeenByProccessor = 1;
            }else{
                $oTask->iTimesSeenByProccessor = $oTask->iTimesSeenByProccessor  + 1;
            }
            $oTask->save();
        }

        return (isset($oTask)) ? $oTask : null;
    }

    public static function bDownloadDropboxFile($iDropboxFileId)
    {
        $bResult = DropboxHelper::bDownloadDropboxFile($iDropboxFileId);
        return [
            'success' => ($bResult ? true : false)
        ];
    }
    public static function bImportDownloadedDropboxFile($iDropboxFileId)
    {
        $bResult = DropboxHelper::checkDownloadedDropboxFile($iDropboxFileId);
        return [
            'success' => ($bResult ? true : false)
        ];
    }

    public static function bProcessPhysicalFile($iPiciliFileId)
    {
        try{
            $bResult = Helper::bProcessPhysicalFile($iPiciliFileId);
            return [
                'success' => ($bResult ? true : false)
            ];
        }catch(Exception $e)
        {
            logger(['process physical file, exception: '.$e]);
            return false;
        }
    }

    public static function bRemoveFromS3($iPiciliFileId) {
        // delete all created thumbs
        $bResult = Helper::bDeleteThumbsFromAWS($iPiciliFileId);
        return [
            'success' => ($bResult ? true : false)
        ];
    }

    public static function bRemoveFromElastic($iPiciliFileId) {
        // delete the document
        $bResult = ElasticHelper::bDeleteFromElastic($iPiciliFileId);
        return [
            'success' => ($bResult ? true : false)
        ];
    }


    //
    // queue stuff
    //
    public static function FirstQueueOfPiciliFile($oPiciliFile)
    {
        $iPhysicalTaskId = Helper::QueueAnItem('physical-file', $oPiciliFile->id, $oPiciliFile->user_id);

        // subject detection
        $iSubjectRecognitionTask = Helper::QueueAnItem('subject-recognition', $oPiciliFile->id, $oPiciliFile->user_id, $iPhysicalTaskId);

        Helper::QueueAnItem('delete-processing-file', $oPiciliFile->id, $oPiciliFile->user_id, $iSubjectRecognitionTask, null, false);
    }
    public static function RediscoveryQueueOfPiciliFile($oPiciliFile)
    {
        $iPhysicalTaskId = Helper::QueueAnItem('physical-file', $oPiciliFile->id, $oPiciliFile->user_id);

        // TODO - decide on whether these should be fired

        Helper::QueueAnItem('subject-recognition', $oPiciliFile->id, $oPiciliFile->user_id, $iPhysicalTaskId);
        // no // Helper::QueueAnItem('face-detection', $oPiciliFile->id, $iPhysicalTaskId);

    }

    //
    // file analysis
    //
    public static function assesPhysicalFile()
    {
        // take a downloaded physical file and

    }
    public static function aGetColours($sFullPath)
    {
        /*
        OLD WAY:


        $pallete = new ImagePalette($sLocalFilePath);
        return json_decode(json_encode($pallete->getColors($iColourLimit)), true);

        */

        // read in file, get main colour and pallette

        $palette = Palette::fromFilename($sFullPath);
        $extractor = new ColorExtractor($palette);

        // it defines an extract method which return the most “representative” colors
        $colours = $extractor->extract(5);
        return $colours;
        // return $palette->getMostUsedColors(5);
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

    //
    // thumbs
    //
    public static function generateThumbs($sInputFilePath, $sOutputFolder, $iUserId, $iFileId)
    {
        $oImage = \Image::make($sInputFilePath);

        $saCreatedThumbs = [];
        $iMediumWidth = null;
        $iMediumHeight = null;

        if ($oImage !== false)
        {
            $oImage->orientate();

            if(isset($oImage))
            {

                $sBaseThumbPath = $sOutputFolder.'/'. $iUserId .'/';


                // lightbox (extra large)
                $oImage->resize(1620, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $sNewPath = $sBaseThumbPath.$iFileId.'xl.jpg';
                array_push($saCreatedThumbs, $sNewPath);
                $oImage->save($sNewPath);

                // lightbox
                $oImage->resize(1120, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $sNewPath = $sBaseThumbPath.$iFileId.'l.jpg';
                array_push($saCreatedThumbs, $sNewPath);
                $oImage->save($sNewPath);

                // thumb results
                $oImage->resize(null, 300, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $sNewPath = $sBaseThumbPath.$iFileId.'m.jpg';
                array_push($saCreatedThumbs, $sNewPath);
                $oImage->save($sNewPath);

                $iMediumWidth = $oImage->width();
                $iMediumHeight = $oImage->height();

                // square
                $oImage->fit(125, 125);

                $sNewPath = $sBaseThumbPath.$iFileId.'s.jpg';
                array_push($saCreatedThumbs, $sNewPath);
                $oImage->save($sNewPath);


                // icon
                $oImage->fit(32, 32);
                $sNewPath = $sBaseThumbPath.$iFileId.'i.jpg';
                array_push($saCreatedThumbs, $sNewPath);
                $oImage->save($sNewPath);


                $oImage->destroy();
                unset($oImage);

            }else{
                // image corrupt?
                echo "\nImage corrupt, can't thumb";
            }
        }else{
            // image corrupt?
            echo "\nImage corrupt 2, can't thumb";
        }
    }

    //
    // external apis
    //
    public static function mAltitudeEncode($iPiciliFileId)
    {
        $oPiciliFile = PiciliFile::find($iPiciliFileId);

        $mElevation = Helper::mElevationFromLatLon($oPiciliFile->latitude, $oPiciliFile->longitude);


        if (isset($mElevation) && isset($mElevation['status']))
        {
            switch($mElevation['status'])
            {
                case 'success':
                    $oPiciliFile->altitude = $mElevation['value'];
                    $oPiciliFile->bHasAltitude = true;
                    $oPiciliFile->save();
                    return [
                        'success' => true
                    ];
                    break;
                case 'throttled':
                    return [
                        'success'=> false,
                        'error' => $mElevation['status']
                    ];
                    break;
                default:
                    logger(['altitude unsuccesful - unexpected status: '.$mElevation['status']]);
                    return ['success'=> false, 'error' => $mElevation['status']];
            }
        }

        return ['success'=> false, 'error' => 'unknown error'];
    }

    public static function mGeocode($iPiciliFileId)
    {
        $oPiciliFile = PiciliFile::find($iPiciliFileId);

        if(isset($oPiciliFile->latitude) && isset($oPiciliFile->longitude)) {
            $mGeodata = Helper::mGeoCodeLatLon($oPiciliFile->latitude, $oPiciliFile->longitude);


            if (isset($mGeodata) && isset($mGeodata['status']))
            {
                switch($mGeodata['status'])
                {
                    case 'success':
                        $oGeoData = $mGeodata['value'];

                        $sFormattedAddress = $oGeoData['formatted'];
                        $aComponents = $oGeoData['components'];

                        $oPiciliFile->address = $sFormattedAddress;
                        $oPiciliFile->save();

                        $aGeoTags = [];

                        foreach($aComponents as $aComponentTag)
                        {
                            array_push($aGeoTags,
                            [
                                'type' => 'opencage',
                                'subtype' => $aComponentTag['type'],
                                'value' => $aComponentTag['value'],
                                'confidence' => 45
                            ]);
                        }

                        TagHelper::removeTagsOfType($oPiciliFile, 'opencage');
                        TagHelper::setTagsToFile($oPiciliFile, $aGeoTags);

                        
                        return ['success'=> true];
                        break;
                    case 'throttled':
                        return ['success'=> false, 'error' => $mGeodata['status']];
                        break;
                    default:
                        return ['success'=> false, 'error' => $mGeodata['status']];
                        break;
                }
            }
        }else{
            return ['success'=> false, 'error' => 'no lat long set on picili file'];
        }

        return ['success'=> false, 'error' => 'unknown error'];
    }

    public static function mSubjectDetect($iPiciliFileId)
    {
        $mResp = Helper::mImagga($iPiciliFileId);
        $oPiciliFile = PiciliFile::find($iPiciliFileId);

        if(isset($mResp['status']) && $mResp['status'] === 'success')
        {
            if(isset($mResp['tags']))
            {
                $aImaggaTags = [];

                foreach($mResp['tags'] as $aTag)
                {
                    array_push($aImaggaTags,
                    [
                        'type' => 'imagga',
                        'value' => $aTag['tag'],
                        'confidence' => $aTag['confidence']
                    ]);
                }

                TagHelper::removeTagsOfType($oPiciliFile, 'imagga');
                TagHelper::setTagsToFile($oPiciliFile, $aImaggaTags);
            } else {
                logger('imagga returned 0 tags for file: '.$iPiciliFileId);
            }

            return ['success'=> true];
        }else{
            return ['success'=> false, 'error' => $mResp['status']];
        }
    }

    public static function mFaceDetect($iPiciliFileId)
    {
        $mReturn = Helper::mAWSFaceDetect($iPiciliFileId);
        $oPiciliFile = PiciliFile::find($iPiciliFileId);

        if(isset($mReturn['status']) && $mReturn['status'] === 'success')
        {
            $oPiciliFile->awsfaces = $mReturn['value'];
            $oPiciliFile->save();
            return ['success'=> true];
        }else{
            return ['success'=> false, 'error' => $mReturn['status']];
        }
    }

}
