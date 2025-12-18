<?php

namespace Modules\Payroll\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Payroll\Services\JiraBillableHoursService;

class SyncJiraBillableHours extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'jira:sync-billable-hours
                            {--start-date= : Start date for sync (Y-m-d format)}
                            {--end-date= : End date for sync (Y-m-d format)}
                            {--period= : Sync period (today, yesterday, this-week, last-week, this-month, last-month)}';

    /**
     * The console command description.
     */
    protected $description = 'Sync billable hours from Jira to the payroll system';

    protected $jiraService;

    public function __construct(JiraBillableHoursService $jiraService)
    {
        parent::__construct();
        $this->jiraService = $jiraService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if Jira sync is enabled
        if (!config('services.jira.sync_enabled')) {
            $this->error('Jira sync is not enabled. Please enable it in your environment configuration.');
            return 1;
        }

        // Test Jira connection first
        $this->info('Testing Jira connection...');
        if (!$this->jiraService->testConnection()) {
            $this->error('Failed to connect to Jira. Please check your configuration.');
            return 1;
        }
        $this->info('✓ Jira connection successful');

        // Determine date range
        [$startDate, $endDate] = $this->determineDateRange();
        
        $this->info("Syncing billable hours from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");

        try {
            $results = $this->jiraService->syncBillableHours($startDate, $endDate);
            
            $this->displayResults($results);
            
            if ($results['failed'] > 0) {
                $this->displayErrors($results['errors']);
                return 1;
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Sync failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Determine the date range for sync
     */
    protected function determineDateRange()
    {
        $startDate = $this->option('start-date');
        $endDate = $this->option('end-date');
        $period = $this->option('period');

        if ($startDate && $endDate) {
            return [
                Carbon::parse($startDate),
                Carbon::parse($endDate)
            ];
        }

        if ($period) {
            return $this->getDateRangeForPeriod($period);
        }

        // Default to last week
        return [
            Carbon::now()->startOfWeek()->subWeek(),
            Carbon::now()->startOfWeek()->subDay()
        ];
    }

    /**
     * Get date range for predefined periods
     */
    protected function getDateRangeForPeriod($period)
    {
        $now = Carbon::now();

        switch ($period) {
            case 'today':
                return [$now->copy(), $now->copy()];
                
            case 'yesterday':
                return [$now->copy()->subDay(), $now->copy()->subDay()];
                
            case 'this-week':
                return [$now->startOfWeek(), $now->endOfWeek()];
                
            case 'last-week':
                return [
                    $now->startOfWeek()->subWeek(),
                    $now->startOfWeek()->subDay()
                ];
                
            case 'this-month':
                return [$now->startOfMonth(), $now->endOfMonth()];
                
            case 'last-month':
                return [
                    $now->startOfMonth()->subMonth(),
                    $now->startOfMonth()->subDay()
                ];
                
            default:
                $this->error("Invalid period: {$period}");
                exit(1);
        }
    }

    /**
     * Display sync results
     */
    protected function displayResults($results)
    {
        $this->info('Sync completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Successful Records', $results['success']],
                ['Failed Records', $results['failed']],
                ['Total Records', $results['success'] + $results['failed']],
            ]
        );
    }

    /**
     * Display errors
     */
    protected function displayErrors($errors)
    {
        if (empty($errors)) {
            return;
        }

        $this->error('The following errors occurred:');
        
        $errorData = [];
        foreach ($errors as $error) {
            $errorData[] = [
                $error['employee'],
                $error['error']
            ];
        }
        
        $this->table(['Employee', 'Error'], $errorData);
    }
}