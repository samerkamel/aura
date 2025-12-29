<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Attendance\Models\AttendanceLog;
use Modules\Attendance\Models\AttendanceRule;
use Modules\Attendance\Models\PermissionUsage;
use Modules\Attendance\Models\PublicHoliday;
use Modules\Attendance\Models\Setting;
use Modules\Attendance\Models\WfhRecord;
use Modules\HR\Models\Employee;
use Modules\Leave\Models\LeaveRecord;
use Modules\Leave\Models\LeavePolicy;
use Modules\Payroll\Models\JiraWorklog;
use Modules\Settings\Models\CompanySetting;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return redirect()->route('attendance.records');
    }

    /**
     * Display attendance records with filtering.
     */
    public function records(Request $request)
    {
        // Get filter parameters
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);
        $employeeId = $request->input('employee_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $filterType = $request->input('filter_type', 'month'); // 'month' or 'range'

        // Build query
        $query = AttendanceLog::with('employee')
            ->whereHas('employee', function ($q) {
                $q->where('status', 'active');
            });

        // Apply date filters
        if ($filterType === 'range' && $dateFrom && $dateTo) {
            $query->whereBetween('timestamp', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay()
            ]);
            $periodStartDate = Carbon::parse($dateFrom);
            $periodEndDate = Carbon::parse($dateTo);
        } else {
            // Default to month filter - use company settings for payroll period
            $companySettings = CompanySetting::getSettings();
            $cycleDay = $companySettings->cycle_start_day ?? 1;
            $selectedDate = Carbon::create($year, $month, $cycleDay);
            $periodStartDate = $companySettings->getPeriodStartForDate($selectedDate);
            $periodEndDate = $companySettings->getPeriodEndForDate($selectedDate);
            $query->whereBetween('timestamp', [$periodStartDate, $periodEndDate]);
        }

        // Filter by employee
        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        // Get all records and group by employee and date
        $allRecords = $query->orderBy('timestamp', 'asc')->get();

        // First pass: collect all punches per employee per day
        $punchesByEmployeeDate = [];
        foreach ($allRecords as $record) {
            $date = $record->timestamp->format('Y-m-d');
            $key = $record->employee_id . '_' . $date;

            if (!isset($punchesByEmployeeDate[$key])) {
                $punchesByEmployeeDate[$key] = [
                    'employee' => $record->employee,
                    'date' => $date,
                    'punches' => [],
                ];
            }

            $punchesByEmployeeDate[$key]['punches'][] = $record->timestamp;
        }

        // Second pass: determine time_in and time_out based on punch count
        // Cutoff time for single punch: 1 PM (13:00)
        $cutoffTime = '13:00';

        $dailyRecords = [];
        foreach ($punchesByEmployeeDate as $key => $data) {
            $punches = $data['punches'];
            $punchCount = count($punches);

            $dailyRecords[$key] = [
                'employee' => $data['employee'],
                'date' => $data['date'],
                'time_in' => null,
                'time_out' => null,
                'total_minutes' => 0,
            ];

            if ($punchCount >= 2) {
                // Multiple punches: first is check-in, last is check-out
                $dailyRecords[$key]['time_in'] = $punches[0]; // First punch (already sorted by timestamp asc)
                $dailyRecords[$key]['time_out'] = $punches[$punchCount - 1]; // Last punch
            } elseif ($punchCount === 1) {
                // Single punch: use cutoff time to determine if sign-in or sign-out
                $punch = $punches[0];
                $cutoffDateTime = Carbon::parse($data['date'] . ' ' . $cutoffTime);

                if ($punch->lt($cutoffDateTime)) {
                    // Before 1 PM: treat as sign-in (missed sign-out)
                    $dailyRecords[$key]['time_in'] = $punch;
                } else {
                    // After 1 PM: treat as sign-out (missed sign-in)
                    $dailyRecords[$key]['time_out'] = $punch;
                }
            }
        }

        // Get flexible hours rule for late penalty calculation
        $flexibleHoursRule = AttendanceRule::getFlexibleHoursRule();
        $flexibleEndTime = $flexibleHoursRule?->config['to'] ?? '10:00';

        // Get permission rule for permission duration
        $permissionRule = AttendanceRule::getPermissionRule();
        $minutesPerPermission = $permissionRule?->config['minutes_per_permission'] ?? 120;

        // Collect all employee IDs and dates to fetch permission usages in bulk
        $employeeIds = array_unique(array_map(fn($r) => $r['employee']->id, $dailyRecords));
        $dates = array_unique(array_column($dailyRecords, 'date'));

        // Fetch all permission usages for the relevant dates and employees
        $permissionUsages = PermissionUsage::whereIn('employee_id', $employeeIds)
            ->whereIn('date', $dates)
            ->get()
            ->keyBy(fn($p) => $p->employee_id . '_' . $p->date->format('Y-m-d'));

        // Fetch employees with billable hours applicable
        $billableEmployeeIds = Employee::whereIn('id', $employeeIds)
            ->where('billable_hours_applicable', true)
            ->pluck('id')
            ->toArray();

        // Fetch Jira worklog hours grouped by employee_id and date (only for billable employees)
        $jiraWorklogHours = [];
        if (!empty($billableEmployeeIds)) {
            $jiraWorklogs = JiraWorklog::whereIn('employee_id', $billableEmployeeIds)
                ->whereBetween('worklog_started', [$periodStartDate, $periodEndDate])
                ->selectRaw('employee_id, DATE(worklog_started) as worklog_date, SUM(time_spent_hours) as total_hours')
                ->groupBy('employee_id', 'worklog_date')
                ->get();

            foreach ($jiraWorklogs as $worklog) {
                $key = $worklog->employee_id . '_' . $worklog->worklog_date;
                $jiraWorklogHours[$key] = (float) $worklog->total_hours;
            }
        }

        // Calculate total time and late penalty for each daily record
        foreach ($dailyRecords as &$record) {
            if ($record['time_in'] && $record['time_out']) {
                $record['total_minutes'] = $record['time_in']->diffInMinutes($record['time_out']);
            }

            // Check if there's a permission used for this date
            $permissionKey = $record['employee']->id . '_' . $record['date'];
            $permissionUsage = $permissionUsages->get($permissionKey);
            $record['permission_usage'] = $permissionUsage;
            $record['has_permission'] = $permissionUsage !== null;
            $record['permission_minutes'] = $permissionUsage ? $permissionUsage->minutes_used : 0;

            // Add permission minutes to total (permission hours count as attendance)
            if ($record['has_permission']) {
                $record['total_minutes'] += $permissionUsage->minutes_used;
            }

            // Calculate late minutes (how late after flexible hours end)
            $record['late_minutes'] = 0;
            $record['late_penalty'] = 0;

            if ($record['time_in']) {
                $flexibleDeadline = Carbon::parse($record['date'] . ' ' . $flexibleEndTime);

                // If employee has permission, extend the deadline by permission minutes
                if ($record['has_permission']) {
                    $flexibleDeadline->addMinutes($permissionUsage->minutes_used);
                }

                if ($record['time_in']->gt($flexibleDeadline)) {
                    $record['late_minutes'] = (int) $flexibleDeadline->diffInMinutes($record['time_in']);
                    $record['late_penalty'] = AttendanceRule::calculateLatePenalty($record['late_minutes']);
                }
            }

            // Add billable hours from Jira worklogs (only for billable employees)
            $empId = $record['employee']->id;
            $isBillable = in_array($empId, $billableEmployeeIds);
            $record['is_billable_employee'] = $isBillable;
            $record['billable_hours'] = null;
            if ($isBillable) {
                $worklogKey = $empId . '_' . $record['date'];
                $record['billable_hours'] = $jiraWorklogHours[$worklogKey] ?? 0;
            }
        }
        // Important: unset reference to prevent issues
        unset($record);

        // Convert to indexed array for proper sorting
        $dailyRecords = array_values($dailyRecords);

        // Get settings for weekend days and public holidays
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

        // Get public holidays in the period
        $publicHolidays = PublicHoliday::whereBetween('date', [$periodStartDate, $periodEndDate])
            ->get()
            ->keyBy(fn($h) => $h->date->format('Y-m-d'));

        // Get leave records for the filtered employee (if any)
        $leaveRecordsByDate = [];
        $wfhRecordsByDate = [];
        if ($employeeId) {
            $leaveRecords = LeaveRecord::where('employee_id', $employeeId)
                ->approved()
                ->inDateRange($periodStartDate, $periodEndDate)
                ->get();

            foreach ($leaveRecords as $leave) {
                $leaveStart = $leave->start_date->max($periodStartDate);
                $leaveEnd = $leave->end_date->min($periodEndDate);
                $leaveDate = $leaveStart->copy();
                while ($leaveDate->lte($leaveEnd)) {
                    $leaveRecordsByDate[$leaveDate->format('Y-m-d')] = $leave;
                    $leaveDate->addDay();
                }
            }

            // Get WFH records for the filtered employee
            $wfhRecords = WfhRecord::where('employee_id', $employeeId)
                ->whereBetween('date', [$periodStartDate, $periodEndDate])
                ->get();

            foreach ($wfhRecords as $wfh) {
                $wfhRecordsByDate[$wfh->date->format('Y-m-d')] = $wfh;
            }
        }

        // If single employee is filtered, generate ALL dates in period
        if ($employeeId) {
            $employee = Employee::find($employeeId);
            $existingDates = array_column($dailyRecords, 'date');

            $currentDate = $periodStartDate->copy();
            while ($currentDate->lte($periodEndDate)) {
                $dateStr = $currentDate->format('Y-m-d');

                // Skip if we already have a record for this date
                if (!in_array($dateStr, $existingDates)) {
                    $isWeekend = in_array($currentDate->dayOfWeek, $weekendDayNumbers);
                    $isHoliday = isset($publicHolidays[$dateStr]);
                    $isOnLeave = isset($leaveRecordsByDate[$dateStr]);
                    $isWfh = isset($wfhRecordsByDate[$dateStr]);

                    // Check billable hours for this date
                    $isBillable = in_array($employee->id, $billableEmployeeIds);
                    $billableHours = null;
                    if ($isBillable) {
                        $worklogKey = $employee->id . '_' . $dateStr;
                        $billableHours = $jiraWorklogHours[$worklogKey] ?? 0;
                    }

                    $dailyRecords[] = [
                        'employee' => $employee,
                        'date' => $dateStr,
                        'time_in' => null,
                        'time_out' => null,
                        'total_minutes' => 0,
                        'permission_usage' => null,
                        'has_permission' => false,
                        'late_minutes' => 0,
                        'late_penalty' => 0,
                        'is_weekend' => $isWeekend,
                        'is_holiday' => $isHoliday,
                        'holiday_name' => $isHoliday ? $publicHolidays[$dateStr]->name : null,
                        'is_on_leave' => $isOnLeave,
                        'leave_type' => $isOnLeave ? ($leaveRecordsByDate[$dateStr]->leavePolicy->name ?? 'Leave') : null,
                        'is_wfh' => $isWfh,
                        'wfh_notes' => $isWfh ? ($wfhRecordsByDate[$dateStr]->notes ?? null) : null,
                        'is_missing' => !$isWeekend && !$isHoliday && !$isOnLeave && !$isWfh,
                        'is_billable_employee' => $isBillable,
                        'billable_hours' => $billableHours,
                    ];
                }
                $currentDate->addDay();
            }

            // Add day type info to existing records
            foreach ($dailyRecords as &$record) {
                if (!isset($record['is_weekend'])) {
                    $dateObj = Carbon::parse($record['date']);
                    $dateStr = $record['date'];
                    $isWeekend = in_array($dateObj->dayOfWeek, $weekendDayNumbers);
                    $isHoliday = isset($publicHolidays[$dateStr]);
                    $isOnLeave = isset($leaveRecordsByDate[$dateStr]);
                    $isWfh = isset($wfhRecordsByDate[$dateStr]);

                    $record['is_weekend'] = $isWeekend;
                    $record['is_holiday'] = $isHoliday;
                    $record['holiday_name'] = $isHoliday ? $publicHolidays[$dateStr]->name : null;
                    $record['is_on_leave'] = $isOnLeave;
                    $record['leave_type'] = $isOnLeave ? ($leaveRecordsByDate[$dateStr]->leavePolicy->name ?? 'Leave') : null;
                    $record['is_wfh'] = $isWfh;
                    $record['wfh_notes'] = $isWfh ? ($wfhRecordsByDate[$dateStr]->notes ?? null) : null;
                    $record['is_missing'] = false; // Has attendance, so not missing
                }
            }
            unset($record);
        } else {
            // For all employees view, add day type info
            foreach ($dailyRecords as &$record) {
                $dateObj = Carbon::parse($record['date']);
                $dateStr = $record['date'];
                $isWeekend = in_array($dateObj->dayOfWeek, $weekendDayNumbers);
                $isHoliday = isset($publicHolidays[$dateStr]);

                $record['is_weekend'] = $isWeekend;
                $record['is_holiday'] = $isHoliday;
                $record['holiday_name'] = $isHoliday ? $publicHolidays[$dateStr]->name : null;
                $record['is_on_leave'] = false;
                $record['leave_type'] = null;
                $record['is_missing'] = false;
            }
            unset($record);
        }

        // Sort by date ascending (oldest first), then by employee name ascending
        usort($dailyRecords, function ($a, $b) {
            // Primary sort: date ascending (oldest first)
            $dateA = strtotime($a['date']);
            $dateB = strtotime($b['date']);

            if ($dateA !== $dateB) {
                return $dateA - $dateB;
            }

            // Secondary sort: employee name ascending
            return strcmp($a['employee']->name ?? '', $b['employee']->name ?? '');
        });

        // Paginate the results manually
        $page = $request->input('page', 1);
        $perPage = 50;
        $total = count($dailyRecords);
        $records = collect(array_slice($dailyRecords, ($page - 1) * $perPage, $perPage));
        $pagination = new \Illuminate\Pagination\LengthAwarePaginator(
            $records,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Get employees for filter dropdown
        $employees = Employee::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'attendance_id']);

        // Calculate summary statistics
        $totalMinutes = array_sum(array_column($dailyRecords, 'total_minutes'));
        $summary = [
            'total_days' => count($dailyRecords),
            'total_hours' => floor($totalMinutes / 60),
            'total_minutes' => $totalMinutes % 60,
            'unique_employees' => count(array_unique(array_column($dailyRecords, 'employee_id'))),
        ];

        // Get unique employee IDs properly
        $uniqueEmployeeIds = [];
        foreach ($dailyRecords as $record) {
            $uniqueEmployeeIds[$record['employee']->id] = true;
        }
        $summary['unique_employees'] = count($uniqueEmployeeIds);

        // Get years for dropdown (from earliest record to current/next year based on payroll cycle)
        $earliestRecord = AttendanceLog::orderBy('timestamp', 'asc')->first();
        $startYear = $earliestRecord ? $earliestRecord->timestamp->year : Carbon::now()->year;
        // If we're past the 26th, include next year (for next month's payroll cycle)
        $endYear = Carbon::now()->day >= 26 ? Carbon::now()->year + 1 : Carbon::now()->year;
        $years = range($startYear, $endYear);

        // Get late penalty rules for display in tooltip
        $latePenaltyRules = AttendanceRule::getLatePenaltyRules();

        // Get leave policies for quick leave addition
        $leavePolicies = LeavePolicy::active()->orderBy('name')->get();

        // Calculate employee summary when single employee is filtered
        $employeeSummary = null;
        if ($employeeId) {
            // Determine date range (use the same period dates as the main query)
            $rangeStart = $periodStartDate->copy()->startOfDay();
            $rangeEnd = $periodEndDate->copy()->endOfDay();

            // Get settings
            $workHoursPerDay = (float) Setting::get('work_hours_per_day', 8);
            $weekendDays = Setting::get('weekend_days', ['friday', 'saturday']);

            // Map weekend day names to Carbon day numbers (0 = Sunday, 6 = Saturday)
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

            // Get public holidays in the date range
            $publicHolidays = PublicHoliday::whereBetween('date', [$rangeStart, $rangeEnd])
                ->pluck('date')
                ->map(fn($d) => $d->format('Y-m-d'))
                ->toArray();

            // Calculate work days (excluding weekends and public holidays)
            $workDays = 0;
            $currentDate = $rangeStart->copy();
            while ($currentDate->lte($rangeEnd)) {
                $isWeekend = in_array($currentDate->dayOfWeek, $weekendDayNumbers);
                $isHoliday = in_array($currentDate->format('Y-m-d'), $publicHolidays);

                if (!$isWeekend && !$isHoliday) {
                    $workDays++;
                }
                $currentDate->addDay();
            }

            // Get approved leave records for the employee in the date range
            $leaveRecords = LeaveRecord::where('employee_id', $employeeId)
                ->approved()
                ->inDateRange($rangeStart, $rangeEnd)
                ->get();

            // Calculate vacation days (only counting work days within leave periods)
            $vacationDays = 0;
            foreach ($leaveRecords as $leave) {
                $leaveStart = $leave->start_date->max($rangeStart);
                $leaveEnd = $leave->end_date->min($rangeEnd);

                $leaveDate = $leaveStart->copy();
                while ($leaveDate->lte($leaveEnd)) {
                    $isWeekend = in_array($leaveDate->dayOfWeek, $weekendDayNumbers);
                    $isHoliday = in_array($leaveDate->format('Y-m-d'), $publicHolidays);

                    if (!$isWeekend && !$isHoliday) {
                        $vacationDays++;
                    }
                    $leaveDate->addDay();
                }
            }

            // Calculate totals from daily records
            $totalWorkMinutes = array_sum(array_column($dailyRecords, 'total_minutes'));
            $totalLatePenaltyMinutes = array_sum(array_column($dailyRecords, 'late_penalty'));

            // Count WFH days (only work days, excluding weekends and holidays)
            $wfhDays = 0;
            foreach ($wfhRecordsByDate as $dateStr => $wfh) {
                $wfhDate = Carbon::parse($dateStr);
                $isWeekend = in_array($wfhDate->dayOfWeek, $weekendDayNumbers);
                $isHoliday = in_array($dateStr, array_keys($publicHolidays->toArray()));
                if (!$isWeekend && !$isHoliday) {
                    $wfhDays++;
                }
            }

            // Calculate summary values
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
            ];
        }

        return view('attendance::records.index', compact(
            'pagination',
            'employees',
            'summary',
            'years',
            'year',
            'month',
            'employeeId',
            'dateFrom',
            'dateTo',
            'filterType',
            'flexibleHoursRule',
            'latePenaltyRules',
            'permissionRule',
            'employeeSummary',
            'periodStartDate',
            'periodEndDate',
            'leavePolicies'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('attendance::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('attendance::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('attendance::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}

    /**
     * Display attendance summary for all employees by month.
     */
    public function summary(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $selectedEmployeeIds = $request->input('employee_ids', []);

        // Get years for dropdown
        $earliestRecord = AttendanceLog::orderBy('timestamp', 'asc')->first();
        $startYear = $earliestRecord ? $earliestRecord->timestamp->year : Carbon::now()->year;
        $years = range($startYear, Carbon::now()->year + 1);

        // Get all employees (active + those who worked during the year) for the filter dropdown
        $yearStart = Carbon::create($year - 1, 12, 26)->startOfDay();
        $allEmployees = Employee::where(function ($query) use ($yearStart) {
                $query->where('status', 'active')
                    ->orWhere(function ($q) use ($yearStart) {
                        $q->whereIn('status', ['terminated', 'resigned'])
                          ->where('termination_date', '>=', $yearStart);
                    });
            })
            ->orderBy('name')
            ->get();

        // Get filtered employees for summary data
        $employeesQuery = Employee::where(function ($query) use ($yearStart) {
                $query->where('status', 'active')
                    ->orWhere(function ($q) use ($yearStart) {
                        $q->whereIn('status', ['terminated', 'resigned'])
                          ->where('termination_date', '>=', $yearStart);
                    });
            })
            ->orderBy('name');

        if (!empty($selectedEmployeeIds)) {
            $employeesQuery->whereIn('id', $selectedEmployeeIds);
        }

        $employees = $employeesQuery->get();

        // Get settings
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

        // Get work hours per day setting
        $workHoursPerDay = (float) Setting::get('work_hours_per_day', 8);

        // Get flexible hours rule for late penalty calculation
        $flexibleHoursRule = AttendanceRule::getFlexibleHoursRule();
        $flexibleEndTime = $flexibleHoursRule?->config['to'] ?? '10:00';

        // Get permission rule
        $permissionRule = AttendanceRule::getPermissionRule();
        $minutesPerPermission = $permissionRule?->config['minutes_per_permission'] ?? 120;

        // Prepare summary data for each employee and month
        $summaryData = [];

        foreach ($employees as $employee) {
            $employeeData = [
                'employee' => $employee,
                'months' => [],
            ];

            $companySettings = CompanySetting::getSettings();
            for ($month = 1; $month <= 12; $month++) {
                // Calculate payroll period for this month using company settings
                $cycleDay = $companySettings->cycle_start_day ?? 1;
                $selectedDate = Carbon::create($year, $month, $cycleDay);
                $periodStartDate = $companySettings->getPeriodStartForDate($selectedDate);
                $periodEndDate = $companySettings->getPeriodEndForDate($selectedDate);

                // Check if this period is entirely in the future
                $today = Carbon::today();
                if ($periodStartDate->gt($today)) {
                    // Future month - all zeros
                    $employeeData['months'][$month] = [
                        'attendance_hours' => 0,
                        'late_penalty_hours' => 0,
                        'absent_days' => 0,
                        'vacation_days' => 0,
                        'permissions' => 0,
                        'wfh_days' => 0,
                        'work_days' => 0,
                        'attended_days' => 0,
                    ];
                    continue;
                }

                // Calculate employee-specific effective dates for this period
                // Start: later of period start or employee's start date
                $employeePeriodStart = $periodStartDate->copy();
                if ($employee->start_date && $employee->start_date->gt($periodStartDate)) {
                    $employeePeriodStart = $employee->start_date->copy();
                }

                // End: earlier of period end, today, or employee's termination date
                $employeePeriodEnd = $periodEndDate->copy()->min($today);
                if ($employee->termination_date && $employee->termination_date->lt($employeePeriodEnd)) {
                    $employeePeriodEnd = $employee->termination_date->copy();
                }

                // If employee wasn't employed during this period, show zeros
                if ($employeePeriodStart->gt($employeePeriodEnd)) {
                    $employeeData['months'][$month] = [
                        'attendance_hours' => 0,
                        'late_penalty_hours' => 0,
                        'absent_days' => 0,
                        'vacation_days' => 0,
                        'permissions' => 0,
                        'wfh_days' => 0,
                        'work_days' => 0,
                        'attended_days' => 0,
                    ];
                    continue;
                }

                // Get attendance logs for this employee in their effective period
                $attendanceLogs = AttendanceLog::where('employee_id', $employee->id)
                    ->whereBetween('timestamp', [$employeePeriodStart, $employeePeriodEnd->endOfDay()])
                    ->orderBy('timestamp', 'asc')
                    ->get();

                // Process attendance logs to calculate daily records
                $punchesByDate = [];
                foreach ($attendanceLogs as $log) {
                    $date = $log->timestamp->format('Y-m-d');
                    if (!isset($punchesByDate[$date])) {
                        $punchesByDate[$date] = [];
                    }
                    $punchesByDate[$date][] = $log->timestamp;
                }

                // Calculate hours and late penalties
                $totalMinutes = 0;
                $totalLatePenaltyMinutes = 0;
                $cutoffTime = '13:00';

                // Get permission usages for this employee's effective period
                $permissionUsages = PermissionUsage::where('employee_id', $employee->id)
                    ->whereBetween('date', [$employeePeriodStart, $employeePeriodEnd])
                    ->get()
                    ->keyBy(fn($p) => $p->date->format('Y-m-d'));

                foreach ($punchesByDate as $date => $punches) {
                    $punchCount = count($punches);
                    $timeIn = null;
                    $timeOut = null;

                    if ($punchCount >= 2) {
                        $timeIn = $punches[0];
                        $timeOut = $punches[$punchCount - 1];
                    } elseif ($punchCount === 1) {
                        $cutoffDateTime = Carbon::parse($date . ' ' . $cutoffTime);
                        if ($punches[0]->lt($cutoffDateTime)) {
                            $timeIn = $punches[0];
                        } else {
                            $timeOut = $punches[0];
                        }
                    }

                    // Calculate work minutes
                    if ($timeIn && $timeOut) {
                        $totalMinutes += $timeIn->diffInMinutes($timeOut);
                    }

                    // Calculate late penalty
                    if ($timeIn) {
                        $flexibleDeadline = Carbon::parse($date . ' ' . $flexibleEndTime);

                        // Check for permission
                        if (isset($permissionUsages[$date])) {
                            $flexibleDeadline->addMinutes($permissionUsages[$date]->minutes_used);
                        }

                        if ($timeIn->gt($flexibleDeadline)) {
                            $lateMinutes = (int) $flexibleDeadline->diffInMinutes($timeIn);
                            $totalLatePenaltyMinutes += AttendanceRule::calculateLatePenalty($lateMinutes);
                        }
                    }
                }

                // Add permission minutes to total attendance (permission hours count as attendance)
                foreach ($permissionUsages as $permissionUsage) {
                    $totalMinutes += $permissionUsage->minutes_used;
                }

                // Get public holidays in period
                $publicHolidays = PublicHoliday::whereBetween('date', [$periodStartDate, $periodEndDate])
                    ->pluck('date')
                    ->map(fn($d) => $d->format('Y-m-d'))
                    ->toArray();

                // Calculate work days in employee's effective period
                $workDays = 0;
                $currentDate = $employeePeriodStart->copy();
                while ($currentDate->lte($employeePeriodEnd)) {
                    $isWeekend = in_array($currentDate->dayOfWeek, $weekendDayNumbers);
                    $isHoliday = in_array($currentDate->format('Y-m-d'), $publicHolidays);
                    if (!$isWeekend && !$isHoliday) {
                        $workDays++;
                    }
                    $currentDate->addDay();
                }

                // Count days with attendance
                $attendedDays = count($punchesByDate);

                // Get leave records (vacation days) for employee's effective period
                $leaveRecords = LeaveRecord::where('employee_id', $employee->id)
                    ->approved()
                    ->inDateRange($employeePeriodStart, $employeePeriodEnd)
                    ->get();

                $vacationDays = 0;
                foreach ($leaveRecords as $leave) {
                    $leaveStart = $leave->start_date->max($employeePeriodStart);
                    $leaveEnd = $leave->end_date->min($employeePeriodEnd);
                    $leaveDate = $leaveStart->copy();
                    while ($leaveDate->lte($leaveEnd)) {
                        $isWeekend = in_array($leaveDate->dayOfWeek, $weekendDayNumbers);
                        $isHoliday = in_array($leaveDate->format('Y-m-d'), $publicHolidays);
                        if (!$isWeekend && !$isHoliday) {
                            $vacationDays++;
                        }
                        $leaveDate->addDay();
                    }
                }

                // Get WFH days for employee's effective period
                $wfhDays = WfhRecord::where('employee_id', $employee->id)
                    ->whereBetween('date', [$employeePeriodStart, $employeePeriodEnd])
                    ->count();

                // Count permissions used
                $permissionsUsed = $permissionUsages->count();

                // Calculate absent days (work days - attended days - vacation days - WFH days)
                $absentDays = max(0, $workDays - $attendedDays - $vacationDays - $wfhDays);

                // Calculate WFH hours (WFH counts as full work day)
                $wfhHours = $wfhDays * $workHoursPerDay;

                $employeeData['months'][$month] = [
                    'attendance_hours' => round(($totalMinutes / 60) + $wfhHours, 1),
                    'late_penalty_hours' => round($totalLatePenaltyMinutes / 60, 1),
                    'absent_days' => $absentDays,
                    'vacation_days' => $vacationDays,
                    'permissions' => $permissionsUsed,
                    'wfh_days' => $wfhDays,
                    'wfh_hours' => round($wfhHours, 1),
                    'work_days' => $workDays,
                    'attended_days' => $attendedDays,
                ];
            }

            $summaryData[] = $employeeData;
        }

        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
        ];

        // Calculate yearly statistics for the selected/all employees
        // Note: $workHoursPerDay is already defined earlier

        // Calculate total working days in the year (26/12 previous year to 25/12 this year)
        $yearStart = Carbon::create($year - 1, 12, 26)->startOfDay();
        $yearEnd = Carbon::create($year, 12, 25)->endOfDay();
        $today = Carbon::today();
        $effectiveYearEnd = $yearEnd->copy()->min($today);

        // Get public holidays for the entire year
        $yearlyPublicHolidays = PublicHoliday::whereBetween('date', [$yearStart, $yearEnd])
            ->pluck('date')
            ->map(fn($d) => $d->format('Y-m-d'))
            ->toArray();

        // Calculate total working days in the year
        $totalYearWorkDays = 0;
        $currentDate = $yearStart->copy();
        while ($currentDate->lte($effectiveYearEnd)) {
            $isWeekend = in_array($currentDate->dayOfWeek, $weekendDayNumbers);
            $isHoliday = in_array($currentDate->format('Y-m-d'), $yearlyPublicHolidays);
            if (!$isWeekend && !$isHoliday) {
                $totalYearWorkDays++;
            }
            $currentDate->addDay();
        }

        // Calculate total expected hours for the year
        $totalExpectedHours = $totalYearWorkDays * $workHoursPerDay;

        // Calculate aggregates from selected employees' summary data
        $employeeCount = count($summaryData);
        $yearlyStats = [
            'total_work_days' => $totalYearWorkDays,
            'total_expected_hours' => $totalExpectedHours,
            'work_hours_per_day' => $workHoursPerDay,
            'employee_count' => $employeeCount,
            // Totals across all selected employees
            'total_attended' => 0,
            'total_hours' => 0,
            'total_penalty' => 0,
            'total_absent' => 0,
            'total_vacation' => 0,
            'total_permissions' => 0,
            'total_wfh' => 0,
        ];

        // Sum up all employee data
        foreach ($summaryData as $data) {
            foreach ($data['months'] as $monthData) {
                $yearlyStats['total_attended'] += $monthData['attended_days'];
                $yearlyStats['total_hours'] += $monthData['attendance_hours'];
                $yearlyStats['total_penalty'] += $monthData['late_penalty_hours'];
                $yearlyStats['total_absent'] += $monthData['absent_days'];
                $yearlyStats['total_vacation'] += $monthData['vacation_days'];
                $yearlyStats['total_permissions'] += $monthData['permissions'];
                $yearlyStats['total_wfh'] += $monthData['wfh_days'];
            }
        }

        // Calculate averages per employee
        if ($employeeCount > 0) {
            $yearlyStats['avg_attended'] = round($yearlyStats['total_attended'] / $employeeCount, 1);
            $yearlyStats['avg_hours'] = round($yearlyStats['total_hours'] / $employeeCount, 1);
            $yearlyStats['avg_penalty'] = round($yearlyStats['total_penalty'] / $employeeCount, 1);
            $yearlyStats['avg_absent'] = round($yearlyStats['total_absent'] / $employeeCount, 1);
            $yearlyStats['avg_vacation'] = round($yearlyStats['total_vacation'] / $employeeCount, 1);
            $yearlyStats['avg_permissions'] = round($yearlyStats['total_permissions'] / $employeeCount, 1);
            $yearlyStats['avg_wfh'] = round($yearlyStats['total_wfh'] / $employeeCount, 1);

            // Calculate percentages
            $yearlyStats['attended_percentage'] = $totalYearWorkDays > 0
                ? round(($yearlyStats['avg_attended'] / $totalYearWorkDays) * 100, 1)
                : 0;
            $yearlyStats['hours_percentage'] = $totalExpectedHours > 0
                ? round(($yearlyStats['avg_hours'] / $totalExpectedHours) * 100, 1)
                : 0;

            // Monthly averages (per employee per month)
            $yearlyStats['avg_penalty_per_month'] = round($yearlyStats['avg_penalty'] / 12, 1);
            $yearlyStats['avg_absent_per_month'] = round($yearlyStats['avg_absent'] / 12, 1);
            $yearlyStats['avg_vacation_per_month'] = round($yearlyStats['avg_vacation'] / 12, 1);
            $yearlyStats['avg_permissions_per_month'] = round($yearlyStats['avg_permissions'] / 12, 1);
            $yearlyStats['avg_wfh_per_month'] = round($yearlyStats['avg_wfh'] / 12, 1);
        } else {
            $yearlyStats['avg_attended'] = 0;
            $yearlyStats['avg_hours'] = 0;
            $yearlyStats['avg_penalty'] = 0;
            $yearlyStats['avg_absent'] = 0;
            $yearlyStats['avg_vacation'] = 0;
            $yearlyStats['avg_permissions'] = 0;
            $yearlyStats['avg_wfh'] = 0;
            $yearlyStats['attended_percentage'] = 0;
            $yearlyStats['hours_percentage'] = 0;
            $yearlyStats['avg_penalty_per_month'] = 0;
            $yearlyStats['avg_absent_per_month'] = 0;
            $yearlyStats['avg_vacation_per_month'] = 0;
            $yearlyStats['avg_permissions_per_month'] = 0;
            $yearlyStats['avg_wfh_per_month'] = 0;
        }

        return view('attendance::summary.index', compact(
            'summaryData',
            'years',
            'year',
            'monthNames',
            'allEmployees',
            'selectedEmployeeIds',
            'yearlyStats'
        ));
    }
}
