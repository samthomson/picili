<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DropboxFilesource;

use App\Library\Helper;
use App\Library\PiciliProcessor;


class ProcessAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process-all';

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
        $bMoreTasks = true;
        $cProcessed = 0;
        $bReachedLimit = false;
        $iLimit = 10;

        #echo php_ini_loaded_file();

        while ($bMoreTasks && !$bReachedLimit)
        {
            $bMoreTasks = PiciliProcessor::bProcessQueue();

            if($bMoreTasks) {
                $cProcessed++;
            }

            if ($cProcessed === $iLimit)
            {
                $bReachedLimit = true;
            }
        }

        echo "processed ~",$cProcessed, " tasks";
    }
}
