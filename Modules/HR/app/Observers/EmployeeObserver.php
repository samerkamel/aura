<?php

namespace Modules\HR\Observers;

use Modules\HR\Models\Employee;
use Modules\HR\Models\SalaryHistory;

/**
 * Employee Observer
 *
 * Handles Employee model events, particularly for tracking salary changes
 * and automatically creating salary history records.
 *
 * @author Dev Agent
 */
class EmployeeObserver
{
    /**
     * Handle the Employee "updating" event.
     *
     * This method is called before an employee record is updated,
     * allowing us to capture salary changes before they're saved.
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
    }
}
