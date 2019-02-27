<?php

namespace Share;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
	protected $fillable = ['user_id', 'type', 'subtype', 'value', 'confidence'];

	protected function __distruct() {
		$this->childObject = null;
	}
    
	public function piciliFile()
	{
    	return $this->belongsTo('PiciliFile', 'file_id', 'id');
	}
}
