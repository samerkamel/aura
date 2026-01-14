<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * BudgetPersonnelEntry Model
 *
 * Stores salary planning data for each employee in the budget, including allocations to products and G&A.
 */
class BudgetPersonnelEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'employee_id',
        'current_salary',
        'proposed_salary',
        'increase_percentage',
        'is_new_hire',
        'hire_month',
    ];

    protected $casts = [
        'current_salary' => 'decimal:2',
        'proposed_salary' => 'decimal:2',
        'increase_percentage' => 'decimal:2',
        'is_new_hire' => 'boolean',
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
     * Get the employee this entry is for
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(\Modules\HR\Models\Employee::class);
    }

    /**
     * Get the product allocations for this employee
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(BudgetPersonnelAllocation::class);
    }

    // ==================== Methods ====================

    /**
     * Calculate increase percentage
     */
    public function calculateIncreasePercentage(): float
    {
        if ($this->current_salary == 0) {
            return 0;
        }

        return (($this->proposed_salary - $this->current_salary) / $this->current_salary) * 100;
    }

    /**
     * Get the effective salary (proposed or current)
     */
    public function getEffectiveSalary(): float
    {
        return $this->proposed_salary ?? $this->current_salary;
    }

    /**
     * Get total cost across all allocations (effective salary)
     */
    public function getTotalCost(): float
    {
        return $this->getEffectiveSalary();
    }

    /**
     * Get cost allocated to a specific product
     */
    public function getCostForProduct($productId): float
    {
        $allocation = $this->allocations()->where('product_id', $productId)->first();

        if (!$allocation) {
            return 0;
        }

        return $this->getEffectiveSalary() * ($allocation->allocation_percentage / 100);
    }

    /**
     * Get G&A allocated cost (allocated to null product)
     */
    public function getGACost(): float
    {
        $allocation = $this->allocations()->whereNull('product_id')->first();

        if (!$allocation) {
            return 0;
        }

        return $this->getEffectiveSalary() * ($allocation->allocation_percentage / 100);
    }

    /**
     * Check if allocations sum to 100%
     */
    public function allocationsValid(): bool
    {
        $total = $this->allocations()->sum('allocation_percentage');
        return abs($total - 100) < 0.01; // Allow for rounding errors
    }

    /**
     * Get allocation summary
     */
    public function getAllocationsSummary(): array
    {
        return $this->allocations()
            ->with('product')
            ->get()
            ->map(fn($alloc) => [
                'product' => $alloc->product?->name ?? 'G&A',
                'percentage' => $alloc->allocation_percentage,
                'cost' => $this->getEffectiveSalary() * ($alloc->allocation_percentage / 100),
            ])
            ->toArray();
    }

    /**
     * Get salary change amount
     */
    public function getSalaryChangeAmount(): float
    {
        return ($this->proposed_salary ?? $this->current_salary) - $this->current_salary;
    }

    /**
     * Check if this is a new hire from capacity planning
     */
    public function isNewHireFromCapacity(): bool
    {
        return $this->is_new_hire && $this->hire_month !== null;
    }

    /**
     * Get hire month name
     */
    public function getHireMonthName(): ?string
    {
        if (!$this->hire_month) {
            return null;
        }

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

        return $months[$this->hire_month] ?? null;
    }
}
