<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Modules\HR\Models\Employee;
use Modules\Leave\Models\LeavePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class PastDateLeaveWfhTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $employee;
    protected $leavePolicy;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user
        $this->user = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com'
        ]);

        // Create an employee
        $this->employee = Employee::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.doe@test.com',
        ]);

        // Create a leave policy
        $this->leavePolicy = LeavePolicy::factory()->create([
            'name' => 'Standard PTO Policy',
            'type' => 'pto',
        ]);
    }

    /** @test */
    public function admin_can_create_leave_record_with_past_dates()
    {
        // Use past dates (7 days ago to 5 days ago)
        $startDate = Carbon::now()->subDays(7)->format('Y-m-d');
        $endDate = Carbon::now()->subDays(5)->format('Y-m-d');

        $response = $this->actingAs($this->user, 'web')
            ->postJson("/api/v1/employees/{$this->employee->id}/leave-records", [
                'leave_policy_id' => $this->leavePolicy->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'notes' => 'Past leave request',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Leave record created successfully.',
            ]);

        $this->assertDatabaseHas('leave_records', [
            'employee_id' => $this->employee->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /** @test */
    public function admin_can_create_wfh_record_with_past_dates()
    {
        // Use past dates (yesterday and 3 days ago)
        $pastDates = [
            Carbon::now()->subDay()->format('Y-m-d'),
            Carbon::now()->subDays(3)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user, 'web')
            ->postJson("/api/v1/employees/{$this->employee->id}/wfh-records", [
                'dates' => $pastDates,
                'notes' => 'Past WFH days',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        // Check that both WFH records were created
        foreach ($pastDates as $date) {
            $this->assertDatabaseHas('wfh_records', [
                'employee_id' => $this->employee->id,
                'date' => $date,
            ]);
        }
    }

    /** @test */
    public function admin_can_create_leave_record_with_current_date()
    {
        // Use current date
        $today = Carbon::now()->format('Y-m-d');

        $response = $this->actingAs($this->user, 'web')
            ->postJson("/api/v1/employees/{$this->employee->id}/leave-records", [
                'leave_policy_id' => $this->leavePolicy->id,
                'start_date' => $today,
                'end_date' => $today,
                'notes' => 'Today leave request',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Leave record created successfully.',
            ]);
    }

    /** @test */
    public function admin_can_create_wfh_record_with_current_date()
    {
        // Use current date
        $today = Carbon::now()->format('Y-m-d');

        $response = $this->actingAs($this->user, 'web')
            ->postJson("/api/v1/employees/{$this->employee->id}/wfh-records", [
                'dates' => [$today],
                'notes' => 'Today WFH',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('wfh_records', [
            'employee_id' => $this->employee->id,
            'date' => $today,
        ]);
    }
}
