<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Attendance\Models\Setting;
use Modules\Payroll\Http\Requests\StorePayrollSettingRequest;
use Modules\Payroll\Models\PayrollPeriodSetting;
use Modules\Payroll\Models\JiraSetting;
use Modules\Payroll\Services\JiraBillableHoursService;
use Carbon\Carbon;

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

        // Get period settings for the last 3 months and next 3 months
        $periodSettings = collect();
        for ($i = -3; $i <= 3; $i++) {
            $periodStart = Carbon::now()->addMonths($i)->startOfMonth();
            $setting = PayrollPeriodSetting::forPeriod($periodStart);

            // Calculate default target (6 hours/day, max 120)
            $workingDays = $this->countWorkingDays($periodStart, $periodStart->copy()->endOfMonth());
            $defaultTarget = min($workingDays * 6, 120);

            $periodSettings->push([
                'period' => $periodStart->format('Y-m'),
                'period_label' => $periodStart->format('F Y'),
                'target_billable_hours' => $setting?->target_billable_hours,
                'default_target' => $defaultTarget,
                'notes' => $setting?->notes,
            ]);
        }

        // Get Jira settings
        $jiraSettings = JiraSetting::getInstance();

        return view('payroll::settings.index', compact('attendanceWeight', 'billableHoursWeight', 'periodSettings', 'jiraSettings'));
    }

    /**
     * Count working days in a period.
     */
    private function countWorkingDays(Carbon $start, Carbon $end): int
    {
        $workingDays = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if ($current->isWeekday()) {
                $workingDays++;
            }
            $current->addDay();
        }

        return $workingDays;
    }

    /**
     * Store period-specific target billable hours.
     */
    public function storePeriodSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'period' => 'required|date_format:Y-m',
            'target_billable_hours' => 'nullable|numeric|min:0|max:999',
            'notes' => 'nullable|string|max:500',
        ]);

        $periodStart = Carbon::createFromFormat('Y-m', $request->period)->startOfMonth();

        PayrollPeriodSetting::updateOrCreate(
            ['period_start' => $periodStart->toDateString()],
            [
                'target_billable_hours' => $request->target_billable_hours ?: null,
                'notes' => $request->notes,
            ]
        );

        return redirect()->route('payroll.settings.index')
            ->with('success', "Target billable hours for {$periodStart->format('F Y')} updated successfully.");
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

    /**
     * Store Jira integration settings
     */
    public function storeJiraSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'jira_base_url' => 'required|url',
            'jira_email' => 'required|email',
            'jira_api_token' => 'nullable|string', // Optional - only update if provided
            'jira_billable_projects' => 'nullable|string',
            'jira_sync_enabled' => 'boolean',
            'jira_sync_frequency' => 'required|in:daily,weekly,monthly',
        ]);

        $jiraSettings = JiraSetting::getInstance();

        $data = [
            'base_url' => $request->jira_base_url,
            'email' => $request->jira_email,
            'billable_projects' => $request->jira_billable_projects,
            'sync_enabled' => (bool) $request->jira_sync_enabled,
            'sync_frequency' => $request->jira_sync_frequency,
        ];

        // Only update API token if a new one is provided
        if (!empty($request->jira_api_token)) {
            $data['api_token'] = $request->jira_api_token;
        }

        $jiraSettings->update($data);

        return redirect()->route('payroll.settings.index')
            ->with('success', 'Jira settings saved successfully.');
    }

    /**
     * Test Jira connection
     */
    public function testJiraConnection(Request $request)
    {
        $request->validate([
            'jira_base_url' => 'required|url',
            'jira_email' => 'required|email',
            'jira_api_token' => 'nullable|string',
        ]);

        try {
            $baseUrl = $request->jira_base_url;
            $email = $request->jira_email;
            $apiToken = $request->jira_api_token;

            // If no API token provided, use saved one
            if (empty($apiToken)) {
                $jiraSettings = JiraSetting::getInstance();
                $apiToken = $jiraSettings->api_token;
            }

            if (empty($apiToken)) {
                return response()->json([
                    'success' => false,
                    'message' => 'API token is required for testing'
                ]);
            }

            // Test connection directly
            $response = \Illuminate\Support\Facades\Http::withBasicAuth($email, $apiToken)
                ->get("{$baseUrl}/rest/api/3/myself");

            if ($response->successful()) {
                $user = $response->json();
                return response()->json([
                    'success' => true,
                    'message' => 'Connection successful! Connected as: ' . ($user['displayName'] ?? $user['emailAddress'] ?? 'Unknown')
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection failed: ' . $response->status() . ' - ' . $response->body()
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
