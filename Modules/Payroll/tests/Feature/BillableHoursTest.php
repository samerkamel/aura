<?php

namespace Modules\Payroll\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Payroll\Models\BillableHour;
use Modules\HR\Models\Employee;
use App\Models\User;
use Carbon\Carbon;

/**
 * BillableHoursTest
 *
 * Feature tests for billable hours management including manual entry
 * and CSV import functionality.
 *
 * @author Dev Agent
 */
class BillableHoursTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user for authentication
        $this->user = User::factory()->create();
    }

    /**
     * Test that billable hours index page can be accessed.
     */
    public function test_billable_hours_index_can_be_accessed(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('payroll.billable-hours.index'));

        $response->assertStatus(200);
        $response->assertViewIs('payroll::billable-hours.index');
        $response->assertViewHas(['employees', 'currentPeriod', 'expectedHeaders']);
    }

    /**
     * Test manual entry of billable hours.
     */
    public function test_manual_entry_of_billable_hours(): void
    {
        // Create test employees
        $employee1 = Employee::factory()->create(['status' => 'active']);
        $employee2 = Employee::factory()->create(['status' => 'active']);

        $hoursData = [
            $employee1->id => 40.5,
            $employee2->id => 35.25,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('payroll.billable-hours.store'), [
                'hours' => $hoursData
            ]);

        $response->assertRedirect(route('payroll.billable-hours.index'));
        $response->assertSessionHas('success');

        // Assert database records were created
        $currentPeriod = Carbon::now()->startOfMonth();

        $this->assertDatabaseHas('billable_hours', [
            'employee_id' => $employee1->id,
            'payroll_period_start_date' => $currentPeriod->format('Y-m-d'),
            'hours' => 40.5
        ]);

        $this->assertDatabaseHas('billable_hours', [
            'employee_id' => $employee2->id,
            'payroll_period_start_date' => $currentPeriod->format('Y-m-d'),
            'hours' => 35.25
        ]);
    }

    /**
     * Test manual entry validation.
     */
    public function test_manual_entry_validation(): void
    {
        $employee = Employee::factory()->create(['status' => 'active']);

        // Test with invalid hours (negative value)
        $response = $this->actingAs($this->user)
            ->post(route('payroll.billable-hours.store'), [
                'hours' => [$employee->id => -5]
            ]);

        $response->assertSessionHasErrors(['hours.' . $employee->id]);

        // Test with invalid hours (too large)
        $response = $this->actingAs($this->user)
            ->post(route('payroll.billable-hours.store'), [
                'hours' => [$employee->id => 1000]
            ]);

        $response->assertSessionHasErrors(['hours.' . $employee->id]);
    }

    /**
     * Test successful CSV import with valid data.
     */
    public function test_successful_csv_import(): void
    {
        // Create test employees
        $employee1 = Employee::factory()->create(['id' => 1, 'status' => 'active']);
        $employee2 = Employee::factory()->create(['id' => 2, 'status' => 'active']);

        // Create valid CSV content
        $csvContent = "EmployeeID,BillableHours\n";
        $csvContent .= "1,40.5\n";
        $csvContent .= "2,35.25\n";

        // Create temporary CSV file
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('billable_hours.csv', $csvContent);

        // Perform import
        $response = $this->actingAs($this->user)
            ->post(route('payroll.billable-hours.store'), [
                'csv_file' => $file
            ]);

        $response->assertStatus(200);
        $response->assertViewIs('payroll::billable-hours.import-summary');

        // Assert records were created
        $currentPeriod = Carbon::now()->startOfMonth();

        $this->assertDatabaseHas('billable_hours', [
            'employee_id' => 1,
            'payroll_period_start_date' => $currentPeriod->format('Y-m-d'),
            'hours' => 40.5
        ]);

        $this->assertDatabaseHas('billable_hours', [
            'employee_id' => 2,
            'payroll_period_start_date' => $currentPeriod->format('Y-m-d'),
            'hours' => 35.25
        ]);

        // Check view data
        $results = $response->viewData('results');
        $this->assertEquals(2, $results['total_rows']);
        $this->assertEquals(2, $results['successful_imports']);
        $this->assertEmpty($results['failed_rows']);
        $this->assertEmpty($results['errors']);
    }

    /**
     * Test CSV import with validation errors.
     */
    public function test_csv_import_with_validation_errors(): void
    {
        // Create test employee
        $employee = Employee::factory()->create(['id' => 123, 'status' => 'active']);

        // Create CSV with mixed valid/invalid data
        $csvContent = "EmployeeID,BillableHours\n";
        $csvContent .= "123,40.5\n";                     // Valid
        $csvContent .= "999,35.0\n";                     // Invalid employee
        $csvContent .= "123,-5.0\n";                     // Invalid hours (negative)
        $csvContent .= "123,1000.0\n";                   // Invalid hours (too large)
        $csvContent .= ",30.0\n";                        // Missing employee ID

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('invalid_data.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->post(route('payroll.billable-hours.store'), [
                'csv_file' => $file
            ]);

        $response->assertStatus(200);
        $response->assertViewIs('payroll::billable-hours.import-summary');

        // Assert results
        $results = $response->viewData('results');
        $this->assertEquals(5, $results['total_rows']);
        $this->assertEquals(1, $results['successful_imports']);
        $this->assertCount(4, $results['failed_rows']);
        $this->assertEmpty($results['errors']);

        // Assert only valid record was created
        $this->assertDatabaseCount('billable_hours', 1);

        // Check specific error messages
        $failedRows = $results['failed_rows'];
        $this->assertStringContainsString('Employee not found', implode(' ', $failedRows[0]['errors']));
        $this->assertStringContainsString('between 0 and 999.99', implode(' ', $failedRows[1]['errors']));
        $this->assertStringContainsString('between 0 and 999.99', implode(' ', $failedRows[2]['errors']));
        $this->assertStringContainsString('Missing or empty field', implode(' ', $failedRows[3]['errors']));
    }

    /**
     * Test CSV import with invalid headers.
     */
    public function test_csv_import_with_invalid_headers(): void
    {
        // Create CSV with wrong headers
        $csvContent = "WrongHeader,AnotherWrong\n";
        $csvContent .= "1,40.5\n";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('wrong_headers.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->post(route('payroll.billable-hours.store'), [
                'csv_file' => $file
            ]);

        $response->assertStatus(200);
        $response->assertViewIs('payroll::billable-hours.import-summary');

        // No records should be created
        $this->assertDatabaseCount('billable_hours', 0);

        // Assert error message about headers
        $results = $response->viewData('results');
        $this->assertNotEmpty($results['errors']);
        $this->assertStringContainsString('Invalid CSV headers', $results['errors'][0]);
    }

    /**
     * Test file upload validation.
     */
    public function test_file_upload_validation(): void
    {
        // Test without file
        $response = $this->actingAs($this->user)
            ->post(route('payroll.billable-hours.store'), []);

        $response->assertSessionHasErrors('hours');

        // Test with non-CSV file
        Storage::fake('local');
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->user)
            ->post(route('payroll.billable-hours.store'), [
                'csv_file' => $file
            ]);

        $response->assertSessionHasErrors('csv_file');

        // Test with oversized file (simulate large file)
        $largeFile = UploadedFile::fake()->create('large.csv', 11000); // 11MB > 10MB limit

        $response = $this->actingAs($this->user)
            ->post(route('payroll.billable-hours.store'), [
                'csv_file' => $largeFile
            ]);

        $response->assertSessionHasErrors('csv_file');
    }

    /**
     * Test that billable hours are updated (upsert functionality).
     */
    public function test_billable_hours_upsert_functionality(): void
    {
        $employee = Employee::factory()->create(['status' => 'active']);
        $currentPeriod = Carbon::now()->startOfMonth();

        // Create initial billable hours record
        BillableHour::create([
            'employee_id' => $employee->id,
            'payroll_period_start_date' => $currentPeriod,
            'hours' => 30.0
        ]);

        // Update via manual entry
        $response = $this->actingAs($this->user)
            ->post(route('payroll.billable-hours.store'), [
                'hours' => [$employee->id => 45.5]
            ]);

        $response->assertRedirect(route('payroll.billable-hours.index'));

        // Assert record was updated, not duplicated
        $this->assertDatabaseCount('billable_hours', 1);
        $this->assertDatabaseHas('billable_hours', [
            'employee_id' => $employee->id,
            'payroll_period_start_date' => $currentPeriod->format('Y-m-d'),
            'hours' => 45.5
        ]);
    }

    /**
     * Test authentication requirement.
     */
    public function test_billable_hours_requires_authentication(): void
    {
        $response = $this->get(route('payroll.billable-hours.index'));
        $response->assertRedirect(route('login'));

        $response = $this->post(route('payroll.billable-hours.store'), []);
        $response->assertRedirect(route('login'));
    }

    /**
     * Test that only active employees are shown.
     */
    public function test_only_active_employees_are_shown(): void
    {
        // Create employees with different statuses
        $activeEmployee = Employee::factory()->create(['status' => 'active']);
        $terminatedEmployee = Employee::factory()->create(['status' => 'terminated']);
        $resignedEmployee = Employee::factory()->create(['status' => 'resigned']);

        $response = $this->actingAs($this->user)
            ->get(route('payroll.billable-hours.index'));

        $response->assertStatus(200);

        $employees = $response->viewData('employees');
        $this->assertCount(1, $employees);
        $this->assertEquals($activeEmployee->id, $employees->first()->id);
    }
}
