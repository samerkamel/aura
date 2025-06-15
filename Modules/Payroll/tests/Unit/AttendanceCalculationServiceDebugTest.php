<?php

namespace Modules\Payroll\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payroll\Services\AttendanceCalculationService;
use Modules\HR\Models\Employee;
use Modules\Attendance\Models\AttendanceLog;
use Modules\Attendance\Models\AttendanceRule;
use Carbon\Carbon;

/**
 * Simple debugging test for AttendanceCalculationService
 */
class AttendanceCalculationServiceDebugTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function debug_basic_calculation()
    {
        // Create employee
        $employee = Employee::factory()->create([
            'name' => 'Debug Employee',
            'email' => 'debug@example.com',
            'base_salary' => 5000.00
        ]);

        // Set period
        $periodStart = Carbon::parse('2025-05-26');
        $periodEnd = Carbon::parse('2025-06-25');

        // Create simple attendance log
        $signInLog = AttendanceLog::create([
            'employee_id' => $employee->id,
            'timestamp' => $periodStart->copy()->setTime(9, 15),
            'type' => 'sign_in'
        ]);
        $signOutLog = AttendanceLog::create([
            'employee_id' => $employee->id,
            'timestamp' => $periodStart->copy()->setTime(17, 15),
            'type' => 'sign_out'
        ]);

        // Verify logs were created
        $this->assertTrue($signInLog->exists());
        $this->assertTrue($signOutLog->exists());

        // Check we can fetch the logs
        $logs = AttendanceLog::where('employee_id', $employee->id)
            ->whereBetween('timestamp', [$periodStart, $periodEnd])
            ->orderBy('timestamp')
            ->get();

        $this->assertCount(2, $logs);
        $this->assertEquals('sign_in', $logs->first()->type);
        $this->assertEquals('sign_out', $logs->last()->type);

        // Create basic rules
        AttendanceRule::create([
            'rule_name' => 'Debug Flexible Hours',
            'rule_type' => AttendanceRule::TYPE_FLEXIBLE_HOURS,
            'config' => [
                'official_start_time' => '09:00',
                'flexible_window_minutes' => 30
            ]
        ]);

        AttendanceRule::create([
            'rule_name' => 'Debug Late Penalties',
            'rule_type' => AttendanceRule::TYPE_LATE_PENALTY,
            'config' => [
                'tiers' => [
                    [
                        'min_minutes_late' => 1,
                        'max_minutes_late' => 30,
                        'penalty_minutes' => 30
                    ]
                ]
            ]
        ]);

        AttendanceRule::create([
            'rule_name' => 'Debug Permission',
            'rule_type' => AttendanceRule::TYPE_PERMISSION,
            'config' => [
                'monthly_allowance_minutes' => 60
            ]
        ]);

        // Now test the service
        $service = new AttendanceCalculationService();
        $netHours = $service->calculateNetHours($employee, $periodStart, $periodEnd);

        // Should get 8 hours since 9:15 is within 30-minute flexible window
        $this->assertEquals(8.0, $netHours, 'Expected 8 hours for arrival within flexible window');
    }
}
