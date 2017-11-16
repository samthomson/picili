<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use SharedLibrary\ElasticHelper;


class ElasticDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elastic-delete';

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
        $bStatus = ElasticHelper::bDeleteIndex();
        echo "index deleted: ".$bStatus;
    }
}
