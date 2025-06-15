<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payroll\Services\AttendanceCalculationService;
use Modules\HR\Models\Employee;
use Modules\Leave\Models\LeaveRecord;
use Modules\Leave\Models\LeavePolicy;
use Carbon\Carbon;

/**
 * Debug test for AttendanceCalculationService leave processing
 */
class AttendanceCalculationServiceDebugLeaveTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function debug_leave_record_processing()
    {
        // Create employee
        $employee = Employee::factory()->create([
            'name' => 'Debug Employee',
            'email' => 'debug-leave@example.com',
            'base_salary' => 5000.00,
            'start_date' => Carbon::now()->subYears(2),
        ]);

        // Set period (Wednesday only)
        $periodStart = Carbon::parse('2025-06-18'); // Wednesday
        $periodEnd = Carbon::parse('2025-06-18');   // Wednesday

        // Create leave policy
        $leavePolicy = LeavePolicy::factory()->create([
            'name' => 'Debug Leave',
            'type' => 'pto',
        ]);

        // Create leave record for Wednesday
        $leaveRecord = LeaveRecord::factory()->create([
            'employee_id' => $employee->id,
            'leave_policy_id' => $leavePolicy->id,
            'start_date' => Carbon::parse('2025-06-18'),
            'end_date' => Carbon::parse('2025-06-18'),
            'status' => LeaveRecord::STATUS_APPROVED,
        ]);

        // Debug: Check that leave record was created correctly
        $this->assertTrue($leaveRecord->exists());
        $this->assertEquals('approved', $leaveRecord->status);
        $this->assertEquals('2025-06-18', $leaveRecord->start_date->format('Y-m-d'));

        // Debug: Check that the leave record can be fetched by the service query
        $fetchedLeaveRecords = LeaveRecord::where('employee_id', $employee->id)
            ->approved()
            ->inDateRange($periodStart, $periodEnd)
            ->with('leavePolicy')
            ->get();

        $this->assertCount(1, $fetchedLeaveRecords);
        $this->assertEquals($leaveRecord->id, $fetchedLeaveRecords->first()->id);

        // Test the service
        $service = new AttendanceCalculationService();
        $result = $service->calculateNetHours($employee, $periodStart, $periodEnd);

        // Should be 1 day * 8 hours = 8 hours
        $this->assertEquals(8.0, $result, 'Leave day should count as 8 hours');

        // If this passes, the issue is elsewhere
        // If this fails, the issue is in the leave processing logic
    }
}
