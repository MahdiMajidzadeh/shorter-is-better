<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Init extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('username');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('links', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('link');
            $table->string('slug');
            $table->integer('view')->unsigned();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('views', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('link_id')->unsigned();
            $table->string('browser')->nullabe();
            $table->string('platform')->nullabe();
            $table->string('device')->nullabe();
            $table->string('system')->nullabe();
            $table->string('country')->nullabe();
            $table->string('city')->nullabe();
            $table->string('ip')->nullabe();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('links');
        Schema::dropIfExists('views');
    }
}
