<?php

namespace Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class ProjectTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category',
        'is_active',
        'default_settings',
        'milestone_templates',
        'risk_templates',
        'task_templates',
        'team_structure',
        'estimated_duration_days',
        'estimated_budget',
        'created_by',
        'usage_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_settings' => 'array',
        'milestone_templates' => 'array',
        'risk_templates' => 'array',
        'task_templates' => 'array',
        'team_structure' => 'array',
        'estimated_budget' => 'decimal:2',
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'template_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    // Helpers
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Create a new project from this template
     */
    public function createProject(array $baseData): Project
    {
        // Merge template defaults with provided data
        $settings = $this->default_settings ?? [];

        $projectData = array_merge([
            'template_id' => $this->id,
            'estimated_hours' => $settings['estimated_hours'] ?? null,
            'hourly_rate' => $settings['hourly_rate'] ?? null,
            'billing_type' => $settings['billing_type'] ?? 'hourly',
        ], $baseData);

        $project = Project::create($projectData);

        // Create milestones from template
        if ($this->milestone_templates) {
            foreach ($this->milestone_templates as $index => $milestone) {
                $dueDate = isset($milestone['offset_days'])
                    ? ($project->planned_start_date ?? now())->addDays($milestone['offset_days'])
                    : null;

                $project->milestones()->create([
                    'name' => $milestone['name'],
                    'description' => $milestone['description'] ?? null,
                    'due_date' => $dueDate,
                    'status' => 'pending',
                    'priority' => $milestone['priority'] ?? 'medium',
                    'deliverables' => $milestone['deliverables'] ?? null,
                    'created_by' => auth()->id(),
                ]);
            }
        }

        // Create risks from template
        if ($this->risk_templates) {
            foreach ($this->risk_templates as $risk) {
                $project->risks()->create([
                    'title' => $risk['title'],
                    'description' => $risk['description'] ?? null,
                    'category' => $risk['category'] ?? 'other',
                    'probability' => $risk['probability'] ?? 'medium',
                    'impact' => $risk['impact'] ?? 'medium',
                    'status' => 'identified',
                    'mitigation_plan' => $risk['mitigation_plan'] ?? null,
                    'identified_date' => now(),
                    'created_by' => auth()->id(),
                ]);
            }
        }

        // Create time estimates from template
        if ($this->task_templates) {
            foreach ($this->task_templates as $task) {
                $project->timeEstimates()->create([
                    'task_name' => $task['name'],
                    'description' => $task['description'] ?? null,
                    'estimated_hours' => $task['estimated_hours'] ?? 0,
                    'status' => 'not_started',
                    'created_by' => auth()->id(),
                ]);
            }
        }

        $this->incrementUsage();

        return $project;
    }

    /**
     * Create a template from an existing project
     */
    public static function createFromProject(Project $project, string $name, ?string $description = null): self
    {
        $milestoneTemplates = $project->milestones->map(function ($milestone) use ($project) {
            return [
                'name' => $milestone->name,
                'description' => $milestone->description,
                'priority' => $milestone->priority,
                'deliverables' => $milestone->deliverables,
                'offset_days' => $milestone->due_date && $project->planned_start_date
                    ? $project->planned_start_date->diffInDays($milestone->due_date)
                    : null,
            ];
        })->toArray();

        $riskTemplates = $project->risks->map(function ($risk) {
            return [
                'title' => $risk->title,
                'description' => $risk->description,
                'category' => $risk->category,
                'probability' => $risk->probability,
                'impact' => $risk->impact,
                'mitigation_plan' => $risk->mitigation_plan,
            ];
        })->toArray();

        $taskTemplates = $project->timeEstimates->map(function ($estimate) {
            return [
                'name' => $estimate->task_name,
                'description' => $estimate->description,
                'estimated_hours' => $estimate->estimated_hours,
            ];
        })->toArray();

        return self::create([
            'name' => $name,
            'description' => $description ?? "Template created from project: {$project->name}",
            'is_active' => true,
            'default_settings' => [
                'estimated_hours' => $project->estimated_hours,
                'hourly_rate' => $project->hourly_rate,
                'billing_type' => $project->billing_type,
            ],
            'milestone_templates' => $milestoneTemplates,
            'risk_templates' => $riskTemplates,
            'task_templates' => $taskTemplates,
            'estimated_duration_days' => $project->planned_start_date && $project->planned_end_date
                ? $project->planned_start_date->diffInDays($project->planned_end_date)
                : null,
            'estimated_budget' => $project->planned_budget,
            'created_by' => auth()->id(),
        ]);
    }
}
