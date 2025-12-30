<?php

namespace Modules\Project\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\HR\Models\Employee;
use Modules\Payroll\Models\JiraSetting;
use Modules\Project\Models\JiraIssue;
use Modules\Project\Models\Project;

class JiraIssueSyncService
{
    protected $baseUrl;
    protected $email;
    protected $apiToken;
    protected $settings;

    public function __construct()
    {
        $this->settings = JiraSetting::getInstance();

        if ($this->settings->isConfigured()) {
            $this->baseUrl = $this->settings->base_url;
            $this->email = $this->settings->email;
            $this->apiToken = $this->settings->api_token;
        } else {
            $this->baseUrl = config('services.jira.base_url');
            $this->email = config('services.jira.email');
            $this->apiToken = config('services.jira.api_token');
        }
    }

    /**
     * Check if the service is properly configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->baseUrl) && !empty($this->email) && !empty($this->apiToken);
    }

    /**
     * Sync all issues for a project.
     */
    public function syncProjectIssues(Project $project, int $maxResults = 500): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Jira is not configured. Please configure Jira settings first.');
        }

        if (empty($project->code)) {
            throw new \Exception('Project has no Jira code.');
        }

        $issues = $this->fetchProjectIssues($project->code, $maxResults);

        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($issues as $jiraIssue) {
            try {
                $result = $this->createOrUpdateIssue($project, $jiraIssue);
                if ($result === 'created') {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (\Exception $e) {
                $errors[] = "Issue {$jiraIssue['key']}: " . $e->getMessage();
                Log::error("Jira issue sync error for {$jiraIssue['key']}: " . $e->getMessage());
            }
        }

        return [
            'total' => count($issues),
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    /**
     * Fetch issues from Jira API using JQL.
     */
    public function fetchProjectIssues(string $projectCode, int $maxResults = 500): array
    {
        $url = rtrim($this->baseUrl, '/') . '/rest/api/3/search';

        $allIssues = [];
        $startAt = 0;
        $batchSize = 100;

        do {
            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->timeout(60)
                ->post($url, [
                    'jql' => "project = {$projectCode} ORDER BY updated DESC",
                    'maxResults' => min($batchSize, $maxResults - count($allIssues)),
                    'startAt' => $startAt,
                    'fields' => [
                        'summary',
                        'description',
                        'status',
                        'issuetype',
                        'priority',
                        'assignee',
                        'reporter',
                        'parent',
                        'customfield_10014', // Epic link (common field)
                        'customfield_10016', // Story points (common field)
                        'duedate',
                        'labels',
                        'components',
                        'created',
                        'updated',
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('Jira API error fetching issues', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to fetch issues from Jira: ' . $response->status());
            }

            $data = $response->json();
            $issues = $data['issues'] ?? [];
            $allIssues = array_merge($allIssues, $issues);

            $startAt += count($issues);
            $total = $data['total'] ?? 0;

        } while (count($issues) === $batchSize && count($allIssues) < $maxResults && $startAt < $total);

        return $allIssues;
    }

    /**
     * Create or update a Jira issue in the database.
     */
    public function createOrUpdateIssue(Project $project, array $jiraIssue): string
    {
        $fields = $jiraIssue['fields'];

        // Extract status category
        $statusCategory = $fields['status']['statusCategory']['key'] ?? 'new';

        // Map Jira status category to our simplified categories
        $statusCategoryMap = [
            'new' => 'new',
            'undefined' => 'new',
            'indeterminate' => 'indeterminate',
            'done' => 'done',
        ];
        $normalizedCategory = $statusCategoryMap[$statusCategory] ?? 'new';

        // Try to match assignee to employee
        $assigneeEmail = $fields['assignee']['emailAddress'] ?? null;
        $assigneeEmployeeId = null;
        if ($assigneeEmail) {
            $assigneeEmployeeId = $this->matchAssigneeToEmployee($assigneeEmail);
        }

        // Extract epic key if present
        $epicKey = $fields['customfield_10014'] ?? null;
        if (is_array($epicKey)) {
            $epicKey = $epicKey['key'] ?? null;
        }

        $data = [
            'project_id' => $project->id,
            'jira_issue_id' => $jiraIssue['id'],
            'issue_key' => $jiraIssue['key'],
            'summary' => $fields['summary'] ?? 'No Summary',
            'description' => $this->extractDescription($fields['description'] ?? null),
            'issue_type' => $fields['issuetype']['name'] ?? 'Task',
            'status' => $fields['status']['name'] ?? 'Unknown',
            'status_category' => $normalizedCategory,
            'priority' => $fields['priority']['name'] ?? null,
            'assignee_email' => $assigneeEmail,
            'assignee_employee_id' => $assigneeEmployeeId,
            'reporter_email' => $fields['reporter']['emailAddress'] ?? null,
            'parent_key' => $fields['parent']['key'] ?? null,
            'epic_key' => $epicKey,
            'story_points' => $fields['customfield_10016'] ?? null,
            'due_date' => $fields['duedate'] ?? null,
            'labels' => $fields['labels'] ?? [],
            'components' => array_map(fn($c) => $c['name'], $fields['components'] ?? []),
            'jira_created_at' => $fields['created'] ? Carbon::parse($fields['created']) : null,
            'jira_updated_at' => $fields['updated'] ? Carbon::parse($fields['updated']) : null,
            'last_synced_at' => now(),
        ];

        $existing = JiraIssue::where('jira_issue_id', $jiraIssue['id'])->first();

        if ($existing) {
            $existing->update($data);
            return 'updated';
        } else {
            JiraIssue::create($data);
            return 'created';
        }
    }

    /**
     * Extract plain text from Jira's document format description.
     */
    protected function extractDescription($description): ?string
    {
        if (empty($description)) {
            return null;
        }

        if (is_string($description)) {
            return $description;
        }

        // Jira API v3 returns description as a document object
        if (is_array($description) && isset($description['content'])) {
            return $this->extractTextFromContent($description['content']);
        }

        return null;
    }

    /**
     * Recursively extract text from Jira document content.
     */
    protected function extractTextFromContent(array $content): string
    {
        $text = '';
        foreach ($content as $block) {
            if (isset($block['text'])) {
                $text .= $block['text'];
            }
            if (isset($block['content'])) {
                $text .= $this->extractTextFromContent($block['content']);
            }
            if (isset($block['type']) && $block['type'] === 'paragraph') {
                $text .= "\n";
            }
        }
        return trim($text);
    }

    /**
     * Match a Jira assignee email to an employee.
     */
    public function matchAssigneeToEmployee(string $email): ?int
    {
        $employee = Employee::where('work_email', $email)
            ->orWhere('personal_email', $email)
            ->first();

        return $employee?->id;
    }

    /**
     * Get project issue summary (counts by status).
     */
    public function getProjectIssueSummary(Project $project): array
    {
        $issues = $project->jiraIssues()
            ->selectRaw('status_category, issue_type, COUNT(*) as count')
            ->groupBy('status_category', 'issue_type')
            ->get();

        $byStatus = [
            'new' => 0,
            'indeterminate' => 0,
            'done' => 0,
        ];

        $byType = [];

        foreach ($issues as $issue) {
            $byStatus[$issue->status_category] = ($byStatus[$issue->status_category] ?? 0) + $issue->count;
            $byType[$issue->issue_type] = ($byType[$issue->issue_type] ?? 0) + $issue->count;
        }

        $total = array_sum($byStatus);

        return [
            'total' => $total,
            'by_status' => [
                'todo' => $byStatus['new'],
                'in_progress' => $byStatus['indeterminate'],
                'done' => $byStatus['done'],
            ],
            'by_type' => $byType,
            'completion_percentage' => $total > 0 ? round(($byStatus['done'] / $total) * 100, 1) : 0,
            'open_count' => $byStatus['new'] + $byStatus['indeterminate'],
            'last_synced' => $project->jiraIssues()->max('last_synced_at'),
        ];
    }

    /**
     * Get issues for Kanban view.
     */
    public function getIssuesForKanban(Project $project): array
    {
        $issues = $project->jiraIssues()
            ->with('assignee')
            ->orderBy('jira_updated_at', 'desc')
            ->get();

        return [
            'todo' => $issues->where('status_category', 'new')->values(),
            'in_progress' => $issues->where('status_category', 'indeterminate')->values(),
            'done' => $issues->where('status_category', 'done')->values(),
        ];
    }

    /**
     * Get issues with filters.
     */
    public function getFilteredIssues(Project $project, array $filters = []): Collection
    {
        $query = $project->jiraIssues()->with('assignee');

        if (!empty($filters['status_category'])) {
            $query->where('status_category', $filters['status_category']);
        }

        if (!empty($filters['issue_type'])) {
            $query->where('issue_type', $filters['issue_type']);
        }

        if (!empty($filters['assignee_employee_id'])) {
            $query->where('assignee_employee_id', $filters['assignee_employee_id']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('issue_key', 'LIKE', "%{$search}%")
                    ->orWhere('summary', 'LIKE', "%{$search}%");
            });
        }

        $sortBy = $filters['sort_by'] ?? 'jira_updated_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query->orderBy($sortBy, $sortDir)->get();
    }

    /**
     * Sync single issue by key.
     */
    public function syncIssue(string $issueKey): ?JiraIssue
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Jira is not configured.');
        }

        $url = rtrim($this->baseUrl, '/') . '/rest/api/3/issue/' . $issueKey;

        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->timeout(30)
            ->get($url, [
                'fields' => 'summary,description,status,issuetype,priority,assignee,reporter,parent,customfield_10014,customfield_10016,duedate,labels,components,created,updated',
            ]);

        if (!$response->successful()) {
            Log::error('Jira API error fetching issue', [
                'issue_key' => $issueKey,
                'status' => $response->status(),
            ]);
            return null;
        }

        $jiraIssue = $response->json();
        $projectCode = explode('-', $issueKey)[0];
        $project = Project::where('code', $projectCode)->first();

        if (!$project) {
            return null;
        }

        $this->createOrUpdateIssue($project, $jiraIssue);

        return JiraIssue::where('issue_key', $issueKey)->first();
    }
}
