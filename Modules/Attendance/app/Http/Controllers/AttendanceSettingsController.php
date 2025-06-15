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
        $weekendDays = Setting::get('weekend_days', ['friday', 'saturday']);

        return view('attendance::settings.index', compact('workHoursPerDay', 'weekendDays'));
    }

    /**
     * Update the attendance settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'work_hours_per_day' => 'required|numeric|min:0.5|max:24',
            'weekend_days' => 'required|array|min:1|max:7',
            'weekend_days.*' => 'required|string|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday'
        ]);

        // Save work hours per day
        Setting::set(
            'work_hours_per_day',
            $request->work_hours_per_day,
            'Standard work hours required per day'
        );

        // Save weekend days
        Setting::set(
            'weekend_days',
            $request->weekend_days,
            'Official weekend days when employees are not expected to work'
        );

        return redirect()
            ->route('attendance.settings.index')
            ->with('success', 'Attendance settings updated successfully.');
    }
}
