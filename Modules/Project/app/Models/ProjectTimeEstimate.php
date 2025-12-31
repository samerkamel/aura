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
        'remaining_hours',
        'variance_hours',
        'variance_percentage',
        'assigned_to',
        'estimated_start_date',
        'estimated_end_date',
        'actual_start_date',
        'actual_end_date',
        'status',
        'jira_issue_key',
        'created_by',
    ];

    protected $casts = [
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'remaining_hours' => 'decimal:2',
        'variance_hours' => 'decimal:2',
        'variance_percentage' => 'decimal:2',
        'estimated_start_date' => 'date',
        'estimated_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
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
}
