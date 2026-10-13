<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Gives hospital admins and quality control the new registry.export permission (Role::permissions()).
 */
return new class extends Migration
{
    public function up(): void
    {
        (new RolesAndPermissionsSeeder)->run();
    }

    public function down(): void
    {
        // The seeder only syncs role permissions; nothing to undo.
    }
};
