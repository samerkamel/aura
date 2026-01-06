<?php

namespace Modules\Project\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PMNotification extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'type',
        'title',
        'message',
        'action_url',
        'priority',
        'reference_type',
        'reference_id',
        'due_at',
        'read_at',
        'dismissed_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'read_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    /**
     * Notification types.
     */
    public const TYPES = [
        'followup_due' => 'Follow-up Due',
        'followup_overdue' => 'Follow-up Overdue',
        'milestone_due' => 'Milestone Due',
        'milestone_overdue' => 'Milestone Overdue',
        'payment_due' => 'Payment Due',
        'payment_overdue' => 'Payment Overdue',
        'health_alert' => 'Project Health Alert',
        'stale_project' => 'Stale Project',
        'capacity_alert' => 'Capacity Alert',
    ];

    /**
     * Priority levels.
     */
    public const PRIORITIES = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    /**
     * Priority colors for badges.
     */
    public const PRIORITY_COLORS = [
        'low' => 'secondary',
        'normal' => 'info',
        'high' => 'warning',
        'urgent' => 'danger',
    ];

    /**
     * Type icons.
     */
    public const TYPE_ICONS = [
        'followup_due' => 'ti ti-phone',
        'followup_overdue' => 'ti ti-phone-off',
        'milestone_due' => 'ti ti-flag',
        'milestone_overdue' => 'ti ti-flag-off',
        'payment_due' => 'ti ti-cash',
        'payment_overdue' => 'ti ti-cash-off',
        'health_alert' => 'ti ti-heart-rate-monitor',
        'stale_project' => 'ti ti-clock-pause',
        'capacity_alert' => 'ti ti-users',
    ];

    /**
     * Get the user this notification belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project this notification is related to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the reference model (polymorphic).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Get the priority label.
     */
    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    /**
     * Get the priority color for badge.
     */
    public function getPriorityColorAttribute(): string
    {
        return self::PRIORITY_COLORS[$this->priority] ?? 'secondary';
    }

    /**
     * Get the type icon.
     */
    public function getTypeIconAttribute(): string
    {
        return self::TYPE_ICONS[$this->type] ?? 'ti ti-bell';
    }

    /**
     * Check if notification is unread.
     */
    public function getIsUnreadAttribute(): bool
    {
        return is_null($this->read_at);
    }

    /**
     * Check if notification is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_at && $this->due_at->isPast();
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(): self
    {
        $this->update(['read_at' => now()]);
        return $this;
    }

    /**
     * Mark notification as dismissed.
     */
    public function dismiss(): self
    {
        $this->update(['dismissed_at' => now()]);
        return $this;
    }

    /**
     * Scope for unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope for undismissed notifications.
     */
    public function scopeUndismissed($query)
    {
        return $query->whereNull('dismissed_at');
    }

    /**
     * Scope for active notifications (unread and undismissed).
     */
    public function scopeActive($query)
    {
        return $query->whereNull('dismissed_at');
    }

    /**
     * Scope for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for a specific priority.
     */
    public function scopeOfPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for urgent and high priority.
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['urgent', 'high']);
    }

    /**
     * Scope for today's notifications.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope for recent notifications.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Check if a similar notification already exists (to prevent duplicates).
     */
    public static function exists(
        int $userId,
        string $type,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): bool {
        return self::where('user_id', $userId)
            ->where('type', $type)
            ->when($referenceType, fn($q) => $q->where('reference_type', $referenceType))
            ->when($referenceId, fn($q) => $q->where('reference_id', $referenceId))
            ->whereNull('dismissed_at')
            ->whereDate('created_at', today())
            ->exists();
    }
}
