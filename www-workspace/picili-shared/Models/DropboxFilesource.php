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
