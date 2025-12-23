<?php

use Illuminate\Support\Facades\Route;
use Modules\Accounting\Http\Controllers\AccountingController;
use Modules\Accounting\Http\Controllers\ExpenseController;
use Modules\Accounting\Http\Controllers\ExpenseTypeController;
use Modules\Accounting\Http\Controllers\IncomeController;
use Modules\Accounting\Http\Controllers\IncomeSheetController;
use Modules\Accounting\Http\Controllers\AccountController;

Route::middleware(['auth', 'verified'])->group(function () {

    // Main Accounting Routes
    Route::prefix('accounting')->name('accounting.')->group(function () {

        // Dashboard
        Route::get('/', [AccountingController::class, 'index'])->name('dashboard');
        Route::match(['GET', 'POST'], '/reports', [AccountingController::class, 'reports'])->name('reports');

        // I&E Report
        Route::get('/reports/income-expenses', [ExpenseController::class, 'incomeExpensesReport'])->name('reports.income-expenses');

        // Expense Management Routes
        Route::prefix('expenses')->name('expenses.')->group(function () {
            Route::get('/', [ExpenseController::class, 'index'])->name('index');
            Route::get('/paid', [ExpenseController::class, 'paidExpenses'])->name('paid');
            Route::get('/create', [ExpenseController::class, 'create'])->name('create');
            Route::post('/', [ExpenseController::class, 'store'])->name('store');

            // CSV Import routes (must come before dynamic routes)
            Route::get('/import', [ExpenseController::class, 'importForm'])->name('import');
            Route::post('/import', [ExpenseController::class, 'import'])->name('import.process');
            Route::get('/import/sample', [ExpenseController::class, 'downloadSample'])->name('import.sample');

            // Category management
            Route::get('/categories/manage', [ExpenseController::class, 'categories'])->name('categories');
            Route::post('/categories', [ExpenseController::class, 'storeCategory'])->name('categories.store');
            Route::put('/categories/{category}', [ExpenseController::class, 'updateCategory'])->name('categories.update');
            Route::delete('/categories/{category}', [ExpenseController::class, 'destroyCategory'])->name('categories.destroy');
            Route::patch('/categories/{category}/toggle-status', [ExpenseController::class, 'toggleCategoryStatus'])->name('categories.toggle-status');

            // Category Budget management
            Route::get('/categories/budgets', [ExpenseController::class, 'categoryBudgets'])->name('categories.budgets');
            Route::post('/categories/budgets/copy-from-year', [ExpenseController::class, 'copyBudgetsFromYear'])->name('categories.budgets.copy');
            Route::post('/categories/{category}/budgets', [ExpenseController::class, 'storeCategoryBudget'])->name('categories.budgets.store');
            Route::put('/categories/{category}/budgets/{budget}', [ExpenseController::class, 'updateCategoryBudget'])->name('categories.budgets.update');
            Route::delete('/categories/{category}/budgets/{budget}', [ExpenseController::class, 'destroyCategoryBudget'])->name('categories.budgets.destroy');

            // Category CSV Import routes
            Route::get('/categories/import', [ExpenseController::class, 'importCategoriesForm'])->name('categories.import');
            Route::post('/categories/import', [ExpenseController::class, 'importCategories'])->name('categories.import.process');
            Route::get('/categories/import/sample', [ExpenseController::class, 'downloadCategoriesSample'])->name('categories.import.sample');

            // Dynamic expense routes (must come after specific routes)
            Route::get('/{expenseSchedule}', [ExpenseController::class, 'show'])->name('show');
            Route::get('/{expenseSchedule}/edit', [ExpenseController::class, 'edit'])->name('edit');
            Route::put('/{expenseSchedule}', [ExpenseController::class, 'update'])->name('update');
            Route::delete('/{expenseSchedule}', [ExpenseController::class, 'destroy'])->name('destroy');

            // Additional expense actions
            Route::patch('/{expenseSchedule}/toggle-status', [ExpenseController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{expenseSchedule}/mark-as-paid', [ExpenseController::class, 'markAsPaid'])->name('mark-as-paid');
            Route::get('/{expenseSchedule}/payment-attachment', [ExpenseController::class, 'downloadPaymentAttachment'])->name('payment-attachment');
            Route::post('/bulk-action', [ExpenseController::class, 'bulkAction'])->name('bulk-action');
        });

        // Expense Type Management Routes
        Route::prefix('expense-types')->name('expense-types.')->group(function () {
            Route::get('/', [ExpenseTypeController::class, 'index'])->name('index');
            Route::post('/', [ExpenseTypeController::class, 'store'])->name('store');
            Route::put('/{expenseType}', [ExpenseTypeController::class, 'update'])->name('update');
            Route::delete('/{expenseType}', [ExpenseTypeController::class, 'destroy'])->name('destroy');
            Route::patch('/{expenseType}/toggle-status', [ExpenseTypeController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/update-sort-order', [ExpenseTypeController::class, 'updateSortOrder'])->name('update-sort-order');
        });

        // Income Sheet Routes
        Route::prefix('income-sheet')->name('income-sheet.')->group(function () {
            Route::get('/', [IncomeSheetController::class, 'index'])->name('index');
            Route::get('/export', [IncomeSheetController::class, 'export'])->name('export');
        });

        // Account Management Routes
        Route::prefix('accounts')->name('accounts.')->group(function () {
            Route::get('/', [AccountController::class, 'index'])->name('index');
            Route::get('/create', [AccountController::class, 'create'])->name('create');
            Route::post('/', [AccountController::class, 'store'])->name('store');
            Route::get('/{account}', [AccountController::class, 'show'])->name('show');
            Route::get('/{account}/edit', [AccountController::class, 'edit'])->name('edit');
            Route::put('/{account}', [AccountController::class, 'update'])->name('update');
            Route::delete('/{account}', [AccountController::class, 'destroy'])->name('destroy');
            Route::patch('/{account}/toggle-status', [AccountController::class, 'toggleStatus'])->name('toggle-status');
        });

        // Income & Contract Management Routes
        Route::prefix('income')->name('income.')->group(function () {
            Route::get('/', [IncomeController::class, 'index'])->name('index');

            // Contract routes
            Route::prefix('contracts')->name('contracts.')->group(function () {
                Route::get('/', [IncomeController::class, 'contracts'])->name('index');
                Route::get('/create', [IncomeController::class, 'createContract'])->name('create');
                Route::post('/', [IncomeController::class, 'storeContract'])->name('store');
                Route::get('/{contract}', [IncomeController::class, 'showContract'])->name('show');
                Route::get('/{contract}/edit', [IncomeController::class, 'editContract'])->name('edit');
                Route::put('/{contract}', [IncomeController::class, 'updateContract'])->name('update');
                Route::delete('/{contract}', [IncomeController::class, 'destroyContract'])->name('destroy');
                Route::patch('/{contract}/toggle-status', [IncomeController::class, 'toggleContractStatus'])->name('toggle-status');
                Route::post('/bulk-action', [IncomeController::class, 'bulkContractAction'])->name('bulk-action');

                // Payment routes
                Route::post('/{contract}/payments', [IncomeController::class, 'addPayment'])->name('payments.store');
                Route::post('/{contract}/recurring-payments', [IncomeController::class, 'generateRecurringPayments'])->name('recurring-payments.generate');
                Route::patch('/{contract}/payments/{payment}/status', [IncomeController::class, 'updatePaymentStatus'])->name('payments.update-status');
                Route::delete('/{contract}/payments/{payment}', [IncomeController::class, 'deletePayment'])->name('payments.destroy');

                // CSV Import routes for contracts
                Route::get('/import', [IncomeController::class, 'importForm'])->name('import');
                Route::post('/import', [IncomeController::class, 'import'])->name('import.process');
                Route::get('/import/sample', [IncomeController::class, 'downloadSample'])->name('import.sample');

            });
        });
    });
});