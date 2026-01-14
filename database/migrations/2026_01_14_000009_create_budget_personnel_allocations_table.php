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
        // Check if the new table exists, if not create it
        // If old table exists, we can drop it and create new one
        if (!Schema::hasTable('budget_plan_allocations')) {
            // Drop old table if it exists
            if (Schema::hasTable('budget_personnel_allocations')) {
                Schema::dropIfExists('budget_personnel_allocations');
            }

            Schema::create('budget_plan_allocations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('budget_personnel_entry_id')->constrained('budget_personnel_entries')->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete(); // null = G&A

                $table->decimal('allocation_percentage', 5, 2); // % of salary allocated to this product

                $table->timestamps();

                // Multiple allocations allowed per employee (each must be unique)
                $table->unique(['budget_personnel_entry_id', 'product_id']);
                $table->index('product_id');
                $table->index('budget_personnel_entry_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_plan_allocations');
    }
};
