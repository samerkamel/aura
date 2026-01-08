@extends('layouts/layoutMaster')

@section('title', $project->name . ' Dashboard')

@section('vendor-style')
<style>
  .dashboard-header {
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
    font-size: 0.875rem;
  }
  .kpi-card {
    transition: transform 0.2s ease-in-out;
    border-left: 4px solid transparent;
  }
  .kpi-card:hover {
    transform: translateY(-2px);
  }
  .kpi-card.success { border-left-color: #28c76f; }
  .kpi-card.warning { border-left-color: #ff9f43; }
  .kpi-card.danger { border-left-color: #ea5455; }
  .kpi-card.info { border-left-color: #00cfe8; }
  .kpi-card.primary { border-left-color: #7367f0; }
  .progress-circle-lg {
    width: 100px;
    height: 100px;
    position: relative;
  }
  .progress-circle-lg .progress-value {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 1.5rem;
    font-weight: 700;
  }
  .progress-circle-lg .progress-label {
    position: absolute;
    top: 60%;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.625rem;
    color: #6e6b7b;
    margin-top: 0.5rem;
  }
  .circular-chart-lg {
    display: block;
    max-width: 100%;
    max-height: 100%;
  }
  .health-badge {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
  }
  .alert-card {
    border-left: 4px solid;
  }
  .alert-card.alert-danger { border-left-color: #ea5455; }
  .alert-card.alert-warning { border-left-color: #ff9f43; }
  .alert-card.alert-info { border-left-color: #00cfe8; }
  .activity-timeline {
    position: relative;
    padding-left: 1.5rem;
  }
  .activity-timeline::before {
    content: '';
    position: absolute;
    left: 0.375rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #eee;
  }
  .activity-item {
    position: relative;
    padding-bottom: 1rem;
  }
  .activity-item::before {
    content: '';
    position: absolute;
    left: -1.125rem;
    top: 0.25rem;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #7367f0;
  }
  .activity-item.worklog::before { background: #7367f0; }
  .activity-item.revenue::before { background: #28c76f; }
  .activity-item.cost::before { background: #ff9f43; }
  .mini-stat-label {
    font-size: 0.75rem;
    color: #6e6b7b;
  }
  .mini-stat-value {
    font-size: 1.125rem;
    font-weight: 600;
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

  <!-- Dashboard Header -->
  <div class="dashboard-header">
    <div class="row align-items-center">
      <div class="col-lg-8">
        <div class="d-flex align-items-center mb-2">
          <span class="project-code me-2">{{ $project->code }}</span>
          @if($dashboard['overview']['health_status'])
            <span class="health-badge bg-{{ $dashboard['overview']['health_color'] }} me-2" title="{{ $dashboard['overview']['health_label'] }}"></span>
          @endif
          @if($project->is_active)
            <span class="badge bg-success">Active</span>
          @else
            <span class="badge bg-secondary">Inactive</span>
          @endif
          @if($project->phase)
            <span class="badge bg-info ms-2">{{ $dashboard['overview']['phase_label'] }}</span>
          @endif
        </div>
        <h2 class="mb-1">{{ $project->name }}</h2>
        @if($project->customer)
          <p class="mb-0 opacity-75">
            <i class="ti ti-building me-1"></i>{{ $project->customer->display_name }}
            @if($dashboard['overview']['project_manager'])
              <span class="mx-2">|</span>
              <i class="ti ti-user me-1"></i>PM: {{ $dashboard['overview']['project_manager'] }}
            @endif
          </p>
        @endif
      </div>
      <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
        <div class="btn-group mb-2">
          <a href="{{ route('projects.finance.index', $project) }}" class="btn btn-light btn-sm">
            <i class="ti ti-chart-pie-2 me-1"></i>Finance
          </a>
          <a href="{{ route('projects.tasks', $project) }}" class="btn btn-light btn-sm">
            <i class="ti ti-subtask me-1"></i>Tasks
          </a>
          <a href="{{ route('projects.manage-employees', $project) }}" class="btn btn-light btn-sm">
            <i class="ti ti-users me-1"></i>Team
          </a>
        </div>
        <div class="btn-group mb-2">
          <button type="button" class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
            <i class="ti ti-calendar-event me-1"></i>Planning
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="{{ route('projects.planning.milestones', $project) }}"><i class="ti ti-flag me-2"></i>Milestones</a></li>
            <li><a class="dropdown-item" href="{{ route('projects.planning.timeline', $project) }}"><i class="ti ti-chart-line me-2"></i>Timeline</a></li>
            <li><a class="dropdown-item" href="{{ route('projects.planning.time-estimates', $project) }}"><i class="ti ti-clock me-2"></i>Time Estimates</a></li>
            <li><a class="dropdown-item" href="{{ route('projects.planning.risks', $project) }}"><i class="ti ti-alert-triangle me-2"></i>Risks</a></li>
          </ul>
        </div>
        <a href="{{ route('projects.edit', $project) }}" class="btn btn-outline-light btn-sm mb-2">
          <i class="ti ti-pencil me-1"></i>Edit
        </a>
        <a href="{{ route('projects.index') }}" class="btn btn-outline-light btn-sm mb-2">
          <i class="ti ti-arrow-left"></i>
        </a>
      </div>
    </div>
  </div>

  <!-- Alerts Section -->
  @if(count($dashboard['alerts']) > 0)
  <div class="row mb-4">
    <div class="col-12">
      @foreach($dashboard['alerts'] as $alert)
        <div class="alert alert-{{ $alert['type'] }} alert-card d-flex align-items-center mb-2" role="alert">
          <i class="{{ $alert['icon'] }} me-2"></i>
          <div>
            <strong>{{ $alert['title'] }}</strong> - {{ $alert['message'] }}
          </div>
        </div>
      @endforeach
    </div>
  </div>
  @endif

  <!-- KPI Cards Row -->
  <div class="row mb-4">
    <!-- Completion Card -->
    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card kpi-card h-100 {{ $dashboard['progress']['work_progress'] >= 80 ? 'success' : ($dashboard['progress']['work_progress'] >= 50 ? 'info' : 'warning') }}">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="text-muted mb-1">Completion</h6>
              <h3 class="mb-0">{{ round($dashboard['progress']['work_progress']) }}%</h3>
              <small class="text-{{ $dashboard['progress']['schedule_color'] }}">
                {{ ucfirst(str_replace('_', ' ', $dashboard['progress']['schedule_status'])) }}
              </small>
            </div>
            <div class="progress-circle-lg">
              <svg viewBox="0 0 36 36" class="circular-chart-lg">
                <path class="circle-bg" stroke="#eee" stroke-width="3" fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                <path class="circle" stroke="{{ $dashboard['progress']['work_progress'] >= 80 ? '#28c76f' : '#7367f0' }}"
                      stroke-width="3" stroke-linecap="round" fill="none"
                      stroke-dasharray="{{ $dashboard['progress']['work_progress'] }}, 100"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
              </svg>
            </div>
          </div>
          @if($dashboard['progress']['days_remaining'] !== null)
            <div class="mt-2">
              <small class="text-muted">
                @if($dashboard['progress']['is_overdue'])
                  <span class="text-danger">{{ abs($dashboard['overview']['days_until_deadline']) }} days overdue</span>
                @else
                  {{ $dashboard['progress']['days_remaining'] }} days remaining
                @endif
              </small>
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Revenue Card -->
    @can('view-financial-reports')
    <div class="col-xl-3 col-md-6 mb-4">
      <a href="{{ route('projects.finance.revenues', $project) }}" class="text-decoration-none">
        <div class="card kpi-card h-100 success" style="cursor: pointer;">
          <div class="card-body">
            <h6 class="text-muted mb-1">Revenue</h6>
            <h3 class="mb-0">{{ number_format($dashboard['financial']['summary']['received_revenue'], 0) }}</h3>
            <div class="d-flex justify-content-between align-items-center mt-2">
              <small class="text-muted">
                of {{ number_format($dashboard['financial']['summary']['total_revenue'], 0) }} total
              </small>
              <span class="badge bg-label-{{ $dashboard['financial']['revenue_breakdown']['collection_rate'] >= 80 ? 'success' : 'warning' }}">
                {{ $dashboard['financial']['revenue_breakdown']['collection_rate'] }}% collected
              </span>
            </div>
            @if($dashboard['financial']['summary']['pending_revenue'] > 0)
              <small class="text-warning">
                {{ number_format($dashboard['financial']['summary']['pending_revenue'], 0) }} pending
              </small>
            @endif
          </div>
        </div>
      </a>
    </div>

    <!-- Costs Card -->
    <div class="col-xl-3 col-md-6 mb-4">
      <a href="{{ route('projects.finance.costs', $project) }}" class="text-decoration-none">
        <div class="card kpi-card h-100 {{ $dashboard['financial']['summary']['budget_status'] === 'healthy' ? 'info' : ($dashboard['financial']['summary']['budget_status'] === 'warning' ? 'warning' : 'danger') }}" style="cursor: pointer;">
          <div class="card-body">
            <h6 class="text-muted mb-1">Costs</h6>
            <h3 class="mb-0">{{ number_format($dashboard['financial']['summary']['total_spent'], 0) }}</h3>
            <div class="d-flex justify-content-between align-items-center mt-2">
              <small class="text-muted">
                of {{ number_format($dashboard['financial']['summary']['total_budget'], 0) }} budget
              </small>
              <span class="badge bg-label-{{ $dashboard['financial']['summary']['budget_status'] === 'healthy' ? 'success' : ($dashboard['financial']['summary']['budget_status'] === 'warning' ? 'warning' : 'danger') }}">
                {{ $dashboard['financial']['summary']['budget_utilization'] }}% used
              </span>
            </div>
            @if($dashboard['financial']['summary']['budget_remaining'] < 0)
              <small class="text-danger">
                {{ number_format(abs($dashboard['financial']['summary']['budget_remaining']), 0) }} over budget
              </small>
            @endif
          </div>
        </div>
      </a>
    </div>

    <!-- Profit Card -->
    <div class="col-xl-3 col-md-6 mb-4">
      <a href="{{ route('projects.finance.profitability', $project) }}" class="text-decoration-none">
        <div class="card kpi-card h-100 {{ $dashboard['financial']['summary']['is_profitable'] ? 'success' : 'danger' }}" style="cursor: pointer;">
          <div class="card-body">
            <h6 class="text-muted mb-1">Profit</h6>
            <h3 class="mb-0 text-{{ $dashboard['financial']['summary']['gross_profit'] >= 0 ? 'success' : 'danger' }}">
              {{ number_format($dashboard['financial']['summary']['gross_profit'], 0) }}
            </h3>
            <div class="d-flex justify-content-between align-items-center mt-2">
              <small class="text-muted">Gross Margin</small>
              <span class="badge bg-{{ $dashboard['financial']['summary']['gross_margin'] >= 20 ? 'success' : ($dashboard['financial']['summary']['gross_margin'] >= 0 ? 'warning' : 'danger') }}">
                {{ $dashboard['financial']['summary']['gross_margin'] }}%
              </span>
            </div>
            <small class="text-muted">
              ROI: {{ $dashboard['financial']['profitability']['roi'] }}%
            </small>
          </div>
        </div>
      </a>
    </div>
    @endcan
  </div>

  <!-- Second Row: Hours, Team, Issues, Milestones -->
  <div class="row mb-4">
    <div class="col-md-3 mb-4">
      <div class="card h-100">
        <div class="card-body text-center">
          <i class="ti ti-clock ti-lg text-primary mb-2"></i>
          <h3 class="mb-1">{{ number_format($dashboard['progress']['actual_hours'], 1) }}h</h3>
          <small class="text-muted">Total Hours</small>
          @if($dashboard['progress']['budgeted_hours'] > 0)
            <div class="progress mt-2" style="height: 6px;">
              <div class="progress-bar" role="progressbar" style="width: {{ min(100, $dashboard['progress']['hours_utilization']) }}%"></div>
            </div>
            <small class="text-muted">{{ $dashboard['progress']['hours_utilization'] }}% of {{ $dashboard['progress']['budgeted_hours'] }}h budget</small>
          @endif
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-4">
      <div class="card h-100">
        <div class="card-body text-center">
          <i class="ti ti-users ti-lg text-info mb-2"></i>
          <h3 class="mb-1">{{ $dashboard['team']['team_size'] }}</h3>
          <small class="text-muted">Team Members</small>
          @if($dashboard['team']['average_allocation'] > 0)
            <div class="mt-2">
              <small class="text-muted">Avg. {{ $dashboard['team']['average_allocation'] }}% allocation</small>
            </div>
          @endif
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-4">
      <div class="card h-100">
        <div class="card-body text-center">
          <i class="ti ti-list-check ti-lg text-success mb-2"></i>
          <h3 class="mb-1">{{ $dashboard['progress']['issues']['done'] }}/{{ $dashboard['progress']['issues']['total'] }}</h3>
          <small class="text-muted">Issues Completed</small>
          @if($dashboard['progress']['issues']['total'] > 0)
            <div class="progress mt-2" style="height: 6px;">
              <div class="progress-bar bg-success" style="width: {{ $dashboard['progress']['issue_completion'] }}%"></div>
            </div>
            <small class="text-muted">{{ $dashboard['progress']['issue_completion'] }}% done</small>
          @endif
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-4">
      <div class="card h-100">
        <div class="card-body text-center">
          <i class="ti ti-flag ti-lg text-warning mb-2"></i>
          <h3 class="mb-1">{{ $dashboard['milestones']['completed'] }}/{{ $dashboard['milestones']['total'] }}</h3>
          <small class="text-muted">Milestones</small>
          @if($dashboard['milestones']['overdue'] > 0)
            <div class="mt-2">
              <span class="badge bg-danger">{{ $dashboard['milestones']['overdue'] }} overdue</span>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- Left Column: Financial & Progress Details -->
    <div class="col-lg-8">
      @can('view-financial-reports')
      <!-- Financial Summary Card -->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="ti ti-chart-pie me-2 text-primary"></i>Financial Summary</h5>
          <a href="{{ route('projects.finance.index', $project) }}" class="btn btn-sm btn-outline-primary">
            View Details
          </a>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <h6 class="text-muted mb-3">Cost Breakdown</h6>
              @foreach($dashboard['financial']['cost_breakdown']['breakdown'] as $item)
                @if($item['amount'] > 0)
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="d-flex align-items-center">
                    <span class="badge bg-label-{{ $item['color'] }} me-2">{{ $item['label'] }}</span>
                  </div>
                  <span>{{ number_format($item['amount'], 0) }} ({{ $item['percentage'] }}%)</span>
                </div>
                @endif
              @endforeach
            </div>
            <div class="col-md-6">
              <h6 class="text-muted mb-3">Burn Rate</h6>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Daily</span>
                <span class="fw-semibold">{{ number_format($dashboard['financial']['burn_rate']['daily'], 0) }}/day</span>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Monthly</span>
                <span class="fw-semibold">{{ number_format($dashboard['financial']['burn_rate']['monthly'], 0) }}/mo</span>
              </div>
              @if($dashboard['financial']['burn_rate']['runway_days'])
              <div class="d-flex justify-content-between">
                <span class="text-muted">Runway</span>
                <span class="fw-semibold text-{{ $dashboard['financial']['burn_rate']['runway_days'] > 30 ? 'success' : 'warning' }}">
                  {{ $dashboard['financial']['burn_rate']['runway_days'] }} days
                </span>
              </div>
              @endif
            </div>
          </div>
        </div>
      </div>

      <!-- Contracts Section -->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="ti ti-file-text me-2 text-success"></i>Contracts
            <span class="badge bg-success ms-2">{{ $project->contracts->count() }}</span>
          </h5>
          <a href="{{ route('accounting.income.contracts.create', ['project_id' => $project->id, 'customer_id' => $project->customer_id]) }}" class="btn btn-sm btn-success">
            <i class="ti ti-plus me-1"></i>Create
          </a>
        </div>
        <div class="card-body">
          @if($project->contracts->count() > 0)
            <div class="table-responsive">
              <table class="table table-sm table-hover">
                <thead class="table-light">
                  <tr>
                    <th>Contract</th>
                    <th class="text-end">Value</th>
                    <th class="text-end">Paid</th>
                    <th class="text-center">Status</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($project->contracts->take(5) as $contract)
                    <tr>
                      <td>
                        <a href="{{ route('accounting.income.contracts.show', $contract) }}" class="fw-semibold">
                          {{ $contract->contract_number }}
                        </a>
                        <br><small class="text-muted">{{ $contract->customer?->display_name ?? $contract->client_name }}</small>
                      </td>
                      <td class="text-end">{{ number_format($contract->total_amount, 0) }}</td>
                      <td class="text-end">{{ number_format($contract->paid_amount, 0) }}</td>
                      <td class="text-center">
                        <span class="badge bg-{{ $contract->status_color }}">{{ ucfirst($contract->status) }}</span>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            @if($project->contracts->count() > 5)
              <div class="text-center mt-2">
                <a href="{{ route('projects.finance.index', $project) }}" class="btn btn-sm btn-outline-secondary">
                  View all {{ $project->contracts->count() }} contracts
                </a>
              </div>
            @endif
          @else
            <div class="text-center py-3">
              <i class="ti ti-file-off ti-lg text-muted mb-2 d-block"></i>
              <p class="text-muted mb-2">No contracts yet</p>
              <a href="{{ route('accounting.income.contracts.create', ['project_id' => $project->id, 'customer_id' => $project->customer_id]) }}" class="btn btn-sm btn-success">
                <i class="ti ti-plus me-1"></i>Create Contract
              </a>
            </div>
          @endif
        </div>
      </div>
      @endcan

      <!-- Time Estimates Summary -->
      @if($dashboard['timeEstimates']['total'] > 0)
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="ti ti-clock me-2 text-info"></i>Time Estimates</h5>
          <a href="{{ route('projects.planning.time-estimates', $project) }}" class="btn btn-sm btn-outline-info">
            View All
          </a>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4 text-center border-end">
              <h4 class="mb-1">{{ $dashboard['timeEstimates']['estimated_hours'] }}h</h4>
              <small class="text-muted">Estimated</small>
            </div>
            <div class="col-md-4 text-center border-end">
              <h4 class="mb-1">{{ $dashboard['timeEstimates']['actual_hours'] }}h</h4>
              <small class="text-muted">Actual</small>
            </div>
            <div class="col-md-4 text-center">
              <h4 class="mb-1 text-{{ $dashboard['timeEstimates']['hours_variance'] > 0 ? 'danger' : 'success' }}">
                {{ $dashboard['timeEstimates']['hours_variance'] > 0 ? '+' : '' }}{{ $dashboard['timeEstimates']['hours_variance'] }}h
              </h4>
              <small class="text-muted">Variance ({{ $dashboard['timeEstimates']['hours_variance_percentage'] }}%)</small>
            </div>
          </div>
          <div class="mt-3">
            <div class="d-flex justify-content-between mb-2">
              <span>Completion Rate</span>
              <span class="fw-semibold">{{ $dashboard['timeEstimates']['completion_rate'] }}%</span>
            </div>
            <div class="progress" style="height: 8px;">
              <div class="progress-bar bg-success" style="width: {{ $dashboard['timeEstimates']['completion_rate'] }}%"></div>
            </div>
            <div class="d-flex justify-content-between mt-2 small text-muted">
              <span>{{ $dashboard['timeEstimates']['by_status']['completed'] ?? 0 }} completed</span>
              <span>{{ $dashboard['timeEstimates']['by_status']['in_progress'] ?? 0 }} in progress</span>
              <span>{{ $dashboard['timeEstimates']['by_status']['not_started'] ?? 0 }} pending</span>
            </div>
          </div>
        </div>
      </div>
      @endif

      <!-- Hours by Employee -->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0"><i class="ti ti-clock me-2 text-primary"></i>Hours by Employee</h5>
          </div>
          <a href="{{ route('projects.worklogs', $project) }}" class="btn btn-sm btn-outline-primary">
            View All Worklogs
          </a>
        </div>
        <div class="card-body">
          <!-- Date Filter -->
          <form action="{{ route('projects.show', $project) }}" method="GET" class="row g-2 mb-3">
            <div class="col-4">
              <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}" placeholder="From">
            </div>
            <div class="col-4">
              <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}" placeholder="To">
            </div>
            <div class="col-4">
              <div class="btn-group w-100">
                <button type="submit" class="btn btn-sm btn-primary"><i class="ti ti-filter"></i></button>
                @if($startDate && $endDate)
                  <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-x"></i></a>
                @endif
              </div>
            </div>
          </form>

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
                  @foreach($worklogsByEmployee->sortByDesc('total_hours')->take(10) as $employeeId => $data)
                    <tr>
                      <td>
                        @if($data['employee'])
                          <a href="{{ route('hr.employees.show', $data['employee']) }}">{{ $data['employee']->name }}</a>
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
            <div class="text-center py-3">
              <i class="ti ti-clock-off ti-lg text-muted mb-2 d-block"></i>
              <p class="text-muted">No hours logged for this period.</p>
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Right Column: Team, Activity, Risks -->
    <div class="col-lg-4">
      <!-- Team Card -->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="ti ti-users me-2 text-info"></i>Team</h5>
          <a href="{{ route('projects.manage-employees', $project) }}" class="btn btn-sm btn-outline-info">Manage</a>
        </div>
        <div class="card-body">
          @if($dashboard['team']['project_manager'])
            <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
              <div class="avatar avatar-sm me-2" style="background-color: {{ '#' . substr(md5($dashboard['team']['project_manager']['name']), 0, 6) }}; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.75rem;">
                {{ strtoupper(substr($dashboard['team']['project_manager']['name'], 0, 2)) }}
              </div>
              <div>
                <strong>{{ $dashboard['team']['project_manager']['name'] }}</strong>
                <span class="badge bg-warning ms-1">PM</span>
              </div>
            </div>
          @endif

          @if(count($dashboard['team']['top_contributors']) > 0)
            <h6 class="text-muted mb-2">Top Contributors</h6>
            @foreach($dashboard['team']['top_contributors'] as $member)
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-xs me-2" style="background-color: {{ '#' . substr(md5($member['name']), 0, 6) }}; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.625rem;">
                    {{ strtoupper(substr($member['name'], 0, 2)) }}
                  </div>
                  <span>{{ $member['name'] }}</span>
                </div>
                <span class="badge bg-label-primary">{{ $member['recent_hours'] }}h</span>
              </div>
            @endforeach
          @elseif($project->employees->count() > 0)
            @foreach($project->employees->take(5) as $employee)
              <div class="d-flex align-items-center mb-2">
                <div class="avatar avatar-xs me-2" style="background-color: {{ '#' . substr(md5($employee->name), 0, 6) }}; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.625rem;">
                  {{ strtoupper(substr($employee->name, 0, 2)) }}
                </div>
                <span>{{ $employee->name }}</span>
                @if($employee->pivot->role === 'lead')
                  <span class="badge bg-label-warning ms-1">Lead</span>
                @endif
              </div>
            @endforeach
          @else
            <div class="text-center py-2">
              <p class="text-muted mb-2">No team assigned</p>
              <a href="{{ route('projects.manage-employees', $project) }}" class="btn btn-sm btn-info">
                <i class="ti ti-user-plus me-1"></i>Add Members
              </a>
            </div>
          @endif
        </div>
      </div>

      <!-- Risks Card -->
      @if($dashboard['risks']['total'] > 0)
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="ti ti-alert-triangle me-2 text-{{ $dashboard['risks']['risk_color'] }}"></i>Risks
            @if($dashboard['risks']['critical'] > 0)
              <span class="badge bg-danger ms-1">{{ $dashboard['risks']['critical'] }} critical</span>
            @endif
          </h5>
          <a href="{{ route('projects.planning.risks', $project) }}" class="btn btn-sm btn-outline-warning">View All</a>
        </div>
        <div class="card-body">
          <div class="d-flex justify-content-between mb-3">
            <div class="text-center">
              <h4 class="mb-0">{{ $dashboard['risks']['active'] }}</h4>
              <small class="text-muted">Active</small>
            </div>
            <div class="text-center">
              <h4 class="mb-0 text-success">{{ $dashboard['risks']['mitigated'] }}</h4>
              <small class="text-muted">Mitigated</small>
            </div>
            <div class="text-center">
              <h4 class="mb-0 text-warning">{{ $dashboard['risks']['average_score'] }}</h4>
              <small class="text-muted">Avg Score</small>
            </div>
          </div>
          @if(count($dashboard['risks']['top_risks']) > 0)
            <h6 class="text-muted mb-2">Top Risks</h6>
            @foreach(array_slice($dashboard['risks']['top_risks'], 0, 3) as $risk)
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-truncate" style="max-width: 70%;">{{ $risk['title'] }}</span>
                <span class="badge bg-{{ $risk['risk_level'] === 'critical' ? 'danger' : ($risk['risk_level'] === 'high' ? 'warning' : 'info') }}">
                  {{ $risk['risk_score'] }}
                </span>
              </div>
            @endforeach
          @endif
        </div>
      </div>
      @endif

      <!-- Milestones Card -->
      @if($dashboard['milestones']['total'] > 0)
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="ti ti-flag me-2 text-success"></i>Milestones</h5>
          <a href="{{ route('projects.planning.milestones', $project) }}" class="btn btn-sm btn-outline-success">View All</a>
        </div>
        <div class="card-body">
          <div class="progress mb-3" style="height: 8px;">
            <div class="progress-bar bg-success" style="width: {{ $dashboard['milestones']['completion_percentage'] }}%"></div>
          </div>
          <div class="d-flex justify-content-between mb-3 small text-muted">
            <span>{{ $dashboard['milestones']['completed'] }} completed</span>
            <span>{{ $dashboard['milestones']['in_progress'] }} in progress</span>
            <span>{{ $dashboard['milestones']['pending'] }} pending</span>
          </div>
          @if($dashboard['milestones']['next_milestone'])
            <div class="alert alert-light mb-0">
              <strong>Next:</strong> {{ $dashboard['milestones']['next_milestone']['name'] }}
              <br>
              <small class="text-muted">
                Due: {{ $dashboard['milestones']['next_milestone']['due_date'] }}
                @if($dashboard['milestones']['next_milestone']['days_until'] < 0)
                  <span class="text-danger">({{ abs($dashboard['milestones']['next_milestone']['days_until']) }} days overdue)</span>
                @elseif($dashboard['milestones']['next_milestone']['days_until'] <= 7)
                  <span class="text-warning">({{ $dashboard['milestones']['next_milestone']['days_until'] }} days left)</span>
                @endif
              </small>
            </div>
          @endif
        </div>
      </div>
      @endif

      <!-- Recent Activity -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="ti ti-activity me-2 text-primary"></i>Recent Activity</h5>
        </div>
        <div class="card-body">
          @if(count($dashboard['activity']) > 0)
            <div class="activity-timeline">
              @foreach(array_slice($dashboard['activity'], 0, 8) as $activity)
                <div class="activity-item {{ $activity['type'] }}">
                  <div class="d-flex justify-content-between">
                    <small class="fw-semibold">{{ $activity['title'] }}</small>
                    <small class="text-muted">{{ $activity['date']->diffForHumans() }}</small>
                  </div>
                  <small class="text-muted">{{ \Illuminate\Support\Str::limit($activity['description'], 60) }}</small>
                </div>
              @endforeach
            </div>
          @else
            <div class="text-center py-3">
              <i class="ti ti-activity-heartbeat ti-lg text-muted mb-2 d-block"></i>
              <p class="text-muted">No recent activity</p>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<style>
  .circular-chart-lg {
    display: block;
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
