@extends('layouts/layoutMaster')

@section('title', 'Projects')

@section('vendor-style')
<style>
  .stat-card {
    transition: transform 0.2s ease-in-out;
  }
  .stat-card:hover {
    transform: translateY(-2px);
  }
  .project-card {
    transition: all 0.2s ease-in-out;
    border-left: 4px solid transparent;
  }
  .project-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
  }
  .project-card.health-green { border-left-color: #28c76f; }
  .project-card.health-yellow { border-left-color: #ff9f43; }
  .project-card.health-red { border-left-color: #ea5455; }
  .progress-circle {
    width: 60px;
    height: 60px;
    position: relative;
  }
  .progress-circle-inner {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 0.875rem;
    font-weight: 600;
  }
  .mini-stat {
    font-size: 0.75rem;
    color: #6e6b7b;
  }
  .mini-stat .value {
    font-weight: 600;
    font-size: 0.875rem;
  }
  .health-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
  }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Portfolio Summary Stats -->
  <div class="row mb-4">
    <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
      <div class="card stat-card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <h3 class="fw-bold mb-1">{{ $portfolioStats['active_projects'] }}</h3>
              <span class="text-muted">Active Projects</span>
            </div>
            <div class="avatar avatar-sm bg-label-primary rounded-circle">
              <span class="avatar-initial"><i class="ti ti-folder"></i></span>
            </div>
          </div>
          <div class="mt-3">
            <small class="text-muted">Total: {{ $portfolioStats['total_projects'] }}</small>
            @if($portfolioStats['overdue_count'] > 0)
              <span class="badge bg-danger ms-2">{{ $portfolioStats['overdue_count'] }} Overdue</span>
            @endif
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
      <div class="card stat-card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <h3 class="fw-bold mb-1">{{ number_format($portfolioStats['total_revenue'], 0) }}</h3>
              <span class="text-muted">Total Revenue</span>
            </div>
            <div class="avatar avatar-sm bg-label-success rounded-circle">
              <span class="avatar-initial"><i class="ti ti-currency-dollar"></i></span>
            </div>
          </div>
          <div class="mt-3">
            <small class="text-{{ $portfolioStats['overall_margin'] >= 20 ? 'success' : ($portfolioStats['overall_margin'] >= 0 ? 'warning' : 'danger') }}">
              {{ $portfolioStats['overall_margin'] }}% Margin
            </small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
      <div class="card stat-card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <h3 class="fw-bold mb-1">{{ number_format($portfolioStats['total_profit'], 0) }}</h3>
              <span class="text-muted">Total Profit</span>
            </div>
            <div class="avatar avatar-sm bg-label-{{ $portfolioStats['total_profit'] >= 0 ? 'success' : 'danger' }} rounded-circle">
              <span class="avatar-initial"><i class="ti ti-chart-line"></i></span>
            </div>
          </div>
          <div class="mt-3">
            <small class="text-muted">Costs: {{ number_format($portfolioStats['total_costs'], 0) }}</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
      <div class="card stat-card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <h3 class="fw-bold mb-1">{{ number_format($portfolioStats['total_hours'], 0) }}h</h3>
              <span class="text-muted">Total Hours</span>
            </div>
            <div class="avatar avatar-sm bg-label-info rounded-circle">
              <span class="avatar-initial"><i class="ti ti-clock"></i></span>
            </div>
          </div>
          <div class="mt-3">
            <small class="text-muted">Avg Completion: {{ $portfolioStats['average_completion'] }}%</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Health Distribution -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body py-3">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-4">
              <span class="text-muted">Health Status:</span>
              <div class="d-flex align-items-center">
                <span class="health-indicator bg-success me-2"></span>
                <span class="me-3">{{ $portfolioStats['health_distribution']['green'] ?? 0 }} On Track</span>
              </div>
              <div class="d-flex align-items-center">
                <span class="health-indicator bg-warning me-2"></span>
                <span class="me-3">{{ $portfolioStats['health_distribution']['yellow'] ?? 0 }} At Risk</span>
              </div>
              <div class="d-flex align-items-center">
                <span class="health-indicator bg-danger me-2"></span>
                <span>{{ $portfolioStats['health_distribution']['red'] ?? 0 }} Critical</span>
              </div>
            </div>
            <div class="d-flex gap-2">
              <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                  <i class="ti ti-apps me-1"></i>Tools
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                    <a class="dropdown-item" href="{{ route('projects.templates.index') }}">
                      <i class="ti ti-template me-2"></i>Project Templates
                    </a>
                  </li>
                  <li>
                    <a class="dropdown-item" href="{{ route('projects.capacity.index') }}">
                      <i class="ti ti-calendar-stats me-2"></i>Capacity Planning
                    </a>
                  </li>
                  <li>
                    <a class="dropdown-item" href="{{ route('projects.reports.index') }}">
                      <i class="ti ti-report me-2"></i>Reports
                    </a>
                  </li>
                  <li>
                    <a class="dropdown-item" href="{{ route('projects.followups') }}">
                      <i class="ti ti-bell me-2"></i>Follow-ups
                    </a>
                  </li>
                </ul>
              </div>
              <form action="{{ route('projects.sync-jira') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm">
                  <i class="ti ti-refresh me-1"></i>Sync Jira
                </button>
              </form>
              <a href="{{ route('projects.create') }}" class="btn btn-primary btn-sm">
                <i class="ti ti-plus me-1"></i>Add Project
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

  @if (session('error'))
    <div class="alert alert-danger alert-dismissible mb-4" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- Filters -->
  <div class="card mb-4">
    <div class="card-body">
      <form action="{{ route('projects.index') }}" method="GET" class="row g-3">
        <div class="col-md-2">
          <label class="form-label">Search</label>
          <input type="text" class="form-control form-control-sm" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name or code...">
        </div>
        <div class="col-md-2">
          <label class="form-label">Customer</label>
          <select class="form-select form-select-sm" name="customer_id">
            <option value="">All Customers</option>
            @foreach($customers as $customer)
              <option value="{{ $customer->id }}" {{ ($filters['customer_id'] ?? '') == $customer->id ? 'selected' : '' }}>
                {{ $customer->display_name }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Status</label>
          <select class="form-select form-select-sm" name="status">
            <option value="active" {{ ($filters['status'] ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            <option value="all" {{ ($filters['status'] ?? '') === 'all' ? 'selected' : '' }}>All</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Health</label>
          <select class="form-select form-select-sm" name="health_status">
            <option value="">All Health</option>
            @foreach($healthStatuses as $value => $label)
              <option value="{{ $value }}" {{ ($filters['health_status'] ?? '') === $value ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Phase</label>
          <select class="form-select form-select-sm" name="phase">
            <option value="">All Phases</option>
            @foreach($phases as $value => $label)
              <option value="{{ $value }}" {{ ($filters['phase'] ?? '') === $value ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 d-flex align-items-end gap-2">
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="ti ti-filter me-1"></i>Filter
          </button>
          <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-x"></i>
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- Projects Grid -->
  <div class="row">
    @forelse($projects as $project)
      @php $summary = $projectSummaries[$project->id] ?? []; @endphp
      <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
        <div class="card project-card h-100 health-{{ $project->health_status ?? 'green' }}">
          <div class="card-body">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div class="flex-grow-1">
                <div class="d-flex align-items-center mb-1">
                  <span class="badge bg-label-primary me-2">{{ $project->code }}</span>
                  @if($project->health_status)
                    <span class="health-indicator bg-{{ $summary['health_color'] ?? 'secondary' }}" title="{{ $project->health_status_label ?? 'Unknown' }}"></span>
                  @endif
                </div>
                <h5 class="mb-0">
                  <a href="{{ route('projects.show', $project) }}" class="text-body">
                    {{ \Illuminate\Support\Str::limit($project->name, 35) }}
                  </a>
                </h5>
                @if($project->customer)
                  <small class="text-muted">
                    <a href="{{ route('administration.customers.show', $project->customer) }}" class="text-muted">
                      {{ $project->customer->display_name }}
                    </a>
                  </small>
                @endif
              </div>
              <div class="dropdown">
                <button type="button" class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                  <i class="ti ti-dots-vertical"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                  <a class="dropdown-item" href="{{ route('projects.show', $project) }}">
                    <i class="ti ti-eye me-1"></i> View Dashboard
                  </a>
                  <a class="dropdown-item" href="{{ route('projects.finance.index', $project) }}">
                    <i class="ti ti-chart-bar me-1"></i> Financials
                  </a>
                  <a class="dropdown-item" href="{{ route('projects.edit', $project) }}">
                    <i class="ti ti-pencil me-1"></i> Edit
                  </a>
                  <div class="dropdown-divider"></div>
                  <form action="{{ route('projects.destroy', $project) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Delete this project?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger">
                      <i class="ti ti-trash me-1"></i> Delete
                    </button>
                  </form>
                </div>
              </div>
            </div>

            <!-- Progress Circle & Stats -->
            <div class="d-flex align-items-center mb-3">
              <div class="progress-circle me-3">
                <svg viewBox="0 0 36 36" class="circular-chart">
                  <path class="circle-bg" stroke="#eee" stroke-width="3" fill="none"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                  <path class="circle" stroke="{{ ($summary['completion'] ?? 0) >= 100 ? '#28c76f' : '#7367f0' }}"
                        stroke-width="3" stroke-linecap="round" fill="none"
                        stroke-dasharray="{{ $summary['completion'] ?? 0 }}, 100"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                </svg>
                <div class="progress-circle-inner">{{ round($summary['completion'] ?? 0) }}%</div>
              </div>
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between mini-stat mb-1">
                  <span>Hours</span>
                  <span class="value">{{ number_format($summary['total_hours'] ?? 0, 1) }}h</span>
                </div>
                <div class="d-flex justify-content-between mini-stat mb-1">
                  <span>Revenue</span>
                  <span class="value">{{ number_format($summary['received_revenue'] ?? 0, 0) }}</span>
                </div>
                <div class="d-flex justify-content-between mini-stat">
                  <span>Profit</span>
                  <span class="value text-{{ ($summary['gross_profit'] ?? 0) >= 0 ? 'success' : 'danger' }}">
                    {{ number_format($summary['gross_profit'] ?? 0, 0) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Footer Stats -->
            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
              <div class="d-flex align-items-center gap-3">
                @if($summary['team_size'] ?? 0)
                  <span class="d-flex align-items-center" title="Team Size">
                    <i class="ti ti-users ti-xs text-muted me-1"></i>
                    <small>{{ $summary['team_size'] }}</small>
                  </span>
                @endif
                @if($summary['open_issues'] ?? 0)
                  <span class="d-flex align-items-center" title="Open Issues">
                    <i class="ti ti-list-check ti-xs text-muted me-1"></i>
                    <small>{{ $summary['open_issues'] }}</small>
                  </span>
                @endif
                @if($summary['gross_margin'] ?? false)
                  <span class="badge bg-{{ $summary['gross_margin'] >= 20 ? 'success' : ($summary['gross_margin'] >= 0 ? 'warning' : 'danger') }}" title="Margin">
                    {{ $summary['gross_margin'] }}%
                  </span>
                @endif
              </div>
              <div>
                @if($summary['is_overdue'] ?? false)
                  <span class="badge bg-danger">Overdue</span>
                @elseif(isset($summary['days_until_deadline']) && $summary['days_until_deadline'] !== null && $summary['days_until_deadline'] <= 7)
                  <span class="badge bg-warning">{{ $summary['days_until_deadline'] }}d left</span>
                @elseif($project->is_active)
                  <span class="badge bg-success">Active</span>
                @else
                  <span class="badge bg-secondary">Inactive</span>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12">
        <div class="card">
          <div class="card-body text-center py-5">
            <i class="ti ti-folder-off ti-lg text-muted mb-3"></i>
            <h5>No projects found</h5>
            <p class="text-muted mb-3">Get started by creating a project or syncing from Jira.</p>
            <div class="d-flex justify-content-center gap-2">
              <a href="{{ route('projects.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>Create Project
              </a>
              <form action="{{ route('projects.sync-jira') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary">
                  <i class="ti ti-refresh me-1"></i>Sync from Jira
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    @endforelse
  </div>

  <!-- Pagination -->
  @if($projects->hasPages())
    <div class="d-flex justify-content-center mt-4">
      {{ $projects->withQueryString()->links() }}
    </div>
  @endif
</div>
@endsection

@section('page-script')
<style>
  .circular-chart {
    display: block;
    margin: 0 auto;
    max-width: 100%;
    max-height: 100%;
  }
  .circle-bg {
    fill: none;
  }
  .circle {
    fill: none;
    animation: progress 1s ease-out forwards;
  }
  @keyframes progress {
    0% { stroke-dasharray: 0 100; }
  }
</style>
@endsection
