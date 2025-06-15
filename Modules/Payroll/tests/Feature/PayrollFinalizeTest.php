<?php

namespace Modules\Payroll\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HR\Models\Employee;
use Modules\Payroll\Models\PayrollRun;
use Modules\Payroll\Models\BillableHour;
use Modules\Attendance\Models\Setting;
use Modules\Attendance\Models\AttendanceRule;
use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

/**
 * PayrollFinalizeTest
 *
 * Tests the payroll finalization and Excel export functionality.
 * Verifies that payroll runs are created correctly and Excel files are generated.
 *
 * @author Dev Agent
 */
class PayrollFinalizeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var User
     */
    private $user;

    /**
     * @var Employee
     */
    private $employee;

    /**
     * @var Carbon
     */
    private $periodStart;

    /**
     * @var Carbon
     */
    private $periodEnd;

    /**
     * Set up test data before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test user for authentication
        $this->user = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        // Create test employee with bank information
        $this->employee = Employee::create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'position' => 'Software Developer',
            'start_date' => Carbon::now()->subMonths(6),
            'status' => 'active',
            'base_salary' => 5000.00,
            'contact_info' => ['phone' => '123-456-7890'],
            'bank_info' => [
                'bank_name' => 'Test Bank',
                'account_number' => '1234567890',
            ],
        ]);

        // Set up test period (current month)
        $this->periodStart = Carbon::now()->startOfMonth();
        $this->periodEnd = $this->periodStart->copy()->endOfMonth();

        // Set up payroll weight settings
        Setting::set('weight_attendance_pct', 60, 'Attendance weight for payroll');
        Setting::set('weight_billable_hours_pct', 40, 'Billable hours weight for payroll');

        // Create basic attendance rule
        AttendanceRule::create([
            'rule_name' => 'Default Flexible Hours',
            'rule_type' => 'flexible_hours',
            'config' => [
                'min_hours_per_day' => 8,
                'max_hours_per_day' => 10,
            ],
            'created_by' => $this->user->id,
        ]);

        // Create billable hours record
        BillableHour::create([
            'employee_id' => $this->employee->id,
            'payroll_period_start_date' => $this->periodStart,
            'hours' => 120.0,
        ]);
    }

    /**
     * Test payroll finalization creates payroll run records.
     */
    public function test_payroll_finalization_creates_records(): void
    {
        $this->actingAs($this->user);

        $period = $this->periodStart->format('Y-m');

        $response = $this->post(route('payroll.run.finalize'), [
            'period' => $period,
        ]);

        // Debug: Check what we got back
        if ($response->isServerError()) {
            $this->fail('Server error: ' . $response->getContent());
        }

        if ($response->isClientError()) {
            $this->fail('Client error: ' . $response->getContent());
        }

        // Should either redirect back with success or download Excel file
        $this->assertTrue(
            $response->isRedirect() ||
                $response->headers->get('content-type') === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        // Verify payroll run record was created
        $this->assertDatabaseHas('payroll_runs', [
            'employee_id' => $this->employee->id,
            'period_start_date' => $this->periodStart->toDateString(),
            'period_end_date' => $this->periodEnd->toDateString(),
            'status' => 'finalized',
        ]);

        $payrollRun = PayrollRun::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($payrollRun);
        $this->assertEquals(5000.00, $payrollRun->base_salary);
        $this->assertNotNull($payrollRun->final_salary);
        $this->assertNotNull($payrollRun->calculation_snapshot);
        $this->assertIsArray($payrollRun->calculation_snapshot);
    }

    /**
     * Test Excel file generation and download.
     */
    public function test_excel_file_generation(): void
    {
        $this->actingAs($this->user);

        $period = $this->periodStart->format('Y-m');

        $response = $this->post(route('payroll.run.finalize'), [
            'period' => $period,
        ]);

        // Should be a successful Excel download response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('content-type')
        );

        // Check that the content disposition header contains our filename
        $contentDisposition = $response->headers->get('content-disposition');
        $expectedFilename = 'payroll_bank_sheet_' . $this->periodStart->format('Y_m') . '.xlsx';
        $this->assertStringContainsString($expectedFilename, $contentDisposition);
    }

    /**
     * Test validation for required period parameter.
     */
    public function test_validation_requires_period(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('payroll.run.finalize'), []);

        $response->assertSessionHasErrors(['period']);
    }

    /**
     * Test validation for invalid period format.
     */
    public function test_validation_requires_valid_period_format(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('payroll.run.finalize'), [
            'period' => 'invalid-format',
        ]);

        $response->assertSessionHasErrors(['period']);
    }

    /**
     * Test that duplicate finalization attempts handle gracefully.
     */
    public function test_duplicate_finalization_updates_existing_record(): void
    {
        $this->actingAs($this->user);

        $period = $this->periodStart->format('Y-m');

        // First finalization
        $this->post(route('payroll.run.finalize'), ['period' => $period]);

        $firstRun = PayrollRun::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($firstRun, 'First payroll run should be created');
        $firstUpdatedAt = $firstRun->updated_at;

        // Small delay to ensure timestamp difference
        sleep(1);

        // Second finalization (should update, not create new)
        $this->post(route('payroll.run.finalize'), ['period' => $period]);

        $this->assertEquals(1, PayrollRun::where('employee_id', $this->employee->id)->count());

        $updatedRun = PayrollRun::where('employee_id', $this->employee->id)->first();
        $this->assertTrue($updatedRun->updated_at->gt($firstUpdatedAt));
    }
}
