<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVersionGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('version_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->text('eggs');
			$table->integer('type');
            $table->string('folder')->nullable();
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
        Schema::dropIfExists('version_groups');
    }
}
