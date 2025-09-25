<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Accounting\Services\CashFlowProjectionService;
use Carbon\Carbon;

class TestProjectionService extends Command
{
    protected $signature = 'test:projection-service';
    protected $description = 'Test the projection service to isolate the issue';

    public function handle()
    {
        $this->info('Testing projection service...');

        try {
            $service = app(CashFlowProjectionService::class);

            $startDate = now();
            $endDate = now()->addMonths(3);

            $this->info("Start date: {$startDate->format('Y-m-d')}");
            $this->info("End date: {$endDate->format('Y-m-d')}");

            $this->info('Generating projections...');
            $projections = $service
                ->setStartingBalance(10000)
                ->generateProjections($startDate, $endDate, 'monthly');

            $this->info("Generated " . $projections->count() . " projections");

            if ($projections->count() > 0) {
                $first = $projections->first();
                $this->info("First projection date: " . $first['projection_date']->format('Y-m-d'));
                $this->info("First projection income: " . $first['projected_income']);
            }

            $this->info('✅ Projection service working correctly');

        } catch (\Exception $e) {
            $this->error('❌ Error in projection service:');
            $this->error($e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
        }
    }
}