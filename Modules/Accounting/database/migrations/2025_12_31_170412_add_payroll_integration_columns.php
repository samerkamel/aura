<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds payroll integration columns to expense_schedules
     * and transfer status columns to payroll_runs for the Payroll → Accounting integration.
     */
    public function up(): void
    {
        // Add payroll linking columns to expense_schedules
        Schema::table('expense_schedules', function (Blueprint $table) {
            $table->foreignId('payroll_run_id')->nullable()->after('perfex_id')
                ->constrained('payroll_runs')->nullOnDelete();
            $table->foreignId('payroll_employee_id')->nullable()->after('payroll_run_id')
                ->constrained('employees')->nullOnDelete();
            $table->boolean('is_payroll_expense')->default(false)->after('payroll_employee_id');

            // Index for quick payroll expense lookups
            $table->index(['is_payroll_expense', 'payroll_run_id']);
        });

        // Add transfer status columns to payroll_runs
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->enum('transfer_status', ['pending', 'processing', 'transferred', 'failed'])
                ->default('pending')->after('status');
            $table->timestamp('transferred_at')->nullable()->after('transfer_status');
            $table->foreignId('transferred_by')->nullable()->after('transferred_at')
                ->constrained('users')->nullOnDelete();
            $table->boolean('synced_to_accounting')->default(false)->after('transferred_by');
            $table->timestamp('synced_at')->nullable()->after('synced_to_accounting');

            // Index for transfer status queries
            $table->index(['status', 'transfer_status']);
        });

        // Create or get "Payroll Expense" category
        $this->createPayrollExpenseCategory();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_schedules', function (Blueprint $table) {
            $table->dropIndex(['is_payroll_expense', 'payroll_run_id']);
            $table->dropForeign(['payroll_run_id']);
            $table->dropForeign(['payroll_employee_id']);
            $table->dropColumn(['payroll_run_id', 'payroll_employee_id', 'is_payroll_expense']);
        });

        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropIndex(['status', 'transfer_status']);
            $table->dropForeign(['transferred_by']);
            $table->dropColumn([
                'transfer_status', 'transferred_at', 'transferred_by',
                'synced_to_accounting', 'synced_at'
            ]);
        });
    }

    /**
     * Create Payroll Expense category if it doesn't exist.
     */
    private function createPayrollExpenseCategory(): void
    {
        $categoryClass = \Modules\Accounting\Models\ExpenseCategory::class;

        if (class_exists($categoryClass)) {
            $categoryClass::firstOrCreate(
                ['name' => 'Payroll Expense'],
                [
                    'name_ar' => 'مصروفات الرواتب',
                    'description' => 'Expenses automatically synced from payroll runs',
                    'color' => '#4CAF50',
                    'is_active' => true,
                    'sort_order' => 1,
                ]
            );
        }
    }
};
