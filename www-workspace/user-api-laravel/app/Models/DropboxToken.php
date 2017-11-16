<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DropboxToken extends Model
{
	protected $table = 'dropbox_tokens';
	protected $connection = 'picili';

    protected $encrypt = ['access_token'];

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
