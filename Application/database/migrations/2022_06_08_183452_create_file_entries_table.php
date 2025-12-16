<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('file_entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ip');
            $table->string('shared_id', 100)->unique()->nullable();
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->bigInteger('storage_provider_id')->unsigned()->nullable();
            $table->string('name');
            $table->text('filename');
            $table->string('mime', 100)->nullable();
            $table->bigInteger('size')->unsigned()->default(0);
            $table->string('extension', 10)->nullable();
            $table->string('type', 20);
            $table->text('path')->nullable();
            $table->text('link')->nullable();
            $table->boolean('access_status')->default(true)->commnt('0:private 1:public');
            $table->string('password')->nullable();
            $table->bigInteger('downloads')->unsigned()->default(0);
            $table->bigInteger('views')->unsigned()->default(0);
            $table->boolean('admin_has_viewed')->default(false);
            $table->timestamp('expiry_at')->nullable();
            $table->foreign("user_id")->references("id")->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign("storage_provider_id")->references("id")->on('storage_providers')->onUpdate('cascade')->onDelete('cascade');
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
        Schema::dropIfExists('file_entries');
    }
}
