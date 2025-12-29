@extends('layouts/layoutMaster')

@section('title', 'Attendance Records')

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
  .clickable-time {
    cursor: pointer;
    transition: all 0.2s;
  }
  .clickable-time:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
  }
  .clickable-missing {
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: underline;
  }
  .clickable-missing:hover {
    color: #7367f0 !important;
    font-weight: 500;
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
            <i class="ti tabler-calendar-stats me-2"></i>Attendance Records
          </h5>
          <small class="text-muted">View and filter employee attendance data</small>
        </div>
        <div class="d-flex gap-2">
          <a href="{{ route('attendance.import.zkteco') }}" class="btn btn-outline-primary">
            <i class="ti tabler-fingerprint me-1"></i>Import ZKTeco
          </a>
          <a href="{{ route('attendance.import.create') }}" class="btn btn-outline-secondary">
            <i class="ti tabler-upload me-1"></i>Import CSV
          </a>
        </div>
      </div>
    </div>

    <!-- Filter Card -->
    <div class="card filter-card mb-4">
      <div class="card-header">
        <h6 class="mb-0"><i class="ti tabler-filter me-2"></i>Filters</h6>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route('attendance.records') }}" id="filterForm">
          <div class="row g-3">
            <!-- Filter Type Toggle -->
            <div class="col-12">
              <div class="btn-group" role="group">
                <input type="radio" class="btn-check" name="filter_type" id="filterMonth" value="month"
                       {{ $filterType === 'month' ? 'checked' : '' }} onchange="toggleFilterType()">
                <label class="btn btn-outline-primary" for="filterMonth">
                  <i class="ti tabler-calendar-month me-1"></i>By Month
                </label>

                <input type="radio" class="btn-check" name="filter_type" id="filterRange" value="range"
                       {{ $filterType === 'range' ? 'checked' : '' }} onchange="toggleFilterType()">
                <label class="btn btn-outline-primary" for="filterRange">
                  <i class="ti tabler-calendar-event me-1"></i>Date Range
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
              </div>
            </div>

            <!-- Employee Filter (Always Visible) -->
            <div class="col-md-4">
              <label class="form-label">Employee</label>
              <select class="form-select" name="employee_id" onchange="document.getElementById('filterForm').submit()">
                <option value="">All Employees</option>
                @foreach($employees as $emp)
                  <option value="{{ $emp->id }}" {{ $employeeId == $emp->id ? 'selected' : '' }}>
                    {{ $emp->name }}
                    @if($emp->attendance_id)
                      (ID: {{ $emp->attendance_id }})
                    @endif
                  </option>
                @endforeach
              </select>
            </div>

            <!-- Apply Button (for date range) -->
            <div class="col-md-2 d-flex align-items-end">
              <button type="submit" class="btn btn-primary w-100">
                <i class="ti tabler-search me-1"></i>Apply
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card summary-card bg-label-primary">
          <div class="card-body text-center">
            <i class="ti tabler-calendar-check mb-2" style="font-size: 2rem;"></i>
            <h6 class="mb-1">Total Days</h6>
            <h3 class="mb-0">{{ number_format($summary['total_days']) }}</h3>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card summary-card bg-label-success">
          <div class="card-body text-center">
            <i class="ti tabler-clock-hour-4 mb-2" style="font-size: 2rem;"></i>
            <h6 class="mb-1">Total Hours</h6>
            <h3 class="mb-0">{{ $summary['total_hours'] }}h {{ $summary['total_minutes'] }}m</h3>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card summary-card bg-label-info">
          <div class="card-body text-center">
            <i class="ti tabler-users mb-2" style="font-size: 2rem;"></i>
            <h6 class="mb-1">Employees</h6>
            <h3 class="mb-0">{{ number_format($summary['unique_employees']) }}</h3>
          </div>
        </div>
      </div>
    </div>

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
            @if($employeeId)
              - {{ $employees->firstWhere('id', $employeeId)->name ?? '' }}
            @endif
          </h6>
          <span class="badge bg-primary">{{ $pagination->total() }} records</span>
        </div>
        @if($employeeId)
        <div class="d-flex gap-3 flex-wrap small">
          <span><span class="badge bg-warning day-badge">Weekend</span> Light orange rows</span>
          <span><span class="badge bg-success day-badge">Holiday</span> Light green rows</span>
          <span><span class="badge bg-info day-badge">Leave</span> Light blue rows</span>
          <span><span class="badge bg-primary day-badge">WFH</span> Light purple rows</span>
          <span><span class="badge bg-danger day-badge">Absent</span> Light red rows - no attendance</span>
        </div>
        @endif
      </div>

      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>Employee</th>
              <th>Date</th>
              <th>Time In</th>
              <th>Time Out</th>
              <th>Total Time</th>
              <th>Permission</th>
              <th>Late Penalty</th>
              <th>Billable</th>
              @if(auth()->user() && (auth()->user()->hasRole('super-admin') || auth()->user()->role === 'super_admin'))
              <th>Actions</th>
              @endif
            </tr>
          </thead>
          <tbody>
            @forelse($pagination as $record)
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
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-sm me-2 bg-label-primary">
                    <span class="avatar-initial rounded-circle">
                      {{ strtoupper(substr($record['employee']->name ?? '?', 0, 1)) }}
                    </span>
                  </div>
                  <div>
                    <strong>{{ $record['employee']->name ?? 'Unknown' }}</strong>
                    @if($record['employee']->name_ar ?? null)
                      <br><small class="text-muted">{{ $record['employee']->name_ar }}</small>
                    @endif
                  </div>
                </div>
              </td>
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
                  <span class="badge bg-primary day-badge ms-1" title="{{ $record['wfh_notes'] ?? 'Work From Home' }}">WFH</span>
                @elseif($record['is_missing'] ?? false)
                  <span class="badge bg-danger day-badge ms-1">Absent</span>
                @endif
              </td>
              <td>
                @if($record['time_in'])
                  @php
                    // Get flexible check-in deadline from attendance rules (default 10:00 if no rule)
                    $flexibleTo = $flexibleHoursRule?->config['to'] ?? '10:00';
                    $flexibleCheckIn = Carbon\Carbon::parse($record['date'] . ' ' . $flexibleTo);
                    // If has permission, adjust the deadline
                    if ($record['has_permission']) {
                      $flexibleCheckIn->addMinutes($record['permission_usage']->minutes_used);
                    }
                    $isLateCheckIn = $record['time_in']->gt($flexibleCheckIn);
                  @endphp
                  <span class="badge bg-{{ $isLateCheckIn ? 'danger' : 'success' }} clickable-time"
                        onclick="openPermissionModal({{ $record['employee']->id }}, '{{ $record['date'] }}', '{{ $record['employee']->name }}', '{{ $record['time_in']->format('h:i:s A') }}')"
                        title="Click to manage permission">
                    <i class="ti tabler-login me-1"></i>{{ $record['time_in']->format('h:i:s A') }}
                  </span>
                @elseif($record['is_weekend'] ?? false)
                  <span class="text-muted">-</span>
                @elseif($record['is_holiday'] ?? false)
                  <span class="text-muted">-</span>
                @elseif($record['is_on_leave'] ?? false)
                  <span class="text-muted">-</span>
                @elseif($record['is_wfh'] ?? false)
                  <span class="text-muted">-</span>
                @else
                  @if(auth()->user() && (auth()->user()->hasRole('super-admin') || auth()->user()->role === 'super_admin'))
                    <span class="time-missing clickable-missing"
                          onclick="openManualAttendanceModal({{ $record['employee']->id }}, '{{ $record['date'] }}', '{{ $record['employee']->name }}', 'time_in')"
                          title="Click to add check-in time">Missing</span>
                  @else
                    <span class="time-missing">Missing</span>
                  @endif
                @endif
              </td>
              <td>
                @if($record['time_out'])
                  @php
                    // Get flexible start time and calculate minimum checkout (start + 8 hours)
                    $flexibleFrom = $flexibleHoursRule?->config['from'] ?? '08:00';
                    $minimumCheckOut = Carbon\Carbon::parse($record['date'] . ' ' . $flexibleFrom)->addHours(8);
                    $isEarlyCheckOut = $record['time_out']->lt($minimumCheckOut);
                  @endphp
                  <span class="badge bg-{{ $isEarlyCheckOut ? 'danger' : 'success' }}">
                    <i class="ti tabler-logout me-1"></i>{{ $record['time_out']->format('h:i:s A') }}
                  </span>
                @elseif($record['is_weekend'] ?? false)
                  <span class="text-muted">-</span>
                @elseif($record['is_holiday'] ?? false)
                  <span class="text-muted">-</span>
                @elseif($record['is_on_leave'] ?? false)
                  <span class="text-muted">-</span>
                @elseif($record['is_wfh'] ?? false)
                  <span class="text-muted">-</span>
                @else
                  @if(auth()->user() && (auth()->user()->hasRole('super-admin') || auth()->user()->role === 'super_admin'))
                    <span class="time-missing clickable-missing"
                          onclick="openManualAttendanceModal({{ $record['employee']->id }}, '{{ $record['date'] }}', '{{ $record['employee']->name }}', 'time_out')"
                          title="Click to add check-out time">Missing</span>
                  @else
                    <span class="time-missing">Missing</span>
                  @endif
                @endif
              </td>
              <td>
                @if($record['time_in'] && $record['time_out'])
                  @php
                    $hours = floor($record['total_minutes'] / 60);
                    $minutes = $record['total_minutes'] % 60;
                    // Less than 8 hours (480 minutes) is insufficient
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
                @if($record['has_permission'])
                  <span class="badge bg-info" title="{{ $record['permission_usage']->reason ?? 'Permission used' }}">
                    <i class="ti tabler-clock-pause me-1"></i>{{ $record['permission_usage']->minutes_used }}m
                  </span>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>
              <td>
                @if($record['late_penalty'] > 0)
                  <span class="badge bg-warning text-dark" title="Late by {{ round($record['late_minutes']) }} minutes">
                    <i class="ti tabler-clock-exclamation me-1"></i>{{ $record['late_penalty'] }}m
                  </span>
                @elseif($record['time_in'])
                  <span class="badge bg-label-success">
                    <i class="ti tabler-check me-1"></i>On time
                  </span>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>
              <td>
                @if($record['is_billable_employee'] ?? false)
                  @if(($record['billable_hours'] ?? 0) > 0)
                    <span class="badge bg-label-primary" title="Jira worklog hours">
                      <i class="ti tabler-brand-jira me-1"></i>{{ number_format($record['billable_hours'], 2) }}h
                    </span>
                  @else
                    <span class="text-muted">0h</span>
                  @endif
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>
              @if(auth()->user() && (auth()->user()->hasRole('super-admin') || auth()->user()->role === 'super_admin'))
              <td>
                <div class="dropdown">
                  <button type="button" class="btn btn-sm btn-icon btn-outline-secondary dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="ti tabler-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    @if($record['time_in'])
                      <li>
                        <a class="dropdown-item" href="javascript:void(0);"
                           onclick="openEditAttendanceModal({{ $record['employee']->id }}, '{{ $record['date'] }}', '{{ $record['employee']->name }}', 'time_in', '{{ $record['time_in']->format('H:i') }}')">
                          <i class="ti tabler-edit me-2 text-info"></i>Edit Check-in
                        </a>
                      </li>
                    @else
                      <li>
                        <a class="dropdown-item" href="javascript:void(0);"
                           onclick="openManualAttendanceModal({{ $record['employee']->id }}, '{{ $record['date'] }}', '{{ $record['employee']->name }}', 'time_in')">
                          <i class="ti tabler-login me-2 text-success"></i>Add Check-in
                        </a>
                      </li>
                    @endif
                    @if($record['time_out'])
                      <li>
                        <a class="dropdown-item" href="javascript:void(0);"
                           onclick="openEditAttendanceModal({{ $record['employee']->id }}, '{{ $record['date'] }}', '{{ $record['employee']->name }}', 'time_out', '{{ $record['time_out']->format('H:i') }}')">
                          <i class="ti tabler-edit me-2 text-info"></i>Edit Check-out
                        </a>
                      </li>
                    @else
                      <li>
                        <a class="dropdown-item" href="javascript:void(0);"
                           onclick="openManualAttendanceModal({{ $record['employee']->id }}, '{{ $record['date'] }}', '{{ $record['employee']->name }}', 'time_out')">
                          <i class="ti tabler-logout me-2 text-warning"></i>Add Check-out
                        </a>
                      </li>
                    @endif
                    <li><hr class="dropdown-divider"></li>
                    @if(!($record['is_on_leave'] ?? false))
                      <li>
                        <a class="dropdown-item" href="javascript:void(0);"
                           onclick="openAddWfhModal({{ $record['employee']->id }}, '{{ $record['date'] }}', '{{ $record['employee']->name }}')">
                          <i class="ti tabler-home me-2 text-primary"></i>Add WFH
                        </a>
                      </li>
                      <li>
                        <a class="dropdown-item" href="javascript:void(0);"
                           onclick="openAddLeaveModal({{ $record['employee']->id }}, '{{ $record['date'] }}', '{{ $record['employee']->name }}')">
                          <i class="ti tabler-calendar-off me-2 text-info"></i>Add Leave
                        </a>
                      </li>
                    @else
                      <li>
                        <a class="dropdown-item text-danger" href="javascript:void(0);"
                           onclick="deleteLeave({{ $record['employee']->id }}, '{{ $record['date'] }}')">
                          <i class="ti tabler-calendar-x me-2"></i>Remove Leave
                        </a>
                      </li>
                    @endif
                    @if($record['time_in'] || $record['time_out'])
                    <li><hr class="dropdown-divider"></li>
                    @if($record['time_in'])
                      <li>
                        <a class="dropdown-item text-danger" href="javascript:void(0);"
                           onclick="deleteAttendance({{ $record['employee']->id }}, '{{ $record['date'] }}', 'time_in')">
                          <i class="ti tabler-trash me-2"></i>Delete Check-in
                        </a>
                      </li>
                    @endif
                    @if($record['time_out'])
                      <li>
                        <a class="dropdown-item text-danger" href="javascript:void(0);"
                           onclick="deleteAttendance({{ $record['employee']->id }}, '{{ $record['date'] }}', 'time_out')">
                          <i class="ti tabler-trash me-2"></i>Delete Check-out
                        </a>
                      </li>
                    @endif
                    @endif
                  </ul>
                </div>
              </td>
              @endif
            </tr>
            @empty
            <tr>
              <td colspan="{{ (auth()->user() && (auth()->user()->hasRole('super-admin') || auth()->user()->role === 'super_admin')) ? 9 : 8 }}" class="text-center py-5">
                <div class="d-flex flex-column align-items-center">
                  <i class="ti tabler-calendar-off text-muted" style="font-size: 3rem;"></i>
                  <h6 class="mt-2">No attendance records found</h6>
                  <p class="text-muted">Try adjusting your filters or import attendance data</p>
                  <a href="{{ route('attendance.import.zkteco') }}" class="btn btn-primary">
                    <i class="ti tabler-fingerprint me-1"></i>Import ZKTeco Data
                  </a>
                </div>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($pagination->hasPages())
      <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
          <div class="text-muted">
            Showing {{ $pagination->firstItem() }} to {{ $pagination->lastItem() }} of {{ $pagination->total() }} records
          </div>
          {{ $pagination->links() }}
        </div>
      </div>
      @endif
    </div>

    <!-- Employee Summary (only shown when single employee is filtered) -->
    @if($employeeSummary)
    <div class="card mt-4">
      <div class="card-header bg-label-primary">
        <h6 class="mb-0">
          <i class="ti tabler-chart-bar me-2"></i>Employee Summary
        </h6>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <!-- Expected Work Hours -->
          <div class="col-md-3">
            <div class="border rounded p-3 text-center">
              <div class="text-muted small mb-1">Expected Work Hours</div>
              <h4 class="mb-0 text-primary">
                {{ number_format($employeeSummary['expected_work_hours'], 1) }}h
              </h4>
              <small class="text-muted">{{ $employeeSummary['work_days'] }} work days × {{ $employeeSummary['work_hours_per_day'] }}h</small>
            </div>
          </div>

          <!-- Vacation Hours -->
          <div class="col-md-3">
            <div class="border rounded p-3 text-center">
              <div class="text-muted small mb-1">Vacation Hours</div>
              <h4 class="mb-0 text-info">
                {{ number_format($employeeSummary['vacation_hours'], 1) }}h
              </h4>
              <small class="text-muted">{{ $employeeSummary['vacation_days'] }} leave days × {{ $employeeSummary['work_hours_per_day'] }}h</small>
            </div>
          </div>

          <!-- WFH Hours -->
          <div class="col-md-3">
            <div class="border rounded p-3 text-center">
              <div class="text-muted small mb-1">WFH Hours</div>
              <h4 class="mb-0 text-primary">
                {{ number_format($employeeSummary['wfh_hours'] ?? 0, 1) }}h
              </h4>
              <small class="text-muted">{{ $employeeSummary['wfh_days'] ?? 0 }} WFH days × {{ $employeeSummary['work_hours_per_day'] }}h</small>
            </div>
          </div>

          <!-- Total Work Hours -->
          <div class="col-md-3">
            <div class="border rounded p-3 text-center">
              <div class="text-muted small mb-1">Total Work Hours</div>
              <h4 class="mb-0 {{ $employeeSummary['percentage'] >= 100 ? 'text-success' : ($employeeSummary['percentage'] >= 90 ? 'text-warning' : 'text-danger') }}">
                {{ number_format($employeeSummary['total_work_hours'], 1) }}h
              </h4>
              <small class="text-muted">
                {{ floor($employeeSummary['total_work_minutes'] / 60) }}h {{ $employeeSummary['total_work_minutes'] % 60 }}m worked
                - {{ floor($employeeSummary['total_late_penalty_minutes'] / 60) }}h {{ $employeeSummary['total_late_penalty_minutes'] % 60 }}m penalties
                + {{ number_format($employeeSummary['vacation_hours'], 1) }}h vacation
                + {{ number_format($employeeSummary['wfh_hours'] ?? 0, 1) }}h WFH
              </small>
            </div>
          </div>

          <!-- Percentage -->
          <div class="col-md-3">
            <div class="border rounded p-3 text-center">
              <div class="text-muted small mb-1">Completion Percentage</div>
              <h4 class="mb-0 {{ $employeeSummary['percentage'] >= 100 ? 'text-success' : ($employeeSummary['percentage'] >= 90 ? 'text-warning' : 'text-danger') }}">
                {{ number_format($employeeSummary['percentage'], 1) }}%
              </h4>
              <div class="progress mt-2" style="height: 8px;">
                <div class="progress-bar {{ $employeeSummary['percentage'] >= 100 ? 'bg-success' : ($employeeSummary['percentage'] >= 90 ? 'bg-warning' : 'bg-danger') }}"
                     role="progressbar"
                     style="width: {{ min($employeeSummary['percentage'], 100) }}%"
                     aria-valuenow="{{ $employeeSummary['percentage'] }}"
                     aria-valuemin="0"
                     aria-valuemax="100">
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Detailed Breakdown -->
        <div class="mt-4 pt-3 border-top">
          <h6 class="mb-3"><i class="ti tabler-list-details me-2"></i>Detailed Breakdown</h6>
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
                  <td class="text-muted">WFH Days</td>
                  <td><strong>{{ $employeeSummary['wfh_days'] ?? 0 }} days</strong></td>
                </tr>
                <tr>
                  <td class="text-muted">WFH Hours (WFH × {{ $employeeSummary['work_hours_per_day'] }}h)</td>
                  <td><strong>{{ number_format($employeeSummary['wfh_hours'] ?? 0, 1) }} hours</strong></td>
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
    </div>
    @endif
  </div>
</div>

<!-- Permission Modal -->
<div class="modal fade" id="permissionModal" tabindex="-1" aria-labelledby="permissionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="permissionModalLabel">
          <i class="ti tabler-clock-pause me-2"></i>Manage Permission
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Employee & Date Info -->
        <div class="alert alert-light d-flex align-items-center mb-3">
          <i class="ti tabler-user me-2"></i>
          <div>
            <strong id="modal-employee-name"></strong>
            <br>
            <small class="text-muted" id="modal-date"></small>
            <span class="badge bg-label-primary ms-2" id="modal-time-in"></span>
          </div>
        </div>

        <!-- Permission Status -->
        <div id="permission-status-container" class="mb-3">
          <div class="d-flex justify-content-center">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
        </div>

        <!-- Add Permission Form (shown when no permission exists) -->
        <div id="add-permission-form" class="d-none">
          <div class="mb-3">
            <label class="form-label">Reason (optional)</label>
            <textarea class="form-control" id="permission-reason" rows="2" placeholder="e.g., Doctor's appointment, Personal errand..."></textarea>
          </div>
          <div class="alert alert-info small">
            <i class="ti tabler-info-circle me-1"></i>
            Adding a permission will extend the flexible hours deadline by <strong id="permission-minutes">120</strong> minutes for this date.
          </div>
        </div>

        <!-- Existing Permission Info (shown when permission exists) -->
        <div id="existing-permission-info" class="d-none">
          <div class="card bg-label-info">
            <div class="card-body">
              <h6 class="card-title"><i class="ti tabler-check me-2"></i>Permission Already Added</h6>
              <p class="card-text mb-1"><strong>Duration:</strong> <span id="existing-minutes"></span> minutes</p>
              <p class="card-text mb-1"><strong>Reason:</strong> <span id="existing-reason"></span></p>
              <p class="card-text mb-0"><small class="text-muted">Granted by: <span id="existing-granted-by"></span></small></p>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-danger d-none" id="remove-permission-btn" onclick="removePermission()">
          <i class="ti tabler-trash me-1"></i>Remove Permission
        </button>
        <button type="button" class="btn btn-primary d-none" id="add-permission-btn" onclick="addPermission()">
          <i class="ti tabler-plus me-1"></i>Add Permission
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Manual Attendance Modal (Super Admin only) -->
@if(auth()->user() && (auth()->user()->hasRole('super-admin') || auth()->user()->role === 'super_admin'))
<div class="modal fade" id="manualAttendanceModal" tabindex="-1" aria-labelledby="manualAttendanceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="manualAttendanceModalLabel">
          <i class="ti tabler-calendar-plus me-2"></i><span id="modal-title-text">Add Manual Attendance</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Employee & Date Info -->
        <div class="alert alert-light d-flex align-items-center mb-3">
          <i class="ti tabler-user me-2"></i>
          <div>
            <strong id="manual-employee-name"></strong>
            <br>
            <small class="text-muted" id="manual-date"></small>
            <span class="badge bg-label-warning ms-2" id="manual-type-badge"></span>
          </div>
        </div>

        <!-- Time Input -->
        <div class="mb-3">
          <label class="form-label" for="manual-time">
            <span id="manual-time-label">Check-in Time</span> <span class="text-danger">*</span>
          </label>
          <input type="time" class="form-control form-control-lg" id="manual-time" required>
          <div class="form-text" id="manual-time-hint">Enter the actual check-in time for this employee.</div>
        </div>

        <div class="alert alert-warning small">
          <i class="ti tabler-alert-triangle me-1"></i>
          <strong>Super Admin Action:</strong> This will create an attendance record in the database. Please ensure the time is accurate.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="save-manual-attendance-btn" onclick="saveManualAttendance()">
          <i class="ti tabler-device-floppy me-1"></i>Save Attendance
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Add WFH Modal -->
<div class="modal fade" id="addWfhModal" tabindex="-1" aria-labelledby="addWfhModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addWfhModalLabel">
          <i class="ti tabler-home me-2"></i>Add Work From Home
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Employee & Date Info -->
        <div class="alert alert-light d-flex align-items-center mb-3">
          <i class="ti tabler-user me-2"></i>
          <div>
            <strong id="wfh-employee-name"></strong>
            <br>
            <small class="text-muted" id="wfh-date"></small>
          </div>
        </div>

        <!-- Notes Input -->
        <div class="mb-3">
          <label class="form-label" for="wfh-notes">Notes (optional)</label>
          <textarea class="form-control" id="wfh-notes" rows="2" placeholder="e.g., Remote work, Personal reason..."></textarea>
        </div>

        <div class="alert alert-info small">
          <i class="ti tabler-info-circle me-1"></i>
          This will mark the employee as working from home for this date. The day will count as a work day.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="save-wfh-btn" onclick="saveWfh()">
          <i class="ti tabler-home me-1"></i>Add WFH
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Add Leave Modal -->
<div class="modal fade" id="addLeaveModal" tabindex="-1" aria-labelledby="addLeaveModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addLeaveModalLabel">
          <i class="ti tabler-calendar-off me-2"></i>Add Leave
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Employee & Date Info -->
        <div class="alert alert-light d-flex align-items-center mb-3">
          <i class="ti tabler-user me-2"></i>
          <div>
            <strong id="leave-employee-name"></strong>
            <br>
            <small class="text-muted" id="leave-date"></small>
          </div>
        </div>

        <!-- Leave Type Selection -->
        <div class="mb-3">
          <label class="form-label" for="leave-type">Leave Type <span class="text-danger">*</span></label>
          <select class="form-select" id="leave-type" required>
            <option value="">Select leave type...</option>
            @foreach($leavePolicies as $policy)
              <option value="{{ $policy->id }}">{{ $policy->name }}</option>
            @endforeach
          </select>
        </div>

        <!-- Notes Input -->
        <div class="mb-3">
          <label class="form-label" for="leave-notes">Notes (optional)</label>
          <textarea class="form-control" id="leave-notes" rows="2" placeholder="e.g., Doctor appointment, Family emergency..."></textarea>
        </div>

        <div class="alert alert-info small">
          <i class="ti tabler-info-circle me-1"></i>
          This will create an approved single-day leave record for this employee.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="save-leave-btn" onclick="saveLeave()">
          <i class="ti tabler-calendar-plus me-1"></i>Add Leave
        </button>
      </div>
    </div>
  </div>
</div>
@endif
@endsection

@section('page-script')
<script>
let currentEmployeeId = null;
let currentDate = null;
let permissionModal = null;
let manualAttendanceModal = null;
let addWfhModal = null;
let addLeaveModal = null;
let currentType = null;
let isEditMode = false;

document.addEventListener('DOMContentLoaded', function() {
  permissionModal = new bootstrap.Modal(document.getElementById('permissionModal'));
  @if(auth()->user() && (auth()->user()->hasRole('super-admin') || auth()->user()->role === 'super_admin'))
  manualAttendanceModal = new bootstrap.Modal(document.getElementById('manualAttendanceModal'));
  addWfhModal = new bootstrap.Modal(document.getElementById('addWfhModal'));
  addLeaveModal = new bootstrap.Modal(document.getElementById('addLeaveModal'));
  @endif
});

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

function openPermissionModal(employeeId, date, employeeName, timeIn) {
  currentEmployeeId = employeeId;
  currentDate = date;

  // Set modal info
  document.getElementById('modal-employee-name').textContent = employeeName;
  document.getElementById('modal-date').textContent = new Date(date).toLocaleDateString('en-US', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
  });
  document.getElementById('modal-time-in').textContent = 'Check-in: ' + timeIn;

  // Reset UI
  document.getElementById('permission-status-container').innerHTML = `
    <div class="d-flex justify-content-center">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
  `;
  document.getElementById('add-permission-form').classList.add('d-none');
  document.getElementById('existing-permission-info').classList.add('d-none');
  document.getElementById('add-permission-btn').classList.add('d-none');
  document.getElementById('remove-permission-btn').classList.add('d-none');
  document.getElementById('permission-reason').value = '';

  // Show modal
  permissionModal.show();

  // Fetch permission status
  fetchPermissionStatus();
}

function fetchPermissionStatus() {
  fetch('{{ route("attendance.permission-usage.status") }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({
      employee_id: currentEmployeeId,
      date: currentDate
    })
  })
  .then(response => response.json())
  .then(data => {
    // Update permission minutes info
    document.getElementById('permission-minutes').textContent = data.minutes_per_permission;

    // Show status
    let statusHtml = `
      <div class="row text-center mb-3">
        <div class="col-4">
          <div class="border rounded p-2">
            <div class="text-muted small">Available</div>
            <h5 class="mb-0 text-primary">${data.total_available}</h5>
          </div>
        </div>
        <div class="col-4">
          <div class="border rounded p-2">
            <div class="text-muted small">Used</div>
            <h5 class="mb-0 text-warning">${data.used_this_month}</h5>
          </div>
        </div>
        <div class="col-4">
          <div class="border rounded p-2">
            <div class="text-muted small">Remaining</div>
            <h5 class="mb-0 ${data.remaining > 0 ? 'text-success' : 'text-danger'}">${data.remaining}</h5>
          </div>
        </div>
      </div>
    `;
    document.getElementById('permission-status-container').innerHTML = statusHtml;

    if (data.existing_usage) {
      // Show existing permission info
      document.getElementById('existing-permission-info').classList.remove('d-none');
      document.getElementById('existing-minutes').textContent = data.existing_usage.minutes_used;
      document.getElementById('existing-reason').textContent = data.existing_usage.reason || 'Not specified';
      document.getElementById('existing-granted-by').textContent = data.existing_usage.granted_by || 'Unknown';
      document.getElementById('remove-permission-btn').classList.remove('d-none');
    } else if (data.can_use) {
      // Show add permission form
      document.getElementById('add-permission-form').classList.remove('d-none');
      document.getElementById('add-permission-btn').classList.remove('d-none');
    } else {
      // Show cannot use message
      document.getElementById('permission-status-container').innerHTML += `
        <div class="alert alert-warning">
          <i class="ti tabler-alert-triangle me-1"></i>
          ${data.reason}
        </div>
      `;
    }
  })
  .catch(error => {
    console.error('Error:', error);
    document.getElementById('permission-status-container').innerHTML = `
      <div class="alert alert-danger">
        <i class="ti tabler-alert-circle me-1"></i>
        Failed to load permission status. Please try again.
      </div>
    `;
  });
}

function addPermission() {
  const btn = document.getElementById('add-permission-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Adding...';

  fetch('{{ route("attendance.permission-usage.store") }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({
      employee_id: currentEmployeeId,
      date: currentDate,
      reason: document.getElementById('permission-reason').value
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Close modal and reload page
      permissionModal.hide();
      window.location.reload();
    } else {
      alert(data.message || 'Failed to add permission');
      btn.disabled = false;
      btn.innerHTML = '<i class="ti tabler-plus me-1"></i>Add Permission';
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Failed to add permission. Please try again.');
    btn.disabled = false;
    btn.innerHTML = '<i class="ti tabler-plus me-1"></i>Add Permission';
  });
}

function removePermission() {
  if (!confirm('Are you sure you want to remove this permission?')) {
    return;
  }

  const btn = document.getElementById('remove-permission-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Removing...';

  fetch('{{ route("attendance.permission-usage.destroy") }}', {
    method: 'DELETE',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({
      employee_id: currentEmployeeId,
      date: currentDate
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Close modal and reload page
      permissionModal.hide();
      window.location.reload();
    } else {
      alert(data.message || 'Failed to remove permission');
      btn.disabled = false;
      btn.innerHTML = '<i class="ti tabler-trash me-1"></i>Remove Permission';
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Failed to remove permission. Please try again.');
    btn.disabled = false;
    btn.innerHTML = '<i class="ti tabler-trash me-1"></i>Remove Permission';
  });
}

@if(auth()->user() && (auth()->user()->hasRole('super-admin') || auth()->user()->role === 'super_admin'))
function openManualAttendanceModal(employeeId, date, employeeName, type) {
  currentEmployeeId = employeeId;
  currentDate = date;
  currentType = type;
  isEditMode = false;

  // Set modal info
  document.getElementById('manual-employee-name').textContent = employeeName;
  document.getElementById('manual-date').textContent = new Date(date).toLocaleDateString('en-US', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
  });

  // Update type-specific content
  const isTimeIn = type === 'time_in';
  document.getElementById('modal-title-text').textContent = 'Add Manual Attendance';
  document.getElementById('manual-type-badge').textContent = isTimeIn ? 'Check-in' : 'Check-out';
  document.getElementById('manual-time-label').textContent = isTimeIn ? 'Check-in Time' : 'Check-out Time';
  document.getElementById('manual-time-hint').textContent = isTimeIn
    ? 'Enter the actual check-in time for this employee.'
    : 'Enter the actual check-out time for this employee.';

  // Set default time based on type
  if (isTimeIn) {
    document.getElementById('manual-time').value = '09:00';
  } else {
    document.getElementById('manual-time').value = '17:00';
  }

  // Reset button state
  const btn = document.getElementById('save-manual-attendance-btn');
  btn.disabled = false;
  btn.innerHTML = '<i class="ti tabler-device-floppy me-1"></i>Save Attendance';

  // Show modal
  manualAttendanceModal.show();
}

function openEditAttendanceModal(employeeId, date, employeeName, type, existingTime) {
  currentEmployeeId = employeeId;
  currentDate = date;
  currentType = type;
  isEditMode = true;

  // Set modal info
  document.getElementById('manual-employee-name').textContent = employeeName;
  document.getElementById('manual-date').textContent = new Date(date).toLocaleDateString('en-US', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
  });

  // Update type-specific content
  const isTimeIn = type === 'time_in';
  document.getElementById('modal-title-text').textContent = 'Edit Attendance';
  document.getElementById('manual-type-badge').textContent = isTimeIn ? 'Check-in' : 'Check-out';
  document.getElementById('manual-time-label').textContent = isTimeIn ? 'Check-in Time' : 'Check-out Time';
  document.getElementById('manual-time-hint').textContent = isTimeIn
    ? 'Update the check-in time for this employee.'
    : 'Update the check-out time for this employee.';

  // Set existing time
  document.getElementById('manual-time').value = existingTime;

  // Reset button state
  const btn = document.getElementById('save-manual-attendance-btn');
  btn.disabled = false;
  btn.innerHTML = '<i class="ti tabler-device-floppy me-1"></i>Update Attendance';

  // Show modal
  manualAttendanceModal.show();
}

function saveManualAttendance() {
  const time = document.getElementById('manual-time').value;

  if (!time) {
    alert('Please enter a time.');
    return;
  }

  const btn = document.getElementById('save-manual-attendance-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

  const url = isEditMode ? '{{ route("attendance.manual-attendance.update") }}' : '{{ route("attendance.manual-attendance.store") }}';
  const method = isEditMode ? 'PUT' : 'POST';

  fetch(url, {
    method: method,
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({
      employee_id: currentEmployeeId,
      date: currentDate,
      time: time,
      type: currentType
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Close modal and reload page
      manualAttendanceModal.hide();
      window.location.reload();
    } else {
      alert(data.message || 'Failed to save attendance');
      btn.disabled = false;
      btn.innerHTML = '<i class="ti tabler-device-floppy me-1"></i>' + (isEditMode ? 'Update Attendance' : 'Save Attendance');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Failed to save attendance. Please try again.');
    btn.disabled = false;
    btn.innerHTML = '<i class="ti tabler-device-floppy me-1"></i>' + (isEditMode ? 'Update Attendance' : 'Save Attendance');
  });
}

function deleteAttendance(employeeId, date, type) {
  if (!confirm('Are you sure you want to delete this attendance record?')) {
    return;
  }

  fetch('{{ route("attendance.manual-attendance.destroy") }}', {
    method: 'DELETE',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({
      employee_id: employeeId,
      date: date,
      type: type
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      window.location.reload();
    } else {
      alert(data.message || 'Failed to delete attendance record');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Failed to delete attendance record. Please try again.');
  });
}

// WFH Functions
function openAddWfhModal(employeeId, date, employeeName) {
  currentEmployeeId = employeeId;
  currentDate = date;

  // Set modal info
  document.getElementById('wfh-employee-name').textContent = employeeName;
  document.getElementById('wfh-date').textContent = new Date(date).toLocaleDateString('en-US', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
  });

  // Reset form
  document.getElementById('wfh-notes').value = '';

  // Reset button state
  const btn = document.getElementById('save-wfh-btn');
  btn.disabled = false;
  btn.innerHTML = '<i class="ti tabler-home me-1"></i>Add WFH';

  // Show modal
  addWfhModal.show();
}

function saveWfh() {
  const btn = document.getElementById('save-wfh-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

  fetch('{{ route("attendance.quick-wfh.store") }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({
      employee_id: currentEmployeeId,
      date: currentDate,
      notes: document.getElementById('wfh-notes').value
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      addWfhModal.hide();
      window.location.reload();
    } else {
      alert(data.message || 'Failed to add WFH record');
      btn.disabled = false;
      btn.innerHTML = '<i class="ti tabler-home me-1"></i>Add WFH';
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Failed to add WFH record. Please try again.');
    btn.disabled = false;
    btn.innerHTML = '<i class="ti tabler-home me-1"></i>Add WFH';
  });
}

// Leave Functions
function openAddLeaveModal(employeeId, date, employeeName) {
  currentEmployeeId = employeeId;
  currentDate = date;

  // Set modal info
  document.getElementById('leave-employee-name').textContent = employeeName;
  document.getElementById('leave-date').textContent = new Date(date).toLocaleDateString('en-US', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
  });

  // Reset form
  document.getElementById('leave-type').value = '';
  document.getElementById('leave-notes').value = '';

  // Reset button state
  const btn = document.getElementById('save-leave-btn');
  btn.disabled = false;
  btn.innerHTML = '<i class="ti tabler-calendar-plus me-1"></i>Add Leave';

  // Show modal
  addLeaveModal.show();
}

function saveLeave() {
  const leaveType = document.getElementById('leave-type').value;

  if (!leaveType) {
    alert('Please select a leave type.');
    return;
  }

  const btn = document.getElementById('save-leave-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

  fetch('{{ route("attendance.quick-leave.store") }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({
      employee_id: currentEmployeeId,
      date: currentDate,
      leave_policy_id: leaveType,
      notes: document.getElementById('leave-notes').value
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      addLeaveModal.hide();
      window.location.reload();
    } else {
      alert(data.message || 'Failed to add leave record');
      btn.disabled = false;
      btn.innerHTML = '<i class="ti tabler-calendar-plus me-1"></i>Add Leave';
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Failed to add leave record. Please try again.');
    btn.disabled = false;
    btn.innerHTML = '<i class="ti tabler-calendar-plus me-1"></i>Add Leave';
  });
}

function deleteLeave(employeeId, date) {
  if (!confirm('Are you sure you want to remove this leave record?')) {
    return;
  }

  fetch('{{ route("attendance.quick-leave.destroy") }}', {
    method: 'DELETE',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({
      employee_id: employeeId,
      date: date
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      window.location.reload();
    } else {
      alert(data.message || 'Failed to remove leave record');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Failed to remove leave record. Please try again.');
  });
}
@endif
</script>
@endsection
