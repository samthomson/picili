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
		
		$cFilesToIndex = count($aoFiles);

		// echo "\n\n$cFilesToIndex files to index";

		if (true) {
			// echo "\n\none at a time\n\n";
			gc_enable();
			foreach ($aoFiles as $i => $oFile) {
				// if ($i > 0 && $i % 1 === 0) {
					gc_collect_cycles();
				// }

				// if ($i > 0 && $i % 1000 === 0) {
				// 	echo "\nmem at $i: ", $this->reduceMem(memory_get_usage(true));
				// }
				ElasticHelper::bSaveFileToElastic($oFile);
				$oFile = null;
				unset($oFile);
			}
		} else {
			// echo "\n\n1000 at a time\n\n";
			ElasticHelper::bBatchSaveToElastic($aoFiles);			
		}
	}
	
	// public function reduceMem($iMem) {
	// 	if ($iMem === 0) {
	// 		return $iMem;
	// 	}
	// 	return ($iMem / 1024 / 1024).' mb';
	// }
}
