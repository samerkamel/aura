<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BudgetExpenseEntry Model
 *
 * Stores expense budgets for OpEx, Taxes, and CapEx categories.
 * Supports both percentage-based and amount-based overrides.
 */
class BudgetExpenseEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'expense_category_id',
        'type',
        'last_year_total',
        'last_year_avg_monthly',
        'increase_percentage',
        'proposed_amount',
        'proposed_total',
        'is_override',
    ];

    protected $casts = [
        'last_year_total' => 'decimal:2',
        'last_year_avg_monthly' => 'decimal:2',
        'increase_percentage' => 'decimal:2',
        'proposed_amount' => 'decimal:2',
        'proposed_total' => 'decimal:2',
        'is_override' => 'boolean',
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
     * Get the expense category this entry is for
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    // ==================== Methods ====================

    /**
     * Calculate proposed total based on increase percentage
     */
    public function calculateProposedTotal(): float
    {
        if ($this->is_override && $this->proposed_amount) {
            return $this->proposed_amount;
        }

        $increasePercent = $this->increase_percentage ?? 0;
        return $this->last_year_total * (1 + ($increasePercent / 100));
    }

    /**
     * Calculate proposed monthly average
     */
    public function calculateProposedMonthly(): float
    {
        if ($this->is_override && $this->proposed_amount) {
            return $this->proposed_amount / 12;
        }

        return $this->calculateProposedTotal() / 12;
    }

    /**
     * Get the increase amount in currency
     */
    public function getIncreaseAmount(): float
    {
        return $this->calculateProposedTotal() - $this->last_year_total;
    }

    /**
     * Get the increase percentage display
     */
    public function getIncreasePercentageDisplay(): string
    {
        $increasePct = $this->increase_percentage ?? 0;

        if ($this->is_override && $this->proposed_amount) {
            $calculatedPct = (($this->proposed_amount - $this->last_year_total) / $this->last_year_total) * 100;
            return "{$calculatedPct}% (custom amount)";
        }

        return "{$increasePct}%";
    }

    /**
     * Get the type label
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            'opex' => 'Operating Expense',
            'tax' => 'Tax',
            'capex' => 'Capital Expenditure',
            default => 'Unknown',
        };
    }

    /**
     * Get the type badge color
     */
    public function getTypeBadgeColor(): string
    {
        return match($this->type) {
            'opex' => 'warning',
            'tax' => 'danger',
            'capex' => 'info',
            default => 'secondary',
        };
    }

    /**
     * Check if this is an OpEx entry
     */
    public function isOpEx(): bool
    {
        return $this->type === 'opex';
    }

    /**
     * Check if this is a Tax entry
     */
    public function isTax(): bool
    {
        return $this->type === 'tax';
    }

    /**
     * Check if this is a CapEx entry
     */
    public function isCapEx(): bool
    {
        return $this->type === 'capex';
    }

    /**
     * Check if this category was actually used last year
     */
    public function wasUsedLastYear(): bool
    {
        return $this->last_year_total > 0;
    }

    /**
     * Get category display name with hierarchy
     */
    public function getCategoryDisplayName(): string
    {
        return $this->category?->full_name ?? 'Unknown Category';
    }

    /**
     * Apply global increase if not overridden
     */
    public function applyGlobalIncrease(float $globalIncreasePct): void
    {
        if (!$this->is_override) {
            $this->increase_percentage = $globalIncreasePct;
            $this->proposed_total = $this->calculateProposedTotal();
            $this->save();
        }
    }
}
