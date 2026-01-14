<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * BudgetCollectionEntry Model
 *
 * Stores collection-based budget data including payment balance analysis and collection patterns.
 */
class BudgetCollectionEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'product_id',
        'beginning_balance',
        'end_balance',
        'avg_balance',
        'avg_contract_per_month',
        'avg_payment_per_month',
        'last_year_collection_months',
        'budgeted_collection_months',
        'projected_collection_months',
        'budgeted_income',
    ];

    protected $casts = [
        'beginning_balance' => 'decimal:2',
        'end_balance' => 'decimal:2',
        'avg_balance' => 'decimal:2',
        'avg_contract_per_month' => 'decimal:2',
        'avg_payment_per_month' => 'decimal:2',
        'last_year_collection_months' => 'decimal:2',
        'budgeted_collection_months' => 'decimal:2',
        'projected_collection_months' => 'decimal:2',
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
     * Get the payment patterns for this entry
     */
    public function patterns(): HasMany
    {
        return $this->hasMany(BudgetCollectionPattern::class);
    }

    // ==================== Methods ====================

    /**
     * Calculate last year collection months
     * Formula: Average Balance ÷ Average Payment Per Month
     */
    public function calculateLastYearCollectionMonths(): float
    {
        if ($this->avg_payment_per_month == 0) {
            return 0;
        }

        return (float) $this->avg_balance / $this->avg_payment_per_month;
    }

    /**
     * Calculate budgeted collection months from payment patterns
     * Weighted average of all patterns' collection months
     */
    public function calculateBudgetedCollectionMonths(): float
    {
        $totalMonths = 0;

        foreach ($this->patterns as $pattern) {
            $patternMonths = $pattern->calculateCollectionMonths();
            $weight = $pattern->contract_percentage / 100;
            $totalMonths += $patternMonths * $weight;
        }

        return $totalMonths;
    }

    /**
     * Calculate projected collection months (average of last year and budgeted)
     */
    public function calculateProjectedCollectionMonths(): float
    {
        return ($this->last_year_collection_months + $this->budgeted_collection_months) / 2;
    }

    /**
     * Calculate target income for this product
     * Formula: End Balance ÷ Projected Collection Months × 12
     */
    public function calculateBudgetedIncome(): float
    {
        if ($this->projected_collection_months == 0) {
            return 0;
        }

        $avgMonthlyCollection = $this->end_balance / $this->projected_collection_months;
        return $avgMonthlyCollection * 12;
    }

    /**
     * Get summary of all patterns for display
     */
    public function getPatternsSummary(): array
    {
        return $this->patterns->map(fn($p) => [
            'name' => $p->pattern_name,
            'percentage' => $p->contract_percentage,
            'collection_months' => $p->calculateCollectionMonths(),
        ])->toArray();
    }
}
