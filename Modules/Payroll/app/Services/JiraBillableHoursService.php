<?php

namespace Modules\Payroll\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\HR\Models\Employee;
use Modules\Payroll\Models\BillableHour;
use Modules\Payroll\Models\JiraSyncLog;
use Modules\Payroll\Models\JiraSetting;
use Modules\Payroll\Models\JiraWorklog;

class JiraBillableHoursService
{
    protected $baseUrl;
    protected $email;
    protected $apiToken;
    protected $billableProjects;
    protected $settings;

    public function __construct()
    {
        // Try to load from database first, fall back to config
        $this->settings = JiraSetting::getInstance();

        if ($this->settings->isConfigured()) {
            $this->baseUrl = $this->settings->base_url;
            $this->email = $this->settings->email;
            $this->apiToken = $this->settings->api_token;
            $this->billableProjects = $this->settings->billable_projects_array;
        } else {
            // Fall back to config for backwards compatibility
            $this->baseUrl = config('services.jira.base_url');
            $this->email = config('services.jira.email');
            $this->apiToken = config('services.jira.api_token');
            $this->billableProjects = array_filter(array_map('trim', explode(',', config('services.jira.billable_projects', ''))));
        }
    }

    /**
     * Sync billable hours from Jira for a given date range
     */
    public function syncBillableHours(Carbon $startDate, Carbon $endDate)
    {
        // Check if sync is already in progress
        if (JiraSyncLog::isInProgress()) {
            throw new \Exception('A sync is already in progress');
        }

        // Create sync log
        $syncLog = JiraSyncLog::create([
            'sync_date' => now()->toDateString(),
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        try {
            $results = $this->fetchAndProcessWorklogs($startDate, $endDate);

            $syncLog->updateProgress($results['imported'], $results['failed']);
            $syncLog->markAsCompleted();

            return $results;
        } catch (\Exception $e) {
            $syncLog->markAsFailed(['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Fetch and process worklogs from Jira - imports individual worklogs to JiraWorklog table
     */
    protected function fetchAndProcessWorklogs(Carbon $startDate, Carbon $endDate)
    {
        $importedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;
        $errors = [];

        // Get all employees with Jira account IDs mapped to their IDs
        $employees = Employee::whereNotNull('jira_account_id')
            ->where('status', 'active')
            ->get();

        // If no employees are mapped, return early with helpful message
        if ($employees->isEmpty()) {
            Log::warning("Jira sync: No employees have jira_account_id mapped");
            return [
                'imported' => 0,
                'skipped' => 0,
                'failed' => 0,
                'total_mapped' => 0,
                'errors' => [],
                'message' => 'No employees are linked to Jira accounts. Please map Jira users to employees first.',
            ];
        }

        // Create a map of Jira account ID to employee
        $employeeMap = $employees->keyBy('jira_account_id');

        Log::info("Jira sync: Found {$employees->count()} employees with Jira account IDs");

        // Fetch all worklogs for the date range
        $allWorklogs = $this->fetchAllWorklogs($startDate, $endDate);

        Log::info("Jira sync: Found " . count($allWorklogs) . " total worklogs in date range");

        // Get current sync log ID
        $syncLog = JiraSyncLog::where('status', 'in_progress')->latest()->first();
        $syncLogId = $syncLog?->id;

        foreach ($allWorklogs as $worklogData) {
            $authorAccountId = $worklogData['author']['accountId'] ?? null;

            // Skip if author is not a mapped employee
            if (!$authorAccountId || !isset($employeeMap[$authorAccountId])) {
                continue;
            }

            $employee = $employeeMap[$authorAccountId];

            try {
                $worklogStarted = Carbon::parse($worklogData['started']);

                // Try to create the worklog (will fail silently if duplicate due to unique constraint)
                $created = JiraWorklog::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'issue_key' => $worklogData['issueKey'],
                        'worklog_started' => $worklogStarted,
                    ],
                    [
                        'jira_author_name' => $worklogData['author']['displayName'] ?? 'Unknown',
                        'issue_summary' => $worklogData['issueSummary'] ?? '',
                        'worklog_created' => isset($worklogData['created']) ? Carbon::parse($worklogData['created']) : $worklogStarted,
                        'timezone' => $worklogData['author']['timeZone'] ?? null,
                        'time_spent_hours' => round(($worklogData['timeSpentSeconds'] ?? 0) / 3600, 2),
                        'comment' => $worklogData['comment']['content'][0]['content'][0]['text'] ?? null,
                        'sync_log_id' => $syncLogId,
                    ]
                );

                if ($created->wasRecentlyCreated) {
                    $importedCount++;
                } else {
                    $skippedCount++;
                }
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = [
                    'issue' => $worklogData['issueKey'] ?? 'Unknown',
                    'employee' => $employee->name,
                    'error' => $e->getMessage(),
                ];
                Log::error("Failed to import worklog: " . $e->getMessage());
            }
        }

        Log::info("Jira sync complete: {$importedCount} imported, {$skippedCount} skipped, {$failedCount} failed");

        return [
            'imported' => $importedCount,
            'skipped' => $skippedCount,
            'failed' => $failedCount,
            'total_mapped' => $employees->count(),
            'errors' => $errors,
        ];
    }

    /**
     * Fetch all worklogs for a date range (all users)
     */
    protected function fetchAllWorklogs(Carbon $startDate, Carbon $endDate): array
    {
        $allWorklogs = [];
        $nextPageToken = null;

        // Build JQL to get all issues with worklogs in the date range
        $jql = "worklogDate >= '{$startDate->format('Y-m-d')}' AND worklogDate <= '{$endDate->format('Y-m-d')}'";

        // Filter by billable projects if configured
        if (!empty($this->billableProjects)) {
            $projectsString = implode("','", $this->billableProjects);
            $jql .= " AND project in ('{$projectsString}')";
        }

        do {
            $requestBody = [
                'jql' => $jql,
                'fields' => ['summary', 'worklog'],
                'maxResults' => 100,
            ];

            if ($nextPageToken) {
                $requestBody['nextPageToken'] = $nextPageToken;
            }

            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->post("{$this->baseUrl}/rest/api/3/search/jql", $requestBody);

            if (!$response->successful()) {
                throw new \Exception("Jira API error: " . $response->body());
            }

            $data = $response->json();

            // Process issues and extract worklogs
            foreach ($data['issues'] ?? [] as $issue) {
                $issueKey = $issue['key'];
                $issueSummary = $issue['fields']['summary'] ?? '';
                $worklogs = $issue['fields']['worklog']['worklogs'] ?? [];

                // If there are more worklogs than returned, fetch them all
                $totalWorklogs = $issue['fields']['worklog']['total'] ?? 0;
                if ($totalWorklogs > count($worklogs)) {
                    $worklogs = $this->fetchAllWorklogsForIssue($issueKey);
                }

                foreach ($worklogs as $worklog) {
                    $worklogDate = Carbon::parse($worklog['started']);

                    // Only include worklogs within the date range
                    if ($worklogDate->between($startDate, $endDate)) {
                        $worklog['issueKey'] = $issueKey;
                        $worklog['issueSummary'] = $issueSummary;
                        $allWorklogs[] = $worklog;
                    }
                }
            }

            $nextPageToken = $data['nextPageToken'] ?? null;
        } while ($nextPageToken);

        return $allWorklogs;
    }

    /**
     * Fetch worklogs for a specific employee from Jira
     */
    protected function fetchEmployeeWorklogs($jiraAccountId, Carbon $startDate, Carbon $endDate)
    {
        $worklogs = [];
        $nextPageToken = null;

        do {
            // JQL query to get issues with worklogs by the user
            $jql = $this->buildJqlQuery($jiraAccountId, $startDate, $endDate);

            $requestBody = [
                'jql' => $jql,
                'fields' => ['worklog'],
                'maxResults' => 100,
            ];

            if ($nextPageToken) {
                $requestBody['nextPageToken'] = $nextPageToken;
            }

            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->post("{$this->baseUrl}/rest/api/3/search/jql", $requestBody);

            if (!$response->successful()) {
                throw new \Exception("Jira API error: " . $response->body());
            }

            $data = $response->json();

            // Process issues and extract worklogs
            foreach ($data['issues'] ?? [] as $issue) {
                if (isset($issue['fields']['worklog']['worklogs'])) {
                    foreach ($issue['fields']['worklog']['worklogs'] as $worklog) {
                        if ($this->isWorklogInRange($worklog, $jiraAccountId, $startDate, $endDate)) {
                            $worklogs[] = $worklog;
                        }
                    }
                }
            }

            $nextPageToken = $data['nextPageToken'] ?? null;
        } while ($nextPageToken);

        return $worklogs;
    }

    /**
     * Build JQL query for fetching issues
     */
    protected function buildJqlQuery($jiraAccountId, Carbon $startDate, Carbon $endDate)
    {
        $jql = "worklogAuthor = '{$jiraAccountId}' AND worklogDate >= '{$startDate->format('Y-m-d')}' AND worklogDate <= '{$endDate->format('Y-m-d')}'";

        // Filter by billable projects if configured (empty = all projects)
        if (!empty($this->billableProjects)) {
            $projectsString = implode("','", $this->billableProjects);
            $jql .= " AND project in ('{$projectsString}')";
        }

        return $jql;
    }

    /**
     * Check if a worklog is within the date range and by the specified user
     */
    protected function isWorklogInRange($worklog, $jiraAccountId, Carbon $startDate, Carbon $endDate)
    {
        $worklogDate = Carbon::parse($worklog['started']);
        $authorAccountId = $worklog['author']['accountId'] ?? null;

        return $authorAccountId === $jiraAccountId &&
               $worklogDate->between($startDate, $endDate);
    }

    /**
     * Calculate total hours from worklogs
     */
    protected function calculateTotalHours($worklogs)
    {
        $totalSeconds = 0;

        foreach ($worklogs as $worklog) {
            $totalSeconds += $worklog['timeSpentSeconds'] ?? 0;
        }

        // Convert seconds to hours with 2 decimal places
        return round($totalSeconds / 3600, 2);
    }

    /**
     * Test Jira connection
     */
    public function testConnection()
    {
        try {
            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->get("{$this->baseUrl}/rest/api/3/myself");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Jira connection test failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get Jira user by email
     */
    public function getUserByEmail($email)
    {
        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->get("{$this->baseUrl}/rest/api/3/user/search", [
                'query' => $email,
            ]);

        if ($response->successful()) {
            $users = $response->json();
            return !empty($users) ? $users[0] : null;
        }

        return null;
    }

    /**
     * Check if Jira is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->baseUrl) && !empty($this->email) && !empty($this->apiToken);
    }

    /**
     * Fetch all Jira users who have logged worklogs in a date range
     */
    public function fetchWorklogAuthors(Carbon $startDate, Carbon $endDate): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Jira is not configured');
        }

        $authors = [];
        $nextPageToken = null;

        // Build JQL to get all issues with worklogs in the date range
        $jql = "worklogDate >= '{$startDate->format('Y-m-d')}' AND worklogDate <= '{$endDate->format('Y-m-d')}'";

        // Filter by billable projects if configured
        if (!empty($this->billableProjects)) {
            $projectsString = implode("','", $this->billableProjects);
            $jql .= " AND project in ('{$projectsString}')";
        }

        do {
            $requestBody = [
                'jql' => $jql,
                'fields' => ['worklog'],
                'maxResults' => 100,
            ];

            if ($nextPageToken) {
                $requestBody['nextPageToken'] = $nextPageToken;
            }

            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->post("{$this->baseUrl}/rest/api/3/search/jql", $requestBody);

            if (!$response->successful()) {
                throw new \Exception("Jira API error: " . $response->body());
            }

            $data = $response->json();

            // Process issues and extract unique authors
            foreach ($data['issues'] ?? [] as $issue) {
                $worklogs = $issue['fields']['worklog']['worklogs'] ?? [];

                // If there are more worklogs than returned, fetch them all
                $totalWorklogs = $issue['fields']['worklog']['total'] ?? 0;
                if ($totalWorklogs > count($worklogs)) {
                    $worklogs = $this->fetchAllWorklogsForIssue($issue['key']);
                }

                foreach ($worklogs as $worklog) {
                    $worklogDate = Carbon::parse($worklog['started']);
                    if (!$worklogDate->between($startDate, $endDate)) {
                        continue;
                    }

                    $author = $worklog['author'] ?? null;
                    if ($author && isset($author['accountId'])) {
                        $accountId = $author['accountId'];
                        if (!isset($authors[$accountId])) {
                            $authors[$accountId] = [
                                'accountId' => $accountId,
                                'displayName' => $author['displayName'] ?? 'Unknown',
                                'emailAddress' => $author['emailAddress'] ?? null,
                                'avatarUrl' => $author['avatarUrls']['48x48'] ?? null,
                                'totalHours' => 0,
                            ];
                        }
                        $authors[$accountId]['totalHours'] += ($worklog['timeSpentSeconds'] ?? 0) / 3600;
                    }
                }
            }

            $nextPageToken = $data['nextPageToken'] ?? null;
        } while ($nextPageToken);

        // Round hours and sort by name
        foreach ($authors as &$author) {
            $author['totalHours'] = round($author['totalHours'], 2);
        }

        usort($authors, fn($a, $b) => strcasecmp($a['displayName'], $b['displayName']));

        return array_values($authors);
    }

    /**
     * Fetch all worklogs for a specific issue
     */
    protected function fetchAllWorklogsForIssue(string $issueKey): array
    {
        $worklogs = [];
        $startAt = 0;
        $maxResults = 100;

        do {
            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->get("{$this->baseUrl}/rest/api/3/issue/{$issueKey}/worklog", [
                    'startAt' => $startAt,
                    'maxResults' => $maxResults,
                ]);

            if (!$response->successful()) {
                break;
            }

            $data = $response->json();
            $worklogs = array_merge($worklogs, $data['worklogs'] ?? []);
            $startAt += $maxResults;
        } while ($startAt < ($data['total'] ?? 0));

        return $worklogs;
    }

    /**
     * Get all assignable users from Jira (for linking)
     */
    public function getAllUsers(): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Jira is not configured');
        }

        $users = [];
        $startAt = 0;
        $maxResults = 50;

        do {
            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->get("{$this->baseUrl}/rest/api/3/users/search", [
                    'startAt' => $startAt,
                    'maxResults' => $maxResults,
                ]);

            if (!$response->successful()) {
                break;
            }

            $data = $response->json();
            foreach ($data as $user) {
                if (($user['accountType'] ?? '') === 'atlassian') {
                    $users[] = [
                        'accountId' => $user['accountId'],
                        'displayName' => $user['displayName'] ?? 'Unknown',
                        'emailAddress' => $user['emailAddress'] ?? null,
                        'avatarUrl' => $user['avatarUrls']['48x48'] ?? null,
                    ];
                }
            }

            $startAt += $maxResults;
        } while (count($data) === $maxResults);

        usort($users, fn($a, $b) => strcasecmp($a['displayName'], $b['displayName']));

        return $users;
    }
}