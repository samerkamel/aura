<?php

namespace Modules\Invoicing\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Invoicing\Models\InvoiceSequence;
use Modules\Invoicing\Models\InternalSequence;
use App\Models\BusinessUnit;
use App\Models\Sector;

class InvoicingSeeder extends Seeder
{
    public function run(): void
    {
        // Create invoice sequences
        $this->createInvoiceSequences();

        // Create internal transaction sequences
        $this->createInternalSequences();
    }

    private function createInvoiceSequences(): void
    {
        $sequences = [
            [
                'name' => 'Default Invoice Sequence',
                'prefix' => 'INV',
                'format' => '{PREFIX}-{YEAR}-{NUMBER:6}',
                'current_number' => 0,
                'starting_number' => 1,
                'business_unit_id' => null,
                'sector_ids' => null,
                'is_active' => true,
                'description' => 'Default sequence for all invoices',
            ],
            [
                'name' => 'Technology Sector Invoices',
                'prefix' => 'TECH',
                'format' => '{PREFIX}-{YEAR}-{MONTH}-{NUMBER:4}',
                'current_number' => 0,
                'starting_number' => 1,
                'business_unit_id' => null,
                'sector_ids' => json_encode([1]), // Assuming Technology sector has ID 1
                'is_active' => true,
                'description' => 'Invoice sequence for technology sector',
            ],
            [
                'name' => 'Consulting Invoices',
                'prefix' => 'CONS',
                'format' => '{PREFIX}-{NUMBER:5}',
                'current_number' => 0,
                'starting_number' => 1000,
                'business_unit_id' => null,
                'sector_ids' => json_encode([2]), // Assuming Consulting sector has ID 2
                'is_active' => true,
                'description' => 'Invoice sequence for consulting services',
            ],
        ];

        // Check if we have actual business units and sectors to work with
        $firstBusinessUnit = BusinessUnit::first();
        $sectors = Sector::take(2)->pluck('id')->toArray();

        if ($firstBusinessUnit && !empty($sectors)) {
            $sequences[] = [
                'name' => 'Business Unit Specific',
                'prefix' => 'BU' . $firstBusinessUnit->id,
                'format' => '{PREFIX}-{YEAR}-{NUMBER:4}',
                'current_number' => 0,
                'starting_number' => 1,
                'business_unit_id' => $firstBusinessUnit->id,
                'sector_ids' => json_encode($sectors),
                'is_active' => true,
                'description' => 'Invoice sequence for ' . $firstBusinessUnit->name,
            ];
        }

        foreach ($sequences as $sequenceData) {
            InvoiceSequence::firstOrCreate(
                ['name' => $sequenceData['name']],
                $sequenceData
            );
        }
    }

    private function createInternalSequences(): void
    {
        $sequences = [
            [
                'name' => 'Default Internal Transaction Sequence',
                'prefix' => 'IBT',
                'format' => '{PREFIX}-{YEAR}-{NUMBER:6}',
                'current_number' => 0,
                'starting_number' => 1,
                'business_unit_id' => null,
                'sector_ids' => null,
                'is_active' => true,
                'description' => 'Default sequence for internal business transactions',
            ],
            [
                'name' => 'Monthly Internal Transactions',
                'prefix' => 'INT',
                'format' => '{PREFIX}-{YEAR}{MONTH}-{NUMBER:4}',
                'current_number' => 0,
                'starting_number' => 1,
                'business_unit_id' => null,
                'sector_ids' => null,
                'is_active' => true,
                'description' => 'Monthly numbered internal transactions',
            ],
        ];

        // Add sector-specific sequences if sectors exist
        $sectors = Sector::take(2)->get();
        foreach ($sectors as $sector) {
            $sequences[] = [
                'name' => $sector->name . ' Internal Transactions',
                'prefix' => strtoupper(substr($sector->name, 0, 3)),
                'format' => '{PREFIX}-{YEAR}-{NUMBER:4}',
                'current_number' => 0,
                'starting_number' => 1,
                'business_unit_id' => null,
                'sector_ids' => json_encode([$sector->id]),
                'is_active' => true,
                'description' => 'Internal transactions for ' . $sector->name . ' sector',
            ];
        }

        foreach ($sequences as $sequenceData) {
            InternalSequence::firstOrCreate(
                ['name' => $sequenceData['name']],
                $sequenceData
            );
        }
    }
}