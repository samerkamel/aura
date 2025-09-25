<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Modules\Accounting\Http\Controllers\AccountingController;

class TestReportsMethod extends Command
{
    protected $signature = 'test:reports-method';
    protected $description = 'Test the reports method to find any errors';

    public function handle()
    {
        $this->info('Testing reports method...');

        try {
            // Create a fake request
            $request = new Request();
            $request->merge([
                'tab' => 'schedule'
            ]);

            // Mock user permissions
            $user = \App\Models\User::first();
            auth()->login($user);

            // Create controller and call reports method
            $controller = new AccountingController(app(\Modules\Accounting\Services\CashFlowProjectionService::class));

            $this->info('Calling reports method...');
            $result = $controller->reports($request);

            $this->info('✅ Reports method executed successfully');
            $this->info('View: ' . $result->name());
            $this->info('Data keys: ' . implode(', ', array_keys($result->getData())));

        } catch (\Exception $e) {
            $this->error('❌ Error in reports method:');
            $this->error($e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
            $this->error('Stack trace:');
            $this->error($e->getTraceAsString());
        }
    }
}