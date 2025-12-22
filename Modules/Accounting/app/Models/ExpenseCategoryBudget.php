<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

/**
 * ExpenseCategoryBudget Model
 *
 * Represents annual budget allocations for expense categories.
 * The budget is expressed as a percentage of monthly revenue (Tier 1)
 * or as a percentage of net income after Tier 1 deductions (Tier 2).
 */
class ExpenseCategoryBudget extends Model
{
    use HasFactory;

    /**
     * Calculation base constants.
     */
    public const BASE_TOTAL_REVENUE = 'total_revenue';
    public const BASE_NET_INCOME = 'net_income';

    /**
     * The percentage of total revenue used by Tier 1 categories.
     * Net Income = Total Revenue * (1 - TIER1_PERCENTAGE / 100)
     */
    public const TIER1_PERCENTAGE = 23.1;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'expense_category_id',
        'budget_year',
        'budget_percentage',
        'calculation_base',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'budget_year' => 'integer',
        'budget_percentage' => 'decimal:2',
    ];

    /**
     * Get available calculation bases.
     */
    public static function getCalculationBases(): array
    {
        return [
            self::BASE_TOTAL_REVENUE => 'Total Revenue (Tier 1)',
            self::BASE_NET_INCOME => 'Net Income / عائد الدخل (Tier 2)',
        ];
    }

    /**
     * Check if this is a Tier 1 (total revenue) budget.
     */
    public function isTier1(): bool
    {
        return $this->calculation_base === self::BASE_TOTAL_REVENUE;
    }

    /**
     * Check if this is a Tier 2 (net income) budget.
     */
    public function isTier2(): bool
    {
        return $this->calculation_base === self::BASE_NET_INCOME;
    }

    /**
     * Get the expense category this budget belongs to.
     */
    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /**
     * Get the user who created this budget.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this budget.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Calculate the budget amount for a given monthly revenue.
     *
     * For Tier 1 (total_revenue): budget = percentage * total_revenue
     * For Tier 2 (net_income): budget = percentage * (total_revenue * (1 - TIER1_PERCENTAGE/100))
     */
    public function calculateBudgetAmount(float $monthlyRevenue): float
    {
        if ($this->isTier2()) {
            // Net income = Total Revenue - Tier 1 expenses (23.1%)
            $netIncome = $monthlyRevenue * (1 - self::TIER1_PERCENTAGE / 100);
            return ($this->budget_percentage / 100) * $netIncome;
        }

        // Tier 1: Direct percentage of total revenue
        return ($this->budget_percentage / 100) * $monthlyRevenue;
    }

    /**
     * Calculate the net income (عائد الدخل) from total revenue.
     */
    public static function calculateNetIncome(float $totalRevenue): float
    {
        return $totalRevenue * (1 - self::TIER1_PERCENTAGE / 100);
    }

    /**
     * Get the calculation base label.
     */
    public function getCalculationBaseLabel(): string
    {
        return self::getCalculationBases()[$this->calculation_base] ?? $this->calculation_base;
    }

    /**
     * Scope to get budgets for a specific year.
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('budget_year', $year);
    }

    /**
     * Scope to get budgets for the current year.
     */
    public function scopeCurrentYear($query)
    {
        return $query->where('budget_year', (int) date('Y'));
    }
}
