<?php

namespace Modules\Project\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Project\Models\BitbucketSetting;
use Modules\Project\Models\BitbucketCommit;
use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectBitbucketRepository;

class BitbucketService
{
    protected $baseUrl = 'https://api.bitbucket.org/2.0';
    protected $email;
    protected $apiToken;
    protected $workspace;
    protected $settings;

    public function __construct()
    {
        $this->settings = BitbucketSetting::getInstance();

        if ($this->settings->isConfigured()) {
            $this->email = $this->settings->email;
            $this->apiToken = $this->settings->api_token;
            $this->workspace = $this->settings->workspace;
        }
    }

    /**
     * Check if the service is properly configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->email) && !empty($this->apiToken) && !empty($this->workspace);
    }

    /**
     * Make an authenticated request to Bitbucket API.
     */
    protected function request(string $method, string $endpoint, array $options = [])
    {
        $url = $this->baseUrl . $endpoint;

        $request = Http::withBasicAuth($this->email, $this->apiToken)
            ->timeout(30);

        if ($method === 'GET') {
            return $request->get($url, $options);
        } elseif ($method === 'POST') {
            return $request->post($url, $options);
        }

        return $request->$method($url, $options);
    }

    /**
     * Test the Bitbucket connection.
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Bitbucket is not configured',
            ];
        }

        try {
            $response = $this->request('GET', '/user');

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'Connected successfully',
                    'user' => $data['display_name'] ?? $data['username'] ?? 'Unknown',
                    'account_id' => $data['account_id'] ?? null,
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

    /**
     * Get all repositories in the workspace.
     */
    public function getRepositories(): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Bitbucket is not configured');
        }

        $repositories = [];
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            $response = $this->request('GET', "/repositories/{$this->workspace}", [
                'pagelen' => 100,
                'page' => $page,
                'sort' => '-updated_on',
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch repositories: ' . $response->status());
            }

            $data = $response->json();
            $repositories = array_merge($repositories, $data['values'] ?? []);

            $hasMore = isset($data['next']);
            $page++;

            // Safety limit
            if ($page > 10) {
                break;
            }
        }

        return $repositories;
    }

    /**
     * Get repository details.
     */
    public function getRepository(string $repoSlug): ?array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Bitbucket is not configured');
        }

        $response = $this->request('GET', "/repositories/{$this->workspace}/{$repoSlug}");

        if (!$response->successful()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Get commits for a repository.
     */
    public function getCommits(string $repoSlug, ?string $since = null, int $limit = 100): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Bitbucket is not configured');
        }

        $commits = [];
        $page = 1;
        $hasMore = true;
        $totalFetched = 0;

        while ($hasMore && $totalFetched < $limit) {
            $params = [
                'pagelen' => min(100, $limit - $totalFetched),
                'page' => $page,
            ];

            $response = $this->request('GET', "/repositories/{$this->workspace}/{$repoSlug}/commits", $params);

            if (!$response->successful()) {
                Log::error('Bitbucket API error fetching commits', [
                    'repo' => $repoSlug,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                break;
            }

            $data = $response->json();
            $pageCommits = $data['values'] ?? [];

            foreach ($pageCommits as $commit) {
                // Check if we've reached the "since" date
                if ($since) {
                    $commitDate = $commit['date'] ?? null;
                    if ($commitDate && strtotime($commitDate) < strtotime($since)) {
                        $hasMore = false;
                        break;
                    }
                }

                $commits[] = $commit;
                $totalFetched++;

                if ($totalFetched >= $limit) {
                    $hasMore = false;
                    break;
                }
            }

            $hasMore = $hasMore && isset($data['next']);
            $page++;

            // Safety limit
            if ($page > 20) {
                break;
            }
        }

        return $commits;
    }

    /**
     * Get commit details including diffstat.
     */
    public function getCommitDetails(string $repoSlug, string $commitHash): ?array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Bitbucket is not configured');
        }

        $response = $this->request('GET', "/repositories/{$this->workspace}/{$repoSlug}/commit/{$commitHash}");

        if (!$response->successful()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Get diffstat for a commit.
     */
    public function getCommitDiffstat(string $repoSlug, string $commitHash): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Bitbucket is not configured');
        }

        $response = $this->request('GET', "/repositories/{$this->workspace}/{$repoSlug}/diffstat/{$commitHash}");

        if (!$response->successful()) {
            return ['additions' => 0, 'deletions' => 0, 'files' => []];
        }

        $data = $response->json();
        $values = $data['values'] ?? [];

        $additions = 0;
        $deletions = 0;
        $files = [];

        foreach ($values as $file) {
            $additions += $file['lines_added'] ?? 0;
            $deletions += $file['lines_removed'] ?? 0;
            $files[] = $file['new']['path'] ?? $file['old']['path'] ?? 'unknown';
        }

        return [
            'additions' => $additions,
            'deletions' => $deletions,
            'files' => $files,
        ];
    }

    /**
     * Sync commits for a project from all linked repositories.
     */
    public function syncProjectCommits(Project $project): array
    {
        // Get all repository slugs (both from pivot table and legacy field)
        $repositories = $project->bitbucketRepositories()->get();

        // Also check legacy field
        if ($project->bitbucket_repo_slug) {
            $legacyExists = $repositories->contains('repo_slug', $project->bitbucket_repo_slug);
            if (!$legacyExists) {
                // Create a fake collection item for legacy repo
                $repositories = $repositories->push((object)[
                    'repo_slug' => $project->bitbucket_repo_slug,
                    'workspace' => $project->bitbucket_workspace,
                ]);
            }
        }

        if ($repositories->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Project is not linked to any Bitbucket repositories',
            ];
        }

        $totalCreated = 0;
        $totalSkipped = 0;
        $allErrors = [];
        $repoResults = [];

        foreach ($repositories as $repo) {
            $repoSlug = $repo->repo_slug;
            $workspace = $repo->workspace ?? $this->workspace;

            // Get the last synced commit date for this specific repo
            $lastCommit = $project->bitbucketCommits()
                ->where('repo_slug', $repoSlug)
                ->orderByDesc('committed_at')
                ->first();
            $since = $lastCommit ? $lastCommit->committed_at->toIso8601String() : null;

            try {
                $commits = $this->getCommits($repoSlug, $since, 500);

                $created = 0;
                $skipped = 0;

                foreach ($commits as $commit) {
                    try {
                        $hash = $commit['hash'] ?? null;
                        if (!$hash) {
                            continue;
                        }

                        // Skip if already exists for this project
                        if (BitbucketCommit::where('commit_hash', $hash)->where('project_id', $project->id)->exists()) {
                            $skipped++;
                            continue;
                        }

                        // Get diffstat for the commit
                        $diffstat = $this->getCommitDiffstat($repoSlug, $hash);

                        // Extract author info
                        $author = $commit['author'] ?? [];
                        $user = $author['user'] ?? [];
                        $rawAuthor = $author['raw'] ?? '';

                        // Parse author name and email from raw string
                        $authorName = $user['display_name'] ?? '';
                        $authorEmail = null;
                        if (preg_match('/<(.+?)>/', $rawAuthor, $matches)) {
                            $authorEmail = $matches[1];
                        }
                        if (!$authorName && preg_match('/^([^<]+)/', $rawAuthor, $matches)) {
                            $authorName = trim($matches[1]);
                        }

                        BitbucketCommit::create([
                            'project_id' => $project->id,
                            'repo_slug' => $repoSlug,
                            'commit_hash' => $hash,
                            'short_hash' => substr($hash, 0, 7),
                            'message' => $commit['message'] ?? '',
                            'author_name' => $authorName ?: 'Unknown',
                            'author_email' => $authorEmail,
                            'author_username' => $user['nickname'] ?? $user['username'] ?? null,
                            'committed_at' => $commit['date'] ?? now(),
                            'branch' => null,
                            'additions' => $diffstat['additions'],
                            'deletions' => $diffstat['deletions'],
                            'files_changed' => $diffstat['files'],
                            'bitbucket_url' => $commit['links']['html']['href'] ?? null,
                        ]);

                        $created++;
                    } catch (\Exception $e) {
                        $allErrors[] = "Repo {$repoSlug}, Commit {$hash}: " . $e->getMessage();
                        Log::error("Bitbucket commit sync error", [
                            'project' => $project->code,
                            'repo' => $repoSlug,
                            'commit' => $hash ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $totalCreated += $created;
                $totalSkipped += $skipped;
                $repoResults[$repoSlug] = ['created' => $created, 'skipped' => $skipped];

                // Update last sync time for this repo in pivot table
                if ($repo instanceof ProjectBitbucketRepository) {
                    $repo->update(['last_sync_at' => now()]);
                }

            } catch (\Exception $e) {
                $allErrors[] = "Repo {$repoSlug}: " . $e->getMessage();
            }
        }

        // Update project's last sync time
        $project->update(['bitbucket_last_sync_at' => now()]);

        return [
            'success' => true,
            'message' => "Synced {$totalCreated} new commits from " . count($repositories) . " repositories, skipped {$totalSkipped} existing",
            'created' => $totalCreated,
            'skipped' => $totalSkipped,
            'repositories' => $repoResults,
            'errors' => $allErrors,
        ];
    }

    /**
     * Link a project to a Bitbucket repository.
     * Supports multiple repositories per project.
     */
    public function linkRepository(Project $project, string $repoSlug, ?string $workspace = null): array
    {
        $workspace = $workspace ?? $this->workspace;

        // Verify the repository exists
        $repo = $this->getRepository($repoSlug);

        if (!$repo) {
            return [
                'success' => false,
                'message' => 'Repository not found',
            ];
        }

        // Check if already linked
        $existing = ProjectBitbucketRepository::where('project_id', $project->id)
            ->where('repo_slug', $repoSlug)
            ->first();

        if ($existing) {
            return [
                'success' => true,
                'message' => 'Repository already linked',
                'repository' => [
                    'name' => $repo['name'],
                    'slug' => $repo['slug'],
                    'description' => $repo['description'] ?? '',
                ],
            ];
        }

        // Create new link in pivot table
        ProjectBitbucketRepository::create([
            'project_id' => $project->id,
            'repo_slug' => $repoSlug,
            'repo_name' => $repo['name'],
            'workspace' => $workspace,
            'repo_uuid' => $repo['uuid'] ?? null,
        ]);

        // Also update legacy field for backward compatibility (first repo only)
        if (empty($project->bitbucket_repo_slug)) {
            $project->update([
                'bitbucket_workspace' => $workspace,
                'bitbucket_repo_slug' => $repoSlug,
                'bitbucket_repo_uuid' => $repo['uuid'] ?? null,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Repository linked successfully',
            'repository' => [
                'name' => $repo['name'],
                'slug' => $repo['slug'],
                'description' => $repo['description'] ?? '',
            ],
        ];
    }

    /**
     * Unlink a specific repository from a project.
     */
    public function unlinkRepositoryBySlug(Project $project, string $repoSlug): void
    {
        // Remove from pivot table
        ProjectBitbucketRepository::where('project_id', $project->id)
            ->where('repo_slug', $repoSlug)
            ->delete();

        // If this was the legacy repo, clear those fields
        if ($project->bitbucket_repo_slug === $repoSlug) {
            // Try to promote another repo to legacy field
            $nextRepo = ProjectBitbucketRepository::where('project_id', $project->id)->first();

            if ($nextRepo) {
                $project->update([
                    'bitbucket_workspace' => $nextRepo->workspace,
                    'bitbucket_repo_slug' => $nextRepo->repo_slug,
                    'bitbucket_repo_uuid' => $nextRepo->repo_uuid,
                ]);
            } else {
                $project->update([
                    'bitbucket_workspace' => null,
                    'bitbucket_repo_slug' => null,
                    'bitbucket_repo_uuid' => null,
                    'bitbucket_last_sync_at' => null,
                ]);
            }
        }

        // Optionally delete commits for this repo
        // BitbucketCommit::where('project_id', $project->id)->where('repo_slug', $repoSlug)->delete();
    }

    /**
     * Unlink a project from its Bitbucket repository.
     */
    public function unlinkRepository(Project $project): void
    {
        $project->update([
            'bitbucket_workspace' => null,
            'bitbucket_repo_slug' => null,
            'bitbucket_repo_uuid' => null,
            'bitbucket_last_sync_at' => null,
        ]);

        // Optionally delete commits
        // $project->bitbucketCommits()->delete();
    }

    /**
     * Get commit statistics for a project.
     */
    public function getProjectCommitStats(Project $project, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = $project->bitbucketCommits();

        if ($startDate) {
            $query->where('committed_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('committed_at', '<=', $endDate);
        }

        $commits = $query->get();

        $byAuthor = $commits->groupBy('author_email')->map(function ($authorCommits) {
            return [
                'name' => $authorCommits->first()->author_name,
                'commits' => $authorCommits->count(),
                'additions' => $authorCommits->sum('additions'),
                'deletions' => $authorCommits->sum('deletions'),
            ];
        })->sortByDesc('commits')->values();

        $byDate = $commits->groupBy(function ($commit) {
            return $commit->committed_at->format('Y-m-d');
        })->map->count()->sortKeys();

        return [
            'total_commits' => $commits->count(),
            'total_additions' => $commits->sum('additions'),
            'total_deletions' => $commits->sum('deletions'),
            'unique_authors' => $commits->pluck('author_email')->unique()->count(),
            'by_author' => $byAuthor->toArray(),
            'by_date' => $byDate->toArray(),
            'date_range' => [
                'start' => $commits->min('committed_at')?->format('Y-m-d'),
                'end' => $commits->max('committed_at')?->format('Y-m-d'),
            ],
        ];
    }
}
