<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Library\FileTaggingHelper;

class FileTagger extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file-tagger';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'file-tagger';

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

        $this->info("File tagger");

        $iFilesProcessed = FileTaggingHelper::iProcessFiles();


        //
        // end
        //


        $diff = microtime(true) - $timeStart;
        $oLog = new \App\Models\Event;
        $oLog->event = "tag files";
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
