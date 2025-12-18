<?php

namespace Modules\Project\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Payroll\Models\JiraSetting;
use Modules\Project\Models\Project;

class JiraProjectSyncService
{
    protected $baseUrl;
    protected $email;
    protected $apiToken;
    protected $settings;

    public function __construct()
    {
        // Try to load from database first, fall back to config
        $this->settings = JiraSetting::getInstance();

        if ($this->settings->isConfigured()) {
            $this->baseUrl = $this->settings->base_url;
            $this->email = $this->settings->email;
            $this->apiToken = $this->settings->api_token;
        } else {
            // Fall back to config for backwards compatibility
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
     * Sync all projects from Jira.
     *
     * @return array Statistics about the sync operation.
     */
    public function syncProjects(): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Jira is not configured. Please configure Jira settings first.');
        }

        $jiraProjects = $this->fetchJiraProjects();

        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($jiraProjects as $jiraProject) {
            try {
                $result = $this->createOrUpdateProject($jiraProject);
                if ($result === 'created') {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (\Exception $e) {
                $errors[] = "Project {$jiraProject['key']}: " . $e->getMessage();
                Log::error("Jira project sync error for {$jiraProject['key']}: " . $e->getMessage());
            }
        }

        return [
            'total' => count($jiraProjects),
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    /**
     * Fetch all projects from Jira API.
     *
     * @return array List of Jira projects.
     */
    public function fetchJiraProjects(): array
    {
        $url = rtrim($this->baseUrl, '/') . '/rest/api/3/project';

        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->timeout(30)
            ->get($url);

        if (!$response->successful()) {
            Log::error('Jira API error fetching projects', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to fetch projects from Jira: ' . $response->status());
        }

        return $response->json() ?? [];
    }

    /**
     * Create or update a project from Jira data.
     *
     * @param array $jiraProject Jira project data.
     * @return string 'created' or 'updated'.
     */
    public function createOrUpdateProject(array $jiraProject): string
    {
        $existing = Project::where('code', $jiraProject['key'])->first();

        $data = [
            'name' => $jiraProject['name'],
            'code' => $jiraProject['key'],
            'jira_project_id' => $jiraProject['id'] ?? null,
        ];

        if ($existing) {
            $existing->update($data);
            return 'updated';
        } else {
            Project::create($data);
            return 'created';
        }
    }

    /**
     * Test the Jira connection.
     *
     * @return array Connection test result.
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Jira is not configured',
            ];
        }

        try {
            $url = rtrim($this->baseUrl, '/') . '/rest/api/3/myself';

            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->timeout(10)
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'Connected successfully',
                    'user' => $data['displayName'] ?? $data['emailAddress'] ?? 'Unknown',
                ];
            }

            return [
                'success' => false,
                'message' => 'Connection failed: ' . $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }
}
