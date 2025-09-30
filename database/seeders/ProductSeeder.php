<?php

namespace Database\Seeders;

use App\Models\BusinessUnit;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            $this->command->warn('No users found. Please create users first.');
            return;
        }

        $businessUnits = BusinessUnit::all();

        if ($businessUnits->isEmpty()) {
            $this->command->warn('No business units found. Please create business units first.');
            return;
        }

        $productTemplates = [
            ['name' => 'Software Development', 'code' => 'SW-DEV', 'description' => 'Custom software development services', 'price' => 15000.00],
            ['name' => 'Web Applications', 'code' => 'WEB-APP', 'description' => 'Web application development and maintenance', 'price' => 8000.00],
            ['name' => 'Mobile Applications', 'code' => 'MOB-APP', 'description' => 'Mobile app development for iOS and Android', 'price' => 12000.00],
            ['name' => 'Cloud Services', 'code' => 'CLOUD-SVC', 'description' => 'Cloud infrastructure and services', 'price' => 5000.00],
            ['name' => 'Consulting Services', 'code' => 'CONSULT', 'description' => 'Technical consulting and advisory services', 'price' => 3000.00],
            ['name' => 'System Integration', 'code' => 'SYS-INT', 'description' => 'System integration and API development', 'price' => 10000.00],
            ['name' => 'Database Solutions', 'code' => 'DB-SOL', 'description' => 'Database design and optimization', 'price' => 7000.00],
            ['name' => 'Security Auditing', 'code' => 'SEC-AUD', 'description' => 'Security assessment and auditing services', 'price' => 6000.00],
        ];

        foreach ($businessUnits as $businessUnit) {
            // Create 3-5 products per business unit
            $productsToCreate = array_slice($productTemplates, 0, rand(3, 5));

            foreach ($productsToCreate as $index => $template) {
                Product::create([
                    'name' => $template['name'],
                    'code' => $businessUnit->code . '-' . $template['code'],
                    'description' => $template['description'] . ' for ' . $businessUnit->name,
                    'business_unit_id' => $businessUnit->id,
                    'price' => $template['price'] * (1 + ($index * 0.1)), // Vary prices slightly
                    'status' => 'active',
                    'created_by' => $user->id,
                ]);
            }

            $this->command->info("Created products for {$businessUnit->name}");
        }

        $this->command->info('Product seeding completed!');
    }
}