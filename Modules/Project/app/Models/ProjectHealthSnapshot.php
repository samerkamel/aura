<?php

namespace Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectHealthSnapshot extends Model
{
    protected $fillable = [
        'project_id',
        'snapshot_date',
        'health_score',
        'budget_score',
        'schedule_score',
        'scope_score',
        'quality_score',
        'metrics',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'health_score' => 'decimal:2',
        'budget_score' => 'decimal:2',
        'schedule_score' => 'decimal:2',
        'scope_score' => 'decimal:2',
        'quality_score' => 'decimal:2',
        'metrics' => 'array',
    ];

    /**
     * Get the project that owns the snapshot.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the health status based on score.
     */
    public function getStatusAttribute(): string
    {
        if ($this->health_score >= 70) {
            return 'green';
        } elseif ($this->health_score >= 40) {
            return 'yellow';
        }
        return 'red';
    }

    /**
     * Get status color for display.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'green' => 'success',
            'yellow' => 'warning',
            'red' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Scope to get snapshots for a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('snapshot_date', [$startDate, $endDate]);
    }

    /**
     * Scope to get latest snapshot per project.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('snapshot_date', 'desc');
    }
}
