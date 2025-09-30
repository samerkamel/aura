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
        Schema::table('budgets', function (Blueprint $table) {
            // Drop the foreign key constraint to products
            $table->dropForeign(['product_id']);
            $table->dropIndex(['product_id', 'budget_year']);
            $table->dropUnique('unique_budget_per_bu_product_year');

            // Rename the column back to department_id
            $table->renameColumn('product_id', 'department_id');

            // Add the foreign key constraint to departments
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');

            // Add the indexes back
            $table->index(['department_id', 'budget_year']);
            $table->unique(['business_unit_id', 'department_id', 'budget_year'], 'unique_budget_per_bu_dept_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            // Drop the foreign key constraint to departments
            $table->dropForeign(['department_id']);
            $table->dropIndex(['department_id', 'budget_year']);
            $table->dropUnique('unique_budget_per_bu_dept_year');

            // Rename the column back to product_id
            $table->renameColumn('department_id', 'product_id');

            // Add the foreign key constraint to products
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            // Add the indexes back
            $table->index(['product_id', 'budget_year']);
            $table->unique(['business_unit_id', 'product_id', 'budget_year'], 'unique_budget_per_bu_product_year');
        });
    }
};