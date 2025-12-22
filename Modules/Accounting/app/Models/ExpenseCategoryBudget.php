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
 * The budget is expressed as a percentage of monthly revenue.
 */
class ExpenseCategoryBudget extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'expense_category_id',
        'budget_year',
        'budget_percentage',
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
     */
    public function calculateBudgetAmount(float $monthlyRevenue): float
    {
        return ($this->budget_percentage / 100) * $monthlyRevenue;
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
