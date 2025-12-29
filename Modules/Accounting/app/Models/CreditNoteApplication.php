<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CreditNoteApplication Model
 *
 * Tracks how credit notes are applied to invoices.
 */
class CreditNoteApplication extends Model
{
    use HasFactory;

    protected $table = 'credit_note_applications';

    protected $fillable = [
        'credit_note_id',
        'invoice_id',
        'amount_applied',
        'applied_date',
        'notes',
        'applied_by',
    ];

    protected $casts = [
        'applied_date' => 'date',
        'amount_applied' => 'decimal:2',
    ];

    /**
     * Get the credit note this application belongs to.
     */
    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    /**
     * Get the invoice this application was applied to.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(\Modules\Invoicing\Models\Invoice::class);
    }

    /**
     * Get the user who applied this credit.
     */
    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'applied_by');
    }

    /**
     * Get formatted amount with currency.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount_applied, 2) . ' EGP';
    }
}
