<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The column is already renamed to department_id, we just need to fix the index names
        Schema::table('budgets', function (Blueprint $table) {
            // Drop the old index names
            $table->dropIndex('budgets_product_id_budget_year_index');
            $table->dropUnique('unique_budget_per_bu_product_year');

            // Add the new index names
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
            // Drop the new index names
            $table->dropIndex(['department_id', 'budget_year']);
            $table->dropUnique('unique_budget_per_bu_dept_year');

            // Add the old index names back
            $table->index(['department_id', 'budget_year'], 'budgets_product_id_budget_year_index');
            $table->unique(['business_unit_id', 'department_id', 'budget_year'], 'unique_budget_per_bu_product_year');
        });
    }
};