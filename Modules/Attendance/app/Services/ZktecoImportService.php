<?php

namespace Modules\Attendance\Services;

use Modules\Attendance\Models\AttendanceLog;
use Modules\HR\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ZktecoImportService
{
    /**
     * Work day cutoff hour (4 AM)
     * Punches before this hour belong to the previous work day
     */
    protected const WORK_DAY_CUTOFF_HOUR = 4;

    /**
     * Import attendance records from a ZKTeco .dat file
     *
     * Business Logic:
     * - Device status codes are ignored
     * - For each work day per employee: first punch = sign_in, last punch = sign_out
     * - Work day extends until 4 AM (late night work counts as same day)
     * - Records older than the latest existing record are skipped for performance
     *
     * @param string $filePath Path to the .dat file
     * @return array Import results with statistics
     */
    public function importFromDatFile(string $filePath): array
    {
        $results = [
            'total_records' => 0,
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'skipped_old' => 0,
            'errors' => [],
            'unmapped_users' => [],
            'duplicates' => 0,
            'work_days_processed' => 0,
            'latest_existing_record' => null,
        ];

        if (!file_exists($filePath)) {
            $results['errors'][] = "File not found: {$filePath}";
            return $results;
        }

        // Build a lookup map of attendance_id => employee_id
        $employeeMap = $this->buildEmployeeMap();

        // Get the latest attendance record timestamp to skip old records
        $latestRecord = AttendanceLog::orderBy('timestamp', 'desc')->first();
        $latestTimestamp = $latestRecord ? Carbon::parse($latestRecord->timestamp) : null;
        $results['latest_existing_record'] = $latestTimestamp?->format('Y-m-d H:i:s');

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $results['errors'][] = "Unable to open file: {$filePath}";
            return $results;
        }

        // First pass: collect all punches grouped by employee and work day
        $punchesByEmployeeDay = [];
        $lineNumber = 0;

        while (($line = fgets($handle)) !== false) {
            $lineNumber++;
            $results['total_records']++;

            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $parsed = $this->parseLine($line, $lineNumber);

            if ($parsed === null) {
                $results['skipped']++;
                continue;
            }

            // Skip records older than or equal to latest existing record
            if ($latestTimestamp && $parsed['datetime']->lte($latestTimestamp)) {
                $results['skipped_old']++;
                continue;
            }

            // Check if we have an employee mapping for this attendance_id
            $attendanceId = $parsed['user_id'];
            if (!isset($employeeMap[$attendanceId])) {
                if (!in_array($attendanceId, $results['unmapped_users'])) {
                    $results['unmapped_users'][] = $attendanceId;
                }
                $results['skipped']++;
                continue;
            }

            $employeeId = $employeeMap[$attendanceId];
            $workDay = $this->getWorkDay($parsed['datetime']);

            // Group punches by employee and work day
            $key = "{$employeeId}_{$workDay}";
            if (!isset($punchesByEmployeeDay[$key])) {
                $punchesByEmployeeDay[$key] = [
                    'employee_id' => $employeeId,
                    'work_day' => $workDay,
                    'punches' => [],
                ];
            }
            $punchesByEmployeeDay[$key]['punches'][] = $parsed['datetime'];
        }

        fclose($handle);

        // Second pass: process each work day to create sign_in and sign_out records
        DB::beginTransaction();

        try {
            foreach ($punchesByEmployeeDay as $data) {
                $employeeId = $data['employee_id'];
                $workDay = $data['work_day'];
                $punches = $data['punches'];

                // Sort punches chronologically
                usort($punches, fn($a, $b) => $a <=> $b);

                $firstPunch = $punches[0];
                $lastPunch = end($punches);

                $results['work_days_processed']++;

                // Create sign_in record (first punch of the day)
                $this->createAttendanceRecord(
                    $employeeId,
                    $firstPunch,
                    'sign_in',
                    $workDay,
                    $results
                );

                // Create sign_out record (last punch of the day) - only if different from first
                if ($firstPunch != $lastPunch) {
                    $this->createAttendanceRecord(
                        $employeeId,
                        $lastPunch,
                        'sign_out',
                        $workDay,
                        $results
                    );
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = "Import failed: " . $e->getMessage();
            Log::error('ZKTeco import failed', [
                'error' => $e->getMessage(),
                'file' => $filePath,
            ]);
        }

        return $results;
    }

    /**
     * Get the work day for a given timestamp
     *
     * If time is before 4 AM, it belongs to the previous calendar day's work day
     *
     * @param Carbon $datetime
     * @return string Work day in Y-m-d format
     */
    protected function getWorkDay(Carbon $datetime): string
    {
        // If time is before cutoff (4 AM), it belongs to previous day
        if ($datetime->hour < self::WORK_DAY_CUTOFF_HOUR) {
            return $datetime->copy()->subDay()->format('Y-m-d');
        }

        return $datetime->format('Y-m-d');
    }

    /**
     * Create or update an attendance record for a work day
     *
     * Duplicate handling strategy:
     * - sign_in: Keep the EARLIEST time for the work day (update if new time is earlier)
     * - sign_out: Keep the LATEST time for the work day (update if new time is later)
     *
     * This ensures uploading overlapping files produces correct results.
     *
     * @param int $employeeId
     * @param Carbon $timestamp
     * @param string $type
     * @param string $workDay The work day in Y-m-d format
     * @param array &$results
     * @return bool Whether record was created or updated
     */
    protected function createAttendanceRecord(
        int $employeeId,
        Carbon $timestamp,
        string $type,
        string $workDay,
        array &$results
    ): bool {
        // Define work day boundaries (work day starts at 4 AM, ends at 4 AM next day)
        $workDayStart = Carbon::parse($workDay)->setHour(self::WORK_DAY_CUTOFF_HOUR)->setMinute(0)->setSecond(0);
        $workDayEnd = $workDayStart->copy()->addDay();

        // Check if a record already exists for this employee, type, and work day
        $existing = AttendanceLog::where('employee_id', $employeeId)
            ->where('type', $type)
            ->where('timestamp', '>=', $workDayStart)
            ->where('timestamp', '<', $workDayEnd)
            ->first();

        if ($existing) {
            // Record exists for this work day - check if we should update it
            $existingTime = Carbon::parse($existing->timestamp);

            if ($type === 'sign_in') {
                // For sign_in: keep the EARLIEST time
                if ($timestamp < $existingTime) {
                    $existing->update(['timestamp' => $timestamp]);
                    $results['updated']++;
                    return true;
                }
            } else {
                // For sign_out: keep the LATEST time
                if ($timestamp > $existingTime) {
                    $existing->update(['timestamp' => $timestamp]);
                    $results['updated']++;
                    return true;
                }
            }

            // Existing record is better, skip this one
            $results['duplicates']++;
            return false;
        }

        // No existing record, create new one
        try {
            AttendanceLog::create([
                'employee_id' => $employeeId,
                'timestamp' => $timestamp,
                'type' => $type,
            ]);
            $results['imported']++;
            return true;
        } catch (\Exception $e) {
            $results['errors'][] = "Failed to insert record: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Parse a single line from the ZKTeco .dat file
     *
     * Format: USER_ID\tDATETIME\tDEVICE_ID\tSTATUS\tVERIFY_TYPE\tWORK_CODE
     * Example: 4\t2017-11-08 14:31:20\t254\t0\t0\t0
     *
     * Note: STATUS is ignored - we determine sign_in/sign_out by first/last punch of day
     *
     * @param string $line
     * @param int $lineNumber
     * @return array|null
     */
    protected function parseLine(string $line, int $lineNumber): ?array
    {
        // Split by tab
        $parts = explode("\t", $line);

        if (count($parts) < 2) {
            Log::warning("ZKTeco import: Invalid line format at line {$lineNumber}");
            return null;
        }

        $userId = trim($parts[0]);
        $datetime = trim($parts[1]);

        // Validate user ID is numeric
        if (!is_numeric($userId)) {
            return null;
        }

        // Validate and parse datetime
        try {
            $parsedDatetime = Carbon::parse($datetime);
        } catch (\Exception $e) {
            Log::warning("ZKTeco import: Invalid datetime at line {$lineNumber}: {$datetime}");
            return null;
        }

        return [
            'user_id' => (int) $userId,
            'datetime' => $parsedDatetime,
        ];
    }

    /**
     * Build a map of attendance_id => employee_id
     *
     * @return array
     */
    protected function buildEmployeeMap(): array
    {
        $employees = Employee::whereNotNull('attendance_id')
            ->where('attendance_id', '!=', '')
            ->get(['id', 'attendance_id']);

        $map = [];
        foreach ($employees as $employee) {
            $map[$employee->attendance_id] = $employee->id;
        }

        return $map;
    }

    /**
     * Get preview data from a .dat file without importing
     *
     * Shows work days with first/last punch times
     * Also shows how many records will be skipped (older than latest existing)
     *
     * @param string $filePath
     * @param int $limit
     * @return array
     */
    public function previewDatFile(string $filePath, int $limit = 10): array
    {
        $preview = [
            'records' => [],
            'total_lines' => 0,
            'total_work_days' => 0,
            'new_work_days' => 0,
            'skipped_old_records' => 0,
            'date_range' => [
                'start' => null,
                'end' => null,
            ],
            'new_date_range' => [
                'start' => null,
                'end' => null,
            ],
            'unique_users' => [],
            'latest_existing_record' => null,
        ];

        if (!file_exists($filePath)) {
            return $preview;
        }

        $employeeMap = $this->buildEmployeeMap();

        // Get the latest attendance record timestamp
        $latestRecord = AttendanceLog::orderBy('timestamp', 'desc')->first();
        $latestTimestamp = $latestRecord ? Carbon::parse($latestRecord->timestamp) : null;
        $preview['latest_existing_record'] = $latestTimestamp?->format('Y-m-d H:i:s');

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return $preview;
        }

        // Collect all punches grouped by employee and work day
        $punchesByEmployeeDay = [];
        $newPunchesByEmployeeDay = [];
        $lineNumber = 0;
        $allDates = [];
        $newDates = [];
        $allUsers = [];

        while (($line = fgets($handle)) !== false) {
            $lineNumber++;
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            $parsed = $this->parseLine($line, $lineNumber);

            if ($parsed === null) {
                continue;
            }

            $allUsers[$parsed['user_id']] = true;
            $allDates[] = $parsed['datetime'];

            // Check if this is an old record
            $isOldRecord = $latestTimestamp && $parsed['datetime']->lte($latestTimestamp);
            if ($isOldRecord) {
                $preview['skipped_old_records']++;
            } else {
                $newDates[] = $parsed['datetime'];
            }

            // Group by employee and work day (for total count)
            $workDay = $this->getWorkDay($parsed['datetime']);
            $key = "{$parsed['user_id']}_{$workDay}";

            if (!isset($punchesByEmployeeDay[$key])) {
                $punchesByEmployeeDay[$key] = [
                    'user_id' => $parsed['user_id'],
                    'work_day' => $workDay,
                    'punches' => [],
                    'is_new' => !$isOldRecord,
                ];
            }
            $punchesByEmployeeDay[$key]['punches'][] = $parsed['datetime'];
            if (!$isOldRecord) {
                $punchesByEmployeeDay[$key]['is_new'] = true;
            }

            // Also track new records separately for preview
            if (!$isOldRecord) {
                if (!isset($newPunchesByEmployeeDay[$key])) {
                    $newPunchesByEmployeeDay[$key] = [
                        'user_id' => $parsed['user_id'],
                        'work_day' => $workDay,
                        'punches' => [],
                    ];
                }
                $newPunchesByEmployeeDay[$key]['punches'][] = $parsed['datetime'];
            }
        }

        fclose($handle);

        // Count new work days
        $preview['new_work_days'] = count($newPunchesByEmployeeDay);

        // Process NEW work days for preview (show most recent first)
        $sortedNewWorkDays = $newPunchesByEmployeeDay;
        uasort($sortedNewWorkDays, fn($a, $b) => strcmp($b['work_day'], $a['work_day']));

        $previewCount = 0;
        foreach ($sortedNewWorkDays as $data) {
            if ($previewCount >= $limit) {
                break;
            }

            $userId = $data['user_id'];
            $punches = $data['punches'];

            // Sort punches chronologically
            usort($punches, fn($a, $b) => $a <=> $b);

            $firstPunch = $punches[0];
            $lastPunch = end($punches);

            $employeeName = 'Unknown';
            $mapped = isset($employeeMap[$userId]);
            if ($mapped) {
                $employee = Employee::find($employeeMap[$userId]);
                $employeeName = $employee ? $employee->name : 'Unknown';
            }

            $preview['records'][] = [
                'attendance_id' => $userId,
                'employee_name' => $employeeName,
                'work_day' => $data['work_day'],
                'sign_in' => $firstPunch->format('Y-m-d H:i:s'),
                'sign_out' => ($firstPunch != $lastPunch) ? $lastPunch->format('Y-m-d H:i:s') : null,
                'punch_count' => count($punches),
                'mapped' => $mapped,
            ];
            $previewCount++;
        }

        $preview['total_lines'] = $lineNumber;
        $preview['total_work_days'] = count($punchesByEmployeeDay);
        $preview['unique_users'] = array_keys($allUsers);

        // Date range for all records
        if (!empty($allDates)) {
            usort($allDates, fn($a, $b) => $a <=> $b);
            $preview['date_range']['start'] = $allDates[0]->format('Y-m-d H:i:s');
            $preview['date_range']['end'] = end($allDates)->format('Y-m-d H:i:s');
        }

        // Date range for new records only
        if (!empty($newDates)) {
            usort($newDates, fn($a, $b) => $a <=> $b);
            $preview['new_date_range']['start'] = $newDates[0]->format('Y-m-d H:i:s');
            $preview['new_date_range']['end'] = end($newDates)->format('Y-m-d H:i:s');
        }

        return $preview;
    }
}
