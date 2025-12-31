<?php

namespace Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Modules\HR\Models\Employee;

class ProjectRisk extends Model
{
    protected $fillable = [
        'project_id',
        'title',
        'description',
        'category',
        'probability',
        'impact',
        'risk_score',
        'status',
        'mitigation_plan',
        'contingency_plan',
        'owner_id',
        'identified_date',
        'target_resolution_date',
        'resolved_date',
        'potential_cost_impact',
        'potential_delay_days',
        'created_by',
    ];

    protected $casts = [
        'identified_date' => 'date',
        'target_resolution_date' => 'date',
        'resolved_date' => 'date',
        'potential_cost_impact' => 'decimal:2',
        'risk_score' => 'integer',
        'potential_delay_days' => 'integer',
    ];

    // Risk score multipliers
    const PROBABILITY_SCORES = [
        'low' => 1,
        'medium' => 2,
        'high' => 3,
        'very_high' => 4,
    ];

    const IMPACT_SCORES = [
        'low' => 1,
        'medium' => 2,
        'high' => 3,
        'critical' => 4,
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($risk) {
            // Calculate risk score
            $probScore = self::PROBABILITY_SCORES[$risk->probability] ?? 1;
            $impactScore = self::IMPACT_SCORES[$risk->impact] ?? 1;
            $risk->risk_score = $probScore * $impactScore;
        });
    }

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['resolved', 'accepted']);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeHighRisk($query)
    {
        return $query->where('risk_score', '>=', 9); // High probability × High impact
    }

    public function scopeMediumRisk($query)
    {
        return $query->whereBetween('risk_score', [4, 8]);
    }

    public function scopeLowRisk($query)
    {
        return $query->where('risk_score', '<', 4);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // Helpers
    public function getRiskLevel(): string
    {
        if ($this->risk_score >= 9) {
            return 'critical';
        } elseif ($this->risk_score >= 6) {
            return 'high';
        } elseif ($this->risk_score >= 3) {
            return 'medium';
        }
        return 'low';
    }

    public function getRiskLevelColor(): string
    {
        return match ($this->getRiskLevel()) {
            'critical' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'success',
        };
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'resolved'
            && $this->target_resolution_date
            && $this->target_resolution_date->isPast();
    }

    public function markAsResolved(): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_date' => now(),
        ]);
    }
}
