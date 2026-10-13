<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demographics and contact details from heart's patient and admit forms.
 * Contact details are encrypted at rest (text columns), like phone_number.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('father_name')->nullable();
            // Heart's codes: ir, af, iq, or 99999 (other, named in nationality_other).
            $table->string('nationality', 8)->nullable();
            $table->string('nationality_other')->nullable();
            // Heart's codes: 1 single, 2 married, 3 widowed/divorced, 4 without guardian, 9 unknown.
            $table->unsignedSmallInteger('marital_status')->nullable();
            $table->string('occupation')->nullable();
            $table->text('home_phone')->nullable();
            $table->text('emergency_phone')->nullable();
            $table->text('email')->nullable();
            $table->text('address')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['father_name', 'nationality', 'nationality_other', 'marital_status', 'occupation', 'home_phone', 'emergency_phone', 'email', 'address']);
        });
    }
};
