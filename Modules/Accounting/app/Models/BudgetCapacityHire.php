<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BudgetCapacityHire Model
 *
 * Represents planned hiring for a product in a budget period.
 */
class BudgetCapacityHire extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_capacity_entry_id',
        'hire_month',
        'hire_count',
    ];

    protected $casts = [
        'hire_month' => 'integer',
        'hire_count' => 'decimal:2',
    ];

    // ==================== Relationships ====================

    /**
     * Get the capacity entry this hire belongs to
     */
    public function capacityEntry(): BelongsTo
    {
        return $this->belongsTo(BudgetCapacityEntry::class);
    }

    // ==================== Methods ====================

    /**
     * Get the month name for display
     */
    public function getMonthNameAttribute(): string
    {
        $months = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];

        return $months[$this->hire_month] ?? 'Unknown';
    }

    /**
     * Get the short month name
     */
    public function getShortMonthAttribute(): string
    {
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return $months[$this->hire_month - 1] ?? 'N/A';
    }

    /**
     * Get months remaining in the year from hire month
     */
    public function getMonthsRemainingAttribute(): int
    {
        return 12 - $this->hire_month + 1;
    }

    /**
     * Get the proportion of the year this hire is employed
     */
    public function getAnnualizationFactorAttribute(): float
    {
        $monthsRemaining = 12 - $this->hire_month + 1;
        return $monthsRemaining / 12;
    }

    /**
     * Get the budgeted headcount contribution (annualized)
     */
    public function getAnnualizedHeadcountAttribute(): float
    {
        return $this->hire_count * $this->getAnnualizationFactorAttribute();
    }
}
