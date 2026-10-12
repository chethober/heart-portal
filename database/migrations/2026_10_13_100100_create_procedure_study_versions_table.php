<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procedure_study_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->restrictOnDelete();
            // Procedure key (see App\Support\Procedures) and the study's id in its table.
            $table->morphs('study');
            $table->unsignedInteger('version');
            // Status the study had when this signed version was recorded (final or amended).
            $table->string('status', 16);
            $table->jsonb('data');
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            // Signature image as it was when this version was signed.
            $table->string('signature_path')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at');

            $table->unique(['study_type', 'study_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedure_study_versions');
    }
};
