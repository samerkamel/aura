<?php

namespace Modules\Project\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;
use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectMilestone;
use Modules\Project\Models\ProjectRisk;
use Modules\Project\Models\ProjectTimeEstimate;
use Modules\Project\Models\ProjectDependency;
use Modules\HR\Models\Employee;

class ProjectPlanningController extends Controller
{
    /**
     * Show project milestones.
     */
    public function milestones(Project $project): View
    {
        $project->load(['milestones.assignee', 'milestones.creator']);

        return view('project::projects.planning.milestones', [
            'project' => $project,
            'milestones' => $project->milestones,
            'employees' => Employee::active()->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a new milestone.
     */
    public function storeMilestone(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'priority' => 'required|in:low,medium,high,critical',
            'assigned_to' => 'nullable|exists:employees,id',
            'deliverables' => 'nullable|array',
            'deliverables.*' => 'string|max:255',
        ]);

        $project->milestones()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'due_date' => $validated['due_date'],
            'priority' => $validated['priority'],
            'assigned_to' => $validated['assigned_to'] ?? null,
            'deliverables' => array_filter($validated['deliverables'] ?? []),
            'status' => 'pending',
            'progress_percentage' => 0,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Milestone created successfully.');
    }

    /**
     * Update a milestone.
     */
    public function updateMilestone(Request $request, Project $project, ProjectMilestone $milestone): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'priority' => 'required|in:low,medium,high,critical',
            'status' => 'required|in:pending,in_progress,completed,on_hold,cancelled',
            'progress_percentage' => 'required|numeric|min:0|max:100',
            'assigned_to' => 'nullable|exists:employees,id',
            'deliverables' => 'nullable|array',
            'deliverables.*' => 'string|max:255',
        ]);

        $milestone->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'due_date' => $validated['due_date'],
            'priority' => $validated['priority'],
            'status' => $validated['status'],
            'progress_percentage' => $validated['progress_percentage'],
            'assigned_to' => $validated['assigned_to'] ?? null,
            'deliverables' => array_filter($validated['deliverables'] ?? []),
            'completed_date' => $validated['status'] === 'completed' ? now() : null,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Milestone updated successfully.');
    }

    /**
     * Delete a milestone.
     */
    public function destroyMilestone(Project $project, ProjectMilestone $milestone): RedirectResponse
    {
        $milestone->delete();

        return redirect()
            ->back()
            ->with('success', 'Milestone deleted successfully.');
    }

    /**
     * Show project risks.
     */
    public function risks(Project $project): View
    {
        $project->load(['risks.owner', 'risks.creator']);

        return view('project::projects.planning.risks', [
            'project' => $project,
            'risks' => $project->risks,
            'employees' => Employee::active()->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a new risk.
     */
    public function storeRisk(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:technical,resource,schedule,budget,scope,external,other',
            'probability' => 'required|in:low,medium,high,very_high',
            'impact' => 'required|in:low,medium,high,critical',
            'mitigation_plan' => 'nullable|string',
            'contingency_plan' => 'nullable|string',
            'owner_id' => 'nullable|exists:employees,id',
            'target_resolution_date' => 'nullable|date',
            'potential_cost_impact' => 'nullable|numeric|min:0',
            'potential_delay_days' => 'nullable|integer|min:0',
        ]);

        $project->risks()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'],
            'probability' => $validated['probability'],
            'impact' => $validated['impact'],
            'mitigation_plan' => $validated['mitigation_plan'] ?? null,
            'contingency_plan' => $validated['contingency_plan'] ?? null,
            'owner_id' => $validated['owner_id'] ?? null,
            'target_resolution_date' => $validated['target_resolution_date'] ?? null,
            'potential_cost_impact' => $validated['potential_cost_impact'] ?? null,
            'potential_delay_days' => $validated['potential_delay_days'] ?? null,
            'identified_date' => now(),
            'status' => 'identified',
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Risk added successfully.');
    }

    /**
     * Update a risk.
     */
    public function updateRisk(Request $request, Project $project, ProjectRisk $risk): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:technical,resource,schedule,budget,scope,external,other',
            'probability' => 'required|in:low,medium,high,very_high',
            'impact' => 'required|in:low,medium,high,critical',
            'status' => 'required|in:identified,analyzing,mitigating,monitoring,resolved,accepted',
            'mitigation_plan' => 'nullable|string',
            'contingency_plan' => 'nullable|string',
            'owner_id' => 'nullable|exists:employees,id',
            'target_resolution_date' => 'nullable|date',
            'potential_cost_impact' => 'nullable|numeric|min:0',
            'potential_delay_days' => 'nullable|integer|min:0',
        ]);

        $risk->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'],
            'probability' => $validated['probability'],
            'impact' => $validated['impact'],
            'status' => $validated['status'],
            'mitigation_plan' => $validated['mitigation_plan'] ?? null,
            'contingency_plan' => $validated['contingency_plan'] ?? null,
            'owner_id' => $validated['owner_id'] ?? null,
            'target_resolution_date' => $validated['target_resolution_date'] ?? null,
            'potential_cost_impact' => $validated['potential_cost_impact'] ?? null,
            'potential_delay_days' => $validated['potential_delay_days'] ?? null,
            'resolved_date' => $validated['status'] === 'resolved' ? now() : null,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Risk updated successfully.');
    }

    /**
     * Delete a risk.
     */
    public function destroyRisk(Project $project, ProjectRisk $risk): RedirectResponse
    {
        $risk->delete();

        return redirect()
            ->back()
            ->with('success', 'Risk deleted successfully.');
    }

    /**
     * Show project time estimates.
     */
    public function timeEstimates(Project $project): View
    {
        $project->load(['timeEstimates.assignee', 'timeEstimates.milestone', 'milestones']);

        // Calculate summary stats
        $estimates = $project->timeEstimates;
        $summary = [
            'total_estimated' => $estimates->sum('estimated_hours'),
            'total_actual' => $estimates->sum('actual_hours'),
            'total_remaining' => $estimates->sum('remaining_hours'),
            'variance' => $estimates->sum('variance_hours'),
            'completed_count' => $estimates->where('status', 'completed')->count(),
            'in_progress_count' => $estimates->where('status', 'in_progress')->count(),
            'not_started_count' => $estimates->where('status', 'not_started')->count(),
        ];

        return view('project::projects.planning.time-estimates', [
            'project' => $project,
            'estimates' => $estimates,
            'summary' => $summary,
            'employees' => Employee::active()->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a new time estimate.
     */
    public function storeTimeEstimate(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'task_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'milestone_id' => 'nullable|exists:project_milestones,id',
            'estimated_hours' => 'required|numeric|min:0',
            'assigned_to' => 'nullable|exists:employees,id',
            'estimated_start_date' => 'nullable|date',
            'estimated_end_date' => 'nullable|date|after_or_equal:estimated_start_date',
            'jira_issue_key' => 'nullable|string|max:50',
        ]);

        $project->timeEstimates()->create([
            'task_name' => $validated['task_name'],
            'description' => $validated['description'] ?? null,
            'milestone_id' => $validated['milestone_id'] ?? null,
            'estimated_hours' => $validated['estimated_hours'],
            'assigned_to' => $validated['assigned_to'] ?? null,
            'estimated_start_date' => $validated['estimated_start_date'] ?? null,
            'estimated_end_date' => $validated['estimated_end_date'] ?? null,
            'jira_issue_key' => $validated['jira_issue_key'] ?? null,
            'status' => 'not_started',
            'actual_hours' => 0,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Time estimate added successfully.');
    }

    /**
     * Update a time estimate.
     */
    public function updateTimeEstimate(Request $request, Project $project, ProjectTimeEstimate $estimate): RedirectResponse
    {
        $validated = $request->validate([
            'task_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'milestone_id' => 'nullable|exists:project_milestones,id',
            'estimated_hours' => 'required|numeric|min:0',
            'actual_hours' => 'required|numeric|min:0',
            'status' => 'required|in:not_started,in_progress,completed,on_hold',
            'assigned_to' => 'nullable|exists:employees,id',
            'estimated_start_date' => 'nullable|date',
            'estimated_end_date' => 'nullable|date|after_or_equal:estimated_start_date',
            'actual_start_date' => 'nullable|date',
            'actual_end_date' => 'nullable|date|after_or_equal:actual_start_date',
            'jira_issue_key' => 'nullable|string|max:50',
        ]);

        $estimate->update([
            'task_name' => $validated['task_name'],
            'description' => $validated['description'] ?? null,
            'milestone_id' => $validated['milestone_id'] ?? null,
            'estimated_hours' => $validated['estimated_hours'],
            'actual_hours' => $validated['actual_hours'],
            'status' => $validated['status'],
            'assigned_to' => $validated['assigned_to'] ?? null,
            'estimated_start_date' => $validated['estimated_start_date'] ?? null,
            'estimated_end_date' => $validated['estimated_end_date'] ?? null,
            'actual_start_date' => $validated['actual_start_date'] ?? null,
            'actual_end_date' => $validated['actual_end_date'] ?? null,
            'jira_issue_key' => $validated['jira_issue_key'] ?? null,
        ]);

        // Update project completion percentage
        $project->updateCompletionPercentage();

        return redirect()
            ->back()
            ->with('success', 'Time estimate updated successfully.');
    }

    /**
     * Delete a time estimate.
     */
    public function destroyTimeEstimate(Project $project, ProjectTimeEstimate $estimate): RedirectResponse
    {
        $estimate->delete();

        // Update project completion percentage
        $project->updateCompletionPercentage();

        return redirect()
            ->back()
            ->with('success', 'Time estimate deleted successfully.');
    }

    /**
     * Show project dependencies.
     */
    public function dependencies(Project $project): View
    {
        $project->load(['dependencies', 'dependents', 'dependencyRecords.dependsOnProject']);

        // Get available projects for dependencies (excluding self)
        $availableProjects = Project::where('id', '!=', $project->id)
            ->active()
            ->orderBy('name')
            ->get();

        return view('project::projects.planning.dependencies', [
            'project' => $project,
            'availableProjects' => $availableProjects,
        ]);
    }

    /**
     * Store a new dependency.
     */
    public function storeDependency(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'depends_on_project_id' => 'required|exists:projects,id|different:project',
            'dependency_type' => 'required|in:finish_to_start,start_to_start,finish_to_finish,start_to_finish',
            'lag_days' => 'nullable|integer',
            'description' => 'nullable|string|max:500',
        ]);

        // Check if dependency already exists
        $existing = ProjectDependency::where('project_id', $project->id)
            ->where('depends_on_project_id', $validated['depends_on_project_id'])
            ->exists();

        if ($existing) {
            return redirect()
                ->back()
                ->with('error', 'This dependency already exists.');
        }

        ProjectDependency::create([
            'project_id' => $project->id,
            'depends_on_project_id' => $validated['depends_on_project_id'],
            'dependency_type' => $validated['dependency_type'],
            'lag_days' => $validated['lag_days'] ?? 0,
            'description' => $validated['description'] ?? null,
            'status' => 'active',
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Dependency added successfully.');
    }

    /**
     * Update a dependency.
     */
    public function updateDependency(Request $request, Project $project, ProjectDependency $dependency): RedirectResponse
    {
        $validated = $request->validate([
            'dependency_type' => 'required|in:finish_to_start,start_to_start,finish_to_finish,start_to_finish',
            'lag_days' => 'nullable|integer',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:active,resolved,broken',
        ]);

        $dependency->update([
            'dependency_type' => $validated['dependency_type'],
            'lag_days' => $validated['lag_days'] ?? 0,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
        ]);

        return redirect()
            ->back()
            ->with('success', 'Dependency updated successfully.');
    }

    /**
     * Delete a dependency.
     */
    public function destroyDependency(Project $project, ProjectDependency $dependency): RedirectResponse
    {
        $dependency->delete();

        return redirect()
            ->back()
            ->with('success', 'Dependency removed successfully.');
    }

    /**
     * Show project timeline/Gantt view.
     */
    public function timeline(Project $project): View
    {
        $project->load(['milestones', 'timeEstimates.assignee', 'dependencies.dependsOnProject']);

        return view('project::projects.planning.timeline', [
            'project' => $project,
        ]);
    }
}
