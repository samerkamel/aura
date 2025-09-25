<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('income_schedules');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse migration - we're permanently removing this table
        throw new Exception('Cannot reverse the removal of income_schedules table');
    }
};