<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Authentication Integration Test
 *
 * Tests the complete authentication integration including:
 * - Super admin login
 * - Dashboard access
 * - Navbar logout functionality
 * - Route protection
 *
 * @author GitHub Copilot
 */
class AuthenticationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const SUPER_ADMIN_EMAIL = 'admin@qflow.test';
    private const SUPER_ADMIN_PASSWORD = 'password';

    /**
     * Test super admin can login and access dashboard
     */
    public function test_super_admin_can_login_and_access_dashboard(): void
    {
        // Create super admin user
        $admin = User::factory()->create([
            'name' => 'Super Administrator',
            'email' => self::SUPER_ADMIN_EMAIL,
            'password' => Hash::make(self::SUPER_ADMIN_PASSWORD),
            'email_verified_at' => now(),
        ]);

        // Test login
        $response = $this->post('/login', [
            'email' => self::SUPER_ADMIN_EMAIL,
            'password' => self::SUPER_ADMIN_PASSWORD,
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($admin);

        // Test dashboard access
        $dashboardResponse = $this->actingAs($admin)->get('/');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertViewIs('content.dashboard.dashboards-analytics');
    }

    /**
     * Test authentication middleware protects routes
     */
    public function test_authentication_middleware_protects_routes(): void
    {
        $protectedRoutes = [
            '/',
            '/dashboard/analytics',
            '/dashboard/crm',
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/login');
        }
    }

    /**
     * Test login form validation
     */
    public function test_login_form_validation(): void
    {
        // Test empty fields
        $response = $this->post('/login', []);
        $response->assertSessionHasErrors(['email', 'password']);

        // Test invalid email format
        $response = $this->post('/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);
        $response->assertSessionHasErrors(['email']);

        // Test short password
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '123',
        ]);
        $response->assertSessionHasErrors(['password']);
    }

    /**
     * Test registration form validation
     */
    public function test_registration_form_validation(): void
    {
        // Test empty fields
        $response = $this->post('/register', []);
        $response->assertSessionHasErrors(['name', 'email', 'password']);

        // Test password confirmation mismatch
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'differentpassword',
        ]);
        $response->assertSessionHasErrors(['password']);

        // Test duplicate email
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Test successful logout
     */
    public function test_user_can_logout_successfully(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        // Login first
        $this->actingAs($user);
        $this->assertAuthenticated();

        // Test logout
        $response = $this->post('/logout');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * Test login redirects to intended page
     */
    public function test_login_redirects_to_intended_page(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        // Try to access protected route first
        $this->get('/dashboard/crm');

        // Then login
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        // Should redirect to originally intended page
        $response->assertRedirect('/dashboard/crm');
    }

    /**
     * Test authenticated users cannot access login/register pages
     */
    public function test_authenticated_users_redirected_from_auth_pages(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        // Test login page redirect
        $response = $this->actingAs($user)->get('/login');
        $response->assertRedirect('/');

        // Test register page redirect
        $response = $this->actingAs($user)->get('/register');
        $response->assertRedirect('/');
    }
}
