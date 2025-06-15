<?php

namespace Modules\LetterGenerator\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;
use Modules\HR\Models\Employee;
use Modules\LetterGenerator\Models\LetterTemplate;

/**
 * Document Generation Service
 *
 * Handles the core logic for generating personalized employee documents
 * from letter templates, including placeholder replacement and PDF generation.
 *
 * @author Dev Agent
 */
class DocumentGenerationService
{
    /**
     * Generate document content by replacing placeholders with employee data.
     *
     * @return string Generated content with placeholders replaced
     */
    public function generateDocument(Employee $employee, LetterTemplate $template): string
    {
        return PlaceholderService::replacePlaceholders($template->content, $employee);
    }

    /**
     * Generate PDF from content with language-specific configuration.
     *
     * @param  string  $content  HTML content to convert to PDF
     * @param  string  $language  Template language (en/ar)
     * @return DomPDF PDF instance ready for download
     */
    public function generatePdf(string $content, string $language): DomPDF
    {
        // Create the HTML structure with proper language attributes
        $html = $this->wrapContentInHtml($content, $language);

        // Configure PDF options for proper rendering
        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
                'defaultFont' => $language === 'ar' ? 'DejaVu Sans' : 'sans-serif',
                'fontHeightRatio' => 1.1,
                'dpi' => 150,
            ]);

        return $pdf;
    }

    /**
     * Wrap content in proper HTML structure with language support.
     *
     * @param  string  $content  Document content
     * @param  string  $language  Language code (en/ar)
     * @return string Complete HTML document
     */
    private function wrapContentInHtml(string $content, string $language): string
    {
        $direction = $language === 'ar' ? 'rtl' : 'ltr';
        $fontFamily = $language === 'ar' ? '"DejaVu Sans", sans-serif' : '"Helvetica", "Arial", sans-serif';

        return "<!DOCTYPE html>
<html lang=\"{$language}\" dir=\"{$direction}\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Employee Document</title>
    <style>
        body {
            font-family: {$fontFamily};
            font-size: 14px;
            line-height: 1.6;
            margin: 40px;
            color: #333;
            direction: {$direction};
        }

        h1, h2, h3, h4, h5, h6 {
            color: #2c3e50;
            margin-top: 24px;
            margin-bottom: 16px;
        }

        p {
            margin-bottom: 12px;
            text-align: justify;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 20px;
        }

        .content {
            margin: 20px 0;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }

        /* Arabic-specific styles */
        " . ($language === 'ar' ? '
        body {
            text-align: right;
        }

        p {
            text-align: right;
        }
        ' : '') . "

        /* Print-specific styles */
        @media print {
            body {
                margin: 20px;
            }

            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    <div class=\"content\">
        {$content}
    </div>
</body>
</html>";
    }

    /**
     * Validate that template and employee are compatible for document generation.
     *
     * @throws \InvalidArgumentException
     */
    public function validateDocumentGeneration(Employee $employee, LetterTemplate $template): void
    {
        if (! $employee->exists) {
            throw new \InvalidArgumentException('Employee must exist in database');
        }

        if (! $template->exists) {
            throw new \InvalidArgumentException('Template must exist in database');
        }

        if (empty($template->content)) {
            throw new \InvalidArgumentException('Template content cannot be empty');
        }
    }

    /**
     * Get preview data for template with employee information.
     *
     * @return array Preview data including generated content and metadata
     */
    public function getPreviewData(Employee $employee, LetterTemplate $template): array
    {
        $this->validateDocumentGeneration($employee, $template);

        $generatedContent = $this->generateDocument($employee, $template);

        return [
            'employee' => $employee,
            'template' => $template,
            'generated_content' => $generatedContent,
            'language' => $template->language,
            'direction' => $template->language === 'ar' ? 'rtl' : 'ltr',
            'placeholders_used' => $this->getUsedPlaceholders($template->content),
            'generation_date' => now(),
        ];
    }

    /**
     * Extract placeholders used in template content.
     *
     * @param  string  $content  Template content
     * @return array List of placeholders found in content
     */
    private function getUsedPlaceholders(string $content): array
    {
        preg_match_all('/\{\{([^}]+)\}\}/', $content, $matches);

        return array_unique($matches[0]);
    }
}
