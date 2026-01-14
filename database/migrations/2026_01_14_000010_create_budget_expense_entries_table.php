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
        Schema::create('budget_expense_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budget_plans')->cascadeOnDelete();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->cascadeOnDelete();

            // Type of expense (OpEx, Tax, or CapEx)
            $table->enum('type', ['opex', 'tax', 'capex']);

            // Last year data (calculated from expense history)
            $table->decimal('last_year_total', 15, 2)->default(0);
            $table->decimal('last_year_avg_monthly', 15, 2)->default(0);

            // User input - override mechanism
            $table->decimal('increase_percentage', 5, 2)->nullable(); // User override
            $table->decimal('proposed_amount', 15, 2)->nullable(); // Alternative: exact amount override

            // Calculated or overridden proposed total
            $table->decimal('proposed_total', 15, 2)->nullable();

            // Flag to indicate manual override was used
            $table->boolean('is_override')->default(false);

            $table->timestamps();

            // Unique constraint: one entry per budget and category
            $table->unique(['budget_id', 'expense_category_id']);
            $table->index(['budget_id', 'type']);
            $table->index('expense_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_expense_entries');
    }
};
