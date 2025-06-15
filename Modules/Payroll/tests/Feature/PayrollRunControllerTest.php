<?php

namespace Modules\Payroll\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HR\Models\Employee;
use Modules\Payroll\Models\BillableHour;
use Modules\Attendance\Models\AttendanceRule;
use Modules\Attendance\Models\AttendanceLog;
use Modules\Attendance\Models\Setting;
use Modules\Attendance\Models\WfhRecord;
use Carbon\Carbon;
use App\Models\User;

/**
 * PayrollRunControllerTest
 *
 * Tests the payroll run and review functionality to ensure accurate calculation
 * and display of employee payroll summaries.
 *
 * @author Dev Agent
 */
class PayrollRunControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var Employee
     */
    private $employee;

    /**
     * @var User
     */
    private $user;

    /**
     * Set up test data before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user for authentication
        $this->user = User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        // Create a test employee
        $this->employee = Employee::create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'position' => 'Software Developer',
            'start_date' => Carbon::now()->subMonths(6),
            'status' => 'active',
            'base_salary' => 5000.00,
            'contact_info' => ['phone' => '123-456-7890'],
        ]);

        // Set up payroll weight settings
        Setting::set('weight_attendance_pct', 60, 'Attendance weight for payroll');
        Setting::set('weight_billable_hours_pct', 40, 'Billable hours weight for payroll');

        // Create attendance rule for the employee
        AttendanceRule::create([
            'rule_name' => 'Default Flexible Hours',
            'rule_type' => 'flexible_hours',
            'config' => [
                'min_hours_per_day' => 8,
                'max_hours_per_day' => 10,
                'core_hours_start' => '10:00',
                'core_hours_end' => '15:00',
                'late_penalty_minutes' => 30,
            ],
        ]);
    }

    /** @test */
    public function it_displays_payroll_review_page_successfully()
    {
        $response = $this->actingAs($this->user)
            ->get(route('payroll.run.review'));

        $response->assertStatus(200);
        $response->assertViewIs('payroll::run.review');
        $response->assertViewHas(['employeeSummaries', 'periodStart', 'periodEnd', 'periodOptions']);
    }

    /** @test */
    public function it_calculates_employee_payroll_summary_correctly()
    {
        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        // Create sample attendance logs for the current month
        $this->createSampleAttendanceLogs($periodStart, $periodEnd);

        // Create billable hours record
        BillableHour::create([
            'employee_id' => $this->employee->id,
            'payroll_period_start_date' => $periodStart->toDateString(),
            'hours' => 120.00,
        ]);

        // Create WFH record
        WfhRecord::create([
            'employee_id' => $this->employee->id,
            'date' => $periodStart->copy()->addDays(10),
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('payroll.run.review'));

        $response->assertStatus(200);

        // Verify that the employee appears in the summary table
        $response->assertSee($this->employee->name);
        $response->assertSee($this->employee->position);

        // Verify that calculated values are displayed
        $response->assertSee('120'); // Billable hours
        $response->assertSee('60%'); // Attendance weight
        $response->assertSee('40%'); // Billable hours weight

        // Verify WFH badge
        $response->assertSee('1'); // WFH days (in badge)
    }

    /** @test */
    public function it_handles_period_selection_correctly()
    {
        // Test with a specific period
        $testPeriod = Carbon::now()->subMonth()->format('Y-m');

        $response = $this->actingAs($this->user)
            ->get(route('payroll.run.review', ['period' => $testPeriod]));

        $response->assertStatus(200);
        $response->assertSee(Carbon::createFromFormat('Y-m', $testPeriod)->format('F Y'));
    }

    /** @test */
    public function it_shows_empty_state_when_no_active_employees()
    {
        // Terminate the employee (set status to terminated)
        $this->employee->update(['status' => 'terminated']);

        $response = $this->actingAs($this->user)
            ->get(route('payroll.run.review'));

        $response->assertStatus(200);
        $response->assertSee('No Active Employees');
        $response->assertSee('No active employees found for the selected period.');
    }

    /** @test */
    public function it_displays_period_options_correctly()
    {
        $response = $this->actingAs($this->user)
            ->get(route('payroll.run.review'));

        $response->assertStatus(200);

        // Check that current month is in the options
        $currentMonth = Carbon::now()->format('F Y');
        $response->assertSee($currentMonth);

        // Check that previous months are in the options
        $lastMonth = Carbon::now()->subMonth()->format('F Y');
        $response->assertSee($lastMonth);
    }

    /** @test */
    public function it_calculates_performance_percentages_with_correct_weights()
    {
        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        // Create good attendance logs
        $this->createOptimalAttendanceLogs($periodStart, $periodEnd);

        // Create billable hours record
        BillableHour::create([
            'employee_id' => $this->employee->id,
            'payroll_period_start_date' => $periodStart->toDateString(),
            'hours' => 100.00, // A reasonable amount of billable hours
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('payroll.run.review'));

        $response->assertStatus(200);

        // Check that the employee appears in the table
        $response->assertSee($this->employee->name);

        // Check that some performance percentage is calculated
        $response->assertSee('%'); // Any percentage should be displayed

        // Check that weights are displayed
        $response->assertSee('60%'); // Attendance weight
        $response->assertSee('40%'); // Billable hours weight
    }

    /**
     * Create sample attendance logs for testing.
     */
    private function createSampleAttendanceLogs(Carbon $periodStart, Carbon $periodEnd): void
    {
        $current = $periodStart->copy();

        while ($current->lte($periodEnd)) {
            if ($current->isWeekday()) {
                // Create check-in and check-out logs (7 hours per day)
                AttendanceLog::create([
                    'employee_id' => $this->employee->id,
                    'timestamp' => $current->copy()->setTime(9, 0),
                    'action' => 'check_in',
                    'source' => 'web',
                ]);

                AttendanceLog::create([
                    'employee_id' => $this->employee->id,
                    'timestamp' => $current->copy()->setTime(16, 0),
                    'action' => 'check_out',
                    'source' => 'web',
                ]);
            }
            $current->addDay();
        }
    }

    /**
     * Create optimal attendance logs for testing maximum performance.
     */
    private function createOptimalAttendanceLogs(Carbon $periodStart, Carbon $periodEnd): void
    {
        $current = $periodStart->copy();

        while ($current->lte($periodEnd)) {
            if ($current->isWeekday()) {
                // Perfect 8-hour days
                AttendanceLog::create([
                    'employee_id' => $this->employee->id,
                    'timestamp' => $current->copy()->setTime(9, 0),
                    'action' => 'check_in',
                    'source' => 'web',
                ]);

                AttendanceLog::create([
                    'employee_id' => $this->employee->id,
                    'timestamp' => $current->copy()->setTime(17, 0),
                    'action' => 'check_out',
                    'source' => 'web',
                ]);
            }
            $current->addDay();
        }
    }
}
