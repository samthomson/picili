<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DropboxFilesource;

use SharedLibrary\ElasticHelper;
use App\Library\PiciliProcessor;

use Share\PiciliFile;

use Elasticsearch\ClientBuilder;

class IndexAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'index-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $aoFiles = PiciliFile::all();

        foreach ($aoFiles as $oFile) {

            ElasticHelper::bSaveFileToElastic($oFile);
        }
    }
}
