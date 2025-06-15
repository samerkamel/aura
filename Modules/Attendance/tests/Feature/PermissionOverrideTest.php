<?php

namespace Modules\Attendance\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Attendance\Models\PermissionOverride;
use Modules\HR\Models\Employee;
use Tests\TestCase;

/**
 * Permission Override Feature Tests
 *
 * Tests the permission override functionality for Super Admin users
 * to grant exceptional permissions to employees beyond their standard monthly allowance
 *
 * @author GitHub Copilot
 */
class PermissionOverrideTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $admin;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Super Admin user
        $this->superAdmin = User::factory()->create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@test.com',
            'role' => 'super_admin',
        ]);

        // Create regular Admin user
        $this->admin = User::factory()->create([
            'name' => 'Regular Admin',
            'email' => 'admin@test.com',
            'role' => 'admin',
        ]);

        // Create test employee
        $this->employee = Employee::factory()->create([
            'name' => 'Test Employee',
            'email' => 'employee@test.com',
            'base_salary' => 50000.00,
        ]);
    }

    /**
     * Test Super Admin can access employee profile and see permission override section
     */
    public function test_super_admin_can_see_permission_override_section(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('hr.employees.show', $this->employee));

        $response->assertStatus(200);
        $response->assertSee('Extra Permissions');
        $response->assertSee('Add Extra Permission');
        $response->assertSee('No extra permissions granted this month');
    }

    /**
     * Test regular Admin cannot see permission override section
     */
    public function test_regular_admin_cannot_see_permission_override_section(): void
    {
        $response = $this->actingAs($this->admin)->get(route('hr.employees.show', $this->employee));

        $response->assertStatus(200);
        $response->assertDontSee('Add Extra Permission');
        $response->assertDontSee('Extra Permissions');
    }

    /**
     * Test Super Admin can grant extra permissions successfully
     */
    public function test_super_admin_can_grant_extra_permissions(): void
    {
        $data = [
            'employee_id' => $this->employee->id,
            'extra_permissions_granted' => 3,
            'reason' => 'Employee worked overtime on important project',
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('attendance.permission-overrides.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Extra permissions granted successfully.');

        $this->assertDatabaseHas('permission_overrides', [
            'employee_id' => $this->employee->id,
            'extra_permissions_granted' => 3,
            'granted_by_user_id' => $this->superAdmin->id,
            'reason' => 'Employee worked overtime on important project',
            'payroll_period_start_date' => now()->startOfMonth()->toDateString(),
        ]);
    }

    /**
     * Test regular Admin cannot grant extra permissions
     */
    public function test_regular_admin_cannot_grant_extra_permissions(): void
    {
        $data = [
            'employee_id' => $this->employee->id,
            'extra_permissions_granted' => 2,
            'reason' => 'Test reason',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('attendance.permission-overrides.store'), $data);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('permission_overrides', [
            'employee_id' => $this->employee->id,
        ]);
    }

    /**
     * Test validation for permission override creation
     */
    public function test_permission_override_validation(): void
    {
        // Test missing employee_id
        $response = $this->actingAs($this->superAdmin)
            ->post(route('attendance.permission-overrides.store'), [
                'extra_permissions_granted' => 2,
            ]);

        $response->assertSessionHasErrors(['employee_id']);

        // Test missing extra_permissions_granted
        $response = $this->actingAs($this->superAdmin)
            ->post(route('attendance.permission-overrides.store'), [
                'employee_id' => $this->employee->id,
            ]);

        $response->assertSessionHasErrors(['extra_permissions_granted']);

        // Test invalid extra_permissions_granted (too high)
        $response = $this->actingAs($this->superAdmin)
            ->post(route('attendance.permission-overrides.store'), [
                'employee_id' => $this->employee->id,
                'extra_permissions_granted' => 15,
            ]);

        $response->assertSessionHasErrors(['extra_permissions_granted']);

        // Test invalid extra_permissions_granted (too low)
        $response = $this->actingAs($this->superAdmin)
            ->post(route('attendance.permission-overrides.store'), [
                'employee_id' => $this->employee->id,
                'extra_permissions_granted' => 0,
            ]);

        $response->assertSessionHasErrors(['extra_permissions_granted']);

        // Test reason too long
        $response = $this->actingAs($this->superAdmin)
            ->post(route('attendance.permission-overrides.store'), [
                'employee_id' => $this->employee->id,
                'extra_permissions_granted' => 2,
                'reason' => str_repeat('a', 501), // 501 characters
            ]);

        $response->assertSessionHasErrors(['reason']);
    }

    /**
     * Test multiple permission overrides for same employee in same month are cumulative
     */
    public function test_multiple_overrides_are_cumulative(): void
    {
        // First override
        $firstData = [
            'employee_id' => $this->employee->id,
            'extra_permissions_granted' => 2,
            'reason' => 'First reason',
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('attendance.permission-overrides.store'), $firstData);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Extra permissions granted successfully.');

        // Second override
        $secondData = [
            'employee_id' => $this->employee->id,
            'extra_permissions_granted' => 3,
            'reason' => 'Second reason',
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('attendance.permission-overrides.store'), $secondData);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Extra permissions added successfully. Total extra permissions for this month: 5');

        // Check that only one record exists with cumulative permissions
        $this->assertEquals(1, PermissionOverride::where('employee_id', $this->employee->id)->count());

        $override = PermissionOverride::where('employee_id', $this->employee->id)->first();
        $this->assertEquals(5, $override->extra_permissions_granted);
        $this->assertStringContainsString('First reason | Second reason', $override->reason);
    }

    /**
     * Test permission overrides display correctly on employee profile
     */
    public function test_permission_overrides_display_on_employee_profile(): void
    {
        // Create a permission override
        PermissionOverride::create([
            'employee_id' => $this->employee->id,
            'payroll_period_start_date' => now()->startOfMonth(),
            'extra_permissions_granted' => 4,
            'granted_by_user_id' => $this->superAdmin->id,
            'reason' => 'Exceptional work performance',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('hr.employees.show', $this->employee));

        $response->assertStatus(200);
        $response->assertSee('Total Extra Permissions');
        $response->assertSee('4');
        $response->assertSee('Exceptional work performance');
        $response->assertSee($this->superAdmin->name);
    }

    /**
     * Test getCurrentMonthOverrides method
     */
    public function test_get_current_month_overrides_method(): void
    {
        // Create single override for current month
        PermissionOverride::create([
            'employee_id' => $this->employee->id,
            'payroll_period_start_date' => now()->startOfMonth(),
            'extra_permissions_granted' => 5,
            'granted_by_user_id' => $this->superAdmin->id,
        ]);

        // Create override for previous month (should not count)
        PermissionOverride::create([
            'employee_id' => $this->employee->id,
            'payroll_period_start_date' => now()->subMonth()->startOfMonth(),
            'extra_permissions_granted' => 3,
            'granted_by_user_id' => $this->superAdmin->id,
        ]);

        $currentOverrides = PermissionOverride::getCurrentMonthOverrides($this->employee->id);
        $this->assertEquals(5, $currentOverrides); // Only current month override should count
    }

    /**
     * Test Super Admin can get employee overrides via API
     */
    public function test_super_admin_can_get_employee_overrides(): void
    {
        // Create a permission override
        PermissionOverride::create([
            'employee_id' => $this->employee->id,
            'payroll_period_start_date' => now()->startOfMonth(),
            'extra_permissions_granted' => 2,
            'granted_by_user_id' => $this->superAdmin->id,
            'reason' => 'API test override',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('attendance.permission-overrides.get', $this->employee->id));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'extra_permissions_granted' => 2,
            'reason' => 'API test override',
        ]);
    }

    /**
     * Test regular Admin cannot get employee overrides via API
     */
    public function test_regular_admin_cannot_get_employee_overrides(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('attendance.permission-overrides.get', $this->employee->id));

        $response->assertStatus(403);
    }

    /**
     * Test unauthenticated users cannot access permission override functionality
     */
    public function test_unauthenticated_users_cannot_access_permission_overrides(): void
    {
        // Test store endpoint
        $response = $this->post(route('attendance.permission-overrides.store'), [
            'employee_id' => $this->employee->id,
            'extra_permissions_granted' => 2,
        ]);

        $response->assertRedirect('/login');

        // Test get endpoint
        $response = $this->get(route('attendance.permission-overrides.get', $this->employee->id));

        $response->assertRedirect('/login');
    }
}
