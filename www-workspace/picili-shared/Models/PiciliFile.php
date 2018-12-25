<?php

namespace Share;

use Illuminate\Database\Eloquent\Model;

class PiciliFile extends Model
{
	protected $table = 'picili_files';
	protected $dates = ['updated_at', 'created_at', 'updated_at'];
	public $timestamps = false;
    protected $connection = 'picili';
    
    dropboxFile() {
        return $this->hasOne('Share\DropboxFiles', 'id', 'dropbox_filesource_id');
    }

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

    public function save(array $options = array())
    {
        try{
            return parent::save($options);
        }catch(\Illuminate\Database\QueryException $er){
            logger('[caught exception saving file - likely duplicate key constraint (expected)]');
            return true;
        }catch(\Exception $e)
        {
            logger(['\nunexpected exception saving file\n\n'.$e, get_class($e)]);
            return false;
        }
    }
}
