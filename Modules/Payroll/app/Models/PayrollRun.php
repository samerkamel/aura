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
}
