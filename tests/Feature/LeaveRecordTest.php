<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HR\Models\Employee;
use Modules\Leave\Models\LeavePolicy;
use Modules\Leave\Models\LeaveRecord;
use App\Models\User;
use Carbon\Carbon;

/**
 * Leave Record Feature Tests
 *
 * Tests the creation, updating, and deletion of leave records
 * through the API endpoints.
 *
 * @author Dev Agent
 */
class LeaveRecordTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Employee $employee;
    protected LeavePolicy $leavePolicy;

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

        // Create test leave policy
        $this->leavePolicy = LeavePolicy::factory()->create([
            'name' => 'Annual Leave',
            'type' => 'PTO',
            'initial_days' => 20,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function admin_can_create_leave_record_for_employee()
    {
        $startDate = Carbon::now()->addDays(5)->format('Y-m-d');
        $endDate = Carbon::now()->addDays(7)->format('Y-m-d');

        $response = $this->actingAs($this->user, 'web')
            ->postJson("/api/v1/employees/{$this->employee->id}/leave-records", [
                'leave_policy_id' => $this->leavePolicy->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'notes' => 'Test leave request',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Leave record created successfully.',
            ]);

        $this->assertDatabaseHas('leave_records', [
            'employee_id' => $this->employee->id,
            'leave_policy_id' => $this->leavePolicy->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => LeaveRecord::STATUS_APPROVED,
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function cannot_create_overlapping_leave_records()
    {
        $startDate = Carbon::now()->addDays(5);
        $endDate = Carbon::now()->addDays(7);

        // Create first leave record
        LeaveRecord::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_policy_id' => $this->leavePolicy->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => LeaveRecord::STATUS_APPROVED,
        ]);

        // Try to create overlapping leave record
        $response = $this->actingAs($this->user, 'web')
            ->postJson("/api/v1/employees/{$this->employee->id}/leave-records", [
                'leave_policy_id' => $this->leavePolicy->id,
                'start_date' => $startDate->addDay()->format('Y-m-d'),
                'end_date' => $endDate->addDay()->format('Y-m-d'),
                'notes' => 'Overlapping leave request',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Employee already has approved leave during this period.',
            ]);
    }

    /** @test */
    public function admin_can_update_leave_record()
    {
        $leaveRecord = LeaveRecord::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_policy_id' => $this->leavePolicy->id,
            'start_date' => Carbon::now()->addDays(5),
            'end_date' => Carbon::now()->addDays(7),
            'status' => LeaveRecord::STATUS_APPROVED,
        ]);

        $newStartDate = Carbon::now()->addDays(10)->format('Y-m-d');
        $newEndDate = Carbon::now()->addDays(12)->format('Y-m-d');

        $response = $this->actingAs($this->user, 'web')
            ->putJson("/api/v1/employees/{$this->employee->id}/leave-records/{$leaveRecord->id}", [
                'leave_policy_id' => $this->leavePolicy->id,
                'start_date' => $newStartDate,
                'end_date' => $newEndDate,
                'status' => LeaveRecord::STATUS_APPROVED,
                'notes' => 'Updated leave request',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Leave record updated successfully.',
            ]);

        $this->assertDatabaseHas('leave_records', [
            'id' => $leaveRecord->id,
            'start_date' => $newStartDate,
            'end_date' => $newEndDate,
        ]);
    }

    /** @test */
    public function admin_can_delete_leave_record()
    {
        $leaveRecord = LeaveRecord::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_policy_id' => $this->leavePolicy->id,
        ]);

        $response = $this->actingAs($this->user, 'web')
            ->deleteJson("/api/v1/employees/{$this->employee->id}/leave-records/{$leaveRecord->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Leave record deleted successfully.',
            ]);

        $this->assertDatabaseMissing('leave_records', [
            'id' => $leaveRecord->id,
        ]);
    }

    /** @test */
    public function validation_fails_for_invalid_leave_record_data()
    {
        $response = $this->actingAs($this->user, 'web')
            ->postJson("/api/v1/employees/{$this->employee->id}/leave-records", [
                'leave_policy_id' => 'invalid',
                'start_date' => 'invalid-date',
                'end_date' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['leave_policy_id', 'start_date', 'end_date']);
    }

    /** @test */
    public function cannot_access_leave_record_of_different_employee()
    {
        $otherEmployee = Employee::factory()->create();
        $leaveRecord = LeaveRecord::factory()->create([
            'employee_id' => $otherEmployee->id,
            'leave_policy_id' => $this->leavePolicy->id,
        ]);

        $response = $this->actingAs($this->user, 'web')
            ->putJson("/api/v1/employees/{$this->employee->id}/leave-records/{$leaveRecord->id}", [
                'leave_policy_id' => $this->leavePolicy->id,
                'start_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
                'end_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
                'status' => LeaveRecord::STATUS_APPROVED,
            ]);

        $response->assertStatus(404);
    }
}
