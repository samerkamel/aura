<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Attendance\Models\AttendanceLog;
use Modules\Attendance\Models\WfhRecord;
use Modules\Leave\Models\LeaveRecord;
use Carbon\Carbon;

/**
 * Manual Attendance Controller
 *
 * Allows Super Admin to manually add attendance records for employees
 */
class ManualAttendanceController extends Controller
{
    /**
     * Store a new manual attendance record
     */
    public function store(Request $request): JsonResponse
    {
        // Check if user is super admin
        $user = auth()->user();
        if (!$user || !($user->hasRole('super-admin') || $user->role === 'super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Super Admin can add manual attendance records.',
            ], 403);
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'type' => 'required|in:time_in,time_out',
        ]);

        $employeeId = $validated['employee_id'];
        $date = $validated['date'];
        $time = $validated['time'];
        $type = $validated['type'];

        // Combine date and time into timestamp
        $timestamp = Carbon::parse($date . ' ' . $time);

        // Check if there's already a record for this employee on this date with the same type
        // For time_in: check if there's already an entry before 1 PM
        // For time_out: check if there's already an entry after 1 PM
        $cutoffTime = Carbon::parse($date . ' 13:00');

        $existingQuery = AttendanceLog::where('employee_id', $employeeId)
            ->whereDate('timestamp', $date);

        if ($type === 'time_in') {
            // Check for existing check-in (before 1 PM or first punch of the day)
            $existingCheckIn = (clone $existingQuery)
                ->where('timestamp', '<', $cutoffTime)
                ->first();

            if ($existingCheckIn) {
                return response()->json([
                    'success' => false,
                    'message' => 'A check-in record already exists for this date at ' . $existingCheckIn->timestamp->format('H:i'),
                ], 422);
            }
        } else {
            // Check for existing check-out (after 1 PM or last punch of the day)
            $existingCheckOut = (clone $existingQuery)
                ->where('timestamp', '>=', $cutoffTime)
                ->first();

            if ($existingCheckOut) {
                return response()->json([
                    'success' => false,
                    'message' => 'A check-out record already exists for this date at ' . $existingCheckOut->timestamp->format('H:i'),
                ], 422);
            }
        }

        // Map the type to database enum value
        $dbType = $type === 'time_in' ? 'sign_in' : 'sign_out';

        // Create the attendance record
        $attendanceLog = AttendanceLog::create([
            'employee_id' => $employeeId,
            'timestamp' => $timestamp,
            'type' => $dbType,
        ]);

        return response()->json([
            'success' => true,
            'message' => ucfirst(str_replace('_', ' ', $type)) . ' recorded successfully at ' . $timestamp->format('H:i'),
            'attendance_log' => [
                'id' => $attendanceLog->id,
                'timestamp' => $attendanceLog->timestamp->format('Y-m-d H:i:s'),
                'type' => $type,
            ],
        ]);
    }

    /**
     * Update an existing attendance record
     */
    public function update(Request $request): JsonResponse
    {
        // Check if user is super admin
        $user = auth()->user();
        if (!$user || !($user->hasRole('super-admin') || $user->role === 'super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Super Admin can update attendance records.',
            ], 403);
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'type' => 'required|in:time_in,time_out',
        ]);

        $employeeId = $validated['employee_id'];
        $date = $validated['date'];
        $time = $validated['time'];
        $type = $validated['type'];

        // Map the type to database enum value
        $dbType = $type === 'time_in' ? 'sign_in' : 'sign_out';

        // Find existing record to update
        $cutoffTime = Carbon::parse($date . ' 13:00');
        $existingRecord = AttendanceLog::where('employee_id', $employeeId)
            ->whereDate('timestamp', $date)
            ->where('type', $dbType)
            ->first();

        if (!$existingRecord) {
            return response()->json([
                'success' => false,
                'message' => 'No existing record found to update.',
            ], 404);
        }

        // Combine date and time into timestamp
        $timestamp = Carbon::parse($date . ' ' . $time);

        $existingRecord->update(['timestamp' => $timestamp]);

        return response()->json([
            'success' => true,
            'message' => ucfirst(str_replace('_', ' ', $type)) . ' updated successfully to ' . $timestamp->format('H:i'),
        ]);
    }

    /**
     * Delete an attendance record
     */
    public function destroy(Request $request): JsonResponse
    {
        // Check if user is super admin
        $user = auth()->user();
        if (!$user || !($user->hasRole('super-admin') || $user->role === 'super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Super Admin can delete attendance records.',
            ], 403);
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'type' => 'required|in:time_in,time_out',
        ]);

        $employeeId = $validated['employee_id'];
        $date = $validated['date'];
        $type = $validated['type'];

        // Map the type to database enum value
        $dbType = $type === 'time_in' ? 'sign_in' : 'sign_out';

        // Find and delete the record
        $deleted = AttendanceLog::where('employee_id', $employeeId)
            ->whereDate('timestamp', $date)
            ->where('type', $dbType)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'No record found to delete.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => ucfirst(str_replace('_', ' ', $type)) . ' record deleted successfully.',
        ]);
    }

    /**
     * Store a WFH record for a specific date
     */
    public function storeWfh(Request $request): JsonResponse
    {
        // Check if user is super admin
        $user = auth()->user();
        if (!$user || !($user->hasRole('super-admin') || $user->role === 'super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Super Admin can add WFH records.',
            ], 403);
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check if WFH record already exists
        if (WfhRecord::existsForEmployeeOnDate($validated['employee_id'], Carbon::parse($validated['date']))) {
            return response()->json([
                'success' => false,
                'message' => 'WFH record already exists for this date.',
            ], 422);
        }

        // Create WFH record
        $wfhRecord = WfhRecord::create([
            'employee_id' => $validated['employee_id'],
            'date' => $validated['date'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'WFH record added successfully.',
            'wfh_record' => $wfhRecord,
        ]);
    }

    /**
     * Delete a WFH record for a specific date
     */
    public function destroyWfh(Request $request): JsonResponse
    {
        // Check if user is super admin
        $user = auth()->user();
        if (!$user || !($user->hasRole('super-admin') || $user->role === 'super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Super Admin can delete WFH records.',
            ], 403);
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
        ]);

        $deleted = WfhRecord::where('employee_id', $validated['employee_id'])
            ->whereDate('date', $validated['date'])
            ->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'No WFH record found to delete.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'WFH record deleted successfully.',
        ]);
    }

    /**
     * Store a Leave record for a specific date
     */
    public function storeLeave(Request $request): JsonResponse
    {
        // Check if user is super admin
        $user = auth()->user();
        if (!$user || !($user->hasRole('super-admin') || $user->role === 'super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Super Admin can add leave records.',
            ], 403);
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'leave_policy_id' => 'required|exists:leave_policies,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $date = Carbon::parse($validated['date']);

        // Check if leave record already exists for this date
        $existingLeave = LeaveRecord::where('employee_id', $validated['employee_id'])
            ->where('status', 'approved')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();

        if ($existingLeave) {
            return response()->json([
                'success' => false,
                'message' => 'Leave record already exists for this date.',
            ], 422);
        }

        // Create single-day leave record (auto-approved)
        $leaveRecord = LeaveRecord::create([
            'employee_id' => $validated['employee_id'],
            'leave_policy_id' => $validated['leave_policy_id'],
            'start_date' => $date,
            'end_date' => $date,
            'status' => 'approved',
            'notes' => $validated['notes'] ?? 'Added via attendance records',
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave record added successfully.',
            'leave_record' => $leaveRecord,
        ]);
    }

    /**
     * Delete a Leave record for a specific date
     */
    public function destroyLeave(Request $request): JsonResponse
    {
        // Check if user is super admin
        $user = auth()->user();
        if (!$user || !($user->hasRole('super-admin') || $user->role === 'super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Super Admin can delete leave records.',
            ], 403);
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
        ]);

        $date = Carbon::parse($validated['date']);

        // Find and delete single-day leave records for this date
        $deleted = LeaveRecord::where('employee_id', $validated['employee_id'])
            ->whereDate('start_date', $date)
            ->whereDate('end_date', $date)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'No single-day leave record found to delete for this date.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Leave record deleted successfully.',
        ]);
    }
}
