<?php

namespace Modules\Attendance\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Attendance\Models\AttendanceLog;
use Modules\HR\Models\Employee;
use App\Models\User;

/**
 * AttendanceImportTest
 *
 * Tests CSV import functionality for attendance logs
 */
class AttendanceImportTest extends TestCase
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
     * Test that import form can be accessed
     */
    public function test_import_form_can_be_accessed(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('attendance.import.create'));

        $response->assertStatus(200);
        $response->assertViewIs('attendance::import.create');
        $response->assertViewHas(['expectedHeaders', 'validLogTypes']);
    }

    /**
     * Test successful CSV import with valid data
     */
    public function test_successful_csv_import(): void
    {
        // Create test employees
        $employee1 = Employee::factory()->create(['id' => 1]);
        $employee2 = Employee::factory()->create(['id' => 2]);

        // Create valid CSV content
        $csvContent = "EmployeeID,DateTime,LogType\n";
        $csvContent .= "1,2025-06-14 09:00:00,sign_in\n";
        $csvContent .= "1,2025-06-14 17:00:00,sign_out\n";
        $csvContent .= "2,2025-06-14 09:30:00,sign_in\n";
        $csvContent .= "2,2025-06-14 17:30:00,sign_out\n";

        // Create temporary CSV file
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('attendance.csv', $csvContent);

        // Perform import
        $response = $this->actingAs($this->user)
            ->post(route('attendance.import.store'), [
                'csv_file' => $file
            ]);

        // Assert response
        $response->assertStatus(200);
        $response->assertViewIs('attendance::import.summary');
        $response->assertViewHas('results');

        // Assert database records were created
        $this->assertDatabaseCount('attendance_logs', 4);

        // Assert specific records
        $this->assertDatabaseHas('attendance_logs', [
            'employee_id' => 1,
            'type' => 'sign_in'
        ]);

        $this->assertDatabaseHas('attendance_logs', [
            'employee_id' => 2,
            'type' => 'sign_out'
        ]);

        // Assert import summary
        $results = $response->viewData('results');
        $this->assertEquals(4, $results['total_rows']);
        $this->assertEquals(4, $results['successful_imports']);
        $this->assertEmpty($results['failed_rows']);
        $this->assertEmpty($results['errors']);
    }

    /**
     * Test CSV import with invalid data
     */
    public function test_csv_import_with_invalid_data(): void
    {
        // Create one valid employee
        $employee1 = Employee::factory()->create(['id' => 1]);

        // Create CSV with mixed valid/invalid data
        $csvContent = "EmployeeID,DateTime,LogType\n";
        $csvContent .= "1,2025-06-14 09:00:00,sign_in\n";           // Valid
        $csvContent .= "999,2025-06-14 09:00:00,sign_in\n";         // Invalid employee ID
        $csvContent .= "1,invalid-date,sign_in\n";                  // Invalid date format
        $csvContent .= "1,2025-06-14 09:00:00,invalid_type\n";      // Invalid log type
        $csvContent .= ",2025-06-14 09:00:00,sign_in\n";            // Missing employee ID

        // Create temporary CSV file
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('attendance_errors.csv', $csvContent);

        // Perform import
        $response = $this->actingAs($this->user)
            ->post(route('attendance.import.store'), [
                'csv_file' => $file
            ]);

        // Assert response
        $response->assertStatus(200);
        $response->assertViewIs('attendance::import.summary');

        // Assert only valid record was created
        $this->assertDatabaseCount('attendance_logs', 1);
        $this->assertDatabaseHas('attendance_logs', [
            'employee_id' => 1,
            'type' => 'sign_in'
        ]);

        // Assert import summary shows errors
        $results = $response->viewData('results');
        $this->assertEquals(5, $results['total_rows']);
        $this->assertEquals(1, $results['successful_imports']);
        $this->assertCount(4, $results['failed_rows']);

        // Check specific error messages
        $failedRows = $results['failed_rows'];
        $this->assertStringContainsString('Employee not found', implode(' ', $failedRows[0]['errors']));
        $this->assertStringContainsString('Invalid DateTime format', implode(' ', $failedRows[1]['errors']));
        $this->assertStringContainsString('Invalid LogType', implode(' ', $failedRows[2]['errors']));
        $this->assertStringContainsString('Missing or empty field', implode(' ', $failedRows[3]['errors']));
    }

    /**
     * Test CSV import with invalid headers
     */
    public function test_csv_import_with_invalid_headers(): void
    {
        // Create CSV with wrong headers
        $csvContent = "WrongHeader,AnotherWrong,StillWrong\n";
        $csvContent .= "1,2025-06-14 09:00:00,sign_in\n";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('wrong_headers.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->post(route('attendance.import.store'), [
                'csv_file' => $file
            ]);

        $response->assertStatus(200);
        $response->assertViewIs('attendance::import.summary');

        // No records should be created
        $this->assertDatabaseCount('attendance_logs', 0);

        // Assert error message about headers
        $results = $response->viewData('results');
        $this->assertNotEmpty($results['errors']);
        $this->assertStringContainsString('Invalid CSV headers', $results['errors'][0]);
    }

    /**
     * Test file upload validation
     */
    public function test_file_upload_validation(): void
    {
        // Test without file
        $response = $this->actingAs($this->user)
            ->post(route('attendance.import.store'), []);

        $response->assertSessionHasErrors('csv_file');

        // Test with non-CSV file
        Storage::fake('local');
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->user)
            ->post(route('attendance.import.store'), [
                'csv_file' => $file
            ]);

        $response->assertSessionHasErrors('csv_file');

        // Test with oversized file (simulate large file)
        $largeFile = UploadedFile::fake()->create('large.csv', 11000); // 11MB > 10MB limit

        $response = $this->actingAs($this->user)
            ->post(route('attendance.import.store'), [
                'csv_file' => $largeFile
            ]);

        $response->assertSessionHasErrors('csv_file');
    }

    /**
     * Test authentication requirement
     */
    public function test_import_requires_authentication(): void
    {
        $response = $this->get(route('attendance.import.create'));
        $response->assertRedirect(route('login'));

        $response = $this->post(route('attendance.import.store'), []);
        $response->assertRedirect(route('login'));
    }
}
