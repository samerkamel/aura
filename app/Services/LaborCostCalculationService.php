<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Models\PublicHoliday;
use Modules\Attendance\Models\Setting;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeSalaryHistory;
use Modules\Payroll\Models\JiraWorklog;
use Modules\Project\Models\Project;

/**
 * Service for calculating and persisting labor costs.
 *
 * This service calculates labor costs based on:
 * - Employee salary at the time of work (using salary history)
 * - Billable hours in the month
 * - Labor cost multiplier from settings
 * - 20% PM overhead
 */
class LaborCostCalculationService
{
    /**
     * Billable hours per day constant.
     */
    private const BILLABLE_HOURS_PER_DAY = 5;

    /**
     * Project management overhead percentage.
     */
    private const PM_OVERHEAD_PERCENTAGE = 0.20;

    /**
     * Default labor cost multiplier (used if not set in settings).
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
            // Skip weekends (Friday = 5, Saturday = 6 in Middle East)
            if ($date->dayOfWeek === Carbon::FRIDAY || $date->dayOfWeek === Carbon::SATURDAY) {
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
     * Get employee salary at a specific date using salary history.
     */
    private function getEmployeeSalaryAtDate(Employee $employee, Carbon $date): float
    {
        // Try to get salary from history
        $historySalary = EmployeeSalaryHistory::where('employee_id', $employee->id)
            ->where('effective_date', '<=', $date)
            ->orderByDesc('effective_date')
            ->first();

        if ($historySalary) {
            return (float) $historySalary->new_salary;
        }

        // Fall back to current salary
        return (float) ($employee->base_salary ?? 0);
    }

    /**
     * Calculate and persist labor cost for a single worklog.
     */
    public function calculateWorklogCost(JiraWorklog $worklog, bool $force = false): array
    {
        // Skip if already calculated and not forcing
        if ($worklog->cost_calculated && !$force) {
            return [
                'success' => false,
                'message' => 'Cost already calculated',
                'worklog_id' => $worklog->id,
            ];
        }

        $employee = $worklog->employee;

        if (!$employee) {
            return [
                'success' => false,
                'message' => 'No employee associated with worklog',
                'worklog_id' => $worklog->id,
            ];
        }

        $worklogDate = $worklog->worklog_started;
        $year = $worklogDate->year;
        $month = $worklogDate->month;

        // Get salary at time of worklog
        $salaryAtTime = $this->getEmployeeSalaryAtDate($employee, $worklogDate);

        if ($salaryAtTime <= 0) {
            return [
                'success' => false,
                'message' => 'Employee has no salary at time of worklog',
                'worklog_id' => $worklog->id,
            ];
        }

        // Calculate billable hours for the month
        $billableHoursInMonth = $this->calculateBillableHoursForMonth($year, $month);

        if ($billableHoursInMonth <= 0) {
            return [
                'success' => false,
                'message' => 'No billable hours in month',
                'worklog_id' => $worklog->id,
            ];
        }

        // Get labor cost multiplier
        $multiplier = $this->getLaborCostMultiplier();

        // Calculate hourly rate
        $hourlyRate = $salaryAtTime / $billableHoursInMonth;

        // Calculate labor cost: hourly_rate * hours * multiplier
        $laborCost = $hourlyRate * $worklog->time_spent_hours * $multiplier;

        // Calculate PM overhead (20%)
        $pmOverhead = $laborCost * self::PM_OVERHEAD_PERCENTAGE;

        // Total cost
        $totalCost = $laborCost + $pmOverhead;

        // Persist the calculation
        $worklog->update([
            'employee_salary_at_time' => $salaryAtTime,
            'billable_hours_in_month' => $billableHoursInMonth,
            'hourly_rate' => round($hourlyRate, 2),
            'labor_cost' => round($laborCost, 2),
            'labor_cost_multiplier' => $multiplier,
            'pm_overhead' => round($pmOverhead, 2),
            'total_cost' => round($totalCost, 2),
            'cost_calculated' => true,
            'cost_calculated_at' => now(),
        ]);

        Log::debug('Labor cost calculated for worklog', [
            'worklog_id' => $worklog->id,
            'employee_id' => $employee->id,
            'salary_at_time' => $salaryAtTime,
            'hours' => $worklog->time_spent_hours,
            'total_cost' => round($totalCost, 2),
        ]);

        return [
            'success' => true,
            'message' => 'Cost calculated successfully',
            'worklog_id' => $worklog->id,
            'data' => [
                'salary_at_time' => $salaryAtTime,
                'billable_hours_in_month' => $billableHoursInMonth,
                'hourly_rate' => round($hourlyRate, 2),
                'labor_cost' => round($laborCost, 2),
                'pm_overhead' => round($pmOverhead, 2),
                'total_cost' => round($totalCost, 2),
            ],
        ];
    }

    /**
     * Calculate costs for all worklogs of a project.
     */
    public function calculateProjectCosts(Project $project, bool $force = false): array
    {
        $query = $project->worklogs();

        if (!$force) {
            $query->where('cost_calculated', false);
        }

        $worklogs = $query->get();

        if ($worklogs->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No worklogs to process',
                'calculated' => 0,
                'total_cost' => 0,
            ];
        }

        $calculated = 0;
        $failed = 0;
        $totalCost = 0;

        foreach ($worklogs as $worklog) {
            $result = $this->calculateWorklogCost($worklog, $force);

            if ($result['success']) {
                $calculated++;
                $totalCost += $result['data']['total_cost'];
            } else {
                $failed++;
            }
        }

        return [
            'success' => $failed === 0,
            'message' => "Calculated {$calculated} worklogs" . ($failed > 0 ? ", {$failed} failed" : ''),
            'calculated' => $calculated,
            'failed' => $failed,
            'total_cost' => round($totalCost, 2),
        ];
    }

    /**
     * Calculate costs for all worklogs in a date range.
     */
    public function calculateCostsForPeriod(Carbon $startDate, Carbon $endDate, bool $force = false): array
    {
        $query = JiraWorklog::whereBetween('worklog_started', [$startDate, $endDate]);

        if (!$force) {
            $query->where('cost_calculated', false);
        }

        $worklogs = $query->with('employee')->get();

        if ($worklogs->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No worklogs to process',
                'calculated' => 0,
                'total_cost' => 0,
            ];
        }

        $calculated = 0;
        $failed = 0;
        $totalCost = 0;

        foreach ($worklogs as $worklog) {
            $result = $this->calculateWorklogCost($worklog, $force);

            if ($result['success']) {
                $calculated++;
                $totalCost += $result['data']['total_cost'];
            } else {
                $failed++;
            }
        }

        Log::info('Labor cost calculation for period completed', [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'calculated' => $calculated,
            'failed' => $failed,
            'total_cost' => $totalCost,
        ]);

        return [
            'success' => $failed === 0,
            'message' => "Calculated {$calculated} worklogs" . ($failed > 0 ? ", {$failed} failed" : ''),
            'calculated' => $calculated,
            'failed' => $failed,
            'total_cost' => round($totalCost, 2),
        ];
    }

    /**
     * Get persisted labor costs for a project.
     */
    public function getPersistedLaborCosts(Project $project, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = $project->worklogs()
            ->where('cost_calculated', true)
            ->with('employee');

        if ($startDate && $endDate) {
            $query->whereBetween('worklog_started', [$startDate, $endDate]);
        }

        $worklogs = $query->get();

        $totalLaborCost = $worklogs->sum('labor_cost');
        $totalPmOverhead = $worklogs->sum('pm_overhead');
        $totalCost = $worklogs->sum('total_cost');
        $totalHours = $worklogs->sum('time_spent_hours');

        // Group by employee
        $byEmployee = $worklogs->groupBy('employee_id')->map(function ($employeeWorklogs) {
            $employee = $employeeWorklogs->first()->employee;
            return [
                'employee_id' => $employee?->id,
                'employee_name' => $employee?->name ?? 'Unknown',
                'hours' => round($employeeWorklogs->sum('time_spent_hours'), 2),
                'labor_cost' => round($employeeWorklogs->sum('labor_cost'), 2),
                'pm_overhead' => round($employeeWorklogs->sum('pm_overhead'), 2),
                'total_cost' => round($employeeWorklogs->sum('total_cost'), 2),
            ];
        })->values()->toArray();

        // Group by month
        $byMonth = $worklogs->groupBy(function ($worklog) {
            return $worklog->worklog_started->format('Y-m');
        })->map(function ($monthWorklogs, $yearMonth) {
            return [
                'month' => $yearMonth,
                'month_label' => Carbon::createFromFormat('Y-m', $yearMonth)->format('M Y'),
                'hours' => round($monthWorklogs->sum('time_spent_hours'), 2),
                'labor_cost' => round($monthWorklogs->sum('labor_cost'), 2),
                'pm_overhead' => round($monthWorklogs->sum('pm_overhead'), 2),
                'total_cost' => round($monthWorklogs->sum('total_cost'), 2),
            ];
        })->values()->toArray();

        return [
            'total_hours' => round($totalHours, 2),
            'labor_subtotal' => round($totalLaborCost, 2),
            'pm_overhead' => round($totalPmOverhead, 2),
            'total' => round($totalCost, 2),
            'by_employee' => $byEmployee,
            'by_month' => $byMonth,
        ];
    }

    /**
     * Recalculate all costs for a project (force recalculation).
     */
    public function recalculateProjectCosts(Project $project): array
    {
        return $this->calculateProjectCosts($project, true);
    }

    /**
     * Get summary of uncalculated worklogs.
     */
    public function getUncalculatedSummary(): array
    {
        $uncalculated = JiraWorklog::where('cost_calculated', false)->count();
        $calculated = JiraWorklog::where('cost_calculated', true)->count();

        return [
            'uncalculated' => $uncalculated,
            'calculated' => $calculated,
            'total' => $uncalculated + $calculated,
            'percentage_calculated' => $uncalculated + $calculated > 0
                ? round(($calculated / ($uncalculated + $calculated)) * 100, 1)
                : 0,
        ];
    }
}
