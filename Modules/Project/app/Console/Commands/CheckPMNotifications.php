<?php

namespace Modules\Project\Console\Commands;

use Illuminate\Console\Command;
use Modules\Project\Services\PMNotificationService;

class CheckPMNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pm:check-notifications
                            {--quiet : Suppress output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for PM notifications (overdue items, upcoming deadlines, health alerts)';

    /**
     * Execute the console command.
     */
    public function handle(PMNotificationService $service): int
    {
        if (!$this->option('quiet')) {
            $this->info('Checking for PM notifications...');
        }

        $stats = $service->checkAndCreateNotifications();

        if (!$this->option('quiet')) {
            $this->newLine();
            $this->info('Notification check completed:');
            $this->table(
                ['Type', 'New Notifications'],
                [
                    ['Overdue Follow-ups', $stats['overdue_followups']],
                    ['Upcoming Follow-ups', $stats['upcoming_followups']],
                    ['Overdue Milestones', $stats['overdue_milestones']],
                    ['Upcoming Milestones', $stats['upcoming_milestones']],
                    ['Overdue Payments', $stats['overdue_payments']],
                    ['Upcoming Payments', $stats['upcoming_payments']],
                    ['Health Alerts', $stats['health_alerts']],
                    ['Stale Projects', $stats['stale_projects']],
                ]
            );

            $total = array_sum($stats);
            $this->newLine();
            $this->info("Total new notifications created: {$total}");
        }

        return Command::SUCCESS;
    }
}
