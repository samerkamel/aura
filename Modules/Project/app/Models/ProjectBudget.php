<?php

namespace Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectBudget extends Model
{
    protected $fillable = [
        'project_id',
        'category',
        'description',
        'planned_amount',
        'actual_amount',
        'period_start',
        'period_end',
        'is_active',
    ];

    protected $casts = [
        'planned_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Budget categories.
     */
    public const CATEGORIES = [
        'development' => 'Development',
        'design' => 'Design',
        'infrastructure' => 'Infrastructure',
        'qa_testing' => 'QA & Testing',
        'project_management' => 'Project Management',
        'marketing' => 'Marketing',
        'training' => 'Training',
        'support' => 'Support',
        'contingency' => 'Contingency',
        'other' => 'Other',
    ];

    /**
     * Category colors for display.
     */
    public const CATEGORY_COLORS = [
        'development' => 'primary',
        'design' => 'info',
        'infrastructure' => 'warning',
        'qa_testing' => 'success',
        'project_management' => 'secondary',
        'marketing' => 'danger',
        'training' => 'dark',
        'support' => 'light',
        'contingency' => 'warning',
        'other' => 'secondary',
    ];

    /**
     * Get the project that owns the budget.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get costs associated with this budget category.
     */
    public function costs(): HasMany
    {
        return $this->hasMany(ProjectCost::class);
    }

    /**
     * Get the category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }

    /**
     * Get the category color.
     */
    public function getCategoryColorAttribute(): string
    {
        return self::CATEGORY_COLORS[$this->category] ?? 'secondary';
    }

    /**
     * Get remaining budget amount.
     */
    public function getRemainingAmountAttribute(): float
    {
        return $this->planned_amount - $this->actual_amount;
    }

    /**
     * Get utilization percentage.
     */
    public function getUtilizationPercentageAttribute(): float
    {
        if ($this->planned_amount <= 0) {
            return 0;
        }
        return round(($this->actual_amount / $this->planned_amount) * 100, 1);
    }

    /**
     * Check if budget is over-utilized.
     */
    public function isOverBudget(): bool
    {
        return $this->actual_amount > $this->planned_amount;
    }

    /**
     * Get budget status.
     */
    public function getStatusAttribute(): string
    {
        $utilization = $this->utilization_percentage;
        if ($utilization >= 100) {
            return 'over';
        } elseif ($utilization >= 90) {
            return 'critical';
        } elseif ($utilization >= 75) {
            return 'warning';
        }
        return 'healthy';
    }

    /**
     * Get status color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'over' => 'danger',
            'critical' => 'danger',
            'warning' => 'warning',
            'healthy' => 'success',
            default => 'secondary',
        };
    }

    /**
     * Recalculate actual amount from costs.
     */
    public function recalculateActual(): void
    {
        $this->actual_amount = $this->costs()->sum('amount');
        $this->save();
    }

    /**
     * Scope for active budgets.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
