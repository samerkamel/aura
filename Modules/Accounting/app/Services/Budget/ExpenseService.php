<?php

namespace Modules\Accounting\Services\Budget;

use Modules\Accounting\Models\Budget;
use Modules\Accounting\Models\BudgetExpenseEntry;
use Modules\Accounting\Models\ExpenseCategory;

/**
 * ExpenseService
 *
 * Manages OpEx, Tax, and CapEx budget entries with support for percentage-based or amount-based overrides.
 */
class ExpenseService
{
    /**
     * Initialize expense entries from all active expense categories
     */
    public function initializeExpenseEntries(Budget $budget): void
    {
        $categories = ExpenseCategory::active()->get();

        foreach ($categories as $category) {
            // Map expense type code to budget type
            // Existing codes: 'CapEx', 'OpEx', 'CoS', 'Admin'
            // Budget types: 'opex', 'tax', 'capex'
            $type = $this->mapExpenseTypeTobudgetType($category->expenseType?->code);

            $this->createExpenseEntry($budget, $category, $type);
        }
    }

    /**
     * Map ExpenseType code to budget expense type
     *
     * Mapping:
     * - 'CapEx' -> 'capex'
     * - 'OpEx', 'CoS', 'Admin' -> 'opex'
     * - Tax categories need to be manually categorized
     */
    private function mapExpenseTypeTobudgetType(?string $expenseTypeCode): string
    {
        return match(strtolower($expenseTypeCode ?? '')) {
            'capex' => 'capex',
            'opex', 'cos', 'admin', '' => 'opex',
            'tax' => 'tax',
            default => 'opex',
        };
    }

    /**
     * Create an expense entry
     */
    public function createExpenseEntry(
        Budget $budget,
        ExpenseCategory $category,
        string $type = 'opex'
    ): BudgetExpenseEntry {
        // Get last year data (would integrate with actual expense history)
        $lastYearTotal = 0; // TODO: Calculate from expense history

        return BudgetExpenseEntry::create([
            'budget_id' => $budget->id,
            'expense_category_id' => $category->id,
            'type' => $type,
            'last_year_total' => $lastYearTotal,
            'last_year_avg_monthly' => $lastYearTotal / 12,
            'increase_percentage' => null,
            'proposed_amount' => null,
            'proposed_total' => null,
            'is_override' => false,
        ]);
    }

    /**
     * Update expense entry with percentage increase
     */
    public function updateWithPercentageIncrease(
        BudgetExpenseEntry $entry,
        float $increasePercentage
    ): void {
        $proposedTotal = $entry->calculateProposedTotal();

        $entry->update([
            'increase_percentage' => $increasePercentage,
            'proposed_amount' => null,
            'proposed_total' => $proposedTotal,
            'is_override' => false,
        ]);
    }

    /**
     * Update expense entry with exact amount override
     */
    public function updateWithAmountOverride(
        BudgetExpenseEntry $entry,
        float $proposedAmount
    ): void {
        $entry->update([
            'proposed_amount' => $proposedAmount,
            'proposed_total' => $proposedAmount,
            'is_override' => true,
        ]);
    }

    /**
     * Clear override and use percentage instead
     */
    public function clearOverride(BudgetExpenseEntry $entry, float $increasePercentage): void
    {
        $this->updateWithPercentageIncrease($entry, $increasePercentage);
    }

    /**
     * Get expense entries for a budget
     */
    public function getBudgetExpenseEntries(Budget $budget)
    {
        return $budget->expenseEntries()
            ->with('category', 'category.expenseType')
            ->get();
    }

    /**
     * Get expense entries by type
     */
    public function getExpensesByType(Budget $budget, string $type)
    {
        return $budget->expenseEntries()
            ->where('type', $type)
            ->with('category')
            ->get();
    }

    /**
     * Get OpEx entries
     */
    public function getOpExEntries(Budget $budget)
    {
        return $this->getExpensesByType($budget, 'opex');
    }

    /**
     * Get Tax entries
     */
    public function getTaxEntries(Budget $budget)
    {
        return $this->getExpensesByType($budget, 'tax');
    }

    /**
     * Get CapEx entries
     */
    public function getCapExEntries(Budget $budget)
    {
        return $this->getExpensesByType($budget, 'capex');
    }

    /**
     * Get total for expense type
     */
    public function getTypeTotal(Budget $budget, string $type): float
    {
        return $budget->expenseEntries()
            ->where('type', $type)
            ->sum('proposed_total');
    }

    /**
     * Get OpEx total
     */
    public function getOpExTotal(Budget $budget): float
    {
        return $this->getTypeTotal($budget, 'opex');
    }

    /**
     * Get Tax total
     */
    public function getTaxTotal(Budget $budget): float
    {
        return $this->getTypeTotal($budget, 'tax');
    }

    /**
     * Get CapEx total
     */
    public function getCapExTotal(Budget $budget): float
    {
        return $this->getTypeTotal($budget, 'capex');
    }

    /**
     * Get expenses summary
     */
    public function getExpensesSummary(Budget $budget): array
    {
        return [
            'opex_total' => $this->getOpExTotal($budget),
            'tax_total' => $this->getTaxTotal($budget),
            'capex_total' => $this->getCapExTotal($budget),
            'grand_total' => $this->getOpExTotal($budget) + $this->getTaxTotal($budget) + $this->getCapExTotal($budget),
            'opex_count' => $budget->expenseEntries()->where('type', 'opex')->count(),
            'tax_count' => $budget->expenseEntries()->where('type', 'tax')->count(),
            'capex_count' => $budget->expenseEntries()->where('type', 'capex')->count(),
        ];
    }

    /**
     * Apply global increase to all OpEx entries
     */
    public function applyGlobalOpExIncrease(Budget $budget, float $increasePercentage): void
    {
        $budget->expenseEntries()
            ->where('type', 'opex')
            ->get()
            ->each(fn($entry) => $entry->applyGlobalIncrease($increasePercentage));
    }

    /**
     * Apply global increase to all Tax entries
     */
    public function applyGlobalTaxIncrease(Budget $budget, float $increasePercentage): void
    {
        $budget->expenseEntries()
            ->where('type', 'tax')
            ->get()
            ->each(fn($entry) => $entry->applyGlobalIncrease($increasePercentage));
    }

    /**
     * Get entries with custom overrides
     */
    public function getOverriddenEntries(Budget $budget)
    {
        return $budget->expenseEntries()
            ->where('is_override', true)
            ->with('category')
            ->get();
    }

    /**
     * Get category display name with hierarchy
     */
    public function getCategoryDisplayName(BudgetExpenseEntry $entry): string
    {
        return $entry->getCategoryDisplayName();
    }

    /**
     * Populate expense entries from last year actual data
     */
    public function populateFromLastYear(Budget $budget): void
    {
        // Implementation would fetch actual last year expense data
        // For now, this is a placeholder for the structure
    }

    /**
     * Calculate average monthly expense
     */
    public function getAverageMonthly(BudgetExpenseEntry $entry): float
    {
        return $entry->calculateProposedMonthly();
    }

    /**
     * Get increase amount in currency
     */
    public function getIncreaseAmount(BudgetExpenseEntry $entry): float
    {
        return $entry->getIncreaseAmount();
    }

    /**
     * Get percentage display with override notation
     */
    public function getIncreasePercentageDisplay(BudgetExpenseEntry $entry): string
    {
        return $entry->getIncreasePercentageDisplay();
    }
}
