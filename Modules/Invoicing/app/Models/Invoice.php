<?php

namespace Modules\Invoicing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'perfex_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'currency',
        'exchange_rate',
        'subtotal_in_base',
        'total_in_base',
        'status',
        'customer_id',
        'project_id',
        'invoice_sequence_id',
        'business_unit_id',
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
        'exchange_rate' => 'decimal:6',
        'subtotal_in_base' => 'decimal:2',
        'total_in_base' => 'decimal:2',
    ];

    /**
     * Available currencies.
     */
    public const CURRENCIES = [
        'EGP' => ['name' => 'Egyptian Pound', 'symbol' => 'EGP'],
        'USD' => ['name' => 'US Dollar', 'symbol' => '$'],
        'EUR' => ['name' => 'Euro', 'symbol' => '€'],
        'GBP' => ['name' => 'British Pound', 'symbol' => '£'],
        'SAR' => ['name' => 'Saudi Riyal', 'symbol' => 'SAR'],
        'AED' => ['name' => 'UAE Dirham', 'symbol' => 'AED'],
    ];

    /**
     * Get currency symbol.
     */
    public function getCurrencySymbolAttribute(): string
    {
        return self::CURRENCIES[$this->currency]['symbol'] ?? $this->currency;
    }

    /**
     * Get formatted total with currency.
     */
    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total_amount, 2) . ' ' . $this->currency;
    }

    /**
     * Get formatted total in base currency (EGP).
     */
    public function getFormattedTotalInBaseAttribute(): string
    {
        $total = $this->total_in_base ?? ($this->total_amount * $this->exchange_rate);
        return number_format($total, 2) . ' EGP';
    }

    /**
     * Calculate and store base currency amounts.
     */
    public function calculateBaseCurrencyAmounts(): void
    {
        if ($this->currency !== 'EGP' && $this->exchange_rate > 0) {
            $this->subtotal_in_base = $this->subtotal * $this->exchange_rate;
            $this->total_in_base = $this->total_amount * $this->exchange_rate;
        } else {
            $this->subtotal_in_base = $this->subtotal;
            $this->total_in_base = $this->total_amount;
        }
    }

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
     * Get the project this invoice belongs to (single project - for backward compatibility).
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(\Modules\Project\Models\Project::class);
    }

    /**
     * Get all projects this invoice is allocated to (many-to-many).
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(\Modules\Project\Models\Project::class, 'invoice_project')
            ->withPivot(['allocated_amount', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get the primary project (either from direct link or first allocated project).
     */
    public function getPrimaryProjectAttribute(): ?\Modules\Project\Models\Project
    {
        // First check direct project_id (backward compatibility)
        if ($this->project_id) {
            return $this->project;
        }

        // Otherwise return first allocated project
        return $this->projects->first();
    }

    /**
     * Recalculate project allocations based on line items.
     *
     * Groups line items by project and updates the invoice_project pivot table
     * with the sum of line item totals per project.
     */
    public function recalculateProjectAllocations(): void
    {
        // Get allocations from line items grouped by project
        $allocations = $this->items()
            ->whereNotNull('project_id')
            ->selectRaw('project_id, SUM(total) as total_amount')
            ->groupBy('project_id')
            ->pluck('total_amount', 'project_id')
            ->toArray();

        // Sync projects with their allocated amounts
        $syncData = [];
        foreach ($allocations as $projectId => $amount) {
            $syncData[$projectId] = ['allocated_amount' => $amount];
        }

        $this->projects()->sync($syncData);
    }

    /**
     * Get total allocated amount across all projects.
     */
    public function getTotalAllocatedAmountAttribute(): float
    {
        return $this->projects->sum('pivot.allocated_amount');
    }

    /**
     * Get unallocated amount (difference between total and allocated).
     */
    public function getUnallocatedAmountAttribute(): float
    {
        return max(0, $this->total_amount - $this->total_allocated_amount);
    }

    /**
     * Get the sequence this invoice was generated from.
     */
    public function invoiceSequence(): BelongsTo
    {
        return $this->belongsTo(InvoiceSequence::class);
    }

    /**
     * Get the business unit this invoice belongs to.
     */
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessUnit::class);
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
     * Get number of days the invoice is overdue.
     */
    public function getDaysOverdueAttribute(): int
    {
        if (!$this->is_overdue || !$this->due_date) {
            return 0;
        }
        return $this->due_date->diffInDays(now());
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
     * Mark invoice as sent and assign invoice number from sequence.
     * Invoice numbers are only assigned when invoice is issued, not when created as draft.
     */
    public function markAsSent(): void
    {
        $updateData = ['status' => 'sent'];

        // Assign invoice number if this is a draft (has placeholder number)
        if ($this->isDraft() && $this->invoiceSequence) {
            $updateData['invoice_number'] = $this->invoiceSequence->generateInvoiceNumber();
        }

        $this->update($updateData);
    }

    /**
     * Check if invoice is a draft (has placeholder number).
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft' || str_starts_with($this->invoice_number, 'DRAFT-');
    }

    /**
     * Generate a draft placeholder number.
     */
    public static function generateDraftNumber(): string
    {
        return 'DRAFT-' . now()->format('YmdHis') . '-' . mt_rand(1000, 9999);
    }

    /**
     * Cancel invoice.
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Check if invoice can be deleted.
     * Only draft or cancelled invoices without an actual invoice number can be deleted.
     */
    public function canBeDeleted(): bool
    {
        // Must be draft or cancelled
        if (!in_array($this->status, ['draft', 'cancelled'])) {
            return false;
        }

        // Must not have an actual invoice number (only DRAFT- placeholders allowed)
        // Real invoice numbers don't start with DRAFT-
        if (!str_starts_with($this->invoice_number, 'DRAFT-')) {
            return false;
        }

        return true;
    }

    /**
     * Get human-readable reason why invoice cannot be deleted.
     */
    public function getCannotDeleteReasonAttribute(): ?string
    {
        if ($this->canBeDeleted()) {
            return null;
        }

        if (!in_array($this->status, ['draft', 'cancelled'])) {
            return 'Only draft or cancelled invoices can be deleted.';
        }

        if (!str_starts_with($this->invoice_number, 'DRAFT-')) {
            return 'Invoices with assigned invoice numbers cannot be deleted.';
        }

        return 'This invoice cannot be deleted.';
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
        $totalAmount = $subtotal + $this->tax_amount;

        // Calculate base currency amounts
        $exchangeRate = $this->exchange_rate ?? 1;
        $subtotalInBase = ($this->currency !== 'EGP' && $exchangeRate > 0)
            ? $subtotal * $exchangeRate
            : $subtotal;
        $totalInBase = ($this->currency !== 'EGP' && $exchangeRate > 0)
            ? $totalAmount * $exchangeRate
            : $totalAmount;

        $this->update([
            'subtotal' => $subtotal,
            'total_amount' => $totalAmount,
            'subtotal_in_base' => $subtotalInBase,
            'total_in_base' => $totalInBase,
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