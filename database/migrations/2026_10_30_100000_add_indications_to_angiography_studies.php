<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = ['indication_stable_angina', 'indication_st_elevated_mi', 'indication_non_st_elevated_mi', 'indication_unstable_angina', 'indication_cardiomyopathy', 'indication_heart_failure', 'indication_valvular_disease', 'indication_arrhythmia', 'indication_positive_stress_test', 'indication_cardiogenic_shock', 'indication_cardiac_arrest'];

    public function up(): void
    {
        foreach (self::COLUMNS as $column) {
            // Fresh databases already include these fields through Procedures::createTable().
            if (! Schema::hasColumn('angiography_studies', $column)) {
                Schema::table('angiography_studies', function (Blueprint $table) use ($column) {
                    $table->boolean($column)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('angiography_studies', function (Blueprint $table) {
            $table->dropColumn(self::COLUMNS);
        });
    }
};
