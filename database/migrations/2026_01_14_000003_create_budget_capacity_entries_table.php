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
        Schema::create('budget_capacity_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budget_plans')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // Last year calculations (read-only, calculated)
            $table->integer('last_year_headcount')->default(0);
            $table->decimal('last_year_available_hours', 10, 2)->default(0);
            $table->decimal('last_year_avg_hourly_price', 10, 2)->default(0);
            $table->decimal('last_year_income', 15, 2)->default(0);
            $table->decimal('last_year_billable_hours', 10, 2)->default(0);
            $table->decimal('last_year_billable_pct', 5, 2)->default(0);

            // Next year user input
            $table->integer('next_year_headcount')->default(0);
            $table->decimal('next_year_avg_hourly_price', 10, 2)->default(0);
            $table->decimal('next_year_billable_pct', 5, 2)->default(0);

            // Calculated budgeted income for capacity method
            $table->decimal('budgeted_income', 15, 2)->nullable();

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
        Schema::dropIfExists('budget_capacity_entries');
    }
};
