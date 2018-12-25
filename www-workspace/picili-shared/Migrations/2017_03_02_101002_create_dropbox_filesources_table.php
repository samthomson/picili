<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDropboxFilesourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dropbox_filesources', function (Blueprint $table) {
            $table->increments('id');
            $table->string('folder', 512)->default('');
            $table->integer('user_id')->unique();

            $table->timestamps();

            // not necessary, only one folder per user
            //// $table->unique(array('user_id'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dropbox_filesources');
    }
}
