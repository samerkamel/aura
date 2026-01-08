<?php

namespace Modules\Project\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Project\Models\JiraIssue;
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
     * Get team workload for 2 weeks (starting from previous Sunday).
     */
    protected function getTeamWorkload(): array
    {
        // Start from the most recent Sunday (or today if Sunday)
        $startDate = now()->startOfWeek(\Carbon\Carbon::SUNDAY);
        // End 2 weeks from start (14 days total)
        $endDate = $startDate->copy()->addDays(13)->endOfDay();

        // 60 hours capacity for 2 weeks (6 hours/day * 10 working days)
        $capacity = 60;

        // Get logged hours from worklogs
        $loggedHours = JiraWorklog::whereNotNull('employee_id')
            ->whereBetween('worklog_started', [$startDate, $endDate])
            ->selectRaw('employee_id, SUM(time_spent_hours) as logged_hours')
            ->groupBy('employee_id')
            ->pluck('logged_hours', 'employee_id')
            ->toArray();

        // Get scheduled hours from active Jira issues (remaining estimate)
        // Only include: To Do, In Progress (exclude: Hold, Testing, Done, etc.)
        // Only include tasks with due dates in the 2-week period
        $scheduledHours = JiraIssue::whereNotNull('assignee_employee_id')
            ->whereIn('status', ['To Do', 'In Progress', 'Pending'])
            ->whereNotNull('remaining_estimate_seconds')
            ->where('remaining_estimate_seconds', '>', 0)
            ->whereBetween('due_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('assignee_employee_id, SUM(remaining_estimate_seconds) / 3600 as scheduled_hours')
            ->groupBy('assignee_employee_id')
            ->pluck('scheduled_hours', 'assignee_employee_id')
            ->toArray();

        // Get all employees who have either logged hours or scheduled hours
        $employeeIds = array_unique(array_merge(
            array_keys($loggedHours),
            array_keys($scheduledHours)
        ));

        if (empty($employeeIds)) {
            return [];
        }

        // Fetch employees
        $employees = \Modules\HR\Models\Employee::whereIn('id', $employeeIds)
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($employeeIds as $empId) {
            $employee = $employees->get($empId);
            if (!$employee) {
                continue;
            }

            $logged = round($loggedHours[$empId] ?? 0, 1);
            $scheduled = round($scheduledHours[$empId] ?? 0, 1);
            $totalAllocated = $logged + $scheduled;

            // Calculate percentages for the bar (capped at 100% each)
            $loggedPercent = min(100, ($logged / $capacity) * 100);
            $scheduledPercent = min(100 - $loggedPercent, ($scheduled / $capacity) * 100);
            $unutilizedPercent = max(0, 100 - $loggedPercent - $scheduledPercent);

            $result[] = [
                'employee' => $employee,
                'logged_hours' => $logged,
                'scheduled_hours' => $scheduled,
                'total_hours' => round($totalAllocated, 1),
                'capacity' => $capacity,
                'logged_percent' => round($loggedPercent, 0),
                'scheduled_percent' => round($scheduledPercent, 0),
                'unutilized_percent' => round($unutilizedPercent, 0),
                'utilization_percent' => round(min(100, ($totalAllocated / $capacity) * 100), 0),
                'is_overloaded' => $totalAllocated > $capacity,
            ];
        }

        // Sort by total allocated hours descending
        usort($result, fn($a, $b) => $b['total_hours'] <=> $a['total_hours']);

        // Limit to top 10
        return array_slice($result, 0, 10);
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

    /**
     * Get employee workload details for modal.
     */
    public function employeeWorkload(Request $request, int $employeeId): JsonResponse
    {
        $employee = \Modules\HR\Models\Employee::findOrFail($employeeId);

        // Date ranges
        $startDate = now()->startOfWeek(\Carbon\Carbon::SUNDAY);
        $endDate = $startDate->copy()->addDays(13)->endOfDay();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        // Recent tasks with worklogs (last 2 weeks)
        $recentWorklogs = JiraWorklog::where('employee_id', $employeeId)
            ->whereBetween('worklog_started', [$startDate, $endDate])
            ->orderByDesc('worklog_started')
            ->get()
            ->groupBy('issue_key')
            ->map(function ($worklogs, $issueKey) {
                $issue = JiraIssue::where('issue_key', $issueKey)->first();
                return [
                    'issue_key' => $issueKey,
                    'summary' => $issue->summary ?? 'Unknown Task',
                    'status' => $issue->status ?? 'Unknown',
                    'status_category' => $issue->status_category ?? 'new',
                    'project_code' => explode('-', $issueKey)[0],
                    'total_hours' => round($worklogs->sum('time_spent_hours'), 2),
                    'worklogs' => $worklogs->map(function ($wl) {
                        return [
                            'id' => $wl->id,
                            'date' => $wl->worklog_started->format('M d, Y'),
                            'time' => $wl->worklog_started->format('H:i'),
                            'hours' => round($wl->time_spent_hours, 2),
                            'description' => $wl->comment,
                        ];
                    })->values()->toArray(),
                ];
            })
            ->values()
            ->take(10)
            ->toArray();

        // Scheduled tasks (To Do, In Progress with due date in period)
        $scheduledTasks = JiraIssue::where('assignee_employee_id', $employeeId)
            ->whereIn('status', ['To Do', 'In Progress', 'Pending'])
            ->whereNotNull('remaining_estimate_seconds')
            ->where('remaining_estimate_seconds', '>', 0)
            ->whereBetween('due_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('due_date')
            ->get()
            ->map(function ($issue) {
                return [
                    'issue_key' => $issue->issue_key,
                    'summary' => $issue->summary,
                    'status' => $issue->status,
                    'status_category' => $issue->status_category ?? 'new',
                    'priority' => $issue->priority,
                    'due_date' => $issue->due_date?->format('M d, Y'),
                    'remaining_hours' => round($issue->remaining_estimate_seconds / 3600, 1),
                    'original_hours' => $issue->original_estimate_seconds ? round($issue->original_estimate_seconds / 3600, 1) : null,
                    'jira_url' => $issue->jira_url,
                ];
            })
            ->toArray();

        // Monthly logged hours (last 6 months)
        $monthlyHours = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = now()->subMonths($i);
            $monthStartDate = $monthDate->copy()->startOfMonth();
            $monthEndDate = $monthDate->copy()->endOfMonth();

            $hours = JiraWorklog::where('employee_id', $employeeId)
                ->whereBetween('worklog_started', [$monthStartDate, $monthEndDate])
                ->sum('time_spent_hours');

            $monthlyHours[] = [
                'month' => $monthDate->format('M Y'),
                'hours' => round($hours, 1),
            ];
        }

        $monthlyTotal = JiraWorklog::where('employee_id', $employeeId)
            ->whereBetween('worklog_started', [$monthStart, $monthEnd])
            ->sum('time_spent_hours');

        // Upcoming leaves (approved and pending)
        $upcomingLeaves = [];
        if (class_exists(\Modules\Leave\Models\LeaveRecord::class)) {
            try {
                $upcomingLeaves = \Modules\Leave\Models\LeaveRecord::where('employee_id', $employeeId)
                    ->whereIn('status', ['approved', 'pending'])
                    ->where('start_date', '>=', today())
                    ->where('start_date', '<=', today()->addDays(60))
                    ->orderBy('start_date')
                    ->with('leavePolicy')
                    ->get()
                    ->map(function ($leave) {
                        $days = $leave->start_date->diffInDays($leave->end_date) + 1;
                        return [
                            'id' => $leave->id,
                            'type' => $leave->leavePolicy->name ?? 'Leave',
                            'start_date' => $leave->start_date->format('M d, Y'),
                            'end_date' => $leave->end_date->format('M d, Y'),
                            'days' => $days,
                            'status' => $leave->status,
                        ];
                    })
                    ->toArray();
            } catch (\Exception $e) {
                // Leave module might not be available or has errors
            }
        }

        // Period totals
        $periodLoggedHours = JiraWorklog::where('employee_id', $employeeId)
            ->whereBetween('worklog_started', [$startDate, $endDate])
            ->sum('time_spent_hours');

        $periodScheduledHours = JiraIssue::where('assignee_employee_id', $employeeId)
            ->whereIn('status', ['To Do', 'In Progress', 'Pending'])
            ->whereNotNull('remaining_estimate_seconds')
            ->where('remaining_estimate_seconds', '>', 0)
            ->whereBetween('due_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('remaining_estimate_seconds') / 3600;

        // Count of active tasks
        $taskCount = JiraIssue::where('assignee_employee_id', $employeeId)
            ->whereIn('status', ['To Do', 'In Progress', 'Pending'])
            ->count();

        return response()->json([
            'success' => true,
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'position' => $employee->position,
                'department' => $employee->department?->name,
                'avatar' => $employee->avatar_url,
            ],
            'period' => [
                'start' => $startDate->format('M d, Y'),
                'end' => $endDate->format('M d, Y'),
                'logged_hours' => round($periodLoggedHours, 1),
                'scheduled_hours' => round($periodScheduledHours, 1),
                'capacity' => 60,
                'task_count' => $taskCount,
            ],
            'recent_tasks' => $recentWorklogs,
            'scheduled_tasks' => $scheduledTasks,
            'monthly_hours' => $monthlyHours,
            'upcoming_leaves' => $upcomingLeaves,
        ]);
    }
}
