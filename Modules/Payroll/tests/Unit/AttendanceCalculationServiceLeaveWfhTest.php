<?php

namespace Modules\Payroll\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payroll\Services\AttendanceCalculationService;
use Modules\HR\Models\Employee;
use Modules\Attendance\Models\AttendanceLog;
use Modules\Attendance\Models\AttendanceRule;
use Modules\Attendance\Models\WfhRecord;
use Modules\Leave\Models\LeaveRecord;
use Modules\Leave\Models\LeavePolicy;
use Carbon\Carbon;

/**
 * Attendance Calculation Service Leave and WFH Tests
 *
 * Unit tests specifically for testing leave records and WFH records
 * integration with the AttendanceCalculationService.
 *
 * @author Dev Agent
 */
class AttendanceCalculationServiceLeaveWfhTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceCalculationService $service;
    private Employee $employee;
    private Carbon $periodStart;
    private Carbon $periodEnd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AttendanceCalculationService();

        // Create test employee
        $this->employee = Employee::factory()->create([
            'name' => 'Test Employee',
            'email' => 'test-' . uniqid() . '@example.com',
            'base_salary' => 5000.00,
            'start_date' => Carbon::now()->subYears(2),
        ]);

        // Set up a test period (1 week for focused testing)
        $this->periodStart = Carbon::parse('2025-06-16'); // Monday
        $this->periodEnd = Carbon::parse('2025-06-20');   // Friday

        // Create basic attendance rules
        $this->createFlexibleHoursRule();
        $this->createLatePenaltyRule();
        $this->createPermissionRule();
    }

    /** @test */
    public function leave_days_count_as_full_work_days()
    {
        // Create leave policy
        $leavePolicy = LeavePolicy::factory()->create([
            'name' => 'Annual Leave',
            'type' => 'PTO',
        ]);

        // Create leave record for Wednesday (2025-06-18)
        LeaveRecord::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_policy_id' => $leavePolicy->id,
            'start_date' => Carbon::parse('2025-06-18'),
            'end_date' => Carbon::parse('2025-06-18'),
            'status' => LeaveRecord::STATUS_APPROVED,
        ]);

        // Create attendance logs for other days
        $this->createAttendanceLog('2025-06-16', '09:00', '17:00'); // Monday: 8 hours
        $this->createAttendanceLog('2025-06-17', '09:00', '17:00'); // Tuesday: 8 hours
        // Wednesday: Leave day (should count as 8 hours)
        $this->createAttendanceLog('2025-06-19', '09:00', '17:00'); // Thursday: 8 hours
        $this->createAttendanceLog('2025-06-20', '09:00', '17:00'); // Friday: 8 hours

        $result = $this->service->calculateNetHours($this->employee, $this->periodStart, $this->periodEnd);

        // Should be 5 days * 8 hours = 40 hours
        $this->assertEquals(40.0, $result);
    }

    /** @test */
    public function multiple_day_leave_counts_correctly()
    {
        // Create leave policy
        $leavePolicy = LeavePolicy::factory()->create([
            'name' => 'Annual Leave',
            'type' => 'PTO',
        ]);

        // Create leave record for Wednesday to Friday (3 days)
        LeaveRecord::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_policy_id' => $leavePolicy->id,
            'start_date' => Carbon::parse('2025-06-18'),
            'end_date' => Carbon::parse('2025-06-20'),
            'status' => LeaveRecord::STATUS_APPROVED,
        ]);

        // Create attendance logs for Monday and Tuesday only
        $this->createAttendanceLog('2025-06-16', '09:00', '17:00'); // Monday: 8 hours
        $this->createAttendanceLog('2025-06-17', '09:00', '17:00'); // Tuesday: 8 hours

        $result = $this->service->calculateNetHours($this->employee, $this->periodStart, $this->periodEnd);

        // Should be 5 days * 8 hours = 40 hours (2 attendance + 3 leave days)
        $this->assertEquals(40.0, $result);
    }

    /** @test */
    public function wfh_days_contribute_based_on_policy_percentage()
    {
        // Create WFH policy with 80% contribution
        AttendanceRule::factory()->create([
            'rule_type' => 'wfh_policy',
            'config' => [
                'monthly_allowance_days' => 10,
                'attendance_contribution_percentage' => 80,
            ],
        ]);

        // Create WFH record for Wednesday
        WfhRecord::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => Carbon::parse('2025-06-18'),
        ]);

        // Create regular attendance logs for other days
        $this->createAttendanceLog('2025-06-16', '09:00', '17:00'); // Monday: 8 hours
        $this->createAttendanceLog('2025-06-17', '09:00', '17:00'); // Tuesday: 8 hours
        $this->createAttendanceLog('2025-06-19', '09:00', '17:00'); // Thursday: 8 hours
        $this->createAttendanceLog('2025-06-20', '09:00', '17:00'); // Friday: 8 hours

        $result = $this->service->calculateNetHours($this->employee, $this->periodStart, $this->periodEnd);

        // Should be 4 days * 8 hours + 1 WFH day * 8 hours * 0.8 = 32 + 6.4 = 38.4 hours
        $this->assertEquals(38.4, $result);
    }

    /** @test */
    public function wfh_day_with_actual_attendance_applies_percentage_to_logged_hours()
    {
        // Create WFH policy with 80% contribution
        AttendanceRule::factory()->create([
            'rule_type' => 'wfh_policy',
            'config' => [
                'monthly_allowance_days' => 10,
                'attendance_contribution_percentage' => 80,
            ],
        ]);

        // Create WFH record for Wednesday
        WfhRecord::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => Carbon::parse('2025-06-18'),
        ]);

        // Create attendance logs including for WFH day
        $this->createAttendanceLog('2025-06-16', '09:00', '17:00'); // Monday: 8 hours
        $this->createAttendanceLog('2025-06-17', '09:00', '17:00'); // Tuesday: 8 hours
        $this->createAttendanceLog('2025-06-18', '09:00', '16:00'); // Wednesday WFH: 7 hours * 0.8 = 5.6 hours
        $this->createAttendanceLog('2025-06-19', '09:00', '17:00'); // Thursday: 8 hours
        $this->createAttendanceLog('2025-06-20', '09:00', '17:00'); // Friday: 8 hours

        $result = $this->service->calculateNetHours($this->employee, $this->periodStart, $this->periodEnd);

        // Should be 4 days * 8 hours + 1 WFH day * 7 hours * 0.8 = 32 + 5.6 = 37.6 hours
        $this->assertEquals(37.6, $result);
    }

    /** @test */
    public function leave_takes_precedence_over_wfh_on_same_date()
    {
        // Create leave policy
        $leavePolicy = LeavePolicy::factory()->create([
            'name' => 'Annual Leave',
            'type' => 'PTO',
        ]);

        // Create WFH policy
        AttendanceRule::factory()->create([
            'rule_type' => 'wfh_policy',
            'config' => [
                'monthly_allowance_days' => 10,
                'attendance_contribution_percentage' => 80,
            ],
        ]);

        // Create both leave and WFH record for the same date
        LeaveRecord::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_policy_id' => $leavePolicy->id,
            'start_date' => Carbon::parse('2025-06-18'),
            'end_date' => Carbon::parse('2025-06-18'),
            'status' => LeaveRecord::STATUS_APPROVED,
        ]);

        WfhRecord::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => Carbon::parse('2025-06-18'),
        ]);

        // Create attendance logs for other days
        $this->createAttendanceLog('2025-06-16', '09:00', '17:00'); // Monday: 8 hours
        $this->createAttendanceLog('2025-06-17', '09:00', '17:00'); // Tuesday: 8 hours
        $this->createAttendanceLog('2025-06-19', '09:00', '17:00'); // Thursday: 8 hours
        $this->createAttendanceLog('2025-06-20', '09:00', '17:00'); // Friday: 8 hours

        $result = $this->service->calculateNetHours($this->employee, $this->periodStart, $this->periodEnd);

        // Leave should take precedence, so should be 5 days * 8 hours = 40 hours
        $this->assertEquals(40.0, $result);
    }

    /** @test */
    public function leave_and_wfh_work_together_for_different_dates()
    {
        // Create leave policy
        $leavePolicy = LeavePolicy::factory()->create([
            'name' => 'Annual Leave',
            'type' => 'PTO',
        ]);

        // Create WFH policy
        AttendanceRule::factory()->create([
            'rule_type' => 'wfh_policy',
            'config' => [
                'monthly_allowance_days' => 10,
                'attendance_contribution_percentage' => 80,
            ],
        ]);

        // Create leave for Tuesday
        LeaveRecord::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_policy_id' => $leavePolicy->id,
            'start_date' => Carbon::parse('2025-06-17'),
            'end_date' => Carbon::parse('2025-06-17'),
            'status' => LeaveRecord::STATUS_APPROVED,
        ]);

        // Create WFH for Thursday
        WfhRecord::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => Carbon::parse('2025-06-19'),
        ]);

        // Create attendance logs for Monday, Wednesday, Friday
        $this->createAttendanceLog('2025-06-16', '09:00', '17:00'); // Monday: 8 hours
        // Tuesday: Leave (8 hours)
        $this->createAttendanceLog('2025-06-18', '09:00', '17:00'); // Wednesday: 8 hours
        // Thursday: WFH with no attendance (8 * 0.8 = 6.4 hours)
        $this->createAttendanceLog('2025-06-20', '09:00', '17:00'); // Friday: 8 hours

        $result = $this->service->calculateNetHours($this->employee, $this->periodStart, $this->periodEnd);

        // Should be 3 regular days * 8 + 1 leave day * 8 + 1 WFH day * 8 * 0.8 = 24 + 8 + 6.4 = 38.4 hours
        $this->assertEquals(38.4, $result);
    }

    private function createAttendanceLog(string $date, string $signIn, string $signOut): void
    {
        $baseDate = Carbon::parse($date);

        AttendanceLog::factory()->create([
            'employee_id' => $this->employee->id,
            'timestamp' => $baseDate->copy()->setTimeFromTimeString($signIn),
            'type' => 'sign_in'
        ]);

        AttendanceLog::factory()->create([
            'employee_id' => $this->employee->id,
            'timestamp' => $baseDate->copy()->setTimeFromTimeString($signOut),
            'type' => 'sign_out'
        ]);
    }

    private function createFlexibleHoursRule(): void
    {
        AttendanceRule::factory()->create([
            'rule_type' => AttendanceRule::TYPE_FLEXIBLE_HOURS,
            'config' => [
                'official_start_time' => '09:00',
                'flexible_window_minutes' => 30
            ],
        ]);
    }

    private function createLatePenaltyRule(): void
    {
        AttendanceRule::factory()->create([
            'rule_type' => AttendanceRule::TYPE_LATE_PENALTY,
            'config' => [
                'tiers' => [
                    [
                        'min_minutes_late' => 1,
                        'max_minutes_late' => 15,
                        'penalty_minutes' => 30
                    ],
                    [
                        'min_minutes_late' => 16,
                        'max_minutes_late' => 30,
                        'penalty_minutes' => 60
                    ]
                ]
            ],
        ]);
    }

    private function createPermissionRule(): void
    {
        AttendanceRule::factory()->create([
            'rule_type' => AttendanceRule::TYPE_PERMISSION,
            'config' => [
                'monthly_allowance_minutes' => 180
            ],
        ]);
    }
}
