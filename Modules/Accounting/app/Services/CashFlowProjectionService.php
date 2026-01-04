<?php

namespace Modules\Accounting\Services;

use Modules\Accounting\Models\ExpenseSchedule;
use Modules\Accounting\Models\ContractPayment;
use Modules\Accounting\Models\CashFlowProjection;
use Modules\Accounting\Models\Contract;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * CashFlowProjectionService
 *
 * Handles the calculation and generation of cash flow projections
 * based on income and expense schedules.
 */
class CashFlowProjectionService
{
    protected float $startingBalance = 0.0;
    protected ?Collection $cachedActiveSchedules = null;

    /**
     * Set the starting cash balance for projections.
     */
    public function setStartingBalance(float $balance): self
    {
        $this->startingBalance = $balance;
        return $this;
    }

    /**
     * Generate cash flow projections for a specified period.
     */
    public function generateProjections(
        Carbon $startDate,
        Carbon $endDate,
        string $periodType = 'monthly'
    ): Collection {
        // Pre-cache active schedules to avoid loading them for each period
        $this->cachedActiveSchedules = ExpenseSchedule::active()->with('category')->get();

        $projections = collect();
        $runningBalance = $this->startingBalance;

        $periods = $this->generatePeriods($startDate, $endDate, $periodType);

        foreach ($periods as $period) {
            $projection = $this->calculateProjectionForPeriod(
                $period['start'],
                $period['end'],
                $periodType,
                $runningBalance
            );

            $runningBalance = $projection['running_balance'];
            $projections->push($projection);
        }

        // Clear cache after processing
        $this->cachedActiveSchedules = null;

        return $projections;
    }

    /**
     * Generate and save cash flow projections to database.
     */
    public function generateAndSaveProjections(
        Carbon $startDate,
        Carbon $endDate,
        string $periodType = 'monthly'
    ): Collection {
        $projections = $this->generateProjections($startDate, $endDate, $periodType);
        $savedProjections = collect();

        foreach ($projections as $projectionData) {
            $projection = CashFlowProjection::updateOrCreate(
                [
                    'projection_date' => $projectionData['projection_date'],
                    'period_type' => $periodType,
                ],
                [
                    'projected_income' => $projectionData['projected_income'],
                    'projected_expenses' => $projectionData['projected_expenses'],
                    'net_flow' => $projectionData['net_flow'],
                    'running_balance' => $projectionData['running_balance'],
                    'has_deficit' => $projectionData['has_deficit'],
                    'income_breakdown' => $projectionData['income_breakdown'],
                    'expense_breakdown' => $projectionData['expense_breakdown'],
                    'calculated_at' => now(),
                ]
            );

            $savedProjections->push($projection);
        }

        return $savedProjections;
    }

    /**
     * Calculate projection for a single period.
     * Now includes actual vs expected data based on period timing.
     */
    protected function calculateProjectionForPeriod(
        Carbon $startDate,
        Carbon $endDate,
        string $periodType,
        float $previousBalance
    ): array {
        $now = now();
        $periodStart = $startDate->copy()->startOfDay();
        $periodEnd = $endDate->copy()->endOfDay();

        // Determine period type: past, current, or future
        $isPast = $periodEnd->lt($now->copy()->startOfDay());
        $isCurrent = $periodStart->lte($now) && $periodEnd->gte($now);
        $isFuture = $periodStart->gt($now);

        // Initialize data structures
        $actualIncome = 0;
        $expectedIncome = 0;
        $actualExpenses = 0;
        $scheduledExpenses = 0;
        $actualContracts = 0;
        $expectedContracts = 0;
        $incomeBreakdown = [];
        $expenseBreakdown = [];

        // =====================
        // INCOME CALCULATIONS
        // =====================

        // Actual Income: Paid contract payments in this period
        if ($isPast || $isCurrent) {
            $paidPayments = ContractPayment::with('contract')
                ->where('status', 'paid')
                ->whereBetween('paid_date', [$periodStart, $periodEnd])
                ->get();

            foreach ($paidPayments as $payment) {
                $actualIncome += $payment->paid_amount ?? $payment->amount;
                $contractName = $payment->contract->client_name ?? 'Unknown';
                $incomeBreakdown[$contractName] = ($incomeBreakdown[$contractName] ?? 0) + ($payment->paid_amount ?? $payment->amount);
            }
        }

        // Expected Income: Pending payments with due dates in this period
        if ($isCurrent || $isFuture) {
            $pendingPayments = ContractPayment::with('contract')
                ->where('status', 'pending')
                ->whereBetween('due_date', [$periodStart, $periodEnd])
                ->get();

            foreach ($pendingPayments as $payment) {
                $expectedIncome += $payment->amount;
                $contractName = $payment->contract->client_name ?? 'Unknown';
                $incomeBreakdown[$contractName . ' (Expected)'] = ($incomeBreakdown[$contractName . ' (Expected)'] ?? 0) + $payment->amount;
            }
        }

        // =====================
        // CONTRACT CALCULATIONS
        // =====================

        // Actual Contracts: Approved/Active contracts with start_date in this period
        if ($isPast || $isCurrent) {
            $actualContracts = Contract::whereIn('status', ['approved', 'active'])
                ->whereBetween('start_date', [$periodStart, $periodEnd])
                ->sum('total_amount');
        }

        // Expected Contracts: Draft contracts with start_date in this period
        if ($isCurrent || $isFuture) {
            $expectedContracts = Contract::where('status', 'draft')
                ->whereBetween('start_date', [$periodStart, $periodEnd])
                ->sum('total_amount');
        }

        // =====================
        // EXPENSE CALCULATIONS
        // =====================

        // Actual Expenses: Paid expenses in this period
        if ($isPast || $isCurrent) {
            $paidExpenses = ExpenseSchedule::where('payment_status', 'paid')
                ->whereBetween('paid_date', [$periodStart, $periodEnd])
                ->with('category')
                ->get();

            foreach ($paidExpenses as $expense) {
                $actualExpenses += $expense->paid_amount ?? $expense->amount;
                $categoryName = $expense->category->name ?? 'Uncategorized';
                $expenseBreakdown[$categoryName] = ($expenseBreakdown[$categoryName] ?? 0) + ($expense->paid_amount ?? $expense->amount);
            }
        }

        // Scheduled Expenses: Active recurring schedules for future/current periods
        if ($isCurrent || $isFuture) {
            $activeSchedules = $this->cachedActiveSchedules ?? ExpenseSchedule::active()->with('category')->get();

            foreach ($activeSchedules as $schedule) {
                $occurrences = $schedule->getOccurrencesInPeriod($periodStart, $periodEnd);
                $scheduleAmount = count($occurrences) * $schedule->amount;

                if ($scheduleAmount > 0) {
                    $scheduledExpenses += $scheduleAmount;
                    $categoryName = ($schedule->category->name ?? 'Uncategorized') . ' (Scheduled)';
                    $expenseBreakdown[$categoryName] = ($expenseBreakdown[$categoryName] ?? 0) + $scheduleAmount;
                }
            }
        }

        // Calculate totals
        $totalIncome = $actualIncome + $expectedIncome;
        $totalExpenses = $actualExpenses + $scheduledExpenses;
        $totalContracts = $actualContracts + $expectedContracts;
        $netFlow = $totalIncome - $totalExpenses;
        $runningBalance = $previousBalance + $netFlow;
        $hasDeficit = $netFlow < 0;

        return [
            'projection_date' => $periodType === 'weekly'
                ? $startDate->copy()->startOfWeek()
                : ($periodType === 'monthly' ? $startDate->copy()->startOfMonth() : $startDate->copy()),
            'period_type_label' => $isPast ? 'past' : ($isCurrent ? 'current' : 'future'),

            // Income breakdown
            'actual_income' => $actualIncome,
            'expected_income' => $expectedIncome,
            'projected_income' => $totalIncome,

            // Contracts breakdown
            'actual_contracts' => $actualContracts,
            'expected_contracts' => $expectedContracts,
            'total_contracts' => $totalContracts,

            // Expenses breakdown
            'actual_expenses' => $actualExpenses,
            'scheduled_expenses' => $scheduledExpenses,
            'projected_expenses' => $totalExpenses,

            // Totals
            'net_flow' => $netFlow,
            'running_balance' => $runningBalance,
            'has_deficit' => $hasDeficit,
            'income_breakdown' => $incomeBreakdown,
            'expense_breakdown' => $expenseBreakdown,
        ];
    }

    /**
     * Generate period boundaries based on period type.
     */
    protected function generatePeriods(Carbon $startDate, Carbon $endDate, string $periodType): array
    {
        $periods = [];
        $current = $startDate->copy();

        // Safety counter to prevent infinite loops
        $maxPeriods = 100;
        $count = 0;

        while ($current->lte($endDate) && $count < $maxPeriods) {
            $count++;
            $periodStart = $current->copy();

            // Calculate the natural end of the period
            $naturalPeriodEnd = match($periodType) {
                'daily' => $current->copy()->endOfDay(),
                'weekly' => $current->copy()->endOfWeek(),
                'monthly' => $current->copy()->endOfMonth(),
                default => $current->copy()->endOfMonth(),
            };

            // Limit to end date if period extends beyond
            $periodEnd = $naturalPeriodEnd->gt($endDate) ? $endDate->copy() : $naturalPeriodEnd;

            $periods[] = [
                'start' => $periodStart,
                'end' => $periodEnd,
            ];

            // Move to next period using the NATURAL period end (not the limited one)
            // This prevents infinite loops when end date is mid-period
            $current = match($periodType) {
                'daily' => $naturalPeriodEnd->copy()->addDay()->startOfDay(),
                'weekly' => $naturalPeriodEnd->copy()->addDay()->startOfWeek(),
                'monthly' => $naturalPeriodEnd->copy()->addDay()->startOfMonth(),
                default => $naturalPeriodEnd->copy()->addDay()->startOfMonth(),
            };
        }

        return $periods;
    }

    /**
     * Get cash flow summary for a period.
     */
    public function getCashFlowSummary(Carbon $startDate, Carbon $endDate): array
    {
        $projections = $this->generateProjections($startDate, $endDate, 'monthly');

        return [
            'total_projected_income' => $projections->sum('projected_income'),
            'total_projected_expenses' => $projections->sum('projected_expenses'),
            'net_cash_flow' => $projections->sum('net_flow'),
            'periods_with_deficit' => $projections->where('has_deficit', true)->count(),
            'average_monthly_income' => $projections->avg('projected_income'),
            'average_monthly_expenses' => $projections->avg('projected_expenses'),
            'final_balance' => $projections->last()['running_balance'] ?? $this->startingBalance,
        ];
    }

    /**
     * Identify potential cash flow problems.
     */
    public function identifyCashFlowProblems(Carbon $startDate, Carbon $endDate): array
    {
        $projections = $this->generateProjections($startDate, $endDate, 'monthly');
        $problems = [];

        foreach ($projections as $projection) {
            if ($projection['running_balance'] < 0) {
                $problems[] = [
                    'type' => 'negative_balance',
                    'severity' => 'critical',
                    'date' => $projection['projection_date'],
                    'amount' => abs($projection['running_balance']),
                    'message' => 'Projected negative cash balance of $' . number_format(abs($projection['running_balance']), 2),
                ];
            } elseif ($projection['has_deficit'] && abs($projection['net_flow']) > 5000) {
                $problems[] = [
                    'type' => 'large_deficit',
                    'severity' => 'warning',
                    'date' => $projection['projection_date'],
                    'amount' => abs($projection['net_flow']),
                    'message' => 'Large cash deficit of $' . number_format(abs($projection['net_flow']), 2),
                ];
            }
        }

        return $problems;
    }

    /**
     * Get upcoming cash flow events (next 30 days).
     */
    public function getUpcomingCashFlowEvents(int $days = 30): array
    {
        $startDate = now();
        $endDate = now()->addDays($days);
        $events = [];

        // Get all active schedules and payments
        $expenseSchedules = ExpenseSchedule::active()->get();
        $contractPayments = ContractPayment::with('contract')
            ->where('status', 'pending')
            ->where('due_date', '>=', $startDate)
            ->where('due_date', '<=', $endDate)
            ->get();

        // Get upcoming expenses
        foreach ($expenseSchedules as $schedule) {
            $occurrences = $schedule->getOccurrencesInPeriod($startDate, $endDate);
            foreach ($occurrences as $occurrence) {
                $events[] = [
                    'date' => $occurrence,
                    'type' => 'expense',
                    'amount' => -$schedule->amount,
                    'description' => $schedule->name,
                    'category' => $schedule->category->name ?? 'Uncategorized',
                ];
            }
        }

        // Get upcoming income from contract payments
        foreach ($contractPayments as $payment) {
            $events[] = [
                'date' => $payment->due_date,
                'type' => 'income',
                'amount' => $payment->amount,
                'description' => $payment->name,
                'category' => $payment->contract->client_name ?? 'Unknown Client',
            ];
        }

        // Sort by date
        usort($events, function ($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        return $events;
    }
}