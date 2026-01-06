<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\HR\Models\Employee;
use Modules\Leave\Models\LeaveRecord;
use Modules\Attendance\Models\WfhRecord;
use Modules\Attendance\Models\PublicHoliday;
use Modules\Attendance\Models\AttendanceLog;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class TeamAvailabilityController extends Controller
{
    /**
     * Display the team availability calendar.
     */
    public function index(Request $request)
    {
        // Get month/year from request or default to current
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        // Create start and end dates for the month
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get all days in the month
        $days = [];
        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            $days[] = $date->copy();
        }

        // Get active employees
        $employees = Employee::active()
            ->orderBy('name')
            ->get();

        // Get all leave records for the month
        $leaveRecords = LeaveRecord::with('leavePolicy')
            ->whereIn('employee_id', $employees->pluck('id'))
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->get();

        // Get all WFH records for the month
        $wfhRecords = WfhRecord::whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        // Get public holidays for the month
        $publicHolidays = PublicHoliday::whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(fn($h) => $h->date->format('Y-m-d'));

        // Get attendance logs for the month (grouped by employee and date)
        $attendanceLogs = AttendanceLog::whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('timestamp', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->select('employee_id', DB::raw('DATE(timestamp) as attendance_date'))
            ->groupBy('employee_id', DB::raw('DATE(timestamp)'))
            ->get()
            ->groupBy('employee_id')
            ->map(fn($logs) => $logs->pluck('attendance_date')->toArray());

        // Build availability data per employee per day
        $availabilityData = [];

        foreach ($employees as $employee) {
            $employeeData = [
                'employee' => $employee,
                'days' => [],
            ];

            foreach ($days as $day) {
                $dayKey = $day->format('Y-m-d');
                $status = $this->getEmployeeDayStatus(
                    $employee->id,
                    $day,
                    $leaveRecords,
                    $wfhRecords,
                    $publicHolidays,
                    $attendanceLogs
                );
                $employeeData['days'][$dayKey] = $status;
            }

            $availabilityData[] = $employeeData;
        }

        // Generate month options for navigation
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = Carbon::create(null, $m, 1)->format('F');
        }

        // Generate year options
        $currentYear = now()->year;
        $years = range($currentYear - 1, $currentYear + 1);

        return view('attendance::team-availability.index', [
            'availabilityData' => $availabilityData,
            'days' => $days,
            'month' => $month,
            'year' => $year,
            'months' => $months,
            'years' => $years,
            'publicHolidays' => $publicHolidays,
            'startDate' => $startDate,
        ]);
    }

    /**
     * Get the status for an employee on a specific day.
     */
    private function getEmployeeDayStatus(
        int $employeeId,
        Carbon $day,
        $leaveRecords,
        $wfhRecords,
        $publicHolidays,
        $attendanceLogs
    ): array {
        $dayKey = $day->format('Y-m-d');

        // Check if weekend (Friday and Saturday for Egypt)
        if ($day->isFriday() || $day->isSaturday()) {
            return [
                'type' => 'weekend',
                'label' => 'Weekend',
                'class' => 'bg-light text-muted',
                'icon' => '',
            ];
        }

        // Check if public holiday
        if (isset($publicHolidays[$dayKey])) {
            return [
                'type' => 'holiday',
                'label' => $publicHolidays[$dayKey]->name,
                'class' => 'bg-dark text-white',
                'icon' => 'ti-calendar-event',
            ];
        }

        // Check for leave records
        $leave = $leaveRecords->first(function ($record) use ($employeeId, $day) {
            return $record->employee_id === $employeeId
                && $day->between($record->start_date, $record->end_date);
        });

        if ($leave) {
            $statusClass = match($leave->status) {
                'approved' => 'bg-success text-white',
                'pending' => 'bg-warning text-dark',
                'rejected' => 'bg-danger text-white',
                default => 'bg-secondary text-white',
            };

            $statusIcon = match($leave->status) {
                'approved' => 'ti-check',
                'pending' => 'ti-clock',
                'rejected' => 'ti-x',
                default => 'ti-question-mark',
            };

            $policyName = $leave->leavePolicy ? $leave->leavePolicy->name : 'Leave';

            return [
                'type' => 'leave',
                'status' => $leave->status,
                'label' => $policyName . ' (' . ucfirst($leave->status) . ')',
                'class' => $statusClass,
                'icon' => $statusIcon,
                'policy' => $policyName,
            ];
        }

        // Check for WFH
        $wfh = $wfhRecords->first(function ($record) use ($employeeId, $dayKey) {
            return $record->employee_id === $employeeId
                && $record->date->format('Y-m-d') === $dayKey;
        });

        if ($wfh) {
            return [
                'type' => 'wfh',
                'label' => 'WFH',
                'class' => 'bg-info text-white',
                'icon' => 'ti-home',
            ];
        }

        // For past days (including today), check attendance records
        if ($day->lte(now()->endOfDay())) {
            // Check if employee has attendance record for this day
            $employeeAttendance = $attendanceLogs->get($employeeId, []);
            $hasAttendance = in_array($dayKey, $employeeAttendance);

            if ($hasAttendance) {
                return [
                    'type' => 'present',
                    'label' => 'Present (Fingerprint)',
                    'class' => 'bg-label-success',
                    'icon' => 'ti-fingerprint',
                ];
            } else {
                return [
                    'type' => 'absent',
                    'label' => 'Absent (No Record)',
                    'class' => 'bg-label-danger',
                    'icon' => 'ti-x',
                ];
            }
        }

        // Future days - no status yet
        return [
            'type' => 'available',
            'label' => '',
            'class' => '',
            'icon' => '',
        ];
    }
}
