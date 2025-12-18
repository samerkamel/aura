<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * Contract Model
 *
 * Represents client contracts that generate income schedules in the cash flow system.
 * Contracts serve as the parent entity for organizing income streams.
 */
class Contract extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'contracts';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'client_name',
        'customer_id',
        'contract_number',
        'description',
        'total_amount',
        'start_date',
        'end_date',
        'status',
        'is_active',
        'contact_info',
        'notes',
        'business_unit_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'total_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'contact_info' => 'array',
    ];


    /**
     * Get all payments for this contract.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(ContractPayment::class)
                    ->orderBy('sequence_number')
                    ->orderBy('due_date');
    }

    /**
     * Get pending payments for this contract.
     */
    public function pendingPayments(): HasMany
    {
        return $this->hasMany(ContractPayment::class)
                    ->where('status', 'pending')
                    ->orderBy('due_date');
    }

    /**
     * Get paid payments for this contract.
     */
    public function paidPayments(): HasMany
    {
        return $this->hasMany(ContractPayment::class)
                    ->where('status', 'paid')
                    ->orderBy('paid_date', 'desc');
    }

    /**
     * Get the business unit this contract belongs to.
     */
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessUnit::class);
    }

    /**
     * Get the customer this contract belongs to.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    /**
     * Get the projects associated with this contract.
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(\Modules\Project\Models\Project::class, 'contract_project')
                    ->withTimestamps();
    }

    /**
     * Get the products that this contract is allocated to.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Product::class, 'contract_product', 'contract_id', 'product_id')
                    ->withPivot(['allocation_type', 'allocation_percentage', 'allocation_amount', 'notes'])
                    ->withTimestamps();
    }

    /**
     * Legacy method for backwards compatibility - redirects to products.
     * @deprecated Use products() instead
     */
    public function departments(): BelongsToMany
    {
        return $this->products();
    }

    /**
     * Scope to get only active contracts.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
                    ->where('status', '!=', 'cancelled');
    }

    /**
     * Scope to get contracts by status.
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by business unit.
     */
    public function scopeForBusinessUnit(Builder $query, $businessUnitId): Builder
    {
        return $query->where('business_unit_id', $businessUnitId);
    }


    /**
     * Get count of active payments.
     */
    public function getActivePaymentsCountAttribute(): int
    {
        return $this->payments()->where('status', '!=', 'cancelled')->count();
    }

    /**
     * Get contract progress percentage based on income received vs total.
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_amount == 0) {
            return 0;
        }

        // This would be calculated based on actual payments received
        // For now, we'll calculate based on time elapsed if contract has dates
        if ($this->start_date && $this->end_date) {
            $totalDays = $this->start_date->diffInDays($this->end_date);
            $elapsedDays = $this->start_date->diffInDays(now());

            return min(100, max(0, ($elapsedDays / $totalDays) * 100));
        }

        return 0;
    }

    /**
     * Check if contract is currently active based on dates and status.
     */
    public function getIsCurrentlyActiveAttribute(): bool
    {
        if (!$this->is_active || $this->status === 'cancelled') {
            return false;
        }

        $now = now();

        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        return true;
    }


    /**
     * Get total amount of all payments (regardless of status).
     */
    public function getTotalPaymentAmountAttribute(): float
    {
        return $this->payments()->sum('amount');
    }

    /**
     * Get total amount of paid payments.
     */
    public function getPaidAmountAttribute(): float
    {
        return $this->payments()->where('status', 'paid')->sum('paid_amount');
    }

    /**
     * Get total amount of pending payments.
     */
    public function getPendingAmountAttribute(): float
    {
        return $this->payments()->where('status', 'pending')->sum('amount');
    }

    /**
     * Get remaining contract value that hasn't been assigned to payments.
     */
    public function getUnassignedAmountAttribute(): float
    {
        return max(0, $this->total_amount - $this->total_payment_amount);
    }

    /**
     * Check if contract payments exceed contract value.
     */
    public function getIsPaymentOverCommittedAttribute(): bool
    {
        return $this->total_payment_amount > $this->total_amount;
    }

    /**
     * Get contract completion percentage based on paid amounts.
     */
    public function getPaymentProgressPercentageAttribute(): float
    {
        if ($this->total_amount == 0) {
            return 0;
        }

        return min(100, ($this->paid_amount / $this->total_amount) * 100);
    }

    /**
     * Generate recurring payments for this contract.
     */
    public function generateRecurringPayments(string $frequency, int $frequencyValue, Carbon $startDate, Carbon $endDate = null): void
    {
        // Clear existing non-milestone payments
        $this->payments()->where('is_milestone', false)->delete();

        $endDate = $endDate ?? $this->end_date ?? $startDate->copy()->addYear();

        // Calculate total number of payments
        $current = $startDate->copy();
        $paymentDates = [];

        while ($current->lte($endDate)) {
            $paymentDates[] = $current->copy();

            // Calculate next payment date
            switch ($frequency) {
                case 'weekly':
                    $current->addWeeks($frequencyValue);
                    break;
                case 'bi-weekly':
                    $current->addWeeks($frequencyValue * 2);
                    break;
                case 'monthly':
                    $current->addMonths($frequencyValue);
                    break;
                case 'quarterly':
                    $current->addMonths($frequencyValue * 3);
                    break;
                case 'yearly':
                    $current->addYears($frequencyValue);
                    break;
                default:
                    break 2; // Exit loop for unknown frequency
            }
        }

        if (empty($paymentDates)) {
            return;
        }

        // Distribute contract value equally across payments
        $amountPerPayment = $this->total_amount / count($paymentDates);

        // Create payment records
        foreach ($paymentDates as $index => $date) {
            ContractPayment::create([
                'contract_id' => $this->id,
                'name' => "Payment " . ($index + 1),
                'description' => "Recurring payment " . ($index + 1) . " of " . count($paymentDates),
                'amount' => $amountPerPayment,
                'due_date' => $date,
                'status' => 'pending',
                'is_milestone' => false,
                'sequence_number' => $index + 1,
            ]);
        }
    }

    /**
     * Get status badge color for UI display.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'secondary',
            'active' => 'success',
            'completed' => 'primary',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }
}