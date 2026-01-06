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
        $url = rtrim($this->baseUrl, '/') . '/rest/api/3/search/jql';

        $allIssues = [];
        $nextPageToken = null;

        do {
            $requestBody = [
                'jql' => "project = {$projectCode} ORDER BY updated DESC",
                'maxResults' => min(100, $maxResults - count($allIssues)),
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
            ];

            if ($nextPageToken) {
                $requestBody['nextPageToken'] = $nextPageToken;
            }

            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->timeout(60)
                ->post($url, $requestBody);

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

            $nextPageToken = $data['nextPageToken'] ?? null;

        } while ($nextPageToken && count($allIssues) < $maxResults);

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

        // Try to match assignee to employee (by Jira account ID first, then by email)
        $assigneeEmail = $fields['assignee']['emailAddress'] ?? null;
        $assigneeAccountId = $fields['assignee']['accountId'] ?? null;
        $assigneeEmployeeId = $this->matchAssigneeToEmployee($assigneeAccountId, $assigneeEmail);

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
     * Match a Jira assignee to an employee by account ID or email.
     */
    public function matchAssigneeToEmployee(?string $accountId, ?string $email): ?int
    {
        // First try to match by Jira account ID (most reliable)
        if ($accountId) {
            $employee = Employee::where('jira_account_id', $accountId)->first();
            if ($employee) {
                return $employee->id;
            }
        }

        // Fallback to email matching
        if ($email) {
            $employee = Employee::where('email', $email)
                ->orWhere('personal_email', $email)
                ->first();
            if ($employee) {
                return $employee->id;
            }
        }

        return null;
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
     * Get issues for Kanban view grouped by actual status.
     */
    public function getIssuesForKanban(Project $project): array
    {
        $issues = $project->jiraIssues()
            ->with('assignee')
            ->orderBy('jira_updated_at', 'desc')
            ->get();

        // Group by actual status
        $grouped = $issues->groupBy('status');

        // Get unique statuses with their categories for ordering
        $statusOrder = $project->jiraIssues()
            ->select('status', 'status_category')
            ->distinct()
            ->get()
            ->sortBy(function ($item) {
                // Order: new (To Do) -> indeterminate (In Progress) -> done
                $categoryOrder = ['new' => 0, 'indeterminate' => 1, 'done' => 2];
                return $categoryOrder[$item->status_category] ?? 1;
            });

        $columns = [];
        foreach ($statusOrder as $statusInfo) {
            $status = $statusInfo->status;
            $columns[$status] = [
                'name' => $status,
                'category' => $statusInfo->status_category,
                'issues' => $grouped->get($status, collect())->values(),
            ];
        }

        return $columns;
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

    /**
     * Create a new issue in Jira and sync it back.
     */
    public function createIssueInJira(Project $project, array $data): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Jira is not configured. Please configure Jira settings first.');
        }

        if (empty($project->code)) {
            throw new \Exception('Project has no Jira project key/code.');
        }

        $url = rtrim($this->baseUrl, '/') . '/rest/api/3/issue';

        // Build the request body
        $issueData = [
            'fields' => [
                'project' => [
                    'key' => $project->code,
                ],
                'summary' => $data['summary'],
                'issuetype' => [
                    'name' => $data['issue_type'] ?? 'Task',
                ],
            ],
        ];

        // Add description if provided (Jira API v3 uses ADF format)
        if (!empty($data['description'])) {
            $issueData['fields']['description'] = [
                'type' => 'doc',
                'version' => 1,
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $data['description'],
                            ],
                        ],
                    ],
                ],
            ];
        }

        // Add priority if provided
        if (!empty($data['priority'])) {
            $issueData['fields']['priority'] = [
                'name' => $data['priority'],
            ];
        }

        // Add due date if provided
        if (!empty($data['due_date'])) {
            $issueData['fields']['duedate'] = $data['due_date'];
        }

        // Add assignee if provided (need account ID)
        if (!empty($data['assignee_account_id'])) {
            $issueData['fields']['assignee'] = [
                'accountId' => $data['assignee_account_id'],
            ];
        }

        // Add labels if provided
        if (!empty($data['labels']) && is_array($data['labels'])) {
            $issueData['fields']['labels'] = $data['labels'];
        }

        Log::info('Creating Jira issue', [
            'project' => $project->code,
            'summary' => $data['summary'],
        ]);

        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->timeout(30)
            ->post($url, $issueData);

        if (!$response->successful()) {
            $errorBody = $response->json();
            $errorMessage = $errorBody['errors'] ?? $errorBody['errorMessages'] ?? $response->body();
            Log::error('Jira API error creating issue', [
                'status' => $response->status(),
                'body' => $errorMessage,
            ]);
            throw new \Exception('Failed to create issue in Jira: ' . json_encode($errorMessage));
        }

        $createdIssue = $response->json();
        $issueKey = $createdIssue['key'];

        Log::info('Jira issue created', [
            'issue_key' => $issueKey,
            'issue_id' => $createdIssue['id'],
        ]);

        // Sync the created issue back to our system
        $localIssue = $this->syncIssue($issueKey);

        return [
            'success' => true,
            'issue_key' => $issueKey,
            'issue_id' => $createdIssue['id'],
            'jira_url' => rtrim($this->baseUrl, '/') . '/browse/' . $issueKey,
            'local_issue' => $localIssue,
        ];
    }

    /**
     * Get available issue types for a project from Jira.
     */
    public function getProjectIssueTypes(string $projectCode): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $url = rtrim($this->baseUrl, '/') . '/rest/api/3/project/' . $projectCode;

        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->timeout(30)
            ->get($url);

        if (!$response->successful()) {
            return ['Task', 'Bug', 'Story', 'Epic']; // Default fallback
        }

        $projectData = $response->json();

        // Get issue types from project
        $issueTypes = [];
        if (isset($projectData['issueTypes'])) {
            foreach ($projectData['issueTypes'] as $type) {
                if (!($type['subtask'] ?? false)) { // Exclude subtasks
                    $issueTypes[] = $type['name'];
                }
            }
        }

        return !empty($issueTypes) ? $issueTypes : ['Task', 'Bug', 'Story', 'Epic'];
    }

    /**
     * Get available priorities from Jira.
     */
    public function getPriorities(): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $url = rtrim($this->baseUrl, '/') . '/rest/api/3/priority';

        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->timeout(30)
            ->get($url);

        if (!$response->successful()) {
            return ['Highest', 'High', 'Medium', 'Low', 'Lowest']; // Default fallback
        }

        return collect($response->json())->pluck('name')->toArray();
    }

    /**
     * Get assignable users for a project from Jira.
     */
    public function getAssignableUsers(string $projectCode): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $url = rtrim($this->baseUrl, '/') . '/rest/api/3/user/assignable/search';

        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->timeout(30)
            ->get($url, [
                'project' => $projectCode,
                'maxResults' => 100,
            ]);

        if (!$response->successful()) {
            return [];
        }

        return collect($response->json())->map(function ($user) {
            return [
                'account_id' => $user['accountId'],
                'display_name' => $user['displayName'],
                'email' => $user['emailAddress'] ?? null,
                'avatar_url' => $user['avatarUrls']['24x24'] ?? null,
            ];
        })->toArray();
    }

    /**
     * Update an issue field in Jira.
     */
    public function updateIssueField(string $issueKey, string $field, $value): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Jira is not configured.');
        }

        $url = rtrim($this->baseUrl, '/') . '/rest/api/3/issue/' . $issueKey;

        // Build the update payload based on field type
        $updateData = $this->buildUpdatePayload($field, $value);

        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->timeout(30)
            ->put($url, $updateData);

        if (!$response->successful()) {
            $errorBody = $response->json();
            $errorMessage = $errorBody['errors'] ?? $errorBody['errorMessages'] ?? $response->body();
            Log::error('Jira API error updating issue', [
                'issue_key' => $issueKey,
                'field' => $field,
                'status' => $response->status(),
                'body' => $errorMessage,
            ]);
            throw new \Exception('Failed to update issue: ' . json_encode($errorMessage));
        }

        // Sync the updated issue back
        $localIssue = $this->syncIssue($issueKey);

        return [
            'success' => true,
            'issue_key' => $issueKey,
            'field' => $field,
            'local_issue' => $localIssue,
        ];
    }

    /**
     * Build the update payload for Jira API.
     */
    protected function buildUpdatePayload(string $field, $value): array
    {
        $payload = ['fields' => []];

        switch ($field) {
            case 'assignee':
                $payload['fields']['assignee'] = $value ? ['accountId' => $value] : null;
                break;

            case 'priority':
                $payload['fields']['priority'] = $value ? ['name' => $value] : null;
                break;

            case 'due_date':
            case 'duedate':
                $payload['fields']['duedate'] = $value ?: null;
                break;

            case 'story_points':
                // Story points is usually customfield_10016, but can vary
                $payload['fields']['customfield_10016'] = $value ? (float) $value : null;
                break;

            case 'summary':
                $payload['fields']['summary'] = $value;
                break;

            case 'description':
                $payload['fields']['description'] = $value ? [
                    'type' => 'doc',
                    'version' => 1,
                    'content' => [
                        [
                            'type' => 'paragraph',
                            'content' => [['type' => 'text', 'text' => $value]],
                        ],
                    ],
                ] : null;
                break;

            case 'labels':
                $payload['fields']['labels'] = is_array($value) ? $value : [];
                break;

            default:
                $payload['fields'][$field] = $value;
        }

        return $payload;
    }

    /**
     * Transition an issue to a new status.
     */
    public function transitionIssue(string $issueKey, string $transitionId): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Jira is not configured.');
        }

        $url = rtrim($this->baseUrl, '/') . '/rest/api/3/issue/' . $issueKey . '/transitions';

        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->timeout(30)
            ->post($url, [
                'transition' => ['id' => $transitionId],
            ]);

        if (!$response->successful()) {
            $errorBody = $response->json();
            $errorMessage = $errorBody['errors'] ?? $errorBody['errorMessages'] ?? $response->body();
            Log::error('Jira API error transitioning issue', [
                'issue_key' => $issueKey,
                'transition_id' => $transitionId,
                'status' => $response->status(),
                'body' => $errorMessage,
            ]);
            throw new \Exception('Failed to transition issue: ' . json_encode($errorMessage));
        }

        // Sync the updated issue back
        $localIssue = $this->syncIssue($issueKey);

        return [
            'success' => true,
            'issue_key' => $issueKey,
            'local_issue' => $localIssue,
        ];
    }

    /**
     * Get available transitions for an issue.
     */
    public function getIssueTransitions(string $issueKey): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $url = rtrim($this->baseUrl, '/') . '/rest/api/3/issue/' . $issueKey . '/transitions';

        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->timeout(30)
            ->get($url);

        if (!$response->successful()) {
            return [];
        }

        $transitions = $response->json()['transitions'] ?? [];

        return collect($transitions)->map(function ($transition) {
            return [
                'id' => $transition['id'],
                'name' => $transition['name'],
                'to_status' => $transition['to']['name'] ?? $transition['name'],
                'to_category' => $transition['to']['statusCategory']['key'] ?? 'indeterminate',
            ];
        })->toArray();
    }

    /**
     * Get issue details with all fields.
     */
    public function getIssueDetails(string $issueKey): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $url = rtrim($this->baseUrl, '/') . '/rest/api/3/issue/' . $issueKey;

        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->timeout(30)
            ->get($url, [
                'fields' => 'summary,description,status,issuetype,priority,assignee,reporter,parent,customfield_10014,customfield_10016,duedate,labels,components,created,updated,comment',
            ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();
        $fields = $data['fields'];

        return [
            'key' => $data['key'],
            'id' => $data['id'],
            'summary' => $fields['summary'] ?? '',
            'description' => $this->extractDescription($fields['description'] ?? null),
            'status' => $fields['status']['name'] ?? 'Unknown',
            'status_category' => $fields['status']['statusCategory']['key'] ?? 'indeterminate',
            'issue_type' => $fields['issuetype']['name'] ?? 'Task',
            'priority' => $fields['priority']['name'] ?? null,
            'assignee' => $fields['assignee'] ? [
                'account_id' => $fields['assignee']['accountId'],
                'display_name' => $fields['assignee']['displayName'],
                'avatar_url' => $fields['assignee']['avatarUrls']['24x24'] ?? null,
            ] : null,
            'reporter' => $fields['reporter'] ? [
                'display_name' => $fields['reporter']['displayName'],
            ] : null,
            'epic_key' => $fields['customfield_10014'] ?? null,
            'story_points' => $fields['customfield_10016'] ?? null,
            'due_date' => $fields['duedate'] ?? null,
            'labels' => $fields['labels'] ?? [],
            'components' => array_map(fn($c) => $c['name'], $fields['components'] ?? []),
            'created_at' => $fields['created'] ?? null,
            'updated_at' => $fields['updated'] ?? null,
            'comments_count' => $fields['comment']['total'] ?? 0,
        ];
    }
}
