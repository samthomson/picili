<?php

namespace Share;

use Illuminate\Database\Eloquent\Model;

class DropboxFilesource extends Model
{
	protected $connection = 'picili';
	protected $table = 'dropbox_filesources';

	public function dropboxFiles()
	{
        return $this->hasMany('Share\DropboxFiles', 'dropbox_folder_id', 'id');
    }
    public function getDropboxFilesFromOwnerOfThisFileSource()
    {
        /*
        quite hacky.
        returns all dropbox files of the user (that owns this file source),
         and not just ones belonging to that file-source/folder. Changed 
         as updating the file source stopped the change detect algorithm
          from comparing differences across all files, just those within
           a folder. This way if a user updates their folder source - 
           after a dropbox disconnect and reconnect for example - then 
           it will be stored as a new file source and any files belonging 
           to it would not be included in files changed/import comparison.
        */

        return $this->hasMany('Share\DropboxFiles', 'user_id', 'user_id');
    }
    
    public function user()
    {
        return $this->belongsTo('Share\User', 'user_id', 'id');
    }

	public function getAttribute($key)
    {
        return parent::getAttribute($key);
    }

    public function attributesToArray()
    {
        // obsolete now model has no encrypted attributes
        // $attributes = parent::attributesToArray();

        // foreach ($attributes as $key => $value)
        // {
        //     if (in_array($key, $this->encrypt))
        //     {
        //         $attributes[$key] = \Crypt::decryptString($value);
        //     }
        // }

        // return $attributes;

        return parent::attributesToArray();
    }

    public function setAttribute($key, $value)
    {
        return parent::setAttribute($key, $value);
    }
}
