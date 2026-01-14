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

*This document continues in Part 2 with Phases 4-11...*

Due to length constraints, I've provided the most critical and complex phases. The remaining phases (Capacity, Collection, Result, Personnel, OpEx/Taxes/CapEx, P&L, Finalization, Testing) follow similar patterns.

Would you like me to continue with specific phases, or would you like me to create separate detailed documents for each remaining phase?
