# Budget Feature - Detailed Implementation Guide

## Table of Contents
1. [Prerequisites & Setup](#phase-0-prerequisites--setup)
2. [Database Schema](#phase-1-database-schema--models)
3. [Service Layer](#phase-2-service-layer)
4. [Growth Tab](#phase-3-growth-tab)
5. [Capacity Tab](#phase-4-capacity-tab)
6. [Collection Tab](#phase-5-collection-tab)
7. [Result Tab](#phase-6-result-tab)
8. [Personnel Tab](#phase-7-personnel-tab)
9. [OpEx/Taxes/CapEx Tabs](#phase-8-opextaxescapex-tabs)
10. [P&L Tab](#phase-9-pl-tab)
11. [Finalization](#phase-10-finalization)
12. [Testing](#phase-11-testing)

---

## Phase 0: Prerequisites & Setup

### 0.1 Verify Existing System Components

Before starting, verify these exist in the system:

**Check Products Table:**
```bash
php artisan tinker
>>> Schema::getColumnListing('products')
```
Ensure there's a field for yearly budget/target. If not, create a migration:
```bash
php artisan make:migration add_yearly_budget_to_products_table
```

**Check Employee Product Assignments:**
Look at `Modules/HR/app/Models/Employee.php` for product relationship.
```php
// Expected relationship
public function products() { ... }
// Or
public function primaryProduct() { ... }
```

**Check Expense Categories:**
```bash
php artisan tinker
>>> \App\Models\ExpenseCategory::pluck('type', 'name')
```
Ensure categories have a `type` field distinguishing: `opex`, `tax`, `capex`

**Check Financial Year Settings:**
Look for settings in:
- `config/settings.php`
- `settings` database table
- Or dedicated `financial_years` table

### 0.2 Create Module Structure

All budget code will live in the Accounting module:

```bash
# Create directories
mkdir -p Modules/Accounting/app/Http/Controllers/Budget
mkdir -p Modules/Accounting/app/Services/Budget
mkdir -p Modules/Accounting/app/Models/Budget
mkdir -p Modules/Accounting/resources/views/budget
mkdir -p Modules/Accounting/resources/views/budget/partials/tabs
mkdir -p Modules/Accounting/resources/views/budget/partials/modals
```

### 0.3 Add Menu Item

Edit `Modules/Accounting/resources/views/partials/sidebar.blade.php` or equivalent:
```php
// Find "Financial Planning" section and add:
<li class="menu-item {{ request()->routeIs('accounting.budget.*') ? 'active' : '' }}">
    <a href="{{ route('accounting.budget.index') }}" class="menu-link">
        <i class="menu-icon ti ti-calculator"></i>
        <div>Budget Planning</div>
    </a>
</li>
```

---

## Phase 1: Database Schema & Models

### 1.1 Main Budget Table

**Create Migration:**
```bash
php artisan make:migration create_budgets_table --path=Modules/Accounting/database/migrations
```

**Migration File:** `Modules/Accounting/database/migrations/xxxx_create_budgets_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();

            // Year this budget is for (e.g., 2027)
            $table->year('budget_year')->unique();

            // Draft = still editing, Finalized = locked and applied to system
            $table->enum('status', ['draft', 'finalized'])->default('draft');

            // Global increase percentages for OpEx and Taxes
            // These are default values that apply to all categories
            // Individual categories can override
            $table->decimal('opex_global_increase_pct', 5, 2)->default(10.00);
            $table->decimal('tax_global_increase_pct', 5, 2)->default(10.00);

            // When creating budget before year end, store the cutoff date
            // This is used for missing month compensation
            // Example: If created Nov 15, 2026, store 2026-11-15
            $table->date('data_cutoff_date')->nullable();

            // Finalization tracking
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Index for quick year lookups
            $table->index('budget_year');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
```

**Model:** `Modules/Accounting/app/Models/Budget/Budget.php`
```php
<?php

namespace Modules\Accounting\Models\Budget;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Budget extends Model
{
    protected $fillable = [
        'budget_year',
        'status',
        'opex_global_increase_pct',
        'tax_global_increase_pct',
        'data_cutoff_date',
        'finalized_at',
        'finalized_by',
    ];

    protected $casts = [
        'budget_year' => 'integer',
        'opex_global_increase_pct' => 'decimal:2',
        'tax_global_increase_pct' => 'decimal:2',
        'data_cutoff_date' => 'date',
        'finalized_at' => 'datetime',
    ];

    // ===== RELATIONSHIPS =====

    public function finalizedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function growthEntries(): HasMany
    {
        return $this->hasMany(BudgetGrowthEntry::class);
    }

    public function capacityEntries(): HasMany
    {
        return $this->hasMany(BudgetCapacityEntry::class);
    }

    public function collectionEntries(): HasMany
    {
        return $this->hasMany(BudgetCollectionEntry::class);
    }

    public function resultEntries(): HasMany
    {
        return $this->hasMany(BudgetResultEntry::class);
    }

    public function personnelEntries(): HasMany
    {
        return $this->hasMany(BudgetPersonnelEntry::class);
    }

    public function expenseEntries(): HasMany
    {
        return $this->hasMany(BudgetExpenseEntry::class);
    }

    // ===== HELPER METHODS =====

    /**
     * Check if this budget can be edited.
     * Budgets can only be edited if:
     * 1. Status is 'draft', OR
     * 2. Status is 'finalized' but we're still in the budget year
     */
    public function canEdit(): bool
    {
        if ($this->status === 'draft') {
            return true;
        }

        // Get current financial year from settings
        $currentFY = $this->getCurrentFinancialYear();

        return $this->budget_year >= $currentFY;
    }

    /**
     * Check if this budget is for a past financial year.
     */
    public function isPastYear(): bool
    {
        $currentFY = $this->getCurrentFinancialYear();
        return $this->budget_year < $currentFY;
    }

    /**
     * Get the "last year" for calculations.
     * This is the year before the budget year.
     */
    public function getLastYear(): int
    {
        return $this->budget_year - 1;
    }

    /**
     * Get elapsed months for missing month compensation.
     * Returns decimal (e.g., 10.5 for Nov 15).
     */
    public function getElapsedMonths(): float
    {
        if (!$this->data_cutoff_date) {
            return 12.0; // Full year
        }

        $month = $this->data_cutoff_date->month;
        $day = $this->data_cutoff_date->day;
        $daysInMonth = $this->data_cutoff_date->daysInMonth;

        // Calculate partial month
        $partialMonth = $day / $daysInMonth;

        return ($month - 1) + $partialMonth;
    }

    /**
     * Get current financial year based on system settings.
     * Override this based on your FY settings implementation.
     */
    protected function getCurrentFinancialYear(): int
    {
        // TODO: Get from your financial year settings
        // For now, assume calendar year
        return (int) date('Y');
    }

    // ===== SCOPES =====

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeFinalized($query)
    {
        return $query->where('status', 'finalized');
    }
}
```

### 1.2 Growth Entries Table

**Migration:**
```bash
php artisan make:migration create_budget_growth_entries_table --path=Modules/Accounting/database/migrations
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_growth_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // Historical income data (calculated from invoices/payments)
            // These are stored to avoid recalculating every time
            // year_minus_1 = last year, year_minus_2 = 2 years ago, etc.
            $table->decimal('year_minus_3_income', 15, 2)->default(0);
            $table->decimal('year_minus_2_income', 15, 2)->default(0);
            $table->decimal('year_minus_1_income', 15, 2)->default(0);

            // Trendline settings chosen by user
            $table->enum('trendline_type', ['linear', 'logarithmic', 'polynomial'])->default('linear');
            $table->unsignedTinyInteger('polynomial_order')->nullable(); // 2, 3, 4, etc.

            // The projected value from trendline calculation (for reference)
            $table->decimal('trendline_projected_value', 15, 2)->nullable();

            // User's final budgeted value for Growth method
            $table->decimal('budgeted_value', 15, 2)->nullable();

            $table->timestamps();

            // Each product can only have one entry per budget
            $table->unique(['budget_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_growth_entries');
    }
};
```

**Model:** `Modules/Accounting/app/Models/Budget/BudgetGrowthEntry.php`
```php
<?php

namespace Modules\Accounting\Models\Budget;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Accounting\Models\Product;

class BudgetGrowthEntry extends Model
{
    protected $fillable = [
        'budget_id',
        'product_id',
        'year_minus_3_income',
        'year_minus_2_income',
        'year_minus_1_income',
        'trendline_type',
        'polynomial_order',
        'trendline_projected_value',
        'budgeted_value',
    ];

    protected $casts = [
        'year_minus_3_income' => 'decimal:2',
        'year_minus_2_income' => 'decimal:2',
        'year_minus_1_income' => 'decimal:2',
        'trendline_projected_value' => 'decimal:2',
        'budgeted_value' => 'decimal:2',
        'polynomial_order' => 'integer',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get historical data as array for chart.
     * Returns: [year => income, ...]
     */
    public function getHistoricalDataForChart(): array
    {
        $budgetYear = $this->budget->budget_year;

        return [
            $budgetYear - 3 => (float) $this->year_minus_3_income,
            $budgetYear - 2 => (float) $this->year_minus_2_income,
            $budgetYear - 1 => (float) $this->year_minus_1_income,
            $budgetYear => (float) $this->budgeted_value, // The target
        ];
    }
}
```

### 1.3 Capacity Entries Tables

**Migration for main capacity entries:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_capacity_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // ===== LAST YEAR DATA (Calculated, read-only in UI) =====
            // These are snapshot values calculated when budget is created/refreshed

            // Number of developer employees assigned to this product last year
            $table->unsignedInteger('last_year_headcount')->default(0);

            // Average available hours per employee per month
            // Formula: (5 hours/day × work days in year) / 12
            $table->decimal('last_year_available_hours_monthly', 8, 2)->default(0);

            // Average hourly rate for employees in this product
            $table->decimal('last_year_avg_hourly_rate', 10, 2)->default(0);

            // Actual income for this product last year
            $table->decimal('last_year_income', 15, 2)->default(0);

            // Calculated: income / (headcount × avg_rate × 12)
            $table->decimal('last_year_billable_hours_monthly', 8, 2)->default(0);

            // Calculated: billable_hours / available_hours × 100
            $table->decimal('last_year_billable_pct', 5, 2)->default(0);

            // ===== NEXT YEAR BUDGET (User input) =====

            // Base headcount (before new hires)
            // Usually same as last_year_headcount unless employees left
            $table->unsignedInteger('next_year_base_headcount')->default(0);

            // Target average hourly rate
            $table->decimal('next_year_avg_hourly_rate', 10, 2)->nullable();

            // Target billable percentage
            $table->decimal('next_year_billable_pct', 5, 2)->nullable();

            // Calculated weighted headcount (accounts for hire months)
            $table->decimal('next_year_weighted_headcount', 8, 2)->default(0);

            // Final budgeted income from Capacity method
            // Formula: weighted_headcount × available_hours × hourly_rate × billable_pct × 12
            $table->decimal('budgeted_income', 15, 2)->nullable();

            $table->timestamps();

            $table->unique(['budget_id', 'product_id']);
        });

        // Separate table for planned hires
        Schema::create('budget_capacity_hires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_capacity_entry_id')
                  ->constrained('budget_capacity_entries')
                  ->cascadeOnDelete();

            // Month when hire is planned (1 = January, 12 = December)
            $table->unsignedTinyInteger('hire_month');

            // Number of employees to hire in this month
            $table->unsignedInteger('hire_count')->default(1);

            // Optional: Expected hourly rate for new hire
            $table->decimal('expected_hourly_rate', 10, 2)->nullable();

            $table->timestamps();

            // Can have multiple hires in same month, so no unique constraint on month
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_capacity_hires');
        Schema::dropIfExists('budget_capacity_entries');
    }
};
```

**Model:** `Modules/Accounting/app/Models/Budget/BudgetCapacityEntry.php`
```php
<?php

namespace Modules\Accounting\Models\Budget;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Accounting\Models\Product;

class BudgetCapacityEntry extends Model
{
    protected $fillable = [
        'budget_id',
        'product_id',
        'last_year_headcount',
        'last_year_available_hours_monthly',
        'last_year_avg_hourly_rate',
        'last_year_income',
        'last_year_billable_hours_monthly',
        'last_year_billable_pct',
        'next_year_base_headcount',
        'next_year_avg_hourly_rate',
        'next_year_billable_pct',
        'next_year_weighted_headcount',
        'budgeted_income',
    ];

    protected $casts = [
        'last_year_headcount' => 'integer',
        'last_year_available_hours_monthly' => 'decimal:2',
        'last_year_avg_hourly_rate' => 'decimal:2',
        'last_year_income' => 'decimal:2',
        'last_year_billable_hours_monthly' => 'decimal:2',
        'last_year_billable_pct' => 'decimal:2',
        'next_year_base_headcount' => 'integer',
        'next_year_avg_hourly_rate' => 'decimal:2',
        'next_year_billable_pct' => 'decimal:2',
        'next_year_weighted_headcount' => 'decimal:2',
        'budgeted_income' => 'decimal:2',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function plannedHires(): HasMany
    {
        return $this->hasMany(BudgetCapacityHire::class);
    }

    /**
     * Calculate the weighted headcount based on base + planned hires.
     *
     * Example:
     * - Base headcount: 3
     * - Hire 1 person in March (month 3): contributes 10/12 of a person
     * - Hire 1 person in September (month 9): contributes 4/12 of a person
     * - Weighted total: 3 + 0.833 + 0.333 = 4.166
     */
    public function calculateWeightedHeadcount(): float
    {
        $weighted = $this->next_year_base_headcount;

        foreach ($this->plannedHires as $hire) {
            // Months remaining in year after hire (including hire month)
            $monthsActive = 13 - $hire->hire_month;
            $contribution = ($hire->hire_count * $monthsActive) / 12;
            $weighted += $contribution;
        }

        return round($weighted, 2);
    }

    /**
     * Calculate budgeted income based on capacity inputs.
     */
    public function calculateBudgetedIncome(): float
    {
        if (!$this->next_year_avg_hourly_rate || !$this->next_year_billable_pct) {
            return 0;
        }

        $weightedHeadcount = $this->next_year_weighted_headcount ?: $this->calculateWeightedHeadcount();
        $availableHours = $this->last_year_available_hours_monthly; // Use same as last year
        $hourlyRate = $this->next_year_avg_hourly_rate;
        $billablePct = $this->next_year_billable_pct / 100;

        // Monthly income × 12 months
        return $weightedHeadcount * $availableHours * $hourlyRate * $billablePct * 12;
    }

    /**
     * Get total planned hires count.
     */
    public function getTotalPlannedHires(): int
    {
        return $this->plannedHires->sum('hire_count');
    }
}
```

**Model:** `Modules/Accounting/app/Models/Budget/BudgetCapacityHire.php`
```php
<?php

namespace Modules\Accounting\Models\Budget;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetCapacityHire extends Model
{
    protected $fillable = [
        'budget_capacity_entry_id',
        'hire_month',
        'hire_count',
        'expected_hourly_rate',
    ];

    protected $casts = [
        'hire_month' => 'integer',
        'hire_count' => 'integer',
        'expected_hourly_rate' => 'decimal:2',
    ];

    public function capacityEntry(): BelongsTo
    {
        return $this->belongsTo(BudgetCapacityEntry::class, 'budget_capacity_entry_id');
    }

    /**
     * Get month name for display.
     */
    public function getMonthNameAttribute(): string
    {
        return date('F', mktime(0, 0, 0, $this->hire_month, 1));
    }
}
```

### 1.4 Collection Entries Tables

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_collection_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // ===== LAST YEAR DATA (Calculated) =====

            // Outstanding balance at start of last year
            $table->decimal('beginning_balance', 15, 2)->default(0);

            // Outstanding balance at end of last year
            $table->decimal('end_balance', 15, 2)->default(0);

            // Average: (beginning + end) / 2
            $table->decimal('avg_balance', 15, 2)->default(0);

            // Average new contracts per month (value)
            $table->decimal('avg_contract_monthly', 15, 2)->default(0);

            // Average payments received per month
            $table->decimal('avg_payment_monthly', 15, 2)->default(0);

            // Collection months: avg_balance / avg_payment_monthly
            $table->decimal('last_year_collection_months', 8, 2)->default(0);

            // ===== BUDGETED YEAR (Calculated from patterns) =====

            // Collection months calculated from payment patterns
            $table->decimal('pattern_collection_months', 8, 2)->nullable();

            // Average of last year and pattern: (last + pattern) / 2
            $table->decimal('projected_collection_months', 8, 2)->nullable();

            // Final budgeted income from Collection method
            // Formula: (end_balance / projected_collection_months) × 12
            $table->decimal('budgeted_income', 15, 2)->nullable();

            $table->timestamps();

            $table->unique(['budget_id', 'product_id']);
        });

        // Payment schedule patterns
        Schema::create('budget_collection_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_collection_entry_id')
                  ->constrained('budget_collection_entries')
                  ->cascadeOnDelete();

            // Pattern name for reference (e.g., "Standard", "Enterprise", "Milestone")
            $table->string('pattern_name', 100);

            // What percentage of contracts follow this pattern
            $table->decimal('contract_percentage', 5, 2)->default(0);

            // Payment distribution across months
            // Each represents % of contract paid in that month
            // Sum should equal 100
            $table->decimal('month_1_pct', 5, 2)->default(0);
            $table->decimal('month_2_pct', 5, 2)->default(0);
            $table->decimal('month_3_pct', 5, 2)->default(0);
            $table->decimal('month_4_pct', 5, 2)->default(0);
            $table->decimal('month_5_pct', 5, 2)->default(0);
            $table->decimal('month_6_pct', 5, 2)->default(0);
            $table->decimal('month_7_pct', 5, 2)->default(0);
            $table->decimal('month_8_pct', 5, 2)->default(0);
            $table->decimal('month_9_pct', 5, 2)->default(0);
            $table->decimal('month_10_pct', 5, 2)->default(0);
            $table->decimal('month_11_pct', 5, 2)->default(0);
            $table->decimal('month_12_pct', 5, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_collection_patterns');
        Schema::dropIfExists('budget_collection_entries');
    }
};
```

### 1.5 Result Entries Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_result_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // Values pulled from other tabs (stored for quick access)
            $table->decimal('growth_value', 15, 2)->nullable();
            $table->decimal('capacity_value', 15, 2)->nullable();
            $table->decimal('collection_value', 15, 2)->nullable();

            // Calculated: (growth + capacity + collection) / 3
            // Only averages non-null values
            $table->decimal('average_value', 15, 2)->nullable();

            // User's final selected budget value
            $table->decimal('final_value', 15, 2)->nullable();

            $table->timestamps();

            $table->unique(['budget_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_result_entries');
    }
};
```

### 1.6 Personnel Entries Tables

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_personnel_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();

            // Can be null for planned new hires (not yet in system)
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();

            // For new hires, store the name
            $table->string('employee_name', 255)->nullable();

            // Current monthly salary (from employee record or input for new hire)
            $table->decimal('current_salary', 12, 2)->default(0);

            // Proposed new salary
            $table->decimal('proposed_salary', 12, 2)->nullable();

            // Calculated: ((proposed - current) / current) × 100
            $table->decimal('increase_percentage', 5, 2)->nullable();

            // Is this a planned new hire from Capacity tab?
            $table->boolean('is_new_hire')->default(false);

            // If new hire, which month do they start?
            $table->unsignedTinyInteger('hire_month')->nullable();

            // Link to capacity hire record if applicable
            $table->foreignId('budget_capacity_hire_id')->nullable()
                  ->constrained('budget_capacity_hires')->nullOnDelete();

            $table->timestamps();

            // Existing employee can only appear once per budget
            $table->unique(['budget_id', 'employee_id']);
        });

        // Employee allocation to products/G&A
        Schema::create('budget_personnel_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_personnel_entry_id')
                  ->constrained('budget_personnel_entries')
                  ->cascadeOnDelete();

            // Null product_id means G&A (General & Administrative)
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();

            // Percentage allocated to this product (0-100)
            // Sum of all allocations for an employee should equal 100
            $table->decimal('allocation_percentage', 5, 2)->default(100);

            $table->timestamps();

            // Employee can only have one allocation per product
            $table->unique(['budget_personnel_entry_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_personnel_allocations');
        Schema::dropIfExists('budget_personnel_entries');
    }
};
```

### 1.7 Expense Entries Table (OpEx, Taxes, CapEx)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_expense_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->cascadeOnDelete();

            // Type determines which tab this appears in
            $table->enum('expense_type', ['opex', 'tax', 'capex']);

            // ===== LAST YEAR DATA (Calculated) =====
            $table->decimal('last_year_total', 15, 2)->default(0);
            $table->decimal('last_year_avg_monthly', 15, 2)->default(0);

            // ===== BUDGETED YEAR =====

            // If null, uses global increase percentage
            // If set, overrides global
            $table->decimal('custom_increase_pct', 5, 2)->nullable();

            // If set, this exact amount is used instead of calculating from increase
            $table->decimal('override_amount', 15, 2)->nullable();

            // Final calculated or overridden values
            $table->decimal('proposed_avg_monthly', 15, 2)->nullable();
            $table->decimal('proposed_total', 15, 2)->nullable();

            $table->timestamps();

            $table->unique(['budget_id', 'expense_category_id']);
            $table->index('expense_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_expense_entries');
    }
};
```

### 1.8 Run All Migrations

```bash
php artisan migrate --path=Modules/Accounting/database/migrations
```

---

## Phase 2: Service Layer

### 2.1 Historical Income Service

This service retrieves income data from invoices and contract payments.

**File:** `Modules/Accounting/app/Services/Budget/HistoricalIncomeService.php`

```php
<?php

namespace Modules\Accounting\Services\Budget;

use Illuminate\Support\Facades\DB;
use Modules\Invoicing\Models\Invoice;
use Modules\Accounting\Models\ContractPayment;
use Carbon\Carbon;

class HistoricalIncomeService
{
    /**
     * Get total income for a product in a specific year.
     *
     * Income sources:
     * 1. Invoices linked to product (by project -> product relationship)
     * 2. Contract payments without invoices (direct payments)
     *
     * @param int $productId
     * @param int $year
     * @param float|null $elapsedMonths For partial year compensation
     * @return float
     */
    public function getIncomeByProductAndYear(int $productId, int $year, ?float $elapsedMonths = null): float
    {
        $startDate = Carbon::create($year, 1, 1)->startOfDay();
        $endDate = Carbon::create($year, 12, 31)->endOfDay();

        // 1. Invoice income
        // Assuming invoices have: invoice_date, product_id (directly or via project)
        $invoiceIncome = $this->getInvoiceIncomeForProduct($productId, $startDate, $endDate);

        // 2. Contract payment income (payments not tied to invoices)
        $paymentIncome = $this->getDirectPaymentIncomeForProduct($productId, $startDate, $endDate);

        $total = $invoiceIncome + $paymentIncome;

        // Apply missing month compensation if needed
        if ($elapsedMonths && $elapsedMonths < 12) {
            $total = ($total / $elapsedMonths) * 12;
        }

        return round($total, 2);
    }

    /**
     * Get income from invoices for a product.
     *
     * IMPORTANT: Adjust this query based on your actual database schema.
     * The relationship path might be: Invoice -> Project -> Product
     * Or Invoice might have a direct product_id.
     */
    protected function getInvoiceIncomeForProduct(int $productId, Carbon $startDate, Carbon $endDate): float
    {
        // Option 1: If invoices have direct product_id
        // return Invoice::where('product_id', $productId)
        //     ->whereBetween('invoice_date', [$startDate, $endDate])
        //     ->where('status', 'paid')
        //     ->sum('total_amount');

        // Option 2: If invoices link through projects
        return DB::table('invoices')
            ->join('projects', 'invoices.project_id', '=', 'projects.id')
            ->where('projects.product_id', $productId)
            ->whereBetween('invoices.invoice_date', [$startDate, $endDate])
            ->whereIn('invoices.status', ['paid', 'partial']) // Include partial payments
            ->sum('invoices.paid_amount'); // Use paid_amount, not total

        // Option 3: If you need to check invoice_items for product
        // Adjust based on your schema
    }

    /**
     * Get income from contract payments not linked to invoices.
     *
     * These are direct payments on contracts that haven't been invoiced.
     */
    protected function getDirectPaymentIncomeForProduct(int $productId, Carbon $startDate, Carbon $endDate): float
    {
        // Assuming contract_payments has: payment_date, paid_amount, invoice_id
        // And contracts link to products via projects
        return DB::table('contract_payments')
            ->join('contracts', 'contract_payments.contract_id', '=', 'contracts.id')
            ->join('contract_project', 'contracts.id', '=', 'contract_project.contract_id')
            ->join('projects', 'contract_project.project_id', '=', 'projects.id')
            ->where('projects.product_id', $productId)
            ->whereNull('contract_payments.invoice_id') // Not linked to invoice
            ->whereBetween('contract_payments.payment_date', [$startDate, $endDate])
            ->where('contract_payments.status', 'paid')
            ->sum('contract_payments.paid_amount');
    }

    /**
     * Get income for all products across multiple years.
     *
     * @param int $fromYear
     * @param int $toYear
     * @param float|null $currentYearElapsedMonths
     * @return array [product_id => [year => income, ...], ...]
     */
    public function getAllProductsIncome(int $fromYear, int $toYear, ?float $currentYearElapsedMonths = null): array
    {
        $currentYear = (int) date('Y');
        $results = [];

        // Get all products (including inactive ones that had income)
        $productIds = $this->getProductsWithHistoricalIncome($fromYear, $toYear);

        foreach ($productIds as $productId) {
            $results[$productId] = [];

            for ($year = $fromYear; $year <= $toYear; $year++) {
                // Only apply elapsed months compensation to current year
                $elapsed = ($year === $currentYear) ? $currentYearElapsedMonths : null;
                $results[$productId][$year] = $this->getIncomeByProductAndYear($productId, $year, $elapsed);
            }
        }

        return $results;
    }

    /**
     * Get all product IDs that had income in the given year range.
     * Includes inactive products.
     */
    protected function getProductsWithHistoricalIncome(int $fromYear, int $toYear): array
    {
        $startDate = Carbon::create($fromYear, 1, 1);
        $endDate = Carbon::create($toYear, 12, 31);

        // Get products from invoices
        $fromInvoices = DB::table('invoices')
            ->join('projects', 'invoices.project_id', '=', 'projects.id')
            ->whereBetween('invoices.invoice_date', [$startDate, $endDate])
            ->distinct()
            ->pluck('projects.product_id')
            ->toArray();

        // Get products from direct payments
        $fromPayments = DB::table('contract_payments')
            ->join('contracts', 'contract_payments.contract_id', '=', 'contracts.id')
            ->join('contract_project', 'contracts.id', '=', 'contract_project.contract_id')
            ->join('projects', 'contract_project.project_id', '=', 'projects.id')
            ->whereBetween('contract_payments.payment_date', [$startDate, $endDate])
            ->distinct()
            ->pluck('projects.product_id')
            ->toArray();

        // Also include all active products
        $activeProducts = DB::table('products')
            ->where('status', 'active')
            ->pluck('id')
            ->toArray();

        return array_unique(array_merge($fromInvoices, $fromPayments, $activeProducts));
    }
}
```

### 2.2 Balance Service

**File:** `Modules/Accounting/app/Services/Budget/BalanceService.php`

```php
<?php

namespace Modules\Accounting\Services\Budget;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BalanceService
{
    /**
     * Get outstanding balance for a product at a specific date.
     *
     * Outstanding = Total contract value - Total paid
     *
     * Only considers contracts that were active at that date.
     */
    public function getOutstandingBalanceAtDate(int $productId, Carbon $date): float
    {
        // Get all contracts for this product that existed at the given date
        $contracts = DB::table('contracts')
            ->join('contract_project', 'contracts.id', '=', 'contract_project.contract_id')
            ->join('projects', 'contract_project.project_id', '=', 'projects.id')
            ->where('projects.product_id', $productId)
            ->where('contracts.start_date', '<=', $date)
            ->select('contracts.id', 'contracts.total_amount')
            ->get();

        $totalOutstanding = 0;

        foreach ($contracts as $contract) {
            // Get payments made up to the given date
            $paidAmount = DB::table('contract_payments')
                ->where('contract_id', $contract->id)
                ->where('payment_date', '<=', $date)
                ->where('status', 'paid')
                ->sum('paid_amount');

            $outstanding = $contract->total_amount - $paidAmount;

            // Only count positive balances (ignore overpayments for this calculation)
            if ($outstanding > 0) {
                $totalOutstanding += $outstanding;
            }
        }

        return round($totalOutstanding, 2);
    }

    /**
     * Get beginning balance (Jan 1) for a product in a year.
     */
    public function getBeginningBalance(int $productId, int $year): float
    {
        $date = Carbon::create($year, 1, 1)->startOfDay();
        return $this->getOutstandingBalanceAtDate($productId, $date);
    }

    /**
     * Get ending balance (Dec 31) for a product in a year.
     */
    public function getEndBalance(int $productId, int $year): float
    {
        $date = Carbon::create($year, 12, 31)->endOfDay();
        return $this->getOutstandingBalanceAtDate($productId, $date);
    }

    /**
     * Get average contract value per month for a product in a year.
     *
     * This is the average value of NEW contracts started per month.
     */
    public function getAverageContractPerMonth(int $productId, int $year, float $elapsedMonths = 12): float
    {
        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);

        $totalContractValue = DB::table('contracts')
            ->join('contract_project', 'contracts.id', '=', 'contract_project.contract_id')
            ->join('projects', 'contract_project.project_id', '=', 'projects.id')
            ->where('projects.product_id', $productId)
            ->whereBetween('contracts.start_date', [$startDate, $endDate])
            ->sum('contracts.total_amount');

        return round($totalContractValue / $elapsedMonths, 2);
    }

    /**
     * Get average payment collected per month for a product in a year.
     */
    public function getAveragePaymentPerMonth(int $productId, int $year, float $elapsedMonths = 12): float
    {
        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);

        $totalPayments = DB::table('contract_payments')
            ->join('contracts', 'contract_payments.contract_id', '=', 'contracts.id')
            ->join('contract_project', 'contracts.id', '=', 'contract_project.contract_id')
            ->join('projects', 'contract_project.project_id', '=', 'projects.id')
            ->where('projects.product_id', $productId)
            ->whereBetween('contract_payments.payment_date', [$startDate, $endDate])
            ->where('contract_payments.status', 'paid')
            ->sum('contract_payments.paid_amount');

        return round($totalPayments / $elapsedMonths, 2);
    }
}
```

### 2.3 Employee Data Service

**File:** `Modules/Accounting/app/Services/Budget/EmployeeDataService.php`

```php
<?php

namespace Modules\Accounting\Services\Budget;

use Illuminate\Support\Facades\DB;
use Modules\HR\Models\Employee;
use Carbon\Carbon;

class EmployeeDataService
{
    /**
     * Get developers (employees who log hours) for a product.
     *
     * IMPORTANT: Adjust based on how your system identifies developers.
     * This might be:
     * - employee.type = 'developer'
     * - employee.is_developer = true
     * - employee has logged time entries
     */
    public function getDevelopersByProduct(int $productId): \Illuminate\Support\Collection
    {
        // Option 1: If employees have a direct product assignment
        // return Employee::where('product_id', $productId)
        //     ->where('is_developer', true)
        //     ->where('status', 'active')
        //     ->get();

        // Option 2: If employees are linked via a pivot table
        return Employee::whereHas('products', function ($query) use ($productId) {
                $query->where('products.id', $productId);
            })
            ->where('is_billable', true) // Or whatever field identifies developers
            ->where('status', 'active')
            ->get();
    }

    /**
     * Get headcount of developers for a product in a given year.
     *
     * Uses average headcount if employees joined/left during the year.
     */
    public function getHeadcountByProduct(int $productId, int $year): int
    {
        // Simple approach: count current active developers
        // For more accuracy, you'd need to track employment history
        return $this->getDevelopersByProduct($productId)->count();
    }

    /**
     * Get average hourly rate for developers in a product.
     *
     * IMPORTANT: Adjust based on where hourly rate is stored.
     * It might be:
     * - employee.hourly_rate
     * - Calculated from salary / expected monthly hours
     */
    public function getAverageHourlyRate(int $productId): float
    {
        $developers = $this->getDevelopersByProduct($productId);

        if ($developers->isEmpty()) {
            return 0;
        }

        // Option 1: Direct hourly_rate field
        // return $developers->avg('hourly_rate');

        // Option 2: Calculate from salary
        // Assuming: monthly_salary / (22 work days * 5 billable hours)
        $totalRate = 0;
        $count = 0;

        foreach ($developers as $employee) {
            if ($employee->monthly_salary > 0) {
                // 22 work days × 5 hours = 110 hours/month typical
                $hourlyRate = $employee->monthly_salary / 110;
                $totalRate += $hourlyRate;
                $count++;
            }
        }

        return $count > 0 ? round($totalRate / $count, 2) : 0;
    }

    /**
     * Get number of working days in a year, excluding weekends and holidays.
     */
    public function getWorkingDaysInYear(int $year): int
    {
        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);

        $workDays = 0;
        $current = $startDate->copy();

        while ($current <= $endDate) {
            // Skip weekends (Saturday = 6, Sunday = 0)
            if (!$current->isWeekend()) {
                $workDays++;
            }
            $current->addDay();
        }

        // Subtract holidays
        $holidays = $this->getHolidaysInYear($year);

        // Only subtract holidays that fall on weekdays
        foreach ($holidays as $holiday) {
            $holidayDate = Carbon::parse($holiday);
            if (!$holidayDate->isWeekend()) {
                $workDays--;
            }
        }

        return $workDays;
    }

    /**
     * Get holidays for a year.
     *
     * IMPORTANT: Adjust based on how holidays are stored in your system.
     */
    protected function getHolidaysInYear(int $year): array
    {
        // Option 1: From holidays table
        // return DB::table('holidays')
        //     ->whereYear('date', $year)
        //     ->pluck('date')
        //     ->toArray();

        // Option 2: From Attendance module
        // return \Modules\Attendance\Models\Holiday::whereYear('date', $year)
        //     ->pluck('date')
        //     ->toArray();

        // Fallback: Return empty (assumes no holidays)
        return [];
    }

    /**
     * Calculate average available hours per month.
     *
     * Formula: (5 billable hours/day × working days in year) / 12 months
     */
    public function getAvailableHoursPerMonth(int $year): float
    {
        $workDays = $this->getWorkingDaysInYear($year);
        $billableHoursPerDay = 5; // Standard billable hours

        return round(($workDays * $billableHoursPerDay) / 12, 2);
    }

    /**
     * Get all employees (for Personnel tab), grouped by type.
     *
     * Returns: [
     *   'developers' => [...employees with product assignments...],
     *   'ga' => [...employees without product assignments (G&A)...]
     * ]
     */
    public function getAllEmployeesGrouped(): array
    {
        $allEmployees = Employee::where('status', 'active')
            ->with('products')
            ->get();

        $developers = $allEmployees->filter(fn($e) => $e->products->isNotEmpty());
        $ga = $allEmployees->filter(fn($e) => $e->products->isEmpty());

        return [
            'developers' => $developers,
            'ga' => $ga,
        ];
    }
}
```

### 2.4 Expense Data Service

**File:** `Modules/Accounting/app/Services/Budget/ExpenseDataService.php`

```php
<?php

namespace Modules\Accounting\Services\Budget;

use Illuminate\Support\Facades\DB;
use App\Models\ExpenseCategory;
use Carbon\Carbon;

class ExpenseDataService
{
    /**
     * Get all expense categories of a specific type.
     */
    public function getCategoriesByType(string $type): \Illuminate\Support\Collection
    {
        return ExpenseCategory::where('type', $type)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get total expenses for a category in a year.
     */
    public function getExpensesByCategory(int $categoryId, int $year, float $elapsedMonths = 12): float
    {
        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);

        // Adjust table/column names based on your schema
        $total = DB::table('expenses')
            ->where('expense_category_id', $categoryId)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->sum('amount');

        // Extrapolate for partial year
        if ($elapsedMonths < 12) {
            $total = ($total / $elapsedMonths) * 12;
        }

        return round($total, 2);
    }

    /**
     * Get average monthly expenses for a category in a year.
     */
    public function getAverageMonthlyExpense(int $categoryId, int $year, float $elapsedMonths = 12): float
    {
        $total = $this->getExpensesByCategory($categoryId, $year, 12); // Get raw total
        return round($total / $elapsedMonths, 2);
    }

    /**
     * Get all expenses by type for a year.
     *
     * @return array [category_id => ['total' => x, 'avg_monthly' => y], ...]
     */
    public function getAllExpensesByType(string $type, int $year, float $elapsedMonths = 12): array
    {
        $categories = $this->getCategoriesByType($type);
        $results = [];

        foreach ($categories as $category) {
            $total = $this->getExpensesByCategory($category->id, $year, $elapsedMonths);
            $results[$category->id] = [
                'category' => $category,
                'total' => $total,
                'avg_monthly' => round($total / 12, 2),
            ];
        }

        return $results;
    }

    /**
     * Identify special categories for P&L mapping.
     *
     * IMPORTANT: Adjust category names based on your actual data.
     */
    public function getCostOfSalesCategoryId(): ?int
    {
        $category = ExpenseCategory::where('name', 'LIKE', '%Cost of Sales%')
            ->orWhere('name', 'LIKE', '%COS%')
            ->first();

        return $category?->id;
    }

    public function getVatCategoryId(): ?int
    {
        $category = ExpenseCategory::where('name', 'LIKE', '%VAT%')
            ->orWhere('name', 'LIKE', '%Value Added Tax%')
            ->first();

        return $category?->id;
    }

    public function getSalesCommissionsCategoryId(): ?int
    {
        $category = ExpenseCategory::where('name', 'LIKE', '%Commission%')
            ->orWhere('name', 'LIKE', '%Sales Commission%')
            ->first();

        return $category?->id;
    }
}
```

### 2.5 Growth Calculation Service

**File:** `Modules/Accounting/app/Services/Budget/GrowthCalculationService.php`

```php
<?php

namespace Modules\Accounting\Services\Budget;

class GrowthCalculationService
{
    /**
     * Calculate trendline and project future value.
     *
     * @param array $dataPoints [year => value, ...]
     * @param string $type linear|logarithmic|polynomial
     * @param int $targetYear Year to project
     * @param int|null $polynomialOrder For polynomial type
     * @return float Projected value
     */
    public function projectValue(array $dataPoints, string $type, int $targetYear, ?int $polynomialOrder = null): float
    {
        if (empty($dataPoints)) {
            return 0;
        }

        // Convert to x,y coordinates (x = year offset from first year)
        $years = array_keys($dataPoints);
        $baseYear = min($years);

        $points = [];
        foreach ($dataPoints as $year => $value) {
            $points[] = [
                'x' => $year - $baseYear,
                'y' => (float) $value,
            ];
        }

        $targetX = $targetYear - $baseYear;

        switch ($type) {
            case 'logarithmic':
                return $this->logarithmicProjection($points, $targetX);
            case 'polynomial':
                return $this->polynomialProjection($points, $targetX, $polynomialOrder ?? 2);
            case 'linear':
            default:
                return $this->linearProjection($points, $targetX);
        }
    }

    /**
     * Linear regression: y = mx + b
     */
    protected function linearProjection(array $points, float $targetX): float
    {
        $n = count($points);
        if ($n < 2) {
            return $points[0]['y'] ?? 0;
        }

        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($points as $point) {
            $sumX += $point['x'];
            $sumY += $point['y'];
            $sumXY += $point['x'] * $point['y'];
            $sumX2 += $point['x'] * $point['x'];
        }

        $denominator = ($n * $sumX2 - $sumX * $sumX);

        if ($denominator == 0) {
            return $sumY / $n; // Return average if can't calculate slope
        }

        $m = ($n * $sumXY - $sumX * $sumY) / $denominator;
        $b = ($sumY - $m * $sumX) / $n;

        $result = $m * $targetX + $b;

        return max(0, round($result, 2)); // Don't allow negative projections
    }

    /**
     * Logarithmic regression: y = a + b * ln(x)
     *
     * Note: Requires x > 0, so we offset x values by 1
     */
    protected function logarithmicProjection(array $points, float $targetX): float
    {
        $n = count($points);
        if ($n < 2) {
            return $points[0]['y'] ?? 0;
        }

        // Transform: use ln(x+1) to handle x=0
        $sumLnX = 0;
        $sumY = 0;
        $sumLnXY = 0;
        $sumLnX2 = 0;

        foreach ($points as $point) {
            $lnX = log($point['x'] + 1);
            $sumLnX += $lnX;
            $sumY += $point['y'];
            $sumLnXY += $lnX * $point['y'];
            $sumLnX2 += $lnX * $lnX;
        }

        $denominator = ($n * $sumLnX2 - $sumLnX * $sumLnX);

        if ($denominator == 0) {
            return $sumY / $n;
        }

        $b = ($n * $sumLnXY - $sumLnX * $sumY) / $denominator;
        $a = ($sumY - $b * $sumLnX) / $n;

        $result = $a + $b * log($targetX + 1);

        return max(0, round($result, 2));
    }

    /**
     * Polynomial regression: y = a0 + a1*x + a2*x^2 + ...
     *
     * Uses least squares method.
     */
    protected function polynomialProjection(array $points, float $targetX, int $order): float
    {
        $n = count($points);

        // Need at least order+1 points for polynomial regression
        if ($n <= $order) {
            // Fall back to linear if not enough points
            return $this->linearProjection($points, $targetX);
        }

        // Build matrices for least squares
        // This is a simplified implementation
        // For production, consider using a library like MathPHP

        $x = array_column($points, 'x');
        $y = array_column($points, 'y');

        // Build Vandermonde matrix
        $matrix = [];
        for ($i = 0; $i < $n; $i++) {
            $row = [];
            for ($j = 0; $j <= $order; $j++) {
                $row[] = pow($x[$i], $j);
            }
            $matrix[] = $row;
        }

        // Solve using normal equations: (X'X)^-1 * X'y
        // Simplified: use basic polynomial fit for order 2
        if ($order == 2) {
            return $this->quadraticFit($points, $targetX);
        }

        // For higher orders, fall back to linear
        return $this->linearProjection($points, $targetX);
    }

    /**
     * Quadratic fit: y = ax^2 + bx + c
     */
    protected function quadraticFit(array $points, float $targetX): float
    {
        $n = count($points);

        $sumX = 0; $sumX2 = 0; $sumX3 = 0; $sumX4 = 0;
        $sumY = 0; $sumXY = 0; $sumX2Y = 0;

        foreach ($points as $point) {
            $x = $point['x'];
            $y = $point['y'];
            $x2 = $x * $x;

            $sumX += $x;
            $sumX2 += $x2;
            $sumX3 += $x2 * $x;
            $sumX4 += $x2 * $x2;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2Y += $x2 * $y;
        }

        // Solve system of equations using Cramer's rule
        $det = $n * ($sumX2 * $sumX4 - $sumX3 * $sumX3)
             - $sumX * ($sumX * $sumX4 - $sumX2 * $sumX3)
             + $sumX2 * ($sumX * $sumX3 - $sumX2 * $sumX2);

        if (abs($det) < 0.0001) {
            return $this->linearProjection($points, $targetX);
        }

        $c = ($sumY * ($sumX2 * $sumX4 - $sumX3 * $sumX3)
            - $sumX * ($sumXY * $sumX4 - $sumX2Y * $sumX3)
            + $sumX2 * ($sumXY * $sumX3 - $sumX2Y * $sumX2)) / $det;

        $b = ($n * ($sumXY * $sumX4 - $sumX2Y * $sumX3)
            - $sumY * ($sumX * $sumX4 - $sumX2 * $sumX3)
            + $sumX2 * ($sumX * $sumX2Y - $sumX2 * $sumXY)) / $det;

        $a = ($n * ($sumX2 * $sumX2Y - $sumX3 * $sumXY)
            - $sumX * ($sumX * $sumX2Y - $sumX2 * $sumXY)
            + $sumY * ($sumX * $sumX3 - $sumX2 * $sumX2)) / $det;

        $result = $a * $targetX * $targetX + $b * $targetX + $c;

        return max(0, round($result, 2));
    }

    /**
     * Get chart data for trendline display.
     *
     * @return array ['historical' => [...], 'trendline' => [...]]
     */
    public function getChartData(array $dataPoints, string $type, int $targetYear, ?int $polynomialOrder = null): array
    {
        $years = array_keys($dataPoints);
        $minYear = min($years);
        $maxYear = $targetYear;

        // Generate trendline points
        $trendline = [];
        for ($year = $minYear; $year <= $maxYear; $year++) {
            $trendline[$year] = $this->projectValue($dataPoints, $type, $year, $polynomialOrder);
        }

        return [
            'historical' => $dataPoints,
            'trendline' => $trendline,
            'projected' => $this->projectValue($dataPoints, $type, $targetYear, $polynomialOrder),
        ];
    }
}
```

---

## Phase 3: Growth Tab

### 3.1 Controller

**File:** `Modules/Accounting/app/Http/Controllers/Budget/BudgetGrowthController.php`

```php
<?php

namespace Modules\Accounting\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Accounting\Models\Budget\Budget;
use Modules\Accounting\Models\Budget\BudgetGrowthEntry;
use Modules\Accounting\Services\Budget\HistoricalIncomeService;
use Modules\Accounting\Services\Budget\GrowthCalculationService;

class BudgetGrowthController extends Controller
{
    public function __construct(
        protected HistoricalIncomeService $incomeService,
        protected GrowthCalculationService $growthService
    ) {}

    /**
     * Get growth tab data.
     */
    public function index(Budget $budget): JsonResponse
    {
        // Check permission
        if (!auth()->user()->can('view-accounting-readonly')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Load existing entries or create from historical data
        $entries = $budget->growthEntries()->with('product')->get();

        if ($entries->isEmpty()) {
            $entries = $this->initializeGrowthEntries($budget);
        }

        // Format for frontend
        $data = $entries->map(function ($entry) use ($budget) {
            return [
                'id' => $entry->id,
                'product_id' => $entry->product_id,
                'product_name' => $entry->product->name,
                'product_status' => $entry->product->status,
                'historical' => [
                    $budget->budget_year - 3 => $entry->year_minus_3_income,
                    $budget->budget_year - 2 => $entry->year_minus_2_income,
                    $budget->budget_year - 1 => $entry->year_minus_1_income,
                ],
                'trendline_type' => $entry->trendline_type,
                'polynomial_order' => $entry->polynomial_order,
                'trendline_projected_value' => $entry->trendline_projected_value,
                'budgeted_value' => $entry->budgeted_value,
            ];
        });

        return response()->json([
            'success' => true,
            'budget_year' => $budget->budget_year,
            'can_edit' => $budget->canEdit() && auth()->user()->hasRole('super-admin'),
            'entries' => $data,
        ]);
    }

    /**
     * Initialize growth entries from historical data.
     */
    protected function initializeGrowthEntries(Budget $budget): \Illuminate\Support\Collection
    {
        $budgetYear = $budget->budget_year;
        $elapsedMonths = $budget->getElapsedMonths();

        // Get historical income for all products
        $historicalData = $this->incomeService->getAllProductsIncome(
            $budgetYear - 3,
            $budgetYear - 1,
            $elapsedMonths
        );

        $entries = collect();

        foreach ($historicalData as $productId => $yearlyData) {
            $entry = BudgetGrowthEntry::create([
                'budget_id' => $budget->id,
                'product_id' => $productId,
                'year_minus_3_income' => $yearlyData[$budgetYear - 3] ?? 0,
                'year_minus_2_income' => $yearlyData[$budgetYear - 2] ?? 0,
                'year_minus_1_income' => $yearlyData[$budgetYear - 1] ?? 0,
                'trendline_type' => 'linear',
            ]);

            // Calculate initial trendline projection
            $projected = $this->growthService->projectValue(
                $yearlyData,
                'linear',
                $budgetYear
            );

            $entry->update(['trendline_projected_value' => $projected]);

            $entry->load('product');
            $entries->push($entry);
        }

        return $entries;
    }

    /**
     * Save growth entry.
     */
    public function save(Request $request, Budget $budget): JsonResponse
    {
        // Check permission
        if (!auth()->user()->hasRole('super-admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$budget->canEdit()) {
            return response()->json(['error' => 'Budget is locked'], 403);
        }

        $validated = $request->validate([
            'entries' => 'required|array',
            'entries.*.id' => 'required|exists:budget_growth_entries,id',
            'entries.*.trendline_type' => 'required|in:linear,logarithmic,polynomial',
            'entries.*.polynomial_order' => 'nullable|integer|min:2|max:6',
            'entries.*.budgeted_value' => 'nullable|numeric|min:0',
        ]);

        foreach ($validated['entries'] as $entryData) {
            $entry = BudgetGrowthEntry::find($entryData['id']);

            // Verify entry belongs to this budget
            if ($entry->budget_id !== $budget->id) {
                continue;
            }

            // Calculate new trendline projection
            $historicalData = [
                $budget->budget_year - 3 => $entry->year_minus_3_income,
                $budget->budget_year - 2 => $entry->year_minus_2_income,
                $budget->budget_year - 1 => $entry->year_minus_1_income,
            ];

            $projected = $this->growthService->projectValue(
                $historicalData,
                $entryData['trendline_type'],
                $budget->budget_year,
                $entryData['polynomial_order'] ?? null
            );

            $entry->update([
                'trendline_type' => $entryData['trendline_type'],
                'polynomial_order' => $entryData['polynomial_order'] ?? null,
                'trendline_projected_value' => $projected,
                'budgeted_value' => $entryData['budgeted_value'],
            ]);
        }

        // Update Result tab with new Growth values
        $this->updateResultEntries($budget);

        return response()->json(['success' => true]);
    }

    /**
     * Get chart data for a specific product.
     */
    public function chartData(Budget $budget, int $productId): JsonResponse
    {
        $entry = $budget->growthEntries()
            ->where('product_id', $productId)
            ->first();

        if (!$entry) {
            return response()->json(['error' => 'Entry not found'], 404);
        }

        $historicalData = [
            $budget->budget_year - 3 => (float) $entry->year_minus_3_income,
            $budget->budget_year - 2 => (float) $entry->year_minus_2_income,
            $budget->budget_year - 1 => (float) $entry->year_minus_1_income,
        ];

        $chartData = $this->growthService->getChartData(
            $historicalData,
            $entry->trendline_type,
            $budget->budget_year,
            $entry->polynomial_order
        );

        // Add budgeted value point
        $chartData['budgeted'] = $entry->budgeted_value;

        return response()->json([
            'success' => true,
            'chart_data' => $chartData,
        ]);
    }

    /**
     * Update Result entries with Growth values.
     */
    protected function updateResultEntries(Budget $budget): void
    {
        foreach ($budget->growthEntries as $growthEntry) {
            $budget->resultEntries()->updateOrCreate(
                ['product_id' => $growthEntry->product_id],
                ['growth_value' => $growthEntry->budgeted_value]
            );
        }
    }
}
```

### 3.2 View

**File:** `Modules/Accounting/resources/views/budget/partials/tabs/growth.blade.php`

```html
<div class="tab-pane fade" id="growth-tab" role="tabpanel">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Growth Analysis</h5>
                <small class="text-muted">Budget based on historical income trends</small>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary" id="refreshGrowthData">
                    <i class="ti ti-refresh me-1"></i>Refresh Data
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Historical Data Table -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered" id="growthTable">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-end" id="yearMinus3Header">Year -3</th>
                            <th class="text-end" id="yearMinus2Header">Year -2</th>
                            <th class="text-end" id="yearMinus1Header">Year -1</th>
                            <th style="width: 150px;">Trendline</th>
                            <th class="text-end">Projected</th>
                            <th class="text-end" style="width: 150px;">Budget Value</th>
                            <th style="width: 50px;">Chart</th>
                        </tr>
                    </thead>
                    <tbody id="growthTableBody">
                        <!-- Populated via JavaScript -->
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary">
                            <th>Total</th>
                            <th class="text-end" id="totalYearMinus3">-</th>
                            <th class="text-end" id="totalYearMinus2">-</th>
                            <th class="text-end" id="totalYearMinus1">-</th>
                            <th></th>
                            <th class="text-end" id="totalProjected">-</th>
                            <th class="text-end" id="totalBudgeted">-</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Chart Modal -->
            <div class="modal fade" id="growthChartModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Income Trend - <span id="chartProductName"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <canvas id="growthChart" height="300"></canvas>
                            <div class="mt-3">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">Trendline Type</label>
                                        <select class="form-select" id="chartTrendlineType">
                                            <option value="linear">Linear</option>
                                            <option value="logarithmic">Logarithmic</option>
                                            <option value="polynomial">Polynomial</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4" id="polynomialOrderGroup" style="display: none;">
                                        <label class="form-label">Polynomial Order</label>
                                        <select class="form-select" id="chartPolynomialOrder">
                                            <option value="2">2 (Quadratic)</option>
                                            <option value="3">3 (Cubic)</option>
                                            <option value="4">4</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Projected Value</label>
                                        <div class="form-control-plaintext fw-bold" id="chartProjectedValue">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="applyChartSettings">Apply & Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Growth Tab JavaScript
document.addEventListener('DOMContentLoaded', function() {
    let growthData = [];
    let currentChartProductId = null;
    let growthChart = null;
    const budgetId = {{ $budget->id }};
    const budgetYear = {{ $budget->budget_year }};
    const canEdit = {{ $budget->canEdit() && auth()->user()->hasRole('super-admin') ? 'true' : 'false' }};

    // Initialize
    loadGrowthData();

    async function loadGrowthData() {
        try {
            const response = await fetch(`/accounting/budget/${budgetId}/growth`);
            const data = await response.json();

            if (data.success) {
                growthData = data.entries;
                renderGrowthTable();
                updateYearHeaders(data.budget_year);
            }
        } catch (error) {
            console.error('Error loading growth data:', error);
        }
    }

    function updateYearHeaders(year) {
        document.getElementById('yearMinus3Header').textContent = year - 3;
        document.getElementById('yearMinus2Header').textContent = year - 2;
        document.getElementById('yearMinus1Header').textContent = year - 1;
    }

    function renderGrowthTable() {
        const tbody = document.getElementById('growthTableBody');
        tbody.innerHTML = '';

        let totals = { y3: 0, y2: 0, y1: 0, projected: 0, budgeted: 0 };

        growthData.forEach(entry => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    ${entry.product_name}
                    ${entry.product_status !== 'active' ? '<span class="badge bg-label-secondary ms-1">Inactive</span>' : ''}
                </td>
                <td class="text-end">${formatCurrency(entry.historical[budgetYear - 3])}</td>
                <td class="text-end">${formatCurrency(entry.historical[budgetYear - 2])}</td>
                <td class="text-end">${formatCurrency(entry.historical[budgetYear - 1])}</td>
                <td>
                    <select class="form-select form-select-sm trendline-select"
                            data-entry-id="${entry.id}"
                            ${!canEdit ? 'disabled' : ''}>
                        <option value="linear" ${entry.trendline_type === 'linear' ? 'selected' : ''}>Linear</option>
                        <option value="logarithmic" ${entry.trendline_type === 'logarithmic' ? 'selected' : ''}>Logarithmic</option>
                        <option value="polynomial" ${entry.trendline_type === 'polynomial' ? 'selected' : ''}>Polynomial</option>
                    </select>
                </td>
                <td class="text-end">${formatCurrency(entry.trendline_projected_value)}</td>
                <td>
                    <input type="number" class="form-control form-control-sm text-end budget-input"
                           data-entry-id="${entry.id}"
                           value="${entry.budgeted_value || ''}"
                           placeholder="Enter budget"
                           ${!canEdit ? 'disabled' : ''}>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-icon btn-outline-primary show-chart"
                            data-product-id="${entry.product_id}"
                            data-product-name="${entry.product_name}">
                        <i class="ti ti-chart-line"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);

            // Accumulate totals
            totals.y3 += entry.historical[budgetYear - 3] || 0;
            totals.y2 += entry.historical[budgetYear - 2] || 0;
            totals.y1 += entry.historical[budgetYear - 1] || 0;
            totals.projected += entry.trendline_projected_value || 0;
            totals.budgeted += parseFloat(entry.budgeted_value) || 0;
        });

        // Update totals
        document.getElementById('totalYearMinus3').textContent = formatCurrency(totals.y3);
        document.getElementById('totalYearMinus2').textContent = formatCurrency(totals.y2);
        document.getElementById('totalYearMinus1').textContent = formatCurrency(totals.y1);
        document.getElementById('totalProjected').textContent = formatCurrency(totals.projected);
        document.getElementById('totalBudgeted').textContent = formatCurrency(totals.budgeted);

        // Attach event listeners
        attachEventListeners();
    }

    function attachEventListeners() {
        // Trendline select change
        document.querySelectorAll('.trendline-select').forEach(select => {
            select.addEventListener('change', function() {
                const entryId = this.dataset.entryId;
                const entry = growthData.find(e => e.id == entryId);
                if (entry) {
                    entry.trendline_type = this.value;
                    markUnsaved();
                }
            });
        });

        // Budget input change
        document.querySelectorAll('.budget-input').forEach(input => {
            input.addEventListener('change', function() {
                const entryId = this.dataset.entryId;
                const entry = growthData.find(e => e.id == entryId);
                if (entry) {
                    entry.budgeted_value = parseFloat(this.value) || null;
                    markUnsaved();
                    updateTotals();
                }
            });
        });

        // Show chart button
        document.querySelectorAll('.show-chart').forEach(btn => {
            btn.addEventListener('click', function() {
                showChartModal(this.dataset.productId, this.dataset.productName);
            });
        });
    }

    async function showChartModal(productId, productName) {
        currentChartProductId = productId;
        document.getElementById('chartProductName').textContent = productName;

        try {
            const response = await fetch(`/accounting/budget/${budgetId}/growth/chart/${productId}`);
            const data = await response.json();

            if (data.success) {
                renderChart(data.chart_data);

                // Set current trendline settings
                const entry = growthData.find(e => e.product_id == productId);
                if (entry) {
                    document.getElementById('chartTrendlineType').value = entry.trendline_type;
                    document.getElementById('chartPolynomialOrder').value = entry.polynomial_order || 2;
                    document.getElementById('polynomialOrderGroup').style.display =
                        entry.trendline_type === 'polynomial' ? 'block' : 'none';
                }

                document.getElementById('chartProjectedValue').textContent =
                    formatCurrency(data.chart_data.projected);

                new bootstrap.Modal(document.getElementById('growthChartModal')).show();
            }
        } catch (error) {
            console.error('Error loading chart data:', error);
        }
    }

    function renderChart(chartData) {
        const ctx = document.getElementById('growthChart').getContext('2d');

        if (growthChart) {
            growthChart.destroy();
        }

        const years = Object.keys(chartData.historical).concat([budgetYear]);
        const historicalValues = Object.values(chartData.historical);
        const trendlineValues = years.map(year => chartData.trendline[year] || null);

        growthChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: years,
                datasets: [
                    {
                        label: 'Historical Income',
                        data: historicalValues.concat([chartData.budgeted]),
                        backgroundColor: years.map((y, i) =>
                            i < historicalValues.length ? 'rgba(105, 108, 255, 0.8)' : 'rgba(40, 199, 111, 0.8)'
                        ),
                        borderColor: years.map((y, i) =>
                            i < historicalValues.length ? 'rgb(105, 108, 255)' : 'rgb(40, 199, 111)'
                        ),
                        borderWidth: 1
                    },
                    {
                        label: 'Trendline',
                        data: trendlineValues,
                        type: 'line',
                        borderColor: 'rgb(255, 159, 64)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.1,
                        pointRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => formatCurrency(value)
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: context => context.dataset.label + ': ' + formatCurrency(context.raw)
                        }
                    }
                }
            }
        });
    }

    function formatCurrency(value) {
        if (value === null || value === undefined) return '-';
        return new Intl.NumberFormat('en-EG', {
            style: 'decimal',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value) + ' EGP';
    }

    function markUnsaved() {
        // Implement unsaved changes indicator
        document.getElementById('saveButton')?.classList.remove('btn-outline-primary');
        document.getElementById('saveButton')?.classList.add('btn-primary');
    }

    function updateTotals() {
        let totalBudgeted = 0;
        growthData.forEach(entry => {
            totalBudgeted += parseFloat(entry.budgeted_value) || 0;
        });
        document.getElementById('totalBudgeted').textContent = formatCurrency(totalBudgeted);
    }

    // Trendline type change in modal
    document.getElementById('chartTrendlineType').addEventListener('change', function() {
        document.getElementById('polynomialOrderGroup').style.display =
            this.value === 'polynomial' ? 'block' : 'none';
    });

    // Apply chart settings
    document.getElementById('applyChartSettings').addEventListener('click', function() {
        const entry = growthData.find(e => e.product_id == currentChartProductId);
        if (entry) {
            entry.trendline_type = document.getElementById('chartTrendlineType').value;
            entry.polynomial_order = entry.trendline_type === 'polynomial'
                ? parseInt(document.getElementById('chartPolynomialOrder').value)
                : null;

            renderGrowthTable();
            markUnsaved();
        }

        bootstrap.Modal.getInstance(document.getElementById('growthChartModal')).hide();
    });

    // Expose save function for main save button
    window.saveGrowthTab = async function() {
        const entries = growthData.map(entry => ({
            id: entry.id,
            trendline_type: entry.trendline_type,
            polynomial_order: entry.polynomial_order,
            budgeted_value: entry.budgeted_value
        }));

        try {
            const response = await fetch(`/accounting/budget/${budgetId}/growth`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ entries })
            });

            const data = await response.json();
            return data.success;
        } catch (error) {
            console.error('Error saving growth data:', error);
            return false;
        }
    };
});
</script>
```

---

## Phase 4: Capacity Tab

The Capacity tab calculates budgeted income based on developer headcount, available hours, hourly rates, and billable percentages.

### 4.1 Capacity Calculation Service

**File:** `Modules/Accounting/app/Services/Budget/CapacityCalculationService.php`

```php
<?php

namespace Modules\Accounting\Services\Budget;

use Modules\Accounting\Models\Budget\BudgetCapacityEntry;
use Modules\Accounting\Models\Budget\BudgetCapacityHire;

class CapacityCalculationService
{
    public function __construct(
        protected EmployeeDataService $employeeService,
        protected HistoricalIncomeService $incomeService
    ) {}

    /**
     * Calculate all last year values for a capacity entry.
     *
     * This populates the read-only "Last Year" section of the Capacity tab.
     */
    public function calculateLastYearData(int $productId, int $year, float $elapsedMonths = 12): array
    {
        // 1. Get headcount (developers assigned to this product)
        $headcount = $this->employeeService->getHeadcountByProduct($productId, $year);

        // 2. Get available hours per month
        // Formula: (5 billable hours/day × working days in year) / 12
        $availableHoursMonthly = $this->employeeService->getAvailableHoursPerMonth($year);

        // 3. Get average hourly rate for developers in this product
        $avgHourlyRate = $this->employeeService->getAverageHourlyRate($productId);

        // 4. Get actual income for this product last year
        $income = $this->incomeService->getIncomeByProductAndYear($productId, $year, $elapsedMonths);

        // 5. Calculate billable hours per employee per month
        // Formula: Income / (Headcount × Avg Hourly Rate × 12 months)
        $billableHoursMonthly = 0;
        if ($headcount > 0 && $avgHourlyRate > 0) {
            $totalBillableHours = $income / $avgHourlyRate;
            $billableHoursMonthly = $totalBillableHours / ($headcount * 12);
        }

        // 6. Calculate billable percentage
        // Formula: (Billable Hours / Available Hours) × 100
        $billablePct = 0;
        if ($availableHoursMonthly > 0) {
            $billablePct = ($billableHoursMonthly / $availableHoursMonthly) * 100;
        }

        return [
            'headcount' => $headcount,
            'available_hours_monthly' => round($availableHoursMonthly, 2),
            'avg_hourly_rate' => round($avgHourlyRate, 2),
            'income' => round($income, 2),
            'billable_hours_monthly' => round($billableHoursMonthly, 2),
            'billable_pct' => round($billablePct, 2),
        ];
    }

    /**
     * Calculate weighted headcount based on base + planned hires.
     *
     * New hires are weighted by how many months they'll be active in the year.
     *
     * Example:
     * - Base headcount: 5 (active full year = 5.0)
     * - Hire 2 people in March (month 3): Active for 10 months = 2 × (10/12) = 1.67
     * - Hire 1 person in October (month 10): Active for 3 months = 1 × (3/12) = 0.25
     * - Weighted total: 5.0 + 1.67 + 0.25 = 6.92
     */
    public function calculateWeightedHeadcount(int $baseHeadcount, array $hires): float
    {
        $weighted = (float) $baseHeadcount;

        foreach ($hires as $hire) {
            // Months remaining after hire (including the hire month)
            // If hired in January (month 1), active for 12 months
            // If hired in December (month 12), active for 1 month
            $monthsActive = 13 - $hire['month'];
            $contribution = $hire['count'] * ($monthsActive / 12);
            $weighted += $contribution;
        }

        return round($weighted, 2);
    }

    /**
     * Calculate budgeted income from capacity inputs.
     *
     * Formula:
     * Budgeted Income = Weighted Headcount × Available Hours/Month × Hourly Rate × Billable% × 12
     */
    public function calculateBudgetedIncome(
        float $weightedHeadcount,
        float $availableHoursMonthly,
        float $hourlyRate,
        float $billablePct
    ): float {
        if ($weightedHeadcount <= 0 || $hourlyRate <= 0 || $billablePct <= 0) {
            return 0;
        }

        // Convert percentage to decimal
        $billableDecimal = $billablePct / 100;

        // Monthly income × 12 months
        $monthlyIncome = $weightedHeadcount * $availableHoursMonthly * $hourlyRate * $billableDecimal;

        return round($monthlyIncome * 12, 2);
    }
}
```

### 4.2 Capacity Controller

**File:** `Modules/Accounting/app/Http/Controllers/Budget/BudgetCapacityController.php`

```php
<?php

namespace Modules\Accounting\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Budget\Budget;
use Modules\Accounting\Models\Budget\BudgetCapacityEntry;
use Modules\Accounting\Models\Budget\BudgetCapacityHire;
use Modules\Accounting\Services\Budget\CapacityCalculationService;
use Modules\Accounting\Services\Budget\EmployeeDataService;

class BudgetCapacityController extends Controller
{
    public function __construct(
        protected CapacityCalculationService $capacityService,
        protected EmployeeDataService $employeeService
    ) {}

    /**
     * Get capacity tab data.
     */
    public function index(Budget $budget): JsonResponse
    {
        if (!auth()->user()->can('view-accounting-readonly')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Load existing entries or initialize from employee data
        $entries = $budget->capacityEntries()
            ->with(['product', 'plannedHires'])
            ->get();

        if ($entries->isEmpty()) {
            $entries = $this->initializeCapacityEntries($budget);
        }

        // Format for frontend
        $data = $entries->map(function ($entry) {
            return [
                'id' => $entry->id,
                'product_id' => $entry->product_id,
                'product_name' => $entry->product->name,
                // Last year data (read-only)
                'last_year' => [
                    'headcount' => $entry->last_year_headcount,
                    'available_hours' => $entry->last_year_available_hours_monthly,
                    'avg_hourly_rate' => $entry->last_year_avg_hourly_rate,
                    'income' => $entry->last_year_income,
                    'billable_hours' => $entry->last_year_billable_hours_monthly,
                    'billable_pct' => $entry->last_year_billable_pct,
                ],
                // Next year budget (editable)
                'next_year' => [
                    'base_headcount' => $entry->next_year_base_headcount,
                    'avg_hourly_rate' => $entry->next_year_avg_hourly_rate,
                    'billable_pct' => $entry->next_year_billable_pct,
                    'weighted_headcount' => $entry->next_year_weighted_headcount,
                    'budgeted_income' => $entry->budgeted_income,
                ],
                // Planned hires
                'hires' => $entry->plannedHires->map(fn($h) => [
                    'id' => $h->id,
                    'month' => $h->hire_month,
                    'month_name' => $h->month_name,
                    'count' => $h->hire_count,
                    'hourly_rate' => $h->expected_hourly_rate,
                ])->toArray(),
                'total_hires' => $entry->plannedHires->sum('hire_count'),
            ];
        });

        return response()->json([
            'success' => true,
            'budget_year' => $budget->budget_year,
            'can_edit' => $budget->canEdit() && auth()->user()->hasRole('super-admin'),
            'entries' => $data,
        ]);
    }

    /**
     * Initialize capacity entries from employee/product data.
     */
    protected function initializeCapacityEntries(Budget $budget): \Illuminate\Support\Collection
    {
        $lastYear = $budget->getLastYear();
        $elapsedMonths = $budget->getElapsedMonths();

        // Get all products with developers
        $products = \Modules\Accounting\Models\Product::where('status', 'active')
            ->whereHas('employees', function ($q) {
                $q->where('is_billable', true);
            })
            ->get();

        $entries = collect();

        foreach ($products as $product) {
            // Calculate last year metrics
            $lastYearData = $this->capacityService->calculateLastYearData(
                $product->id,
                $lastYear,
                $elapsedMonths
            );

            $entry = BudgetCapacityEntry::create([
                'budget_id' => $budget->id,
                'product_id' => $product->id,
                // Last year (calculated)
                'last_year_headcount' => $lastYearData['headcount'],
                'last_year_available_hours_monthly' => $lastYearData['available_hours_monthly'],
                'last_year_avg_hourly_rate' => $lastYearData['avg_hourly_rate'],
                'last_year_income' => $lastYearData['income'],
                'last_year_billable_hours_monthly' => $lastYearData['billable_hours_monthly'],
                'last_year_billable_pct' => $lastYearData['billable_pct'],
                // Next year defaults (user will edit)
                'next_year_base_headcount' => $lastYearData['headcount'],
                'next_year_avg_hourly_rate' => $lastYearData['avg_hourly_rate'],
                'next_year_billable_pct' => $lastYearData['billable_pct'],
                'next_year_weighted_headcount' => $lastYearData['headcount'],
            ]);

            $entry->load(['product', 'plannedHires']);
            $entries->push($entry);
        }

        return $entries;
    }

    /**
     * Save capacity entries.
     */
    public function save(Request $request, Budget $budget): JsonResponse
    {
        if (!auth()->user()->hasRole('super-admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$budget->canEdit()) {
            return response()->json(['error' => 'Budget is locked'], 403);
        }

        $validated = $request->validate([
            'entries' => 'required|array',
            'entries.*.id' => 'required|exists:budget_capacity_entries,id',
            'entries.*.base_headcount' => 'required|integer|min:0',
            'entries.*.avg_hourly_rate' => 'nullable|numeric|min:0',
            'entries.*.billable_pct' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($validated, $budget) {
            foreach ($validated['entries'] as $entryData) {
                $entry = BudgetCapacityEntry::find($entryData['id']);

                if ($entry->budget_id !== $budget->id) {
                    continue;
                }

                // Get current hires for weighted calculation
                $hires = $entry->plannedHires->map(fn($h) => [
                    'month' => $h->hire_month,
                    'count' => $h->hire_count,
                ])->toArray();

                // Calculate weighted headcount
                $weightedHeadcount = $this->capacityService->calculateWeightedHeadcount(
                    $entryData['base_headcount'],
                    $hires
                );

                // Calculate budgeted income
                $budgetedIncome = $this->capacityService->calculateBudgetedIncome(
                    $weightedHeadcount,
                    $entry->last_year_available_hours_monthly,
                    $entryData['avg_hourly_rate'] ?? 0,
                    $entryData['billable_pct'] ?? 0
                );

                $entry->update([
                    'next_year_base_headcount' => $entryData['base_headcount'],
                    'next_year_avg_hourly_rate' => $entryData['avg_hourly_rate'],
                    'next_year_billable_pct' => $entryData['billable_pct'],
                    'next_year_weighted_headcount' => $weightedHeadcount,
                    'budgeted_income' => $budgetedIncome,
                ]);
            }
        });

        // Update Result tab with new Capacity values
        $this->updateResultEntries($budget);

        return response()->json(['success' => true]);
    }

    /**
     * Save hiring plan for a capacity entry.
     */
    public function saveHires(Request $request, Budget $budget): JsonResponse
    {
        if (!auth()->user()->hasRole('super-admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'entry_id' => 'required|exists:budget_capacity_entries,id',
            'hires' => 'required|array',
            'hires.*.month' => 'required|integer|min:1|max:12',
            'hires.*.count' => 'required|integer|min:1',
            'hires.*.hourly_rate' => 'nullable|numeric|min:0',
        ]);

        $entry = BudgetCapacityEntry::find($validated['entry_id']);

        if ($entry->budget_id !== $budget->id) {
            return response()->json(['error' => 'Invalid entry'], 400);
        }

        DB::transaction(function () use ($entry, $validated) {
            // Delete existing hires
            $entry->plannedHires()->delete();

            // Create new hires
            foreach ($validated['hires'] as $hireData) {
                BudgetCapacityHire::create([
                    'budget_capacity_entry_id' => $entry->id,
                    'hire_month' => $hireData['month'],
                    'hire_count' => $hireData['count'],
                    'expected_hourly_rate' => $hireData['hourly_rate'] ?? null,
                ]);
            }

            // Recalculate weighted headcount
            $hires = collect($validated['hires'])->map(fn($h) => [
                'month' => $h['month'],
                'count' => $h['count'],
            ])->toArray();

            $weighted = app(CapacityCalculationService::class)->calculateWeightedHeadcount(
                $entry->next_year_base_headcount,
                $hires
            );

            // Recalculate budgeted income
            $budgetedIncome = app(CapacityCalculationService::class)->calculateBudgetedIncome(
                $weighted,
                $entry->last_year_available_hours_monthly,
                $entry->next_year_avg_hourly_rate ?? 0,
                $entry->next_year_billable_pct ?? 0
            );

            $entry->update([
                'next_year_weighted_headcount' => $weighted,
                'budgeted_income' => $budgetedIncome,
            ]);
        });

        // Sync new hires to Personnel tab
        $this->syncHiresToPersonnel($budget, $entry);

        return response()->json(['success' => true]);
    }

    /**
     * Sync capacity hires to personnel entries.
     *
     * When hires are added in Capacity, they should appear in Personnel tab.
     */
    protected function syncHiresToPersonnel(Budget $budget, BudgetCapacityEntry $capacityEntry): void
    {
        // Remove existing personnel entries linked to this capacity entry's hires
        $budget->personnelEntries()
            ->whereNotNull('budget_capacity_hire_id')
            ->whereHas('capacityHire', fn($q) => $q->where('budget_capacity_entry_id', $capacityEntry->id))
            ->delete();

        // Create personnel entries for each hire
        foreach ($capacityEntry->plannedHires as $hire) {
            \Modules\Accounting\Models\Budget\BudgetPersonnelEntry::create([
                'budget_id' => $budget->id,
                'employee_id' => null, // New hire, no employee yet
                'employee_name' => "New Hire ({$capacityEntry->product->name})",
                'current_salary' => 0,
                'proposed_salary' => $hire->expected_hourly_rate
                    ? $hire->expected_hourly_rate * 110 // Convert hourly to monthly (rough estimate)
                    : null,
                'is_new_hire' => true,
                'hire_month' => $hire->hire_month,
                'budget_capacity_hire_id' => $hire->id,
            ]);
        }
    }

    /**
     * Update Result entries with Capacity values.
     */
    protected function updateResultEntries(Budget $budget): void
    {
        foreach ($budget->capacityEntries as $capacityEntry) {
            $budget->resultEntries()->updateOrCreate(
                ['product_id' => $capacityEntry->product_id],
                ['capacity_value' => $capacityEntry->budgeted_income]
            );
        }
    }
}
```

### 4.3 Capacity Tab View

**File:** `Modules/Accounting/resources/views/budget/partials/tabs/capacity.blade.php`

```html
<div class="tab-pane fade" id="capacity-tab" role="tabpanel">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Capacity Planning</h5>
            <small class="text-muted">Budget based on developer capacity and billable hours</small>
        </div>
        <div class="card-body">
            <!-- Info Alert -->
            <div class="alert alert-info mb-4">
                <i class="ti ti-info-circle me-2"></i>
                <strong>How it works:</strong> Last year data is calculated from actual employee headcount and income.
                Adjust next year values to project budgeted income based on capacity.
            </div>

            <!-- Capacity Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-sm" id="capacityTable">
                    <thead>
                        <tr>
                            <th rowspan="2" class="align-middle">Product</th>
                            <th colspan="6" class="text-center bg-light">Last Year (Calculated)</th>
                            <th colspan="5" class="text-center bg-success-subtle">Next Year (Budget)</th>
                        </tr>
                        <tr>
                            <!-- Last Year -->
                            <th class="text-end bg-light">Headcount</th>
                            <th class="text-end bg-light">Avail Hrs/Mo</th>
                            <th class="text-end bg-light">Avg Rate</th>
                            <th class="text-end bg-light">Income</th>
                            <th class="text-end bg-light">Bill Hrs/Mo</th>
                            <th class="text-end bg-light">Bill %</th>
                            <!-- Next Year -->
                            <th class="text-center bg-success-subtle" style="width: 120px;">Headcount</th>
                            <th class="text-end bg-success-subtle" style="width: 100px;">Avg Rate</th>
                            <th class="text-end bg-success-subtle" style="width: 100px;">Bill %</th>
                            <th class="text-end bg-success-subtle">Weighted HC</th>
                            <th class="text-end bg-success-subtle">Budgeted Income</th>
                        </tr>
                    </thead>
                    <tbody id="capacityTableBody">
                        <!-- Populated via JavaScript -->
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary">
                            <th>Total</th>
                            <th class="text-end" id="totalLastHeadcount">-</th>
                            <th class="text-end">-</th>
                            <th class="text-end">-</th>
                            <th class="text-end" id="totalLastIncome">-</th>
                            <th class="text-end">-</th>
                            <th class="text-end">-</th>
                            <th class="text-end" id="totalNextHeadcount">-</th>
                            <th class="text-end">-</th>
                            <th class="text-end">-</th>
                            <th class="text-end" id="totalWeightedHC">-</th>
                            <th class="text-end" id="totalBudgetedIncome">-</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Hiring Plan Modal -->
            <div class="modal fade" id="hiringPlanModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Hiring Plan - <span id="hiringProductName"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted mb-3">
                                Plan new hires for this product. Each hire is weighted by months active in the year.
                            </p>
                            <div id="hiresContainer">
                                <!-- Hire rows added here -->
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addHireRow">
                                <i class="ti ti-plus me-1"></i>Add Hire
                            </button>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveHiringPlan">Save Plan</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let capacityData = [];
    let currentEntryId = null;
    const budgetId = {{ $budget->id }};
    const canEdit = {{ $budget->canEdit() && auth()->user()->hasRole('super-admin') ? 'true' : 'false' }};

    // Load data when tab is shown
    document.querySelector('a[href="#capacity-tab"]')?.addEventListener('shown.bs.tab', loadCapacityData);

    async function loadCapacityData() {
        try {
            const response = await fetch(`/accounting/budget/${budgetId}/capacity`);
            const data = await response.json();

            if (data.success) {
                capacityData = data.entries;
                renderCapacityTable();
            }
        } catch (error) {
            console.error('Error loading capacity data:', error);
        }
    }

    function renderCapacityTable() {
        const tbody = document.getElementById('capacityTableBody');
        tbody.innerHTML = '';

        let totals = {
            lastHeadcount: 0, lastIncome: 0,
            nextHeadcount: 0, weightedHC: 0, budgetedIncome: 0
        };

        capacityData.forEach(entry => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${entry.product_name}</td>
                <!-- Last Year (read-only) -->
                <td class="text-end bg-light">${entry.last_year.headcount}</td>
                <td class="text-end bg-light">${entry.last_year.available_hours.toFixed(1)}</td>
                <td class="text-end bg-light">${formatCurrency(entry.last_year.avg_hourly_rate)}</td>
                <td class="text-end bg-light">${formatCurrency(entry.last_year.income)}</td>
                <td class="text-end bg-light">${entry.last_year.billable_hours.toFixed(1)}</td>
                <td class="text-end bg-light">${entry.last_year.billable_pct.toFixed(1)}%</td>
                <!-- Next Year (editable) -->
                <td class="text-center">
                    <div class="d-flex align-items-center justify-content-center gap-1">
                        <input type="number" class="form-control form-control-sm text-center base-headcount"
                               data-entry-id="${entry.id}" value="${entry.next_year.base_headcount}"
                               style="width: 60px;" ${!canEdit ? 'disabled' : ''}>
                        <button type="button" class="btn btn-sm btn-icon btn-outline-primary open-hiring"
                                data-entry-id="${entry.id}" data-product-name="${entry.product_name}"
                                title="Hiring Plan (${entry.total_hires} planned)">
                            <i class="ti ti-user-plus"></i>
                            ${entry.total_hires > 0 ? `<span class="badge bg-success position-absolute" style="top:-5px;right:-5px;font-size:10px">${entry.total_hires}</span>` : ''}
                        </button>
                    </div>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm text-end avg-rate"
                           data-entry-id="${entry.id}" value="${entry.next_year.avg_hourly_rate || ''}"
                           step="0.01" ${!canEdit ? 'disabled' : ''}>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" class="form-control text-end billable-pct"
                               data-entry-id="${entry.id}" value="${entry.next_year.billable_pct || ''}"
                               step="0.1" min="0" max="100" ${!canEdit ? 'disabled' : ''}>
                        <span class="input-group-text">%</span>
                    </div>
                </td>
                <td class="text-end weighted-hc">${entry.next_year.weighted_headcount?.toFixed(2) || '-'}</td>
                <td class="text-end fw-bold budgeted-income">${formatCurrency(entry.next_year.budgeted_income)}</td>
            `;
            tbody.appendChild(row);

            // Accumulate totals
            totals.lastHeadcount += entry.last_year.headcount || 0;
            totals.lastIncome += entry.last_year.income || 0;
            totals.nextHeadcount += entry.next_year.base_headcount || 0;
            totals.weightedHC += entry.next_year.weighted_headcount || 0;
            totals.budgetedIncome += entry.next_year.budgeted_income || 0;
        });

        // Update totals
        document.getElementById('totalLastHeadcount').textContent = totals.lastHeadcount;
        document.getElementById('totalLastIncome').textContent = formatCurrency(totals.lastIncome);
        document.getElementById('totalNextHeadcount').textContent = totals.nextHeadcount;
        document.getElementById('totalWeightedHC').textContent = totals.weightedHC.toFixed(2);
        document.getElementById('totalBudgetedIncome').textContent = formatCurrency(totals.budgetedIncome);

        attachEventListeners();
    }

    function attachEventListeners() {
        // Input changes
        document.querySelectorAll('.base-headcount, .avg-rate, .billable-pct').forEach(input => {
            input.addEventListener('change', function() {
                const entryId = this.dataset.entryId;
                const entry = capacityData.find(e => e.id == entryId);
                if (entry) {
                    if (this.classList.contains('base-headcount')) {
                        entry.next_year.base_headcount = parseInt(this.value) || 0;
                    } else if (this.classList.contains('avg-rate')) {
                        entry.next_year.avg_hourly_rate = parseFloat(this.value) || 0;
                    } else if (this.classList.contains('billable-pct')) {
                        entry.next_year.billable_pct = parseFloat(this.value) || 0;
                    }
                    markUnsaved();
                }
            });
        });

        // Open hiring modal
        document.querySelectorAll('.open-hiring').forEach(btn => {
            btn.addEventListener('click', function() {
                openHiringModal(this.dataset.entryId, this.dataset.productName);
            });
        });
    }

    function openHiringModal(entryId, productName) {
        currentEntryId = entryId;
        document.getElementById('hiringProductName').textContent = productName;

        const entry = capacityData.find(e => e.id == entryId);
        const container = document.getElementById('hiresContainer');
        container.innerHTML = '';

        // Add existing hires
        if (entry.hires && entry.hires.length > 0) {
            entry.hires.forEach(hire => addHireRow(hire));
        } else {
            addHireRow(); // Add empty row
        }

        new bootstrap.Modal(document.getElementById('hiringPlanModal')).show();
    }

    function addHireRow(hire = null) {
        const container = document.getElementById('hiresContainer');
        const row = document.createElement('div');
        row.className = 'row mb-2 hire-row';
        row.innerHTML = `
            <div class="col-4">
                <select class="form-select form-select-sm hire-month">
                    ${[...Array(12)].map((_, i) => `
                        <option value="${i + 1}" ${hire?.month === i + 1 ? 'selected' : ''}>
                            ${new Date(2000, i, 1).toLocaleString('default', { month: 'long' })}
                        </option>
                    `).join('')}
                </select>
            </div>
            <div class="col-3">
                <input type="number" class="form-control form-control-sm hire-count"
                       placeholder="Count" value="${hire?.count || 1}" min="1">
            </div>
            <div class="col-4">
                <input type="number" class="form-control form-control-sm hire-rate"
                       placeholder="Hourly rate" value="${hire?.hourly_rate || ''}" step="0.01">
            </div>
            <div class="col-1">
                <button type="button" class="btn btn-sm btn-icon btn-outline-danger remove-hire">
                    <i class="ti ti-x"></i>
                </button>
            </div>
        `;
        container.appendChild(row);

        row.querySelector('.remove-hire').addEventListener('click', () => row.remove());
    }

    document.getElementById('addHireRow').addEventListener('click', () => addHireRow());

    document.getElementById('saveHiringPlan').addEventListener('click', async function() {
        const hires = [];
        document.querySelectorAll('.hire-row').forEach(row => {
            const month = parseInt(row.querySelector('.hire-month').value);
            const count = parseInt(row.querySelector('.hire-count').value) || 1;
            const rate = parseFloat(row.querySelector('.hire-rate').value) || null;

            if (month && count) {
                hires.push({ month, count, hourly_rate: rate });
            }
        });

        try {
            const response = await fetch(`/accounting/budget/${budgetId}/capacity/hires`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    entry_id: currentEntryId,
                    hires: hires
                })
            });

            const data = await response.json();
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('hiringPlanModal')).hide();
                loadCapacityData(); // Refresh to get updated calculations
            }
        } catch (error) {
            console.error('Error saving hiring plan:', error);
        }
    });

    function formatCurrency(value) {
        if (value === null || value === undefined) return '-';
        return new Intl.NumberFormat('en-EG', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value) + ' EGP';
    }

    function markUnsaved() {
        window.budgetHasUnsavedChanges = true;
    }

    // Expose save function
    window.saveCapacityTab = async function() {
        const entries = capacityData.map(entry => ({
            id: entry.id,
            base_headcount: entry.next_year.base_headcount,
            avg_hourly_rate: entry.next_year.avg_hourly_rate,
            billable_pct: entry.next_year.billable_pct
        }));

        try {
            const response = await fetch(`/accounting/budget/${budgetId}/capacity`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ entries })
            });

            return (await response.json()).success;
        } catch (error) {
            console.error('Error saving capacity data:', error);
            return false;
        }
    };
});
</script>
```

---

## Phase 5: Collection Tab

The Collection tab calculates budgeted income based on payment collection patterns and outstanding balances.

### 5.1 Collection Calculation Service

**File:** `Modules/Accounting/app/Services/Budget/CollectionCalculationService.php`

```php
<?php

namespace Modules\Accounting\Services\Budget;

class CollectionCalculationService
{
    /**
     * Calculate collection months from average balance and average payment.
     *
     * Collection Months = Average Outstanding Balance / Average Payment Per Month
     *
     * This tells us how many months of payments are "in the pipeline".
     * Lower is better (faster collection).
     *
     * Example:
     * - Average balance: $500,000
     * - Average payment/month: $250,000
     * - Collection months: 2.0 (takes 2 months to collect on average)
     */
    public function calculateCollectionMonths(float $avgBalance, float $avgPaymentMonthly): float
    {
        if ($avgPaymentMonthly <= 0) {
            return 0;
        }

        return round($avgBalance / $avgPaymentMonthly, 2);
    }

    /**
     * Calculate collection months from payment schedule patterns.
     *
     * This uses the budgeted payment patterns to project collection months.
     *
     * For each pattern:
     * 1. Calculate weighted average payment month
     * 2. Weight by percentage of contracts using that pattern
     *
     * Example:
     * Pattern A (20% of contracts): 60% in M1, 40% in M2 = 1.4 avg months
     * Pattern B (50% of contracts): 100% in M1 = 1.0 avg months
     * Pattern C (30% of contracts): 33% each M1,M2,M3 = 2.0 avg months
     *
     * Weighted: (0.20 × 1.4) + (0.50 × 1.0) + (0.30 × 2.0) = 1.38 months
     */
    public function calculatePatternCollectionMonths(array $patterns): float
    {
        if (empty($patterns)) {
            return 0;
        }

        $totalWeightedMonths = 0;
        $totalContractPct = 0;

        foreach ($patterns as $pattern) {
            $contractPct = $pattern['contract_percentage'] / 100;
            if ($contractPct <= 0) continue;

            // Calculate weighted average month for this pattern
            $weightedMonth = 0;
            for ($month = 1; $month <= 12; $month++) {
                $monthPct = ($pattern["month_{$month}_pct"] ?? 0) / 100;
                $weightedMonth += $month * $monthPct;
            }

            $totalWeightedMonths += $weightedMonth * $contractPct;
            $totalContractPct += $contractPct;
        }

        if ($totalContractPct <= 0) {
            return 0;
        }

        return round($totalWeightedMonths / $totalContractPct, 2);
    }

    /**
     * Calculate projected collection months (average of last year and pattern-based).
     */
    public function calculateProjectedCollectionMonths(float $lastYearMonths, float $patternMonths): float
    {
        if ($lastYearMonths <= 0 && $patternMonths <= 0) {
            return 0;
        }

        if ($lastYearMonths <= 0) {
            return $patternMonths;
        }

        if ($patternMonths <= 0) {
            return $lastYearMonths;
        }

        return round(($lastYearMonths + $patternMonths) / 2, 2);
    }

    /**
     * Calculate budgeted income from collection method.
     *
     * Formula:
     * 1. Average Collection/Month = End Balance / Projected Collection Months
     * 2. Target Income = Average Collection/Month × 12
     *
     * Example:
     * - End Balance: 853,405
     * - Collection Months: 2.02
     * - Avg Collection: 853,405 / 2.02 = 422,855
     * - Target Income: 422,855 × 12 = 5,074,263
     */
    public function calculateBudgetedIncome(float $endBalance, float $projectedCollectionMonths): float
    {
        if ($projectedCollectionMonths <= 0) {
            return 0;
        }

        $avgCollectionMonthly = $endBalance / $projectedCollectionMonths;
        return round($avgCollectionMonthly * 12, 2);
    }
}
```

### 5.2 Collection Controller

**File:** `Modules/Accounting/app/Http/Controllers/Budget/BudgetCollectionController.php`

```php
<?php

namespace Modules\Accounting\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Budget\Budget;
use Modules\Accounting\Models\Budget\BudgetCollectionEntry;
use Modules\Accounting\Models\Budget\BudgetCollectionPattern;
use Modules\Accounting\Services\Budget\BalanceService;
use Modules\Accounting\Services\Budget\CollectionCalculationService;

class BudgetCollectionController extends Controller
{
    public function __construct(
        protected BalanceService $balanceService,
        protected CollectionCalculationService $collectionService
    ) {}

    /**
     * Get collection tab data.
     */
    public function index(Budget $budget): JsonResponse
    {
        if (!auth()->user()->can('view-accounting-readonly')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $entries = $budget->collectionEntries()
            ->with(['product', 'patterns'])
            ->get();

        if ($entries->isEmpty()) {
            $entries = $this->initializeCollectionEntries($budget);
        }

        $data = $entries->map(function ($entry) {
            return [
                'id' => $entry->id,
                'product_id' => $entry->product_id,
                'product_name' => $entry->product->name,
                'last_year' => [
                    'beginning_balance' => $entry->beginning_balance,
                    'end_balance' => $entry->end_balance,
                    'avg_balance' => $entry->avg_balance,
                    'avg_contract_monthly' => $entry->avg_contract_monthly,
                    'avg_payment_monthly' => $entry->avg_payment_monthly,
                    'collection_months' => $entry->last_year_collection_months,
                ],
                'budgeted' => [
                    'pattern_collection_months' => $entry->pattern_collection_months,
                    'projected_collection_months' => $entry->projected_collection_months,
                    'budgeted_income' => $entry->budgeted_income,
                ],
                'patterns' => $entry->patterns->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->pattern_name,
                    'contract_pct' => $p->contract_percentage,
                    'months' => collect(range(1, 12))->mapWithKeys(fn($m) => [
                        $m => $p->{"month_{$m}_pct"}
                    ])->toArray(),
                ])->toArray(),
            ];
        });

        return response()->json([
            'success' => true,
            'budget_year' => $budget->budget_year,
            'can_edit' => $budget->canEdit() && auth()->user()->hasRole('super-admin'),
            'entries' => $data,
        ]);
    }

    /**
     * Initialize collection entries from balance data.
     */
    protected function initializeCollectionEntries(Budget $budget): \Illuminate\Support\Collection
    {
        $lastYear = $budget->getLastYear();
        $elapsedMonths = $budget->getElapsedMonths();

        $products = \Modules\Accounting\Models\Product::where('status', 'active')->get();
        $entries = collect();

        foreach ($products as $product) {
            // Calculate last year balances
            $beginningBalance = $this->balanceService->getBeginningBalance($product->id, $lastYear);
            $endBalance = $this->balanceService->getEndBalance($product->id, $lastYear);
            $avgBalance = ($beginningBalance + $endBalance) / 2;

            // Calculate averages
            $avgContractMonthly = $this->balanceService->getAverageContractPerMonth($product->id, $lastYear, $elapsedMonths);
            $avgPaymentMonthly = $this->balanceService->getAveragePaymentPerMonth($product->id, $lastYear, $elapsedMonths);

            // Calculate collection months
            $collectionMonths = $this->collectionService->calculateCollectionMonths($avgBalance, $avgPaymentMonthly);

            $entry = BudgetCollectionEntry::create([
                'budget_id' => $budget->id,
                'product_id' => $product->id,
                'beginning_balance' => $beginningBalance,
                'end_balance' => $endBalance,
                'avg_balance' => $avgBalance,
                'avg_contract_monthly' => $avgContractMonthly,
                'avg_payment_monthly' => $avgPaymentMonthly,
                'last_year_collection_months' => $collectionMonths,
            ]);

            // Create default pattern (100% paid in month 1)
            BudgetCollectionPattern::create([
                'budget_collection_entry_id' => $entry->id,
                'pattern_name' => 'Standard',
                'contract_percentage' => 100,
                'month_1_pct' => 100,
            ]);

            $entry->load(['product', 'patterns']);
            $entries->push($entry);
        }

        return $entries;
    }

    /**
     * Save payment patterns for a collection entry.
     */
    public function savePatterns(Request $request, Budget $budget): JsonResponse
    {
        if (!auth()->user()->hasRole('super-admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'entry_id' => 'required|exists:budget_collection_entries,id',
            'patterns' => 'required|array|min:1',
            'patterns.*.name' => 'required|string|max:100',
            'patterns.*.contract_pct' => 'required|numeric|min:0|max:100',
            'patterns.*.months' => 'required|array',
            'patterns.*.months.*' => 'numeric|min:0|max:100',
        ]);

        $entry = BudgetCollectionEntry::find($validated['entry_id']);

        if ($entry->budget_id !== $budget->id) {
            return response()->json(['error' => 'Invalid entry'], 400);
        }

        // Validate total contract percentage equals 100
        $totalPct = collect($validated['patterns'])->sum('contract_pct');
        if (abs($totalPct - 100) > 0.01) {
            return response()->json([
                'error' => 'Contract percentages must total 100%',
                'total' => $totalPct
            ], 422);
        }

        DB::transaction(function () use ($entry, $validated) {
            // Delete existing patterns
            $entry->patterns()->delete();

            // Create new patterns
            foreach ($validated['patterns'] as $patternData) {
                $pattern = new BudgetCollectionPattern([
                    'budget_collection_entry_id' => $entry->id,
                    'pattern_name' => $patternData['name'],
                    'contract_percentage' => $patternData['contract_pct'],
                ]);

                // Set month percentages
                foreach ($patternData['months'] as $month => $pct) {
                    $pattern->{"month_{$month}_pct"} = $pct;
                }

                $pattern->save();
            }

            // Recalculate collection months from patterns
            $patterns = $entry->fresh()->patterns->map(fn($p) => [
                'contract_percentage' => $p->contract_percentage,
                ...collect(range(1, 12))->mapWithKeys(fn($m) => ["month_{$m}_pct" => $p->{"month_{$m}_pct"}])->toArray()
            ])->toArray();

            $patternMonths = app(CollectionCalculationService::class)
                ->calculatePatternCollectionMonths($patterns);

            $projectedMonths = app(CollectionCalculationService::class)
                ->calculateProjectedCollectionMonths($entry->last_year_collection_months, $patternMonths);

            $budgetedIncome = app(CollectionCalculationService::class)
                ->calculateBudgetedIncome($entry->end_balance, $projectedMonths);

            $entry->update([
                'pattern_collection_months' => $patternMonths,
                'projected_collection_months' => $projectedMonths,
                'budgeted_income' => $budgetedIncome,
            ]);
        });

        // Update Result tab
        $this->updateResultEntries($budget);

        return response()->json(['success' => true]);
    }

    protected function updateResultEntries(Budget $budget): void
    {
        foreach ($budget->collectionEntries as $entry) {
            $budget->resultEntries()->updateOrCreate(
                ['product_id' => $entry->product_id],
                ['collection_value' => $entry->budgeted_income]
            );
        }
    }
}
```

---

## Phase 6: Result Tab

The Result tab consolidates values from Growth, Capacity, and Collection methods, calculates an average, and allows the user to select a final budget value.

### 6.1 Result Controller

**File:** `Modules/Accounting/app/Http/Controllers/Budget/BudgetResultController.php`

```php
<?php

namespace Modules\Accounting\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Accounting\Models\Budget\Budget;
use Modules\Accounting\Models\Budget\BudgetResultEntry;

class BudgetResultController extends Controller
{
    /**
     * Get result tab data.
     *
     * This aggregates data from Growth, Capacity, and Collection tabs.
     */
    public function index(Budget $budget): JsonResponse
    {
        if (!auth()->user()->can('view-accounting-readonly')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get or create result entries
        $entries = $budget->resultEntries()->with('product')->get();

        if ($entries->isEmpty()) {
            $entries = $this->initializeResultEntries($budget);
        } else {
            // Refresh values from other tabs
            $this->refreshResultEntries($budget);
            $entries = $budget->resultEntries()->with('product')->get();
        }

        $data = $entries->map(function ($entry) {
            // Calculate average of non-null values
            $values = array_filter([
                $entry->growth_value,
                $entry->capacity_value,
                $entry->collection_value
            ], fn($v) => $v !== null);

            $average = count($values) > 0 ? array_sum($values) / count($values) : null;

            return [
                'id' => $entry->id,
                'product_id' => $entry->product_id,
                'product_name' => $entry->product->name,
                'growth_value' => $entry->growth_value,
                'capacity_value' => $entry->capacity_value,
                'collection_value' => $entry->collection_value,
                'average_value' => $average ? round($average, 2) : null,
                'final_value' => $entry->final_value,
            ];
        });

        // Calculate totals
        $totals = [
            'growth' => $data->sum('growth_value'),
            'capacity' => $data->sum('capacity_value'),
            'collection' => $data->sum('collection_value'),
            'average' => $data->sum('average_value'),
            'final' => $data->sum('final_value'),
        ];

        return response()->json([
            'success' => true,
            'budget_year' => $budget->budget_year,
            'can_edit' => $budget->canEdit() && auth()->user()->hasRole('super-admin'),
            'entries' => $data,
            'totals' => $totals,
        ]);
    }

    /**
     * Initialize result entries from other tabs.
     */
    protected function initializeResultEntries(Budget $budget): \Illuminate\Support\Collection
    {
        // Get all products from Growth tab (most comprehensive list)
        $productIds = $budget->growthEntries()->pluck('product_id')
            ->merge($budget->capacityEntries()->pluck('product_id'))
            ->merge($budget->collectionEntries()->pluck('product_id'))
            ->unique();

        foreach ($productIds as $productId) {
            $growthEntry = $budget->growthEntries()->where('product_id', $productId)->first();
            $capacityEntry = $budget->capacityEntries()->where('product_id', $productId)->first();
            $collectionEntry = $budget->collectionEntries()->where('product_id', $productId)->first();

            BudgetResultEntry::create([
                'budget_id' => $budget->id,
                'product_id' => $productId,
                'growth_value' => $growthEntry?->budgeted_value,
                'capacity_value' => $capacityEntry?->budgeted_income,
                'collection_value' => $collectionEntry?->budgeted_income,
            ]);
        }

        return $budget->resultEntries()->with('product')->get();
    }

    /**
     * Refresh result entries with latest values from other tabs.
     */
    protected function refreshResultEntries(Budget $budget): void
    {
        foreach ($budget->resultEntries as $entry) {
            $growthEntry = $budget->growthEntries()->where('product_id', $entry->product_id)->first();
            $capacityEntry = $budget->capacityEntries()->where('product_id', $entry->product_id)->first();
            $collectionEntry = $budget->collectionEntries()->where('product_id', $entry->product_id)->first();

            $entry->update([
                'growth_value' => $growthEntry?->budgeted_value,
                'capacity_value' => $capacityEntry?->budgeted_income,
                'collection_value' => $collectionEntry?->budgeted_income,
            ]);
        }
    }

    /**
     * Save final values.
     */
    public function save(Request $request, Budget $budget): JsonResponse
    {
        if (!auth()->user()->hasRole('super-admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$budget->canEdit()) {
            return response()->json(['error' => 'Budget is locked'], 403);
        }

        $validated = $request->validate([
            'entries' => 'required|array',
            'entries.*.id' => 'required|exists:budget_result_entries,id',
            'entries.*.final_value' => 'nullable|numeric|min:0',
        ]);

        foreach ($validated['entries'] as $entryData) {
            $entry = BudgetResultEntry::find($entryData['id']);

            if ($entry->budget_id !== $budget->id) {
                continue;
            }

            $entry->update([
                'final_value' => $entryData['final_value'],
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Quick fill: Set final value to a specific method for all products.
     */
    public function quickFill(Request $request, Budget $budget): JsonResponse
    {
        if (!auth()->user()->hasRole('super-admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'method' => 'required|in:growth,capacity,collection,average',
        ]);

        foreach ($budget->resultEntries as $entry) {
            $value = match($validated['method']) {
                'growth' => $entry->growth_value,
                'capacity' => $entry->capacity_value,
                'collection' => $entry->collection_value,
                'average' => $this->calculateAverage($entry),
            };

            $entry->update(['final_value' => $value]);
        }

        return response()->json(['success' => true]);
    }

    protected function calculateAverage(BudgetResultEntry $entry): ?float
    {
        $values = array_filter([
            $entry->growth_value,
            $entry->capacity_value,
            $entry->collection_value
        ], fn($v) => $v !== null);

        return count($values) > 0 ? round(array_sum($values) / count($values), 2) : null;
    }
}
```

### 6.2 Result Tab View

**File:** `Modules/Accounting/resources/views/budget/partials/tabs/result.blade.php`

```html
<div class="tab-pane fade" id="result-tab" role="tabpanel">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Budget Results</h5>
                <small class="text-muted">Compare methods and select final budget per product</small>
            </div>
            <div class="dropdown" id="quickFillDropdown">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="ti ti-copy me-1"></i>Quick Fill Final Values
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item quick-fill" data-method="growth">Use Growth Values</a></li>
                    <li><a class="dropdown-item quick-fill" data-method="capacity">Use Capacity Values</a></li>
                    <li><a class="dropdown-item quick-fill" data-method="collection">Use Collection Values</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item quick-fill" data-method="average">Use Average Values</a></li>
                </ul>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="resultTable">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-end">Growth Method</th>
                            <th class="text-end">Capacity Method</th>
                            <th class="text-end">Collection Method</th>
                            <th class="text-end">Average</th>
                            <th class="text-end bg-primary-subtle" style="width: 180px;">Final Budget</th>
                        </tr>
                    </thead>
                    <tbody id="resultTableBody">
                        <!-- Populated via JavaScript -->
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary fw-bold">
                            <th>Total</th>
                            <th class="text-end" id="totalGrowth">-</th>
                            <th class="text-end" id="totalCapacity">-</th>
                            <th class="text-end" id="totalCollection">-</th>
                            <th class="text-end" id="totalAverage">-</th>
                            <th class="text-end bg-primary-subtle" id="totalFinal">-</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Visual Comparison -->
            <div class="mt-4">
                <h6>Method Comparison</h6>
                <canvas id="resultComparisonChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let resultData = [];
    let resultTotals = {};
    const budgetId = {{ $budget->id }};
    const canEdit = {{ $budget->canEdit() && auth()->user()->hasRole('super-admin') ? 'true' : 'false' }};
    let comparisonChart = null;

    document.querySelector('a[href="#result-tab"]')?.addEventListener('shown.bs.tab', loadResultData);

    async function loadResultData() {
        try {
            const response = await fetch(`/accounting/budget/${budgetId}/result`);
            const data = await response.json();

            if (data.success) {
                resultData = data.entries;
                resultTotals = data.totals;
                renderResultTable();
                renderComparisonChart();
            }
        } catch (error) {
            console.error('Error loading result data:', error);
        }
    }

    function renderResultTable() {
        const tbody = document.getElementById('resultTableBody');
        tbody.innerHTML = '';

        resultData.forEach(entry => {
            const row = document.createElement('tr');

            // Determine which method is highest (for highlighting)
            const methods = [
                { name: 'growth', value: entry.growth_value },
                { name: 'capacity', value: entry.capacity_value },
                { name: 'collection', value: entry.collection_value }
            ].filter(m => m.value !== null);

            const maxMethod = methods.reduce((max, m) => m.value > (max?.value || 0) ? m : max, null);

            row.innerHTML = `
                <td>${entry.product_name}</td>
                <td class="text-end ${maxMethod?.name === 'growth' ? 'text-success fw-bold' : ''}">
                    ${formatCurrency(entry.growth_value)}
                    <button class="btn btn-sm btn-icon btn-text-primary copy-value ms-1"
                            data-entry-id="${entry.id}" data-value="${entry.growth_value}"
                            title="Copy to Final" ${!canEdit || !entry.growth_value ? 'disabled' : ''}>
                        <i class="ti ti-arrow-right"></i>
                    </button>
                </td>
                <td class="text-end ${maxMethod?.name === 'capacity' ? 'text-success fw-bold' : ''}">
                    ${formatCurrency(entry.capacity_value)}
                    <button class="btn btn-sm btn-icon btn-text-primary copy-value ms-1"
                            data-entry-id="${entry.id}" data-value="${entry.capacity_value}"
                            title="Copy to Final" ${!canEdit || !entry.capacity_value ? 'disabled' : ''}>
                        <i class="ti ti-arrow-right"></i>
                    </button>
                </td>
                <td class="text-end ${maxMethod?.name === 'collection' ? 'text-success fw-bold' : ''}">
                    ${formatCurrency(entry.collection_value)}
                    <button class="btn btn-sm btn-icon btn-text-primary copy-value ms-1"
                            data-entry-id="${entry.id}" data-value="${entry.collection_value}"
                            title="Copy to Final" ${!canEdit || !entry.collection_value ? 'disabled' : ''}>
                        <i class="ti ti-arrow-right"></i>
                    </button>
                </td>
                <td class="text-end">
                    ${formatCurrency(entry.average_value)}
                    <button class="btn btn-sm btn-icon btn-text-primary copy-value ms-1"
                            data-entry-id="${entry.id}" data-value="${entry.average_value}"
                            title="Copy to Final" ${!canEdit || !entry.average_value ? 'disabled' : ''}>
                        <i class="ti ti-arrow-right"></i>
                    </button>
                </td>
                <td class="bg-primary-subtle">
                    <input type="number" class="form-control form-control-sm text-end fw-bold final-value"
                           data-entry-id="${entry.id}" value="${entry.final_value || ''}"
                           ${!canEdit ? 'disabled' : ''}>
                </td>
            `;
            tbody.appendChild(row);
        });

        // Update totals
        document.getElementById('totalGrowth').textContent = formatCurrency(resultTotals.growth);
        document.getElementById('totalCapacity').textContent = formatCurrency(resultTotals.capacity);
        document.getElementById('totalCollection').textContent = formatCurrency(resultTotals.collection);
        document.getElementById('totalAverage').textContent = formatCurrency(resultTotals.average);
        document.getElementById('totalFinal').textContent = formatCurrency(resultTotals.final);

        attachEventListeners();
    }

    function attachEventListeners() {
        // Copy value buttons
        document.querySelectorAll('.copy-value').forEach(btn => {
            btn.addEventListener('click', function() {
                const entryId = this.dataset.entryId;
                const value = this.dataset.value;
                const input = document.querySelector(`.final-value[data-entry-id="${entryId}"]`);
                if (input) {
                    input.value = value;
                    input.dispatchEvent(new Event('change'));
                }
            });
        });

        // Final value changes
        document.querySelectorAll('.final-value').forEach(input => {
            input.addEventListener('change', function() {
                const entryId = this.dataset.entryId;
                const entry = resultData.find(e => e.id == entryId);
                if (entry) {
                    entry.final_value = parseFloat(this.value) || null;
                    updateTotalFinal();
                    markUnsaved();
                }
            });
        });

        // Quick fill
        document.querySelectorAll('.quick-fill').forEach(link => {
            link.addEventListener('click', async function(e) {
                e.preventDefault();
                await quickFill(this.dataset.method);
            });
        });
    }

    async function quickFill(method) {
        try {
            const response = await fetch(`/accounting/budget/${budgetId}/result/quick-fill`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ method })
            });

            if ((await response.json()).success) {
                loadResultData();
            }
        } catch (error) {
            console.error('Error quick filling:', error);
        }
    }

    function updateTotalFinal() {
        const total = resultData.reduce((sum, e) => sum + (parseFloat(e.final_value) || 0), 0);
        document.getElementById('totalFinal').textContent = formatCurrency(total);
    }

    function renderComparisonChart() {
        const ctx = document.getElementById('resultComparisonChart').getContext('2d');

        if (comparisonChart) {
            comparisonChart.destroy();
        }

        comparisonChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Growth', 'Capacity', 'Collection', 'Average', 'Final'],
                datasets: [{
                    label: 'Total Budget',
                    data: [
                        resultTotals.growth,
                        resultTotals.capacity,
                        resultTotals.collection,
                        resultTotals.average,
                        resultTotals.final
                    ],
                    backgroundColor: [
                        'rgba(105, 108, 255, 0.8)',
                        'rgba(40, 199, 111, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 99, 132, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: v => formatCurrency(v) }
                    }
                }
            }
        });
    }

    function formatCurrency(value) {
        if (value === null || value === undefined) return '-';
        return new Intl.NumberFormat('en-EG', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value) + ' EGP';
    }

    function markUnsaved() {
        window.budgetHasUnsavedChanges = true;
    }

    window.saveResultTab = async function() {
        const entries = resultData.map(e => ({
            id: e.id,
            final_value: e.final_value
        }));

        try {
            const response = await fetch(`/accounting/budget/${budgetId}/result`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ entries })
            });

            return (await response.json()).success;
        } catch (error) {
            console.error('Error saving result data:', error);
            return false;
        }
    };
});
</script>
```

---

## Phase 7: Personnel Tab

The Personnel tab manages salary planning for all employees, with allocations to products and G&A.

### 7.1 Personnel Controller

**File:** `Modules/Accounting/app/Http/Controllers/Budget/BudgetPersonnelController.php`

```php
<?php

namespace Modules\Accounting\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Budget\Budget;
use Modules\Accounting\Models\Budget\BudgetPersonnelEntry;
use Modules\Accounting\Models\Budget\BudgetPersonnelAllocation;
use Modules\Accounting\Services\Budget\EmployeeDataService;
use Modules\HR\Models\Employee;

class BudgetPersonnelController extends Controller
{
    public function __construct(
        protected EmployeeDataService $employeeService
    ) {}

    /**
     * Get personnel tab data.
     *
     * Organizes employees by product allocation (can appear in multiple products).
     */
    public function index(Budget $budget): JsonResponse
    {
        if (!auth()->user()->can('view-accounting-readonly')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $entries = $budget->personnelEntries()
            ->with(['employee', 'allocations.product'])
            ->get();

        if ($entries->isEmpty()) {
            $entries = $this->initializePersonnelEntries($budget);
        }

        // Group by product for display
        $products = \Modules\Accounting\Models\Product::where('status', 'active')
            ->orderBy('name')
            ->get();

        $groupedData = [];

        // Product sections
        foreach ($products as $product) {
            $productEntries = $entries->filter(function ($entry) use ($product) {
                return $entry->allocations->contains('product_id', $product->id);
            });

            if ($productEntries->isNotEmpty()) {
                $groupedData[] = [
                    'section' => 'product',
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'entries' => $this->formatEntries($productEntries, $product->id),
                    'totals' => $this->calculateSectionTotals($productEntries, $product->id),
                ];
            }
        }

        // G&A section (employees with no product allocation or allocation to null product)
        $gaEntries = $entries->filter(function ($entry) {
            return $entry->allocations->isEmpty() ||
                   $entry->allocations->contains('product_id', null);
        });

        if ($gaEntries->isNotEmpty()) {
            $groupedData[] = [
                'section' => 'ga',
                'product_id' => null,
                'product_name' => 'General & Administrative (G&A)',
                'entries' => $this->formatEntries($gaEntries, null),
                'totals' => $this->calculateSectionTotals($gaEntries, null),
            ];
        }

        // Grand totals
        $grandTotals = [
            'current_salary' => $entries->sum('current_salary'),
            'proposed_salary' => $entries->sum('proposed_salary'),
            'increase_amount' => $entries->sum(fn($e) => ($e->proposed_salary ?? 0) - $e->current_salary),
        ];

        return response()->json([
            'success' => true,
            'budget_year' => $budget->budget_year,
            'can_edit' => $budget->canEdit() && auth()->user()->hasRole('super-admin'),
            'sections' => $groupedData,
            'grand_totals' => $grandTotals,
        ]);
    }

    protected function formatEntries($entries, $productId): array
    {
        return $entries->map(function ($entry) use ($productId) {
            $allocation = $productId !== null
                ? $entry->allocations->firstWhere('product_id', $productId)
                : $entry->allocations->firstWhere('product_id', null);

            $allocationPct = $allocation?->allocation_percentage ?? 100;
            $effectiveCost = ($entry->proposed_salary ?? 0) * ($allocationPct / 100);

            return [
                'id' => $entry->id,
                'employee_id' => $entry->employee_id,
                'employee_name' => $entry->employee_id
                    ? $entry->employee->full_name
                    : $entry->employee_name,
                'is_new_hire' => $entry->is_new_hire,
                'hire_month' => $entry->hire_month,
                'current_salary' => $entry->current_salary,
                'proposed_salary' => $entry->proposed_salary,
                'increase_pct' => $entry->current_salary > 0
                    ? round((($entry->proposed_salary ?? 0) - $entry->current_salary) / $entry->current_salary * 100, 1)
                    : null,
                'allocation_pct' => $allocationPct,
                'effective_cost' => round($effectiveCost, 2),
            ];
        })->values()->toArray();
    }

    protected function calculateSectionTotals($entries, $productId): array
    {
        $currentTotal = 0;
        $proposedTotal = 0;

        foreach ($entries as $entry) {
            $allocation = $productId !== null
                ? $entry->allocations->firstWhere('product_id', $productId)
                : $entry->allocations->firstWhere('product_id', null);

            $allocationPct = ($allocation?->allocation_percentage ?? 100) / 100;

            $currentTotal += $entry->current_salary * $allocationPct;
            $proposedTotal += ($entry->proposed_salary ?? 0) * $allocationPct;
        }

        return [
            'current_salary' => round($currentTotal, 2),
            'proposed_salary' => round($proposedTotal, 2),
            'increase_amount' => round($proposedTotal - $currentTotal, 2),
        ];
    }

    protected function initializePersonnelEntries(Budget $budget): \Illuminate\Support\Collection
    {
        // Get all active employees
        $employees = Employee::where('status', 'active')
            ->with('products')
            ->get();

        foreach ($employees as $employee) {
            $entry = BudgetPersonnelEntry::create([
                'budget_id' => $budget->id,
                'employee_id' => $employee->id,
                'current_salary' => $employee->monthly_salary ?? 0,
                'proposed_salary' => $employee->monthly_salary ?? 0,
            ]);

            // Create allocations based on employee's product assignments
            if ($employee->products->isNotEmpty()) {
                $pctPerProduct = 100 / $employee->products->count();
                foreach ($employee->products as $product) {
                    BudgetPersonnelAllocation::create([
                        'budget_personnel_entry_id' => $entry->id,
                        'product_id' => $product->id,
                        'allocation_percentage' => $pctPerProduct,
                    ]);
                }
            } else {
                // G&A employee
                BudgetPersonnelAllocation::create([
                    'budget_personnel_entry_id' => $entry->id,
                    'product_id' => null,
                    'allocation_percentage' => 100,
                ]);
            }
        }

        return $budget->personnelEntries()
            ->with(['employee', 'allocations.product'])
            ->get();
    }

    /**
     * Save personnel entries.
     */
    public function save(Request $request, Budget $budget): JsonResponse
    {
        if (!auth()->user()->hasRole('super-admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'entries' => 'required|array',
            'entries.*.id' => 'required|exists:budget_personnel_entries,id',
            'entries.*.proposed_salary' => 'nullable|numeric|min:0',
        ]);

        foreach ($validated['entries'] as $entryData) {
            $entry = BudgetPersonnelEntry::find($entryData['id']);

            if ($entry->budget_id !== $budget->id) {
                continue;
            }

            $proposedSalary = $entryData['proposed_salary'];
            $currentSalary = $entry->current_salary;

            // Calculate increase percentage
            $increasePct = $currentSalary > 0
                ? (($proposedSalary - $currentSalary) / $currentSalary) * 100
                : null;

            $entry->update([
                'proposed_salary' => $proposedSalary,
                'increase_percentage' => $increasePct ? round($increasePct, 2) : null,
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Add a new hire entry (independent of Capacity tab).
     */
    public function addNewHire(Request $request, Budget $budget): JsonResponse
    {
        if (!auth()->user()->hasRole('super-admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'employee_name' => 'required|string|max:255',
            'proposed_salary' => 'required|numeric|min:0',
            'hire_month' => 'required|integer|min:1|max:12',
            'product_id' => 'nullable|exists:products,id',
        ]);

        $entry = BudgetPersonnelEntry::create([
            'budget_id' => $budget->id,
            'employee_id' => null,
            'employee_name' => $validated['employee_name'],
            'current_salary' => 0,
            'proposed_salary' => $validated['proposed_salary'],
            'is_new_hire' => true,
            'hire_month' => $validated['hire_month'],
        ]);

        BudgetPersonnelAllocation::create([
            'budget_personnel_entry_id' => $entry->id,
            'product_id' => $validated['product_id'], // null = G&A
            'allocation_percentage' => 100,
        ]);

        // Sync to Capacity tab if product assigned
        if ($validated['product_id']) {
            $this->syncToCapacity($budget, $entry, $validated['product_id']);
        }

        return response()->json(['success' => true, 'entry_id' => $entry->id]);
    }

    protected function syncToCapacity(Budget $budget, BudgetPersonnelEntry $personnelEntry, int $productId): void
    {
        $capacityEntry = $budget->capacityEntries()
            ->where('product_id', $productId)
            ->first();

        if (!$capacityEntry) {
            return;
        }

        // Add a hire to capacity
        \Modules\Accounting\Models\Budget\BudgetCapacityHire::create([
            'budget_capacity_entry_id' => $capacityEntry->id,
            'hire_month' => $personnelEntry->hire_month,
            'hire_count' => 1,
            'expected_hourly_rate' => $personnelEntry->proposed_salary / 110, // Rough conversion
        ]);
    }
}
```

---

## Phase 8: OpEx/Taxes/CapEx Tabs

These three tabs share similar structure. The main difference is the expense category type filter.

### 8.1 Base Expense Controller

**File:** `Modules/Accounting/app/Http/Controllers/Budget/BudgetExpenseController.php`

```php
<?php

namespace Modules\Accounting\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Accounting\Models\Budget\Budget;
use Modules\Accounting\Models\Budget\BudgetExpenseEntry;
use Modules\Accounting\Services\Budget\ExpenseDataService;
use App\Models\ExpenseCategory;

class BudgetExpenseController extends Controller
{
    protected string $expenseType; // 'opex', 'tax', or 'capex'
    protected string $globalIncreasePctField; // 'opex_global_increase_pct' or 'tax_global_increase_pct'

    public function __construct(
        protected ExpenseDataService $expenseService
    ) {
        // Will be set by child controllers
    }

    /**
     * Get expense tab data.
     */
    public function index(Budget $budget): JsonResponse
    {
        if (!auth()->user()->can('view-accounting-readonly')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $entries = $budget->expenseEntries()
            ->with('expenseCategory')
            ->where('expense_type', $this->expenseType)
            ->get();

        if ($entries->isEmpty()) {
            $entries = $this->initializeExpenseEntries($budget);
        }

        // Get global increase percentage
        $globalIncreasePct = $budget->{$this->globalIncreasePctField} ?? 10;

        $data = $entries->map(function ($entry) use ($globalIncreasePct) {
            // Calculate proposed values
            $effectiveIncreasePct = $entry->custom_increase_pct ?? $globalIncreasePct;

            $proposedAvgMonthly = $entry->override_amount
                ?? ($entry->last_year_avg_monthly * (1 + $effectiveIncreasePct / 100));

            $proposedTotal = $proposedAvgMonthly * 12;

            return [
                'id' => $entry->id,
                'category_id' => $entry->expense_category_id,
                'category_name' => $entry->expenseCategory->name,
                'last_year' => [
                    'total' => $entry->last_year_total,
                    'avg_monthly' => $entry->last_year_avg_monthly,
                ],
                'budget' => [
                    'uses_global' => $entry->custom_increase_pct === null && $entry->override_amount === null,
                    'increase_pct' => $entry->custom_increase_pct ?? $globalIncreasePct,
                    'override_amount' => $entry->override_amount,
                    'proposed_avg_monthly' => round($proposedAvgMonthly, 2),
                    'proposed_total' => round($proposedTotal, 2),
                ],
            ];
        });

        $totals = [
            'last_year_total' => $data->sum('last_year.total'),
            'proposed_total' => $data->sum('budget.proposed_total'),
        ];

        return response()->json([
            'success' => true,
            'budget_year' => $budget->budget_year,
            'can_edit' => $budget->canEdit() && auth()->user()->hasRole('super-admin'),
            'global_increase_pct' => $globalIncreasePct,
            'entries' => $data,
            'totals' => $totals,
        ]);
    }

    protected function initializeExpenseEntries(Budget $budget): \Illuminate\Support\Collection
    {
        $lastYear = $budget->getLastYear();
        $elapsedMonths = $budget->getElapsedMonths();

        $categories = $this->expenseService->getCategoriesByType($this->expenseType);

        foreach ($categories as $category) {
            $total = $this->expenseService->getExpensesByCategory($category->id, $lastYear, $elapsedMonths);
            $avgMonthly = $total / 12;

            BudgetExpenseEntry::create([
                'budget_id' => $budget->id,
                'expense_category_id' => $category->id,
                'expense_type' => $this->expenseType,
                'last_year_total' => $total,
                'last_year_avg_monthly' => round($avgMonthly, 2),
            ]);
        }

        return $budget->expenseEntries()
            ->with('expenseCategory')
            ->where('expense_type', $this->expenseType)
            ->get();
    }

    /**
     * Save expense entries.
     */
    public function save(Request $request, Budget $budget): JsonResponse
    {
        if (!auth()->user()->hasRole('super-admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'entries' => 'required|array',
            'entries.*.id' => 'required|exists:budget_expense_entries,id',
            'entries.*.custom_increase_pct' => 'nullable|numeric|min:-100|max:1000',
            'entries.*.override_amount' => 'nullable|numeric|min:0',
        ]);

        foreach ($validated['entries'] as $entryData) {
            $entry = BudgetExpenseEntry::find($entryData['id']);

            if ($entry->budget_id !== $budget->id) {
                continue;
            }

            $entry->update([
                'custom_increase_pct' => $entryData['custom_increase_pct'],
                'override_amount' => $entryData['override_amount'],
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Set global increase percentage.
     */
    public function setGlobalIncrease(Request $request, Budget $budget): JsonResponse
    {
        if (!auth()->user()->hasRole('super-admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'increase_pct' => 'required|numeric|min:-100|max:1000',
        ]);

        $budget->update([
            $this->globalIncreasePctField => $validated['increase_pct'],
        ]);

        return response()->json(['success' => true]);
    }
}
```

### 8.2 OpEx, Taxes, and CapEx Controllers

```php
// Modules/Accounting/app/Http/Controllers/Budget/BudgetOpExController.php
<?php

namespace Modules\Accounting\Http\Controllers\Budget;

class BudgetOpExController extends BudgetExpenseController
{
    protected string $expenseType = 'opex';
    protected string $globalIncreasePctField = 'opex_global_increase_pct';
}

// Modules/Accounting/app/Http/Controllers/Budget/BudgetTaxesController.php
<?php

namespace Modules\Accounting\Http\Controllers\Budget;

class BudgetTaxesController extends BudgetExpenseController
{
    protected string $expenseType = 'tax';
    protected string $globalIncreasePctField = 'tax_global_increase_pct';
}

// Modules/Accounting/app/Http/Controllers/Budget/BudgetCapExController.php
<?php

namespace Modules\Accounting\Http\Controllers\Budget;

class BudgetCapExController extends BudgetExpenseController
{
    protected string $expenseType = 'capex';
    protected string $globalIncreasePctField = 'opex_global_increase_pct'; // CapEx uses same or no global
}
```

---

## Phase 9: P&L Tab

The P&L (Profit & Loss) tab is a read-only summary view that consolidates data from all other tabs.

### 9.1 P&L Controller

**File:** `Modules/Accounting/app/Http/Controllers/Budget/BudgetPnLController.php`

```php
<?php

namespace Modules\Accounting\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Accounting\Models\Budget\Budget;
use Modules\Accounting\Services\Budget\ExpenseDataService;

class BudgetPnLController extends Controller
{
    public function __construct(
        protected ExpenseDataService $expenseService
    ) {}

    /**
     * Get P&L summary data.
     *
     * This consolidates all budget data into P&L format:
     * - Revenue: From Result tab (final values)
     * - Cost of Sales: From OpEx (specific category)
     * - VAT: From Taxes (specific category)
     * - Salaries: From Personnel tab (product allocations + G&A)
     * - Sales Commissions: From OpEx (specific category)
     * - OpEx: From OpEx tab (excluding Cost of Sales, Commissions)
     * - Taxes: From Taxes tab (excluding VAT)
     * - CapEx: From CapEx tab
     */
    public function index(Budget $budget): JsonResponse
    {
        if (!auth()->user()->can('view-accounting-readonly')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get special category IDs
        $costOfSalesCategoryId = $this->expenseService->getCostOfSalesCategoryId();
        $vatCategoryId = $this->expenseService->getVatCategoryId();
        $commissionsCategoryId = $this->expenseService->getSalesCommissionsCategoryId();

        // ===== REVENUE =====
        $revenue = [];
        foreach ($budget->resultEntries()->with('product')->get() as $entry) {
            $revenue[] = [
                'name' => $entry->product->name,
                'budgeted' => $entry->final_value ?? 0,
                'last_year' => $entry->growth_value ?? 0, // Using growth (historical) as proxy
            ];
        }
        $totalRevenue = collect($revenue)->sum('budgeted');

        // ===== COST OF SALES =====
        $costOfSales = 0;
        $costOfSalesLastYear = 0;
        if ($costOfSalesCategoryId) {
            $entry = $budget->expenseEntries()
                ->where('expense_category_id', $costOfSalesCategoryId)
                ->first();
            if ($entry) {
                $costOfSales = $this->calculateProposedTotal($entry, $budget);
                $costOfSalesLastYear = $entry->last_year_total ?? 0;
            }
        }

        // ===== VAT =====
        $vat = 0;
        $vatLastYear = 0;
        if ($vatCategoryId) {
            $entry = $budget->expenseEntries()
                ->where('expense_category_id', $vatCategoryId)
                ->first();
            if ($entry) {
                $vat = $this->calculateProposedTotal($entry, $budget);
                $vatLastYear = $entry->last_year_total ?? 0;
            }
        }

        // GROSS PROFIT = Revenue - Cost of Sales - VAT
        $grossProfit = $totalRevenue - $costOfSales - $vat;

        // ===== SALARIES (Product + G&A) =====
        $productSalaries = 0;
        $gaSalaries = 0;

        foreach ($budget->personnelEntries()->with('allocations')->get() as $entry) {
            $proposedSalary = $entry->proposed_salary ?? 0;

            foreach ($entry->allocations as $allocation) {
                $amount = $proposedSalary * ($allocation->allocation_percentage / 100) * 12; // Annual

                if ($allocation->product_id) {
                    $productSalaries += $amount;
                } else {
                    $gaSalaries += $amount;
                }
            }
        }

        // ===== SALES COMMISSIONS =====
        $commissions = 0;
        if ($commissionsCategoryId) {
            $entry = $budget->expenseEntries()
                ->where('expense_category_id', $commissionsCategoryId)
                ->first();
            if ($entry) {
                $commissions = $this->calculateProposedTotal($entry, $budget);
            }
        }

        // EARNINGS = Gross Profit - Product Salaries - Commissions
        $earnings = $grossProfit - $productSalaries - $commissions;

        // ===== OTHER OPEX (excluding special categories) =====
        $otherOpEx = 0;
        $excludedOpExIds = array_filter([$costOfSalesCategoryId, $commissionsCategoryId]);

        foreach ($budget->expenseEntries()->where('expense_type', 'opex')->get() as $entry) {
            if (!in_array($entry->expense_category_id, $excludedOpExIds)) {
                $otherOpEx += $this->calculateProposedTotal($entry, $budget);
            }
        }

        // ===== OTHER TAXES (excluding VAT) =====
        $otherTaxes = 0;
        foreach ($budget->expenseEntries()->where('expense_type', 'tax')->get() as $entry) {
            if ($entry->expense_category_id !== $vatCategoryId) {
                $otherTaxes += $this->calculateProposedTotal($entry, $budget);
            }
        }

        // PROFIT = Earnings - G&A Salaries - Other OpEx - Other Taxes
        $profit = $earnings - $gaSalaries - $otherOpEx - $otherTaxes;

        // ===== CAPEX =====
        $capex = 0;
        foreach ($budget->expenseEntries()->where('expense_type', 'capex')->get() as $entry) {
            $capex += $this->calculateProposedTotal($entry, $budget);
        }

        // Build P&L structure
        $pnl = [
            'revenue' => [
                'items' => $revenue,
                'total' => $totalRevenue,
            ],
            'cost_of_sales' => $costOfSales,
            'vat' => $vat,
            'gross_profit' => $grossProfit,
            'direct_expenses' => [
                'product_salaries' => $productSalaries,
                'commissions' => $commissions,
            ],
            'earnings' => $earnings,
            'contribution' => [
                'ga_salaries' => $gaSalaries,
                'opex' => $otherOpEx,
                'taxes' => $otherTaxes,
            ],
            'profit' => $profit,
            'capex' => $capex,
            'net_cash_flow' => $profit - $capex,
        ];

        return response()->json([
            'success' => true,
            'budget_year' => $budget->budget_year,
            'pnl' => $pnl,
        ]);
    }

    protected function calculateProposedTotal($entry, Budget $budget): float
    {
        if ($entry->override_amount) {
            return $entry->override_amount * 12;
        }

        $globalIncreasePct = $entry->expense_type === 'tax'
            ? $budget->tax_global_increase_pct
            : $budget->opex_global_increase_pct;

        $effectiveIncreasePct = $entry->custom_increase_pct ?? $globalIncreasePct ?? 10;
        $proposedAvgMonthly = $entry->last_year_avg_monthly * (1 + $effectiveIncreasePct / 100);

        return round($proposedAvgMonthly * 12, 2);
    }
}
```

---

## Phase 10: Finalization

The finalization process applies budget data to the main system.

### 10.1 Finalization Service

**File:** `Modules/Accounting/app/Services/Budget/BudgetFinalizationService.php`

```php
<?php

namespace Modules\Accounting\Services\Budget;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\Models\Budget\Budget;
use Modules\Accounting\Models\Product;
use Modules\HR\Models\Employee;
use App\Models\ExpenseCategory;

class BudgetFinalizationService
{
    /**
     * Validate budget before finalization.
     *
     * @return array ['valid' => bool, 'errors' => []]
     */
    public function validate(Budget $budget): array
    {
        $errors = [];

        // Check all products have final values
        $missingFinal = $budget->resultEntries()
            ->whereNull('final_value')
            ->with('product')
            ->get();

        if ($missingFinal->isNotEmpty()) {
            $errors[] = 'Missing final budget values for: ' .
                $missingFinal->pluck('product.name')->join(', ');
        }

        // Check all personnel have proposed salaries
        $missingSalaries = $budget->personnelEntries()
            ->whereNull('proposed_salary')
            ->get();

        if ($missingSalaries->isNotEmpty()) {
            $errors[] = "Missing proposed salaries for {$missingSalaries->count()} employees";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Preview what will be updated when finalized.
     */
    public function previewChanges(Budget $budget): array
    {
        $changes = [
            'products' => [],
            'employees' => [],
            'expense_categories' => [],
        ];

        // Product targets
        foreach ($budget->resultEntries()->with('product')->get() as $entry) {
            $product = $entry->product;
            $changes['products'][] = [
                'id' => $product->id,
                'name' => $product->name,
                'current_target' => $product->yearly_budget ?? 0,
                'new_target' => $entry->final_value ?? 0,
            ];
        }

        // Employee salaries
        foreach ($budget->personnelEntries()->with('employee')->get() as $entry) {
            if ($entry->employee_id) {
                $employee = $entry->employee;
                $changes['employees'][] = [
                    'id' => $employee->id,
                    'name' => $employee->full_name,
                    'current_salary' => $entry->current_salary,
                    'new_salary' => $entry->proposed_salary,
                    'change' => ($entry->proposed_salary ?? 0) - $entry->current_salary,
                ];
            }
        }

        // Expense category budgets
        foreach ($budget->expenseEntries()->with('expenseCategory')->get() as $entry) {
            $proposedTotal = $this->calculateProposedTotal($entry, $budget);
            $changes['expense_categories'][] = [
                'id' => $entry->expense_category_id,
                'name' => $entry->expenseCategory->name,
                'type' => $entry->expense_type,
                'new_budget' => $proposedTotal,
            ];
        }

        return $changes;
    }

    /**
     * Finalize budget and apply to system.
     */
    public function finalize(Budget $budget): bool
    {
        // Validate first
        $validation = $this->validate($budget);
        if (!$validation['valid']) {
            throw new \Exception('Budget validation failed: ' . implode('; ', $validation['errors']));
        }

        try {
            DB::transaction(function () use ($budget) {
                // 1. Update product yearly targets
                $this->updateProductTargets($budget);

                // 2. Update employee salaries (for next FY)
                $this->updateEmployeeSalaries($budget);

                // 3. Update expense category budgets
                $this->updateExpenseCategoryBudgets($budget);

                // 4. Mark budget as finalized
                $budget->update([
                    'status' => 'finalized',
                    'finalized_at' => now(),
                    'finalized_by' => auth()->id(),
                ]);

                // 5. Create audit log
                $this->createAuditLog($budget);
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Budget finalization failed', [
                'budget_id' => $budget->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function updateProductTargets(Budget $budget): void
    {
        foreach ($budget->resultEntries as $entry) {
            Product::where('id', $entry->product_id)->update([
                'yearly_budget' => $entry->final_value,
                'budget_year' => $budget->budget_year,
            ]);
        }
    }

    protected function updateEmployeeSalaries(Budget $budget): void
    {
        foreach ($budget->personnelEntries as $entry) {
            if ($entry->employee_id && $entry->proposed_salary) {
                Employee::where('id', $entry->employee_id)->update([
                    'next_year_salary' => $entry->proposed_salary,
                    'salary_effective_date' => "{$budget->budget_year}-01-01",
                ]);
            }
        }
    }

    protected function updateExpenseCategoryBudgets(Budget $budget): void
    {
        foreach ($budget->expenseEntries as $entry) {
            $proposedTotal = $this->calculateProposedTotal($entry, $budget);

            ExpenseCategory::where('id', $entry->expense_category_id)->update([
                'yearly_budget' => $proposedTotal,
                'budget_year' => $budget->budget_year,
            ]);
        }
    }

    protected function calculateProposedTotal($entry, Budget $budget): float
    {
        if ($entry->override_amount) {
            return $entry->override_amount * 12;
        }

        $globalIncreasePct = $entry->expense_type === 'tax'
            ? $budget->tax_global_increase_pct
            : $budget->opex_global_increase_pct;

        $effectiveIncreasePct = $entry->custom_increase_pct ?? $globalIncreasePct ?? 10;
        return round($entry->last_year_avg_monthly * (1 + $effectiveIncreasePct / 100) * 12, 2);
    }

    protected function createAuditLog(Budget $budget): void
    {
        // Create audit log entry
        DB::table('audit_logs')->insert([
            'user_id' => auth()->id(),
            'action' => 'budget_finalized',
            'model_type' => Budget::class,
            'model_id' => $budget->id,
            'changes' => json_encode([
                'budget_year' => $budget->budget_year,
                'total_revenue' => $budget->resultEntries()->sum('final_value'),
                'employee_count' => $budget->personnelEntries()->count(),
            ]),
            'created_at' => now(),
        ]);
    }
}
```

### 10.2 Finalization Controller

**File:** `Modules/Accounting/app/Http/Controllers/Budget/BudgetFinalizationController.php`

```php
<?php

namespace Modules\Accounting\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Accounting\Models\Budget\Budget;
use Modules\Accounting\Services\Budget\BudgetFinalizationService;

class BudgetFinalizationController extends Controller
{
    public function __construct(
        protected BudgetFinalizationService $finalizationService
    ) {}

    /**
     * Preview finalization changes.
     */
    public function preview(Budget $budget): JsonResponse
    {
        if (!auth()->user()->hasRole('super-admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validation = $this->finalizationService->validate($budget);
        $changes = $this->finalizationService->previewChanges($budget);

        return response()->json([
            'success' => true,
            'validation' => $validation,
            'changes' => $changes,
        ]);
    }

    /**
     * Finalize budget.
     */
    public function finalize(Budget $budget): JsonResponse
    {
        if (!auth()->user()->hasRole('super-admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($budget->status === 'finalized') {
            return response()->json([
                'error' => 'Budget is already finalized'
            ], 400);
        }

        try {
            $this->finalizationService->finalize($budget);

            return response()->json([
                'success' => true,
                'message' => 'Budget finalized successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Finalization failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
```

---

## Phase 11: Testing

### 11.1 Unit Tests for Calculation Services

**File:** `Modules/Accounting/tests/Unit/Services/GrowthCalculationServiceTest.php`

```php
<?php

namespace Modules\Accounting\Tests\Unit\Services;

use Tests\TestCase;
use Modules\Accounting\Services\Budget\GrowthCalculationService;

class GrowthCalculationServiceTest extends TestCase
{
    protected GrowthCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GrowthCalculationService();
    }

    public function test_linear_projection_with_increasing_trend(): void
    {
        $dataPoints = [
            2021 => 100000,
            2022 => 120000,
            2023 => 140000,
        ];

        $projected = $this->service->projectValue($dataPoints, 'linear', 2024);

        // Should project approximately 160000
        $this->assertGreaterThan(155000, $projected);
        $this->assertLessThan(165000, $projected);
    }

    public function test_linear_projection_with_decreasing_trend(): void
    {
        $dataPoints = [
            2021 => 100000,
            2022 => 80000,
            2023 => 60000,
        ];

        $projected = $this->service->projectValue($dataPoints, 'linear', 2024);

        // Should project approximately 40000
        $this->assertGreaterThan(35000, $projected);
        $this->assertLessThan(45000, $projected);
    }

    public function test_projection_never_returns_negative(): void
    {
        $dataPoints = [
            2021 => 30000,
            2022 => 20000,
            2023 => 10000,
        ];

        $projected = $this->service->projectValue($dataPoints, 'linear', 2025);

        // Even with extreme downward trend, should not go negative
        $this->assertGreaterThanOrEqual(0, $projected);
    }

    public function test_polynomial_projection(): void
    {
        $dataPoints = [
            2021 => 100000,
            2022 => 150000,
            2023 => 220000,
        ];

        $projected = $this->service->projectValue($dataPoints, 'polynomial', 2024, 2);

        // Should show accelerating growth
        $this->assertGreaterThan(280000, $projected);
    }
}
```

### 11.2 Feature Tests for Controllers

**File:** `Modules/Accounting/tests/Feature/BudgetControllerTest.php`

```php
<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\Budget\Budget;
use App\Models\User;

class BudgetControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $financeUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users with roles
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super-admin');

        $this->financeUser = User::factory()->create();
        $this->financeUser->assignRole('finance');
    }

    public function test_super_admin_can_create_budget(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('accounting.budget.store'), [
                'budget_year' => 2027,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('budgets', ['budget_year' => 2027]);
    }

    public function test_finance_user_cannot_create_budget(): void
    {
        $response = $this->actingAs($this->financeUser)
            ->post(route('accounting.budget.store'), [
                'budget_year' => 2027,
            ]);

        $response->assertForbidden();
    }

    public function test_finance_user_can_view_budget(): void
    {
        $budget = Budget::factory()->create(['budget_year' => 2027]);

        $response = $this->actingAs($this->financeUser)
            ->get(route('accounting.budget.show', $budget));

        $response->assertOk();
    }

    public function test_cannot_edit_finalized_past_year_budget(): void
    {
        $budget = Budget::factory()->create([
            'budget_year' => 2020, // Past year
            'status' => 'finalized',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->postJson("/accounting/budget/{$budget->id}/growth", [
                'entries' => [],
            ]);

        $response->assertForbidden();
    }
}
```

### 11.3 Browser Tests with Playwright

**File:** `Modules/Accounting/tests/Browser/BudgetGrowthTabTest.php`

```php
<?php

namespace Modules\Accounting\Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\User;
use Modules\Accounting\Models\Budget\Budget;

class BudgetGrowthTabTest extends DuskTestCase
{
    public function test_growth_tab_displays_historical_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $budget = Budget::factory()->create(['budget_year' => 2027]);

        $this->browse(function (Browser $browser) use ($user, $budget) {
            $browser->loginAs($user)
                ->visit("/accounting/budget/{$budget->id}")
                ->waitFor('#growth-tab')
                ->click('a[href="#growth-tab"]')
                ->waitFor('#growthTableBody')
                ->assertSee('Growth Analysis')
                ->assertVisible('#growthTable');
        });
    }

    public function test_can_change_trendline_type(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $budget = Budget::factory()
            ->hasGrowthEntries(1)
            ->create(['budget_year' => 2027]);

        $this->browse(function (Browser $browser) use ($user, $budget) {
            $browser->loginAs($user)
                ->visit("/accounting/budget/{$budget->id}")
                ->click('a[href="#growth-tab"]')
                ->waitFor('.trendline-select')
                ->select('.trendline-select', 'polynomial')
                ->assertSelected('.trendline-select', 'polynomial');
        });
    }

    public function test_chart_modal_opens(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $budget = Budget::factory()
            ->hasGrowthEntries(1)
            ->create(['budget_year' => 2027]);

        $this->browse(function (Browser $browser) use ($user, $budget) {
            $browser->loginAs($user)
                ->visit("/accounting/budget/{$budget->id}")
                ->click('a[href="#growth-tab"]')
                ->waitFor('.show-chart')
                ->click('.show-chart')
                ->waitFor('#growthChartModal.show')
                ->assertVisible('#growthChart');
        });
    }
}
```

---

## Routes Summary

**File:** `Modules/Accounting/routes/web.php` (add to existing routes)

```php
// Budget Routes
Route::prefix('budget')->name('budget.')->middleware(['auth'])->group(function () {
    // Main budget management
    Route::get('/', [BudgetController::class, 'index'])->name('index');
    Route::get('/create', [BudgetController::class, 'create'])->name('create');
    Route::post('/', [BudgetController::class, 'store'])->name('store');
    Route::get('/{budget}', [BudgetController::class, 'show'])->name('show');

    // Tab-specific routes
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
        Route::post('/collection/patterns', [BudgetCollectionController::class, 'savePatterns']);

        // Result
        Route::get('/result', [BudgetResultController::class, 'index']);
        Route::post('/result', [BudgetResultController::class, 'save']);
        Route::post('/result/quick-fill', [BudgetResultController::class, 'quickFill']);

        // Personnel
        Route::get('/personnel', [BudgetPersonnelController::class, 'index']);
        Route::post('/personnel', [BudgetPersonnelController::class, 'save']);
        Route::post('/personnel/new-hire', [BudgetPersonnelController::class, 'addNewHire']);

        // OpEx/Taxes/CapEx
        Route::get('/opex', [BudgetOpExController::class, 'index']);
        Route::post('/opex', [BudgetOpExController::class, 'save']);
        Route::post('/opex/global', [BudgetOpExController::class, 'setGlobalIncrease']);

        Route::get('/taxes', [BudgetTaxesController::class, 'index']);
        Route::post('/taxes', [BudgetTaxesController::class, 'save']);
        Route::post('/taxes/global', [BudgetTaxesController::class, 'setGlobalIncrease']);

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

---

## Implementation Checklist

Use this checklist to track progress:

### Phase 1: Database & Models
- [ ] Create all migrations
- [ ] Run migrations
- [ ] Create all Eloquent models
- [ ] Test relationships in tinker

### Phase 2: Services
- [ ] HistoricalIncomeService
- [ ] BalanceService
- [ ] EmployeeDataService
- [ ] ExpenseDataService
- [ ] GrowthCalculationService
- [ ] CapacityCalculationService
- [ ] CollectionCalculationService
- [ ] BudgetFinalizationService

### Phase 3: Controllers & Routes
- [ ] BudgetController (main CRUD)
- [ ] BudgetGrowthController
- [ ] BudgetCapacityController
- [ ] BudgetCollectionController
- [ ] BudgetResultController
- [ ] BudgetPersonnelController
- [ ] BudgetOpExController
- [ ] BudgetTaxesController
- [ ] BudgetCapExController
- [ ] BudgetPnLController
- [ ] BudgetFinalizationController
- [ ] Add all routes

### Phase 4: Views
- [ ] Main tabbed layout
- [ ] Growth tab with chart
- [ ] Capacity tab with hiring modal
- [ ] Collection tab with patterns
- [ ] Result tab with comparison
- [ ] Personnel tab with sections
- [ ] OpEx/Taxes/CapEx tabs
- [ ] P&L summary view
- [ ] Finalization modal

### Phase 5: Testing
- [ ] Unit tests for calculation services
- [ ] Feature tests for controllers
- [ ] Browser tests for UI interactions

### Phase 6: Polish
- [ ] Add menu item
- [ ] Implement permissions
- [ ] Add unsaved changes warning
- [ ] Auto-save functionality
- [ ] Cross-tab update notifications

---

*Document completed: January 2025*
*Status: Ready for Implementation*
