<?php

use App\Support\LegacyImport;
use Illuminate\Database\Migrations\Migration;

/**
 * One-off: on an existing install, create project 1 from APP_URL, attach the
 * channel configured in storage/settings.json, and backfill every link.
 * A fresh database (no users, no links) is left alone; the admin seeder
 * creates the first project there.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new LegacyImport)->run();
    }

    public function down(): void
    {
        //
    }
};
