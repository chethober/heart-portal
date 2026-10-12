<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prescriptions (heart's ep_prescriptions / ep_prescriptionitems): drafted by clinical staff,
     * signed by a doctor, then immutable except for cancellation.
     */
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->restrictOnDelete();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            // Set for the discharge medications of a discharge summary (heart's drugDischarge_ep).
            $table->foreignId('discharge_study_id')->nullable()->constrained()->nullOnDelete();
            // Jalali date, as entered.
            $table->string('prescribed_on', 10)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 16)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signed_at')->nullable();
            // Random token in the printed QR code's verification link; set when signed.
            $table->string('verification_token', 64)->nullable()->unique();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            // Row id in heart's ep_prescriptions table, for imported prescriptions.
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['hospital_id', 'patient_id', 'created_at']);
            $table->index(['hospital_id', 'discharge_study_id']);
            $table->unique(['hospital_id', 'legacy_id']);
        });

        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->restrictOnDelete();
            $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('drug_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('position');
            // Copied from the catalog when saved, so later catalog edits never change a prescription.
            $table->string('drug_name');
            $table->string('form', 32)->nullable();
            $table->string('strength', 64)->nullable();
            $table->string('dose', 32)->nullable();
            $table->string('unit', 16)->nullable();
            $table->string('route', 8)->nullable();
            $table->string('frequency', 8)->nullable();
            $table->string('duration', 64)->nullable();
            // Number of units to dispense (heart's amount_req).
            $table->unsignedSmallInteger('quantity')->nullable();
            // Free-text directions (heart's SIG).
            $table->text('instructions')->nullable();
            $table->timestamps();

            $table->index(['prescription_id', 'position']);
            $table->index(['hospital_id', 'drug_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
    }
};
