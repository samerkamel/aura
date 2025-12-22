<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Expense Category Budgets store annual budget allocations for top-level expense categories.
     * The budget_percentage represents the percentage of total monthly revenue that should be
     * allocated to this expense category each month.
     */
    public function up(): void
    {
        Schema::create('expense_category_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->onDelete('cascade');
            $table->year('budget_year');
            $table->decimal('budget_percentage', 5, 2)->default(0); // % of monthly revenue (e.g., 15.50 = 15.50%)
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Unique constraint: one budget per category per year
            $table->unique(['expense_category_id', 'budget_year'], 'category_year_unique');

            // Indexes for common queries
            $table->index('budget_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_category_budgets');
    }
};
