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
        Schema::table('payroll_runs', function (Blueprint $table) {
            // Change status column to accept longer values like 'pending_adjustment'
            $table->string('status', 50)->default('pending_adjustment')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            // Revert to original column size
            $table->string('status', 20)->default('pending')->change();
        });
    }
};
