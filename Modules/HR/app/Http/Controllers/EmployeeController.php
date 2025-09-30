<?php

namespace Modules\HR\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HR\Http\Requests\StoreEmployeeRequest;
use Modules\HR\Http\Requests\UpdateEmployeeRequest;
use Modules\HR\Models\Employee;
use Modules\AssetManager\Models\Asset;
use Modules\Payroll\Services\FinalPayrollService;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

/**
 * Employee Controller
 *
 * Handles CRUD operations for employee management including off-boarding workflows.
 *
 * @author Dev Agent
 */
class EmployeeController extends Controller
{
    /**
     * Display a listing of the employees.
     */
    public function index(): View
    {
        $employees = Employee::active()->latest()->get();

        return view('hr::employees.index', compact('employees'));
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create(): View
    {
        return view('hr::employees.create');
    }

    /**
     * Store a newly created employee in storage.
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = Employee::create($request->validated());

        return redirect()
            ->route('hr.employees.index')
            ->with('success', "Employee {$employee->name} created successfully.");
    }

    /**
     * Display the specified employee.
     */
    public function show(Employee $employee): View
    {
        $employee->load(['documents', 'salaryHistories']);

        return view('hr::employees.show', compact('employee'));
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit(Employee $employee): View
    {
        return view('hr::employees.edit', compact('employee'));
    }

    /**
     * Update the specified employee in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $validatedData = $request->validated();

        // If salary is being changed and a reason is provided, temporarily set it on the model
        // The observer will pick this up and include it in the salary history record
        if (isset($validatedData['salary_change_reason'])) {
            $employee->salary_change_reason = $validatedData['salary_change_reason'];
            // Don't include it in the update data as it's not a database field
            unset($validatedData['salary_change_reason']);
        }

        $employee->update($validatedData);

        return redirect()
            ->route('hr.employees.index')
            ->with('success', "Employee {$employee->name} updated successfully.");
    }

    /**
     * Remove the specified employee from storage.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return redirect()
            ->route('hr.employees.index')
            ->with('success', "Employee {$employee->name} deleted successfully.");
    }

    /**
     * Show the off-boarding page for the specified employee.
     */
    public function showOffboarding(Employee $employee): View
    {
        // Load employee's current assets
        $employee->load(['currentAssets']);

        // Get final payroll calculation
        $finalPayrollService = new FinalPayrollService();
        $payrollCalculation = $finalPayrollService->calculateFinalPay($employee, Carbon::now());

        return view('hr::employees.offboarding', compact('employee', 'payrollCalculation'));
    }

    /**
     * Process the off-boarding for the specified employee.
     */
    public function processOffboarding(Request $request, Employee $employee): RedirectResponse
    {
        $request->validate([
            'termination_date' => 'required|date|before_or_equal:today',
            'status' => 'required|in:terminated,resigned',
            'notes' => 'nullable|string|max:1000',
        ]);

        $terminationDate = Carbon::parse($request->termination_date);

        // Calculate final payroll
        $finalPayrollService = new FinalPayrollService();
        $payrollCalculation = $finalPayrollService->calculateFinalPay($employee, $terminationDate);

        // Update employee status and termination date
        $employee->update([
            'status' => $request->status,
            'termination_date' => $terminationDate,
        ]);

        // Mark all current assets as returned
        $currentAssets = $employee->currentAssets;
        foreach ($currentAssets as $asset) {
            $employee->assets()->updateExistingPivot($asset->id, [
                'returned_date' => $terminationDate,
                'notes' => $request->notes ?? 'Returned during off-boarding process',
            ]);

            // Update asset status to available
            $asset->update(['status' => 'available']);
        }

        // Store final payroll calculation
        $finalPayrollService->storeFinalPayrollCalculation($payrollCalculation);

        return redirect()
            ->route('hr.employees.show', $employee)
            ->with('success', "Employee {$employee->name} has been successfully processed for {$request->status}. Final pay calculation: $" . number_format($payrollCalculation['pro_rated_amount'], 2));
    }

    /**
     * Show the import form for employees.
     */
    public function showImport(): View
    {
        return view('hr::employees.import');
    }

    /**
     * Process the employee import from CSV/Excel.
     */
    public function processImport(Request $request): RedirectResponse
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,xlsx,xls|max:10240', // 10MB max
        ]);

        $file = $request->file('import_file');
        $extension = $file->getClientOriginalExtension();

        try {
            if ($extension === 'csv') {
                $results = $this->importFromCsv($file);
            } else {
                $results = $this->importFromExcel($file);
            }

            $message = "Import completed successfully! ";
            $message .= "Created: {$results['created']} employees, ";
            $message .= "Updated: {$results['updated']} employees";

            if ($results['errors'] > 0) {
                $message .= ", Errors: {$results['errors']} rows";
            }

            return redirect()
                ->route('hr.employees.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Import employees from CSV file.
     */
    private function importFromCsv($file): array
    {
        $results = ['created' => 0, 'updated' => 0, 'errors' => 0];

        if (($handle = fopen($file->getPathname(), "r")) !== FALSE) {
            $header = fgetcsv($handle);
            $header = array_map('trim', $header);

            // Expected CSV columns
            $expectedColumns = ['name', 'email', 'position', 'start_date', 'base_salary', 'phone', 'address'];

            while (($row = fgetcsv($handle)) !== FALSE) {
                if (empty(array_filter($row))) continue; // Skip empty rows

                try {
                    $data = array_combine($header, $row);
                    $this->processEmployeeRow($data, $results);
                } catch (\Exception $e) {
                    $results['errors']++;
                    Log::error('Employee import row error: ' . $e->getMessage());
                }
            }
            fclose($handle);
        }

        return $results;
    }

    /**
     * Import employees from Excel file.
     */
    private function importFromExcel($file): array
    {
        $results = ['created' => 0, 'updated' => 0, 'errors' => 0];

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (empty($rows)) {
                throw new \Exception('Excel file is empty');
            }

            $header = array_map('trim', $rows[0]);

            for ($i = 1; $i < count($rows); $i++) {
                if (empty(array_filter($rows[$i]))) continue; // Skip empty rows

                try {
                    $data = array_combine($header, $rows[$i]);
                    $this->processEmployeeRow($data, $results);
                } catch (\Exception $e) {
                    $results['errors']++;
                    Log::error('Employee import row error: ' . $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            throw new \Exception('Failed to read Excel file: ' . $e->getMessage());
        }

        return $results;
    }

    /**
     * Process a single employee row from import.
     */
    private function processEmployeeRow(array $data, array &$results): void
    {
        // Clean and validate data
        $cleanData = [
            'name' => trim($data['name'] ?? ''),
            'email' => trim(strtolower($data['email'] ?? '')),
            'position' => trim($data['position'] ?? ''),
            'start_date' => $this->parseDate($data['start_date'] ?? ''),
            'base_salary' => $this->parseDecimal($data['base_salary'] ?? 0),
            'contact_info' => [
                'phone' => trim($data['phone'] ?? ''),
                'address' => trim($data['address'] ?? ''),
            ],
            'status' => 'active',
        ];

        // Validate required fields
        if (empty($cleanData['name']) || empty($cleanData['email'])) {
            throw new \Exception('Name and email are required');
        }

        // Check if employee exists (by email)
        $employee = Employee::where('email', $cleanData['email'])->first();

        if ($employee) {
            // Update existing employee
            $employee->update($cleanData);
            $results['updated']++;
        } else {
            // Create new employee
            Employee::create($cleanData);
            $results['created']++;
        }
    }

    /**
     * Parse date from various formats.
     */
    private function parseDate($dateString): ?string
    {
        if (empty($dateString)) return null;

        try {
            // Try common date formats
            $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y'];

            foreach ($formats as $format) {
                $date = \DateTime::createFromFormat($format, $dateString);
                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            }

            // Try Carbon parsing as fallback
            return Carbon::parse($dateString)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse decimal from string.
     */
    private function parseDecimal($value): float
    {
        if (empty($value)) return 0.0;

        // Remove currency symbols and spaces
        $cleaned = preg_replace('/[^\d.,]/', '', $value);
        $cleaned = str_replace(',', '', $cleaned);

        return (float) $cleaned;
    }
}
