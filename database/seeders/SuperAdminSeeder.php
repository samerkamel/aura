<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Super Admin User Seeder
 *
 * Creates the default super admin user for the QFlow system
 *
 * @author GitHub Copilot
 */
class SuperAdminSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Create Super Admin User
    $superAdmin = User::firstOrCreate(
      ['email' => 'admin@qflow.test'],
      [
        'name' => 'Super Administrator',
        'email' => 'admin@qflow.test',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
        'role' => 'super_admin',
      ]
    );

    // Ensure the user has super_admin role if they already existed
    if ($superAdmin->role !== 'super_admin') {
      $superAdmin->update(['role' => 'super_admin']);
    }

    $this->command->info('Super Admin user created/updated:');
    $this->command->info('Email: admin@qflow.test');
    $this->command->info('Password: password');
    $this->command->info('Role: super_admin');
    $this->command->info('Permissions: manage-permission-overrides, manage-leave-records, manage-wfh-records, view-employee-details');

    // Create additional test users for development
    $testUsers = [
      [
        'name' => 'Test Manager',
        'email' => 'manager@qflow.test',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
        'role' => 'admin', // Admin role can manage leave records but not permission overrides
      ],
      [
        'name' => 'Test Employee',
        'email' => 'employee@qflow.test',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
        'role' => 'employee', // Regular employee role with basic access
      ]
    ];

    foreach ($testUsers as $userData) {
      $user = User::firstOrCreate(
        ['email' => $userData['email']],
        $userData
      );

      // Update role if user exists but has wrong role
      if ($user->role !== $userData['role']) {
        $user->update(['role' => $userData['role']]);
      }
    }

    $this->command->info('Test users created/updated:');
    $this->command->info('- manager@qflow.test (role: admin) - Can manage leave records');
    $this->command->info('- employee@qflow.test (role: employee) - Basic access only');
    $this->command->info('All test users have password: password');
  }
}
