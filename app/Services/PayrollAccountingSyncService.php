<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\Models\ExpenseCategory;
use Modules\Accounting\Models\ExpenseSchedule;
use Modules\Payroll\Models\PayrollRun;

/**
 * Service for syncing payroll runs to accounting expenses.
 *
 * This service handles:
 * 1. Creating scheduled expenses when payroll is finalized
 * 2. Marking expenses as paid when payroll is transferred
 * 3. Providing summary data for payroll-accounting integration
 */
class PayrollAccountingSyncService
{
    /**
     * The payroll expense category.
     */
    protected ?ExpenseCategory $payrollCategory = null;

    /**
     * Get or create the Payroll Expense category.
     */
    public function getPayrollCategory(): ExpenseCategory
    {
        if ($this->payrollCategory) {
            return $this->payrollCategory;
        }

        $this->payrollCategory = ExpenseCategory::firstOrCreate(
            ['name' => 'Payroll Expense'],
            [
                'name_ar' => 'مصروفات الرواتب',
                'description' => 'Expenses automatically synced from payroll runs',
                'color' => '#4CAF50',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        return $this->payrollCategory;
    }

    /**
     * Create scheduled expenses for a finalized payroll period.
     *
     * Creates individual expense entries for each employee in the payroll run.
     *
     * @param Collection $payrollRuns Collection of PayrollRun models
     * @return array Summary of created expenses
     */
    public function createScheduledExpenses(Collection $payrollRuns): array
    {
        if ($payrollRuns->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No payroll runs provided',
                'created' => 0,
                'total_amount' => 0,
            ];
        }

        $category = $this->getPayrollCategory();
        $firstRun = $payrollRuns->first();
        $periodName = Carbon::parse($firstRun->period_start_date)->format('F Y');
        $created = 0;
        $totalAmount = 0;

        DB::beginTransaction();

        try {
            foreach ($payrollRuns as $payrollRun) {
                // Skip if already synced
                if ($payrollRun->synced_to_accounting) {
                    continue;
                }

                $employee = $payrollRun->employee;
                $effectiveSalary = $payrollRun->effective_salary;

                // Create expense schedule entry
                ExpenseSchedule::create([
                    'category_id' => $category->id,
                    'payroll_run_id' => $payrollRun->id,
                    'payroll_employee_id' => $payrollRun->employee_id,
                    'is_payroll_expense' => true,
                    'name' => "Salary - {$employee->name}",
                    'description' => "Payroll for {$periodName}: {$employee->name}",
                    'amount' => $effectiveSalary,
                    'frequency_type' => 'monthly',
                    'frequency_value' => 1,
                    'start_date' => $payrollRun->period_start_date,
                    'end_date' => $payrollRun->period_end_date,
                    'expense_type' => 'one_time',
                    'expense_date' => $payrollRun->period_end_date,
                    'is_active' => true,
                    'payment_status' => 'pending',
                ]);

                // Mark payroll run as synced
                $payrollRun->update([
                    'synced_to_accounting' => true,
                    'synced_at' => now(),
                ]);

                $created++;
                $totalAmount += $effectiveSalary;
            }

            // Also create a summary expense for the total payroll
            $this->createPayrollSummaryExpense($payrollRuns, $category, $periodName, $totalAmount);

            DB::commit();

            Log::info('Payroll synced to accounting', [
                'period' => $periodName,
                'employees' => $created,
                'total_amount' => $totalAmount,
            ]);

            return [
                'success' => true,
                'message' => "Created {$created} expense entries for {$periodName}",
                'created' => $created,
                'total_amount' => $totalAmount,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to sync payroll to accounting', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to sync: ' . $e->getMessage(),
                'created' => 0,
                'total_amount' => 0,
            ];
        }
    }

    /**
     * Create a summary expense entry for the total payroll amount.
     */
    protected function createPayrollSummaryExpense(
        Collection $payrollRuns,
        ExpenseCategory $category,
        string $periodName,
        float $totalAmount
    ): ExpenseSchedule {
        $firstRun = $payrollRuns->first();

        // Create a summary JSON with employee breakdown
        $breakdown = $payrollRuns->map(function ($run) {
            return [
                'employee_id' => $run->employee_id,
                'employee_name' => $run->employee->name,
                'base_salary' => $run->base_salary,
                'calculated_salary' => $run->calculated_salary,
                'bonus' => $run->bonus_amount,
                'deduction' => $run->deduction_amount,
                'final_salary' => $run->effective_salary,
            ];
        })->toArray();

        return ExpenseSchedule::updateOrCreate(
            [
                'name' => "Total Payroll - {$periodName}",
                'is_payroll_expense' => true,
                'expense_date' => $firstRun->period_end_date,
            ],
            [
                'category_id' => $category->id,
                'description' => json_encode([
                    'type' => 'payroll_summary',
                    'period' => $periodName,
                    'employee_count' => $payrollRuns->count(),
                    'breakdown' => $breakdown,
                ]),
                'amount' => $totalAmount,
                'frequency_type' => 'monthly',
                'frequency_value' => 1,
                'start_date' => $firstRun->period_start_date,
                'end_date' => $firstRun->period_end_date,
                'expense_type' => 'one_time',
                'is_active' => true,
                'payment_status' => 'pending',
            ]
        );
    }

    /**
     * Mark payroll expenses as paid when payroll is transferred.
     *
     * @param Collection $payrollRuns Collection of PayrollRun models
     * @param int|null $paidFromAccountId The account used for payment
     * @return array Summary of marked expenses
     */
    public function markAsPaid(Collection $payrollRuns, ?int $paidFromAccountId = null): array
    {
        if ($payrollRuns->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No payroll runs provided',
                'updated' => 0,
            ];
        }

        $payrollRunIds = $payrollRuns->pluck('id')->toArray();
        $now = now();

        DB::beginTransaction();

        try {
            // Update all linked expenses to paid
            $updated = ExpenseSchedule::whereIn('payroll_run_id', $payrollRunIds)
                ->where('is_payroll_expense', true)
                ->update([
                    'payment_status' => 'paid',
                    'paid_date' => $now,
                    'paid_from_account_id' => $paidFromAccountId,
                ]);

            // Also update summary expense
            $firstRun = $payrollRuns->first();
            $periodName = Carbon::parse($firstRun->period_start_date)->format('F Y');

            ExpenseSchedule::where('name', "Total Payroll - {$periodName}")
                ->where('is_payroll_expense', true)
                ->update([
                    'payment_status' => 'paid',
                    'paid_date' => $now,
                    'paid_from_account_id' => $paidFromAccountId,
                ]);

            // Update payroll runs transfer status
            PayrollRun::whereIn('id', $payrollRunIds)->update([
                'transfer_status' => 'transferred',
                'transferred_at' => $now,
                'transferred_by' => auth()->id(),
            ]);

            DB::commit();

            Log::info('Payroll marked as transferred', [
                'payroll_run_ids' => $payrollRunIds,
                'updated_expenses' => $updated,
            ]);

            return [
                'success' => true,
                'message' => "Marked {$updated} expenses as paid",
                'updated' => $updated,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to mark payroll as paid', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to mark as paid: ' . $e->getMessage(),
                'updated' => 0,
            ];
        }
    }

    /**
     * Get expenses linked to a specific payroll period.
     *
     * @param Carbon $periodStart Start of payroll period
     * @param Carbon $periodEnd End of payroll period
     * @return Collection
     */
    public function getExpensesForPeriod(Carbon $periodStart, Carbon $periodEnd): Collection
    {
        return ExpenseSchedule::where('is_payroll_expense', true)
            ->whereBetween('expense_date', [$periodStart, $periodEnd])
            ->with(['category', 'paidFromAccount'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get payroll expenses summary for a period.
     *
     * @param Carbon $periodStart Start of payroll period
     * @param Carbon $periodEnd End of payroll period
     * @return array
     */
    public function getExpensesSummary(Carbon $periodStart, Carbon $periodEnd): array
    {
        $expenses = $this->getExpensesForPeriod($periodStart, $periodEnd);

        // Separate individual and summary expenses
        $individualExpenses = $expenses->filter(fn($e) => str_starts_with($e->name, 'Salary -'));
        $summaryExpense = $expenses->first(fn($e) => str_starts_with($e->name, 'Total Payroll'));

        return [
            'period' => $periodStart->format('F Y'),
            'total_amount' => $individualExpenses->sum('amount'),
            'employee_count' => $individualExpenses->count(),
            'paid_count' => $individualExpenses->where('payment_status', 'paid')->count(),
            'pending_count' => $individualExpenses->where('payment_status', 'pending')->count(),
            'summary_expense' => $summaryExpense,
            'individual_expenses' => $individualExpenses,
            'is_fully_paid' => $individualExpenses->every(fn($e) => $e->payment_status === 'paid'),
        ];
    }

    /**
     * Check if a payroll period has been synced to accounting.
     *
     * @param Carbon $periodStart Start of payroll period
     * @param Carbon $periodEnd End of payroll period
     * @return bool
     */
    public function isPeriodSynced(Carbon $periodStart, Carbon $periodEnd): bool
    {
        return PayrollRun::where('period_start_date', $periodStart)
            ->where('period_end_date', $periodEnd)
            ->where('status', 'finalized')
            ->where('synced_to_accounting', true)
            ->exists();
    }

    /**
     * Sync finalized payroll runs for a specific period.
     *
     * @param Carbon $periodStart Start of payroll period
     * @param Carbon $periodEnd End of payroll period
     * @return array Sync result
     */
    public function syncPeriod(Carbon $periodStart, Carbon $periodEnd): array
    {
        $payrollRuns = PayrollRun::where('period_start_date', $periodStart)
            ->where('period_end_date', $periodEnd)
            ->where('status', 'finalized')
            ->where('synced_to_accounting', false)
            ->with('employee')
            ->get();

        if ($payrollRuns->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No unsynced finalized payroll runs found for this period',
                'created' => 0,
                'total_amount' => 0,
            ];
        }

        return $this->createScheduledExpenses($payrollRuns);
    }
}
