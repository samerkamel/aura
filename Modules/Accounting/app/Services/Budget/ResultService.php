<?php

namespace Modules\Accounting\Services\Budget;

use Modules\Accounting\Models\Budget;
use Modules\Accounting\Models\BudgetResultEntry;
use App\Models\Product;

/**
 * ResultService
 *
 * Consolidates budget values from Growth, Capacity, and Collection methods.
 * Allows user to select final budget from the three methods or custom value.
 */
class ResultService
{
    /**
     * Create a result entry for a product in a budget
     */
    public function createResultEntry(Budget $budget, Product $product): BudgetResultEntry
    {
        return BudgetResultEntry::create([
            'budget_id' => $budget->id,
            'product_id' => $product->id,
            'growth_value' => null,
            'capacity_value' => null,
            'collection_value' => null,
            'average_value' => null,
            'final_value' => null,
        ]);
    }

    /**
     * Update result entry with calculated values from the three methods
     */
    public function updateCalculatedValues(
        BudgetResultEntry $entry,
        ?float $growthValue,
        ?float $capacityValue,
        ?float $collectionValue
    ): void {
        $entry->update([
            'growth_value' => $growthValue,
            'capacity_value' => $capacityValue,
            'collection_value' => $collectionValue,
        ]);

        // Calculate average
        $average = $entry->calculateAverage();
        $entry->update(['average_value' => $average]);
    }

    /**
     * Set the final budgeted value from one of the three methods
     */
    public function setFinalFromMethod(BudgetResultEntry $entry, string $method): void
    {
        $value = null;

        match($method) {
            'growth' => $value = $entry->growth_value,
            'capacity' => $value = $entry->capacity_value,
            'collection' => $value = $entry->collection_value,
            'average' => $value = $entry->average_value,
        };

        if ($value !== null) {
            $entry->update(['final_value' => $value]);
        }
    }

    /**
     * Set a custom final value
     */
    public function setFinalCustomValue(BudgetResultEntry $entry, float $value): void
    {
        $entry->update(['final_value' => $value]);
    }

    /**
     * Get the method that was used for the final value
     */
    public function getFinalMethod(BudgetResultEntry $entry): ?string
    {
        return $entry->isFinalFromMethod();
    }

    /**
     * Get comparison data for the three methods
     */
    public function getComparisonData(BudgetResultEntry $entry): array
    {
        return [
            'growth' => [
                'value' => $entry->growth_value,
                'label' => 'Growth Method',
            ],
            'capacity' => [
                'value' => $entry->capacity_value,
                'label' => 'Capacity Method',
            ],
            'collection' => [
                'value' => $entry->collection_value,
                'label' => 'Collection Method',
            ],
            'average' => [
                'value' => $entry->average_value,
                'label' => 'Average of Three',
            ],
            'final' => [
                'value' => $entry->final_value,
                'label' => 'Final Selection',
            ],
        ];
    }

    /**
     * Get variance analysis between methods
     */
    public function getVarianceAnalysis(BudgetResultEntry $entry): array
    {
        $highest = $entry->getHighestMethod();
        $lowest = $entry->getLowestMethod();

        $analysis = [];

        if ($highest && $lowest && $highest !== $lowest) {
            $highestValue = $entry->{$highest . '_value'};
            $lowestValue = $entry->{$lowest . '_value'};

            $analysis['highest_method'] = $highest;
            $analysis['highest_value'] = $highestValue;
            $analysis['lowest_method'] = $lowest;
            $analysis['lowest_value'] = $lowestValue;
            $analysis['variance_pct'] = $entry->getPercentageDifference($highest, $lowest);
            $analysis['variance_amount'] = $highestValue - $lowestValue;
        }

        return $analysis;
    }

    /**
     * Get all result entries for a budget
     */
    public function getBudgetResultEntries(Budget $budget)
    {
        return $budget->resultEntries()
            ->with('product')
            ->get();
    }

    /**
     * Sync result values from growth, capacity, and collection entries
     * This is called after all three tabs are completed to populate result tab
     */
    public function syncFromSourceTabs(Budget $budget): void
    {
        $resultEntries = $budget->resultEntries()->get();

        foreach ($resultEntries as $resultEntry) {
            $growthEntry = $budget->growthEntries()
                ->where('product_id', $resultEntry->product_id)
                ->first();

            $capacityEntry = $budget->capacityEntries()
                ->where('product_id', $resultEntry->product_id)
                ->first();

            $collectionEntry = $budget->collectionEntries()
                ->where('product_id', $resultEntry->product_id)
                ->first();

            $this->updateCalculatedValues(
                $resultEntry,
                $growthEntry?->budgeted_value,
                $capacityEntry?->budgeted_income,
                $collectionEntry?->budgeted_income
            );
        }
    }

    /**
     * Get total final budget across all products
     */
    public function getTotalFinalBudget(Budget $budget): float
    {
        return $budget->resultEntries()->sum('final_value');
    }

    /**
     * Get summary statistics for result entries
     */
    public function getResultSummary(Budget $budget): array
    {
        $entries = $budget->resultEntries()->get();

        $completedCount = $entries->filter(fn($e) => $e->final_value !== null)->count();
        $totalCount = $entries->count();

        return [
            'total_products' => $totalCount,
            'completed_products' => $completedCount,
            'pending_products' => $totalCount - $completedCount,
            'total_growth_budget' => $entries->sum('growth_value'),
            'total_capacity_budget' => $entries->sum('capacity_value'),
            'total_collection_budget' => $entries->sum('collection_value'),
            'total_final_budget' => $entries->sum('final_value'),
            'completion_percentage' => $totalCount > 0 ? ($completedCount / $totalCount) * 100 : 0,
        ];
    }
}
