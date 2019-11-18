<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserAgent extends Migration
{
    public function up()
    {
        Schema::table('views', function (Blueprint $table) {
            $table->text('user_agent')->nullable();
        });
    }

    public function down()
    {
        Schema::table('views', function (Blueprint $table) {
            $table->dropColumn('user_agent');
        });
    }
}
