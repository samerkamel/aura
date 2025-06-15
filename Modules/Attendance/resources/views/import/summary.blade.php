@extends('layouts/layoutMaster')

@section('title', 'Import Results')

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Import Results Header Card -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <i class="ti ti-clipboard-check me-2 text-primary" style="font-size: 1.5rem;"></i>
          <div>
            <h5 class="mb-0">Attendance Import Results</h5>
            <small class="text-muted">File: {{ $filename }}</small>
          </div>
        </div>
        <div>
          <a href="{{ route('attendance.import.create') }}" class="btn btn-primary me-2">
            <i class="ti ti-upload me-1"></i>Import Another File
          </a>
          <a href="{{ route('attendance.index') }}" class="btn btn-secondary">
            <i class="ti ti-arrow-left me-1"></i>Back to Attendance
          </a>
        </div>
      </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card text-center">
          <div class="card-body">
            <div class="avatar mx-auto mb-2">
              <span class="avatar-initial rounded-circle bg-label-info">
                <i class="ti ti-file-text ti-md"></i>
              </span>
            </div>
            <span class="h4 d-block mb-1">{{ number_format($results['total_rows']) }}</span>
            <small class="text-muted">Total Rows</small>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center">
          <div class="card-body">
            <div class="avatar mx-auto mb-2">
              <span class="avatar-initial rounded-circle bg-label-success">
                <i class="ti ti-check ti-md"></i>
              </span>
            </div>
            <span class="h4 d-block mb-1 text-success">{{ number_format($results['successful_imports']) }}</span>
            <small class="text-muted">Successful Imports</small>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center">
          <div class="card-body">
            <div class="avatar mx-auto mb-2">
              <span class="avatar-initial rounded-circle bg-label-danger">
                <i class="ti ti-x ti-md"></i>
              </span>
            </div>
            <span class="h4 d-block mb-1 text-danger">{{ number_format(count($results['failed_rows'])) }}</span>
            <small class="text-muted">Failed Rows</small>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center">
          <div class="card-body">
            <div class="avatar mx-auto mb-2">
              <span class="avatar-initial rounded-circle bg-label-warning">
                <i class="ti ti-percentage ti-md"></i>
              </span>
            </div>
            @php
              $successRate = $results['total_rows'] > 0 ? round(($results['successful_imports'] / $results['total_rows']) * 100, 1) : 0;
            @endphp
            <span class="h4 d-block mb-1 text-warning">{{ $successRate }}%</span>
            <small class="text-muted">Success Rate</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Global Errors (if any) -->
    @if(!empty($results['errors']))
      <div class="row mb-4">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h6 class="mb-0 text-danger">
                <i class="ti ti-alert-circle me-2"></i>Import Errors
              </h6>
            </div>
            <div class="card-body">
              <div class="alert alert-danger">
                <ul class="mb-0">
                  @foreach($results['errors'] as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    @endif

    <!-- Success Message -->
    @if($results['successful_imports'] > 0 && empty($results['errors']))
      <div class="row mb-4">
        <div class="col-12">
          <div class="alert alert-success">
            <h6><i class="ti ti-check-circle me-2"></i>Import Completed Successfully!</h6>
            <p class="mb-0">
              {{ number_format($results['successful_imports']) }} attendance records have been successfully imported into the system.
            </p>
          </div>
        </div>
      </div>
    @endif

    <!-- Failed Rows Details -->
    @if(!empty($results['failed_rows']))
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h6 class="mb-0 text-danger">
                <i class="ti ti-alert-triangle me-2"></i>Failed Rows Details
              </h6>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered">
                  <thead>
                    <tr>
                      <th>Row #</th>
                      <th>Employee ID</th>
                      <th>DateTime</th>
                      <th>LogType</th>
                      <th>Errors</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($results['failed_rows'] as $failedRow)
                      <tr>
                        <td>
                          <span class="badge bg-danger">{{ $failedRow['row_number'] }}</span>
                        </td>
                        <td>{{ $failedRow['data']['EmployeeID'] ?? 'N/A' }}</td>
                        <td>{{ $failedRow['data']['DateTime'] ?? 'N/A' }}</td>
                        <td>{{ $failedRow['data']['LogType'] ?? 'N/A' }}</td>
                        <td>
                          <ul class="list-unstyled mb-0 text-danger">
                            @foreach($failedRow['errors'] as $error)
                              <li><small>• {{ $error }}</small></li>
                            @endforeach
                          </ul>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>

              @if(count($results['failed_rows']) > 10)
                <div class="alert alert-info mt-3">
                  <small>
                    <i class="ti ti-info-circle me-1"></i>
                    Showing first {{ count($results['failed_rows']) }} failed rows.
                    Consider fixing the most common errors and re-importing the corrected data.
                  </small>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>
    @endif
  </div>
</div>

@section('page-script')
<script>
// Auto-refresh success rate animation
document.addEventListener('DOMContentLoaded', function() {
    // Add animation to statistics cards
    const cards = document.querySelectorAll('.card .h4');
    cards.forEach(function(card, index) {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';

        setTimeout(function() {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>
@endsection
@endsection
