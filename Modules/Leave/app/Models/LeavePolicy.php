<?php

namespace Modules\Leave\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Leave Policy Model
 *
 * Represents a leave policy configuration (PTO, Sick Leave, etc.)
 * with its associated rules and tiers.
 *
 * @author Dev Agent
 */
class LeavePolicy extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'type',
        'description',
        'initial_days',
        'config',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'initial_days' => 'integer',
    ];

    /**
     * Get the policy tiers for this leave policy.
     */
    public function tiers(): HasMany
    {
        return $this->hasMany(LeavePolicyTier::class);
    }

    /**
     * Scope a query to only include active policies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include PTO policies.
     */
    public function scopePto($query)
    {
        return $query->where('type', 'pto');
    }

    /**
     * Scope a query to only include sick leave policies.
     */
    public function scopeSickLeave($query)
    {
        return $query->where('type', 'sick_leave');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Leave\Database\Factories\LeavePolicyFactory::new();
    }
}
