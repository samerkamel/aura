<?php

namespace Modules\Payroll\Services;

use League\Csv\Reader;
use Modules\Payroll\Models\BillableHour;
use Modules\HR\Models\Employee;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * BillableHoursImportService
 *
 * Handles CSV parsing, validation, and import of billable hours data.
 * Follows the same pattern as AttendanceImportService from Story 3.1.
 *
 * @author Dev Agent
 */
class BillableHoursImportService
{
    /**
     * Expected CSV headers (configurable).
     */
    protected array $expectedHeaders = [
        'EmployeeID',
        'BillableHours'
    ];

    /**
     * Import results.
     */
    protected array $results = [
        'total_rows' => 0,
        'successful_imports' => 0,
        'failed_rows' => [],
        'errors' => []
    ];

    /**
     * Get expected CSV headers.
     */
    public function getExpectedHeaders(): array
    {
        return $this->expectedHeaders;
    }

    /**
     * Set expected CSV headers.
     */
    public function setExpectedHeaders(array $headers): void
    {
        $this->expectedHeaders = $headers;
    }

    /**
     * Reset results for new import.
     */
    protected function resetResults(): void
    {
        $this->results = [
            'total_rows' => 0,
            'successful_imports' => 0,
            'failed_rows' => [],
            'errors' => []
        ];
    }

    /**
     * Import billable hours from CSV file.
     *
     * @param string $filePath Path to the CSV file
     * @return array Import results summary
     */
    public function importFromCsv(string $filePath): array
    {
        try {
            // Reset results for new import
            $this->resetResults();

            // Create CSV reader
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);

            // Validate headers
            $headers = $csv->getHeader();
            if (!$this->validateHeaders($headers)) {
                $this->results['errors'][] = 'Invalid CSV headers. Expected: ' . implode(', ', $this->expectedHeaders);
                return $this->results;
            }

            // Process CSV records
            $records = $csv->getRecords();
            $rowNumber = 1; // Start from 1 (header is row 0)

            foreach ($records as $record) {
                $rowNumber++;
                $this->results['total_rows']++;

                $validationResult = $this->validateAndImportRow($record);

                if ($validationResult['success']) {
                    $this->results['successful_imports']++;
                } else {
                    $this->results['failed_rows'][] = [
                        'row_number' => $rowNumber,
                        'data' => $record,
                        'errors' => $validationResult['errors']
                    ];
                }
            }

            Log::info('Billable hours CSV import completed', [
                'file' => $filePath,
                'total_rows' => $this->results['total_rows'],
                'successful' => $this->results['successful_imports'],
                'failed' => count($this->results['failed_rows'])
            ]);
        } catch (\Exception $e) {
            Log::error('Billable hours CSV import failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);

            $this->results['errors'][] = 'Import failed: ' . $e->getMessage();
        }

        return $this->results;
    }

    /**
     * Validate CSV headers.
     */
    protected function validateHeaders(array $headers): bool
    {
        return count(array_intersect($this->expectedHeaders, $headers)) === count($this->expectedHeaders);
    }

    /**
     * Validate and import a single CSV row.
     */
    protected function validateAndImportRow(array $record): array
    {
        $errors = [];

        // Validate required fields exist
        foreach ($this->expectedHeaders as $header) {
            if (!isset($record[$header]) || trim($record[$header]) === '') {
                $errors[] = "Missing or empty field: {$header}";
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Validate employee ID exists
        $employeeId = trim($record['EmployeeID']);
        if (!is_numeric($employeeId)) {
            $errors[] = "Invalid EmployeeID format: {$employeeId}";
        } else {
            $employee = Employee::find($employeeId);
            if (!$employee) {
                $errors[] = "Employee not found with ID: {$employeeId}";
            }
        }

        // Validate billable hours
        $billableHours = trim($record['BillableHours']);
        if (!is_numeric($billableHours)) {
            $errors[] = "Invalid BillableHours format: {$billableHours}";
        } elseif ($billableHours < 0 || $billableHours > 999.99) {
            $errors[] = "BillableHours must be between 0 and 999.99: {$billableHours}";
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Create or update billable hours record
        try {
            $currentPeriod = Carbon::now()->startOfMonth();

            BillableHour::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'payroll_period_start_date' => $currentPeriod,
                ],
                [
                    'hours' => $billableHours,
                ]
            );

            return ['success' => true, 'errors' => []];
        } catch (\Exception $e) {
            return ['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]];
        }
    }
}
