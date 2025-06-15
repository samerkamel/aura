<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Payroll\Models\BillableHour;
use Modules\Payroll\Services\BillableHoursImportService;
use Modules\HR\Models\Employee;
use Carbon\Carbon;

/**
 * BillableHoursController
 *
 * Handles management of employee billable hours including manual entry
 * and CSV import functionality.
 *
 * @author Dev Agent
 */
class BillableHoursController extends Controller
{
    protected BillableHoursImportService $importService;

    public function __construct(BillableHoursImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Display the billable hours management page.
     */
    public function index(): View
    {
        $currentPeriod = Carbon::now()->startOfMonth();

        // Get all active employees with their billable hours for current period
        $employees = Employee::active()
            ->with(['billableHours' => function ($query) use ($currentPeriod) {
                $query->forPeriod($currentPeriod);
            }])
            ->orderBy('name')
            ->get();

        // Add current hours or default to 0
        $employees->transform(function ($employee) {
            $employee->current_billable_hours = $employee->billableHours->first()?->hours ?? 0;
            return $employee;
        });

        return view('payroll::billable-hours.index', [
            'employees' => $employees,
            'currentPeriod' => $currentPeriod,
            'expectedHeaders' => $this->importService->getExpectedHeaders(),
        ]);
    }

    /**
     * Store billable hours (both manual entry and CSV import).
     */
    public function store(Request $request): RedirectResponse|View
    {
        // Handle CSV import
        if ($request->hasFile('csv_file')) {
            return $this->handleCsvImport($request);
        }

        // Handle manual entry
        return $this->handleManualEntry($request);
    }

    /**
     * Handle manual entry of billable hours.
     */
    protected function handleManualEntry(Request $request): RedirectResponse
    {
        $request->validate([
            'hours' => 'required|array',
            'hours.*' => 'numeric|min:0|max:999.99',
        ]);

        $currentPeriod = Carbon::now()->startOfMonth();
        $updateCount = 0;

        foreach ($request->hours as $employeeId => $hours) {
            if ($hours !== null && $hours !== '') {
                BillableHour::updateOrCreate(
                    [
                        'employee_id' => $employeeId,
                        'payroll_period_start_date' => $currentPeriod,
                    ],
                    [
                        'hours' => $hours,
                    ]
                );
                $updateCount++;
            }
        }

        return redirect()
            ->route('payroll.billable-hours.index')
            ->with('success', "Successfully updated billable hours for {$updateCount} employees.");
    }

    /**
     * Handle CSV import of billable hours.
     */
    protected function handleCsvImport(Request $request): View
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240' // 10MB max
        ]);

        $file = $request->file('csv_file');
        $filePath = $file->getPathname();

        // Import the CSV
        $results = $this->importService->importFromCsv($filePath);

        // Return to summary view with results
        return view('payroll::billable-hours.import-summary', [
            'results' => $results,
            'filename' => $file->getClientOriginalName()
        ]);
    }
}
