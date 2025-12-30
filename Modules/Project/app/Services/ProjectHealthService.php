<?php

namespace Modules\Project\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Payroll\Models\JiraWorklog;
use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectHealthSnapshot;

class ProjectHealthService
{
    /**
     * Calculate overall health score for a project.
     */
    public function calculateHealthScore(Project $project): array
    {
        $budgetScore = $this->calculateBudgetScore($project);
        $scheduleScore = $this->calculateScheduleScore($project);
        $scopeScore = $this->calculateScopeScore($project);
        $qualityScore = $this->calculateQualityScore($project);

        // Weighted average (equal weights for now)
        $overallScore = ($budgetScore + $scheduleScore + $scopeScore + $qualityScore) / 4;

        return [
            'overall' => round($overallScore, 2),
            'budget' => round($budgetScore, 2),
            'schedule' => round($scheduleScore, 2),
            'scope' => round($scopeScore, 2),
            'quality' => round($qualityScore, 2),
            'status' => $this->getStatusFromScore($overallScore),
            'metrics' => [
                'budget' => $this->getBudgetMetrics($project),
                'schedule' => $this->getScheduleMetrics($project),
                'scope' => $this->getScopeMetrics($project),
                'quality' => $this->getQualityMetrics($project),
            ],
        ];
    }

    /**
     * Calculate budget score (0-100).
     * Based on actual spend vs planned budget.
     */
    protected function calculateBudgetScore(Project $project): float
    {
        if (!$project->planned_budget || $project->planned_budget <= 0) {
            return 75; // Default score if no budget set
        }

        $actualCost = $this->calculateActualCost($project);
        $budgetUtilization = ($actualCost / $project->planned_budget) * 100;

        // Score based on utilization
        // 0-80% = 100, 80-100% = linear decrease to 70, 100-120% = 70-40, >120% = below 40
        if ($budgetUtilization <= 80) {
            return 100;
        } elseif ($budgetUtilization <= 100) {
            return 100 - (($budgetUtilization - 80) * 1.5); // 100 -> 70
        } elseif ($budgetUtilization <= 120) {
            return 70 - (($budgetUtilization - 100) * 1.5); // 70 -> 40
        } else {
            return max(0, 40 - (($budgetUtilization - 120) * 0.5));
        }
    }

    /**
     * Calculate schedule score (0-100).
     * Based on timeline progress vs actual progress.
     */
    protected function calculateScheduleScore(Project $project): float
    {
        if (!$project->planned_start_date || !$project->planned_end_date) {
            return 75; // Default score if no dates set
        }

        $now = now();
        $totalDays = $project->planned_start_date->diffInDays($project->planned_end_date);

        if ($totalDays <= 0) {
            return 100;
        }

        // Check if project is completed
        if ($project->actual_end_date) {
            // Completed on time = 100, late = penalty
            $daysLate = $project->planned_end_date->diffInDays($project->actual_end_date, false);
            if ($daysLate <= 0) {
                return 100;
            }
            $latePenalty = min(60, $daysLate * 2); // Max 60 point penalty
            return max(40, 100 - $latePenalty);
        }

        // Project is ongoing
        $elapsedDays = $project->planned_start_date->diffInDays($now);
        $expectedProgress = min(100, ($elapsedDays / $totalDays) * 100);

        // Get actual progress from task completion
        $actualProgress = $this->getTaskCompletionRate($project);

        // Compare expected vs actual
        $progressDiff = $actualProgress - $expectedProgress;

        // Ahead of schedule = bonus, behind = penalty
        if ($progressDiff >= 0) {
            return min(100, 85 + ($progressDiff * 0.5));
        } else {
            return max(0, 85 + ($progressDiff * 1.5)); // Steeper penalty for being behind
        }
    }

    /**
     * Calculate scope score (0-100).
     * Based on task completion rate and scope changes.
     */
    protected function calculateScopeScore(Project $project): float
    {
        $metrics = $this->getScopeMetrics($project);

        if ($metrics['total_tasks'] === 0) {
            return 75; // Default score if no tasks
        }

        // Base score on completion rate
        $completionScore = $metrics['completion_rate'];

        // Adjust for task velocity (are we completing tasks steadily?)
        $velocityBonus = min(10, $metrics['tasks_completed_last_7_days'] * 2);

        return min(100, $completionScore + $velocityBonus);
    }

    /**
     * Calculate quality score (0-100).
     * Based on bug rate, overdue tasks, etc.
     */
    protected function calculateQualityScore(Project $project): float
    {
        $metrics = $this->getQualityMetrics($project);

        $score = 100;

        // Penalty for overdue tasks (up to 30 points)
        if ($metrics['total_open_tasks'] > 0) {
            $overdueRate = ($metrics['overdue_tasks'] / $metrics['total_open_tasks']) * 100;
            $score -= min(30, $overdueRate * 0.5);
        }

        // Penalty for high bug ratio (up to 20 points)
        if ($metrics['total_tasks'] > 0) {
            $bugRate = ($metrics['bug_count'] / $metrics['total_tasks']) * 100;
            $score -= min(20, $bugRate * 0.3);
        }

        // Bonus for having no critical/high priority bugs
        if ($metrics['high_priority_bugs'] === 0) {
            $score = min(100, $score + 5);
        }

        return max(0, $score);
    }

    /**
     * Get status string from score.
     */
    protected function getStatusFromScore(float $score): string
    {
        if ($score >= 70) {
            return 'green';
        } elseif ($score >= 40) {
            return 'yellow';
        }
        return 'red';
    }

    /**
     * Calculate actual cost based on logged hours.
     */
    protected function calculateActualCost(Project $project): float
    {
        $hourlyRate = $project->hourly_rate ?? 0;

        if ($hourlyRate <= 0) {
            return 0;
        }

        $totalHours = JiraWorklog::where('issue_key', 'LIKE', $project->code . '-%')
            ->sum('time_spent_hours');

        return $totalHours * $hourlyRate;
    }

    /**
     * Get task completion rate (0-100).
     */
    protected function getTaskCompletionRate(Project $project): float
    {
        $issues = $project->jiraIssues()
            ->selectRaw('status_category, COUNT(*) as count')
            ->groupBy('status_category')
            ->pluck('count', 'status_category')
            ->toArray();

        $total = array_sum($issues);
        if ($total === 0) {
            return 0;
        }

        $done = $issues['done'] ?? 0;
        return ($done / $total) * 100;
    }

    /**
     * Get budget-related metrics.
     */
    protected function getBudgetMetrics(Project $project): array
    {
        $actualCost = $this->calculateActualCost($project);
        $plannedBudget = $project->planned_budget ?? 0;
        $totalHours = JiraWorklog::where('issue_key', 'LIKE', $project->code . '-%')
            ->sum('time_spent_hours');

        return [
            'planned_budget' => $plannedBudget,
            'actual_cost' => round($actualCost, 2),
            'remaining_budget' => round($plannedBudget - $actualCost, 2),
            'utilization_percentage' => $plannedBudget > 0 ? round(($actualCost / $plannedBudget) * 100, 1) : 0,
            'total_hours_logged' => round($totalHours, 2),
            'hourly_rate' => $project->hourly_rate ?? 0,
        ];
    }

    /**
     * Get schedule-related metrics.
     */
    protected function getScheduleMetrics(Project $project): array
    {
        $now = now();
        $daysUntilDeadline = null;
        $daysElapsed = null;
        $totalDays = null;

        if ($project->planned_start_date && $project->planned_end_date) {
            $totalDays = $project->planned_start_date->diffInDays($project->planned_end_date);
            $daysElapsed = $project->planned_start_date->diffInDays($now);
            $daysUntilDeadline = $now->diffInDays($project->planned_end_date, false);
        }

        return [
            'planned_start_date' => $project->planned_start_date?->format('Y-m-d'),
            'planned_end_date' => $project->planned_end_date?->format('Y-m-d'),
            'actual_start_date' => $project->actual_start_date?->format('Y-m-d'),
            'actual_end_date' => $project->actual_end_date?->format('Y-m-d'),
            'total_days' => $totalDays,
            'days_elapsed' => $daysElapsed,
            'days_remaining' => $daysUntilDeadline,
            'is_overdue' => $project->isOverdue(),
            'timeline_progress' => $project->timeline_progress,
        ];
    }

    /**
     * Get scope-related metrics.
     */
    protected function getScopeMetrics(Project $project): array
    {
        $issues = $project->jiraIssues()
            ->selectRaw('status_category, COUNT(*) as count')
            ->groupBy('status_category')
            ->pluck('count', 'status_category')
            ->toArray();

        $total = array_sum($issues);
        $done = $issues['done'] ?? 0;
        $inProgress = $issues['indeterminate'] ?? 0;
        $todo = $issues['new'] ?? 0;

        // Tasks completed in last 7 days
        $completedRecently = $project->jiraIssues()
            ->where('status_category', 'done')
            ->where('jira_updated_at', '>=', now()->subDays(7))
            ->count();

        return [
            'total_tasks' => $total,
            'tasks_done' => $done,
            'tasks_in_progress' => $inProgress,
            'tasks_todo' => $todo,
            'completion_rate' => $total > 0 ? round(($done / $total) * 100, 1) : 0,
            'tasks_completed_last_7_days' => $completedRecently,
        ];
    }

    /**
     * Get quality-related metrics.
     */
    protected function getQualityMetrics(Project $project): array
    {
        $totalTasks = $project->jiraIssues()->count();
        $openTasks = $project->jiraIssues()->where('status_category', '!=', 'done')->count();

        $overdueTasks = $project->jiraIssues()
            ->where('status_category', '!=', 'done')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->count();

        $bugCount = $project->jiraIssues()
            ->where('issue_type', 'Bug')
            ->count();

        $highPriorityBugs = $project->jiraIssues()
            ->where('issue_type', 'Bug')
            ->whereIn('priority', ['Highest', 'High', 'Critical'])
            ->where('status_category', '!=', 'done')
            ->count();

        return [
            'total_tasks' => $totalTasks,
            'total_open_tasks' => $openTasks,
            'overdue_tasks' => $overdueTasks,
            'bug_count' => $bugCount,
            'high_priority_bugs' => $highPriorityBugs,
            'overdue_rate' => $openTasks > 0 ? round(($overdueTasks / $openTasks) * 100, 1) : 0,
        ];
    }

    /**
     * Create a health snapshot for a project.
     */
    public function createSnapshot(Project $project, ?Carbon $date = null): ProjectHealthSnapshot
    {
        $date = $date ?? now();
        $healthData = $this->calculateHealthScore($project);

        $snapshot = ProjectHealthSnapshot::updateOrCreate(
            [
                'project_id' => $project->id,
                'snapshot_date' => $date->toDateString(),
            ],
            [
                'health_score' => $healthData['overall'],
                'budget_score' => $healthData['budget'],
                'schedule_score' => $healthData['schedule'],
                'scope_score' => $healthData['scope'],
                'quality_score' => $healthData['quality'],
                'metrics' => $healthData['metrics'],
            ]
        );

        // Update project's current health
        $project->update([
            'current_health_score' => $healthData['overall'],
            'health_status' => $healthData['status'],
        ]);

        return $snapshot;
    }

    /**
     * Create snapshots for all active projects.
     */
    public function createSnapshotsForAllProjects(): array
    {
        $projects = Project::active()->get();
        $results = [
            'total' => $projects->count(),
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($projects as $project) {
            try {
                $this->createSnapshot($project);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "{$project->name}: {$e->getMessage()}";
                Log::error("Failed to create health snapshot for project {$project->id}: {$e->getMessage()}");
            }
        }

        return $results;
    }

    /**
     * Get health trend data for a project.
     */
    public function getHealthTrend(Project $project, int $days = 30): array
    {
        $snapshots = $project->healthSnapshots()
            ->where('snapshot_date', '>=', now()->subDays($days))
            ->orderBy('snapshot_date')
            ->get();

        return [
            'dates' => $snapshots->pluck('snapshot_date')->map(fn($d) => $d->format('M d'))->toArray(),
            'overall' => $snapshots->pluck('health_score')->toArray(),
            'budget' => $snapshots->pluck('budget_score')->toArray(),
            'schedule' => $snapshots->pluck('schedule_score')->toArray(),
            'scope' => $snapshots->pluck('scope_score')->toArray(),
            'quality' => $snapshots->pluck('quality_score')->toArray(),
        ];
    }

    /**
     * Get dashboard summary for a project.
     */
    public function getDashboardSummary(Project $project): array
    {
        $healthData = $this->calculateHealthScore($project);
        $trend = $this->getHealthTrend($project, 30);

        // Get recent activity
        $recentWorklogs = JiraWorklog::where('issue_key', 'LIKE', $project->code . '-%')
            ->where('worklog_started', '>=', now()->subDays(7))
            ->with('employee')
            ->orderByDesc('worklog_started')
            ->limit(10)
            ->get();

        // Hours this week vs last week
        $hoursThisWeek = JiraWorklog::where('issue_key', 'LIKE', $project->code . '-%')
            ->whereBetween('worklog_started', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('time_spent_hours');

        $hoursLastWeek = JiraWorklog::where('issue_key', 'LIKE', $project->code . '-%')
            ->whereBetween('worklog_started', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->sum('time_spent_hours');

        // Upcoming deadlines (issues with due dates in next 7 days)
        $upcomingDeadlines = $project->jiraIssues()
            ->where('status_category', '!=', 'done')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        return [
            'health' => $healthData,
            'trend' => $trend,
            'recent_worklogs' => $recentWorklogs,
            'hours_this_week' => round($hoursThisWeek, 1),
            'hours_last_week' => round($hoursLastWeek, 1),
            'hours_change' => $hoursLastWeek > 0
                ? round((($hoursThisWeek - $hoursLastWeek) / $hoursLastWeek) * 100, 1)
                : 0,
            'upcoming_deadlines' => $upcomingDeadlines,
        ];
    }
}
