<?php

namespace Modules\SelfService\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SelfService\Models\SelfServiceRequest;
use Modules\SelfService\Services\RequestApprovalService;
use Modules\HR\Models\Employee;

class ApprovalController extends Controller
{
    protected RequestApprovalService $approvalService;

    public function __construct(RequestApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * Display pending requests for approval.
     */
    public function index(Request $request)
    {
        $employee = $request->attributes->get('employee');
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->hasRole('super-admin') || $user->role === 'super_admin');

        // Get requests pending manager approval (for managers)
        $pendingManagerRequests = collect();
        if ($employee->isManager()) {
            $pendingManagerRequests = $this->approvalService->getPendingForManager($employee);
        }

        // Get requests pending admin approval (for super admins)
        $pendingAdminRequests = collect();
        if ($isSuperAdmin) {
            $pendingAdminRequests = $this->approvalService->getPendingForAdmin();
        }

        // Combine all pending requests for super admin (they can approve anything)
        $allPendingRequests = collect();
        if ($isSuperAdmin) {
            $allPendingRequests = $this->approvalService->getAllPending();
        }

        return view('selfservice::approvals.index', compact(
            'employee',
            'pendingManagerRequests',
            'pendingAdminRequests',
            'allPendingRequests',
            'isSuperAdmin'
        ));
    }

    /**
     * Approve a request.
     */
    public function approve(Request $request, SelfServiceRequest $selfServiceRequest)
    {
        $employee = $request->attributes->get('employee');
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->hasRole('super-admin') || $user->role === 'super_admin');

        // Check permissions
        $canApproveAsManager = $this->approvalService->canApproveAsManager($selfServiceRequest, $employee);
        $canApproveAsAdmin = $this->approvalService->canApproveAsAdmin($selfServiceRequest, $user);

        if (!$canApproveAsManager && !$canApproveAsAdmin) {
            return back()->with('error', 'You do not have permission to approve this request.');
        }

        // Determine approval type
        if ($canApproveAsManager && !$isSuperAdmin) {
            // Manager approval only
            $success = $this->approvalService->approveAsManager($selfServiceRequest, $user);
            $message = $success
                ? 'Request approved and forwarded to admin for final approval.'
                : 'Failed to approve request.';
        } else {
            // Admin approval (final)
            $success = $this->approvalService->approveAsAdmin($selfServiceRequest, $user);
            $message = $success
                ? 'Request approved successfully. The record has been created.'
                : 'Failed to approve request.';
        }

        return back()->with($success ? 'success' : 'error', $message);
    }

    /**
     * Reject a request.
     */
    public function reject(Request $request, SelfServiceRequest $selfServiceRequest)
    {
        $employee = $request->attributes->get('employee');
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->hasRole('super-admin') || $user->role === 'super_admin');

        // Check permissions
        $canApproveAsManager = $this->approvalService->canApproveAsManager($selfServiceRequest, $employee);
        $canApproveAsAdmin = $this->approvalService->canApproveAsAdmin($selfServiceRequest, $user);

        if (!$canApproveAsManager && !$canApproveAsAdmin) {
            return back()->with('error', 'You do not have permission to reject this request.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $success = $this->approvalService->reject($selfServiceRequest, $user, $validated['rejection_reason']);

        return back()->with(
            $success ? 'success' : 'error',
            $success ? 'Request rejected successfully.' : 'Failed to reject request.'
        );
    }

    /**
     * Show a single request for review.
     */
    public function show(Request $request, SelfServiceRequest $selfServiceRequest)
    {
        $employee = $request->attributes->get('employee');
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->hasRole('super-admin') || $user->role === 'super_admin');

        // Super admins can view any request
        if (!$isSuperAdmin) {
            // Check if user can view this request as manager
            $canApproveAsManager = $this->approvalService->canApproveAsManager($selfServiceRequest, $employee);
            $canApproveAsAdmin = $this->approvalService->canApproveAsAdmin($selfServiceRequest, $user);

            if (!$canApproveAsManager && !$canApproveAsAdmin) {
                abort(403, 'You do not have permission to view this request.');
            }
        }

        // Recalculate permissions for template display
        $canApproveAsManager = $this->approvalService->canApproveAsManager($selfServiceRequest, $employee);
        $canApproveAsAdmin = $this->approvalService->canApproveAsAdmin($selfServiceRequest, $user);

        $selfServiceRequest->load([
            'employee',
            'leavePolicy',
            'manager',
            'managerApprover',
            'adminApprover',
            'rejector'
        ]);

        return view('selfservice::approvals.show', compact(
            'employee',
            'selfServiceRequest',
            'canApproveAsManager',
            'canApproveAsAdmin',
            'isSuperAdmin'
        ));
    }
}
