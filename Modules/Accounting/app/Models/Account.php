<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Account Model
 *
 * Represents financial accounts for tracking payments and balances.
 * Supports various account types including cash, bank, credit card, etc.
 */
class Account extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'type',
        'account_number',
        'bank_name',
        'starting_balance',
        'current_balance',
        'currency',
        'is_active',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'starting_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get expense schedules paid from this account.
     */
    public function expenseSchedules(): HasMany
    {
        return $this->hasMany(ExpenseSchedule::class, 'paid_from_account_id');
    }

    /**
     * Scope to get only active accounts.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get accounts by type.
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Get account type display name.
     */
    public function getTypeDisplayAttribute(): string
    {
        return match($this->type) {
            'cash' => 'Cash',
            'bank' => 'Bank Account',
            'credit_card' => 'Credit Card',
            'digital_wallet' => 'Digital Wallet',
            'other' => 'Other',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get account balance with currency formatting.
     */
    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->current_balance, 2) . ' ' . $this->currency;
    }

    /**
     * Update account balance.
     */
    public function updateBalance(float $amount, string $operation = 'subtract'): void
    {
        if ($operation === 'subtract') {
            $this->current_balance -= $amount;
        } else {
            $this->current_balance += $amount;
        }

        $this->save();
    }

    /**
     * Get account type badge class for UI.
     */
    public function getTypeBadgeClassAttribute(): string
    {
        return match($this->type) {
            'cash' => 'bg-success',
            'bank' => 'bg-primary',
            'credit_card' => 'bg-warning',
            'digital_wallet' => 'bg-info',
            'other' => 'bg-secondary',
            default => 'bg-secondary',
        };
    }
}