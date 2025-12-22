<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'head_of_product',
        'email',
        'phone',
        'budget_allocation',
        'is_active',
        'business_unit_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'budget_allocation' => 'decimal:2',
    ];

    /**
     * Get the business unit that owns the product
     */
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    /**
     * Get the user who created the product
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the product
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the budgets for this product
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * Get the contracts that belong to this product.
     */
    public function contracts(): BelongsToMany
    {
        return $this->belongsToMany(\Modules\Accounting\Models\Contract::class, 'contract_product', 'product_id', 'contract_id')
                    ->withPivot(['allocation_type', 'allocation_percentage', 'allocation_amount', 'notes'])
                    ->withTimestamps();
    }

    /**
     * Get total contract allocations for this product.
     */
    public function getTotalContractAllocationsAttribute()
    {
        return $this->contracts->sum(function ($contract) {
            $pivot = $contract->pivot;
            if ($pivot->allocation_type === 'amount') {
                return $pivot->allocation_amount;
            } else {
                return ($pivot->allocation_percentage / 100) * $contract->total_amount;
            }
        });
    }

    /**
     * Get product status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'success',
            'inactive' => 'warning',
            'discontinued' => 'secondary',
            default => 'primary'
        };
    }

    /**
     * Scope to filter by business unit
     */
    public function scopeForBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    /**
     * Scope to filter active products
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to search products by name or code
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%");
        });
    }
}
