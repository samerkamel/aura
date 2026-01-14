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
        Schema::create('budget_collection_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budget_plans')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // Last year analysis (calculated)
            $table->decimal('beginning_balance', 15, 2)->default(0); // Outstanding balance at start of year
            $table->decimal('end_balance', 15, 2)->default(0); // Outstanding balance at end of year
            $table->decimal('avg_balance', 15, 2)->default(0); // Average balance
            $table->decimal('avg_contract_per_month', 15, 2)->default(0); // From Income sheet
            $table->decimal('avg_payment_per_month', 15, 2)->default(0); // From Income sheet
            $table->decimal('last_year_collection_months', 8, 2)->default(0); // Calculated

            // Budgeted year calculated from patterns
            $table->decimal('budgeted_collection_months', 8, 2)->default(0);

            // Average of last year and budgeted
            $table->decimal('projected_collection_months', 8, 2)->default(0);

            // Calculated budgeted income for collection method
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
        Schema::dropIfExists('budget_collection_entries');
    }
};
