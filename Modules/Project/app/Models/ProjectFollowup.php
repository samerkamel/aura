<?php

namespace Modules\Project\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFollowup extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'type',
        'notes',
        'contact_person',
        'outcome',
        'followup_date',
        'next_followup_date',
    ];

    protected $casts = [
        'followup_date' => 'date',
        'next_followup_date' => 'date',
    ];

    /**
     * Follow-up types.
     */
    public const TYPES = [
        'call' => 'Phone Call',
        'email' => 'Email',
        'meeting' => 'Meeting',
        'message' => 'Message',
        'other' => 'Other',
    ];

    /**
     * Outcome types.
     */
    public const OUTCOMES = [
        'positive' => 'Positive',
        'neutral' => 'Neutral',
        'needs_attention' => 'Needs Attention',
        'escalation' => 'Escalation Required',
    ];

    /**
     * Outcome colors for badges.
     */
    public const OUTCOME_COLORS = [
        'positive' => 'success',
        'neutral' => 'secondary',
        'needs_attention' => 'warning',
        'escalation' => 'danger',
    ];

    /**
     * Get the project this follow-up belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who logged this follow-up.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Get the outcome label.
     */
    public function getOutcomeLabelAttribute(): string
    {
        return self::OUTCOMES[$this->outcome] ?? $this->outcome;
    }

    /**
     * Get the outcome color for badge.
     */
    public function getOutcomeColorAttribute(): string
    {
        return self::OUTCOME_COLORS[$this->outcome] ?? 'secondary';
    }

    /**
     * Scope for recent follow-ups.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('followup_date', '>=', now()->subDays($days));
    }

    /**
     * Scope for a specific project.
     */
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }
}
