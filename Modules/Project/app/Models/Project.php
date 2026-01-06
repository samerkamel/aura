<?php

namespace Modules\Project\Models;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\HR\Models\Employee;
use Modules\Payroll\Models\JiraWorklog;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'template_id',
        'project_manager_id',
        'name',
        'code',
        'description',
        'planned_budget',
        'hourly_rate',
        'currency',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'health_status',
        'current_health_score',
        'estimated_hours',
        'budgeted_hours',
        'required_team_size',
        'required_skills',
        'priority',
        'phase',
        'completion_percentage',
        'billing_type',
        'jira_project_id',
        'needs_monthly_report',
        'is_active',
        'last_followup_date',
        'next_followup_date',
        'followup_status',
    ];

    protected $casts = [
        'needs_monthly_report' => 'boolean',
        'is_active' => 'boolean',
        'last_followup_date' => 'date',
        'next_followup_date' => 'date',
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
        'planned_budget' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'current_health_score' => 'decimal:2',
        'budgeted_hours' => 'decimal:2',
        'completion_percentage' => 'decimal:2',
        'required_skills' => 'array',
    ];

    /**
     * Priority levels.
     */
    public const PRIORITIES = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
    ];

    /**
     * Priority colors for badges.
     */
    public const PRIORITY_COLORS = [
        'low' => 'secondary',
        'medium' => 'info',
        'high' => 'warning',
        'critical' => 'danger',
    ];

    /**
     * Project phases.
     */
    public const PHASES = [
        'initiation' => 'Initiation',
        'planning' => 'Planning',
        'execution' => 'Execution',
        'monitoring' => 'Monitoring',
        'closure' => 'Closure',
    ];

    /**
     * Health status types.
     */
    public const HEALTH_STATUSES = [
        'green' => 'On Track',
        'yellow' => 'At Risk',
        'red' => 'Critical',
    ];

    /**
     * Health status colors for badges.
     */
    public const HEALTH_STATUS_COLORS = [
        'green' => 'success',
        'yellow' => 'warning',
        'red' => 'danger',
    ];

    /**
     * Billing types.
     */
    public const BILLING_TYPES = [
        'fixed' => 'Fixed Price',
        'hourly' => 'Hourly Rate',
        'milestone' => 'Milestone-Based',
        'retainer' => 'Retainer',
    ];

    /**
     * Follow-up status types.
     */
    public const FOLLOWUP_STATUSES = [
        'up_to_date' => 'Up to Date',
        'due_soon' => 'Due Soon',
        'overdue' => 'Overdue',
        'none' => 'No Follow-ups',
    ];

    /**
     * Follow-up status colors for badges.
     */
    public const FOLLOWUP_STATUS_COLORS = [
        'up_to_date' => 'success',
        'due_soon' => 'warning',
        'overdue' => 'danger',
        'none' => 'secondary',
    ];

    /**
     * Get worklogs for this project within a date range.
     * Matches worklogs by issue_key prefix (e.g., VIS-123 matches project with code VIS).
     */
    public function getWorklogsInPeriod($startDate, $endDate)
    {
        return JiraWorklog::where('issue_key', 'LIKE', $this->code . '-%')
            ->whereBetween('worklog_started', [$startDate, $endDate])
            ->get();
    }

    /**
     * Get employee hours summary for this project within a date range.
     * Returns array of employee_id => total_hours.
     */
    public function getEmployeeHoursInPeriod($startDate, $endDate)
    {
        return JiraWorklog::where('issue_key', 'LIKE', $this->code . '-%')
            ->whereBetween('worklog_started', [$startDate, $endDate])
            ->selectRaw('employee_id, SUM(time_spent_hours) as total_hours')
            ->groupBy('employee_id')
            ->pluck('total_hours', 'employee_id')
            ->toArray();
    }

    /**
     * Scope for active projects.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for projects needing monthly reports.
     */
    public function scopeNeedsMonthlyReport($query)
    {
        return $query->where('needs_monthly_report', true)->where('is_active', true);
    }

    /**
     * Scope for projects assigned to a specific employee.
     * Includes projects where employee is project manager or team member.
     */
    public function scopeAssignedToEmployee($query, $employeeId)
    {
        return $query->where(function ($q) use ($employeeId) {
            $q->where('project_manager_id', $employeeId)
              ->orWhereHas('employees', function ($q2) use ($employeeId) {
                  $q2->where('employee_id', $employeeId);
              });
        });
    }

    /**
     * Scope for projects accessible by a user based on their permissions.
     * Super admins and users with view-all-projects see all projects.
     * Others see only projects they're assigned to.
     */
    public function scopeAccessibleByUser($query, $user)
    {
        // Super admins and those with view-all-projects can see everything
        if ($user->hasRole('super-admin') || $user->hasPermission('view-all-projects')) {
            return $query;
        }

        if (isset($user->role) && in_array($user->role, ['super_admin', 'admin'])) {
            return $query;
        }

        // Others only see assigned projects
        if ($user->employee) {
            return $query->assignedToEmployee($user->employee->id);
        }

        // No employee linked - return no projects
        return $query->whereRaw('1 = 0');
    }

    /**
     * Get the report lines for this project.
     */
    public function reportLines()
    {
        return $this->hasMany(ProjectReportLine::class);
    }

    /**
     * Get the customer that owns this project.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the project manager.
     */
    public function projectManager()
    {
        return $this->belongsTo(Employee::class, 'project_manager_id');
    }

    /**
     * Get health snapshots for this project.
     */
    public function healthSnapshots()
    {
        return $this->hasMany(ProjectHealthSnapshot::class)->orderByDesc('snapshot_date');
    }

    /**
     * Get budgets for this project.
     */
    public function budgets()
    {
        return $this->hasMany(ProjectBudget::class);
    }

    /**
     * Get costs for this project.
     */
    public function costs()
    {
        return $this->hasMany(ProjectCost::class);
    }

    /**
     * Get revenues for this project.
     */
    public function revenues()
    {
        return $this->hasMany(ProjectRevenue::class);
    }

    /**
     * Get the latest health snapshot.
     */
    public function latestHealthSnapshot()
    {
        return $this->hasOne(ProjectHealthSnapshot::class)->latestOfMany('snapshot_date');
    }

    /**
     * Get health status label.
     */
    public function getHealthStatusLabelAttribute(): string
    {
        return self::HEALTH_STATUSES[$this->health_status] ?? 'Unknown';
    }

    /**
     * Get health status color for badge.
     */
    public function getHealthStatusColorAttribute(): string
    {
        return self::HEALTH_STATUS_COLORS[$this->health_status] ?? 'secondary';
    }

    /**
     * Get billing type label.
     */
    public function getBillingTypeLabelAttribute(): string
    {
        return self::BILLING_TYPES[$this->billing_type] ?? 'Unknown';
    }

    /**
     * Get project progress percentage based on timeline.
     */
    public function getTimelineProgressAttribute(): ?int
    {
        if (!$this->planned_start_date || !$this->planned_end_date) {
            return null;
        }

        $totalDays = $this->planned_start_date->diffInDays($this->planned_end_date);
        if ($totalDays <= 0) {
            return 100;
        }

        $elapsedDays = $this->planned_start_date->diffInDays(now());
        $progress = min(100, max(0, round(($elapsedDays / $totalDays) * 100)));

        return $progress;
    }

    /**
     * Check if project is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->planned_end_date && $this->planned_end_date->isPast() && !$this->actual_end_date;
    }

    /**
     * Get days until deadline (negative if overdue).
     */
    public function getDaysUntilDeadlineAttribute(): ?int
    {
        if (!$this->planned_end_date) {
            return null;
        }
        return now()->startOfDay()->diffInDays($this->planned_end_date->startOfDay(), false);
    }

    /**
     * Get all invoices directly linked to this project (single project - backward compatibility).
     */
    public function invoices()
    {
        return $this->hasMany(\Modules\Invoicing\Models\Invoice::class);
    }

    /**
     * Get all invoices allocated to this project (many-to-many through pivot).
     */
    public function allocatedInvoices()
    {
        return $this->belongsToMany(\Modules\Invoicing\Models\Invoice::class, 'invoice_project')
            ->withPivot(['allocated_amount', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get total invoice amount allocated to this project.
     */
    public function getTotalInvoicesAllocatedValueAttribute(): float
    {
        return $this->allocatedInvoices->sum('pivot.allocated_amount');
    }

    /**
     * Get all invoices for this project (combined direct + allocated).
     */
    public function getAllInvoicesAttribute()
    {
        $directInvoices = $this->invoices;
        $allocatedInvoices = $this->allocatedInvoices;

        return $directInvoices->merge($allocatedInvoices)->unique('id');
    }

    /**
     * Get all contracts for this project.
     */
    public function contracts()
    {
        return $this->belongsToMany(\Modules\Accounting\Models\Contract::class, 'contract_project')
                    ->withPivot(['allocation_type', 'allocation_percentage', 'allocation_amount', 'is_primary', 'notes'])
                    ->withTimestamps();
    }

    /**
     * Get total allocated contract value for this project.
     */
    public function getTotalContractsAllocatedValueAttribute(): float
    {
        $total = 0;
        foreach ($this->contracts as $contract) {
            $pivot = $contract->pivot;
            if ($pivot->allocation_type === 'percentage') {
                $total += $contract->total_amount * (($pivot->allocation_percentage ?? 0) / 100);
            } else {
                $total += $pivot->allocation_amount ?? 0;
            }
        }
        return $total;
    }

    /**
     * Get total paid amount from contracts for this project.
     */
    public function getTotalContractsPaidAttribute(): float
    {
        $total = 0;
        foreach ($this->contracts()->with('payments')->get() as $contract) {
            $allocationRatio = $contract->total_amount > 0
                ? $this->getContractAllocationRatio($contract)
                : 0;
            $total += $contract->paid_amount * $allocationRatio;
        }
        return $total;
    }

    /**
     * Get allocation ratio for a contract (0 to 1).
     */
    public function getContractAllocationRatio(\Modules\Accounting\Models\Contract $contract): float
    {
        $pivot = $this->contracts()->where('contracts.id', $contract->id)->first()?->pivot;
        if (!$pivot) {
            return 0;
        }

        if ($pivot->allocation_type === 'percentage') {
            return ($pivot->allocation_percentage ?? 0) / 100;
        }

        return $contract->total_amount > 0 ? (($pivot->allocation_amount ?? 0) / $contract->total_amount) : 0;
    }

    /**
     * Get all follow-ups for this project.
     */
    public function followups()
    {
        return $this->hasMany(ProjectFollowup::class)->orderByDesc('followup_date');
    }

    /**
     * Get all Jira issues for this project.
     */
    public function jiraIssues()
    {
        return $this->hasMany(JiraIssue::class);
    }

    /**
     * Get open Jira issues count.
     */
    public function getOpenIssuesCountAttribute(): int
    {
        return $this->jiraIssues()->open()->count();
    }

    /**
     * Get Jira issue summary by status category.
     */
    public function getIssueSummaryAttribute(): array
    {
        $issues = $this->jiraIssues()
            ->selectRaw('status_category, COUNT(*) as count')
            ->groupBy('status_category')
            ->pluck('count', 'status_category')
            ->toArray();

        return [
            'todo' => $issues['new'] ?? 0,
            'in_progress' => $issues['indeterminate'] ?? 0,
            'done' => $issues['done'] ?? 0,
            'total' => array_sum($issues),
        ];
    }

    /**
     * Get the latest follow-up for this project.
     */
    public function latestFollowup()
    {
        return $this->hasOne(ProjectFollowup::class)->latestOfMany('followup_date');
    }

    /**
     * Get follow-up status label.
     */
    public function getFollowupStatusLabelAttribute(): string
    {
        return self::FOLLOWUP_STATUSES[$this->followup_status] ?? 'Unknown';
    }

    /**
     * Get follow-up status color for badge.
     */
    public function getFollowupStatusColorAttribute(): string
    {
        return self::FOLLOWUP_STATUS_COLORS[$this->followup_status] ?? 'secondary';
    }

    /**
     * Check if project needs follow-up (active in past 30 days or has open tasks).
     */
    public function needsFollowup(): bool
    {
        // Has worklogs in the past 30 days
        $hasRecentActivity = JiraWorklog::where('issue_key', 'LIKE', $this->code . '-%')
            ->where('worklog_started', '>=', now()->subDays(30))
            ->exists();

        return $this->is_active && ($hasRecentActivity || $this->followup_status === 'overdue' || $this->followup_status === 'due_soon');
    }

    /**
     * Get all worklogs for this project.
     * Matches worklogs by issue_key prefix (e.g., VIS-123 matches project with code VIS).
     */
    public function worklogs()
    {
        return JiraWorklog::where('issue_key', 'LIKE', $this->code . '-%');
    }

    /**
     * Get total logged hours for this project.
     */
    public function getTotalHoursAttribute()
    {
        return JiraWorklog::where('issue_key', 'LIKE', $this->code . '-%')
            ->sum('time_spent_hours');
    }

    /**
     * Get total contract value for this project (in base currency EGP).
     * Uses total_in_base for foreign currency invoices, total_amount for EGP invoices.
     */
    public function getTotalContractValueAttribute()
    {
        return $this->invoices->sum(function ($invoice) {
            // Use total_in_base if available (foreign currency), otherwise use total_amount (EGP)
            if ($invoice->currency !== 'EGP' && $invoice->total_in_base > 0) {
                return $invoice->total_in_base;
            }
            return $invoice->total_amount;
        });
    }

    /**
     * Get total paid amount for this project (in base currency EGP).
     * Converts paid_amount using exchange_rate for foreign currency invoices.
     */
    public function getTotalPaidAttribute()
    {
        return $this->invoices->sum(function ($invoice) {
            // For foreign currency invoices, convert paid_amount using exchange_rate
            if ($invoice->currency !== 'EGP' && $invoice->exchange_rate > 0) {
                return $invoice->paid_amount * $invoice->exchange_rate;
            }
            return $invoice->paid_amount;
        });
    }

    /**
     * Get total invoice value in base currency (EGP) - alias for consistency.
     */
    public function getTotalInvoicesInBaseAttribute(): float
    {
        return $this->total_contract_value;
    }

    /**
     * Get total paid in base currency (EGP) - alias for consistency.
     */
    public function getTotalPaidInBaseAttribute(): float
    {
        return $this->total_paid;
    }

    /**
     * Get the employees assigned to this project.
     */
    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'project_employee')
                    ->withPivot([
                        'role',
                        'auto_assigned',
                        'assigned_at',
                        'allocation_percentage',
                        'start_date',
                        'end_date',
                        'hourly_rate',
                        'notes',
                    ])
                    ->withTimestamps();
    }

    /**
     * Get the template used to create this project.
     */
    public function template()
    {
        return $this->belongsTo(ProjectTemplate::class, 'template_id');
    }

    /**
     * Get project milestones.
     */
    public function milestones()
    {
        return $this->hasMany(ProjectMilestone::class)->orderBy('due_date');
    }

    /**
     * Get project risks.
     */
    public function risks()
    {
        return $this->hasMany(ProjectRisk::class)->orderByDesc('risk_score');
    }

    /**
     * Get project time estimates.
     */
    public function timeEstimates()
    {
        return $this->hasMany(ProjectTimeEstimate::class);
    }

    /**
     * Get projects that this project depends on.
     */
    public function dependencies()
    {
        return $this->belongsToMany(
            Project::class,
            'project_dependencies',
            'project_id',
            'depends_on_project_id'
        )->withPivot(['dependency_type', 'lag_days', 'description', 'status', 'created_by'])
         ->withTimestamps();
    }

    /**
     * Get projects that depend on this project.
     */
    public function dependents()
    {
        return $this->belongsToMany(
            Project::class,
            'project_dependencies',
            'depends_on_project_id',
            'project_id'
        )->withPivot(['dependency_type', 'lag_days', 'description', 'status', 'created_by'])
         ->withTimestamps();
    }

    /**
     * Get dependency records for this project.
     */
    public function dependencyRecords()
    {
        return $this->hasMany(ProjectDependency::class, 'project_id');
    }

    /**
     * Get priority label.
     */
    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? 'Unknown';
    }

    /**
     * Get priority color for badge.
     */
    public function getPriorityColorAttribute(): string
    {
        return self::PRIORITY_COLORS[$this->priority] ?? 'secondary';
    }

    /**
     * Get phase label.
     */
    public function getPhaseLabelAttribute(): string
    {
        return self::PHASES[$this->phase] ?? 'Unknown';
    }

    /**
     * Get total allocated hours based on employee allocations.
     */
    public function getAllocatedHoursAttribute(): float
    {
        return $this->employees()
            ->where(function ($query) {
                $query->whereNull('project_employee.end_date')
                      ->orWhere('project_employee.end_date', '>=', now());
            })
            ->sum(\DB::raw('(project_employee.allocation_percentage / 100) * 40')); // Assuming 40h/week
    }

    /**
     * Get count of active risks.
     */
    public function getActiveRisksCountAttribute(): int
    {
        return $this->risks()->active()->count();
    }

    /**
     * Get count of high-risk issues.
     */
    public function getHighRisksCountAttribute(): int
    {
        return $this->risks()->highRisk()->count();
    }

    /**
     * Get upcoming milestones (next 14 days).
     */
    public function getUpcomingMilestonesAttribute()
    {
        return $this->milestones()->upcoming()->get();
    }

    /**
     * Get overdue milestones.
     */
    public function getOverdueMilestonesAttribute()
    {
        return $this->milestones()->overdue()->get();
    }

    /**
     * Calculate actual completion percentage based on time estimates.
     */
    public function calculateCompletionPercentage(): float
    {
        $estimates = $this->timeEstimates;

        if ($estimates->isEmpty()) {
            return 0;
        }

        $totalEstimated = $estimates->sum('estimated_hours');
        $completedHours = $estimates->where('status', 'completed')->sum('estimated_hours');

        if ($totalEstimated <= 0) {
            return 0;
        }

        return round(($completedHours / $totalEstimated) * 100, 2);
    }

    /**
     * Update completion percentage from time estimates.
     */
    public function updateCompletionPercentage(): void
    {
        $this->update([
            'completion_percentage' => $this->calculateCompletionPercentage(),
        ]);
    }

    /**
     * Sync employees from worklogs - auto-assign employees who have logged time.
     */
    public function syncEmployeesFromWorklogs(): array
    {
        $employeeIds = JiraWorklog::where('issue_key', 'LIKE', $this->code . '-%')
            ->whereNotNull('employee_id')
            ->distinct()
            ->pluck('employee_id')
            ->toArray();

        $added = 0;
        foreach ($employeeIds as $employeeId) {
            // Only add if not already assigned
            if (!$this->employees()->where('employee_id', $employeeId)->exists()) {
                $this->employees()->attach($employeeId, [
                    'role' => 'member',
                    'auto_assigned' => true,
                    'assigned_at' => now(),
                ]);
                $added++;
            }
        }

        return [
            'total_with_worklogs' => count($employeeIds),
            'newly_added' => $added,
        ];
    }

    /**
     * Check if an employee is assigned to this project.
     */
    public function hasEmployee(Employee $employee): bool
    {
        return $this->employees()->where('employee_id', $employee->id)->exists();
    }

    /**
     * Get employees who have worklogs but are not assigned.
     */
    public function getUnassignedWorklogEmployees()
    {
        $worklogEmployeeIds = JiraWorklog::where('issue_key', 'LIKE', $this->code . '-%')
            ->whereNotNull('employee_id')
            ->distinct()
            ->pluck('employee_id')
            ->toArray();

        $assignedEmployeeIds = $this->employees()->pluck('employees.id')->toArray();

        $unassignedIds = array_diff($worklogEmployeeIds, $assignedEmployeeIds);

        return Employee::whereIn('id', $unassignedIds)->get();
    }
}
