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
        Schema::create('billable_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->date('payroll_period_start_date');
            $table->decimal('hours', 5, 2)->default(0);
            $table->timestamps();

            // Ensure unique constraint for employee per payroll period
            $table->unique(['employee_id', 'payroll_period_start_date']);

            // Index for efficient querying
            $table->index('payroll_period_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billable_hours');
    }
};
