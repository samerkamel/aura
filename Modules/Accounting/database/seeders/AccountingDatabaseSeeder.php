<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\ExpenseCategory;
use Modules\Accounting\Models\ExpenseSchedule;
use Modules\Accounting\Models\Contract;
use Modules\Accounting\Models\IncomeSchedule;
use Carbon\Carbon;

/**
 * AccountingDatabaseSeeder
 *
 * Seeds the accounting module with sample data for demonstration and testing.
 */
class AccountingDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedExpenseCategories();
        $this->seedExpenseSchedules();
        $this->seedContracts();
        $this->seedIncomeSchedules();

        $this->command->info('Accounting module seeded successfully!');
    }

    /**
     * Seed expense categories.
     */
    private function seedExpenseCategories(): void
    {
        $categories = [
            ['name' => 'Office Rent', 'description' => 'Monthly office rental payments', 'color' => '#FF6B6B'],
            ['name' => 'Utilities', 'description' => 'Electricity, water, internet, phone', 'color' => '#4ECDC4'],
            ['name' => 'Software Subscriptions', 'description' => 'SaaS tools and software licenses', 'color' => '#45B7D1'],
            ['name' => 'Marketing', 'description' => 'Advertising and promotional expenses', 'color' => '#96CEB4'],
            ['name' => 'Insurance', 'description' => 'Business insurance premiums', 'color' => '#FFEAA7'],
            ['name' => 'Professional Services', 'description' => 'Legal, accounting, consulting', 'color' => '#DDA0DD'],
            ['name' => 'Equipment', 'description' => 'Computer equipment and maintenance', 'color' => '#98D8C8'],
            ['name' => 'Travel & Entertainment', 'description' => 'Business travel and client entertainment', 'color' => '#F7DC6F'],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::create($category);
        }
    }

    /**
     * Seed sample expense schedules.
     */
    private function seedExpenseSchedules(): void
    {
        $schedules = [
            [
                'category_id' => 1, // Office Rent
                'name' => 'Main Office Rent',
                'description' => 'Monthly rent for downtown office space',
                'amount' => 4500.00,
                'frequency_type' => 'monthly',
                'frequency_value' => 1,
                'start_date' => now()->startOfMonth(),
                'skip_weekends' => false,
            ],
            [
                'category_id' => 2, // Utilities
                'name' => 'Electricity Bill',
                'description' => 'Monthly electricity bill',
                'amount' => 350.00,
                'frequency_type' => 'monthly',
                'frequency_value' => 1,
                'start_date' => now()->startOfMonth()->addDays(5),
                'skip_weekends' => true,
            ],
            [
                'category_id' => 2, // Utilities
                'name' => 'Internet & Phone',
                'description' => 'Monthly internet and phone service',
                'amount' => 280.00,
                'frequency_type' => 'monthly',
                'frequency_value' => 1,
                'start_date' => now()->startOfMonth()->addDays(10),
                'skip_weekends' => true,
            ],
            [
                'category_id' => 3, // Software
                'name' => 'CRM Software License',
                'description' => 'Monthly SaaS subscription for CRM',
                'amount' => 199.00,
                'frequency_type' => 'monthly',
                'frequency_value' => 1,
                'start_date' => now()->startOfMonth()->addDays(1),
                'skip_weekends' => true,
            ],
            [
                'category_id' => 3, // Software
                'name' => 'Design Software Suite',
                'description' => 'Annual Adobe Creative Suite license',
                'amount' => 599.00,
                'frequency_type' => 'yearly',
                'frequency_value' => 1,
                'start_date' => now()->addMonths(2)->startOfMonth(),
                'skip_weekends' => true,
            ],
            [
                'category_id' => 5, // Insurance
                'name' => 'Business Insurance',
                'description' => 'Quarterly business liability insurance',
                'amount' => 1250.00,
                'frequency_type' => 'quarterly',
                'frequency_value' => 1,
                'start_date' => now()->addDays(45),
                'skip_weekends' => true,
            ],
            [
                'category_id' => 4, // Marketing
                'name' => 'Google Ads Campaign',
                'description' => 'Monthly digital advertising spend',
                'amount' => 800.00,
                'frequency_type' => 'monthly',
                'frequency_value' => 1,
                'start_date' => now()->startOfMonth()->addDays(15),
                'skip_weekends' => true,
            ],
            [
                'category_id' => 6, // Professional Services
                'name' => 'Accounting Services',
                'description' => 'Monthly bookkeeping and accounting',
                'amount' => 750.00,
                'frequency_type' => 'monthly',
                'frequency_value' => 1,
                'start_date' => now()->startOfMonth()->addDays(25),
                'skip_weekends' => true,
            ],
        ];

        foreach ($schedules as $schedule) {
            ExpenseSchedule::create($schedule);
        }
    }

    /**
     * Seed sample contracts.
     */
    private function seedContracts(): void
    {
        $contracts = [
            [
                'client_name' => 'Acme Corporation',
                'contract_number' => 'ACM-2024-001',
                'description' => 'Website development and maintenance contract',
                'total_amount' => 45000.00,
                'start_date' => now()->subMonths(2)->startOfMonth(),
                'end_date' => now()->addMonths(10)->endOfMonth(),
                'status' => 'active',
                'contact_info' => [
                    'email' => 'billing@acmecorp.com',
                    'phone' => '(555) 123-4567',
                ],
                'notes' => 'Long-term web development partnership',
            ],
            [
                'client_name' => 'Tech Solutions Inc',
                'contract_number' => 'TSI-2024-002',
                'description' => 'Mobile app development project',
                'total_amount' => 25000.00,
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->addMonths(6)->endOfMonth(),
                'status' => 'active',
                'contact_info' => [
                    'email' => 'projects@techsolutions.com',
                    'phone' => '(555) 987-6543',
                ],
                'notes' => 'iOS and Android app development',
            ],
            [
                'client_name' => 'Global Marketing Group',
                'contract_number' => 'GMG-2024-003',
                'description' => 'Digital marketing campaign management',
                'total_amount' => 18000.00,
                'start_date' => now()->addDays(15),
                'end_date' => now()->addMonths(9)->endOfMonth(),
                'status' => 'active',
                'contact_info' => [
                    'email' => 'finance@globalmarketing.com',
                    'phone' => '(555) 456-7890',
                ],
                'notes' => 'Comprehensive digital marketing services',
            ],
            [
                'client_name' => 'Startup Innovations',
                'contract_number' => 'SI-2024-004',
                'description' => 'Consulting and strategy development',
                'total_amount' => 12000.00,
                'start_date' => now()->subDays(30),
                'end_date' => now()->addMonths(4)->endOfMonth(),
                'status' => 'active',
                'contact_info' => [
                    'email' => 'ceo@startupinnovations.com',
                    'phone' => '(555) 234-5678',
                ],
                'notes' => 'Business strategy and growth consulting',
            ],
        ];

        foreach ($contracts as $contract) {
            Contract::create($contract);
        }
    }

    /**
     * Seed sample income schedules.
     */
    private function seedIncomeSchedules(): void
    {
        $schedules = [
            [
                'contract_id' => 1, // Acme Corporation
                'name' => 'Monthly Retainer',
                'description' => 'Monthly development and maintenance fee',
                'amount' => 3500.00,
                'frequency_type' => 'monthly',
                'frequency_value' => 1,
                'start_date' => now()->subMonths(2)->startOfMonth(),
                'skip_weekends' => true,
            ],
            [
                'contract_id' => 1, // Acme Corporation
                'name' => 'Quarterly Bonus',
                'description' => 'Performance-based quarterly bonus',
                'amount' => 2000.00,
                'frequency_type' => 'quarterly',
                'frequency_value' => 1,
                'start_date' => now()->subDays(15),
                'skip_weekends' => true,
            ],
            [
                'contract_id' => 2, // Tech Solutions Inc
                'name' => 'Project Milestone Payments',
                'description' => 'Bi-weekly milestone-based payments',
                'amount' => 2500.00,
                'frequency_type' => 'bi-weekly',
                'frequency_value' => 1,
                'start_date' => now()->startOfMonth(),
                'skip_weekends' => true,
            ],
            [
                'contract_id' => 3, // Global Marketing Group
                'name' => 'Monthly Campaign Fee',
                'description' => 'Monthly marketing management fee',
                'amount' => 1800.00,
                'frequency_type' => 'monthly',
                'frequency_value' => 1,
                'start_date' => now()->addDays(15),
                'skip_weekends' => true,
            ],
            [
                'contract_id' => 4, // Startup Innovations
                'name' => 'Weekly Consulting Fee',
                'description' => 'Weekly consulting sessions',
                'amount' => 750.00,
                'frequency_type' => 'weekly',
                'frequency_value' => 1,
                'start_date' => now()->subDays(30),
                'skip_weekends' => true,
            ],
            [
                'contract_id' => 4, // Startup Innovations
                'name' => 'Strategy Document Delivery',
                'description' => 'One-time payment for strategy document',
                'amount' => 3000.00,
                'frequency_type' => 'monthly',
                'frequency_value' => 4, // Every 4 months (one-time in this case)
                'start_date' => now()->addMonths(2),
                'end_date' => now()->addMonths(2)->addDays(1),
                'skip_weekends' => true,
            ],
        ];

        foreach ($schedules as $schedule) {
            IncomeSchedule::create($schedule);
        }
    }
}