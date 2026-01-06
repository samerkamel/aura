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
              <span class="text-muted">Revenue {{ $selectedFY ? "($selectedFY)" : '(All Time)' }}</span>
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
              <span class="text-muted">Profit {{ $selectedFY ? "($selectedFY)" : '(All Time)' }}</span>
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
              <span class="text-muted">Hours {{ $selectedFY ? "($selectedFY)" : '(All Time)' }}</span>
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
              <!-- Financial Year Filter -->
              <div class="d-flex align-items-center gap-2">
                <label class="mb-0 text-muted small">FY:</label>
                <select class="form-select form-select-sm" style="width: auto;" id="fyFilter" onchange="applyFYFilter(this.value)">
                  @foreach($financialYears as $year)
                    <option value="{{ $year }}" {{ $selectedFY == $year ? 'selected' : '' }}>{{ $year }}</option>
                  @endforeach
                  <option value="all" {{ !$selectedFY ? 'selected' : '' }}>All Time</option>
                </select>
              </div>
              <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                  <i class="ti ti-apps me-1"></i>Tools
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                    <a class="dropdown-item" href="{{ route('projects.link-customers') }}">
                      <i class="ti ti-link me-2"></i>Link to Customers
                    </a>
                  </li>
                  <li><hr class="dropdown-divider"></li>
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
              <button type="button" class="btn btn-outline-primary btn-sm" id="jiraSyncBtn" onclick="startJiraSync()">
                <i class="ti ti-refresh me-1"></i>Sync All from Jira
              </button>
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
          <label class="form-label">Phase</label>
          <select class="form-select form-select-sm" name="phase">
            <option value="active" {{ $phaseFilter === 'active' ? 'selected' : '' }}>Active Projects</option>
            <option value="all" {{ $phaseFilter === 'all' ? 'selected' : '' }}>All Phases</option>
            <option value="closure" {{ $phaseFilter === 'closure' ? 'selected' : '' }}>Closure Only</option>
            <optgroup label="Specific Phase">
              @foreach($phases as $value => $label)
                @if($value !== 'closure')
                  <option value="{{ $value }}" {{ $phaseFilter === $value ? 'selected' : '' }}>
                    {{ $label }}
                  </option>
                @endif
              @endforeach
            </optgroup>
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
      @php
        // Use eager-loaded aggregates directly
        $completion = $project->completion_percentage ?? 0;
        $lifetimeHours = $project->lifetime_hours ?? 0;
        $fyHours = $project->fy_hours ?? 0;
        $receivedRevenue = $project->revenues_sum_amount_received ?? 0;
        $teamSize = $project->employees_count ?? 0;
        $openIssues = $project->open_issues_count ?? 0;
        $healthColor = match($project->health_status) {
            'green' => 'success',
            'yellow' => 'warning',
            'red' => 'danger',
            default => 'secondary',
        };
      @endphp
      <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
        <div class="card project-card h-100 health-{{ $project->health_status ?? 'green' }}">
          <div class="card-body">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div class="flex-grow-1">
                <div class="d-flex align-items-center mb-1">
                  <span class="badge bg-label-primary me-2">{{ $project->code }}</span>
                  @if($project->health_status)
                    <span class="health-indicator bg-{{ $healthColor }}" title="{{ $project->health_status_label ?? 'Unknown' }}"></span>
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
                      {{ $project->customer->company_name ?: $project->customer->name }}
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
                  <path class="circle" stroke="{{ $completion >= 100 ? '#28c76f' : '#7367f0' }}"
                        stroke-width="3" stroke-linecap="round" fill="none"
                        stroke-dasharray="{{ $completion }}, 100"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                </svg>
                <div class="progress-circle-inner">{{ round($completion) }}%</div>
              </div>
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between mini-stat mb-1">
                  <span>Hours</span>
                  <span class="value" title="FY {{ $selectedFY ?? 'All' }}: {{ number_format($fyHours, 1) }}h | Lifetime: {{ number_format($lifetimeHours, 1) }}h">
                    @if($selectedFY)
                      {{ number_format($fyHours, 1) }} <small class="text-muted">({{ number_format($lifetimeHours, 0) }})</small>
                    @else
                      {{ number_format($lifetimeHours, 1) }}h
                    @endif
                  </span>
                </div>
                <div class="d-flex justify-content-between mini-stat mb-1">
                  <span>Revenue</span>
                  <span class="value">{{ number_format($receivedRevenue, 0) }}</span>
                </div>
                <div class="d-flex justify-content-between mini-stat">
                  <span>Team</span>
                  <span class="value">{{ $teamSize }} members</span>
                </div>
              </div>
            </div>

            <!-- Footer Stats -->
            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
              <div class="d-flex align-items-center gap-3">
                @if($teamSize > 0)
                  <span class="d-flex align-items-center" title="Team Size">
                    <i class="ti ti-users ti-xs text-muted me-1"></i>
                    <small>{{ $teamSize }}</small>
                  </span>
                @endif
                @if($openIssues > 0)
                  <span class="d-flex align-items-center" title="Open Issues">
                    <i class="ti ti-list-check ti-xs text-muted me-1"></i>
                    <small>{{ $openIssues }}</small>
                  </span>
                @endif
                @if($project->phase)
                  <span class="badge bg-label-info" title="Phase">
                    {{ $project->phase_label ?? $project->phase }}
                  </span>
                @endif
              </div>
              <div>
                @if($project->isOverdue())
                  <span class="badge bg-danger">Overdue</span>
                @elseif($project->days_until_deadline !== null && $project->days_until_deadline <= 7 && $project->days_until_deadline >= 0)
                  <span class="badge bg-warning">{{ $project->days_until_deadline }}d left</span>
                @elseif($project->phase === 'closure')
                  <span class="badge bg-secondary">Closed</span>
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
              <button type="button" class="btn btn-outline-primary" onclick="startJiraSync()">
                <i class="ti ti-refresh me-1"></i>Sync All from Jira
              </button>
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

<!-- Jira Sync Progress Modal -->
<div class="modal fade" id="jiraSyncModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="ti ti-refresh me-2"></i>Syncing from Jira
        </h5>
      </div>
      <div class="modal-body">
        <!-- Overall Progress -->
        <div class="mb-4">
          <div class="d-flex justify-content-between mb-2">
            <span class="fw-semibold" id="syncPhaseText">Initializing...</span>
            <span id="syncProgressPercent">0%</span>
          </div>
          <div class="progress" style="height: 8px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated" id="syncProgressBar" role="progressbar" style="width: 0%"></div>
          </div>
        </div>

        <!-- Current Step -->
        <div class="border rounded p-3 bg-light" id="syncStatusContainer">
          <div class="d-flex align-items-center mb-2">
            <span class="spinner-border spinner-border-sm me-2" id="syncSpinner"></span>
            <span id="syncCurrentStep">Preparing sync...</span>
          </div>
          <small class="text-muted" id="syncDetails"></small>
        </div>

        <!-- Results Summary (shown when complete) -->
        <div id="syncResultsSummary" class="mt-3" style="display: none;">
          <h6 class="mb-3">Sync Results</h6>
          <div class="row g-2">
            <div class="col-4">
              <div class="border rounded p-2 text-center">
                <div class="fw-bold text-primary" id="resultProjects">-</div>
                <small class="text-muted">Projects</small>
              </div>
            </div>
            <div class="col-4">
              <div class="border rounded p-2 text-center">
                <div class="fw-bold text-success" id="resultIssues">-</div>
                <small class="text-muted">Issues</small>
              </div>
            </div>
            <div class="col-4">
              <div class="border rounded p-2 text-center">
                <div class="fw-bold text-info" id="resultWorklogs">-</div>
                <small class="text-muted">Worklogs</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Error Display -->
        <div id="syncErrorContainer" class="alert alert-danger mt-3" style="display: none;">
          <i class="ti ti-alert-circle me-1"></i>
          <span id="syncErrorMessage"></span>
        </div>
      </div>
      <div class="modal-footer" id="syncModalFooter" style="display: none;">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="location.reload()">
          <i class="ti ti-refresh me-1"></i>Refresh Page
        </button>
      </div>
    </div>
  </div>
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
<script>
function applyFYFilter(year) {
    const url = new URL(window.location.href);
    if (year === 'all') {
        url.searchParams.delete('fy');
    } else {
        url.searchParams.set('fy', year);
    }
    window.location.href = url.toString();
}

// Jira Sync AJAX Functions
let syncModal = null;
let syncResults = {
    projects: { created: 0, updated: 0 },
    issues: { total: 0, created: 0, updated: 0 },
    worklogs: { imported: 0, skipped: 0 }
};

function startJiraSync() {
    // Reset results
    syncResults = {
        projects: { created: 0, updated: 0 },
        issues: { total: 0, created: 0, updated: 0 },
        worklogs: { imported: 0, skipped: 0 }
    };

    // Show modal
    syncModal = new bootstrap.Modal(document.getElementById('jiraSyncModal'));
    syncModal.show();

    // Reset UI
    document.getElementById('syncProgressBar').style.width = '0%';
    document.getElementById('syncProgressPercent').textContent = '0%';
    document.getElementById('syncPhaseText').textContent = 'Starting sync...';
    document.getElementById('syncCurrentStep').textContent = 'Preparing...';
    document.getElementById('syncDetails').textContent = '';
    document.getElementById('syncResultsSummary').style.display = 'none';
    document.getElementById('syncErrorContainer').style.display = 'none';
    document.getElementById('syncModalFooter').style.display = 'none';
    document.getElementById('syncSpinner').style.display = 'inline-block';

    // Start the sync process
    syncProjects();
}

function updateProgress(percent, phase, step, details = '') {
    document.getElementById('syncProgressBar').style.width = percent + '%';
    document.getElementById('syncProgressPercent').textContent = percent + '%';
    document.getElementById('syncPhaseText').textContent = phase;
    document.getElementById('syncCurrentStep').textContent = step;
    document.getElementById('syncDetails').textContent = details;
}

function showError(message) {
    document.getElementById('syncErrorContainer').style.display = 'block';
    document.getElementById('syncErrorMessage').textContent = message;
    document.getElementById('syncSpinner').style.display = 'none';
    document.getElementById('syncModalFooter').style.display = 'flex';
}

function showComplete() {
    document.getElementById('syncSpinner').style.display = 'none';
    document.getElementById('syncCurrentStep').textContent = 'Sync completed successfully!';
    document.getElementById('syncDetails').textContent = '';

    // Show results
    document.getElementById('resultProjects').textContent =
        (syncResults.projects.created + syncResults.projects.updated) + ' synced';
    document.getElementById('resultIssues').textContent =
        syncResults.issues.total + ' synced';
    document.getElementById('resultWorklogs').textContent =
        syncResults.worklogs.imported + ' imported';

    document.getElementById('syncResultsSummary').style.display = 'block';
    document.getElementById('syncModalFooter').style.display = 'flex';
}

async function syncProjects() {
    updateProgress(5, 'Phase 1/3: Projects', 'Syncing projects from Jira...', 'Fetching project list from Jira API');

    try {
        const response = await fetch('{{ route("projects.sync-jira.projects") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (!data.success) {
            showError(data.error || 'Failed to sync projects');
            return;
        }

        syncResults.projects = data.projects;
        updateProgress(15, 'Phase 1/3: Projects', 'Projects synced!',
            `Created: ${data.projects.created}, Updated: ${data.projects.updated}`);

        // Now sync issues for each project
        if (data.active_projects && data.active_projects.length > 0) {
            await syncIssues(data.active_projects);
        } else {
            // No projects, skip to worklogs
            await syncWorklogs();
        }

    } catch (error) {
        showError('Network error: ' + error.message);
    }
}

async function syncIssues(projects) {
    const totalProjects = projects.length;
    let completedProjects = 0;

    updateProgress(20, 'Phase 2/3: Issues', `Syncing issues for ${totalProjects} projects...`, '');

    for (const project of projects) {
        completedProjects++;
        const progressPercent = 20 + Math.round((completedProjects / totalProjects) * 50);

        updateProgress(
            progressPercent,
            'Phase 2/3: Issues',
            `Syncing issues: ${project.code} (${completedProjects}/${totalProjects})`,
            `Project: ${project.name}`
        );

        try {
            const response = await fetch('{{ route("projects.sync-jira.issues") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ project_id: project.id })
            });

            const data = await response.json();

            if (data.success && data.issues) {
                syncResults.issues.total += data.issues.total || 0;
                syncResults.issues.created += data.issues.created || 0;
                syncResults.issues.updated += data.issues.updated || 0;
            }
            // Continue even if individual project fails

        } catch (error) {
            // Log but continue with other projects
            console.error(`Error syncing ${project.code}:`, error);
        }
    }

    updateProgress(75, 'Phase 2/3: Issues', 'Issues synced!',
        `Total: ${syncResults.issues.total} issues processed`);

    // Now sync worklogs
    await syncWorklogs();
}

async function syncWorklogs() {
    updateProgress(80, 'Phase 3/3: Worklogs', 'Syncing worklogs...', 'Fetching worklogs from the last 90 days');

    try {
        const response = await fetch('{{ route("projects.sync-jira.worklogs") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ days: 90 })
        });

        const data = await response.json();

        if (!data.success) {
            showError(data.error || 'Failed to sync worklogs');
            return;
        }

        syncResults.worklogs = data.worklogs;
        updateProgress(100, 'Complete', 'All syncs completed!',
            `Imported: ${data.worklogs.imported}, Skipped: ${data.worklogs.skipped}`);

        showComplete();

    } catch (error) {
        showError('Network error syncing worklogs: ' + error.message);
    }
}
</script>
@endsection
