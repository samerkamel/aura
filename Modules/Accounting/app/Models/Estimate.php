<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Estimate Model
 *
 * Represents client estimates/quotations that can be converted to contracts.
 */
class Estimate extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'estimates';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'estimate_number',
        'customer_id',
        'project_id',
        'client_name',
        'client_email',
        'client_address',
        'title',
        'description',
        'issue_date',
        'valid_until',
        'status',
        'subtotal',
        'vat_rate',
        'vat_amount',
        'total',
        'notes',
        'internal_notes',
        'created_by',
        'sent_at',
        'approved_at',
        'rejected_at',
        'converted_to_contract_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'issue_date' => 'date',
        'valid_until' => 'date',
        'sent_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Get estimate items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(EstimateItem::class)->orderBy('sort_order');
    }

    /**
     * Get the customer this estimate belongs to.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    /**
     * Get the project this estimate belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(\Modules\Project\Models\Project::class);
    }

    /**
     * Get the user who created this estimate.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the contract this estimate was converted to.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'converted_to_contract_id');
    }

    /**
     * Scope to get draft estimates.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to get sent estimates.
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope to get approved estimates.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get rejected estimates.
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Recalculate totals based on items.
     */
    public function recalculateTotals(): void
    {
        $this->subtotal = $this->items->sum('amount');
        $this->vat_amount = $this->subtotal * ($this->vat_rate / 100);
        $this->total = $this->subtotal + $this->vat_amount;
        $this->save();
    }

    /**
     * Generate a new estimate number.
     */
    public static function generateNumber(): string
    {
        $year = now()->year;
        $lastEstimate = static::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();

        $sequence = $lastEstimate
            ? (int) substr($lastEstimate->estimate_number, -4) + 1
            : 1;

        return sprintf('EST-%d-%04d', $year, $sequence);
    }

    /**
     * Check if estimate can be converted to a contract.
     */
    public function canBeConverted(): bool
    {
        return $this->status === 'approved' && !$this->converted_to_contract_id;
    }

    /**
     * Check if estimate can be edited.
     */
    public function canBeEdited(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if estimate can be sent.
     */
    public function canBeSent(): bool
    {
        return $this->status === 'draft' && $this->items()->count() > 0;
    }

    /**
     * Check if estimate is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    /**
     * Get status badge color for UI display.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'sent' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
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
            'sent' => 'Sent',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
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
     * Get days until expiry.
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->valid_until) {
            return null;
        }

        return now()->diffInDays($this->valid_until, false);
    }
}
