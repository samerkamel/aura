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
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->decimal('base_salary', 10, 2);
            $table->decimal('final_salary', 10, 2);
            $table->decimal('performance_percentage', 5, 2);
            $table->json('calculation_snapshot'); // JSON snapshot of contributing factors
            $table->enum('status', ['draft', 'finalized'])->default('draft');
            $table->timestamps();

            // Composite index for efficient queries
            $table->index(['employee_id', 'period_start_date', 'period_end_date']);

            // Unique constraint to prevent duplicate payroll runs for same employee/period
            $table->unique(['employee_id', 'period_start_date', 'period_end_date'], 'unique_employee_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
