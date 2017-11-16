<?php

use Illuminate\Database\Seeder;

class FolderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // make folders
        // public/processing
        $saFolders = [
            public_path().DIRECTORY_SEPARATOR.'processing',
            public_path().DIRECTORY_SEPARATOR.'thumbs',
            resource_path('test-temp')
        ];
        foreach ($saFolders as $sMakeFolder)
        {
            if(!File::isDirectory($sMakeFolder))
            {
                // make it
                File::makeDirectory($sMakeFolder,  $mode = 0755, $recursive = false);
            }
        }
    }
}
