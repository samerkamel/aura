<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Modules\Attendance\Models\AttendanceRule;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Simple Attendance Rule Test
 *
 * Basic test to verify attendance rule functionality
 */
class SimpleAttendanceRuleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating a flexible hours rule
     */
    public function test_can_create_flexible_hours_rule(): void
    {
        $user = User::factory()->create();

        $data = [
            'rule_name' => 'Flexible Start Time',
            'start_time_from' => '08:00',
            'start_time_to' => '10:00',
        ];

        $response = $this->actingAs($user)->post(route('attendance.rules.store'), $data);

        $response->assertRedirect(route('attendance.rules.index'));

        $this->assertDatabaseHas('attendance_rules', [
            'rule_name' => 'Flexible Start Time',
            'rule_type' => 'flexible_hours',
        ]);
    }

    /**
     * Test validation fails when to time is before from time
     */
    public function test_validation_fails_when_to_time_is_before_from_time(): void
    {
        $user = User::factory()->create();

        $data = [
            'rule_name' => 'Invalid Rule',
            'start_time_from' => '10:00',
            'start_time_to' => '08:00',
        ];

        $response = $this->actingAs($user)->post(route('attendance.rules.store'), $data);

        $response->assertSessionHasErrors(['start_time_to']);
    }
}
