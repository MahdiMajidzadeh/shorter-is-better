<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * A link made for a channel post remembers the channel, so reports can
 * say which channel's posts get the clicks. Telegram sends no referer, so
 * this is the only way to attribute them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('short_urls', function (Blueprint $table) {
            $table->unsignedBigInteger('channel_id')->nullable()->index()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('short_urls', function (Blueprint $table) {
            $table->dropColumn('channel_id');
        });
    }
};
