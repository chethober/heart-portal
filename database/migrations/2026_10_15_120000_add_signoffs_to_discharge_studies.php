<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The discharge summary is signed in three steps: intern (کارورز), resident (دستیار), then the
 * attending physician (پزشک معالج), whose signature finalizes it like any other report
 * (reported_by / finalized_at). The first two steps are kept here, with the signature image
 * each was signed with (DischargeStudy::SIGNOFFS).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discharge_studies', function (Blueprint $table) {
            foreach (['intern', 'resident'] as $step) {
                $table->foreignId("{$step}_signed_by")->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp("{$step}_signed_at")->nullable();
                $table->string("{$step}_signature_path")->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('discharge_studies', function (Blueprint $table) {
            foreach (['intern', 'resident'] as $step) {
                $table->dropConstrainedForeignId("{$step}_signed_by");
                $table->dropColumn(["{$step}_signed_at", "{$step}_signature_path"]);
            }
        });
    }
};
