<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The drug catalog (heart's ep_drugs). Rows without a hospital are the shared catalog every
     * hospital sees; a hospital may add its own rows, which only it sees.
     */
    public function up(): void
    {
        Schema::create('drugs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->nullable()->constrained()->restrictOnDelete();
            // Stable key for seeded shared drugs, so the seeder can be re-run.
            $table->string('code', 64)->nullable();
            $table->string('generic_name');
            // Name in the local language (heart's FarsiName).
            $table->string('local_name')->nullable();
            $table->string('form', 32);
            $table->string('strength', 64)->nullable();
            // Defaults offered when the drug is picked (heart's defaultAmount_csv / defaultUsage_csv).
            $table->string('default_dose', 32)->nullable();
            $table->string('default_unit', 16)->nullable();
            $table->string('default_route', 8)->nullable();
            $table->string('default_frequency', 8)->nullable();
            $table->boolean('is_active')->default(true);
            // Lower-cased, normalized names for searching (Arabic/Persian letter variants folded).
            $table->text('search_text');
            // Row id in heart's ep_drugs table, for imported drugs.
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->timestamps();

            $table->unique(['hospital_id', 'code'])->nullsNotDistinct();
            $table->index(['hospital_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drugs');
    }
};
