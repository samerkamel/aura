@use('Illuminate\Support\Str')
@extends('layouts/layoutMaster')

@section('title', $project->name . ' - Tasks')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
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
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
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
        <form action="{{ route('projects.sync-issues', $project) }}" method="POST" class="d-inline">
          @csrf
          <button type="submit" class="btn btn-light btn-sm">
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
      <div class="row align-items-center">
        <div class="col-md-6">
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
            <small class="text-muted ms-3">
              <i class="ti ti-clock me-1"></i>Last synced: {{ \Carbon\Carbon::parse($summary['last_synced'])->diffForHumans() }}
            </small>
          @endif
        </div>
        <div class="col-md-6">
          @if($view === 'list')
          <form method="GET" class="d-flex gap-2 justify-content-end">
            <input type="hidden" name="view" value="list">
            <input type="text" name="search" class="form-control form-control-sm" style="width: 200px;"
                   placeholder="Search issues..." value="{{ $filters['search'] ?? '' }}">
            <select name="status_category" class="form-select form-select-sm" style="width: 150px;">
              <option value="">All Status</option>
              <option value="new" {{ ($filters['status_category'] ?? '') === 'new' ? 'selected' : '' }}>To Do</option>
              <option value="indeterminate" {{ ($filters['status_category'] ?? '') === 'indeterminate' ? 'selected' : '' }}>In Progress</option>
              <option value="done" {{ ($filters['status_category'] ?? '') === 'done' ? 'selected' : '' }}>Done</option>
            </select>
            <button type="submit" class="btn btn-sm btn-primary">
              <i class="ti ti-filter"></i>
            </button>
            @if(!empty($filters['search']) || !empty($filters['status_category']))
            <a href="{{ route('projects.tasks', ['project' => $project, 'view' => 'list']) }}" class="btn btn-sm btn-outline-secondary">
              <i class="ti ti-x"></i>
            </a>
            @endif
          </form>
          @endif
        </div>
      </div>
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
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 60px;">Type</th>
                <th style="width: 100px;">Key</th>
                <th>Summary</th>
                <th style="width: 120px;">Status</th>
                <th style="width: 100px;">Priority</th>
                <th style="width: 140px;">Assignee</th>
                <th style="width: 100px;">Due Date</th>
                <th style="width: 50px;"></th>
              </tr>
            </thead>
            <tbody>
              @forelse($issues as $issue)
                <tr class="{{ $issue->isOverdue() ? 'table-danger' : '' }}">
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
                    <a href="{{ $issue->jira_url }}" target="_blank" class="text-body text-decoration-none">
                      {{ Str::limit($issue->summary, 60) }}
                    </a>
                  </td>
                  <td>
                    <span class="badge bg-{{ $issue->status_color }}">{{ $issue->status }}</span>
                  </td>
                  <td>
                    @if($issue->priority)
                      <span class="badge bg-{{ $issue->priority_color }}">{{ $issue->priority }}</span>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    @if($issue->assignee)
                      <span class="issue-assignee-avatar me-1">{{ substr($issue->assignee->name, 0, 2) }}</span>
                      <small>{{ $issue->assignee->name }}</small>
                    @elseif($issue->assignee_email)
                      <small class="text-muted">{{ Str::before($issue->assignee_email, '@') }}</small>
                    @else
                      <span class="text-muted">Unassigned</span>
                    @endif
                  </td>
                  <td>
                    @if($issue->due_date)
                      <span class="{{ $issue->isOverdue() ? 'text-danger fw-bold' : '' }}">
                        {{ $issue->due_date->format('M d') }}
                      </span>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    <a href="{{ $issue->jira_url }}" target="_blank" class="btn btn-sm btn-icon btn-outline-secondary">
                      <i class="ti ti-external-link"></i>
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center py-4 text-muted">
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
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Add click handler for kanban cards to open in Jira
  document.querySelectorAll('.kanban-card[data-jira-url]').forEach(function(card) {
    card.addEventListener('click', function() {
      window.open(this.dataset.jiraUrl, '_blank');
    });
  });
});
</script>
@endsection
