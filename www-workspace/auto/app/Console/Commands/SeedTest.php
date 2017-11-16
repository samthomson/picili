<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DropboxFilesource;

use App\Library\Helper;

class SeedTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed';

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
        $oDropboxSource = Helper::oAddDropboxFolderSource(
            'SS7HiGIa1ZoAAAAAAAD41dowgqujvLpa8l5Qx6Y9XKiQePm5yz1MZIMuYBapMQ2D',
            '/picili-test',
            '1'
        );

        Helper::QueueAnItem('full-dropbox-import', $oDropboxSource->id, $oDropboxSource->user_id);

    }
}
