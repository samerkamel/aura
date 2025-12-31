<?php

namespace Modules\Project\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectTemplate;
use App\Models\Customer;

class ProjectTemplateController extends Controller
{
    /**
     * Display a listing of templates.
     */
    public function index(): View
    {
        $templates = ProjectTemplate::with('creator')
            ->withCount('projects')
            ->orderByDesc('usage_count')
            ->get();

        $categories = ProjectTemplate::whereNotNull('category')
            ->distinct()
            ->pluck('category');

        return view('project::templates.index', [
            'templates' => $templates,
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for creating a new template.
     */
    public function create(): View
    {
        return view('project::templates.create');
    }

    /**
     * Store a newly created template.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'estimated_duration_days' => 'nullable|integer|min:1',
            'estimated_budget' => 'nullable|numeric|min:0',
            'default_settings' => 'nullable|array',
            'default_settings.estimated_hours' => 'nullable|numeric|min:0',
            'default_settings.hourly_rate' => 'nullable|numeric|min:0',
            'default_settings.billing_type' => 'nullable|in:fixed,hourly,milestone,retainer',
            'milestone_templates' => 'nullable|array',
            'milestone_templates.*.name' => 'required|string|max:255',
            'milestone_templates.*.description' => 'nullable|string',
            'milestone_templates.*.offset_days' => 'nullable|integer',
            'milestone_templates.*.priority' => 'nullable|in:low,medium,high,critical',
            'risk_templates' => 'nullable|array',
            'risk_templates.*.title' => 'required|string|max:255',
            'risk_templates.*.category' => 'nullable|in:technical,resource,schedule,budget,scope,external,other',
            'risk_templates.*.probability' => 'nullable|in:low,medium,high,very_high',
            'risk_templates.*.impact' => 'nullable|in:low,medium,high,critical',
            'task_templates' => 'nullable|array',
            'task_templates.*.name' => 'required|string|max:255',
            'task_templates.*.estimated_hours' => 'nullable|numeric|min:0',
            'team_structure' => 'nullable|array',
        ]);

        $template = ProjectTemplate::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'] ?? null,
            'estimated_duration_days' => $validated['estimated_duration_days'] ?? null,
            'estimated_budget' => $validated['estimated_budget'] ?? null,
            'default_settings' => $validated['default_settings'] ?? null,
            'milestone_templates' => array_filter($validated['milestone_templates'] ?? [], fn($m) => !empty($m['name'])),
            'risk_templates' => array_filter($validated['risk_templates'] ?? [], fn($r) => !empty($r['title'])),
            'task_templates' => array_filter($validated['task_templates'] ?? [], fn($t) => !empty($t['name'])),
            'team_structure' => $validated['team_structure'] ?? null,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('projects.templates.index')
            ->with('success', "Template '{$template->name}' created successfully.");
    }

    /**
     * Display the specified template.
     */
    public function show(ProjectTemplate $template): View
    {
        $template->load(['creator', 'projects']);

        return view('project::templates.show', [
            'template' => $template,
        ]);
    }

    /**
     * Show the form for editing the specified template.
     */
    public function edit(ProjectTemplate $template): View
    {
        return view('project::templates.edit', [
            'template' => $template,
        ]);
    }

    /**
     * Update the specified template.
     */
    public function update(Request $request, ProjectTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'estimated_duration_days' => 'nullable|integer|min:1',
            'estimated_budget' => 'nullable|numeric|min:0',
            'default_settings' => 'nullable|array',
            'milestone_templates' => 'nullable|array',
            'risk_templates' => 'nullable|array',
            'task_templates' => 'nullable|array',
            'team_structure' => 'nullable|array',
        ]);

        $template->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'estimated_duration_days' => $validated['estimated_duration_days'] ?? null,
            'estimated_budget' => $validated['estimated_budget'] ?? null,
            'default_settings' => $validated['default_settings'] ?? null,
            'milestone_templates' => array_filter($validated['milestone_templates'] ?? [], fn($m) => !empty($m['name'])),
            'risk_templates' => array_filter($validated['risk_templates'] ?? [], fn($r) => !empty($r['title'])),
            'task_templates' => array_filter($validated['task_templates'] ?? [], fn($t) => !empty($t['name'])),
            'team_structure' => $validated['team_structure'] ?? null,
        ]);

        return redirect()
            ->route('projects.templates.show', $template)
            ->with('success', 'Template updated successfully.');
    }

    /**
     * Remove the specified template.
     */
    public function destroy(ProjectTemplate $template): RedirectResponse
    {
        $name = $template->name;
        $template->delete();

        return redirect()
            ->route('projects.templates.index')
            ->with('success', "Template '{$name}' deleted successfully.");
    }

    /**
     * Toggle template active status.
     */
    public function toggleActive(ProjectTemplate $template): RedirectResponse
    {
        $template->update(['is_active' => !$template->is_active]);

        $status = $template->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Template {$status} successfully.");
    }

    /**
     * Show form to create project from template.
     */
    public function createProject(ProjectTemplate $template): View
    {
        $customers = Customer::orderBy('display_name')->get();

        return view('project::templates.create-project', [
            'template' => $template,
            'customers' => $customers,
        ]);
    }

    /**
     * Create a new project from template.
     */
    public function storeProject(Request $request, ProjectTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:projects,code',
            'customer_id' => 'nullable|exists:customers,id',
            'project_manager_id' => 'nullable|exists:employees,id',
            'planned_start_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        // Calculate end date based on template duration
        $plannedEndDate = null;
        if ($template->estimated_duration_days && $validated['planned_start_date']) {
            $plannedEndDate = \Carbon\Carbon::parse($validated['planned_start_date'])
                ->addDays($template->estimated_duration_days);
        }

        $project = $template->createProject([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'customer_id' => $validated['customer_id'] ?? null,
            'project_manager_id' => $validated['project_manager_id'] ?? null,
            'planned_start_date' => $validated['planned_start_date'],
            'planned_end_date' => $plannedEndDate,
            'description' => $validated['description'] ?? null,
            'planned_budget' => $template->estimated_budget,
            'is_active' => true,
            'phase' => 'initiation',
        ]);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', "Project '{$project->name}' created from template successfully.");
    }

    /**
     * Create a template from an existing project.
     */
    public function createFromProject(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
        ]);

        $template = ProjectTemplate::createFromProject(
            $project,
            $validated['name'],
            $validated['description']
        );

        if ($validated['category']) {
            $template->update(['category' => $validated['category']]);
        }

        return redirect()
            ->route('projects.templates.show', $template)
            ->with('success', "Template '{$template->name}' created from project successfully.");
    }
}
