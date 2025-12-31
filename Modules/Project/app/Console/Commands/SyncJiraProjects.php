<?php

namespace Modules\Project\Console\Commands;

use Illuminate\Console\Command;
use Modules\Project\Services\JiraProjectSyncService;

class SyncJiraProjects extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'jira:sync-projects';

    /**
     * The console command description.
     */
    protected $description = 'Sync all projects from Jira';

    protected JiraProjectSyncService $syncService;

    public function __construct(JiraProjectSyncService $syncService)
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

        $this->info('Testing Jira connection...');
        $connectionTest = $this->syncService->testConnection();

        if (!$connectionTest['success']) {
            $this->error('Failed to connect to Jira: ' . $connectionTest['message']);
            return 1;
        }

        $this->info('Connected as: ' . $connectionTest['user']);
        $this->info('Syncing projects from Jira...');

        try {
            $results = $this->syncService->syncProjects();

            $this->info('Sync completed!');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Projects', $results['total']],
                    ['Created', $results['created']],
                    ['Updated', $results['updated']],
                    ['Errors', count($results['errors'])],
                ]
            );

            if (!empty($results['errors'])) {
                $this->warn('Errors:');
                foreach ($results['errors'] as $error) {
                    $this->error('  - ' . $error);
                }
                return 1;
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            return 1;
        }
    }
}
