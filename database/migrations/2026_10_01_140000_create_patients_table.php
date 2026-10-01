<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->restrictOnDelete();
            // Encrypted at rest; looked up through the keyed hash below.
            $table->text('national_id');
            $table->char('national_id_hash', 64);
            $table->string('first_name');
            $table->string('last_name');
            // Jalali date as entered on the form, e.g. 1370/05/21.
            $table->string('dob', 10)->nullable();
            $table->string('gender', 16)->nullable();
            $table->text('phone_number')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['hospital_id', 'national_id_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
