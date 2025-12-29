@extends('layouts/layoutMaster')

@section('title', 'Manage Billable Hours')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="ti tabler-clock me-2"></i>Manage Billable Hours
          </h5>
          <small class="text-muted">
            Period: {{ $periodStart->format('M j') }} - {{ $periodEnd->format('M j, Y') }}
          </small>
        </div>
        <div class="card-body">
          @if (session('success'))
            <div class="alert alert-success alert-dismissible" role="alert">
              {{ session('success') }}
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @endif

          @if (session('error'))
            <div class="alert alert-danger alert-dismissible" role="alert">
              {{ session('error') }}
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @endif

          @if ($errors->any())
            <div class="alert alert-danger">
              <h6><i class="ti tabler-alert-circle me-2"></i>Validation Errors</h6>
              <ul class="mb-0">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <!-- Jira Sync Section -->
          @if(config('services.jira.sync_enabled'))
          <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h6 class="mb-0">
                <i class="ti tabler-brand-jira me-2"></i>Jira Integration
              </h6>
              @if(isset($lastSyncLog) && $lastSyncLog)
                <small class="text-muted">
                  Last sync: {{ $lastSyncLog->started_at->format('M d, Y H:i') }}
                  <span class="badge badge-sm 
                    @if($lastSyncLog->status === 'completed') bg-success
                    @elseif($lastSyncLog->status === 'failed') bg-danger
                    @else bg-warning @endif">
                    {{ ucfirst($lastSyncLog->status) }}
                  </span>
                </small>
              @endif
            </div>
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col-md-8">
                  <p class="mb-1">Automatically sync billable hours from Jira worklogs</p>
                  <small class="text-muted">
                    Syncs hours for employees with configured Jira account IDs
                  </small>
                </div>
                <div class="col-md-4 text-end">
                  <button type="button" class="btn btn-outline-primary btn-sm" onclick="syncJiraHours()">
                    <i class="ti tabler-refresh me-1"></i>Sync from Jira
                  </button>
                </div>
              </div>
              
              @if(isset($lastSyncLog) && $lastSyncLog && $lastSyncLog->status === 'failed')
                <div class="alert alert-warning mt-3 mb-0">
                  <small><strong>Last sync failed:</strong> 
                    {{ isset($lastSyncLog->error_details['error']) ? $lastSyncLog->error_details['error'] : 'Unknown error' }}
                  </small>
                </div>
              @endif
            </div>
          </div>
          @endif

          <!-- Jira Integration Section -->
          <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h6 class="mb-0">
                <i class="ti tabler-brand-jira me-2"></i>Jira Integration
              </h6>
              <div class="btn-group">
                <a href="{{ route('payroll.billable-hours.jira-worklogs') }}" class="btn btn-sm btn-outline-primary">
                  <i class="ti tabler-list me-1"></i>View Worklogs
                </a>
                <a href="{{ route('payroll.billable-hours.jira-user-mapping') }}" class="btn btn-sm btn-outline-secondary">
                  <i class="ti tabler-link me-1"></i>User Mapping
                </a>
              </div>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6 border-end">
                  <h6 class="mb-2"><i class="ti tabler-api me-1"></i>API Sync</h6>
                  <p class="small text-muted mb-2">
                    Sync worklogs directly from Jira API for mapped employees
                  </p>
                  <div class="d-flex gap-2">
                    <a href="{{ route('payroll.billable-hours.jira-user-mapping') }}" class="btn btn-outline-primary btn-sm">
                      <i class="ti tabler-link me-1"></i>Map Users
                    </a>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#manualSyncModal">
                      <i class="ti tabler-refresh me-1"></i>Sync Now
                    </button>
                  </div>
                </div>
                <div class="col-md-6">
                  <h6 class="mb-2"><i class="ti tabler-file-upload me-1"></i>CSV Import</h6>
                  <p class="small text-muted mb-2">
                    Upload CSV exports from Jira containing worklog data
                  </p>
                  <a href="{{ route('payroll.billable-hours.import-jira-worklogs') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ti tabler-upload me-1"></i>Import CSV
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Manual Sync Modal -->
          <div class="modal fade" id="manualSyncModal" tabindex="-1" aria-labelledby="manualSyncModalLabel" aria-hidden="true">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="manualSyncModalLabel">
                    <i class="ti tabler-refresh me-2"></i>Sync from Jira API
                  </h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <p class="text-muted">Sync billable hours from Jira worklogs for mapped employees.</p>

                  <div class="mb-3">
                    <label class="form-label">Period</label>
                    <select class="form-select" id="syncPeriod">
                      <option value="this-week">This Week</option>
                      <option value="last-week">Last Week</option>
                      <option value="this-month" selected>This Month</option>
                      <option value="last-month">Last Month</option>
                      <option value="custom">Custom Range</option>
                    </select>
                  </div>

                  <div id="customDateRange" class="d-none">
                    <div class="row">
                      <div class="col-6">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="syncStartDate">
                      </div>
                      <div class="col-6">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" id="syncEndDate">
                      </div>
                    </div>
                  </div>

                  <div id="syncResult" class="mt-3 d-none">
                    <div class="alert mb-0" id="syncResultAlert"></div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button type="button" class="btn btn-primary" id="startSyncBtn" onclick="startManualSync()">
                    <i class="ti tabler-refresh me-1"></i>Start Sync
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- CSV Import Section -->
          <div class="card mb-4">
            <div class="card-header">
              <h6 class="mb-0">
                <i class="ti tabler-file-upload me-2"></i>CSV Import
              </h6>
            </div>
            <div class="card-body">
              <div class="alert alert-info mb-3">
                <h6><i class="ti tabler-info-circle me-2"></i>CSV Format Requirements</h6>
                <p class="mb-2">Your CSV file must contain the following columns:</p>
                <ul class="mb-2">
                  @foreach($expectedHeaders as $header)
                    <li><strong>{{ $header }}</strong></li>
                  @endforeach
                </ul>
                <small class="text-muted">
                  <strong>Example:</strong> EmployeeID: 123, BillableHours: 40.5
                </small>
              </div>

              <form action="{{ route('payroll.billable-hours.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                  <div class="col-md-8">
                    <div class="mb-3">
                      <label for="csv_file" class="form-label">CSV File</label>
                      <input type="file" class="form-control @error('csv_file') is-invalid @enderror"
                             id="csv_file" name="csv_file" accept=".csv">
                      @error('csv_file')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                  <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                      <i class="ti tabler-upload me-1"></i>Import CSV
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <!-- Billable Hours Summary -->
          <div class="card">
            <div class="card-header">
              <h6 class="mb-0">
                <i class="ti tabler-clock-hour-4 me-2"></i>Billable Hours Summary
              </h6>
            </div>
            <div class="card-body">
              <form action="{{ route('payroll.billable-hours.store') }}" method="POST">
                @csrf
                <div class="table-responsive">
                  <table class="table table-bordered">
                    <thead class="table-light">
                      <tr>
                        <th>Employee Name</th>
                        <th>Employee ID</th>
                        <th>Jira Hours</th>
                        <th>Manual Hours</th>
                        <th>Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse($employees as $employee)
                        <tr>
                          <td>
                            <div class="d-flex align-items-center">
                              <div class="avatar avatar-sm me-3">
                                <span class="avatar-initial rounded-circle bg-label-primary">
                                  {{ substr($employee->name, 0, 1) }}
                                </span>
                              </div>
                              <div>
                                <h6 class="mb-0">{{ $employee->name }}</h6>
                                <small class="text-muted">{{ $employee->position ?? 'N/A' }}</small>
                              </div>
                            </div>
                          </td>
                          <td>{{ $employee->id }}</td>
                          <td>
                            @if($employee->jira_hours > 0)
                              <span class="badge bg-label-primary">
                                <i class="ti tabler-brand-jira me-1"></i>{{ number_format($employee->jira_hours, 2) }} hrs
                              </span>
                            @else
                              <span class="text-muted">-</span>
                            @endif
                          </td>
                          <td>
                            <input type="number"
                                   class="form-control form-control-sm @error('hours.' . $employee->id) is-invalid @enderror"
                                   name="hours[{{ $employee->id }}]"
                                   value="{{ old('hours.' . $employee->id, $employee->manual_hours) }}"
                                   step="0.01"
                                   min="0"
                                   max="999.99"
                                   placeholder="0.00"
                                   style="width: 100px;">
                            @error('hours.' . $employee->id)
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </td>
                          <td>
                            <span class="badge bg-success">
                              {{ number_format($employee->jira_hours + $employee->manual_hours, 2) }} hrs
                            </span>
                          </td>
                        </tr>
                      @empty
                        <tr>
                          <td colspan="5" class="text-center text-muted">
                            <i class="ti tabler-users-off me-2"></i>No active employees found.
                          </td>
                        </tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>

                @if($employees->isNotEmpty())
                  <div class="d-flex justify-content-between mt-3">
                    <button type="button" class="btn btn-outline-secondary" onclick="clearAllHours()">
                      <i class="ti tabler-x me-1"></i>Clear All
                    </button>
                    <button type="submit" class="btn btn-primary">
                      <i class="ti tabler-device-floppy me-1"></i>Save All Changes
                    </button>
                  </div>
                @endif
              </form>
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
function clearAllHours() {
  if (confirm('Are you sure you want to clear all billable hours? This will set all values to 0.')) {
    document.querySelectorAll('input[name^="hours["]').forEach(function(input) {
      input.value = '0';
    });
  }
}

// Show/hide custom date range based on period selection
document.getElementById('syncPeriod')?.addEventListener('change', function() {
  const customRange = document.getElementById('customDateRange');
  if (this.value === 'custom') {
    customRange.classList.remove('d-none');
  } else {
    customRange.classList.add('d-none');
  }
});

function startManualSync() {
  const btn = document.getElementById('startSyncBtn');
  const resultDiv = document.getElementById('syncResult');
  const alertDiv = document.getElementById('syncResultAlert');

  const period = document.getElementById('syncPeriod').value;
  const startDate = document.getElementById('syncStartDate').value;
  const endDate = document.getElementById('syncEndDate').value;

  // Validate custom range
  if (period === 'custom' && (!startDate || !endDate)) {
    resultDiv.classList.remove('d-none');
    alertDiv.className = 'alert alert-danger mb-0';
    alertDiv.innerHTML = '<i class="ti tabler-alert-circle me-1"></i>Please select both start and end dates.';
    return;
  }

  // Show loading
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Syncing...';
  resultDiv.classList.add('d-none');

  // Build request data
  const data = { period };
  if (period === 'custom') {
    data.start_date = startDate;
    data.end_date = endDate;
  }

  fetch('{{ route("payroll.billable-hours.manual-jira-sync") }}', {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify(data)
  })
  .then(response => response.json())
  .then(data => {
    resultDiv.classList.remove('d-none');
    if (data.success) {
      alertDiv.className = 'alert alert-success mb-0';
      alertDiv.innerHTML = '<i class="ti tabler-check me-1"></i>' + data.message;
      // Reload page after a short delay
      setTimeout(() => location.reload(), 2000);
    } else {
      alertDiv.className = 'alert alert-danger mb-0';
      alertDiv.innerHTML = '<i class="ti tabler-alert-circle me-1"></i>' + data.message;
    }
  })
  .catch(error => {
    resultDiv.classList.remove('d-none');
    alertDiv.className = 'alert alert-danger mb-0';
    alertDiv.innerHTML = '<i class="ti tabler-alert-circle me-1"></i>Network error: ' + error.message;
  })
  .finally(() => {
    btn.disabled = false;
    btn.innerHTML = '<i class="ti tabler-refresh me-1"></i>Start Sync';
  });
}

// File upload preview
document.getElementById('csv_file')?.addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (file) {
    const fileSize = (file.size / 1024 / 1024).toFixed(2);
    console.log(`Selected file: ${file.name} (${fileSize} MB)`);
  }
});
</script>
@endsection
