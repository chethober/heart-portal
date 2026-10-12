<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->restrictOnDelete();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->string('ward')->nullable();
            // Jalali dates, as entered.
            $table->string('admitted_on', 10)->nullable();
            $table->string('discharged_on', 10)->nullable();
            $table->string('physician')->nullable();
            // Admission number in the hospital information system.
            $table->string('his_id')->nullable();
            // Row id in heart's admit table, for imported admissions.
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['hospital_id', 'patient_id']);
            $table->unique(['hospital_id', 'his_id']);
            $table->unique(['hospital_id', 'legacy_id']);
        });

        Schema::table('echo_studies', function (Blueprint $table) {
            $table->foreignId('admission_id')->nullable()->after('patient_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('echo_studies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admission_id');
        });

        Schema::dropIfExists('admissions');
    }
};
