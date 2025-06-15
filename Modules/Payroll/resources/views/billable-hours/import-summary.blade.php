@extends('layouts/layoutMaster')

@section('title', 'Billable Hours Import Summary')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="ti ti-file-check me-2"></i>Import Summary
          </h5>
          <small class="text-muted">
            File: {{ $filename }}
          </small>
        </div>
        <div class="card-body">
          <!-- Import Statistics -->
          <div class="row mb-4">
            <div class="col-md-3">
              <div class="card bg-label-primary">
                <div class="card-body text-center">
                  <i class="ti ti-file-text display-6 mb-2"></i>
                  <h4 class="mb-1">{{ $results['total_rows'] }}</h4>
                  <p class="mb-0">Total Rows</p>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card bg-label-success">
                <div class="card-body text-center">
                  <i class="ti ti-check display-6 mb-2"></i>
                  <h4 class="mb-1">{{ $results['successful_imports'] }}</h4>
                  <p class="mb-0">Successful</p>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card bg-label-danger">
                <div class="card-body text-center">
                  <i class="ti ti-x display-6 mb-2"></i>
                  <h4 class="mb-1">{{ count($results['failed_rows']) }}</h4>
                  <p class="mb-0">Failed</p>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card bg-label-warning">
                <div class="card-body text-center">
                  <i class="ti ti-alert-triangle display-6 mb-2"></i>
                  <h4 class="mb-1">{{ count($results['errors']) }}</h4>
                  <p class="mb-0">Errors</p>
                </div>
              </div>
            </div>
          </div>

          <!-- General Errors -->
          @if(!empty($results['errors']))
            <div class="alert alert-danger">
              <h6><i class="ti ti-alert-circle me-2"></i>General Import Errors</h6>
              <ul class="mb-0">
                @foreach($results['errors'] as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <!-- Success Message -->
          @if($results['successful_imports'] > 0)
            <div class="alert alert-success">
              <i class="ti ti-check-circle me-2"></i>
              Successfully imported billable hours for {{ $results['successful_imports'] }} employee(s).
            </div>
          @endif

          <!-- Failed Rows Details -->
          @if(!empty($results['failed_rows']))
            <div class="card mt-4">
              <div class="card-header">
                <h6 class="mb-0">
                  <i class="ti ti-alert-triangle me-2"></i>Failed Rows Details
                </h6>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-bordered">
                    <thead class="table-light">
                      <tr>
                        <th>Row #</th>
                        <th>Employee ID</th>
                        <th>Billable Hours</th>
                        <th>Error(s)</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($results['failed_rows'] as $failedRow)
                        <tr>
                          <td>{{ $failedRow['row_number'] }}</td>
                          <td>{{ $failedRow['data']['EmployeeID'] ?? 'N/A' }}</td>
                          <td>{{ $failedRow['data']['BillableHours'] ?? 'N/A' }}</td>
                          <td>
                            <ul class="list-unstyled mb-0">
                              @foreach($failedRow['errors'] as $error)
                                <li class="text-danger">
                                  <i class="ti ti-x me-1"></i>{{ $error }}
                                </li>
                              @endforeach
                            </ul>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          @endif

          <!-- Action Buttons -->
          <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('payroll.billable-hours.index') }}" class="btn btn-outline-secondary">
              <i class="ti ti-arrow-left me-1"></i>Back to Billable Hours
            </a>

            @if($results['successful_imports'] > 0 && empty($results['failed_rows']))
              <a href="{{ route('payroll.billable-hours.index') }}" class="btn btn-success">
                <i class="ti ti-check me-1"></i>Complete
              </a>
            @elseif(!empty($results['failed_rows']))
              <a href="{{ route('payroll.billable-hours.index') }}" class="btn btn-warning">
                <i class="ti ti-edit me-1"></i>Fix Failed Entries Manually
              </a>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
