<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Accounting\Models\Budget;
use Modules\Accounting\Models\BudgetCapacityHire;
use Modules\Accounting\Models\BudgetCollectionPattern;
use Modules\Accounting\Models\BudgetPersonnelEntry;
use Modules\Accounting\Services\Budget\BudgetService;
use Modules\Accounting\Services\Budget\GrowthService;
use Modules\Accounting\Services\Budget\CapacityService;
use Modules\Accounting\Services\Budget\CollectionService;
use Modules\Accounting\Services\Budget\ResultService;
use Modules\Accounting\Services\Budget\PersonnelService;
use Modules\Accounting\Services\Budget\ExpenseService;
use Modules\Accounting\Services\Budget\FinalizationService;
use Modules\HR\Models\Employee;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * BudgetController
 *
 * Main controller for budget management with multi-tab interface
 */
class BudgetController extends Controller
{
    public function __construct(
        private BudgetService $budgetService,
        private GrowthService $growthService,
        private CapacityService $capacityService,
        private CollectionService $collectionService,
        private ResultService $resultService,
        private PersonnelService $personnelService,
        private ExpenseService $expenseService,
        private FinalizationService $finalizationService,
    ) {}

    /**
     * Show budget selection page (year selection)
     */
    public function index()
    {
        $budgets = Budget::orderByDesc('year')->paginate(10);

        return view('accounting::budget.index', [
            'budgets' => $budgets,
        ]);
    }

    /**
     * Create a new budget
     */
    public function create()
    {
        $currentYear = now()->year;
        $nextYear = $currentYear + 1;

        return view('accounting::budget.create', [
            'nextYear' => $nextYear,
        ]);
    }

    /**
     * Store a new budget
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2100|unique:budget_plans',
        ]);

        $budget = $this->budgetService->createBudget($validated['year']);
        $this->budgetService->initializeBudget($budget);

        return redirect()->route('accounting.budgets.growth', $budget->id)
            ->with('success', "Budget for {$budget->year} created successfully");
    }

    /**
     * Show Growth Tab
     */
    public function growth(Budget $budget)
    {
        $this->authorizeEdit($budget);

        $growthEntries = $this->growthService->getBudgetGrowthEntries($budget);

        // Prepare chart data for JavaScript
        $chartData = $growthEntries->map(function ($entry) {
            return [
                'id' => $entry->id,
                'name' => $entry->product->name ?? 'Unknown',
                'year_minus_3' => (float) ($entry->year_minus_3 ?? 0),
                'year_minus_2' => (float) ($entry->year_minus_2 ?? 0),
                'year_minus_1' => (float) ($entry->year_minus_1 ?? 0),
                'trendline_type' => $entry->trendline_type ?? 'linear',
                'budgeted_value' => (float) ($entry->budgeted_value ?? 0),
            ];
        })->values();

        return view('accounting::budget.tabs.growth', [
            'budget' => $budget,
            'growthEntries' => $growthEntries,
            'chartData' => $chartData,
        ]);
    }

    /**
     * Update growth entry
     */
    public function updateGrowth(Request $request, Budget $budget)
    {
        $this->authorizeEdit($budget);

        $validated = $request->validate([
            'growth_entries' => 'required|array',
            'growth_entries.*.id' => 'required|exists:budget_growth_entries,id',
            'growth_entries.*.year_minus_3' => 'nullable|numeric|min:0',
            'growth_entries.*.year_minus_2' => 'nullable|numeric|min:0',
            'growth_entries.*.year_minus_1' => 'nullable|numeric|min:0',
            'growth_entries.*.trendline_type' => 'required|in:linear,logarithmic,polynomial',
            'growth_entries.*.polynomial_order' => 'nullable|integer|min:2|max:3',
            'growth_entries.*.budgeted_value' => 'nullable|numeric|min:0',
        ]);

        foreach ($validated['growth_entries'] as $entryData) {
            $entry = $budget->growthEntries()->findOrFail($entryData['id']);

            // Update historical data and trendline settings
            $entry->update([
                'year_minus_3' => $entryData['year_minus_3'],
                'year_minus_2' => $entryData['year_minus_2'],
                'year_minus_1' => $entryData['year_minus_1'],
                'trendline_type' => $entryData['trendline_type'],
                'polynomial_order' => $entryData['polynomial_order'],
                'budgeted_value' => $entryData['budgeted_value'],
            ]);
        }

        return redirect()->back()
            ->with('success', 'Growth budget entries updated successfully');
    }

    /**
     * Calculate trendline projection for an entry
     */
    public function calculateTrendline(Request $request, Budget $budget)
    {
        $validated = $request->validate([
            'growth_entry_id' => 'required|exists:budget_growth_entries,id',
            'trendline_type' => 'nullable|in:linear,logarithmic,polynomial',
            'polynomial_order' => 'nullable|integer|min:2|max:3',
        ]);

        $entry = $budget->growthEntries()->findOrFail($validated['growth_entry_id']);

        // Use form values if provided, otherwise fall back to database values
        $trendlineType = $validated['trendline_type'] ?? $entry->trendline_type;
        $polynomialOrder = $validated['polynomial_order'] ?? $entry->polynomial_order;

        $historicalData = [
            $entry->year_minus_3,
            $entry->year_minus_2,
            $entry->year_minus_1,
        ];

        $projection = $this->growthService->calculateTrendlineProjection(
            $historicalData,
            $trendlineType,
            $polynomialOrder
        );

        return response()->json([
            'projection' => round($projection, 2),
            'message' => "Projected {$trendlineType} trendline value: " . number_format($projection, 2),
        ]);
    }

    /**
     * Auto-fill historical data from actual invoices/contracts
     */
    public function populateHistoricalData(Request $request, Budget $budget)
    {
        $this->authorizeEdit($budget);

        try {
            // Populate historical data from contracts/payments
            $results = $this->growthService->populateHistoricalData($budget);

            // Return JSON for AJAX request, redirect for regular request
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Historical data populated from contracts',
                    'data' => $results,
                ]);
            }

            return redirect()->back()
                ->with('success', 'Historical data populated from contracts');
        } catch (\Exception $e) {
            \Log::error('Failed to populate historical data: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to populate historical data: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to populate historical data: ' . $e->getMessage());
        }
    }

    /**
     * Show Capacity Tab
     */
    public function capacity(Budget $budget)
    {
        $this->authorizeEdit($budget);

        $capacityEntries = $this->capacityService->getBudgetCapacityEntries($budget);

        return view('accounting::budget.tabs.capacity', [
            'budget' => $budget,
            'capacityEntries' => $capacityEntries,
        ]);
    }

    /**
     * Update capacity entries
     */
    public function updateCapacity(Request $request, Budget $budget)
    {
        $this->authorizeEdit($budget);

        $validated = $request->validate([
            'capacity_entries' => 'required|array',
            'capacity_entries.*.id' => 'required|exists:budget_capacity_entries,id',
            'capacity_entries.*.last_year_available_hours' => 'nullable|numeric|min:0',
            'capacity_entries.*.next_year_headcount' => 'nullable|numeric|min:0',
            'capacity_entries.*.next_year_avg_hourly_price' => 'nullable|numeric|min:0',
            'capacity_entries.*.next_year_billable_pct' => 'nullable|numeric|min:0|max:100',
        ]);

        foreach ($validated['capacity_entries'] as $entryData) {
            $entry = $budget->capacityEntries()->with('hires')->findOrFail($entryData['id']);

            $entry->update([
                'last_year_available_hours' => $entryData['last_year_available_hours'],
                'next_year_headcount' => $entryData['next_year_headcount'],
                'next_year_avg_hourly_price' => $entryData['next_year_avg_hourly_price'],
                'next_year_billable_pct' => $entryData['next_year_billable_pct'],
            ]);

            // Calculate and save budgeted income (reload to get fresh data)
            $entry->refresh();
            $entry->load('hires');
            $this->capacityService->calculateAndSaveBudgetedIncome($entry);
        }

        return redirect()->back()
            ->with('success', 'Capacity budget entries updated successfully');
    }

    /**
     * Add hire to capacity entry
     */
    public function addHire(Request $request, Budget $budget)
    {
        $this->authorizeEdit($budget);

        $validated = $request->validate([
            'capacity_entry_id' => 'required|exists:budget_capacity_entries,id',
            'hire_month' => 'required|integer|min:1|max:12',
            'hire_count' => 'required|numeric|min:0.1',
        ]);

        $entry = $budget->capacityEntries()->findOrFail($validated['capacity_entry_id']);

        $hire = BudgetCapacityHire::create([
            'budget_capacity_entry_id' => $entry->id,
            'hire_month' => $validated['hire_month'],
            'hire_count' => $validated['hire_count'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'hire' => $hire,
                'message' => 'Hire record added successfully',
            ]);
        }

        return redirect()->back()
            ->with('success', 'Hire record added successfully');
    }

    /**
     * Delete hire from capacity entry
     */
    public function deleteHire(Request $request, Budget $budget, BudgetCapacityHire $hire)
    {
        $this->authorizeEdit($budget);

        // Verify the hire belongs to this budget's capacity entry
        $capacityEntry = $hire->capacityEntry;
        if ($capacityEntry->budget_id !== $budget->id) {
            abort(403, 'Unauthorized');
        }

        $hire->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Hire record deleted successfully',
            ]);
        }

        return redirect()->back()
            ->with('success', 'Hire record deleted successfully');
    }

    /**
     * Calculate budgeted income for capacity entry
     */
    public function calculateCapacityIncome(Request $request, Budget $budget)
    {
        $validated = $request->validate([
            'capacity_entry_id' => 'required|exists:budget_capacity_entries,id',
        ]);

        $entry = $budget->capacityEntries()->findOrFail($validated['capacity_entry_id']);

        $budgetedIncome = $this->capacityService->calculateBudgetedIncome($entry);
        $weightedHeadcount = $this->capacityService->calculateWeightedHeadcount($entry);

        return response()->json([
            'weighted_headcount' => round($weightedHeadcount, 2),
            'budgeted_income' => round($budgetedIncome, 2),
            'message' => 'Capacity calculation completed',
        ]);
    }

    /**
     * Populate capacity entries from employee data.
     * Maps employee teams to products and populates headcount and average hourly rates.
     */
    public function populateCapacityFromEmployees(Request $request, Budget $budget)
    {
        $this->authorizeEdit($budget);

        try {
            $results = $this->capacityService->populateFromEmployees($budget);

            $updatedCount = collect($results)->filter(fn($r) => $r['updated'])->count();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Populated {$updatedCount} capacity entries from employee data",
                    'results' => $results,
                ]);
            }

            return redirect()->back()
                ->with('success', "Populated {$updatedCount} capacity entries from employee data");

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to populate from employees: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to populate from employees: ' . $e->getMessage());
        }
    }

    /**
     * Calculate and populate available hours for all capacity entries.
     * Based on working days (excluding weekends and public holidays) × hours per day.
     */
    public function calculateAvailableHours(Request $request, Budget $budget)
    {
        $this->authorizeEdit($budget);

        $validated = $request->validate([
            'hours_per_day' => 'nullable|numeric|min:1|max:24',
        ]);

        $hoursPerDay = $validated['hours_per_day'] ?? 5.0;

        try {
            $results = $this->capacityService->populateAvailableHours($budget, $hoursPerDay);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Calculated {$results['calculation']['available_hours']} available hours per employee for {$budget->year}",
                    'results' => $results,
                ]);
            }

            return redirect()->back()
                ->with('success', "Calculated {$results['calculation']['available_hours']} available hours ({$results['calculation']['working_days']} working days × {$hoursPerDay} hrs/day) for all entries");

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to calculate available hours: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to calculate available hours: ' . $e->getMessage());
        }
    }

    /**
     * Delete a budget
     */
    public function destroy(Budget $budget)
    {
        $this->authorizeEdit($budget);

        $year = $budget->year;
        $budget->delete();

        return redirect()->route('accounting.budgets.index')
            ->with('success', "Budget for {$year} has been deleted successfully");
    }

    // ==================== Collection Tab Methods ====================

    /**
     * Show Collection Tab
     */
    public function collection(Budget $budget)
    {
        $this->authorizeEdit($budget);

        $collectionEntries = $this->collectionService->getBudgetCollectionEntries($budget);

        // Prepare chart data for JavaScript
        $chartData = $collectionEntries->map(function ($entry) {
            return [
                'id' => $entry->id,
                'name' => $entry->product->name ?? 'Unknown',
                'beginning_balance' => (float) ($entry->beginning_balance ?? 0),
                'end_balance' => (float) ($entry->end_balance ?? 0),
                'avg_balance' => (float) ($entry->avg_balance ?? 0),
                'last_year_collection_months' => (float) ($entry->last_year_collection_months ?? 0),
                'budgeted_collection_months' => (float) ($entry->budgeted_collection_months ?? 0),
                'projected_collection_months' => (float) ($entry->projected_collection_months ?? 0),
                'budgeted_income' => (float) ($entry->budgeted_income ?? 0),
                'patterns' => $entry->patterns->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->pattern_name,
                    'contract_percentage' => (float) $p->contract_percentage,
                    'collection_months' => $p->calculateCollectionMonths(),
                ]),
            ];
        })->values();

        return view('accounting::budget.tabs.collection', [
            'budget' => $budget,
            'collectionEntries' => $collectionEntries,
            'chartData' => $chartData,
        ]);
    }

    /**
     * Update collection entries
     */
    public function updateCollection(Request $request, Budget $budget)
    {
        $this->authorizeEdit($budget);

        $validated = $request->validate([
            'collection_entries' => 'required|array',
            'collection_entries.*.id' => 'required|exists:budget_collection_entries,id',
            'collection_entries.*.beginning_balance' => 'nullable|numeric|min:0',
            'collection_entries.*.end_balance' => 'nullable|numeric|min:0',
            'collection_entries.*.avg_balance' => 'nullable|numeric|min:0',
            'collection_entries.*.avg_contract_per_month' => 'nullable|numeric|min:0',
            'collection_entries.*.avg_payment_per_month' => 'nullable|numeric|min:0',
        ]);

        foreach ($validated['collection_entries'] as $entryData) {
            $entry = $budget->collectionEntries()->findOrFail($entryData['id']);

            // Update balance data
            $this->collectionService->updateLastYearBalanceData(
                $entry,
                $entryData['beginning_balance'] ?? 0,
                $entryData['end_balance'] ?? 0,
                $entryData['avg_balance'] ?? 0,
                $entryData['avg_contract_per_month'] ?? 0,
                $entryData['avg_payment_per_month'] ?? 0
            );
        }

        return redirect()->back()
            ->with('success', 'Collection budget entries updated successfully');
    }

    /**
     * Populate collection data from actual balances
     */
    public function populateCollectionData(Request $request, Budget $budget)
    {
        $this->authorizeEdit($budget);

        try {
            // Get payment balances from contract payments
            $results = $this->populateCollectionFromPayments($budget);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Collection data populated from payment history',
                    'data' => $results,
                ]);
            }

            return redirect()->back()
                ->with('success', 'Collection data populated from payment history');
        } catch (\Exception $e) {
            \Log::error('Failed to populate collection data: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to populate collection data: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to populate collection data: ' . $e->getMessage());
        }
    }

    /**
     * Helper to populate collection data from payment history
     *
     * Note: Beginning balance and end balance only include contracts that existed
     * at those respective dates. Contracts created during or after the budget year
     * are excluded to ensure accurate historical analysis.
     */
    private function populateCollectionFromPayments(Budget $budget): array
    {
        $results = [];
        $budgetYear = $budget->year;
        $lastYear = $budgetYear - 1;

        // Define reference dates
        $startOfLastYear = "{$lastYear}-01-01";
        $endOfLastYear = "{$lastYear}-12-31";

        $entries = $budget->collectionEntries()->with('product')->get();

        foreach ($entries as $entry) {
            $productId = $entry->product_id;

            if (!$productId) {
                continue;
            }

            // Get contracts with this product allocated that existed by the end of last year
            // This excludes contracts created during the budget year
            $contracts = \Modules\Accounting\Models\Contract::whereHas('products', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->where(function ($query) use ($endOfLastYear) {
                // Include contracts created on or before end of last year
                // Use start_date if available, otherwise fall back to created_at
                $query->whereDate('start_date', '<=', $endOfLastYear)
                      ->orWhere(function ($q) use ($endOfLastYear) {
                          $q->whereNull('start_date')
                            ->whereDate('created_at', '<=', $endOfLastYear);
                      });
            })
            ->get();

            $beginningBalance = 0;
            $endBalance = 0;
            $totalContracts = 0;
            $totalPayments = 0;

            foreach ($contracts as $contract) {
                // Get contract allocation for this product
                $allocation = $contract->products()->where('product_id', $productId)->first();
                if (!$allocation) continue;

                $allocPct = ($allocation->pivot->allocation_percentage ?? 100) / 100;

                // Calculate balances for last year (prorated by product allocation)
                $contractTotal = $contract->total_amount * $allocPct;

                // Determine contract start date for existence checks
                $contractStartDate = $contract->start_date ?? $contract->created_at;

                // Beginning balance = unpaid at start of last year
                // Only include contracts that existed BEFORE the start of last year
                if ($contractStartDate && $contractStartDate < $startOfLastYear) {
                    $paidBeforeYear = $contract->payments()
                        ->where('status', 'paid')
                        ->whereDate('paid_date', '<', $startOfLastYear)
                        ->sum('amount');
                    $beginningBalance += max(0, $contractTotal - ($paidBeforeYear * $allocPct));
                }

                // End balance = unpaid at end of last year
                // Include all contracts that existed by end of last year (already filtered in query)
                $paidByEndOfYear = $contract->payments()
                    ->where('status', 'paid')
                    ->whereDate('paid_date', '<=', $endOfLastYear)
                    ->sum('amount');
                $endBalance += max(0, $contractTotal - ($paidByEndOfYear * $allocPct));

                // Contracts created during last year (for average calculation)
                if ($contractStartDate &&
                    $contractStartDate >= $startOfLastYear &&
                    $contractStartDate <= $endOfLastYear) {
                    $totalContracts += $contractTotal;
                }

                $totalPayments += $contract->payments()
                    ->where('status', 'paid')
                    ->whereYear('paid_date', $lastYear)
                    ->sum('amount') * $allocPct;
            }

            // Calculate averages
            $avgBalance = ($beginningBalance + $endBalance) / 2;
            $avgContractPerMonth = $totalContracts / 12;
            $avgPaymentPerMonth = $totalPayments / 12;

            // Update entry
            $this->collectionService->updateLastYearBalanceData(
                $entry,
                $beginningBalance,
                $endBalance,
                $avgBalance,
                $avgContractPerMonth,
                $avgPaymentPerMonth
            );

            $results[$entry->id] = [
                'product_id' => $productId,
                'product_name' => $entry->product->name ?? 'Unknown',
                'beginning_balance' => $beginningBalance,
                'end_balance' => $endBalance,
                'avg_balance' => $avgBalance,
            ];
        }

        return $results;
    }

    /**
     * Add a payment pattern
     */
    public function addPattern(Request $request, Budget $budget)
    {
        $this->authorizeEdit($budget);

        $validated = $request->validate([
            'collection_entry_id' => 'required|exists:budget_collection_entries,id',
            'pattern_name' => 'required|string|max:100',
            'contract_percentage' => 'required|numeric|min:0|max:100',
            'monthly_percentages' => 'required|array|size:12',
            'monthly_percentages.*' => 'numeric|min:0|max:100',
        ]);

        $entry = $budget->collectionEntries()->findOrFail($validated['collection_entry_id']);

        // Convert monthly percentages array to keyed array
        $monthlyPcts = [];
        foreach ($validated['monthly_percentages'] as $index => $pct) {
            $monthlyPcts[$index + 1] = (float) $pct;
        }

        $pattern = $this->collectionService->addPattern(
            $entry,
            $validated['pattern_name'],
            $validated['contract_percentage'],
            $monthlyPcts
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'pattern' => $pattern,
                'message' => 'Payment pattern added successfully',
            ]);
        }

        return redirect()->back()
            ->with('success', 'Payment pattern added successfully');
    }

    /**
     * Update a payment pattern
     */
    public function updatePattern(Request $request, Budget $budget, BudgetCollectionPattern $pattern)
    {
        $this->authorizeEdit($budget);

        // Verify pattern belongs to this budget
        $entry = $pattern->collectionEntry;
        if ($entry->budget_id !== $budget->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'pattern_name' => 'required|string|max:100',
            'contract_percentage' => 'required|numeric|min:0|max:100',
            'monthly_percentages' => 'required|array|size:12',
            'monthly_percentages.*' => 'numeric|min:0|max:100',
        ]);

        // Convert monthly percentages array to keyed array
        $monthlyPcts = [];
        foreach ($validated['monthly_percentages'] as $index => $pct) {
            $monthlyPcts[$index + 1] = (float) $pct;
        }

        $this->collectionService->updatePattern(
            $pattern,
            $validated['pattern_name'],
            $validated['contract_percentage'],
            $monthlyPcts
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment pattern updated successfully',
            ]);
        }

        return redirect()->back()
            ->with('success', 'Payment pattern updated successfully');
    }

    /**
     * Delete a payment pattern
     */
    public function deletePattern(Request $request, Budget $budget, BudgetCollectionPattern $pattern)
    {
        $this->authorizeEdit($budget);

        // Verify pattern belongs to this budget
        $entry = $pattern->collectionEntry;
        if ($entry->budget_id !== $budget->id) {
            abort(403, 'Unauthorized');
        }

        $this->collectionService->deletePattern($pattern);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment pattern deleted successfully',
            ]);
        }

        return redirect()->back()
            ->with('success', 'Payment pattern deleted successfully');
    }

    /**
     * Calculate collection income for an entry
     */
    public function calculateCollectionIncome(Request $request, Budget $budget)
    {
        $validated = $request->validate([
            'collection_entry_id' => 'required|exists:budget_collection_entries,id',
        ]);

        $entry = $budget->collectionEntries()->findOrFail($validated['collection_entry_id']);

        $budgetedIncome = $this->collectionService->calculateBudgetedIncome($entry);
        $this->collectionService->calculateAndSaveBudgetedIncome($entry);

        return response()->json([
            'budgeted_income' => round($budgetedIncome, 2),
            'projected_collection_months' => round($entry->projected_collection_months, 2),
            'message' => 'Collection income calculated',
        ]);
    }

    // ==================== Result Tab Methods ====================

    /**
     * Show Result Tab
     */
    public function result(Budget $budget)
    {
        $this->authorizeEdit($budget);

        // Sync result entries from source tabs (growth, capacity, collection)
        $this->resultService->syncFromSourceTabs($budget);

        $resultEntries = $this->resultService->getBudgetResultEntries($budget);

        // Also get source data for display
        $growthEntries = $this->growthService->getBudgetGrowthEntries($budget);
        $capacityEntries = $this->capacityService->getBudgetCapacityEntries($budget);
        $collectionEntries = $this->collectionService->getBudgetCollectionEntries($budget);

        // Prepare comparison data for JavaScript (includes all 4 methods)
        $comparisonData = $resultEntries->map(function ($entry) {
            return [
                'id' => $entry->id,
                'name' => $entry->product->name ?? 'Unknown',
                'growth_value' => (float) ($entry->growth_value ?? 0),
                'capacity_value' => (float) ($entry->capacity_value ?? 0),
                'collection_value' => (float) ($entry->collection_value ?? 0),
                'average_value' => (float) ($entry->average_value ?? 0),
                'final_value' => (float) ($entry->final_value ?? 0),
            ];
        })->values();

        return view('accounting::budget.tabs.result', [
            'budget' => $budget,
            'resultEntries' => $resultEntries,
            'growthEntries' => $growthEntries,
            'capacityEntries' => $capacityEntries,
            'collectionEntries' => $collectionEntries,
            'comparisonData' => $comparisonData,
        ]);
    }

    /**
     * Update result entries (select method for each product)
     */
    public function updateResult(Request $request, Budget $budget)
    {
        $this->authorizeEdit($budget);

        $validated = $request->validate([
            'result_entries' => 'required|array',
            'result_entries.*.id' => 'required|exists:budget_result_entries,id',
            'result_entries.*.selected_method' => 'required|in:growth,capacity,collection,average,manual',
            'result_entries.*.manual_override' => 'nullable|numeric|min:0',
        ]);

        foreach ($validated['result_entries'] as $entryData) {
            $entry = $budget->resultEntries()->findOrFail($entryData['id']);

            $this->resultService->selectMethod(
                $entry,
                $entryData['selected_method'],
                $entryData['manual_override'] ?? null
            );
        }

        return redirect()->back()
            ->with('success', 'Result budget entries updated successfully');
    }

    // ==================== Personnel Tab Methods ====================

    /**
     * Show Personnel Tab
     */
    public function personnel(Budget $budget)
    {
        $this->authorizeEdit($budget);

        $personnelEntries = $this->personnelService->getBudgetPersonnelEntries($budget);
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $summary = $this->personnelService->getSalaryChangesSummary($budget);

        // Prepare chart data for JavaScript
        $chartData = [
            'byDepartment' => $this->getPersonnelByDepartment($personnelEntries),
            'allocations' => $this->getAllocationsSummary($personnelEntries, $products),
        ];

        return view('accounting::budget.tabs.personnel', [
            'budget' => $budget,
            'personnelEntries' => $personnelEntries,
            'products' => $products,
            'summary' => $summary,
            'chartData' => $chartData,
        ]);
    }

    /**
     * Initialize personnel entries from active employees
     */
    public function initializePersonnel(Request $request, Budget $budget)
    {
        $this->authorizeEdit($budget);

        try {
            // Check if already initialized
            if ($budget->personnelEntries()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Personnel entries already initialized. Clear existing entries first.',
                ], 400);
            }

            $this->personnelService->initializePersonnelEntries($budget);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Personnel entries initialized from active employees',
                    'count' => $budget->personnelEntries()->count(),
                ]);
            }

            return redirect()->back()
                ->with('success', 'Personnel entries initialized from active employees');
        } catch (\Exception $e) {
            \Log::error('Failed to initialize personnel entries: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to initialize personnel entries: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to initialize personnel entries: ' . $e->getMessage());
        }
    }

    /**
     * Update personnel entries (salaries)
     */
    public function updatePersonnel(Request $request, Budget $budget)
    {
        $this->authorizeEdit($budget);

        $validated = $request->validate([
            'personnel_entries' => 'required|array',
            'personnel_entries.*.id' => 'required|exists:budget_personnel_entries,id',
            'personnel_entries.*.proposed_salary' => 'nullable|numeric|min:0',
            'personnel_entries.*.is_new_hire' => 'nullable|boolean',
            'personnel_entries.*.hire_month' => 'nullable|integer|min:1|max:12',
        ]);

        foreach ($validated['personnel_entries'] as $entryData) {
            $entry = $budget->personnelEntries()->findOrFail($entryData['id']);

            $entry->update([
                'proposed_salary' => $entryData['proposed_salary'] ?? $entry->current_salary,
                'increase_percentage' => $entry->calculateIncreasePercentage(),
                'is_new_hire' => $entryData['is_new_hire'] ?? false,
                'hire_month' => $entryData['hire_month'] ?? null,
            ]);
        }

        return redirect()->back()
            ->with('success', 'Personnel budget entries updated successfully');
    }

    /**
     * Update allocations for a personnel entry
     */
    public function updateAllocations(Request $request, Budget $budget, BudgetPersonnelEntry $entry)
    {
        $this->authorizeEdit($budget);

        // Verify entry belongs to this budget
        if ($entry->budget_id !== $budget->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'allocations' => 'required|array',
            'allocations.*.product_id' => 'nullable|exists:products,id',
            'allocations.*.is_ga' => 'nullable|boolean',
            'allocations.*.percentage' => 'required|numeric|min:0|max:100',
        ]);

        // Build allocations array for service
        $allocations = [];
        foreach ($validated['allocations'] as $alloc) {
            if (($alloc['is_ga'] ?? false) === true) {
                $allocations['ga'] = (float) $alloc['percentage'];
            } elseif (!empty($alloc['product_id'])) {
                $allocations[(int) $alloc['product_id']] = (float) $alloc['percentage'];
            }
        }

        try {
            $this->personnelService->setAllocations($entry, $allocations);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Allocations updated successfully',
                ]);
            }

            return redirect()->back()
                ->with('success', 'Allocations updated successfully');
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Add a new hire entry
     */
    public function addNewHire(Request $request, Budget $budget)
    {
        $this->authorizeEdit($budget);

        $validated = $request->validate([
            'position_name' => 'required|string|max:255',
            'proposed_salary' => 'required|numeric|min:0',
            'hire_month' => 'required|integer|min:1|max:12',
            'team' => 'nullable|string|max:100',
        ]);

        // Create a placeholder employee-less entry for new hires
        $entry = BudgetPersonnelEntry::create([
            'budget_id' => $budget->id,
            'employee_id' => null, // New hire without employee record
            'current_salary' => 0,
            'proposed_salary' => $validated['proposed_salary'],
            'increase_percentage' => 0,
            'is_new_hire' => true,
            'hire_month' => $validated['hire_month'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'entry' => $entry,
                'message' => 'New hire entry added successfully',
            ]);
        }

        return redirect()->back()
            ->with('success', 'New hire entry added successfully');
    }

    /**
     * Delete a personnel entry
     */
    public function deletePersonnelEntry(Request $request, Budget $budget, BudgetPersonnelEntry $entry)
    {
        $this->authorizeEdit($budget);

        // Verify entry belongs to this budget
        if ($entry->budget_id !== $budget->id) {
            abort(403, 'Unauthorized');
        }

        // Only allow deleting new hire entries (without employee_id)
        if ($entry->employee_id !== null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete existing employee entries',
                ], 400);
            }

            return redirect()->back()
                ->with('error', 'Cannot delete existing employee entries');
        }

        $entry->allocations()->delete();
        $entry->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Personnel entry deleted successfully',
            ]);
        }

        return redirect()->back()
            ->with('success', 'Personnel entry deleted successfully');
    }

    /**
     * Helper to get personnel grouped by department/team
     */
    private function getPersonnelByDepartment($entries): array
    {
        $byDept = [];
        foreach ($entries as $entry) {
            $team = $entry->employee?->team ?? 'New Hires';
            if (!isset($byDept[$team])) {
                $byDept[$team] = [
                    'count' => 0,
                    'current_total' => 0,
                    'proposed_total' => 0,
                ];
            }
            $byDept[$team]['count']++;
            $byDept[$team]['current_total'] += (float) $entry->current_salary;
            $byDept[$team]['proposed_total'] += (float) $entry->getEffectiveSalary();
        }
        return $byDept;
    }

    /**
     * Helper to get allocations summary by product
     */
    private function getAllocationsSummary($entries, $products): array
    {
        $allocations = [];

        // Initialize with all products
        foreach ($products as $product) {
            $allocations[$product->id] = [
                'name' => $product->name,
                'total' => 0,
            ];
        }
        $allocations['ga'] = [
            'name' => 'G&A',
            'total' => 0,
        ];

        // Sum allocations
        foreach ($entries as $entry) {
            $salary = $entry->getEffectiveSalary();
            foreach ($entry->allocations as $alloc) {
                $cost = $salary * ($alloc->allocation_percentage / 100);
                if ($alloc->product_id === null) {
                    $allocations['ga']['total'] += $cost;
                } elseif (isset($allocations[$alloc->product_id])) {
                    $allocations[$alloc->product_id]['total'] += $cost;
                }
            }
        }

        return $allocations;
    }

    // ==================== Expenses Tab Methods ====================

    /**
     * Show Expenses Tab (OpEx, Tax, CapEx combined)
     */
    public function expenses(Budget $budget)
    {
        $this->authorizeEdit($budget);

        $expenseEntries = $this->expenseService->getBudgetExpenseEntries($budget);
        $summary = $this->expenseService->getExpensesSummary($budget);

        // Group entries by type
        $opexEntries = $expenseEntries->where('type', 'opex');
        $taxEntries = $expenseEntries->where('type', 'tax');
        $capexEntries = $expenseEntries->where('type', 'capex');

        // Prepare chart data
        $chartData = [
            'byType' => [
                'OpEx' => $summary['opex_total'],
                'Tax' => $summary['tax_total'],
                'CapEx' => $summary['capex_total'],
            ],
            'comparison' => [
                'last_year' => $expenseEntries->sum('last_year_total'),
                'proposed' => $summary['grand_total'],
            ],
        ];

        return view('accounting::budget.tabs.expenses', [
            'budget' => $budget,
            'expenseEntries' => $expenseEntries,
            'opexEntries' => $opexEntries,
            'taxEntries' => $taxEntries,
            'capexEntries' => $capexEntries,
            'summary' => $summary,
            'chartData' => $chartData,
        ]);
    }

    /**
     * Initialize expense entries from expense categories
     */
    public function initializeExpenses(Request $request, Budget $budget)
    {
        $this->authorizeEdit($budget);

        try {
            if ($budget->expenseEntries()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Expense entries already initialized.',
                ], 400);
            }

            $this->expenseService->initializeExpenseEntries($budget);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Expense entries initialized from categories',
                    'count' => $budget->expenseEntries()->count(),
                ]);
            }

            return redirect()->back()
                ->with('success', 'Expense entries initialized from categories');
        } catch (\Exception $e) {
            \Log::error('Failed to initialize expense entries: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to initialize expense entries: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to initialize expense entries: ' . $e->getMessage());
        }
    }

    /**
     * Update expense entries
     */
    public function updateExpenses(Request $request, Budget $budget)
    {
        $this->authorizeEdit($budget);

        $validated = $request->validate([
            'expense_entries' => 'required|array',
            'expense_entries.*.id' => 'required|exists:budget_expense_entries,id',
            'expense_entries.*.increase_percentage' => 'nullable|numeric',
            'expense_entries.*.proposed_amount' => 'nullable|numeric|min:0',
            'expense_entries.*.is_override' => 'nullable|boolean',
        ]);

        foreach ($validated['expense_entries'] as $entryData) {
            $entry = $budget->expenseEntries()->findOrFail($entryData['id']);

            $isOverride = $entryData['is_override'] ?? false;

            if ($isOverride && isset($entryData['proposed_amount'])) {
                $this->expenseService->updateWithAmountOverride($entry, $entryData['proposed_amount']);
            } else {
                $this->expenseService->updateWithPercentageIncrease($entry, $entryData['increase_percentage'] ?? 0);
            }
        }

        return redirect()->back()
            ->with('success', 'Expense budget entries updated successfully');
    }

    /**
     * Apply global increase to all entries of a type
     */
    public function applyGlobalIncrease(Request $request, Budget $budget)
    {
        $this->authorizeEdit($budget);

        $validated = $request->validate([
            'type' => 'required|in:opex,tax,capex,all',
            'increase_percentage' => 'required|numeric',
        ]);

        $type = $validated['type'];
        $pct = $validated['increase_percentage'];

        if ($type === 'opex' || $type === 'all') {
            $this->expenseService->applyGlobalOpExIncrease($budget, $pct);
            $budget->update(['opex_global_increase_pct' => $pct]);
        }

        if ($type === 'tax' || $type === 'all') {
            $this->expenseService->applyGlobalTaxIncrease($budget, $pct);
            $budget->update(['tax_global_increase_pct' => $pct]);
        }

        if ($type === 'capex' || $type === 'all') {
            // CapEx typically doesn't have global increase
            $budget->expenseEntries()
                ->where('type', 'capex')
                ->get()
                ->each(fn($entry) => $entry->applyGlobalIncrease($pct));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Global increase of {$pct}% applied to {$type} expenses",
            ]);
        }

        return redirect()->back()
            ->with('success', "Global increase of {$pct}% applied to {$type} expenses");
    }

    /**
     * Populate expense data from last year actuals
     */
    public function populateExpenseData(Request $request, Budget $budget)
    {
        $this->authorizeEdit($budget);

        try {
            $results = $this->populateExpensesFromActuals($budget);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Expense data populated from last year actuals',
                    'data' => $results,
                ]);
            }

            return redirect()->back()
                ->with('success', 'Expense data populated from last year actuals');
        } catch (\Exception $e) {
            \Log::error('Failed to populate expense data: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to populate expense data: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to populate expense data: ' . $e->getMessage());
        }
    }

    /**
     * Helper to populate expense data from actual expenses
     */
    private function populateExpensesFromActuals(Budget $budget): array
    {
        $results = [];
        $budgetYear = $budget->year;
        $lastYear = $budgetYear - 1;

        $entries = $budget->expenseEntries()->with('category')->get();

        foreach ($entries as $entry) {
            $categoryId = $entry->expense_category_id;

            if (!$categoryId) {
                continue;
            }

            // Get actual expenses from last year for this category
            $lastYearTotal = \Modules\Accounting\Models\ExpenseSchedule::query()
                ->where('expense_category_id', $categoryId)
                ->whereYear('expense_date', $lastYear)
                ->where('status', 'paid')
                ->sum('amount');

            $entry->update([
                'last_year_total' => $lastYearTotal,
                'last_year_avg_monthly' => $lastYearTotal / 12,
            ]);

            $results[$entry->id] = [
                'category_id' => $categoryId,
                'category_name' => $entry->category?->name ?? 'Unknown',
                'last_year_total' => $lastYearTotal,
            ];
        }

        return $results;
    }

    // ==========================================
    // Summary Tab Methods
    // ==========================================

    /**
     * Show the Summary/P&L tab (read-only)
     */
    public function summary(Budget $budget)
    {
        // Get checklist data which has comprehensive summary
        $checklist = $this->finalizationService->getFinalizationChecklist($budget);

        // Get additional data for charts
        $resultEntries = $budget->resultEntries()
            ->with('product')
            ->get();

        $personnelEntries = $budget->personnelEntries()
            ->with(['employee', 'allocations', 'allocations.product'])
            ->get();

        // Revenue by product for chart
        $revenueByProduct = $resultEntries->mapWithKeys(function ($entry) {
            return [$entry->product->name => $entry->final_value ?? 0];
        })->toArray();

        // Personnel cost by product allocation
        $personnelByProduct = [];
        foreach ($personnelEntries as $entry) {
            $effectiveSalary = $entry->getEffectiveSalary();
            foreach ($entry->allocations as $allocation) {
                $productName = $allocation->product?->name ?? 'G&A';
                $allocatedAmount = $effectiveSalary * ($allocation->allocation_percentage / 100);
                $personnelByProduct[$productName] = ($personnelByProduct[$productName] ?? 0) + $allocatedAmount;
            }
        }

        // Calculate P&L
        $totalRevenue = $checklist['budget_summary']['total_final_budget'];
        $personnelCost = $checklist['personnel']['total_proposed_salaries'];
        $opexCost = $checklist['expenses']['opex'];
        $taxCost = $checklist['expenses']['taxes'];
        $capexCost = $checklist['expenses']['capex'];
        $totalExpenses = $personnelCost + $opexCost + $taxCost;
        $grossProfit = $totalRevenue - $personnelCost;
        $operatingProfit = $grossProfit - $opexCost;
        $netProfit = $operatingProfit - $taxCost;

        $pnl = [
            'revenue' => $totalRevenue,
            'personnel_cost' => $personnelCost,
            'gross_profit' => $grossProfit,
            'gross_margin' => $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0,
            'opex' => $opexCost,
            'operating_profit' => $operatingProfit,
            'operating_margin' => $totalRevenue > 0 ? ($operatingProfit / $totalRevenue) * 100 : 0,
            'taxes' => $taxCost,
            'net_profit' => $netProfit,
            'net_margin' => $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0,
            'capex' => $capexCost,
        ];

        // Chart data
        $chartData = [
            'revenueByProduct' => $revenueByProduct,
            'personnelByProduct' => $personnelByProduct,
            'expenseBreakdown' => [
                'Personnel' => $personnelCost,
                'OpEx' => $opexCost,
                'Taxes' => $taxCost,
                'CapEx' => $capexCost,
            ],
            'pnlWaterfall' => [
                'Revenue' => $totalRevenue,
                'Personnel' => -$personnelCost,
                'OpEx' => -$opexCost,
                'Taxes' => -$taxCost,
                'Net Profit' => $netProfit,
            ],
        ];

        // Get readiness status
        $readiness = $this->finalizationService->checkReadyForFinalization($budget);

        return view('accounting::budget.tabs.summary', [
            'budget' => $budget,
            'checklist' => $checklist,
            'pnl' => $pnl,
            'chartData' => $chartData,
            'readiness' => $readiness,
        ]);
    }

    // ==========================================
    // Finalization Methods
    // ==========================================

    /**
     * Show finalization page with checklist
     */
    public function finalization(Budget $budget)
    {
        $readinessStatus = $this->finalizationService->getReadinessStatus($budget);
        $comparison = $this->finalizationService->compareWithPreviousYear($budget);
        $history = $this->finalizationService->getFinalizationHistory($budget);

        return view('accounting::budget.finalization', [
            'budget' => $budget,
            'readiness' => $readinessStatus,
            'comparison' => $comparison,
            'history' => $history,
        ]);
    }

    /**
     * Finalize the budget
     */
    public function finalize(Budget $budget, Request $request)
    {
        $this->authorizeEdit($budget);

        try {
            $this->finalizationService->finalizeBudget($budget, auth()->id());

            return redirect()
                ->route('accounting.budgets.summary', $budget->id)
                ->with('success', 'Budget has been finalized successfully.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Revert a finalized budget to draft
     */
    public function revertToDraft(Budget $budget)
    {
        try {
            $this->finalizationService->revertToDraft($budget);

            return redirect()
                ->route('accounting.budgets.summary', $budget->id)
                ->with('success', 'Budget has been reverted to draft.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Authorize that budget can be edited
     */
    private function authorizeEdit(Budget $budget)
    {
        if (!$this->budgetService->canEdit($budget)) {
            abort(403, 'This budget cannot be edited');
        }
    }
}
