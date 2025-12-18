<?php

namespace Modules\Leave\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Leave\Models\LeavePolicy;
use Modules\Leave\Models\LeavePolicyTier;

/**
 * Leave Policy Controller
 *
 * Handles the configuration and management of leave policies including
 * PTO and Sick Leave policies with their respective tiers and rules.
 *
 * @author Dev Agent
 */
class LeavePolicyController extends Controller
{
    /**
     * Display the leave policy management page.
     */
    public function index(): View
    {
        $ptoPolicies = LeavePolicy::with('tiers')
            ->where('type', 'pto')
            ->active()
            ->get();

        $sickLeavePolicies = LeavePolicy::with('tiers')
            ->where('type', 'sick_leave')
            ->active()
            ->get();

        $emergencyLeavePolicies = LeavePolicy::with('tiers')
            ->where('type', 'emergency')
            ->active()
            ->get();

        return view('leave::policies.index', compact('ptoPolicies', 'sickLeavePolicies', 'emergencyLeavePolicies'));
    }

    /**
     * Update the PTO policy configuration.
     */
    public function updatePtoPolicy(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'initial_days' => 'required|integer|min:0',
            'tiers' => 'required|array|min:1',
            'tiers.*.min_years' => 'required|integer|min:0',
            'tiers.*.max_years' => 'nullable|integer|min:0',
            'tiers.*.annual_days' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request) {
            // Get or create the PTO policy
            $ptoPolicy = LeavePolicy::firstOrCreate(
                ['type' => 'pto'],
                [
                    'name' => $request->name,
                    'description' => $request->description,
                    'initial_days' => $request->initial_days,
                    'is_active' => true,
                ]
            );

            // Update policy attributes
            $ptoPolicy->update([
                'name' => $request->name,
                'description' => $request->description,
                'initial_days' => $request->initial_days,
            ]);

            // Delete existing tiers and recreate them
            $ptoPolicy->tiers()->delete();

            // Create new tiers
            foreach ($request->tiers as $tierData) {
                LeavePolicyTier::create([
                    'leave_policy_id' => $ptoPolicy->id,
                    'min_years' => $tierData['min_years'],
                    'max_years' => $tierData['max_years'] ?? null,
                    'annual_days' => $tierData['annual_days'],
                ]);
            }
        });

        return redirect()
            ->route('leave.policies.index')
            ->with('success', 'PTO policy updated successfully.');
    }

    /**
     * Update the Sick Leave policy configuration.
     */
    public function updateSickLeavePolicy(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'days' => 'required|integer|min:1',
            'period_in_years' => 'required|integer|min:1',
        ]);

        $sickLeavePolicy = LeavePolicy::firstOrCreate(
            ['type' => 'sick_leave'],
            [
                'name' => $request->name,
                'description' => $request->description,
                'config' => [
                    'days' => $request->days,
                    'period_in_years' => $request->period_in_years,
                ],
                'is_active' => true,
            ]
        );

        // Update policy attributes
        $sickLeavePolicy->update([
            'name' => $request->name,
            'description' => $request->description,
            'config' => [
                'days' => $request->days,
                'period_in_years' => $request->period_in_years,
            ],
        ]);

        return redirect()
            ->route('leave.policies.index')
            ->with('success', 'Sick Leave policy updated successfully.');
    }

    /**
     * Update the Emergency Leave policy configuration.
     */
    public function updateEmergencyLeavePolicy(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'days_per_year' => 'required|integer|min:1',
        ]);

        $emergencyPolicy = LeavePolicy::firstOrCreate(
            ['type' => 'emergency'],
            [
                'name' => $request->name,
                'description' => $request->description,
                'initial_days' => $request->days_per_year,
                'is_active' => true,
                'config' => json_encode([
                    'carry_forward' => false,
                    'requires_approval' => true,
                    'notice_days' => 0,
                ]),
            ]
        );

        // Update policy attributes
        $emergencyPolicy->update([
            'name' => $request->name,
            'description' => $request->description,
            'initial_days' => $request->days_per_year,
        ]);

        return redirect()
            ->route('leave.policies.index')
            ->with('success', 'Emergency Leave policy updated successfully.');
    }
}
