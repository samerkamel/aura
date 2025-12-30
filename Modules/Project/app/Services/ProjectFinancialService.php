<?php

namespace Modules\Project\Services;

use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectBudget;
use Modules\Project\Models\ProjectCost;
use Modules\Project\Models\ProjectRevenue;
use Modules\Attendance\Models\PublicHoliday;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProjectFinancialService
{
    /**
     * Billable hours per day constant.
     */
    private const BILLABLE_HOURS_PER_DAY = 5;

    /**
     * Calculate billable hours for a given month.
     */
    private function calculateBillableHoursForMonth(int $year, int $month): float
    {
        $publicHolidays = PublicHoliday::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->pluck('date')
            ->map(fn($d) => $d->format('Y-m-d'))
            ->toArray();

        $startOfMonth = Carbon::create($year, $month, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $workingDays = 0;

        for ($date = $startOfMonth->copy(); $date <= $endOfMonth; $date->addDay()) {
            // Skip weekends (Saturday = 6, Sunday = 0)
            if ($date->dayOfWeek === Carbon::SATURDAY || $date->dayOfWeek === Carbon::SUNDAY) {
                continue;
            }
            // Skip public holidays
            if (in_array($date->format('Y-m-d'), $publicHolidays)) {
                continue;
            }
            $workingDays++;
        }

        return $workingDays * self::BILLABLE_HOURS_PER_DAY;
    }

    /**
     * Calculate dynamic labor costs from worklogs.
     * Formula: (Salary / Billable Hours This Month) × Worked Hours × 3
     */
    public function calculateLaborCostsFromWorklogs(Project $project, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = $project->worklogs()->with('employee');

        if ($startDate && $endDate) {
            $query->whereBetween('worklog_started', [$startDate, $endDate]);
        }

        $worklogs = $query->get();

        $laborDetails = [];
        $totalLaborCost = 0;
        $totalHours = 0;

        // Group by employee and month for accurate calculation
        $groupedWorklogs = $worklogs->groupBy('employee_id');

        foreach ($groupedWorklogs as $employeeId => $employeeWorklogs) {
            $employee = $employeeWorklogs->first()->employee;

            if (!$employee || $employee->base_salary <= 0) {
                continue;
            }

            // Group by year-month
            $monthlyWorklogs = $employeeWorklogs->groupBy(function ($worklog) {
                return $worklog->worklog_started->format('Y-m');
            });

            foreach ($monthlyWorklogs as $yearMonth => $monthWorklogs) {
                $firstWorklog = $monthWorklogs->first();
                $year = $firstWorklog->worklog_started->year;
                $month = $firstWorklog->worklog_started->month;

                $billableHoursThisMonth = $this->calculateBillableHoursForMonth($year, $month);
                $workedHoursThisMonth = $monthWorklogs->sum('time_spent_hours');

                if ($billableHoursThisMonth <= 0) {
                    continue;
                }

                // Formula: (Salary / Billable Hours This Month) × Worked Hours × 3
                $hourlyRate = $employee->base_salary / $billableHoursThisMonth;
                $cost = $hourlyRate * $workedHoursThisMonth * 3;

                $laborDetails[] = [
                    'employee_id' => $employeeId,
                    'employee_name' => $employee->name,
                    'month' => $yearMonth,
                    'month_label' => Carbon::createFromFormat('Y-m', $yearMonth)->format('M Y'),
                    'salary' => $employee->base_salary,
                    'billable_hours' => $billableHoursThisMonth,
                    'worked_hours' => round($workedHoursThisMonth, 2),
                    'hourly_rate' => round($hourlyRate, 2),
                    'cost' => round($cost, 2),
                ];

                $totalLaborCost += $cost;
                $totalHours += $workedHoursThisMonth;
            }
        }

        return [
            'total' => round($totalLaborCost, 2),
            'total_hours' => round($totalHours, 2),
            'details' => $laborDetails,
        ];
    }

    /**
     * Get total project costs (recorded + dynamic labor).
     */
    public function getTotalProjectCosts(Project $project): array
    {
        // Get recorded costs (non-labor)
        $recordedCosts = $project->costs()->where('cost_type', '!=', 'labor')->sum('amount');

        // Get dynamic labor costs from worklogs
        $laborCosts = $this->calculateLaborCostsFromWorklogs($project);

        return [
            'recorded_costs' => round($recordedCosts, 2),
            'labor_costs' => $laborCosts['total'],
            'labor_hours' => $laborCosts['total_hours'],
            'total' => round($recordedCosts + $laborCosts['total'], 2),
        ];
    }

    /**
     * Get financial summary for a project.
     */
    public function getFinancialSummary(Project $project): array
    {
        $totalBudget = $project->budgets()->active()->sum('planned_amount');

        // Get total costs (recorded + dynamic labor)
        $totalCosts = $this->getTotalProjectCosts($project);
        $totalSpent = $totalCosts['total'];

        $totalRevenue = $project->revenues()->sum('amount');
        $receivedRevenue = $project->revenues()->sum('amount_received');
        $pendingRevenue = $totalRevenue - $receivedRevenue;

        $grossProfit = $receivedRevenue - $totalSpent;
        $grossMargin = $receivedRevenue > 0 ? ($grossProfit / $receivedRevenue) * 100 : 0;

        $budgetRemaining = $totalBudget - $totalSpent;
        $budgetUtilization = $totalBudget > 0 ? ($totalSpent / $totalBudget) * 100 : 0;

        return [
            'total_budget' => $totalBudget,
            'total_spent' => $totalSpent,
            'budget_remaining' => $budgetRemaining,
            'budget_utilization' => round($budgetUtilization, 1),
            'total_revenue' => $totalRevenue,
            'received_revenue' => $receivedRevenue,
            'pending_revenue' => $pendingRevenue,
            'gross_profit' => $grossProfit,
            'gross_margin' => round($grossMargin, 1),
            'is_profitable' => $grossProfit > 0,
            'budget_status' => $this->getBudgetStatus($budgetUtilization),
        ];
    }

    /**
     * Get budget status based on utilization.
     */
    private function getBudgetStatus(float $utilization): string
    {
        if ($utilization >= 100) {
            return 'over';
        } elseif ($utilization >= 90) {
            return 'critical';
        } elseif ($utilization >= 75) {
            return 'warning';
        }
        return 'healthy';
    }

    /**
     * Calculate burn rate for a project.
     */
    public function calculateBurnRate(Project $project, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);
        $costs = $project->costs()
            ->where('cost_date', '>=', $startDate)
            ->sum('amount');

        $dailyBurnRate = $costs / $days;
        $weeklyBurnRate = $dailyBurnRate * 7;
        $monthlyBurnRate = $dailyBurnRate * 30;

        // Calculate runway
        $totalBudget = $project->budgets()->active()->sum('planned_amount');
        $totalSpent = $project->costs()->sum('amount');
        $remaining = $totalBudget - $totalSpent;
        $runwayDays = $dailyBurnRate > 0 ? $remaining / $dailyBurnRate : null;

        return [
            'daily' => round($dailyBurnRate, 2),
            'weekly' => round($weeklyBurnRate, 2),
            'monthly' => round($monthlyBurnRate, 2),
            'period_days' => $days,
            'period_total' => round($costs, 2),
            'runway_days' => $runwayDays ? round($runwayDays) : null,
            'runway_date' => $runwayDays ? Carbon::now()->addDays($runwayDays)->format('Y-m-d') : null,
        ];
    }

    /**
     * Get cost breakdown by type.
     */
    public function getCostBreakdown(Project $project): array
    {
        // Get recorded costs (excluding labor since we calculate it dynamically)
        $costs = $project->costs()
            ->where('cost_type', '!=', 'labor')
            ->selectRaw('cost_type, SUM(amount) as total')
            ->groupBy('cost_type')
            ->pluck('total', 'cost_type')
            ->toArray();

        // Get dynamic labor costs from worklogs
        $laborCosts = $this->calculateLaborCostsFromWorklogs($project);
        $costs['labor'] = $laborCosts['total'];

        $total = array_sum($costs);

        $breakdown = [];
        foreach (ProjectCost::COST_TYPES as $type => $label) {
            $amount = $costs[$type] ?? 0;
            $breakdown[] = [
                'type' => $type,
                'label' => $label,
                'amount' => $amount,
                'percentage' => $total > 0 ? round(($amount / $total) * 100, 1) : 0,
                'color' => ProjectCost::COST_TYPE_COLORS[$type] ?? 'secondary',
                'is_dynamic' => $type === 'labor',
            ];
        }

        return [
            'total' => $total,
            'breakdown' => $breakdown,
            'labor_details' => $laborCosts['details'],
            'labor_hours' => $laborCosts['total_hours'],
        ];
    }

    /**
     * Get revenue breakdown by type.
     */
    public function getRevenueBreakdown(Project $project): array
    {
        $revenues = $project->revenues()
            ->selectRaw('revenue_type, SUM(amount) as total, SUM(amount_received) as received')
            ->groupBy('revenue_type')
            ->get()
            ->keyBy('revenue_type')
            ->toArray();

        $total = array_sum(array_column($revenues, 'total'));
        $totalReceived = array_sum(array_column($revenues, 'received'));

        $breakdown = [];
        foreach (ProjectRevenue::REVENUE_TYPES as $type => $label) {
            $data = $revenues[$type] ?? ['total' => 0, 'received' => 0];
            $breakdown[] = [
                'type' => $type,
                'label' => $label,
                'amount' => $data['total'] ?? 0,
                'received' => $data['received'] ?? 0,
                'pending' => ($data['total'] ?? 0) - ($data['received'] ?? 0),
                'percentage' => $total > 0 ? round((($data['total'] ?? 0) / $total) * 100, 1) : 0,
                'color' => ProjectRevenue::REVENUE_TYPE_COLORS[$type] ?? 'secondary',
            ];
        }

        return [
            'total' => $total,
            'received' => $totalReceived,
            'pending' => $total - $totalReceived,
            'collection_rate' => $total > 0 ? round(($totalReceived / $total) * 100, 1) : 0,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Get budget breakdown by category.
     */
    public function getBudgetBreakdown(Project $project): array
    {
        $budgets = $project->budgets()
            ->active()
            ->get()
            ->map(function ($budget) {
                return [
                    'id' => $budget->id,
                    'category' => $budget->category,
                    'category_label' => $budget->category_label,
                    'planned' => $budget->planned_amount,
                    'actual' => $budget->actual_amount,
                    'remaining' => $budget->remaining_amount,
                    'utilization' => $budget->utilization_percentage,
                    'status' => $budget->status,
                    'status_color' => $budget->status_color,
                    'category_color' => $budget->category_color,
                ];
            });

        $totalPlanned = $budgets->sum('planned');
        $totalActual = $budgets->sum('actual');

        return [
            'total_planned' => $totalPlanned,
            'total_actual' => $totalActual,
            'total_remaining' => $totalPlanned - $totalActual,
            'overall_utilization' => $totalPlanned > 0 ? round(($totalActual / $totalPlanned) * 100, 1) : 0,
            'categories' => $budgets->toArray(),
        ];
    }

    /**
     * Get monthly financial trend.
     */
    public function getMonthlyTrend(Project $project, int $months = 6): array
    {
        $startDate = Carbon::now()->subMonths($months - 1)->startOfMonth();
        $trend = [];

        for ($i = 0; $i < $months; $i++) {
            $monthStart = $startDate->copy()->addMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth();

            $costs = $project->costs()
                ->whereBetween('cost_date', [$monthStart, $monthEnd])
                ->sum('amount');

            $revenue = $project->revenues()
                ->whereBetween('revenue_date', [$monthStart, $monthEnd])
                ->sum('amount_received');

            $trend[] = [
                'month' => $monthStart->format('M Y'),
                'month_short' => $monthStart->format('M'),
                'costs' => round($costs, 2),
                'revenue' => round($revenue, 2),
                'profit' => round($revenue - $costs, 2),
            ];
        }

        return $trend;
    }

    /**
     * Get profitability analysis.
     */
    public function getProfitabilityAnalysis(Project $project): array
    {
        $totalRevenue = $project->revenues()->sum('amount');
        $receivedRevenue = $project->revenues()->sum('amount_received');

        // Get recorded costs (non-labor)
        $recordedCosts = $project->costs()->where('cost_type', '!=', 'labor')->sum('amount');

        // Get dynamic labor costs from worklogs
        $laborCostsData = $this->calculateLaborCostsFromWorklogs($project);
        $laborCosts = $laborCostsData['total'];
        $totalHours = $laborCostsData['total_hours'];

        $totalCosts = $recordedCosts + $laborCosts;
        $nonLaborCosts = $recordedCosts;

        $grossProfit = $receivedRevenue - $totalCosts;
        $grossMargin = $receivedRevenue > 0 ? ($grossProfit / $receivedRevenue) * 100 : 0;

        // Calculate labor efficiency
        $effectiveRate = $totalHours > 0 ? $receivedRevenue / $totalHours : 0;
        $averageCostRate = $totalHours > 0 ? $laborCosts / $totalHours : 0;

        // Project ROI
        $roi = $totalCosts > 0 ? (($receivedRevenue - $totalCosts) / $totalCosts) * 100 : 0;

        return [
            'total_revenue' => $totalRevenue,
            'received_revenue' => $receivedRevenue,
            'total_costs' => $totalCosts,
            'labor_costs' => $laborCosts,
            'non_labor_costs' => $nonLaborCosts,
            'gross_profit' => $grossProfit,
            'gross_margin' => round($grossMargin, 1),
            'total_hours' => round($totalHours, 1),
            'effective_hourly_rate' => round($effectiveRate, 2),
            'average_cost_rate' => round($averageCostRate, 2),
            'hourly_margin' => round($effectiveRate - $averageCostRate, 2),
            'roi' => round($roi, 1),
            'is_profitable' => $grossProfit > 0,
            'profitability_status' => $this->getProfitabilityStatus($grossMargin),
        ];
    }

    /**
     * Get profitability status.
     */
    private function getProfitabilityStatus(float $margin): string
    {
        if ($margin >= 30) {
            return 'excellent';
        } elseif ($margin >= 20) {
            return 'good';
        } elseif ($margin >= 10) {
            return 'fair';
        } elseif ($margin >= 0) {
            return 'low';
        }
        return 'loss';
    }

    /**
     * Get upcoming payments (receivables).
     */
    public function getUpcomingPayments(Project $project, int $days = 30): Collection
    {
        return $project->revenues()
            ->where('status', '!=', 'received')
            ->where(function ($query) use ($days) {
                $query->whereNull('due_date')
                    ->orWhere('due_date', '<=', Carbon::now()->addDays($days));
            })
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Get overdue payments.
     */
    public function getOverduePayments(Project $project): Collection
    {
        return $project->revenues()
            ->where('status', '!=', 'received')
            ->where('due_date', '<', Carbon::now())
            ->whereRaw('(amount - amount_received) > 0')
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Record a cost entry.
     */
    public function recordCost(Project $project, array $data): ProjectCost
    {
        $data['project_id'] = $project->id;
        $data['created_by'] = auth()->id();

        return ProjectCost::create($data);
    }

    /**
     * Record a revenue entry.
     */
    public function recordRevenue(Project $project, array $data): ProjectRevenue
    {
        $data['project_id'] = $project->id;
        $data['created_by'] = auth()->id();

        return ProjectRevenue::create($data);
    }

    /**
     * Create budget categories for a project.
     */
    public function createBudget(Project $project, array $data): ProjectBudget
    {
        $data['project_id'] = $project->id;

        return ProjectBudget::create($data);
    }

    /**
     * Get financial dashboard data.
     */
    public function getFinancialDashboard(Project $project): array
    {
        return [
            'summary' => $this->getFinancialSummary($project),
            'burn_rate' => $this->calculateBurnRate($project),
            'cost_breakdown' => $this->getCostBreakdown($project),
            'revenue_breakdown' => $this->getRevenueBreakdown($project),
            'budget_breakdown' => $this->getBudgetBreakdown($project),
            'profitability' => $this->getProfitabilityAnalysis($project),
            'monthly_trend' => $this->getMonthlyTrend($project),
            'upcoming_payments' => $this->getUpcomingPayments($project)->take(5),
            'overdue_payments' => $this->getOverduePayments($project),
        ];
    }

    /**
     * Generate labor costs from worklogs.
     */
    public function generateLaborCostsFromWorklogs(Project $project, Carbon $startDate, Carbon $endDate): int
    {
        // Get existing worklog IDs that already have costs
        $existingWorklogIds = ProjectCost::where('project_id', $project->id)
            ->where('reference_type', 'worklog')
            ->pluck('reference_id')
            ->toArray();

        // Get worklogs for the project in the date range that don't have costs yet
        $worklogs = $project->worklogs()
            ->whereBetween('worklog_started', [$startDate, $endDate])
            ->whereNotIn('id', $existingWorklogIds)
            ->with('employee')
            ->get();

        $count = 0;
        foreach ($worklogs as $worklog) {
            $hourlyRate = $worklog->employee->hourly_rate ?? $project->hourly_rate ?? 0;
            $hours = $worklog->time_spent_hours;
            $amount = $hours * $hourlyRate;

            ProjectCost::create([
                'project_id' => $project->id,
                'cost_type' => 'labor',
                'description' => "Worklog: {$worklog->issue_summary}",
                'amount' => $amount,
                'cost_date' => $worklog->worklog_started->toDateString(),
                'employee_id' => $worklog->employee_id,
                'hours' => $hours,
                'hourly_rate' => $hourlyRate,
                'is_billable' => true,
                'is_auto_generated' => true,
                'reference_type' => 'worklog',
                'reference_id' => $worklog->id,
                'created_by' => auth()->id(),
            ]);

            $count++;
        }

        return $count;
    }
}
