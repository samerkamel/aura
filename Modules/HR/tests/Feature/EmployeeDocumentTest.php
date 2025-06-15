<?php

namespace Modules\HR\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeDocument;
use Tests\TestCase;

/**
 * Employee Document Feature Test
 *
 * Tests the employee document management functionality including
 * upload, download, and deletion operations.
 *
 * @author Dev Agent
 */
class EmployeeDocumentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test constants
     */
    private const TEST_DOCUMENT_TYPE = 'Passport';

    private const TEST_FILENAME = 'test-document.pdf';

    private const TEST_EMPLOYEE_NAME = 'John Doe';

    private const TEST_EMPLOYEE_EMAIL = 'john.doe@example.com';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Test successful document upload with valid data.
     */
    public function test_can_upload_document_with_valid_data(): void
    {
        $employee = Employee::create([
            'name' => self::TEST_EMPLOYEE_NAME,
            'email' => self::TEST_EMPLOYEE_EMAIL,
            'base_salary' => 75000.00,
        ]);

        $file = UploadedFile::fake()->create(self::TEST_FILENAME, 1024, 'application/pdf');

        $documentData = [
            'document_type' => self::TEST_DOCUMENT_TYPE,
            'document_file' => $file,
            'issue_date' => '2024-01-15',
            'expiry_date' => '2029-01-15',
        ];

        $response = $this->post(route('hr.employees.documents.store', $employee), $documentData);

        $response->assertRedirect(route('hr.employees.show', $employee));
        $response->assertSessionHas('success', "Document 'Passport' uploaded successfully.");

        $this->assertDatabaseHas('employee_documents', [
            'employee_id' => $employee->id,
            'document_type' => self::TEST_DOCUMENT_TYPE,
            'original_filename' => self::TEST_FILENAME,
            'issue_date' => '2024-01-15',
            'expiry_date' => '2029-01-15',
        ]);

        // Verify file was stored
        $document = EmployeeDocument::where('employee_id', $employee->id)->first();
        Storage::disk('public')->assertExists($document->file_path);
    }

    /**
     * Test document upload fails with missing required fields.
     */
    public function test_document_upload_fails_with_missing_required_fields(): void
    {
        $employee = Employee::create([
            'name' => self::TEST_EMPLOYEE_NAME,
            'email' => self::TEST_EMPLOYEE_EMAIL,
            'base_salary' => 50000.00,
        ]);

        $response = $this->post(route('hr.employees.documents.store', $employee), []);

        $response->assertSessionHasErrors(['document_type', 'document_file']);
        $this->assertEquals(0, EmployeeDocument::count());
    }

    /**
     * Test document upload fails with invalid file type.
     */
    public function test_document_upload_fails_with_invalid_file_type(): void
    {
        $employee = Employee::create([
            'name' => self::TEST_EMPLOYEE_NAME,
            'email' => self::TEST_EMPLOYEE_EMAIL,
            'base_salary' => 50000.00,
        ]);

        $file = UploadedFile::fake()->create('test.exe', 1024, 'application/x-executable');

        $documentData = [
            'document_type' => self::TEST_DOCUMENT_TYPE,
            'document_file' => $file,
        ];

        $response = $this->post(route('hr.employees.documents.store', $employee), $documentData);

        $response->assertSessionHasErrors(['document_file']);
        $this->assertEquals(0, EmployeeDocument::count());
    }

    /**
     * Test document upload fails with file too large.
     */
    public function test_document_upload_fails_with_file_too_large(): void
    {
        $employee = Employee::create([
            'name' => self::TEST_EMPLOYEE_NAME,
            'email' => self::TEST_EMPLOYEE_EMAIL,
            'base_salary' => 50000.00,
        ]);

        // Create file larger than 5MB (5242880 bytes)
        $file = UploadedFile::fake()->create('large-file.pdf', 6000, 'application/pdf');

        $documentData = [
            'document_type' => self::TEST_DOCUMENT_TYPE,
            'document_file' => $file,
        ];

        $response = $this->post(route('hr.employees.documents.store', $employee), $documentData);

        $response->assertSessionHasErrors(['document_file']);
        $this->assertEquals(0, EmployeeDocument::count());
    }

    /**
     * Test document download functionality.
     */
    public function test_can_download_document(): void
    {
        $employee = Employee::create([
            'name' => self::TEST_EMPLOYEE_NAME,
            'email' => self::TEST_EMPLOYEE_EMAIL,
            'base_salary' => 50000.00,
        ]);

        $file = UploadedFile::fake()->create(self::TEST_FILENAME, 1024, 'application/pdf');
        $path = $file->storeAs("documents/{$employee->id}", self::TEST_FILENAME, 'public');

        $document = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => self::TEST_DOCUMENT_TYPE,
            'file_path' => $path,
            'original_filename' => self::TEST_FILENAME,
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        $response = $this->get(route('hr.employees.documents.download', [$employee, $document]));

        $response->assertStatus(200);
        $response->assertDownload(self::TEST_FILENAME);
    }

    /**
     * Test document deletion functionality.
     */
    public function test_can_delete_document(): void
    {
        $employee = Employee::create([
            'name' => self::TEST_EMPLOYEE_NAME,
            'email' => self::TEST_EMPLOYEE_EMAIL,
            'base_salary' => 50000.00,
        ]);

        $file = UploadedFile::fake()->create(self::TEST_FILENAME, 1024, 'application/pdf');
        $path = $file->storeAs("documents/{$employee->id}", self::TEST_FILENAME, 'public');

        $document = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => self::TEST_DOCUMENT_TYPE,
            'file_path' => $path,
            'original_filename' => self::TEST_FILENAME,
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        $response = $this->delete(route('hr.employees.documents.destroy', [$employee, $document]));

        $response->assertRedirect(route('hr.employees.show', $employee));
        $response->assertSessionHas('success', "Document 'Passport' deleted successfully.");

        $this->assertDatabaseMissing('employee_documents', [
            'id' => $document->id,
        ]);

        // Verify file was deleted
        Storage::disk('public')->assertMissing($path);
    }

    /**
     * Test document upload with only required fields.
     */
    public function test_can_upload_document_with_only_required_fields(): void
    {
        $employee = Employee::create([
            'name' => self::TEST_EMPLOYEE_NAME,
            'email' => self::TEST_EMPLOYEE_EMAIL,
            'base_salary' => 50000.00,
        ]);

        $file = UploadedFile::fake()->create('contract.pdf', 512, 'application/pdf');

        $documentData = [
            'document_type' => 'Employment Contract',
            'document_file' => $file,
        ];

        $response = $this->post(route('hr.employees.documents.store', $employee), $documentData);

        $response->assertRedirect(route('hr.employees.show', $employee));
        $response->assertSessionHas('success', "Document 'Employment Contract' uploaded successfully.");

        $this->assertDatabaseHas('employee_documents', [
            'employee_id' => $employee->id,
            'document_type' => 'Employment Contract',
            'issue_date' => null,
            'expiry_date' => null,
        ]);
    }

    /**
     * Test that documents show in employee profile.
     */
    public function test_documents_show_in_employee_profile(): void
    {
        $employee = Employee::create([
            'name' => self::TEST_EMPLOYEE_NAME,
            'email' => self::TEST_EMPLOYEE_EMAIL,
            'base_salary' => 50000.00,
        ]);

        EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => self::TEST_DOCUMENT_TYPE,
            'file_path' => 'documents/1/passport.pdf',
            'original_filename' => 'passport.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        $response = $this->get(route('hr.employees.show', $employee));

        $response->assertStatus(200);
        $response->assertSee(self::TEST_DOCUMENT_TYPE);
        $response->assertSee('Documents');
    }

    /**
     * Test validation for expiry date after issue date.
     */
    public function test_expiry_date_must_be_after_issue_date(): void
    {
        $employee = Employee::create([
            'name' => self::TEST_EMPLOYEE_NAME,
            'email' => self::TEST_EMPLOYEE_EMAIL,
            'base_salary' => 50000.00,
        ]);

        $file = UploadedFile::fake()->create(self::TEST_FILENAME, 1024, 'application/pdf');

        $documentData = [
            'document_type' => self::TEST_DOCUMENT_TYPE,
            'document_file' => $file,
            'issue_date' => '2024-12-31',
            'expiry_date' => '2024-01-01', // Before issue date
        ];

        $response = $this->post(route('hr.employees.documents.store', $employee), $documentData);

        $response->assertSessionHasErrors(['expiry_date']);
        $this->assertEquals(0, EmployeeDocument::count());
    }
}
