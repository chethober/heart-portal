<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Final diagnoses chosen from heart's ICD guide (config/diagnoses.php): on the discharge summary
 * (config/procedures/discharge.php "final_diagnosis_codes") and on admissions, like heart's
 * admit.ICDCodes. Both hold a list of diagnosis keys.
 */
return new class extends Migration
{
    public function up(): void
    {
        // A fresh database already has it: the discharge table's migration adds every config field.
        if (! Schema::hasColumn('discharge_studies', 'final_diagnosis_codes')) {
            Schema::table('discharge_studies', function (Blueprint $table) {
                $table->jsonb('final_diagnosis_codes')->nullable();
            });
        }
        Schema::table('admissions', function (Blueprint $table) {
            $table->jsonb('icd_codes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('discharge_studies', function (Blueprint $table) {
            $table->dropColumn('final_diagnosis_codes');
        });
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropColumn('icd_codes');
        });
    }
};
