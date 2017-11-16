<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DropboxFiles extends Model
{
	public function dropboxFolder()
	{
    	return $this->belongsTo('Share\DropboxFilesource', 'dropbox_folder_id', 'id');
	}
}
