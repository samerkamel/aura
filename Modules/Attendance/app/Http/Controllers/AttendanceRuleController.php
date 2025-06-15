<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\Attendance\Models\AttendanceRule;

/**
 * AttendanceRuleController
 *
 * Manages attendance rules including flexible hours configuration
 *
 * @author GitHub Copilot
 */
class AttendanceRuleController extends Controller
{
    // Validation rule constants to avoid duplication
    private const VALIDATION_MINUTES_RULE = 'required|integer|min:1|max:1440';

    private const VALIDATION_MONTH_DAYS_RULE = 'required|integer|min:1|max:31';

    private const VALIDATION_PERCENTAGE_RULE = 'required|integer|min:0|max:100';

    /**
     * Display a listing of attendance rules
     */
    public function index(): View
    {
        $rules = AttendanceRule::orderBy('created_at', 'desc')->get();

        return view('attendance::rules.index', compact('rules'));
    }

    /**
     * Show the form for creating a new flexible hours rule
     */
    public function create(): View
    {
        $existingRule = AttendanceRule::getFlexibleHoursRule();

        return view('attendance::rules.create', compact('existingRule'));
    }

    /**
     * Store a newly created rule
     */
    public function store(Request $request): RedirectResponse
    {
        // Determine the rule type
        $ruleType = $request->input('rule_type', AttendanceRule::TYPE_FLEXIBLE_HOURS);

        if ($ruleType === AttendanceRule::TYPE_LATE_PENALTY) {
            return $this->storeLatepenaltyRule($request);
        }

        if ($ruleType === AttendanceRule::TYPE_PERMISSION) {
            return $this->storePermissionRule($request);
        }

        if ($ruleType === AttendanceRule::TYPE_WFH_POLICY) {
            return $this->storeWfhPolicyRule($request);
        }

        return $this->storeFlexibleHoursRule($request);
    }

    /**
     * Store a flexible hours rule (existing logic)
     */
    private function storeFlexibleHoursRule(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'rule_name' => 'required|string|max:255',
            'start_time_from' => 'required|date_format:H:i',
            'start_time_to' => 'required|date_format:H:i|after:start_time_from',
        ], [
            'start_time_to.after' => 'The "To" time must be later than the "From" time.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $config = [
            'from' => $request->start_time_from,
            'to' => $request->start_time_to,
        ];

        // Use upsert logic - update existing or create new flexible hours rule
        $existingRule = AttendanceRule::getFlexibleHoursRule();

        if ($existingRule) {
            $existingRule->update([
                'rule_name' => $request->rule_name,
                'config' => $config,
            ]);

            $message = 'Flexible hours rule updated successfully!';
        } else {
            AttendanceRule::create([
                'rule_name' => $request->rule_name,
                'rule_type' => AttendanceRule::TYPE_FLEXIBLE_HOURS,
                'config' => $config,
            ]);

            $message = 'Flexible hours rule created successfully!';
        }

        return redirect()->route('attendance.rules.index')
            ->with('success', $message);
    }

    /**
     * Store a late penalty rule (always creates new record)
     */
    private function storeLatepenaltyRule(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'rule_name' => 'required|string|max:255',
            'late_minutes' => self::VALIDATION_MINUTES_RULE,
            'penalty_minutes' => self::VALIDATION_MINUTES_RULE,
        ], [
            'late_minutes.min' => 'Late minutes must be at least 1 minute.',
            'late_minutes.max' => 'Late minutes cannot exceed 1440 minutes (24 hours).',
            'penalty_minutes.min' => 'Penalty minutes must be at least 1 minute.',
            'penalty_minutes.max' => 'Penalty minutes cannot exceed 1440 minutes (24 hours).',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $config = [
            'late_minutes' => (int) $request->late_minutes,
            'penalty_minutes' => (int) $request->penalty_minutes,
        ];

        AttendanceRule::create([
            'rule_name' => $request->rule_name,
            'rule_type' => AttendanceRule::TYPE_LATE_PENALTY,
            'config' => $config,
        ]);

        return redirect()->route('attendance.rules.index')
            ->with('success', 'Late penalty rule created successfully!');
    }

    /**
     * Store a permission rule (upsert logic - only one permission rule allowed)
     */
    private function storePermissionRule(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'max_per_month' => self::VALIDATION_MONTH_DAYS_RULE,
            'minutes_per_permission' => self::VALIDATION_MINUTES_RULE,
        ], [
            'max_per_month.min' => 'Max permissions per month must be at least 1.',
            'max_per_month.max' => 'Max permissions per month cannot exceed 31.',
            'minutes_per_permission.min' => 'Minutes per permission must be at least 1 minute.',
            'minutes_per_permission.max' => 'Minutes per permission cannot exceed 1440 minutes (24 hours).',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $config = [
            'max_per_month' => (int) $request->max_per_month,
            'minutes_per_permission' => (int) $request->minutes_per_permission,
        ];

        // Use upsert logic - update existing or create new permission rule
        $existingRule = AttendanceRule::getPermissionRule();

        if ($existingRule) {
            $existingRule->update([
                'config' => $config,
            ]);

            $message = 'Employee permissions configuration updated successfully!';
        } else {
            AttendanceRule::create([
                'rule_name' => 'Employee Permissions',
                'rule_type' => AttendanceRule::TYPE_PERMISSION,
                'config' => $config,
            ]);

            $message = 'Employee permissions configuration created successfully!';
        }

        return redirect()->route('attendance.rules.index')
            ->with('success', $message);
    }

    /**
     * Store a WFH policy rule (upsert logic - only one WFH policy allowed)
     */
    private function storeWfhPolicyRule(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'max_days_per_month' => self::VALIDATION_MONTH_DAYS_RULE,
            'attendance_percentage' => self::VALIDATION_PERCENTAGE_RULE,
        ], [
            'max_days_per_month.min' => 'Max WFH days per month must be at least 1.',
            'max_days_per_month.max' => 'Max WFH days per month cannot exceed 31.',
            'attendance_percentage.min' => 'Attendance percentage must be at least 0%.',
            'attendance_percentage.max' => 'Attendance percentage cannot exceed 100%.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $config = [
            'max_days_per_month' => (int) $request->max_days_per_month,
            'attendance_percentage' => (int) $request->attendance_percentage,
        ];

        // Use upsert logic - update existing or create new WFH policy rule
        $existingRule = AttendanceRule::getWfhPolicyRule();

        if ($existingRule) {
            $existingRule->update([
                'config' => $config,
            ]);

            $message = 'Work-From-Home policy updated successfully!';
        } else {
            AttendanceRule::create([
                'rule_name' => 'Work-From-Home Policy',
                'rule_type' => AttendanceRule::TYPE_WFH_POLICY,
                'config' => $config,
            ]);

            $message = 'Work-From-Home policy created successfully!';
        }

        return redirect()->route('attendance.rules.index')
            ->with('success', $message);
    }

    /**
     * Remove the specified rule from storage
     */
    public function destroy(AttendanceRule $attendanceRule): RedirectResponse
    {
        $ruleName = $attendanceRule->rule_name;
        $attendanceRule->delete();

        return redirect()->route('attendance.rules.index')
            ->with('success', "Rule '{$ruleName}' deleted successfully!");
    }
}
