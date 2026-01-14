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
        Schema::create('budget_result_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budget_plans')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // Values from the three budget methods
            $table->decimal('growth_value', 15, 2)->nullable(); // From Growth tab
            $table->decimal('capacity_value', 15, 2)->nullable(); // From Capacity tab
            $table->decimal('collection_value', 15, 2)->nullable(); // From Collection tab

            // Calculated average
            $table->decimal('average_value', 15, 2)->nullable();

            // User's final selection (can be any of the above or custom)
            $table->decimal('final_value', 15, 2)->nullable();

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
        Schema::dropIfExists('budget_result_entries');
    }
};
