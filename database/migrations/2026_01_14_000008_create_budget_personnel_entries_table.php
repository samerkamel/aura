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
        Schema::create('budget_personnel_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budget_plans')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->decimal('current_salary', 12, 2); // From employee record at budget creation
            $table->decimal('proposed_salary', 12, 2)->nullable(); // User input
            $table->decimal('increase_percentage', 5, 2)->nullable(); // Calculated

            $table->boolean('is_new_hire')->default(false); // From Capacity tab
            $table->integer('hire_month')->nullable(); // Target hire month (1-12) if new hire

            $table->timestamps();

            // Unique constraint: one entry per budget and employee
            $table->unique(['budget_id', 'employee_id']);
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_personnel_entries');
    }
};
