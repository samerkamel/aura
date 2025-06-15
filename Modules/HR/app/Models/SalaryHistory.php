<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SalaryHistory Model
 *
 * Tracks the history of salary changes for employees, including the old salary,
 * new salary, change date, and optional reason for the change.
 *
 * @author Dev Agent
 */
class SalaryHistory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'employee_id',
        'old_salary',
        'new_salary',
        'change_date',
        'reason',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'old_salary' => 'decimal:2',
        'new_salary' => 'decimal:2',
        'change_date' => 'datetime',
    ];

    /**
     * The default attributes.
     */
    protected $attributes = [
        'change_date' => null, // Will be set to current timestamp in migration
    ];

    /**
     * Get the employee that owns this salary history record.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the formatted old salary.
     */
    public function getFormattedOldSalaryAttribute(): string
    {
        return number_format($this->old_salary, 2);
    }

    /**
     * Get the formatted new salary.
     */
    public function getFormattedNewSalaryAttribute(): string
    {
        return number_format($this->new_salary, 2);
    }

    /**
     * Get the salary change amount.
     */
    public function getSalaryChangeAttribute(): float
    {
        return $this->new_salary - $this->old_salary;
    }

    /**
     * Get the formatted salary change amount.
     */
    public function getFormattedSalaryChangeAttribute(): string
    {
        $change = $this->salary_change;
        $prefix = $change >= 0 ? '+' : '';

        return $prefix . number_format($change, 2);
    }

    /**
     * Scope a query to order by most recent change first.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('change_date', 'desc');
    }
}
