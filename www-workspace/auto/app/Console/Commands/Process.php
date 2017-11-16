<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DropboxFilesource;

use App\Library\Helper;
use App\Library\PiciliProcessor;


class Process extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process';

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
        $bResult = PiciliProcessor::bProcessQueue();
        echo "processed 1 task? : ".$bResult;
    }
}
