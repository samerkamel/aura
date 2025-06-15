<?php

namespace Modules\LetterGenerator\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\HR\Models\Employee;
use Modules\LetterGenerator\Models\LetterTemplate;
use App\Models\User;
use Tests\TestCase;

/**
 * Document Generation Feature Test
 *
 * Tests the document generation functionality including template selection,
 * preview generation, and PDF download with both English and Arabic support.
 *
 * @author Dev Agent
 */
class DocumentGenerationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    /**
     * Test data constants
     */
    private const TEST_EMPLOYEE_NAME = 'Ahmed Hassan';

    private const TEST_EMPLOYEE_EMAIL = 'ahmed.hassan@company.com';

    private const TEST_POSITION = 'Software Engineer';

    private const TEST_SALARY = 85000.00;

    private const TEST_TEMPLATE_EN = 'Employment Contract';

    private const TEST_TEMPLATE_AR = 'عقد العمل';

    private const TEST_CONTENT_EN = '<h1>Employment Contract</h1><p>Dear {{employee_name}},</p><p>We are pleased to offer you the position of {{employee_position}} with a salary of ${{base_salary}}.</p><p>Start Date: {{start_date}}</p>';

    private const TEST_CONTENT_AR = '<h1 dir="rtl">عقد العمل</h1><p dir="rtl">عزيزي {{employee_name}}،</p><p dir="rtl">يسعدنا أن نقدم لك منصب {{employee_position}} براتب {{base_salary}}.</p>';

    private const CONTENT_TYPE_PDF = 'application/pdf';

    private Employee $testEmployee;

    private LetterTemplate $englishTemplate;

    private LetterTemplate $arabicTemplate;

    /**
     * Set up test data before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user for authentication
        $this->user = User::factory()->create([
            'email' => 'test@qflow.com',
            'name' => 'Test User'
        ]);

        // Create test employee
        $this->testEmployee = Employee::create([
            'name' => self::TEST_EMPLOYEE_NAME,
            'email' => self::TEST_EMPLOYEE_EMAIL,
            'position' => self::TEST_POSITION,
            'start_date' => '2024-01-15',
            'base_salary' => self::TEST_SALARY,
            'status' => 'active',
        ]);

        // Create English template
        $this->englishTemplate = LetterTemplate::create([
            'name' => self::TEST_TEMPLATE_EN,
            'language' => 'en',
            'content' => self::TEST_CONTENT_EN,
        ]);

        // Create Arabic template
        $this->arabicTemplate = LetterTemplate::create([
            'name' => self::TEST_TEMPLATE_AR,
            'language' => 'ar',
            'content' => self::TEST_CONTENT_AR,
        ]);
    }

    /**
     * Test that template selection page can be accessed.
     */
    public function test_can_access_template_selection_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('documents.select-template', $this->testEmployee));

        $response->assertStatus(200);
        $response->assertViewIs('lettergenerator::documents.select-template');
        $response->assertViewHas('employee', $this->testEmployee);
        $response->assertViewHas('templates');
    }

    /**
     * Test that template selection page displays available templates.
     */
    public function test_template_selection_displays_templates(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('documents.select-template', $this->testEmployee));

        $response->assertSee($this->englishTemplate->name);
        $response->assertSee($this->arabicTemplate->name);
        $response->assertSee('English');
        $response->assertSee('Arabic');
    }

    /**
     * Test document preview with English template.
     */
    public function test_can_preview_english_document(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('documents.preview', $this->testEmployee), [
                'template_id' => $this->englishTemplate->id,
            ]);

        $response->assertStatus(200);
        $response->assertViewIs('lettergenerator::documents.preview');
        $response->assertViewHas('employee', $this->testEmployee);
        $response->assertViewHas('template', $this->englishTemplate);
        $response->assertViewHas('generatedContent');

        // Check that placeholders are replaced
        $response->assertSee(self::TEST_EMPLOYEE_NAME);
        $response->assertSee(self::TEST_POSITION);
        $response->assertSee('85,000.00'); // Formatted salary
    }

    /**
     * Test document preview with Arabic template.
     */
    public function test_can_preview_arabic_document(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('documents.preview', $this->testEmployee), [
                'template_id' => $this->arabicTemplate->id,
            ]);

        $response->assertStatus(200);
        $response->assertViewIs('lettergenerator::documents.preview');
        $response->assertSee('dir="rtl"', false); // Check RTL attribute
        $response->assertSee(self::TEST_EMPLOYEE_NAME);
    }

    /**
     * Test document preview validation.
     */
    public function test_document_preview_requires_template(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('documents.preview', $this->testEmployee), []);

        $response->assertSessionHasErrors(['template_id']);
    }

    /**
     * Test document preview with invalid template.
     */
    public function test_document_preview_fails_with_invalid_template(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('documents.preview', $this->testEmployee), [
                'template_id' => 99999,
            ]);

        $response->assertSessionHasErrors(['template_id']);
    }

    /**
     * Test PDF download with English template.
     */
    public function test_can_download_english_pdf(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('documents.download', $this->testEmployee), [
                'template_id' => $this->englishTemplate->id,
            ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', self::CONTENT_TYPE_PDF);

        // Check filename format
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('Employment_Contract', $contentDisposition);
        $this->assertStringContainsString('Ahmed_Hassan', $contentDisposition);
        $this->assertStringContainsString('.pdf', $contentDisposition);
    }

    /**
     * Test PDF download with Arabic template.
     */
    public function test_can_download_arabic_pdf(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('documents.download', $this->testEmployee), [
                'template_id' => $this->arabicTemplate->id,
            ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', self::CONTENT_TYPE_PDF);

        // Verify that PDF content is generated (content length > 0)
        $this->assertGreaterThan(0, strlen($response->getContent()));
    }

    /**
     * Test PDF download validation.
     */
    public function test_pdf_download_requires_template(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('documents.download', $this->testEmployee), []);

        $response->assertSessionHasErrors(['template_id']);
    }

    /**
     * Test complete document generation workflow.
     */
    public function test_complete_document_generation_workflow(): void
    {
        // Step 1: Access template selection
        $response = $this->actingAs($this->user)
            ->get(route('documents.select-template', $this->testEmployee));
        $response->assertStatus(200);

        // Step 2: Preview document
        $response = $this->actingAs($this->user)
            ->post(route('documents.preview', $this->testEmployee), [
                'template_id' => $this->englishTemplate->id,
            ]);
        $response->assertStatus(200);
        $response->assertSee(self::TEST_EMPLOYEE_NAME);

        // Step 3: Download PDF
        $response = $this->actingAs($this->user)
            ->post(route('documents.download', $this->testEmployee), [
                'template_id' => $this->englishTemplate->id,
            ]);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', self::CONTENT_TYPE_PDF);
    }

    /**
     * Test that placeholders are correctly replaced in generated content.
     */
    public function test_placeholders_are_correctly_replaced(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('documents.preview', $this->testEmployee), [
                'template_id' => $this->englishTemplate->id,
            ]);

        $generatedContent = $response->viewData('generatedContent');

        // Verify specific placeholder replacements
        $this->assertStringContainsString(self::TEST_EMPLOYEE_NAME, $generatedContent);
        $this->assertStringContainsString(self::TEST_POSITION, $generatedContent);
        $this->assertStringContainsString('85,000.00', $generatedContent);
        $this->assertStringContainsString('2024-01-15', $generatedContent);

        // Verify placeholders are replaced (should not contain template syntax)
        $this->assertStringNotContainsString('{{employee_name}}', $generatedContent);
        $this->assertStringNotContainsString('{{employee_position}}', $generatedContent);
        $this->assertStringNotContainsString('{{base_salary}}', $generatedContent);
    }

    /**
     * Test Arabic template rendering and encoding.
     */
    public function test_arabic_template_rendering_and_encoding(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('documents.preview', $this->testEmployee), [
                'template_id' => $this->arabicTemplate->id,
            ]);

        $response->assertStatus(200);

        // Check for RTL direction attribute
        $response->assertSee('dir="rtl"', false);

        // Verify Arabic content is present
        $generatedContent = $response->viewData('generatedContent');
        $this->assertStringContainsString('عقد العمل', $generatedContent);
        $this->assertStringContainsString('عزيزي', $generatedContent);

        // Verify employee data is properly inserted
        $this->assertStringContainsString(self::TEST_EMPLOYEE_NAME, $generatedContent);
    }

    /**
     * Test document generation with employee having minimal data.
     */
    public function test_document_generation_with_minimal_employee_data(): void
    {
        $minimalEmployee = Employee::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'status' => 'active',
            // Missing position, start_date, base_salary
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('documents.preview', $minimalEmployee), [
                'template_id' => $this->englishTemplate->id,
            ]);

        $response->assertStatus(200);

        $generatedContent = $response->viewData('generatedContent');
        $this->assertStringContainsString('Jane Doe', $generatedContent);

        // Should handle missing data gracefully
        $this->assertStringNotContainsString('{{', $generatedContent);
    }
}
