<?php

namespace Share;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;


class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'password',
    ];
	protected $connection = 'picili';

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
    
    public function dropboxToken()
    {
        return $this->hasOne('Share\DropboxToken', 'user_id', 'id');
    }
    
    public function dropboxFileSource()
    {
        return $this->hasOne('Share\DropboxFilesource', 'user_id', 'id');
    }
}
