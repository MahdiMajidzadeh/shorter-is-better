<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Every report filters visits by link and by date, and the table only had
 * the single-column foreign key index, so each dashboard count walked a
 * link's whole click history. The composite index turns that into a range.
 *
 * Idempotent, so the index can be created by hand ahead of a release on a
 * big table (see DOCKER.md) and this migration then costs nothing at boot.
 */
return new class extends Migration
{
    private const INDEX = 'short_url_visits_short_url_id_visited_at_index';

    public function up(): void
    {
        if (Schema::hasIndex('short_url_visits', self::INDEX)) {
            return;
        }

        Schema::table('short_url_visits', function (Blueprint $table) {
            $table->index(['short_url_id', 'visited_at'], self::INDEX);
        });
    }

    public function down(): void
    {
        Schema::table('short_url_visits', function (Blueprint $table) {
            $table->dropIndex(self::INDEX);
        });
    }
};
