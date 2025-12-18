<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HR\Models\Employee;

class JiraWorklog extends Model
{
    protected $fillable = [
        'employee_id',
        'jira_author_name',
        'issue_key',
        'issue_summary',
        'worklog_started',
        'worklog_created',
        'timezone',
        'time_spent_hours',
        'comment',
        'sync_log_id',
    ];

    protected $casts = [
        'worklog_started' => 'datetime',
        'worklog_created' => 'datetime',
        'time_spent_hours' => 'decimal:2',
    ];

    /**
     * Get the employee that owns this worklog entry.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the sync log that created this entry.
     */
    public function syncLog(): BelongsTo
    {
        return $this->belongsTo(JiraSyncLog::class, 'sync_log_id');
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
