<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use \Carbon\Carbon;

use App\Library;
use App\Library\Helper;
use App\Library\DropboxHelper;
use SharedLibrary\ElasticHelper;
use App\Library\PiciliProcessor;

use Share\Task;
use Share\PiciliFile;
Use Share\DropboxFilesource;
Use Share\DropboxFiles;


class PiciliProcessorTest extends TestCase
{
    public function testProcessNewFile()
    {
        /*
        should schedule a new file for all the processors
        with some tasks dependent on another.
        */
        $oFakeNewPiciliFile = new PiciliFile;
        $oFakeNewPiciliFile->id = 10;
        $oFakeNewPiciliFile->user_id = 1;

        PiciliProcessor::FirstQueueOfPiciliFile($oFakeNewPiciliFile);

        $oaTasks = Task::where('related_file_id', $oFakeNewPiciliFile->id)->get();

        // physical
        // subject
        // delete processing path
        $this->assertTrue(count($oaTasks) === 3);
    }

    public function testAddRemoveSourceToPiciliFile()
    {
        $oTestFile = new PiciliFile;
        $oTestFile->user_id = 0;
        $oTestFile->signature = 'fds';
        $oTestFile->save();

        $iId = $oTestFile->id;
        unset($oTestFile);

        $iDropboxId = 47;
        Helper::addSourceToPiciliFile($iId, 'dropbox', $iDropboxId);

        $oTestFile = PiciliFile::find($iId);

        $this->assertEquals($oTestFile->dropbox_filesource_id, $iDropboxId);
        $this->assertFalse($oTestFile->aSources() === []);
        unset($oTestFile);

        $oTestFile = PiciliFile::find($iId);

        $this->assertTrue(isset($oTestFile->dropbox_filesource_id));
        Helper::removeSourceFromPiciliFile($oTestFile, 'dropbox');
        
        // $oTestFile = PiciliFile::find($iId);
        $this->assertFalse(isset($oTestFile->dropbox_filesource_id));
        
        $this->assertEquals($oTestFile->aSources(), []);

    }

    public function testDetermineExistingFileStatus()
    {
        /*
        test with two local physical files each with
         corresponding dropbox file, but only one with
         an existing picili file with same signature.
         should return either:
         - new
         - active
         - deleted
         */

        $oDeadPiciliFile = new PiciliFile;
        $oDeadPiciliFile->signature = 'no source';
        $oDeadPiciliFile->bDeleted = true;
        $oDeadPiciliFile->user_id = 0;
        $oDeadPiciliFile->save();

        $oInstaPiciliFile = new PiciliFile;
        $oInstaPiciliFile->signature = 'insta';
        $oInstaPiciliFile->user_id = 0;
        $oInstaPiciliFile->save();

        $this->assertEquals('new', Helper::sPicilFileStatusFromSignature('unseen'));
        $this->assertEquals('active', Helper::sPicilFileStatusFromSignature('insta'));
        $this->assertEquals('deleted', Helper::sPicilFileStatusFromSignature('no source'));
    }
    public function testGetNextTaskToProcess()
    {
        // QueueAnItem($sProcessorName, $iRelatedId, $iTaskDependentOn = null, $oDateFrom = null, $bImporting = true)

        // empty queue returns null
        $this->assertTrue(PiciliProcessor::taskGetNextTaskToProcess() === null);

        // can't get item from future
        $iFutureTask = Helper::QueueAnItem('test', 5, 1, null, Carbon::now()->addDays(1));

        $this->assertTrue(PiciliProcessor::taskGetNextTaskToProcess() === null);
        $oDelete = Task::find($iFutureTask);
        $oDelete->delete();

        // can't get item that is dependent on another task
        $iDependent = Helper::QueueAnItem('dep test', 5, 1, 2);
        $this->assertTrue(PiciliProcessor::taskGetNextTaskToProcess() === null);

        // can get a task otherqise
        Helper::QueueAnItem('asap', 5, 1);
        $oTaskSearch = PiciliProcessor::taskGetNextTaskToProcess();

        // echo "\n\nTASK: \n";
        // print_r($oTaskSearch);
        // echo "\n\nEND: \n";
        $this->assertTrue(isset($oTaskSearch));
        $this->assertEquals($oTaskSearch->processor, 'asap');

        // test tasks are taken ordered by their priority
        
        // add two tasks, with different start from dates, and test that although one is to be started later, the task with higher priority is picked first
        Task::truncate();

        Helper::QueueAnItem('test-priority-one', 5, 1, null, Carbon::now(), true, 10);
        Helper::QueueAnItem('test-priority-two', 5, 1, null, Carbon::now()->addMinutes(-10));

        $oPrioritisedTaskSearch = PiciliProcessor::taskGetNextTaskToProcess();
        $this->assertTrue(isset($oPrioritisedTaskSearch));
        $this->assertEquals($oPrioritisedTaskSearch->processor, 'test-priority-two');
    }

    public function testDeletingTasksArePrioritised()
    {
        // seed two normal tasks, one for now, one for five mins ago
        Helper::QueueAnItem('normal-1', 5, 1, null, null, true);
        Helper::QueueAnItem('normal-2', 5, 1, null, Carbon::now()->addMinutes(-5), true);

        // get next task, assert it is the task for five mins ago
        $oNextTask = PiciliProcessor::taskGetNextTaskToProcess();
        $this->assertEquals($oNextTask->processor, 'normal-2');

        // seed a deleting task for now
        Helper::QueueAnItem('deleting-task', 5, 1, null, null, false);

        // get next task, assert it is the deleting task
        $oSearchForTaskAgain = PiciliProcessor::taskGetNextTaskToProcess();
        $this->assertEquals($oSearchForTaskAgain->processor, 'deleting-task');
    }

    public function testRemoveImportTasksForAFileNowDeleted()
    {
        // create dropbox file with certain id       
        $sTestPath = 'test-path.jpg';
        $oDropboxFile = new DropboxFiles;
        $oDropboxFile->user_id = 0;
        $oDropboxFile->dropbox_path = $sTestPath;
        $oDropboxFile->dropbox_id = 0;
        $oDropboxFile->dropbox_name = 0;
        $oDropboxFile->server_modified = Carbon::now();
        $oDropboxFile->size = 54;
        $oDropboxFile->dropbox_folder_id = 54;
        $oDropboxFile->sTempFileName = 'dfsf';
        $oDropboxFile->save();

        // create associated picili file

        $oPiciliFile = new PiciliFile;
        $oPiciliFile->signature = 'fdfdf';
        $oPiciliFile->user_id = 0;
        $oPiciliFile->save();

        Helper::addSourceToPiciliFile($oPiciliFile->id, 'dropbox', $oDropboxFile->id);

        // create some import tasks
        $iFileId = $oPiciliFile->id;
        Helper::QueueAnItem('normal-1', $iFileId, 1);
        Helper::QueueAnItem('normal-2', $iFileId, 1);
        Helper::QueueAnItem('normal-3', $iFileId, 1);

        // create some clean-up/delete tasks
        Helper::QueueAnItem('delete-1', $iFileId, 1, null, null, false);
        Helper::QueueAnItem('delete-2', $iFileId, 1, null, null, false);

        // call handle delete function
        DropboxHelper::handleDeletedFileEvent($sTestPath);

        // test the import tasks are gone but the clean-up task remain
        $cImportTasks = Task::where('related_file_id', $iFileId)->where('bImporting', true)->count();
        $cDeleteTasks = Task::where('related_file_id', $iFileId)->where('bImporting', false)->count();

        $this->assertEquals(0, $cImportTasks);
        // 2 above plus the 3 delete tasks the handle delete function creates
        $this->assertEquals($cDeleteTasks, 5);
    }

    public function testProcessQueue()
    {
        // seed a task
        $iTaskId = Helper::QueueAnItem('test task', 5, 1);
        $this->assertFalse(isset($oTask->iTimesSeenByProccessor));

        // run processor
        PiciliProcessor::bProcessQueue();

        // check seed task is schedule for 5 mins away
        $oTask = Task::find($iTaskId);
        $this->assertEquals($oTask->iTimesSeenByProccessor, 1);
    }

    public function testRequeueFileImportAfterCompletion()
    {
        // processing a file import tast should re queue it for x minutes time
        $sDropboxImportTaskName = 'full-dropbox-import';
        $iTaskId = Helper::QueueAnItem($sDropboxImportTaskName, 5, 1);
        // assert tasks is in queue
        $this->assertTrue(Task::find($iTaskId) !== null);

        // assert no other file import task is there
        $this->assertTrue(count(Task::where('processor', $sDropboxImportTaskName)->get()) === 1);

        // process task, then assert it is gone
        // PiciliProcessor::bProcessQueue();
        Helper::completeATask($iTaskId);
        $this->assertTrue(Task::find($iTaskId) === null);

        // assert new task is there for importing files, from ~x mins away
        $this->assertTrue(count(Task::where('processor', $sDropboxImportTaskName)->get()) === 1);
    }

    public function testDelayProcessorsTasks()
    {
        // add three tasks to be done immediately
        $iTaskId = Helper::QueueAnItem('test-queue', 1, 1);
        $iTaskId = Helper::QueueAnItem('test-queue', 2, 1);
        $iTaskId = Helper::QueueAnItem('test-queue', 3, 1);

        // assert they are to be done within next day
        $oTaskSearch = PiciliProcessor::taskGetNextTaskToProcess();
        $this->assertTrue(isset($oTaskSearch));
        unset($oTaskSearch);

        // delay them 2 days
        Helper::delayProcessorsTasks('test-queue', 2);

        // assert they are not within next day
        $oTaskSearch = PiciliProcessor::taskGetNextTaskToProcess();
        $this->assertFalse(isset($oTaskSearch));

        // assert they are to be done over a day away
    }
    public function testOneFolderPerUserLimit()
    {
        // create a folder for user
        $iUser = 52;
        $oFirst = Helper::oAddDropboxFolderSource('token', '/test', $iUser);
        // assert it exists
        $this->assertTrue(count(DropboxFilesource::where('user_id', $iUser)->get()) === 1);

        // try to add another
        Helper::oAddDropboxFolderSource('token', '/test', $iUser);
        // assert it does not exist
        $this->assertTrue(count(DropboxFilesource::where('user_id', $iUser)->get()) === 1);

        $oFirst->delete();
    }

    public function testDeleteProcessingFile()
    {
        // create picili file and seed processing path on it
        $sTempFile = resource_path('test-files/jpegs/sony_a55.JPG');
        $sTempFileProcessing = public_path('processing/temp.JPG');
        // copy temp file to processing folder
        \File::copy($sTempFile, $sTempFileProcessing);
        $oDeleteAttachedFile = new PiciliFile;
        $oDeleteAttachedFile->user_id = 48;
        $oDeleteAttachedFile->signature = 'res';
        $oDeleteAttachedFile->sTempProcessingFilePath = $sTempFileProcessing;
        $oDeleteAttachedFile->save();

        // assert processing file exists
        $this->assertTrue(file_exists($sTempFileProcessing));

        // run delete processing path from picili id method
        $bDeleteResponse = Helper::bRemoveProcessingfile($oDeleteAttachedFile->id);
        $this->assertTrue($bDeleteResponse);

        // assert file doesn't exist
        $this->assertFalse(file_exists($sTempFileProcessing));
    }

    public function testDeleteFromElastic()
    {
        $iUniqueUser = 865;
        $oPiciliFile = new PiciliFile;
        $oPiciliFile->user_id = $iUniqueUser;
        $oPiciliFile->bHasThumbs = true;
        $oPiciliFile->signature = 'sig';
        $oPiciliFile->save();
        $iTestFileId = $oPiciliFile->id;

        /// put doc in elastic
        ElasticHelper::bSaveFileToElastic($oPiciliFile);

        // assert doc in elastic
        $oDoc = ElasticHelper::mGetDocument($iTestFileId);
        $this->assertEquals($oDoc['user_id'], $iUniqueUser);

        /// use function to remove from elastic
        $bResponse = ElasticHelper::bDeleteFromElastic($iTestFileId);

        $this->assertTrue($bResponse);

        // assert doc not in elastic 
        $this->assertTrue(null === ElasticHelper::mGetDocument($iTestFileId));


        // delete non existent file returns false
        $bResponse = ElasticHelper::bDeleteFromElastic(666);
        $this->assertFalse($bResponse);

        // and for previous - now - delete documetn
        $bResponse = ElasticHelper::bDeleteFromElastic($iTestFileId);
        $this->assertFalse($bResponse);
    }
}
