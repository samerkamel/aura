<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Payroll\Models\BillableHour;
use Modules\Payroll\Models\JiraSyncLog;
use Modules\Payroll\Models\JiraWorklog;
use Modules\Payroll\Services\BillableHoursImportService;
use Modules\Payroll\Services\JiraBillableHoursService;
use Modules\Payroll\Services\JiraWorklogImportService;
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
        // Payroll period: 26th of previous month to 25th of current month
        $today = Carbon::now();
        if ($today->day >= 26) {
            // We're in next month's payroll period
            $periodMonth = $today->copy()->addMonth();
        } else {
            $periodMonth = $today->copy();
        }

        $periodStart = $periodMonth->copy()->subMonth()->setDay(26)->startOfDay();
        $periodEnd = $periodMonth->copy()->setDay(25)->endOfDay();

        // Get active employees with billable hours applicable
        $employees = Employee::active()
            ->where('billable_hours_applicable', true)
            ->with(['billableHours' => function ($query) use ($periodStart) {
                $query->forPeriod($periodStart);
            }])
            ->orderBy('name')
            ->get();

        // Calculate billable hours from Jira worklogs for each employee
        $jiraHoursByEmployee = JiraWorklog::whereBetween('worklog_started', [$periodStart, $periodEnd])
            ->selectRaw('employee_id, SUM(time_spent_hours) as total_hours')
            ->groupBy('employee_id')
            ->pluck('total_hours', 'employee_id')
            ->toArray();

        // Add current hours from Jira worklogs (or manual entry as fallback)
        $employees->transform(function ($employee) use ($jiraHoursByEmployee) {
            // Prioritize Jira worklog hours, fallback to manual billable hours
            $jiraHours = $jiraHoursByEmployee[$employee->id] ?? 0;
            $manualHours = $employee->billableHours->first()?->hours ?? 0;

            $employee->current_billable_hours = $jiraHours > 0 ? (float) $jiraHours : $manualHours;
            $employee->jira_hours = (float) $jiraHours;
            $employee->manual_hours = $manualHours;
            return $employee;
        });

        // Get the latest sync log
        $lastSyncLog = JiraSyncLog::latest();

        return view('payroll::billable-hours.index', [
            'employees' => $employees,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'expectedHeaders' => $this->importService->getExpectedHeaders(),
            'lastSyncLog' => $lastSyncLog,
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

        // Payroll period: 26th of previous month to 25th of current month
        $today = Carbon::now();
        if ($today->day >= 26) {
            $periodMonth = $today->copy()->addMonth();
        } else {
            $periodMonth = $today->copy();
        }
        $periodStart = $periodMonth->copy()->subMonth()->setDay(26)->startOfDay();

        $updateCount = 0;

        foreach ($request->hours as $employeeId => $hours) {
            if ($hours !== null && $hours !== '') {
                BillableHour::updateOrCreate(
                    [
                        'employee_id' => $employeeId,
                        'payroll_period_start_date' => $periodStart,
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

    /**
     * Sync billable hours from Jira
     */
    public function syncJira(Request $request, JiraBillableHoursService $jiraService)
    {
        // Validate request
        $request->validate([
            'period' => 'string|in:this-week,last-week,this-month,last-month'
        ]);

        // Check if Jira sync is enabled
        if (!config('services.jira.sync_enabled')) {
            return response()->json([
                'success' => false,
                'message' => 'Jira sync is not enabled'
            ], 400);
        }

        try {
            // Determine date range based on period
            $period = $request->input('period', 'this-month');
            [$startDate, $endDate] = $this->getDateRangeForPeriod($period);

            // Perform sync
            $results = $jiraService->syncBillableHours($startDate, $endDate);

            return response()->json([
                'success' => true,
                'message' => 'Sync completed successfully',
                'successful_records' => $results['success'],
                'failed_records' => $results['failed'],
                'errors' => $results['errors'] ?? []
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get date range for a period
     */
    protected function getDateRangeForPeriod($period)
    {
        $now = Carbon::now();

        switch ($period) {
            case 'this-week':
                return [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()];

            case 'last-week':
                $lastWeekStart = $now->copy()->subWeek()->startOfWeek();
                $lastWeekEnd = $now->copy()->subWeek()->endOfWeek();
                return [$lastWeekStart, $lastWeekEnd];

            case 'this-month':
                return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];

            case 'last-month':
                $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
                $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();
                return [$lastMonthStart, $lastMonthEnd];

            default:
                return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
        }
    }

    /**
     * Display the Jira worklog import form.
     */
    public function importJiraWorklogsForm(JiraWorklogImportService $worklogService): View
    {
        $mappedEmployees = $worklogService->getMappedEmployees();

        return view('payroll::billable-hours.import-jira-worklogs', [
            'mappedEmployees' => $mappedEmployees,
        ]);
    }

    /**
     * Import Jira worklog CSV file.
     */
    public function importJiraWorklogs(Request $request, JiraWorklogImportService $worklogService): RedirectResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        try {
            $content = file_get_contents($request->file('csv_file')->getRealPath());
            $stats = $worklogService->importFromCsv($content);

            return redirect()
                ->route('payroll.billable-hours.jira-worklogs')
                ->with('import_stats', $stats)
                ->with('success', "Import completed: {$stats['imported']} records imported, {$stats['skipped']} duplicates skipped.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Display the Jira user mapping page.
     */
    public function jiraUserMapping(Request $request, JiraBillableHoursService $jiraService): View
    {
        $jiraUsers = [];
        $error = null;

        // Check if Jira is configured
        if (!$jiraService->isConfigured()) {
            $error = 'Jira is not configured. Please configure Jira settings first.';
        } else {
            try {
                // Get the period to fetch users from
                $period = $request->input('period', 'this-month');
                [$startDate, $endDate] = $this->getDateRangeForPeriod($period);

                // Fetch worklog authors from Jira
                $jiraUsers = $jiraService->fetchWorklogAuthors($startDate, $endDate);
            } catch (\Exception $e) {
                $error = 'Failed to fetch Jira users: ' . $e->getMessage();
            }
        }

        // Get all employees for the dropdown
        $employees = Employee::active()
            ->orderBy('name')
            ->get(['id', 'name', 'jira_account_id']);

        // Create a map of Jira account IDs to employee IDs
        $mappedAccounts = $employees
            ->whereNotNull('jira_account_id')
            ->pluck('id', 'jira_account_id')
            ->toArray();

        // Enrich Jira users with mapping status
        foreach ($jiraUsers as &$user) {
            $user['mappedEmployeeId'] = $mappedAccounts[$user['accountId']] ?? null;
            $user['isMapped'] = isset($mappedAccounts[$user['accountId']]);
        }

        return view('payroll::billable-hours.jira-user-mapping', [
            'jiraUsers' => $jiraUsers,
            'employees' => $employees,
            'error' => $error,
            'period' => $request->input('period', 'this-month'),
        ]);
    }

    /**
     * Save Jira user to employee mapping.
     */
    public function saveJiraUserMapping(Request $request): RedirectResponse
    {
        $request->validate([
            'mappings' => 'required|array',
            'mappings.*.jira_account_id' => 'required|string',
            'mappings.*.employee_id' => 'nullable|exists:employees,id',
        ]);

        $updatedCount = 0;
        $clearedCount = 0;

        foreach ($request->mappings as $mapping) {
            $jiraAccountId = $mapping['jira_account_id'];
            $employeeId = $mapping['employee_id'] ?? null;

            // First, clear any existing mapping for this Jira account
            Employee::where('jira_account_id', $jiraAccountId)
                ->update(['jira_account_id' => null]);

            // If an employee is selected, create the mapping
            if ($employeeId) {
                Employee::where('id', $employeeId)
                    ->update(['jira_account_id' => $jiraAccountId]);
                $updatedCount++;
            } else {
                $clearedCount++;
            }
        }

        $message = "Mapping updated: {$updatedCount} linked";
        if ($clearedCount > 0) {
            $message .= ", {$clearedCount} cleared";
        }

        return redirect()
            ->route('payroll.billable-hours.jira-user-mapping')
            ->with('success', $message);
    }

    /**
     * Manual sync worklogs from Jira API for a specific period.
     */
    public function manualJiraSync(Request $request, JiraBillableHoursService $jiraService)
    {
        $request->validate([
            'period' => 'required|string|in:this-week,last-week,this-month,last-month,custom',
            'start_date' => 'required_if:period,custom|nullable|date',
            'end_date' => 'required_if:period,custom|nullable|date|after_or_equal:start_date',
        ]);

        if (!$jiraService->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Jira is not configured. Please configure Jira settings first.',
            ], 400);
        }

        try {
            $period = $request->input('period');

            if ($period === 'custom') {
                $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            } else {
                [$startDate, $endDate] = $this->getDateRangeForPeriod($period);
            }

            $results = $jiraService->syncBillableHours($startDate, $endDate);

            // Check if there's a custom message (e.g., no employees mapped)
            if (isset($results['message'])) {
                return response()->json([
                    'success' => false,
                    'message' => $results['message'],
                    'data' => $results,
                ], 400);
            }

            $message = "Sync completed: {$results['imported']} worklogs imported";
            if ($results['skipped'] > 0) {
                $message .= ", {$results['skipped']} duplicates skipped";
            }
            if ($results['failed'] > 0) {
                $message .= ", {$results['failed']} failed";
            }

            // Add extra debug info if nothing was synced
            if ($results['imported'] === 0 && $results['skipped'] === 0 && $results['failed'] === 0) {
                $totalWorklogs = $results['total_worklogs_found'] ?? 0;
                $unmappedCount = count($results['unmapped_authors'] ?? []);

                if ($totalWorklogs === 0) {
                    $message .= ". No worklogs found in Jira for the selected date range.";
                } elseif ($unmappedCount > 0) {
                    $message .= ". Found {$totalWorklogs} worklogs, but all {$unmappedCount} Jira users are not mapped to employees. Go to Jira User Mapping to link them.";
                } else {
                    $message .= ". Check logs for details.";
                }
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $results,
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the Jira worklogs list.
     */
    public function jiraWorklogs(Request $request): View
    {
        $query = JiraWorklog::with('employee')
            ->orderBy('worklog_started', 'desc');

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('worklog_started', '>=', Carbon::parse($request->start_date)->startOfDay());
        }
        if ($request->filled('end_date')) {
            $query->where('worklog_started', '<=', Carbon::parse($request->end_date)->endOfDay());
        }

        // Filter by issue key
        if ($request->filled('issue_key')) {
            $query->where('issue_key', 'like', '%' . $request->issue_key . '%');
        }

        $worklogs = $query->paginate(50);

        // Get summary statistics for the current filter
        $summaryQuery = JiraWorklog::query();
        if ($request->filled('employee_id')) {
            $summaryQuery->where('employee_id', $request->employee_id);
        }
        if ($request->filled('start_date')) {
            $summaryQuery->where('worklog_started', '>=', Carbon::parse($request->start_date)->startOfDay());
        }
        if ($request->filled('end_date')) {
            $summaryQuery->where('worklog_started', '<=', Carbon::parse($request->end_date)->endOfDay());
        }
        if ($request->filled('issue_key')) {
            $summaryQuery->where('issue_key', 'like', '%' . $request->issue_key . '%');
        }

        $summary = [
            'total_entries' => $summaryQuery->count(),
            'total_hours' => $summaryQuery->sum('time_spent_hours'),
        ];

        // Get employees with worklogs for filter dropdown
        $employees = Employee::whereHas('jiraWorklogs')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('payroll::billable-hours.jira-worklogs', [
            'worklogs' => $worklogs,
            'summary' => $summary,
            'employees' => $employees,
            'filters' => $request->only(['employee_id', 'start_date', 'end_date', 'issue_key']),
        ]);
    }
}
