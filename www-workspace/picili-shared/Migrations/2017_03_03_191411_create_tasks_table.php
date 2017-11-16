<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('processor');
            $table->string('related_file_id', 24);
            $table->integer('iAfter');
            $table->datetime('dDateAfter');
            $table->boolean('bImporting')->default(true);
            $table->unsignedTinyInteger('priority')->default(0);
            $table->integer('iTimesSeenByProccessor')->default(0);
            $table->integer('user_id');

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
        Schema::dropIfExists('tasks');
    }
}
