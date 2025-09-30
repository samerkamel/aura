<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BusinessUnit;

class BusinessUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businessUnits = [
            [
                'name' => 'Head Office',
                'code' => 'HQ',
                'description' => 'Main headquarters - manages company-wide operations and expenses',
                'type' => 'head_office',
                'is_active' => true,
            ],
            [
                'name' => 'Information Technology',
                'code' => 'IT',
                'description' => 'Technology services and software development',
                'type' => 'business_unit',
                'is_active' => true,
            ],
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'description' => 'Employee management and organizational development',
                'type' => 'business_unit',
                'is_active' => true,
            ],
            [
                'name' => 'Finance & Operations',
                'code' => 'FINOPS',
                'description' => 'Financial management and business operations',
                'type' => 'business_unit',
                'is_active' => true,
            ],
            [
                'name' => 'Marketing & Sales',
                'code' => 'MKTSL',
                'description' => 'Marketing campaigns and sales management',
                'type' => 'business_unit',
                'is_active' => true,
            ],
        ];

        foreach ($businessUnits as $businessUnitData) {
            BusinessUnit::firstOrCreate(
                ['code' => $businessUnitData['code']],
                $businessUnitData
            );
        }

        $this->command->info('Business Units seeded successfully!');
    }
}