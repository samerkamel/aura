@extends('layouts/layoutMaster')

@section('title', 'Preview ZKTeco Import')

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Import Header Card -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <i class="ti tabler-fingerprint me-2 text-primary" style="font-size: 1.5rem;"></i>
          <div>
            <h5 class="mb-0">Preview ZKTeco Import</h5>
            <small class="text-muted">Review the data before importing</small>
          </div>
        </div>
        <a href="{{ route('attendance.import.zkteco') }}" class="btn btn-outline-secondary">
          <i class="ti tabler-arrow-left me-1"></i>Back to Upload
        </a>
      </div>
    </div>

    <!-- Existing Records Info -->
    @if($preview['latest_existing_record'])
    <div class="alert alert-info mb-4">
      <div class="d-flex align-items-start">
        <i class="ti tabler-database me-2 mt-1" style="font-size: 1.2rem;"></i>
        <div>
          <h6 class="mb-1">Incremental Import</h6>
          <p class="mb-0 small">
            Latest existing record: <strong>{{ \Carbon\Carbon::parse($preview['latest_existing_record'])->format('M d, Y h:i A') }}</strong>.
            Records on or before this timestamp will be skipped to avoid duplicates.
          </p>
        </div>
      </div>
    </div>
    @endif

    <!-- File Summary Card -->
    <div class="row mb-4">
      <div class="col-md-2">
        <div class="card">
          <div class="card-body text-center">
            <i class="ti tabler-file-text text-primary mb-2" style="font-size: 2rem;"></i>
            <h6 class="mb-1">File</h6>
            <p class="mb-0 text-muted small">{{ $filename }}</p>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card">
          <div class="card-body text-center">
            <i class="ti tabler-list-numbers text-info mb-2" style="font-size: 2rem;"></i>
            <h6 class="mb-1">Raw Punches</h6>
            <p class="mb-0 fw-bold">{{ number_format($preview['total_lines']) }}</p>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card {{ ($preview['skipped_old_records'] ?? 0) > 0 ? 'border-warning' : '' }}">
          <div class="card-body text-center">
            <i class="ti tabler-clock-off text-warning mb-2" style="font-size: 2rem;"></i>
            <h6 class="mb-1">Skipped (Old)</h6>
            <p class="mb-0 fw-bold text-warning">{{ number_format($preview['skipped_old_records'] ?? 0) }}</p>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card border-success">
          <div class="card-body text-center">
            <i class="ti tabler-calendar-plus text-success mb-2" style="font-size: 2rem;"></i>
            <h6 class="mb-1">New Work Days</h6>
            <p class="mb-0 fw-bold text-success">{{ number_format($preview['new_work_days'] ?? 0) }}</p>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card">
          <div class="card-body text-center">
            <i class="ti tabler-users text-secondary mb-2" style="font-size: 2rem;"></i>
            <h6 class="mb-1">Unique Users</h6>
            <p class="mb-0 fw-bold">{{ count($preview['unique_users']) }}</p>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card">
          <div class="card-body text-center">
            <i class="ti tabler-calendar text-secondary mb-2" style="font-size: 2rem;"></i>
            <h6 class="mb-1">New Date Range</h6>
            <p class="mb-0 small">
              @if($preview['new_date_range']['start'] ?? null)
                {{ \Carbon\Carbon::parse($preview['new_date_range']['start'])->format('M d') }}
                -
                {{ \Carbon\Carbon::parse($preview['new_date_range']['end'])->format('M d, Y') }}
              @else
                <span class="text-muted">No new records</span>
              @endif
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Business Logic Info -->
    <div class="alert alert-info mb-4">
      <div class="d-flex align-items-start">
        <i class="ti tabler-info-circle me-2 mt-1" style="font-size: 1.2rem;"></i>
        <div>
          <h6 class="mb-1">Import Logic</h6>
          <p class="mb-0 small">
            For each work day per employee: <strong>First punch = Check In</strong>, <strong>Last punch = Check Out</strong>.
            Work days extend until 4:00 AM (late night work counts as the same day).
          </p>
        </div>
      </div>
    </div>

    <!-- Sample Work Days Preview -->
    <div class="card mb-4">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ti tabler-eye me-2"></i>New Work Days Preview (Most Recent 10)
        </h6>
      </div>
      <div class="card-body">
        @if(count($preview['records']) > 0)
          <div class="table-responsive">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>Attendance ID</th>
                  <th>Employee</th>
                  <th>Work Day</th>
                  <th>Check In</th>
                  <th>Check Out</th>
                  <th>Punches</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach($preview['records'] as $record)
                  <tr>
                    <td>{{ $record['attendance_id'] }}</td>
                    <td>
                      @if($record['mapped'])
                        <span class="text-success">
                          <i class="ti tabler-check me-1"></i>{{ $record['employee_name'] }}
                        </span>
                      @else
                        <span class="text-danger">
                          <i class="ti tabler-x me-1"></i>Not Mapped
                        </span>
                      @endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($record['work_day'])->format('M d, Y') }}</td>
                    <td>
                      <span class="badge bg-success">
                        {{ \Carbon\Carbon::parse($record['sign_in'])->format('h:i A') }}
                      </span>
                    </td>
                    <td>
                      @if($record['sign_out'])
                        <span class="badge bg-warning">
                          {{ \Carbon\Carbon::parse($record['sign_out'])->format('h:i A') }}
                        </span>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                    <td>
                      <span class="badge bg-label-secondary">{{ $record['punch_count'] }}</span>
                    </td>
                    <td>
                      @if($record['mapped'])
                        <span class="badge bg-label-success">Will Import</span>
                      @else
                        <span class="badge bg-label-danger">Will Skip</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="alert alert-warning mb-0">
            <i class="ti tabler-alert-triangle me-2"></i>
            @if(($preview['skipped_old_records'] ?? 0) > 0)
              No new records found. All {{ number_format($preview['skipped_old_records']) }} records in the file are older than or equal to your latest existing record.
            @else
              No valid records found in the file.
            @endif
          </div>
        @endif
      </div>
    </div>

    <!-- Unmapped Users Warning -->
    @php
      $unmappedInPreview = collect($preview['records'])->where('mapped', false)->pluck('attendance_id')->unique()->values();
    @endphp

    @if($unmappedInPreview->count() > 0)
      <div class="alert alert-warning mb-4">
        <h6><i class="ti tabler-alert-triangle me-2"></i>Unmapped Attendance IDs Detected</h6>
        <p class="mb-2">The following attendance IDs do not match any employee in the system and their records will be skipped:</p>
        <p class="mb-0">
          <strong>{{ $unmappedInPreview->implode(', ') }}</strong>
        </p>
        <hr>
        <small>
          To import these records, please update the corresponding employees' <strong>Attendance ID</strong> field in their profile.
          <a href="{{ route('hr.employees.index') }}">Go to Employee Management</a>
        </small>
      </div>
    @endif

    <!-- Import Confirmation Form -->
    <div class="card">
      <div class="card-body">
        <form method="POST" action="{{ route('attendance.import.zkteco.store') }}">
          @csrf
          <input type="hidden" name="temp_path" value="{{ $tempPath }}">
          <input type="hidden" name="filename" value="{{ $filename }}">

          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="mb-0">
                <i class="ti tabler-info-circle me-1 text-info"></i>
                Ready to import <strong>{{ number_format($preview['new_work_days'] ?? 0) }}</strong> new work days
                from <strong>{{ number_format($preview['total_lines']) }}</strong> raw punches.
                @if(($preview['skipped_old_records'] ?? 0) > 0)
                  <span class="text-warning">({{ number_format($preview['skipped_old_records']) }} old punches will be skipped)</span>
                @endif
                <br>
                <small class="text-muted">Each work day creates up to 2 records (check-in + check-out). Unmapped users will be skipped.</small>
              </p>
            </div>
            <div class="d-flex gap-2">
              <a href="{{ route('attendance.import.zkteco') }}" class="btn btn-outline-secondary">
                <i class="ti tabler-x me-1"></i>Cancel
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="ti tabler-database-import me-1"></i>Confirm Import
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
