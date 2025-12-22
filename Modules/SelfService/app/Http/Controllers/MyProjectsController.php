<?php

namespace Modules\SelfService\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Project\Models\Project;

class MyProjectsController extends Controller
{
    /**
     * Display the list of projects assigned to the current employee.
     */
    public function index(Request $request): View
    {
        $employee = auth()->user()->employee;

        if (!$employee) {
            abort(403, 'No employee profile linked to your account.');
        }

        $projects = $employee->projects()
            ->with('customer')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('selfservice::my-projects.index', [
            'projects' => $projects,
            'employee' => $employee,
        ]);
    }

    /**
     * Display a specific project the employee is assigned to.
     */
    public function show(Request $request, Project $project): View
    {
        $employee = auth()->user()->employee;

        if (!$employee) {
            abort(403, 'No employee profile linked to your account.');
        }

        // Check if employee is assigned to this project
        if (!$project->hasEmployee($employee)) {
            abort(403, 'You are not assigned to this project.');
        }

        $project->load(['customer', 'employees']);

        // Get worklogs with optional date filtering
        $startDate = $request->filled('start_date') ? $request->start_date : null;
        $endDate = $request->filled('end_date') ? $request->end_date : null;

        $worklogsQuery = \Modules\Payroll\Models\JiraWorklog::where('issue_key', 'LIKE', $project->code . '-%')
            ->with('employee')
            ->orderBy('worklog_started', 'desc');

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
        $lifetimeHours = $totalHours;

        if ($startDate && $endDate) {
            $lifetimeHours = \Modules\Payroll\Models\JiraWorklog::where('issue_key', 'LIKE', $project->code . '-%')
                ->sum('time_spent_hours');
        }

        return view('selfservice::my-projects.show', [
            'project' => $project,
            'employee' => $employee,
            'worklogs' => $worklogs,
            'worklogsByEmployee' => $worklogsByEmployee,
            'totalHours' => $totalHours,
            'lifetimeHours' => $lifetimeHours,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
