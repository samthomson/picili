<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProcessorLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('processor_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('processor');
            $table->boolean('result');
            $table->integer('iRunTime')->unsigned();
            $table->integer('processTask')->unsigned();   
            $table->integer('sRelatedPiciliFileId')->unsigned(); 
            $table->string('mExtra')->nullable();
            
            $table->datetime('dTimeOccured');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('processor_logs');
    }
}
