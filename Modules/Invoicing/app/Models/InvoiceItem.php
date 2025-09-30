<?php

namespace Modules\Invoicing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InvoiceItem Model
 *
 * Represents individual line items on an invoice.
 * Can be linked to contract payments for automated invoice generation.
 */
class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'total',
        'sort_order',
        'contract_payment_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * Get the invoice this item belongs to.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the contract payment this item is linked to (if any).
     */
    public function contractPayment(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\ContractPayment::class);
    }

    /**
     * Calculate total automatically when saving.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->total = $item->quantity * $item->unit_price;
        });

        static::saved(function ($item) {
            // Recalculate invoice totals when item is saved
            $item->invoice->calculateTotals();
        });

        static::deleted(function ($item) {
            // Recalculate invoice totals when item is deleted
            $item->invoice->calculateTotals();
        });
    }
}