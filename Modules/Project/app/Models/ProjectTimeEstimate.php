<?php

namespace Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Modules\HR\Models\Employee;

class ProjectTimeEstimate extends Model
{
    protected $fillable = [
        'project_id',
        'milestone_id',
        'task_name',
        'description',
        'estimated_hours',
        'actual_hours',
        'synced_hours',
        'remaining_hours',
        'variance_hours',
        'variance_percentage',
        'progress_percentage',
        'assigned_to',
        'hourly_rate',
        'estimated_cost',
        'actual_cost',
        'cost_variance',
        'cost_variance_percentage',
        'estimated_start_date',
        'estimated_end_date',
        'actual_start_date',
        'actual_end_date',
        'status',
        'priority',
        'category',
        'jira_issue_key',
        'last_worklog_sync',
        'worklog_count',
        'created_by',
    ];

    protected $casts = [
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'synced_hours' => 'decimal:2',
        'remaining_hours' => 'decimal:2',
        'variance_hours' => 'decimal:2',
        'variance_percentage' => 'decimal:2',
        'progress_percentage' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'cost_variance' => 'decimal:2',
        'cost_variance_percentage' => 'decimal:2',
        'estimated_start_date' => 'date',
        'estimated_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
        'last_worklog_sync' => 'datetime',
        'worklog_count' => 'integer',
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
     * Priority colors.
     */
    public const PRIORITY_COLORS = [
        'low' => 'secondary',
        'medium' => 'info',
        'high' => 'warning',
        'critical' => 'danger',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($estimate) {
            // Calculate remaining hours if not set
            if ($estimate->remaining_hours === null && $estimate->estimated_hours) {
                $estimate->remaining_hours = max(0, $estimate->estimated_hours - $estimate->actual_hours);
            }

            // Calculate variance
            if ($estimate->estimated_hours > 0 && $estimate->actual_hours > 0) {
                $estimate->variance_hours = $estimate->actual_hours - $estimate->estimated_hours;
                $estimate->variance_percentage = ($estimate->variance_hours / $estimate->estimated_hours) * 100;
            }
        });
    }

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ProjectMilestone::class, 'milestone_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeNotStarted($query)
    {
        return $query->where('status', 'not_started');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverEstimate($query)
    {
        return $query->where('variance_hours', '>', 0);
    }

    public function scopeUnderEstimate($query)
    {
        return $query->where('variance_hours', '<', 0);
    }

    public function scopeByJiraIssue($query, string $issueKey)
    {
        return $query->where('jira_issue_key', $issueKey);
    }

    // Helpers
    public function getCompletionPercentage(): float
    {
        if ($this->estimated_hours <= 0) {
            return 0;
        }
        return min(100, ($this->actual_hours / $this->estimated_hours) * 100);
    }

    public function isOverBudget(): bool
    {
        return $this->variance_hours > 0;
    }

    public function isUnderBudget(): bool
    {
        return $this->variance_hours < 0;
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'not_started' => 'secondary',
            'in_progress' => 'primary',
            'completed' => 'success',
            'on_hold' => 'warning',
            default => 'secondary',
        };
    }

    public function getVarianceColor(): string
    {
        if ($this->variance_hours === null || $this->variance_hours == 0) {
            return 'secondary';
        }
        return $this->variance_hours > 0 ? 'danger' : 'success';
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'actual_end_date' => now(),
            'remaining_hours' => 0,
        ]);
    }

    public function startWork(): void
    {
        $this->update([
            'status' => 'in_progress',
            'actual_start_date' => $this->actual_start_date ?? now(),
        ]);
    }

    // Cost tracking helpers
    public function isCostOverBudget(): bool
    {
        return ($this->cost_variance ?? 0) > 0;
    }

    public function isCostUnderBudget(): bool
    {
        return ($this->cost_variance ?? 0) < 0;
    }

    public function getCostVarianceColor(): string
    {
        if ($this->cost_variance === null || $this->cost_variance == 0) {
            return 'secondary';
        }
        return $this->cost_variance > 0 ? 'danger' : 'success';
    }

    public function getPriorityBadgeClass(): string
    {
        return self::PRIORITY_COLORS[$this->priority] ?? 'secondary';
    }

    public function getPriorityLabel(): string
    {
        return self::PRIORITIES[$this->priority] ?? 'Unknown';
    }

    public function needsSync(): bool
    {
        if (!$this->jira_issue_key) {
            return false;
        }

        // Needs sync if never synced or last sync was more than 1 hour ago
        if (!$this->last_worklog_sync) {
            return true;
        }

        return $this->last_worklog_sync->lt(now()->subHour());
    }

    // Scopes for new fields
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'critical']);
    }

    public function scopeOverCostBudget($query)
    {
        return $query->where('cost_variance', '>', 0);
    }

    public function scopeUnderCostBudget($query)
    {
        return $query->where('cost_variance', '<', 0);
    }

    public function scopeNeedsWorklogSync($query)
    {
        return $query->whereNotNull('jira_issue_key')
            ->where(function ($q) {
                $q->whereNull('last_worklog_sync')
                    ->orWhere('last_worklog_sync', '<', now()->subHour());
            });
    }

    public function scopeWithCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
