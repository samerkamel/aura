<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HR\Models\Employee;
use App\Models\User;
use Carbon\Carbon;

/**
 * Work From Home Record Model
 *
 * Represents a work from home day for an employee
 * with specific date and creation details.
 *
 * @author Dev Agent
 */
class WfhRecord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'employee_id',
        'date',
        'notes',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the employee that owns the WFH record.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who created this WFH record.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeInDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by specific month.
     */
    public function scopeInMonth($query, Carbon $date)
    {
        return $query->whereYear('date', $date->year)
            ->whereMonth('date', $date->month);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Attendance\Database\Factories\WfhRecordFactory::new();
    }

    /**
     * Check if a WFH record exists for an employee on a specific date.
     */
    public static function existsForEmployeeOnDate(int $employeeId, Carbon $date): bool
    {
        return self::where('employee_id', $employeeId)
            ->where('date', $date->format('Y-m-d'))
            ->exists();
    }
}
