<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * ExpenseCategory Model
 *
 * Represents categories for organizing expense schedules in the cash flow system.
 * Categories help group similar expenses for better reporting and analysis.
 */
class ExpenseCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'color',
        'is_active',
        'parent_id',
        'expense_type_id',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all expense schedules belonging to this category.
     */
    public function expenseSchedules(): HasMany
    {
        return $this->hasMany(ExpenseSchedule::class, 'category_id');
    }

    /**
     * Get active expense schedules for this category.
     */
    public function activeExpenseSchedules(): HasMany
    {
        return $this->hasMany(ExpenseSchedule::class, 'category_id')
                    ->where('is_active', true);
    }

    /**
     * Scope to get only active categories.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get total monthly amount for this category.
     */
    public function getMonthlyAmountAttribute(): float
    {
        return $this->activeExpenseSchedules()
                    ->get()
                    ->sum(function ($schedule) {
                        return $schedule->monthly_equivalent_amount;
                    });
    }

    /**
     * Get count of active schedules in this category.
     */
    public function getActiveSchedulesCountAttribute(): int
    {
        return $this->activeExpenseSchedules()->count();
    }

    /**
     * Get parent category.
     */
    public function parent()
    {
        return $this->belongsTo(ExpenseCategory::class, 'parent_id');
    }

    /**
     * Get subcategories.
     */
    public function subcategories()
    {
        return $this->hasMany(ExpenseCategory::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Get the expense type for this category.
     */
    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class, 'expense_type_id');
    }

    /**
     * Get all expense schedules including those in subcategories.
     */
    public function allExpenseSchedules(): HasMany
    {
        return $this->hasMany(ExpenseSchedule::class, 'category_id')
                    ->orWhereHas('subcategory', function ($query) {
                        $query->where('parent_id', $this->id);
                    });
    }

    /**
     * Scope to get only main categories (no parent).
     */
    public function scopeMainCategories(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get subcategories of a parent.
     */
    public function scopeSubcategoriesOf(Builder $query, int $parentId): Builder
    {
        return $query->where('parent_id', $parentId);
    }

    /**
     * Scope to get categories by expense type.
     */
    public function scopeOfType(Builder $query, int $expenseTypeId): Builder
    {
        return $query->where('expense_type_id', $expenseTypeId);
    }

    /**
     * Check if this is a main category.
     */
    public function getIsMainCategoryAttribute(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Get full category name including parent.
     */
    public function getFullNameAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->name . ' > ' . $this->name;
        }

        return $this->name;
    }
}