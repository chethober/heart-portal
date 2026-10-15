<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * بومی / غیر بومی is now a two-way toggle; heart's "Unknown" code (9) is a blank,
 * stored as null like the other fields' placeholder codes. The report never printed it.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('discharge_studies')->where('bomi', '9')->update(['bomi' => null]);
    }

    public function down(): void
    {
        //
    }
};
