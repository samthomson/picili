<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \Exception;

use App\Library\Helper;
use App\Library\PiciliProcessor;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

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

    /* old image test */
    /* 
        echo "hello";

        echo "\n mem: ".ini_get("memory_limit")."\n";

        print_r(gd_info());

        // try{
        $im = @imagecreatefromjpeg(resource_path('test-files/jpegs/31468070-275d1a5e-aed4-11e7-9636-458e44838477.jpg'));

        if(!$im){
            echo "corrupt image..";
        }else{
            echo "image is ok";
        }
        echo "goodbye";
    */


    public function handle()
    {
        // test calling plant net tagger
        // echo "test plant net data\n\n";
        // $plantNetTagged = Helper::plantNet(65);
        // print_r($plantNetTagged);

        // test plant net tagging some images
        PiciliProcessor::plantDetect(61);
    }
}
