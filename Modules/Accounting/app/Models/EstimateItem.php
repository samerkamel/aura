<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * EstimateItem Model
 *
 * Represents line items within an estimate.
 */
class EstimateItem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'estimate_items';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'estimate_id',
        'description',
        'details',
        'quantity',
        'unit',
        'unit_price',
        'amount',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the estimate this item belongs to.
     */
    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    /**
     * Boot method to auto-calculate amount.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->amount = $item->quantity * $item->unit_price;
        });

        static::saved(function ($item) {
            // Recalculate parent estimate totals
            if ($item->estimate) {
                $item->estimate->recalculateTotals();
            }
        });

        static::deleted(function ($item) {
            // Recalculate parent estimate totals
            if ($item->estimate) {
                $item->estimate->recalculateTotals();
            }
        });
    }

    /**
     * Get formatted amount for display.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2);
    }

    /**
     * Get formatted unit price for display.
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return number_format($this->unit_price, 2);
    }
}
