<?php

namespace Modules\Attendance\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Modules\Attendance\Models\AttendanceRule;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * AttendanceRuleTest
 *
 * Tests the attendance rule functionality including flexible hours creation and management
 *
 * @author GitHub Copilot
 */
class AttendanceRuleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    // Test constants to avoid duplication
    private const TEST_RULE_NAME = 'Test Rule';
    private const TEST_START_TIME = '08:00';
    private const TEST_END_TIME = '10:00';
    private const TEST_LATE_MINUTES = 15;
    private const TEST_PENALTY_MINUTES = 30;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test that attendance rules index page is accessible
     */
    public function test_attendance_rules_index_page_is_accessible(): void
    {
        $response = $this->actingAs($this->user)->get(route('attendance.rules.index'));

        $response->assertStatus(200);
        $response->assertViewIs('attendance::rules.index');
        $response->assertSee('Attendance Rules');
    }

    /**
     * Test that create rule page is accessible
     */
    public function test_create_rule_page_is_accessible(): void
    {
        $response = $this->actingAs($this->user)->get(route('attendance.rules.create'));

        $response->assertStatus(200);
        $response->assertViewIs('attendance::rules.create');
        $response->assertSee('Create Flexible Hours Rule');
    }

    /**
     * Test creating a new flexible hours rule
     */
    public function test_can_create_flexible_hours_rule(): void
    {
        $data = [
            'rule_name' => 'Flexible Start Time',
            'start_time_from' => '08:00',
            'start_time_to' => '10:00',
        ];

        $response = $this->actingAs($this->user)->post(route('attendance.rules.store'), $data);

        $response->assertRedirect(route('attendance.rules.index'));
        $response->assertSessionHas('success', 'Flexible hours rule created successfully!');

        $this->assertDatabaseHas('attendance_rules', [
            'rule_name' => 'Flexible Start Time',
            'rule_type' => AttendanceRule::TYPE_FLEXIBLE_HOURS,
        ]);

        $rule = AttendanceRule::where('rule_name', 'Flexible Start Time')->first();
        $this->assertEquals(['from' => '08:00', 'to' => '10:00'], $rule->config);
    }

    /**
     * Test updating existing flexible hours rule (upsert functionality)
     */
    public function test_can_update_existing_flexible_hours_rule(): void
    {
        // Create an existing rule
        $existingRule = AttendanceRule::create([
            'rule_name' => 'Old Flexible Hours',
            'rule_type' => AttendanceRule::TYPE_FLEXIBLE_HOURS,
            'config' => ['from' => '07:00', 'to' => '09:00'],
        ]);

        $data = [
            'rule_name' => 'Updated Flexible Hours',
            'start_time_from' => '08:30',
            'start_time_to' => '10:30',
        ];

        $response = $this->actingAs($this->user)->post(route('attendance.rules.store'), $data);

        $response->assertRedirect(route('attendance.rules.index'));
        $response->assertSessionHas('success', 'Flexible hours rule updated successfully!');

        // Should still have only one flexible hours rule
        $this->assertEquals(1, AttendanceRule::where('rule_type', AttendanceRule::TYPE_FLEXIBLE_HOURS)->count());

        // Rule should be updated
        $existingRule->refresh();
        $this->assertEquals('Updated Flexible Hours', $existingRule->rule_name);
        $this->assertEquals(['from' => '08:30', 'to' => '10:30'], $existingRule->config);
    }

    /**
     * Test validation: rule name is required
     */
    public function test_rule_name_is_required(): void
    {
        $data = [
            'rule_name' => '',
            'start_time_from' => '08:00',
            'start_time_to' => '10:00',
        ];

        $response = $this->actingAs($this->user)->post(route('attendance.rules.store'), $data);

        $response->assertSessionHasErrors(['rule_name']);
    }

    /**
     * Test validation: from time is required
     */
    public function test_from_time_is_required(): void
    {
        $data = [
            'rule_name' => 'Test Rule',
            'start_time_from' => '',
            'start_time_to' => '10:00',
        ];

        $response = $this->actingAs($this->user)->post(route('attendance.rules.store'), $data);

        $response->assertSessionHasErrors(['start_time_from']);
    }

    /**
     * Test validation: to time is required
     */
    public function test_to_time_is_required(): void
    {
        $data = [
            'rule_name' => 'Test Rule',
            'start_time_from' => '08:00',
            'start_time_to' => '',
        ];

        $response = $this->actingAs($this->user)->post(route('attendance.rules.store'), $data);

        $response->assertSessionHasErrors(['start_time_to']);
    }

    /**
     * Test validation: to time must be after from time
     */
    public function test_to_time_must_be_after_from_time(): void
    {
        $data = [
            'rule_name' => 'Test Rule',
            'start_time_from' => '10:00',
            'start_time_to' => '08:00', // Earlier than from time
        ];

        $response = $this->actingAs($this->user)->post(route('attendance.rules.store'), $data);

        $response->assertSessionHasErrors(['start_time_to']);
    }

    /**
     * Test validation: same from and to time should fail
     */
    public function test_same_from_and_to_time_should_fail(): void
    {
        $data = [
            'rule_name' => 'Test Rule',
            'start_time_from' => '09:00',
            'start_time_to' => '09:00', // Same as from time
        ];

        $response = $this->actingAs($this->user)->post(route('attendance.rules.store'), $data);

        $response->assertSessionHasErrors(['start_time_to']);
    }

    /**
     * Test validation: invalid time format should fail
     */
    public function test_invalid_time_format_should_fail(): void
    {
        $data = [
            'rule_name' => 'Test Rule',
            'start_time_from' => 'invalid-time',
            'start_time_to' => '10:00',
        ];

        $response = $this->actingAs($this->user)->post(route('attendance.rules.store'), $data);

        $response->assertSessionHasErrors(['start_time_from']);
    }

    /**
     * Test AttendanceRule model methods
     */
    public function test_attendance_rule_model_methods(): void
    {
        // Test getFlexibleHoursRule when none exists
        $this->assertNull(AttendanceRule::getFlexibleHoursRule());

        // Create a flexible hours rule
        $rule = AttendanceRule::create([
            'rule_name' => 'Flexible Hours',
            'rule_type' => AttendanceRule::TYPE_FLEXIBLE_HOURS,
            'config' => ['from' => '08:00', 'to' => '10:00'],
        ]);

        // Test getFlexibleHoursRule returns the rule
        $foundRule = AttendanceRule::getFlexibleHoursRule();
        $this->assertNotNull($foundRule);
        $this->assertEquals($rule->id, $foundRule->id);

        // Test available types
        $types = AttendanceRule::getAvailableTypes();
        $this->assertContains(AttendanceRule::TYPE_FLEXIBLE_HOURS, $types);
        $this->assertContains(AttendanceRule::TYPE_LATE_PENALTY, $types);
        $this->assertContains(AttendanceRule::TYPE_PERMISSION, $types);
        $this->assertContains(AttendanceRule::TYPE_WFH_POLICY, $types);

        // Test byType scope
        $flexibleRules = AttendanceRule::byType(AttendanceRule::TYPE_FLEXIBLE_HOURS)->get();
        $this->assertEquals(1, $flexibleRules->count());
        $this->assertEquals($rule->id, $flexibleRules->first()->id);
    }

    /**
     * Test that rules are displayed in the index view
     */
    public function test_rules_are_displayed_in_index(): void
    {
        $rule = AttendanceRule::create([
            'rule_name' => 'Test Flexible Hours',
            'rule_type' => AttendanceRule::TYPE_FLEXIBLE_HOURS,
            'config' => ['from' => '08:00', 'to' => '10:00'],
        ]);

        $response = $this->actingAs($this->user)->get(route('attendance.rules.index'));

        $response->assertStatus(200);
        $response->assertSee('Test Flexible Hours');
        $response->assertSee('08:00 - 10:00');
        $response->assertSee('Flexible Hours');
    }

    /**
     * Test that existing rule data is populated in create form
     */
    public function test_existing_rule_data_is_populated_in_create_form(): void
    {
        $rule = AttendanceRule::create([
            'rule_name' => 'Existing Rule',
            'rule_type' => AttendanceRule::TYPE_FLEXIBLE_HOURS,
            'config' => ['from' => '07:30', 'to' => '09:30'],
        ]);

        $response = $this->actingAs($this->user)->get(route('attendance.rules.create'));

        $response->assertStatus(200);
        $response->assertSee('value="Existing Rule"', false);
        $response->assertSee('value="07:30"', false);
        $response->assertSee('value="09:30"', false);
        $response->assertSee('Update Flexible Hours Rule');
    }

    // ===========================================
    // Late Penalty Rule Tests
    // ===========================================

    /**
     * Test that a late penalty rule can be created successfully
     */
    public function test_late_penalty_rule_can_be_created(): void
    {
        $ruleData = [
            'rule_name' => '15 Minutes Late Penalty',
            'rule_type' => 'late_penalty',
            'late_minutes' => 15,
            'penalty_minutes' => 30,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('attendance.rules.store'), $ruleData);

        $response->assertRedirect(route('attendance.rules.index'));
        $response->assertSessionHas('success', 'Late penalty rule created successfully!');

        $this->assertDatabaseHas('attendance_rules', [
            'rule_name' => '15 Minutes Late Penalty',
            'rule_type' => 'late_penalty',
        ]);

        // Check the config separately due to JSON formatting
        $rule = AttendanceRule::where('rule_name', '15 Minutes Late Penalty')->first();
        $this->assertEquals(['late_minutes' => 15, 'penalty_minutes' => 30], $rule->config);
    }

    /**
     * Test that multiple late penalty rules can be created
     */
    public function test_multiple_late_penalty_rules_can_be_created(): void
    {
        // Create first rule
        $firstRule = [
            'rule_name' => '15 Minutes Late',
            'rule_type' => 'late_penalty',
            'late_minutes' => 15,
            'penalty_minutes' => 30,
        ];

        $this->actingAs($this->user)
            ->post(route('attendance.rules.store'), $firstRule);

        // Create second rule
        $secondRule = [
            'rule_name' => '30 Minutes Late',
            'rule_type' => 'late_penalty',
            'late_minutes' => 30,
            'penalty_minutes' => 60,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('attendance.rules.store'), $secondRule);

        $response->assertRedirect(route('attendance.rules.index'));

        // Both rules should exist in database
        $this->assertDatabaseHas('attendance_rules', [
            'rule_name' => '15 Minutes Late',
            'rule_type' => 'late_penalty',
        ]);

        $this->assertDatabaseHas('attendance_rules', [
            'rule_name' => '30 Minutes Late',
            'rule_type' => 'late_penalty',
        ]);

        $this->assertEquals(2, AttendanceRule::where('rule_type', 'late_penalty')->count());
    }

    /**
     * Test validation for late penalty rule creation
     */
    public function test_late_penalty_rule_validation(): void
    {
        // Test missing rule name
        $response = $this->actingAs($this->user)
            ->post(route('attendance.rules.store'), [
                'rule_type' => 'late_penalty',
                'late_minutes' => self::TEST_LATE_MINUTES,
                'penalty_minutes' => self::TEST_PENALTY_MINUTES,
            ]);

        $response->assertSessionHasErrors(['rule_name']);

        // Test missing late_minutes
        $response = $this->actingAs($this->user)
            ->post(route('attendance.rules.store'), [
                'rule_name' => self::TEST_RULE_NAME,
                'rule_type' => 'late_penalty',
                'penalty_minutes' => self::TEST_PENALTY_MINUTES,
            ]);

        $response->assertSessionHasErrors(['late_minutes']);

        // Test missing penalty_minutes
        $response = $this->actingAs($this->user)
            ->post(route('attendance.rules.store'), [
                'rule_name' => self::TEST_RULE_NAME,
                'rule_type' => 'late_penalty',
                'late_minutes' => self::TEST_LATE_MINUTES,
            ]);

        $response->assertSessionHasErrors(['penalty_minutes']);

        // Test invalid values (less than 1)
        $response = $this->actingAs($this->user)
            ->post(route('attendance.rules.store'), [
                'rule_name' => self::TEST_RULE_NAME,
                'rule_type' => 'late_penalty',
                'late_minutes' => 0,
                'penalty_minutes' => 0,
            ]);

        $response->assertSessionHasErrors(['late_minutes', 'penalty_minutes']);

        // Test invalid values (more than 1440)
        $response = $this->actingAs($this->user)
            ->post(route('attendance.rules.store'), [
                'rule_name' => self::TEST_RULE_NAME,
                'rule_type' => 'late_penalty',
                'late_minutes' => 1500,
                'penalty_minutes' => 1500,
            ]);

        $response->assertSessionHasErrors(['late_minutes', 'penalty_minutes']);
    }

    /**
     * Test that late penalty rules can be deleted
     */
    public function test_late_penalty_rule_can_be_deleted(): void
    {
        $rule = AttendanceRule::create([
            'rule_name' => 'Test Penalty Rule',
            'rule_type' => AttendanceRule::TYPE_LATE_PENALTY,
            'config' => ['late_minutes' => 15, 'penalty_minutes' => 30],
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('attendance.rules.destroy', $rule));

        $response->assertRedirect(route('attendance.rules.index'));
        $response->assertSessionHas('success', "Rule 'Test Penalty Rule' deleted successfully!");

        $this->assertDatabaseMissing('attendance_rules', [
            'id' => $rule->id,
        ]);
    }

    /**
     * Test that index page displays late penalty rules correctly
     */
    public function test_index_page_displays_late_penalty_rules(): void
    {
        // Create a late penalty rule
        AttendanceRule::create([
            'rule_name' => 'Test Late Penalty',
            'rule_type' => AttendanceRule::TYPE_LATE_PENALTY,
            'config' => ['late_minutes' => 20, 'penalty_minutes' => 40],
        ]);

        $response = $this->actingAs($this->user)->get(route('attendance.rules.index'));

        $response->assertStatus(200);
        $response->assertSee('Late-in Penalties');
        $response->assertSee('Test Late Penalty');
        $response->assertSee('20 minutes');
        $response->assertSee('40 minutes');
        $response->assertSee('Delete');
    }

    /**
     * Test that penalty rules and flexible hours rules are displayed separately
     */
    public function test_rules_are_displayed_in_separate_sections(): void
    {
        // Create both types of rules
        AttendanceRule::create([
            'rule_name' => 'Flexible Hours Rule',
            'rule_type' => AttendanceRule::TYPE_FLEXIBLE_HOURS,
            'config' => ['from' => '08:00', 'to' => '10:00'],
        ]);

        AttendanceRule::create([
            'rule_name' => 'Late Penalty Rule',
            'rule_type' => AttendanceRule::TYPE_LATE_PENALTY,
            'config' => ['late_minutes' => 15, 'penalty_minutes' => 30],
        ]);

        $response = $this->actingAs($this->user)->get(route('attendance.rules.index'));

        $response->assertStatus(200);

        // Check both sections exist
        $response->assertSee('Flexible Hours Rules');
        $response->assertSee('Late-in Penalties');

        // Check rules appear in correct sections
        $response->assertSee('Flexible Hours Rule');
        $response->assertSee('Late Penalty Rule');
        $response->assertSee('08:00 - 10:00');
        $response->assertSee('15 minutes');
        $response->assertSee('30 minutes');
    }

    // ===========================================
    // Permission Rule Tests
    // ===========================================

    /**
     * Test that a permission rule can be created successfully
     */
    public function test_permission_rule_can_be_created(): void
    {
        $ruleData = [
            'rule_type' => 'permission',
            'max_per_month' => 2,
            'minutes_per_permission' => 60,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('attendance.rules.store'), $ruleData);

        $response->assertRedirect(route('attendance.rules.index'));
        $response->assertSessionHas('success', 'Employee permissions configuration created successfully!');

        $this->assertDatabaseHas('attendance_rules', [
            'rule_name' => 'Employee Permissions',
            'rule_type' => 'permission',
        ]);

        // Check the config separately due to JSON formatting
        $rule = AttendanceRule::where('rule_type', 'permission')->first();
        $this->assertEquals(['max_per_month' => 2, 'minutes_per_permission' => 60], $rule->config);
    }

    /**
     * Test that permission rule can be updated (upsert functionality)
     */
    public function test_permission_rule_can_be_updated(): void
    {
        // Create an existing permission rule
        $existingRule = AttendanceRule::create([
            'rule_name' => 'Employee Permissions',
            'rule_type' => AttendanceRule::TYPE_PERMISSION,
            'config' => ['max_per_month' => 1, 'minutes_per_permission' => 30],
        ]);

        $updateData = [
            'rule_type' => 'permission',
            'max_per_month' => 3,
            'minutes_per_permission' => 90,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('attendance.rules.store'), $updateData);

        $response->assertRedirect(route('attendance.rules.index'));
        $response->assertSessionHas('success', 'Employee permissions configuration updated successfully!');

        // Should still have only one permission rule
        $this->assertEquals(1, AttendanceRule::where('rule_type', 'permission')->count());

        // Rule should be updated
        $existingRule->refresh();
        $this->assertEquals(['max_per_month' => 3, 'minutes_per_permission' => 90], $existingRule->config);
    }

    /**
     * Test validation for permission rule creation
     */
    public function test_permission_rule_validation(): void
    {
        // Test missing max_per_month
        $response = $this->actingAs($this->user)
            ->post(route('attendance.rules.store'), [
                'rule_type' => 'permission',
                'minutes_per_permission' => 60,
            ]);

        $response->assertSessionHasErrors(['max_per_month']);

        // Test missing minutes_per_permission
        $response = $this->actingAs($this->user)
            ->post(route('attendance.rules.store'), [
                'rule_type' => 'permission',
                'max_per_month' => 2,
            ]);

        $response->assertSessionHasErrors(['minutes_per_permission']);

        // Test invalid values (less than 1)
        $response = $this->actingAs($this->user)
            ->post(route('attendance.rules.store'), [
                'rule_type' => 'permission',
                'max_per_month' => 0,
                'minutes_per_permission' => 0,
            ]);

        $response->assertSessionHasErrors(['max_per_month', 'minutes_per_permission']);

        // Test invalid values (max_per_month > 31)
        $response = $this->actingAs($this->user)
            ->post(route('attendance.rules.store'), [
                'rule_type' => 'permission',
                'max_per_month' => 32,
                'minutes_per_permission' => 60,
            ]);

        $response->assertSessionHasErrors(['max_per_month']);

        // Test invalid values (minutes_per_permission > 1440)
        $response = $this->actingAs($this->user)
            ->post(route('attendance.rules.store'), [
                'rule_type' => 'permission',
                'max_per_month' => 2,
                'minutes_per_permission' => 1500,
            ]);

        $response->assertSessionHasErrors(['minutes_per_permission']);
    }

    /**
     * Test that index page displays permission rule form correctly
     */
    public function test_index_page_displays_permission_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('attendance.rules.index'));

        $response->assertStatus(200);
        $response->assertSee('Employee Permissions');
        $response->assertSee('Max Permissions Per Month');
        $response->assertSee('Length of Each Permission (minutes)');
        $response->assertSee('Save Configuration');
    }

    /**
     * Test that permission rule form is populated with existing data
     */
    public function test_permission_form_is_populated_with_existing_data(): void
    {
        // Create an existing permission rule
        AttendanceRule::create([
            'rule_name' => 'Employee Permissions',
            'rule_type' => AttendanceRule::TYPE_PERMISSION,
            'config' => ['max_per_month' => 3, 'minutes_per_permission' => 120],
        ]);

        $response = $this->actingAs($this->user)->get(route('attendance.rules.index'));

        $response->assertStatus(200);
        $response->assertSee('value="3"', false);
        $response->assertSee('value="120"', false);
        $response->assertSee('Update Configuration');
    }

    /**
     * Test AttendanceRule::getPermissionRule method
     */
    public function test_get_permission_rule_method(): void
    {
        // Test when no permission rule exists
        $this->assertNull(AttendanceRule::getPermissionRule());

        // Create a permission rule
        $rule = AttendanceRule::create([
            'rule_name' => 'Employee Permissions',
            'rule_type' => AttendanceRule::TYPE_PERMISSION,
            'config' => ['max_per_month' => 2, 'minutes_per_permission' => 60],
        ]);

        // Test getPermissionRule returns the rule
        $foundRule = AttendanceRule::getPermissionRule();
        $this->assertNotNull($foundRule);
        $this->assertEquals($rule->id, $foundRule->id);
        $this->assertEquals(['max_per_month' => 2, 'minutes_per_permission' => 60], $foundRule->config);
    }

    /**
     * Test creating a new WFH policy rule
     */
    public function test_can_create_wfh_policy_rule(): void
    {
        $data = [
            'rule_type' => 'wfh_policy',
            'max_days_per_month' => 5,
            'attendance_percentage' => 80,
        ];

        $response = $this->actingAs($this->user)->post(route('attendance.rules.store'), $data);

        $response->assertRedirect(route('attendance.rules.index'));
        $response->assertSessionHas('success', 'Work-From-Home policy created successfully!');

        $this->assertDatabaseHas('attendance_rules', [
            'rule_name' => 'Work-From-Home Policy',
            'rule_type' => AttendanceRule::TYPE_WFH_POLICY,
        ]);

        $rule = AttendanceRule::where('rule_type', AttendanceRule::TYPE_WFH_POLICY)->first();
        $this->assertEquals(['max_days_per_month' => 5, 'attendance_percentage' => 80], $rule->config);
    }

    /**
     * Test updating existing WFH policy rule (upsert functionality)
     */
    public function test_can_update_existing_wfh_policy_rule(): void
    {
        // Create an existing WFH policy rule
        $existingRule = AttendanceRule::create([
            'rule_name' => 'Work-From-Home Policy',
            'rule_type' => AttendanceRule::TYPE_WFH_POLICY,
            'config' => ['max_days_per_month' => 3, 'attendance_percentage' => 70],
        ]);

        $data = [
            'rule_type' => 'wfh_policy',
            'max_days_per_month' => 8,
            'attendance_percentage' => 90,
        ];

        $response = $this->actingAs($this->user)->post(route('attendance.rules.store'), $data);

        $response->assertRedirect(route('attendance.rules.index'));
        $response->assertSessionHas('success', 'Work-From-Home policy updated successfully!');

        // Should still be only one WFH policy rule
        $this->assertEquals(1, AttendanceRule::where('rule_type', AttendanceRule::TYPE_WFH_POLICY)->count());

        // Check the rule was updated
        $existingRule->refresh();
        $this->assertEquals(['max_days_per_month' => 8, 'attendance_percentage' => 90], $existingRule->config);
    }

    /**
     * Test WFH policy validation rules
     */
    public function test_wfh_policy_validation_rules(): void
    {
        // Test with invalid data
        $invalidData = [
            'rule_type' => 'wfh_policy',
            'max_days_per_month' => 0, // Invalid: less than 1
            'attendance_percentage' => 150, // Invalid: greater than 100
        ];

        $response = $this->actingAs($this->user)->post(route('attendance.rules.store'), $invalidData);

        $response->assertSessionHasErrors(['max_days_per_month', 'attendance_percentage']);

        // Test with missing data
        $missingData = [
            'rule_type' => 'wfh_policy',
        ];

        $response = $this->actingAs($this->user)->post(route('attendance.rules.store'), $missingData);

        $response->assertSessionHasErrors(['max_days_per_month', 'attendance_percentage']);

        // Test with boundary values
        $boundaryData = [
            'rule_type' => 'wfh_policy',
            'max_days_per_month' => 31, // Maximum allowed
            'attendance_percentage' => 0, // Minimum allowed
        ];

        $response = $this->actingAs($this->user)->post(route('attendance.rules.store'), $boundaryData);

        $response->assertRedirect(route('attendance.rules.index'));
        $response->assertSessionHas('success', 'Work-From-Home policy created successfully!');
    }

    /**
     * Test getWfhPolicyRule method
     */
    public function test_get_wfh_policy_rule(): void
    {
        // Test when no WFH policy rule exists
        $this->assertNull(AttendanceRule::getWfhPolicyRule());

        // Create a WFH policy rule
        $rule = AttendanceRule::create([
            'rule_name' => 'Work-From-Home Policy',
            'rule_type' => AttendanceRule::TYPE_WFH_POLICY,
            'config' => ['max_days_per_month' => 5, 'attendance_percentage' => 80],
        ]);

        // Test getWfhPolicyRule returns the rule
        $foundRule = AttendanceRule::getWfhPolicyRule();
        $this->assertNotNull($foundRule);
        $this->assertEquals($rule->id, $foundRule->id);
        $this->assertEquals(['max_days_per_month' => 5, 'attendance_percentage' => 80], $foundRule->config);
    }
}
