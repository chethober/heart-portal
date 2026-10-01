<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Null only for platform admins, who don't belong to a hospital.
            $table->foreignId('hospital_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->string('medical_license_no', 64)->nullable()->after('email');
            $table->string('signature_path')->nullable()->after('medical_license_no');
            $table->boolean('is_active')->default(true)->after('signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hospital_id');
            $table->dropColumn(['medical_license_no', 'signature_path', 'is_active']);
        });
    }
};
