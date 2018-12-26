<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Carbon\Carbon;

use App\Library\Helper;
use App\Library\PiciliProcessor;
use App\Library\DropboxHelper;

use Share\DropboxFiles;

use Share\PiciliFile;
use Share\DropboxFilesource;

class WebTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */



    //   public function testTest()
    //   {
    //       $sPath = public_path('processing/58c3959b1109cf2d2c001532.jpg');
      //
    //       if(file_exists($sPath))
    //       {
    //           echo filesize ($sPath);
    //       }else{
    //           echo "no file";
    //       }
    //       $this->assertTrue(true);
    //   }

     public function testDownloadFile()
     {
         // create seed dropbox file with real dropboxfile id
         // download it

         // seed a dropbox folder;
         $oDropboxFolderSource = new DropboxFilesource;
         $oDropboxFolderSource->user_id = 1;
         $oDropboxFolderSource->save();

         $oDropboxFile = new DropboxFiles;
         $oDropboxFile->dropbox_folder_id = $oDropboxFolderSource->id;
         $oDropboxFile->dropbox_id = 'id:qEr0pTQv8p0AAAAAAABkCg';
         $oDropboxFile->user_id = 12;
         $oDropboxFile->size = 12615680;
         $oDropboxFile->sTempFileName = 'fds';
         $oDropboxFile->dropbox_path = 'gdfgfd';
         $oDropboxFile->dropbox_name = 'gdfgfd';
         $oDropboxFile->server_modified = Carbon::now();
         $oDropboxFile->save();

         // $sPath = resource_path('test-temp/'.uniqid().'.jpg');

         $bResp = DropboxHelper::bDownloadDropboxFile(
             $oDropboxFile->id
         );
         print_r($bResp);
         $this->assertTrue($bResp);

         // now change its size and check it 'fails' to download

         $oDropboxFile->size = 12615690;
         $oDropboxFile->save();
         $bResp = DropboxHelper::bDownloadDropboxFile(
             $oDropboxFile->id
         );
         $this->assertFalse($bResp);
         // unset($oDropboxFile);
         // $oDropboxFile = DropboxFiles::find($oDropboxFile->id);

         // check it exists
         $sDownloadedTo = Helper::sTempFilePathForDropboxFile($oDropboxFile->id);
         $this->assertTrue(file_exists($sDownloadedTo));

         unlink($sDownloadedTo);
         $this->assertTrue(!file_exists($sDownloadedTo));

      }

      public function testPullDropboxFeed()
      {
          $oResp = DropboxHelper::listDropboxFolderContents('/test pics', 'SS7HiGIa1ZoAAAAAAAD41dowgqujvLpa8l5Qx6Y9XKiQePm5yz1MZIMuYBapMQ2D');

          $this->assertArrayHasKey('entries', $oResp);
          $this->assertArrayHasKey('cursor', $oResp);
          $this->assertArrayHasKey('has_more', $oResp);
      }


      //
      // tagging APIs
      //

      public function testElevationFromLatLon()
      {
          $fLat = 46.174125;
          $fLon = 2.162933;

          $fExpectedElevation = 456.22;

          $mResp = Helper::mElevationFromLatLon($fLat, $fLon);

          if($mResp['status'] === 'throttled') {
              $this->assertTrue(false);
          }else{
            $this->assertEquals($mResp['value'], $fExpectedElevation);
          }

      }
      public function testGeocodeFromLatLon()
      {
          $fLat = 27.183610;
          $fLon = 100.085723;

          $mResp = Helper::mGeoCodeLatLon($fLat, $fLon);

          if($mResp['status'] === 'throttled') {
              $this->assertTrue(false);
          }else{
            $this->assertArrayHasKey('value', $mResp);
            $this->assertArrayHasKey('formatted', $mResp['value']);
            $this->assertArrayHasKey('components', $mResp['value']);
            $this->assertTrue(isset($mResp));
          }
      }

    public function testImagga()
    {
        $oPiciliFile = new PiciliFile;
        $oPiciliFile->user_id = 1;
        $oPiciliFile->signature = 'sig';
        $oPiciliFile->save();

        $mResp = Helper::mImagga($oPiciliFile->id, true);

        $this->assertTrue(isset($mResp));
    }

    //
    // queue processing
    //
    public function testGeocoding()
    {
        $oPiciliFile = new PiciliFile;
        $oPiciliFile->user_id = 0;
        $oPiciliFile->bHasThumbs = true;
        $oPiciliFile->signature = 'sig-geocode-test';
        $oPiciliFile->latitude = 15;
        $oPiciliFile->longitude = -3;
        $oPiciliFile->save();
        $iTestFileId = $oPiciliFile->id;
        
        PiciliProcessor::mGeocode($iTestFileId);
    }
    public function testDeleteFromS3()
    {
        $sTestFileName = 'kodak_z740.JPG';
        $sTestAWSPath = Helper::sGetAWSBasePath('test').$sTestFileName;
        // assert files not on aws
        $s3 = \Storage::disk('s3');
        $this->assertFalse(\Storage::disk('s3')->exists($sTestAWSPath));

        /// put files on aws
        $oImage = \Image::make(resource_path('test-files/jpegs/'.$sTestFileName));
        $sAWSBaseThumbPath = Helper::sGetAWSBasePath('test');
        Helper::saveStreamToAWS(
            $sTestAWSPath,
            $oImage->stream()->__toString()
        );

        // assert files in aws
        $this->assertTrue(\Storage::disk('s3')->exists($sTestAWSPath));

        /// use function to remove from aws
        $bResponse = Helper::deleteFilesFromAWS([$sTestAWSPath]);

        // assert files not in aws
        $this->assertFalse(\Storage::disk('s3')->exists($sTestAWSPath));
        $this->assertTrue($bResponse);
    }
}
