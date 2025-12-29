<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CreditNoteItem Model
 *
 * Represents line items in a credit note.
 */
class CreditNoteItem extends Model
{
    use HasFactory;

    protected $table = 'credit_note_items';

    protected $fillable = [
        'credit_note_id',
        'description',
        'details',
        'quantity',
        'unit',
        'unit_price',
        'amount',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the credit note this item belongs to.
     */
    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    /**
     * Boot method for auto-calculating amount.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->amount = $item->quantity * $item->unit_price;
        });

        static::saved(function ($item) {
            // Recalculate credit note totals when item is saved
            if ($item->creditNote) {
                $item->creditNote->recalculateTotals();
            }
        });

        static::deleted(function ($item) {
            // Recalculate credit note totals when item is deleted
            if ($item->creditNote) {
                $item->creditNote->recalculateTotals();
            }
        });
    }

    /**
     * Get formatted amount with currency.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' EGP';
    }

    /**
     * Get formatted unit price with currency.
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return number_format($this->unit_price, 2) . ' EGP';
    }
}
