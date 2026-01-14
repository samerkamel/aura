# Budget Feature Implementation Plan

## Overview
Comprehensive budget planning system with 9 interconnected tabs, historical data analysis, and system integration.

---

## Phase 1: Database Schema & Models

### 1.1 Core Budget Table
```
budgets
├── id
├── year (int, unique)
├── status (enum: draft, finalized)
├── opex_global_increase (decimal, default 10%)
├── tax_global_increase (decimal, default 10%)
├── created_at
├── updated_at
├── finalized_at (nullable)
├── finalized_by (user_id, nullable)
```

### 1.2 Growth Tab Tables
```
budget_growth_entries
├── id
├── budget_id (FK)
├── product_id (FK)
├── year_minus_3 (decimal) -- calculated from invoices/payments
├── year_minus_2 (decimal)
├── year_minus_1 (decimal)
├── trendline_type (enum: linear, logarithmic, polynomial)
├── polynomial_order (int, nullable)
├── budgeted_value (decimal) -- user input
├── created_at, updated_at
```

### 1.3 Capacity Tab Tables
```
budget_capacity_entries
├── id
├── budget_id (FK)
├── product_id (FK)
├── last_year_headcount (int) -- calculated
├── last_year_available_hours (decimal) -- calculated
├── last_year_avg_hourly_price (decimal) -- calculated
├── last_year_income (decimal) -- calculated
├── last_year_billable_hours (decimal) -- calculated
├── last_year_billable_pct (decimal) -- calculated
├── next_year_headcount (int) -- user input (base)
├── next_year_avg_hourly_price (decimal) -- user input
├── next_year_billable_pct (decimal) -- user input
├── budgeted_income (decimal) -- calculated
├── created_at, updated_at

budget_capacity_hires
├── id
├── budget_capacity_entry_id (FK)
├── hire_month (int, 1-12)
├── hire_count (int)
├── created_at, updated_at
```

### 1.4 Collection Tab Tables
```
budget_collection_entries
├── id
├── budget_id (FK)
├── product_id (FK)
├── beginning_balance (decimal) -- calculated
├── end_balance (decimal) -- calculated
├── avg_balance (decimal) -- calculated
├── avg_contract_per_month (decimal) -- calculated
├── avg_payment_per_month (decimal) -- calculated
├── last_year_collection_months (decimal) -- calculated
├── budgeted_collection_months (decimal) -- calculated from patterns
├── projected_collection_months (decimal) -- average of above two
├── budgeted_income (decimal) -- calculated
├── created_at, updated_at

budget_collection_patterns
├── id
├── budget_collection_entry_id (FK)
├── pattern_name (string)
├── contract_percentage (decimal) -- % of contracts using this pattern
├── month_1_pct (decimal)
├── month_2_pct (decimal)
├── ... month_12_pct (decimal)
├── created_at, updated_at
```

### 1.5 Result Tab Table
```
budget_result_entries
├── id
├── budget_id (FK)
├── product_id (FK)
├── growth_value (decimal) -- from growth tab
├── capacity_value (decimal) -- from capacity tab
├── collection_value (decimal) -- from collection tab
├── average_value (decimal) -- calculated
├── final_value (decimal) -- user input
├── created_at, updated_at
```

### 1.6 Personnel Tab Tables
```
budget_personnel_entries
├── id
├── budget_id (FK)
├── employee_id (FK)
├── current_salary (decimal) -- from employee record
├── proposed_salary (decimal) -- user input
├── increase_percentage (decimal) -- calculated
├── is_new_hire (boolean) -- from capacity tab
├── hire_month (int, nullable) -- if new hire
├── created_at, updated_at

budget_personnel_allocations
├── id
├── budget_personnel_entry_id (FK)
├── product_id (FK, nullable) -- null = G&A
├── allocation_percentage (decimal)
├── created_at, updated_at
```

### 1.7 OpEx/Taxes/CapEx Tables
```
budget_expense_entries
├── id
├── budget_id (FK)
├── expense_category_id (FK)
├── type (enum: opex, tax, capex)
├── last_year_total (decimal) -- calculated
├── last_year_avg_monthly (decimal) -- calculated
├── increase_percentage (decimal, nullable) -- user override
├── proposed_amount (decimal, nullable) -- user override
├── proposed_total (decimal) -- calculated or override
├── is_override (boolean)
├── created_at, updated_at
```

### 1.8 Migrations Order
1. `create_budgets_table`
2. `create_budget_growth_entries_table`
3. `create_budget_capacity_entries_table`
4. `create_budget_capacity_hires_table`
5. `create_budget_collection_entries_table`
6. `create_budget_collection_patterns_table`
7. `create_budget_result_entries_table`
8. `create_budget_personnel_entries_table`
9. `create_budget_personnel_allocations_table`
10. `create_budget_expense_entries_table`

---

## Phase 2: Service Layer

### 2.1 Data Retrieval Services

**HistoricalIncomeService**
```php
- getIncomeByProductAndYear(productId, year)
- getIncomeByProduct(productId, fromYear, toYear)
- getAllProductsIncome(fromYear, toYear)
- Source: Invoices + Contract Payments without invoices
```

**BalanceService**
```php
- getOutstandingBalanceAtDate(productId, date)
- getBeginningBalance(productId, year)
- getEndBalance(productId, year)
```

**EmployeeDataService**
```php
- getDevelopersByProduct(productId)
- getHeadcountByProduct(productId, year)
- getAverageHourlyRate(productId)
- getWorkingDaysInYear(year) -- excluding holidays/weekends
```

**ExpenseDataService**
```php
- getExpensesByCategory(categoryId, year)
- getExpensesByType(type, year) -- opex, tax, capex
- getAverageMonthlyExpense(categoryId, year)
```

### 2.2 Calculation Services

**MissingMonthCompensationService**
```php
- getElapsedMonths(date) -- returns decimal (e.g., 10.5)
- extrapolateToFullYear(ytdTotal, elapsedMonths)
- isPartialYear(year) -- check if current year
```

**GrowthCalculationService**
```php
- calculateLinearTrend(dataPoints)
- calculateLogarithmicTrend(dataPoints)
- calculatePolynomialTrend(dataPoints, order)
- projectValue(trendType, dataPoints, targetYear)
```

**CapacityCalculationService**
```php
- calculateAvailableHours(year) -- 5hrs × workdays / 12
- calculateBillableHours(income, headcount, hourlyRate)
- calculateBillablePercentage(billableHours, availableHours)
- calculateBudgetedIncome(headcount, availableHours, hourlyRate, billablePct)
- calculateWeightedHeadcount(baseCount, hires[]) -- accounts for hire months
```

**CollectionCalculationService**
```php
- calculateCollectionMonths(avgBalance, avgPayment)
- calculateBudgetedCollectionMonths(patterns[])
- calculateProjectedCollectionMonths(lastYear, budgeted)
- calculateBudgetedIncome(endBalance, projectedMonths)
```

**PersonnelCalculationService**
```php
- calculateIncreasePercentage(current, proposed)
- calculateEffectiveCost(salary, allocationPct)
- calculateTotalByProduct(personnelEntries, productId)
- calculateTotalGA(personnelEntries)
```

### 2.3 Budget Management Services

**BudgetService**
```php
- createBudget(year)
- getBudget(year)
- initializeFromHistoricalData(budget)
- saveDraft(budget)
- canEdit(budget) -- check FY status
```

**BudgetFinalizationService**
```php
- validateBudget(budget) -- all required fields filled
- previewChanges(budget) -- what will be updated
- finalize(budget)
- updateProductTargets(budget)
- updateEmployeeSalaries(budget)
- updateExpenseCategoryBudgets(budget)
- createAuditLog(budget, changes)
```

---

## Phase 3: Controllers & Routes

### 3.1 Route Structure
```php
Route::prefix('financial-planning/budget')->name('budget.')->group(function () {
    // Main budget management
    Route::get('/', [BudgetController::class, 'index'])->name('index');
    Route::get('/create', [BudgetController::class, 'create'])->name('create');
    Route::post('/', [BudgetController::class, 'store'])->name('store');
    Route::get('/{budget}', [BudgetController::class, 'show'])->name('show');

    // Tab-specific routes (AJAX)
    Route::prefix('/{budget}')->group(function () {
        // Growth
        Route::get('/growth', [BudgetGrowthController::class, 'index']);
        Route::post('/growth', [BudgetGrowthController::class, 'save']);
        Route::get('/growth/chart/{product}', [BudgetGrowthController::class, 'chartData']);

        // Capacity
        Route::get('/capacity', [BudgetCapacityController::class, 'index']);
        Route::post('/capacity', [BudgetCapacityController::class, 'save']);
        Route::post('/capacity/hires', [BudgetCapacityController::class, 'saveHires']);

        // Collection
        Route::get('/collection', [BudgetCollectionController::class, 'index']);
        Route::post('/collection', [BudgetCollectionController::class, 'save']);
        Route::post('/collection/patterns', [BudgetCollectionController::class, 'savePatterns']);

        // Result
        Route::get('/result', [BudgetResultController::class, 'index']);
        Route::post('/result', [BudgetResultController::class, 'save']);

        // Personnel
        Route::get('/personnel', [BudgetPersonnelController::class, 'index']);
        Route::post('/personnel', [BudgetPersonnelController::class, 'save']);
        Route::post('/personnel/allocations', [BudgetPersonnelController::class, 'saveAllocations']);

        // OpEx
        Route::get('/opex', [BudgetOpExController::class, 'index']);
        Route::post('/opex', [BudgetOpExController::class, 'save']);
        Route::post('/opex/global-increase', [BudgetOpExController::class, 'setGlobalIncrease']);

        // Taxes
        Route::get('/taxes', [BudgetTaxesController::class, 'index']);
        Route::post('/taxes', [BudgetTaxesController::class, 'save']);
        Route::post('/taxes/global-increase', [BudgetTaxesController::class, 'setGlobalIncrease']);

        // CapEx
        Route::get('/capex', [BudgetCapExController::class, 'index']);
        Route::post('/capex', [BudgetCapExController::class, 'save']);

        // P&L
        Route::get('/pnl', [BudgetPnLController::class, 'index']);

        // Finalization
        Route::get('/finalize/preview', [BudgetFinalizationController::class, 'preview']);
        Route::post('/finalize', [BudgetFinalizationController::class, 'finalize']);
    });
});
```

### 3.2 Controllers
```
Modules/Accounting/app/Http/Controllers/Budget/
├── BudgetController.php
├── BudgetGrowthController.php
├── BudgetCapacityController.php
├── BudgetCollectionController.php
├── BudgetResultController.php
├── BudgetPersonnelController.php
├── BudgetOpExController.php
├── BudgetTaxesController.php
├── BudgetCapExController.php
├── BudgetPnLController.php
└── BudgetFinalizationController.php
```

---

## Phase 4: Frontend/Views

### 4.1 View Structure
```
Modules/Accounting/resources/views/budget/
├── index.blade.php          -- Budget list/selection
├── show.blade.php           -- Main tabbed interface
├── partials/
│   ├── tabs/
│   │   ├── growth.blade.php
│   │   ├── capacity.blade.php
│   │   ├── collection.blade.php
│   │   ├── result.blade.php
│   │   ├── personnel.blade.php
│   │   ├── opex.blade.php
│   │   ├── taxes.blade.php
│   │   ├── capex.blade.php
│   │   └── pnl.blade.php
│   ├── modals/
│   │   ├── hiring-plan.blade.php
│   │   ├── collection-pattern.blade.php
│   │   ├── employee-allocation.blade.php
│   │   └── finalization-preview.blade.php
│   └── components/
│       ├── trendline-chart.blade.php
│       └── editable-table.blade.php
```

### 4.2 JavaScript Requirements
- **Chart.js** - For Growth trendline charts
- **Regression.js** or custom - For trendline calculations (linear, log, polynomial)
- **Tab state management** - Track unsaved changes
- **Cross-tab updates** - Recalculate dependent values
- **Auto-save** - Optional periodic save

### 4.3 UI Components
1. **Year Selector** - Dropdown at top to switch budget years
2. **Save Button** - Persistent save button with unsaved changes indicator
3. **Tab Navigation** - Bootstrap tabs with badges showing completion status
4. **Editable Tables** - Inline editing with validation
5. **Charts** - Interactive trendline charts with type selector
6. **Modals** - For complex inputs (hiring plan, patterns)

---

## Phase 5: Implementation Order

### Sprint 1: Foundation (Week 1-2)
- [ ] Create all migrations
- [ ] Create Eloquent models with relationships
- [ ] Create base BudgetController (index, create, store, show)
- [ ] Create main tabbed view structure
- [ ] Add menu item and routes

### Sprint 2: Data Services (Week 2-3)
- [ ] HistoricalIncomeService
- [ ] BalanceService
- [ ] EmployeeDataService
- [ ] ExpenseDataService
- [ ] MissingMonthCompensationService

### Sprint 3: Growth Tab (Week 3-4)
- [ ] BudgetGrowthController
- [ ] Growth calculation service
- [ ] Growth view with historical table
- [ ] Chart.js integration with trendlines
- [ ] Save functionality

### Sprint 4: Capacity Tab (Week 4-5)
- [ ] BudgetCapacityController
- [ ] Capacity calculation service
- [ ] Last year table (read-only)
- [ ] Next year table (editable)
- [ ] Hiring plan modal
- [ ] Bidirectional sync with Personnel

### Sprint 5: Collection Tab (Week 5-6)
- [ ] BudgetCollectionController
- [ ] Collection calculation service
- [ ] Last year analysis table
- [ ] Payment pattern configuration
- [ ] Collection months calculation

### Sprint 6: Result Tab (Week 6)
- [ ] BudgetResultController
- [ ] Aggregation from Growth/Capacity/Collection
- [ ] Comparison table
- [ ] Final value input

### Sprint 7: Personnel Tab (Week 7-8)
- [ ] BudgetPersonnelController
- [ ] Personnel calculation service
- [ ] Product sections with employee tables
- [ ] G&A section
- [ ] Allocation modal
- [ ] Sync with Capacity hires
- [ ] Totals calculation

### Sprint 8: OpEx/Taxes/CapEx Tabs (Week 8-9)
- [ ] BudgetOpExController
- [ ] BudgetTaxesController
- [ ] BudgetCapExController
- [ ] Global increase setting
- [ ] Override functionality
- [ ] Category tables

### Sprint 9: P&L Tab (Week 9-10)
- [ ] BudgetPnLController
- [ ] P&L aggregation logic
- [ ] Summary view
- [ ] Comparison columns

### Sprint 10: Finalization & Polish (Week 10-11)
- [ ] BudgetFinalizationService
- [ ] Preview modal
- [ ] System update jobs
- [ ] Audit logging
- [ ] Permissions implementation
- [ ] View-only mode for Finance role
- [ ] Budget locking for past FY

### Sprint 11: Testing & QA (Week 11-12)
- [ ] Unit tests for all calculation services
- [ ] Feature tests for each controller
- [ ] Integration tests for finalization
- [ ] UI testing with Playwright
- [ ] Bug fixes and refinements

---

## Phase 6: Dependencies & Prerequisites

### Check/Create Before Implementation
1. **Products table** - Ensure `yearly_target` field exists
2. **Employees table** - Ensure salary fields and product assignments exist
3. **Expense Categories** - Ensure `type` field distinguishes OpEx/Tax/CapEx
4. **Holidays** - System needs holiday data for work days calculation
5. **Financial Year Settings** - Verify FY configuration exists

### External Libraries
- Chart.js (likely already installed)
- Regression.js or similar for trendline calculations

---

## Phase 7: Risk Mitigation

### Potential Challenges
1. **Complex calculations** - Extensive unit testing required
2. **Cross-tab dependencies** - Careful state management needed
3. **Large data sets** - May need pagination/lazy loading
4. **Historical data gaps** - Handle missing data gracefully

### Mitigation Strategies
1. Build calculation services with comprehensive test coverage first
2. Use events/observers for cross-tab updates
3. Implement efficient queries with proper indexing
4. Default to 0 for missing data with visual indicators

---

## Estimated Timeline
- **Total Duration**: 10-12 weeks
- **MVP (Growth + Capacity + Result + P&L)**: 6 weeks
- **Full Feature**: 10-12 weeks

---

*Document created: January 2025*
*Status: Implementation Plan Complete*
