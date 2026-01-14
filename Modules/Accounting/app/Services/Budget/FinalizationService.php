<?php

namespace Modules\Accounting\Services\Budget;

use Modules\Accounting\Models\Budget;
use Illuminate\Database\Eloquent\Collection;

/**
 * FinalizationService
 *
 * Handles budget finalization logic and validation.
 * Ensures all required data is complete before marking budget as finalized.
 */
class FinalizationService
{
    public function __construct(
        private ResultService $resultService,
        private PersonnelService $personnelService,
        private ExpenseService $expenseService,
    ) {}

    /**
     * Check if a budget is ready to be finalized
     *
     * Returns array with 'is_ready' boolean and 'errors' array
     */
    public function checkReadyForFinalization(Budget $budget): array
    {
        $errors = [];

        // Check result entries
        $resultErrors = $this->validateResultEntries($budget);
        if (!empty($resultErrors)) {
            $errors['result'] = $resultErrors;
        }

        // Check personnel allocations
        $personnelErrors = $this->validatePersonnelAllocations($budget);
        if (!empty($personnelErrors)) {
            $errors['personnel'] = $personnelErrors;
        }

        // Check expense entries
        $expenseErrors = $this->validateExpenseEntries($budget);
        if (!empty($expenseErrors)) {
            $errors['expenses'] = $expenseErrors;
        }

        return [
            'is_ready' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate result entries are complete
     */
    private function validateResultEntries(Budget $budget): array
    {
        $errors = [];

        $incompleteEntries = $budget->resultEntries()
            ->whereNull('final_value')
            ->with('product')
            ->get();

        foreach ($incompleteEntries as $entry) {
            $errors[] = "Product '{$entry->product->name}' - final budget not selected";
        }

        return $errors;
    }

    /**
     * Validate personnel allocations are complete
     */
    private function validatePersonnelAllocations(Budget $budget): array
    {
        $errors = [];

        // Check if personnel entries exist
        if ($budget->personnelEntries()->count() === 0) {
            $errors[] = "No personnel entries created";
            return $errors;
        }

        // Check for entries with no allocations
        $entriesWithoutAllocations = $budget->personnelEntries()
            ->whereDoesntHave('allocations')
            ->with('employee')
            ->get();

        foreach ($entriesWithoutAllocations as $entry) {
            $errors[] = "Employee '{$entry->employee->name}' - no product/G&A allocations";
        }

        // Check for invalid allocations (not summing to 100%)
        if (!$this->personnelService->validateAllAllocations($budget)) {
            $errors[] = "Some employees have allocations that do not sum to 100%";
        }

        return $errors;
    }

    /**
     * Validate expense entries are complete
     */
    private function validateExpenseEntries(Budget $budget): array
    {
        $errors = [];

        $incompleteEntries = $budget->expenseEntries()
            ->whereNull('proposed_total')
            ->with('category')
            ->get();

        foreach ($incompleteEntries as $entry) {
            $errors[] = "{$entry->getTypeLabel()} '{$entry->getCategoryDisplayName()}' - proposed total not set";
        }

        return $errors;
    }

    /**
     * Finalize the budget
     *
     * @param Budget $budget
     * @param int $userId User ID who is finalizing
     * @throws \InvalidArgumentException If budget is not ready for finalization
     */
    public function finalizeBudget(Budget $budget, int $userId): void
    {
        // Check if ready for finalization
        $readiness = $this->checkReadyForFinalization($budget);

        if (!$readiness['is_ready']) {
            $errorMessages = [];
            foreach ($readiness['errors'] as $section => $errors) {
                foreach ($errors as $error) {
                    $errorMessages[] = $error;
                }
            }
            throw new \InvalidArgumentException(
                "Budget cannot be finalized. Issues: " . implode("; ", $errorMessages)
            );
        }

        // Update yearly targets on products
        $this->updateProductYearlyTargets($budget);

        // Finalize the budget
        $budget->finalize($userId);
    }

    /**
     * Update yearly targets on products based on finalized budget
     */
    private function updateProductYearlyTargets(Budget $budget): void
    {
        $resultEntries = $budget->resultEntries()
            ->with('product')
            ->get();

        foreach ($resultEntries as $entry) {
            if ($entry->final_value) {
                $entry->product->update(['yearly_target' => $entry->final_value]);
            }
        }
    }

    /**
     * Get finalization summary/checklist
     */
    public function getFinalizationChecklist(Budget $budget): array
    {
        $resultSummary = $this->resultService->getResultSummary($budget);
        $personalSummary = $this->personnelService->getSalaryChangesSummary($budget);
        $expenseSummary = $this->expenseService->getExpensesSummary($budget);

        return [
            'year' => $budget->year,
            'status' => $budget->status,
            'result_completion' => [
                'completed' => $resultSummary['completed_products'],
                'pending' => $resultSummary['pending_products'],
                'percentage' => $resultSummary['completion_percentage'],
            ],
            'personnel' => [
                'total_employees' => $personalSummary['employee_count'],
                'new_hires' => $personalSummary['new_hires_count'],
                'total_current_salaries' => $personalSummary['total_current_salaries'],
                'total_proposed_salaries' => $personalSummary['total_proposed_salaries'],
                'total_increase_amount' => $personalSummary['total_increase_amount'],
                'total_increase_percentage' => $personalSummary['total_increase_percentage'],
            ],
            'expenses' => [
                'opex' => $expenseSummary['opex_total'],
                'taxes' => $expenseSummary['tax_total'],
                'capex' => $expenseSummary['capex_total'],
                'total_expenses' => $expenseSummary['grand_total'],
            ],
            'budget_summary' => [
                'total_growth_budget' => $resultSummary['total_growth_budget'],
                'total_capacity_budget' => $resultSummary['total_capacity_budget'],
                'total_collection_budget' => $resultSummary['total_collection_budget'],
                'total_final_budget' => $resultSummary['total_final_budget'],
            ],
        ];
    }

    /**
     * Get finalization readiness status
     */
    public function getReadinessStatus(Budget $budget): array
    {
        $readiness = $this->checkReadyForFinalization($budget);
        $checklist = $this->getFinalizationChecklist($budget);

        return [
            'is_ready' => $readiness['is_ready'],
            'errors' => $readiness['errors'],
            'checklist' => $checklist,
        ];
    }

    /**
     * Revert a finalized budget back to draft
     */
    public function revertToDraft(Budget $budget): void
    {
        if ($budget->status !== 'finalized') {
            throw new \InvalidArgumentException("Only finalized budgets can be reverted");
        }

        $budget->update([
            'status' => 'draft',
            'finalized_at' => null,
            'finalized_by' => null,
        ]);
    }

    /**
     * Get finalization history (when budget was finalized, by whom)
     */
    public function getFinalizationHistory(Budget $budget): ?array
    {
        if ($budget->finalized_at === null) {
            return null;
        }

        $finalizer = $budget->finalizer;

        return [
            'finalized_at' => $budget->finalized_at,
            'finalized_by' => $finalizer?->name ?? 'Unknown',
            'user_id' => $budget->finalized_by,
        ];
    }

    /**
     * Compare budget against previous year targets
     */
    public function compareWithPreviousYear(Budget $budget): array
    {
        $previousYearBudget = $budget->getPreviousYearBudget();

        if (!$previousYearBudget) {
            return [];
        }

        $currentYear = $budget->resultEntries()
            ->with('product')
            ->get()
            ->keyBy('product_id');

        $previousYear = $previousYearBudget->resultEntries()
            ->with('product')
            ->get()
            ->keyBy('product_id');

        $comparison = [];

        foreach ($currentYear as $productId => $entry) {
            $prevEntry = $previousYear->get($productId);

            $prevValue = $prevEntry?->final_value ?? 0;
            $currValue = $entry->final_value ?? 0;
            $difference = $currValue - $prevValue;
            $percentageDifference = $prevValue > 0 ? ($difference / $prevValue) * 100 : 0;

            $comparison[] = [
                'product' => $entry->product->name,
                'previous_year' => $prevValue,
                'current_year' => $currValue,
                'difference_amount' => $difference,
                'difference_percentage' => $percentageDifference,
            ];
        }

        return $comparison;
    }
}
