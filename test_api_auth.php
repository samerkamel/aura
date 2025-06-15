<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Test API authentication by simulating a POST request
$request = Request::create('/api/v1/employees/1/leave-records', 'POST', [
    'leave_policy_id' => 1,
    'start_date' => '2025-06-15',
    'end_date' => '2025-06-15',
    'notes' => 'Test leave record'
]);

// Add CSRF token and session to simulate web authentication
$request->headers->set('X-CSRF-TOKEN', 'test-token');
$request->headers->set('Accept', 'application/json');

try {
    // Simulate authenticated user
    $user = App\Models\User::where('email', 'admin@qflow.test')->first();
    if ($user) {
        auth()->login($user);
        echo "User authenticated: " . $user->name . "\n";

        // Process the request
        $response = $kernel->handle($request);
        echo "Response status: " . $response->getStatusCode() . "\n";
        echo "Response content: " . $response->getContent() . "\n";
    } else {
        echo "User not found\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

$kernel->terminate($request, $response ?? null);
