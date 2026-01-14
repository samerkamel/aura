<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BudgetPersonnelAllocation Model
 *
 * Represents allocation of an employee's salary to products or G&A.
 * An employee can be split across multiple products with different percentages.
 */
class BudgetPersonnelAllocation extends Model
{
    use HasFactory;

    protected $table = 'budget_plan_allocations';

    protected $fillable = [
        'budget_personnel_entry_id',
        'product_id',
        'allocation_percentage',
    ];

    protected $casts = [
        'allocation_percentage' => 'decimal:2',
    ];

    // ==================== Relationships ====================

    /**
     * Get the personnel entry this allocation belongs to
     */
    public function personnelEntry(): BelongsTo
    {
        return $this->belongsTo(BudgetPersonnelEntry::class);
    }

    /**
     * Get the product this allocation is for (null = G&A)
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class);
    }

    // ==================== Methods ====================

    /**
     * Get the type of allocation (product or G&A)
     */
    public function getAllocationType(): string
    {
        return $this->product_id === null ? 'G&A' : 'Product';
    }

    /**
     * Get the display name for this allocation
     */
    public function getDisplayName(): string
    {
        if ($this->product_id === null) {
            return 'General & Administrative (G&A)';
        }

        return $this->product?->name ?? 'Unknown Product';
    }

    /**
     * Calculate the allocated cost
     */
    public function getAllocatedCost(): float
    {
        $salary = $this->personnelEntry->getEffectiveSalary();
        return $salary * ($this->allocation_percentage / 100);
    }

    /**
     * Get the employee name
     */
    public function getEmployeeName(): string
    {
        return $this->personnelEntry->employee->name;
    }

    /**
     * Check if this is a product allocation
     */
    public function isProductAllocation(): bool
    {
        return $this->product_id !== null;
    }

    /**
     * Check if this is a G&A allocation
     */
    public function isGAAllocation(): bool
    {
        return $this->product_id === null;
    }
}
