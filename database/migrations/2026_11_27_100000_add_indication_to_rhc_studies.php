<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = ['indication', 'indication_other'];

    public function up(): void
    {
        foreach (self::COLUMNS as $column) {
            // Fresh databases already include these fields through Procedures::createTable().
            if (! Schema::hasColumn('rhc_studies', $column)) {
                Schema::table('rhc_studies', function (Blueprint $table) use ($column) {
                    $table->text($column)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('rhc_studies', function (Blueprint $table) {
            $table->dropColumn(self::COLUMNS);
        });
    }
};
