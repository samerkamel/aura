<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'sector_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the users that have access to this business unit.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_unit_user')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /**
     * Get the sector that this business unit belongs to.
     * Note: Returns null if sector_id = 0 (head office with all sector access)
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class)->where('id', '>', 0);
    }

    /**
     * Get the products for this business unit.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Legacy method for backwards compatibility.
     * @deprecated Use products() instead
     */
    public function departments(): HasMany
    {
        return $this->products();
    }

    /**
     * Get the contracts for this business unit.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(\Modules\Accounting\Models\Contract::class);
    }

    /**
     * Get the expense schedules for this business unit.
     */
    public function expenseSchedules(): HasMany
    {
        return $this->hasMany(\Modules\Accounting\Models\ExpenseSchedule::class);
    }

    /**
     * Get the budgets for this business unit.
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * Scope to get only active business units.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get business units by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if this is the head office business unit.
     */
    public function isHeadOffice(): bool
    {
        return $this->type === 'head_office';
    }

    /**
     * Check if this business unit has access to all sectors (sector_id = 0).
     */
    public function hasAllSectorAccess(): bool
    {
        return $this->sector_id === 0;
    }

    /**
     * Get the sector name for display purposes.
     */
    public function getSectorNameAttribute(): string
    {
        if ($this->hasAllSectorAccess()) {
            return 'All Sectors';
        }

        return $this->sector ? $this->sector->name : 'No Sector';
    }

    /**
     * Scope to filter business units by sector.
     * Pass 0 to get head office units with all sector access.
     */
    public function scopeForSector($query, $sectorId)
    {
        return $query->where('sector_id', $sectorId);
    }

    /**
     * Scope to get business units accessible from a specific sector context.
     * This includes units in that sector AND head office units (sector_id = 0).
     */
    public function scopeAccessibleFromSector($query, $sectorId)
    {
        if ($sectorId === 0) {
            // If requesting from head office context, return all units
            return $query;
        }

        // Return units in the specific sector OR head office units
        return $query->whereIn('sector_id', [0, $sectorId]);
    }

    /**
     * Get total budget allocation for this business unit.
     */
    public function getTotalBudgetAttribute()
    {
        return $this->products()->sum('budget_allocation');
    }

    /**
     * Get active products count.
     */
    public function getActiveDepartmentsCountAttribute()
    {
        return $this->products()->where('is_active', true)->count();
    }

    /**
     * Get products count (alias for departments_count).
     */
    public function getProductsCountAttribute()
    {
        return $this->products_count ?? $this->products()->count();
    }

    /**
     * Get active products count (alias for active_departments_count).
     */
    public function getActiveProductsCountAttribute()
    {
        return $this->active_products_count;
    }

    /**
     * Get total contracts value for this business unit.
     */
    public function getTotalContractsValueAttribute()
    {
        return $this->contracts()->where('is_active', true)->sum('total_amount');
    }
}