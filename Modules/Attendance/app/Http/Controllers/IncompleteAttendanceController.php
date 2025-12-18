<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Attendance\Models\AttendanceLog;
use Modules\Attendance\Models\PublicHoliday;
use Modules\Attendance\Models\WfhRecord;
use Modules\Attendance\Models\Setting;
use Modules\HR\Models\Employee;
use Modules\Leave\Models\LeaveRecord;
use Modules\Leave\Models\LeavePolicy;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * IncompleteAttendanceController
 *
 * Handles identification of employees with incomplete attendance
 * (missing check-in or check-out on working days)
 */
class IncompleteAttendanceController extends Controller
{
    /**
     * Display employees with incomplete attendance
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        // Get month/year filter (default to current month)
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $selectedEmployeeIds = $request->get('employees', []);

        // Build date range for the selected month
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth();
        $today = Carbon::today();

        // Don't go beyond today
        if ($endDate->gt($today)) {
            $endDate = $today->copy();
        }

        // Get weekend days from settings
        $weekendDays = Setting::get('weekend_days', ['Friday', 'Saturday']);
        $weekendDayNumbers = $this->convertDaysToNumbers($weekendDays);

        // Get public holidays for the month
        $publicHolidays = PublicHoliday::whereBetween('date', [$startDate, $endDate])
            ->pluck('date')
            ->map(fn($d) => $d->format('Y-m-d'))
            ->toArray();

        // Get all employees who were active during the selected period for the filter dropdown
        // Include active employees OR those who terminated during/after the selected month
        $allEmployees = Employee::where(function ($query) use ($startDate) {
                $query->where('status', 'active')
                    ->orWhere(function ($q) use ($startDate) {
                        $q->whereIn('status', ['terminated', 'resigned'])
                          ->where('termination_date', '>=', $startDate);
                    });
            })
            ->orderBy('name')
            ->get();

        // Get employees to check (filtered or all)
        $employees = empty($selectedEmployeeIds)
            ? $allEmployees
            : Employee::where(function ($query) use ($startDate) {
                    $query->where('status', 'active')
                        ->orWhere(function ($q) use ($startDate) {
                            $q->whereIn('status', ['terminated', 'resigned'])
                              ->where('termination_date', '>=', $startDate);
                        });
                })
                ->whereIn('id', $selectedEmployeeIds)
                ->orderBy('name')
                ->get();

        // Get leave policies for the dropdown
        $leavePolicies = LeavePolicy::active()->get();

        // Find incomplete attendance for each employee
        $incompleteRecords = [];

        foreach ($employees as $employee) {
            // Calculate effective date range for this employee
            // Start: later of month start or employee's start date
            $employeeStartDate = $startDate->copy();
            if ($employee->start_date && $employee->start_date->gt($startDate)) {
                $employeeStartDate = $employee->start_date->copy();
            }

            // End: earlier of month end or employee's termination date
            $employeeEndDate = $endDate->copy();
            if ($employee->termination_date && $employee->termination_date->lt($endDate)) {
                $employeeEndDate = $employee->termination_date->copy();
            }

            // Skip if employee wasn't employed during this period
            if ($employeeStartDate->gt($employeeEndDate)) {
                continue;
            }

            // Get all attendance logs for this employee in the date range
            $attendanceLogs = AttendanceLog::where('employee_id', $employee->id)
                ->whereBetween('timestamp', [$employeeStartDate, $employeeEndDate->endOfDay()])
                ->orderBy('timestamp')
                ->get()
                ->groupBy(fn($log) => $log->timestamp->format('Y-m-d'));

            // Get WFH records for this employee
            $wfhDates = WfhRecord::where('employee_id', $employee->id)
                ->whereBetween('date', [$employeeStartDate, $employeeEndDate])
                ->pluck('date')
                ->map(fn($d) => $d->format('Y-m-d'))
                ->toArray();

            // Get approved leave records for this employee
            $leaveRecords = LeaveRecord::where('employee_id', $employee->id)
                ->approved()
                ->inDateRange($employeeStartDate, $employeeEndDate)
                ->get();

            // Build array of dates covered by leave
            $leaveDates = [];
            foreach ($leaveRecords as $leave) {
                $leaveStart = $leave->start_date->max($employeeStartDate);
                $leaveEnd = $leave->end_date->min($employeeEndDate);
                $current = $leaveStart->copy();
                while ($current->lte($leaveEnd)) {
                    $leaveDates[] = $current->format('Y-m-d');
                    $current->addDay();
                }
            }

            // Check each working day for this employee
            $currentDate = $employeeStartDate->copy();
            while ($currentDate->lte($employeeEndDate)) {
                $dateStr = $currentDate->format('Y-m-d');
                $isWeekend = in_array($currentDate->dayOfWeek, $weekendDayNumbers);
                $isHoliday = in_array($dateStr, $publicHolidays);
                $isWfh = in_array($dateStr, $wfhDates);
                $isOnLeave = in_array($dateStr, $leaveDates);

                // Skip if not a working day, WFH, or on leave
                if ($isWeekend || $isHoliday || $isWfh || $isOnLeave) {
                    $currentDate->addDay();
                    continue;
                }

                // Check attendance for this day
                $dayLogs = $attendanceLogs->get($dateStr, collect());
                $hasCheckIn = $dayLogs->where('type', 'sign_in')->isNotEmpty();
                $hasCheckOut = $dayLogs->where('type', 'sign_out')->isNotEmpty();

                // Determine issue type
                $issue = null;
                if (!$hasCheckIn && !$hasCheckOut) {
                    $issue = 'no_attendance';
                } elseif (!$hasCheckIn) {
                    $issue = 'no_check_in';
                } elseif (!$hasCheckOut) {
                    $issue = 'no_check_out';
                }

                if ($issue) {
                    $incompleteRecords[] = [
                        'employee' => $employee,
                        'date' => $currentDate->copy(),
                        'date_formatted' => $currentDate->format('M d, Y'),
                        'day_name' => $currentDate->format('l'),
                        'issue' => $issue,
                        'issue_label' => $this->getIssueLabel($issue),
                        'check_in' => $hasCheckIn ? $dayLogs->where('type', 'sign_in')->first()->timestamp->format('H:i') : null,
                        'check_out' => $hasCheckOut ? $dayLogs->where('type', 'sign_out')->last()->timestamp->format('H:i') : null,
                    ];
                }

                $currentDate->addDay();
            }
        }

        // Sort by date descending, then by employee name
        usort($incompleteRecords, function ($a, $b) {
            $dateCompare = $b['date']->timestamp - $a['date']->timestamp;
            if ($dateCompare !== 0) {
                return $dateCompare;
            }
            return strcmp($a['employee']->name, $b['employee']->name);
        });

        // Generate month/year options for filters
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = Carbon::create(null, $m, 1)->format('F');
        }

        $years = range(now()->year - 2, now()->year);

        return view('attendance::incomplete-attendance.index', compact(
            'incompleteRecords',
            'month',
            'year',
            'months',
            'years',
            'weekendDays',
            'leavePolicies',
            'allEmployees',
            'selectedEmployeeIds'
        ));
    }

    /**
     * Add leave record for an employee on a specific date
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function addLeave(Request $request): RedirectResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'leave_policy_id' => 'required|exists:leave_policies,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $date = Carbon::parse($request->date);
        $employee = Employee::findOrFail($request->employee_id);
        $leavePolicy = LeavePolicy::findOrFail($request->leave_policy_id);

        // Check if leave record already exists
        $existingLeave = LeaveRecord::where('employee_id', $employee->id)
            ->where('start_date', '<=', $date->format('Y-m-d'))
            ->where('end_date', '>=', $date->format('Y-m-d'))
            ->first();

        if ($existingLeave) {
            return back()->with('error', "A leave record already exists for {$employee->name} on this date.");
        }

        LeaveRecord::create([
            'employee_id' => $employee->id,
            'leave_policy_id' => $leavePolicy->id,
            'start_date' => $date->format('Y-m-d'),
            'end_date' => $date->format('Y-m-d'),
            'status' => LeaveRecord::STATUS_APPROVED,
            'notes' => $request->notes ?? "Added from incomplete attendance review",
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', "{$leavePolicy->name} added for {$employee->name} on {$date->format('M d, Y')}.");
    }

    /**
     * Add WFH record for an employee on a specific date
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function addWfh(Request $request): RedirectResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $date = Carbon::parse($request->date);
        $employee = Employee::findOrFail($request->employee_id);

        // Check if WFH record already exists
        if (WfhRecord::existsForEmployeeOnDate($employee->id, $date)) {
            return back()->with('error', "A WFH record already exists for {$employee->name} on this date.");
        }

        WfhRecord::create([
            'employee_id' => $employee->id,
            'date' => $date->format('Y-m-d'),
            'notes' => $request->notes ?? "Added from incomplete attendance review",
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', "WFH added for {$employee->name} on {$date->format('M d, Y')}.");
    }

    /**
     * Bulk action - add leave for multiple records
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function bulkAddLeave(Request $request): RedirectResponse
    {
        $request->validate([
            'records' => 'required|array',
            'records.*' => 'required|string',
            'leave_policy_id' => 'required|exists:leave_policies,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $leavePolicy = LeavePolicy::findOrFail($request->leave_policy_id);
        $created = 0;
        $skipped = 0;

        foreach ($request->records as $record) {
            list($employeeId, $dateStr) = explode('|', $record);
            $date = Carbon::parse($dateStr);
            $employee = Employee::find($employeeId);

            if (!$employee) {
                $skipped++;
                continue;
            }

            // Check if leave record already exists
            $existingLeave = LeaveRecord::where('employee_id', $employeeId)
                ->where('start_date', '<=', $date->format('Y-m-d'))
                ->where('end_date', '>=', $date->format('Y-m-d'))
                ->first();

            if ($existingLeave) {
                $skipped++;
                continue;
            }

            LeaveRecord::create([
                'employee_id' => $employeeId,
                'leave_policy_id' => $leavePolicy->id,
                'start_date' => $date->format('Y-m-d'),
                'end_date' => $date->format('Y-m-d'),
                'status' => LeaveRecord::STATUS_APPROVED,
                'notes' => $request->notes ?? "Bulk action from incomplete attendance review",
                'created_by' => auth()->id(),
            ]);
            $created++;
        }

        $message = "{$leavePolicy->name} records created for {$created} entries.";
        if ($skipped > 0) {
            $message .= " {$skipped} entries were skipped (already have leave records).";
        }

        return back()->with('success', $message);
    }

    /**
     * Convert day names to Carbon day numbers
     *
     * @param array $days
     * @return array
     */
    private function convertDaysToNumbers(array $days): array
    {
        $dayMap = [
            'Sunday' => 0,
            'Monday' => 1,
            'Tuesday' => 2,
            'Wednesday' => 3,
            'Thursday' => 4,
            'Friday' => 5,
            'Saturday' => 6,
        ];

        return array_map(fn($day) => $dayMap[$day] ?? null, $days);
    }

    /**
     * Get human-readable label for issue type
     *
     * @param string $issue
     * @return string
     */
    private function getIssueLabel(string $issue): string
    {
        return match ($issue) {
            'no_attendance' => 'No Attendance',
            'no_check_in' => 'Missing Check-In',
            'no_check_out' => 'Missing Check-Out',
            default => 'Unknown Issue',
        };
    }
}
