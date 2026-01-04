<?php

namespace Modules\HR\Observers;

use Modules\HR\Models\Employee;
use Modules\HR\Models\SalaryHistory;
use Modules\HR\Models\EmployeeHourlyRateHistory;

/**
 * Employee Observer
 *
 * Handles Employee model events, particularly for tracking salary and hourly rate changes
 * and automatically creating history records.
 *
 * @author Dev Agent
 */
class EmployeeObserver
{
    /**
     * Handle the Employee "updating" event.
     *
     * This method is called before an employee record is updated,
     * allowing us to capture salary and hourly rate changes before they're saved.
     */
    public function updating(Employee $employee): void
    {
        // Check if the base_salary is being changed
        if ($employee->isDirty('base_salary')) {
            $oldSalary = $employee->getOriginal('base_salary');
            $newSalary = $employee->base_salary;

            // Only create history record if there's an actual change
            if ($oldSalary != $newSalary && ! is_null($oldSalary)) {
                SalaryHistory::create([
                    'employee_id' => $employee->id,
                    'old_salary' => $oldSalary,
                    'new_salary' => $newSalary,
                    'change_date' => now(),
                    'reason' => $employee->salary_change_reason ?? null,
                ]);

                // Clear the temporary reason field after using it
                unset($employee->salary_change_reason);
            }
        }

        // Check if the hourly_rate is being changed
        if ($employee->isDirty('hourly_rate')) {
            $oldRate = $employee->getOriginal('hourly_rate');
            $newRate = $employee->hourly_rate;

            // Only create history record if there's an actual change
            if ($oldRate != $newRate && ! is_null($oldRate) && $oldRate > 0) {
                // Close the current rate record
                EmployeeHourlyRateHistory::where('employee_id', $employee->id)
                    ->whereNull('end_date')
                    ->update(['end_date' => now()->subDay()]);

                // Create new rate record
                EmployeeHourlyRateHistory::create([
                    'employee_id' => $employee->id,
                    'hourly_rate' => $newRate,
                    'currency' => 'EGP',
                    'effective_date' => now(),
                    'end_date' => null,
                    'reason' => $employee->hourly_rate_change_reason ?? 'adjustment',
                    'notes' => $employee->hourly_rate_change_notes ?? null,
                    'approved_by' => $employee->hourly_rate_approved_by ?? null,
                    'created_by' => auth()->id(),
                ]);

                // Clear the temporary fields after using them
                unset($employee->hourly_rate_change_reason);
                unset($employee->hourly_rate_change_notes);
                unset($employee->hourly_rate_approved_by);
            }
        }
    }
}
