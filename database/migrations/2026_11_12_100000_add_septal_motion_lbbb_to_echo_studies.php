<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fresh databases already have it: the echo_studies migration adds every config('echo.fields') column.
        if (! Schema::hasColumn('echo_studies', 'lv_septal_motion_lbbb')) {
            Schema::table('echo_studies', function (Blueprint $table) {
                $table->boolean('lv_septal_motion_lbbb')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('echo_studies', function (Blueprint $table) {
            $table->dropColumn('lv_septal_motion_lbbb');
        });
    }
};
