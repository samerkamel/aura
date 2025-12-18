<?php

namespace Modules\SelfService\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SelfService\Models\SelfServiceRequest;
use Modules\SelfService\Services\RequestApprovalService;
use Modules\Leave\Models\LeavePolicy;
use Modules\Leave\Services\LeaveBalanceService;
use Modules\Attendance\Models\Setting;
use Carbon\Carbon;

class LeaveRequestController extends Controller
{
    protected RequestApprovalService $approvalService;
    protected LeaveBalanceService $leaveBalanceService;

    public function __construct(
        RequestApprovalService $approvalService,
        LeaveBalanceService $leaveBalanceService
    ) {
        $this->approvalService = $approvalService;
        $this->leaveBalanceService = $leaveBalanceService;
    }

    /**
     * Display a listing of the employee's leave requests.
     */
    public function index(Request $request)
    {
        $employee = $request->attributes->get('employee');
        $currentYear = Carbon::now()->year;

        $requests = SelfServiceRequest::forEmployee($employee->id)
            ->ofType(SelfServiceRequest::TYPE_LEAVE)
            ->with(['leavePolicy', 'managerApprover', 'adminApprover', 'rejector'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Get leave balances
        $leaveBalanceSummary = $this->leaveBalanceService->getLeaveBalanceSummary($employee, $currentYear);
        $leaveBalances = $leaveBalanceSummary['balances'] ?? [];

        return view('selfservice::leave.index', compact('employee', 'requests', 'leaveBalances'));
    }

    /**
     * Show the form for creating a new leave request.
     */
    public function create(Request $request)
    {
        $employee = $request->attributes->get('employee');
        $currentYear = Carbon::now()->year;

        // Get active leave policies
        $leavePolicies = LeavePolicy::active()->orderBy('name')->get();

        // Get leave balances (extract just the balances array from the summary)
        $leaveBalanceSummary = $this->leaveBalanceService->getLeaveBalanceSummary($employee, $currentYear);
        $leaveBalances = $leaveBalanceSummary['balances'] ?? [];

        // Check if past date requests are allowed
        $allowPastDates = (bool) Setting::get('allow_past_date_requests', false);

        return view('selfservice::leave.create', compact('employee', 'leavePolicies', 'leaveBalances', 'allowPastDates'));
    }

    /**
     * Store a newly created leave request.
     */
    public function store(Request $request)
    {
        $employee = $request->attributes->get('employee');

        // Check if past date requests are allowed
        $allowPastDates = (bool) Setting::get('allow_past_date_requests', false);
        $startDateRule = $allowPastDates ? 'required|date' : 'required|date|after_or_equal:today';

        $validated = $request->validate([
            'leave_policy_id' => 'required|exists:leave_policies,id',
            'start_date' => $startDateRule,
            'end_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $leavePolicy = LeavePolicy::findOrFail($validated['leave_policy_id']);
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        // Check leave balance availability
        $availability = $this->leaveBalanceService->checkLeaveAvailability(
            $employee,
            $leavePolicy,
            $startDate,
            $endDate
        );

        if (!$availability['available']) {
            return back()->withInput()->withErrors([
                'leave_policy_id' => $availability['message'] ?? 'Insufficient leave balance.',
            ]);
        }

        // Check for overlapping approved requests or pending requests
        $overlapping = SelfServiceRequest::forEmployee($employee->id)
            ->ofType(SelfServiceRequest::TYPE_LEAVE)
            ->whereIn('status', [
                SelfServiceRequest::STATUS_PENDING_MANAGER,
                SelfServiceRequest::STATUS_PENDING_ADMIN,
                SelfServiceRequest::STATUS_APPROVED,
            ])
            ->inDateRange($startDate, $endDate)
            ->exists();

        if ($overlapping) {
            return back()->withInput()->withErrors([
                'start_date' => 'You already have a leave request for these dates.',
            ]);
        }

        // Create the request
        $selfServiceRequest = $this->approvalService->createRequest(
            $employee,
            SelfServiceRequest::TYPE_LEAVE,
            $startDate,
            $endDate,
            $leavePolicy->id,
            $validated['notes'] ?? null
        );

        return redirect()
            ->route('self-service.leave-requests.show', $selfServiceRequest)
            ->with('success', 'Leave request submitted successfully. Waiting for approval.');
    }

    /**
     * Display the specified leave request.
     */
    public function show(Request $request, SelfServiceRequest $leave_request)
    {
        $employee = $request->attributes->get('employee');

        // Ensure the request belongs to this employee
        if ($leave_request->employee_id !== $employee->id) {
            abort(403, 'Unauthorized access to this request.');
        }

        $leave_request->load(['leavePolicy', 'employee', 'manager', 'managerApprover', 'adminApprover', 'rejector']);

        return view('selfservice::leave.show', compact('employee', 'leave_request'));
    }

    /**
     * Cancel a pending leave request.
     */
    public function cancel(Request $request, SelfServiceRequest $leave_request)
    {
        $employee = $request->attributes->get('employee');

        // Ensure the request belongs to this employee
        if ($leave_request->employee_id !== $employee->id) {
            abort(403, 'Unauthorized access to this request.');
        }

        if (!$leave_request->canBeCancelled()) {
            return back()->with('error', 'This request cannot be cancelled.');
        }

        $this->approvalService->cancel($leave_request);

        return redirect()
            ->route('self-service.leave-requests.index')
            ->with('success', 'Leave request cancelled successfully.');
    }
}
