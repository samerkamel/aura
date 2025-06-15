<?php

namespace Modules\Leave\Services;

use Modules\HR\Models\Employee;
use Modules\Leave\Models\LeaveRecord;
use Modules\Leave\Models\LeavePolicy;
use Modules\Leave\Models\LeavePolicyTier;
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
        // Calculate employee's tenure in years
        $employeeStartDate = $employee->start_date;
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

        $totalUsedDays = 0;

        foreach ($leaveRecords as $record) {
            // Calculate actual days within the year range
            $effectiveStartDate = $record->start_date->max($yearStart);
            $effectiveEndDate = $record->end_date->min($yearEnd);

            if ($effectiveStartDate->lte($effectiveEndDate)) {
                $totalUsedDays += (int)($effectiveStartDate->diffInDays($effectiveEndDate)) + 1;
            }
        }

        return $totalUsedDays;
    }

    /**
     * Check if employee has sufficient leave balance for a request.
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
        $year = $year ?? $startDate->year;
        $yearStart = Carbon::create($year, 1, 1, 0, 0, 0);
        $yearEnd = Carbon::create($year, 12, 31, 23, 59, 59);

        $balance = $this->calculatePolicyBalance($employee, $policy, $yearStart, $yearEnd);
        $requestedDays = (int)$startDate->diffInDays($endDate) + 1;

        return [
            'available' => $balance['remaining_days'] >= $requestedDays,
            'requested_days' => $requestedDays,
            'remaining_days' => $balance['remaining_days'],
            'shortfall' => max(0, $requestedDays - $balance['remaining_days']),
        ];
    }
}
