<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HR\Models\Employee;
use Modules\Attendance\Models\WfhRecord;
use Modules\Attendance\Models\AttendanceRule;
use App\Models\User;
use Carbon\Carbon;

/**
 * WFH Record Feature Tests
 *
 * Tests the creation, updating, and deletion of WFH records
 * through the API endpoints and WFH policy validation.
 *
 * @author Dev Agent
 */
class WfhRecordTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create test employee
        $this->employee = Employee::factory()->create([
            'name' => 'Test Employee',
            'email' => 'test@example.com',
            'start_date' => Carbon::now()->subYears(2),
        ]);

        // Create WFH policy rule
        AttendanceRule::factory()->create([
            'rule_type' => 'wfh_policy',
            'config' => [
                'monthly_allowance_days' => 5,
                'attendance_contribution_percentage' => 80,
            ],
        ]);
    }

    /** @test */
    public function admin_can_create_wfh_record_for_employee()
    {
        $wfhDate = Carbon::now()->addDays(5)->format('Y-m-d');

        $response = $this->actingAs($this->user, 'web')
            ->postJson("/api/v1/employees/{$this->employee->id}/wfh-records", [
                'dates' => [$wfhDate],
                'notes' => 'Working from home for project meeting',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => '1 WFH record(s) created successfully.',
            ]);

        $this->assertDatabaseHas('wfh_records', [
            'employee_id' => $this->employee->id,
            'date' => $wfhDate,
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function admin_can_create_multiple_wfh_records()
    {
        $wfhDates = [
            Carbon::now()->addDays(5)->format('Y-m-d'),
            Carbon::now()->addDays(7)->format('Y-m-d'),
            Carbon::now()->addDays(10)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user, 'web')
            ->postJson("/api/v1/employees/{$this->employee->id}/wfh-records", [
                'dates' => $wfhDates,
                'notes' => 'Multiple WFH days',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => '3 WFH record(s) created successfully.',
            ]);

        foreach ($wfhDates as $date) {
            $this->assertDatabaseHas('wfh_records', [
                'employee_id' => $this->employee->id,
                'date' => $date,
            ]);
        }
    }

    /** @test */
    public function cannot_create_duplicate_wfh_record_for_same_date()
    {
        $wfhDate = Carbon::now()->addDays(5);

        // Create first WFH record
        WfhRecord::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => $wfhDate,
        ]);

        // Try to create duplicate
        $response = $this->actingAs($this->user, 'web')
            ->postJson("/api/v1/employees/{$this->employee->id}/wfh-records", [
                'dates' => [$wfhDate->format('Y-m-d')],
                'notes' => 'Duplicate WFH day',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'No WFH records were created.',
            ]);
    }

    /** @test */
    public function cannot_exceed_monthly_wfh_allowance()
    {
        $futureStart = Carbon::now()->addDays(1); // Start from tomorrow to avoid past date validation issues

        // Create 5 WFH records (reaching the monthly limit)
        for ($i = 0; $i < 5; $i++) {
            WfhRecord::factory()->create([
                'employee_id' => $this->employee->id,
                'date' => $futureStart->copy()->addDays($i),
            ]);
        }

        // Try to create one more (should exceed limit)
        $response = $this->actingAs($this->user, 'web')
            ->postJson("/api/v1/employees/{$this->employee->id}/wfh-records", [
                'dates' => [$futureStart->copy()->addDays(5)->format('Y-m-d')],
                'notes' => 'Exceeding monthly limit',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'No WFH records were created.',
            ]);
    }

    /** @test */
    public function admin_can_update_wfh_record()
    {
        $wfhRecord = WfhRecord::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => Carbon::now()->addDays(5),
        ]);

        $newDate = Carbon::now()->addDays(10)->format('Y-m-d');

        $response = $this->actingAs($this->user, 'web')
            ->putJson("/api/v1/employees/{$this->employee->id}/wfh-records/{$wfhRecord->id}", [
                'date' => $newDate,
                'notes' => 'Updated WFH record',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'WFH record updated successfully.',
            ]);

        $this->assertDatabaseHas('wfh_records', [
            'id' => $wfhRecord->id,
            'date' => $newDate,
        ]);
    }

    /** @test */
    public function admin_can_delete_wfh_record()
    {
        $wfhRecord = WfhRecord::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->actingAs($this->user, 'web')
            ->deleteJson("/api/v1/employees/{$this->employee->id}/wfh-records/{$wfhRecord->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'WFH record deleted successfully.',
            ]);

        $this->assertDatabaseMissing('wfh_records', [
            'id' => $wfhRecord->id,
        ]);
    }

    /** @test */
    public function validation_fails_for_invalid_wfh_record_data()
    {
        $response = $this->actingAs($this->user, 'web')
            ->postJson("/api/v1/employees/{$this->employee->id}/wfh-records", [
                'dates' => ['invalid-date'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['dates.0']);
    }

    /** @test */
    public function cannot_access_wfh_record_of_different_employee()
    {
        $otherEmployee = Employee::factory()->create();
        $wfhRecord = WfhRecord::factory()->create([
            'employee_id' => $otherEmployee->id,
        ]);

        $response = $this->actingAs($this->user, 'web')
            ->putJson("/api/v1/employees/{$this->employee->id}/wfh-records/{$wfhRecord->id}", [
                'date' => Carbon::now()->addDays(5)->format('Y-m-d'),
            ]);

        $response->assertStatus(404);
    }
}
