<?php

namespace Modules\Payroll\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\HR\Models\Employee;
use Modules\Payroll\Models\JiraWorklog;
use Modules\Payroll\Models\JiraSyncLog;

class JiraWorklogImportService
{
    /**
     * Import worklogs from a Jira CSV export.
     *
     * @param string $csvContent The raw CSV content
     * @return array Import statistics
     */
    public function importFromCsv(string $csvContent): array
    {
        // Remove BOM if present and normalize line endings
        $csvContent = str_replace("\xEF\xBB\xBF", '', $csvContent);
        $csvContent = str_replace("\r\n", "\n", $csvContent);
        $csvContent = str_replace("\r", "\n", $csvContent);

        $lines = array_filter(explode("\n", $csvContent), fn($line) => trim($line) !== '');

        if (count($lines) < 2) {
            return [
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['CSV file is empty or has no data rows'],
                'unmapped_authors' => [],
            ];
        }

        // Parse headers
        $headers = str_getcsv(array_shift($lines));
        $headers = array_map('trim', $headers);

        // Validate required columns
        $requiredColumns = ['Author', 'Issue', 'Issue Summary', 'Work log started', 'Work log created', 'Time spent'];
        $missingColumns = array_diff($requiredColumns, $headers);

        if (!empty($missingColumns)) {
            return [
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['Missing required columns: ' . implode(', ', $missingColumns)],
                'unmapped_authors' => [],
            ];
        }

        // Create sync log
        $syncLog = JiraSyncLog::create([
            'sync_date' => now()->toDateString(),
            'started_at' => now(),
            'status' => 'in_progress',
            'notes' => 'CSV Import',
        ]);

        $stats = [
            'imported' => 0,
            'skipped' => 0,
            'errors' => [],
            'unmapped_authors' => [],
        ];

        // Build author-to-employee map
        $employeeMap = Employee::whereNotNull('jira_author_name')
            ->where('jira_author_name', '!=', '')
            ->pluck('id', 'jira_author_name')
            ->toArray();

        DB::beginTransaction();

        try {
            foreach ($lines as $lineNum => $line) {
                $row = str_getcsv($line);

                // Skip empty rows
                if (count($row) < count($headers)) {
                    continue;
                }

                $data = array_combine($headers, $row);
                $author = trim($data['Author'] ?? '');

                if (empty($author)) {
                    continue;
                }

                // Check if author is mapped to an employee
                if (!isset($employeeMap[$author])) {
                    $stats['unmapped_authors'][$author] = ($stats['unmapped_authors'][$author] ?? 0) + 1;
                    continue;
                }

                try {
                    $result = $this->processRow($data, $employeeMap[$author], $syncLog->id);

                    if ($result === 'imported') {
                        $stats['imported']++;
                    } elseif ($result === 'skipped') {
                        $stats['skipped']++;
                    }
                } catch (\Exception $e) {
                    $stats['errors'][] = 'Line ' . ($lineNum + 2) . ': ' . $e->getMessage();

                    // Stop after 50 errors to prevent overwhelming
                    if (count($stats['errors']) >= 50) {
                        $stats['errors'][] = '... and more errors (stopped after 50)';
                        break;
                    }
                }
            }

            DB::commit();

            // Update sync log
            $syncLog->update([
                'status' => 'completed',
                'completed_at' => now(),
                'total_records' => $stats['imported'] + $stats['skipped'],
                'successful_records' => $stats['imported'],
                'failed_records' => count($stats['errors']),
                'error_details' => !empty($stats['errors']) ? array_slice($stats['errors'], 0, 20) : null,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            $syncLog->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_details' => ['error' => $e->getMessage()],
            ]);

            throw $e;
        }

        return $stats;
    }

    /**
     * Process a single CSV row.
     *
     * @param array $data Row data
     * @param int $employeeId Employee ID
     * @param int $syncLogId Sync log ID
     * @return string 'imported' or 'skipped'
     */
    protected function processRow(array $data, int $employeeId, int $syncLogId): string
    {
        // Parse dates - Jira exports in format: "11/26/2025 1:44:41 PM"
        $worklogStarted = $this->parseJiraDate($data['Work log started'] ?? '');
        $worklogCreated = $this->parseJiraDate($data['Work log created'] ?? '');

        if (!$worklogStarted) {
            throw new \Exception('Invalid "Work log started" date format');
        }

        $issueKey = trim($data['Issue'] ?? '');

        if (empty($issueKey)) {
            throw new \Exception('Missing issue key');
        }

        // Check for duplicate using unique constraint fields
        $existing = JiraWorklog::where('employee_id', $employeeId)
            ->where('issue_key', $issueKey)
            ->where('worklog_started', $worklogStarted)
            ->exists();

        if ($existing) {
            return 'skipped';
        }

        // Create the worklog entry
        JiraWorklog::create([
            'employee_id' => $employeeId,
            'jira_author_name' => trim($data['Author'] ?? ''),
            'issue_key' => $issueKey,
            'issue_summary' => trim($data['Issue Summary'] ?? ''),
            'worklog_started' => $worklogStarted,
            'worklog_created' => $worklogCreated,
            'timezone' => trim($data['Work log time zone'] ?? '') ?: null,
            'time_spent_hours' => (float) ($data['Time spent'] ?? 0),
            'comment' => trim($data['Work log comment'] ?? '') ?: null,
            'sync_log_id' => $syncLogId,
        ]);

        return 'imported';
    }

    /**
     * Parse Jira date format.
     *
     * @param string $dateString Date string in Jira format (e.g., "11/26/2025 1:44:41 PM")
     * @return Carbon|null
     */
    protected function parseJiraDate(string $dateString): ?Carbon
    {
        $dateString = trim($dateString);

        if (empty($dateString)) {
            return null;
        }

        // Try different formats that Jira might export
        $formats = [
            'n/j/Y g:i:s A',      // 11/26/2025 1:44:41 PM
            'm/d/Y g:i:s A',      // 11/26/2025 1:44:41 PM (with leading zeros)
            'n/j/Y h:i:s A',      // 11/26/2025 01:44:41 PM
            'm/d/Y h:i:s A',      // 11/26/2025 01:44:41 PM
            'Y-m-d H:i:s',        // ISO format
            'Y-m-d\TH:i:s.uP',    // ISO 8601 with microseconds
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $dateString);
                if ($date !== false) {
                    return $date;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Last resort: let Carbon parse it
        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get employees with Jira author names configured.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMappedEmployees()
    {
        return Employee::whereNotNull('jira_author_name')
            ->where('jira_author_name', '!=', '')
            ->orderBy('name')
            ->get(['id', 'name', 'jira_author_name']);
    }

    /**
     * Get summary of worklogs for an employee in a date range.
     *
     * @param int $employeeId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function getEmployeeSummary(int $employeeId, Carbon $startDate, Carbon $endDate): array
    {
        $worklogs = JiraWorklog::where('employee_id', $employeeId)
            ->whereBetween('worklog_started', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->get();

        return [
            'total_entries' => $worklogs->count(),
            'total_hours' => $worklogs->sum('time_spent_hours'),
            'issues' => $worklogs->groupBy('issue_key')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'hours' => $group->sum('time_spent_hours'),
                    'summary' => $group->first()->issue_summary,
                ];
            })->toArray(),
        ];
    }
}
