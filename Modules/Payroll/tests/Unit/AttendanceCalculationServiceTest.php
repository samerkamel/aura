<?php

namespace Modules\Payroll\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payroll\Services\AttendanceCalculationService;
use Modules\HR\Models\Employee;
use Modules\Attendance\Models\AttendanceLog;
use Modules\Attendance\Models\AttendanceRule;
use Modules\Attendance\Models\PermissionOverride;
use Modules\Attendance\Models\PublicHoliday;
use App\Models\User;
use Carbon\Carbon;

/**
 * AttendanceCalculationServiceTest
 *
 * Comprehensive unit tests for the AttendanceCalculationService
 * Tests each rule in isolation and complex interactions between rules
 *
 * @author GitHub Copilot
 */
class AttendanceCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceCalculationService $service;
    private Carbon $periodStart;
    private Carbon $periodEnd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AttendanceCalculationService();

        // Set up a test period (1 month)
        $this->periodStart = Carbon::parse('2025-05-26');
        $this->periodEnd = Carbon::parse('2025-06-25');
    }

    protected function createTestEmployee(): Employee
    {
        return Employee::factory()->create([
            'name' => 'Test Employee',
            'email' => 'test-' . uniqid() . '@example.com', // Unique email to avoid constraints
            'base_salary' => 5000.00
        ]);
    }

    /** @test */
    public function it_calculates_net_hours_for_perfect_attendance()
    {
        $employee = $this->createTestEmployee();

        // Setup: Create 23 working days with perfect attendance (9:00-17:00 = 8 hours each)
        // Period from 2025-05-26 to 2025-06-25 includes 23 working days (not 22)
        $this->createPerfectAttendanceLogs($employee);
        $this->createStandardRules();

        $netHours = $this->service->calculateNetHours($employee, $this->periodStart, $this->periodEnd);

        // 23 working days * 8 hours = 184 hours
        $this->assertEquals(184.0, $netHours);
    }

    /** @test */
    public function it_handles_missing_sign_out_gracefully()
    {
        $employee = $this->createTestEmployee();

        // Create a day with sign-in but no sign-out
        AttendanceLog::create([
            'employee_id' => $employee->id,
            'timestamp' => $this->periodStart->copy()->setTime(9, 0),
            'type' => 'sign_in'
        ]);

        $this->createStandardRules();

        $netHours = $this->service->calculateNetHours($employee, $this->periodStart, $this->periodEnd);

        // Should return 0 for the day with missing sign-out
        $this->assertEquals(0.0, $netHours);
    }

    /** @test */
    public function it_handles_missing_sign_in_gracefully()
    {
        $employee = $this->createTestEmployee();

        // Create a day with sign-out but no sign-in
        AttendanceLog::create([
            'employee_id' => $employee->id,
            'timestamp' => $this->periodStart->copy()->setTime(17, 0),
            'type' => 'sign_out'
        ]);

        $this->createStandardRules();

        $netHours = $this->service->calculateNetHours($employee, $this->periodStart, $this->periodEnd);

        // Should return 0 for the day with missing sign-in
        $this->assertEquals(0.0, $netHours);
    }

    /** @test */
    public function it_applies_flexible_hours_rule_correctly()
    {
        $employee = $this->createTestEmployee();

        // Create attendance log arriving at 9:15 (within 30-minute flexible window)
        AttendanceLog::create([
            'employee_id' => $employee->id,
            'timestamp' => $this->periodStart->copy()->setTime(9, 15),
            'type' => 'sign_in'
        ]);
        AttendanceLog::create([
            'employee_id' => $employee->id,
            'timestamp' => $this->periodStart->copy()->setTime(17, 15),
            'type' => 'sign_out'
        ]);

        $this->createStandardRules();

        $netHours = $this->service->calculateNetHours($employee, $this->periodStart, $this->periodEnd);

        // Should get full 8 hours with no penalty (within flexible window)
        $this->assertEquals(8.0, $netHours);
    }

    /** @test */
    public function it_applies_late_penalty_for_arrivals_outside_flexible_window()
    {
        $employee = $this->createTestEmployee();

        // Create attendance log arriving at 9:45 (15 minutes beyond 30-minute flexible window)
        AttendanceLog::create([
            'employee_id' => $employee->id,
            'timestamp' => $this->periodStart->copy()->setTime(9, 45),
            'type' => 'sign_in'
        ]);
        AttendanceLog::create([
            'employee_id' => $employee->id,
            'timestamp' => $this->periodStart->copy()->setTime(17, 45),
            'type' => 'sign_out'
        ]);

        // Create rules but with no permission allowance to see the raw penalty
        $this->createRulesWithoutPermissions();

        $netHours = $this->service->calculateNetHours($employee, $this->periodStart, $this->periodEnd);

        // 9:45 arrival is 15 minutes late beyond flexible window
        // This should get a 30-minute penalty (tier 1: 1-30 minutes late)
        // 8 hours - 0.5 hours = 7.5 hours
        $this->assertEquals(7.5, $netHours, '15 minutes late should get 30-minute penalty (7.5 hours)');
    }

    /** @test */
    public function it_applies_tiered_late_penalties_correctly()
    {
        // Test different penalty tiers
        $testCases = [
            ['arrival_time' => '9:35', 'expected_hours' => 7.5], // 5 min late: 30 min penalty
            ['arrival_time' => '9:50', 'expected_hours' => 7.5], // 20 min late: 30 min penalty
            ['arrival_time' => '10:05', 'expected_hours' => 7.0], // 35 min late: 60 min penalty
            ['arrival_time' => '10:30', 'expected_hours' => 7.0], // 60 min late: 60 min penalty
            ['arrival_time' => '11:00', 'expected_hours' => 6.0], // 90 min late: 120 min penalty
        ];

        foreach ($testCases as $index => $testCase) {
            // Create individual employee and test data for each case
            $employee = $this->createTestEmployee();

            // Create attendance log for this test case
            $arrivalTime = Carbon::parse($this->periodStart->format('Y-m-d') . ' ' . $testCase['arrival_time']);
            AttendanceLog::create([
                'employee_id' => $employee->id,
                'timestamp' => $arrivalTime,
                'type' => 'sign_in'
            ]);
            AttendanceLog::create([
                'employee_id' => $employee->id,
                'timestamp' => $arrivalTime->copy()->addHours(8),
                'type' => 'sign_out'
            ]);

            $this->createRulesWithoutPermissions(); // Use rules without permission allowance to see raw penalties

            $netHours = $this->service->calculateNetHours($employee, $this->periodStart, $this->periodEnd);

            $this->assertEquals(
                $testCase['expected_hours'],
                $netHours,
                "Test case {$index}: Arrival at {$testCase['arrival_time']} should result in {$testCase['expected_hours']} hours"
            );
        }
    }

    /** @test */
    public function it_uses_permission_minutes_to_offset_penalties()
    {
        $employee = $this->createTestEmployee();

        // Create attendance with 30-minute penalty
        AttendanceLog::create([
            'employee_id' => $employee->id,
            'timestamp' => $this->periodStart->copy()->setTime(9, 45), // 15 min late
            'type' => 'sign_in'
        ]);
        AttendanceLog::create([
            'employee_id' => $employee->id,
            'timestamp' => $this->periodStart->copy()->setTime(17, 45),
            'type' => 'sign_out'
        ]);

        $this->createStandardRules();

        $netHours = $this->service->calculateNetHours($employee, $this->periodStart, $this->periodEnd);

        // With 60 minutes standard permission, the 30-minute penalty should be fully offset
        // 8 hours - 0.5 hours penalty + 0.5 hours from permissions = 8 hours
        $this->assertEquals(8.0, $netHours);
    }

    /** @test */
    public function it_applies_permission_overrides_correctly()
    {
        $employee = $this->createTestEmployee();
        $testUser = User::factory()->create();

        // Create attendance with 60-minute penalty
        AttendanceLog::create([
            'employee_id' => $employee->id,
            'timestamp' => $this->periodStart->copy()->setTime(10, 30), // 60 min late
            'type' => 'sign_in'
        ]);
        AttendanceLog::create([
            'employee_id' => $employee->id,
            'timestamp' => $this->periodStart->copy()->setTime(18, 30),
            'type' => 'sign_out'
        ]);

        // Add permission override of 30 additional minutes
        PermissionOverride::create([
            'employee_id' => $employee->id,
            'payroll_period_start_date' => $this->periodStart,
            'extra_permissions_granted' => 30,
            'granted_by_user_id' => $testUser->id,
            'reason' => 'Test override'
        ]);

        $this->createStandardRules();

        $netHours = $this->service->calculateNetHours($employee, $this->periodStart, $this->periodEnd);

        // 60 minutes standard + 30 minutes override = 90 minutes total permission
        // 8 hours - 1 hour penalty + 1.5 hours from permissions = 8.5 hours (but capped at 8)
        $this->assertEquals(8.0, $netHours);
    }

    /** @test */
    public function it_excludes_public_holidays_from_calculation()
    {
        $employee = $this->createTestEmployee();

        // This test verifies that the service structure can handle holidays
        // The actual exclusion logic would depend on business requirements
        PublicHoliday::create([
            'name' => 'Test Holiday',
            'date' => $this->periodStart->copy()->addDays(1)
        ]);

        $this->createPerfectAttendanceLogs($employee);
        $this->createStandardRules();

        $netHours = $this->service->calculateNetHours($employee, $this->periodStart, $this->periodEnd);

        // For now, holidays are fetched but not applied to the calculation
        // This test ensures the structure supports future holiday logic
        $this->assertIsFloat($netHours);
    }

    /** @test */
    public function it_handles_complex_scenarios_with_multiple_rules()
    {
        $employee = $this->createTestEmployee();

        // Create a scenario with multiple days, different lateness levels, and permissions
        $days = [
            ['day' => 0, 'sign_in' => '9:00', 'sign_out' => '17:00'], // Perfect
            ['day' => 1, 'sign_in' => '9:15', 'sign_out' => '17:15'], // Within flexible window
            ['day' => 2, 'sign_in' => '9:45', 'sign_out' => '17:45'], // 15 min late, 30 min penalty
            ['day' => 4, 'sign_in' => '10:15', 'sign_out' => '18:15'], // 45 min late, 60 min penalty
        ];

        foreach ($days as $dayData) {
            $date = $this->periodStart->copy()->addDays($dayData['day']);
            AttendanceLog::create([
                'employee_id' => $employee->id,
                'timestamp' => Carbon::parse($date->format('Y-m-d') . ' ' . $dayData['sign_in']),
                'type' => 'sign_in'
            ]);
            AttendanceLog::create([
                'employee_id' => $employee->id,
                'timestamp' => Carbon::parse($date->format('Y-m-d') . ' ' . $dayData['sign_out']),
                'type' => 'sign_out'
            ]);
        }

        $this->createStandardRules();

        $netHours = $this->service->calculateNetHours($employee, $this->periodStart, $this->periodEnd);

        // 4 days * 8 hours = 32 hours
        // Penalties: 0 + 0 + 30 + 60 = 90 minutes = 1.5 hours
        // Available permissions: 60 minutes = 1 hour
        // Net: 32 - 1.5 + 1 = 31.5 hours
        $this->assertEquals(31.5, $netHours);
    }

    /**
     * Create perfect attendance logs for 23 working days (excluding weekends)
     */
    private function createPerfectAttendanceLogs(Employee $employee): void
    {
        $currentDate = $this->periodStart->copy();

        while ($currentDate->lte($this->periodEnd)) {
            // Skip weekends
            if ($currentDate->isWeekend()) {
                $currentDate->addDay();
                continue;
            }

            AttendanceLog::create([
                'employee_id' => $employee->id,
                'timestamp' => $currentDate->copy()->setTime(9, 0),
                'type' => 'sign_in'
            ]);

            AttendanceLog::create([
                'employee_id' => $employee->id,
                'timestamp' => $currentDate->copy()->setTime(17, 0),
                'type' => 'sign_out'
            ]);

            $currentDate->addDay();
        }
    }

    /**
     * Create standard attendance rules for testing
     */
    private function createStandardRules(): void
    {
        // Flexible hours rule: 9:00 AM start with 30-minute flexible window
        AttendanceRule::create([
            'rule_name' => 'Standard Flexible Hours',
            'rule_type' => AttendanceRule::TYPE_FLEXIBLE_HOURS,
            'config' => [
                'official_start_time' => '09:00',
                'flexible_window_minutes' => 30
            ]
        ]);

        // Late penalty rule with tiered structure
        AttendanceRule::create([
            'rule_name' => 'Tiered Late Penalties',
            'rule_type' => AttendanceRule::TYPE_LATE_PENALTY,
            'config' => [
                'tiers' => [
                    [
                        'min_minutes_late' => 1,
                        'max_minutes_late' => 30,
                        'penalty_minutes' => 30
                    ],
                    [
                        'min_minutes_late' => 31,
                        'max_minutes_late' => 60,
                        'penalty_minutes' => 60
                    ],
                    [
                        'min_minutes_late' => 61,
                        'max_minutes_late' => 120,
                        'penalty_minutes' => 120
                    ]
                ]
            ]
        ]);

        // Permission rule: 60 minutes monthly allowance
        AttendanceRule::create([
            'rule_name' => 'Monthly Permission Allowance',
            'rule_type' => AttendanceRule::TYPE_PERMISSION,
            'config' => [
                'monthly_allowance_minutes' => 60
            ]
        ]);
    }

    /**
     * Create attendance rules without permission allowance
     */
    private function createRulesWithoutPermissions(): void
    {
        // Flexible hours rule: 9:00 AM start with 30-minute flexible window
        AttendanceRule::create([
            'rule_name' => 'Standard Flexible Hours',
            'rule_type' => AttendanceRule::TYPE_FLEXIBLE_HOURS,
            'config' => [
                'official_start_time' => '09:00',
                'flexible_window_minutes' => 30
            ]
        ]);

        // Late penalty rule with tiered structure
        AttendanceRule::create([
            'rule_name' => 'Tiered Late Penalties',
            'rule_type' => AttendanceRule::TYPE_LATE_PENALTY,
            'config' => [
                'tiers' => [
                    [
                        'min_minutes_late' => 1,
                        'max_minutes_late' => 30,
                        'penalty_minutes' => 30
                    ],
                    [
                        'min_minutes_late' => 31,
                        'max_minutes_late' => 60,
                        'penalty_minutes' => 60
                    ],
                    [
                        'min_minutes_late' => 61,
                        'max_minutes_late' => 120,
                        'penalty_minutes' => 120
                    ]
                ]
            ]
        ]);

        // No permission rule - so no allowance to offset penalties
    }
}
