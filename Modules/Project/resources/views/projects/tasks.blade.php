@use('Illuminate\Support\Str')
@extends('layouts/layoutMaster')

@section('title', $project->name . ' - Tasks')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-style')
<style>
  .project-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 0.5rem;
    padding: 1.5rem;
    color: white;
    margin-bottom: 1.5rem;
  }
  .project-code {
    background: rgba(255,255,255,0.2);
    padding: 0.25rem 0.75rem;
    border-radius: 0.25rem;
    font-family: monospace;
    font-size: 0.9rem;
  }
  .kanban-board {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    padding-bottom: 1rem;
  }
  .kanban-column {
    flex: 0 0 280px;
    min-width: 280px;
  }
  .kanban-column-header {
    padding: 0.75rem 1rem;
    border-radius: 0.5rem 0.5rem 0 0;
    font-weight: 600;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .kanban-column-header.todo {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    color: white;
  }
  .kanban-column-header.in-progress {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
  }
  .kanban-column-header.done {
    background: linear-gradient(135deg, #28a745 0%, #218838 100%);
    color: white;
  }
  .kanban-column-body {
    background: #f8f9fa;
    border-radius: 0 0 0.5rem 0.5rem;
    padding: 0.75rem;
    min-height: 400px;
    max-height: 600px;
    overflow-y: auto;
  }
  .kanban-card {
    background: white;
    border-radius: 0.375rem;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: box-shadow 0.2s;
    cursor: pointer;
  }
  .kanban-card:hover {
    box-shadow: 0 3px 6px rgba(0,0,0,0.15);
  }
  .issue-key {
    font-family: monospace;
    font-size: 0.8rem;
    color: #6c757d;
  }
  .issue-summary {
    font-size: 0.9rem;
    margin: 0.25rem 0;
    line-height: 1.3;
  }
  .issue-meta {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    align-items: center;
  }
  .issue-type-icon {
    width: 18px;
    height: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 3px;
    font-size: 10px;
  }
  .issue-assignee-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #6c757d;
    color: white;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 600;
  }
  .summary-card {
    text-align: center;
    padding: 1rem;
  }
  .summary-value {
    font-size: 1.5rem;
    font-weight: 700;
  }
  .issue-overdue {
    border-left: 3px solid #dc3545;
  }
  .progress-ring {
    width: 60px;
    height: 60px;
  }
  /* List view enhancements */
  .summary-link {
    color: inherit;
    text-decoration: none;
    cursor: pointer;
  }
  .summary-link:hover {
    color: #667eea;
  }
  .issue-labels {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    margin-top: 0.25rem;
  }
  .issue-label {
    font-size: 0.7rem;
    padding: 0.1rem 0.4rem;
    background: #e9ecef;
    border-radius: 0.25rem;
    color: #495057;
  }
  .epic-link {
    font-size: 0.75rem;
    color: #7c3aed;
    font-weight: 500;
  }
  /* Inline editing */
  .editable-field {
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    transition: background-color 0.15s;
  }
  .editable-field:hover {
    background: #f0f4ff;
  }
  .inline-edit-select {
    min-width: 120px;
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
  }
  .inline-edit-input {
    width: 100px;
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
  }
  .inline-edit-date {
    width: 130px;
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
  }
  .saving-indicator {
    display: none;
  }
  .saving-indicator.active {
    display: inline-block;
  }
  .story-points-display {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 24px;
    background: #e9ecef;
    border-radius: 50%;
    font-size: 0.75rem;
    font-weight: 600;
  }
  .list-table th {
    font-size: 0.8rem;
    text-transform: uppercase;
    color: #6c757d;
    font-weight: 600;
  }
  .list-table td {
    vertical-align: middle;
    font-size: 0.875rem;
  }
  .component-badge {
    font-size: 0.7rem;
    padding: 0.15rem 0.4rem;
    background: #dbeafe;
    color: #1d4ed8;
    border-radius: 0.25rem;
  }
  .dates-small {
    font-size: 0.75rem;
    color: #6c757d;
  }
  /* Sortable headers */
  .sortable {
    cursor: pointer;
    user-select: none;
    white-space: nowrap;
  }
  .sortable:hover {
    background: #e9ecef;
  }
  .sortable .sort-icon {
    opacity: 0.3;
    margin-left: 0.25rem;
  }
  .sortable.sorted-asc .sort-icon,
  .sortable.sorted-desc .sort-icon {
    opacity: 1;
  }
  .sortable.sorted-asc .sort-icon::before {
    content: "\eb37";
  }
  .sortable.sorted-desc .sort-icon::before {
    content: "\eb3a";
  }
</style>
@endsection

@section('content')
<div class="flex-grow-1 container-p-y px-4">
  @if (session('success'))
    <div class="alert alert-success alert-dismissible mb-4" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if (session('error'))
    <div class="alert alert-danger alert-dismissible mb-4" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if (session('warning'))
    <div class="alert alert-warning alert-dismissible mb-4" role="alert">
      {{ session('warning') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- Project Header -->
  <div class="project-header">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <span class="project-code">{{ $project->code }}</span>
        <h4 class="mb-0 mt-2">{{ $project->name }}</h4>
        @if($project->customer)
          <small class="opacity-75">{{ $project->customer->display_name }}</small>
        @endif
      </div>
      <div class="d-flex gap-2">
        <div class="btn-group">
          <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createTaskModal">
            <i class="ti ti-plus me-1"></i>Create Task
          </button>
          <a href="{{ route('projects.bulk-create-tasks', $project) }}" class="btn btn-light btn-sm" title="Bulk create tasks">
            <i class="ti ti-table"></i>
          </a>
        </div>
        <form action="{{ route('projects.sync-issues', $project) }}" method="POST" class="d-inline">
          @csrf
          <button type="submit" class="btn btn-outline-light btn-sm">
            <i class="ti ti-refresh me-1"></i>Sync from Jira
          </button>
        </form>
        <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-light btn-sm">
          <i class="ti ti-arrow-left me-1"></i>Back to Project
        </a>
      </div>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
      <div class="card h-100 border-start border-4 border-primary">
        <div class="card-body summary-card">
          <div class="summary-value text-primary">{{ $summary['total'] }}</div>
          <small class="text-muted">Total Issues</small>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
      <div class="card h-100 border-start border-4 border-secondary">
        <div class="card-body summary-card">
          <div class="summary-value text-secondary">{{ $summary['by_status']['todo'] }}</div>
          <small class="text-muted">To Do</small>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
      <div class="card h-100 border-start border-4 border-info">
        <div class="card-body summary-card">
          <div class="summary-value text-info">{{ $summary['by_status']['in_progress'] }}</div>
          <small class="text-muted">In Progress</small>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6">
      <div class="card h-100 border-start border-4 border-success">
        <div class="card-body summary-card d-flex align-items-center justify-content-between">
          <div>
            <div class="summary-value text-success">{{ $summary['by_status']['done'] }}</div>
            <small class="text-muted">Done</small>
          </div>
          <div class="text-end">
            <div class="fs-4 fw-bold text-success">{{ $summary['completion_percentage'] }}%</div>
            <small class="text-muted">Complete</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- View Toggle and Filters -->
  <div class="card mb-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-3">
          <div class="btn-group" role="group">
            <a href="{{ route('projects.tasks', ['project' => $project, 'view' => 'kanban']) }}"
               class="btn {{ $view === 'kanban' ? 'btn-primary' : 'btn-outline-primary' }}">
              <i class="ti ti-layout-kanban me-1"></i>Kanban
            </a>
            <a href="{{ route('projects.tasks', ['project' => $project, 'view' => 'list']) }}"
               class="btn {{ $view === 'list' ? 'btn-primary' : 'btn-outline-primary' }}">
              <i class="ti ti-list me-1"></i>List
            </a>
          </div>
          @if($summary['last_synced'])
            <small class="text-muted">
              <i class="ti ti-clock me-1"></i>Last synced: {{ \Carbon\Carbon::parse($summary['last_synced'])->diffForHumans() }}
            </small>
          @endif
        </div>
        @if($view === 'list')
        <div>
          @php
            $hasFilters = !empty($filters['search']) || !empty($filters['exclude_statuses']) || !empty($filters['priorities']) || !empty($filters['assignees']);
          @endphp
          @if($hasFilters)
            <a href="{{ route('projects.tasks', ['project' => $project, 'view' => 'list']) }}" class="btn btn-sm btn-outline-danger">
              <i class="ti ti-x me-1"></i>Clear Filters
            </a>
          @endif
        </div>
        @endif
      </div>

      @if($view === 'list')
      <form method="GET" id="filterForm">
        <input type="hidden" name="view" value="list">
        @if(request('sort'))
          <input type="hidden" name="sort" value="{{ request('sort') }}">
          <input type="hidden" name="direction" value="{{ request('direction', 'asc') }}">
        @endif
        <div class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Search</label>
            <input type="text" name="search" class="form-control form-control-sm"
                   placeholder="Search key or summary..." value="{{ $filters['search'] ?? '' }}">
          </div>
          <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Exclude Statuses</label>
            <select name="exclude_statuses[]" class="form-select form-select-sm select2-statuses" multiple data-placeholder="Select statuses to hide...">
              @foreach($statuses as $status)
                <option value="{{ $status }}" {{ in_array($status, $filters['exclude_statuses'] ?? []) ? 'selected' : '' }}>
                  {{ $status }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Priority</label>
            <select name="priorities[]" class="form-select form-select-sm select2-priorities" multiple data-placeholder="All priorities">
              @foreach($priorities as $priority)
                <option value="{{ $priority }}" {{ in_array($priority, $filters['priorities'] ?? []) ? 'selected' : '' }}>
                  {{ $priority }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Assignee</label>
            <select name="assignees[]" class="form-select form-select-sm select2-assignees" multiple data-placeholder="All assignees">
              <option value="unassigned" {{ in_array('unassigned', $filters['assignees'] ?? []) ? 'selected' : '' }}>Unassigned</option>
              @foreach($assignees as $assignee)
                <option value="{{ $assignee->id }}" {{ in_array((string)$assignee->id, $filters['assignees'] ?? []) ? 'selected' : '' }}>
                  {{ $assignee->name }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-sm btn-primary w-100">
              <i class="ti ti-filter me-1"></i>Apply Filters
            </button>
          </div>
        </div>
      </form>
      @endif
    </div>
  </div>

  @if($summary['total'] === 0)
    <!-- Empty State -->
    <div class="card">
      <div class="card-body text-center py-5">
        <i class="ti ti-clipboard-list ti-xl text-muted mb-3"></i>
        <h5>No Issues Found</h5>
        <p class="text-muted mb-4">No Jira issues have been synced for this project yet.</p>
        <form action="{{ route('projects.sync-issues', $project) }}" method="POST" class="d-inline">
          @csrf
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-refresh me-1"></i>Sync Issues from Jira
          </button>
        </form>
      </div>
    </div>
  @elseif($view === 'kanban')
    <!-- Kanban Board -->
    <div class="kanban-board">
      @forelse($issues as $statusKey => $column)
        @php
          $categoryClass = match($column['category']) {
            'new' => 'todo',
            'indeterminate' => 'in-progress',
            'done' => 'done',
            default => 'in-progress'
          };
          $categoryIcon = match($column['category']) {
            'new' => 'ti-circle',
            'indeterminate' => 'ti-loader',
            'done' => 'ti-check',
            default => 'ti-loader'
          };
        @endphp
        <div class="kanban-column">
          <div class="kanban-column-header {{ $categoryClass }}">
            <span><i class="ti {{ $categoryIcon }} me-2"></i>{{ $column['name'] }}</span>
            <span class="badge bg-light text-dark">{{ count($column['issues']) }}</span>
          </div>
          <div class="kanban-column-body">
            @forelse($column['issues'] as $issue)
              @include('project::projects.partials.kanban-card', ['issue' => $issue])
            @empty
              <div class="text-center text-muted py-4">
                <i class="ti {{ $categoryIcon }} mb-2"></i>
                <p class="mb-0 small">No items</p>
              </div>
            @endforelse
          </div>
        </div>
      @empty
        <div class="col-12 text-center py-5">
          <i class="ti ti-clipboard-list ti-xl text-muted mb-3"></i>
          <p class="text-muted">No issues found. Click "Sync from Jira" to fetch issues.</p>
        </div>
      @endforelse
    </div>
  @else
    <!-- List View -->
    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0 list-table">
            <thead class="table-light">
              <tr>
                <th style="width: 40px;" class="sortable" data-sort="issue_type">Type<i class="ti sort-icon"></i></th>
                <th style="width: 90px;" class="sortable" data-sort="issue_key">Key<i class="ti sort-icon"></i></th>
                <th style="min-width: 250px;" class="sortable" data-sort="summary">Summary<i class="ti sort-icon"></i></th>
                <th style="width: 90px;" class="sortable" data-sort="epic_key">Epic<i class="ti sort-icon"></i></th>
                <th style="width: 110px;" class="sortable" data-sort="status">Status<i class="ti sort-icon"></i></th>
                <th style="width: 90px;" class="sortable" data-sort="priority">Priority<i class="ti sort-icon"></i></th>
                <th style="width: 130px;" class="sortable" data-sort="assignee">Assignee<i class="ti sort-icon"></i></th>
                <th style="width: 100px;" class="sortable" data-sort="due_date">Due Date<i class="ti sort-icon"></i></th>
                <th style="width: 50px;" class="sortable" data-sort="story_points">SP<i class="ti sort-icon"></i></th>
                <th style="width: 90px;">Components</th>
                <th style="width: 100px;" class="sortable" data-sort="jira_created_at">Created<i class="ti sort-icon"></i></th>
                <th style="width: 40px;"></th>
              </tr>
            </thead>
            <tbody>
              @forelse($issues as $issue)
                <tr class="{{ $issue->isOverdue() ? 'table-danger' : '' }}" data-issue-id="{{ $issue->id }}" data-issue-key="{{ $issue->issue_key }}">
                  <td>
                    <span class="issue-type-icon bg-{{ $issue->issue_type_color }}" title="{{ $issue->issue_type }}">
                      <i class="ti {{ $issue->issue_type_icon }} text-white"></i>
                    </span>
                  </td>
                  <td>
                    <a href="{{ $issue->jira_url }}" target="_blank" class="issue-key text-decoration-none">
                      {{ $issue->issue_key }}
                    </a>
                  </td>
                  <td>
                    <div>
                      @if($issue->description)
                        <span class="summary-link" data-issue-id="{{ $issue->id }}" data-bs-toggle="modal" data-bs-target="#descriptionModal">
                          {{ Str::limit($issue->summary, 60) }}
                        </span>
                      @else
                        <span>{{ Str::limit($issue->summary, 60) }}</span>
                      @endif
                    </div>
                    @if($issue->labels && count($issue->labels) > 0)
                      <div class="issue-labels">
                        @foreach(array_slice($issue->labels, 0, 3) as $label)
                          <span class="issue-label">{{ $label }}</span>
                        @endforeach
                        @if(count($issue->labels) > 3)
                          <span class="issue-label">+{{ count($issue->labels) - 3 }}</span>
                        @endif
                      </div>
                    @endif
                  </td>
                  <td>
                    @if($issue->epic_key)
                      <a href="https://aura-llc.atlassian.net/browse/{{ $issue->epic_key }}" target="_blank" class="epic-link text-decoration-none">
                        {{ $issue->epic_key }}
                      </a>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    <div class="editable-field status-field" data-field="status" data-current="{{ $issue->status }}">
                      <span class="badge bg-{{ $issue->status_color }}">{{ $issue->status }}</span>
                      <i class="ti ti-chevron-down ms-1" style="font-size: 0.7rem;"></i>
                      <span class="saving-indicator"><i class="ti ti-loader ti-spin"></i></span>
                    </div>
                  </td>
                  <td>
                    <div class="editable-field priority-field" data-field="priority" data-current="{{ $issue->priority }}">
                      @if($issue->priority)
                        <span class="badge bg-{{ $issue->priority_color }}">{{ $issue->priority }}</span>
                      @else
                        <span class="text-muted">None</span>
                      @endif
                      <i class="ti ti-chevron-down ms-1" style="font-size: 0.7rem;"></i>
                      <span class="saving-indicator"><i class="ti ti-loader ti-spin"></i></span>
                    </div>
                  </td>
                  <td>
                    <div class="editable-field assignee-field" data-field="assignee" data-current="{{ $issue->assignee_email }}">
                      @if($issue->assignee)
                        <span class="issue-assignee-avatar me-1">{{ substr($issue->assignee->name, 0, 2) }}</span>
                        <small>{{ Str::limit($issue->assignee->name, 12) }}</small>
                      @elseif($issue->assignee_email)
                        <small>{{ Str::before($issue->assignee_email, '@') }}</small>
                      @else
                        <span class="text-muted">Unassigned</span>
                      @endif
                      <i class="ti ti-chevron-down ms-1" style="font-size: 0.7rem;"></i>
                      <span class="saving-indicator"><i class="ti ti-loader ti-spin"></i></span>
                    </div>
                  </td>
                  <td>
                    <div class="editable-field due-date-field" data-field="due_date" data-current="{{ $issue->due_date?->format('Y-m-d') }}">
                      @if($issue->due_date)
                        <span class="{{ $issue->isOverdue() ? 'text-danger fw-bold' : '' }}">
                          {{ $issue->due_date->format('M d') }}
                        </span>
                      @else
                        <span class="text-muted">Set date</span>
                      @endif
                      <span class="saving-indicator"><i class="ti ti-loader ti-spin"></i></span>
                    </div>
                  </td>
                  <td>
                    <div class="editable-field story-points-field" data-field="story_points" data-current="{{ $issue->story_points }}">
                      @if($issue->story_points)
                        <span class="story-points-display">{{ $issue->story_points }}</span>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                      <span class="saving-indicator"><i class="ti ti-loader ti-spin"></i></span>
                    </div>
                  </td>
                  <td>
                    @if($issue->components && count($issue->components) > 0)
                      <div class="d-flex flex-wrap gap-1">
                        @foreach(array_slice($issue->components, 0, 2) as $component)
                          <span class="component-badge">{{ Str::limit($component, 10) }}</span>
                        @endforeach
                        @if(count($issue->components) > 2)
                          <span class="component-badge">+{{ count($issue->components) - 2 }}</span>
                        @endif
                      </div>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td class="dates-small" title="Created: {{ $issue->jira_created_at?->format('Y-m-d H:i') }}&#10;Updated: {{ $issue->jira_updated_at?->format('Y-m-d H:i') }}">
                    {{ $issue->jira_created_at?->format('M d, Y') }}
                  </td>
                  <td>
                    <a href="{{ $issue->jira_url }}" target="_blank" class="btn btn-sm btn-icon btn-outline-secondary" title="Open in Jira">
                      <i class="ti ti-external-link"></i>
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="12" class="text-center py-4 text-muted">
                    No issues match your filters.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  @endif

  <!-- Description Modal -->
  <div class="modal fade" id="descriptionModal" tabindex="-1" aria-labelledby="descriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="descriptionModalLabel">
            <span id="descriptionModalIssueKey" class="issue-key me-2"></span>
            <span id="descriptionModalSummary"></span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="descriptionLoading" class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
          <div id="descriptionContent" style="display: none;">
            <div id="descriptionText" class="mb-3" style="white-space: pre-wrap;"></div>
            <hr>
            <div class="row text-muted small">
              <div class="col-md-6">
                <strong>Created:</strong> <span id="descCreated"></span>
              </div>
              <div class="col-md-6">
                <strong>Updated:</strong> <span id="descUpdated"></span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <a href="#" id="descriptionJiraLink" target="_blank" class="btn btn-primary">
            <i class="ti ti-external-link me-1"></i>Open in Jira
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Create Task Modal -->
  <div class="modal fade" id="createTaskModal" tabindex="-1" aria-labelledby="createTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form action="{{ route('projects.store-task', $project) }}" method="POST" id="createTaskForm">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title" id="createTaskModalLabel">
              <i class="ti ti-plus me-2"></i>Create Task in Jira
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div id="modalLoadingState" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p class="text-muted mt-2">Loading Jira options...</p>
            </div>
            <div id="modalFormContent" style="display: none;">
              <div class="row g-3">
                <div class="col-12">
                  <label for="summary" class="form-label">Summary <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="summary" name="summary" required maxlength="255" placeholder="Brief description of the task">
                </div>
                <div class="col-md-6">
                  <label for="issue_type" class="form-label">Issue Type <span class="text-danger">*</span></label>
                  <select class="form-select" id="issue_type" name="issue_type" required>
                    <!-- Options will be populated dynamically -->
                  </select>
                </div>
                <div class="col-md-6">
                  <label for="priority" class="form-label">Priority</label>
                  <select class="form-select" id="priority" name="priority">
                    <option value="">-- Select Priority --</option>
                    <!-- Options will be populated dynamically -->
                  </select>
                </div>
                <div class="col-md-6">
                  <label for="assignee_account_id" class="form-label">Assignee</label>
                  <select class="form-select" id="assignee_account_id" name="assignee_account_id">
                    <option value="">-- Unassigned --</option>
                    <!-- Options will be populated dynamically -->
                  </select>
                </div>
                <div class="col-md-6">
                  <label for="due_date" class="form-label">Due Date</label>
                  <input type="date" class="form-control" id="due_date" name="due_date">
                </div>
                <div class="col-12">
                  <label for="description" class="form-label">Description</label>
                  <textarea class="form-control" id="description" name="description" rows="4" maxlength="5000" placeholder="Detailed description of the task..."></textarea>
                </div>
              </div>
            </div>
            <div id="modalErrorState" style="display: none;">
              <div class="alert alert-danger mb-0">
                <i class="ti ti-alert-circle me-2"></i>
                <span id="modalErrorMessage">Failed to load Jira options.</span>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary" id="submitTaskBtn" disabled>
              <i class="ti ti-send me-1"></i>Create in Jira
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const projectId = {{ $project->id }};
  let jiraOptions = null;
  let activeEditField = null;

  // Add click handler for kanban cards to open in Jira
  document.querySelectorAll('.kanban-card[data-jira-url]').forEach(function(card) {
    card.addEventListener('click', function() {
      window.open(this.dataset.jiraUrl, '_blank');
    });
  });

  // ==========================================
  // Column Sorting
  // ==========================================
  const sortableHeaders = document.querySelectorAll('.sortable');
  let currentSort = { column: null, direction: 'asc' };

  // Check URL for existing sort params
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.has('sort')) {
    currentSort.column = urlParams.get('sort');
    currentSort.direction = urlParams.get('direction') || 'asc';
    // Highlight current sorted column
    const sortedHeader = document.querySelector(`.sortable[data-sort="${currentSort.column}"]`);
    if (sortedHeader) {
      sortedHeader.classList.add(currentSort.direction === 'asc' ? 'sorted-asc' : 'sorted-desc');
    }
  }

  sortableHeaders.forEach(header => {
    header.addEventListener('click', function() {
      const sortColumn = this.dataset.sort;
      let direction = 'asc';

      // If clicking same column, toggle direction
      if (currentSort.column === sortColumn) {
        direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
      }

      // Update URL with sort params
      const url = new URL(window.location.href);
      url.searchParams.set('sort', sortColumn);
      url.searchParams.set('direction', direction);
      window.location.href = url.toString();
    });
  });

  // ==========================================
  // Description Modal
  // ==========================================
  const descriptionModal = document.getElementById('descriptionModal');
  if (descriptionModal) {
    descriptionModal.addEventListener('show.bs.modal', function(event) {
      const trigger = event.relatedTarget;
      if (!trigger) return;

      const issueId = trigger.dataset.issueId;
      const row = trigger.closest('tr');
      const issueKey = row.dataset.issueKey;

      document.getElementById('descriptionLoading').style.display = 'block';
      document.getElementById('descriptionContent').style.display = 'none';
      document.getElementById('descriptionModalIssueKey').textContent = issueKey;
      document.getElementById('descriptionModalSummary').textContent = trigger.textContent.trim();
      document.getElementById('descriptionJiraLink').href = `https://aura-llc.atlassian.net/browse/${issueKey}`;

      fetch(`/projects/${projectId}/tasks/${issueId}/details`)
        .then(response => response.json())
        .then(data => {
          document.getElementById('descriptionLoading').style.display = 'none';
          document.getElementById('descriptionContent').style.display = 'block';
          document.getElementById('descriptionText').textContent = data.description || 'No description available.';
          document.getElementById('descCreated').textContent = data.jira_created_at || '-';
          document.getElementById('descUpdated').textContent = data.jira_updated_at || '-';
        })
        .catch(error => {
          document.getElementById('descriptionLoading').style.display = 'none';
          document.getElementById('descriptionContent').style.display = 'block';
          document.getElementById('descriptionText').textContent = 'Failed to load description.';
        });
    });
  }

  // ==========================================
  // Inline Editing
  // ==========================================

  // Load Jira options once for inline editing
  function loadJiraOptionsForEditing() {
    if (jiraOptions) return Promise.resolve(jiraOptions);

    return fetch('{{ route("projects.create-task", $project) }}')
      .then(response => response.json())
      .then(data => {
        jiraOptions = data;
        return data;
      });
  }

  // Close any active inline edit
  function closeActiveEdit() {
    if (activeEditField) {
      const editContainer = activeEditField.querySelector('.inline-edit-container');
      if (editContainer) {
        editContainer.remove();
      }
      activeEditField.style.display = '';
      activeEditField = null;
    }
  }

  // Handle clicks outside to close edit
  document.addEventListener('click', function(e) {
    if (activeEditField && !activeEditField.contains(e.target)) {
      closeActiveEdit();
    }
  });

  // Status field click handler
  document.querySelectorAll('.status-field').forEach(function(field) {
    field.addEventListener('click', function(e) {
      e.stopPropagation();
      if (activeEditField === this) return;
      closeActiveEdit();

      const row = this.closest('tr');
      const issueId = row.dataset.issueId;
      const savingIndicator = this.querySelector('.saving-indicator');

      // Show loading in field
      savingIndicator.classList.add('active');

      // Fetch available transitions
      fetch(`/projects/${projectId}/tasks/${issueId}/transitions`)
        .then(response => response.json())
        .then(data => {
          savingIndicator.classList.remove('active');

          if (!data.transitions || data.transitions.length === 0) {
            Swal.fire('No Transitions', 'No status transitions available for this issue.', 'info');
            return;
          }

          // Create dropdown
          const select = document.createElement('select');
          select.className = 'form-select form-select-sm inline-edit-select';
          select.innerHTML = '<option value="">-- Select Status --</option>';

          data.transitions.forEach(function(transition) {
            const option = document.createElement('option');
            option.value = transition.id;
            option.textContent = transition.name;
            select.appendChild(option);
          });

          const container = document.createElement('div');
          container.className = 'inline-edit-container';
          container.appendChild(select);

          this.style.display = 'none';
          this.parentNode.appendChild(container);
          activeEditField = this;
          select.focus();

          select.addEventListener('change', function() {
            if (!this.value) return;

            savingIndicator.classList.add('active');
            container.remove();
            field.style.display = '';

            fetch(`/projects/${projectId}/tasks/${issueId}/transition`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
              },
              body: JSON.stringify({ transition_id: this.value })
            })
            .then(response => response.json())
            .then(data => {
              savingIndicator.classList.remove('active');
              if (data.success) {
                location.reload();
              } else {
                Swal.fire('Error', data.error || 'Failed to update status', 'error');
              }
            })
            .catch(error => {
              savingIndicator.classList.remove('active');
              Swal.fire('Error', 'Failed to update status', 'error');
            });

            activeEditField = null;
          });

          select.addEventListener('blur', function() {
            setTimeout(() => {
              container.remove();
              field.style.display = '';
              activeEditField = null;
            }, 200);
          });
        })
        .catch(error => {
          savingIndicator.classList.remove('active');
          Swal.fire('Error', 'Failed to load transitions', 'error');
        });
    });
  });

  // Priority field click handler
  document.querySelectorAll('.priority-field').forEach(function(field) {
    field.addEventListener('click', function(e) {
      e.stopPropagation();
      if (activeEditField === this) return;
      closeActiveEdit();

      const row = this.closest('tr');
      const issueId = row.dataset.issueId;
      const currentValue = this.dataset.current;
      const savingIndicator = this.querySelector('.saving-indicator');

      loadJiraOptionsForEditing().then(function(options) {
        const select = document.createElement('select');
        select.className = 'form-select form-select-sm inline-edit-select';
        select.innerHTML = '<option value="">None</option>';

        options.priorities.forEach(function(priority) {
          const option = document.createElement('option');
          option.value = priority;
          option.textContent = priority;
          if (priority === currentValue) option.selected = true;
          select.appendChild(option);
        });

        const container = document.createElement('div');
        container.className = 'inline-edit-container';
        container.appendChild(select);

        field.style.display = 'none';
        field.parentNode.appendChild(container);
        activeEditField = field;
        select.focus();

        select.addEventListener('change', function() {
          saveFieldUpdate(issueId, 'priority', this.value, field, container, savingIndicator);
        });

        select.addEventListener('blur', function() {
          setTimeout(() => {
            container.remove();
            field.style.display = '';
            activeEditField = null;
          }, 200);
        });
      });
    });
  });

  // Assignee field click handler
  document.querySelectorAll('.assignee-field').forEach(function(field) {
    field.addEventListener('click', function(e) {
      e.stopPropagation();
      if (activeEditField === this) return;
      closeActiveEdit();

      const row = this.closest('tr');
      const issueId = row.dataset.issueId;
      const currentValue = this.dataset.current;
      const savingIndicator = this.querySelector('.saving-indicator');

      loadJiraOptionsForEditing().then(function(options) {
        const select = document.createElement('select');
        select.className = 'form-select form-select-sm inline-edit-select';
        select.innerHTML = '<option value="">Unassigned</option>';

        options.assignees.forEach(function(user) {
          const option = document.createElement('option');
          option.value = user.account_id;
          option.textContent = user.display_name;
          if (user.email === currentValue) option.selected = true;
          select.appendChild(option);
        });

        const container = document.createElement('div');
        container.className = 'inline-edit-container';
        container.appendChild(select);

        field.style.display = 'none';
        field.parentNode.appendChild(container);
        activeEditField = field;
        select.focus();

        select.addEventListener('change', function() {
          saveFieldUpdate(issueId, 'assignee', this.value, field, container, savingIndicator);
        });

        select.addEventListener('blur', function() {
          setTimeout(() => {
            container.remove();
            field.style.display = '';
            activeEditField = null;
          }, 200);
        });
      });
    });
  });

  // Due date field click handler
  document.querySelectorAll('.due-date-field').forEach(function(field) {
    field.addEventListener('click', function(e) {
      e.stopPropagation();
      if (activeEditField === this) return;
      closeActiveEdit();

      const row = this.closest('tr');
      const issueId = row.dataset.issueId;
      const currentValue = this.dataset.current || '';
      const savingIndicator = this.querySelector('.saving-indicator');

      const input = document.createElement('input');
      input.type = 'date';
      input.className = 'form-control form-control-sm inline-edit-date';
      input.value = currentValue;

      const container = document.createElement('div');
      container.className = 'inline-edit-container';
      container.appendChild(input);

      field.style.display = 'none';
      field.parentNode.appendChild(container);
      activeEditField = field;
      input.focus();

      input.addEventListener('change', function() {
        saveFieldUpdate(issueId, 'due_date', this.value, field, container, savingIndicator);
      });

      input.addEventListener('blur', function() {
        setTimeout(() => {
          container.remove();
          field.style.display = '';
          activeEditField = null;
        }, 200);
      });
    });
  });

  // Story points field click handler
  document.querySelectorAll('.story-points-field').forEach(function(field) {
    field.addEventListener('click', function(e) {
      e.stopPropagation();
      if (activeEditField === this) return;
      closeActiveEdit();

      const row = this.closest('tr');
      const issueId = row.dataset.issueId;
      const currentValue = this.dataset.current || '';
      const savingIndicator = this.querySelector('.saving-indicator');

      const input = document.createElement('input');
      input.type = 'number';
      input.className = 'form-control form-control-sm inline-edit-input';
      input.value = currentValue;
      input.min = 0;
      input.max = 100;
      input.step = 1;
      input.style.width = '60px';

      const container = document.createElement('div');
      container.className = 'inline-edit-container';
      container.appendChild(input);

      field.style.display = 'none';
      field.parentNode.appendChild(container);
      activeEditField = field;
      input.focus();
      input.select();

      input.addEventListener('blur', function() {
        const newValue = this.value ? parseInt(this.value) : null;
        if (newValue !== parseInt(currentValue)) {
          saveFieldUpdate(issueId, 'story_points', newValue, field, container, savingIndicator);
        } else {
          container.remove();
          field.style.display = '';
          activeEditField = null;
        }
      });

      input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
          this.blur();
        } else if (e.key === 'Escape') {
          container.remove();
          field.style.display = '';
          activeEditField = null;
        }
      });
    });
  });

  // Generic save field update function
  function saveFieldUpdate(issueId, field, value, fieldElement, container, savingIndicator) {
    container.remove();
    fieldElement.style.display = '';
    savingIndicator.classList.add('active');
    activeEditField = null;

    fetch(`/projects/${projectId}/tasks/${issueId}/update-field`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ field: field, value: value })
    })
    .then(response => response.json())
    .then(data => {
      savingIndicator.classList.remove('active');
      if (data.success) {
        location.reload();
      } else {
        Swal.fire('Error', data.error || 'Failed to update field', 'error');
      }
    })
    .catch(error => {
      savingIndicator.classList.remove('active');
      Swal.fire('Error', 'Failed to update field', 'error');
    });
  }

  // ==========================================
  // Create Task Modal handling
  // ==========================================
  const createTaskModal = document.getElementById('createTaskModal');
  let jiraOptionsLoaded = false;

  createTaskModal.addEventListener('show.bs.modal', function() {
    if (!jiraOptionsLoaded) {
      loadJiraOptions();
    }
  });

  function loadJiraOptions() {
    const loadingState = document.getElementById('modalLoadingState');
    const formContent = document.getElementById('modalFormContent');
    const errorState = document.getElementById('modalErrorState');
    const submitBtn = document.getElementById('submitTaskBtn');

    loadingState.style.display = 'block';
    formContent.style.display = 'none';
    errorState.style.display = 'none';
    submitBtn.disabled = true;

    fetch('{{ route("projects.create-task", $project) }}')
      .then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Failed to load options');
          });
        }
        return response.json();
      })
      .then(data => {
        // Store for inline editing
        jiraOptions = data;

        // Populate issue types
        const issueTypeSelect = document.getElementById('issue_type');
        issueTypeSelect.innerHTML = '';
        data.issue_types.forEach(function(type) {
          const option = document.createElement('option');
          option.value = type;
          option.textContent = type;
          if (type === 'Task') option.selected = true;
          issueTypeSelect.appendChild(option);
        });

        // Populate priorities
        const prioritySelect = document.getElementById('priority');
        prioritySelect.innerHTML = '<option value="">-- Select Priority --</option>';
        data.priorities.forEach(function(priority) {
          const option = document.createElement('option');
          option.value = priority;
          option.textContent = priority;
          if (priority === 'Medium') option.selected = true;
          prioritySelect.appendChild(option);
        });

        // Populate assignees
        const assigneeSelect = document.getElementById('assignee_account_id');
        assigneeSelect.innerHTML = '<option value="">-- Unassigned --</option>';
        data.assignees.forEach(function(user) {
          const option = document.createElement('option');
          option.value = user.account_id;
          option.textContent = user.display_name;
          assigneeSelect.appendChild(option);
        });

        loadingState.style.display = 'none';
        formContent.style.display = 'block';
        submitBtn.disabled = false;
        jiraOptionsLoaded = true;
      })
      .catch(error => {
        loadingState.style.display = 'none';
        errorState.style.display = 'block';
        document.getElementById('modalErrorMessage').textContent = error.message;
      });
  }

  // Form submission with loading state
  const createTaskForm = document.getElementById('createTaskForm');
  createTaskForm.addEventListener('submit', function() {
    const submitBtn = document.getElementById('submitTaskBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Creating...';
  });

  // Reset modal on close
  createTaskModal.addEventListener('hidden.bs.modal', function() {
    createTaskForm.reset();
  });

  // ==========================================
  // Initialize Select2 for filters
  // ==========================================
  if (typeof $.fn.select2 !== 'undefined') {
    $('.select2-statuses').select2({
      placeholder: 'Select statuses to hide...',
      allowClear: true,
      width: '100%'
    });

    $('.select2-priorities').select2({
      placeholder: 'All priorities',
      allowClear: true,
      width: '100%'
    });

    $('.select2-assignees').select2({
      placeholder: 'All assignees',
      allowClear: true,
      width: '100%'
    });
  }
});
</script>
@endsection
