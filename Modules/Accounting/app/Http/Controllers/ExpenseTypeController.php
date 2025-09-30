<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Accounting\Models\ExpenseType;

/**
 * ExpenseTypeController
 *
 * Handles CRUD operations for expense types (CapEx, OpEx, etc.).
 */
class ExpenseTypeController extends Controller
{
    /**
     * Display a listing of expense types.
     */
    public function index(): View
    {
        // Check authorization
        if (!auth()->user()->can('manage-expense-categories')) {
            abort(403, 'Unauthorized to manage expense types.');
        }

        $expenseTypes = ExpenseType::withCount(['categories', 'activeCategories'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('accounting::expense-types.index', compact('expenseTypes'));
    }

    /**
     * Store a newly created expense type.
     */
    public function store(Request $request): RedirectResponse
    {
        // Check authorization
        if (!auth()->user()->can('manage-expense-categories')) {
            abort(403, 'Unauthorized to manage expense types.');
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:expense_types,name',
            'code' => 'required|string|max:20|unique:expense_types,code',
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|regex:/^#[A-Fa-f0-9]{6}$/',
            'sort_order' => 'nullable|integer|min:0|max:999',
        ]);

        // Set default sort order if not provided
        if (!isset($validatedData['sort_order'])) {
            $maxSortOrder = ExpenseType::max('sort_order') ?? 0;
            $validatedData['sort_order'] = $maxSortOrder + 1;
        }

        $validatedData['is_active'] = true;

        ExpenseType::create($validatedData);

        return redirect()->route('accounting.expense-types.index')
                        ->with('success', 'Expense type created successfully.');
    }

    /**
     * Update the specified expense type.
     */
    public function update(Request $request, ExpenseType $expenseType): RedirectResponse
    {
        // Check authorization
        if (!auth()->user()->can('manage-expense-categories')) {
            abort(403, 'Unauthorized to manage expense types.');
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:expense_types,name,' . $expenseType->id,
            'code' => 'required|string|max:20|unique:expense_types,code,' . $expenseType->id,
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|regex:/^#[A-Fa-f0-9]{6}$/',
            'sort_order' => 'nullable|integer|min:0|max:999',
        ]);

        $expenseType->update($validatedData);

        return redirect()->route('accounting.expense-types.index')
                        ->with('success', 'Expense type updated successfully.');
    }

    /**
     * Toggle the active status of an expense type.
     */
    public function toggleStatus(ExpenseType $expenseType): RedirectResponse
    {
        // Check authorization
        if (!auth()->user()->can('manage-expense-categories')) {
            abort(403, 'Unauthorized to manage expense types.');
        }

        $expenseType->update([
            'is_active' => !$expenseType->is_active
        ]);

        $status = $expenseType->is_active ? 'activated' : 'deactivated';

        return redirect()->route('accounting.expense-types.index')
                        ->with('success', "Expense type {$status} successfully.");
    }

    /**
     * Remove the specified expense type from storage.
     */
    public function destroy(ExpenseType $expenseType): RedirectResponse
    {
        // Check authorization
        if (!auth()->user()->can('manage-expense-categories')) {
            abort(403, 'Unauthorized to manage expense types.');
        }

        // Check if expense type has categories
        if ($expenseType->categories()->exists()) {
            return redirect()->route('accounting.expense-types.index')
                            ->with('error', 'Cannot delete expense type that has categories assigned to it.');
        }

        $expenseType->delete();

        return redirect()->route('accounting.expense-types.index')
                        ->with('success', 'Expense type deleted successfully.');
    }

    /**
     * Update the sort order of expense types.
     */
    public function updateSortOrder(Request $request): RedirectResponse
    {
        // Check authorization
        if (!auth()->user()->can('manage-expense-categories')) {
            abort(403, 'Unauthorized to manage expense types.');
        }

        $validatedData = $request->validate([
            'expense_types' => 'required|array',
            'expense_types.*' => 'exists:expense_types,id',
        ]);

        foreach ($validatedData['expense_types'] as $index => $expenseTypeId) {
            ExpenseType::where('id', $expenseTypeId)->update(['sort_order' => $index + 1]);
        }

        return redirect()->route('accounting.expense-types.index')
                        ->with('success', 'Expense type order updated successfully.');
    }
}