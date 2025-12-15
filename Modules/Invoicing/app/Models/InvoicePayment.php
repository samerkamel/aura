<?php

namespace Modules\Invoicing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InvoicePayment Model
 *
 * Tracks individual payments made against invoices.
 * Supports partial payments and full payment tracking.
 */
class InvoicePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'account_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
        'attachment_path',
        'attachment_original_name',
        'attachment_mime_type',
        'attachment_size',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the invoice this payment belongs to.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the account that received this payment.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class);
    }

    /**
     * Get the user who created this payment.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get payment method display name.
     */
    public function getPaymentMethodDisplayAttribute(): string
    {
        return match($this->payment_method) {
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'check' => 'Check',
            'card' => 'Credit/Debit Card',
            'online' => 'Online Payment',
            'other' => 'Other',
            default => $this->payment_method ? ucfirst(str_replace('_', ' ', $this->payment_method)) : 'Not Specified'
        };
    }

    /**
     * Check if payment has an attachment.
     */
    public function hasAttachment(): bool
    {
        return !empty($this->attachment_path);
    }

    /**
     * Get the attachment download URL.
     */
    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->hasAttachment()) {
            return null;
        }

        return route('invoicing.payments.attachment', $this->id);
    }

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($payment) {
            // Update invoice payment tracking when payment is saved
            $payment->invoice->updatePaymentStatus();
        });

        static::deleted(function ($payment) {
            // Update invoice payment tracking when payment is deleted
            $payment->invoice->updatePaymentStatus();
        });
    }
}