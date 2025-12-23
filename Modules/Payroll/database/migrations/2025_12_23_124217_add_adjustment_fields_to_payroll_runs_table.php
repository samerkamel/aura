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
        Schema::table('payroll_runs', function (Blueprint $table) {
            // Calculated salary before adjustments (copy of final_salary before override)
            $table->decimal('calculated_salary', 12, 2)->nullable()->after('final_salary');
            // Manual salary adjustment (override)
            $table->decimal('adjusted_salary', 12, 2)->nullable()->after('calculated_salary');
            // Bonus amount to add
            $table->decimal('bonus_amount', 12, 2)->default(0)->after('adjusted_salary');
            // Deduction amount to subtract
            $table->decimal('deduction_amount', 12, 2)->default(0)->after('bonus_amount');
            // Notes for adjustments
            $table->text('adjustment_notes')->nullable()->after('deduction_amount');
            // Flag to indicate if manual adjustments were made
            $table->boolean('is_adjusted')->default(false)->after('adjustment_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropColumn([
                'calculated_salary',
                'adjusted_salary',
                'bonus_amount',
                'deduction_amount',
                'adjustment_notes',
                'is_adjusted',
            ]);
        });
    }
};
