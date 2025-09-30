<?php

namespace Modules\Invoicing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * InternalTransaction Model
 *
 * Represents transactions between business units within the organization.
 * Separate from customer invoices and used for internal accounting.
 */
class InternalTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_number',
        'transaction_date',
        'total_amount',
        'status',
        'approval_status',
        'from_business_unit_id',
        'to_business_unit_id',
        'description',
        'reference',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'internal_sequence_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the items for this transaction.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InternalTransactionItem::class)->orderBy('sort_order');
    }

    /**
     * Get the business unit this transaction is from.
     */
    public function fromBusinessUnit(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessUnit::class, 'from_business_unit_id');
    }

    /**
     * Get the business unit this transaction is to.
     */
    public function toBusinessUnit(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessUnit::class, 'to_business_unit_id');
    }

    /**
     * Get the user who created this transaction.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who approved this transaction.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /**
     * Get the sequence this transaction was generated from.
     */
    public function internalSequence(): BelongsTo
    {
        return $this->belongsTo(InternalSequence::class);
    }

    /**
     * Scope to get only draft transactions.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to get only pending transactions.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get only approved transactions.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get transactions needing approval.
     */
    public function scopeNeedsApproval(Builder $query): Builder
    {
        return $query->where('approval_status', 'pending')
                    ->where('status', 'pending');
    }

    /**
     * Scope to get transactions for a specific business unit.
     */
    public function scopeForBusinessUnit(Builder $query, int $businessUnitId): Builder
    {
        return $query->where(function ($q) use ($businessUnitId) {
            $q->where('from_business_unit_id', $businessUnitId)
              ->orWhere('to_business_unit_id', $businessUnitId);
        });
    }

    /**
     * Scope to get transactions in a date range.
     */
    public function scopeInPeriod(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->where('transaction_date', '>=', $startDate)
                    ->where('transaction_date', '<=', $endDate);
    }

    /**
     * Get transaction status badge class for UI.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'draft' => 'bg-secondary',
            'pending' => 'bg-warning',
            'approved' => 'bg-success',
            'cancelled' => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    /**
     * Get transaction status display text.
     */
    public function getStatusDisplayAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'pending' => 'Pending',
            'approved' => 'Approved',
            'cancelled' => 'Cancelled',
            default => 'Unknown'
        };
    }

    /**
     * Get approval status badge class for UI.
     */
    public function getApprovalStatusBadgeClassAttribute(): string
    {
        return match($this->approval_status) {
            'pending' => 'bg-warning',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    /**
     * Get approval status display text.
     */
    public function getApprovalStatusDisplayAttribute(): string
    {
        return match($this->approval_status) {
            'pending' => 'Pending Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Unknown'
        };
    }

    /**
     * Submit transaction for approval.
     */
    public function submitForApproval(): void
    {
        $this->update([
            'status' => 'pending',
            'approval_status' => 'pending',
        ]);
    }

    /**
     * Approve transaction.
     */
    public function approve(int $approvedBy): void
    {
        $this->update([
            'status' => 'approved',
            'approval_status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject transaction.
     */
    public function reject(int $rejectedBy): void
    {
        $this->update([
            'approval_status' => 'rejected',
            'approved_by' => $rejectedBy,
            'approved_at' => now(),
        ]);
    }

    /**
     * Cancel transaction.
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Calculate and update total based on items.
     */
    public function calculateTotal(): void
    {
        $total = $this->items->sum('amount');
        $this->update(['total_amount' => $total]);
    }

    /**
     * Check if transaction can be edited.
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft']);
    }

    /**
     * Check if transaction can be approved.
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending' && $this->approval_status === 'pending';
    }
}