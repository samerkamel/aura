<?php

namespace Modules\Accounting\Services;

use Modules\Accounting\Models\ExpenseSchedule;
use Modules\Accounting\Models\ContractPayment;
use Modules\Accounting\Models\CashFlowProjection;
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
     */
    protected function calculateProjectionForPeriod(
        Carbon $startDate,
        Carbon $endDate,
        string $periodType,
        float $previousBalance
    ): array {
        // Get all active schedules for this period
        $expenseSchedules = ExpenseSchedule::activeInPeriod($startDate, $endDate)->get();
        $contractPayments = ContractPayment::with('contract')
            ->where('status', 'pending')
            ->where('due_date', '>=', $startDate)
            ->where('due_date', '<=', $endDate)
            ->get();

        // Calculate total income and expenses
        $totalIncome = 0;
        $totalExpenses = 0;
        $incomeBreakdown = [];
        $expenseBreakdown = [];

        // Calculate income from contract payments
        foreach ($contractPayments as $payment) {
            $totalIncome += $payment->amount;

            $contractName = $payment->contract->client_name ?? 'Unknown';
            $incomeBreakdown[$contractName] = ($incomeBreakdown[$contractName] ?? 0) + $payment->amount;
        }

        // Calculate expenses
        foreach ($expenseSchedules as $schedule) {
            $occurrences = $schedule->getOccurrencesInPeriod($startDate, $endDate);
            $scheduleAmount = count($occurrences) * $schedule->amount;
            $totalExpenses += $scheduleAmount;

            $categoryName = $schedule->category->name ?? 'Uncategorized';
            $expenseBreakdown[$categoryName] = ($expenseBreakdown[$categoryName] ?? 0) + $scheduleAmount;
        }

        $netFlow = $totalIncome - $totalExpenses;
        $runningBalance = $previousBalance + $netFlow;
        $hasDeficit = $netFlow < 0;

        return [
            'projection_date' => $periodType === 'weekly'
                ? $startDate->copy()->startOfWeek()
                : ($periodType === 'monthly' ? $startDate->copy()->startOfMonth() : $startDate->copy()),
            'projected_income' => $totalIncome,
            'projected_expenses' => $totalExpenses,
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

        while ($current->lte($endDate)) {
            $periodStart = $current->copy();

            $periodEnd = match($periodType) {
                'daily' => $current->copy()->endOfDay(),
                'weekly' => $current->copy()->endOfWeek(),
                'monthly' => $current->copy()->endOfMonth(),
                default => $current->copy()->endOfMonth(),
            };

            // Don't exceed the end date
            if ($periodEnd->gt($endDate)) {
                $periodEnd = $endDate->copy();
            }

            $periods[] = [
                'start' => $periodStart,
                'end' => $periodEnd,
            ];

            // Move to next period
            $current = match($periodType) {
                'daily' => $periodEnd->copy()->addDay()->startOfDay(),
                'weekly' => $periodEnd->copy()->addDay()->startOfWeek(),
                'monthly' => $periodEnd->copy()->addDay()->startOfMonth(),
                default => $periodEnd->copy()->addDay()->startOfMonth(),
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