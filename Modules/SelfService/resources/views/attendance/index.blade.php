@extends('layouts/layoutMaster')

@section('title', 'My Attendance')

@section('page-style')
<style>
  .filter-card {
    border-left: 3px solid #7367f0;
  }
  .summary-card {
    transition: transform 0.2s;
  }
  .summary-card:hover {
    transform: translateY(-2px);
  }
  .time-missing {
    color: #ea5455;
    font-style: italic;
  }
  /* Day type highlighting */
  .row-weekend {
    background-color: rgba(255, 159, 67, 0.15) !important;
  }
  .row-weekend:hover {
    background-color: rgba(255, 159, 67, 0.25) !important;
  }
  .row-holiday {
    background-color: rgba(40, 199, 111, 0.15) !important;
  }
  .row-holiday:hover {
    background-color: rgba(40, 199, 111, 0.25) !important;
  }
  .row-leave {
    background-color: rgba(0, 207, 232, 0.15) !important;
  }
  .row-leave:hover {
    background-color: rgba(0, 207, 232, 0.25) !important;
  }
  .row-wfh {
    background-color: rgba(115, 103, 240, 0.15) !important;
  }
  .row-wfh:hover {
    background-color: rgba(115, 103, 240, 0.25) !important;
  }
  .row-missing {
    background-color: rgba(234, 84, 85, 0.15) !important;
  }
  .row-missing:hover {
    background-color: rgba(234, 84, 85, 0.25) !important;
  }
  .day-badge {
    font-size: 0.7rem;
    padding: 2px 6px;
  }
</style>
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Page Header -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0">
            <i class="ti ti-calendar-stats me-2"></i>My Attendance
          </h5>
          <small class="text-muted">View your attendance records</small>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-label-primary">
            <i class="ti ti-user me-1"></i>{{ $employee->name }}
          </span>
        </div>
      </div>
    </div>

    <!-- Filter Card -->
    <div class="card filter-card mb-4">
      <div class="card-header">
        <h6 class="mb-0"><i class="ti ti-filter me-2"></i>Filters</h6>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route('self-service.attendance') }}" id="filterForm">
          <div class="row g-3">
            <!-- Filter Type Toggle -->
            <div class="col-12">
              <div class="btn-group" role="group">
                <input type="radio" class="btn-check" name="filter_type" id="filterMonth" value="month"
                       {{ $filterType === 'month' ? 'checked' : '' }} onchange="toggleFilterType()">
                <label class="btn btn-outline-primary" for="filterMonth">
                  <i class="ti ti-calendar-month me-1"></i>By Month
                </label>

                <input type="radio" class="btn-check" name="filter_type" id="filterRange" value="range"
                       {{ $filterType === 'range' ? 'checked' : '' }} onchange="toggleFilterType()">
                <label class="btn btn-outline-primary" for="filterRange">
                  <i class="ti ti-calendar-event me-1"></i>Date Range
                </label>
              </div>
            </div>

            <!-- Month Filter -->
            <div id="monthFilters" class="{{ $filterType === 'range' ? 'd-none' : '' }}">
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label">Year</label>
                  <select class="form-select" name="year" onchange="document.getElementById('filterForm').submit()">
                    @foreach($years as $y)
                      <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Month</label>
                  <select class="form-select" name="month" onchange="document.getElementById('filterForm').submit()">
                    @for($m = 1; $m <= 12; $m++)
                      <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                        {{ Carbon\Carbon::create(null, $m, 1)->format('F') }}
                      </option>
                    @endfor
                  </select>
                </div>
              </div>
            </div>

            <!-- Date Range Filter -->
            <div id="rangeFilters" class="{{ $filterType === 'month' ? 'd-none' : '' }}">
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label">From Date</label>
                  <input type="date" class="form-control" name="date_from"
                         value="{{ $dateFrom ?? Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                  <label class="form-label">To Date</label>
                  <input type="date" class="form-control" name="date_to"
                         value="{{ $dateTo ?? Carbon\Carbon::now()->format('Y-m-d') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                  <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-search me-1"></i>Apply
                  </button>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Summary Cards -->
    @if($employeeSummary)
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card summary-card bg-label-primary">
          <div class="card-body text-center">
            <i class="ti ti-calendar-check mb-2" style="font-size: 2rem;"></i>
            <h6 class="mb-1">Work Days</h6>
            <h3 class="mb-0">{{ number_format($employeeSummary['work_days']) }}</h3>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card summary-card bg-label-info">
          <div class="card-body text-center">
            <i class="ti ti-clock-hour-4 mb-2" style="font-size: 2rem;"></i>
            <h6 class="mb-1">Expected Hours</h6>
            <h3 class="mb-0">{{ number_format($employeeSummary['expected_work_hours'], 1) }}h</h3>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card summary-card bg-label-success">
          <div class="card-body text-center">
            <i class="ti ti-clock-check mb-2" style="font-size: 2rem;"></i>
            <h6 class="mb-1">Total Hours</h6>
            <h3 class="mb-0">{{ number_format($employeeSummary['total_work_hours'], 1) }}h</h3>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card summary-card {{ $employeeSummary['percentage'] >= 100 ? 'bg-label-success' : ($employeeSummary['percentage'] >= 90 ? 'bg-label-warning' : 'bg-label-danger') }}">
          <div class="card-body text-center">
            <i class="ti ti-percentage mb-2" style="font-size: 2rem;"></i>
            <h6 class="mb-1">Completion</h6>
            <h3 class="mb-0">{{ number_format($employeeSummary['percentage'], 1) }}%</h3>
          </div>
        </div>
      </div>
    </div>
    @endif

    <!-- Records Table -->
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="mb-0">
            @if($filterType === 'range' && $dateFrom && $dateTo)
              Records from {{ Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} to {{ Carbon\Carbon::parse($dateTo)->format('M d, Y') }}
            @else
              Records for {{ Carbon\Carbon::create($year, $month, 1)->format('F Y') }}
              <small class="text-muted">({{ $periodStartDate->format('M d') }} - {{ $periodEndDate->format('M d, Y') }})</small>
            @endif
          </h6>
          <span class="badge bg-primary">{{ count($dailyRecords) }} days</span>
        </div>
        <div class="d-flex gap-3 flex-wrap small">
          <span><span class="badge bg-warning day-badge">Weekend</span> Light orange rows</span>
          <span><span class="badge bg-success day-badge">Holiday</span> Light green rows</span>
          <span><span class="badge bg-info day-badge">Leave</span> Light blue rows</span>
          <span><span class="badge bg-primary day-badge">WFH</span> Light purple rows</span>
          <span><span class="badge bg-danger day-badge">Absent</span> Light red rows</span>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>Date</th>
              <th>Time In</th>
              <th>Time Out</th>
              <th>Total Time</th>
              <th>Permission</th>
              <th>Late Penalty</th>
            </tr>
          </thead>
          <tbody>
            @forelse($dailyRecords as $record)
            @php
              $rowClass = '';
              if ($record['is_weekend'] ?? false) {
                $rowClass = 'row-weekend';
              } elseif ($record['is_holiday'] ?? false) {
                $rowClass = 'row-holiday';
              } elseif ($record['is_on_leave'] ?? false) {
                $rowClass = 'row-leave';
              } elseif ($record['is_wfh'] ?? false) {
                $rowClass = 'row-wfh';
              } elseif ($record['is_missing'] ?? false) {
                $rowClass = 'row-missing';
              }
            @endphp
            <tr class="{{ $rowClass }}">
              <td>
                <strong>{{ Carbon\Carbon::parse($record['date'])->format('M d, Y') }}</strong>
                <br>
                <small class="text-muted">{{ Carbon\Carbon::parse($record['date'])->format('l') }}</small>
                @if($record['is_weekend'] ?? false)
                  <span class="badge bg-warning day-badge ms-1">Weekend</span>
                @elseif($record['is_holiday'] ?? false)
                  <span class="badge bg-success day-badge ms-1" title="{{ $record['holiday_name'] ?? 'Public Holiday' }}">Holiday</span>
                @elseif($record['is_on_leave'] ?? false)
                  <span class="badge bg-info day-badge ms-1" title="{{ $record['leave_type'] ?? 'Leave' }}">{{ $record['leave_type'] ?? 'Leave' }}</span>
                @elseif($record['is_wfh'] ?? false)
                  <span class="badge bg-primary day-badge ms-1">WFH</span>
                @elseif($record['is_missing'] ?? false)
                  <span class="badge bg-danger day-badge ms-1">Absent</span>
                @endif
              </td>
              <td>
                @if($record['time_in'])
                  <span class="badge bg-{{ ($record['late_minutes'] ?? 0) > 0 ? 'danger' : 'success' }}">
                    <i class="ti ti-login me-1"></i>{{ $record['time_in']->format('h:i:s A') }}
                  </span>
                @elseif(($record['is_weekend'] ?? false) || ($record['is_holiday'] ?? false) || ($record['is_on_leave'] ?? false) || ($record['is_wfh'] ?? false))
                  <span class="text-muted">-</span>
                @else
                  <span class="time-missing">Missing</span>
                @endif
              </td>
              <td>
                @if($record['time_out'])
                  <span class="badge bg-success">
                    <i class="ti ti-logout me-1"></i>{{ $record['time_out']->format('h:i:s A') }}
                  </span>
                @elseif(($record['is_weekend'] ?? false) || ($record['is_holiday'] ?? false) || ($record['is_on_leave'] ?? false) || ($record['is_wfh'] ?? false))
                  <span class="text-muted">-</span>
                @else
                  <span class="time-missing">Missing</span>
                @endif
              </td>
              <td>
                @if($record['time_in'] && $record['time_out'])
                  @php
                    $hours = floor($record['total_minutes'] / 60);
                    $minutes = $record['total_minutes'] % 60;
                    $isInsufficientTime = $record['total_minutes'] < 480;
                  @endphp
                  <span class="badge bg-{{ $isInsufficientTime ? 'danger' : 'success' }}">
                    <strong>{{ $hours }}h {{ $minutes }}m</strong>
                  </span>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>
              <td>
                @if($record['has_permission'] ?? false)
                  <span class="badge bg-info" title="{{ $record['permission_usage']->reason ?? 'Permission used' }}">
                    <i class="ti ti-clock-pause me-1"></i>{{ $record['permission_usage']->minutes_used ?? $minutesPerPermission }}m
                  </span>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>
              <td>
                @if(($record['late_penalty'] ?? 0) > 0)
                  <span class="badge bg-warning text-dark" title="Late by {{ round($record['late_minutes'] ?? 0) }} minutes">
                    <i class="ti ti-clock-exclamation me-1"></i>{{ $record['late_penalty'] }}m
                  </span>
                @elseif($record['time_in'])
                  <span class="badge bg-label-success">
                    <i class="ti ti-check me-1"></i>On time
                  </span>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="6" class="text-center py-5">
                <div class="d-flex flex-column align-items-center">
                  <i class="ti ti-calendar-off text-muted" style="font-size: 3rem;"></i>
                  <h6 class="mt-2">No attendance records found</h6>
                  <p class="text-muted">No records available for the selected period.</p>
                </div>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <!-- Detailed Summary -->
    @if($employeeSummary)
    <div class="card mt-4">
      <div class="card-header bg-label-primary">
        <h6 class="mb-0">
          <i class="ti ti-chart-bar me-2"></i>Detailed Breakdown
        </h6>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <tbody>
              <tr>
                <td class="text-muted" width="40%">Total Days in Period (excl. weekends & holidays)</td>
                <td><strong>{{ $employeeSummary['work_days'] }} days</strong></td>
              </tr>
              <tr>
                <td class="text-muted">Standard Work Hours per Day</td>
                <td><strong>{{ $employeeSummary['work_hours_per_day'] }} hours</strong></td>
              </tr>
              <tr>
                <td class="text-muted">Expected Work Hours</td>
                <td><strong>{{ number_format($employeeSummary['expected_work_hours'], 1) }} hours</strong></td>
              </tr>
              <tr>
                <td class="text-muted">Approved Leave Days</td>
                <td><strong>{{ $employeeSummary['vacation_days'] }} days</strong></td>
              </tr>
              <tr>
                <td class="text-muted">Vacation Hours (Leave × {{ $employeeSummary['work_hours_per_day'] }}h)</td>
                <td><strong>{{ number_format($employeeSummary['vacation_hours'], 1) }} hours</strong></td>
              </tr>
              <tr>
                <td class="text-muted">Actual Worked Time (from attendance)</td>
                <td><strong>{{ floor($employeeSummary['total_work_minutes'] / 60) }}h {{ $employeeSummary['total_work_minutes'] % 60 }}m</strong></td>
              </tr>
              <tr>
                <td class="text-muted">Late Penalties Deducted</td>
                <td class="text-danger"><strong>-{{ floor($employeeSummary['total_late_penalty_minutes'] / 60) }}h {{ $employeeSummary['total_late_penalty_minutes'] % 60 }}m</strong></td>
              </tr>
              <tr class="table-active">
                <td class="text-muted"><strong>Total Work Hours</strong></td>
                <td><strong class="{{ $employeeSummary['percentage'] >= 100 ? 'text-success' : ($employeeSummary['percentage'] >= 90 ? 'text-warning' : 'text-danger') }}">
                  {{ number_format($employeeSummary['total_work_hours'], 1) }} hours ({{ number_format($employeeSummary['percentage'], 1) }}%)
                </strong></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    @endif

    <!-- Late Penalty Rules Info -->
    @if(!empty($latePenaltyRules))
    <div class="card mt-4">
      <div class="card-header">
        <h6 class="mb-0"><i class="ti ti-info-circle me-2"></i>Late Penalty Rules</h6>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm table-bordered mb-0">
            <thead class="table-light">
              <tr>
                <th>Late Duration</th>
                <th>Penalty</th>
              </tr>
            </thead>
            <tbody>
              @php
                $rulesArray = $latePenaltyRules->values()->all();
                $rulesCount = count($rulesArray);
              @endphp
              @foreach($rulesArray as $index => $rule)
              @php
                $fromMinutes = $rule->config['late_minutes'] ?? 0;
                $toMinutes = ($index < $rulesCount - 1)
                    ? (($rulesArray[$index + 1]->config['late_minutes'] ?? 0) - 1)
                    : null;
                $penaltyMinutes = $rule->config['penalty_minutes'] ?? 0;
              @endphp
              <tr>
                <td>{{ $fromMinutes }} - {{ $toMinutes !== null ? $toMinutes : '∞' }} minutes late</td>
                <td><span class="badge bg-warning text-dark">{{ $penaltyMinutes }} minutes</span></td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
    @endif
  </div>
</div>
@endsection

@section('page-script')
<script>
function toggleFilterType() {
  const filterType = document.querySelector('input[name="filter_type"]:checked').value;
  const monthFilters = document.getElementById('monthFilters');
  const rangeFilters = document.getElementById('rangeFilters');

  if (filterType === 'month') {
    monthFilters.classList.remove('d-none');
    rangeFilters.classList.add('d-none');
  } else {
    monthFilters.classList.add('d-none');
    rangeFilters.classList.remove('d-none');
  }
}
</script>
@endsection
