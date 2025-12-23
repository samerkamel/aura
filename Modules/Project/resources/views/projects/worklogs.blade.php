@extends('layouts/layoutMaster')

@section('title', 'Work Entries - ' . $project->name)

@section('vendor-style')
@vite('resources/assets/vendor/libs/select2/select2.scss')
@endsection

@section('vendor-script')
@vite('resources/assets/vendor/libs/select2/select2.js')
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card bg-primary text-white">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="text-white mb-1">
                <i class="ti ti-list me-2"></i>Work Entries
              </h4>
              <p class="mb-0 opacity-75">
                <span class="badge bg-light text-primary me-2">{{ $project->code }}</span>
                {{ $project->name }}
              </p>
            </div>
            <div>
              <a href="{{ route('projects.show', $project) }}" class="btn btn-light">
                <i class="ti ti-arrow-left me-1"></i>Back to Project
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Stats Summary -->
  <div class="row mb-4">
    <div class="col-md-4 mb-3 mb-md-0">
      <div class="card h-100">
        <div class="card-body text-center">
          <h3 class="text-primary mb-1">{{ number_format($totalHours, 1) }}h</h3>
          <small class="text-muted">Total Hours (filtered)</small>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-3 mb-md-0">
      <div class="card h-100">
        <div class="card-body text-center">
          <h3 class="text-info mb-1">{{ number_format($totalEntries) }}</h3>
          <small class="text-muted">Total Entries (filtered)</small>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body text-center">
          <h3 class="text-success mb-1">{{ $employeesWithWorklogs->count() }}</h3>
          <small class="text-muted">Contributors</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0"><i class="ti ti-filter me-2"></i>Filters</h5>
    </div>
    <div class="card-body">
      <form action="{{ route('projects.worklogs', $project) }}" method="GET">
        <div class="row g-3">
          <div class="col-md-2">
            <label class="form-label">From Date</label>
            <input type="date" class="form-control" name="start_date" value="{{ $filters['start_date'] ?? '' }}">
          </div>
          <div class="col-md-2">
            <label class="form-label">To Date</label>
            <input type="date" class="form-control" name="end_date" value="{{ $filters['end_date'] ?? '' }}">
          </div>
          <div class="col-md-2">
            <label class="form-label">Employee</label>
            <select class="form-select select2" name="employee_id">
              <option value="">All Employees</option>
              @foreach($employeesWithWorklogs as $emp)
                <option value="{{ $emp->id }}" {{ ($filters['employee_id'] ?? '') == $emp->id ? 'selected' : '' }}>
                  {{ $emp->name }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Issue Key</label>
            <input type="text" class="form-control" name="issue_key" value="{{ $filters['issue_key'] ?? '' }}" placeholder="e.g. {{ $project->code }}-123">
          </div>
          <div class="col-md-2">
            <label class="form-label">Search</label>
            <input type="text" class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Description...">
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary me-2">
              <i class="ti ti-filter me-1"></i>Filter
            </button>
            <a href="{{ route('projects.worklogs', $project) }}" class="btn btn-outline-secondary">
              <i class="ti ti-x"></i>
            </a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Worklogs Table -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i class="ti ti-clock me-2"></i>Work Entries
      </h5>
      <span class="badge bg-secondary">
        Showing {{ $worklogs->firstItem() ?? 0 }}-{{ $worklogs->lastItem() ?? 0 }} of {{ $worklogs->total() }}
      </span>
    </div>
    <div class="card-body">
      @if($worklogs->count() > 0)
        <div class="table-responsive">
          <table class="table table-hover">
            <thead class="table-light">
              <tr>
                <th style="width: 100px;">Date</th>
                <th style="width: 150px;">Employee</th>
                <th style="width: 120px;">Issue</th>
                <th>Task Name</th>
                <th style="width: 80px;" class="text-end">Hours</th>
              </tr>
            </thead>
            <tbody>
              @foreach($worklogs as $worklog)
                <tr>
                  <td>
                    <span class="fw-semibold">{{ $worklog->worklog_started->format('M d, Y') }}</span>
                    <br>
                    <small class="text-muted">{{ $worklog->worklog_started->format('g:i A') }}</small>
                  </td>
                  <td>
                    @if($worklog->employee)
                      <div class="d-flex align-items-center">
                        <div class="avatar avatar-xs me-2" style="background-color: {{ '#' . substr(md5($worklog->employee->name), 0, 6) }}; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.7rem;">
                          {{ strtoupper(substr($worklog->employee->name, 0, 2)) }}
                        </div>
                        <div>
                          <span class="fw-semibold">{{ $worklog->employee->name }}</span>
                        </div>
                      </div>
                    @else
                      <span class="text-muted">{{ $worklog->jira_author_name ?? 'Unknown' }}</span>
                    @endif
                  </td>
                  <td>
                    <span class="badge bg-label-primary">{{ $worklog->issue_key }}</span>
                  </td>
                  <td>
                    @if($worklog->issue_summary)
                      <div class="text-wrap" style="max-width: 400px;">
                        <span class="fw-semibold">{{ $worklog->issue_summary }}</span>
                        @if($worklog->comment)
                          <span class="badge bg-label-info ms-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="{{ e($worklog->comment) }}" style="cursor: help;">
                            <i class="ti ti-message-circle me-1"></i>Details
                          </span>
                        @endif
                      </div>
                    @elseif($worklog->comment)
                      <div class="text-wrap" style="max-width: 400px;">
                        {{ $worklog->comment }}
                      </div>
                    @else
                      <span class="text-muted fst-italic">No description</span>
                    @endif
                  </td>
                  <td class="text-end">
                    <span class="badge bg-info fs-6">{{ number_format($worklog->time_spent_hours, 1) }}h</span>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
          {{ $worklogs->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
      @else
        <div class="text-center py-5">
          <i class="ti ti-clock-off display-1 text-muted mb-3"></i>
          <h4 class="text-muted">No Work Entries Found</h4>
          <p class="text-muted">Try adjusting your filters or date range.</p>
        </div>
      @endif
    </div>
  </div>

  <!-- Summary by Employee (collapsible) -->
  @if($worklogs->count() > 0)
  <div class="card mt-4">
    <div class="card-header" data-bs-toggle="collapse" data-bs-target="#employeeSummary" style="cursor: pointer;">
      <h5 class="mb-0">
        <i class="ti ti-users me-2"></i>Summary by Employee
        <i class="ti ti-chevron-down float-end"></i>
      </h5>
    </div>
    <div class="collapse" id="employeeSummary">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm">
            <thead class="table-light">
              <tr>
                <th>Employee</th>
                <th class="text-end">Hours</th>
                <th class="text-end">Entries</th>
                <th class="text-end">Avg/Entry</th>
              </tr>
            </thead>
            <tbody>
              @php
                $employeeSummary = $worklogs->getCollection()->groupBy('employee_id')->map(function ($items) {
                    return [
                        'employee' => $items->first()->employee,
                        'jira_author' => $items->first()->jira_author_name,
                        'hours' => $items->sum('time_spent_hours'),
                        'entries' => $items->count(),
                    ];
                })->sortByDesc('hours');
              @endphp
              @foreach($employeeSummary as $summary)
                <tr>
                  <td>
                    @if($summary['employee'])
                      {{ $summary['employee']->name }}
                    @else
                      <span class="text-muted">{{ $summary['jira_author'] ?? 'Unknown' }}</span>
                    @endif
                  </td>
                  <td class="text-end fw-semibold">{{ number_format($summary['hours'], 1) }}h</td>
                  <td class="text-end">{{ $summary['entries'] }}</td>
                  <td class="text-end">{{ number_format($summary['hours'] / $summary['entries'], 1) }}h</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Initialize Select2
  if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
    jQuery('.select2').select2({
      theme: 'bootstrap-5',
      allowClear: true
    });
  }

  // Initialize Bootstrap tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl, {
      container: 'body'
    });
  });
});
</script>
@endsection
@endsection
