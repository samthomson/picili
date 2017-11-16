<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PiciliUser extends Model
{
	protected $connection = 'picili';
	protected $table = 'users';
}
