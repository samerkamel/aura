<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Attendance\Models\Setting;
use Modules\Payroll\Http\Requests\StorePayrollSettingRequest;

/**
 * PayrollSettingController
 *
 * Handles the configuration of payroll calculation weights for attendance and billable hours.
 * Allows admins to set the percentage weights that contribute to the final payroll calculation.
 *
 * @author Dev Agent
 */
class PayrollSettingController extends Controller
{
    /**
     * Display the payroll settings form.
     */
    public function index(): View
    {
        // Get current weight settings or set defaults
        $attendanceWeight = Setting::get('weight_attendance_pct', 50);
        $billableHoursWeight = Setting::get('weight_billable_hours_pct', 50);

        return view('payroll::settings.index', compact('attendanceWeight', 'billableHoursWeight'));
    }

    /**
     * Store the payroll weight settings.
     */
    public function store(StorePayrollSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Store the weight settings
        Setting::set(
            'weight_attendance_pct',
            $validated['attendance_weight'],
            'Attendance percentage weight for payroll calculation'
        );

        Setting::set(
            'weight_billable_hours_pct',
            $validated['billable_hours_weight'],
            'Billable hours percentage weight for payroll calculation'
        );

        return redirect()->route('payroll.settings.index')
            ->with('success', 'Payroll weight settings updated successfully.');
    }
}
