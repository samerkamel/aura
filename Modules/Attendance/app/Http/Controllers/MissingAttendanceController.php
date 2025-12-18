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
use Carbon\Carbon;

/**
 * MissingAttendanceController
 *
 * Handles identification of working days with no attendance records
 * and provides bulk actions to mark them as holidays or WFH for all employees
 */
class MissingAttendanceController extends Controller
{
    /**
     * Display dates with no attendance from any employees
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        // Get month/year filter (default to current month)
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        // Build date range for the selected month
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth();

        // Get weekend days from settings
        $weekendDays = Setting::get('weekend_days', ['Friday', 'Saturday']);
        $weekendDayNumbers = $this->convertDaysToNumbers($weekendDays);

        // Get public holidays for the month
        $publicHolidays = PublicHoliday::whereBetween('date', [$startDate, $endDate])
            ->pluck('date')
            ->map(fn($d) => $d->format('Y-m-d'))
            ->toArray();

        // Get all dates with attendance logs
        $datesWithAttendance = AttendanceLog::whereBetween('timestamp', [$startDate, $endDate])
            ->selectRaw('DATE(timestamp) as date')
            ->distinct()
            ->pluck('date')
            ->toArray();

        // Find missing dates (working days with no attendance)
        $missingDates = [];
        $currentDate = $startDate->copy();
        $today = Carbon::today();

        while ($currentDate->lte($endDate)) {
            // Skip future dates
            if ($currentDate->gt($today)) {
                $currentDate->addDay();
                continue;
            }

            $dateStr = $currentDate->format('Y-m-d');
            $isWeekend = in_array($currentDate->dayOfWeek, $weekendDayNumbers);
            $isHoliday = in_array($dateStr, $publicHolidays);
            $hasAttendance = in_array($dateStr, $datesWithAttendance);

            // If it's a working day with no attendance
            if (!$isWeekend && !$isHoliday && !$hasAttendance) {
                $missingDates[] = [
                    'date' => $currentDate->copy(),
                    'day_name' => $currentDate->format('l'),
                    'formatted' => $currentDate->format('M d, Y'),
                ];
            }

            $currentDate->addDay();
        }

        // Generate month/year options for filters
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = Carbon::create(null, $m, 1)->format('F');
        }

        $years = range(now()->year - 2, now()->year);

        return view('attendance::missing-attendance.index', compact(
            'missingDates',
            'month',
            'year',
            'months',
            'years',
            'weekendDays'
        ));
    }

    /**
     * Add a date as a public holiday
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function addHoliday(Request $request): RedirectResponse
    {
        $request->validate([
            'date' => 'required|date',
            'name' => 'required|string|max:255',
        ]);

        $date = Carbon::parse($request->date);

        // Check if holiday already exists
        $exists = PublicHoliday::where('date', $date->format('Y-m-d'))->exists();
        if ($exists) {
            return back()->with('error', 'A holiday already exists on this date.');
        }

        PublicHoliday::create([
            'name' => $request->name,
            'date' => $date->format('Y-m-d'),
        ]);

        return back()->with('success', "Public holiday '{$request->name}' added for {$date->format('M d, Y')}.");
    }

    /**
     * Set WFH for all active employees on a specific date
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function setWfhForAll(Request $request): RedirectResponse
    {
        $request->validate([
            'date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $date = Carbon::parse($request->date);
        $notes = $request->notes ?? 'Bulk WFH - No office attendance recorded';

        // Get employees who were active on that date
        // (status = 'active' OR terminated/resigned with termination_date >= $date)
        // AND (start_date is null OR start_date <= $date)
        $eligibleEmployees = Employee::where(function ($query) use ($date) {
                $query->where('status', 'active')
                    ->orWhere(function ($q) use ($date) {
                        $q->whereIn('status', ['terminated', 'resigned'])
                          ->where('termination_date', '>=', $date);
                    });
            })
            ->where(function ($query) use ($date) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', $date);
            })
            ->get();

        if ($eligibleEmployees->isEmpty()) {
            return back()->with('error', 'No employees were active on this date.');
        }

        $created = 0;
        $skipped = 0;

        foreach ($eligibleEmployees as $employee) {
            // Check if WFH record already exists
            if (!WfhRecord::existsForEmployeeOnDate($employee->id, $date)) {
                WfhRecord::create([
                    'employee_id' => $employee->id,
                    'date' => $date->format('Y-m-d'),
                    'notes' => $notes,
                    'created_by' => auth()->id(),
                ]);
                $created++;
            } else {
                $skipped++;
            }
        }

        $message = "WFH records created for {$created} employees on {$date->format('M d, Y')}.";
        if ($skipped > 0) {
            $message .= " {$skipped} employees already had WFH records.";
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
}
