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
        $categories = ExpenseCategory::active()->with('expenseType', 'parent')->get();

        foreach ($categories as $category) {
            // Determine the correct budget type based on expense type and name
            $type = $this->determineBudgetType($category);

            $this->createExpenseEntry($budget, $category, $type);
        }
    }

    /**
     * Map ExpenseType code to budget expense type
     *
     * Mapping:
     * - 'CapEx' -> 'capex'
     * - 'Tax' -> 'tax'
     * - 'OpEx', 'CoS', 'Admin' -> 'opex'
     */
    private function mapExpenseTypeTobudgetType(?string $expenseTypeCode): string
    {
        return match(strtolower($expenseTypeCode ?? '')) {
            'capex' => 'capex',
            'tax' => 'tax',
            'opex', 'cos', 'admin', '' => 'opex',
            default => 'opex',
        };
    }

    /**
     * Determine budget type for a category based on expense type and name
     *
     * Uses expense type code first, then falls back to name-based detection
     * for tax-related categories that might not have a Tax expense type assigned.
     */
    public function determineBudgetType(ExpenseCategory $category): string
    {
        // First check the expense type code
        $expenseTypeCode = $category->expenseType?->code;

        if ($expenseTypeCode) {
            $type = $this->mapExpenseTypeTobudgetType($expenseTypeCode);
            if ($type !== 'opex') {
                return $type;
            }
        }

        // Check category name for tax-related keywords
        $categoryName = strtolower($category->name);
        $parentName = strtolower($category->parent?->name ?? '');

        $taxKeywords = ['vat', 'tax', 'income tax', 'sales tax', 'withholding'];
        foreach ($taxKeywords as $keyword) {
            if (str_contains($categoryName, $keyword) || str_contains($parentName, $keyword)) {
                return 'tax';
            }
        }

        return $this->mapExpenseTypeTobudgetType($expenseTypeCode);
    }

    /**
     * Sync budget entry types based on their category's expense type
     *
     * Updates existing budget entries to use the correct type (opex, tax, capex)
     * based on the category's expense type and name detection.
     */
    public function syncEntryTypes(Budget $budget): array
    {
        $updated = ['opex' => 0, 'tax' => 0, 'capex' => 0];

        $entries = $budget->expenseEntries()
            ->with('category', 'category.expenseType', 'category.parent')
            ->get();

        foreach ($entries as $entry) {
            if (!$entry->category) continue;

            $correctType = $this->determineBudgetType($entry->category);

            if ($entry->type !== $correctType) {
                $entry->update(['type' => $correctType]);
                $updated[$correctType]++;
            }
        }

        return $updated;
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
            ->with('category', 'category.expenseType', 'category.parent')
            ->get();
    }

    /**
     * Get expense entries organized hierarchically by parent category
     *
     * Returns entries grouped by parent category, with orphan categories (no parent)
     * as their own group. Each group has 'parent' and 'entries' keys.
     *
     * @param \Illuminate\Support\Collection $entries Collection of expense entries
     * @return \Illuminate\Support\Collection Collection of hierarchical groups
     */
    public function organizeEntriesHierarchically($entries)
    {
        $hierarchy = collect();
        $processed = collect();

        // First pass: Find all unique parent categories
        $parentCategories = $entries->map(function ($entry) {
            return $entry->category?->parent ?? $entry->category;
        })->unique('id')->sortBy('sort_order');

        // Build hierarchy: group entries under their parent category
        foreach ($parentCategories as $parentCat) {
            if (!$parentCat) continue;

            // Get all entries that belong to this parent (either directly or as subcategories)
            $groupEntries = $entries->filter(function ($entry) use ($parentCat) {
                if (!$entry->category) return false;

                // Entry is directly under this parent
                if ($entry->category->parent_id === $parentCat->id) {
                    return true;
                }

                // Entry's category IS the parent (no subcategory level)
                if ($entry->category->id === $parentCat->id && is_null($entry->category->parent_id)) {
                    return true;
                }

                return false;
            });

            if ($groupEntries->isNotEmpty()) {
                // Sort entries by category sort_order
                $sortedEntries = $groupEntries->sortBy(function ($entry) {
                    return $entry->category?->sort_order ?? 999;
                });

                $hierarchy->push([
                    'parent' => $parentCat,
                    'is_parent_only' => $groupEntries->count() === 1 && $groupEntries->first()->category->id === $parentCat->id,
                    'entries' => $sortedEntries->values(),
                    'subtotal' => $sortedEntries->sum(fn($e) => $e->proposed_total ?? $e->last_year_total),
                ]);

                $processed = $processed->merge($groupEntries->pluck('id'));
            }
        }

        // Handle any orphan entries not yet processed
        $orphans = $entries->whereNotIn('id', $processed->toArray());
        if ($orphans->isNotEmpty()) {
            foreach ($orphans as $orphan) {
                $hierarchy->push([
                    'parent' => $orphan->category,
                    'is_parent_only' => true,
                    'entries' => collect([$orphan]),
                    'subtotal' => $orphan->proposed_total ?? $orphan->last_year_total,
                ]);
            }
        }

        return $hierarchy->sortBy(function ($group) {
            return $group['parent']?->sort_order ?? 999;
        })->values();
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
