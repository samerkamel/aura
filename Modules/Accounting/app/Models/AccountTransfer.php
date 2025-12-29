<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AccountTransfer Model
 *
 * Represents a transfer of funds between two accounts (balance swap).
 */
class AccountTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_account_id',
        'to_account_id',
        'amount',
        'transfer_date',
        'reference',
        'description',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transfer_date' => 'date',
    ];

    /**
     * Get the source account.
     */
    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    /**
     * Get the destination account.
     */
    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    /**
     * Get the user who created this transfer.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2);
    }

    /**
     * Get transfer summary for display.
     */
    public function getSummaryAttribute(): string
    {
        return "{$this->fromAccount->name} → {$this->toAccount->name}: {$this->formatted_amount}";
    }

    /**
     * Create a transfer and update account balances.
     */
    public static function createWithBalanceUpdate(array $data): self
    {
        $transfer = static::create($data);

        // Update account balances
        $fromAccount = Account::find($data['from_account_id']);
        $toAccount = Account::find($data['to_account_id']);

        if ($fromAccount) {
            $fromAccount->updateBalance($data['amount'], 'subtract');
        }

        if ($toAccount) {
            $toAccount->updateBalance($data['amount'], 'add');
        }

        return $transfer;
    }
}
