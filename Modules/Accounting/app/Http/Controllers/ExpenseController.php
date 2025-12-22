<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Accounting\Models\ExpenseSchedule;
use Modules\Accounting\Models\ExpenseCategory;
use Modules\Accounting\Models\ExpenseCategoryBudget;
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

        // Get expense types for category filtering
        $expenseTypes = \Modules\Accounting\Models\ExpenseType::active()
            ->with('activeCategories')
            ->orderBy('sort_order')
            ->get();

        return view('accounting::expenses.create', compact('categories', 'accounts', 'frequencyOptions', 'expenseTypes'));
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

        $accounts = Account::active()->orderBy('name')->get();

        return view('accounting::expenses.show', compact(
            'expenseSchedule',
            'upcomingOccurrences',
            'statistics',
            'accounts'
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
            }, 'expenseType'])
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

        // Get expense types for selection
        $expenseTypes = \Modules\Accounting\Models\ExpenseType::active()
            ->orderBy('sort_order')
            ->get();

        return view('accounting::expenses.categories', compact('categories', 'parentCategories', 'expenseTypes'));
    }

    /**
     * Store a new expense category.
     */
    public function storeCategory(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name',
            'name_ar' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'parent_id' => 'nullable|exists:expense_categories,id',
            'expense_type_id' => 'nullable|exists:expense_types,id',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data = $request->only(['name', 'name_ar', 'description', 'color', 'parent_id', 'expense_type_id', 'sort_order']);

        // If creating a subcategory, inherit expense type from parent
        if (!empty($data['parent_id']) && empty($data['expense_type_id'])) {
            $parent = ExpenseCategory::find($data['parent_id']);
            $data['expense_type_id'] = $parent ? $parent->expense_type_id : null;
        }

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
        // Validate based on category type
        $rules = [
            'name' => 'required|string|max:255|unique:expense_categories,name,' . $category->id,
            'name_ar' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
        ];

        // Only main categories need expense_type_id
        if ($category->parent_id === null) {
            $rules['expense_type_id'] = 'required|exists:expense_types,id';
        } else {
            $rules['expense_type_id'] = 'nullable|exists:expense_types,id';
        }

        $request->validate($rules);

        $data = $request->only(['name', 'name_ar', 'description', 'color', 'expense_type_id']);

        // Only main categories can have expense types
        if ($category->parent_id !== null) {
            unset($data['expense_type_id']);
        }

        $category->update($data);

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
     * Display budget management page for expense categories.
     */
    public function categoryBudgets(Request $request): View
    {
        // Check authorization
        if (!auth()->user()->can('manage-expense-categories')) {
            abort(403, 'Unauthorized to manage expense category budgets.');
        }

        $year = $request->get('year', (int) date('Y'));

        // Get only main categories (top-level) with their budgets
        $categories = ExpenseCategory::mainCategories()
            ->active()
            ->with(['budgets' => function ($query) use ($year) {
                $query->where('budget_year', $year);
            }, 'expenseType'])
            ->orderBy('name')
            ->get();

        // Calculate YTD spending for each category
        $yearStart = now()->setYear($year)->startOfYear();
        $currentDate = $year == date('Y') ? now() : now()->setYear($year)->endOfYear();
        $monthsElapsed = $yearStart->diffInMonths($currentDate) + 1;

        foreach ($categories as $category) {
            $category->ytd_spending = $this->calculateYtdTotal($category);
            $category->ytd_average_per_month = $monthsElapsed > 0 ? $category->ytd_spending / $monthsElapsed : 0;
        }

        // Get available years for selection
        $availableYears = range(date('Y') - 2, date('Y') + 2);

        return view('accounting::expenses.category-budgets', compact('categories', 'year', 'availableYears'));
    }

    /**
     * Store a new category budget.
     */
    public function storeCategoryBudget(Request $request, ExpenseCategory $category): RedirectResponse
    {
        // Check authorization
        if (!auth()->user()->can('manage-expense-categories')) {
            abort(403, 'Unauthorized to manage expense category budgets.');
        }

        // Ensure this is a main category (top-level)
        if ($category->parent_id !== null) {
            return redirect()->back()->with('error', 'Budgets can only be set for main categories.');
        }

        $request->validate([
            'budget_year' => 'required|integer|min:2020|max:2050',
            'budget_percentage' => 'required|numeric|min:0|max:100',
            'calculation_base' => 'required|in:total_revenue,net_income',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check if budget already exists for this category and year
        $existingBudget = ExpenseCategoryBudget::where('expense_category_id', $category->id)
            ->where('budget_year', $request->budget_year)
            ->first();

        if ($existingBudget) {
            return redirect()->back()->with('error', "A budget for {$request->budget_year} already exists for this category.");
        }

        ExpenseCategoryBudget::create([
            'expense_category_id' => $category->id,
            'budget_year' => $request->budget_year,
            'budget_percentage' => $request->budget_percentage,
            'calculation_base' => $request->calculation_base,
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->back()
            ->with('success', "Budget for {$request->budget_year} added to {$category->name}.");
    }

    /**
     * Update an existing category budget.
     */
    public function updateCategoryBudget(Request $request, ExpenseCategory $category, ExpenseCategoryBudget $budget): RedirectResponse
    {
        // Check authorization
        if (!auth()->user()->can('manage-expense-categories')) {
            abort(403, 'Unauthorized to manage expense category budgets.');
        }

        // Verify budget belongs to this category
        if ($budget->expense_category_id !== $category->id) {
            abort(404, 'Budget not found for this category.');
        }

        $request->validate([
            'budget_percentage' => 'required|numeric|min:0|max:100',
            'calculation_base' => 'required|in:total_revenue,net_income',
            'notes' => 'nullable|string|max:500',
        ]);

        $budget->update([
            'budget_percentage' => $request->budget_percentage,
            'calculation_base' => $request->calculation_base,
            'notes' => $request->notes,
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->back()
            ->with('success', "Budget for {$budget->budget_year} updated successfully.");
    }

    /**
     * Delete a category budget.
     */
    public function destroyCategoryBudget(ExpenseCategory $category, ExpenseCategoryBudget $budget): RedirectResponse
    {
        // Check authorization
        if (!auth()->user()->can('manage-expense-categories')) {
            abort(403, 'Unauthorized to manage expense category budgets.');
        }

        // Verify budget belongs to this category
        if ($budget->expense_category_id !== $category->id) {
            abort(404, 'Budget not found for this category.');
        }

        $year = $budget->budget_year;
        $budget->delete();

        return redirect()
            ->back()
            ->with('success', "Budget for {$year} deleted successfully.");
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
     * Show CSV import form for expense categories.
     */
    public function importCategoriesForm(): View
    {
        // Check authorization
        if (!auth()->user()->can('manage-expense-categories')) {
            abort(403, 'Unauthorized to import expense categories.');
        }

        $parentCategories = ExpenseCategory::active()->mainCategories()->orderBy('name')->get();

        return view('accounting::expenses.import-categories', compact('parentCategories'));
    }

    /**
     * Process CSV import for expense categories.
     */
    public function importCategories(Request $request): RedirectResponse
    {
        // Check authorization
        if (!auth()->user()->can('manage-expense-categories')) {
            abort(403, 'Unauthorized to import expense categories.');
        }

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        try {
            $file = $request->file('csv_file');
            $csvData = array_map('str_getcsv', file($file->getPathname()));

            // Remove header row
            $header = array_shift($csvData);

            // Validate header format
            $expectedHeader = ['name', 'description', 'color', 'parent_id', 'sort_order'];
            if (count(array_intersect($header, $expectedHeader)) < 2) { // At least name and color required
                return redirect()->back()
                    ->with('error', 'Invalid CSV format. Please download the sample CSV and follow the format.');
            }

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($csvData as $rowIndex => $row) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    // Map CSV row to array using header
                    $data = array_combine($header, $row);

                    // Validate required fields
                    if (empty($data['name'])) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Missing required field 'name'";
                        $errorCount++;
                        continue;
                    }

                    // Check for duplicate names
                    if (ExpenseCategory::where('name', trim($data['name']))->exists()) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Category name '" . trim($data['name']) . "' already exists";
                        $errorCount++;
                        continue;
                    }

                    // Validate parent category exists if provided
                    $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
                    if ($parentId && !ExpenseCategory::find($parentId)) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Parent category ID {$parentId} does not exist";
                        $errorCount++;
                        continue;
                    }

                    // Validate color format
                    $color = !empty($data['color']) ? $data['color'] : '#007bff';
                    if (!preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {
                        $color = '#007bff'; // Default color if invalid
                    }

                    // Create the category record
                    $categoryData = [
                        'name' => trim($data['name']),
                        'description' => !empty($data['description']) ? trim($data['description']) : null,
                        'color' => $color,
                        'parent_id' => $parentId,
                        'sort_order' => !empty($data['sort_order']) ? (int)$data['sort_order'] : 0,
                        'is_active' => true,
                    ];

                    ExpenseCategory::create($categoryData);
                    $successCount++;

                } catch (\Exception $e) {
                    $errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
                    $errorCount++;
                }
            }

            $message = "{$successCount} expense categories imported successfully.";
            if ($errorCount > 0) {
                $message .= " {$errorCount} errors occurred.";
            }

            $messageType = $errorCount > 0 ? 'warning' : 'success';

            return redirect()->route('accounting.expenses.categories')
                ->with($messageType, $message)
                ->with('import_errors', $errors);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error processing CSV file: ' . $e->getMessage());
        }
    }

    /**
     * Download sample CSV file for expense categories import.
     */
    public function downloadCategoriesSample()
    {
        // Check authorization
        if (!auth()->user()->can('manage-expense-categories')) {
            abort(403, 'Unauthorized to download sample files.');
        }

        $headers = ['name', 'description', 'color', 'parent_id', 'sort_order'];

        $sampleData = [
            ['Office Supplies', 'General office supplies and equipment', '#28a745', '', '1'],
            ['Marketing', 'Marketing and advertising expenses', '#fd7e14', '', '2'],
            ['Travel', 'Business travel and accommodation', '#6f42c1', '', '3'],
            ['Office Rent', 'Monthly office rental payments', '#28a745', '1', '1'],
            ['Stationery', 'Paper, pens, and office supplies', '#28a745', '1', '2'],
        ];

        $csvContent = implode(',', $headers) . "\n";
        foreach ($sampleData as $row) {
            $csvContent .= '"' . implode('","', $row) . '"' . "\n";
        }

        $filename = 'expense_categories_sample_' . date('Y-m-d') . '.csv';

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Show CSV import form for paid expenses.
     */
    public function importForm(): View
    {
        // Check authorization
        if (!auth()->user()->can('manage-expense-schedules')) {
            abort(403, 'Unauthorized to import expenses.');
        }

        $categories = ExpenseCategory::active()->orderBy('name')->get();
        $accounts = Account::active()->orderBy('name')->get();

        return view('accounting::expenses.import', compact('categories', 'accounts'));
    }

    /**
     * Process CSV import for paid expenses.
     */
    public function import(Request $request): RedirectResponse
    {
        // Check authorization
        if (!auth()->user()->can('manage-expense-schedules')) {
            abort(403, 'Unauthorized to import expenses.');
        }

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        try {
            $file = $request->file('csv_file');
            $csvData = array_map('str_getcsv', file($file->getPathname()));

            // Remove header row
            $header = array_shift($csvData);

            // Validate header format
            $expectedHeader = ['name', 'description', 'amount', 'category_id', 'subcategory_id', 'expense_date', 'paid_from_account_id', 'payment_notes'];
            if (count(array_intersect($header, $expectedHeader)) < 4) { // At least 4 required fields
                return redirect()->back()
                    ->with('error', 'Invalid CSV format. Please download the sample CSV and follow the format.');
            }

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($csvData as $rowIndex => $row) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    // Map CSV row to array using header
                    $data = array_combine($header, $row);

                    // Validate required fields
                    if (empty($data['name']) || empty($data['amount']) || empty($data['expense_date'])) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Missing required fields (name, amount, expense_date)";
                        $errorCount++;
                        continue;
                    }

                    // Validate account exists if provided
                    $accountId = !empty($data['paid_from_account_id']) ? (int)$data['paid_from_account_id'] : null;
                    if ($accountId && !Account::find($accountId)) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Account ID {$accountId} does not exist";
                        $errorCount++;
                        continue;
                    }

                    // Validate category exists if provided
                    $categoryId = !empty($data['category_id']) ? (int)$data['category_id'] : null;
                    if ($categoryId && !ExpenseCategory::find($categoryId)) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Category ID {$categoryId} does not exist";
                        $errorCount++;
                        continue;
                    }

                    // Validate subcategory exists if provided
                    $subcategoryId = !empty($data['subcategory_id']) ? (int)$data['subcategory_id'] : null;
                    if ($subcategoryId && !ExpenseCategory::find($subcategoryId)) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Subcategory ID {$subcategoryId} does not exist";
                        $errorCount++;
                        continue;
                    }

                    // Create the expense record
                    $expenseData = [
                        'name' => trim($data['name']),
                        'description' => !empty($data['description']) ? trim($data['description']) : null,
                        'amount' => (float)$data['amount'],
                        'category_id' => $categoryId,
                        'subcategory_id' => $subcategoryId,
                        'expense_type' => 'one_time',
                        'expense_date' => date('Y-m-d', strtotime($data['expense_date'])),
                        'payment_status' => 'paid',
                        'paid_amount' => (float)$data['amount'],
                        'paid_from_account_id' => $accountId,
                        'paid_date' => date('Y-m-d', strtotime($data['expense_date'])),
                        'payment_notes' => !empty($data['payment_notes']) ? trim($data['payment_notes']) : null,
                        // Required fields for database constraints
                        'frequency_type' => 'monthly',
                        'frequency_value' => 1,
                        'start_date' => date('Y-m-d', strtotime($data['expense_date'])),
                        'is_active' => true,
                    ];

                    $expense = ExpenseSchedule::create($expenseData);

                    // Update account balance if account was provided
                    if ($accountId) {
                        $account = Account::find($accountId);
                        if ($account) {
                            $account->updateBalance($expense->paid_amount, 'subtract');
                        }
                    }

                    $successCount++;

                } catch (\Exception $e) {
                    $errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
                    $errorCount++;
                }
            }

            $message = "{$successCount} expenses imported successfully.";
            if ($errorCount > 0) {
                $message .= " {$errorCount} errors occurred.";
            }

            $messageType = $errorCount > 0 ? 'warning' : 'success';

            return redirect()->route('accounting.expenses.paid')
                ->with($messageType, $message)
                ->with('import_errors', $errors);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error processing CSV file: ' . $e->getMessage());
        }
    }

    /**
     * Download sample CSV file for expense import.
     */
    public function downloadSample()
    {
        // Check authorization
        if (!auth()->user()->can('manage-expense-schedules')) {
            abort(403, 'Unauthorized to download sample files.');
        }

        $headers = [
            'name',
            'description',
            'amount',
            'category_id',
            'subcategory_id',
            'expense_date',
            'paid_from_account_id',
            'payment_notes'
        ];

        $sampleData = [
            [
                'Office Supplies',
                'Monthly office supplies for November',
                '250.00',
                '7',
                '',
                '2024-11-15',
                '3',
                'Paid via company card'
            ],
            [
                'Software License',
                'Adobe Creative Suite monthly subscription',
                '89.99',
                '5',
                '',
                '2024-11-01',
                '1',
                'Monthly subscription payment'
            ],
            [
                'Internet Bill',
                'Company internet service - November',
                '199.50',
                '14',
                '',
                '2024-11-05',
                '4',
                'Monthly internet service'
            ]
        ];

        $csvContent = implode(',', $headers) . "\n";
        foreach ($sampleData as $row) {
            $csvContent .= '"' . implode('","', $row) . '"' . "\n";
        }

        $filename = 'expense_import_sample_' . date('Y-m-d') . '.csv';

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Mark expense as paid.
     */
    public function markAsPaid(Request $request, ExpenseSchedule $expenseSchedule): RedirectResponse
    {
        if (!auth()->user()->can('manage-expenses')) {
            abort(403, 'Unauthorized to mark expenses as paid.');
        }

        $request->validate([
            'paid_date' => 'required|date|before_or_equal:today',
            'paid_amount' => 'required|numeric|min:0.01',
            'paid_from_account_id' => 'required|exists:accounts,id',
            'payment_notes' => 'nullable|string|max:1000',
            'payment_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,gif,doc,docx|max:10240', // 10MB max
        ]);

        // Handle file upload
        $attachmentData = [];
        if ($request->hasFile('payment_attachment')) {
            $file = $request->file('payment_attachment');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('expense_payments', $fileName, 'private');

            $attachmentData = [
                'payment_attachment_path' => $filePath,
                'payment_attachment_original_name' => $file->getClientOriginalName(),
                'payment_attachment_mime_type' => $file->getClientMimeType(),
                'payment_attachment_size' => $file->getSize(),
            ];
        }

        $expenseSchedule->update([
            'payment_status' => 'paid',
            'paid_date' => $request->paid_date,
            'paid_amount' => $request->paid_amount,
            'paid_from_account_id' => $request->paid_from_account_id,
            'payment_notes' => $request->payment_notes,
            ...$attachmentData,
        ]);

        // Update account balance
        $account = Account::find($request->paid_from_account_id);
        if ($account) {
            $account->updateBalance($request->paid_amount, 'subtract');
        }

        return redirect()
            ->back()
            ->with('success', 'Expense marked as paid successfully.');
    }

    /**
     * Download expense payment attachment.
     */
    public function downloadPaymentAttachment(ExpenseSchedule $expenseSchedule)
    {
        if (!auth()->user()->can('view-expenses') && !auth()->user()->can('manage-expenses')) {
            abort(403, 'Unauthorized to download expense attachments.');
        }

        if (!$expenseSchedule->hasPaymentAttachment()) {
            abort(404, 'Attachment not found.');
        }

        $filePath = storage_path('app/private/' . $expenseSchedule->payment_attachment_path);

        if (!file_exists($filePath)) {
            abort(404, 'File not found.');
        }

        return response()->download($filePath, $expenseSchedule->payment_attachment_original_name);
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
