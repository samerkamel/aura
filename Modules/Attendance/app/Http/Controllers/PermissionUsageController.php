<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Attendance\Models\AttendanceRule;
use Modules\Attendance\Models\PermissionUsage;
use Carbon\Carbon;

/**
 * Permission Usage Controller
 *
 * Handles adding and removing permission usage for employees on specific dates
 */
class PermissionUsageController extends Controller
{
    /**
     * Get permission status for an employee on a specific date
     */
    public function status(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
        ]);

        $employeeId = $validated['employee_id'];
        $date = $validated['date'];
        $dateObj = Carbon::parse($date);

        // Get permission rule
        $permissionRule = AttendanceRule::getPermissionRule();
        $minutesPerPermission = $permissionRule?->config['minutes_per_permission'] ?? 120;

        // Get status
        $canUseResult = PermissionUsage::canUsePermission($employeeId, $date);
        $totalAvailable = PermissionUsage::getTotalAvailablePermissions($employeeId, $dateObj->year, $dateObj->month);
        $used = PermissionUsage::getMonthlyUsageCount($employeeId, $dateObj->year, $dateObj->month);
        $existingUsage = PermissionUsage::getForDate($employeeId, $date);

        return response()->json([
            'can_use' => $canUseResult['can_use'],
            'reason' => $canUseResult['reason'] ?? null,
            'total_available' => $totalAvailable,
            'used_this_month' => $used,
            'remaining' => $canUseResult['remaining'] ?? 0,
            'minutes_per_permission' => $minutesPerPermission,
            'existing_usage' => $existingUsage ? [
                'id' => $existingUsage->id,
                'minutes_used' => $existingUsage->minutes_used,
                'reason' => $existingUsage->reason,
                'granted_by' => $existingUsage->grantedBy?->name,
                'created_at' => $existingUsage->created_at->format('Y-m-d H:i:s'),
            ] : null,
        ]);
    }

    /**
     * Store a new permission usage
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'reason' => 'nullable|string|max:500',
        ]);

        $employeeId = $validated['employee_id'];
        $date = $validated['date'];

        // Check if permission can be used
        $canUseResult = PermissionUsage::canUsePermission($employeeId, $date);
        if (!$canUseResult['can_use']) {
            return response()->json([
                'success' => false,
                'message' => $canUseResult['reason'],
            ], 422);
        }

        // Get permission minutes from rule
        $permissionRule = AttendanceRule::getPermissionRule();
        $minutesPerPermission = $permissionRule?->config['minutes_per_permission'] ?? 120;

        // Create the permission usage
        $permissionUsage = PermissionUsage::create([
            'employee_id' => $employeeId,
            'date' => $date,
            'minutes_used' => $minutesPerPermission,
            'granted_by_user_id' => auth()->id(),
            'reason' => $validated['reason'] ?? null,
        ]);

        $dateObj = Carbon::parse($date);

        return response()->json([
            'success' => true,
            'message' => 'Permission added successfully',
            'permission_usage' => [
                'id' => $permissionUsage->id,
                'minutes_used' => $permissionUsage->minutes_used,
                'reason' => $permissionUsage->reason,
            ],
            'remaining' => PermissionUsage::getRemainingPermissions($employeeId, $dateObj->year, $dateObj->month),
        ]);
    }

    /**
     * Remove a permission usage
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
        ]);

        $employeeId = $validated['employee_id'];
        $date = $validated['date'];

        // Find the permission usage
        $permissionUsage = PermissionUsage::getForDate($employeeId, $date);

        if (!$permissionUsage) {
            return response()->json([
                'success' => false,
                'message' => 'No permission found for this date',
            ], 404);
        }

        $permissionUsage->delete();

        $dateObj = Carbon::parse($date);

        return response()->json([
            'success' => true,
            'message' => 'Permission removed successfully',
            'remaining' => PermissionUsage::getRemainingPermissions($employeeId, $dateObj->year, $dateObj->month),
        ]);
    }
}
