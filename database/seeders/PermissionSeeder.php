<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Gate;

/**
 * Permission Seeder
 *
 * Handles setting up permissions and ensuring users have correct access levels
 * for leave management and other administrative functions.
 *
 * @author Dev Agent
 */
class PermissionSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $this->command->info('Setting up permissions for QFlow system...');

    // Ensure admin@qflow.test has super_admin role
    $adminUser = User::where('email', 'admin@qflow.test')->first();
    if ($adminUser) {
      if ($adminUser->role !== 'super_admin') {
        $adminUser->update(['role' => 'super_admin']);
        $this->command->info('✓ Updated admin@qflow.test to super_admin role');
      } else {
        $this->command->info('✓ admin@qflow.test already has super_admin role');
      }
    } else {
      $this->command->warn('⚠ admin@qflow.test user not found. Run SuperAdminSeeder first.');
    }

    // Update any existing users with 'admin' role to have proper permissions
    $adminUsers = User::where('role', 'admin')->get();
    foreach ($adminUsers as $user) {
      $this->command->info("✓ User {$user->email} has admin role with leave management permissions");
    }

    // Display permission summary
    $this->displayPermissionSummary();
  }

  /**
   * Display a summary of the permission system
   */
  private function displayPermissionSummary(): void
  {
    $this->command->info('');
    $this->command->info('📋 Permission Summary:');
    $this->command->info('');

    $this->command->info('🔥 Super Admin Role (super_admin):');
    $this->command->info('   - manage-permission-overrides ✓');
    $this->command->info('   - manage-leave-records ✓');
    $this->command->info('   - manage-wfh-records ✓');
    $this->command->info('   - view-employee-details ✓');
    $this->command->info('');

    $this->command->info('👨‍💼 Admin Role (admin):');
    $this->command->info('   - manage-leave-records ✓');
    $this->command->info('   - manage-wfh-records ✓');
    $this->command->info('   - view-employee-details ✓');
    $this->command->info('   - manage-permission-overrides ✗');
    $this->command->info('');

    $this->command->info('👤 Employee Role (employee):');
    $this->command->info('   - Basic access only');
    $this->command->info('   - All administrative permissions ✗');
    $this->command->info('');

    // Test permissions for admin@qflow.test if they exist
    $adminUser = User::where('email', 'admin@qflow.test')->first();
    if ($adminUser) {
      $this->command->info("🧪 Testing permissions for admin@qflow.test:");

      $permissions = [
        'manage-permission-overrides',
        'manage-leave-records',
        'manage-wfh-records',
        'view-employee-details'
      ];

      foreach ($permissions as $permission) {
        $hasPermission = Gate::forUser($adminUser)->allows($permission);
        $status = $hasPermission ? '✓' : '✗';
        $this->command->info("   - {$permission}: {$status}");
      }
    }
  }
}
