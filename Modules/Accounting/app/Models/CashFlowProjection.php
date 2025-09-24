<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * CashFlowProjection Model
 *
 * Represents calculated cash flow projections for different periods.
 * This is a generated/calculated model used for reporting and analysis.
 */
class CashFlowProjection extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'projection_date',
        'projected_income',
        'projected_expenses',
        'net_flow',
        'running_balance',
        'period_type',
        'has_deficit',
        'income_breakdown',
        'expense_breakdown',
        'calculated_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'projection_date' => 'date',
        'projected_income' => 'decimal:2',
        'projected_expenses' => 'decimal:2',
        'net_flow' => 'decimal:2',
        'running_balance' => 'decimal:2',
        'has_deficit' => 'boolean',
        'income_breakdown' => 'array',
        'expense_breakdown' => 'array',
        'calculated_at' => 'datetime',
    ];

    /**
     * Scope to get projections for a specific period type.
     */
    public function scopeForPeriodType(Builder $query, string $periodType): Builder
    {
        return $query->where('period_type', $periodType);
    }

    /**
     * Scope to get projections within a date range.
     */
    public function scopeInPeriod(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->whereBetween('projection_date', [$startDate, $endDate]);
    }

    /**
     * Scope to get projections with deficits.
     */
    public function scopeWithDeficit(Builder $query): Builder
    {
        return $query->where('has_deficit', true);
    }

    /**
     * Scope to get recent projections (calculated within last hour).
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->where('calculated_at', '>=', now()->subHour());
    }

    /**
     * Get formatted projection date based on period type.
     */
    public function getFormattedDateAttribute(): string
    {
        return match($this->period_type) {
            'daily' => $this->projection_date->format('M j, Y'),
            'weekly' => 'Week of ' . $this->projection_date->format('M j, Y'),
            'monthly' => $this->projection_date->format('F Y'),
            default => $this->projection_date->format('M j, Y'),
        };
    }

    /**
     * Get the deficit amount if any.
     */
    public function getDeficitAmountAttribute(): float
    {
        return $this->net_flow < 0 ? abs($this->net_flow) : 0;
    }

    /**
     * Get the surplus amount if any.
     */
    public function getSurplusAmountAttribute(): float
    {
        return $this->net_flow > 0 ? $this->net_flow : 0;
    }

    /**
     * Check if this projection indicates a cash flow problem.
     */
    public function getIsCriticalAttribute(): bool
    {
        return $this->running_balance < 0 || ($this->has_deficit && abs($this->net_flow) > 1000);
    }

    /**
     * Get status color based on cash flow situation.
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->running_balance < 0) {
            return 'danger';
        }

        if ($this->has_deficit) {
            return 'warning';
        }

        if ($this->net_flow > 0) {
            return 'success';
        }

        return 'secondary';
    }

    /**
     * Get status text for display.
     */
    public function getStatusTextAttribute(): string
    {
        if ($this->running_balance < 0) {
            return 'Critical Deficit';
        }

        if ($this->has_deficit) {
            return 'Cash Deficit';
        }

        if ($this->net_flow > 0) {
            return 'Positive Flow';
        }

        return 'Break Even';
    }

    /**
     * Get top expense categories for this period.
     */
    public function getTopExpenseCategoriesAttribute(): array
    {
        if (!$this->expense_breakdown) {
            return [];
        }

        $categories = $this->expense_breakdown;
        arsort($categories);

        return array_slice($categories, 0, 5, true);
    }

    /**
     * Get top income contracts for this period.
     */
    public function getTopIncomeContractsAttribute(): array
    {
        if (!$this->income_breakdown) {
            return [];
        }

        $contracts = $this->income_breakdown;
        arsort($contracts);

        return array_slice($contracts, 0, 5, true);
    }
}