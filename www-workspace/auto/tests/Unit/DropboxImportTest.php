<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use \Carbon\Carbon;

use \App\Library\Helper;
use \App\Library\DropboxHelper;

use Share\PiciliFile;
use Share\DropboxFilesource;
use \App\Models\DropboxFiles;

class DropboxImportTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */


    public function testGetFolderComponentsFromDropboxFile()
    {
        $oDropboxSource = new DropboxFilesource;
        $oDropboxSource->folder = '/test pics';

        $oDropboxFile = new DropboxFiles;
        $oDropboxFile->dropbox_path = '/test pics/subfolder/100_8870.jpg';

        $aResult = Helper::aGetFolderComponentsFromDropboxFile(
            $oDropboxSource,
            $oDropboxFile
        );
        $this->assertEquals('subfolder', $aResult['parent-path']);
        $this->assertEquals(['subfolder'], $aResult['folders']);

        // print_r($aResult);

        $oDropboxFile = new DropboxFiles;
        $oDropboxFile->dropbox_path = '/test pics/nested/subfolder/100_8870.jpg';

        $aResult = Helper::aGetFolderComponentsFromDropboxFile(
            $oDropboxSource,
            $oDropboxFile
        );
        $this->assertEquals('nested/subfolder', $aResult['parent-path']);
        $this->assertEquals(['nested', 'subfolder'], $aResult['folders']);

        // print_r($aResult);
    }

    public function testDealWithNewPhysicalDropboxFileBasedOnStatus()
    {
        $iTestUserId = 632;
        $iDropboxId = 'fake_id';

        $oDropboxSource = new DropboxFilesource;
        $oDropboxSource->folder = '/test pics';
        $oDropboxSource->user_id = $iTestUserId;
        $oDropboxSource->access_token = 'giraffe';
        $oDropboxSource->save();

        $oDropboxFile = new DropboxFiles;
        $oDropboxFile->user_id = $iTestUserId;
        $oDropboxFile->dropbox_path = '/test pics/subfolder/100_8870.jpg';
        $oDropboxFile->dropbox_id = $iDropboxId;
        $oDropboxFile->dropbox_name = 'fake_name';
        $oDropboxFile->dropbox_folder_id = $oDropboxSource->id;
        $oDropboxFile->sTempFileName = Helper::sTempFilePathForDropboxFile($iDropboxId);
        $oDropboxFile->server_modified = Carbon::now();
        $oDropboxFile->size = 5675456;
        $oDropboxFile->save();

        $oTestFile = PiciliFile::where('user_id', $iTestUserId)->first();
        $this->assertFalse(isset($oTestFile->dropbox_filesource_id));
        
        unset($oTestFile);

        DropboxHelper::sDealWithNewPhysicalDropboxFileBasedOnStatus(
            $oDropboxFile,
            $oDropboxSource,
            'new',
            'signature.'.uniqid()
        );

        $oFindAgainTestFile = PiciliFile::where('user_id', $iTestUserId)->first();
        $this->assertTrue(isset($oFindAgainTestFile));
        $this->assertEquals('subfolder', $oFindAgainTestFile->sParentPath);

        // print_r($oTags);
        $oFolderTag = $oFindAgainTestFile->tags->where('value', 'subfolder')->where('type', 'folder')->first();
        
        $this->assertEquals('subfolder', $oFolderTag->value);
        $this->assertTrue(isset($oFindAgainTestFile->dropbox_filesource_id));

        // check filename was stored
        $this->assertEquals($oFindAgainTestFile->baseName, '100_8870.jpg');
        $this->assertEquals($oFindAgainTestFile->extension, 'jpg');
    }

    public function testListDropboxFolderContents()
    {
        /*
        tests DropboxHelper::listDropboxFolderContents
        
        should return a list of files, but also a status. 
        dropbox might return error status' or the request might fail altogether. 

                
        */

        // call api, get back a status ok and array of files

        // call api under throttle, get status throttled
    }
}
