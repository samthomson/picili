<?php

namespace App\Library;

use Share\Task;

class Helper {

    private static function cMitigatingTasksForUser($iUserId) {

		/*
		return count of tasks that would get in the way of changing
		 the current dropbox folder.
		this allows not changing the folder while tasks are processing.
		*/
		return Task::where('user_id', $iUserId)
			->where('processor', '<>', 'full-dropbox-import')
			->count();
    }
}
