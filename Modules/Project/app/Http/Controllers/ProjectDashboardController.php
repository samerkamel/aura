<?php

namespace Modules\Project\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Payroll\Models\JiraWorklog;
use Modules\Project\Models\Project;
use Modules\Project\Services\ProjectHealthService;

class ProjectDashboardController extends Controller
{
    public function __construct(
        protected ProjectHealthService $healthService
    ) {
    }

    /**
     * Display the project dashboard.
     */
    public function index(Project $project): View
    {
        if (!Gate::allows('view-project', $project)) {
            abort(403, 'You do not have permission to view this project.');
        }

        $project->load(['customer', 'projectManager', 'latestHealthSnapshot']);

        $summary = $this->healthService->getDashboardSummary($project);

        // Get team members with hours logged
        $teamHours = JiraWorklog::where('issue_key', 'LIKE', $project->code . '-%')
            ->whereNotNull('employee_id')
            ->selectRaw('employee_id, SUM(time_spent_hours) as total_hours')
            ->groupBy('employee_id')
            ->with('employee')
            ->orderByDesc('total_hours')
            ->limit(10)
            ->get();

        // Issue summary by type
        $issuesByType = $project->jiraIssues()
            ->selectRaw('issue_type, COUNT(*) as count')
            ->groupBy('issue_type')
            ->pluck('count', 'issue_type')
            ->toArray();

        // Recent follow-ups
        $recentFollowups = $project->followups()
            ->with('user')
            ->limit(5)
            ->get();

        return view('project::projects.dashboard', compact(
            'project',
            'summary',
            'teamHours',
            'issuesByType',
            'recentFollowups'
        ));
    }

    /**
     * Recalculate and refresh health score.
     */
    public function refreshHealth(Project $project): JsonResponse
    {
        if (!Gate::allows('view-project', $project)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $snapshot = $this->healthService->createSnapshot($project);

            return response()->json([
                'success' => true,
                'health' => [
                    'overall' => $snapshot->health_score,
                    'budget' => $snapshot->budget_score,
                    'schedule' => $snapshot->schedule_score,
                    'scope' => $snapshot->scope_score,
                    'quality' => $snapshot->quality_score,
                    'status' => $snapshot->status,
                    'status_color' => $snapshot->status_color,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get health trend data for chart.
     */
    public function healthTrend(Request $request, Project $project): JsonResponse
    {
        if (!Gate::allows('view-project', $project)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $days = $request->input('days', 30);
        $trend = $this->healthService->getHealthTrend($project, $days);

        return response()->json($trend);
    }

    /**
     * Get activity feed for project.
     */
    public function activityFeed(Request $request, Project $project): JsonResponse
    {
        if (!Gate::allows('view-project', $project)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $limit = $request->input('limit', 20);

        // Combine worklogs, follow-ups, and issue updates
        $activities = collect();

        // Recent worklogs
        $worklogs = JiraWorklog::where('issue_key', 'LIKE', $project->code . '-%')
            ->with('employee')
            ->orderByDesc('worklog_started')
            ->limit($limit)
            ->get()
            ->map(function ($worklog) {
                return [
                    'type' => 'worklog',
                    'date' => $worklog->worklog_started,
                    'description' => ($worklog->employee?->name ?? 'Unknown') . ' logged ' .
                        round($worklog->time_spent_hours, 1) . 'h on ' . $worklog->issue_key,
                    'icon' => 'ti-clock',
                    'color' => 'info',
                ];
            });
        $activities = $activities->merge($worklogs);

        // Recent follow-ups
        $followups = $project->followups()
            ->with('user')
            ->limit($limit)
            ->get()
            ->map(function ($followup) {
                return [
                    'type' => 'followup',
                    'date' => $followup->followup_date,
                    'description' => ($followup->user?->name ?? 'Unknown') . ' added a follow-up: ' .
                        \Str::limit($followup->notes, 50),
                    'icon' => 'ti-message-circle',
                    'color' => 'primary',
                ];
            });
        $activities = $activities->merge($followups);

        // Sort by date and limit
        $activities = $activities->sortByDesc('date')->take($limit)->values();

        return response()->json($activities);
    }

    /**
     * Get team performance metrics.
     */
    public function teamPerformance(Request $request, Project $project): JsonResponse
    {
        if (!Gate::allows('view-project', $project)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $teamData = JiraWorklog::where('issue_key', 'LIKE', $project->code . '-%')
            ->whereBetween('worklog_started', [$startDate, $endDate])
            ->whereNotNull('employee_id')
            ->with('employee')
            ->selectRaw('employee_id, SUM(time_spent_hours) as total_hours, COUNT(*) as worklog_count')
            ->groupBy('employee_id')
            ->orderByDesc('total_hours')
            ->get()
            ->map(function ($item) {
                return [
                    'employee_id' => $item->employee_id,
                    'name' => $item->employee?->name ?? 'Unknown',
                    'total_hours' => round($item->total_hours, 1),
                    'worklog_count' => $item->worklog_count,
                    'avg_hours_per_day' => round($item->total_hours / max(1, now()->diffInWeekdays(now()->startOfMonth())), 1),
                ];
            });

        return response()->json($teamData);
    }
}
