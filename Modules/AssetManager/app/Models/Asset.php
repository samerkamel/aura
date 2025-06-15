<?php

namespace Modules\AssetManager\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\HR\Models\Employee;

/**
 * Asset Model
 *
 * Represents an asset in the asset management system with its details,
 * status, and relationships to employees.
 *
 * @author Dev Agent
 */
class Asset extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'type',
        'serial_number',
        'purchase_date',
        'purchase_price',
        'description',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
    ];

    /**
     * Scope a query to only include available assets.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope a query to only include assigned assets.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    /**
     * Get the employees that have been assigned this asset.
     */
    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'asset_employee')
            ->withPivot(['assigned_date', 'returned_date', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get the currently assigned employee for this asset.
     */
    public function currentEmployee()
    {
        return $this->belongsToMany(Employee::class, 'asset_employee')
            ->withPivot(['assigned_date', 'returned_date', 'notes'])
            ->wherePivotNull('returned_date')
            ->withTimestamps();
    }

    /**
     * Check if the asset is currently assigned to an employee.
     */
    public function isAssigned(): bool
    {
        return $this->currentEmployee()->exists();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\AssetManager\Database\Factories\AssetFactory::new();
    }
}
