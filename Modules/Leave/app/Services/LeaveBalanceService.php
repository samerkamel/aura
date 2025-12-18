<?php

namespace Modules\Leave\Services;

use Modules\HR\Models\Employee;
use Modules\Leave\Models\LeaveRecord;
use Modules\Leave\Models\LeavePolicy;
use Modules\Leave\Models\LeavePolicyTier;
use Modules\Attendance\Services\WorkingDaysService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Leave Balance Service
 *
 * Calculates employee leave balances based on their policies,
 * tenure, and used leave records.
 *
 * @author Dev Agent
 */
class LeaveBalanceService
{
    /**
     * Calculate leave balance summary for an employee.
     *
     * @param Employee $employee
     * @param int|null $year
     * @return array
     */
    public function getLeaveBalanceSummary(Employee $employee, ?int $year = null): array
    {
        $year = $year ?? Carbon::now()->year;
        $yearStart = Carbon::create($year, 1, 1, 0, 0, 0);
        $yearEnd = Carbon::create($year, 12, 31, 23, 59, 59);

        // Get all active leave policies
        $leavePolicies = LeavePolicy::active()->get();
        $balances = [];

        foreach ($leavePolicies as $policy) {
            $balances[] = $this->calculatePolicyBalance($employee, $policy, $yearStart, $yearEnd);
        }

        return [
            'year' => $year,
            'employee_id' => $employee->id,
            'balances' => $balances,
            'generated_at' => Carbon::now()
        ];
    }

    /**
     * Calculate balance for a specific leave policy.
     *
     * @param Employee $employee
     * @param LeavePolicy $policy
     * @param Carbon $yearStart
     * @param Carbon $yearEnd
     * @return array
     */
    protected function calculatePolicyBalance(Employee $employee, LeavePolicy $policy, Carbon $yearStart, Carbon $yearEnd): array
    {
        // Check if this is a rolling window policy (like sick leave)
        $config = $policy->config ?? [];
        if ($policy->type === 'sick_leave' && isset($config['period_in_years']) && isset($config['days'])) {
            return $this->calculateRollingWindowBalance($employee, $policy, $config);
        }

        // Standard annual leave calculation
        // Calculate employee's tenure in years
        // If no start_date, assume employee started at the beginning of the year (0 tenure years)
        $employeeStartDate = $employee->start_date ?? $yearStart->copy();
        $tenureYears = (int)$employeeStartDate->diffInYears($yearStart);

        // Get applicable policy tier based on tenure
        $applicableTier = $this->getApplicableTier($policy, $tenureYears);

        // Calculate total entitled days
        $entitledDays = $this->calculateEntitledDays($policy, $applicableTier, $employeeStartDate, $yearStart, $yearEnd);

        // Calculate used days from leave records
        $usedDays = $this->calculateUsedDays($employee, $policy, $yearStart, $yearEnd);

        // Calculate remaining days
        $remainingDays = max(0, $entitledDays - $usedDays);

        return [
            'policy_id' => $policy->id,
            'policy_name' => $policy->name,
            'policy_type' => $policy->type,
            'entitled_days' => $entitledDays,
            'used_days' => $usedDays,
            'remaining_days' => $remainingDays,
            'applicable_tier' => $applicableTier ? [
                'id' => $applicableTier->id,
                'min_years' => $applicableTier->min_years,
                'max_years' => $applicableTier->max_years,
                'annual_days' => $applicableTier->annual_days,
            ] : null,
        ];
    }

    /**
     * Calculate balance for rolling window policies (e.g., sick leave).
     * These policies have a total allowance over a rolling period (e.g., 60 days over 3 years).
     *
     * @param Employee $employee
     * @param LeavePolicy $policy
     * @param array $config
     * @return array
     */
    protected function calculateRollingWindowBalance(Employee $employee, LeavePolicy $policy, array $config): array
    {
        $totalDays = (float) ($config['days'] ?? 0);
        $periodInYears = (int) ($config['period_in_years'] ?? 1);
        $periodInMonths = $periodInYears * 12;

        // Calculate the rolling window: from (today - period) to today
        $windowEnd = Carbon::now()->endOfDay();
        $windowStart = Carbon::now()->subMonths($periodInMonths)->startOfDay();

        // Calculate used days within the rolling window
        $usedDays = $this->calculateUsedDays($employee, $policy, $windowStart, $windowEnd);

        // Calculate remaining days
        $remainingDays = max(0, $totalDays - $usedDays);

        return [
            'policy_id' => $policy->id,
            'policy_name' => $policy->name,
            'policy_type' => $policy->type,
            'entitled_days' => $totalDays,
            'used_days' => $usedDays,
            'remaining_days' => $remainingDays,
            'applicable_tier' => null,
            'rolling_window' => [
                'period_in_years' => $periodInYears,
                'window_start' => $windowStart->format('Y-m-d'),
                'window_end' => $windowEnd->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Get the applicable leave policy tier based on employee tenure.
     *
     * @param LeavePolicy $policy
     * @param int $tenureYears
     * @return LeavePolicyTier|null
     */
    protected function getApplicableTier(LeavePolicy $policy, int $tenureYears): ?LeavePolicyTier
    {
        return $policy->tiers()
            ->where('min_years', '<=', $tenureYears)
            ->where(function ($query) use ($tenureYears) {
                $query->where('max_years', '>=', $tenureYears)
                    ->orWhereNull('max_years');
            })
            ->orderBy('min_years', 'desc')
            ->first();
    }

    /**
     * Calculate total entitled days for the year.
     *
     * @param LeavePolicy $policy
     * @param LeavePolicyTier|null $tier
     * @param Carbon $employeeStartDate
     * @param Carbon $yearStart
     * @param Carbon $yearEnd
     * @return float
     */
    protected function calculateEntitledDays(LeavePolicy $policy, ?LeavePolicyTier $tier, Carbon $employeeStartDate, Carbon $yearStart, Carbon $yearEnd): float
    {
        $daysPerYear = $tier ? $tier->annual_days : ($policy->initial_days ?? 0);

        // If employee started this year, calculate pro-rated entitlement
        if ($employeeStartDate->year === $yearStart->year) {
            $daysInYear = $yearStart->isLeapYear() ? 366 : 365;
            $workingDaysInYear = $employeeStartDate->diffInDays($yearEnd) + 1;
            return ($daysPerYear * $workingDaysInYear) / $daysInYear;
        }

        return $daysPerYear;
    }

    /**
     * Calculate used leave days from records.
     * Only counts working days (excludes weekends and public holidays).
     *
     * @param Employee $employee
     * @param LeavePolicy $policy
     * @param Carbon $yearStart
     * @param Carbon $yearEnd
     * @return int
     */
    protected function calculateUsedDays(Employee $employee, LeavePolicy $policy, Carbon $yearStart, Carbon $yearEnd): int
    {
        $leaveRecords = LeaveRecord::where('employee_id', $employee->id)
            ->where('leave_policy_id', $policy->id)
            ->approved()
            ->inDateRange($yearStart, $yearEnd)
            ->get();

        $workingDaysService = app(WorkingDaysService::class);
        $totalUsedDays = 0;

        foreach ($leaveRecords as $record) {
            // Calculate actual days within the year range
            $effectiveStartDate = $record->start_date->max($yearStart);
            $effectiveEndDate = $record->end_date->min($yearEnd);

            if ($effectiveStartDate->lte($effectiveEndDate)) {
                // Use WorkingDaysService to count only working days
                $totalUsedDays += $workingDaysService->calculateWorkingDays($effectiveStartDate, $effectiveEndDate);
            }
        }

        return $totalUsedDays;
    }

    /**
     * Check if employee has sufficient leave balance for a request.
     * Counts only working days (excludes weekends and public holidays).
     *
     * @param Employee $employee
     * @param LeavePolicy $policy
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int|null $year
     * @return array
     */
    public function checkLeaveAvailability(Employee $employee, LeavePolicy $policy, Carbon $startDate, Carbon $endDate, ?int $year = null): array
    {
        // For rolling window policies, we don't need year-based calculation
        $config = $policy->config ?? [];
        if ($policy->type === 'sick_leave' && isset($config['period_in_years']) && isset($config['days'])) {
            $balance = $this->calculateRollingWindowBalance($employee, $policy, $config);
        } else {
            $year = $year ?? $startDate->year;
            $yearStart = Carbon::create($year, 1, 1, 0, 0, 0);
            $yearEnd = Carbon::create($year, 12, 31, 23, 59, 59);
            $balance = $this->calculatePolicyBalance($employee, $policy, $yearStart, $yearEnd);
        }

        // Calculate requested working days (excluding weekends and public holidays)
        $workingDaysService = app(WorkingDaysService::class);
        $requestedDays = $workingDaysService->calculateWorkingDays($startDate, $endDate);

        // Check if there are any working days in the requested period
        if ($requestedDays === 0) {
            return [
                'available' => false,
                'requested_days' => 0,
                'remaining_days' => $balance['remaining_days'],
                'shortfall' => 0,
                'message' => 'The selected date range contains no working days.',
            ];
        }

        return [
            'available' => $balance['remaining_days'] >= $requestedDays,
            'requested_days' => $requestedDays,
            'remaining_days' => $balance['remaining_days'],
            'shortfall' => max(0, $requestedDays - $balance['remaining_days']),
        ];
    }
}
