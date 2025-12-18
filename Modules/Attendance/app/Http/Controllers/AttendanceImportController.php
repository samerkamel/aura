<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Attendance\Services\AttendanceImportService;
use Modules\Attendance\Services\ZktecoImportService;

/**
 * AttendanceImportController
 *
 * Handles CSV and ZKTeco DAT file import of attendance logs
 */
class AttendanceImportController extends Controller
{
    protected AttendanceImportService $importService;
    protected ZktecoImportService $zktecoService;

    public function __construct(
        AttendanceImportService $importService,
        ZktecoImportService $zktecoService
    ) {
        $this->importService = $importService;
        $this->zktecoService = $zktecoService;
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

    /**
     * Show the form for importing ZKTeco fingerprint data
     */
    public function zktecoCreate(): View
    {
        return view('attendance::import.zkteco');
    }

    /**
     * Handle the ZKTeco DAT file upload and preview
     */
    public function zktecoPreview(Request $request): View
    {
        $request->validate([
            'dat_file' => 'required|file|max:51200' // 50MB max for large attendance files
        ]);

        $file = $request->file('dat_file');
        $filePath = $file->getPathname();

        // Store file temporarily for import
        $tempPath = storage_path('app/temp/' . uniqid('zkteco_') . '.dat');
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        copy($filePath, $tempPath);

        // Get preview data
        $preview = $this->zktecoService->previewDatFile($tempPath);

        return view('attendance::import.zkteco-preview', [
            'preview' => $preview,
            'filename' => $file->getClientOriginalName(),
            'tempPath' => $tempPath,
        ]);
    }

    /**
     * Process the ZKTeco import after preview confirmation
     */
    public function zktecoStore(Request $request): View
    {
        $request->validate([
            'temp_path' => 'required|string',
            'filename' => 'required|string',
        ]);

        $tempPath = $request->input('temp_path');
        $filename = $request->input('filename');

        if (!file_exists($tempPath)) {
            return view('attendance::import.zkteco', [
                'error' => 'The uploaded file has expired. Please upload again.',
            ]);
        }

        // Import the DAT file
        $results = $this->zktecoService->importFromDatFile($tempPath);

        // Clean up temp file
        @unlink($tempPath);

        // Return to summary view with results
        return view('attendance::import.zkteco-summary', [
            'results' => $results,
            'filename' => $filename,
        ]);
    }
}
