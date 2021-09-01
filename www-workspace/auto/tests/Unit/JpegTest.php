<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use \App\Library\FileTaggingHelper;
use \App\Library\Helper;

use Share\PiciliFile;
use Share\Task;

class JpegTest extends TestCase
{
    public function testProcessFile()
    {
        $oPiciliFile = new PiciliFile;
        $oPiciliFile->sTempProcessingFilePath = resource_path('test-files/jpegs/gopro_3.JPG');
        $oPiciliFile->user_id = 0;
        $oPiciliFile->signature = 'signature';
        $oPiciliFile->save();

        $this->assertTrue(Helper::bProcessPhysicalFile($oPiciliFile->id));

        // file has a signature now?

        $iFileId = $oPiciliFile->id;
        unset($oPiciliFile);
        $oFoundAgainFile = PiciliFile::find($iFileId);

        $this->assertTrue(isset($oFoundAgainFile->phash));
    }   

    public function testSignatureFile()
    {
        $saSeeds = [
            'kodak_z740.JPG' => [
                "shaone" => "c6140cd84c29a18015f47ddb0ff178482788efee",
                "filesize" => 1443191,
                "signature" => "c6140cd84c29a18015f47ddb0ff178482788efee.1443191"
            ],
            'sony_a69.JPG' => [
                "shaone" => "e1d555a016a93dde3371ebb8ec465d6c9890f1ef",
                "filesize" => 6527406,
                "signature" => "e1d555a016a93dde3371ebb8ec465d6c9890f1ef.6527406"
            ]
        ];
        foreach ($saSeeds as $sSeed => $aV)
        {
            $sSeedFilePath = resource_path('test-files/jpegs/'.$sSeed);
            $aSig = Helper::saSignatureLocalFile($sSeedFilePath);

            $this->assertEquals($aSig, $aV);
        }
    }

    public function testCreateThumbnailsWithJPEGs()
    {
        $sTestFolder = resource_path('test-files/jpegs');
        $saPaths = array_diff(scandir($sTestFolder), ['..', '.']);

        $sLandscape = resource_path('test-files/jpegs/sony_a55.JPG');
        $sTestOutputFolder = resource_path('test-temp');

        $iFakeUserId = uniqid();

        $oPiciliFileLandscape = new PiciliFile;
        $oPiciliFileLandscape->signature = 'sig1';
        $oPiciliFileLandscape->user_id = 0;
        $oPiciliFileLandscape->save();

        $bResp = Helper::bGenerateThumbs($sLandscape, $sTestOutputFolder, $iFakeUserId, $oPiciliFileLandscape->id);


        $oUpdatedPiciliFile = PiciliFile::find($oPiciliFileLandscape->id);

        $this->assertTrue($bResp);
        $this->assertTrue(isset($oUpdatedPiciliFile->medium_width));
        $this->assertTrue(isset($oUpdatedPiciliFile->medium_height));

        // delete all thumb files
        $sThumbDir = $sTestOutputFolder.'/'.$iFakeUserId;
        $saPaths = array_diff(scandir($sThumbDir), ['..', '.']);
        $this->assertEquals(count($saPaths), 5);
        foreach($saPaths as $sPath)
        {
            $sFullPath = $sThumbDir.'/'.$sPath;
            $this->assertTrue(@is_array(getimagesize($sFullPath)));
            // delete $sPath
            unlink($sFullPath);
        }
        rmdir($sThumbDir);
    }

    public function testCreateThumbnailsWithCorruptImages()
    {
        $sTestOutputFolder = resource_path('test-temp');

        $iFakeUserId = uniqid();
        
        // 'broken' japanese pic.. 
        $oPiciliFileBroken = new PiciliFile;
        $oPiciliFileBroken->signature = 'sig2'.$iFakeUserId;
        $oPiciliFileBroken->user_id = rand(0,9999);
        $oPiciliFileBroken->save();

        $sBroken = resource_path('test-files/jpegs/48.jpg');
        $bResp = Helper::bGenerateThumbs($sBroken, $sTestOutputFolder, $iFakeUserId, $oPiciliFileBroken->id);
        $this->assertTrue($bResp);

        // 'broken' turkey pic..   
        $oPiciliFileBrokenTurkey = new PiciliFile;
        $oPiciliFileBrokenTurkey->signature = 'sig3'.$iFakeUserId;
        $oPiciliFileBrokenTurkey->user_id = rand(0,9999);
        $oPiciliFileBrokenTurkey->save();     
        $sBroken = resource_path('test-files/jpegs/440.jpg');
        $bResp = Helper::bGenerateThumbs($sBroken, $sTestOutputFolder, $iFakeUserId, $oPiciliFileBrokenTurkey->id);
        $this->assertFalse($bResp);

        $iFileId = $oPiciliFileBrokenTurkey->id;
        unset($oPiciliFile);
        $oPiciliFileFound = PiciliFile::find($iFileId);
        $this->assertEquals($oPiciliFileFound->bCorrupt, true);
    }

    public function testIsCorrupt()
    {   
        $this->assertEquals(Helper::bIsCorrupt(resource_path('test-files/jpegs/48.jpg')), false);
        $this->assertEquals(Helper::bIsCorrupt(resource_path('test-files/jpegs/gopro_3.JPG')), false);
        
        $this->assertEquals(Helper::bIsCorrupt(resource_path('test-files/jpegs/440.jpg')), true);
        // not corrupt - but from sony a55
        $this->assertEquals(Helper::bIsCorrupt(resource_path('test-files/jpegs/sony_a55_amedi.JPG')), false);
        $this->assertEquals(Helper::bIsCorrupt(resource_path('test-files/jpegs/sony_a55.JPG')), false);
    }

    public function testColourExtraction()
    {
        $aColours = Helper::aGetColours(resource_path('test-files/colour-test/taungi-color-extract-memory-limit-test.JPG'));

        $this->assertTrue(isset($aColours['best']));
        $this->assertTrue(is_integer($aColours['best']['r']));
        $this->assertTrue(is_integer($aColours['best']['g']));
        $this->assertTrue(is_integer($aColours['best']['b']));

        $this->assertTrue(isset($aColours['pallette']));
        $this->assertTrue(is_array($aColours['pallette']));
        $this->assertTrue(count($aColours['pallette']) === 5);

        // test somewhat corrupt picture
        $aCorruptColours = Helper::aGetColours(resource_path('test-files/colour-test/'.$saP[6]));
        $this->assertTrue(isset($aCorruptColours['best']));
    }

    public function testColourExtractionOnImageThatWasSilentlyFailing()
    {
        // tests memory limit is set high enough
        $saP = [
            '13.jpg'
        ];

        $aColours = Helper::aGetColours(resource_path('test-files/colour-test/'.$saP[0]));

        $this->assertTrue(isset($aColours['best']));
        $this->assertTrue(is_integer($aColours['best']['r']));
        $this->assertTrue(is_integer($aColours['best']['g']));
        $this->assertTrue(is_integer($aColours['best']['b']));

        $this->assertTrue(isset($aColours['pallette']));
        $this->assertTrue(is_array($aColours['pallette']));
        $this->assertTrue(count($aColours['pallette']) === 5);
    }

    public function testCanReadExif()
    {
        $sTestFolder = resource_path('test-files/jpegs');
        $saPaths = array_diff(scandir($sTestFolder), ['..', '.']);

        foreach($saPaths as $sTestFile)
        {
            $sFullPath = resource_path('test-files/jpegs/'.$sTestFile);

            $aExifData = FileTaggingHelper::aExifDataFromImagePath($sFullPath);

            $this->assertTrue(is_array($aExifData));

            if (!isset($aExifData['datetime'])) {
                logger('exif date not set..', $aExifData);
            }

            $this->assertTrue(isset($aExifData['cameramake']));
            $this->assertTrue(isset($aExifData['cameramodel']));
            $this->assertTrue(isset($aExifData['orientation']));
            $this->assertTrue(isset($aExifData['datetime']));

            // some with altitude/lat/lon
            if(
                $sTestFile === 'sony_a55.JPG'
                ||
                $sTestFile === 'sony_a55_amedi.JPG'
                ||
                $sTestFile === 'complex-geo.jpg'
            ){
                $this->assertTrue(isset($aExifData['altitude']));
                $this->assertTrue(isset($aExifData['latitude']));
                $this->assertTrue(isset($aExifData['longitude']));
            }
        }

        // test invalid geo data doesn't raise anything
        $aExifData = FileTaggingHelper::aExifDataFromImagePath(resource_path('test-files/jpegs/invalid-geo.jpg'));

        $this->assertFalse(isset($aExifData['latitude']));
        $this->assertFalse(isset($aExifData['longitude']));

        // test datetime is read from exif not file
        $aExifData = FileTaggingHelper::aExifDataFromImagePath(resource_path('test-files/jpegs/multi-date.JPG'));

        $this->assertEquals($aExifData['datetime'], '2017:09:23 17:06:25');
    }
    public function testAltitudeExifStringToNumber()
    {
        $saKeyValueExpect = [
            '11787/10' => 1178.7,
            ' 512/10' => 51.2,
            'giraffe' => false,
            '56' => 56,
            '56/0' => false
        ];

        foreach($saKeyValueExpect as $k => $v)
        {
            $this->assertEquals(Helper::fAltitudeExifStringToNumber($k), $v);
        }
    }

    public function testQueueForAltitudeProcesingIfGPS()
    {
        //
        // seed picili files and exif data
        //
        $oNoGeoData = new PiciliFile;
        $oNoGeoData->signature = 'sam';
        $oNoGeoData->user_id = 0;
        $oNoGeoData->save();
        $aExifNoGeoData = [];

        $oGPSOnly = new PiciliFile;
        $oGPSOnly->signature = 'gpssig';
        $oGPSOnly->user_id = 0;
        $oGPSOnly->save();
        $aExifJustGPS = ['latitude' => 0, 'longitude' => 0];

        $oLatLonAlt = new PiciliFile;
        $oLatLonAlt->signature = 'latlonaltsig';
        $oLatLonAlt->user_id = 0;
        $oLatLonAlt->save();
        $aExifLatLonAltData = ['latitude' => 0, 'longitude' => 0, 'altitude' => 0];

        $oWithDate = new PiciliFile;
        $oWithDate->signature = 'datesig';
        $oWithDate->user_id = 0;
        $oWithDate->save();
        $aExifDateData = ['datetime' => '2016:07:08 13:29:45'];

        //
        // no gps
        //
        Helper::ConditionalHandleExifdata($oNoGeoData, $aExifNoGeoData);

        // no task queued
        $oTaskNotFound = Task::where('related_file_id', $oNoGeoData->id)->first();
        $this->assertFalse(isset($oTaskNotFound));

        // picili file has bHasAltitude=false and bHasGPS=false
        $this->assertEquals($oNoGeoData->bHasAltitude, false);
        $this->assertEquals($oNoGeoData->bHasGPS, false);

        //
        // has gps but no altitude
        //
        Helper::ConditionalHandleExifdata($oGPSOnly, $aExifJustGPS);

        // queued for altitude
        $oAltitudeTask = Task::where('related_file_id', $oGPSOnly->id)->first();
        $this->assertTrue(isset($oAltitudeTask));

        // picili file has bHasGPS=true and bHasAltitude=false
        $this->assertEquals($oGPSOnly->bHasGPS, true);
        $this->assertEquals($oGPSOnly->bHasAltitude, false);

        //
        // has gps and altitude
        //
        Helper::ConditionalHandleExifdata($oLatLonAlt, $aExifLatLonAltData);

        // picili file has bHasGPS=true and bHasAltitude=true
        $this->assertEquals($oLatLonAlt->bHasGPS, true);
        $this->assertEquals($oLatLonAlt->bHasAltitude, true);

        // no task queued
        $oTaskNotNecessary = Task::where('related_file_id', $oLatLonAlt->id)->first();
        $this->assertFalse(isset($oTaskNotNecessary));

        // has date set
        Helper::ConditionalHandleExifdata($oWithDate, $aExifDateData);
        $this->assertTrue(isset($oWithDate->datetime));

        // has date literals
        $oDateLiteralTags = $oWithDate->tags->where('type', '=', 'dateliteral');
        $this->assertTrue(count($oDateLiteralTags) > 0);
    }
}
