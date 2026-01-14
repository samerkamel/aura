<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Budget Model
 *
 * Main budget record for a specific financial year, containing references to all budget planning data
 * (growth, capacity, collection, results, personnel, and expenses).
 */
class Budget extends Model
{
    use HasFactory;

    protected $table = 'budget_plans';

    protected $fillable = [
        'year',
        'status',
        'opex_global_increase_pct',
        'tax_global_increase_pct',
        'finalized_at',
        'finalized_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'finalized_at' => 'datetime',
        'opex_global_increase_pct' => 'decimal:2',
        'tax_global_increase_pct' => 'decimal:2',
    ];

    // ==================== Relationships ====================

    /**
     * Get the growth entries for this budget
     */
    public function growthEntries(): HasMany
    {
        return $this->hasMany(BudgetGrowthEntry::class);
    }

    /**
     * Get the capacity entries for this budget
     */
    public function capacityEntries(): HasMany
    {
        return $this->hasMany(BudgetCapacityEntry::class);
    }

    /**
     * Get the collection entries for this budget
     */
    public function collectionEntries(): HasMany
    {
        return $this->hasMany(BudgetCollectionEntry::class);
    }

    /**
     * Get the result entries for this budget
     */
    public function resultEntries(): HasMany
    {
        return $this->hasMany(BudgetResultEntry::class);
    }

    /**
     * Get the personnel entries for this budget
     */
    public function personnelEntries(): HasMany
    {
        return $this->hasMany(BudgetPersonnelEntry::class);
    }

    /**
     * Get the expense entries for this budget
     */
    public function expenseEntries(): HasMany
    {
        return $this->hasMany(BudgetExpenseEntry::class);
    }

    /**
     * Get the user who finalized this budget
     */
    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'finalized_by');
    }

    // ==================== Scopes ====================

    /**
     * Scope to get only draft budgets
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to get only finalized budgets
     */
    public function scopeFinalized($query)
    {
        return $query->where('status', 'finalized');
    }

    /**
     * Scope to get budget for a specific year
     */
    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    // ==================== Methods ====================

    /**
     * Check if this budget can be edited
     * Budgets can be edited until the financial year ends
     */
    public function canEdit(): bool
    {
        if ($this->status === 'finalized') {
            return false;
        }

        // Check if current year equals budget year (can edit)
        return now()->year <= $this->year;
    }

    /**
     * Check if this budget is for a past financial year
     */
    public function isPastYear(): bool
    {
        return $this->year < now()->year;
    }

    /**
     * Get the budget for the previous year
     */
    public function getPreviousYearBudget(): ?Budget
    {
        return static::where('year', $this->year - 1)->first();
    }

    /**
     * Get the last complete financial year (for calculating last year data)
     */
    public function getLastYear(): int
    {
        return $this->year - 1;
    }

    /**
     * Calculate elapsed months for current year if budgeting before year-end
     * Returns decimal (e.g., 10.5 for November 15th)
     */
    public function getElapsedMonths(): float
    {
        if ($this->year != now()->year) {
            return 12; // Full year
        }

        $now = now();
        $monthsPassed = ($now->month - 1) + ($now->day / $now->daysInMonth);
        return $monthsPassed;
    }

    /**
     * Get the start date of the financial year
     */
    public function getFinancialYearStart(): Carbon
    {
        // Default: January 1st (customize based on company FY settings if needed)
        return Carbon::createFromDate($this->year, 1, 1);
    }

    /**
     * Get the end date of the financial year
     */
    public function getFinancialYearEnd(): Carbon
    {
        // Default: December 31st (customize based on company FY settings if needed)
        return Carbon::createFromDate($this->year, 12, 31);
    }

    /**
     * Mark this budget as finalized
     */
    public function finalize(int $userId): void
    {
        $this->update([
            'status' => 'finalized',
            'finalized_at' => now(),
            'finalized_by' => $userId,
        ]);
    }

    /**
     * Get summary totals for this budget
     */
    public function getSummary(): array
    {
        return [
            'total_growth_budget' => $this->resultEntries()->sum('growth_value'),
            'total_capacity_budget' => $this->resultEntries()->sum('capacity_value'),
            'total_collection_budget' => $this->resultEntries()->sum('collection_value'),
            'total_final_budget' => $this->resultEntries()->sum('final_value'),
            'total_personnel_cost' => $this->personnelEntries()
                ->with('allocations')
                ->get()
                ->sum(fn($entry) => $entry->proposed_salary ?? $entry->current_salary),
            'total_opex' => $this->expenseEntries()->where('type', 'opex')->sum('proposed_total'),
            'total_taxes' => $this->expenseEntries()->where('type', 'tax')->sum('proposed_total'),
            'total_capex' => $this->expenseEntries()->where('type', 'capex')->sum('proposed_total'),
        ];
    }

    /**
     * Calculate overall completion percentage for the budget
     * Checks progress across all tabs
     */
    public function getCompletionPercentage(): int
    {
        $growthEntries = $this->growthEntries()->count();
        $capacityEntries = $this->capacityEntries()->count();
        $collectionEntries = $this->collectionEntries()->count();
        $resultEntries = $this->resultEntries()->count();
        $personnelEntries = $this->personnelEntries()->count();
        $expenseEntries = $this->expenseEntries()->count();

        // If no entries, consider it 0% complete
        if ($growthEntries === 0 && $capacityEntries === 0 && $collectionEntries === 0 &&
            $resultEntries === 0 && $personnelEntries === 0 && $expenseEntries === 0) {
            return 0;
        }

        $percentages = [];

        // Growth tab completion: entries with budgeted_value
        if ($growthEntries > 0) {
            $growthCompleted = $this->growthEntries()
                ->whereNotNull('budgeted_value')
                ->count();
            $percentages[] = ($growthCompleted / $growthEntries) * 100;
        }

        // Capacity tab completion: entries with next_year_avg_hourly_price and next_year_billable_pct
        if ($capacityEntries > 0) {
            $capacityCompleted = $this->capacityEntries()
                ->whereNotNull('next_year_avg_hourly_price')
                ->whereNotNull('next_year_billable_pct')
                ->count();
            $percentages[] = ($capacityCompleted / $capacityEntries) * 100;
        }

        // Collection tab completion: entries with patterns configured
        if ($collectionEntries > 0) {
            $collectionCompleted = $this->collectionEntries()
                ->whereHas('patterns')
                ->count();
            $percentages[] = ($collectionCompleted / $collectionEntries) * 100;
        }

        // Result tab completion: entries with final_value selected
        if ($resultEntries > 0) {
            $resultCompleted = $this->resultEntries()
                ->whereNotNull('final_value')
                ->count();
            $percentages[] = ($resultCompleted / $resultEntries) * 100;
        }

        // Personnel tab completion: entries with allocations configured
        if ($personnelEntries > 0) {
            $personnelCompleted = $this->personnelEntries()
                ->whereHas('allocations')
                ->count();
            $percentages[] = ($personnelCompleted / $personnelEntries) * 100;
        }

        // Expense tab completion: entries with proposed_total set
        if ($expenseEntries > 0) {
            $expenseCompleted = $this->expenseEntries()
                ->whereNotNull('proposed_total')
                ->count();
            $percentages[] = ($expenseCompleted / $expenseEntries) * 100;
        }

        // Return average of all tab completion percentages
        return (int) round(array_sum($percentages) / max(count($percentages), 1));
    }
}
