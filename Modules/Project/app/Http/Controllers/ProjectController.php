<?php

namespace Modules\Project\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\HR\Models\Employee;
use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectFollowup;
use Modules\Project\Services\JiraProjectSyncService;
use Modules\Project\Services\ProjectFollowupService;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects.
     */
    public function index(Request $request): View
    {
        $query = Project::with('customer');

        // Filter by status (default to active only)
        $status = $request->get('status', 'active');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
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

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $projects = $query->orderBy('name')->paginate(20);
        $customers = Customer::active()->orderBy('name')->get();

        return view('project::projects.index', [
            'projects' => $projects,
            'customers' => $customers,
            'filters' => $request->only(['status', 'needs_report', 'search', 'customer_id']),
        ]);
    }

    /**
     * Show the form for creating a new project.
     */
    public function create(): View
    {
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
    public function show(Request $request, Project $project): View
    {
        $project->load(['customer', 'employees', 'invoices.payments', 'invoices.customer', 'contracts.payments', 'contracts.customer']);

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

        $worklogs = $worklogsQuery->get();

        // Group worklogs by employee
        $worklogsByEmployee = $worklogs->groupBy('employee_id')->map(function ($employeeWorklogs) {
            return [
                'employee' => $employeeWorklogs->first()->employee,
                'total_hours' => $employeeWorklogs->sum('time_spent_hours'),
                'entries' => $employeeWorklogs,
            ];
        });

        // Calculate totals
        $totalHours = $worklogs->sum('time_spent_hours');
        $lifetimeHours = $totalHours; // Same as totalHours when no filter, recalculated below if filtered
        $totalContractValue = $project->invoices->sum('total_amount');
        $totalPaid = $project->invoices->sum('paid_amount');
        $totalRemaining = $totalContractValue - $totalPaid;

        // If date filter is applied, get lifetime hours separately for the stats card
        if ($startDate && $endDate) {
            $lifetimeHours = \Modules\Payroll\Models\JiraWorklog::where('issue_key', 'LIKE', $project->code . '-%')
                ->sum('time_spent_hours');
        }

        // Calculate project cost (lifetime hours * hourly cost per employee * 3)
        // Hourly cost = Employee Salary / 120, then multiplied by 3 for full cost
        $lifetimeWorklogs = ($startDate && $endDate)
            ? \Modules\Payroll\Models\JiraWorklog::where('issue_key', 'LIKE', $project->code . '-%')->with('employee')->get()
            : $worklogs;

        $projectCost = $lifetimeWorklogs->groupBy('employee_id')->sum(function ($employeeWorklogs) {
            $employee = $employeeWorklogs->first()->employee;
            $totalEmployeeHours = $employeeWorklogs->sum('time_spent_hours');

            if ($employee && $employee->base_salary > 0) {
                $hourlyCost = $employee->base_salary / 120;
                return $hourlyCost * $totalEmployeeHours * 3;
            }
            return 0;
        });

        return view('project::projects.show', [
            'project' => $project,
            'worklogs' => $worklogs,
            'worklogsByEmployee' => $worklogsByEmployee,
            'totalHours' => $totalHours,
            'lifetimeHours' => $lifetimeHours,
            'totalContractValue' => $totalContractValue,
            'totalPaid' => $totalPaid,
            'totalRemaining' => $totalRemaining,
            'projectCost' => $projectCost,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Show the form for editing a project.
     */
    public function edit(Project $project): View
    {
        $customers = Customer::active()->orderBy('name')->get();

        return view('project::projects.edit', [
            'project' => $project,
            'customers' => $customers,
        ]);
    }

    /**
     * Update the specified project.
     */
    public function update(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:projects,code,' . $project->id,
            'description' => 'nullable|string',
            'needs_monthly_report' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['needs_monthly_report'] = $request->has('needs_monthly_report');
        $validated['is_active'] = $request->has('is_active');

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
        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project deleted successfully.');
    }

    /**
     * Sync projects from Jira.
     */
    public function syncFromJira(JiraProjectSyncService $jiraService): RedirectResponse
    {
        try {
            $results = $jiraService->syncProjects();

            $message = "Sync completed: {$results['created']} created, {$results['updated']} updated.";
            if (!empty($results['errors'])) {
                $message .= " " . count($results['errors']) . " errors occurred.";
            }

            return redirect()
                ->route('projects.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()
                ->route('projects.index')
                ->with('error', 'Sync failed: ' . $e->getMessage());
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
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'role' => 'required|in:member,lead',
        ]);

        // Check if already assigned
        if ($project->employees()->where('employee_id', $validated['employee_id'])->exists()) {
            return redirect()
                ->back()
                ->with('error', 'Employee is already assigned to this project.');
        }

        $project->employees()->attach($validated['employee_id'], [
            'role' => $validated['role'],
            'auto_assigned' => false,
            'assigned_at' => now(),
        ]);

        $employee = Employee::find($validated['employee_id']);

        return redirect()
            ->back()
            ->with('success', "{$employee->name} has been assigned to the project.");
    }

    /**
     * Update an employee's role in a project.
     */
    public function updateEmployeeRole(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'role' => 'required|in:member,lead',
        ]);

        $project->employees()->updateExistingPivot($validated['employee_id'], [
            'role' => $validated['role'],
        ]);

        return redirect()
            ->back()
            ->with('success', 'Employee role updated successfully.');
    }

    /**
     * Remove an employee from a project.
     */
    public function unassignEmployee(Request $request, Project $project): RedirectResponse
    {
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
        $result = $project->syncEmployeesFromWorklogs();

        $message = "Sync complete: {$result['newly_added']} new employees added from {$result['total_with_worklogs']} with worklogs.";

        return redirect()
            ->back()
            ->with('success', $message);
    }

    /**
     * Display the follow-ups dashboard.
     */
    public function followups(ProjectFollowupService $followupService): View
    {
        $dashboard = $followupService->getFollowupDashboard();

        return view('project::projects.followups', [
            'projects' => $dashboard['projects'],
            'summary' => $dashboard['summary'],
        ]);
    }

    /**
     * Store a new follow-up for a project.
     */
    public function storeFollowup(Request $request, Project $project, ProjectFollowupService $followupService): RedirectResponse
    {
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
}
