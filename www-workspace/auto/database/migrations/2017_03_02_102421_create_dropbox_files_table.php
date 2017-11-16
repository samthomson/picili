<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDropboxFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dropbox_files', function (Blueprint $table) {
            $table->increments('id');
            $table->string('dropbox_id');
            $table->string('dropbox_path');
            $table->string('dropbox_name');
            $table->string('server_modified', 32);
            $table->integer('size')->unsigned();
            $table->integer('dropbox_folder_id')->unsigned();
            $table->integer('user_id')->unsigned();

            $table->string('sTempFileName');
            $table->string('sha1')->default('');
            $table->string('combinedSignature')->default('');

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dropbox_files');
    }
}
