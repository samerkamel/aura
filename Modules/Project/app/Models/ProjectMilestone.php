<?php

namespace Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\User;
use Modules\HR\Models\Employee;

class ProjectMilestone extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'description',
        'due_date',
        'completed_date',
        'status',
        'priority',
        'progress_percentage',
        'deliverables',
        'assigned_to',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_date' => 'date',
        'progress_percentage' => 'decimal:2',
        'deliverables' => 'array',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            ProjectMilestone::class,
            'project_milestone_dependencies',
            'milestone_id',
            'depends_on_milestone_id'
        )->withPivot(['dependency_type', 'lag_days'])->withTimestamps();
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            ProjectMilestone::class,
            'project_milestone_dependencies',
            'depends_on_milestone_id',
            'milestone_id'
        )->withPivot(['dependency_type', 'lag_days'])->withTimestamps();
    }

    public function timeEstimates(): HasMany
    {
        return $this->hasMany(ProjectTimeEstimate::class, 'milestone_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'completed')
            ->where('due_date', '<', now());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', '!=', 'completed')
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays(14));
    }

    // Helpers
    public function isOverdue(): bool
    {
        return $this->status !== 'completed' && $this->due_date && $this->due_date->isPast();
    }

    public function daysUntilDue(): ?int
    {
        if (!$this->due_date) {
            return null;
        }
        return now()->diffInDays($this->due_date, false);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_date' => now(),
            'progress_percentage' => 100,
        ]);
    }
}
