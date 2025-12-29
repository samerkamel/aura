@extends('layouts/layoutMaster')

@section('title', 'Attendance Summary')

@section('page-style')
<style>
  .summary-table {
    font-size: 0.85rem;
  }
  .summary-table th,
  .summary-table td {
    text-align: center;
    vertical-align: middle;
    padding: 0.4rem 0.3rem;
    white-space: nowrap;
  }
  .summary-table th.employee-col,
  .summary-table td.employee-col {
    text-align: center;
    width: 50px;
    min-width: 50px;
    max-width: 50px;
    position: sticky;
    left: 0;
    background: #fff;
    z-index: 1;
  }
  .summary-table thead th {
    background: #f8f9fa;
    position: sticky;
    top: 0;
    z-index: 2;
  }
  .summary-table thead th.employee-col {
    z-index: 3;
  }
  .metric-label {
    text-align: left;
    padding-left: 1rem !important;
    font-weight: normal;
    color: #6c757d;
    min-width: 110px;
  }
  .employee-name {
    font-weight: 600;
    background-color: #f8f9fa;
  }
  .employee-name-vertical {
    writing-mode: vertical-rl;
    text-orientation: mixed;
    transform: rotate(180deg);
    white-space: nowrap;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.5rem 0.2rem !important;
    height: auto;
    line-height: 1.2;
  }
  .employee-name-vertical .name-en {
    display: block;
    color: #333;
  }
  .employee-name-vertical .name-ar {
    display: block;
    font-size: 0.65rem;
    color: #6c757d;
    margin-top: 4px;
  }
  .metric-row-attended td { background-color: rgba(102, 108, 255, 0.05); }
  .metric-row-hours td { background-color: rgba(115, 103, 240, 0.05); }
  .metric-row-penalty td { background-color: rgba(234, 84, 85, 0.05); }
  .metric-row-absent td { background-color: rgba(255, 159, 67, 0.05); }
  .metric-row-vacation td { background-color: rgba(0, 207, 232, 0.05); }
  .metric-row-permission td { background-color: rgba(40, 199, 111, 0.05); }
  .metric-row-wfh td { background-color: rgba(168, 170, 174, 0.05); }
  .value-zero { color: #adb5bd; }
  .value-warning { color: #ff9f43; font-weight: 500; }
  .value-danger { color: #ea5455; font-weight: 500; }
  .value-success { color: #28c76f; font-weight: 500; }
  .value-info { color: #00cfe8; font-weight: 500; }
  .month-header {
    min-width: 70px;
  }
  .table-wrapper {
    overflow-x: auto;
    max-height: 75vh;
  }
  .year-filter {
    max-width: 200px;
  }
  .employee-filter {
    min-width: 300px;
  }
  .filter-form {
    flex-wrap: wrap;
  }
</style>
@endsection

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Page Header -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0">
            <i class="ti tabler-chart-bar me-2"></i>Attendance Summary
          </h5>
          <small class="text-muted">Yearly attendance overview for all employees</small>
        </div>
        <div class="d-flex gap-2 align-items-center">
          <a href="{{ route('attendance.records') }}" class="btn btn-outline-secondary">
            <i class="ti tabler-list me-1"></i>Daily Records
          </a>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" action="{{ route('attendance.summary') }}" id="filterForm" class="d-flex align-items-end gap-3 filter-form">
          <div>
            <label class="form-label fw-semibold">Year</label>
            <select class="form-select year-filter" name="year">
              @foreach($years as $y)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
              @endforeach
            </select>
          </div>
          <div class="flex-grow-1">
            <label class="form-label fw-semibold">Employees</label>
            <select class="form-select employee-filter" name="employee_ids[]" id="employeeFilter" multiple>
              @foreach($allEmployees as $emp)
                <option value="{{ $emp->id }}" {{ in_array($emp->id, $selectedEmployeeIds) ? 'selected' : '' }}>
                  {{ $emp->name }}{{ $emp->name_ar ? ' - ' . $emp->name_ar : '' }}
                </option>
              @endforeach
            </select>
          </div>
          <div>
            <button type="submit" class="btn btn-primary">
              <i class="ti tabler-filter me-1"></i>Apply Filter
            </button>
          </div>
          @if(!empty($selectedEmployeeIds))
          <div>
            <a href="{{ route('attendance.summary', ['year' => $year]) }}" class="btn btn-outline-secondary">
              <i class="ti tabler-x me-1"></i>Clear
            </a>
          </div>
          @endif
        </form>
      </div>
    </div>

    <!-- Summary Table -->
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ti tabler-calendar-stats me-2"></i>{{ $year }} Attendance Summary
          <span class="badge bg-primary ms-2">{{ count($summaryData) }} employee{{ count($summaryData) !== 1 ? 's' : '' }}</span>
          @if(!empty($selectedEmployeeIds))
            <span class="badge bg-label-info ms-1"><i class="ti tabler-filter me-1"></i>Filtered</span>
          @endif
        </h6>
      </div>
      <div class="card-body p-0">
        <div class="table-wrapper">
          <table class="table table-bordered summary-table mb-0">
            <thead>
              <tr>
                <th class="employee-col" rowspan="2"></th>
                <th class="metric-label" rowspan="2">Metric</th>
                @foreach($monthNames as $monthNum => $monthName)
                  <th class="month-header">{{ $monthName }}</th>
                @endforeach
                <th class="month-header bg-label-primary">Total</th>
              </tr>
            </thead>
            <tbody>
              @forelse($summaryData as $data)
                @php
                  $employee = $data['employee'];
                  $months = $data['months'];

                  // Calculate yearly totals
                  $totalAttended = 0;
                  $totalHours = 0;
                  $totalPenalty = 0;
                  $totalAbsent = 0;
                  $totalVacation = 0;
                  $totalPermissions = 0;
                  $totalWfh = 0;

                  foreach ($months as $m) {
                    $totalAttended += $m['attended_days'];
                    $totalHours += $m['attendance_hours'];
                    $totalPenalty += $m['late_penalty_hours'];
                    $totalAbsent += $m['absent_days'];
                    $totalVacation += $m['vacation_days'];
                    $totalPermissions += $m['permissions'];
                    $totalWfh += $m['wfh_days'];
                  }
                @endphp

                <!-- Attended Days Row -->
                <tr class="metric-row-attended">
                  <td class="employee-col employee-name employee-name-vertical" rowspan="7">
                    <span class="name-en">{{ $employee->name }}</span>
                    @if($employee->name_ar)
                      <span class="name-ar">{{ $employee->name_ar }}</span>
                    @endif
                  </td>
                  <td class="metric-label">
                    <i class="ti tabler-calendar-check text-primary me-1"></i>Attended
                  </td>
                  @foreach($monthNames as $monthNum => $monthName)
                    @php $val = $months[$monthNum]['attended_days']; @endphp
                    <td class="{{ $val == 0 ? 'value-zero' : '' }}">
                      {{ $val > 0 ? $val : '-' }}
                    </td>
                  @endforeach
                  <td class="fw-bold">{{ $totalAttended > 0 ? $totalAttended : '-' }}</td>
                </tr>

                <!-- Attendance Hours Row -->
                <tr class="metric-row-hours">
                  <td class="metric-label">
                    <i class="ti tabler-clock text-primary me-1"></i>Hours
                  </td>
                  @foreach($monthNames as $monthNum => $monthName)
                    @php $val = $months[$monthNum]['attendance_hours']; @endphp
                    <td class="{{ $val == 0 ? 'value-zero' : '' }}">
                      {{ $val > 0 ? number_format($val, 1) : '-' }}
                    </td>
                  @endforeach
                  <td class="fw-bold">{{ number_format($totalHours, 1) }}</td>
                </tr>

                <!-- Late Penalty Row -->
                <tr class="metric-row-penalty">
                  <td class="metric-label">
                    <i class="ti tabler-clock-exclamation text-danger me-1"></i>Late Penalty
                  </td>
                  @foreach($monthNames as $monthNum => $monthName)
                    @php $val = $months[$monthNum]['late_penalty_hours']; @endphp
                    <td class="{{ $val == 0 ? 'value-zero' : 'value-danger' }}">
                      {{ $val > 0 ? number_format($val, 1) : '-' }}
                    </td>
                  @endforeach
                  <td class="fw-bold {{ $totalPenalty > 0 ? 'value-danger' : '' }}">
                    {{ $totalPenalty > 0 ? number_format($totalPenalty, 1) : '-' }}
                  </td>
                </tr>

                <!-- Absent Days Row -->
                <tr class="metric-row-absent">
                  <td class="metric-label">
                    <i class="ti tabler-calendar-x text-warning me-1"></i>Absent Days
                  </td>
                  @foreach($monthNames as $monthNum => $monthName)
                    @php $val = $months[$monthNum]['absent_days']; @endphp
                    <td class="{{ $val == 0 ? 'value-zero' : 'value-warning' }}">
                      {{ $val > 0 ? $val : '-' }}
                    </td>
                  @endforeach
                  <td class="fw-bold {{ $totalAbsent > 0 ? 'value-warning' : '' }}">
                    {{ $totalAbsent > 0 ? $totalAbsent : '-' }}
                  </td>
                </tr>

                <!-- Vacation Days Row -->
                <tr class="metric-row-vacation">
                  <td class="metric-label">
                    <i class="ti tabler-beach text-info me-1"></i>Vacation Days
                  </td>
                  @foreach($monthNames as $monthNum => $monthName)
                    @php $val = $months[$monthNum]['vacation_days']; @endphp
                    <td class="{{ $val == 0 ? 'value-zero' : 'value-info' }}">
                      {{ $val > 0 ? $val : '-' }}
                    </td>
                  @endforeach
                  <td class="fw-bold {{ $totalVacation > 0 ? 'value-info' : '' }}">
                    {{ $totalVacation > 0 ? $totalVacation : '-' }}
                  </td>
                </tr>

                <!-- Permissions Row -->
                <tr class="metric-row-permission">
                  <td class="metric-label">
                    <i class="ti tabler-clock-pause text-success me-1"></i>Permissions
                  </td>
                  @foreach($monthNames as $monthNum => $monthName)
                    @php $val = $months[$monthNum]['permissions']; @endphp
                    <td class="{{ $val == 0 ? 'value-zero' : 'value-success' }}">
                      {{ $val > 0 ? $val : '-' }}
                    </td>
                  @endforeach
                  <td class="fw-bold {{ $totalPermissions > 0 ? 'value-success' : '' }}">
                    {{ $totalPermissions > 0 ? $totalPermissions : '-' }}
                  </td>
                </tr>

                <!-- WFH Days Row -->
                <tr class="metric-row-wfh">
                  <td class="metric-label">
                    <i class="ti tabler-home text-secondary me-1"></i>WFH Days
                  </td>
                  @foreach($monthNames as $monthNum => $monthName)
                    @php $val = $months[$monthNum]['wfh_days']; @endphp
                    <td class="{{ $val == 0 ? 'value-zero' : '' }}">
                      {{ $val > 0 ? $val : '-' }}
                    </td>
                  @endforeach
                  <td class="fw-bold">
                    {{ $totalWfh > 0 ? $totalWfh : '-' }}
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="15" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                      <i class="ti tabler-users-minus text-muted" style="font-size: 3rem;"></i>
                      <h6 class="mt-2">No employees found</h6>
                      <p class="text-muted">Add employees to see attendance summary</p>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Yearly Statistics Card -->
    <div class="card mt-4">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ti tabler-chart-pie me-2"></i>Yearly Statistics
          <span class="badge bg-label-secondary ms-2">{{ $year }}</span>
          @if(!empty($selectedEmployeeIds))
            <span class="badge bg-label-info ms-1">{{ $yearlyStats['employee_count'] }} employee{{ $yearlyStats['employee_count'] !== 1 ? 's' : '' }} selected</span>
          @else
            <span class="badge bg-label-primary ms-1">All {{ $yearlyStats['employee_count'] }} employees</span>
          @endif
        </h6>
      </div>
      <div class="card-body">
        <div class="row g-4">
          <!-- Working Days Target -->
          <div class="col-md-6 col-lg-3">
            <div class="d-flex align-items-start">
              <div class="avatar avatar-sm me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="ti tabler-calendar-check ti-md"></i>
                </span>
              </div>
              <div>
                <h6 class="mb-0">Attended Days</h6>
                <div class="d-flex align-items-center gap-2 mt-1">
                  <span class="h5 mb-0 text-primary">{{ $yearlyStats['avg_attended'] }}</span>
                  <span class="text-muted">/</span>
                  <span class="text-muted">{{ $yearlyStats['total_work_days'] }} days</span>
                </div>
                <div class="d-flex align-items-center mt-1">
                  <span class="badge bg-{{ $yearlyStats['attended_percentage'] >= 90 ? 'success' : ($yearlyStats['attended_percentage'] >= 75 ? 'warning' : 'danger') }}">
                    {{ $yearlyStats['attended_percentage'] }}%
                  </span>
                  <small class="text-muted ms-2">avg per employee</small>
                </div>
              </div>
            </div>
          </div>

          <!-- Expected Hours -->
          <div class="col-md-6 col-lg-3">
            <div class="d-flex align-items-start">
              <div class="avatar avatar-sm me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="ti tabler-clock ti-md"></i>
                </span>
              </div>
              <div>
                <h6 class="mb-0">Work Hours</h6>
                <div class="d-flex align-items-center gap-2 mt-1">
                  <span class="h5 mb-0 text-primary">{{ number_format($yearlyStats['avg_hours'], 1) }}h</span>
                  <span class="text-muted">/</span>
                  <span class="text-muted">{{ number_format($yearlyStats['total_expected_hours'], 0) }}h</span>
                </div>
                <div class="d-flex align-items-center mt-1">
                  <span class="badge bg-{{ $yearlyStats['hours_percentage'] >= 90 ? 'success' : ($yearlyStats['hours_percentage'] >= 75 ? 'warning' : 'danger') }}">
                    {{ $yearlyStats['hours_percentage'] }}%
                  </span>
                  <small class="text-muted ms-2">{{ $yearlyStats['work_hours_per_day'] }}h/day target</small>
                </div>
              </div>
            </div>
          </div>

          <!-- Late Penalty Average -->
          <div class="col-md-6 col-lg-3">
            <div class="d-flex align-items-start">
              <div class="avatar avatar-sm me-3">
                <span class="avatar-initial rounded bg-label-danger">
                  <i class="ti tabler-clock-exclamation ti-md"></i>
                </span>
              </div>
              <div>
                <h6 class="mb-0">Late Penalty</h6>
                <div class="d-flex align-items-center gap-2 mt-1">
                  <span class="h5 mb-0 {{ $yearlyStats['avg_penalty'] > 0 ? 'text-danger' : '' }}">{{ number_format($yearlyStats['avg_penalty'], 1) }}h</span>
                  <span class="text-muted">total</span>
                </div>
                <div class="d-flex align-items-center mt-1">
                  <small class="text-muted">
                    <i class="ti tabler-trending-down me-1"></i>
                    ~{{ number_format($yearlyStats['avg_penalty_per_month'], 1) }}h/month avg
                  </small>
                </div>
              </div>
            </div>
          </div>

          <!-- Absent Days Average -->
          <div class="col-md-6 col-lg-3">
            <div class="d-flex align-items-start">
              <div class="avatar avatar-sm me-3">
                <span class="avatar-initial rounded bg-label-warning">
                  <i class="ti tabler-calendar-x ti-md"></i>
                </span>
              </div>
              <div>
                <h6 class="mb-0">Absent Days</h6>
                <div class="d-flex align-items-center gap-2 mt-1">
                  <span class="h5 mb-0 {{ $yearlyStats['avg_absent'] > 0 ? 'text-warning' : '' }}">{{ $yearlyStats['avg_absent'] }}</span>
                  <span class="text-muted">days total</span>
                </div>
                <div class="d-flex align-items-center mt-1">
                  <small class="text-muted">
                    <i class="ti tabler-calendar me-1"></i>
                    ~{{ number_format($yearlyStats['avg_absent_per_month'], 1) }} days/month avg
                  </small>
                </div>
              </div>
            </div>
          </div>

          <!-- Vacation Days -->
          <div class="col-md-6 col-lg-3">
            <div class="d-flex align-items-start">
              <div class="avatar avatar-sm me-3">
                <span class="avatar-initial rounded bg-label-info">
                  <i class="ti tabler-beach ti-md"></i>
                </span>
              </div>
              <div>
                <h6 class="mb-0">Vacation Days</h6>
                <div class="d-flex align-items-center gap-2 mt-1">
                  <span class="h5 mb-0 text-info">{{ $yearlyStats['avg_vacation'] }}</span>
                  <span class="text-muted">days total</span>
                </div>
                <div class="d-flex align-items-center mt-1">
                  <small class="text-muted">
                    <i class="ti tabler-calendar me-1"></i>
                    ~{{ number_format($yearlyStats['avg_vacation_per_month'], 1) }} days/month avg
                  </small>
                </div>
              </div>
            </div>
          </div>

          <!-- Permissions -->
          <div class="col-md-6 col-lg-3">
            <div class="d-flex align-items-start">
              <div class="avatar avatar-sm me-3">
                <span class="avatar-initial rounded bg-label-success">
                  <i class="ti tabler-clock-pause ti-md"></i>
                </span>
              </div>
              <div>
                <h6 class="mb-0">Permissions Used</h6>
                <div class="d-flex align-items-center gap-2 mt-1">
                  <span class="h5 mb-0 text-success">{{ $yearlyStats['avg_permissions'] }}</span>
                  <span class="text-muted">total</span>
                </div>
                <div class="d-flex align-items-center mt-1">
                  <small class="text-muted">
                    <i class="ti tabler-clock me-1"></i>
                    ~{{ number_format($yearlyStats['avg_permissions_per_month'], 1) }}/month avg
                  </small>
                </div>
              </div>
            </div>
          </div>

          <!-- WFH Days -->
          <div class="col-md-6 col-lg-3">
            <div class="d-flex align-items-start">
              <div class="avatar avatar-sm me-3">
                <span class="avatar-initial rounded bg-label-secondary">
                  <i class="ti tabler-home ti-md"></i>
                </span>
              </div>
              <div>
                <h6 class="mb-0">WFH Days</h6>
                <div class="d-flex align-items-center gap-2 mt-1">
                  <span class="h5 mb-0">{{ $yearlyStats['avg_wfh'] }}</span>
                  <span class="text-muted">days total</span>
                </div>
                <div class="d-flex align-items-center mt-1">
                  <small class="text-muted">
                    <i class="ti tabler-calendar me-1"></i>
                    ~{{ number_format($yearlyStats['avg_wfh_per_month'], 1) }} days/month avg
                  </small>
                </div>
              </div>
            </div>
          </div>

          <!-- Summary Info -->
          <div class="col-md-6 col-lg-3">
            <div class="d-flex align-items-start">
              <div class="avatar avatar-sm me-3">
                <span class="avatar-initial rounded bg-label-dark">
                  <i class="ti tabler-info-circle ti-md"></i>
                </span>
              </div>
              <div>
                <h6 class="mb-0">Period Info</h6>
                <div class="mt-1">
                  <small class="text-muted d-block">
                    <i class="ti tabler-calendar-event me-1"></i>
                    26 Dec {{ $year - 1 }} - 25 Dec {{ $year }}
                  </small>
                  <small class="text-muted d-block mt-1">
                    <i class="ti tabler-users me-1"></i>
                    Values shown as average per employee
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2 for employee filter when jQuery is available
    if (typeof $ !== 'undefined' && $.fn.select2) {
      $('#employeeFilter').select2({
        placeholder: 'All Employees',
        allowClear: true,
        width: '100%'
      });
    }
  });
</script>
@endsection
