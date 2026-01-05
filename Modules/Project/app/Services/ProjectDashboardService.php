<?php

namespace Modules\Project\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectMilestone;
use Modules\Project\Models\ProjectRisk;
use Modules\Project\Models\ProjectTimeEstimate;

/**
 * Service for aggregating project dashboard data.
 *
 * Provides comprehensive project overview including:
 * - Financial metrics
 * - Progress tracking
 * - Team utilization
 * - Risk indicators
 * - Milestone status
 */
class ProjectDashboardService
{
    protected ProjectFinancialService $financialService;

    public function __construct(ProjectFinancialService $financialService)
    {
        $this->financialService = $financialService;
    }

    /**
     * Get complete dashboard data for a project.
     */
    public function getDashboardData(Project $project): array
    {
        return [
            'overview' => $this->getOverview($project),
            'financial' => $this->financialService->getFinancialDashboard($project),
            'progress' => $this->getProgressMetrics($project),
            'team' => $this->getTeamMetrics($project),
            'milestones' => $this->getMilestoneMetrics($project),
            'risks' => $this->getRiskMetrics($project),
            'timeEstimates' => $this->getTimeEstimateMetrics($project),
            'activity' => $this->getRecentActivity($project),
            'alerts' => $this->getAlerts($project),
        ];
    }

    /**
     * Get project overview with key indicators.
     */
    public function getOverview(Project $project): array
    {
        $healthColor = match ($project->health_status) {
            'green' => 'success',
            'yellow' => 'warning',
            'red' => 'danger',
            default => 'secondary',
        };

        return [
            'id' => $project->id,
            'name' => $project->name,
            'code' => $project->code,
            'customer' => $project->customer?->display_name,
            'customer_id' => $project->customer_id,
            'project_manager' => $project->projectManager?->name,
            'project_manager_id' => $project->project_manager_id,
            'description' => $project->description,
            'status' => $project->is_active ? 'active' : 'inactive',
            'health_status' => $project->health_status,
            'health_label' => $project->health_status_label,
            'health_color' => $healthColor,
            'health_score' => $project->current_health_score,
            'phase' => $project->phase,
            'phase_label' => $project->phase_label,
            'priority' => $project->priority,
            'priority_label' => $project->priority_label,
            'priority_color' => $project->priority_color,
            'billing_type' => $project->billing_type,
            'billing_label' => $project->billing_type_label,
            'currency' => $project->currency ?? 'EGP',
            'planned_start' => $project->planned_start_date?->format('Y-m-d'),
            'planned_end' => $project->planned_end_date?->format('Y-m-d'),
            'actual_start' => $project->actual_start_date?->format('Y-m-d'),
            'actual_end' => $project->actual_end_date?->format('Y-m-d'),
            'days_until_deadline' => $project->days_until_deadline,
            'is_overdue' => $project->isOverdue(),
            'timeline_progress' => $project->timeline_progress,
            'completion_percentage' => $project->completion_percentage ?? 0,
            'jira_project_id' => $project->jira_project_id,
            'followup_status' => $project->followup_status,
            'followup_color' => $project->followup_status_color,
            'next_followup' => $project->next_followup_date?->format('Y-m-d'),
        ];
    }

    /**
     * Get progress metrics for a project.
     */
    public function getProgressMetrics(Project $project): array
    {
        $timelineProgress = $project->timeline_progress ?? 0;
        $workProgress = $project->completion_percentage ?? 0;

        // Calculate schedule performance index (SPI)
        // SPI = Work Progress / Timeline Progress
        // SPI > 1 = ahead of schedule, SPI < 1 = behind schedule
        $spi = $timelineProgress > 0 ? round($workProgress / $timelineProgress, 2) : 1;

        $scheduleStatus = 'on_track';
        $scheduleColor = 'success';
        if ($spi < 0.8) {
            $scheduleStatus = 'behind';
            $scheduleColor = 'danger';
        } elseif ($spi < 0.95) {
            $scheduleStatus = 'at_risk';
            $scheduleColor = 'warning';
        } elseif ($spi > 1.1) {
            $scheduleStatus = 'ahead';
            $scheduleColor = 'info';
        }

        // Get issue summary if Jira integrated
        $issueSummary = $project->issue_summary ?? ['todo' => 0, 'in_progress' => 0, 'done' => 0, 'total' => 0];

        // Calculate issue completion percentage
        $issueCompletion = $issueSummary['total'] > 0
            ? round(($issueSummary['done'] / $issueSummary['total']) * 100, 1)
            : 0;

        return [
            'timeline_progress' => $timelineProgress,
            'work_progress' => $workProgress,
            'schedule_performance_index' => $spi,
            'schedule_status' => $scheduleStatus,
            'schedule_color' => $scheduleColor,
            'days_elapsed' => $this->getDaysElapsed($project),
            'days_remaining' => $this->getDaysRemaining($project),
            'total_days' => $this->getTotalDays($project),
            'is_overdue' => $project->isOverdue(),
            'issues' => $issueSummary,
            'issue_completion' => $issueCompletion,
            'estimated_hours' => $project->estimated_hours ?? 0,
            'budgeted_hours' => $project->budgeted_hours ?? 0,
            'actual_hours' => $project->total_hours ?? 0,
            'hours_utilization' => $project->budgeted_hours > 0
                ? round(($project->total_hours / $project->budgeted_hours) * 100, 1)
                : 0,
        ];
    }

    /**
     * Get team metrics for a project.
     */
    public function getTeamMetrics(Project $project): array
    {
        $employees = $project->employees()
            ->with('user')
            ->get();

        $teamSize = $employees->count();
        $totalAllocation = $employees->sum('pivot.allocation_percentage');
        $averageAllocation = $teamSize > 0 ? round($totalAllocation / $teamSize, 1) : 0;

        // Get hours by team member (last 30 days)
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $hoursByMember = DB::table('jira_worklogs')
            ->where('issue_key', 'LIKE', $project->code . '-%')
            ->where('worklog_started', '>=', $thirtyDaysAgo)
            ->whereNotNull('employee_id')
            ->select('employee_id', DB::raw('SUM(time_spent_hours) as hours'))
            ->groupBy('employee_id')
            ->pluck('hours', 'employee_id')
            ->toArray();

        $teamMembers = $employees->map(function ($employee) use ($hoursByMember) {
            $recentHours = $hoursByMember[$employee->id] ?? 0;
            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'role' => $employee->pivot->role ?? 'member',
                'allocation' => $employee->pivot->allocation_percentage ?? 0,
                'recent_hours' => round($recentHours, 1),
                'hourly_rate' => $employee->pivot->hourly_rate,
                'avatar' => $employee->user?->profile_photo_path,
            ];
        })->sortByDesc('recent_hours')->values();

        return [
            'team_size' => $teamSize,
            'required_size' => $project->required_team_size ?? 0,
            'total_allocation' => $totalAllocation,
            'average_allocation' => $averageAllocation,
            'project_manager' => $project->projectManager ? [
                'id' => $project->projectManager->id,
                'name' => $project->projectManager->name,
            ] : null,
            'members' => $teamMembers->take(10)->toArray(),
            'top_contributors' => $teamMembers->take(5)->toArray(),
        ];
    }

    /**
     * Get milestone metrics for a project.
     */
    public function getMilestoneMetrics(Project $project): array
    {
        $milestones = $project->milestones()->get();

        $total = $milestones->count();
        $completed = $milestones->where('status', 'completed')->count();
        $inProgress = $milestones->where('status', 'in_progress')->count();
        $overdue = $milestones->filter(fn($m) => $m->isOverdue())->count();
        $upcoming = $milestones->filter(fn($m) => $m->isUpcoming())->count();

        $completionPercentage = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

        // Get next milestone
        $nextMilestone = $milestones
            ->where('status', '!=', 'completed')
            ->sortBy('due_date')
            ->first();

        return [
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'pending' => $total - $completed - $inProgress,
            'overdue' => $overdue,
            'upcoming' => $upcoming,
            'completion_percentage' => $completionPercentage,
            'next_milestone' => $nextMilestone ? [
                'id' => $nextMilestone->id,
                'name' => $nextMilestone->name,
                'due_date' => $nextMilestone->due_date?->format('Y-m-d'),
                'days_until' => $nextMilestone->due_date?->diffInDays(now(), false) * -1,
                'status' => $nextMilestone->status,
            ] : null,
            'recent' => $milestones->sortByDesc('due_date')->take(5)->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'due_date' => $m->due_date?->format('Y-m-d'),
                'status' => $m->status,
                'status_color' => $m->status_color,
            ])->values()->toArray(),
        ];
    }

    /**
     * Get risk metrics for a project.
     */
    public function getRiskMetrics(Project $project): array
    {
        $risks = $project->risks()->get();

        $total = $risks->count();
        $active = $risks->where('status', 'active')->count();
        $mitigated = $risks->where('status', 'mitigated')->count();
        $high = $risks->where('risk_level', 'high')->where('status', 'active')->count();
        $critical = $risks->where('risk_level', 'critical')->where('status', 'active')->count();

        // Calculate average risk score for active risks
        $activeRisks = $risks->where('status', 'active');
        $averageScore = $activeRisks->count() > 0
            ? round($activeRisks->avg('risk_score'), 1)
            : 0;

        // Risk level indicator
        $riskLevel = 'low';
        $riskColor = 'success';
        if ($critical > 0 || $averageScore >= 15) {
            $riskLevel = 'critical';
            $riskColor = 'danger';
        } elseif ($high > 0 || $averageScore >= 10) {
            $riskLevel = 'high';
            $riskColor = 'warning';
        } elseif ($averageScore >= 5) {
            $riskLevel = 'medium';
            $riskColor = 'info';
        }

        return [
            'total' => $total,
            'active' => $active,
            'mitigated' => $mitigated,
            'high' => $high,
            'critical' => $critical,
            'average_score' => $averageScore,
            'risk_level' => $riskLevel,
            'risk_color' => $riskColor,
            'top_risks' => $risks->where('status', 'active')
                ->sortByDesc('risk_score')
                ->take(5)
                ->map(fn($r) => [
                    'id' => $r->id,
                    'title' => $r->title,
                    'risk_level' => $r->risk_level,
                    'risk_score' => $r->risk_score,
                    'status' => $r->status,
                ])->values()->toArray(),
        ];
    }

    /**
     * Get time estimate metrics for a project.
     */
    public function getTimeEstimateMetrics(Project $project): array
    {
        $estimates = $project->timeEstimates()->get();

        $total = $estimates->count();
        $totalEstimatedHours = $estimates->sum('estimated_hours');
        $totalActualHours = $estimates->sum('actual_hours');
        $totalEstimatedCost = $estimates->sum('estimated_cost');
        $totalActualCost = $estimates->sum('actual_cost');

        // By status
        $byStatus = [
            'not_started' => $estimates->where('status', 'not_started')->count(),
            'in_progress' => $estimates->where('status', 'in_progress')->count(),
            'completed' => $estimates->where('status', 'completed')->count(),
            'on_hold' => $estimates->where('status', 'on_hold')->count(),
        ];

        // Variance analysis
        $overBudget = $estimates->filter(fn($e) => ($e->variance_hours ?? 0) > 0)->count();
        $underBudget = $estimates->filter(fn($e) => ($e->variance_hours ?? 0) < 0)->count();
        $onBudget = $total - $overBudget - $underBudget;

        // Calculate overall variance
        $hoursVariance = $totalActualHours - $totalEstimatedHours;
        $hoursVariancePercentage = $totalEstimatedHours > 0
            ? round(($hoursVariance / $totalEstimatedHours) * 100, 1)
            : 0;

        $costVariance = $totalActualCost - $totalEstimatedCost;
        $costVariancePercentage = $totalEstimatedCost > 0
            ? round(($costVariance / $totalEstimatedCost) * 100, 1)
            : 0;

        return [
            'total' => $total,
            'by_status' => $byStatus,
            'estimated_hours' => round($totalEstimatedHours, 1),
            'actual_hours' => round($totalActualHours, 1),
            'hours_variance' => round($hoursVariance, 1),
            'hours_variance_percentage' => $hoursVariancePercentage,
            'estimated_cost' => round($totalEstimatedCost, 2),
            'actual_cost' => round($totalActualCost, 2),
            'cost_variance' => round($costVariance, 2),
            'cost_variance_percentage' => $costVariancePercentage,
            'over_budget_count' => $overBudget,
            'under_budget_count' => $underBudget,
            'on_budget_count' => $onBudget,
            'completion_rate' => $total > 0
                ? round(($byStatus['completed'] / $total) * 100, 1)
                : 0,
        ];
    }

    /**
     * Get recent activity for a project.
     */
    public function getRecentActivity(Project $project, int $limit = 10): array
    {
        $activities = [];

        // Recent worklogs
        $worklogs = $project->worklogs()
            ->with('employee')
            ->orderByDesc('worklog_started')
            ->limit(5)
            ->get();

        foreach ($worklogs as $worklog) {
            $activities[] = [
                'type' => 'worklog',
                'icon' => 'ti-clock',
                'color' => 'primary',
                'title' => 'Time logged',
                'description' => ($worklog->employee?->name ?? 'Unknown') . ' logged ' .
                    round($worklog->time_spent_hours, 1) . 'h on ' . $worklog->issue_key,
                'date' => $worklog->worklog_started,
                'timestamp' => $worklog->worklog_started->timestamp,
            ];
        }

        // Recent revenues
        $revenues = $project->revenues()
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        foreach ($revenues as $revenue) {
            $activities[] = [
                'type' => 'revenue',
                'icon' => 'ti-currency-dollar',
                'color' => 'success',
                'title' => 'Revenue ' . $revenue->status,
                'description' => number_format($revenue->amount, 2) . ' ' . ($project->currency ?? 'EGP') .
                    ' - ' . $revenue->description,
                'date' => $revenue->created_at,
                'timestamp' => $revenue->created_at->timestamp,
            ];
        }

        // Recent costs
        $costs = $project->costs()
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        foreach ($costs as $cost) {
            $activities[] = [
                'type' => 'cost',
                'icon' => 'ti-receipt',
                'color' => 'warning',
                'title' => 'Cost recorded',
                'description' => number_format($cost->amount, 2) . ' ' . ($project->currency ?? 'EGP') .
                    ' - ' . $cost->description,
                'date' => $cost->created_at,
                'timestamp' => $cost->created_at->timestamp,
            ];
        }

        // Sort by timestamp and take limit
        usort($activities, fn($a, $b) => $b['timestamp'] - $a['timestamp']);

        return array_slice($activities, 0, $limit);
    }

    /**
     * Get alerts and warnings for a project.
     */
    public function getAlerts(Project $project): array
    {
        $alerts = [];

        // Check if overdue
        if ($project->isOverdue()) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'ti-alert-triangle',
                'title' => 'Project Overdue',
                'message' => 'This project is past its planned end date.',
            ];
        }

        // Check budget utilization
        $financial = $this->financialService->getFinancialSummary($project);
        if ($financial['budget_status'] === 'over') {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'ti-alert-circle',
                'title' => 'Budget Exceeded',
                'message' => 'Spending has exceeded the budget by ' .
                    number_format(abs($financial['budget_remaining']), 2),
            ];
        } elseif ($financial['budget_status'] === 'critical') {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'ti-alert-triangle',
                'title' => 'Budget Critical',
                'message' => 'Budget utilization is at ' . $financial['budget_utilization'] . '%',
            ];
        }

        // Check overdue milestones
        $overdueMilestones = $project->milestones()->overdue()->count();
        if ($overdueMilestones > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'ti-flag',
                'title' => 'Overdue Milestones',
                'message' => $overdueMilestones . ' milestone(s) are past their due date.',
            ];
        }

        // Check high risks
        $highRisks = $project->risks()->highRisk()->active()->count();
        if ($highRisks > 0) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'ti-alert-octagon',
                'title' => 'High Risk Alert',
                'message' => $highRisks . ' high/critical risk(s) require attention.',
            ];
        }

        // Check overdue payments
        $overduePayments = $this->financialService->getOverduePayments($project)->count();
        if ($overduePayments > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'ti-cash',
                'title' => 'Overdue Payments',
                'message' => $overduePayments . ' payment(s) are overdue.',
            ];
        }

        // Check follow-up status
        if ($project->followup_status === 'overdue') {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'ti-bell',
                'title' => 'Follow-up Overdue',
                'message' => 'Project follow-up is overdue. Schedule a check-in.',
            ];
        }

        return $alerts;
    }

    /**
     * Get summary stats for project list view.
     */
    public function getProjectListSummary(Project $project): array
    {
        // Cache expensive calculations
        $totalHours = $project->total_hours ?? 0;
        $totalRevenue = $project->revenues()->sum('amount');
        $receivedRevenue = $project->revenues()->sum('amount_received');
        $totalCosts = $this->financialService->getTotalProjectCosts($project);

        $grossProfit = $receivedRevenue - $totalCosts['total'];
        $grossMargin = $receivedRevenue > 0 ? round(($grossProfit / $receivedRevenue) * 100, 1) : 0;

        return [
            'id' => $project->id,
            'name' => $project->name,
            'code' => $project->code,
            'customer_name' => $project->customer?->display_name,
            'customer_id' => $project->customer_id,
            'is_active' => $project->is_active,
            'health_status' => $project->health_status,
            'health_color' => $project->health_status_color,
            'phase' => $project->phase,
            'phase_label' => $project->phase_label,
            'priority' => $project->priority,
            'priority_color' => $project->priority_color,
            'completion' => $project->completion_percentage ?? 0,
            'total_hours' => round($totalHours, 1),
            'total_revenue' => round($totalRevenue, 2),
            'received_revenue' => round($receivedRevenue, 2),
            'total_costs' => round($totalCosts['total'], 2),
            'gross_profit' => round($grossProfit, 2),
            'gross_margin' => $grossMargin,
            'is_profitable' => $grossProfit > 0,
            'team_size' => $project->employees()->count(),
            'open_issues' => $project->jiraIssues()->open()->count(),
            'days_until_deadline' => $project->days_until_deadline,
            'is_overdue' => $project->isOverdue(),
            'needs_monthly_report' => $project->needs_monthly_report,
            'jira_project_id' => $project->jira_project_id,
        ];
    }

    /**
     * Get portfolio-level statistics for all projects.
     */
    public function getPortfolioStats(Collection $projects): array
    {
        $totalProjects = $projects->count();
        $activeProjects = $projects->where('is_active', true)->count();

        $healthCounts = [
            'green' => $projects->where('health_status', 'green')->count(),
            'yellow' => $projects->where('health_status', 'yellow')->count(),
            'red' => $projects->where('health_status', 'red')->count(),
        ];

        // Financial aggregates
        $totalRevenue = 0;
        $totalCosts = 0;
        $totalHours = 0;

        foreach ($projects as $project) {
            $totalRevenue += $project->revenues()->sum('amount_received');
            $costs = $this->financialService->getTotalProjectCosts($project);
            $totalCosts += $costs['total'];
            $totalHours += $project->total_hours ?? 0;
        }

        $totalProfit = $totalRevenue - $totalCosts;
        $overallMargin = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0;

        return [
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'inactive_projects' => $totalProjects - $activeProjects,
            'health_distribution' => $healthCounts,
            'total_revenue' => round($totalRevenue, 2),
            'total_costs' => round($totalCosts, 2),
            'total_profit' => round($totalProfit, 2),
            'overall_margin' => $overallMargin,
            'total_hours' => round($totalHours, 1),
            'average_completion' => round($projects->avg('completion_percentage') ?? 0, 1),
            'overdue_count' => $projects->filter(fn($p) => $p->isOverdue())->count(),
        ];
    }

    /**
     * Get portfolio stats using optimized database aggregation.
     * Uses single queries instead of N+1 queries.
     */
    public function getPortfolioStatsOptimized(string $status, $user): array
    {
        // Build base query for accessible projects
        $baseQuery = Project::accessibleByUser($user);

        if ($status === 'active') {
            $baseQuery->where('is_active', true);
        } elseif ($status === 'inactive') {
            $baseQuery->where('is_active', false);
        }

        // Get project counts and aggregates in single query
        $stats = (clone $baseQuery)->selectRaw('
            COUNT(*) as total_projects,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_projects,
            SUM(CASE WHEN health_status = "green" THEN 1 ELSE 0 END) as health_green,
            SUM(CASE WHEN health_status = "yellow" THEN 1 ELSE 0 END) as health_yellow,
            SUM(CASE WHEN health_status = "red" THEN 1 ELSE 0 END) as health_red,
            AVG(COALESCE(completion_percentage, 0)) as avg_completion,
            SUM(CASE WHEN planned_end_date < CURDATE() AND COALESCE(completion_percentage, 0) < 100 THEN 1 ELSE 0 END) as overdue_count
        ')->first();

        // Get project IDs and codes for subsequent queries
        $projectIds = (clone $baseQuery)->pluck('id');
        $projectCodes = (clone $baseQuery)->pluck('code');

        // Get revenue totals in single query
        $revenueStats = DB::table('project_revenues')
            ->whereIn('project_id', $projectIds)
            ->selectRaw('COALESCE(SUM(amount_received), 0) as total_revenue')
            ->first();

        // Get total hours from worklogs
        $totalHours = 0;
        if ($projectCodes->isNotEmpty()) {
            $worklogStats = DB::table('jira_worklogs')
                ->where(function ($q) use ($projectCodes) {
                    foreach ($projectCodes as $code) {
                        $q->orWhere('issue_key', 'LIKE', $code . '-%');
                    }
                })
                ->selectRaw('COALESCE(SUM(time_spent_hours), 0) as total_hours')
                ->first();

            $totalHours = $worklogStats->total_hours ?? 0;
        }

        // Calculate labor costs using ProjectFinancialService for accuracy
        // This includes the proper formula: (Salary/BillableHours) * WorkedHours * Multiplier + PM Overhead
        $financialService = app(ProjectFinancialService::class);
        $totalLaborCosts = 0;
        $totalPmOverhead = 0;

        // Get projects for labor cost calculation
        $projects = (clone $baseQuery)->get();
        foreach ($projects as $project) {
            $laborCosts = $financialService->calculateLaborCostsFromWorklogs($project);
            $totalLaborCosts += $laborCosts['subtotal'];
            $totalPmOverhead += $laborCosts['pm_overhead'];
        }

        // Get direct costs (non-labor) from project_costs table
        $directCosts = DB::table('project_costs')
            ->whereIn('project_id', $projectIds)
            ->where('cost_type', '!=', 'labor') // Exclude labor since we calculate it dynamically
            ->sum('amount') ?? 0;

        $totalCosts = $totalLaborCosts + $totalPmOverhead + $directCosts;
        $totalRevenue = $revenueStats->total_revenue ?? 0;
        $totalProfit = $totalRevenue - $totalCosts;
        $overallMargin = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0;

        return [
            'total_projects' => (int) ($stats->total_projects ?? 0),
            'active_projects' => (int) ($stats->active_projects ?? 0),
            'inactive_projects' => (int) (($stats->total_projects ?? 0) - ($stats->active_projects ?? 0)),
            'health_distribution' => [
                'green' => (int) ($stats->health_green ?? 0),
                'yellow' => (int) ($stats->health_yellow ?? 0),
                'red' => (int) ($stats->health_red ?? 0),
            ],
            'total_revenue' => round($totalRevenue, 2),
            'total_costs' => round($totalCosts, 2),
            'labor_costs' => round($totalLaborCosts, 2),
            'pm_overhead' => round($totalPmOverhead, 2),
            'direct_costs' => round($directCosts, 2),
            'total_profit' => round($totalProfit, 2),
            'overall_margin' => $overallMargin,
            'total_hours' => round($totalHours, 1),
            'average_completion' => round($stats->avg_completion ?? 0, 1),
            'overdue_count' => (int) ($stats->overdue_count ?? 0),
        ];
    }

    /**
     * Get days elapsed since project start.
     */
    private function getDaysElapsed(Project $project): ?int
    {
        $startDate = $project->actual_start_date ?? $project->planned_start_date;
        if (!$startDate) {
            return null;
        }
        return max(0, $startDate->diffInDays(now()));
    }

    /**
     * Get days remaining until project end.
     */
    private function getDaysRemaining(Project $project): ?int
    {
        if (!$project->planned_end_date) {
            return null;
        }
        return max(0, now()->diffInDays($project->planned_end_date, false));
    }

    /**
     * Get total project duration in days.
     */
    private function getTotalDays(Project $project): ?int
    {
        $startDate = $project->actual_start_date ?? $project->planned_start_date;
        if (!$startDate || !$project->planned_end_date) {
            return null;
        }
        return max(1, $startDate->diffInDays($project->planned_end_date));
    }
}
