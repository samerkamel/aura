<?php

namespace Modules\Project\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectRevenue extends Model
{
    protected $fillable = [
        'project_id',
        'revenue_type',
        'description',
        'notes',
        'amount',
        'revenue_date',
        'contract_id',
        'invoice_id',
        'status',
        'amount_received',
        'due_date',
        'received_date',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_received' => 'decimal:2',
        'revenue_date' => 'date',
        'due_date' => 'date',
        'received_date' => 'date',
    ];

    /**
     * Revenue types.
     */
    public const REVENUE_TYPES = [
        'contract' => 'Contract Payment',
        'invoice' => 'Invoice',
        'milestone' => 'Milestone Payment',
        'retainer' => 'Retainer',
        'other' => 'Other',
    ];

    /**
     * Revenue type colors.
     */
    public const REVENUE_TYPE_COLORS = [
        'contract' => 'primary',
        'invoice' => 'info',
        'milestone' => 'success',
        'retainer' => 'warning',
        'other' => 'secondary',
    ];

    /**
     * Revenue statuses.
     */
    public const STATUSES = [
        'planned' => 'Planned',
        'invoiced' => 'Invoiced',
        'partial' => 'Partially Paid',
        'received' => 'Received',
        'overdue' => 'Overdue',
    ];

    /**
     * Status colors.
     */
    public const STATUS_COLORS = [
        'planned' => 'secondary',
        'invoiced' => 'info',
        'partial' => 'warning',
        'received' => 'success',
        'overdue' => 'danger',
    ];

    /**
     * Get the project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the contract (if linked).
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Contract::class);
    }

    /**
     * Get the invoice (if linked).
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(\Modules\Invoicing\Models\Invoice::class);
    }

    /**
     * Get the user who created this revenue entry.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get revenue type label.
     */
    public function getRevenueTypeLabelAttribute(): string
    {
        return self::REVENUE_TYPES[$this->revenue_type] ?? ucfirst($this->revenue_type);
    }

    /**
     * Get revenue type color.
     */
    public function getRevenueTypeColorAttribute(): string
    {
        return self::REVENUE_TYPE_COLORS[$this->revenue_type] ?? 'secondary';
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get status color.
     */
    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    /**
     * Get outstanding amount.
     */
    public function getOutstandingAmountAttribute(): float
    {
        return max(0, $this->amount - $this->amount_received);
    }

    /**
     * Get payment percentage.
     */
    public function getPaymentPercentageAttribute(): float
    {
        if ($this->amount <= 0) {
            return 0;
        }
        return round(($this->amount_received / $this->amount) * 100, 1);
    }

    /**
     * Check if revenue is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_date &&
               $this->due_date->isPast() &&
               $this->outstanding_amount > 0;
    }

    /**
     * Check if revenue is fully received.
     */
    public function isFullyReceived(): bool
    {
        return $this->amount_received >= $this->amount;
    }

    /**
     * Update status based on payment.
     */
    public function updateStatus(): void
    {
        if ($this->isFullyReceived()) {
            $this->status = 'received';
        } elseif ($this->amount_received > 0) {
            $this->status = 'partial';
        } elseif ($this->isOverdue()) {
            $this->status = 'overdue';
        }
        $this->save();
    }

    /**
     * Scope for specific status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for received revenue.
     */
    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    /**
     * Scope for pending revenue.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['planned', 'invoiced', 'partial', 'overdue']);
    }

    /**
     * Scope for date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('revenue_date', [$startDate, $endDate]);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-update status when saved
        static::saving(function ($revenue) {
            if ($revenue->isFullyReceived()) {
                $revenue->status = 'received';
                if (!$revenue->received_date) {
                    $revenue->received_date = now();
                }
            } elseif ($revenue->amount_received > 0) {
                $revenue->status = 'partial';
            } elseif ($revenue->due_date && $revenue->due_date->isPast() && $revenue->outstanding_amount > 0) {
                $revenue->status = 'overdue';
            }
        });
    }
}
