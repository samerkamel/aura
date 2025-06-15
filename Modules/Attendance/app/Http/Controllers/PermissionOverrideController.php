<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Attendance\Models\PermissionOverride;

/**
 * Permission Override Controller
 *
 * Handles the granting of exceptional permissions to employees beyond their standard monthly allowance
 *
 * @author GitHub Copilot
 */
class PermissionOverrideController extends Controller
{
    /**
     * Store a newly created permission override
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Check authorization
        if (! Gate::allows('manage-permission-overrides')) {
            abort(403, 'Unauthorized action.');
        }

        // Validate request
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'extra_permissions_granted' => 'required|integer|min:1|max:10',
            'reason' => 'nullable|string|max:500',
        ]);

        // Get current payroll month start date
        $payrollPeriodStart = now()->startOfMonth();

        // Check if override already exists for this employee and period
        $existingOverride = PermissionOverride::where('employee_id', $validated['employee_id'])
            ->where('payroll_period_start_date', $payrollPeriodStart)
            ->first();

        if ($existingOverride) {
            // Update existing override by adding the new permissions
            $existingOverride->update([
                'extra_permissions_granted' => $existingOverride->extra_permissions_granted + $validated['extra_permissions_granted'],
                'reason' => $existingOverride->reason . ' | ' . ($validated['reason'] ?? 'Additional permissions granted'),
                'granted_by_user_id' => auth()->id(),
            ]);

            return redirect()->back()->with('success', 'Extra permissions added successfully. Total extra permissions for this month: ' . $existingOverride->extra_permissions_granted);
        } else {
            // Create new override
            PermissionOverride::create([
                'employee_id' => $validated['employee_id'],
                'payroll_period_start_date' => $payrollPeriodStart,
                'extra_permissions_granted' => $validated['extra_permissions_granted'],
                'granted_by_user_id' => auth()->id(),
                'reason' => $validated['reason'] ?? 'Extra permissions granted by Super Admin',
            ]);

            return redirect()->back()->with('success', 'Extra permissions granted successfully.');
        }
    }

    /**
     * Get permission overrides for a specific employee and current month
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEmployeeOverrides(int $employeeId)
    {
        if (! Gate::allows('manage-permission-overrides')) {
            abort(403, 'Unauthorized action.');
        }

        $currentMonthStart = now()->startOfMonth();

        $overrides = PermissionOverride::where('employee_id', $employeeId)
            ->where('payroll_period_start_date', $currentMonthStart)
            ->with('grantedBy')
            ->get();

        return response()->json($overrides);
    }
}
