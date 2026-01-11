<?php

namespace Modules\Leave\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Leave\Models\LeaveRecord;
use Modules\Leave\Models\LeavePolicy;
use Modules\HR\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * Leave Record Controller
 *
 * Handles CRUD operations for employee leave records (PTO, Sick Leave).
 * Allows admins to log leave days for employees.
 *
 * @author Dev Agent
 */
class LeaveRecordController extends Controller
{
  /**
   * Store a newly created leave record.
   *
   * @param Request $request
   * @param Employee $employee
   * @return JsonResponse
   */
  public function store(Request $request, Employee $employee): JsonResponse
  {
    try {
      $validator = Validator::make($request->all(), [
        'leave_policy_id' => 'required|exists:leave_policies,id',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'notes' => 'nullable|string|max:500',
      ]);

      if ($validator->fails()) {
        return response()->json([
          'success' => false,
          'message' => 'Validation failed',
          'errors' => $validator->errors()
        ], 422);
      }

      // Check for overlapping leave records
      $overlappingLeave = LeaveRecord::where('employee_id', $employee->id)
        ->approved()
        ->where(function ($query) use ($request) {
          $query->whereBetween('start_date', [$request->start_date, $request->end_date])
            ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
            ->orWhere(function ($subQuery) use ($request) {
              $subQuery->where('start_date', '<=', $request->start_date)
                ->where('end_date', '>=', $request->end_date);
            });
        })
        ->exists();

      if ($overlappingLeave) {
        return response()->json([
          'success' => false,
          'message' => 'Employee already has approved leave during this period.'
        ], 422);
      }

      $leaveRecord = LeaveRecord::create([
        'employee_id' => $employee->id,
        'leave_policy_id' => $request->leave_policy_id,
        'start_date' => $request->start_date,
        'end_date' => $request->end_date,
        'notes' => $request->notes,
        'status' => LeaveRecord::STATUS_APPROVED, // Auto-approve admin-logged leave
        'created_by' => Auth::id(),
      ]);

      $leaveRecord->load(['leavePolicy', 'createdBy']);

      return response()->json([
        'success' => true,
        'message' => 'Leave record created successfully.',
        'data' => $leaveRecord
      ], 201);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error creating leave record: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Update the specified leave record.
   *
   * @param Request $request
   * @param Employee $employee
   * @param LeaveRecord $leaveRecord
   * @return JsonResponse
   */
  public function update(Request $request, Employee $employee, LeaveRecord $leaveRecord): JsonResponse
  {
    try {
      // Ensure the leave record belongs to the employee
      if ($leaveRecord->employee_id !== $employee->id) {
        return response()->json([
          'success' => false,
          'message' => 'Leave record does not belong to this employee.'
        ], 404);
      }

      $validator = Validator::make($request->all(), [
        'leave_policy_id' => 'required|exists:leave_policies,id',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'status' => 'required|in:pending,approved,rejected',
        'notes' => 'nullable|string|max:500',
      ]);

      if ($validator->fails()) {
        return response()->json([
          'success' => false,
          'message' => 'Validation failed',
          'errors' => $validator->errors()
        ], 422);
      }

      $leaveRecord->update($request->only([
        'leave_policy_id',
        'start_date',
        'end_date',
        'status',
        'notes'
      ]));

      $leaveRecord->load(['leavePolicy', 'createdBy']);

      return response()->json([
        'success' => true,
        'message' => 'Leave record updated successfully.',
        'data' => $leaveRecord
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error updating leave record: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Remove the specified leave record.
   *
   * @param Employee $employee
   * @param LeaveRecord $leaveRecord
   * @return JsonResponse
   */
  public function destroy(Employee $employee, LeaveRecord $leaveRecord): JsonResponse
  {
    try {
      // Ensure the leave record belongs to the employee
      if ($leaveRecord->employee_id !== $employee->id) {
        return response()->json([
          'success' => false,
          'message' => 'Leave record does not belong to this employee.'
        ], 404);
      }

      $leaveRecord->delete();

      return response()->json([
        'success' => true,
        'message' => 'Leave record deleted successfully.'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error deleting leave record: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Cancel an approved leave record (only if start date is in the future).
   *
   * @param Employee $employee
   * @param LeaveRecord $leaveRecord
   * @return JsonResponse
   */
  public function cancel(Employee $employee, LeaveRecord $leaveRecord): JsonResponse
  {
    try {
      // Ensure the leave record belongs to the employee
      if ($leaveRecord->employee_id !== $employee->id) {
        return response()->json([
          'success' => false,
          'message' => 'Leave record does not belong to this employee.'
        ], 404);
      }

      // Check if the leave can be cancelled
      if (!$leaveRecord->canBeCancelled()) {
        return response()->json([
          'success' => false,
          'message' => 'Only approved future leaves can be cancelled.'
        ], 422);
      }

      $leaveRecord->update([
        'status' => LeaveRecord::STATUS_CANCELLED
      ]);

      return response()->json([
        'success' => true,
        'message' => 'Leave request cancelled successfully.'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error cancelling leave record: ' . $e->getMessage()
      ], 500);
    }
  }
}
