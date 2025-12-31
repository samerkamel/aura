@extends('layouts/layoutMaster')

@section('title', 'Template - ' . $template->name)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Page Header -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center">
              <div class="avatar avatar-lg me-4">
                <span class="avatar-initial rounded-circle bg-label-{{ $template->is_active ? 'primary' : 'secondary' }}">
                  <i class="ti ti-template ti-lg"></i>
                </span>
              </div>
              <div>
                <h4 class="mb-0">{{ $template->name }}</h4>
                <p class="text-muted mb-0">
                  @if($template->category)
                    <span class="badge bg-label-info me-2">{{ $template->category }}</span>
                  @endif
                  <span class="badge bg-label-{{ $template->is_active ? 'success' : 'secondary' }}">
                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                  </span>
                </p>
              </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
              <a href="{{ route('projects.templates.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Back to Templates
              </a>
              <a href="{{ route('projects.templates.edit', $template) }}" class="btn btn-outline-primary">
                <i class="ti ti-edit me-1"></i>Edit
              </a>
              <a href="{{ route('projects.templates.create-project', $template) }}"
                 class="btn btn-primary {{ !$template->is_active ? 'disabled' : '' }}">
                <i class="ti ti-plus me-1"></i>Create Project
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  @if (session('success'))
    <div class="alert alert-success alert-dismissible mb-4" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="row">
    <!-- Template Details -->
    <div class="col-lg-8">
      <!-- Description -->
      @if($template->description)
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0"><i class="ti ti-info-circle me-2"></i>Description</h5>
        </div>
        <div class="card-body">
          <p class="mb-0">{{ $template->description }}</p>
        </div>
      </div>
      @endif

      <!-- Milestones -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="ti ti-flag me-2"></i>Milestone Templates
            @if(is_array($template->milestone_templates) && count($template->milestone_templates) > 0)
              <span class="badge bg-primary">{{ count($template->milestone_templates) }}</span>
            @endif
          </h5>
        </div>
        <div class="card-body">
          @if(is_array($template->milestone_templates) && count($template->milestone_templates) > 0)
            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Day Offset</th>
                    <th>Priority</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($template->milestone_templates as $milestone)
                    <tr>
                      <td><strong>{{ $milestone['name'] ?? 'Unnamed' }}</strong></td>
                      <td>
                        @if(isset($milestone['offset_days']))
                          Day {{ $milestone['offset_days'] }}
                        @else
                          -
                        @endif
                      </td>
                      <td>
                        @php
                          $priority = $milestone['priority'] ?? 'medium';
                          $priorityColors = ['low' => 'secondary', 'medium' => 'info', 'high' => 'warning', 'critical' => 'danger'];
                        @endphp
                        <span class="badge bg-label-{{ $priorityColors[$priority] ?? 'secondary' }}">
                          {{ ucfirst($priority) }}
                        </span>
                      </td>
                      <td class="text-muted">{{ Str::limit($milestone['description'] ?? '', 50) }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <p class="text-muted mb-0">No milestone templates defined.</p>
          @endif
        </div>
      </div>

      <!-- Risks -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="ti ti-alert-triangle me-2"></i>Risk Templates
            @if(is_array($template->risk_templates) && count($template->risk_templates) > 0)
              <span class="badge bg-warning">{{ count($template->risk_templates) }}</span>
            @endif
          </h5>
        </div>
        <div class="card-body">
          @if(is_array($template->risk_templates) && count($template->risk_templates) > 0)
            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Probability</th>
                    <th>Impact</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($template->risk_templates as $risk)
                    <tr>
                      <td><strong>{{ $risk['title'] ?? 'Unnamed' }}</strong></td>
                      <td>
                        <span class="badge bg-label-secondary">
                          {{ ucfirst($risk['category'] ?? 'other') }}
                        </span>
                      </td>
                      <td>
                        @php
                          $prob = $risk['probability'] ?? 'medium';
                          $probColors = ['low' => 'success', 'medium' => 'info', 'high' => 'warning', 'very_high' => 'danger'];
                        @endphp
                        <span class="badge bg-label-{{ $probColors[$prob] ?? 'secondary' }}">
                          {{ str_replace('_', ' ', ucfirst($prob)) }}
                        </span>
                      </td>
                      <td>
                        @php
                          $impact = $risk['impact'] ?? 'medium';
                          $impactColors = ['low' => 'success', 'medium' => 'info', 'high' => 'warning', 'critical' => 'danger'];
                        @endphp
                        <span class="badge bg-label-{{ $impactColors[$impact] ?? 'secondary' }}">
                          {{ ucfirst($impact) }}
                        </span>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <p class="text-muted mb-0">No risk templates defined.</p>
          @endif
        </div>
      </div>

      <!-- Tasks -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="ti ti-list-check me-2"></i>Task Templates
            @if(is_array($template->task_templates) && count($template->task_templates) > 0)
              <span class="badge bg-info">{{ count($template->task_templates) }}</span>
            @endif
          </h5>
        </div>
        <div class="card-body">
          @if(is_array($template->task_templates) && count($template->task_templates) > 0)
            <div class="list-group list-group-flush">
              @foreach($template->task_templates as $task)
                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                  <span>{{ $task['name'] ?? 'Unnamed Task' }}</span>
                  @if(isset($task['estimated_hours']) && $task['estimated_hours'] > 0)
                    <span class="badge bg-label-secondary">{{ $task['estimated_hours'] }}h</span>
                  @endif
                </div>
              @endforeach
            </div>
            @php
              $totalHours = collect($template->task_templates)->sum('estimated_hours');
            @endphp
            @if($totalHours > 0)
              <div class="mt-3 pt-3 border-top">
                <strong>Total Estimated Hours:</strong> {{ $totalHours }}h
              </div>
            @endif
          @else
            <p class="text-muted mb-0">No task templates defined.</p>
          @endif
        </div>
      </div>

      <!-- Projects Created -->
      @if($template->projects && $template->projects->count() > 0)
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="ti ti-folder me-2"></i>Projects Created from Template
            <span class="badge bg-secondary">{{ $template->projects->count() }}</span>
          </h5>
        </div>
        <div class="card-body">
          <div class="list-group list-group-flush">
            @foreach($template->projects->take(5) as $project)
              <a href="{{ route('projects.show', $project) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0">
                <div>
                  <strong>{{ $project->name }}</strong>
                  <span class="text-muted ms-2">{{ $project->code }}</span>
                </div>
                <span class="badge bg-label-{{ $project->is_active ? 'success' : 'secondary' }}">
                  {{ $project->is_active ? 'Active' : 'Inactive' }}
                </span>
              </a>
            @endforeach
          </div>
          @if($template->projects->count() > 5)
            <p class="text-muted mb-0 mt-3">And {{ $template->projects->count() - 5 }} more projects...</p>
          @endif
        </div>
      </div>
      @endif
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
      <!-- Stats -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0"><i class="ti ti-chart-bar me-2"></i>Template Stats</h5>
        </div>
        <div class="card-body">
          <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between px-0">
              <span>Times Used</span>
              <strong>{{ $template->usage_count }}</strong>
            </li>
            @if($template->estimated_duration_days)
              <li class="list-group-item d-flex justify-content-between px-0">
                <span>Duration</span>
                <strong>{{ $template->estimated_duration_days }} days</strong>
              </li>
            @endif
            @if($template->estimated_budget)
              <li class="list-group-item d-flex justify-content-between px-0">
                <span>Budget</span>
                <strong>${{ number_format($template->estimated_budget, 2) }}</strong>
              </li>
            @endif
            @if(isset($template->default_settings['estimated_hours']))
              <li class="list-group-item d-flex justify-content-between px-0">
                <span>Estimated Hours</span>
                <strong>{{ $template->default_settings['estimated_hours'] }}h</strong>
              </li>
            @endif
            @if(isset($template->default_settings['hourly_rate']))
              <li class="list-group-item d-flex justify-content-between px-0">
                <span>Hourly Rate</span>
                <strong>${{ number_format($template->default_settings['hourly_rate'], 2) }}</strong>
              </li>
            @endif
            @if(isset($template->default_settings['billing_type']))
              <li class="list-group-item d-flex justify-content-between px-0">
                <span>Billing Type</span>
                <strong>{{ ucfirst($template->default_settings['billing_type']) }}</strong>
              </li>
            @endif
          </ul>
        </div>
      </div>

      <!-- Template Summary -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0"><i class="ti ti-box me-2"></i>Contents Summary</h5>
        </div>
        <div class="card-body">
          <div class="d-flex flex-column gap-2">
            <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
              <span><i class="ti ti-flag me-2 text-primary"></i>Milestones</span>
              <span class="badge bg-primary">
                {{ is_array($template->milestone_templates) ? count($template->milestone_templates) : 0 }}
              </span>
            </div>
            <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
              <span><i class="ti ti-alert-triangle me-2 text-warning"></i>Risks</span>
              <span class="badge bg-warning">
                {{ is_array($template->risk_templates) ? count($template->risk_templates) : 0 }}
              </span>
            </div>
            <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
              <span><i class="ti ti-list-check me-2 text-info"></i>Tasks</span>
              <span class="badge bg-info">
                {{ is_array($template->task_templates) ? count($template->task_templates) : 0 }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Metadata -->
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0"><i class="ti ti-info-square me-2"></i>Metadata</h5>
        </div>
        <div class="card-body">
          <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between px-0">
              <span>Created By</span>
              <strong>{{ $template->creator?->name ?? 'Unknown' }}</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0">
              <span>Created</span>
              <strong>{{ $template->created_at->format('M d, Y') }}</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0">
              <span>Last Updated</span>
              <strong>{{ $template->updated_at->format('M d, Y') }}</strong>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
