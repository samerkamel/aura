@extends('layouts/layoutMaster')

@section('title', 'Capacity Planning')

@section('vendor-style')
@vite('resources/assets/vendor/libs/flatpickr/flatpickr.scss')
@endsection

@section('vendor-script')
@vite('resources/assets/vendor/libs/flatpickr/flatpickr.js')
@endsection

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
                <span class="avatar-initial rounded-circle bg-label-info">
                  <i class="ti ti-calendar-stats ti-lg"></i>
                </span>
              </div>
              <div>
                <h4 class="mb-0">Capacity Planning</h4>
                <p class="text-muted mb-0">Resource allocation and availability overview</p>
              </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
              <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Projects
              </a>
              <form method="GET" action="{{ route('projects.capacity.index') }}" class="d-flex gap-2">
                <input type="text" name="start_date" class="form-control flatpickr" value="{{ $startDate->format('Y-m-d') }}" style="width: 130px;">
                <span class="d-flex align-items-center px-2">to</span>
                <input type="text" name="end_date" class="form-control flatpickr" value="{{ $endDate->format('Y-m-d') }}" style="width: 130px;">
                <button type="submit" class="btn btn-primary">
                  <i class="ti ti-filter"></i>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Summary Stats -->
  <div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <p class="mb-1 text-muted">Team Size</p>
              <h3 class="mb-0">{{ $employees->count() }}</h3>
              <small class="text-muted">Active employees</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="ti ti-users"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <p class="mb-1 text-muted">Active Projects</p>
              <h3 class="mb-0">{{ $projects->count() }}</h3>
              <small class="text-muted">In date range</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info">
                <i class="ti ti-folder"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <p class="mb-1 text-muted">Avg Utilization</p>
              <h3 class="mb-0 {{ $utilizationRate > 100 ? 'text-danger' : ($utilizationRate < 50 ? 'text-warning' : 'text-success') }}">
                {{ number_format($utilizationRate, 0) }}%
              </h3>
              <small class="text-muted">Team capacity used</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-{{ $utilizationRate > 100 ? 'danger' : ($utilizationRate < 50 ? 'warning' : 'success') }}">
                <i class="ti ti-chart-pie"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <p class="mb-1 text-muted">Available</p>
              <h3 class="mb-0">{{ $unallocatedEmployees->count() }}</h3>
              <small class="text-muted">Unassigned team members</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-secondary">
                <i class="ti ti-user-check"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Alerts -->
  @if($overallocatedEmployees->count() > 0)
  <div class="alert alert-danger mb-4">
    <div class="d-flex align-items-center">
      <i class="ti ti-alert-triangle me-2"></i>
      <strong>Overallocated Resources:</strong>
      <span class="ms-2">
        @foreach($overallocatedEmployees as $employee)
          {{ $employee->full_name }} ({{ $employee->projects->sum('pivot.allocation_percentage') }}%){{ !$loop->last ? ', ' : '' }}
        @endforeach
      </span>
    </div>
  </div>
  @endif

  @if($underutilizedEmployees->count() > 0)
  <div class="alert alert-warning mb-4">
    <div class="d-flex align-items-center">
      <i class="ti ti-info-circle me-2"></i>
      <strong>Underutilized Resources:</strong>
      <span class="ms-2">
        @foreach($underutilizedEmployees->take(5) as $employee)
          {{ $employee->full_name }} ({{ $employee->projects->sum('pivot.allocation_percentage') }}%){{ !$loop->last ? ', ' : '' }}
        @endforeach
        @if($underutilizedEmployees->count() > 5)
          ... and {{ $underutilizedEmployees->count() - 5 }} more
        @endif
      </span>
    </div>
  </div>
  @endif

  <div class="row">
    <!-- Capacity Heatmap -->
    <div class="col-12 mb-4">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0"><i class="ti ti-grid-dots me-2"></i>Capacity Heatmap</h5>
          <div class="d-flex gap-3">
            <div class="d-flex align-items-center">
              <span class="badge bg-success me-2" style="width: 20px; height: 20px;"></span>
              <small>Available</small>
            </div>
            <div class="d-flex align-items-center">
              <span class="badge bg-info me-2" style="width: 20px; height: 20px;"></span>
              <small>Optimal (50-100%)</small>
            </div>
            <div class="d-flex align-items-center">
              <span class="badge bg-warning me-2" style="width: 20px; height: 20px;"></span>
              <small>Underutilized (&lt;50%)</small>
            </div>
            <div class="d-flex align-items-center">
              <span class="badge bg-danger me-2" style="width: 20px; height: 20px;"></span>
              <small>Overallocated (&gt;100%)</small>
            </div>
          </div>
        </div>
        <div class="card-body">
          @if($capacityData && count($capacityData) > 0)
          <div class="table-responsive">
            <table class="table table-sm table-bordered capacity-heatmap">
              <thead>
                <tr>
                  <th style="min-width: 180px;">Employee</th>
                  @foreach($weeks as $week)
                    <th class="text-center" style="min-width: 80px;">{{ $week['label'] }}</th>
                  @endforeach
                  <th class="text-center" style="min-width: 80px;">Avg</th>
                </tr>
              </thead>
              <tbody>
                @foreach($capacityData as $row)
                  @php
                    $avgAllocation = collect($row['weeks'])->avg('allocation');
                  @endphp
                  <tr>
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-2">
                          <span class="avatar-initial rounded-circle bg-label-primary">
                            {{ strtoupper(substr($row['employee']->name, 0, 1)) }}
                          </span>
                        </div>
                        {{ $row['employee']->full_name }}
                      </div>
                    </td>
                    @foreach($row['weeks'] as $weekData)
                      @php
                        $bgColor = match($weekData['status']) {
                          'available' => 'bg-success',
                          'underutilized' => 'bg-warning',
                          'optimal' => 'bg-info',
                          'overallocated' => 'bg-danger',
                          default => 'bg-secondary'
                        };
                        $opacity = min(1, $weekData['allocation'] / 100) * 0.7 + 0.3;
                      @endphp
                      <td class="text-center {{ $bgColor }}" style="opacity: {{ $opacity }};">
                        <span class="fw-semibold {{ $weekData['allocation'] > 0 ? 'text-white' : 'text-muted' }}">
                          {{ $weekData['allocation'] > 0 ? $weekData['allocation'] . '%' : '-' }}
                        </span>
                      </td>
                    @endforeach
                    <td class="text-center {{ $avgAllocation > 100 ? 'bg-danger' : ($avgAllocation >= 50 ? 'bg-info' : ($avgAllocation > 0 ? 'bg-warning' : 'bg-success')) }}">
                      <span class="fw-semibold {{ $avgAllocation > 0 ? 'text-white' : 'text-muted' }}">
                        {{ $avgAllocation > 0 ? number_format($avgAllocation, 0) . '%' : '-' }}
                      </span>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          @else
            <div class="text-center py-5">
              <i class="ti ti-calendar-off text-muted mb-3" style="font-size: 3rem;"></i>
              <h5 class="text-muted">No capacity data available</h5>
              <p class="text-muted">Assign employees to projects to see capacity planning.</p>
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Project Allocations -->
    <div class="col-lg-8 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title mb-0"><i class="ti ti-folder me-2"></i>Project Allocations</h5>
        </div>
        <div class="card-body">
          @if($projects->count() > 0)
            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Project</th>
                    <th>Customer</th>
                    <th>Duration</th>
                    <th>Team</th>
                    <th>Total Allocation</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($projects as $project)
                    @php
                      $totalAllocation = $project->employees->sum('pivot.allocation_percentage');
                    @endphp
                    <tr>
                      <td>
                        <a href="{{ route('projects.show', $project) }}" class="fw-semibold">
                          {{ $project->name }}
                        </a>
                        <br>
                        <small class="text-muted">{{ $project->code }}</small>
                      </td>
                      <td>{{ $project->customer?->display_name ?? '-' }}</td>
                      <td>
                        @if($project->planned_start_date && $project->planned_end_date)
                          <small>
                            {{ $project->planned_start_date->format('M d') }} - {{ $project->planned_end_date->format('M d, Y') }}
                          </small>
                        @else
                          <span class="text-muted">-</span>
                        @endif
                      </td>
                      <td>
                        <span class="badge bg-label-primary">
                          {{ $project->employees->count() }} members
                        </span>
                      </td>
                      <td>
                        <span class="badge bg-label-{{ $totalAllocation > 300 ? 'danger' : ($totalAllocation >= 100 ? 'success' : 'warning') }}">
                          {{ $totalAllocation }}%
                        </span>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <div class="text-center py-4">
              <i class="ti ti-folder-off text-muted mb-2" style="font-size: 2rem;"></i>
              <p class="text-muted mb-0">No active projects in this date range.</p>
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Sidebar Stats -->
    <div class="col-lg-4">
      <!-- Unallocated Employees -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="ti ti-user-search me-2"></i>Available Team Members
            <span class="badge bg-success ms-2">{{ $unallocatedEmployees->count() }}</span>
          </h5>
        </div>
        <div class="card-body">
          @if($unallocatedEmployees->count() > 0)
            <div class="list-group list-group-flush">
              @foreach($unallocatedEmployees->take(10) as $employee)
                <div class="list-group-item d-flex align-items-center px-0">
                  <div class="avatar avatar-sm me-3">
                    <span class="avatar-initial rounded-circle bg-label-secondary">
                      {{ strtoupper(substr($employee->name, 0, 1)) }}
                    </span>
                  </div>
                  <div class="flex-grow-1">
                    <span>{{ $employee->full_name }}</span>
                    @if($employee->job_title)
                      <br><small class="text-muted">{{ $employee->job_title }}</small>
                    @endif
                  </div>
                  <span class="badge bg-success">Available</span>
                </div>
              @endforeach
            </div>
            @if($unallocatedEmployees->count() > 10)
              <p class="text-muted mb-0 mt-2">
                And {{ $unallocatedEmployees->count() - 10 }} more...
              </p>
            @endif
          @else
            <p class="text-muted mb-0">All team members are assigned to projects.</p>
          @endif
        </div>
      </div>

      <!-- Overallocated Employees -->
      @if($overallocatedEmployees->count() > 0)
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="ti ti-alert-triangle me-2 text-danger"></i>Overallocated
            <span class="badge bg-danger ms-2">{{ $overallocatedEmployees->count() }}</span>
          </h5>
        </div>
        <div class="card-body">
          <div class="list-group list-group-flush">
            @foreach($overallocatedEmployees as $employee)
              @php
                $totalAllocation = $employee->projects->sum('pivot.allocation_percentage');
              @endphp
              <div class="list-group-item px-0">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fw-semibold">{{ $employee->full_name }}</span>
                  <span class="badge bg-danger">{{ $totalAllocation }}%</span>
                </div>
                <div class="small">
                  @foreach($employee->projects as $project)
                    <span class="badge bg-label-secondary mb-1">
                      {{ $project->code }}: {{ $project->pivot->allocation_percentage }}%
                    </span>
                  @endforeach
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
      @endif
    </div>
  </div>
</div>

<style>
.capacity-heatmap td {
  transition: opacity 0.2s ease;
}
.capacity-heatmap tbody tr:hover td {
  opacity: 1 !important;
}
</style>

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  if (typeof flatpickr !== 'undefined') {
    flatpickr('.flatpickr', {
      dateFormat: 'Y-m-d'
    });
  }
});
</script>
@endsection
@endsection
