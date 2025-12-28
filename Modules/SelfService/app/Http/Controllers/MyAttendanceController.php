<?php

namespace Modules\SelfService\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Attendance\Models\AttendanceLog;
use Modules\Attendance\Models\AttendanceRule;
use Modules\Attendance\Models\PermissionUsage;
use Modules\Attendance\Models\PublicHoliday;
use Modules\Attendance\Models\Setting;
use Modules\Attendance\Models\WfhRecord;
use Modules\Leave\Models\LeaveRecord;
use Modules\Payroll\Models\JiraWorklog;
use Carbon\Carbon;

class MyAttendanceController extends Controller
{
    /**
     * Display the employee's attendance records (read-only).
     */
    public function index(Request $request)
    {
        $employee = $request->attributes->get('employee');
        $employeeId = $employee->id;

        // Get filter parameters
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);
        $filterType = $request->input('filter_type', 'month');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Calculate period dates
        if ($filterType === 'range' && $dateFrom && $dateTo) {
            $periodStartDate = Carbon::parse($dateFrom)->startOfDay();
            $periodEndDate = Carbon::parse($dateTo)->endOfDay();
        } else {
            // Payroll period: 26th of previous month to 25th of selected month
            $periodEndDate = Carbon::create($year, $month, 25)->endOfDay();
            $periodStartDate = Carbon::create($year, $month, 1)->subMonth()->setDay(26)->startOfDay();
        }

        // Get attendance logs for this employee
        $allRecords = AttendanceLog::where('employee_id', $employeeId)
            ->whereBetween('timestamp', [$periodStartDate, $periodEndDate])
            ->orderBy('timestamp', 'asc')
            ->get();

        // Group punches by date
        $punchesByDate = [];
        foreach ($allRecords as $record) {
            $date = $record->timestamp->format('Y-m-d');
            if (!isset($punchesByDate[$date])) {
                $punchesByDate[$date] = [];
            }
            $punchesByDate[$date][] = $record->timestamp;
        }

        // Process punches to determine time_in and time_out
        $cutoffTime = '13:00';
        $dailyRecords = [];

        foreach ($punchesByDate as $date => $punches) {
            $punchCount = count($punches);
            $record = [
                'employee' => $employee,
                'date' => $date,
                'time_in' => null,
                'time_out' => null,
                'total_minutes' => 0,
            ];

            if ($punchCount >= 2) {
                $record['time_in'] = $punches[0];
                $record['time_out'] = $punches[$punchCount - 1];
            } elseif ($punchCount === 1) {
                $punch = $punches[0];
                $cutoffDateTime = Carbon::parse($date . ' ' . $cutoffTime);

                if ($punch->lt($cutoffDateTime)) {
                    $record['time_in'] = $punch;
                } else {
                    $record['time_out'] = $punch;
                }
            }

            $dailyRecords[$date] = $record;
        }

        // Get rules for calculations
        $flexibleHoursRule = AttendanceRule::getFlexibleHoursRule();
        $flexibleEndTime = $flexibleHoursRule?->config['to'] ?? '10:00';

        $permissionRule = AttendanceRule::getPermissionRule();
        $minutesPerPermission = $permissionRule?->config['minutes_per_permission'] ?? 120;

        // Get permission usages for this employee in the period
        $dates = array_keys($dailyRecords);
        $permissionUsages = PermissionUsage::where('employee_id', $employeeId)
            ->whereIn('date', $dates)
            ->get()
            ->keyBy(fn($p) => $p->date->format('Y-m-d'));

        // Calculate totals and late penalties
        foreach ($dailyRecords as $date => &$record) {
            if ($record['time_in'] && $record['time_out']) {
                $record['total_minutes'] = $record['time_in']->diffInMinutes($record['time_out']);
            }

            $permissionUsage = $permissionUsages->get($date);
            $record['permission_usage'] = $permissionUsage;
            $record['has_permission'] = $permissionUsage !== null;

            $record['late_minutes'] = 0;
            $record['late_penalty'] = 0;

            if ($record['time_in']) {
                $flexibleDeadline = Carbon::parse($date . ' ' . $flexibleEndTime);

                if ($record['has_permission']) {
                    $flexibleDeadline->addMinutes($permissionUsage->minutes_used);
                }

                if ($record['time_in']->gt($flexibleDeadline)) {
                    $record['late_minutes'] = (int) $flexibleDeadline->diffInMinutes($record['time_in']);
                    $record['late_penalty'] = AttendanceRule::calculateLatePenalty($record['late_minutes']);
                }
            }
        }
        unset($record);

        // Get settings for weekends
        $weekendDays = Setting::get('weekend_days', ['friday', 'saturday']);
        $weekendDayNumbers = array_map(function ($day) {
            return match (strtolower($day)) {
                'sunday' => Carbon::SUNDAY,
                'monday' => Carbon::MONDAY,
                'tuesday' => Carbon::TUESDAY,
                'wednesday' => Carbon::WEDNESDAY,
                'thursday' => Carbon::THURSDAY,
                'friday' => Carbon::FRIDAY,
                'saturday' => Carbon::SATURDAY,
                default => null,
            };
        }, $weekendDays);
        $weekendDayNumbers = array_filter($weekendDayNumbers, fn($d) => $d !== null);

        // Get public holidays
        $publicHolidays = PublicHoliday::whereBetween('date', [$periodStartDate, $periodEndDate])
            ->get()
            ->keyBy(fn($h) => $h->date->format('Y-m-d'));

        // Get leave records
        $leaveRecords = LeaveRecord::where('employee_id', $employeeId)
            ->approved()
            ->inDateRange($periodStartDate, $periodEndDate)
            ->get();

        $leaveRecordsByDate = [];
        foreach ($leaveRecords as $leave) {
            $leaveStart = $leave->start_date->max($periodStartDate);
            $leaveEnd = $leave->end_date->min($periodEndDate);
            $leaveDate = $leaveStart->copy();
            while ($leaveDate->lte($leaveEnd)) {
                $leaveRecordsByDate[$leaveDate->format('Y-m-d')] = $leave;
                $leaveDate->addDay();
            }
        }

        // Get WFH records
        $wfhRecords = WfhRecord::where('employee_id', $employeeId)
            ->whereBetween('date', [$periodStartDate, $periodEndDate])
            ->get()
            ->keyBy(fn($w) => $w->date->format('Y-m-d'));

        // Get Jira worklogs for this employee
        $worklogs = JiraWorklog::where('employee_id', $employeeId)
            ->whereBetween('worklog_started', [$periodStartDate, $periodEndDate])
            ->orderBy('worklog_started')
            ->get();

        // Group worklogs by date
        $worklogsByDate = [];
        $totalWorklogHours = 0;
        foreach ($worklogs as $worklog) {
            $dateStr = $worklog->worklog_started->format('Y-m-d');
            if (!isset($worklogsByDate[$dateStr])) {
                $worklogsByDate[$dateStr] = [
                    'hours' => 0,
                    'entries' => [],
                ];
            }
            $worklogsByDate[$dateStr]['hours'] += $worklog->time_spent_hours;
            $worklogsByDate[$dateStr]['entries'][] = $worklog;
            $totalWorklogHours += $worklog->time_spent_hours;
        }

        // Generate all dates in period
        $currentDate = $periodStartDate->copy();
        while ($currentDate->lte($periodEndDate)) {
            $dateStr = $currentDate->format('Y-m-d');

            if (!isset($dailyRecords[$dateStr])) {
                $dailyRecords[$dateStr] = [
                    'employee' => $employee,
                    'date' => $dateStr,
                    'time_in' => null,
                    'time_out' => null,
                    'total_minutes' => 0,
                    'permission_usage' => null,
                    'has_permission' => false,
                    'late_minutes' => 0,
                    'late_penalty' => 0,
                ];
            }

            $isWeekend = in_array($currentDate->dayOfWeek, $weekendDayNumbers);
            $isHoliday = isset($publicHolidays[$dateStr]);
            $isOnLeave = isset($leaveRecordsByDate[$dateStr]);
            $isWfh = isset($wfhRecords[$dateStr]);

            $dailyRecords[$dateStr]['is_weekend'] = $isWeekend;
            $dailyRecords[$dateStr]['is_holiday'] = $isHoliday;
            $dailyRecords[$dateStr]['holiday_name'] = $isHoliday ? $publicHolidays[$dateStr]->name : null;
            $dailyRecords[$dateStr]['is_on_leave'] = $isOnLeave;
            $dailyRecords[$dateStr]['leave_type'] = $isOnLeave ? ($leaveRecordsByDate[$dateStr]->leavePolicy->name ?? 'Leave') : null;
            $dailyRecords[$dateStr]['is_wfh'] = $isWfh;
            $dailyRecords[$dateStr]['is_missing'] = !$isWeekend && !$isHoliday && !$isOnLeave && !$isWfh &&
                !$dailyRecords[$dateStr]['time_in'] && !$dailyRecords[$dateStr]['time_out'];

            // Add worklog data
            $dailyRecords[$dateStr]['worklog_hours'] = $worklogsByDate[$dateStr]['hours'] ?? 0;
            $dailyRecords[$dateStr]['worklog_entries'] = $worklogsByDate[$dateStr]['entries'] ?? [];

            $currentDate->addDay();
        }

        // Sort by date ascending
        ksort($dailyRecords);
        $dailyRecords = array_values($dailyRecords);

        // Calculate summary
        $workHoursPerDay = (float) Setting::get('work_hours_per_day', 8);
        $workDays = 0;
        $vacationDays = 0;

        $currentDate = $periodStartDate->copy();
        while ($currentDate->lte($periodEndDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            $isWeekend = in_array($currentDate->dayOfWeek, $weekendDayNumbers);
            $isHoliday = isset($publicHolidays[$dateStr]);

            if (!$isWeekend && !$isHoliday) {
                $workDays++;
                if (isset($leaveRecordsByDate[$dateStr])) {
                    $vacationDays++;
                }
            }
            $currentDate->addDay();
        }

        $totalWorkMinutes = array_sum(array_column($dailyRecords, 'total_minutes'));
        $totalLatePenaltyMinutes = array_sum(array_column($dailyRecords, 'late_penalty'));

        // Count WFH days (only work days, excluding weekends and holidays)
        $wfhDays = 0;
        foreach ($wfhRecords as $dateStr => $wfh) {
            $wfhDate = Carbon::parse($dateStr);
            $isWeekend = in_array($wfhDate->dayOfWeek, $weekendDayNumbers);
            $isHoliday = isset($publicHolidays[$dateStr]);
            if (!$isWeekend && !$isHoliday) {
                $wfhDays++;
            }
        }

        $expectedWorkHours = $workDays * $workHoursPerDay;
        $vacationHours = $vacationDays * $workHoursPerDay;
        $wfhHours = $wfhDays * $workHoursPerDay;
        $totalWorkHours = ($totalWorkMinutes / 60) - ($totalLatePenaltyMinutes / 60) + $vacationHours + $wfhHours;
        $percentage = $expectedWorkHours > 0 ? ($totalWorkHours / $expectedWorkHours) * 100 : 0;

        $employeeSummary = [
            'work_days' => $workDays,
            'expected_work_hours' => $expectedWorkHours,
            'vacation_days' => $vacationDays,
            'vacation_hours' => $vacationHours,
            'wfh_days' => $wfhDays,
            'wfh_hours' => $wfhHours,
            'total_work_minutes' => $totalWorkMinutes,
            'total_late_penalty_minutes' => $totalLatePenaltyMinutes,
            'total_work_hours' => $totalWorkHours,
            'percentage' => $percentage,
            'work_hours_per_day' => $workHoursPerDay,
            'total_worklog_hours' => $totalWorklogHours,
        ];

        // Get years for dropdown (include next year if past 26th for payroll cycle)
        $earliestRecord = AttendanceLog::where('employee_id', $employeeId)
            ->orderBy('timestamp', 'asc')
            ->first();
        $startYear = $earliestRecord ? $earliestRecord->timestamp->year : Carbon::now()->year;
        $endYear = Carbon::now()->day >= 26 ? Carbon::now()->year + 1 : Carbon::now()->year;
        $years = range($startYear, $endYear);

        // Get late penalty rules for tooltip
        $latePenaltyRules = AttendanceRule::getLatePenaltyRules();

        return view('selfservice::attendance.index', compact(
            'employee',
            'dailyRecords',
            'employeeSummary',
            'years',
            'year',
            'month',
            'dateFrom',
            'dateTo',
            'filterType',
            'periodStartDate',
            'periodEndDate',
            'latePenaltyRules',
            'minutesPerPermission'
        ));
    }
}
