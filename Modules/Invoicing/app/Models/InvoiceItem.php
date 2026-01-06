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
        'long_description',
        'quantity',
        'unit_price',
        'unit',
        'tax_rate',
        'tax_amount',
        'total',
        'sort_order',
        'contract_payment_id',
        'project_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
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
     * Get the project this item is allocated to (if any).
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(\Modules\Project\Models\Project::class);
    }

    /**
     * Calculate total automatically when saving.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $subtotal = $item->quantity * $item->unit_price;
            $item->tax_amount = $subtotal * ($item->tax_rate / 100);
            $item->total = $subtotal + $item->tax_amount;
        });

        static::saved(function ($item) {
            // Recalculate invoice totals when item is saved (skip during migration)
            if (!app()->runningInConsole() || !str_contains(request()->server('argv')[1] ?? '', 'perfex:migrate')) {
                $item->invoice->calculateTotals();
            }
        });

        static::deleted(function ($item) {
            // Recalculate invoice totals when item is deleted
            $item->invoice->calculateTotals();
        });
    }

    /**
     * Get the subtotal (before tax).
     */
    public function getSubtotalAttribute(): float
    {
        return $this->quantity * $this->unit_price;
    }
}