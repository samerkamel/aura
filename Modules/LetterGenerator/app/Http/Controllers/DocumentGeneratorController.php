<?php

namespace Modules\LetterGenerator\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\HR\Models\Employee;
use Modules\LetterGenerator\Models\LetterTemplate;
use Modules\LetterGenerator\Services\DocumentGenerationService;

/**
 * Document Generator Controller
 *
 * Handles the generation of personalized employee documents from letter templates.
 * Provides functionality to select templates, preview generated content, and download PDFs.
 *
 * @author Dev Agent
 */
class DocumentGeneratorController extends Controller
{
    protected DocumentGenerationService $documentService;

    public function __construct(DocumentGenerationService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Show template selection page for an employee.
     */
    public function selectTemplate(Employee $employee): View
    {
        $templates = LetterTemplate::orderBy('name')->get();

        return view('lettergenerator::documents.select-template', compact('employee', 'templates'));
    }

    /**
     * Generate and preview document with selected template.
     */
    public function preview(Request $request, Employee $employee): View
    {
        $request->validate([
            'template_id' => 'required|exists:letter_templates,id',
        ]);

        $template = LetterTemplate::findOrFail($request->template_id);
        $generatedContent = $this->documentService->generateDocument($employee, $template);

        return view('lettergenerator::documents.preview', compact(
            'employee',
            'template',
            'generatedContent'
        ));
    }

    /**
     * Generate and download PDF document.
     */
    public function download(Request $request, Employee $employee): Response
    {
        $request->validate([
            'template_id' => 'required|exists:letter_templates,id',
        ]);

        $template = LetterTemplate::findOrFail($request->template_id);

        // Generate the document content
        $generatedContent = $this->documentService->generateDocument($employee, $template);

        // Create PDF with proper configuration for Arabic support
        $pdf = $this->documentService->generatePdf($generatedContent, $template->language);

        // Generate filename
        $filename = $this->generateFilename($employee, $template);

        return $pdf->download($filename);
    }

    /**
     * Generate appropriate filename for the document.
     */
    private function generateFilename(Employee $employee, LetterTemplate $template): string
    {
        $employeeName = str_replace(' ', '_', $employee->name);
        $templateName = str_replace(' ', '_', $template->name);
        $date = now()->format('Y-m-d');

        return "{$templateName}_{$employeeName}_{$date}.pdf";
    }
}
