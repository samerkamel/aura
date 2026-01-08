<?php

namespace Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HR\Models\Employee;

class JiraIssue extends Model
{
    protected $fillable = [
        'project_id',
        'jira_issue_id',
        'issue_key',
        'summary',
        'description',
        'issue_type',
        'status',
        'status_category',
        'priority',
        'assignee_email',
        'assignee_employee_id',
        'reporter_email',
        'parent_key',
        'epic_key',
        'story_points',
        'original_estimate_seconds',
        'remaining_estimate_seconds',
        'time_spent_seconds',
        'due_date',
        'labels',
        'components',
        'jira_created_at',
        'jira_updated_at',
        'last_synced_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'jira_created_at' => 'datetime',
        'jira_updated_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'labels' => 'array',
        'components' => 'array',
    ];

    /**
     * Issue type labels.
     */
    public const ISSUE_TYPES = [
        'Bug' => ['icon' => 'ti-bug', 'color' => 'danger'],
        'Story' => ['icon' => 'ti-bookmark', 'color' => 'success'],
        'Task' => ['icon' => 'ti-checkbox', 'color' => 'primary'],
        'Epic' => ['icon' => 'ti-bolt', 'color' => 'purple'],
        'Sub-task' => ['icon' => 'ti-subtask', 'color' => 'secondary'],
    ];

    /**
     * Status category colors.
     */
    public const STATUS_CATEGORY_COLORS = [
        'new' => 'secondary',       // To Do
        'indeterminate' => 'info',  // In Progress
        'done' => 'success',        // Done
    ];

    /**
     * Priority colors.
     */
    public const PRIORITY_COLORS = [
        'Highest' => 'danger',
        'High' => 'warning',
        'Medium' => 'info',
        'Low' => 'secondary',
        'Lowest' => 'light',
    ];

    /**
     * Get the project that owns this issue.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the assigned employee.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assignee_employee_id');
    }

    /**
     * Scope: Filter by status category.
     */
    public function scopeByStatusCategory($query, string $category)
    {
        return $query->where('status_category', $category);
    }

    /**
     * Scope: Open issues (not done).
     */
    public function scopeOpen($query)
    {
        return $query->where('status_category', '!=', 'done');
    }

    /**
     * Scope: Done issues.
     */
    public function scopeDone($query)
    {
        return $query->where('status_category', 'done');
    }

    /**
     * Get issue type icon.
     */
    public function getIssueTypeIconAttribute(): string
    {
        return self::ISSUE_TYPES[$this->issue_type]['icon'] ?? 'ti-file';
    }

    /**
     * Get issue type color.
     */
    public function getIssueTypeColorAttribute(): string
    {
        return self::ISSUE_TYPES[$this->issue_type]['color'] ?? 'secondary';
    }

    /**
     * Get status category color.
     */
    public function getStatusColorAttribute(): string
    {
        return self::STATUS_CATEGORY_COLORS[$this->status_category] ?? 'secondary';
    }

    /**
     * Get priority color.
     */
    public function getPriorityColorAttribute(): string
    {
        return self::PRIORITY_COLORS[$this->priority] ?? 'secondary';
    }

    /**
     * Check if issue is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status_category !== 'done';
    }

    /**
     * Get Jira issue URL.
     */
    public function getJiraUrlAttribute(): ?string
    {
        $settings = \Modules\Payroll\Models\JiraSetting::getInstance();
        if (!$settings->base_url) {
            return null;
        }
        return rtrim($settings->base_url, '/') . '/browse/' . $this->issue_key;
    }
}
