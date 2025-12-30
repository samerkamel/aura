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
     * Get all invoices for this project.
     */
    public function invoices()
    {
        return $this->hasMany(\Modules\Invoicing\Models\Invoice::class);
    }

    /**
     * Get all contracts for this project.
     */
    public function contracts()
    {
        return $this->belongsToMany(\Modules\Accounting\Models\Contract::class, 'contract_project')
                    ->withTimestamps();
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
     * Get total contract value for this project.
     */
    public function getTotalContractValueAttribute()
    {
        return $this->invoices()->sum('total_amount');
    }

    /**
     * Get total paid amount for this project.
     */
    public function getTotalPaidAttribute()
    {
        return $this->invoices()->sum('paid_amount');
    }

    /**
     * Get the employees assigned to this project.
     */
    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'project_employee')
                    ->withPivot(['role', 'auto_assigned', 'assigned_at'])
                    ->withTimestamps();
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
