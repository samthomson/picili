<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Library\DropboxHelper;

use \Carbon\Carbon;

class PullDropbox extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pull-dropbox';

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


        $this->info("sync files from Dropbox");
        $sPath = '/test pics';
        // $sPath = '/pictures/PICTURES - SORTING FOR MEDIADUMP';
        // $sPath = '/pictures/PICTURES - SORTING FOR MEDIADUMP/travel/bike-tours/Safari';

        $oDropboxFolder = \App\Models\DropboxFilesource::all()->first();

        // DropboxHelper::mPullAllDropbox(
        //     $sPath,
        //     $oDropboxToken->access_token
        // );

        DropboxHelper::checkFileSource($oDropboxFolder);

        //
        // end
        //

        $diff = microtime(true) - $timeStart;
        $oLog = new \Share\Event;
        $oLog->event = "pulled dropbox file list";
        $oLog->timetaken = $diff;
        $oLog->when = \Carbon\Carbon::now();
        $oLog->save();
    }
}
