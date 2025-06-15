<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Attendance\Services\AttendanceImportService;

/**
 * AttendanceImportController
 *
 * Handles CSV import of attendance logs
 */
class AttendanceImportController extends Controller
{
    protected AttendanceImportService $importService;

    public function __construct(AttendanceImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Show the form for importing attendance CSV
     */
    public function create(): View
    {
        return view('attendance::import.create', [
            'expectedHeaders' => $this->importService->getExpectedHeaders(),
            'validLogTypes' => $this->importService->getValidLogTypes()
        ]);
    }

    /**
     * Handle the CSV file upload and import
     */
    public function store(Request $request): RedirectResponse|View
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240' // 10MB max
        ]);

        $file = $request->file('csv_file');
        $filePath = $file->getPathname();

        // Import the CSV
        $results = $this->importService->importFromCsv($filePath);

        // Return to summary view with results
        return view('attendance::import.summary', [
            'results' => $results,
            'filename' => $file->getClientOriginalName()
        ]);
    }
}
