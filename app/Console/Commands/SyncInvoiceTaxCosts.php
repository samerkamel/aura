<?php

namespace App\Console\Commands;

use App\Services\InvoiceProjectSyncService;
use Illuminate\Console\Command;

class SyncInvoiceTaxCosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:sync-tax-costs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync tax costs for all existing invoice-project links';

    /**
     * Execute the console command.
     */
    public function handle(InvoiceProjectSyncService $service): int
    {
        $this->info('Syncing tax costs for existing invoice-project links...');

        $result = $service->syncTaxCostsForExistingLinks();

        if ($result['success']) {
            $this->info($result['message']);
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Created', $result['created']],
                    ['Updated', $result['updated']],
                    ['Skipped', $result['skipped']],
                ]
            );
            return Command::SUCCESS;
        }

        $this->error('Sync completed with errors:');
        $this->info($result['message']);

        foreach ($result['errors'] as $error) {
            $this->error("  - {$error}");
        }

        return Command::FAILURE;
    }
}
