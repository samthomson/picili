<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use \App\Library\FileTaggingHelper;
use \App\Library\Helper;

use Share\Task;

class QueueTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testTaskCreation()
    {
        $sTaskName = 'task_'. uniqid();
        $iNewTaskId = Helper::QueueAnItem($sTaskName, 58, 1);
        
        $this->assertTrue(isset($iNewTaskId));

        $oAssertSet = Task::where('processor', $sTaskName)->first();

        $this->assertTrue(isset($oAssertSet));
	}
	public function testNoDuplicateTasks()
	{
		$sProcessorName = 'test-duplicate-queue';

        Helper::QueueAnItem($sProcessorName, 1, 1);
		Helper::QueueAnItem($sProcessorName, 1, 1);
		
		$cCount = Task::where('processor', $sProcessorName)
		->count();

		$this->assertEquals($cCount, 1);
	}
}
