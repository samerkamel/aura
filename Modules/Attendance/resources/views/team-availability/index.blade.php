@extends('layouts/layoutMaster')

@section('title', 'Team Availability')

@section('page-style')
<style>
  .availability-table {
    font-size: 0.75rem;
  }
  .availability-table th,
  .availability-table td {
    text-align: center;
    vertical-align: middle;
    padding: 0.25rem 0.15rem;
    white-space: nowrap;
    min-width: 32px;
    max-width: 32px;
  }
  .availability-table th.employee-col,
  .availability-table td.employee-col {
    text-align: left;
    min-width: 150px;
    max-width: 150px;
    position: sticky;
    left: 0;
    background: #fff;
    z-index: 1;
    padding-left: 0.5rem;
  }
  .availability-table thead th {
    background: #f8f9fa;
    position: sticky;
    top: 0;
    z-index: 2;
    font-weight: 600;
  }
  .availability-table thead th.employee-col {
    z-index: 3;
  }
  .day-cell {
    width: 32px;
    height: 32px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 0.65rem;
  }
  .day-cell i {
    font-size: 0.75rem;
  }
  .day-header {
    font-size: 0.65rem;
  }
  .day-header .day-name {
    font-weight: 600;
    color: #6c757d;
  }
  .day-header .day-num {
    font-weight: bold;
    font-size: 0.8rem;
  }
  .day-header.weekend {
    background-color: #f8f9fa;
  }
  .day-header.weekend .day-name,
  .day-header.weekend .day-num {
    color: #adb5bd;
  }
  .table-wrapper {
    overflow-x: auto;
    max-height: 80vh;
  }
  .employee-name {
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .legend-item {
    display: inline-flex;
    align-items: center;
    margin-right: 1rem;
    font-size: 0.8rem;
  }
  .legend-box {
    width: 18px;
    height: 18px;
    border-radius: 4px;
    margin-right: 0.35rem;
  }
  .nav-month {
    font-size: 1.25rem;
    font-weight: 600;
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
            <i class="ti ti-calendar-stats me-2"></i>Team Availability
          </h5>
          <small class="text-muted">View team leave and WFH schedule for the month</small>
        </div>
        <div class="d-flex gap-2 align-items-center">
          <a href="{{ route('attendance.records') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-list me-1"></i>Daily Records
          </a>
          <a href="{{ route('attendance.summary') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-chart-bar me-1"></i>Summary
          </a>
        </div>
      </div>
    </div>

    <!-- Month Navigation -->
    <div class="card mb-4">
      <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center">
          <!-- Previous Month -->
          @php
            $prevMonth = $month - 1;
            $prevYear = $year;
            if ($prevMonth < 1) {
              $prevMonth = 12;
              $prevYear--;
            }
            $nextMonth = $month + 1;
            $nextYear = $year;
            if ($nextMonth > 12) {
              $nextMonth = 1;
              $nextYear++;
            }
          @endphp
          <a href="{{ route('attendance.team-availability', ['month' => $prevMonth, 'year' => $prevYear]) }}" class="btn btn-outline-secondary">
            <i class="ti ti-chevron-left"></i>
          </a>

          <!-- Month/Year Selector -->
          <div class="d-flex align-items-center gap-3">
            <form method="GET" action="{{ route('attendance.team-availability') }}" class="d-flex align-items-center gap-2" id="monthForm">
              <select class="form-select form-select-sm" name="month" style="width: auto;" onchange="document.getElementById('monthForm').submit()">
                @foreach($months as $m => $monthName)
                  <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ $monthName }}</option>
                @endforeach
              </select>
              <select class="form-select form-select-sm" name="year" style="width: auto;" onchange="document.getElementById('monthForm').submit()">
                @foreach($years as $y)
                  <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
              </select>
            </form>
            <span class="nav-month">{{ $startDate->format('F Y') }}</span>
          </div>

          <!-- Next Month -->
          <a href="{{ route('attendance.team-availability', ['month' => $nextMonth, 'year' => $nextYear]) }}" class="btn btn-outline-secondary">
            <i class="ti ti-chevron-right"></i>
          </a>
        </div>
      </div>
    </div>

    <!-- Legend -->
    <div class="card mb-4">
      <div class="card-body py-2">
        <div class="d-flex flex-wrap align-items-center">
          <span class="text-muted me-3 small fw-semibold">Legend:</span>
          <div class="legend-item">
            <div class="legend-box bg-success"></div>
            <span>Leave (Approved)</span>
          </div>
          <div class="legend-item">
            <div class="legend-box bg-warning"></div>
            <span>Leave (Pending)</span>
          </div>
          <div class="legend-item">
            <div class="legend-box bg-danger"></div>
            <span>Leave (Rejected)</span>
          </div>
          <div class="legend-item">
            <div class="legend-box bg-info"></div>
            <span>WFH</span>
          </div>
          <div class="legend-item">
            <div class="legend-box bg-dark"></div>
            <span>Holiday</span>
          </div>
          <div class="legend-item">
            <div class="legend-box bg-light border"></div>
            <span>Weekend</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Availability Calendar -->
    <div class="card">
      <div class="card-body p-0">
        <div class="table-wrapper">
          <table class="table table-bordered availability-table mb-0">
            <thead>
              <tr>
                <th class="employee-col">Employee</th>
                @foreach($days as $day)
                  @php
                    $isWeekend = $day->isFriday() || $day->isSaturday();
                    $isHoliday = isset($publicHolidays[$day->format('Y-m-d')]);
                  @endphp
                  <th class="day-header {{ $isWeekend ? 'weekend' : '' }} {{ $isHoliday ? 'bg-dark text-white' : '' }}"
                      title="{{ $day->format('l, F j, Y') }}{{ $isHoliday ? ' - ' . $publicHolidays[$day->format('Y-m-d')]->name : '' }}">
                    <div class="day-name">{{ substr($day->format('D'), 0, 2) }}</div>
                    <div class="day-num">{{ $day->format('j') }}</div>
                  </th>
                @endforeach
              </tr>
            </thead>
            <tbody>
              @forelse($availabilityData as $data)
                @php $employee = $data['employee']; @endphp
                <tr>
                  <td class="employee-col">
                    <div class="employee-name" title="{{ $employee->name }}{{ $employee->name_ar ? ' - ' . $employee->name_ar : '' }}">
                      {{ \Illuminate\Support\Str::limit($employee->name, 20) }}
                    </div>
                  </td>
                  @foreach($days as $day)
                    @php
                      $dayKey = $day->format('Y-m-d');
                      $status = $data['days'][$dayKey];
                    @endphp
                    <td class="{{ $status['type'] === 'weekend' ? 'bg-light' : '' }}">
                      @if($status['type'] !== 'available' && $status['type'] !== 'weekend')
                        <div class="day-cell {{ $status['class'] }}" title="{{ $status['label'] }}">
                          @if(!empty($status['icon']))
                            <i class="ti {{ $status['icon'] }}"></i>
                          @elseif($status['type'] === 'leave')
                            @if($status['status'] === 'approved')
                              <i class="ti ti-check"></i>
                            @elseif($status['status'] === 'pending')
                              <i class="ti ti-clock"></i>
                            @else
                              <i class="ti ti-x"></i>
                            @endif
                          @endif
                        </div>
                      @endif
                    </td>
                  @endforeach
                </tr>
              @empty
                <tr>
                  <td colspan="{{ count($days) + 1 }}" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                      <i class="ti ti-users-minus text-muted" style="font-size: 3rem;"></i>
                      <h6 class="mt-2">No employees found</h6>
                      <p class="text-muted">Add active employees to see their availability</p>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Monthly Summary Stats -->
    <div class="card mt-4">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ti ti-chart-pie me-2"></i>Monthly Summary
          <span class="badge bg-label-secondary ms-2">{{ $startDate->format('F Y') }}</span>
        </h6>
      </div>
      <div class="card-body">
        @php
          $totalLeaveApproved = 0;
          $totalLeavePending = 0;
          $totalLeaveRejected = 0;
          $totalWfh = 0;
          $totalHolidays = count($publicHolidays);

          foreach ($availabilityData as $data) {
            foreach ($data['days'] as $status) {
              if ($status['type'] === 'leave') {
                if ($status['status'] === 'approved') $totalLeaveApproved++;
                elseif ($status['status'] === 'pending') $totalLeavePending++;
                elseif ($status['status'] === 'rejected') $totalLeaveRejected++;
              } elseif ($status['type'] === 'wfh') {
                $totalWfh++;
              }
            }
          }
        @endphp
        <div class="row g-4">
          <div class="col-md-6 col-lg-3">
            <div class="d-flex align-items-start">
              <div class="avatar avatar-sm me-3">
                <span class="avatar-initial rounded bg-label-success">
                  <i class="ti ti-check ti-md"></i>
                </span>
              </div>
              <div>
                <h6 class="mb-0">Approved Leave</h6>
                <h4 class="mb-0 text-success">{{ $totalLeaveApproved }}</h4>
                <small class="text-muted">employee-days</small>
              </div>
            </div>
          </div>
          <div class="col-md-6 col-lg-3">
            <div class="d-flex align-items-start">
              <div class="avatar avatar-sm me-3">
                <span class="avatar-initial rounded bg-label-warning">
                  <i class="ti ti-clock ti-md"></i>
                </span>
              </div>
              <div>
                <h6 class="mb-0">Pending Leave</h6>
                <h4 class="mb-0 text-warning">{{ $totalLeavePending }}</h4>
                <small class="text-muted">employee-days</small>
              </div>
            </div>
          </div>
          <div class="col-md-6 col-lg-3">
            <div class="d-flex align-items-start">
              <div class="avatar avatar-sm me-3">
                <span class="avatar-initial rounded bg-label-info">
                  <i class="ti ti-home ti-md"></i>
                </span>
              </div>
              <div>
                <h6 class="mb-0">WFH Days</h6>
                <h4 class="mb-0 text-info">{{ $totalWfh }}</h4>
                <small class="text-muted">employee-days</small>
              </div>
            </div>
          </div>
          <div class="col-md-6 col-lg-3">
            <div class="d-flex align-items-start">
              <div class="avatar avatar-sm me-3">
                <span class="avatar-initial rounded bg-label-dark">
                  <i class="ti ti-calendar-event ti-md"></i>
                </span>
              </div>
              <div>
                <h6 class="mb-0">Public Holidays</h6>
                <h4 class="mb-0">{{ $totalHolidays }}</h4>
                <small class="text-muted">this month</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
