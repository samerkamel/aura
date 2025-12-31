<?php

namespace Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class ProjectDependency extends Model
{
    protected $fillable = [
        'project_id',
        'depends_on_project_id',
        'dependency_type',
        'lag_days',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'lag_days' => 'integer',
    ];

    const DEPENDENCY_TYPES = [
        'finish_to_start' => 'Finish to Start (FS)',
        'start_to_start' => 'Start to Start (SS)',
        'finish_to_finish' => 'Finish to Finish (FF)',
        'start_to_finish' => 'Start to Finish (SF)',
    ];

    const DEPENDENCY_TYPE_DESCRIPTIONS = [
        'finish_to_start' => 'The dependent project cannot start until the predecessor finishes',
        'start_to_start' => 'The dependent project cannot start until the predecessor starts',
        'finish_to_finish' => 'The dependent project cannot finish until the predecessor finishes',
        'start_to_finish' => 'The dependent project cannot finish until the predecessor starts',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function dependsOnProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'depends_on_project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeBroken($query)
    {
        return $query->where('status', 'broken');
    }

    // Helpers
    public function getDependencyTypeLabel(): string
    {
        return self::DEPENDENCY_TYPES[$this->dependency_type] ?? $this->dependency_type;
    }

    public function getDependencyTypeDescription(): string
    {
        return self::DEPENDENCY_TYPE_DESCRIPTIONS[$this->dependency_type] ?? '';
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'active' => 'primary',
            'resolved' => 'success',
            'broken' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Check if the dependency is satisfied based on project statuses
     */
    public function isSatisfied(): bool
    {
        $predecessor = $this->dependsOnProject;

        if (!$predecessor) {
            return false;
        }

        switch ($this->dependency_type) {
            case 'finish_to_start':
                // Predecessor must be completed
                return $predecessor->actual_end_date !== null || !$predecessor->is_active;

            case 'start_to_start':
                // Predecessor must have started
                return $predecessor->actual_start_date !== null;

            case 'finish_to_finish':
                // Both must finish together - check if predecessor is done
                return $predecessor->actual_end_date !== null || !$predecessor->is_active;

            case 'start_to_finish':
                // Predecessor must have started
                return $predecessor->actual_start_date !== null;

            default:
                return false;
        }
    }

    /**
     * Calculate the earliest start date for the dependent project
     */
    public function calculateEarliestStartDate(): ?\Carbon\Carbon
    {
        $predecessor = $this->dependsOnProject;

        if (!$predecessor) {
            return null;
        }

        $baseDate = match ($this->dependency_type) {
            'finish_to_start' => $predecessor->actual_end_date ?? $predecessor->planned_end_date,
            'start_to_start' => $predecessor->actual_start_date ?? $predecessor->planned_start_date,
            'finish_to_finish' => $predecessor->actual_end_date ?? $predecessor->planned_end_date,
            'start_to_finish' => $predecessor->actual_start_date ?? $predecessor->planned_start_date,
            default => null,
        };

        if (!$baseDate) {
            return null;
        }

        return $baseDate->copy()->addDays($this->lag_days);
    }

    /**
     * Mark the dependency as resolved
     */
    public function markAsResolved(): void
    {
        $this->update(['status' => 'resolved']);
    }

    /**
     * Mark the dependency as broken
     */
    public function markAsBroken(): void
    {
        $this->update(['status' => 'broken']);
    }
}
