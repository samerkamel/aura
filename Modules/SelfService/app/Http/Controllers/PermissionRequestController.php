<?php

namespace Modules\SelfService\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SelfService\Models\SelfServiceRequest;
use Modules\SelfService\Services\RequestApprovalService;
use Modules\Attendance\Models\AttendanceRule;
use Modules\Attendance\Models\PermissionUsage;
use Modules\Attendance\Models\Setting;
use Modules\Attendance\Services\WorkingDaysService;
use Carbon\Carbon;

class PermissionRequestController extends Controller
{
    protected RequestApprovalService $approvalService;
    protected WorkingDaysService $workingDaysService;

    public function __construct(RequestApprovalService $approvalService, WorkingDaysService $workingDaysService)
    {
        $this->approvalService = $approvalService;
        $this->workingDaysService = $workingDaysService;
    }

    /**
     * Display a listing of the employee's permission requests.
     */
    public function index(Request $request)
    {
        $employee = $request->attributes->get('employee');

        $requests = SelfServiceRequest::forEmployee($employee->id)
            ->ofType(SelfServiceRequest::TYPE_PERMISSION)
            ->with(['managerApprover', 'adminApprover', 'rejector'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Get permission usage info
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $permissionData = $this->getPermissionData($employee, $currentMonth, $currentYear);

        return view('selfservice::permission.index', compact('employee', 'requests', 'permissionData'));
    }

    /**
     * Show the form for creating a new permission request.
     */
    public function create(Request $request)
    {
        $employee = $request->attributes->get('employee');

        // Get permission usage info
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $permissionData = $this->getPermissionData($employee, $currentMonth, $currentYear);

        // Check if past date requests are allowed
        $allowPastDates = (bool) Setting::get('allow_past_date_requests', false);

        return view('selfservice::permission.create', compact('employee', 'permissionData', 'allowPastDates'));
    }

    /**
     * Store a newly created permission request.
     */
    public function store(Request $request)
    {
        $employee = $request->attributes->get('employee');

        // Check if past date requests are allowed
        $allowPastDates = (bool) Setting::get('allow_past_date_requests', false);
        $dateRule = $allowPastDates ? 'required|date' : 'required|date|after_or_equal:today';

        $validated = $request->validate([
            'date' => $dateRule,
            'notes' => 'nullable|string|max:1000',
        ]);

        $date = Carbon::parse($validated['date']);

        // Check if it's a weekend or holiday (using settings-based weekend days)
        if (!$this->workingDaysService->isWorkingDay($date)) {
            return back()->withInput()->withErrors([
                'date' => 'Permission cannot be requested for weekends or public holidays.',
            ]);
        }

        // Check monthly allowance
        $month = $date->month;
        $year = $date->year;
        $permissionData = $this->getPermissionData($employee, $month, $year);

        if ($permissionData['remaining'] <= 0) {
            return back()->withInput()->withErrors([
                'date' => 'You have reached your monthly permission allowance.',
            ]);
        }

        // Check if permission already exists for this date
        $exists = PermissionUsage::where('employee_id', $employee->id)
            ->whereDate('date', $date)
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'date' => 'You already have a permission for this date.',
            ]);
        }

        // Check for overlapping pending/approved requests
        $overlapping = SelfServiceRequest::forEmployee($employee->id)
            ->ofType(SelfServiceRequest::TYPE_PERMISSION)
            ->whereIn('status', [
                SelfServiceRequest::STATUS_PENDING_MANAGER,
                SelfServiceRequest::STATUS_PENDING_ADMIN,
                SelfServiceRequest::STATUS_APPROVED,
            ])
            ->whereDate('start_date', $date)
            ->exists();

        if ($overlapping) {
            return back()->withInput()->withErrors([
                'date' => 'You already have a permission request for this date.',
            ]);
        }

        // Create the request with permission minutes in request_data
        $selfServiceRequest = $this->approvalService->createRequest(
            $employee,
            SelfServiceRequest::TYPE_PERMISSION,
            $date,
            $date,
            null,
            $validated['notes'] ?? null,
            ['minutes' => $permissionData['minutes_per_permission']]
        );

        return redirect()
            ->route('self-service.permission-requests.show', $selfServiceRequest)
            ->with('success', 'Permission request submitted successfully. Waiting for approval.');
    }

    /**
     * Display the specified permission request.
     */
    public function show(Request $request, SelfServiceRequest $permission_request)
    {
        $employee = $request->attributes->get('employee');

        // Ensure the request belongs to this employee
        if ($permission_request->employee_id !== $employee->id) {
            abort(403, 'Unauthorized access to this request.');
        }

        $permission_request->load(['employee', 'manager', 'managerApprover', 'adminApprover', 'rejector']);

        return view('selfservice::permission.show', compact('employee', 'permission_request'));
    }

    /**
     * Cancel a pending permission request.
     */
    public function cancel(Request $request, SelfServiceRequest $permission_request)
    {
        $employee = $request->attributes->get('employee');

        // Ensure the request belongs to this employee
        if ($permission_request->employee_id !== $employee->id) {
            abort(403, 'Unauthorized access to this request.');
        }

        if (!$permission_request->canBeCancelled()) {
            return back()->with('error', 'This request cannot be cancelled.');
        }

        $this->approvalService->cancel($permission_request);

        return redirect()
            ->route('self-service.permission-requests.index')
            ->with('success', 'Permission request cancelled successfully.');
    }

    /**
     * Get permission allowance and usage data.
     */
    protected function getPermissionData($employee, int $month, int $year): array
    {
        // Get total available permissions
        $totalAvailable = PermissionUsage::getTotalAvailablePermissions(
            $employee->id,
            $year,
            $month
        );

        // Get used permissions count
        $usedCount = PermissionUsage::getMonthlyUsageCount(
            $employee->id,
            $year,
            $month
        );

        // Count pending permission requests for this month
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();

        $pendingCount = SelfServiceRequest::forEmployee($employee->id)
            ->ofType(SelfServiceRequest::TYPE_PERMISSION)
            ->pending()
            ->whereBetween('start_date', [$startOfMonth, $endOfMonth])
            ->count();

        // Get permission rule for minutes info
        $permissionRule = AttendanceRule::getPermissionRule();
        $minutesPerPermission = $permissionRule?->config['minutes_per_permission'] ?? 120;

        return [
            'allowance' => $totalAvailable,
            'used' => $usedCount,
            'pending' => $pendingCount,
            'remaining' => max(0, $totalAvailable - $usedCount - $pendingCount),
            'minutes_per_permission' => $minutesPerPermission,
        ];
    }
}
