<?php

namespace Modules\SelfService\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SelfService\Models\SelfServiceRequest;
use Modules\SelfService\Services\RequestApprovalService;
use Modules\Attendance\Models\AttendanceRule;
use Modules\Attendance\Models\Setting;
use Modules\Attendance\Models\WfhRecord;
use Carbon\Carbon;

class WfhRequestController extends Controller
{
    protected RequestApprovalService $approvalService;

    public function __construct(RequestApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * Display a listing of the employee's WFH requests.
     */
    public function index(Request $request)
    {
        $employee = $request->attributes->get('employee');

        $requests = SelfServiceRequest::forEmployee($employee->id)
            ->ofType(SelfServiceRequest::TYPE_WFH)
            ->with(['managerApprover', 'adminApprover', 'rejector'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Get WFH usage info
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $wfhData = $this->getWfhData($employee, $currentMonth, $currentYear);

        return view('selfservice::wfh.index', compact('employee', 'requests', 'wfhData'));
    }

    /**
     * Show the form for creating a new WFH request.
     */
    public function create(Request $request)
    {
        $employee = $request->attributes->get('employee');

        // Get WFH usage info
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $wfhData = $this->getWfhData($employee, $currentMonth, $currentYear);

        // Check if past date requests are allowed
        $allowPastDates = (bool) Setting::get('allow_past_date_requests', false);

        return view('selfservice::wfh.create', compact('employee', 'wfhData', 'allowPastDates'));
    }

    /**
     * Store a newly created WFH request.
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

        // Check if it's a weekend
        if ($date->isWeekend()) {
            return back()->withInput()->withErrors([
                'date' => 'WFH cannot be requested for weekends.',
            ]);
        }

        // Check monthly allowance
        $month = $date->month;
        $year = $date->year;
        $wfhData = $this->getWfhData($employee, $month, $year);

        if ($wfhData['remaining'] <= 0) {
            return back()->withInput()->withErrors([
                'date' => 'You have reached your monthly WFH allowance.',
            ]);
        }

        // Check if WFH already exists for this date
        $exists = WfhRecord::where('employee_id', $employee->id)
            ->whereDate('date', $date)
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'date' => 'You already have a WFH record for this date.',
            ]);
        }

        // Check for overlapping pending/approved requests
        $overlapping = SelfServiceRequest::forEmployee($employee->id)
            ->ofType(SelfServiceRequest::TYPE_WFH)
            ->whereIn('status', [
                SelfServiceRequest::STATUS_PENDING_MANAGER,
                SelfServiceRequest::STATUS_PENDING_ADMIN,
                SelfServiceRequest::STATUS_APPROVED,
            ])
            ->whereDate('start_date', $date)
            ->exists();

        if ($overlapping) {
            return back()->withInput()->withErrors([
                'date' => 'You already have a WFH request for this date.',
            ]);
        }

        // Create the request
        $selfServiceRequest = $this->approvalService->createRequest(
            $employee,
            SelfServiceRequest::TYPE_WFH,
            $date,
            $date, // end_date same as start_date for WFH
            null,
            $validated['notes'] ?? null
        );

        return redirect()
            ->route('self-service.wfh-requests.show', $selfServiceRequest)
            ->with('success', 'WFH request submitted successfully. Waiting for approval.');
    }

    /**
     * Display the specified WFH request.
     */
    public function show(Request $request, SelfServiceRequest $wfh_request)
    {
        $employee = $request->attributes->get('employee');

        // Ensure the request belongs to this employee
        if ($wfh_request->employee_id !== $employee->id) {
            abort(403, 'Unauthorized access to this request.');
        }

        $wfh_request->load(['employee', 'manager', 'managerApprover', 'adminApprover', 'rejector']);

        return view('selfservice::wfh.show', compact('employee', 'wfh_request'));
    }

    /**
     * Cancel a pending WFH request.
     */
    public function cancel(Request $request, SelfServiceRequest $wfh_request)
    {
        $employee = $request->attributes->get('employee');

        // Ensure the request belongs to this employee
        if ($wfh_request->employee_id !== $employee->id) {
            abort(403, 'Unauthorized access to this request.');
        }

        if (!$wfh_request->canBeCancelled()) {
            return back()->with('error', 'This request cannot be cancelled.');
        }

        $this->approvalService->cancel($wfh_request);

        return redirect()
            ->route('self-service.wfh-requests.index')
            ->with('success', 'WFH request cancelled successfully.');
    }

    /**
     * Get WFH allowance and usage data.
     */
    protected function getWfhData($employee, int $month, int $year): array
    {
        // Get WFH policy rule
        $wfhRule = AttendanceRule::getWfhPolicyRule();
        $monthlyAllowance = $wfhRule?->config['monthly_allowance_days'] ?? 2;

        // Count WFH days used this month
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();

        $usedDays = WfhRecord::where('employee_id', $employee->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->count();

        // Count pending WFH requests for this month
        $pendingDays = SelfServiceRequest::forEmployee($employee->id)
            ->ofType(SelfServiceRequest::TYPE_WFH)
            ->pending()
            ->whereBetween('start_date', [$startOfMonth, $endOfMonth])
            ->count();

        return [
            'allowance' => $monthlyAllowance,
            'used' => $usedDays,
            'pending' => $pendingDays,
            'remaining' => max(0, $monthlyAllowance - $usedDays - $pendingDays),
        ];
    }
}
