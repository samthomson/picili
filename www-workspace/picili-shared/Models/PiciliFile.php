<?php

namespace Share;

use Illuminate\Database\Eloquent\Model;

class PiciliFile extends Model
{
	protected $table = 'picili_files';
	protected $dates = ['updated_at', 'created_at', 'updated_at'];
	public $timestamps = false;
	protected $connection = 'picili';

	public function tags()
    {
        return $this->hasMany('Share\Tag', 'file_id', 'id');
    }
    

	public function aSources()
	{
		// possible sources: dropbox, instagram

		$saSources = [];

        if($this->dropbox_filesource_id !== null) {
            array_push($saSources, $this->dropbox_filesource_id);
        }

        if($this->instagram_filesource_id !== null) {
            array_push($saSources, $this->instagram_filesource_id);
        }
        
        return $saSources;
	}

    public function addTags($aaTags)
    {
        // take array of tag arrays and set them as related tags
        $this->tags()->saveMany($aaTags);
    }
}
