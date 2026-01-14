<?php

use Illuminate\Support\Facades\Route;
use Modules\Accounting\Http\Controllers\AccountingController;
use Modules\Accounting\Http\Controllers\ExpenseController;
use Modules\Accounting\Http\Controllers\ExpenseTypeController;
use Modules\Accounting\Http\Controllers\IncomeController;
use Modules\Accounting\Http\Controllers\IncomeSheetController;
use Modules\Accounting\Http\Controllers\AccountController;
use Modules\Accounting\Http\Controllers\EstimateController;
use Modules\Accounting\Http\Controllers\CreditNoteController;
use Modules\Accounting\Http\Controllers\ExpenseImportController;
use Modules\Accounting\Http\Controllers\BudgetController;

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

            // Attachment management
            Route::post('/{expenseSchedule}/attachments', [ExpenseController::class, 'uploadAttachment'])->name('attachments.upload');
            Route::delete('/{expenseSchedule}/attachments/{attachment}', [ExpenseController::class, 'deleteAttachment'])->name('attachments.delete');
            Route::get('/{expenseSchedule}/attachments/{attachment}/download', [ExpenseController::class, 'downloadAttachment'])->name('attachments.download');
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
            Route::get('/product/{product}', [IncomeSheetController::class, 'productDetail'])->name('product');
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
                Route::get('/next-number', [IncomeController::class, 'getNextContractNumber'])->name('next-number');
                Route::get('/create', [IncomeController::class, 'createContract'])->name('create');
                Route::post('/', [IncomeController::class, 'storeContract'])->name('store');

                // Mass Entry routes (must come before {contract} dynamic routes)
                Route::get('/mass-entry', [IncomeController::class, 'massEntryForm'])->name('mass-entry');
                Route::post('/mass-entry/validate', [IncomeController::class, 'validateMassEntry'])->name('mass-entry.validate');
                Route::post('/mass-entry', [IncomeController::class, 'storeMassEntry'])->name('mass-entry.store');

                // Excel Import routes
                Route::get('/import', [IncomeController::class, 'importForm'])->name('import');
                Route::post('/import/preview', [IncomeController::class, 'importPreview'])->name('import.preview');
                Route::post('/import/process', [IncomeController::class, 'importProcess'])->name('import.process');

                // Link to Projects routes
                Route::get('/link-projects', [IncomeController::class, 'linkProjects'])->name('link-projects');
                Route::post('/link-projects', [IncomeController::class, 'updateProjectLinks'])->name('update-project-links');

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

                // Payment-Invoice linking routes
                Route::post('/{contract}/payments/{payment}/generate-invoice', [IncomeController::class, 'generateInvoiceFromPayment'])->name('payments.generate-invoice');
                Route::post('/{contract}/payments/{payment}/link-invoice', [IncomeController::class, 'linkPaymentToInvoice'])->name('payments.link-invoice');
                Route::delete('/{contract}/payments/{payment}/unlink-invoice', [IncomeController::class, 'unlinkPaymentFromInvoice'])->name('payments.unlink-invoice');
                Route::post('/{contract}/payments/{payment}/record-payment', [IncomeController::class, 'recordPaymentWithoutInvoice'])->name('payments.record-payment');
                Route::get('/{contract}/available-invoices', [IncomeController::class, 'getAvailableInvoices'])->name('available-invoices');
                Route::post('/{contract}/sync-payment-statuses', [IncomeController::class, 'syncPaymentStatuses'])->name('sync-payment-statuses');

                // Project Revenue Sync route
                Route::post('/{contract}/sync-to-projects', [IncomeController::class, 'syncContractToProjects'])->name('sync-to-projects');

            });
        });

        // Estimates Management Routes
        Route::prefix('estimates')->name('estimates.')->group(function () {
            Route::get('/', [EstimateController::class, 'index'])->name('index');
            Route::get('/create', [EstimateController::class, 'create'])->name('create');
            Route::post('/', [EstimateController::class, 'store'])->name('store');
            Route::get('/{estimate}', [EstimateController::class, 'show'])->name('show');
            Route::get('/{estimate}/edit', [EstimateController::class, 'edit'])->name('edit');
            Route::put('/{estimate}', [EstimateController::class, 'update'])->name('update');
            Route::delete('/{estimate}', [EstimateController::class, 'destroy'])->name('destroy');
            Route::post('/{estimate}/duplicate', [EstimateController::class, 'duplicate'])->name('duplicate');
            Route::post('/{estimate}/send', [EstimateController::class, 'markAsSent'])->name('send');
            Route::post('/{estimate}/approve', [EstimateController::class, 'markAsApproved'])->name('approve');
            Route::post('/{estimate}/reject', [EstimateController::class, 'markAsRejected'])->name('reject');
            Route::post('/{estimate}/convert', [EstimateController::class, 'convertToContract'])->name('convert');
            Route::post('/{estimate}/convert-with-project', [EstimateController::class, 'convertToContractWithProject'])->name('convert-with-project');
            Route::get('/{estimate}/pdf', [EstimateController::class, 'exportPdf'])->name('pdf');
        });

        // Credit Notes Management Routes
        Route::prefix('credit-notes')->name('credit-notes.')->group(function () {
            Route::get('/', [CreditNoteController::class, 'index'])->name('index');
            Route::get('/create', [CreditNoteController::class, 'create'])->name('create');
            Route::post('/', [CreditNoteController::class, 'store'])->name('store');
            Route::get('/customer-invoices', [CreditNoteController::class, 'getCustomerInvoices'])->name('customer-invoices');
            Route::get('/next-number', [CreditNoteController::class, 'getNextCreditNoteNumber'])->name('next-number');
            Route::get('/{creditNote}', [CreditNoteController::class, 'show'])->name('show');
            Route::get('/{creditNote}/edit', [CreditNoteController::class, 'edit'])->name('edit');
            Route::put('/{creditNote}', [CreditNoteController::class, 'update'])->name('update');
            Route::delete('/{creditNote}', [CreditNoteController::class, 'destroy'])->name('destroy');
            Route::post('/{creditNote}/open', [CreditNoteController::class, 'markAsOpen'])->name('open');
            Route::post('/{creditNote}/void', [CreditNoteController::class, 'markAsVoid'])->name('void');
            Route::post('/{creditNote}/apply', [CreditNoteController::class, 'applyToInvoice'])->name('apply');
            Route::get('/{creditNote}/pdf', [CreditNoteController::class, 'exportPdf'])->name('pdf');
        });

        // Expense Import Routes
        Route::prefix('expense-imports')->name('expense-imports.')->group(function () {
            Route::get('/', [ExpenseImportController::class, 'index'])->name('index');
            Route::get('/create', [ExpenseImportController::class, 'create'])->name('create');
            Route::post('/', [ExpenseImportController::class, 'store'])->name('store');
            Route::get('/search-invoices', [ExpenseImportController::class, 'searchInvoices'])->name('search-invoices');
            Route::get('/{expenseImport}', [ExpenseImportController::class, 'show'])->name('show');
            Route::delete('/{expenseImport}', [ExpenseImportController::class, 'destroy'])->name('destroy');
            Route::get('/{expenseImport}/preview', [ExpenseImportController::class, 'preview'])->name('preview');
            Route::post('/{expenseImport}/execute', [ExpenseImportController::class, 'execute'])->name('execute');
            Route::post('/{expenseImport}/bulk-update', [ExpenseImportController::class, 'bulkUpdate'])->name('bulk-update');
            Route::post('/{expenseImport}/map-value', [ExpenseImportController::class, 'mapValue'])->name('map-value');
        });

        // Expense Import Row Routes (separate for AJAX)
        Route::patch('/expense-imports/rows/{expenseImportRow}', [ExpenseImportController::class, 'updateRow'])
            ->name('expense-imports.rows.update');

        // Budget Planning Routes
        Route::prefix('budgets')->name('budgets.')->group(function () {
            Route::get('/', [BudgetController::class, 'index'])->name('index');
            Route::get('/create', [BudgetController::class, 'create'])->name('create');
            Route::post('/', [BudgetController::class, 'store'])->name('store');
            Route::delete('/{budget}', [BudgetController::class, 'destroy'])->name('destroy');

            // Budget Tab Routes
            Route::get('/{budget}/growth', [BudgetController::class, 'growth'])->name('growth');
            Route::post('/{budget}/growth', [BudgetController::class, 'updateGrowth'])->name('growth.update');
            Route::post('/{budget}/growth/calculate-trendline', [BudgetController::class, 'calculateTrendline'])->name('growth.calculate-trendline');
            Route::post('/{budget}/growth/populate-historical', [BudgetController::class, 'populateHistoricalData'])->name('growth.populate-historical');

            Route::get('/{budget}/capacity', [BudgetController::class, 'capacity'])->name('capacity');
            Route::post('/{budget}/capacity', [BudgetController::class, 'updateCapacity'])->name('capacity.update');
            Route::post('/{budget}/capacity/hires', [BudgetController::class, 'addHire'])->name('capacity.hires.add');
            Route::delete('/{budget}/capacity/hires/{hire}', [BudgetController::class, 'deleteHire'])->name('capacity.hires.delete');
            Route::post('/{budget}/capacity/calculate', [BudgetController::class, 'calculateCapacityIncome'])->name('capacity.calculate');

            // Collection Tab
            Route::get('/{budget}/collection', [BudgetController::class, 'collection'])->name('collection');
            Route::post('/{budget}/collection', [BudgetController::class, 'updateCollection'])->name('collection.update');
            Route::post('/{budget}/collection/populate', [BudgetController::class, 'populateCollectionData'])->name('collection.populate');
            Route::post('/{budget}/collection/patterns', [BudgetController::class, 'addPattern'])->name('collection.patterns.add');
            Route::put('/{budget}/collection/patterns/{pattern}', [BudgetController::class, 'updatePattern'])->name('collection.patterns.update');
            Route::delete('/{budget}/collection/patterns/{pattern}', [BudgetController::class, 'deletePattern'])->name('collection.patterns.delete');
            Route::post('/{budget}/collection/calculate', [BudgetController::class, 'calculateCollectionIncome'])->name('collection.calculate');

            // Result Tab
            Route::get('/{budget}/result', [BudgetController::class, 'result'])->name('result');
            Route::post('/{budget}/result', [BudgetController::class, 'updateResult'])->name('result.update');
        });
    });
});