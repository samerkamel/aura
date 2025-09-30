<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_unit_id',
        'product_id',
        'budget_year',
        'projected_revenue',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'projected_revenue' => 'decimal:2',
        'budget_year' => 'integer',
    ];

    /**
     * Get the business unit that owns the budget
     */
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    /**
     * Get the product that owns the budget
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the user who created the budget
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the budget
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the budget histories for the budget
     */
    public function histories(): HasMany
    {
        return $this->hasMany(BudgetHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the target revenue from product budget allocation
     */
    public function getTargetRevenueAttribute(): float
    {
        return $this->product->budget_allocation ?? 0;
    }

    /**
     * Get the remaining revenue target
     */
    public function getRemainingTargetAttribute(): float
    {
        return $this->target_revenue - $this->actual_revenue;
    }

    /**
     * Get actual revenue from contracts for this business unit and product
     */
    public function getActualRevenueAttribute(): float
    {
        return $this->businessUnit
            ->contracts()
            ->active()
            ->whereHas('products', function ($query) {
                $query->where('product_id', $this->product_id);
            })
            ->sum('total_amount');
    }

    /**
     * Get total paid income from contracts for this business unit and product
     */
    public function getPaidIncomeAttribute(): float
    {
        $totalPaid = 0;

        $contracts = $this->businessUnit
            ->contracts()
            ->active()
            ->whereHas('products', function ($query) {
                $query->where('product_id', $this->product_id);
            })
            ->get();

        foreach ($contracts as $contract) {
            $totalPaid += $contract->paid_amount;
        }

        return $totalPaid;
    }

    /**
     * Get contract/target achievement percentage
     */
    public function getContractTargetPercentageAttribute(): float
    {
        if ($this->target_revenue == 0) {
            return 0;
        }

        return round(($this->actual_revenue / $this->target_revenue) * 100, 2);
    }

    /**
     * Get paid income/target achievement percentage
     */
    public function getPaidTargetPercentageAttribute(): float
    {
        if ($this->target_revenue == 0) {
            return 0;
        }

        return round(($this->paid_income / $this->target_revenue) * 100, 2);
    }

    /**
     * Get the revenue achievement percentage (using paid income)
     */
    public function getAchievementPercentageAttribute(): float
    {
        return $this->paid_target_percentage;
    }

    /**
     * Get the projection percentage
     */
    public function getProjectionPercentageAttribute(): float
    {
        if ($this->target_revenue == 0) {
            return 0;
        }

        return round(($this->projected_revenue / $this->target_revenue) * 100, 2);
    }

    /**
     * Check if projected revenue exceeds target
     */
    public function getIsOverProjectedAttribute(): bool
    {
        return $this->projected_revenue > $this->target_revenue;
    }

    /**
     * Check if actual revenue exceeds target
     */
    public function getIsOverAchievedAttribute(): bool
    {
        return $this->actual_revenue > $this->target_revenue;
    }


    /**
     * Scope to filter by business unit
     */
    public function scopeForBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    /**
     * Scope to filter by year
     */
    public function scopeForYear($query, $year)
    {
        return $query->where('budget_year', $year);
    }

}
