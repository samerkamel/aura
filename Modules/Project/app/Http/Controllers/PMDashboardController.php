<?php

namespace Modules\Project\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Project\Models\PMNotification;
use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectFollowup;
use Modules\Project\Models\ProjectMilestone;
use Modules\Project\Services\PMNotificationService;
use Modules\Accounting\Models\ContractPayment;
use Modules\Payroll\Models\JiraWorklog;

class PMDashboardController extends Controller
{
    public function __construct(
        protected PMNotificationService $notificationService
    ) {
    }

    /**
     * Display the PM Dashboard (Command Center).
     */
    public function index(): View
    {
        $user = auth()->user();

        // Today's Follow-ups
        $todayFollowups = ProjectFollowup::with(['project', 'user'])
            ->whereNotNull('next_followup_date')
            ->whereDate('next_followup_date', today())
            ->orderBy('next_followup_date')
            ->get();

        // Overdue Follow-ups
        $overdueFollowups = ProjectFollowup::with(['project', 'user'])
            ->whereNotNull('next_followup_date')
            ->where('next_followup_date', '<', today())
            ->orderBy('next_followup_date')
            ->get();

        // Upcoming Follow-ups (next 7 days)
        $upcomingFollowups = ProjectFollowup::with(['project', 'user'])
            ->whereNotNull('next_followup_date')
            ->whereBetween('next_followup_date', [today()->addDay(), today()->addDays(7)])
            ->orderBy('next_followup_date')
            ->get();

        // Overdue Milestones
        $overdueMilestones = ProjectMilestone::with('project')
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->where('due_date', '<', today())
            ->orderBy('due_date')
            ->get();

        // Upcoming Milestones (next 14 days)
        $upcomingMilestones = ProjectMilestone::with('project')
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [today(), today()->addDays(14)])
            ->orderBy('due_date')
            ->get();

        // Pending Contract Payments (upcoming and overdue)
        $pendingPayments = ContractPayment::with(['contract.customer', 'contract.projects'])
            ->where('status', 'pending')
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->limit(20)
            ->get()
            ->map(function ($payment) {
                $payment->is_overdue = $payment->due_date->isPast();
                $payment->days_until = now()->diffInDays($payment->due_date, false);
                return $payment;
            });

        // At-Risk Projects (health score < 60)
        $atRiskProjects = Project::active()
            ->with('latestHealthSnapshot')
            ->get()
            ->filter(function ($project) {
                return $project->latestHealthSnapshot && $project->latestHealthSnapshot->health_score < 60;
            })
            ->sortBy(fn($p) => $p->latestHealthSnapshot->health_score);

        // Active Projects Summary
        $activeProjects = Project::active()->count();
        $projectsByPhase = Project::active()
            ->selectRaw('phase, COUNT(*) as count')
            ->groupBy('phase')
            ->pluck('count', 'phase')
            ->toArray();

        // Recent Activity
        $recentActivity = $this->getRecentActivity();

        // Team Workload (hours logged this week)
        $teamWorkload = $this->getTeamWorkload();

        // Notifications Summary
        $notificationsSummary = $this->notificationService->getNotificationsSummary($user->id);

        // Unread Notifications for dropdown
        $notifications = $this->notificationService->getUnreadNotifications($user->id, 10);
        $unreadCount = $this->notificationService->getUnreadCount($user->id);

        return view('project::pm-dashboard.index', compact(
            'todayFollowups',
            'overdueFollowups',
            'upcomingFollowups',
            'overdueMilestones',
            'upcomingMilestones',
            'pendingPayments',
            'atRiskProjects',
            'activeProjects',
            'projectsByPhase',
            'recentActivity',
            'teamWorkload',
            'notificationsSummary',
            'notifications',
            'unreadCount'
        ));
    }

    /**
     * Get all notifications page.
     */
    public function notifications(Request $request): View
    {
        $user = auth()->user();

        $query = PMNotification::forUser($user->id)
            ->with('project')
            ->orderByDesc('created_at');

        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->ofType($request->type);
        }

        // Filter by priority
        if ($request->has('priority') && $request->priority !== 'all') {
            $query->ofPriority($request->priority);
        }

        // Filter by read status
        if ($request->has('status')) {
            if ($request->status === 'unread') {
                $query->unread();
            } elseif ($request->status === 'read') {
                $query->whereNotNull('read_at');
            }
        }

        $notifications = $query->paginate(20);

        return view('project::pm-dashboard.notifications', compact('notifications'));
    }

    /**
     * Get notifications for AJAX dropdown.
     */
    public function getNotifications(Request $request): JsonResponse
    {
        $user = auth()->user();
        $limit = $request->input('limit', 10);

        $notifications = $this->notificationService->getUnreadNotifications($user->id, $limit);
        $unreadCount = $this->notificationService->getUnreadCount($user->id);

        return response()->json([
            'notifications' => $notifications->map(function ($n) {
                return [
                    'id' => $n->id,
                    'type' => $n->type,
                    'type_label' => $n->type_label,
                    'type_icon' => $n->type_icon,
                    'title' => $n->title,
                    'message' => $n->message,
                    'priority' => $n->priority,
                    'priority_color' => $n->priority_color,
                    'action_url' => $n->action_url,
                    'project_name' => $n->project?->name,
                    'is_overdue' => $n->is_overdue,
                    'created_at' => $n->created_at->diffForHumans(),
                ];
            }),
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markNotificationRead(Request $request): JsonResponse
    {
        $user = auth()->user();
        $notificationId = $request->input('notification_id');

        if ($this->notificationService->markAsRead($notificationId, $user->id)) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllNotificationsRead(): JsonResponse
    {
        $user = auth()->user();
        $count = $this->notificationService->markAllAsRead($user->id);

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Dismiss a notification.
     */
    public function dismissNotification(Request $request): JsonResponse
    {
        $user = auth()->user();
        $notificationId = $request->input('notification_id');

        if ($this->notificationService->dismiss($notificationId, $user->id)) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
    }

    /**
     * Calendar view with all events.
     */
    public function calendar(Request $request): View|JsonResponse
    {
        if ($request->ajax() || $request->wantsJson()) {
            return $this->getCalendarEvents($request);
        }

        return view('project::pm-dashboard.calendar');
    }

    /**
     * Get calendar events as JSON.
     */
    protected function getCalendarEvents(Request $request): JsonResponse
    {
        $start = $request->input('start', now()->startOfMonth()->toDateString());
        $end = $request->input('end', now()->endOfMonth()->toDateString());

        $events = collect();

        // Follow-ups
        $followups = ProjectFollowup::with('project')
            ->whereNotNull('next_followup_date')
            ->whereBetween('next_followup_date', [$start, $end])
            ->get();

        foreach ($followups as $followup) {
            $events->push([
                'id' => 'followup-' . $followup->id,
                'title' => $followup->project->name . ' - Follow-up',
                'start' => $followup->next_followup_date->toDateString(),
                'color' => '#6f42c1',
                'type' => 'followup',
                'url' => route('projects.show', $followup->project_id) . '#followups',
            ]);
        }

        // Milestones
        $milestones = ProjectMilestone::with('project')
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$start, $end])
            ->get();

        foreach ($milestones as $milestone) {
            $color = $milestone->isOverdue() ? '#dc3545' : '#198754';
            $events->push([
                'id' => 'milestone-' . $milestone->id,
                'title' => $milestone->project->name . ' - ' . $milestone->name,
                'start' => $milestone->due_date->toDateString(),
                'color' => $color,
                'type' => 'milestone',
                'url' => route('projects.planning.milestones', $milestone->project_id),
            ]);
        }

        // Contract Payments
        $payments = ContractPayment::with(['contract.projects'])
            ->where('status', 'pending')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$start, $end])
            ->get();

        foreach ($payments as $payment) {
            $project = $payment->contract->projects->first();
            if (!$project) continue;

            $color = $payment->due_date->isPast() ? '#dc3545' : '#0d6efd';
            $events->push([
                'id' => 'payment-' . $payment->id,
                'title' => $project->name . ' - Payment: ' . number_format($payment->amount, 0),
                'start' => $payment->due_date->toDateString(),
                'color' => $color,
                'type' => 'payment',
                'url' => route('accounting.contracts.show', $payment->contract_id),
            ]);
        }

        return response()->json($events);
    }

    /**
     * Get recent activity across all projects.
     */
    protected function getRecentActivity(int $limit = 10): array
    {
        $activities = collect();

        // Recent follow-ups
        $followups = ProjectFollowup::with(['project', 'user'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($followup) {
                return [
                    'type' => 'followup',
                    'date' => $followup->created_at,
                    'description' => ($followup->user?->name ?? 'Unknown') . ' logged a follow-up for ' . $followup->project->name,
                    'icon' => 'ti-phone',
                    'color' => 'purple',
                    'url' => route('projects.show', $followup->project_id) . '#followups',
                ];
            });
        $activities = $activities->merge($followups);

        // Recent milestone updates
        $milestones = ProjectMilestone::with('project')
            ->where('status', 'completed')
            ->orderByDesc('completed_date')
            ->limit($limit)
            ->get()
            ->map(function ($milestone) {
                return [
                    'type' => 'milestone',
                    'date' => $milestone->completed_date,
                    'description' => 'Milestone completed: ' . $milestone->name . ' (' . $milestone->project->name . ')',
                    'icon' => 'ti-flag-filled',
                    'color' => 'success',
                    'url' => route('projects.planning.milestones', $milestone->project_id),
                ];
            });
        $activities = $activities->merge($milestones);

        // Sort and return
        return $activities->sortByDesc('date')->take($limit)->values()->toArray();
    }

    /**
     * Get team workload for the current week.
     */
    protected function getTeamWorkload(): array
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        return JiraWorklog::whereNotNull('employee_id')
            ->whereBetween('worklog_started', [$startOfWeek, $endOfWeek])
            ->with('employee')
            ->selectRaw('employee_id, SUM(time_spent_hours) as total_hours, COUNT(DISTINCT issue_key) as task_count')
            ->groupBy('employee_id')
            ->orderByDesc('total_hours')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $capacity = 40; // Standard work week
                $utilizationPercent = min(100, ($item->total_hours / $capacity) * 100);

                return [
                    'employee' => $item->employee,
                    'total_hours' => round($item->total_hours, 1),
                    'task_count' => $item->task_count,
                    'utilization_percent' => round($utilizationPercent, 0),
                    'is_overloaded' => $item->total_hours > $capacity,
                ];
            })
            ->toArray();
    }

    /**
     * API endpoint for dashboard widgets (AJAX refresh).
     */
    public function dashboardData(Request $request): JsonResponse
    {
        $user = auth()->user();

        $data = [
            'overdue_followups_count' => ProjectFollowup::whereNotNull('next_followup_date')
                ->where('next_followup_date', '<', today())
                ->count(),
            'today_followups_count' => ProjectFollowup::whereNotNull('next_followup_date')
                ->whereDate('next_followup_date', today())
                ->count(),
            'overdue_milestones_count' => ProjectMilestone::where('status', '!=', 'completed')
                ->whereNotNull('due_date')
                ->where('due_date', '<', today())
                ->count(),
            'overdue_payments_count' => ContractPayment::where('status', 'pending')
                ->whereNotNull('due_date')
                ->where('due_date', '<', today())
                ->count(),
            'at_risk_projects_count' => Project::active()
                ->whereHas('healthSnapshots', function ($q) {
                    $q->where('health_score', '<', 60);
                })
                ->count(),
            'unread_notifications' => $this->notificationService->getUnreadCount($user->id),
        ];

        return response()->json($data);
    }

    /**
     * Log a quick follow-up from dashboard.
     */
    public function quickFollowup(Request $request): JsonResponse
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'type' => 'required|in:call,email,meeting,message,other',
            'notes' => 'required|string|max:500',
            'next_followup_date' => 'nullable|date|after:today',
        ]);

        $followup = ProjectFollowup::create([
            'project_id' => $request->project_id,
            'user_id' => auth()->id(),
            'type' => $request->type,
            'notes' => $request->notes,
            'followup_date' => now(),
            'next_followup_date' => $request->next_followup_date,
        ]);

        return response()->json([
            'success' => true,
            'followup' => $followup->load('project'),
        ]);
    }
}
