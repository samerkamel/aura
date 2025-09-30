<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sector extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the business units for this sector.
     */
    public function businessUnits(): HasMany
    {
        return $this->hasMany(BusinessUnit::class);
    }

    /**
     * Get active business units for this sector.
     */
    public function activeBusinessUnits(): HasMany
    {
        return $this->hasMany(BusinessUnit::class)->where('is_active', true);
    }

    /**
     * Scope to get only active sectors.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get total business units count.
     */
    public function getBusinessUnitsCountAttribute()
    {
        return $this->businessUnits()->count();
    }

    /**
     * Get active business units count.
     */
    public function getActiveBusinessUnitsCountAttribute()
    {
        return $this->activeBusinessUnits()->count();
    }

    /**
     * Get total budget allocation for this sector.
     */
    public function getTotalBudgetAttribute()
    {
        return $this->businessUnits()->get()->sum('total_budget');
    }

    /**
     * Get total contracts value for this sector.
     */
    public function getTotalContractsValueAttribute()
    {
        return $this->businessUnits()->get()->sum('total_contracts_value');
    }
}