<?php

/**
 * Test script to verify web session authentication works with API routes
 */

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Simulate a login request first
$loginRequest = Illuminate\Http\Request::create('/login', 'POST', [
    'email' => 'admin@qflow.test',
    'password' => 'password',
    '_token' => csrf_token()
]);

echo "🔐 Testing authentication flow...\n";

try {
    // Process login request
    $loginResponse = $kernel->handle($loginRequest);
    echo "✅ Login response status: " . $loginResponse->getStatusCode() . "\n";

    // Check if we're redirected (successful login)
    if ($loginResponse->isRedirection()) {
        echo "✅ Login successful - redirected to: " . $loginResponse->headers->get('Location') . "\n";

        // Get the session from login response
        $session = $loginRequest->getSession();
        if ($session && $session->has('login_web_' . sha1('Illuminate\Auth\SessionGuard'))) {
            echo "✅ Session created successfully\n";

            // Now test API endpoint with session
            $apiRequest = Illuminate\Http\Request::create('/api/v1/employees/1/leave-records', 'POST', [
                'date' => '2025-06-15',
                'type' => 'pto',
                'hours' => 8,
                'reason' => 'Test leave'
            ]);

            // Transfer session to API request
            $apiRequest->setLaravelSession($session);

            $apiResponse = $kernel->handle($apiRequest);
            echo "🔄 API response status: " . $apiResponse->getStatusCode() . "\n";

            if ($apiResponse->getStatusCode() !== 401) {
                echo "✅ Authentication working! API endpoint accessible\n";
            } else {
                echo "❌ Still getting 401 Unauthenticated\n";
            }
        } else {
            echo "❌ No session created during login\n";
        }
    } else {
        echo "❌ Login failed - no redirection\n";
        echo "Response content: " . $loginResponse->getContent() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n🏁 Test completed\n";
