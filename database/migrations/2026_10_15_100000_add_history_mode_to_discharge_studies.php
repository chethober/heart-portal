<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The discharge summary's history is written by hand ("manual") or built from the complaints
 * ("auto"): config/procedures/discharge.php "history_mode". Heart had no such column.
 */
return new class extends Migration
{
    public function up(): void
    {
        // A fresh database already has it: the discharge table's migration adds every config field.
        if (! Schema::hasColumn('discharge_studies', 'history_mode')) {
            Schema::table('discharge_studies', function (Blueprint $table) {
                $table->text('history_mode')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('discharge_studies', function (Blueprint $table) {
            $table->dropColumn('history_mode');
        });
    }
};
