<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Leave\Services\LeaveBalanceService;
use Modules\HR\Models\Employee;
use Modules\Leave\Models\LeavePolicy;
use Modules\Leave\Models\LeavePolicyTier;
use Modules\Leave\Models\LeaveRecord;
use Carbon\Carbon;

/**
 * Leave Balance Service Unit Tests
 *
 * Tests the calculation of employee leave balances based on
 * policies, tenure, and used leave records.
 *
 * @author Dev Agent
 */
class LeaveBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeaveBalanceService $service;
    private Employee $employee;
    private LeavePolicy $leavePolicy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LeaveBalanceService();

        // Create test employee with exactly 2 years tenure in 2025
        $this->employee = Employee::factory()->create([
            'name' => 'Test Employee',
            'email' => 'test@example.com',
            'start_date' => Carbon::parse('2023-01-01'), // Started 2 years before 2025
        ]);

        // Create test leave policy
        $this->leavePolicy = LeavePolicy::factory()->create([
            'name' => 'Annual Leave',
            'type' => 'pto',
            'initial_days' => 15,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function calculates_basic_leave_balance_without_tiers()
    {
        $balance = $this->service->getLeaveBalanceSummary($this->employee, 2025);

        $this->assertEquals(2025, $balance['year']);
        $this->assertEquals($this->employee->id, $balance['employee_id']);
        $this->assertCount(1, $balance['balances']);

        $policyBalance = $balance['balances'][0];
        $this->assertEquals($this->leavePolicy->id, $policyBalance['policy_id']);
        $this->assertEquals('Annual Leave', $policyBalance['policy_name']);
        $this->assertEquals(15, $policyBalance['entitled_days']);
        $this->assertEquals(0, $policyBalance['used_days']);
        $this->assertEquals(15, $policyBalance['remaining_days']);
    }

    /** @test */
    public function calculates_balance_with_policy_tiers()
    {
        // Create policy tiers
        LeavePolicyTier::factory()->create([
            'leave_policy_id' => $this->leavePolicy->id,
            'min_years' => 0,
            'max_years' => 1,
            'annual_days' => 10,
        ]);

        LeavePolicyTier::factory()->create([
            'leave_policy_id' => $this->leavePolicy->id,
            'min_years' => 2,
            'max_years' => null,
            'annual_days' => 20,
        ]);

        $balance = $this->service->getLeaveBalanceSummary($this->employee, 2025);

        $policyBalance = $balance['balances'][0];
        $this->assertEquals(20, $policyBalance['entitled_days']); // Should use Second Year+ tier
        $this->assertEquals(2, $policyBalance['applicable_tier']['min_years']);
        $this->assertNull($policyBalance['applicable_tier']['max_years']);
        $this->assertEquals(20, $policyBalance['applicable_tier']['annual_days']);
    }

    /** @test */
    public function calculates_used_leave_days_correctly()
    {
        // Create leave records for the year
        LeaveRecord::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_policy_id' => $this->leavePolicy->id,
            'start_date' => Carbon::parse('2025-01-10'),
            'end_date' => Carbon::parse('2025-01-12'), // 3 days
            'status' => LeaveRecord::STATUS_APPROVED,
        ]);

        LeaveRecord::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_policy_id' => $this->leavePolicy->id,
            'start_date' => Carbon::parse('2025-03-15'),
            'end_date' => Carbon::parse('2025-03-15'), // 1 day
            'status' => LeaveRecord::STATUS_APPROVED,
        ]);

        // Create a pending leave record (should not count)
        LeaveRecord::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_policy_id' => $this->leavePolicy->id,
            'start_date' => Carbon::parse('2025-05-10'),
            'end_date' => Carbon::parse('2025-05-10'), // 1 day
            'status' => LeaveRecord::STATUS_PENDING,
        ]);

        $balance = $this->service->getLeaveBalanceSummary($this->employee, 2025);

        $policyBalance = $balance['balances'][0];
        $this->assertEquals(4, $policyBalance['used_days']); // 3 + 1 = 4 (pending not counted)
        $this->assertEquals(11, $policyBalance['remaining_days']); // 15 - 4 = 11
    }

    /** @test */
    public function calculates_pro_rated_entitlement_for_new_employee()
    {
        // Create employee who started mid-year
        $newEmployee = Employee::factory()->create([
            'start_date' => Carbon::parse('2025-07-01'), // Started July 1st
        ]);

        $balance = $this->service->getLeaveBalanceSummary($newEmployee, 2025);

        $policyBalance = $balance['balances'][0];

        // Should be pro-rated for 6 months (July-December)
        // 15 days * (184 days remaining / 365 days) ≈ 7.56 days
        $this->assertEqualsWithDelta(7.56, $policyBalance['entitled_days'], 0.1);
    }

    /** @test */
    public function handles_leave_spans_across_year_boundary()
    {
        // Create leave record that spans year boundary
        LeaveRecord::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_policy_id' => $this->leavePolicy->id,
            'start_date' => Carbon::parse('2024-12-30'),
            'end_date' => Carbon::parse('2025-01-05'), // 7 total days, but only 5 in 2025
            'status' => LeaveRecord::STATUS_APPROVED,
        ]);

        $balance = $this->service->getLeaveBalanceSummary($this->employee, 2025);

        $policyBalance = $balance['balances'][0];
        $this->assertEquals(5, $policyBalance['used_days']); // Only days in 2025 count
        $this->assertEquals(10, $policyBalance['remaining_days']); // 15 - 5 = 10
    }

    /** @test */
    public function check_leave_availability_returns_correct_status()
    {
        // Use 10 days already
        LeaveRecord::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_policy_id' => $this->leavePolicy->id,
            'start_date' => Carbon::parse('2025-01-01'),
            'end_date' => Carbon::parse('2025-01-10'), // 10 days
            'status' => LeaveRecord::STATUS_APPROVED,
        ]);

        // Check availability for 3 more days (should be available)
        $availability = $this->service->checkLeaveAvailability(
            $this->employee,
            $this->leavePolicy,
            Carbon::parse('2025-02-01'),
            Carbon::parse('2025-02-03'),
            2025
        );

        $this->assertTrue($availability['available']);
        $this->assertEquals(3, $availability['requested_days']);
        $this->assertEquals(5, $availability['remaining_days']); // 15 - 10 = 5
        $this->assertEquals(0, $availability['shortfall']);

        // Check availability for 8 more days (should not be available)
        $availability = $this->service->checkLeaveAvailability(
            $this->employee,
            $this->leavePolicy,
            Carbon::parse('2025-03-01'),
            Carbon::parse('2025-03-08'),
            2025
        );

        $this->assertFalse($availability['available']);
        $this->assertEquals(8, $availability['requested_days']);
        $this->assertEquals(5, $availability['remaining_days']);
        $this->assertEquals(3, $availability['shortfall']); // 8 - 5 = 3
    }

    /** @test */
    public function handles_multiple_leave_policies()
    {
        // Create additional leave policy
        $sickLeavePolicy = LeavePolicy::factory()->create([
            'name' => 'Sick Leave',
            'type' => 'sick_leave',
            'initial_days' => 10,
            'is_active' => true,
        ]);

        $balance = $this->service->getLeaveBalanceSummary($this->employee, 2025);

        $this->assertCount(2, $balance['balances']);

        // Find annual leave balance
        $annualLeaveBalance = collect($balance['balances'])->firstWhere('policy_name', 'Annual Leave');
        $this->assertEquals(15, $annualLeaveBalance['entitled_days']);

        // Find sick leave balance
        $sickLeaveBalance = collect($balance['balances'])->firstWhere('policy_name', 'Sick Leave');
        $this->assertEquals(10, $sickLeaveBalance['entitled_days']);
    }
}
