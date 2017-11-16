<?php

namespace Share;

use Illuminate\Database\Eloquent\Model;

class DropboxFilesource extends Model
{
	protected $connection = 'picili';
	protected $table = 'dropbox_filesources';

	
	protected $encrypt = ['access_token'];

	public function dropboxFiles()
	{
		return $this->hasMany('\App\Models\DropboxFiles', 'dropbox_folder_id', 'id');
	}


	public function getAttribute($key)
    {
        if (in_array($key, $this->encrypt))
        {
            return \Crypt::decryptString($this->attributes[$key]);
        }

        return parent::getAttribute($key);
    }

    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();

        foreach ($attributes as $key => $value)
        {
            if (in_array($key, $this->encrypt))
            {
                $attributes[$key] = \Crypt::decryptString($value);
            }
        }

        return $attributes;
    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->encrypt))
        {
            $value = \Crypt::encryptString($value);
        }

        return parent::setAttribute($key, $value);
    }
}
