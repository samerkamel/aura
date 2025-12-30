<?php

namespace Modules\Project\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Payroll\Models\JiraWorklog;
use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectFollowup;

class ProjectFollowupService
{
    /**
     * Thresholds for follow-up status (in days).
     */
    protected const OPTIMAL_FOLLOWUP_DAYS = 7;
    protected const MAX_FOLLOWUP_DAYS = 14;
    protected const DUE_SOON_DAYS = 3;

    /**
     * Get projects needing follow-up.
     * Returns projects that:
     * - Are active AND
     * - Have recent activity (worklogs in past 30 days) OR
     * - Have follow-up status as overdue or due_soon
     */
    public function getProjectsNeedingFollowup(): Collection
    {
        // Get project codes with recent worklogs
        $activeProjectCodes = JiraWorklog::where('worklog_started', '>=', now()->subDays(30))
            ->selectRaw('SUBSTRING_INDEX(issue_key, "-", 1) as project_code')
            ->distinct()
            ->pluck('project_code');

        return Project::where('is_active', true)
            ->where(function ($query) use ($activeProjectCodes) {
                $query->whereIn('code', $activeProjectCodes)
                    ->orWhereIn('followup_status', ['overdue', 'due_soon']);
            })
            ->with(['customer', 'latestFollowup'])
            ->orderByRaw("FIELD(followup_status, 'overdue', 'due_soon', 'none', 'up_to_date')")
            ->orderBy('next_followup_date')
            ->get();
    }

    /**
     * Get all projects with their follow-up status for the follow-ups page.
     */
    public function getFollowupDashboard(): array
    {
        $projects = $this->getProjectsNeedingFollowup();

        $overdue = $projects->where('followup_status', 'overdue');
        $dueSoon = $projects->where('followup_status', 'due_soon');
        $upToDate = $projects->where('followup_status', 'up_to_date');
        $none = $projects->where('followup_status', 'none');

        return [
            'projects' => $projects,
            'summary' => [
                'overdue' => $overdue->count(),
                'due_soon' => $dueSoon->count(),
                'up_to_date' => $upToDate->count(),
                'no_followups' => $none->count(),
                'total' => $projects->count(),
            ],
        ];
    }

    /**
     * Log a new follow-up.
     */
    public function logFollowup(Project $project, array $data): ProjectFollowup
    {
        $followup = $project->followups()->create([
            'user_id' => auth()->id(),
            'type' => $data['type'],
            'notes' => $data['notes'],
            'contact_person' => $data['contact_person'] ?? null,
            'outcome' => $data['outcome'] ?? 'neutral',
            'followup_date' => $data['followup_date'] ?? now()->toDateString(),
            'next_followup_date' => $data['next_followup_date'] ?? null,
        ]);

        // Update project follow-up fields
        $this->updateProjectFollowupStatus($project, $followup);

        return $followup;
    }

    /**
     * Update a project's follow-up status based on latest follow-up.
     */
    public function updateProjectFollowupStatus(Project $project, ?ProjectFollowup $latestFollowup = null): void
    {
        if (!$latestFollowup) {
            $latestFollowup = $project->latestFollowup;
        }

        if (!$latestFollowup) {
            $project->update([
                'last_followup_date' => null,
                'next_followup_date' => null,
                'followup_status' => 'none',
            ]);
            return;
        }

        $nextDate = $latestFollowup->next_followup_date;
        $lastDate = $latestFollowup->followup_date;

        // If no next date set, calculate based on optimal interval
        if (!$nextDate) {
            $nextDate = Carbon::parse($lastDate)->addDays(self::OPTIMAL_FOLLOWUP_DAYS);
        }

        $status = $this->calculateFollowupStatus($lastDate, $nextDate);

        $project->update([
            'last_followup_date' => $lastDate,
            'next_followup_date' => $nextDate,
            'followup_status' => $status,
        ]);
    }

    /**
     * Calculate follow-up status based on dates.
     */
    protected function calculateFollowupStatus($lastDate, $nextDate): string
    {
        $today = now()->startOfDay();
        $nextDateCarbon = Carbon::parse($nextDate)->startOfDay();
        $lastDateCarbon = Carbon::parse($lastDate)->startOfDay();

        // If next follow-up date has passed
        if ($nextDateCarbon->lt($today)) {
            return 'overdue';
        }

        // If next follow-up is within DUE_SOON_DAYS
        if ($nextDateCarbon->lte($today->copy()->addDays(self::DUE_SOON_DAYS))) {
            return 'due_soon';
        }

        // If last follow-up was more than MAX_FOLLOWUP_DAYS ago
        if ($lastDateCarbon->lt($today->copy()->subDays(self::MAX_FOLLOWUP_DAYS))) {
            return 'overdue';
        }

        return 'up_to_date';
    }

    /**
     * Update follow-up statuses for all projects.
     * Should be called via scheduler daily.
     */
    public function updateAllFollowupStatuses(): int
    {
        $updated = 0;
        $projects = Project::where('is_active', true)
            ->whereNotNull('last_followup_date')
            ->get();

        foreach ($projects as $project) {
            $oldStatus = $project->followup_status;
            $newStatus = $this->calculateFollowupStatus(
                $project->last_followup_date,
                $project->next_followup_date ?? Carbon::parse($project->last_followup_date)->addDays(self::OPTIMAL_FOLLOWUP_DAYS)
            );

            if ($oldStatus !== $newStatus) {
                $project->update(['followup_status' => $newStatus]);
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Get follow-up history for a project.
     */
    public function getProjectFollowupHistory(Project $project, int $limit = 10): Collection
    {
        return $project->followups()
            ->with('user')
            ->orderByDesc('followup_date')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent activity summary for a project.
     */
    public function getProjectActivitySummary(Project $project): array
    {
        $recentWorklogs = JiraWorklog::where('issue_key', 'LIKE', $project->code . '-%')
            ->where('worklog_started', '>=', now()->subDays(30))
            ->count();

        $lastWorklog = JiraWorklog::where('issue_key', 'LIKE', $project->code . '-%')
            ->orderByDesc('worklog_started')
            ->first();

        return [
            'recent_worklog_count' => $recentWorklogs,
            'last_activity_date' => $lastWorklog?->worklog_started,
            'days_since_activity' => $lastWorklog ? now()->diffInDays($lastWorklog->worklog_started) : null,
            'has_recent_activity' => $recentWorklogs > 0,
        ];
    }
}
