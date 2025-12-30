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
        'name',
        'code',
        'description',
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
