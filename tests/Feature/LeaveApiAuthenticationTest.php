<?php

namespace Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LeaveApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_leave_api_requires_authentication()
    {
        // Test that unauthenticated request gets 401
        $response = $this->postJson('/api/v1/employees/1/leave-records', [
            'date' => '2025-06-15',
            'type' => 'pto',
            'hours' => 8,
            'reason' => 'Test leave'
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_admin_can_access_leave_api()
    {
        // Create an admin user
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => 'admin'
        ]);

        // Test that authenticated admin can access the API
        $response = $this->actingAs($admin, 'web')
            ->postJson('/api/v1/employees/1/leave-records', [
                'date' => '2025-06-15',
                'type' => 'pto',
                'hours' => 8,
                'reason' => 'Test leave'
            ]);

        // Should not get 401 Unauthenticated
        $this->assertNotEquals(401, $response->getStatusCode());

        // Log the actual response for debugging
        if ($response->getStatusCode() !== 200 && $response->getStatusCode() !== 201) {
            echo "\nActual response status: " . $response->getStatusCode();
            echo "\nResponse content: " . $response->getContent();
        }
    }

    public function test_web_session_authentication_works_with_api_routes()
    {
        // Create an admin user
        $admin = User::factory()->create([
            'email' => 'test@qflow.test',
            'password' => bcrypt('password'),
            'role' => 'admin'
        ]);

        // First login via web route
        $loginResponse = $this->post('/login', [
            'email' => 'test@qflow.test',
            'password' => 'password'
        ]);

        // Should be redirected after successful login
        $loginResponse->assertRedirect('/');

        // Now test API access with the same session
        $response = $this->postJson('/api/v1/employees/1/leave-records', [
            'date' => '2025-06-15',
            'type' => 'pto',
            'hours' => 8,
            'reason' => 'Test leave via session'
        ]);

        // Should not get 401 Unauthenticated
        $this->assertNotEquals(401, $response->getStatusCode());

        echo "\nSession-based API call status: " . $response->getStatusCode();
        if ($response->getStatusCode() === 401) {
            echo "\nResponse: " . $response->getContent();
        }
    }
}
