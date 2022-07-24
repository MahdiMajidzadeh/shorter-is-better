<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('telegraph_chats', function (Blueprint $table) {
            $table->unsignedBigInteger('token_id');
        });
    }

    public function down()
    {
        Schema::table('telegraph_chats', function (Blueprint $table) {
            $table->dropColumn('token_id');
        });
    }
};
