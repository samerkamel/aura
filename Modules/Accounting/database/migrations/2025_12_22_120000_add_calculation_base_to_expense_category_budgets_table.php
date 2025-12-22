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
        Schema::table('expense_category_budgets', function (Blueprint $table) {
            $table->enum('calculation_base', ['total_revenue', 'net_income'])
                ->default('total_revenue')
                ->after('budget_percentage')
                ->comment('total_revenue = % from total monthly revenue, net_income = % from عائد الدخل (revenue after Tier 1 deductions)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_category_budgets', function (Blueprint $table) {
            $table->dropColumn('calculation_base');
        });
    }
};
