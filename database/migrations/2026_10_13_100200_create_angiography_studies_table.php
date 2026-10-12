<?php

use App\Support\Procedures;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('angiography_studies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->restrictOnDelete();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 16)->default('draft');
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('amended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('amended_at')->nullable();
            $table->text('amendment_reason')->nullable();
            // Row id in heart's angiography table, for imported studies.
            $table->unsignedBigInteger('legacy_id')->nullable();

            Procedures::addColumns($table, 'angiography');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['hospital_id', 'patient_id', 'created_at']);
            $table->index(['hospital_id', 'status']);
            $table->unique(['hospital_id', 'legacy_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('angiography_studies');
    }
};
