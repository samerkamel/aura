<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * ExpenseType Model
 *
 * Represents types of expenses (CapEx, OpEx, CoS, etc.) for categorizing
 * expense categories at the highest level.
 */
class ExpenseType extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'expense_types';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'color',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all expense categories of this type.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class, 'expense_type_id')
                    ->whereNull('parent_id') // Only main categories
                    ->orderBy('sort_order');
    }

    /**
     * Get all expense categories of this type including subcategories.
     */
    public function allCategories(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class, 'expense_type_id')
                    ->orderBy('sort_order');
    }

    /**
     * Get active expense categories of this type.
     */
    public function activeCategories(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class, 'expense_type_id')
                    ->where('is_active', true)
                    ->whereNull('parent_id') // Only main categories
                    ->orderBy('sort_order');
    }

    /**
     * Scope to get only active expense types.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the count of active categories for this type.
     */
    public function getActiveCategoriesCountAttribute(): int
    {
        return $this->activeCategories()->count();
    }

    /**
     * Get total monthly amount for all categories of this type.
     */
    public function getTotalMonthlyAmountAttribute(): float
    {
        return $this->activeCategories()
                    ->with('expenseSchedules')
                    ->get()
                    ->sum(function ($category) {
                        return $category->monthly_amount;
                    });
    }

    /**
     * Get year-to-date total for this expense type.
     */
    public function getYtdTotalAttribute(): float
    {
        $yearStart = now()->startOfYear();

        return $this->activeCategories()
                    ->with(['expenseSchedules' => function($query) use ($yearStart) {
                        $query->where('payment_status', 'paid')
                              ->where('paid_date', '>=', $yearStart)
                              ->where('paid_date', '<=', now());
                    }])
                    ->get()
                    ->sum(function ($category) {
                        return $category->expenseSchedules->sum('paid_amount');
                    });
    }

    /**
     * Get the display badge HTML for this expense type.
     */
    public function getBadgeHtmlAttribute(): string
    {
        return '<span class="badge" style="background-color: ' . $this->color . '">' . $this->code . '</span>';
    }
}