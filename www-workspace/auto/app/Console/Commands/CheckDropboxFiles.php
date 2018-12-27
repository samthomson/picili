<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Library\DropboxHelper;

class CheckDropboxFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process-dropbox-files';

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
        $timeStart = microtime(true);

        $this->info("check all new files from Dropbox");

        $oDropboxFolder = \App\Models\DropboxFilesource::all()->first();

        $iFilesProcessed = DropboxHelper::checkNewDropboxFiles($oDropboxFolder->access_token);

        //
        // end
        //

        $diff = microtime(true) - $timeStart;
        $oLog = new \Share\Event;
        $oLog->event = "check files from dropbox";
        $oLog->timetaken = $diff;
        $oLog->iFilesProcessed = $iFilesProcessed;
        if($iFilesProcessed > 0)
        {
            $oLog->iTimePerFile = ($diff / $iFilesProcessed);
        }
        $oLog->when = \Carbon\Carbon::now();
        $oLog->save();
    }
}
