<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'action',
        'old_values',
        'new_values',
        'amount_changed',
        'description',
        'user_id',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'amount_changed' => 'decimal:2',
    ];

    /**
     * Get the budget that owns the history
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * Get the user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the action color for UI
     */
    public function getActionColorAttribute(): string
    {
        return match($this->action) {
            'created' => 'success',
            'updated' => 'info',
            'allocated' => 'warning',
            'spent' => 'primary',
            'status_changed' => 'secondary',
            default => 'dark'
        };
    }

    /**
     * Get the action icon for UI
     */
    public function getActionIconAttribute(): string
    {
        return match($this->action) {
            'created' => 'ti-plus',
            'updated' => 'ti-edit',
            'allocated' => 'ti-wallet',
            'spent' => 'ti-minus',
            'status_changed' => 'ti-toggle-left',
            default => 'ti-info-circle'
        };
    }

    /**
     * Create a history record for budget creation
     */
    public static function recordCreation(Budget $budget, User $user): self
    {
        return self::create([
            'budget_id' => $budget->id,
            'action' => 'created',
            'new_values' => $budget->toArray(),
            'description' => "Budget created with amount of " . number_format($budget->budget_amount, 2),
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create a history record for budget updates
     */
    public static function recordUpdate(Budget $budget, array $oldValues, User $user): self
    {
        return self::create([
            'budget_id' => $budget->id,
            'action' => 'updated',
            'old_values' => $oldValues,
            'new_values' => $budget->toArray(),
            'description' => "Budget updated",
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create a history record for budget allocation
     */
    public static function recordAllocation(Budget $budget, float $amount, User $user): self
    {
        return self::create([
            'budget_id' => $budget->id,
            'action' => 'allocated',
            'amount_changed' => $amount,
            'new_values' => ['allocated_amount' => $budget->allocated_amount],
            'description' => "Allocated " . number_format($amount, 2) . " from budget",
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create a history record for budget spending
     */
    public static function recordSpending(Budget $budget, float $amount, User $user): self
    {
        return self::create([
            'budget_id' => $budget->id,
            'action' => 'spent',
            'amount_changed' => $amount,
            'new_values' => ['spent_amount' => $budget->spent_amount],
            'description' => "Spent " . number_format($amount, 2) . " from budget",
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create a history record for status changes
     */
    public static function recordStatusChange(Budget $budget, string $oldStatus, User $user): self
    {
        return self::create([
            'budget_id' => $budget->id,
            'action' => 'status_changed',
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => $budget->status],
            'description' => "Status changed from {$oldStatus} to {$budget->status}",
            'user_id' => $user->id,
        ]);
    }
}
