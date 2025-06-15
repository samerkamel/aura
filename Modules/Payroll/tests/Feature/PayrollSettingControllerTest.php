<?php

namespace Modules\Payroll\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Attendance\Models\Setting;
use App\Models\User;

/**
 * PayrollSettingControllerTest
 *
 * Feature tests for payroll weight settings functionality.
 * Tests the ability to view and update payroll calculation weights.
 *
 * @author Dev Agent
 */
class PayrollSettingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create();
    }

    /**
     * Test that the payroll settings index page loads correctly.
     *
     * @return void
     */
    public function test_payroll_settings_index_loads_correctly(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('payroll.settings.index'));

        $response->assertStatus(200);
        $response->assertViewIs('payroll::settings.index');
        $response->assertSee('Payroll Settings');
        $response->assertSee('Attendance Weight (%)');
        $response->assertSee('Billable Hours Weight (%)');
    }

    /**
     * Test that default weight values are shown when no settings exist.
     *
     * @return void
     */
    public function test_default_weight_values_are_displayed(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('payroll.settings.index'));

        $response->assertStatus(200);
        $response->assertSee('value="50"', false); // Default values should be 50 each
    }

    /**
     * Test that existing weight values are pre-populated in the form.
     *
     * @return void
     */
    public function test_existing_weight_values_are_pre_populated(): void
    {
        // Set existing values
        Setting::set('weight_attendance_pct', 60);
        Setting::set('weight_billable_hours_pct', 40);

        $response = $this->actingAs($this->user)
            ->get(route('payroll.settings.index'));

        $response->assertStatus(200);
        $response->assertSee('value="60"', false);
        $response->assertSee('value="40"', false);
    }

    /**
     * Test that valid weight settings can be saved successfully.
     *
     * @return void
     */
    public function test_valid_weight_settings_can_be_saved(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('payroll.settings.store'), [
                'attendance_weight' => 70,
                'billable_hours_weight' => 30
            ]);

        $response->assertRedirect(route('payroll.settings.index'));
        $response->assertSessionHas('success', 'Payroll weight settings updated successfully.');

        // Verify settings were saved
        $this->assertEquals(70, Setting::get('weight_attendance_pct'));
        $this->assertEquals(30, Setting::get('weight_billable_hours_pct'));
    }

    /**
     * Test that weights must sum to exactly 100.
     *
     * @return void
     */
    public function test_weights_must_sum_to_100(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('payroll.settings.store'), [
                'attendance_weight' => 70,
                'billable_hours_weight' => 50 // Total = 120, should fail
            ]);

        $response->assertSessionHasErrors('weight_sum');
        $response->assertRedirect();
    }

    /**
     * Test that weights cannot be negative.
     *
     * @return void
     */
    public function test_weights_cannot_be_negative(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('payroll.settings.store'), [
                'attendance_weight' => -10,
                'billable_hours_weight' => 110
            ]);

        $response->assertSessionHasErrors('attendance_weight');
        $response->assertRedirect();
    }

    /**
     * Test that weights cannot exceed 100.
     *
     * @return void
     */
    public function test_weights_cannot_exceed_100(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('payroll.settings.store'), [
                'attendance_weight' => 150,
                'billable_hours_weight' => -50
            ]);

        $response->assertSessionHasErrors(['attendance_weight', 'billable_hours_weight']);
        $response->assertRedirect();
    }

    /**
     * Test that weights are required.
     *
     * @return void
     */
    public function test_weights_are_required(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('payroll.settings.store'), []);

        $response->assertSessionHasErrors(['attendance_weight', 'billable_hours_weight']);
        $response->assertRedirect();
    }

    /**
     * Test that weights must be numeric.
     *
     * @return void
     */
    public function test_weights_must_be_numeric(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('payroll.settings.store'), [
                'attendance_weight' => 'invalid',
                'billable_hours_weight' => 'also_invalid'
            ]);

        $response->assertSessionHasErrors(['attendance_weight', 'billable_hours_weight']);
        $response->assertRedirect();
    }

    /**
     * Test that decimal weights are accepted.
     *
     * @return void
     */
    public function test_decimal_weights_are_accepted(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('payroll.settings.store'), [
                'attendance_weight' => 67.5,
                'billable_hours_weight' => 32.5
            ]);

        $response->assertRedirect(route('payroll.settings.index'));
        $response->assertSessionHas('success');

        // Verify settings were saved
        $this->assertEquals(67.5, Setting::get('weight_attendance_pct'));
        $this->assertEquals(32.5, Setting::get('weight_billable_hours_pct'));
    }

    /**
     * Test that settings can be updated multiple times.
     *
     * @return void
     */
    public function test_settings_can_be_updated_multiple_times(): void
    {
        // First update
        $this->actingAs($this->user)
            ->post(route('payroll.settings.store'), [
                'attendance_weight' => 80,
                'billable_hours_weight' => 20
            ]);

        $this->assertEquals(80, Setting::get('weight_attendance_pct'));
        $this->assertEquals(20, Setting::get('weight_billable_hours_pct'));

        // Second update
        $this->actingAs($this->user)
            ->post(route('payroll.settings.store'), [
                'attendance_weight' => 60,
                'billable_hours_weight' => 40
            ]);

        $this->assertEquals(60, Setting::get('weight_attendance_pct'));
        $this->assertEquals(40, Setting::get('weight_billable_hours_pct'));
    }

    /**
     * Test that unauthenticated users cannot access settings.
     *
     * @return void
     */
    public function test_unauthenticated_users_cannot_access_settings(): void
    {
        $response = $this->get(route('payroll.settings.index'));
        $response->assertRedirect(route('login'));

        $response = $this->post(route('payroll.settings.store'), [
            'attendance_weight' => 50,
            'billable_hours_weight' => 50
        ]);
        $response->assertRedirect(route('login'));
    }
}
