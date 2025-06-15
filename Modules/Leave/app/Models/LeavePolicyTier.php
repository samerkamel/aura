<?php

namespace Modules\Leave\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Leave\Database\Factories\LeavePolicyTierFactory;

/**
 * Leave Policy Tier Model
 *
 * Represents accrual tiers for leave policies based on years of service.
 * Used primarily for PTO policies with service-based accrual rates.
 *
 * @author Dev Agent
 */
class LeavePolicyTier extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'leave_policy_id',
        'min_years',
        'max_years',
        'annual_days',
        'monthly_accrual_rate',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'min_years' => 'integer',
        'max_years' => 'integer',
        'annual_days' => 'integer',
        'monthly_accrual_rate' => 'decimal:2',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return LeavePolicyTierFactory::new();
    }

    /**
     * Get the leave policy that owns this tier.
     */
    public function leavePolicy(): BelongsTo
    {
        return $this->belongsTo(LeavePolicy::class);
    }

    /**
     * Calculate monthly accrual rate based on annual days.
     */
    public function calculateMonthlyAccrualRate(): float
    {
        return round($this->annual_days / 12, 2);
    }

    /**
     * Boot the model to automatically calculate monthly accrual rate.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($tier) {
            $tier->monthly_accrual_rate = $tier->calculateMonthlyAccrualRate();
        });
    }
}
