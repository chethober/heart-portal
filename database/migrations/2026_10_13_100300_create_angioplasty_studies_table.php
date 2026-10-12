<?php

use App\Support\Procedures;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Procedures::createTable('angioplasty');
    }

    public function down(): void
    {
        Schema::dropIfExists('angioplasty_studies');
    }
};
