<?php

namespace Modules\SelfService\Services;

use Modules\SelfService\Models\SelfServiceRequest;
use Modules\HR\Models\Employee;
use Modules\Leave\Models\LeaveRecord;
use Modules\Attendance\Models\WfhRecord;
use Modules\Attendance\Models\PermissionUsage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RequestApprovalService
{
    /**
     * Create a new request with proper initial status.
     */
    public function createRequest(
        Employee $employee,
        string $type,
        Carbon $startDate,
        ?Carbon $endDate = null,
        ?int $leavePolicyId = null,
        ?string $notes = null,
        ?array $requestData = null
    ): SelfServiceRequest {
        // Determine initial status based on whether employee has a manager
        $manager = $employee->manager;
        $initialStatus = $manager
            ? SelfServiceRequest::STATUS_PENDING_MANAGER
            : SelfServiceRequest::STATUS_PENDING_ADMIN;

        return SelfServiceRequest::create([
            'employee_id' => $employee->id,
            'request_type' => $type,
            'status' => $initialStatus,
            'start_date' => $startDate,
            'end_date' => $endDate ?? $startDate,
            'leave_policy_id' => $leavePolicyId,
            'request_data' => $requestData,
            'notes' => $notes,
            'manager_id' => $manager?->id,
        ]);
    }

    /**
     * Approve request as manager.
     * Moves status to pending_admin.
     */
    public function approveAsManager(SelfServiceRequest $request, User $approver): bool
    {
        if ($request->status !== SelfServiceRequest::STATUS_PENDING_MANAGER) {
            return false;
        }

        $request->update([
            'status' => SelfServiceRequest::STATUS_PENDING_ADMIN,
            'manager_approved_at' => now(),
            'manager_approved_by' => $approver->id,
        ]);

        return true;
    }

    /**
     * Approve request as admin (final approval).
     * Creates the actual records (Leave, WFH, Permission).
     */
    public function approveAsAdmin(SelfServiceRequest $request, User $approver): bool
    {
        if (!in_array($request->status, [
            SelfServiceRequest::STATUS_PENDING_MANAGER,
            SelfServiceRequest::STATUS_PENDING_ADMIN
        ])) {
            return false;
        }

        return DB::transaction(function () use ($request, $approver) {
            // Update request status
            $request->update([
                'status' => SelfServiceRequest::STATUS_APPROVED,
                'admin_approved_at' => now(),
                'admin_approved_by' => $approver->id,
                // If skipping manager approval, also mark manager fields
                'manager_approved_at' => $request->manager_approved_at ?? now(),
                'manager_approved_by' => $request->manager_approved_by ?? $approver->id,
            ]);

            // Create the actual records based on request type
            $this->createActualRecords($request, $approver);

            return true;
        });
    }

    /**
     * Reject a request.
     */
    public function reject(SelfServiceRequest $request, User $rejector, string $reason): bool
    {
        if (!$request->isPending()) {
            return false;
        }

        $request->update([
            'status' => SelfServiceRequest::STATUS_REJECTED,
            'rejected_at' => now(),
            'rejected_by' => $rejector->id,
            'rejection_reason' => $reason,
        ]);

        return true;
    }

    /**
     * Cancel a request (by employee).
     */
    public function cancel(SelfServiceRequest $request): bool
    {
        if (!$request->canBeCancelled()) {
            return false;
        }

        $request->update([
            'status' => SelfServiceRequest::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        return true;
    }

    /**
     * Create actual records when request is approved.
     */
    protected function createActualRecords(SelfServiceRequest $request, User $approver): void
    {
        switch ($request->request_type) {
            case SelfServiceRequest::TYPE_LEAVE:
                $this->createLeaveRecord($request, $approver);
                break;

            case SelfServiceRequest::TYPE_WFH:
                $this->createWfhRecords($request, $approver);
                break;

            case SelfServiceRequest::TYPE_PERMISSION:
                $this->createPermissionUsage($request, $approver);
                break;
        }
    }

    /**
     * Create LeaveRecord from approved request.
     */
    protected function createLeaveRecord(SelfServiceRequest $request, User $approver): void
    {
        LeaveRecord::create([
            'employee_id' => $request->employee_id,
            'leave_policy_id' => $request->leave_policy_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'approved',
            'notes' => $request->notes ?? 'Approved via Self-Service Portal',
            'created_by' => $approver->id,
        ]);
    }

    /**
     * Create WfhRecord(s) from approved request.
     * Creates one record per day in the date range.
     */
    protected function createWfhRecords(SelfServiceRequest $request, User $approver): void
    {
        $currentDate = $request->start_date->copy();
        $endDate = $request->end_date ?? $request->start_date;

        while ($currentDate->lte($endDate)) {
            // Check if WFH record already exists for this date
            $exists = WfhRecord::where('employee_id', $request->employee_id)
                ->whereDate('date', $currentDate)
                ->exists();

            if (!$exists) {
                WfhRecord::create([
                    'employee_id' => $request->employee_id,
                    'date' => $currentDate->copy(),
                    'notes' => $request->notes ?? 'Approved via Self-Service Portal',
                    'created_by' => $approver->id,
                ]);
            }

            $currentDate->addDay();
        }
    }

    /**
     * Create PermissionUsage from approved request.
     */
    protected function createPermissionUsage(SelfServiceRequest $request, User $approver): void
    {
        // Get permission minutes from request_data or use default
        $minutesUsed = $request->request_data['minutes'] ?? 120;

        // Check if permission already exists for this date
        $exists = PermissionUsage::where('employee_id', $request->employee_id)
            ->whereDate('date', $request->start_date)
            ->exists();

        if (!$exists) {
            PermissionUsage::create([
                'employee_id' => $request->employee_id,
                'date' => $request->start_date,
                'minutes_used' => $minutesUsed,
                'granted_by_user_id' => $approver->id,
                'reason' => $request->notes ?? 'Approved via Self-Service Portal',
            ]);
        }
    }

    /**
     * Get pending requests for a manager.
     */
    public function getPendingForManager(Employee $manager)
    {
        return SelfServiceRequest::awaitingManagerApproval($manager->id)
            ->with(['employee', 'leavePolicy'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get pending requests for admin approval.
     */
    public function getPendingForAdmin()
    {
        return SelfServiceRequest::pendingAdmin()
            ->with(['employee', 'manager', 'leavePolicy', 'managerApprover'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get all pending requests (for super admin who can approve everything).
     */
    public function getAllPending()
    {
        return SelfServiceRequest::pending()
            ->with(['employee', 'manager', 'leavePolicy', 'managerApprover'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Check if a user can approve a request as manager.
     */
    public function canApproveAsManager(SelfServiceRequest $request, Employee $employee): bool
    {
        return $request->status === SelfServiceRequest::STATUS_PENDING_MANAGER
            && $request->manager_id === $employee->id;
    }

    /**
     * Check if a user can approve a request as admin.
     */
    public function canApproveAsAdmin(SelfServiceRequest $request, User $user): bool
    {
        // Super admin can approve any pending request
        if ($user->hasRole('super-admin') || $user->role === 'super_admin') {
            return $request->isPending();
        }

        // Check if user has admin role
        $hasAdminRole = $user->hasRole('admin') || $user->role === 'admin';

        if (!$hasAdminRole) {
            return false;
        }

        // Regular admins can only approve pending_admin requests
        return $request->status === SelfServiceRequest::STATUS_PENDING_ADMIN;
    }
}
