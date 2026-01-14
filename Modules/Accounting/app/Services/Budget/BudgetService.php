<?php

namespace Modules\Accounting\Services\Budget;

use Modules\Accounting\Models\Budget;
use Modules\Accounting\Models\BudgetGrowthEntry;
use Modules\Accounting\Models\BudgetCapacityEntry;
use Modules\Accounting\Models\BudgetCollectionEntry;
use Modules\Accounting\Models\BudgetResultEntry;
use Modules\Accounting\Models\BudgetPersonnelEntry;
use Modules\Accounting\Models\BudgetExpenseEntry;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

/**
 * BudgetService
 *
 * Main orchestrator service for budget management.
 * Coordinates creation, initialization, and calculation of budgets.
 */
class BudgetService
{
    public function __construct(
        private GrowthService $growthService,
        private CapacityService $capacityService,
        private CollectionService $collectionService,
        private ResultService $resultService,
        private PersonnelService $personnelService,
        private ExpenseService $expenseService,
    ) {}

    /**
     * Create a new budget for a given year
     */
    public function createBudget(int $year): Budget
    {
        return Budget::create([
            'year' => $year,
            'status' => 'draft',
            'opex_global_increase_pct' => 10.00,
            'tax_global_increase_pct' => 10.00,
        ]);
    }

    /**
     * Initialize a budget with entries for all products
     */
    public function initializeBudget(Budget $budget): void
    {
        $products = Product::active()->get();

        foreach ($products as $product) {
            // Create Growth entry
            $this->growthService->createGrowthEntry($budget, $product);

            // Create Capacity entry
            $this->capacityService->createCapacityEntry($budget, $product);

            // Create Collection entry
            $this->collectionService->createCollectionEntry($budget, $product);

            // Create Result entry
            $this->resultService->createResultEntry($budget, $product);
        }

        // Initialize personnel entries from employees
        $this->personnelService->initializePersonnelEntries($budget);

        // Initialize expense entries from categories
        $this->expenseService->initializeExpenseEntries($budget);
    }

    /**
     * Get all products for budgeting
     */
    public function getProductsForBudgeting(): Collection
    {
        return Product::active()->get();
    }

    /**
     * Get budget summary data
     */
    public function getBudgetSummary(Budget $budget): array
    {
        return [
            'total_growth_budget' => $budget->resultEntries()->sum('growth_value'),
            'total_capacity_budget' => $budget->resultEntries()->sum('capacity_value'),
            'total_collection_budget' => $budget->resultEntries()->sum('collection_value'),
            'total_final_budget' => $budget->resultEntries()->sum('final_value'),
            'total_personnel_cost' => $budget->personnelEntries()
                ->with('allocations')
                ->get()
                ->sum(fn($entry) => $entry->getEffectiveSalary()),
            'total_opex' => $budget->expenseEntries()->where('type', 'opex')->sum('proposed_total'),
            'total_taxes' => $budget->expenseEntries()->where('type', 'tax')->sum('proposed_total'),
            'total_capex' => $budget->expenseEntries()->where('type', 'capex')->sum('proposed_total'),
        ];
    }

    /**
     * Check if budget can be edited
     */
    public function canEdit(Budget $budget): bool
    {
        return $budget->canEdit();
    }

    /**
     * Get or create budget for year
     */
    public function getOrCreateBudget(int $year): Budget
    {
        $budget = Budget::where('year', $year)->first();

        if (!$budget) {
            $budget = $this->createBudget($year);
            $this->initializeBudget($budget);
        }

        return $budget;
    }

    /**
     * Update global OpEx increase percentage and apply to all entries
     */
    public function updateGlobalOpExIncrease(Budget $budget, float $percentage): void
    {
        $budget->update(['opex_global_increase_pct' => $percentage]);

        $budget->expenseEntries()
            ->where('type', 'opex')
            ->each(fn($entry) => $entry->applyGlobalIncrease($percentage));
    }

    /**
     * Update global Tax increase percentage and apply to all entries
     */
    public function updateGlobalTaxIncrease(Budget $budget, float $percentage): void
    {
        $budget->update(['tax_global_increase_pct' => $percentage]);

        $budget->expenseEntries()
            ->where('type', 'tax')
            ->each(fn($entry) => $entry->applyGlobalIncrease($percentage));
    }
}
