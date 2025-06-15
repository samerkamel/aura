<?php

namespace Modules\Attendance\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Attendance\Services\AttendanceImportService;
use Modules\Attendance\Models\AttendanceLog;
use Modules\HR\Models\Employee;
use Illuminate\Support\Facades\Storage;

/**
 * AttendanceImportServiceTest
 *
 * Unit tests for AttendanceImportService class
 */
class AttendanceImportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AttendanceImportService $importService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importService = new AttendanceImportService();
    }

    /**
     * Test getting expected headers
     */
    public function test_get_expected_headers(): void
    {
        $headers = $this->importService->getExpectedHeaders();

        $this->assertIsArray($headers);
        $this->assertContains('EmployeeID', $headers);
        $this->assertContains('DateTime', $headers);
        $this->assertContains('LogType', $headers);
    }

    /**
     * Test setting expected headers
     */
    public function test_set_expected_headers(): void
    {
        $newHeaders = ['CustomEmployeeID', 'CustomDateTime', 'CustomLogType'];
        $this->importService->setExpectedHeaders($newHeaders);

        $this->assertEquals($newHeaders, $this->importService->getExpectedHeaders());
    }

    /**
     * Test getting valid log types
     */
    public function test_get_valid_log_types(): void
    {
        $logTypes = $this->importService->getValidLogTypes();

        $this->assertIsArray($logTypes);
        $this->assertContains('sign_in', $logTypes);
        $this->assertContains('sign_out', $logTypes);
    }

    /**
     * Test successful CSV import
     */
    public function test_successful_csv_import(): void
    {
        // Create test employee
        $employee = Employee::factory()->create(['id' => 123]);

        // Create temporary CSV file
        $csvContent = "EmployeeID,DateTime,LogType\n";
        $csvContent .= "123,2025-06-14 09:00:00,sign_in\n";
        $csvContent .= "123,2025-06-14 17:00:00,sign_out\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'attendance_test_');
        file_put_contents($tempFile, $csvContent);

        // Import CSV
        $results = $this->importService->importFromCsv($tempFile);

        // Assert results
        $this->assertEquals(2, $results['total_rows']);
        $this->assertEquals(2, $results['successful_imports']);
        $this->assertEmpty($results['failed_rows']);
        $this->assertEmpty($results['errors']);

        // Assert database records
        $this->assertDatabaseCount('attendance_logs', 2);
        $this->assertDatabaseHas('attendance_logs', [
            'employee_id' => 123,
            'type' => 'sign_in'
        ]);

        // Clean up
        unlink($tempFile);
    }

    /**
     * Test CSV import with validation errors
     */
    public function test_csv_import_with_validation_errors(): void
    {
        // Create test employee
        $employee = Employee::factory()->create(['id' => 123]);

        // Create CSV with mixed valid/invalid data
        $csvContent = "EmployeeID,DateTime,LogType\n";
        $csvContent .= "123,2025-06-14 09:00:00,sign_in\n";        // Valid
        $csvContent .= "999,2025-06-14 09:00:00,sign_in\n";        // Invalid employee
        $csvContent .= "123,invalid-date,sign_in\n";                // Invalid date
        $csvContent .= "123,2025-06-14 09:00:00,invalid_type\n";    // Invalid type

        $tempFile = tempnam(sys_get_temp_dir(), 'attendance_test_');
        file_put_contents($tempFile, $csvContent);

        // Import CSV
        $results = $this->importService->importFromCsv($tempFile);

        // Assert results
        $this->assertEquals(4, $results['total_rows']);
        $this->assertEquals(1, $results['successful_imports']);
        $this->assertCount(3, $results['failed_rows']);
        $this->assertEmpty($results['errors']);

        // Assert only valid record was created
        $this->assertDatabaseCount('attendance_logs', 1);

        // Clean up
        unlink($tempFile);
    }

    /**
     * Test CSV import with invalid headers
     */
    public function test_csv_import_with_invalid_headers(): void
    {
        // Create CSV with wrong headers
        $csvContent = "WrongHeader1,WrongHeader2,WrongHeader3\n";
        $csvContent .= "123,2025-06-14 09:00:00,sign_in\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'attendance_test_');
        file_put_contents($tempFile, $csvContent);

        // Import CSV
        $results = $this->importService->importFromCsv($tempFile);

        // Assert results
        $this->assertEquals(0, $results['total_rows']);
        $this->assertEquals(0, $results['successful_imports']);
        $this->assertEmpty($results['failed_rows']);
        $this->assertNotEmpty($results['errors']);
        $this->assertStringContainsString('Invalid CSV headers', $results['errors'][0]);

        // Assert no records created
        $this->assertDatabaseCount('attendance_logs', 0);

        // Clean up
        unlink($tempFile);
    }

    /**
     * Test CSV import with file not found
     */
    public function test_csv_import_file_not_found(): void
    {
        $results = $this->importService->importFromCsv('/path/to/nonexistent/file.csv');

        $this->assertEquals(0, $results['total_rows']);
        $this->assertEquals(0, $results['successful_imports']);
        $this->assertEmpty($results['failed_rows']);
        $this->assertNotEmpty($results['errors']);
        $this->assertStringContainsString('Import failed', $results['errors'][0]);
    }

    /**
     * Test header validation with configurable headers
     */
    public function test_header_validation_with_configurable_headers(): void
    {
        // Set custom headers
        $customHeaders = ['EmpID', 'Timestamp', 'Action'];
        $this->importService->setExpectedHeaders($customHeaders);

        // Create employee
        $employee = Employee::factory()->create(['id' => 456]);

        // Create CSV with custom headers
        $csvContent = "EmpID,Timestamp,Action\n";
        $csvContent .= "456,2025-06-14 10:00:00,sign_in\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'attendance_test_');
        file_put_contents($tempFile, $csvContent);

        // Import CSV (this should fail since the service logic uses hardcoded field names)
        $results = $this->importService->importFromCsv($tempFile);

        // This test demonstrates that custom headers are configurable but the parsing logic
        // still expects the original field names - this is a known design limitation
        $this->assertEquals(1, $results['total_rows']);
        $this->assertEquals(0, $results['successful_imports']);
        $this->assertCount(1, $results['failed_rows']);

        // Clean up
        unlink($tempFile);
    }
}
