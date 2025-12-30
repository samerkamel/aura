<?php

namespace Modules\Project\Console\Commands;

use Illuminate\Console\Command;
use Modules\Project\Services\ProjectHealthService;

class CalculateProjectHealth extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'projects:calculate-health
                            {--project= : Calculate for a specific project ID}';

    /**
     * The console command description.
     */
    protected $description = 'Calculate and store health snapshots for all active projects';

    public function __construct(
        protected ProjectHealthService $healthService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $projectId = $this->option('project');

        if ($projectId) {
            $this->info("Calculating health for project ID: {$projectId}");

            $project = \Modules\Project\Models\Project::find($projectId);
            if (!$project) {
                $this->error("Project not found: {$projectId}");
                return Command::FAILURE;
            }

            try {
                $snapshot = $this->healthService->createSnapshot($project);
                $this->info("Health score: {$snapshot->health_score} ({$snapshot->status})");
                return Command::SUCCESS;
            } catch (\Exception $e) {
                $this->error("Failed: {$e->getMessage()}");
                return Command::FAILURE;
            }
        }

        $this->info('Calculating health for all active projects...');

        $results = $this->healthService->createSnapshotsForAllProjects();

        $this->info("Completed: {$results['success']}/{$results['total']} projects");

        if ($results['failed'] > 0) {
            $this->warn("Failed: {$results['failed']} projects");
            foreach ($results['errors'] as $error) {
                $this->error("  - {$error}");
            }
        }

        return $results['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
