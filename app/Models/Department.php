<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'head_of_department',
        'email',
        'phone',
        'budget_allocation',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'budget_allocation' => 'decimal:2',
    ];

    /**
     * Get the contracts that belong to this department.
     */
    public function contracts(): BelongsToMany
    {
        return $this->belongsToMany(\Modules\Accounting\Models\Contract::class, 'contract_department', 'department_id', 'contract_id')
                    ->withPivot(['allocation_type', 'allocation_percentage', 'allocation_amount', 'notes'])
                    ->withTimestamps();
    }

    /**
     * Scope to get only active departments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get total contract allocations for this department.
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
}
