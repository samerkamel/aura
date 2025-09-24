<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Accounting\Models\ExpenseSchedule;
use Modules\Accounting\Models\ExpenseCategory;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Http\Requests\StoreExpenseScheduleRequest;
use Modules\Accounting\Http\Requests\UpdateExpenseScheduleRequest;
use Modules\Accounting\Services\ScheduleCalculatorService;

/**
 * ExpenseController
 *
 * Handles CRUD operations for expense schedules and categories.
 */
class ExpenseController extends Controller
{
    protected ScheduleCalculatorService $scheduleCalculator;

    public function __construct(ScheduleCalculatorService $scheduleCalculator)
    {
        $this->scheduleCalculator = $scheduleCalculator;
    }

    /**
     * Display a listing of expense schedules.
     */
    public function index(Request $request): View
    {
        // Check authorization
        if (!auth()->user()->can('view-accounting-readonly')) {
            abort(403, 'Unauthorized to view expense schedules.');
        }
        $query = ExpenseSchedule::with(['category'])
            ->where('expense_type', 'recurring');

        // Filter by category
        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search by name
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $expenseSchedules = $query->orderBy('name')->paginate(15);
        $categories = ExpenseCategory::active()->orderBy('name')->get();

        // Calculate summary statistics (recurring expenses only)
        $statistics = [
            'total_schedules' => ExpenseSchedule::where('expense_type', 'recurring')->count(),
            'active_schedules' => ExpenseSchedule::where('expense_type', 'recurring')->active()->count(),
            'total_monthly_amount' => ExpenseSchedule::where('expense_type', 'recurring')->active()->get()->sum('monthly_equivalent_amount'),
            'categories_count' => ExpenseCategory::active()->count(),
        ];

        return view('accounting::expenses.index', compact(
            'expenseSchedules',
            'categories',
            'statistics'
        ));
    }

    /**
     * Display a listing of paid expenses (both one-time and scheduled).
     */
    public function paidExpenses(Request $request): View
    {
        // Check authorization
        if (!auth()->user()->can('view-accounting-readonly')) {
            abort(403, 'Unauthorized to view paid expenses.');
        }

        $query = ExpenseSchedule::with(['category', 'subcategory', 'paidFromAccount'])
            ->where('payment_status', 'paid');

        // Filter by date range
        if ($request->has('start_date') && $request->start_date) {
            $query->where('paid_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->where('paid_date', '<=', $request->end_date);
        }

        // Filter by category
        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by account
        if ($request->has('account_id') && $request->account_id) {
            $query->where('paid_from_account_id', $request->account_id);
        }

        // Filter by expense type
        if ($request->has('expense_type') && $request->expense_type) {
            $query->where('expense_type', $request->expense_type);
        }

        // Search by name
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $paidExpenses = $query->orderBy('paid_date', 'desc')
                             ->orderBy('created_at', 'desc')
                             ->paginate(25);

        // Get filter options
        $categories = ExpenseCategory::active()->orderBy('name')->get();
        $accounts = Account::active()->orderBy('name')->get();

        // Calculate summary statistics
        $statistics = [
            'total_paid' => $query->sum('paid_amount'),
            'total_count' => $query->count(),
            'this_month_total' => ExpenseSchedule::where('payment_status', 'paid')
                ->whereMonth('paid_date', now()->month)
                ->whereYear('paid_date', now()->year)
                ->sum('paid_amount'),
            'this_year_total' => ExpenseSchedule::where('payment_status', 'paid')
                ->whereYear('paid_date', now()->year)
                ->sum('paid_amount'),
        ];

        return view('accounting::expenses.paid', compact(
            'paidExpenses',
            'categories',
            'accounts',
            'statistics'
        ));
    }

    /**
     * Show the form for creating a new expense schedule.
     */
    public function create(): View
    {
        // Check authorization
        if (!auth()->user()->can('manage-expense-schedules')) {
            abort(403, 'Unauthorized to create expense schedules.');
        }

        // Get main categories with their subcategories
        $categories = ExpenseCategory::active()
            ->mainCategories()
            ->with(['subcategories' => function ($query) {
                $query->active()->orderBy('sort_order')->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        $accounts = Account::active()->orderBy('name')->get();
        $frequencyOptions = $this->scheduleCalculator->getFrequencyOptions();

        return view('accounting::expenses.create', compact('categories', 'accounts', 'frequencyOptions'));
    }

    /**
     * Store a newly created expense schedule.
     */
    public function store(StoreExpenseScheduleRequest $request): RedirectResponse
    {
        $validatedData = $request->validated();

        // Set default payment status
        $validatedData['payment_status'] = $request->input('mark_as_paid') ? 'paid' : 'pending';

        // Handle paid amount - use expense amount if not provided
        if ($request->input('mark_as_paid') && !$request->input('paid_amount')) {
            $validatedData['paid_amount'] = $validatedData['amount'];
        }

        // For one-time expenses, set default frequency values to satisfy database constraints
        if ($validatedData['expense_type'] === 'one_time') {
            $validatedData['frequency_type'] = 'monthly';
            $validatedData['frequency_value'] = 1;
            $validatedData['start_date'] = $validatedData['expense_date'] ?? now();
        }

        // Remove the mark_as_paid field as it's not in the database
        unset($validatedData['mark_as_paid']);

        $expenseSchedule = ExpenseSchedule::create($validatedData);

        // Update account balance if payment was made
        if ($expenseSchedule->payment_status === 'paid' && $expenseSchedule->paid_from_account_id) {
            $account = Account::find($expenseSchedule->paid_from_account_id);
            if ($account) {
                $account->updateBalance($expenseSchedule->paid_amount ?? $expenseSchedule->amount, 'subtract');
            }
        }

        $expenseType = $expenseSchedule->expense_type === 'one_time' ? 'one-time expense' : 'expense schedule';

        return redirect()
            ->route('accounting.expenses.show', $expenseSchedule)
            ->with('success', "The {$expenseType} was created successfully.");
    }

    /**
     * Display the specified expense schedule.
     */
    public function show(ExpenseSchedule $expenseSchedule): View
    {
        $expenseSchedule->load(['category', 'subcategory', 'paidFromAccount']);

        $upcomingOccurrences = [];
        $statistics = [
            'monthly_equivalent' => 0,
            'yearly_equivalent' => 0,
            'upcoming_count' => 0,
            'next_occurrence' => null,
        ];

        // Handle different logic for recurring vs one-time expenses
        if ($expenseSchedule->expense_type === 'recurring') {
            // Calculate upcoming occurrences for next 6 months for recurring expenses
            $upcomingOccurrences = $expenseSchedule->getOccurrencesInPeriod(
                now(),
                now()->addMonths(6)
            );

            // Get related statistics for recurring expenses
            $statistics = [
                'monthly_equivalent' => $expenseSchedule->monthly_equivalent_amount,
                'yearly_equivalent' => $expenseSchedule->yearly_equivalent_amount,
                'upcoming_count' => count($upcomingOccurrences),
                'next_occurrence' => $expenseSchedule->getNextOccurrenceAfter(now()),
            ];
        } else {
            // For one-time expenses, provide relevant statistics
            $statistics = [
                'monthly_equivalent' => $expenseSchedule->amount, // One-time amount
                'yearly_equivalent' => $expenseSchedule->amount, // Same as one-time
                'upcoming_count' => $expenseSchedule->payment_status === 'paid' ? 0 : 1,
                'next_occurrence' => $expenseSchedule->payment_status === 'paid' ? null : $expenseSchedule->expense_date,
            ];
        }

        return view('accounting::expenses.show', compact(
            'expenseSchedule',
            'upcomingOccurrences',
            'statistics'
        ));
    }

    /**
     * Show the form for editing the specified expense schedule.
     */
    public function edit(ExpenseSchedule $expenseSchedule): View
    {
        $categories = ExpenseCategory::active()->orderBy('name')->get();
        $frequencyOptions = $this->scheduleCalculator->getFrequencyOptions();

        return view('accounting::expenses.edit', compact(
            'expenseSchedule',
            'categories',
            'frequencyOptions'
        ));
    }

    /**
     * Update the specified expense schedule.
     */
    public function update(UpdateExpenseScheduleRequest $request, ExpenseSchedule $expenseSchedule): RedirectResponse
    {
        $expenseSchedule->update($request->validated());

        return redirect()
            ->route('accounting.expenses.show', $expenseSchedule)
            ->with('success', 'Expense schedule updated successfully.');
    }

    /**
     * Remove the specified expense schedule.
     */
    public function destroy(ExpenseSchedule $expenseSchedule): RedirectResponse
    {
        $expenseSchedule->delete();

        return redirect()
            ->route('accounting.expenses.index')
            ->with('success', 'Expense schedule deleted successfully.');
    }

    /**
     * Toggle active status of expense schedule.
     */
    public function toggleStatus(ExpenseSchedule $expenseSchedule): RedirectResponse
    {
        $expenseSchedule->update(['is_active' => !$expenseSchedule->is_active]);

        $status = $expenseSchedule->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Expense schedule {$status} successfully.");
    }

    /**
     * Bulk operations on expense schedules.
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        // Check authorization
        if (!auth()->user()->can('manage-expense-schedules')) {
            abort(403, 'Unauthorized to perform bulk actions on expense schedules.');
        }

        $request->validate([
            'schedules' => 'required|string',
            'action' => 'required|in:activate,deactivate,delete',
        ]);

        // Decode JSON string to array
        $scheduleIds = json_decode($request->schedules, true);

        if (!is_array($scheduleIds) || empty($scheduleIds)) {
            return redirect()
                ->route('accounting.expenses.index')
                ->with('error', 'No schedules selected.');
        }

        // Validate that all IDs exist
        $validScheduleIds = ExpenseSchedule::whereIn('id', $scheduleIds)->pluck('id')->toArray();

        if (count($validScheduleIds) !== count($scheduleIds)) {
            return redirect()
                ->route('accounting.expenses.index')
                ->with('error', 'Some selected schedules were not found.');
        }

        $schedules = ExpenseSchedule::whereIn('id', $validScheduleIds);

        $message = '';

        switch ($request->action) {
            case 'activate':
                $schedules->update(['is_active' => true]);
                $message = 'Selected schedules activated successfully.';
                break;

            case 'deactivate':
                $schedules->update(['is_active' => false]);
                $message = 'Selected schedules deactivated successfully.';
                break;

            case 'delete':
                $schedules->delete();
                $message = 'Selected schedules deleted successfully.';
                break;

            default:
                return redirect()
                    ->route('accounting.expenses.index')
                    ->with('error', 'Invalid action selected.');
        }

        return redirect()
            ->route('accounting.expenses.index')
            ->with('success', $message);
    }

    /**
     * Display expense categories management.
     */
    public function categories(): View
    {
        // Check authorization
        if (!auth()->user()->can('manage-expense-categories')) {
            abort(403, 'Unauthorized to manage expense categories.');
        }
        // Get main categories first
        $mainCategories = ExpenseCategory::withCount(['expenseSchedules', 'activeExpenseSchedules'])
            ->with(['subcategories' => function ($query) {
                $query->withCount(['expenseSchedules', 'activeExpenseSchedules'])
                      ->orderBy('sort_order')
                      ->orderBy('name');
            }])
            ->mainCategories()
            ->orderBy('name')
            ->get();

        // Calculate YTD values for each category
        $yearStart = now()->startOfYear();
        $monthsElapsed = now()->diffInMonths($yearStart) + 1;

        // Flatten the hierarchy for the table display
        $categories = collect();
        foreach ($mainCategories as $mainCategory) {
            // Calculate YTD and average values for main category
            $mainCategory->ytd_total = $this->calculateYtdTotal($mainCategory);
            $mainCategory->ytd_average_per_month = $monthsElapsed > 0 ? $mainCategory->ytd_total / $monthsElapsed : 0;
            $mainCategory->average_scheduled_per_month = $mainCategory->monthly_amount;

            $categories->push($mainCategory);

            // Add subcategories right after their parent
            foreach ($mainCategory->subcategories as $subcategory) {
                // Load the parent relationship for subcategory
                $subcategory->load('parent');

                // Calculate YTD and average values for subcategory
                $subcategory->ytd_total = $this->calculateYtdTotal($subcategory);
                $subcategory->ytd_average_per_month = $monthsElapsed > 0 ? $subcategory->ytd_total / $monthsElapsed : 0;
                $subcategory->average_scheduled_per_month = $subcategory->monthly_amount;

                $categories->push($subcategory);
            }
        }

        // Get only main categories for parent selection
        $parentCategories = ExpenseCategory::active()
            ->mainCategories()
            ->orderBy('name')
            ->get();

        return view('accounting::expenses.categories', compact('categories', 'parentCategories'));
    }

    /**
     * Store a new expense category.
     */
    public function storeCategory(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name',
            'description' => 'nullable|string',
            'color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'parent_id' => 'nullable|exists:expense_categories,id',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data = $request->only(['name', 'description', 'color', 'parent_id', 'sort_order']);

        // Set default sort_order if not provided
        if (empty($data['sort_order'])) {
            $data['sort_order'] = 0;
        }

        ExpenseCategory::create($data);

        return redirect()
            ->route('accounting.expenses.categories')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Update an expense category.
     */
    public function updateCategory(Request $request, ExpenseCategory $category): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name,' . $category->id,
            'description' => 'nullable|string',
            'color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
        ]);

        $category->update($request->only(['name', 'description', 'color']));

        return redirect()
            ->route('accounting.expenses.categories')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Toggle category status.
     */
    public function toggleCategoryStatus(ExpenseCategory $category): RedirectResponse
    {
        // Check authorization
        if (!auth()->user()->can('manage-expense-categories')) {
            abort(403, 'Unauthorized to modify expense categories.');
        }

        $category->update(['is_active' => !$category->is_active]);

        $status = $category->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Category {$status} successfully.");
    }

    /**
     * Delete an expense category.
     */
    public function destroyCategory(ExpenseCategory $category): RedirectResponse
    {
        // Check authorization
        if (!auth()->user()->can('manage-expense-categories')) {
            abort(403, 'Unauthorized to delete expense categories.');
        }

        // Check if category has any associated expense schedules
        if ($category->expenseSchedules()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete category that has associated expense schedules.');
        }

        $categoryName = $category->name;
        $category->delete();

        return redirect()
            ->route('accounting.expenses.categories')
            ->with('success', "Category '{$categoryName}' deleted successfully.");
    }

    /**
     * Calculate year-to-date total for a category.
     */
    private function calculateYtdTotal(ExpenseCategory $category): float
    {
        $yearStart = now()->startOfYear();
        $yearEnd = now()->endOfYear();

        return $category->expenseSchedules()
            ->where('payment_status', 'paid')
            ->where('paid_date', '>=', $yearStart)
            ->where('paid_date', '<=', now())
            ->sum('paid_amount');
    }
}