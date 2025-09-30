<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetHistory;
use App\Models\BusinessUnit;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BudgetController extends Controller
{
    /**
     * Display budgets for a specific business unit
     */
    public function index(Request $request, BusinessUnit $businessUnit)
    {
        $year = $request->get('year', now()->year);

        // Get all active products for this business unit
        $products = Product::where('business_unit_id', $businessUnit->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Auto-create budget records for products that don't have them for this year
        foreach ($products as $product) {
            Budget::firstOrCreate([
                'business_unit_id' => $businessUnit->id,
                'product_id' => $product->id,
                'budget_year' => $year,
            ], [
                'projected_revenue' => 0,
                'notes' => 'Auto-created budget tracking for ' . $product->name,
                'created_by' => Auth::id(),
            ]);
        }

        // Now get all budgets (which will include the auto-created ones)
        $budgets = Budget::with(['product', 'creator', 'updater'])
            ->forBusinessUnit($businessUnit->id)
            ->forYear($year)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $years = Budget::forBusinessUnit($businessUnit->id)
            ->selectRaw('DISTINCT budget_year')
            ->orderBy('budget_year', 'desc')
            ->pluck('budget_year');

        // Add current year if not present
        if (!$years->contains($year)) {
            $years->prepend($year);
        }

        return view('budgets.index', compact('businessUnit', 'budgets', 'products', 'years', 'year'));
    }

    /**
     * Store a newly created budget
     */
    public function store(Request $request, BusinessUnit $businessUnit)
    {
        $validated = $request->validate([
            'product_id' => [
                'required',
                'exists:products,id',
                Rule::unique('budgets')->where(function ($query) use ($request, $businessUnit) {
                    return $query->where('business_unit_id', $businessUnit->id)
                                ->where('product_id', $request->product_id)
                                ->where('budget_year', $request->budget_year);
                }),
            ],
            'budget_year' => 'required|integer|min:2020|max:2050',
            'notes' => 'nullable|string|max:1000',
        ], [
            'product_id.unique' => 'A budget for this product and year already exists.',
        ]);

        try {
            DB::beginTransaction();

            $budget = Budget::create([
                'business_unit_id' => $businessUnit->id,
                'product_id' => $validated['product_id'],
                'budget_year' => $validated['budget_year'],
                'notes' => $validated['notes'],
                'created_by' => Auth::id(),
            ]);

            // Record the creation in history
            BudgetHistory::recordCreation($budget, Auth::user());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Budget created successfully.',
                'budget' => $budget->load(['product', 'creator'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating budget: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified budget
     */
    public function show(Budget $budget)
    {
        $this->authorize('view', $budget);

        return response()->json([
            'success' => true,
            'budget' => $budget->load(['product', 'creator', 'updater'])
        ]);
    }

    /**
     * Update the specified budget
     */
    public function update(Request $request, Budget $budget)
    {
        $this->authorize('update', $budget);

        $validated = $request->validate([
            'budget_allocation' => 'nullable|numeric|min:0|max:999999999.99',
            'projected_revenue' => 'nullable|numeric|min:0|max:999999999.99',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $oldValues = $budget->toArray();

            // Update product budget allocation if provided
            if (isset($validated['budget_allocation'])) {
                $budget->product->update([
                    'budget_allocation' => $validated['budget_allocation']
                ]);
            }

            $budget->update([
                'projected_revenue' => $validated['projected_revenue'] ?? $budget->projected_revenue,
                'notes' => $validated['notes'],
                'updated_by' => Auth::id(),
            ]);

            // Record the update in history
            BudgetHistory::recordUpdate($budget, $oldValues, Auth::user());


            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Budget updated successfully.',
                'budget' => $budget->fresh(['product', 'creator', 'updater'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating budget: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Allocate budget amount
     */
    public function allocate(Request $request, Budget $budget)
    {
        $this->authorize('update', $budget);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
        ]);

        $newProjectedRevenue = $budget->projected_revenue + $validated['amount'];

        try {
            DB::beginTransaction();

            $budget->update([
                'projected_revenue' => $newProjectedRevenue,
                'updated_by' => Auth::id(),
            ]);

            // Record the allocation in history
            BudgetHistory::create([
                'budget_id' => $budget->id,
                'action' => 'projected',
                'amount_changed' => $validated['amount'],
                'new_values' => ['projected_revenue' => $newProjectedRevenue],
                'description' => $validated['description'] ?? "Projected revenue increase: " . number_format($validated['amount'], 2),
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Revenue projection updated successfully.',
                'budget' => $budget->fresh(['product'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating revenue projection: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Record budget spending
     */
    public function spend(Request $request, Budget $budget)
    {
        $this->authorize('update', $budget);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
        ]);

        $newActualRevenue = $budget->actual_revenue + $validated['amount'];

        try {
            DB::beginTransaction();

            $budget->update([
                'actual_revenue' => $newActualRevenue,
                'updated_by' => Auth::id(),
            ]);

            // Record the revenue achievement in history
            BudgetHistory::create([
                'budget_id' => $budget->id,
                'action' => 'earned',
                'amount_changed' => $validated['amount'],
                'new_values' => ['actual_revenue' => $newActualRevenue],
                'description' => $validated['description'] ?? "Revenue earned: " . number_format($validated['amount'], 2),
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Revenue achievement recorded successfully.',
                'budget' => $budget->fresh(['product'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error recording revenue: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show budget history
     */
    public function history(Budget $budget)
    {
        $this->authorize('view', $budget);

        $histories = $budget->histories()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('budgets.history', compact('budget', 'histories'));
    }

    /**
     * Delete the specified budget
     */
    public function destroy(Budget $budget)
    {
        $this->authorize('delete', $budget);

        try {
            DB::beginTransaction();

            // Delete all history records first
            $budget->histories()->delete();

            // Delete the budget
            $budget->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Budget deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting budget: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get budget summary for a business unit
     */
    public function summary(BusinessUnit $businessUnit, Request $request)
    {
        $year = $request->get('year', now()->year);

        $budgets = Budget::forBusinessUnit($businessUnit->id)
            ->forYear($year)
            ->with('product')
            ->get();

        $totalTarget = $budgets->sum(function($budget) {
            return $budget->target_revenue;
        });

        $totalProjected = $budgets->sum('projected_revenue');
        $totalActual = $budgets->sum('actual_revenue');
        $totalPaid = $budgets->sum('paid_income');
        $avgAchievement = $budgets->count() > 0 ? $budgets->avg('achievement_percentage') : 0;

        $summary = (object) [
            'total_budgets' => $budgets->count(),
            'total_target' => $totalTarget,
            'total_projected' => $totalProjected,
            'total_actual' => $totalActual,
            'avg_achievement' => $avgAchievement
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'year' => $year
        ]);
    }

    /**
     * Create a new product for the business unit
     */
    public function createProduct(BusinessUnit $businessUnit, Request $request)
    {
        $this->authorize('manage-products');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('products')->where(function ($query) use ($businessUnit) {
                    return $query->where('business_unit_id', $businessUnit->id);
                }),
            ],
            'description' => 'nullable|string|max:1000',
            'budget_allocation' => 'nullable|numeric|min:0|max:999999999.99',
            'head_of_product' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
        ], [
            'code.unique' => 'A product with this code already exists in this business unit.',
        ]);

        try {
            DB::beginTransaction();

            $product = Product::create([
                'business_unit_id' => $businessUnit->id,
                'name' => $validated['name'],
                'code' => $validated['code'],
                'description' => $validated['description'],
                'budget_allocation' => $validated['budget_allocation'] ?? 0,
                'head_of_product' => $validated['head_of_product'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'is_active' => true,
            ]);

            // Auto-create budget tracking record for the current year
            Budget::create([
                'business_unit_id' => $businessUnit->id,
                'product_id' => $product->id,
                'budget_year' => now()->year,
                'projected_revenue' => 0,
                'notes' => 'Auto-created budget tracking for ' . $product->name,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully and added to budget tracking.',
                'product' => $product->load('businessUnit')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating product: ' . $e->getMessage()
            ], 500);
        }
    }
}