<?php

namespace Modules\Project\Services;

use Illuminate\Support\Collection;
use Modules\Project\Models\PMNotification;
use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectFollowup;
use Modules\Project\Models\ProjectMilestone;
use Modules\Project\Models\ProjectHealthSnapshot;
use Modules\Accounting\Models\ContractPayment;
use App\Models\User;

class PMNotificationService
{
    /**
     * Check and create all notifications.
     */
    public function checkAndCreateNotifications(): array
    {
        $stats = [
            'overdue_followups' => 0,
            'upcoming_followups' => 0,
            'overdue_milestones' => 0,
            'upcoming_milestones' => 0,
            'overdue_payments' => 0,
            'upcoming_payments' => 0,
            'health_alerts' => 0,
            'stale_projects' => 0,
        ];

        $stats['overdue_followups'] = $this->checkOverdueFollowups();
        $stats['upcoming_followups'] = $this->checkUpcomingFollowups();
        $stats['overdue_milestones'] = $this->checkOverdueMilestones();
        $stats['upcoming_milestones'] = $this->checkUpcomingMilestones();
        $stats['overdue_payments'] = $this->checkOverduePayments();
        $stats['upcoming_payments'] = $this->checkUpcomingPayments();
        $stats['health_alerts'] = $this->checkProjectHealth();
        $stats['stale_projects'] = $this->checkStaleProjects();

        return $stats;
    }

    /**
     * Check for overdue follow-ups.
     */
    protected function checkOverdueFollowups(): int
    {
        $count = 0;

        $overdue = ProjectFollowup::with(['project', 'user'])
            ->whereNotNull('next_followup_date')
            ->where('next_followup_date', '<', now()->startOfDay())
            ->get();

        foreach ($overdue as $followup) {
            if ($this->createNotificationIfNotExists(
                $followup->user_id,
                'followup_overdue',
                ProjectFollowup::class,
                $followup->id,
                [
                    'project_id' => $followup->project_id,
                    'title' => 'Overdue Follow-up',
                    'message' => "Follow-up for {$followup->project->name} was due on {$followup->next_followup_date->format('M d, Y')}",
                    'action_url' => route('projects.show', $followup->project_id) . '#followups',
                    'priority' => 'high',
                    'due_at' => $followup->next_followup_date,
                ]
            )) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check for follow-ups due today or tomorrow.
     */
    protected function checkUpcomingFollowups(): int
    {
        $count = 0;

        $upcoming = ProjectFollowup::with(['project', 'user'])
            ->whereNotNull('next_followup_date')
            ->whereBetween('next_followup_date', [now()->startOfDay(), now()->addDays(2)->endOfDay()])
            ->get();

        foreach ($upcoming as $followup) {
            $isToday = $followup->next_followup_date->isToday();

            if ($this->createNotificationIfNotExists(
                $followup->user_id,
                'followup_due',
                ProjectFollowup::class,
                $followup->id,
                [
                    'project_id' => $followup->project_id,
                    'title' => $isToday ? 'Follow-up Due Today' : 'Follow-up Due Tomorrow',
                    'message' => "Follow-up for {$followup->project->name}: {$followup->notes}",
                    'action_url' => route('projects.show', $followup->project_id) . '#followups',
                    'priority' => $isToday ? 'high' : 'normal',
                    'due_at' => $followup->next_followup_date,
                ]
            )) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check for overdue milestones.
     */
    protected function checkOverdueMilestones(): int
    {
        $count = 0;

        $overdue = ProjectMilestone::with(['project', 'creator'])
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
            ->get();

        foreach ($overdue as $milestone) {
            // Notify the creator or the assignee
            $userId = $milestone->created_by ?? $milestone->assigned_to;
            if (!$userId) continue;

            $daysOverdue = now()->diffInDays($milestone->due_date);

            if ($this->createNotificationIfNotExists(
                $userId,
                'milestone_overdue',
                ProjectMilestone::class,
                $milestone->id,
                [
                    'project_id' => $milestone->project_id,
                    'title' => 'Milestone Overdue',
                    'message' => "{$milestone->name} for {$milestone->project->name} is {$daysOverdue} days overdue",
                    'action_url' => route('projects.planning.milestones', $milestone->project_id),
                    'priority' => $daysOverdue > 7 ? 'urgent' : 'high',
                    'due_at' => $milestone->due_date,
                ]
            )) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check for milestones due in the next 3 days.
     */
    protected function checkUpcomingMilestones(): int
    {
        $count = 0;

        $upcoming = ProjectMilestone::with(['project', 'creator'])
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now()->startOfDay(), now()->addDays(3)->endOfDay()])
            ->get();

        foreach ($upcoming as $milestone) {
            $userId = $milestone->created_by ?? $milestone->assigned_to;
            if (!$userId) continue;

            $daysUntil = now()->diffInDays($milestone->due_date, false);
            $priority = $daysUntil <= 1 ? 'high' : 'normal';

            if ($this->createNotificationIfNotExists(
                $userId,
                'milestone_due',
                ProjectMilestone::class,
                $milestone->id,
                [
                    'project_id' => $milestone->project_id,
                    'title' => $daysUntil == 0 ? 'Milestone Due Today' : "Milestone Due in {$daysUntil} Days",
                    'message' => "{$milestone->name} for {$milestone->project->name}",
                    'action_url' => route('projects.planning.milestones', $milestone->project_id),
                    'priority' => $priority,
                    'due_at' => $milestone->due_date,
                ]
            )) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check for overdue contract payments.
     */
    protected function checkOverduePayments(): int
    {
        $count = 0;

        $overdue = ContractPayment::with(['contract.projects'])
            ->where('status', 'pending')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
            ->get();

        foreach ($overdue as $payment) {
            // Notify users who have PM role or are assigned to related projects
            $projectIds = $payment->contract->projects->pluck('id')->toArray();

            if (empty($projectIds)) continue;

            // Get the first project for context
            $project = $payment->contract->projects->first();
            $daysOverdue = now()->diffInDays($payment->due_date);

            // Notify project managers (users with projects.manage permission)
            $pmUsers = $this->getProjectManagers();

            foreach ($pmUsers as $user) {
                if ($this->createNotificationIfNotExists(
                    $user->id,
                    'payment_overdue',
                    ContractPayment::class,
                    $payment->id,
                    [
                        'project_id' => $project->id,
                        'title' => 'Payment Overdue',
                        'message' => "{$payment->name} ({$payment->contract->contract_number}) - " . number_format($payment->amount, 2) . " overdue by {$daysOverdue} days",
                        'action_url' => route('accounting.contracts.show', $payment->contract_id),
                        'priority' => $daysOverdue > 7 ? 'urgent' : 'high',
                        'due_at' => $payment->due_date,
                    ]
                )) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Check for payments due in the next 7 days.
     */
    protected function checkUpcomingPayments(): int
    {
        $count = 0;

        $upcoming = ContractPayment::with(['contract.projects'])
            ->where('status', 'pending')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
            ->get();

        foreach ($upcoming as $payment) {
            $projectIds = $payment->contract->projects->pluck('id')->toArray();

            if (empty($projectIds)) continue;

            $project = $payment->contract->projects->first();
            $daysUntil = now()->diffInDays($payment->due_date, false);

            $pmUsers = $this->getProjectManagers();

            foreach ($pmUsers as $user) {
                if ($this->createNotificationIfNotExists(
                    $user->id,
                    'payment_due',
                    ContractPayment::class,
                    $payment->id,
                    [
                        'project_id' => $project->id,
                        'title' => $daysUntil <= 1 ? 'Payment Due Soon' : "Payment Due in {$daysUntil} Days",
                        'message' => "{$payment->name} ({$payment->contract->contract_number}) - " . number_format($payment->amount, 2),
                        'action_url' => route('accounting.contracts.show', $payment->contract_id),
                        'priority' => $daysUntil <= 3 ? 'high' : 'normal',
                        'due_at' => $payment->due_date,
                    ]
                )) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Check for projects with low health scores.
     */
    protected function checkProjectHealth(): int
    {
        $count = 0;

        // Get the latest health snapshot for each active project
        $projects = Project::active()
            ->with(['latestHealthSnapshot'])
            ->get();

        foreach ($projects as $project) {
            if (!$project->latestHealthSnapshot) continue;

            $healthScore = $project->latestHealthSnapshot->health_score;

            if ($healthScore < 50) {
                $priority = $healthScore < 30 ? 'urgent' : 'high';

                $pmUsers = $this->getProjectManagers();

                foreach ($pmUsers as $user) {
                    if ($this->createNotificationIfNotExists(
                        $user->id,
                        'health_alert',
                        ProjectHealthSnapshot::class,
                        $project->latestHealthSnapshot->id,
                        [
                            'project_id' => $project->id,
                            'title' => 'Project Health Alert',
                            'message' => "{$project->name} health score is {$healthScore}% - needs attention",
                            'action_url' => route('projects.dashboard', $project->id),
                            'priority' => $priority,
                        ]
                    )) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    /**
     * Check for stale projects (no activity in 14+ days).
     */
    protected function checkStaleProjects(): int
    {
        $count = 0;

        $staleProjects = Project::active()
            ->where('updated_at', '<', now()->subDays(14))
            ->whereNotIn('phase', ['closure', 'completed'])
            ->get();

        foreach ($staleProjects as $project) {
            $daysSinceActivity = now()->diffInDays($project->updated_at);

            $pmUsers = $this->getProjectManagers();

            foreach ($pmUsers as $user) {
                if ($this->createNotificationIfNotExists(
                    $user->id,
                    'stale_project',
                    Project::class,
                    $project->id,
                    [
                        'project_id' => $project->id,
                        'title' => 'Stale Project',
                        'message' => "{$project->name} has no activity for {$daysSinceActivity} days",
                        'action_url' => route('projects.show', $project->id),
                        'priority' => $daysSinceActivity > 21 ? 'high' : 'normal',
                    ]
                )) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Create a notification if a similar one doesn't already exist today.
     */
    protected function createNotificationIfNotExists(
        int $userId,
        string $type,
        string $referenceType,
        int $referenceId,
        array $data
    ): bool {
        // Check if notification already exists
        if (PMNotification::exists($userId, $type, $referenceType, $referenceId)) {
            return false;
        }

        PMNotification::create([
            'user_id' => $userId,
            'project_id' => $data['project_id'] ?? null,
            'type' => $type,
            'title' => $data['title'],
            'message' => $data['message'],
            'action_url' => $data['action_url'] ?? null,
            'priority' => $data['priority'] ?? 'normal',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'due_at' => $data['due_at'] ?? null,
        ]);

        return true;
    }

    /**
     * Get users with project management role.
     * For now, return all users. This can be refined based on your permission system.
     */
    protected function getProjectManagers(): Collection
    {
        // TODO: Refine this based on your permission system
        // For now, get users who have the 'manage projects' permission
        return User::whereHas('roles', function ($query) {
            $query->whereHas('permissions', function ($q) {
                $q->where('name', 'like', '%project%');
            });
        })->get();
    }

    /**
     * Get unread notifications for a user.
     */
    public function getUnreadNotifications(int $userId, int $limit = 10): Collection
    {
        return PMNotification::forUser($userId)
            ->active()
            ->with('project')
            ->orderByRaw("CASE priority
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'normal' THEN 3
                ELSE 4 END")
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get notification count for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        return PMNotification::forUser($userId)
            ->unread()
            ->active()
            ->count();
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = PMNotification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if (!$notification) {
            return false;
        }

        $notification->markAsRead();
        return true;
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): int
    {
        return PMNotification::forUser($userId)
            ->unread()
            ->update(['read_at' => now()]);
    }

    /**
     * Dismiss a notification.
     */
    public function dismiss(int $notificationId, int $userId): bool
    {
        $notification = PMNotification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if (!$notification) {
            return false;
        }

        $notification->dismiss();
        return true;
    }

    /**
     * Get notifications grouped by type for dashboard.
     */
    public function getNotificationsSummary(int $userId): array
    {
        $notifications = PMNotification::forUser($userId)
            ->active()
            ->selectRaw('type, priority, COUNT(*) as count')
            ->groupBy('type', 'priority')
            ->get();

        $summary = [
            'total' => 0,
            'urgent' => 0,
            'by_type' => [],
        ];

        foreach ($notifications as $notification) {
            $summary['total'] += $notification->count;

            if ($notification->priority === 'urgent') {
                $summary['urgent'] += $notification->count;
            }

            if (!isset($summary['by_type'][$notification->type])) {
                $summary['by_type'][$notification->type] = 0;
            }
            $summary['by_type'][$notification->type] += $notification->count;
        }

        return $summary;
    }
}
