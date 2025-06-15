<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Modules\Attendance\Models\AttendanceRule;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Attendance Rules Integration Test
 *
 * Tests the complete attendance rules functionality including:
 * - UI access and navigation
 * - Rule creation and validation
 * - Upsert logic for flexible hours
 *
 * @author GitHub Copilot
 */
class AttendanceRulesIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test attendance rules index page displays correctly
     */
    public function test_attendance_rules_index_displays_correctly(): void
    {
        $response = $this->actingAs($this->user)->get(route('attendance.rules.index'));

        $response->assertStatus(200);
        $response->assertSee('Attendance Rules');
        $response->assertSee('Create Rule');
        $response->assertSee('No Rules Found'); // Initially empty
    }

    /**
     * Test create rule page displays correctly
     */
    public function test_create_rule_page_displays_correctly(): void
    {
        $response = $this->actingAs($this->user)->get(route('attendance.rules.create'));

        $response->assertStatus(200);
        $response->assertSee('Create Flexible Hours Rule');
        $response->assertSee('rule_name');
        $response->assertSee('start_time_from');
        $response->assertSee('start_time_to');
    }

    /**
     * Test successful creation of flexible hours rule
     */
    public function test_successful_creation_of_flexible_hours_rule(): void
    {
        $data = [
            'rule_name' => 'Standard Flexible Hours',
            'start_time_from' => '08:00',
            'start_time_to' => '10:00',
        ];

        $response = $this->actingAs($this->user)->post(route('attendance.rules.store'), $data);

        $response->assertRedirect(route('attendance.rules.index'));
        $response->assertSessionHas('success', 'Flexible hours rule created successfully!');

        // Verify database record
        $this->assertDatabaseHas('attendance_rules', [
            'rule_name' => 'Standard Flexible Hours',
            'rule_type' => 'flexible_hours',
        ]);

        // Verify config JSON
        $rule = AttendanceRule::where('rule_name', 'Standard Flexible Hours')->first();
        $this->assertEquals(['from' => '08:00', 'to' => '10:00'], $rule->config);
    }

    /**
     * Test upsert functionality - updating existing flexible hours rule
     */
    public function test_upsert_functionality_updates_existing_rule(): void
    {
        // Create initial rule
        $initialRule = AttendanceRule::create([
            'rule_name' => 'Initial Flexible Hours',
            'rule_type' => AttendanceRule::TYPE_FLEXIBLE_HOURS,
            'config' => ['from' => '07:00', 'to' => '09:00'],
        ]);

        $updateData = [
            'rule_name' => 'Updated Flexible Hours',
            'start_time_from' => '08:30',
            'start_time_to' => '10:30',
        ];

        $response = $this->actingAs($this->user)->post(route('attendance.rules.store'), $updateData);

        $response->assertRedirect(route('attendance.rules.index'));
        $response->assertSessionHas('success', 'Flexible hours rule updated successfully!');

        // Should still have only one flexible hours rule
        $this->assertEquals(1, AttendanceRule::where('rule_type', 'flexible_hours')->count());

        // Rule should be updated
        $initialRule->refresh();
        $this->assertEquals('Updated Flexible Hours', $initialRule->rule_name);
        $this->assertEquals(['from' => '08:30', 'to' => '10:30'], $initialRule->config);
    }

    /**
     * Test validation: rule name is required
     */
    public function test_validation_rule_name_required(): void
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
    public function test_validation_from_time_required(): void
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
    public function test_validation_to_time_required(): void
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
    public function test_validation_to_time_must_be_after_from_time(): void
    {
        $data = [
            'rule_name' => 'Invalid Rule',
            'start_time_from' => '10:00',
            'start_time_to' => '08:00',
        ];

        $response = $this->actingAs($this->user)->post(route('attendance.rules.store'), $data);
        $response->assertSessionHasErrors(['start_time_to']);
    }

    /**
     * Test that rules are displayed in index after creation
     */
    public function test_rules_displayed_in_index_after_creation(): void
    {
        $rule = AttendanceRule::create([
            'rule_name' => 'Display Test Rule',
            'rule_type' => AttendanceRule::TYPE_FLEXIBLE_HOURS,
            'config' => ['from' => '08:15', 'to' => '10:15'],
        ]);

        $response = $this->actingAs($this->user)->get(route('attendance.rules.index'));

        $response->assertStatus(200);
        $response->assertSee('Display Test Rule');
        $response->assertSee('08:15 - 10:15');
        $response->assertSee('Flexible hours');
    }

    /**
     * Test AttendanceRule model helper methods
     */
    public function test_attendance_rule_model_methods(): void
    {
        // Test getFlexibleHoursRule when none exists
        $this->assertNull(AttendanceRule::getFlexibleHoursRule());

        // Create a flexible hours rule
        $rule = AttendanceRule::create([
            'rule_name' => 'Model Test Rule',
            'rule_type' => AttendanceRule::TYPE_FLEXIBLE_HOURS,
            'config' => ['from' => '09:00', 'to' => '11:00'],
        ]);

        // Test getFlexibleHoursRule returns the rule
        $foundRule = AttendanceRule::getFlexibleHoursRule();
        $this->assertNotNull($foundRule);
        $this->assertEquals($rule->id, $foundRule->id);

        // Test byType scope
        $flexibleRules = AttendanceRule::byType(AttendanceRule::TYPE_FLEXIBLE_HOURS)->get();
        $this->assertEquals(1, $flexibleRules->count());
        $this->assertEquals($rule->id, $flexibleRules->first()->id);
    }
}
