<?php

namespace Modules\LetterGenerator\Tests\Unit;

use Modules\HR\Models\Employee;
use Modules\LetterGenerator\Models\LetterTemplate;
use Modules\LetterGenerator\Services\DocumentGenerationService;
use Tests\TestCase;

/**
 * Document Generation Service Unit Test
 *
 * Tests the core business logic of document generation service
 * including content generation, PDF creation, and validation.
 *
 * @author Dev Agent
 */
class DocumentGenerationServiceTest extends TestCase
{
    private DocumentGenerationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DocumentGenerationService();
    }

    /**
     * Test document content generation with placeholders.
     */
    public function test_generates_document_content_with_placeholders(): void
    {
        $employee = new Employee([
            'name' => 'John Smith',
            'position' => 'Developer',
            'base_salary' => 75000,
        ]);

        $template = new LetterTemplate([
            'content' => 'Hello {{employee_name}}, your position is {{employee_position}} with salary {{base_salary}}.',
            'language' => 'en',
        ]);

        $result = $this->service->generateDocument($employee, $template);

        $this->assertStringContainsString('John Smith', $result);
        $this->assertStringContainsString('Developer', $result);
        $this->assertStringContainsString('75,000.00', $result);
        $this->assertStringNotContainsString('{{employee_name}}', $result);
    }

    /**
     * Test PDF generation returns DomPDF instance.
     */
    public function test_generate_pdf_returns_dompdf_instance(): void
    {
        $content = '<h1>Test Document</h1><p>This is a test.</p>';
        $language = 'en';

        $pdf = $this->service->generatePdf($content, $language);

        $this->assertInstanceOf(\Barryvdh\DomPDF\PDF::class, $pdf);
    }

    /**
     * Test HTML wrapping for English content.
     */
    public function test_html_wrapping_for_english_content(): void
    {
        $content = '<p>Test content</p>';
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('wrapContentInHtml');
        $method->setAccessible(true);

        $html = $method->invoke($this->service, $content, 'en');

        $this->assertStringContainsString('lang="en"', $html);
        $this->assertStringContainsString('dir="ltr"', $html);
        $this->assertStringContainsString('Test content', $html);
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
    }

    /**
     * Test HTML wrapping for Arabic content.
     */
    public function test_html_wrapping_for_arabic_content(): void
    {
        $content = '<p>محتوى تجريبي</p>';
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('wrapContentInHtml');
        $method->setAccessible(true);

        $html = $method->invoke($this->service, $content, 'ar');

        $this->assertStringContainsString('lang="ar"', $html);
        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('DejaVu Sans', $html);
        $this->assertStringContainsString('محتوى تجريبي', $html);
    }

    /**
     * Test validation with valid employee and template.
     */
    public function test_validation_passes_with_valid_data(): void
    {
        $employee = new Employee(['name' => 'Test User']);
        $employee->exists = true;

        $template = new LetterTemplate(['content' => 'Test content']);
        $template->exists = true;

        $this->expectNotToPerformAssertions();
        $this->service->validateDocumentGeneration($employee, $template);
    }

    /**
     * Test validation fails with non-existent employee.
     */
    public function test_validation_fails_with_non_existent_employee(): void
    {
        $employee = new Employee(['name' => 'Test User']);
        $employee->exists = false;

        $template = new LetterTemplate(['content' => 'Test content']);
        $template->exists = true;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Employee must exist in database');

        $this->service->validateDocumentGeneration($employee, $template);
    }

    /**
     * Test validation fails with empty template content.
     */
    public function test_validation_fails_with_empty_template_content(): void
    {
        $employee = new Employee(['name' => 'Test User']);
        $employee->exists = true;

        $template = new LetterTemplate(['content' => '']);
        $template->exists = true;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template content cannot be empty');

        $this->service->validateDocumentGeneration($employee, $template);
    }

    /**
     * Test preview data generation.
     */
    public function test_get_preview_data_returns_complete_information(): void
    {
        $employee = new Employee([
            'name' => 'Jane Doe',
            'position' => 'Manager',
        ]);
        $employee->exists = true;

        $template = new LetterTemplate([
            'content' => 'Hello {{employee_name}}!',
            'language' => 'en',
        ]);
        $template->exists = true;

        $previewData = $this->service->getPreviewData($employee, $template);

        $this->assertArrayHasKey('employee', $previewData);
        $this->assertArrayHasKey('template', $previewData);
        $this->assertArrayHasKey('generated_content', $previewData);
        $this->assertArrayHasKey('language', $previewData);
        $this->assertArrayHasKey('direction', $previewData);
        $this->assertArrayHasKey('placeholders_used', $previewData);
        $this->assertArrayHasKey('generation_date', $previewData);

        $this->assertEquals('en', $previewData['language']);
        $this->assertEquals('ltr', $previewData['direction']);
        $this->assertStringContainsString('Jane Doe', $previewData['generated_content']);
    }

    /**
     * Test placeholder extraction from content.
     */
    public function test_extracts_placeholders_from_content(): void
    {
        $content = 'Hello {{employee_name}}, your {{employee_position}} role starts on {{start_date}}.';

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getUsedPlaceholders');
        $method->setAccessible(true);

        $placeholders = $method->invoke($this->service, $content);

        $this->assertCount(3, $placeholders);
        $this->assertContains('{{employee_name}}', $placeholders);
        $this->assertContains('{{employee_position}}', $placeholders);
        $this->assertContains('{{start_date}}', $placeholders);
    }
}
