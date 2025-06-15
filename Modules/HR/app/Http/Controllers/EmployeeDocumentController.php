<?php

namespace Modules\HR\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Modules\HR\Http\Requests\StoreDocumentRequest;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeDocument;

/**
 * Employee Document Controller
 *
 * Handles document upload, download, and deletion for employee profiles.
 *
 * @author Dev Agent
 */
class EmployeeDocumentController extends Controller
{
    /**
     * Store a newly uploaded document.
     */
    public function store(StoreDocumentRequest $request, Employee $employee): RedirectResponse
    {
        $file = $request->file('document_file');

        // Generate a unique filename to prevent conflicts
        $filename = time().'_'.$file->getClientOriginalName();

        // Store file in employee-specific directory
        $path = $file->storeAs(
            "documents/{$employee->id}",
            $filename,
            'public'
        );

        // Create database record
        $employee->documents()->create([
            'document_type' => $request->document_type,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'issue_date' => $request->issue_date,
            'expiry_date' => $request->expiry_date,
        ]);

        return redirect()
            ->route('hr.employees.show', $employee)
            ->with('success', "Document '{$request->document_type}' uploaded successfully.");
    }

    /**
     * Download a document.
     */
    public function download(Employee $employee, EmployeeDocument $document)
    {
        // Verify the document belongs to the employee
        if ($document->employee_id !== $employee->id) {
            abort(404);
        }

        // Check if file exists
        if (! Storage::disk('public')->exists($document->file_path)) {
            return redirect()
                ->route('hr.employees.show', $employee)
                ->with('error', 'Document file not found.');
        }

        return Storage::disk('public')->download(
            $document->file_path,
            $document->original_filename
        );
    }

    /**
     * Delete a document and its file.
     */
    public function destroy(Employee $employee, EmployeeDocument $document): RedirectResponse
    {
        // Verify the document belongs to the employee
        if ($document->employee_id !== $employee->id) {
            abort(404);
        }

        $documentType = $document->document_type;

        // Delete the document and its file
        $document->deleteWithFile();

        return redirect()
            ->route('hr.employees.show', $employee)
            ->with('success', "Document '{$documentType}' deleted successfully.");
    }
}
