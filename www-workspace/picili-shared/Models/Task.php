<?php

namespace Share;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
	protected $table = 'tasks';
	public $timestamps = false;
	protected $connection = 'picili';
}
