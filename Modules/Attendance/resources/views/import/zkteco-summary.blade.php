@extends('layouts/layoutMaster')

@section('title', 'ZKTeco Import Results')

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Import Header Card -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <i class="ti tabler-fingerprint me-2 text-primary" style="font-size: 1.5rem;"></i>
          <div>
            <h5 class="mb-0">ZKTeco Import Results</h5>
            <small class="text-muted">{{ $filename }}</small>
          </div>
        </div>
        <div class="d-flex gap-2">
          <a href="{{ route('attendance.import.zkteco') }}" class="btn btn-outline-primary">
            <i class="ti tabler-upload me-1"></i>Import Another File
          </a>
          <a href="{{ route('attendance.index') }}" class="btn btn-secondary">
            <i class="ti tabler-list me-1"></i>View Attendance
          </a>
        </div>
      </div>
    </div>

    <!-- Results Summary Cards -->
    <div class="row mb-4">
      <div class="col-md-2 col-sm-4 col-6 mb-3">
        <div class="card h-100">
          <div class="card-body text-center">
            <i class="ti tabler-list-numbers text-info mb-2" style="font-size: 2rem;"></i>
            <h6 class="mb-1">Raw Punches</h6>
            <p class="mb-0 fw-bold fs-5">{{ number_format($results['total_records']) }}</p>
          </div>
        </div>
      </div>
      @if(($results['skipped_old'] ?? 0) > 0)
      <div class="col-md-2 col-sm-4 col-6 mb-3">
        <div class="card h-100 border-warning">
          <div class="card-body text-center">
            <i class="ti tabler-clock-off text-warning mb-2" style="font-size: 2rem;"></i>
            <h6 class="mb-1">Skipped (Old)</h6>
            <p class="mb-0 fw-bold fs-5 text-warning">{{ number_format($results['skipped_old']) }}</p>
          </div>
        </div>
      </div>
      @endif
      <div class="col-md-2 col-sm-4 col-6 mb-3">
        <div class="card h-100">
          <div class="card-body text-center">
            <i class="ti tabler-calendar-event text-primary mb-2" style="font-size: 2rem;"></i>
            <h6 class="mb-1">Work Days</h6>
            <p class="mb-0 fw-bold fs-5">{{ number_format($results['work_days_processed'] ?? 0) }}</p>
          </div>
        </div>
      </div>
      <div class="col-md-2 col-sm-4 col-6 mb-3">
        <div class="card h-100">
          <div class="card-body text-center">
            <i class="ti tabler-plus text-success mb-2" style="font-size: 2rem;"></i>
            <h6 class="mb-1">New Records</h6>
            <p class="mb-0 fw-bold fs-5 text-success">{{ number_format($results['imported']) }}</p>
          </div>
        </div>
      </div>
      <div class="col-md-2 col-sm-4 col-6 mb-3">
        <div class="card h-100">
          <div class="card-body text-center">
            <i class="ti tabler-refresh text-info mb-2" style="font-size: 2rem;"></i>
            <h6 class="mb-1">Updated</h6>
            <p class="mb-0 fw-bold fs-5 text-info">{{ number_format($results['updated'] ?? 0) }}</p>
          </div>
        </div>
      </div>
      <div class="col-md-2 col-sm-4 col-6 mb-3">
        <div class="card h-100">
          <div class="card-body text-center">
            <i class="ti tabler-copy text-warning mb-2" style="font-size: 2rem;"></i>
            <h6 class="mb-1">Duplicates</h6>
            <p class="mb-0 fw-bold fs-5 text-warning">{{ number_format($results['duplicates']) }}</p>
          </div>
        </div>
      </div>
      <div class="col-md-2 col-sm-4 col-6 mb-3">
        <div class="card h-100">
          <div class="card-body text-center">
            <i class="ti tabler-player-skip-forward text-secondary mb-2" style="font-size: 2rem;"></i>
            <h6 class="mb-1">Skipped</h6>
            <p class="mb-0 fw-bold fs-5 text-secondary">{{ number_format($results['skipped']) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Incremental Import Info -->
    @if(($results['skipped_old'] ?? 0) > 0 && isset($results['latest_existing_record']))
    <div class="alert alert-info mb-4">
      <div class="d-flex align-items-start">
        <i class="ti tabler-database me-2 mt-1" style="font-size: 1.2rem;"></i>
        <div>
          <h6 class="mb-1">Incremental Import</h6>
          <p class="mb-0 small">
            {{ number_format($results['skipped_old']) }} records were skipped because they are older than your latest existing record
            (<strong>{{ \Carbon\Carbon::parse($results['latest_existing_record'])->format('M d, Y h:i A') }}</strong>).
          </p>
        </div>
      </div>
    </div>
    @endif

    <!-- Success/Warning Message -->
    @if($results['imported'] > 0 || ($results['updated'] ?? 0) > 0)
      <div class="alert alert-success mb-4">
        <div class="d-flex align-items-center">
          <i class="ti tabler-circle-check me-2" style="font-size: 1.5rem;"></i>
          <div>
            <h6 class="mb-0">Import Completed Successfully</h6>
            <p class="mb-0">
              @if($results['imported'] > 0 && ($results['updated'] ?? 0) > 0)
                {{ number_format($results['imported']) }} new records created, {{ number_format($results['updated']) }} existing records updated.
              @elseif($results['imported'] > 0)
                {{ number_format($results['imported']) }} new attendance records have been imported into the system.
              @else
                {{ number_format($results['updated']) }} existing attendance records have been updated.
              @endif
            </p>
          </div>
        </div>
      </div>
    @elseif($results['duplicates'] > 0)
      <div class="alert alert-info mb-4">
        <div class="d-flex align-items-center">
          <i class="ti tabler-info-circle me-2" style="font-size: 1.5rem;"></i>
          <div>
            <h6 class="mb-0">File Already Imported</h6>
            <p class="mb-0">All {{ number_format($results['duplicates']) }} records in this file already exist in the system. No changes were made.</p>
          </div>
        </div>
      </div>
    @else
      <div class="alert alert-warning mb-4">
        <div class="d-flex align-items-center">
          <i class="ti tabler-alert-triangle me-2" style="font-size: 1.5rem;"></i>
          <div>
            <h6 class="mb-0">No Records Imported</h6>
            <p class="mb-0">
              @if(($results['skipped_old'] ?? 0) > 0 && $results['skipped'] == 0)
                All {{ number_format($results['skipped_old']) }} records in the file are older than your latest existing record. No new data to import.
              @elseif(($results['skipped_old'] ?? 0) > 0)
                No new attendance records were imported. {{ number_format($results['skipped_old']) }} records were skipped (already imported), and remaining records had no matching employees.
              @else
                No attendance records were imported. This may be because no employees matched the attendance IDs in the file.
              @endif
            </p>
          </div>
        </div>
      </div>
    @endif

    <!-- Unmapped Users -->
    @if(count($results['unmapped_users']) > 0)
      <div class="card mb-4">
        <div class="card-header bg-warning bg-opacity-10">
          <h6 class="mb-0 text-warning">
            <i class="ti tabler-users me-2"></i>Unmapped Attendance IDs ({{ count($results['unmapped_users']) }})
          </h6>
        </div>
        <div class="card-body">
          <p class="mb-2">The following attendance IDs were found in the file but do not match any employee:</p>
          <div class="d-flex flex-wrap gap-2 mb-3">
            @foreach($results['unmapped_users'] as $userId)
              <span class="badge bg-label-warning">{{ $userId }}</span>
            @endforeach
          </div>
          <div class="alert alert-info mb-0">
            <i class="ti tabler-info-circle me-2"></i>
            To import records for these users, update the corresponding employees' <strong>Attendance ID</strong> field in their profile and re-import the file.
            <a href="{{ route('hr.employees.index') }}" class="alert-link">Go to Employee Management</a>
          </div>
        </div>
      </div>
    @endif

    <!-- Errors -->
    @if(count($results['errors']) > 0)
      <div class="card mb-4">
        <div class="card-header bg-danger bg-opacity-10">
          <h6 class="mb-0 text-danger">
            <i class="ti tabler-alert-circle me-2"></i>Errors ({{ count($results['errors']) }})
          </h6>
        </div>
        <div class="card-body">
          <ul class="mb-0">
            @foreach($results['errors'] as $error)
              <li class="text-danger">{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      </div>
    @endif

    <!-- Action Buttons -->
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <p class="mb-0 text-muted">
              <i class="ti tabler-clock me-1"></i>
              Import completed at {{ now()->format('M d, Y h:i A') }}
            </p>
          </div>
          <div class="d-flex gap-2">
            <a href="{{ route('attendance.import.zkteco') }}" class="btn btn-outline-primary">
              <i class="ti tabler-upload me-1"></i>Import Another File
            </a>
            <a href="{{ route('attendance.index') }}" class="btn btn-primary">
              <i class="ti tabler-list me-1"></i>View Attendance Records
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
