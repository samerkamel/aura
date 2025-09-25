<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Accounting\Models\ExpenseSchedule;
use Modules\Accounting\Models\ExpenseCategory;
use Carbon\Carbon;

class TestNextPaymentLogic extends Command
{
    protected $signature = 'test:next-payment-logic';
    protected $description = 'Test the improved next payment logic';

    public function handle()
    {
        $this->info('Testing Next Payment Logic...');

        try {
            // Get or create test category
            $category = ExpenseCategory::firstOrCreate([
                'name' => 'Test Category'
            ], [
                'description' => 'Test category for payment logic',
                'color' => '#FF6B6B',
                'is_active' => true
            ]);

            $this->info('Creating test scenarios...');

            $testScenarios = [
                [
                    'name' => 'Monthly on 1st',
                    'start_date' => '2024-01-01', // January 1st
                    'frequency_type' => 'monthly',
                    'frequency_value' => 1,
                    'expected_from_today' => '2025-10-01' // Next 1st of month
                ],
                [
                    'name' => 'Weekly on Monday',
                    'start_date' => '2024-01-01', // Was a Monday
                    'frequency_type' => 'weekly',
                    'frequency_value' => 1,
                    'expected_pattern' => 'Every Monday'
                ],
                [
                    'name' => 'Bi-weekly starting Dec 1',
                    'start_date' => '2024-12-01', // Recent start
                    'frequency_type' => 'bi-weekly',
                    'frequency_value' => 1,
                    'expected_pattern' => 'Every 2 weeks from Dec 1'
                ],
                [
                    'name' => 'Quarterly on 15th',
                    'start_date' => '2024-01-15', // January 15th
                    'frequency_type' => 'quarterly',
                    'frequency_value' => 1,
                    'expected_pattern' => 'Every quarter on 15th'
                ],
                [
                    'name' => 'Yearly on March 1',
                    'start_date' => '2024-03-01', // March 1st
                    'frequency_type' => 'yearly',
                    'frequency_value' => 1,
                    'expected_pattern' => 'Every March 1st'
                ]
            ];

            foreach ($testScenarios as $scenario) {
                $this->info("\n--- Testing: {$scenario['name']} ---");

                // Create test schedule
                $schedule = ExpenseSchedule::create([
                    'category_id' => $category->id,
                    'name' => $scenario['name'],
                    'description' => "Test schedule: {$scenario['name']}",
                    'amount' => 1000.00,
                    'frequency_type' => $scenario['frequency_type'],
                    'frequency_value' => $scenario['frequency_value'],
                    'start_date' => Carbon::parse($scenario['start_date']),
                    'end_date' => null,
                    'is_active' => true,
                    'skip_weekends' => false,
                    'excluded_dates' => null
                ]);

                $this->info("Start Date: {$schedule->start_date->format('Y-m-d (l)')}");
                $this->info("Frequency: {$schedule->frequency_type} every {$schedule->frequency_value}");

                // Test next payment calculation
                $nextPayment = $schedule->next_payment_date;

                if ($nextPayment) {
                    $this->info("Next Payment: {$nextPayment->format('Y-m-d (l)')}");
                    $this->info("Days from now: " . now()->diffInDays($nextPayment, false));

                    // Verify the payment falls on correct day/pattern
                    $this->verifyPaymentPattern($schedule, $nextPayment);
                } else {
                    $this->error("No next payment calculated!");
                }

                // Clean up
                $schedule->delete();
            }

            $this->info("\n✅ Next payment logic tests completed!");

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    private function verifyPaymentPattern($schedule, $nextPayment)
    {
        $startDate = $schedule->start_date;

        switch ($schedule->frequency_type) {
            case 'monthly':
                if ($nextPayment->day === $startDate->day) {
                    $this->info("✅ Correct: Payment on same day of month ({$startDate->day})");
                } else {
                    $this->error("❌ Incorrect: Expected day {$startDate->day}, got day {$nextPayment->day}");
                }
                break;

            case 'weekly':
                if ($nextPayment->dayOfWeek === $startDate->dayOfWeek) {
                    $this->info("✅ Correct: Payment on same day of week ({$startDate->format('l')})");
                } else {
                    $this->error("❌ Incorrect: Expected {$startDate->format('l')}, got {$nextPayment->format('l')}");
                }
                break;

            case 'yearly':
                if ($nextPayment->month === $startDate->month && $nextPayment->day === $startDate->day) {
                    $this->info("✅ Correct: Payment on same month/day ({$startDate->format('M d')})");
                } else {
                    $this->error("❌ Incorrect: Expected {$startDate->format('M d')}, got {$nextPayment->format('M d')}");
                }
                break;
        }
    }
}