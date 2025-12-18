<?php

namespace Modules\Attendance\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HR\Models\Employee;

/**
 * Permission Usage Model
 *
 * Tracks when employees use their attendance permissions on specific dates
 *
 * @property int $id
 * @property int $employee_id
 * @property \Carbon\Carbon $date
 * @property int $minutes_used
 * @property int $granted_by_user_id
 * @property string|null $reason
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PermissionUsage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'employee_id',
        'date',
        'minutes_used',
        'granted_by_user_id',
        'reason',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'date' => 'date',
        'minutes_used' => 'integer',
    ];

    /**
     * Get the employee that used the permission
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Get the user who granted the permission
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }

    /**
     * Get the permission usage for a specific employee on a specific date
     */
    public static function getForDate(int $employeeId, string $date): ?self
    {
        return static::where('employee_id', $employeeId)
            ->where('date', $date)
            ->first();
    }

    /**
     * Get the count of permissions used by an employee in a given month
     */
    public static function getMonthlyUsageCount(int $employeeId, int $year, int $month): int
    {
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();

        return static::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->count();
    }

    /**
     * Get all permission usages for an employee in a given date range
     */
    public static function getForDateRange(int $employeeId, string $startDate, string $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Get the total available permissions for an employee in a given month
     * (base permissions from rules + extra permissions from overrides)
     */
    public static function getTotalAvailablePermissions(int $employeeId, int $year, int $month): int
    {
        // Get base permissions from the rule
        $permissionRule = AttendanceRule::getPermissionRule();
        $basePermissions = $permissionRule?->config['max_per_month'] ?? 0;

        // Get extra permissions from overrides for this month
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $extraPermissions = PermissionOverride::where('employee_id', $employeeId)
            ->where('payroll_period_start_date', $monthStart)
            ->sum('extra_permissions_granted');

        return $basePermissions + $extraPermissions;
    }

    /**
     * Get the remaining permissions for an employee in a given month
     */
    public static function getRemainingPermissions(int $employeeId, int $year, int $month): int
    {
        $total = static::getTotalAvailablePermissions($employeeId, $year, $month);
        $used = static::getMonthlyUsageCount($employeeId, $year, $month);

        return max(0, $total - $used);
    }

    /**
     * Check if an employee can use a permission on a given date
     */
    public static function canUsePermission(int $employeeId, string $date): array
    {
        $dateObj = Carbon::parse($date);
        $year = $dateObj->year;
        $month = $dateObj->month;

        // Get remaining permissions (needed for all scenarios)
        $remaining = static::getRemainingPermissions($employeeId, $year, $month);

        // Check if already used a permission on this date
        $existingUsage = static::getForDate($employeeId, $date);
        if ($existingUsage) {
            return [
                'can_use' => false,
                'reason' => 'Permission already used on this date',
                'existing_usage' => $existingUsage,
                'remaining' => $remaining,
            ];
        }

        // Check remaining permissions
        if ($remaining <= 0) {
            return [
                'can_use' => false,
                'reason' => 'No remaining permissions for this month',
                'remaining' => 0,
            ];
        }

        return [
            'can_use' => true,
            'remaining' => $remaining,
        ];
    }
}
