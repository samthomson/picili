<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePiciliFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('picili_files', function (Blueprint $table) {
            $table->increments('id');
            $table->string('sTempProcessingFilePath')->default('');
            $table->string('signature', 64)->unique();
            $table->integer('user_id');
            $table->integer('dropbox_filesource_id')->nullable()->default(null);
            $table->integer('instagram_filesource_id')->nullable()->default(null);
            $table->boolean('bInFolder')->default(false);
            $table->boolean('bDeleted')->default(false);
            $table->boolean('bHasGPS')->default(false);
            $table->boolean('bHasAltitude')->default(false);
            $table->boolean('bHasThumbs')->default(false);
            $table->boolean('bCorrupt')->default(false);
            $table->decimal('latitude', 8, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->string('address', 255)->nullable();
            $table->decimal('altitude', 8, 4)->nullable();
            $table->string('sParentPath')->default('');
            $table->string('baseName')->default('');
            $table->string('extension', 16)->default('');
            $table->datetime('datetime')->nullable();
            $table->string('phash', 16)->nullable();

            $table->unsignedSmallInteger('medium_height')->nullable();
            $table->unsignedSmallInteger('medium_width')->nullable();

            // skip 'folders' array

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
        Schema::dropIfExists('picili_files');
    }
}
