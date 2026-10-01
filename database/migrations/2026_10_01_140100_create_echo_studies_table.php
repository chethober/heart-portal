<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('echo_studies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->restrictOnDelete();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 16)->default('draft');
            // Jalali date of the exam as entered on the form.
            $table->string('echo_date', 10)->nullable();
            $table->string('doctor')->nullable();
            // Row id in the legacy SQLite echo_reports table, for imported studies.
            $table->unsignedBigInteger('legacy_id')->nullable();

            foreach (config('echo.fields') as $column => $type) {
                match ($type) {
                    'number' => $table->double($column)->nullable(),
                    'boolean' => $table->boolean($column)->nullable(),
                    default => $table->text($column)->nullable(),
                };
            }

            $table->timestamps();
            $table->softDeletes();

            $table->index(['hospital_id', 'patient_id', 'created_at']);
            $table->unique(['hospital_id', 'legacy_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('echo_studies');
    }
};
