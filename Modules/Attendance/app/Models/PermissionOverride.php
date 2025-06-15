<?php

namespace Modules\Attendance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HR\Models\Employee;

/**
 * Permission Override Model
 *
 * Tracks exceptional permissions granted to employees beyond their standard monthly allowance
 *
 * @author GitHub Copilot
 */
class PermissionOverride extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'payroll_period_start_date',
        'extra_permissions_granted',
        'granted_by_user_id',
        'reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payroll_period_start_date' => 'date',
        'extra_permissions_granted' => 'integer',
    ];

    /**
     * Get the employee that received the extra permissions
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Get the user who granted the extra permissions
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }

    /**
     * Get overrides for current payroll month
     */
    public static function getCurrentMonthOverrides(int $employeeId): int
    {
        $currentMonthStart = now()->startOfMonth();

        return static::where('employee_id', $employeeId)
            ->where('payroll_period_start_date', $currentMonthStart)
            ->sum('extra_permissions_granted');
    }
}
