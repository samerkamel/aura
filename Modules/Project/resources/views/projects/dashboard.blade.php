@extends('layouts/layoutMaster')

@section('title', $project->name . ' - Dashboard')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
@endsection

@section('page-style')
<style>
  .health-gauge {
    position: relative;
    width: 180px;
    height: 180px;
    margin: 0 auto;
  }
  .health-score {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
  }
  .health-score .score {
    font-size: 2.5rem;
    font-weight: 700;
    line-height: 1;
  }
  .health-score .label {
    font-size: 0.875rem;
    color: #6c757d;
  }
  .metric-card {
    transition: transform 0.2s;
  }
  .metric-card:hover {
    transform: translateY(-2px);
  }
  .metric-value {
    font-size: 1.75rem;
    font-weight: 700;
  }
  .metric-label {
    font-size: 0.875rem;
    color: #6c757d;
  }
  .metric-change {
    font-size: 0.75rem;
  }
  .metric-change.positive {
    color: #28a745;
  }
  .metric-change.negative {
    color: #dc3545;
  }
  .activity-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #eee;
  }
  .activity-item:last-child {
    border-bottom: none;
  }
  .activity-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
  }
  .deadline-item {
    padding: 0.5rem 0;
  }
  .deadline-item.overdue {
    color: #dc3545;
  }
  .team-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #6c757d;
    color: white;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
  }
  .score-breakdown .breakdown-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
  }
  .score-breakdown .breakdown-item:last-child {
    border-bottom: none;
  }
  .progress-thin {
    height: 6px;
  }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Project Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="badge bg-label-primary">{{ $project->code }}</span>
        @if($project->health_status)
          <span class="badge bg-{{ $project->health_status_color }}">
            {{ $project->health_status_label }}
          </span>
        @endif
      </div>
      <h4 class="mb-0">{{ $project->name }}</h4>
      @if($project->customer)
        <small class="text-muted">{{ $project->customer->display_name }}</small>
      @endif
    </div>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-outline-primary btn-sm" id="refreshHealth">
        <i class="ti ti-refresh me-1"></i>Refresh Health
      </button>
      <a href="{{ route('projects.tasks', $project) }}" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-list-check me-1"></i>Tasks
      </a>
      <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-arrow-left me-1"></i>Back
      </a>
    </div>
  </div>

  <div class="row">
    <!-- Health Score Card -->
    <div class="col-lg-4 col-md-6 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Health Score</h5>
          <small class="text-muted">Updated {{ $project->latestHealthSnapshot?->created_at?->diffForHumans() ?? 'Never' }}</small>
        </div>
        <div class="card-body">
          <div class="health-gauge mb-3">
            <div id="healthGauge"></div>
            <div class="health-score">
              <div class="score text-{{ $summary['health']['status'] === 'green' ? 'success' : ($summary['health']['status'] === 'yellow' ? 'warning' : 'danger') }}">
                {{ round($summary['health']['overall']) }}
              </div>
              <div class="label">/ 100</div>
            </div>
          </div>

          <div class="score-breakdown">
            <div class="breakdown-item">
              <span><i class="ti ti-wallet me-2 text-primary"></i>Budget</span>
              <div class="d-flex align-items-center gap-2">
                <div class="progress progress-thin" style="width: 60px;">
                  <div class="progress-bar bg-primary" style="width: {{ $summary['health']['budget'] }}%"></div>
                </div>
                <span class="fw-semibold">{{ round($summary['health']['budget']) }}</span>
              </div>
            </div>
            <div class="breakdown-item">
              <span><i class="ti ti-calendar me-2 text-info"></i>Schedule</span>
              <div class="d-flex align-items-center gap-2">
                <div class="progress progress-thin" style="width: 60px;">
                  <div class="progress-bar bg-info" style="width: {{ $summary['health']['schedule'] }}%"></div>
                </div>
                <span class="fw-semibold">{{ round($summary['health']['schedule']) }}</span>
              </div>
            </div>
            <div class="breakdown-item">
              <span><i class="ti ti-target me-2 text-success"></i>Scope</span>
              <div class="d-flex align-items-center gap-2">
                <div class="progress progress-thin" style="width: 60px;">
                  <div class="progress-bar bg-success" style="width: {{ $summary['health']['scope'] }}%"></div>
                </div>
                <span class="fw-semibold">{{ round($summary['health']['scope']) }}</span>
              </div>
            </div>
            <div class="breakdown-item">
              <span><i class="ti ti-shield-check me-2 text-warning"></i>Quality</span>
              <div class="d-flex align-items-center gap-2">
                <div class="progress progress-thin" style="width: 60px;">
                  <div class="progress-bar bg-warning" style="width: {{ $summary['health']['quality'] }}%"></div>
                </div>
                <span class="fw-semibold">{{ round($summary['health']['quality']) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Key Metrics -->
    <div class="col-lg-8 col-md-6 mb-4">
      <div class="row h-100">
        <!-- Budget Card -->
        <div class="col-sm-6 col-lg-3 mb-4">
          <div class="card metric-card h-100">
            <div class="card-body">
              <div class="d-flex align-items-center mb-2">
                <div class="avatar avatar-sm me-2">
                  <span class="avatar-initial rounded bg-label-primary">
                    <i class="ti ti-wallet"></i>
                  </span>
                </div>
                <span class="metric-label">Budget</span>
              </div>
              @if($project->planned_budget)
                <div class="metric-value">{{ number_format($summary['health']['metrics']['budget']['utilization_percentage'], 0) }}%</div>
                <small class="text-muted">
                  {{ number_format($summary['health']['metrics']['budget']['actual_cost']) }} /
                  {{ number_format($project->planned_budget) }} {{ $project->currency }}
                </small>
              @else
                <div class="metric-value text-muted">--</div>
                <small class="text-muted">No budget set</small>
              @endif
            </div>
          </div>
        </div>

        <!-- Timeline Card -->
        <div class="col-sm-6 col-lg-3 mb-4">
          <div class="card metric-card h-100">
            <div class="card-body">
              <div class="d-flex align-items-center mb-2">
                <div class="avatar avatar-sm me-2">
                  <span class="avatar-initial rounded bg-label-info">
                    <i class="ti ti-calendar"></i>
                  </span>
                </div>
                <span class="metric-label">Timeline</span>
              </div>
              @if($project->planned_end_date)
                @php $daysLeft = $project->days_until_deadline; @endphp
                <div class="metric-value {{ $daysLeft < 0 ? 'text-danger' : ($daysLeft < 7 ? 'text-warning' : '') }}">
                  {{ abs($daysLeft) }}
                </div>
                <small class="{{ $daysLeft < 0 ? 'text-danger' : 'text-muted' }}">
                  {{ $daysLeft < 0 ? 'days overdue' : 'days remaining' }}
                </small>
              @else
                <div class="metric-value text-muted">--</div>
                <small class="text-muted">No deadline set</small>
              @endif
            </div>
          </div>
        </div>

        <!-- Tasks Card -->
        <div class="col-sm-6 col-lg-3 mb-4">
          <div class="card metric-card h-100">
            <div class="card-body">
              <div class="d-flex align-items-center mb-2">
                <div class="avatar avatar-sm me-2">
                  <span class="avatar-initial rounded bg-label-success">
                    <i class="ti ti-check"></i>
                  </span>
                </div>
                <span class="metric-label">Tasks Done</span>
              </div>
              <div class="metric-value">{{ $summary['health']['metrics']['scope']['completion_rate'] }}%</div>
              <small class="text-muted">
                {{ $summary['health']['metrics']['scope']['tasks_done'] }} /
                {{ $summary['health']['metrics']['scope']['total_tasks'] }} tasks
              </small>
            </div>
          </div>
        </div>

        <!-- Hours Card -->
        <div class="col-sm-6 col-lg-3 mb-4">
          <div class="card metric-card h-100">
            <div class="card-body">
              <div class="d-flex align-items-center mb-2">
                <div class="avatar avatar-sm me-2">
                  <span class="avatar-initial rounded bg-label-warning">
                    <i class="ti ti-clock"></i>
                  </span>
                </div>
                <span class="metric-label">Hours This Week</span>
              </div>
              <div class="metric-value">{{ $summary['hours_this_week'] }}</div>
              <small class="metric-change {{ $summary['hours_change'] >= 0 ? 'positive' : 'negative' }}">
                <i class="ti ti-{{ $summary['hours_change'] >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                {{ abs($summary['hours_change']) }}% vs last week
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- Health Trend Chart -->
    <div class="col-lg-8 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Health Trend</h5>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary active" data-days="7">7D</button>
            <button type="button" class="btn btn-outline-primary" data-days="14">14D</button>
            <button type="button" class="btn btn-outline-primary" data-days="30">30D</button>
          </div>
        </div>
        <div class="card-body">
          <div id="healthTrendChart" style="min-height: 300px;"></div>
        </div>
      </div>
    </div>

    <!-- Team Performance -->
    <div class="col-lg-4 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title mb-0">Team Hours</h5>
        </div>
        <div class="card-body">
          @forelse($teamHours as $member)
            <div class="d-flex align-items-center mb-3">
              <span class="team-avatar me-2">
                {{ substr($member->employee?->name ?? '?', 0, 2) }}
              </span>
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between mb-1">
                  <small class="fw-semibold">{{ $member->employee?->name ?? 'Unknown' }}</small>
                  <small>{{ round($member->total_hours, 1) }}h</small>
                </div>
                <div class="progress progress-thin">
                  @php $maxHours = $teamHours->max('total_hours'); @endphp
                  <div class="progress-bar" style="width: {{ $maxHours > 0 ? ($member->total_hours / $maxHours) * 100 : 0 }}%"></div>
                </div>
              </div>
            </div>
          @empty
            <div class="text-center text-muted py-4">
              <i class="ti ti-users ti-lg mb-2"></i>
              <p class="mb-0">No team hours logged</p>
            </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- Upcoming Deadlines -->
    <div class="col-lg-4 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title mb-0">Upcoming Deadlines</h5>
        </div>
        <div class="card-body">
          @forelse($summary['upcoming_deadlines'] as $issue)
            <div class="deadline-item d-flex justify-content-between align-items-center {{ $issue->isOverdue() ? 'overdue' : '' }}">
              <div>
                <a href="{{ $issue->jira_url }}" target="_blank" class="text-decoration-none">
                  <small class="text-muted">{{ $issue->issue_key }}</small>
                </a>
                <div class="small">{{ \Str::limit($issue->summary, 40) }}</div>
              </div>
              <span class="badge bg-{{ $issue->isOverdue() ? 'danger' : 'warning' }}">
                {{ $issue->due_date->format('M d') }}
              </span>
            </div>
          @empty
            <div class="text-center text-muted py-4">
              <i class="ti ti-calendar-check ti-lg mb-2"></i>
              <p class="mb-0">No upcoming deadlines</p>
            </div>
          @endforelse
        </div>
      </div>
    </div>

    <!-- Issues by Type -->
    <div class="col-lg-4 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title mb-0">Issues by Type</h5>
        </div>
        <div class="card-body">
          <div id="issuesByTypeChart" style="min-height: 250px;"></div>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="col-lg-4 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title mb-0">Recent Activity</h5>
        </div>
        <div class="card-body" style="max-height: 350px; overflow-y: auto;">
          @forelse($summary['recent_worklogs'] as $worklog)
            <div class="activity-item d-flex gap-2">
              <div class="activity-icon bg-label-info">
                <i class="ti ti-clock"></i>
              </div>
              <div>
                <div class="small">
                  <strong>{{ $worklog->employee?->name ?? 'Unknown' }}</strong>
                  logged {{ round($worklog->time_spent_hours, 1) }}h on
                  <a href="#" class="text-decoration-none">{{ $worklog->issue_key }}</a>
                </div>
                <small class="text-muted">{{ $worklog->worklog_started->diffForHumans() }}</small>
              </div>
            </div>
          @empty
            <div class="text-center text-muted py-4">
              <i class="ti ti-activity ti-lg mb-2"></i>
              <p class="mb-0">No recent activity</p>
            </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Follow-ups -->
  @if($recentFollowups->count() > 0)
  <div class="row">
    <div class="col-12 mb-4">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Recent Follow-ups</h5>
          <a href="{{ route('projects.show', $project) }}#followups" class="btn btn-sm btn-outline-primary">
            View All
          </a>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Type</th>
                  <th>Notes</th>
                  <th>By</th>
                </tr>
              </thead>
              <tbody>
                @foreach($recentFollowups as $followup)
                <tr>
                  <td>{{ $followup->followup_date->format('M d, Y') }}</td>
                  <td>
                    <span class="badge bg-label-{{ $followup->followup_type_color }}">
                      {{ $followup->followup_type_label }}
                    </span>
                  </td>
                  <td>{{ \Str::limit($followup->notes, 80) }}</td>
                  <td>{{ $followup->user?->name ?? 'Unknown' }}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const projectId = {{ $project->id }};

  // Health Gauge
  const healthScore = {{ $summary['health']['overall'] }};
  const healthStatus = '{{ $summary['health']['status'] }}';
  const gaugeColor = healthStatus === 'green' ? '#28a745' : (healthStatus === 'yellow' ? '#ffc107' : '#dc3545');

  const gaugeOptions = {
    series: [healthScore],
    chart: {
      type: 'radialBar',
      height: 180,
      sparkline: { enabled: true }
    },
    plotOptions: {
      radialBar: {
        startAngle: -135,
        endAngle: 135,
        hollow: { size: '65%' },
        track: {
          background: '#e7e7e7',
          strokeWidth: '100%'
        },
        dataLabels: { show: false }
      }
    },
    colors: [gaugeColor],
    stroke: { lineCap: 'round' }
  };

  new ApexCharts(document.querySelector('#healthGauge'), gaugeOptions).render();

  // Health Trend Chart
  let trendChart;
  const trendData = @json($summary['trend']);

  function renderTrendChart(data) {
    const options = {
      series: [
        { name: 'Overall', data: data.overall },
        { name: 'Budget', data: data.budget },
        { name: 'Schedule', data: data.schedule },
        { name: 'Scope', data: data.scope },
        { name: 'Quality', data: data.quality }
      ],
      chart: {
        type: 'line',
        height: 300,
        toolbar: { show: false },
        zoom: { enabled: false }
      },
      stroke: {
        width: [3, 2, 2, 2, 2],
        curve: 'smooth'
      },
      colors: ['#696cff', '#03c3ec', '#71dd37', '#ffab00', '#ff3e1d'],
      xaxis: {
        categories: data.dates,
        labels: { style: { fontSize: '11px' } }
      },
      yaxis: {
        min: 0,
        max: 100,
        labels: { formatter: (val) => Math.round(val) }
      },
      legend: {
        position: 'top',
        horizontalAlign: 'right'
      },
      tooltip: {
        y: { formatter: (val) => Math.round(val) + ' / 100' }
      }
    };

    if (trendChart) {
      trendChart.destroy();
    }
    trendChart = new ApexCharts(document.querySelector('#healthTrendChart'), options);
    trendChart.render();
  }

  renderTrendChart(trendData);

  // Trend period buttons
  document.querySelectorAll('[data-days]').forEach(btn => {
    btn.addEventListener('click', function() {
      document.querySelectorAll('[data-days]').forEach(b => b.classList.remove('active'));
      this.classList.add('active');

      fetch(`/projects/${projectId}/dashboard/health-trend?days=${this.dataset.days}`)
        .then(res => res.json())
        .then(data => renderTrendChart(data));
    });
  });

  // Issues by Type Chart
  const issuesByType = @json($issuesByType);
  const typeLabels = Object.keys(issuesByType);
  const typeValues = Object.values(issuesByType);

  if (typeLabels.length > 0) {
    const typeOptions = {
      series: typeValues,
      chart: {
        type: 'donut',
        height: 250
      },
      labels: typeLabels,
      colors: ['#696cff', '#03c3ec', '#71dd37', '#ffab00', '#ff3e1d', '#8592a3'],
      legend: {
        position: 'bottom'
      },
      plotOptions: {
        pie: {
          donut: {
            size: '60%',
            labels: {
              show: true,
              total: {
                show: true,
                label: 'Total',
                formatter: () => typeValues.reduce((a, b) => a + b, 0)
              }
            }
          }
        }
      }
    };

    new ApexCharts(document.querySelector('#issuesByTypeChart'), typeOptions).render();
  }

  // Refresh Health Button
  document.getElementById('refreshHealth').addEventListener('click', function() {
    this.disabled = true;
    this.innerHTML = '<i class="ti ti-loader ti-spin me-1"></i>Refreshing...';

    fetch(`/projects/${projectId}/dashboard/refresh-health`, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          location.reload();
        } else {
          alert('Failed to refresh: ' + data.message);
        }
      })
      .catch(err => {
        alert('Error refreshing health score');
        console.error(err);
      })
      .finally(() => {
        this.disabled = false;
        this.innerHTML = '<i class="ti ti-refresh me-1"></i>Refresh Health';
      });
  });
});
</script>
@endsection
