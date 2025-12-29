<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\Account;

class ImportAccountSeeder extends Seeder
{
    /**
     * Seed accounts from the expense import CSV columns.
     */
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Samer',
                'description' => 'Samer personal account for company expenses',
                'type' => 'digital_wallet',
                'account_number' => 'SAMER-001',
                'currency' => 'EGP',
                'starting_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Simon',
                'description' => 'Simon personal account for company expenses',
                'type' => 'digital_wallet',
                'account_number' => 'SIMON-001',
                'currency' => 'EGP',
                'starting_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Fadi',
                'description' => 'Fadi personal account for company expenses',
                'type' => 'digital_wallet',
                'account_number' => 'FADI-001',
                'currency' => 'EGP',
                'starting_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Adel',
                'description' => 'Adel personal account for company expenses',
                'type' => 'digital_wallet',
                'account_number' => 'ADEL-001',
                'currency' => 'EGP',
                'starting_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'CapEx Cash',
                'description' => 'Capital expenditure cash account',
                'type' => 'cash',
                'account_number' => 'CAPEX-001',
                'currency' => 'EGP',
                'starting_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Cash',
                'description' => 'General cash account',
                'type' => 'cash',
                'account_number' => 'CASH-001',
                'currency' => 'EGP',
                'starting_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Bank (QNB)EGP',
                'description' => 'QNB Bank account in EGP',
                'type' => 'bank',
                'account_number' => 'QNB-EGP-001',
                'bank_name' => 'Qatar National Bank (QNB)',
                'currency' => 'EGP',
                'starting_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Margins',
                'description' => 'Margins/Petty cash account',
                'type' => 'cash',
                'account_number' => 'MARGINS-001',
                'currency' => 'EGP',
                'starting_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ],
        ];

        foreach ($accounts as $accountData) {
            Account::updateOrCreate(
                ['name' => $accountData['name']],
                $accountData
            );
        }

        $this->command->info('Created/Updated ' . count($accounts) . ' accounts for expense import.');
    }
}
