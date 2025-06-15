<?php

namespace Modules\Attendance\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Attendance\Models\Setting;
use App\Models\User;

/**
 * Attendance Settings Test
 *
 * Tests the attendance settings functionality including
 * form display, validation, and settings persistence.
 *
 * @author Dev Agent
 */
class AttendanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create and authenticate a user for tests
     */
    protected function authenticateUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        return $user;
    }

    /** @test */
    public function can_access_attendance_settings_page()
    {
        $this->authenticateUser();

        $response = $this->get(route('attendance.settings.index'));

        $response->assertOk();
        $response->assertViewIs('attendance::settings.index');
        $response->assertViewHas(['workHoursPerDay', 'weekendDays']);
    }

    /** @test */
    public function settings_page_shows_default_values_when_no_settings_exist()
    {
        $this->authenticateUser();

        $response = $this->get(route('attendance.settings.index'));

        $response->assertOk();
        $response->assertViewHas('workHoursPerDay', 8);
        $response->assertViewHas('weekendDays', ['friday', 'saturday']);
    }

    /** @test */
    public function settings_page_shows_existing_values_when_settings_exist()
    {
        $this->authenticateUser();

        Setting::set('work_hours_per_day', 7.5);
        Setting::set('weekend_days', ['saturday', 'sunday']);

        $response = $this->get(route('attendance.settings.index'));

        $response->assertOk();
        $response->assertViewHas('workHoursPerDay', 7.5);
        $response->assertViewHas('weekendDays', ['saturday', 'sunday']);
    }

    /** @test */
    public function can_update_attendance_settings_with_valid_data()
    {
        $this->authenticateUser();

        $response = $this->put(route('attendance.settings.update'), [
            'work_hours_per_day' => 8.5,
            'weekend_days' => ['friday', 'saturday']
        ]);

        $response->assertRedirect(route('attendance.settings.index'));
        $response->assertSessionHas('success', 'Attendance settings updated successfully.');

        // Verify settings were saved
        $this->assertEquals(8.5, Setting::get('work_hours_per_day'));
        $this->assertEquals(['friday', 'saturday'], Setting::get('weekend_days'));
    }

    /** @test */
    public function validation_fails_with_missing_work_hours()
    {
        $this->authenticateUser();

        $response = $this->put(route('attendance.settings.update'), [
            'weekend_days' => ['friday', 'saturday']
        ]);

        $response->assertSessionHasErrors(['work_hours_per_day']);
    }

    /** @test */
    public function validation_fails_with_invalid_work_hours()
    {
        $this->authenticateUser();

        $response = $this->put(route('attendance.settings.update'), [
            'work_hours_per_day' => -1,
            'weekend_days' => ['friday', 'saturday']
        ]);

        $response->assertSessionHasErrors(['work_hours_per_day']);

        $response = $this->put(route('attendance.settings.update'), [
            'work_hours_per_day' => 25,
            'weekend_days' => ['friday', 'saturday']
        ]);

        $response->assertSessionHasErrors(['work_hours_per_day']);

        $response = $this->put(route('attendance.settings.update'), [
            'work_hours_per_day' => 'invalid',
            'weekend_days' => ['friday', 'saturday']
        ]);

        $response->assertSessionHasErrors(['work_hours_per_day']);
    }

    /** @test */
    public function validation_fails_with_missing_weekend_days()
    {
        $this->authenticateUser();

        $response = $this->put(route('attendance.settings.update'), [
            'work_hours_per_day' => 8
        ]);

        $response->assertSessionHasErrors(['weekend_days']);
    }

    /** @test */
    public function validation_fails_with_empty_weekend_days()
    {
        $this->authenticateUser();

        $response = $this->put(route('attendance.settings.update'), [
            'work_hours_per_day' => 8,
            'weekend_days' => []
        ]);

        $response->assertSessionHasErrors(['weekend_days']);
    }

    /** @test */
    public function validation_fails_with_invalid_weekend_days()
    {
        $this->authenticateUser();

        $response = $this->put(route('attendance.settings.update'), [
            'work_hours_per_day' => 8,
            'weekend_days' => ['invalid_day', 'friday']
        ]);

        $response->assertSessionHasErrors(['weekend_days.0']);

        $response = $this->put(route('attendance.settings.update'), [
            'work_hours_per_day' => 8,
            'weekend_days' => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'extra']
        ]);

        $response->assertSessionHasErrors(['weekend_days']);
    }

    /** @test */
    public function can_update_settings_with_all_valid_day_combinations()
    {
        $this->authenticateUser();

        // Test single day
        $response = $this->put(route('attendance.settings.update'), [
            'work_hours_per_day' => 8,
            'weekend_days' => ['sunday']
        ]);

        $response->assertRedirect(route('attendance.settings.index'));
        $this->assertEquals(['sunday'], Setting::get('weekend_days'));

        // Test multiple days
        $response = $this->put(route('attendance.settings.update'), [
            'work_hours_per_day' => 8,
            'weekend_days' => ['friday', 'saturday', 'sunday']
        ]);

        $response->assertRedirect(route('attendance.settings.index'));
        $this->assertEquals(['friday', 'saturday', 'sunday'], Setting::get('weekend_days'));
    }

    /** @test */
    public function setting_model_static_methods_work_correctly()
    {
        // Test setting and getting values
        Setting::set('test_key', 'test_value');
        $this->assertEquals('test_value', Setting::get('test_key'));

        // Test default values
        $this->assertEquals('default', Setting::get('nonexistent_key', 'default'));

        // Test JSON encoding/decoding
        Setting::set('array_key', ['item1', 'item2']);
        $this->assertEquals(['item1', 'item2'], Setting::get('array_key'));

        // Test has method
        $this->assertTrue(Setting::has('test_key'));
        $this->assertFalse(Setting::has('nonexistent_key'));

        // Test forget method
        $this->assertTrue(Setting::forget('test_key'));
        $this->assertFalse(Setting::has('test_key'));

        // Test getMultiple method
        Setting::set('key1', 'value1');
        Setting::set('key2', ['value2a', 'value2b']);

        $result = Setting::getMultiple(['key1', 'key2', 'nonexistent']);
        $this->assertEquals([
            'key1' => 'value1',
            'key2' => ['value2a', 'value2b'],
            'nonexistent' => null
        ], $result);
    }

    /** @test */
    public function settings_persist_across_requests()
    {
        $this->authenticateUser();

        // Set initial settings
        $this->put(route('attendance.settings.update'), [
            'work_hours_per_day' => 7,
            'weekend_days' => ['thursday', 'friday']
        ]);

        // Verify they persist
        $response = $this->get(route('attendance.settings.index'));
        $response->assertViewHas('workHoursPerDay', 7);
        $response->assertViewHas('weekendDays', ['thursday', 'friday']);

        // Update settings
        $this->put(route('attendance.settings.update'), [
            'work_hours_per_day' => 9,
            'weekend_days' => ['saturday', 'sunday']
        ]);

        // Verify new values persist
        $response = $this->get(route('attendance.settings.index'));
        $response->assertViewHas('workHoursPerDay', 9);
        $response->assertViewHas('weekendDays', ['saturday', 'sunday']);
    }
}
