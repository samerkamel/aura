<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Accounting\Models\Budget;
use Modules\Accounting\Models\BudgetCapacityHire;
use Modules\Accounting\Services\Budget\BudgetService;
use Modules\Accounting\Services\Budget\GrowthService;
use Modules\Accounting\Services\Budget\CapacityService;
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

        return view('accounting::budget.tabs.growth', [
            'budget' => $budget,
            'growthEntries' => $growthEntries,
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
            $entry = $budget->capacityEntries()->findOrFail($entryData['id']);

            $entry->update([
                'last_year_available_hours' => $entryData['last_year_available_hours'],
                'next_year_headcount' => $entryData['next_year_headcount'],
                'next_year_avg_hourly_price' => $entryData['next_year_avg_hourly_price'],
                'next_year_billable_pct' => $entryData['next_year_billable_pct'],
            ]);
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
