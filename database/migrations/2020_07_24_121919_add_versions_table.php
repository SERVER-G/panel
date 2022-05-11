<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('versions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('group_id');
            $table->string('name');
            $table->string('download_url');
			$table->string('filename');
            $table->string('action');
            $table->integer('sort');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('versions');
    }
}
