<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

// Boot the application
$app->boot();

use App\Models\User;
use Illuminate\Support\Facades\Gate;

// Create test users similar to the test
$admin = User::factory()->create([
    'name' => 'Regular Admin',
    'email' => 'debug-admin@test.com',
    'role' => 'admin',
]);

$superAdmin = User::factory()->create([
    'name' => 'Super Administrator',
    'email' => 'debug-superadmin@test.com',
    'role' => 'super_admin',
]);

// Test the gate
echo "Admin role: " . $admin->role . PHP_EOL;
echo "Admin can manage overrides: " . (Gate::forUser($admin)->allows('manage-permission-overrides') ? 'YES' : 'NO') . PHP_EOL;

echo "Super Admin role: " . $superAdmin->role . PHP_EOL;
echo "Super Admin can manage overrides: " . (Gate::forUser($superAdmin)->allows('manage-permission-overrides') ? 'YES' : 'NO') . PHP_EOL;

// Clean up
$admin->delete();
$superAdmin->delete();
