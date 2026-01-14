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
        Schema::create('budget_growth_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budget_plans')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // Historical data (calculated from invoices + payments)
            $table->decimal('year_minus_3', 15, 2)->nullable();
            $table->decimal('year_minus_2', 15, 2)->nullable();
            $table->decimal('year_minus_1', 15, 2)->nullable();

            // Trendline configuration
            $table->enum('trendline_type', ['linear', 'logarithmic', 'polynomial'])->default('linear');
            $table->integer('polynomial_order')->nullable(); // For polynomial trendlines

            // User input
            $table->decimal('budgeted_value', 15, 2)->nullable();

            $table->timestamps();

            // Unique constraint: one entry per budget and product
            $table->unique(['budget_id', 'product_id']);
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_growth_entries');
    }
};
