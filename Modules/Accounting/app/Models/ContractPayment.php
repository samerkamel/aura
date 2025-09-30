<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * ContractPayment Model
 *
 * Represents individual payment milestones for contracts.
 * Can be either milestone payments (specific dates/amounts) or generated from recurring settings.
 */
class ContractPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'name',
        'description',
        'amount',
        'due_date',
        'status',
        'paid_date',
        'paid_amount',
        'notes',
        'is_milestone',
        'sequence_number',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
        'is_milestone' => 'boolean',
        'sequence_number' => 'integer',
    ];

    /**
     * Get the contract this payment belongs to.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the invoice item that was created from this contract payment.
     */
    public function invoiceItem(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\Modules\Invoicing\Models\InvoiceItem::class);
    }

    /**
     * Scope to get only pending payments.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get only paid payments.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope to get payments due within a date range.
     */
    public function scopeDueInPeriod(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->whereNotNull('due_date')
                    ->where('due_date', '>=', $startDate)
                    ->where('due_date', '<=', $endDate);
    }

    /**
     * Scope to get overdue payments.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'pending')
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', now());
    }

    /**
     * Scope to get planning milestones (no due date set).
     */
    public function scopePlanning(Builder $query): Builder
    {
        return $query->whereNull('due_date');
    }

    /**
     * Scope to get scheduled payments (with due dates).
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->whereNotNull('due_date');
    }

    /**
     * Get payment status badge class for UI.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'pending' => 'bg-warning',
            'paid' => 'bg-success',
            'overdue' => 'bg-danger',
            'cancelled' => 'bg-secondary',
            default => 'bg-secondary'
        };
    }

    /**
     * Get payment status display text.
     */
    public function getStatusDisplayAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'paid' => 'Paid',
            'overdue' => 'Overdue',
            'cancelled' => 'Cancelled',
            default => 'Unknown'
        };
    }

    /**
     * Check if payment is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'pending' && $this->due_date->lt(now());
    }

    /**
     * Get remaining days until due date.
     */
    public function getDaysUntilDueAttribute(): int
    {
        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Mark payment as paid.
     */
    public function markAsPaid(float $amount = null, Carbon $paidDate = null): void
    {
        $this->update([
            'status' => 'paid',
            'paid_amount' => $amount ?? $this->amount,
            'paid_date' => $paidDate ?? now(),
        ]);
    }

    /**
     * Update overdue payments automatically.
     */
    public static function updateOverdueStatus(): void
    {
        static::where('status', 'pending')
            ->where('due_date', '<', now())
            ->update(['status' => 'overdue']);
    }
}