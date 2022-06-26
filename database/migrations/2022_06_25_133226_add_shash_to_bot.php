<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up()
    {
        Schema::table('telegraph_chats', function(Blueprint $table) {
            $table->dropColumn('token_id');
            $table->string('hash', 50)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
        });
    }

    public function down()
    {
        Schema::table('telegraph_chats', function(Blueprint $table) {
            $table->unsignedBigInteger('token_id');
            $table->dropColumn('hash');
            $table->dropColumn('user_id');
        });
    }
};
