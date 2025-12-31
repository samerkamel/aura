<?php

namespace Modules\Project\Console\Commands;

use Illuminate\Console\Command;
use Modules\Project\Models\Project;
use Modules\Project\Services\JiraIssueSyncService;

class SyncJiraIssues extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'jira:sync-issues
                            {--project= : Specific project code to sync (syncs all if not specified)}
                            {--max-results=500 : Maximum issues to sync per project}';

    /**
     * The console command description.
     */
    protected $description = 'Sync Jira issues for all active projects (or a specific project)';

    protected JiraIssueSyncService $syncService;

    public function __construct(JiraIssueSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->syncService->isConfigured()) {
            $this->error('Jira is not configured. Please configure Jira settings first.');
            return 1;
        }

        $projectCode = $this->option('project');
        $maxResults = (int) $this->option('max-results');

        if ($projectCode) {
            $projects = Project::where('code', $projectCode)->get();
            if ($projects->isEmpty()) {
                $this->error("Project with code '{$projectCode}' not found.");
                return 1;
            }
        } else {
            // Get all active projects with Jira codes
            $projects = Project::where('is_active', true)
                ->whereNotNull('code')
                ->where('code', '!=', '')
                ->get();
        }

        if ($projects->isEmpty()) {
            $this->warn('No projects found to sync.');
            return 0;
        }

        $this->info("Syncing issues for {$projects->count()} project(s)...");
        $this->newLine();

        $totalCreated = 0;
        $totalUpdated = 0;
        $totalErrors = 0;
        $projectResults = [];

        $progressBar = $this->output->createProgressBar($projects->count());
        $progressBar->start();

        foreach ($projects as $project) {
            try {
                $results = $this->syncService->syncProjectIssues($project, $maxResults);

                $projectResults[] = [
                    $project->code,
                    $project->name,
                    $results['total'],
                    $results['created'],
                    $results['updated'],
                    count($results['errors']),
                ];

                $totalCreated += $results['created'];
                $totalUpdated += $results['updated'];
                $totalErrors += count($results['errors']);

            } catch (\Exception $e) {
                $projectResults[] = [
                    $project->code,
                    $project->name,
                    0,
                    0,
                    0,
                    1,
                ];
                $totalErrors++;
                $this->newLine();
                $this->error("Error syncing {$project->code}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('Sync completed!');
        $this->newLine();

        $this->table(
            ['Code', 'Name', 'Total', 'Created', 'Updated', 'Errors'],
            $projectResults
        );

        $this->newLine();
        $this->table(
            ['Summary', 'Count'],
            [
                ['Projects Synced', $projects->count()],
                ['Issues Created', $totalCreated],
                ['Issues Updated', $totalUpdated],
                ['Total Errors', $totalErrors],
            ]
        );

        return $totalErrors > 0 ? 1 : 0;
    }
}
