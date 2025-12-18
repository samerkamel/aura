<?php

namespace Modules\SelfService\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\HR\Models\Employee;
use Modules\SelfService\Models\SelfServiceRequest;
use Modules\Leave\Services\LeaveBalanceService;
use Modules\Leave\Models\LeavePolicy;
use Modules\Attendance\Models\AttendanceRule;
use Modules\Attendance\Models\WfhRecord;
use Modules\Attendance\Models\PermissionUsage;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected LeaveBalanceService $leaveBalanceService;

    public function __construct(LeaveBalanceService $leaveBalanceService)
    {
        $this->leaveBalanceService = $leaveBalanceService;
    }

    /**
     * Display the self-service dashboard.
     */
    public function index(Request $request)
    {
        $employee = $request->attributes->get('employee');
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;

        // Get leave balances (extract just the balances array from the summary)
        $leaveBalanceSummary = $this->leaveBalanceService->getLeaveBalanceSummary($employee, $currentYear);
        $leaveBalances = $leaveBalanceSummary['balances'] ?? [];

        // Get WFH allowance and usage
        $wfhData = $this->getWfhData($employee, $currentMonth, $currentYear);

        // Get Permission allowance and usage
        $permissionData = $this->getPermissionData($employee, $currentMonth, $currentYear);

        // Get recent requests
        $recentRequests = SelfServiceRequest::forEmployee($employee->id)
            ->with(['leavePolicy'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get pending requests count
        $pendingCount = SelfServiceRequest::forEmployee($employee->id)
            ->pending()
            ->count();

        // Get pending approvals count (if this employee is a manager)
        $pendingApprovalsCount = 0;
        if ($employee->isManager()) {
            $pendingApprovalsCount = SelfServiceRequest::awaitingManagerApproval($employee->id)->count();
        }

        // Check if user is super admin (can see all pending admin approvals)
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->hasRole('super-admin') || $user->role === 'super_admin');
        $pendingAdminApprovalsCount = 0;
        if ($isSuperAdmin) {
            $pendingAdminApprovalsCount = SelfServiceRequest::pendingAdmin()->count();
        }

        return view('selfservice::dashboard.index', compact(
            'employee',
            'leaveBalances',
            'wfhData',
            'permissionData',
            'recentRequests',
            'pendingCount',
            'pendingApprovalsCount',
            'pendingAdminApprovalsCount',
            'isSuperAdmin'
        ));
    }

    /**
     * Get WFH allowance and usage for the current month.
     */
    protected function getWfhData(Employee $employee, int $month, int $year): array
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
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('start_date', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('end_date', [$startOfMonth, $endOfMonth]);
            })
            ->get()
            ->sum(fn($r) => $r->getDaysCount());

        return [
            'allowance' => $monthlyAllowance,
            'used' => $usedDays,
            'pending' => $pendingDays,
            'remaining' => max(0, $monthlyAllowance - $usedDays - $pendingDays),
        ];
    }

    /**
     * Get Permission allowance and usage for the current month.
     */
    protected function getPermissionData(Employee $employee, int $month, int $year): array
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
