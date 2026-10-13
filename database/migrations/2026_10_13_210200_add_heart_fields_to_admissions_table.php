<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admission fields from heart's admit form. The ward becomes a link to the hospital's
 * wards; the old `ward` text stays as the name the admission was recorded with
 * (imported rows may only have the text).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->foreignId('ward_id')->nullable()->after('patient_id')->constrained()->nullOnDelete();
            $table->string('admitted_at', 5)->nullable()->after('admitted_on');
            // Heart's codes: admittype 0 independent, 1 outpatient, 2 inpatient;
            // admittype_direct 1 EMS 115, 2 office referral, 3 other hospital, 4 direct, 5 ward transfer, 9 unknown.
            $table->unsignedSmallInteger('admission_type')->nullable()->after('admitted_at');
            $table->unsignedSmallInteger('arrival_mode')->nullable()->after('admission_type');
            $table->string('referred_from')->nullable()->after('arrival_mode');
            $table->string('discharged_at', 5)->nullable()->after('discharged_on');
            // Other hospital-information-system ids (heart's IDCode, PCode, AdmCode, SEPAS ids).
            $table->jsonb('his_codes')->nullable()->after('his_id');

            $table->index(['hospital_id', 'ward_id', 'discharged_on']);
        });

        // Give every ward name already recorded a ward of its hospital, and link it.
        $names = DB::table('admissions')->whereNotNull('ward')->where('ward', '<>', '')
            ->select('hospital_id', 'ward')->distinct()->get();

        foreach ($names as $row) {
            $name = mb_substr(trim($row->ward), 0, 100);
            $wardId = DB::table('wards')->where('hospital_id', $row->hospital_id)->where('name', $name)->value('id')
                ?? DB::table('wards')->insertGetId(['hospital_id' => $row->hospital_id, 'name' => $name, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

            DB::table('admissions')->where('hospital_id', $row->hospital_id)->where('ward', $row->ward)->update(['ward_id' => $wardId]);
        }
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropIndex(['hospital_id', 'ward_id', 'discharged_on']);
            $table->dropConstrainedForeignId('ward_id');
            $table->dropColumn(['admitted_at', 'admission_type', 'arrival_mode', 'referred_from', 'discharged_at', 'his_codes']);
        });
    }
};
