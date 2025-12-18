<?php

namespace Modules\Project\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Project\Models\Project;
use Modules\Project\Services\JiraProjectSyncService;

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
        $project->load(['customer', 'invoices.payments', 'invoices.customer']);

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

        // Calculate project cost (lifetime hours * hourly cost per employee)
        // Hourly cost = Employee Salary / 120
        $lifetimeWorklogs = ($startDate && $endDate)
            ? \Modules\Payroll\Models\JiraWorklog::where('issue_key', 'LIKE', $project->code . '-%')->with('employee')->get()
            : $worklogs;

        $projectCost = $lifetimeWorklogs->groupBy('employee_id')->sum(function ($employeeWorklogs) {
            $employee = $employeeWorklogs->first()->employee;
            $totalEmployeeHours = $employeeWorklogs->sum('time_spent_hours');

            if ($employee && $employee->base_salary > 0) {
                $hourlyCost = $employee->base_salary / 120;
                return $hourlyCost * $totalEmployeeHours;
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
}
