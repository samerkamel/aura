<?php

namespace Modules\Accounting\Services\Budget;

use Modules\Accounting\Models\Budget;
use Modules\Accounting\Models\BudgetCollectionEntry;
use Modules\Accounting\Models\BudgetCollectionPattern;
use App\Models\Product;

/**
 * CollectionService
 *
 * Handles collection-based budget calculations using payment pattern analysis.
 * Analyzes payment balance and collection months to project income.
 */
class CollectionService
{
    /**
     * Create a collection entry for a product in a budget
     */
    public function createCollectionEntry(Budget $budget, Product $product): BudgetCollectionEntry
    {
        return BudgetCollectionEntry::create([
            'budget_id' => $budget->id,
            'product_id' => $product->id,
            'beginning_balance' => 0,
            'end_balance' => 0,
            'avg_balance' => 0,
            'avg_contract_per_month' => 0,
            'avg_payment_per_month' => 0,
            'last_year_collection_months' => 0,
            'budgeted_collection_months' => 0,
            'projected_collection_months' => 0,
        ]);
    }

    /**
     * Update collection entry with last year balance data
     */
    public function updateLastYearBalanceData(
        BudgetCollectionEntry $entry,
        float $beginningBalance,
        float $endBalance,
        float $avgBalance,
        float $avgContractPerMonth,
        float $avgPaymentPerMonth
    ): void {
        $lastYearCollectionMonths = $entry->calculateLastYearCollectionMonths();

        $entry->update([
            'beginning_balance' => $beginningBalance,
            'end_balance' => $endBalance,
            'avg_balance' => $avgBalance,
            'avg_contract_per_month' => $avgContractPerMonth,
            'avg_payment_per_month' => $avgPaymentPerMonth,
            'last_year_collection_months' => $lastYearCollectionMonths,
        ]);
    }

    /**
     * Add a payment pattern to a collection entry
     */
    public function addPattern(
        BudgetCollectionEntry $entry,
        string $patternName,
        float $contractPercentage,
        array $monthlyPercentages
    ): BudgetCollectionPattern {
        $pattern = BudgetCollectionPattern::create([
            'budget_collection_entry_id' => $entry->id,
            'pattern_name' => $patternName,
            'contract_percentage' => $contractPercentage,
        ]);

        $pattern->setMonthlyPercentages($monthlyPercentages);
        $pattern->save();

        // Recalculate budgeted collection months
        $this->recalculateCollectionMonths($entry);

        return $pattern;
    }

    /**
     * Update a payment pattern
     */
    public function updatePattern(
        BudgetCollectionPattern $pattern,
        string $patternName,
        float $contractPercentage,
        array $monthlyPercentages
    ): void {
        $pattern->update([
            'pattern_name' => $patternName,
            'contract_percentage' => $contractPercentage,
        ]);

        $pattern->setMonthlyPercentages($monthlyPercentages);
        $pattern->save();

        // Recalculate budgeted collection months
        $this->recalculateCollectionMonths($pattern->collectionEntry);
    }

    /**
     * Delete a payment pattern
     */
    public function deletePattern(BudgetCollectionPattern $pattern): void
    {
        $entry = $pattern->collectionEntry;
        $pattern->delete();

        // Recalculate budgeted collection months
        $this->recalculateCollectionMonths($entry);
    }

    /**
     * Recalculate collection months for an entry
     */
    public function recalculateCollectionMonths(BudgetCollectionEntry $entry): void
    {
        $budgetedCollectionMonths = $entry->calculateBudgetedCollectionMonths();
        $projectedCollectionMonths = $entry->calculateProjectedCollectionMonths();

        $entry->update([
            'budgeted_collection_months' => $budgetedCollectionMonths,
            'projected_collection_months' => $projectedCollectionMonths,
        ]);

        // Auto-calculate budgeted income
        $this->calculateAndSaveBudgetedIncome($entry);
    }

    /**
     * Calculate budgeted income using collection method
     *
     * Formula: End Balance ÷ Projected Collection Months × 12
     * This represents annualized monthly collection amount
     */
    public function calculateBudgetedIncome(BudgetCollectionEntry $entry): float
    {
        return $entry->calculateBudgetedIncome();
    }

    /**
     * Calculate and save budgeted income
     */
    public function calculateAndSaveBudgetedIncome(BudgetCollectionEntry $entry): void
    {
        $budgetedIncome = $this->calculateBudgetedIncome($entry);
        $entry->update(['budgeted_income' => $budgetedIncome]);
    }

    /**
     * Get collection entries for a budget with patterns
     */
    public function getBudgetCollectionEntries(Budget $budget)
    {
        return $budget->collectionEntries()
            ->with('product', 'patterns')
            ->get();
    }

    /**
     * Get patterns summary for an entry
     */
    public function getPatternsSummary(BudgetCollectionEntry $entry): array
    {
        return $entry->getPatternsSummary();
    }

    /**
     * Validate all patterns for an entry sum to 100%
     */
    public function validatePatterns(BudgetCollectionEntry $entry): bool
    {
        $totalPercentage = $entry->patterns()->sum('contract_percentage');
        return abs($totalPercentage - 100) < 0.01;
    }

    /**
     * Create default payment pattern (single upfront payment)
     */
    public function createDefaultPattern(BudgetCollectionEntry $entry): BudgetCollectionPattern
    {
        $monthlyPercentages = array_fill(1, 12, 0);
        $monthlyPercentages[1] = 100; // 100% in month 1

        return $this->addPattern(
            $entry,
            'Upfront Payment',
            100,
            $monthlyPercentages
        );
    }

    /**
     * Create quarterly payment pattern
     */
    public function createQuarterlyPattern(BudgetCollectionEntry $entry): BudgetCollectionPattern
    {
        $monthlyPercentages = array_fill(1, 12, 0);
        $monthlyPercentages[1] = 25;  // Q1
        $monthlyPercentages[4] = 25;  // Q2
        $monthlyPercentages[7] = 25;  // Q3
        $monthlyPercentages[10] = 25; // Q4

        return $this->addPattern(
            $entry,
            'Quarterly Payments',
            100,
            $monthlyPercentages
        );
    }

    /**
     * Create monthly payment pattern
     */
    public function createMonthlyPattern(BudgetCollectionEntry $entry): BudgetCollectionPattern
    {
        $monthlyPercentages = array_fill(1, 12, 8.33); // ~8.33% per month

        return $this->addPattern(
            $entry,
            'Monthly Payments',
            100,
            $monthlyPercentages
        );
    }
}
