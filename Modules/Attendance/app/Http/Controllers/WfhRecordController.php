<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Attendance\Models\WfhRecord;
use Modules\Attendance\Models\AttendanceRule;
use Modules\HR\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * Work From Home Record Controller
 *
 * Handles CRUD operations for employee WFH records.
 * Validates WFH allowances against WFH Policy rules.
 *
 * @author Dev Agent
 */
class WfhRecordController extends Controller
{
    /**
     * Store newly created WFH records.
     *
     * @param Request $request
     * @param Employee $employee
     * @return JsonResponse
     */
    public function store(Request $request, Employee $employee): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'dates' => 'required|array|min:1',
                'dates.*' => 'required|date',
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dates = array_map(fn($date) => Carbon::parse($date), $request->dates);
            $createdRecords = [];
            $errors = [];

            foreach ($dates as $date) {
                // Check if WFH record already exists for this date
                if (WfhRecord::existsForEmployeeOnDate($employee->id, $date)) {
                    $errors[] = "WFH record already exists for {$date->format('Y-m-d')}";
                    continue;
                }

                // Validate WFH allowance for the month
                $monthlyAllowanceExceeded = $this->checkMonthlyWfhAllowance($employee, $date);
                if ($monthlyAllowanceExceeded) {
                    $errors[] = "Monthly WFH allowance exceeded for {$date->format('M Y')}";
                    continue;
                }

                // Create WFH record
                $wfhRecord = WfhRecord::create([
                    'employee_id' => $employee->id,
                    'date' => $date->format('Y-m-d'),
                    'notes' => $request->notes,
                    'created_by' => Auth::id(),
                ]);

                $wfhRecord->load(['employee', 'createdBy']);
                $createdRecords[] = $wfhRecord;
            }

            if (empty($createdRecords) && !empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No WFH records were created.',
                    'errors' => $errors
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => count($createdRecords) . ' WFH record(s) created successfully.',
                'data' => $createdRecords,
                'errors' => $errors
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating WFH record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified WFH record.
     *
     * @param Request $request
     * @param Employee $employee
     * @param WfhRecord $wfhRecord
     * @return JsonResponse
     */
    public function update(Request $request, Employee $employee, WfhRecord $wfhRecord): JsonResponse
    {
        try {
            // Ensure the WFH record belongs to the employee
            if ($wfhRecord->employee_id !== $employee->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'WFH record does not belong to this employee.'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $newDate = Carbon::parse($request->date);

            // Check if another WFH record exists for the new date (excluding current record)
            $existingRecord = WfhRecord::where('employee_id', $employee->id)
                ->where('date', $newDate->format('Y-m-d'))
                ->where('id', '!=', $wfhRecord->id)
                ->exists();

            if ($existingRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'WFH record already exists for this date.'
                ], 422);
            }

            $wfhRecord->update([
                'date' => $newDate->format('Y-m-d'),
                'notes' => $request->notes,
            ]);

            $wfhRecord->load(['employee', 'createdBy']);

            return response()->json([
                'success' => true,
                'message' => 'WFH record updated successfully.',
                'data' => $wfhRecord
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating WFH record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified WFH record.
     *
     * @param Employee $employee
     * @param WfhRecord $wfhRecord
     * @return JsonResponse
     */
    public function destroy(Employee $employee, WfhRecord $wfhRecord): JsonResponse
    {
        try {
            // Ensure the WFH record belongs to the employee
            if ($wfhRecord->employee_id !== $employee->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'WFH record does not belong to this employee.'
                ], 404);
            }

            $wfhRecord->delete();

            return response()->json([
                'success' => true,
                'message' => 'WFH record deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting WFH record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if adding a WFH day would exceed monthly allowance.
     *
     * @param Employee $employee
     * @param Carbon $date
     * @return bool
     */
    protected function checkMonthlyWfhAllowance(Employee $employee, Carbon $date): bool
    {
        // Get the WFH policy rule
        $wfhRule = AttendanceRule::where('rule_type', 'wfh_policy')->first();

        if (!$wfhRule || !isset($wfhRule->config['monthly_allowance_days'])) {
            return false; // No limit if no rule is set
        }

        $monthlyAllowance = $wfhRule->config['monthly_allowance_days'];

        // Count existing WFH days for this month
        $existingWfhDays = WfhRecord::where('employee_id', $employee->id)
            ->inMonth($date)
            ->count();

        return $existingWfhDays >= $monthlyAllowance;
    }
}
