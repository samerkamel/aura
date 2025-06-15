<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HR\Models\Employee;
use Carbon\Carbon;

/**
 * BillableHour Model
 *
 * Represents billable hours tracked for an employee in a specific payroll period.
 * Used as a component in payroll calculations.
 *
 * @author Dev Agent
 */
class BillableHour extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'employee_id',
        'payroll_period_start_date',
        'hours',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'payroll_period_start_date' => 'date',
        'hours' => 'decimal:2',
    ];

    /**
     * Get the employee that owns this billable hour record.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Scope to filter by payroll period.
     */
    public function scopeForPeriod($query, Carbon $periodStart)
    {
        return $query->where('payroll_period_start_date', $periodStart);
    }

    /**
     * Scope to filter by current payroll period.
     */
    public function scopeForCurrentPeriod($query)
    {
        return $query->where('payroll_period_start_date', $this->getCurrentPayrollPeriodStart());
    }

    /**
     * Get current payroll period start date (first day of current month).
     */
    public function getCurrentPayrollPeriodStart(): Carbon
    {
        return Carbon::now()->startOfMonth();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Payroll\Database\Factories\BillableHourFactory::new();
    }
}
