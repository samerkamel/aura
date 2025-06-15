<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Authentication System Test
 *
 * Tests the complete authentication functionality including:
 * - Login/logout flow
 * - Registration
 * - Route protection
 * - Redirect behavior
 *
 * @author GitHub Copilot
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN_URL = '/login';
    private const REGISTER_URL = '/register';
    private const DASHBOARD_URL = '/';
    private const TEST_EMAIL = 'test@example.com';
    private const TEST_PASSWORD = 'password123';

    /**
     * Test login page is accessible
     */
    public function test_login_page_is_accessible(): void
    {
        $response = $this->get(self::LOGIN_URL);

        $response->assertStatus(200);
        $response->assertViewIs('content.authentications.auth-login-basic');
    }

    /**
     * Test register page is accessible
     */
    public function test_register_page_is_accessible(): void
    {
        $response = $this->get(self::REGISTER_URL);

        $response->assertStatus(200);
        $response->assertViewIs('content.authentications.auth-register-basic');
    }

    /**
     * Test successful login
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => self::TEST_EMAIL,
            'password' => Hash::make(self::TEST_PASSWORD),
        ]);

        $response = $this->post(self::LOGIN_URL, [
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);

        $response->assertRedirect(self::DASHBOARD_URL);
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test login fails with invalid credentials
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make(self::TEST_PASSWORD),
        ]);

        $response = $this->post(self::LOGIN_URL, [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    /**
     * Test user can register
     */
    public function test_user_can_register(): void
    {
        $response = $this->post(self::REGISTER_URL, [
            'name' => 'Test User',
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD,
            'password_confirmation' => self::TEST_PASSWORD,
        ]);

        $response->assertRedirect(self::DASHBOARD_URL);
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => self::TEST_EMAIL,
        ]);
        $this->assertAuthenticated();
    }

    /**
     * Test user can logout
     */
    public function test_user_can_logout(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect(self::LOGIN_URL);
        $this->assertGuest();
    }

    /**
     * Test dashboard is protected by authentication
     */
    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get(self::DASHBOARD_URL);

        $response->assertRedirect(self::LOGIN_URL);
    }

    /**
     * Test authenticated user can access dashboard
     */
    public function test_authenticated_user_can_access_dashboard(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(self::DASHBOARD_URL);

        $response->assertStatus(200);
    }
}
