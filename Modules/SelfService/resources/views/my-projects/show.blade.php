@extends('layouts/layoutMaster')

@section('title', $project->name)

@section('page-style')
<style>
  .project-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 0.5rem;
    padding: 2rem;
    color: white;
    margin-bottom: 1.5rem;
  }
  .stat-card {
    text-align: center;
    padding: 1rem;
  }
  .stat-value {
    font-size: 1.75rem;
    font-weight: 700;
  }
  .project-code {
    background: rgba(255,255,255,0.2);
    padding: 0.25rem 0.75rem;
    border-radius: 0.25rem;
    font-family: monospace;
    font-size: 1rem;
  }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Project Header -->
  <div class="project-header">
    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <span class="project-code">{{ $project->code }}</span>
        @if($project->is_active)
          <span class="badge bg-success ms-2">Active</span>
        @else
          <span class="badge bg-secondary ms-2">Inactive</span>
        @endif
      </div>
      <div>
        <a href="{{ route('self-service.projects.index') }}" class="btn btn-outline-light btn-sm">
          <i class="ti tabler-arrow-left me-1"></i>Back to My Projects
        </a>
      </div>
    </div>
    <h2 class="mb-2">{{ $project->name }}</h2>
    @if($project->customer)
      <p class="mb-0 opacity-75">
        <i class="ti tabler-building me-1"></i>{{ $project->customer->display_name }}
      </p>
    @endif
    @if($project->description)
      <p class="mb-0 mt-2 opacity-75">{{ $project->description }}</p>
    @endif
  </div>

  <!-- Stats Cards -->
  <div class="row mb-4">
    <div class="col-md-4 mb-3 mb-md-0">
      <div class="card h-100">
        <div class="card-body stat-card">
          <div class="stat-value text-primary">{{ number_format($lifetimeHours, 1) }}</div>
          <small class="text-muted">Total Hours (Lifetime)</small>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-3 mb-md-0">
      <div class="card h-100">
        <div class="card-body stat-card">
          <div class="stat-value text-info">{{ $project->employees->count() }}</div>
          <small class="text-muted">Team Members</small>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body stat-card">
          <div class="stat-value text-success">{{ $worklogs->count() }}</div>
          <small class="text-muted">Work Entries</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Team Members Section -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">
            <i class="ti tabler-users me-2 text-info"></i>Team Members
            <span class="badge bg-info ms-2">{{ $project->employees->count() }}</span>
          </h5>
        </div>
        <div class="card-body">
          @if($project->employees->count() > 0)
            <div class="row">
              @foreach($project->employees->sortByDesc('pivot.role') as $emp)
                <div class="col-md-4 col-sm-6 mb-3">
                  <div class="d-flex align-items-center p-2 border rounded {{ $emp->id === $employee->id ? 'bg-light' : '' }}">
                    <div class="avatar avatar-sm me-3" style="background-color: {{ '#' . substr(md5($emp->name), 0, 6) }}; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                      {{ strtoupper(substr($emp->name, 0, 2)) }}
                    </div>
                    <div class="flex-grow-1">
                      <h6 class="mb-0">
                        {{ $emp->name }}
                        @if($emp->id === $employee->id)
                          <span class="badge bg-label-success ms-1">You</span>
                        @endif
                      </h6>
                      <small class="text-muted">{{ $emp->position ?? 'Team Member' }}</small>
                      @if($emp->pivot->role === 'lead')
                        <span class="badge bg-label-warning ms-1">Lead</span>
                      @endif
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- Hours by Employee Section -->
    <div class="col-lg-6 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">
              <i class="ti tabler-clock me-2 text-info"></i>Hours by Employee
            </h5>
            <span class="badge bg-info">
              {{ number_format($totalHours, 1) }}h
              @if($startDate && $endDate)
                (filtered)
              @else
                (lifetime)
              @endif
            </span>
          </div>
          <!-- Date Filter -->
          <form action="{{ route('self-service.projects.show', $project) }}" method="GET" class="row g-2">
            <div class="col-4">
              <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}" placeholder="From">
            </div>
            <div class="col-4">
              <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}" placeholder="To">
            </div>
            <div class="col-4">
              <div class="btn-group w-100">
                <button type="submit" class="btn btn-sm btn-primary">
                  <i class="ti tabler-filter"></i>
                </button>
                @if($startDate && $endDate)
                  <a href="{{ route('self-service.projects.show', $project) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="ti tabler-x"></i>
                  </a>
                @endif
              </div>
            </div>
          </form>
        </div>
        <div class="card-body">
          @if($worklogsByEmployee->count() > 0)
            <div class="table-responsive">
              <table class="table table-sm">
                <thead class="table-light">
                  <tr>
                    <th>Employee</th>
                    <th class="text-end">Hours</th>
                    <th class="text-end">Entries</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($worklogsByEmployee as $employeeId => $data)
                    <tr class="{{ $employeeId == $employee->id ? 'table-info' : '' }}">
                      <td>
                        @if($data['employee'])
                          {{ $data['employee']->name }}
                          @if($data['employee']->id === $employee->id)
                            <span class="badge bg-label-success ms-1">You</span>
                          @endif
                        @else
                          <span class="text-muted">Unknown</span>
                        @endif
                      </td>
                      <td class="text-end fw-semibold">{{ number_format($data['total_hours'], 1) }}h</td>
                      <td class="text-end">{{ $data['entries']->count() }}</td>
                    </tr>
                  @endforeach
                </tbody>
                <tfoot class="table-light">
                  <tr>
                    <th>Total</th>
                    <th class="text-end">{{ number_format($totalHours, 1) }}h</th>
                    <th class="text-end">{{ $worklogs->count() }}</th>
                  </tr>
                </tfoot>
              </table>
            </div>
          @else
            <div class="text-center py-4">
              <i class="ti tabler-clock-off display-6 text-muted mb-3 d-block"></i>
              <p class="text-muted">No hours logged for this period.</p>
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Recent Work Entries -->
    <div class="col-lg-6 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="ti tabler-list me-2 text-warning"></i>Recent Work Entries
          </h5>
          <span class="badge bg-secondary">{{ $worklogs->count() }} entries</span>
        </div>
        <div class="card-body">
          @if($worklogs->count() > 0)
            <div class="table-responsive">
              <table class="table table-sm table-hover">
                <thead class="table-light">
                  <tr>
                    <th>Date</th>
                    <th>Issue</th>
                    <th class="text-end">Hours</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($worklogs->take(15) as $worklog)
                    <tr class="{{ $worklog->employee_id == $employee->id ? 'table-info' : '' }}">
                      <td>{{ $worklog->worklog_started->format('M d') }}</td>
                      <td>
                        <span class="badge bg-label-primary">{{ $worklog->issue_key }}</span>
                        <br>
                        <small class="text-muted">{{ \Illuminate\Support\Str::limit($worklog->issue_summary ?? '', 30) }}</small>
                      </td>
                      <td class="text-end fw-semibold">{{ number_format($worklog->time_spent_hours, 1) }}h</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            @if($worklogs->count() > 15)
              <div class="text-center mt-3">
                <small class="text-muted">Showing 15 of {{ $worklogs->count() }} entries</small>
              </div>
            @endif
          @else
            <div class="text-center py-4">
              <i class="ti tabler-clock-off display-6 text-muted mb-3 d-block"></i>
              <p class="text-muted">No work entries found for this period.</p>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
