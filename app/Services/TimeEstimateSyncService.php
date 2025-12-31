<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeSalaryHistory;
use Modules\Payroll\Models\JiraWorklog;
use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectTimeEstimate;
use Modules\Attendance\Models\PublicHoliday;
use Modules\Attendance\Models\Setting;

/**
 * Service for syncing Jira worklogs to project time estimates.
 *
 * This service:
 * - Syncs actual hours from Jira worklogs to time estimates
 * - Calculates costs based on employee hourly rates
 * - Updates variance and progress tracking
 */
class TimeEstimateSyncService
{
    /**
     * Billable hours per day constant.
     */
    private const BILLABLE_HOURS_PER_DAY = 5;

    /**
     * Default labor cost multiplier.
     */
    private const DEFAULT_LABOR_COST_MULTIPLIER = 2.9;

    /**
     * Get the labor cost multiplier from settings.
     */
    private function getLaborCostMultiplier(): float
    {
        return (float) Setting::get('labor_cost_multiplier', self::DEFAULT_LABOR_COST_MULTIPLIER);
    }

    /**
     * Calculate hourly rate for an employee based on their salary.
     */
    public function calculateEmployeeHourlyRate(Employee $employee, ?Carbon $date = null): float
    {
        $date = $date ?? now();
        $salary = $this->getEmployeeSalaryAtDate($employee, $date);

        if ($salary <= 0) {
            return 0;
        }

        // Calculate billable hours for the month
        $billableHours = $this->calculateBillableHoursForMonth($date->year, $date->month);

        if ($billableHours <= 0) {
            return 0;
        }

        return $salary / $billableHours;
    }

    /**
     * Get employee salary at a specific date.
     */
    private function getEmployeeSalaryAtDate(Employee $employee, Carbon $date): float
    {
        $historySalary = EmployeeSalaryHistory::where('employee_id', $employee->id)
            ->where('effective_date', '<=', $date)
            ->orderByDesc('effective_date')
            ->first();

        if ($historySalary) {
            return (float) $historySalary->new_salary;
        }

        return (float) ($employee->base_salary ?? 0);
    }

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
            if ($date->dayOfWeek === Carbon::FRIDAY || $date->dayOfWeek === Carbon::SATURDAY) {
                continue;
            }
            if (in_array($date->format('Y-m-d'), $publicHolidays)) {
                continue;
            }
            $workingDays++;
        }

        return $workingDays * self::BILLABLE_HOURS_PER_DAY;
    }

    /**
     * Sync worklogs for a specific time estimate by Jira issue key.
     */
    public function syncWorklogsToEstimate(ProjectTimeEstimate $estimate, bool $force = false): array
    {
        if (!$estimate->jira_issue_key) {
            return [
                'success' => false,
                'message' => 'No Jira issue key linked to this estimate',
            ];
        }

        // Get worklogs for this issue
        $worklogs = JiraWorklog::where('issue_key', $estimate->jira_issue_key)
            ->with('employee')
            ->get();

        if ($worklogs->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No worklogs found for this issue',
                'synced_hours' => 0,
            ];
        }

        // Calculate totals
        $totalHours = $worklogs->sum('time_spent_hours');
        $totalCost = $worklogs->sum('total_cost') ?: $this->calculateWorklogsCost($worklogs);
        $worklogCount = $worklogs->count();

        // Update the estimate
        $estimate->update([
            'actual_hours' => $totalHours,
            'synced_hours' => $totalHours,
            'actual_cost' => round($totalCost, 2),
            'worklog_count' => $worklogCount,
            'last_worklog_sync' => now(),
        ]);

        // Recalculate variance
        $this->recalculateVariance($estimate);

        return [
            'success' => true,
            'message' => "Synced {$worklogCount} worklogs ({$totalHours}h)",
            'synced_hours' => round($totalHours, 2),
            'synced_cost' => round($totalCost, 2),
            'worklog_count' => $worklogCount,
        ];
    }

    /**
     * Calculate total cost for worklogs without persisted costs.
     */
    private function calculateWorklogsCost($worklogs): float
    {
        $totalCost = 0;
        $multiplier = $this->getLaborCostMultiplier();

        foreach ($worklogs as $worklog) {
            if ($worklog->total_cost) {
                $totalCost += $worklog->total_cost;
            } elseif ($worklog->employee) {
                $hourlyRate = $this->calculateEmployeeHourlyRate(
                    $worklog->employee,
                    $worklog->worklog_started
                );
                $laborCost = $hourlyRate * $worklog->time_spent_hours * $multiplier;
                $pmOverhead = $laborCost * 0.20; // 20% PM overhead
                $totalCost += $laborCost + $pmOverhead;
            }
        }

        return $totalCost;
    }

    /**
     * Recalculate variance for a time estimate.
     */
    public function recalculateVariance(ProjectTimeEstimate $estimate): void
    {
        // Hours variance
        $varianceHours = $estimate->actual_hours - $estimate->estimated_hours;
        $variancePercentage = $estimate->estimated_hours > 0
            ? ($varianceHours / $estimate->estimated_hours) * 100
            : 0;

        // Cost variance
        $costVariance = ($estimate->actual_cost ?? 0) - ($estimate->estimated_cost ?? 0);
        $costVariancePercentage = ($estimate->estimated_cost ?? 0) > 0
            ? ($costVariance / $estimate->estimated_cost) * 100
            : 0;

        // Progress percentage
        $progress = $estimate->estimated_hours > 0
            ? min(100, ($estimate->actual_hours / $estimate->estimated_hours) * 100)
            : 0;

        // Remaining hours
        $remaining = max(0, $estimate->estimated_hours - $estimate->actual_hours);

        $estimate->update([
            'variance_hours' => round($varianceHours, 2),
            'variance_percentage' => round($variancePercentage, 2),
            'cost_variance' => round($costVariance, 2),
            'cost_variance_percentage' => round($costVariancePercentage, 2),
            'progress_percentage' => round($progress, 2),
            'remaining_hours' => round($remaining, 2),
        ]);
    }

    /**
     * Sync all estimates for a project.
     */
    public function syncProjectEstimates(Project $project): array
    {
        $estimates = $project->timeEstimates()
            ->whereNotNull('jira_issue_key')
            ->get();

        $synced = 0;
        $failed = 0;
        $totalHours = 0;
        $totalCost = 0;

        foreach ($estimates as $estimate) {
            $result = $this->syncWorklogsToEstimate($estimate);
            if ($result['success']) {
                $synced++;
                $totalHours += $result['synced_hours'] ?? 0;
                $totalCost += $result['synced_cost'] ?? 0;
            } else {
                $failed++;
            }
        }

        return [
            'success' => $failed === 0,
            'message' => "Synced {$synced} estimates" . ($failed > 0 ? ", {$failed} failed" : ''),
            'synced' => $synced,
            'failed' => $failed,
            'total_hours' => round($totalHours, 2),
            'total_cost' => round($totalCost, 2),
        ];
    }

    /**
     * Create time estimate from Jira issue.
     */
    public function createEstimateFromJiraIssue(
        Project $project,
        string $issueKey,
        string $issueSummary,
        float $estimatedHours,
        ?int $assigneeId = null
    ): ProjectTimeEstimate {
        // Calculate hourly rate if assignee is set
        $hourlyRate = null;
        $estimatedCost = null;

        if ($assigneeId) {
            $employee = Employee::find($assigneeId);
            if ($employee) {
                $hourlyRate = $this->calculateEmployeeHourlyRate($employee);
                $multiplier = $this->getLaborCostMultiplier();
                $laborCost = $hourlyRate * $estimatedHours * $multiplier;
                $pmOverhead = $laborCost * 0.20;
                $estimatedCost = $laborCost + $pmOverhead;
            }
        }

        $estimate = ProjectTimeEstimate::create([
            'project_id' => $project->id,
            'task_name' => $issueSummary,
            'jira_issue_key' => $issueKey,
            'estimated_hours' => $estimatedHours,
            'assigned_to' => $assigneeId,
            'hourly_rate' => $hourlyRate,
            'estimated_cost' => $estimatedCost,
            'status' => 'not_started',
            'created_by' => auth()->id() ?? 1,
        ]);

        // Immediately sync worklogs
        $this->syncWorklogsToEstimate($estimate);

        return $estimate;
    }

    /**
     * Calculate estimated cost for a time estimate.
     */
    public function calculateEstimatedCost(ProjectTimeEstimate $estimate): float
    {
        if (!$estimate->assigned_to) {
            return 0;
        }

        $employee = $estimate->assignee;
        if (!$employee) {
            return 0;
        }

        $hourlyRate = $this->calculateEmployeeHourlyRate($employee);
        $multiplier = $this->getLaborCostMultiplier();
        $laborCost = $hourlyRate * $estimate->estimated_hours * $multiplier;
        $pmOverhead = $laborCost * 0.20;

        return $laborCost + $pmOverhead;
    }

    /**
     * Update hourly rate and recalculate costs for an estimate.
     */
    public function updateEstimateCosts(ProjectTimeEstimate $estimate): void
    {
        if (!$estimate->assigned_to) {
            return;
        }

        $employee = $estimate->assignee;
        if (!$employee) {
            return;
        }

        $hourlyRate = $this->calculateEmployeeHourlyRate($employee);
        $estimatedCost = $this->calculateEstimatedCost($estimate);

        $estimate->update([
            'hourly_rate' => round($hourlyRate, 2),
            'estimated_cost' => round($estimatedCost, 2),
        ]);

        // Recalculate variance after cost update
        $this->recalculateVariance($estimate);
    }

    /**
     * Get summary statistics for a project's time estimates.
     */
    public function getProjectEstimateSummary(Project $project): array
    {
        $estimates = $project->timeEstimates;

        $totalEstimatedHours = $estimates->sum('estimated_hours');
        $totalActualHours = $estimates->sum('actual_hours');
        $totalEstimatedCost = $estimates->sum('estimated_cost');
        $totalActualCost = $estimates->sum('actual_cost');

        // By status
        $byStatus = $estimates->groupBy('status')->map(function ($group) {
            return [
                'count' => $group->count(),
                'estimated_hours' => $group->sum('estimated_hours'),
                'actual_hours' => $group->sum('actual_hours'),
                'estimated_cost' => $group->sum('estimated_cost'),
                'actual_cost' => $group->sum('actual_cost'),
            ];
        });

        // Over/under budget counts
        $overBudget = $estimates->filter(fn($e) => $e->variance_hours > 0)->count();
        $underBudget = $estimates->filter(fn($e) => $e->variance_hours < 0)->count();
        $onBudget = $estimates->filter(fn($e) => $e->variance_hours == 0)->count();

        return [
            'total_estimates' => $estimates->count(),
            'total_estimated_hours' => round($totalEstimatedHours, 2),
            'total_actual_hours' => round($totalActualHours, 2),
            'hours_variance' => round($totalActualHours - $totalEstimatedHours, 2),
            'hours_variance_percentage' => $totalEstimatedHours > 0
                ? round((($totalActualHours - $totalEstimatedHours) / $totalEstimatedHours) * 100, 1)
                : 0,
            'total_estimated_cost' => round($totalEstimatedCost, 2),
            'total_actual_cost' => round($totalActualCost, 2),
            'cost_variance' => round($totalActualCost - $totalEstimatedCost, 2),
            'by_status' => $byStatus,
            'over_budget_count' => $overBudget,
            'under_budget_count' => $underBudget,
            'on_budget_count' => $onBudget,
            'completion_percentage' => $estimates->avg('progress_percentage') ?? 0,
        ];
    }

    /**
     * Bulk sync all estimates that have Jira issue keys.
     */
    public function bulkSyncAllEstimates(): array
    {
        $estimates = ProjectTimeEstimate::whereNotNull('jira_issue_key')
            ->with('project')
            ->get();

        $synced = 0;
        $failed = 0;

        foreach ($estimates as $estimate) {
            $result = $this->syncWorklogsToEstimate($estimate);
            if ($result['success']) {
                $synced++;
            } else {
                $failed++;
            }
        }

        Log::info('Bulk time estimate sync completed', [
            'synced' => $synced,
            'failed' => $failed,
        ]);

        return [
            'success' => $failed === 0,
            'message' => "Bulk sync: {$synced} synced" . ($failed > 0 ? ", {$failed} failed" : ''),
            'synced' => $synced,
            'failed' => $failed,
        ];
    }
}
