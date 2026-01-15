<?php

namespace Modules\Accounting\Services\Budget;

use Modules\Accounting\Models\Budget;
use Modules\Accounting\Models\BudgetPersonnelEntry;
use Modules\Accounting\Models\BudgetPersonnelAllocation;
use Modules\HR\Models\Employee;

/**
 * PersonnelService
 *
 * Manages salary planning and product/G&A allocations for personnel budgeting.
 */
class PersonnelService
{
    /**
     * Initialize personnel entries from all active employees
     */
    public function initializePersonnelEntries(Budget $budget): void
    {
        $employees = Employee::active()->get();

        foreach ($employees as $employee) {
            $this->createPersonnelEntry($budget, $employee);
        }
    }

    /**
     * Create a personnel entry for an employee in a budget
     */
    public function createPersonnelEntry(Budget $budget, Employee $employee): BudgetPersonnelEntry
    {
        return BudgetPersonnelEntry::create([
            'budget_id' => $budget->id,
            'employee_id' => $employee->id,
            'current_salary' => $employee->salary ?? 0,
            'proposed_salary' => null,
            'increase_percentage' => null,
            'is_new_hire' => false,
            'hire_month' => null,
        ]);
    }

    /**
     * Update personnel entry salary data
     */
    public function updateSalary(
        BudgetPersonnelEntry $entry,
        float $proposedSalary,
        ?bool $isNewHire = null,
        ?int $hireMonth = null
    ): void {
        $data = [
            'proposed_salary' => $proposedSalary,
            'increase_percentage' => $entry->calculateIncreasePercentage(),
        ];

        if ($isNewHire !== null) {
            $data['is_new_hire'] = $isNewHire;
        }

        if ($hireMonth !== null) {
            $data['hire_month'] = $hireMonth;
        }

        $entry->update($data);
    }

    /**
     * Set allocation for an employee to a product
     *
     * @param BudgetPersonnelEntry $entry Personnel entry
     * @param int|null $productId Product ID (null for G&A)
     * @param float $percentage Allocation percentage
     */
    public function setAllocation(BudgetPersonnelEntry $entry, ?int $productId, float $percentage): BudgetPersonnelAllocation
    {
        // Remove existing allocation if it exists
        $existing = $entry->allocations()
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->update(['allocation_percentage' => $percentage]);
            return $existing;
        }

        // Create new allocation
        return BudgetPersonnelAllocation::create([
            'budget_personnel_entry_id' => $entry->id,
            'product_id' => $productId,
            'allocation_percentage' => $percentage,
        ]);
    }

    /**
     * Remove an allocation
     */
    public function removeAllocation(BudgetPersonnelAllocation $allocation): void
    {
        $allocation->delete();
    }

    /**
     * Set multiple allocations for an employee
     *
     * @param BudgetPersonnelEntry $entry Personnel entry
     * @param array $allocations Array of [product_id => percentage] pairs (null key for G&A)
     */
    public function setAllocations(BudgetPersonnelEntry $entry, array $allocations): void
    {
        // Remove all existing allocations
        $entry->allocations()->delete();

        // Create new allocations
        $totalPercentage = 0;
        foreach ($allocations as $productId => $percentage) {
            if ($percentage > 0) {
                $this->setAllocation($entry, $productId === 'ga' ? null : $productId, $percentage);
                $totalPercentage += $percentage;
            }
        }

        // Validate allocations sum to 100%
        if (!$entry->allocationsValid()) {
            throw new \InvalidArgumentException("Allocations must sum to 100%, got {$totalPercentage}%");
        }
    }

    /**
     * Get personnel entries for a budget with allocations
     */
    public function getBudgetPersonnelEntries(Budget $budget)
    {
        return $budget->personnelEntries()
            ->with('employee', 'allocations', 'allocations.product')
            ->get();
    }

    /**
     * Get total personnel cost for a budget
     */
    public function getTotalPersonnelCost(Budget $budget): float
    {
        return $budget->personnelEntries()
            ->get()
            ->sum(fn($entry) => $entry->getEffectiveSalary());
    }

    /**
     * Get personnel cost by product
     */
    public function getCostByProduct(Budget $budget, int $productId): float
    {
        return $budget->personnelEntries()
            ->with('allocations')
            ->get()
            ->sum(fn($entry) => $entry->getCostForProduct($productId));
    }

    /**
     * Get G&A personnel cost
     */
    public function getGACost(Budget $budget): float
    {
        return $budget->personnelEntries()
            ->with('allocations')
            ->get()
            ->sum(fn($entry) => $entry->getGACost());
    }

    /**
     * Validate all personnel entries have valid allocations
     */
    public function validateAllAllocations(Budget $budget): bool
    {
        return $budget->personnelEntries()
            ->get()
            ->every(fn($entry) => $entry->allocationsValid());
    }

    /**
     * Get personnel entries missing allocations
     */
    public function getEntriesMissingAllocations(Budget $budget)
    {
        return $budget->personnelEntries()
            ->with('allocations')
            ->get()
            ->filter(fn($entry) => $entry->allocations()->count() === 0);
    }

    /**
     * Get salary changes summary
     * Note: Returns annual totals (monthly × 12) for budget purposes
     */
    public function getSalaryChangesSummary(Budget $budget): array
    {
        $entries = $budget->personnelEntries()->get();

        // Monthly totals
        $monthlyCurrentSalaries = $entries->sum('current_salary');
        $monthlyProposedSalaries = $entries->sum(fn($e) => $e->getEffectiveSalary());

        // Annual totals (multiply by 12 for budget year)
        $totalCurrentSalaries = $monthlyCurrentSalaries * 12;
        $totalProposedSalaries = $monthlyProposedSalaries * 12;
        $totalIncrease = $totalProposedSalaries - $totalCurrentSalaries;
        $increasePercentage = $totalCurrentSalaries > 0 ? ($totalIncrease / $totalCurrentSalaries) * 100 : 0;

        return [
            'total_current_salaries' => $totalCurrentSalaries,
            'total_proposed_salaries' => $totalProposedSalaries,
            'total_increase_amount' => $totalIncrease,
            'total_increase_percentage' => $increasePercentage,
            'employee_count' => $entries->count(),
            'new_hires_count' => $entries->filter(fn($e) => $e->is_new_hire)->count(),
            // Also include monthly values for reference
            'monthly_current_salaries' => $monthlyCurrentSalaries,
            'monthly_proposed_salaries' => $monthlyProposedSalaries,
        ];
    }

    /**
     * Get allocation summary for a personnel entry
     */
    public function getAllocationSummary(BudgetPersonnelEntry $entry): array
    {
        return $entry->getAllocationsSummary();
    }

    /**
     * Calculate effective salary (proposed or current)
     */
    public function getEffectiveSalary(BudgetPersonnelEntry $entry): float
    {
        return $entry->getEffectiveSalary();
    }

    /**
     * Get salary increase amount
     */
    public function getSalaryIncreaseAmount(BudgetPersonnelEntry $entry): float
    {
        return $entry->getSalaryChangeAmount();
    }

    /**
     * Mark employee as new hire from capacity planning
     */
    public function markAsNewHireFromCapacity(BudgetPersonnelEntry $entry, int $hireMonth, float $salary): void
    {
        $entry->update([
            'is_new_hire' => true,
            'hire_month' => $hireMonth,
            'proposed_salary' => $salary,
            'current_salary' => 0,
        ]);
    }
}
