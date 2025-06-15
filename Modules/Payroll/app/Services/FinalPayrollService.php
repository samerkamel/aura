<?php

namespace Modules\Payroll\Services;

use Modules\HR\Models\Employee;
use Carbon\Carbon;

/**
 * FinalPayrollService
 *
 * Handles the calculation of final pro-rated pay amounts for employee terminations
 * and resignations, covering the period from the last payroll run up to the
 * specified termination date.
 *
 * @author Dev Agent
 */
class FinalPayrollService
{
    /**
     * Calculate the final pro-rated pay amount for an employee.
     *
     * @param Employee $employee The employee being terminated/resigned
     * @param Carbon $terminationDate The date of termination/resignation
     * @return array Contains the calculation breakdown and final amount
     * @throws \Exception If calculation cannot be performed
     */
    public function calculateFinalPay(Employee $employee, Carbon $terminationDate): array
    {
        // Get the last payroll run date (we'll assume monthly payroll for now)
        $lastPayrollDate = $this->getLastPayrollDate($employee);

        // Calculate working days in the final period
        $workingDays = $this->calculateWorkingDays($lastPayrollDate, $terminationDate);

        // Calculate daily rate based on monthly salary
        $dailyRate = $this->calculateDailyRate($employee->base_salary);

        // Calculate the pro-rated amount
        $proRatedAmount = $workingDays * $dailyRate;

        return [
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'base_salary' => $employee->base_salary,
            'last_payroll_date' => $lastPayrollDate->format('Y-m-d'),
            'termination_date' => $terminationDate->format('Y-m-d'),
            'working_days' => $workingDays,
            'daily_rate' => round($dailyRate, 2),
            'pro_rated_amount' => round($proRatedAmount, 2),
            'calculation_details' => [
                'period_start' => $lastPayrollDate->format('Y-m-d'),
                'period_end' => $terminationDate->format('Y-m-d'),
                'total_days_in_period' => $lastPayrollDate->diffInDays($terminationDate) + 1,
                'working_days_calculated' => $workingDays,
                'daily_rate_formula' => 'Monthly Salary / 30 days',
                'final_calculation' => "Working Days ({$workingDays}) × Daily Rate ({$dailyRate})"
            ]
        ];
    }

    /**
     * Get the last payroll run date for an employee.
     * For now, we'll assume the first of the current month.
     * In a real system, this would query the payroll_runs table.
     *
     * @param Employee $employee
     * @return Carbon
     */
    private function getLastPayrollDate(Employee $employee): Carbon
    {
        // For simplicity, assume monthly payroll runs on the 1st of each month
        // In a real system, this would query the actual payroll_runs table
        $now = Carbon::now();
        return $now->copy()->startOfMonth();
    }

    /**
     * Calculate the number of working days between two dates.
     * This excludes weekends but includes public holidays for simplicity.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return int
     */
    private function calculateWorkingDays(Carbon $startDate, Carbon $endDate): int
    {
        $workingDays = 0;
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            // Count Monday through Friday as working days
            if ($currentDate->isWeekday()) {
                $workingDays++;
            }
            $currentDate->addDay();
        }

        return $workingDays;
    }

    /**
     * Calculate the daily rate from monthly salary.
     * Using 30-day month for simplicity.
     *
     * @param float $monthlySalary
     * @return float
     */
    private function calculateDailyRate(float $monthlySalary): float
    {
        return $monthlySalary / 30;
    }

    /**
     * Store the final payroll calculation for export.
     * This would typically save to a final_payroll_runs table.
     *
     * @param array $calculationData
     * @return bool
     */
    public function storeFinalPayrollCalculation(array $calculationData): bool
    {
        // For now, we'll just log this. In a real system, this would save to database
        logger('Final payroll calculation stored', $calculationData);

        // TODO: Implement actual database storage when payroll_runs table is implemented
        return true;
    }
}
