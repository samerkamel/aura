<?php

namespace Modules\Leave\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Leave\Models\LeavePolicy;
use Modules\Leave\Models\LeavePolicyTier;
use Tests\TestCase;

/**
 * Leave Policy Feature Test
 *
 * Tests the leave policy management functionality including PTO and Sick Leave
 * policy configuration, tier management, and validation.
 *
 * @author Dev Agent
 */
class LeavePolicyTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test constants
     */
    private const STANDARD_PTO_POLICY = 'Standard PTO Policy';

    private const STANDARD_SICK_LEAVE_POLICY = 'Standard Sick Leave Policy';

    private const COMPANY_WIDE_PTO_DESC = 'Company-wide PTO policy';

    private const COMPANY_WIDE_SICK_DESC = 'Company-wide sick leave policy';

    /**
     * Create a test user for authentication.
     */
    private function createUser(): User
    {
        return User::factory()->create();
    }

    /**
     * Test that the leave policy management page can be accessed.
     */
    public function test_can_access_leave_policy_management_page(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->get(route('leave.policies.index'));

        $response->assertStatus(200);
        $response->assertViewIs('leave::policies.index');
        $response->assertViewHas(['ptoPolicies', 'sickLeavePolicies']);
    }

    /**
     * Test successful PTO policy creation with valid data.
     */
    public function test_can_create_pto_policy_with_tiers(): void
    {
        $user = $this->createUser();
        $ptoData = [
            'name' => self::STANDARD_PTO_POLICY,
            'description' => self::COMPANY_WIDE_PTO_DESC,
            'initial_days' => 6,
            'tiers' => [
                [
                    'min_years' => 0,
                    'max_years' => 2,
                    'annual_days' => 15,
                ],
                [
                    'min_years' => 3,
                    'max_years' => null,
                    'annual_days' => 24,
                ],
            ],
        ];

        $response = $this->actingAs($user)->put(route('leave.policies.update-pto'), $ptoData);

        $response->assertRedirect(route('leave.policies.index'));
        $response->assertSessionHas('success', 'PTO policy updated successfully.');

        // Verify policy was created
        $this->assertDatabaseHas('leave_policies', [
            'name' => self::STANDARD_PTO_POLICY,
            'type' => 'pto',
            'initial_days' => 6,
            'is_active' => true,
        ]);

        // Verify tiers were created
        $policy = LeavePolicy::where('type', 'pto')->first();
        $this->assertCount(2, $policy->tiers);

        $this->assertDatabaseHas('leave_policy_tiers', [
            'leave_policy_id' => $policy->id,
            'min_years' => 0,
            'max_years' => 2,
            'annual_days' => 15,
            'monthly_accrual_rate' => 1.25,
        ]);

        $this->assertDatabaseHas('leave_policy_tiers', [
            'leave_policy_id' => $policy->id,
            'min_years' => 3,
            'max_years' => null,
            'annual_days' => 24,
            'monthly_accrual_rate' => 2.00,
        ]);
    }

    /**
     * Test successful Sick Leave policy creation with valid data.
     */
    public function test_can_create_sick_leave_policy(): void
    {
        $user = $this->createUser();

        $sickLeaveData = [
            'name' => self::STANDARD_SICK_LEAVE_POLICY,
            'description' => self::COMPANY_WIDE_SICK_DESC,
            'days' => 60,
            'period_in_years' => 3,
        ];

        $response = $this->actingAs($user)->put(route('leave.policies.update-sick-leave'), $sickLeaveData);

        $response->assertRedirect(route('leave.policies.index'));
        $response->assertSessionHas('success', 'Sick Leave policy updated successfully.');

        // Verify policy was created
        $this->assertDatabaseHas('leave_policies', [
            'name' => self::STANDARD_SICK_LEAVE_POLICY,
            'type' => 'sick_leave',
            'is_active' => true,
        ]);

        $policy = LeavePolicy::where('type', 'sick_leave')->first();
        $this->assertEquals(60, $policy->config['days']);
        $this->assertEquals(3, $policy->config['period_in_years']);
    }

    /**
     * Test PTO policy validation requires tiers.
     */
    public function test_pto_policy_validation_requires_tiers(): void
    {
        $user = $this->createUser();

        $ptoData = [
            'name' => self::STANDARD_PTO_POLICY,
            'initial_days' => 6,
            'tiers' => [],
        ];

        $response = $this->actingAs($user)->put(route('leave.policies.update-pto'), $ptoData);

        $response->assertSessionHasErrors(['tiers']);
    }

    /**
     * Test PTO policy validation for tier data.
     */
    public function test_pto_policy_validation_for_tier_data(): void
    {
        $user = $this->createUser();

        $ptoData = [
            'name' => self::STANDARD_PTO_POLICY,
            'initial_days' => 6,
            'tiers' => [
                [
                    'min_years' => -1, // Invalid: negative years
                    'max_years' => 2,
                    'annual_days' => 0, // Invalid: zero days
                ],
            ],
        ];

        $response = $this->actingAs($user)->put(route('leave.policies.update-pto'), $ptoData);

        $response->assertSessionHasErrors(['tiers.0.min_years', 'tiers.0.annual_days']);
    }

    /**
     * Test Sick Leave policy validation.
     */
    public function test_sick_leave_policy_validation(): void
    {
        $user = $this->createUser();

        $sickLeaveData = [
            'name' => '', // Invalid: empty name
            'days' => 0, // Invalid: zero days
            'period_in_years' => 0, // Invalid: zero period
        ];

        $response = $this->actingAs($user)->put(route('leave.policies.update-sick-leave'), $sickLeaveData);

        $response->assertSessionHasErrors(['name', 'days', 'period_in_years']);
    }

    /**
     * Test updating existing PTO policy replaces tiers.
     */
    public function test_updating_pto_policy_replaces_existing_tiers(): void
    {
        $user = $this->createUser();

        // Create initial policy with one tier
        $policy = LeavePolicy::create([
            'name' => 'Old PTO Policy',
            'type' => 'pto',
            'initial_days' => 5,
            'is_active' => true,
        ]);

        $tier = LeavePolicyTier::create([
            'leave_policy_id' => $policy->id,
            'min_years' => 0,
            'max_years' => 5,
            'annual_days' => 10,
        ]);

        // Update with new configuration
        $ptoData = [
            'name' => 'Updated PTO Policy',
            'initial_days' => 8,
            'tiers' => [
                [
                    'min_years' => 0,
                    'max_years' => 1,
                    'annual_days' => 12,
                ],
                [
                    'min_years' => 2,
                    'max_years' => null,
                    'annual_days' => 20,
                ],
            ],
        ];

        $response = $this->actingAs($user)->put(route('leave.policies.update-pto'), $ptoData);

        $response->assertRedirect(route('leave.policies.index'));

        // Verify policy was updated
        $policy->refresh();
        $this->assertEquals('Updated PTO Policy', $policy->name);
        $this->assertEquals(8, $policy->initial_days);

        // Verify old tier was deleted and new tiers created
        $this->assertDatabaseMissing('leave_policy_tiers', ['id' => $tier->id]);
        $this->assertCount(2, $policy->tiers);
    }

    /**
     * Test monthly accrual rate calculation.
     */
    public function test_monthly_accrual_rate_calculation(): void
    {
        $policy = LeavePolicy::create([
            'name' => 'Test PTO Policy',
            'type' => 'pto',
            'initial_days' => 6,
            'is_active' => true,
        ]);

        $tier = LeavePolicyTier::create([
            'leave_policy_id' => $policy->id,
            'min_years' => 0,
            'max_years' => 2,
            'annual_days' => 15,
        ]);

        // Verify monthly accrual rate was calculated automatically
        $this->assertEquals(1.25, $tier->monthly_accrual_rate);

        // Test with different annual days
        $tier2 = LeavePolicyTier::create([
            'leave_policy_id' => $policy->id,
            'min_years' => 3,
            'max_years' => null,
            'annual_days' => 24,
        ]);

        $this->assertEquals(2.00, $tier2->monthly_accrual_rate);
    }

    /**
     * Test policy scopes work correctly.
     */
    public function test_policy_scopes(): void
    {
        $ptoPolicy = LeavePolicy::create([
            'name' => 'PTO Policy',
            'type' => 'pto',
            'is_active' => true,
        ]);

        $sickPolicy = LeavePolicy::create([
            'name' => 'Sick Leave Policy',
            'type' => 'sick_leave',
            'is_active' => true,
        ]);

        $inactivePolicy = LeavePolicy::create([
            'name' => 'Inactive Policy',
            'type' => 'pto',
            'is_active' => false,
        ]);

        // Test active scope
        $activePolicies = LeavePolicy::active()->get();
        $this->assertCount(2, $activePolicies);
        $this->assertFalse($activePolicies->contains($inactivePolicy));

        // Test PTO scope
        $ptoPolicies = LeavePolicy::pto()->get();
        $this->assertCount(2, $ptoPolicies); // Including inactive PTO policy
        $this->assertTrue($ptoPolicies->contains($ptoPolicy));
        $this->assertFalse($ptoPolicies->contains($sickPolicy));

        // Test sick leave scope
        $sickPolicies = LeavePolicy::sickLeave()->get();
        $this->assertCount(1, $sickPolicies);
        $this->assertTrue($sickPolicies->contains($sickPolicy));
    }
}
