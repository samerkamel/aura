<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'name_ar',
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
     * Get full category name including all ancestors.
     */
    public function getFullNameAttribute(): string
    {
        $ancestors = $this->getAncestors();
        if ($ancestors->isNotEmpty()) {
            return $ancestors->pluck('name')->push($this->name)->implode(' > ');
        }

        return $this->name;
    }

    /**
     * Get all ancestors (parent, grandparent, etc.) from root to immediate parent.
     */
    public function getAncestors(): \Illuminate\Support\Collection
    {
        $ancestors = collect();
        $current = $this->parent;

        while ($current) {
            $ancestors->prepend($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Get the depth level of this category (0 = root, 1 = first level child, etc.)
     */
    public function getDepthAttribute(): int
    {
        return $this->getAncestors()->count();
    }

    /**
     * Get all descendants recursively.
     */
    public function allDescendants(): \Illuminate\Support\Collection
    {
        $descendants = collect();

        foreach ($this->subcategories as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->allDescendants());
        }

        return $descendants;
    }

    /**
     * Get all category IDs that are descendants of this category (to prevent circular references).
     */
    public function getDescendantIds(): array
    {
        return $this->allDescendants()->pluck('id')->toArray();
    }

    /**
     * Build a flat tree structure with depth for display purposes.
     * Returns all categories in hierarchical order with depth indicator.
     */
    public static function getFlatTree(bool $activeOnly = true): \Illuminate\Support\Collection
    {
        $query = static::query()->whereNull('parent_id')->orderBy('sort_order')->orderBy('name');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        $rootCategories = $query->get();
        $flatTree = collect();

        foreach ($rootCategories as $category) {
            static::addToFlatTree($flatTree, $category, 0, $activeOnly);
        }

        return $flatTree;
    }

    /**
     * Recursively add categories to flat tree.
     */
    private static function addToFlatTree(\Illuminate\Support\Collection &$tree, ExpenseCategory $category, int $depth, bool $activeOnly): void
    {
        $category->tree_depth = $depth;
        $category->tree_prefix = str_repeat('─ ', $depth);
        $tree->push($category);

        $query = $category->subcategories()->orderBy('sort_order')->orderBy('name');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        foreach ($query->get() as $child) {
            static::addToFlatTree($tree, $child, $depth + 1, $activeOnly);
        }
    }

    /**
     * Get all budgets for this category.
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(ExpenseCategoryBudget::class, 'expense_category_id');
    }

    /**
     * Get budget for a specific year.
     */
    public function budgetForYear(int $year): ?ExpenseCategoryBudget
    {
        return $this->budgets()->where('budget_year', $year)->first();
    }

    /**
     * Get the current year's budget.
     */
    public function currentYearBudget(): HasOne
    {
        return $this->hasOne(ExpenseCategoryBudget::class, 'expense_category_id')
            ->where('budget_year', (int) date('Y'));
    }

    /**
     * Get the current year's budget percentage.
     */
    public function getCurrentYearBudgetPercentageAttribute(): ?float
    {
        $budget = $this->currentYearBudget;
        return $budget ? $budget->budget_percentage : null;
    }
}