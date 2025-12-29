<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * CreditNote Model
 *
 * Represents credit notes issued to customers that can be applied to invoices.
 * Status flow: draft → open → closed (fully applied) or void (cancelled)
 */
class CreditNote extends Model
{
    use HasFactory;

    protected $table = 'credit_notes';

    protected $fillable = [
        'credit_note_number',
        'customer_id',
        'invoice_id',
        'project_id',
        'client_name',
        'client_email',
        'client_address',
        'credit_note_date',
        'reference',
        'status',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'applied_amount',
        'remaining_credits',
        'notes',
        'internal_notes',
        'terms',
        'created_by',
        'sent_at',
        'perfex_id',
    ];

    protected $casts = [
        'credit_note_date' => 'date',
        'sent_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'applied_amount' => 'decimal:2',
        'remaining_credits' => 'decimal:2',
    ];

    /**
     * Get credit note items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class)->orderBy('sort_order');
    }

    /**
     * Get credit applications.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(CreditNoteApplication::class)->orderBy('applied_date', 'desc');
    }

    /**
     * Get the customer this credit note belongs to.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    /**
     * Get the original invoice if this credit note was created from one.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(\Modules\Invoicing\Models\Invoice::class);
    }

    /**
     * Get the project this credit note belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(\Modules\Project\Models\Project::class);
    }

    /**
     * Get the user who created this credit note.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Scope to get draft credit notes.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to get open credit notes (has remaining credits).
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope to get closed credit notes (fully applied).
     */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', 'closed');
    }

    /**
     * Scope to get void credit notes.
     */
    public function scopeVoid(Builder $query): Builder
    {
        return $query->where('status', 'void');
    }

    /**
     * Scope to get credit notes with available credits.
     */
    public function scopeWithAvailableCredits(Builder $query): Builder
    {
        return $query->where('status', 'open')
                    ->where('remaining_credits', '>', 0);
    }

    /**
     * Recalculate totals based on items.
     */
    public function recalculateTotals(): void
    {
        $this->subtotal = $this->items->sum('amount');
        $this->tax_amount = $this->subtotal * ($this->tax_rate / 100);
        $this->total = $this->subtotal + $this->tax_amount;
        $this->remaining_credits = $this->total - $this->applied_amount;
        $this->save();
    }

    /**
     * Generate a new credit note number.
     */
    public static function generateNumber(): string
    {
        $year = now()->year;
        $lastCreditNote = static::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();

        $sequence = $lastCreditNote
            ? (int) substr($lastCreditNote->credit_note_number, -4) + 1
            : 1;

        return sprintf('CN-%d-%04d', $year, $sequence);
    }

    /**
     * Check if credit note can be edited.
     */
    public function canBeEdited(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if credit note can be sent/opened.
     */
    public function canBeOpened(): bool
    {
        return $this->status === 'draft' && $this->items()->count() > 0;
    }

    /**
     * Check if credit note can be applied to invoices.
     */
    public function canBeApplied(): bool
    {
        return $this->status === 'open' && $this->remaining_credits > 0;
    }

    /**
     * Check if credit note can be voided.
     */
    public function canBeVoided(): bool
    {
        return in_array($this->status, ['draft', 'open']) && $this->applied_amount == 0;
    }

    /**
     * Mark credit note as open (available for application).
     */
    public function markAsOpen(): void
    {
        $this->update([
            'status' => 'open',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark credit note as void.
     */
    public function markAsVoid(): void
    {
        if (!$this->canBeVoided()) {
            throw new \Exception('Credit note cannot be voided. It has already been applied to invoices.');
        }

        $this->update(['status' => 'void']);
    }

    /**
     * Apply credit to an invoice.
     *
     * @param \Modules\Invoicing\Models\Invoice $invoice
     * @param float $amount
     * @param string|null $notes
     * @return CreditNoteApplication
     * @throws \Exception
     */
    public function applyToInvoice($invoice, float $amount, ?string $notes = null): CreditNoteApplication
    {
        if (!$this->canBeApplied()) {
            throw new \Exception('Credit note is not available for application.');
        }

        if ($amount > $this->remaining_credits) {
            throw new \Exception('Amount exceeds available credits.');
        }

        if ($amount > $invoice->remaining_amount) {
            throw new \Exception('Amount exceeds invoice remaining balance.');
        }

        // Create application record
        $application = $this->applications()->create([
            'invoice_id' => $invoice->id,
            'amount_applied' => $amount,
            'applied_date' => now(),
            'notes' => $notes,
            'applied_by' => auth()->id(),
        ]);

        // Update credit note amounts
        $this->applied_amount += $amount;
        $this->remaining_credits = $this->total - $this->applied_amount;

        // Update status if fully applied
        if ($this->remaining_credits <= 0) {
            $this->status = 'closed';
        }
        $this->save();

        // Update invoice paid amount
        $invoice->paid_amount += $amount;
        if ($invoice->paid_amount >= $invoice->total_amount) {
            $invoice->status = 'paid';
            $invoice->paid_date = now();
        }
        $invoice->save();

        return $application;
    }

    /**
     * Get status badge color for UI display.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'open' => 'success',
            'closed' => 'info',
            'void' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get status label for display.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'open' => 'Open',
            'closed' => 'Closed',
            'void' => 'Void',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get the formatted total with currency.
     */
    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total, 2) . ' EGP';
    }

    /**
     * Get the formatted remaining credits with currency.
     */
    public function getFormattedRemainingCreditsAttribute(): string
    {
        return number_format($this->remaining_credits, 2) . ' EGP';
    }

    /**
     * Get usage percentage.
     */
    public function getUsagePercentageAttribute(): float
    {
        if ($this->total == 0) {
            return 0;
        }
        return round(($this->applied_amount / $this->total) * 100, 1);
    }
}
