<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    // Create Super Admin and test users
    $this->call([
      SuperAdminSeeder::class,
      EmployeeSeeder::class,
      PermissionSeeder::class, // Set up permissions and verify access
      DefaultLeavePolicySeeder::class, // Create default leave policies
      BusinessUnitSeeder::class, // Create Business Units for multi-tenant support
      \Modules\Invoicing\Database\Seeders\InvoicingSeeder::class, // Create invoice sequences and sample data
    ]);

    // Create additional random users for testing
    User::factory(10)->create();
  }
}
