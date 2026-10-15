<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Links now belong to a project and remember who made them and from where.
 * Every column is nullable or defaulted so existing rows are untouched;
 * the next migration backfills them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('short_urls', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->index()->after('id');
            $table->unsignedBigInteger('user_id')->nullable()->index()->after('project_id');
            $table->string('source', 16)->default('panel')->after('user_id');
            $table->text('title')->nullable()->after('destination_url');
        });
    }

    public function down(): void
    {
        Schema::table('short_urls', function (Blueprint $table) {
            $table->dropColumn(['project_id', 'user_id', 'source', 'title']);
        });
    }
};
