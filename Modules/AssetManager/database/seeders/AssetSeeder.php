<?php

namespace Modules\AssetManager\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AssetManager\Models\Asset;
use Modules\HR\Models\Employee;
use Carbon\Carbon;

/**
 * Asset Seeder
 *
 * Seeds sample assets and assigns some to active employees for testing
 * the off-boarding workflow.
 *
 * @author Dev Agent
 */
class AssetSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Create sample assets
    $assets = [
      [
        'name' => 'MacBook Pro 16"',
        'type' => 'Laptop',
        'serial_number' => 'MBP001',
        'purchase_date' => Carbon::now()->subMonths(6),
        'purchase_price' => 2499.00,
        'description' => 'Development laptop for senior developers',
        'status' => 'available'
      ],
      [
        'name' => 'Dell OptiPlex 7090',
        'type' => 'Desktop',
        'serial_number' => 'DELL001',
        'purchase_date' => Carbon::now()->subMonths(12),
        'purchase_price' => 899.00,
        'description' => 'Office desktop computer',
        'status' => 'available'
      ],
      [
        'name' => 'iPhone 15 Pro',
        'type' => 'Phone',
        'serial_number' => 'IP15001',
        'purchase_date' => Carbon::now()->subMonths(3),
        'purchase_price' => 999.00,
        'description' => 'Company phone for employees',
        'status' => 'available'
      ],
      [
        'name' => 'iPad Air',
        'type' => 'Tablet',
        'serial_number' => 'IPAD001',
        'purchase_date' => Carbon::now()->subMonths(8),
        'purchase_price' => 599.00,
        'description' => 'Tablet for presentations and mobile work',
        'status' => 'available'
      ],
      [
        'name' => '4K Monitor LG 27"',
        'type' => 'Monitor',
        'serial_number' => 'LG27001',
        'purchase_date' => Carbon::now()->subMonths(15),
        'purchase_price' => 349.00,
        'description' => 'External monitor for workstations',
        'status' => 'available'
      ]
    ];

    foreach ($assets as $assetData) {
      Asset::create($assetData);
    }

    // Assign some assets to active employees (if any exist)
    $activeEmployees = Employee::where('status', 'active')->take(2)->get();

    if ($activeEmployees->count() > 0) {
      $laptop = Asset::where('type', 'Laptop')->first();
      $phone = Asset::where('type', 'Phone')->first();
      $monitor = Asset::where('type', 'Monitor')->first();

      if ($laptop && $activeEmployees->count() >= 1) {
        $employee1 = $activeEmployees->first();
        $employee1->assets()->attach($laptop->id, [
          'assigned_date' => Carbon::now()->subDays(30),
          'notes' => 'Assigned for development work'
        ]);
        $laptop->update(['status' => 'assigned']);
      }

      if ($phone && $monitor && $activeEmployees->count() >= 2) {
        $employee2 = $activeEmployees->get(1);
        $employee2->assets()->attach([$phone->id, $monitor->id], [
          'assigned_date' => Carbon::now()->subDays(60),
          'notes' => 'Standard office setup'
        ]);
        $phone->update(['status' => 'assigned']);
        $monitor->update(['status' => 'assigned']);
      }
    }

    $this->command->info('Asset seeding completed. Created ' . count($assets) . ' sample assets.');
  }
}
