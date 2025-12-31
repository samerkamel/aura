<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HR\Models\Employee;

/**
 * PayrollRun Model
 *
 * Represents a finalized payroll record for an employee for a specific period.
 * Contains the final calculated salary and a JSON snapshot of contributing factors.
 *
 * @author Dev Agent
 */
class PayrollRun extends Model
{
    use HasFactory;

    /**
     * Transfer status constants.
     */
    public const TRANSFER_PENDING = 'pending';
    public const TRANSFER_PROCESSING = 'processing';
    public const TRANSFER_TRANSFERRED = 'transferred';
    public const TRANSFER_FAILED = 'failed';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'employee_id',
        'period_start_date',
        'period_end_date',
        'base_salary',
        'final_salary',
        'calculated_salary',
        'adjusted_salary',
        'bonus_amount',
        'deduction_amount',
        'adjustment_notes',
        'is_adjusted',
        'performance_percentage',
        'calculation_snapshot',
        'status',
        // Transfer status fields
        'transfer_status',
        'transferred_at',
        'transferred_by',
        'synced_to_accounting',
        'synced_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'base_salary' => 'decimal:2',
        'final_salary' => 'decimal:2',
        'calculated_salary' => 'decimal:2',
        'adjusted_salary' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'is_adjusted' => 'boolean',
        'performance_percentage' => 'decimal:2',
        'calculation_snapshot' => 'array',
        'transferred_at' => 'datetime',
        'synced_to_accounting' => 'boolean',
        'synced_at' => 'datetime',
    ];

    /**
     * Get the employee that owns the payroll run.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Scope to get finalized payroll runs.
     */
    public function scopeFinalized($query)
    {
        return $query->where('status', 'finalized');
    }

    /**
     * Scope to get payroll runs for a specific period.
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_start_date', $startDate)
            ->where('period_end_date', $endDate);
    }

    /**
     * Get the effective salary after all adjustments.
     * Priority: adjusted_salary (if set) OR calculated_salary + bonus - deductions
     */
    public function getEffectiveSalaryAttribute(): float
    {
        $baseSalary = $this->adjusted_salary ?? $this->calculated_salary ?? $this->final_salary ?? 0;
        return (float) $baseSalary + (float) ($this->bonus_amount ?? 0) - (float) ($this->deduction_amount ?? 0);
    }

    /**
     * Scope to get payroll runs pending adjustment.
     */
    public function scopePendingAdjustment($query)
    {
        return $query->where('status', 'pending_adjustment');
    }

    /**
     * Scope to get payroll runs pending transfer.
     */
    public function scopePendingTransfer($query)
    {
        return $query->where('status', 'finalized')
            ->where('transfer_status', self::TRANSFER_PENDING);
    }

    /**
     * Scope to get transferred payroll runs.
     */
    public function scopeTransferred($query)
    {
        return $query->where('transfer_status', self::TRANSFER_TRANSFERRED);
    }

    /**
     * Scope to get payroll runs not yet synced to accounting.
     */
    public function scopeNotSyncedToAccounting($query)
    {
        return $query->where('synced_to_accounting', false);
    }

    /**
     * Get the user who transferred this payroll run.
     */
    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'transferred_by');
    }

    /**
     * Get the linked expense schedules.
     */
    public function expenseSchedules()
    {
        return $this->hasMany(\Modules\Accounting\Models\ExpenseSchedule::class, 'payroll_run_id');
    }

    /**
     * Check if this payroll run has been synced to accounting.
     */
    public function isSyncedToAccounting(): bool
    {
        return (bool) $this->synced_to_accounting;
    }

    /**
     * Check if this payroll run has been transferred.
     */
    public function isTransferred(): bool
    {
        return $this->transfer_status === self::TRANSFER_TRANSFERRED;
    }

    /**
     * Get the period name (e.g., "December 2025").
     */
    public function getPeriodNameAttribute(): string
    {
        return $this->period_start_date->format('F Y');
    }
}
