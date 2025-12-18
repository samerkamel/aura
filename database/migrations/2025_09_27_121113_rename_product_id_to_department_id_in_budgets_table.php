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
        // Skip if indexes already have correct names (fresh install)
        $indexes = collect(DB::select('SHOW INDEX FROM budgets'))->pluck('Key_name')->unique();

        if ($indexes->contains('budgets_product_id_budget_year_index')) {
            Schema::table('budgets', function (Blueprint $table) {
                $table->dropIndex('budgets_product_id_budget_year_index');
            });
        }

        if ($indexes->contains('unique_budget_per_bu_product_year')) {
            Schema::table('budgets', function (Blueprint $table) {
                $table->dropUnique('unique_budget_per_bu_product_year');
            });
        }

        // Add new indexes if they don't exist
        if (!$indexes->contains('budgets_department_id_budget_year_index')) {
            Schema::table('budgets', function (Blueprint $table) {
                $table->index(['department_id', 'budget_year']);
            });
        }

        if (!$indexes->contains('unique_budget_per_bu_dept_year')) {
            Schema::table('budgets', function (Blueprint $table) {
                $table->unique(['business_unit_id', 'department_id', 'budget_year'], 'unique_budget_per_bu_dept_year');
            });
        }
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