@extends('layouts/layoutMaster')

@section('title', 'Jira Worklogs')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-md-12">
      <!-- Page Header -->
      <div class="card mb-4">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
              <a href="{{ route('payroll.billable-hours.index') }}" class="btn btn-icon btn-outline-secondary me-3">
                <i class="ti ti-arrow-left"></i>
              </a>
              <div>
                <h5 class="mb-0">
                  <i class="ti ti-brand-jira me-2"></i>Jira Worklogs
                </h5>
                <small class="text-muted">View imported Jira worklog entries</small>
              </div>
            </div>
            <a href="{{ route('payroll.billable-hours.import-jira-worklogs') }}" class="btn btn-primary">
              <i class="ti ti-upload me-1"></i>Import Worklogs
            </a>
          </div>
        </div>
      </div>

      @if (session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
          {{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif

      @if (session('import_stats'))
        @php $stats = session('import_stats'); @endphp
        <div class="alert alert-info alert-dismissible" role="alert">
          <h6><i class="ti ti-info-circle me-2"></i>Import Summary</h6>
          <ul class="mb-0">
            <li><strong>Imported:</strong> {{ $stats['imported'] }} new records</li>
            <li><strong>Skipped:</strong> {{ $stats['skipped'] }} duplicates</li>
            @if(!empty($stats['unmapped_authors']))
              <li class="text-warning">
                <strong>Unmapped Authors:</strong>
                @foreach($stats['unmapped_authors'] as $author => $count)
                  {{ $author }} ({{ $count }} entries){{ !$loop->last ? ', ' : '' }}
                @endforeach
              </li>
            @endif
            @if(!empty($stats['errors']))
              <li class="text-danger">
                <strong>Errors:</strong> {{ count($stats['errors']) }}
                <small class="d-block">{{ implode(', ', array_slice($stats['errors'], 0, 3)) }}{{ count($stats['errors']) > 3 ? '...' : '' }}</small>
              </li>
            @endif
          </ul>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif

      <!-- Summary Stats -->
      <div class="row mb-4">
        <div class="col-md-6">
          <div class="card">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="avatar avatar-lg me-3 bg-label-primary">
                  <span class="avatar-initial rounded">
                    <i class="ti ti-list-check"></i>
                  </span>
                </div>
                <div>
                  <h3 class="mb-0">{{ number_format($summary['total_entries']) }}</h3>
                  <small class="text-muted">Total Entries</small>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="avatar avatar-lg me-3 bg-label-success">
                  <span class="avatar-initial rounded">
                    <i class="ti ti-clock"></i>
                  </span>
                </div>
                <div>
                  <h3 class="mb-0">{{ number_format($summary['total_hours'], 2) }}</h3>
                  <small class="text-muted">Total Hours</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="card mb-4">
        <div class="card-header">
          <h6 class="mb-0"><i class="ti ti-filter me-2"></i>Filters</h6>
        </div>
        <div class="card-body">
          <form action="{{ route('payroll.billable-hours.jira-worklogs') }}" method="GET">
            <div class="row">
              <div class="col-md-3 mb-3">
                <label class="form-label">Employee</label>
                <select class="form-select" name="employee_id">
                  <option value="">All Employees</option>
                  @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ ($filters['employee_id'] ?? '') == $employee->id ? 'selected' : '' }}>
                      {{ $employee->name }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-2 mb-3">
                <label class="form-label">Start Date</label>
                <input type="date" class="form-control" name="start_date" value="{{ $filters['start_date'] ?? '' }}">
              </div>
              <div class="col-md-2 mb-3">
                <label class="form-label">End Date</label>
                <input type="date" class="form-control" name="end_date" value="{{ $filters['end_date'] ?? '' }}">
              </div>
              <div class="col-md-3 mb-3">
                <label class="form-label">Issue Key</label>
                <input type="text" class="form-control" name="issue_key" placeholder="e.g., MR-15" value="{{ $filters['issue_key'] ?? '' }}">
              </div>
              <div class="col-md-2 mb-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                  <i class="ti ti-search me-1"></i>Filter
                </button>
                <a href="{{ route('payroll.billable-hours.jira-worklogs') }}" class="btn btn-outline-secondary">
                  <i class="ti ti-x"></i>
                </a>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Worklogs Table -->
      <div class="card">
        <div class="card-body">
          @if($worklogs->isEmpty())
            <div class="text-center py-5 text-muted">
              <i class="ti ti-file-off" style="font-size: 3rem;"></i>
              <p class="mt-3">No worklogs found.</p>
              <a href="{{ route('payroll.billable-hours.import-jira-worklogs') }}" class="btn btn-primary mt-2">
                <i class="ti ti-upload me-1"></i>Import Worklogs
              </a>
            </div>
          @else
            <div class="table-responsive">
              <table class="table table-hover">
                <thead class="table-light">
                  <tr>
                    <th>Employee</th>
                    <th>Issue</th>
                    <th>Started</th>
                    <th>Ended</th>
                    <th>Hours</th>
                    <th>Comment</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($worklogs as $worklog)
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <div class="avatar avatar-sm me-2">
                            <span class="avatar-initial rounded-circle bg-label-primary">
                              {{ substr($worklog->employee->name ?? '?', 0, 1) }}
                            </span>
                          </div>
                          <div>
                            <h6 class="mb-0">{{ $worklog->employee->name ?? 'Unknown' }}</h6>
                            <small class="text-muted">{{ $worklog->jira_author_name }}</small>
                          </div>
                        </div>
                      </td>
                      <td>
                        <span class="badge bg-label-info">{{ $worklog->issue_key }}</span>
                        <small class="d-block text-muted text-truncate" style="max-width: 200px;" title="{{ $worklog->issue_summary }}">
                          {{ $worklog->issue_summary }}
                        </small>
                      </td>
                      <td>
                        <span>{{ $worklog->worklog_started->format('M d, Y') }}</span>
                        <small class="d-block text-muted">{{ $worklog->worklog_started->format('g:i A') }}</small>
                      </td>
                      <td>
                        @php
                          $endTime = $worklog->worklog_started->copy()->addMinutes((int)($worklog->time_spent_hours * 60));
                        @endphp
                        <span>{{ $endTime->format('M d, Y') }}</span>
                        <small class="d-block text-muted">{{ $endTime->format('g:i A') }}</small>
                      </td>
                      <td>
                        <span class="badge bg-success">{{ number_format($worklog->time_spent_hours, 2) }} hrs</span>
                      </td>
                      <td>
                        @if($worklog->comment)
                          <small class="text-muted text-truncate d-block" style="max-width: 150px;" title="{{ $worklog->comment }}">
                            {{ $worklog->comment }}
                          </small>
                        @else
                          <span class="text-muted">-</span>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
              {{ $worklogs->withQueryString()->links() }}
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
