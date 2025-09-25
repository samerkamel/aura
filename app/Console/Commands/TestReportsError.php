<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Accounting\Models\ExpenseSchedule;
use Modules\Accounting\Models\IncomeSchedule;

class TestReportsError extends Command
{
    protected $signature = 'test:reports-error';
    protected $description = 'Test the reports generation to find the error';

    public function handle()
    {
        $this->info('Testing reports error...');

        try {
            $upcomingPayments = collect();

            $this->info('Getting expense schedules...');
            $expenses = ExpenseSchedule::active()->with('category')->get();
            $this->info('Found ' . $expenses->count() . ' expense schedules');

            foreach ($expenses as $schedule) {
                $this->info("Processing expense: {$schedule->name}");

                if (!$schedule->category) {
                    $this->error("Expense {$schedule->name} has no category!");
                    continue;
                }

                if ($schedule->next_payment_date && $schedule->next_payment_date <= now()->addMonths(3)) {
                    $this->info("Adding expense payment: {$schedule->name} on {$schedule->next_payment_date->format('Y-m-d')}");
                    $upcomingPayments->push([
                        'type' => 'expense',
                        'name' => $schedule->name,
                        'description' => $schedule->description,
                        'amount' => $schedule->amount,
                        'date' => $schedule->next_payment_date,
                        'category' => $schedule->category->name,
                        'color' => $schedule->category->color,
                    ]);
                }
            }

            $this->info('Getting income schedules...');
            $incomes = IncomeSchedule::active()->with('contract')->get();
            $this->info('Found ' . $incomes->count() . ' income schedules');

            foreach ($incomes as $schedule) {
                $this->info("Processing income: {$schedule->name}");

                if (!$schedule->contract) {
                    $this->error("Income {$schedule->name} has no contract!");
                    continue;
                }

                if ($schedule->next_payment_date && $schedule->next_payment_date <= now()->addMonths(3)) {
                    $this->info("Adding income payment: {$schedule->name} on {$schedule->next_payment_date->format('Y-m-d')}");
                    $upcomingPayments->push([
                        'type' => 'income',
                        'name' => $schedule->name,
                        'description' => $schedule->description,
                        'amount' => $schedule->amount,
                        'date' => $schedule->next_payment_date,
                        'source' => $schedule->contract->client_name,
                    ]);
                }
            }

            $this->info('Sorting payments...');
            $upcomingPayments = $upcomingPayments->sortBy('date');
            $this->info('Total upcoming payments: ' . $upcomingPayments->count());

            foreach ($upcomingPayments->take(3) as $payment) {
                $this->info("Payment: {$payment['name']} - {$payment['type']} - {$payment['date']->format('Y-m-d')}");
            }

            $this->info('✅ No errors found in payment generation');

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
        }
    }
}