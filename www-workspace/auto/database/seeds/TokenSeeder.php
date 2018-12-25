<?php

use Illuminate\Database\Seeder;
use Share\DropboxFilesource;

class TokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // create dropbox file source
        $odfsDropboxFileSource = new DropboxFilesource;
        $odfsDropboxFileSource->user_id = 6;
        $odfsDropboxFileSource->folder = 'test pics';
        $odfsDropboxFileSource->save();
    }
}
