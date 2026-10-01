<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('echo_studies', function (Blueprint $table) {
            $table->foreignId('reported_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable()->after('status');
            $table->foreignId('amended_by')->nullable()->after('finalized_at')->constrained('users')->nullOnDelete();
            $table->timestamp('amended_at')->nullable()->after('amended_by');
            $table->text('amendment_reason')->nullable()->after('amended_at');
            $table->index(['hospital_id', 'status']);
        });

        Schema::create('echo_study_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->restrictOnDelete();
            $table->foreignId('echo_study_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            // Status the study had when this signed version was recorded (final or amended).
            $table->string('status', 16);
            $table->jsonb('data');
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            // Signature image as it was when this version was signed.
            $table->string('signature_path')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at');

            $table->unique(['echo_study_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('echo_study_versions');

        Schema::table('echo_studies', function (Blueprint $table) {
            $table->dropIndex(['hospital_id', 'status']);
            $table->dropConstrainedForeignId('reported_by');
            $table->dropConstrainedForeignId('amended_by');
            $table->dropColumn(['finalized_at', 'amended_at', 'amendment_reason']);
        });
    }
};
