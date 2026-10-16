<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Every API key is bound to one project.
 *
 * On an old install the Sanctum table may be recorded as migrated yet have
 * been dropped by hand later (the pre-project app never used it). Laravel
 * then skips the 2019 migration and the ALTER below would fail, so recreate
 * the table here with the same columns, plus project_id, in that case.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
                $table->text('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();
            });

            return;
        }

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('tokenable_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
        });
    }
};
