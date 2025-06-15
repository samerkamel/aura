<?php

namespace Modules\Attendance\Services;

use League\Csv\Reader;
use League\Csv\Statement;
use Modules\Attendance\Models\AttendanceLog;
use Modules\HR\Models\Employee;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * AttendanceImportService
 *
 * Handles CSV parsing, validation, and import of attendance logs
 */
class AttendanceImportService
{
    /**
     * Expected CSV headers (configurable)
     */
    protected array $expectedHeaders = [
        'EmployeeID',
        'DateTime',
        'LogType'
    ];

    /**
     * Valid log types
     */
    protected array $validLogTypes = [
        'sign_in',
        'sign_out'
    ];

    /**
     * Import results
     */
    protected array $results = [
        'total_rows' => 0,
        'successful_imports' => 0,
        'failed_rows' => [],
        'errors' => []
    ];

    /**
     * Import attendance data from CSV file
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

            Log::info('Attendance CSV import completed', [
                'file' => $filePath,
                'total_rows' => $this->results['total_rows'],
                'successful' => $this->results['successful_imports'],
                'failed' => count($this->results['failed_rows'])
            ]);
        } catch (\Exception $e) {
            Log::error('Attendance CSV import failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);

            $this->results['errors'][] = 'Import failed: ' . $e->getMessage();
        }

        return $this->results;
    }

    /**
     * Validate CSV headers
     *
     * @param array $headers
     * @return bool
     */
    protected function validateHeaders(array $headers): bool
    {
        return count(array_intersect($this->expectedHeaders, $headers)) === count($this->expectedHeaders);
    }

    /**
     * Validate and import a single CSV row
     *
     * @param array $record
     * @return array
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

        // Validate DateTime format
        $dateTimeString = trim($record['DateTime']);
        try {
            $timestamp = Carbon::parse($dateTimeString);
        } catch (\Exception $e) {
            $errors[] = "Invalid DateTime format: {$dateTimeString}";
            $timestamp = null;
        }

        // Validate LogType
        $logType = strtolower(trim($record['LogType']));
        if (!in_array($logType, $this->validLogTypes)) {
            $errors[] = "Invalid LogType: {$record['LogType']}. Valid types: " . implode(', ', $this->validLogTypes);
        }

        // If validation passed, create the record
        if (empty($errors) && $timestamp) {
            try {
                AttendanceLog::create([
                    'employee_id' => $employeeId,
                    'timestamp' => $timestamp,
                    'type' => $logType
                ]);

                return ['success' => true, 'errors' => []];
            } catch (\Exception $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }

        return ['success' => false, 'errors' => $errors];
    }

    /**
     * Reset results for new import
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
     * Get expected headers (for configuration)
     *
     * @return array
     */
    public function getExpectedHeaders(): array
    {
        return $this->expectedHeaders;
    }

    /**
     * Set expected headers (for configuration)
     *
     * @param array $headers
     * @return void
     */
    public function setExpectedHeaders(array $headers): void
    {
        $this->expectedHeaders = $headers;
    }

    /**
     * Get valid log types
     *
     * @return array
     */
    public function getValidLogTypes(): array
    {
        return $this->validLogTypes;
    }
}
