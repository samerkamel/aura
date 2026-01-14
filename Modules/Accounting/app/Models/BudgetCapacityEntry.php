<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * BudgetCapacityEntry Model
 *
 * Stores capacity-based budget data including employee headcount, available hours, and billable rates.
 */
class BudgetCapacityEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'product_id',
        'last_year_headcount',
        'last_year_available_hours',
        'last_year_avg_hourly_price',
        'last_year_income',
        'last_year_billable_hours',
        'last_year_billable_pct',
        'next_year_headcount',
        'next_year_avg_hourly_price',
        'next_year_billable_pct',
        'budgeted_income',
    ];

    protected $casts = [
        'last_year_available_hours' => 'decimal:2',
        'last_year_avg_hourly_price' => 'decimal:2',
        'last_year_income' => 'decimal:2',
        'last_year_billable_hours' => 'decimal:2',
        'last_year_billable_pct' => 'decimal:2',
        'next_year_avg_hourly_price' => 'decimal:2',
        'next_year_billable_pct' => 'decimal:2',
        'budgeted_income' => 'decimal:2',
    ];

    // ==================== Relationships ====================

    /**
     * Get the budget this entry belongs to
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * Get the product this entry is for
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class);
    }

    /**
     * Get the planned hires for this entry
     */
    public function hires(): HasMany
    {
        return $this->hasMany(BudgetCapacityHire::class);
    }

    // ==================== Methods ====================

    /**
     * Calculate weighted headcount accounting for hire timing
     *
     * Example: If base headcount is 5 and we hire 2 in June,
     * weighted headcount = 5 + (2 * 7/12) = 5.83
     */
    public function calculateWeightedHeadcount(): float
    {
        $weightedCount = (float) ($this->next_year_headcount ?? 0);

        // Add weighted hires (prorated based on months employed)
        foreach ($this->hires as $hire) {
            $monthsEmployed = (12 - $hire->hire_month) + 1; // From hire month to end of year
            $weightedCount += (float) $hire->hire_count * ($monthsEmployed / 12);
        }

        return $weightedCount;
    }

    /**
     * Calculate budgeted income for next year
     * Formula: Available Hours × Weighted Headcount × Avg Hourly Price × Billable %
     */
    public function calculateBudgetedIncome(): float
    {
        $availableHours = (float) ($this->last_year_available_hours ?? 0);
        $weightedHeadcount = $this->calculateWeightedHeadcount();
        $avgPrice = (float) ($this->next_year_avg_hourly_price ?? 0);
        $billablePct = (float) ($this->next_year_billable_pct ?? 0) / 100;

        return $availableHours * $weightedHeadcount * $avgPrice * $billablePct;
    }

    /**
     * Get total new hires across all months
     */
    public function getTotalNewHires(): float
    {
        return (float) $this->hires()->sum('hire_count');
    }

    /**
     * Get array of new hires by month for display
     */
    public function getHiresByMonth(): array
    {
        $hires = array_fill(1, 12, 0.0);

        foreach ($this->hires as $hire) {
            $hires[$hire->hire_month] += (float) $hire->hire_count;
        }

        return $hires;
    }
}
