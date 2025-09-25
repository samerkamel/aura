<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use App\Models\Department;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): View
    {
        if (!auth()->user()->can('manage-departments')) {
            abort(403, 'Unauthorized to view products.');
        }

        $query = Department::query();

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
                  ->orWhere('head_of_department', 'like', '%' . $request->search . '%');
            });
        }

        $departments = $query->with(['contracts' => function($q) {
            $q->where('status', 'active');
        }])->orderBy('name')->paginate(15);

        // Calculate YTD budget and contract values for each department
        foreach ($departments as $department) {
            // Calculate YTD budget (pro-rated based on how much of the year has passed)
            $currentDate = now();
            $yearStart = $currentDate->copy()->startOfYear();
            $daysInYear = $yearStart->daysInYear;
            $daysPassed = $yearStart->diffInDays($currentDate) + 1;

            $department->ytd_budget = ($department->budget_allocation * $daysPassed) / $daysInYear;

            // Calculate total contract allocations for this department
            $contractValue = $department->contracts->sum(function($contract) {
                $pivot = $contract->pivot;
                if ($pivot->allocation_type === 'amount') {
                    return $pivot->allocation_amount;
                } else {
                    return ($pivot->allocation_percentage / 100) * $contract->total_amount;
                }
            });

            $department->contract_value = $contractValue;

            // Calculate achievement percentage (contracts / YTD budget)
            $department->achievement_percentage = $department->ytd_budget > 0
                ? ($contractValue / $department->ytd_budget) * 100
                : 0;
        }

        // Calculate total budget for percentage calculations
        $totalActiveBudget = Department::where('is_active', true)->sum('budget_allocation');

        // Add budget percentage to each department
        foreach ($departments as $department) {
            $department->budget_percentage = $totalActiveBudget > 0 && $department->budget_allocation > 0
                ? ($department->budget_allocation / $totalActiveBudget) * 100
                : 0;
        }

        // Calculate total contract value and YTD budget across all departments
        $totalContractValue = 0;
        $totalYtdBudget = 0;
        foreach ($departments as $department) {
            $totalContractValue += $department->contract_value;
            $totalYtdBudget += $department->ytd_budget;
        }

        // Calculate statistics
        $statistics = [
            'total_contracts' => $totalContractValue,
            'active_departments' => Department::where('is_active', true)->count(),
            'total_ytd_budget' => $totalYtdBudget,
            'total_budget' => $totalActiveBudget,
        ];

        return view('administration.products.index', compact('departments', 'statistics'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): View
    {
        if (!auth()->user()->can('manage-departments')) {
            abort(403, 'Unauthorized to create products.');
        }

        return view('administration.products.create');
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!auth()->user()->can('manage-departments')) {
            abort(403, 'Unauthorized to create products.');
        }

        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:10|unique:departments',
                'description' => 'nullable|string',
                'head_of_department' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'budget_allocation' => 'nullable|numeric|min:0',
            ]);

            $department = Department::create([
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'description' => $request->description,
                'head_of_department' => $request->head_of_department,
                'email' => $request->email,
                'phone' => $request->phone,
                'budget_allocation' => $request->budget_allocation,
                'is_active' => $request->has('is_active'),
            ]);

            return redirect()
                ->route('administration.products.show', $department)
                ->with('success', 'Product created successfully.');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create product: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified product.
     */
    public function show(Department $department): View
    {
        if (!auth()->user()->can('manage-departments')) {
            abort(403, 'Unauthorized to view product details.');
        }

        $department->load('contracts');

        return view('administration.products.show', compact('department'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Department $department): View
    {
        if (!auth()->user()->can('manage-departments')) {
            abort(403, 'Unauthorized to edit products.');
        }

        return view('administration.products.edit', compact('department'));
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Department $department): RedirectResponse
    {
        if (!auth()->user()->can('manage-departments')) {
            abort(403, 'Unauthorized to edit products.');
        }

        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:10|unique:departments,code,' . $department->id,
                'description' => 'nullable|string',
                'head_of_department' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'budget_allocation' => 'nullable|numeric|min:0',
            ]);

            $department->update([
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'description' => $request->description,
                'head_of_department' => $request->head_of_department,
                'email' => $request->email,
                'phone' => $request->phone,
                'budget_allocation' => $request->budget_allocation,
                'is_active' => $request->has('is_active'),
            ]);

            return redirect()
                ->route('administration.products.show', $department)
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
    public function destroy(Department $department): RedirectResponse
    {
        if (!auth()->user()->can('manage-departments')) {
            abort(403, 'Unauthorized to delete products.');
        }

        // Check if product has contracts
        if ($department->contracts()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete product that has assigned contracts.');
        }

        $department->delete();

        return redirect()
            ->route('administration.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    /**
     * Toggle product status.
     */
    public function toggleStatus(Department $department): RedirectResponse
    {
        if (!auth()->user()->can('manage-departments')) {
            abort(403, 'Unauthorized to modify products.');
        }

        $department->update(['is_active' => !$department->is_active]);

        $status = $department->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Product {$status} successfully.");
    }
}
