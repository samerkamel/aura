<?php

namespace Modules\Project\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\HR\Models\Employee;
use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectFollowup;
use Modules\Project\Models\JiraIssue;
use Modules\Project\Services\JiraIssueSyncService;
use Modules\Project\Services\JiraProjectSyncService;
use Modules\Project\Services\ProjectDashboardService;
use Modules\Project\Services\ProjectFollowupService;
use Modules\Project\Services\ProjectFinancialService;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects.
     */
    public function index(Request $request, ProjectDashboardService $dashboardService): View
    {
        // Check if user can view any projects
        if (!Gate::allows('view-all-projects') && !Gate::allows('view-assigned-projects')) {
            abort(403, 'You do not have permission to view projects.');
        }

        // Start with projects accessible by the current user
        // Eager load only what's needed for the list view
        $query = Project::with(['customer:id,name,company_name', 'projectManager:id,name'])
            ->withCount(['employees', 'jiraIssues as open_issues_count' => function ($q) {
                $q->whereNotIn('status', ['Done', 'Closed', 'Resolved']);
            }])
            ->withSum('revenues', 'amount')
            ->withSum('revenues', 'amount_received')
            ->accessibleByUser(auth()->user());

        // Filter by phase (default excludes closure, 'all' shows everything including closure)
        $phaseFilter = $request->get('phase', 'active'); // 'active' = all except closure
        if ($phaseFilter === 'active') {
            $query->where(function ($q) {
                $q->where('phase', '!=', 'closure')->orWhereNull('phase');
            });
        } elseif ($phaseFilter === 'closure') {
            $query->where('phase', 'closure');
        } elseif ($phaseFilter !== 'all') {
            // Specific phase selected
            $query->where('phase', $phaseFilter);
        }
        // 'all' shows everything

        // Filter by needs_monthly_report
        if ($request->filled('needs_report')) {
            $query->where('needs_monthly_report', $request->needs_report === '1');
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by health status
        if ($request->filled('health_status')) {
            $query->where('health_status', $request->health_status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort', 'name');
        $sortDir = $request->get('dir', 'asc');
        $allowedSorts = ['name', 'code', 'created_at', 'completion_percentage', 'health_status'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('name');
        }

        $projects = $query->paginate(20);

        // Financial year filter (default to current year, 'all' for all time)
        $financialYear = null;
        if ($request->filled('fy')) {
            $financialYear = $request->fy === 'all' ? null : (int) $request->fy;
        } else {
            $financialYear = now()->year; // Default to current year
        }

        // Generate list of available financial years (from earliest project to current year + 1)
        $earliestYear = Project::min(DB::raw('YEAR(created_at)')) ?? now()->year;
        $financialYears = range(now()->year + 1, $earliestYear);

        // Calculate hours for each project (FY hours and lifetime hours)
        $projectCodes = $projects->pluck('code')->toArray();

        // Get lifetime hours per project
        $lifetimeHours = DB::table('jira_worklogs')
            ->select(DB::raw("SUBSTRING_INDEX(issue_key, '-', 1) as project_code"), DB::raw('SUM(time_spent_hours) as hours'))
            ->whereIn(DB::raw("SUBSTRING_INDEX(issue_key, '-', 1)"), $projectCodes)
            ->groupBy('project_code')
            ->pluck('hours', 'project_code')
            ->toArray();

        // Get FY hours per project (if FY selected)
        $fyHours = [];
        if ($financialYear) {
            $startDate = \Carbon\Carbon::create($financialYear, 1, 1)->startOfDay();
            $endDate = \Carbon\Carbon::create($financialYear, 12, 31)->endOfDay();

            $fyHours = DB::table('jira_worklogs')
                ->select(DB::raw("SUBSTRING_INDEX(issue_key, '-', 1) as project_code"), DB::raw('SUM(time_spent_hours) as hours'))
                ->whereIn(DB::raw("SUBSTRING_INDEX(issue_key, '-', 1)"), $projectCodes)
                ->whereBetween('worklog_started', [$startDate, $endDate])
                ->groupBy('project_code')
                ->pluck('hours', 'project_code')
                ->toArray();
        }

        // Attach hours to projects
        foreach ($projects as $project) {
            $project->lifetime_hours = $lifetimeHours[$project->code] ?? 0;
            $project->fy_hours = $fyHours[$project->code] ?? 0;
        }

        // Get portfolio stats using optimized aggregation with financial year filter
        $portfolioStats = $dashboardService->getPortfolioStatsOptimized($phaseFilter, auth()->user(), $financialYear);

        $customers = Customer::active()->orderBy('name')->get(['id', 'name', 'company_name']);

        return view('project::projects.index', [
            'projects' => $projects,
            'portfolioStats' => $portfolioStats,
            'customers' => $customers,
            'filters' => $request->only(['needs_report', 'search', 'customer_id', 'health_status', 'sort', 'dir', 'fy']),
            'phaseFilter' => $phaseFilter,
            'canViewAll' => Gate::allows('view-all-projects'),
            'healthStatuses' => Project::HEALTH_STATUSES,
            'phases' => Project::PHASES,
            'financialYears' => $financialYears,
            'selectedFY' => $financialYear,
        ]);
    }

    /**
     * Show the form for creating a new project.
     */
    public function create(): View
    {
        if (!Gate::allows('create-project')) {
            abort(403, 'You do not have permission to create projects.');
        }

        $customers = Customer::active()->orderBy('name')->get();

        return view('project::projects.create', [
            'customers' => $customers,
        ]);
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!Gate::allows('create-project')) {
            abort(403, 'You do not have permission to create projects.');
        }

        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:projects,code',
            'description' => 'nullable|string',
            'needs_monthly_report' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['needs_monthly_report'] = $request->has('needs_monthly_report');
        $validated['is_active'] = $request->has('is_active') || !$request->filled('is_active');

        Project::create($validated);

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project created successfully.');
    }

    /**
     * Display the specified project.
     */
    public function show(Request $request, Project $project, ProjectDashboardService $dashboardService): View
    {
        if (!Gate::allows('view-project', $project)) {
            abort(403, 'You do not have permission to view this project.');
        }

        $project->load([
            'customer',
            'employees',
            'projectManager',
            'invoices.payments',
            'invoices.customer',
            'contracts.payments',
            'contracts.customer',
            'milestones',
            'risks',
            'timeEstimates',
        ]);

        // Get comprehensive dashboard data
        $dashboard = $dashboardService->getDashboardData($project);

        // Get worklogs with optional date filtering (default: lifetime/all time)
        $startDate = $request->filled('start_date') ? $request->start_date : null;
        $endDate = $request->filled('end_date') ? $request->end_date : null;

        $worklogsQuery = \Modules\Payroll\Models\JiraWorklog::where('issue_key', 'LIKE', $project->code . '-%')
            ->with('employee')
            ->orderBy('worklog_started', 'desc');

        // Only apply date filter if dates are provided
        if ($startDate && $endDate) {
            $worklogsQuery->whereBetween('worklog_started', [$startDate, $endDate]);
        }

        $worklogs = $worklogsQuery->limit(100)->get();

        // Group worklogs by employee
        $worklogsByEmployee = $worklogs->groupBy('employee_id')->map(function ($employeeWorklogs) {
            return [
                'employee' => $employeeWorklogs->first()->employee,
                'total_hours' => $employeeWorklogs->sum('time_spent_hours'),
                'entries' => $employeeWorklogs,
            ];
        });

        // Calculate totals - use base currency (EGP) for proper currency conversion
        $totalHours = $dashboard['progress']['actual_hours'];
        $lifetimeHours = $totalHours;
        $totalContractValue = $project->total_contract_value; // Converts foreign currency to EGP
        $totalPaid = $project->total_paid; // Converts foreign currency to EGP
        $totalRemaining = $totalContractValue - $totalPaid;

        // If date filter is applied, recalculate hours for filtered period
        if ($startDate && $endDate) {
            $totalHours = $worklogs->sum('time_spent_hours');
        }

        return view('project::projects.show', [
            'project' => $project,
            'dashboard' => $dashboard,
            'worklogs' => $worklogs,
            'worklogsByEmployee' => $worklogsByEmployee,
            'totalHours' => $totalHours,
            'lifetimeHours' => $lifetimeHours,
            'totalContractValue' => $totalContractValue,
            'totalPaid' => $totalPaid,
            'totalRemaining' => $totalRemaining,
            'projectCost' => $dashboard['financial']['summary']['total_spent'],
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Show the form for editing a project.
     */
    public function edit(Project $project): View
    {
        if (!Gate::allows('edit-project', $project)) {
            abort(403, 'You do not have permission to edit this project.');
        }

        $customers = Customer::active()->orderBy('name')->get();

        // Calculate suggested phase based on project data
        $suggestedPhase = $this->calculateSuggestedPhase($project);

        return view('project::projects.edit', [
            'project' => $project,
            'customers' => $customers,
            'phases' => Project::PHASES,
            'healthStatuses' => Project::HEALTH_STATUSES,
            'suggestedPhase' => $suggestedPhase,
        ]);
    }

    /**
     * Calculate suggested phase based on project metrics.
     */
    private function calculateSuggestedPhase(Project $project): array
    {
        $completion = $project->completion_percentage ?? 0;
        $totalHours = $project->total_hours ?? 0;
        $hasContract = $project->contracts()->exists();
        $openIssues = $project->jiraIssues()->whereNotIn('status', ['Done', 'Closed', 'Resolved'])->count();
        $totalIssues = $project->jiraIssues()->count();
        $closedIssuesRatio = $totalIssues > 0 ? (($totalIssues - $openIssues) / $totalIssues) * 100 : 0;

        // Determine suggested phase and reason
        if ($completion >= 100 && $openIssues === 0) {
            return [
                'phase' => 'closure',
                'reason' => '100% complete with no open issues',
                'confidence' => 'high',
            ];
        }

        if ($completion >= 80 || $closedIssuesRatio >= 80) {
            return [
                'phase' => 'monitoring',
                'reason' => sprintf('%.0f%% complete, %.0f%% issues resolved', $completion, $closedIssuesRatio),
                'confidence' => 'medium',
            ];
        }

        if ($totalHours > 0 || $completion > 0 || $openIssues > 0) {
            return [
                'phase' => 'execution',
                'reason' => sprintf('Active work: %.1f hours logged, %d open issues', $totalHours, $openIssues),
                'confidence' => 'high',
            ];
        }

        if ($hasContract) {
            return [
                'phase' => 'planning',
                'reason' => 'Has contract but no work logged yet',
                'confidence' => 'medium',
            ];
        }

        return [
            'phase' => 'initiation',
            'reason' => 'New project with no activity',
            'confidence' => 'low',
        ];
    }

    /**
     * Update the specified project.
     */
    public function update(Request $request, Project $project): RedirectResponse
    {
        if (!Gate::allows('edit-project', $project)) {
            abort(403, 'You do not have permission to edit this project.');
        }

        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:projects,code,' . $project->id,
            'description' => 'nullable|string',
            'phase' => 'nullable|in:' . implode(',', array_keys(Project::PHASES)),
            'health_status' => 'nullable|in:' . implode(',', array_keys(Project::HEALTH_STATUSES)),
            'needs_monthly_report' => 'boolean',
        ]);

        $validated['needs_monthly_report'] = $request->has('needs_monthly_report');

        $project->update($validated);

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project updated successfully.');
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Project $project): RedirectResponse
    {
        if (!Gate::allows('delete-project')) {
            abort(403, 'You do not have permission to delete projects.');
        }

        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project deleted successfully.');
    }

    /**
     * Sync projects from Jira.
     */
    /**
     * Old sync method - redirects to new AJAX-based sync
     */
    public function syncFromJira(): RedirectResponse
    {
        // This will be handled by AJAX now, just redirect back
        return redirect()
            ->route('projects.index')
            ->with('info', 'Please use the Sync button which now shows progress.');
    }

    /**
     * AJAX: Get list of projects to sync
     */
    public function syncJiraGetProjects(JiraProjectSyncService $projectSyncService): \Illuminate\Http\JsonResponse
    {
        if (!Gate::allows('sync-project-jira')) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        try {
            $results = $projectSyncService->syncProjects();

            // Get list of active projects for issue sync
            $activeProjects = Project::where(function ($q) {
                    $q->where('phase', '!=', 'closure')->orWhereNull('phase');
                })
                ->whereNotNull('code')
                ->orderBy('name')
                ->get(['id', 'code', 'name']);

            return response()->json([
                'success' => true,
                'projects' => [
                    'created' => $results['created'],
                    'updated' => $results['updated'],
                ],
                'active_projects' => $activeProjects->map(fn($p) => [
                    'id' => $p->id,
                    'code' => $p->code,
                    'name' => $p->name,
                ])->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * AJAX: Sync issues for a single project
     */
    public function syncJiraProjectIssues(Request $request, JiraIssueSyncService $issueSyncService): \Illuminate\Http\JsonResponse
    {
        if (!Gate::allows('sync-project-jira')) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $projectId = $request->input('project_id');
        $project = Project::find($projectId);

        if (!$project) {
            return response()->json(['success' => false, 'error' => 'Project not found'], 404);
        }

        try {
            $results = $issueSyncService->syncProjectIssues($project, 500);

            return response()->json([
                'success' => true,
                'project' => $project->code,
                'issues' => [
                    'total' => $results['total'],
                    'created' => $results['created'],
                    'updated' => $results['updated'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'project' => $project->code,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * AJAX: Sync worklogs for a date range
     */
    public function syncJiraWorklogs(Request $request, \Modules\Payroll\Services\JiraBillableHoursService $worklogSyncService): \Illuminate\Http\JsonResponse
    {
        if (!Gate::allows('sync-project-jira')) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        try {
            $days = $request->input('days', 90);
            $startDate = now()->subDays($days);
            $endDate = now();

            $results = $worklogSyncService->syncBillableHours($startDate, $endDate);

            return response()->json([
                'success' => true,
                'worklogs' => [
                    'imported' => $results['imported'],
                    'skipped' => $results['skipped'],
                    'failed' => $results['failed'] ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Toggle the needs_monthly_report flag.
     */
    public function toggleMonthlyReport(Project $project): RedirectResponse
    {
        $project->needs_monthly_report = !$project->needs_monthly_report;
        $project->save();

        $status = $project->needs_monthly_report ? 'enabled' : 'disabled';

        return redirect()
            ->back()
            ->with('success', "Monthly report {$status} for {$project->name}.");
    }

    /**
     * Display all worklogs for a project with filtering and pagination.
     */
    public function worklogs(Request $request, Project $project): View
    {
        if (!Gate::allows('view-project', $project)) {
            abort(403, 'You do not have permission to view this project.');
        }

        $project->load(['customer', 'employees']);

        // Build query
        $query = \Modules\Payroll\Models\JiraWorklog::where('issue_key', 'LIKE', $project->code . '-%')
            ->with('employee')
            ->orderBy('worklog_started', 'desc');

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('worklog_started', [$request->start_date, $request->end_date]);
        }

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by issue key
        if ($request->filled('issue_key')) {
            $query->where('issue_key', 'LIKE', '%' . $request->issue_key . '%');
        }

        // Search in description/comment
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'LIKE', '%' . $search . '%')
                  ->orWhere('issue_summary', 'LIKE', '%' . $search . '%');
            });
        }

        // Get paginated results
        $worklogs = $query->paginate(50)->withQueryString();

        // Get summary stats for current filter
        $statsQuery = \Modules\Payroll\Models\JiraWorklog::where('issue_key', 'LIKE', $project->code . '-%');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $statsQuery->whereBetween('worklog_started', [$request->start_date, $request->end_date]);
        }
        if ($request->filled('employee_id')) {
            $statsQuery->where('employee_id', $request->employee_id);
        }
        if ($request->filled('issue_key')) {
            $statsQuery->where('issue_key', 'LIKE', '%' . $request->issue_key . '%');
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $statsQuery->where(function ($q) use ($search) {
                $q->where('comment', 'LIKE', '%' . $search . '%')
                  ->orWhere('issue_summary', 'LIKE', '%' . $search . '%');
            });
        }

        $totalHours = $statsQuery->sum('time_spent_hours');
        $totalEntries = $statsQuery->count();

        // Get employees who have worklogs on this project for the filter dropdown
        $employeesWithWorklogs = \Modules\Payroll\Models\JiraWorklog::where('issue_key', 'LIKE', $project->code . '-%')
            ->whereNotNull('employee_id')
            ->with('employee')
            ->get()
            ->pluck('employee')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        // Get unique issue keys for filter suggestions
        $issueKeys = \Modules\Payroll\Models\JiraWorklog::where('issue_key', 'LIKE', $project->code . '-%')
            ->distinct()
            ->pluck('issue_key')
            ->sort()
            ->values();

        return view('project::projects.worklogs', [
            'project' => $project,
            'worklogs' => $worklogs,
            'totalHours' => $totalHours,
            'totalEntries' => $totalEntries,
            'employeesWithWorklogs' => $employeesWithWorklogs,
            'issueKeys' => $issueKeys,
            'filters' => $request->only(['start_date', 'end_date', 'employee_id', 'issue_key', 'search']),
        ]);
    }

    /**
     * Show the employees management page for a project.
     */
    public function manageEmployees(Project $project): View
    {
        if (!Gate::allows('manage-project-team', $project)) {
            abort(403, 'You do not have permission to manage this project\'s team.');
        }

        $project->load('employees');

        // Get all active employees not already assigned
        $assignedEmployeeIds = $project->employees->pluck('id')->toArray();
        $availableEmployees = Employee::active()
            ->whereNotIn('id', $assignedEmployeeIds)
            ->orderBy('name')
            ->get();

        // Get employees with worklogs but not assigned
        $unassignedWorklogEmployees = $project->getUnassignedWorklogEmployees();

        return view('project::projects.manage-employees', [
            'project' => $project,
            'availableEmployees' => $availableEmployees,
            'unassignedWorklogEmployees' => $unassignedWorklogEmployees,
        ]);
    }

    /**
     * Assign an employee to a project.
     */
    public function assignEmployee(Request $request, Project $project): RedirectResponse
    {
        if (!Gate::allows('manage-project-team', $project)) {
            abort(403, 'You do not have permission to manage this project\'s team.');
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'role' => 'required|in:member,lead',
            'allocation_percentage' => 'nullable|numeric|min:0|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'hourly_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check if already assigned
        if ($project->employees()->where('employee_id', $validated['employee_id'])->exists()) {
            return redirect()
                ->back()
                ->with('error', 'Employee is already assigned to this project.');
        }

        $project->employees()->attach($validated['employee_id'], [
            'role' => $validated['role'],
            'allocation_percentage' => $validated['allocation_percentage'] ?? 100,
            'start_date' => $validated['start_date'] ?? now()->format('Y-m-d'),
            'end_date' => $validated['end_date'] ?? null,
            'hourly_rate' => $validated['hourly_rate'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'auto_assigned' => false,
            'assigned_at' => now(),
        ]);

        $employee = Employee::find($validated['employee_id']);

        return redirect()
            ->back()
            ->with('success', "{$employee->name} has been assigned to the project.");
    }

    /**
     * Update an employee's allocation in a project.
     */
    public function updateEmployeeRole(Request $request, Project $project): RedirectResponse
    {
        if (!Gate::allows('manage-project-team', $project)) {
            abort(403, 'You do not have permission to manage this project\'s team.');
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'role' => 'required|in:member,lead',
            'allocation_percentage' => 'nullable|numeric|min:0|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'hourly_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $updateData = ['role' => $validated['role']];

        if (isset($validated['allocation_percentage'])) {
            $updateData['allocation_percentage'] = $validated['allocation_percentage'];
        }
        if (array_key_exists('start_date', $validated)) {
            $updateData['start_date'] = $validated['start_date'];
        }
        if (array_key_exists('end_date', $validated)) {
            $updateData['end_date'] = $validated['end_date'];
        }
        if (array_key_exists('hourly_rate', $validated)) {
            $updateData['hourly_rate'] = $validated['hourly_rate'] ?: null;
        }
        if (array_key_exists('notes', $validated)) {
            $updateData['notes'] = $validated['notes'];
        }

        $project->employees()->updateExistingPivot($validated['employee_id'], $updateData);

        return redirect()
            ->back()
            ->with('success', 'Employee allocation updated successfully.');
    }

    /**
     * Remove an employee from a project.
     */
    public function unassignEmployee(Request $request, Project $project): RedirectResponse
    {
        if (!Gate::allows('manage-project-team', $project)) {
            abort(403, 'You do not have permission to manage this project\'s team.');
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        $project->employees()->detach($validated['employee_id']);

        $employee = Employee::find($validated['employee_id']);

        return redirect()
            ->back()
            ->with('success', "{$employee->name} has been removed from the project.");
    }

    /**
     * Sync employees from worklogs for a project.
     */
    public function syncEmployeesFromWorklogs(Project $project): RedirectResponse
    {
        if (!Gate::allows('manage-project-team', $project)) {
            abort(403, 'You do not have permission to manage this project\'s team.');
        }

        $result = $project->syncEmployeesFromWorklogs();

        $message = "Sync complete: {$result['newly_added']} new employees added from {$result['total_with_worklogs']} with worklogs.";

        return redirect()
            ->back()
            ->with('success', $message);
    }

    /**
     * Display the follow-ups dashboard.
     */
    public function followups(Request $request, ProjectFollowupService $followupService): View
    {
        if (!Gate::allows('manage-project-followups')) {
            abort(403, 'You do not have permission to access project follow-ups.');
        }

        $activityDays = (int) $request->get('activity_days', 60);
        $showAllActive = $request->boolean('show_all_active', false);

        // Validate activity_days is in allowed list
        if (!array_key_exists($activityDays, ProjectFollowupService::ACTIVITY_PERIODS)) {
            $activityDays = 60;
        }

        $dashboard = $followupService->getFollowupDashboard($activityDays, $showAllActive);

        return view('project::projects.followups', [
            'projects' => $dashboard['projects'],
            'summary' => $dashboard['summary'],
            'filters' => $dashboard['filters'],
        ]);
    }

    /**
     * Store a new follow-up for a project.
     */
    public function storeFollowup(Request $request, Project $project, ProjectFollowupService $followupService): RedirectResponse
    {
        if (!Gate::allows('manage-project-followups', $project)) {
            abort(403, 'You do not have permission to manage follow-ups for this project.');
        }

        $validated = $request->validate([
            'type' => 'required|in:call,email,meeting,message,other',
            'notes' => 'required|string|max:2000',
            'contact_person' => 'nullable|string|max:255',
            'outcome' => 'required|in:positive,neutral,needs_attention,escalation',
            'followup_date' => 'required|date',
            'next_followup_date' => 'nullable|date|after_or_equal:followup_date',
        ]);

        $followupService->logFollowup($project, $validated);

        return redirect()
            ->back()
            ->with('success', 'Follow-up logged successfully for ' . $project->name);
    }

    /**
     * Get follow-up history for a project (AJAX).
     */
    public function getProjectFollowups(Project $project, ProjectFollowupService $followupService)
    {
        if (!Gate::allows('manage-project-followups', $project)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $followups = $followupService->getProjectFollowupHistory($project, 20);
        $activity = $followupService->getProjectActivitySummary($project);

        return response()->json([
            'followups' => $followups->map(function ($followup) {
                return [
                    'id' => $followup->id,
                    'type' => $followup->type,
                    'type_label' => $followup->type_label,
                    'notes' => $followup->notes,
                    'contact_person' => $followup->contact_person,
                    'outcome' => $followup->outcome,
                    'outcome_label' => $followup->outcome_label,
                    'outcome_color' => $followup->outcome_color,
                    'followup_date' => $followup->followup_date->format('Y-m-d'),
                    'followup_date_formatted' => $followup->followup_date->format('M d, Y'),
                    'next_followup_date' => $followup->next_followup_date?->format('Y-m-d'),
                    'user' => $followup->user?->name ?? 'Unknown',
                    'created_at' => $followup->created_at->diffForHumans(),
                ];
            }),
            'activity' => $activity,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'code' => $project->code,
                'followup_status' => $project->followup_status,
                'followup_status_label' => $project->followup_status_label,
                'followup_status_color' => $project->followup_status_color,
                'last_followup_date' => $project->last_followup_date?->format('M d, Y'),
                'next_followup_date' => $project->next_followup_date?->format('M d, Y'),
            ],
        ]);
    }

    /**
     * Display Jira issues/tasks for a project.
     */
    public function tasks(Request $request, Project $project, JiraIssueSyncService $issueSyncService): View
    {
        if (!Gate::allows('view-project', $project)) {
            abort(403, 'You do not have permission to view this project.');
        }

        $project->load('customer');

        // Get issue summary
        $summary = $issueSyncService->getProjectIssueSummary($project);

        // Get filters
        $filters = $request->only(['status_category', 'issue_type', 'assignee_employee_id', 'priority', 'search', 'view']);
        $view = $filters['view'] ?? 'kanban';

        // Get issues based on view type
        if ($view === 'kanban') {
            $issues = $issueSyncService->getIssuesForKanban($project);
        } else {
            $issues = $issueSyncService->getFilteredIssues($project, $filters);
        }

        // Get filter options
        $issueTypes = $project->jiraIssues()->distinct()->pluck('issue_type')->sort()->values();
        $priorities = $project->jiraIssues()->distinct()->whereNotNull('priority')->pluck('priority')->sort()->values();
        $assignees = Employee::whereIn('id', $project->jiraIssues()->whereNotNull('assignee_employee_id')->pluck('assignee_employee_id'))
            ->orderBy('name')
            ->get();

        return view('project::projects.tasks', [
            'project' => $project,
            'issues' => $issues,
            'summary' => $summary,
            'filters' => $filters,
            'view' => $view,
            'issueTypes' => $issueTypes,
            'priorities' => $priorities,
            'assignees' => $assignees,
        ]);
    }

    /**
     * Sync Jira issues for a project.
     */
    public function syncIssues(Project $project, JiraIssueSyncService $issueSyncService): RedirectResponse
    {
        if (!Gate::allows('sync-project-jira')) {
            abort(403, 'You do not have permission to sync Jira issues.');
        }

        try {
            $results = $issueSyncService->syncProjectIssues($project);

            $message = "Sync completed: {$results['created']} created, {$results['updated']} updated.";
            if (!empty($results['errors'])) {
                $message .= " Errors: " . implode('; ', array_slice($results['errors'], 0, 5));
            }

            return redirect()
                ->back()
                ->with(!empty($results['errors']) ? 'warning' : 'success', $message);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Get Jira issues for a project (AJAX).
     */
    public function getProjectIssues(Request $request, Project $project, JiraIssueSyncService $issueSyncService)
    {
        if (!Gate::allows('view-project', $project)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $filters = $request->only(['status_category', 'issue_type', 'assignee_employee_id', 'priority', 'search', 'sort_by', 'sort_dir']);

        $issues = $issueSyncService->getFilteredIssues($project, $filters);
        $summary = $issueSyncService->getProjectIssueSummary($project);

        return response()->json([
            'issues' => $issues->map(function ($issue) {
                return [
                    'id' => $issue->id,
                    'issue_key' => $issue->issue_key,
                    'summary' => $issue->summary,
                    'issue_type' => $issue->issue_type,
                    'issue_type_icon' => $issue->issue_type_icon,
                    'issue_type_color' => $issue->issue_type_color,
                    'status' => $issue->status,
                    'status_category' => $issue->status_category,
                    'status_color' => $issue->status_color,
                    'priority' => $issue->priority,
                    'priority_color' => $issue->priority_color,
                    'assignee' => $issue->assignee?->name ?? $issue->assignee_email,
                    'due_date' => $issue->due_date?->format('M d, Y'),
                    'is_overdue' => $issue->isOverdue(),
                    'jira_url' => $issue->jira_url,
                    'jira_updated_at' => $issue->jira_updated_at?->diffForHumans(),
                ];
            }),
            'summary' => $summary,
        ]);
    }

    /**
     * Show the create task form for a project.
     */
    public function createTask(Project $project, JiraIssueSyncService $issueSyncService)
    {
        if (!Gate::allows('view-project', $project)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (empty($project->code)) {
            return response()->json(['error' => 'Project has no Jira project code'], 400);
        }

        // Get issue types, priorities, and assignable users from Jira
        $issueTypes = $issueSyncService->getProjectIssueTypes($project->code);
        $priorities = $issueSyncService->getPriorities();
        $assignees = $issueSyncService->getAssignableUsers($project->code);

        return response()->json([
            'issue_types' => $issueTypes,
            'priorities' => $priorities,
            'assignees' => $assignees,
        ]);
    }

    /**
     * Store a new task in Jira and sync it back.
     */
    public function storeTask(Request $request, Project $project, JiraIssueSyncService $issueSyncService): RedirectResponse
    {
        if (!Gate::allows('view-project', $project)) {
            abort(403, 'You do not have permission to create tasks for this project.');
        }

        $validated = $request->validate([
            'summary' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'issue_type' => 'required|string',
            'priority' => 'nullable|string',
            'due_date' => 'nullable|date',
            'assignee_account_id' => 'nullable|string',
        ]);

        try {
            $result = $issueSyncService->createIssueInJira($project, $validated);

            return redirect()
                ->route('projects.tasks', $project)
                ->with('success', "Task {$result['issue_key']} created successfully in Jira.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create task: ' . $e->getMessage());
        }
    }

    /**
     * Show the bulk task creation page.
     */
    public function bulkCreateTasks(Project $project, JiraIssueSyncService $issueSyncService): View
    {
        if (!Gate::allows('view-project', $project)) {
            abort(403, 'You do not have permission to create tasks for this project.');
        }

        if (empty($project->code)) {
            return redirect()
                ->route('projects.tasks', $project)
                ->with('error', 'Project has no Jira project code. Cannot create tasks.');
        }

        $project->load('customer');

        // Get issue types, priorities, and assignable users from Jira
        $issueTypes = $issueSyncService->getProjectIssueTypes($project->code);
        $priorities = $issueSyncService->getPriorities();
        $assignees = $issueSyncService->getAssignableUsers($project->code);

        return view('project::projects.bulk-create-tasks', compact(
            'project',
            'issueTypes',
            'priorities',
            'assignees'
        ));
    }

    /**
     * Store multiple tasks in Jira.
     */
    public function storeBulkTasks(Request $request, Project $project, JiraIssueSyncService $issueSyncService): RedirectResponse
    {
        if (!Gate::allows('view-project', $project)) {
            abort(403, 'You do not have permission to create tasks for this project.');
        }

        $validated = $request->validate([
            'tasks' => 'required|array|min:1',
            'tasks.*.summary' => 'required|string|max:255',
            'tasks.*.description' => 'nullable|string|max:5000',
            'tasks.*.issue_type' => 'required|string',
            'tasks.*.priority' => 'nullable|string',
            'tasks.*.due_date' => 'nullable|date',
            'tasks.*.assignee_account_id' => 'nullable|string',
        ]);

        $created = [];
        $errors = [];

        foreach ($validated['tasks'] as $index => $taskData) {
            // Skip empty rows
            if (empty(trim($taskData['summary']))) {
                continue;
            }

            try {
                $result = $issueSyncService->createIssueInJira($project, $taskData);
                $created[] = $result['issue_key'];
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
            }
        }

        $message = '';
        if (count($created) > 0) {
            $message = count($created) . " task(s) created successfully: " . implode(', ', $created);
        }
        if (count($errors) > 0) {
            $message .= (empty($message) ? '' : '. ') . "Errors: " . implode('; ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $message .= ' and ' . (count($errors) - 3) . ' more errors.';
            }
        }

        $status = count($errors) > 0 ? (count($created) > 0 ? 'warning' : 'error') : 'success';

        return redirect()
            ->route('projects.tasks', $project)
            ->with($status, $message);
    }

    /**
     * Get issue details for modal display.
     */
    public function getIssueDetails(Project $project, JiraIssue $issue, JiraIssueSyncService $issueSyncService)
    {
        if (!Gate::allows('view-project', $project)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get fresh details from Jira
        $details = $issueSyncService->getIssueDetails($issue->issue_key);

        if (!$details) {
            // Fallback to local data
            $details = [
                'key' => $issue->issue_key,
                'summary' => $issue->summary,
                'description' => $issue->description,
                'status' => $issue->status,
                'issue_type' => $issue->issue_type,
                'priority' => $issue->priority,
                'epic_key' => $issue->epic_key,
                'story_points' => $issue->story_points,
                'labels' => $issue->labels ?? [],
                'components' => $issue->components ?? [],
                'due_date' => $issue->due_date?->format('Y-m-d'),
                'created_at' => $issue->jira_created_at?->toIso8601String(),
                'updated_at' => $issue->jira_updated_at?->toIso8601String(),
            ];
        }

        return response()->json($details);
    }

    /**
     * Get available transitions for an issue.
     */
    public function getIssueTransitions(Project $project, JiraIssue $issue, JiraIssueSyncService $issueSyncService)
    {
        if (!Gate::allows('view-project', $project)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $transitions = $issueSyncService->getIssueTransitions($issue->issue_key);

        return response()->json([
            'current_status' => $issue->status,
            'transitions' => $transitions,
        ]);
    }

    /**
     * Update an issue field in Jira.
     */
    public function updateIssueField(Request $request, Project $project, JiraIssue $issue, JiraIssueSyncService $issueSyncService)
    {
        if (!Gate::allows('view-project', $project)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'field' => 'required|string|in:assignee,priority,due_date,story_points',
            'value' => 'nullable',
        ]);

        try {
            $result = $issueSyncService->updateIssueField(
                $issue->issue_key,
                $validated['field'],
                $validated['value']
            );

            return response()->json([
                'success' => true,
                'message' => 'Field updated successfully',
                'issue' => $result['local_issue'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Transition an issue to a new status.
     */
    public function transitionIssue(Request $request, Project $project, JiraIssue $issue, JiraIssueSyncService $issueSyncService)
    {
        if (!Gate::allows('view-project', $project)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'transition_id' => 'required|string',
        ]);

        try {
            $result = $issueSyncService->transitionIssue(
                $issue->issue_key,
                $validated['transition_id']
            );

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'issue' => $result['local_issue'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the mass customer linking page.
     */
    public function linkCustomers(Request $request): View
    {
        if (!Gate::allows('view-all-projects')) {
            abort(403, 'You do not have permission to link projects to customers.');
        }

        // Get all customers
        $customers = Customer::active()
            ->withCount('projects')
            ->orderBy('name')
            ->get();

        // Build query for projects
        $query = Project::with('customer:id,name,company_name')
            ->orderBy('name');

        // Filter by phase
        $phaseFilter = $request->get('phase', 'active');
        if ($phaseFilter === 'active') {
            $query->where(function ($q) {
                $q->where('phase', '!=', 'closure')->orWhereNull('phase');
            });
        } elseif ($phaseFilter === 'closure') {
            $query->where('phase', 'closure');
        } elseif ($phaseFilter !== 'all') {
            $query->where('phase', $phaseFilter);
        }

        // Filter by current customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter unlinked only
        if ($request->get('unlinked_only', '1') === '1') {
            $query->whereNull('customer_id');
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $projects = $query->paginate(50)->withQueryString();

        return view('project::projects.link-customers', [
            'projects' => $projects,
            'customers' => $customers,
            'phaseFilter' => $phaseFilter,
            'phases' => Project::PHASES,
        ]);
    }

    /**
     * Update customer links for multiple projects.
     */
    public function updateCustomerLinks(Request $request): RedirectResponse
    {
        if (!Gate::allows('view-all-projects')) {
            abort(403, 'You do not have permission to link projects to customers.');
        }

        $validated = $request->validate([
            'links' => 'required|array',
            'links.*.project_id' => 'required|exists:projects,id',
            'links.*.customer_id' => 'nullable|exists:customers,id',
        ]);

        $updatedCount = 0;

        foreach ($validated['links'] as $link) {
            $project = Project::find($link['project_id']);
            $newCustomerId = $link['customer_id'] ?: null;

            // Only update if changed
            if ($project->customer_id !== $newCustomerId) {
                $project->customer_id = $newCustomerId;
                $project->save();
                $updatedCount++;
            }
        }

        if ($updatedCount > 0) {
            return redirect()
                ->back()
                ->with('success', "Successfully updated customer links for {$updatedCount} project(s).");
        }

        return redirect()
            ->back()
            ->with('info', 'No changes were made.');
    }
}
