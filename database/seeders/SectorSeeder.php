<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Sector;

class SectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sectors = [
            [
                'name' => 'Technology',
                'code' => 'TECH',
                'description' => 'Technology and innovation sector including software development, IT services, and digital solutions.',
                'is_active' => true,
            ],
            [
                'name' => 'Healthcare',
                'code' => 'HEALTH',
                'description' => 'Healthcare services, medical devices, and pharmaceutical operations.',
                'is_active' => true,
            ],
            [
                'name' => 'Financial Services',
                'code' => 'FINANCE',
                'description' => 'Banking, insurance, investment, and other financial services.',
                'is_active' => true,
            ],
            [
                'name' => 'Manufacturing',
                'code' => 'MFG',
                'description' => 'Production, assembly, and manufacturing operations.',
                'is_active' => true,
            ],
            [
                'name' => 'Retail & Commerce',
                'code' => 'RETAIL',
                'description' => 'Retail operations, e-commerce, and consumer goods.',
                'is_active' => true,
            ],
            [
                'name' => 'Education',
                'code' => 'EDU',
                'description' => 'Educational services, training, and knowledge development.',
                'is_active' => true,
            ],
            [
                'name' => 'Real Estate',
                'code' => 'REALTY',
                'description' => 'Real estate development, property management, and construction.',
                'is_active' => true,
            ],
            [
                'name' => 'Energy & Utilities',
                'code' => 'ENERGY',
                'description' => 'Energy production, utilities, and infrastructure services.',
                'is_active' => true,
            ],
        ];

        foreach ($sectors as $sectorData) {
            Sector::create($sectorData);
        }
    }
}