<?php

namespace Modules\Invoicing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InternalTransactionItem Model
 *
 * Represents individual line items on an internal transaction.
 * Used for detailed breakdown of inter-business unit transactions.
 */
class InternalTransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'internal_transaction_id',
        'description',
        'amount',
        'account_code',
        'cost_center',
        'project_reference',
        'sort_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * Get the internal transaction this item belongs to.
     */
    public function internalTransaction(): BelongsTo
    {
        return $this->belongsTo(InternalTransaction::class);
    }

    /**
     * Calculate and update parent transaction total when item changes.
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($item) {
            // Recalculate transaction total when item is saved
            $item->internalTransaction->calculateTotal();
        });

        static::deleted(function ($item) {
            // Recalculate transaction total when item is deleted
            $item->internalTransaction->calculateTotal();
        });
    }
}