<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * laravel/telescope shipped its own migrations from inside the package, so
     * uninstalling it leaves these tables behind with no migration left to drop
     * them. They are the largest tables in the database on a busy instance.
     *
     * Order matters: telescope_entries_tags has a foreign key onto
     * telescope_entries.
     */
    public function up()
    {
        Schema::dropIfExists('telescope_entries_tags');
        Schema::dropIfExists('telescope_entries');
        Schema::dropIfExists('telescope_monitoring');
    }

    /**
     * Not reversible. The schema belonged to the package; reinstating it means
     * requiring laravel/telescope again and letting its own migrations run.
     */
    public function down()
    {
        //
    }
};
