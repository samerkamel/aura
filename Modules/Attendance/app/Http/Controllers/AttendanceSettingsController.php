<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Attendance\Models\Setting;

/**
 * Attendance Settings Controller
 *
 * Handles the configuration of standard work hours and weekend settings
 * for the attendance system.
 *
 * @author Dev Agent
 */

class AttendanceSettingsController extends Controller
{
    /**
     * Display the attendance settings form.
     */
    public function index(): View
    {
        // Get current settings with defaults
        $workHoursPerDay = Setting::get('work_hours_per_day', 8);
        $wfhAttendanceHours = Setting::get('wfh_attendance_hours', 6);
        $weekendDays = Setting::get('weekend_days', ['friday', 'saturday']);
        $allowPastDateRequests = (bool) Setting::get('allow_past_date_requests', false);

        return view('attendance::settings.index', compact('workHoursPerDay', 'wfhAttendanceHours', 'weekendDays', 'allowPastDateRequests'));
    }

    /**
     * Update the attendance settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'work_hours_per_day' => 'required|numeric|min:0.5|max:24',
            'wfh_attendance_hours' => 'required|numeric|min:0.5|max:24',
            'weekend_days' => 'required|array|min:1|max:7',
            'weekend_days.*' => 'required|string|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'allow_past_date_requests' => 'nullable|boolean'
        ]);

        // Save work hours per day
        Setting::set(
            'work_hours_per_day',
            $request->work_hours_per_day,
            'Standard work hours required per day'
        );

        // Save WFH attendance hours
        Setting::set(
            'wfh_attendance_hours',
            $request->wfh_attendance_hours,
            'Hours counted as attendance for WFH days'
        );

        // Save weekend days
        Setting::set(
            'weekend_days',
            $request->weekend_days,
            'Official weekend days when employees are not expected to work'
        );

        // Save allow past date requests setting
        Setting::set(
            'allow_past_date_requests',
            $request->boolean('allow_past_date_requests'),
            'Allow employees to submit self-service requests for past dates'
        );

        return redirect()
            ->route('attendance.settings.index')
            ->with('success', 'Attendance settings updated successfully.');
    }
}
