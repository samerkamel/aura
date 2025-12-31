<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HR\Models\Employee;

class JiraWorklog extends Model
{
    protected $fillable = [
        'employee_id',
        'project_id',
        'jira_author_name',
        'issue_key',
        'issue_summary',
        'worklog_started',
        'worklog_created',
        'timezone',
        'time_spent_hours',
        // Labor cost persistence fields
        'employee_salary_at_time',
        'billable_hours_in_month',
        'hourly_rate',
        'labor_cost',
        'labor_cost_multiplier',
        'pm_overhead',
        'total_cost',
        'cost_calculated',
        'cost_calculated_at',
        'comment',
        'sync_log_id',
    ];

    protected $casts = [
        'worklog_started' => 'datetime',
        'worklog_created' => 'datetime',
        'time_spent_hours' => 'decimal:2',
        'employee_salary_at_time' => 'decimal:2',
        'billable_hours_in_month' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'labor_cost_multiplier' => 'decimal:2',
        'pm_overhead' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'cost_calculated' => 'boolean',
        'cost_calculated_at' => 'datetime',
    ];

    /**
     * Get the employee that owns this worklog entry.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the project associated with this worklog.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(\Modules\Project\Models\Project::class);
    }

    /**
     * Get the sync log that created this entry.
     */
    public function syncLog(): BelongsTo
    {
        return $this->belongsTo(JiraSyncLog::class, 'sync_log_id');
    }

    /**
     * Check if labor cost has been calculated and persisted.
     */
    public function hasCostCalculated(): bool
    {
        return (bool) $this->cost_calculated;
    }

    /**
     * Scope for worklogs with calculated costs.
     */
    public function scopeWithCostCalculated($query)
    {
        return $query->where('cost_calculated', true);
    }

    /**
     * Scope for worklogs without calculated costs.
     */
    public function scopeWithoutCostCalculated($query)
    {
        return $query->where('cost_calculated', false);
    }

    /**
     * Scope for worklogs by project.
     */
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('worklog_started', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by employee.
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope to filter by issue key.
     */
    public function scopeForIssue($query, $issueKey)
    {
        return $query->where('issue_key', $issueKey);
    }
}
