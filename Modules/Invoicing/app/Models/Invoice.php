<?php

namespace Modules\Invoicing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * Invoice Model
 *
 * Represents customer invoices generated from contract payments or created manually.
 * Supports automatic numbering, payment tracking, and business unit isolation.
 */
class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'status',
        'customer_id',
        'business_unit_id',
        'invoice_sequence_id',
        'created_by',
        'notes',
        'terms_conditions',
        'reference',
        'paid_amount',
        'paid_date',
        'payment_notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    /**
     * Get the items for this invoice.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    /**
     * Get the payments for this invoice.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class)->orderBy('payment_date', 'desc');
    }

    /**
     * Get the customer this invoice belongs to.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    /**
     * Get the business unit this invoice belongs to.
     */
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessUnit::class);
    }

    /**
     * Get the sequence this invoice was generated from.
     */
    public function invoiceSequence(): BelongsTo
    {
        return $this->belongsTo(InvoiceSequence::class);
    }

    /**
     * Get the user who created this invoice.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Scope to get only draft invoices.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to get only sent invoices.
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope to get only paid invoices.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope to get overdue invoices.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', '!=', 'paid')
                    ->where('status', '!=', 'cancelled')
                    ->where('due_date', '<', now());
    }

    /**
     * Scope to get invoices due within a date range.
     */
    public function scopeDueInPeriod(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->where('due_date', '>=', $startDate)
                    ->where('due_date', '<=', $endDate);
    }

    /**
     * Get invoice status badge class for UI.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'draft' => 'bg-secondary',
            'sent' => 'bg-info',
            'paid' => 'bg-success',
            'cancelled' => 'bg-danger',
            'overdue' => 'bg-warning',
            default => 'bg-secondary'
        };
    }

    /**
     * Get invoice status display text.
     */
    public function getStatusDisplayAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'sent' => 'Sent',
            'paid' => 'Paid',
            'cancelled' => 'Cancelled',
            'overdue' => 'Overdue',
            default => 'Unknown'
        };
    }

    /**
     * Check if invoice is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'paid' &&
               $this->status !== 'cancelled' &&
               $this->due_date->lt(now());
    }

    /**
     * Get remaining days until due date.
     */
    public function getDaysUntilDueAttribute(): int
    {
        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Get remaining amount to be paid.
     */
    public function getRemainingAmountAttribute(): float
    {
        return $this->total_amount - $this->paid_amount;
    }

    /**
     * Check if invoice is fully paid.
     */
    public function getIsFullyPaidAttribute(): bool
    {
        return $this->paid_amount >= $this->total_amount;
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(float $amount = null, $paidDate = null, string $notes = null, int $accountId = null): void
    {
        $paidAmount = $amount ?? $this->total_amount;
        $carbonDate = $paidDate ? Carbon::parse($paidDate) : now();

        $this->update([
            'status' => 'paid',
            'paid_amount' => $paidAmount,
            'paid_date' => $carbonDate,
            'payment_notes' => $notes,
        ]);

        // Create a payment record and update account balance if account is provided
        if ($accountId) {
            // Create payment record
            $this->payments()->create([
                'amount' => $paidAmount,
                'payment_date' => $carbonDate,
                'payment_method' => 'other',
                'reference_number' => 'INV-' . $this->invoice_number,
                'notes' => $notes ?? 'Payment recorded when marking invoice as paid',
                'account_id' => $accountId,
                'created_by' => auth()->id(),
            ]);

            // Update account balance
            $account = \Modules\Accounting\Models\Account::find($accountId);
            if ($account) {
                $account->updateBalance($paidAmount, 'add');
            }
        }
    }

    /**
     * Mark invoice as sent.
     */
    public function markAsSent(): void
    {
        $this->update(['status' => 'sent']);
    }

    /**
     * Cancel invoice.
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Update overdue invoices automatically.
     */
    public static function updateOverdueStatus(): void
    {
        static::where('status', 'sent')
            ->where('due_date', '<', now())
            ->update(['status' => 'overdue']);
    }

    /**
     * Calculate and update totals based on items.
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->items->sum('total');
        $this->update([
            'subtotal' => $subtotal,
            'total_amount' => $subtotal + $this->tax_amount,
        ]);
    }

    /**
     * Update payment status based on total payments received.
     */
    public function updatePaymentStatus(): void
    {
        $totalPaid = $this->payments->sum('amount');

        $this->update([
            'paid_amount' => $totalPaid,
            'paid_date' => $totalPaid >= $this->total_amount ? ($this->payments->first()->payment_date ?? now()) : null,
            'status' => $this->determineStatusFromPayments($totalPaid),
        ]);
    }

    /**
     * Determine invoice status based on payment amount.
     */
    private function determineStatusFromPayments(float $totalPaid): string
    {
        // Don't change status if invoice is cancelled
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        // If fully paid
        if ($totalPaid >= $this->total_amount) {
            return 'paid';
        }

        // If partially paid or unpaid, check if overdue
        if ($this->due_date < now() && $this->status !== 'draft') {
            return 'overdue';
        }

        // If not draft and not paid, mark as sent
        if ($this->status !== 'draft') {
            return 'sent';
        }

        return 'draft';
    }

    /**
     * Get total payments received.
     */
    public function getTotalPaidAttribute(): float
    {
        return $this->payments->sum('amount');
    }

    /**
     * Check if invoice has partial payments.
     */
    public function getHasPartialPaymentsAttribute(): bool
    {
        $totalPaid = $this->total_paid;
        return $totalPaid > 0 && $totalPaid < $this->total_amount;
    }
}