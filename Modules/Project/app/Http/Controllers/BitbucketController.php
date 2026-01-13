<?php

namespace Modules\Project\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Project\Models\BitbucketSetting;
use Modules\Project\Models\BitbucketCommit;
use Modules\Project\Models\Project;
use Modules\Project\Services\BitbucketService;

class BitbucketController extends Controller
{
    protected BitbucketService $bitbucketService;

    public function __construct(BitbucketService $bitbucketService)
    {
        $this->bitbucketService = $bitbucketService;
    }

    /**
     * Show Bitbucket settings page.
     */
    public function settings()
    {
        $settings = BitbucketSetting::getInstance();
        $connectionStatus = null;
        $linkedProjects = collect();

        if ($settings->isConfigured()) {
            $connectionStatus = $this->bitbucketService->testConnection();

            // Get projects with linked repositories
            $linkedProjects = Project::with(['bitbucketRepositories', 'bitbucketCommits'])
                ->where(function ($query) {
                    $query->whereNotNull('bitbucket_repo_slug')
                        ->orWhereHas('bitbucketRepositories');
                })
                ->orderBy('name')
                ->get();
        }

        return view('project::bitbucket.settings', compact('settings', 'connectionStatus', 'linkedProjects'));
    }

    /**
     * Show bulk link projects page.
     */
    public function linkProjects()
    {
        $settings = BitbucketSetting::getInstance();
        $isConfigured = $settings->isConfigured();

        $projects = Project::with(['customer', 'bitbucketRepositories'])
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get();

        return view('project::bitbucket.link-projects', compact('projects', 'isConfigured'));
    }

    /**
     * Update Bitbucket settings.
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'workspace' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'api_token' => 'nullable|string',
            'sync_enabled' => 'boolean',
            'sync_frequency' => 'in:manual,hourly,daily',
        ]);

        $settings = BitbucketSetting::getInstance();

        $data = [
            'workspace' => $request->workspace,
            'email' => $request->email,
            'sync_enabled' => $request->boolean('sync_enabled'),
            'sync_frequency' => $request->sync_frequency ?? 'daily',
        ];

        // Only update token if provided (not empty)
        if ($request->filled('api_token')) {
            $data['api_token'] = $request->api_token;
        }

        $settings->update($data);

        return redirect()->route('projects.bitbucket.settings')
            ->with('success', 'Bitbucket settings updated successfully');
    }

    /**
     * Test Bitbucket connection.
     */
    public function testConnection()
    {
        $result = $this->bitbucketService->testConnection();

        return response()->json($result);
    }

    /**
     * Get available repositories.
     */
    public function getRepositories()
    {
        try {
            $repositories = $this->bitbucketService->getRepositories();

            return response()->json([
                'success' => true,
                'repositories' => collect($repositories)->map(function ($repo) {
                    $project = $repo['project'] ?? null;
                    return [
                        'slug' => $repo['slug'],
                        'name' => $repo['name'],
                        'description' => $repo['description'] ?? '',
                        'updated_on' => $repo['updated_on'] ?? null,
                        'is_private' => $repo['is_private'] ?? true,
                        'project_key' => $project['key'] ?? null,
                        'project_name' => $project['name'] ?? null,
                        'project_link' => $project['links']['html']['href'] ?? null,
                    ];
                })->values(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Link a repository to a project.
     */
    public function linkRepository(Request $request, Project $project)
    {
        $request->validate([
            'repo_slug' => 'required|string',
            'workspace' => 'nullable|string',
        ]);

        $result = $this->bitbucketService->linkRepository(
            $project,
            $request->repo_slug,
            $request->workspace
        );

        // Return JSON for AJAX requests
        if ($request->expectsJson()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        if ($result['success']) {
            return redirect()->back()->with('success', 'Repository linked successfully');
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Unlink a repository from a project.
     */
    public function unlinkRepository(Request $request, Project $project)
    {
        $repoSlug = $request->input('repo_slug');

        if ($repoSlug) {
            // Unlink specific repository
            $this->bitbucketService->unlinkRepositoryBySlug($project, $repoSlug);
        } else {
            // Unlink all repositories (legacy behavior)
            $this->bitbucketService->unlinkRepository($project);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Repository unlinked successfully');
    }

    /**
     * Sync commits for a project.
     */
    public function syncCommits(Project $project)
    {
        $result = $this->bitbucketService->syncProjectCommits($project);

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json($result, 500);
    }

    /**
     * Show commits for a project.
     */
    public function projectCommits(Request $request, Project $project)
    {
        $query = $project->bitbucketCommits()->orderBy('committed_at', 'desc');

        // Filter by repository
        if ($request->filled('repo')) {
            $query->where('repo_slug', $request->repo);
        }

        // Filter by author
        if ($request->filled('author')) {
            $query->where('author_email', $request->author);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('committed_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('committed_at', '<=', $request->end_date . ' 23:59:59');
        }

        // Search in message
        if ($request->filled('search')) {
            $query->where('message', 'like', '%' . $request->search . '%');
        }

        $commits = $query->paginate(50);

        // Get unique authors for filter dropdown
        $authors = $project->bitbucketCommits()
            ->select('author_email', 'author_name')
            ->distinct()
            ->orderBy('author_name')
            ->get();

        // Get unique repositories for filter dropdown
        $repositories = $project->bitbucketCommits()
            ->select('repo_slug')
            ->distinct()
            ->orderBy('repo_slug')
            ->pluck('repo_slug');

        // Get all linked repositories (new model + legacy)
        $linkedRepos = $project->getAllBitbucketRepoSlugs();

        // Get stats
        $stats = $this->bitbucketService->getProjectCommitStats(
            $project,
            $request->start_date,
            $request->end_date
        );

        return view('project::bitbucket.commits', compact('project', 'commits', 'authors', 'repositories', 'linkedRepos', 'stats'));
    }

    /**
     * Get commit statistics (AJAX).
     */
    public function getCommitStats(Request $request, Project $project)
    {
        $stats = $this->bitbucketService->getProjectCommitStats(
            $project,
            $request->start_date,
            $request->end_date
        );

        return response()->json($stats);
    }

    /**
     * Show commit details modal content.
     */
    public function commitDetails(Project $project, BitbucketCommit $commit)
    {
        return view('project::bitbucket.partials.commit-details', compact('project', 'commit'));
    }

    /**
     * Bulk sync all linked projects.
     */
    public function syncAll()
    {
        $projects = Project::whereNotNull('bitbucket_repo_slug')->get();

        $results = [
            'total' => $projects->count(),
            'success' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($projects as $project) {
            $result = $this->bitbucketService->syncProjectCommits($project);

            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
            }

            $results['details'][] = [
                'project' => $project->name,
                'result' => $result,
            ];
        }

        return response()->json($results);
    }
}
