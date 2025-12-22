<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Budget;
use App\Models\BusinessUnit;
use App\Helpers\BusinessUnitHelper;

class ProductController extends Controller
{
    /**
     * Apply business unit filtering to a query based on request parameters
     */
    private function applyBusinessUnitFilter($query, Request $request)
    {
        if ($request->has('business_unit_id') && $request->business_unit_id) {
            // Verify user has access to the selected business unit
            $accessibleBusinessUnitIds = BusinessUnitHelper::getAccessibleBusinessUnitIds();
            if (BusinessUnitHelper::isSuperAdmin() || in_array($request->business_unit_id, $accessibleBusinessUnitIds)) {
                return $query->where('business_unit_id', $request->business_unit_id);
            }
        }
        // Apply default business unit filtering
        return BusinessUnitHelper::filterQueryByBusinessUnit($query, $request);
    }
    /**
     * Display a listing of products.
     */
    public function index(Request $request): View
    {
        if (!auth()->user()->can('view-products') && !auth()->user()->can('manage-products')) {
            abort(403, 'Unauthorized to view products.');
        }

        $query = Product::query();

        // Apply business unit filtering
        $query = $this->applyBusinessUnitFilter($query, $request);

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search by name or code
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%')
                  ->orWhere('head_of_product', 'like', '%' . $request->search . '%');
            });
        }

        $products = $query->with(['contracts' => function($q) {
            $q->where('status', 'active');
        }, 'businessUnit'])->orderBy('name')->paginate(15);

        // Calculate YTD budget and contract values for each product
        foreach ($products as $product) {
            // Calculate YTD budget (pro-rated based on how much of the year has passed)
            $currentDate = now();
            $yearStart = $currentDate->copy()->startOfYear();
            $daysInYear = $yearStart->daysInYear;
            $daysPassed = $yearStart->diffInDays($currentDate) + 1;

            $product->ytd_budget = ($product->budget_allocation * $daysPassed) / $daysInYear;

            // Calculate total contract allocations for this product
            $contractValue = $product->contracts->sum(function($contract) {
                $pivot = $contract->pivot;
                if ($pivot->allocation_type === 'amount') {
                    return $pivot->allocation_amount;
                } else {
                    return ($pivot->allocation_percentage / 100) * $contract->total_amount;
                }
            });

            $product->contract_value = $contractValue;

            // Calculate achievement percentage (contracts / YTD budget)
            $product->achievement_percentage = $product->ytd_budget > 0
                ? ($contractValue / $product->ytd_budget) * 100
                : 0;
        }

        // Calculate total budget for percentage calculations (using same filtering as main query)
        $totalBudgetQuery = Product::where('is_active', true);
        $totalBudgetQuery = $this->applyBusinessUnitFilter($totalBudgetQuery, $request);
        $totalActiveBudget = $totalBudgetQuery->sum('budget_allocation');

        // Add budget percentage to each product
        foreach ($products as $product) {
            $product->budget_percentage = $totalActiveBudget > 0 && $product->budget_allocation > 0
                ? ($product->budget_allocation / $totalActiveBudget) * 100
                : 0;
        }

        // Calculate total contract value and YTD budget across all products
        $totalContractValue = 0;
        $totalYtdBudget = 0;
        foreach ($products as $product) {
            $totalContractValue += $product->contract_value;
            $totalYtdBudget += $product->ytd_budget;
        }

        // Calculate statistics (using same filtering as main query)
        $activeProductsQuery = Product::where('is_active', true);
        $activeProductsQuery = $this->applyBusinessUnitFilter($activeProductsQuery, $request);

        $currentBusinessUnit = BusinessUnitHelper::getCurrentBusinessUnit($request);
        $accessibleBusinessUnits = BusinessUnitHelper::getAccessibleBusinessUnits();

        $statistics = [
            'total_contracts' => $totalContractValue,
            'active_products' => $activeProductsQuery->count(),
            'total_ytd_budget' => $totalYtdBudget,
            'total_budget' => $totalActiveBudget,
            'current_business_unit' => $currentBusinessUnit,
            'accessible_business_units' => $accessibleBusinessUnits,
            'can_access_multiple_bus' => BusinessUnitHelper::canAccessMultipleBusinessUnits(),
        ];

        return view('administration.products.index', compact('products', 'statistics', 'currentBusinessUnit', 'accessibleBusinessUnits'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): View
    {
        if (!auth()->user()->can('manage-products')) {
            abort(403, 'Unauthorized to create products.');
        }

        $currentBusinessUnit = BusinessUnitHelper::getCurrentBusinessUnit();
        $accessibleBusinessUnits = BusinessUnitHelper::getAccessibleBusinessUnits();

        return view('administration.products.create', compact('currentBusinessUnit', 'accessibleBusinessUnits'));
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!auth()->user()->can('manage-products')) {
            abort(403, 'Unauthorized to create products.');
        }

        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:10|unique:products',
                'description' => 'nullable|string',
                'head_of_product' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'budget_year' => 'required|integer|min:2020|max:2050',
                'budget_allocation' => 'required|numeric|min:0',
                'business_unit_id' => 'nullable|exists:business_units,id',
            ]);

            // Determine business unit ID
            $businessUnitId = $request->business_unit_id ?? BusinessUnitHelper::getCurrentBusinessUnitId();

            // Verify user has access to the selected business unit
            if (!BusinessUnitHelper::isSuperAdmin() && !in_array($businessUnitId, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
                abort(403, 'Unauthorized to create products in this business unit.');
            }

            DB::beginTransaction();

            // Create the product (budget_allocation stores current year's budget for quick access)
            $product = Product::create([
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'description' => $request->description,
                'head_of_product' => $request->head_of_product,
                'email' => $request->email,
                'phone' => $request->phone,
                'budget_allocation' => $request->budget_allocation,
                'is_active' => $request->has('is_active'),
                'business_unit_id' => $businessUnitId,
            ]);

            // Create the budget record for the specified year
            Budget::create([
                'business_unit_id' => $businessUnitId,
                'product_id' => $product->id,
                'budget_year' => $request->budget_year,
                'projected_revenue' => $request->budget_allocation,
                'notes' => 'Initial budget created with product',
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()
                ->route('administration.products.show', $product)
                ->with('success', 'Product created successfully with ' . $request->budget_year . ' budget.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create product: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): View
    {
        if (!auth()->user()->can('view-products') && !auth()->user()->can('manage-products')) {
            abort(403, 'Unauthorized to view product details.');
        }

        // Verify user has access to this product's business unit
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($product->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to view this product.');
        }

        $product->load(['contracts', 'businessUnit', 'budgets' => function($query) {
            $query->orderBy('budget_year', 'desc');
        }]);

        // Get current year budget
        $currentYearBudget = $product->budgets->where('budget_year', date('Y'))->first();

        return view('administration.products.show', compact('product', 'currentYearBudget'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product): View
    {
        if (!auth()->user()->can('manage-products')) {
            abort(403, 'Unauthorized to edit products.');
        }

        // Verify user has access to this product's business unit
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($product->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to edit this product.');
        }

        $product->load(['budgets' => function($query) {
            $query->orderBy('budget_year', 'desc');
        }]);

        $accessibleBusinessUnits = BusinessUnitHelper::getAccessibleBusinessUnits();

        // Get years that don't have budgets yet
        $existingYears = $product->budgets->pluck('budget_year')->toArray();
        $availableYears = [];
        for ($year = date('Y') + 1; $year >= date('Y') - 5; $year--) {
            if (!in_array($year, $existingYears)) {
                $availableYears[] = $year;
            }
        }

        return view('administration.products.edit', compact('product', 'accessibleBusinessUnits', 'availableYears'));
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        if (!auth()->user()->can('manage-products')) {
            abort(403, 'Unauthorized to edit products.');
        }

        // Verify user has access to this product's business unit
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($product->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to edit this product.');
        }

        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:10|unique:products,code,' . $product->id,
                'description' => 'nullable|string',
                'head_of_product' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'budget_allocation' => 'nullable|numeric|min:0',
                'business_unit_id' => 'nullable|exists:business_units,id',
            ]);

            // If business unit is being changed, verify access
            if ($request->has('business_unit_id') && $request->business_unit_id != $product->business_unit_id) {
                if (!BusinessUnitHelper::isSuperAdmin() &&
                    !in_array($request->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
                    abort(403, 'Unauthorized to move product to this business unit.');
                }
            }

            $updateData = [
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'description' => $request->description,
                'head_of_product' => $request->head_of_product,
                'email' => $request->email,
                'phone' => $request->phone,
                'budget_allocation' => $request->budget_allocation,
                'is_active' => $request->has('is_active'),
            ];

            // Only update business unit if provided and user has access
            if ($request->has('business_unit_id')) {
                $updateData['business_unit_id'] = $request->business_unit_id;
            }

            $product->update($updateData);

            return redirect()
                ->route('administration.products.show', $product)
                ->with('success', 'Product updated successfully.');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update product: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product): RedirectResponse
    {
        if (!auth()->user()->can('manage-products')) {
            abort(403, 'Unauthorized to delete products.');
        }

        // Verify user has access to this product's business unit
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($product->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to delete this product.');
        }

        // Check if product has contracts
        if ($product->contracts()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete product that has assigned contracts.');
        }

        $product->delete();

        return redirect()
            ->route('administration.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    /**
     * Toggle product status.
     */
    public function toggleStatus(Product $product): RedirectResponse
    {
        if (!auth()->user()->can('manage-products')) {
            abort(403, 'Unauthorized to modify products.');
        }

        // Verify user has access to this product's business unit
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($product->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to modify this product.');
        }

        $product->update(['is_active' => !$product->is_active]);

        $status = $product->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Product {$status} successfully.");
    }

    /**
     * Add a budget for a specific year.
     */
    public function addBudget(Request $request, Product $product): RedirectResponse
    {
        if (!auth()->user()->can('manage-products')) {
            abort(403, 'Unauthorized to manage product budgets.');
        }

        // Verify user has access to this product's business unit
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($product->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to manage this product.');
        }

        $request->validate([
            'budget_year' => 'required|integer|min:2020|max:2050',
            'budget_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check if budget already exists for this year
        $existingBudget = Budget::where('product_id', $product->id)
            ->where('budget_year', $request->budget_year)
            ->first();

        if ($existingBudget) {
            return redirect()
                ->back()
                ->with('error', 'A budget already exists for ' . $request->budget_year . '. Please edit the existing budget instead.');
        }

        Budget::create([
            'business_unit_id' => $product->business_unit_id,
            'product_id' => $product->id,
            'budget_year' => $request->budget_year,
            'projected_revenue' => $request->budget_amount,
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        // Update product's budget_allocation if this is for current year
        if ($request->budget_year == date('Y')) {
            $product->update(['budget_allocation' => $request->budget_amount]);
        }

        return redirect()
            ->back()
            ->with('success', 'Budget for ' . $request->budget_year . ' added successfully.');
    }

    /**
     * Update an existing budget.
     */
    public function updateBudget(Request $request, Product $product, Budget $budget): RedirectResponse
    {
        if (!auth()->user()->can('manage-products')) {
            abort(403, 'Unauthorized to manage product budgets.');
        }

        // Verify user has access to this product's business unit
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($product->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to manage this product.');
        }

        // Verify budget belongs to this product
        if ($budget->product_id !== $product->id) {
            abort(404, 'Budget not found for this product.');
        }

        $request->validate([
            'budget_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $budget->update([
            'projected_revenue' => $request->budget_amount,
            'notes' => $request->notes,
            'updated_by' => auth()->id(),
        ]);

        // Update product's budget_allocation if this is for current year
        if ($budget->budget_year == date('Y')) {
            $product->update(['budget_allocation' => $request->budget_amount]);
        }

        return redirect()
            ->back()
            ->with('success', 'Budget for ' . $budget->budget_year . ' updated successfully.');
    }

    /**
     * Delete a budget.
     */
    public function deleteBudget(Product $product, Budget $budget): RedirectResponse
    {
        if (!auth()->user()->can('manage-products')) {
            abort(403, 'Unauthorized to manage product budgets.');
        }

        // Verify user has access to this product's business unit
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($product->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to manage this product.');
        }

        // Verify budget belongs to this product
        if ($budget->product_id !== $product->id) {
            abort(404, 'Budget not found for this product.');
        }

        $year = $budget->budget_year;
        $budget->delete();

        // If this was the current year's budget, reset the product's budget_allocation
        if ($year == date('Y')) {
            $product->update(['budget_allocation' => 0]);
        }

        return redirect()
            ->back()
            ->with('success', 'Budget for ' . $year . ' deleted successfully.');
    }
}
