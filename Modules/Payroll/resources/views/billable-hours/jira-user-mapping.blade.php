@extends('layouts/layoutMaster')

@section('title', 'Jira User Mapping')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-md-12">
      <!-- Page Header -->
      <div class="card mb-4">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
              <a href="{{ route('payroll.billable-hours.index') }}" class="btn btn-icon btn-outline-secondary me-3">
                <i class="ti tabler-arrow-left"></i>
              </a>
              <div>
                <h5 class="mb-0">
                  <i class="ti tabler-link me-2"></i>Jira User Mapping
                </h5>
                <small class="text-muted">Link Jira users to employees for automatic worklog sync</small>
              </div>
            </div>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#manualSyncModal">
                <i class="ti tabler-refresh me-1"></i>Manual Sync
              </button>
              <a href="{{ route('payroll.settings.index') }}#jira-settings" class="btn btn-outline-secondary">
                <i class="ti tabler-settings me-1"></i>Jira Settings
              </a>
            </div>
          </div>
        </div>
      </div>

      @if (session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
          {{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif

      @if (session('error') || $error)
        <div class="alert alert-danger alert-dismissible" role="alert">
          {{ session('error') ?? $error }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif

      <!-- Period Filter -->
      <div class="card mb-4">
        <div class="card-header">
          <h6 class="mb-0"><i class="ti tabler-calendar me-2"></i>Period to Scan</h6>
        </div>
        <div class="card-body">
          <form action="{{ route('payroll.billable-hours.jira-user-mapping') }}" method="GET" class="row align-items-end">
            <div class="col-md-4 mb-3 mb-md-0">
              <label class="form-label">Select Period</label>
              <select class="form-select" name="period" onchange="this.form.submit()">
                <option value="this-week" {{ $period === 'this-week' ? 'selected' : '' }}>This Week</option>
                <option value="last-week" {{ $period === 'last-week' ? 'selected' : '' }}>Last Week</option>
                <option value="this-month" {{ $period === 'this-month' ? 'selected' : '' }}>This Month</option>
                <option value="last-month" {{ $period === 'last-month' ? 'selected' : '' }}>Last Month</option>
              </select>
            </div>
            <div class="col-md-8">
              <div class="alert alert-info mb-0 py-2">
                <i class="ti tabler-info-circle me-1"></i>
                Showing Jira users who logged worklogs in the selected period. Link them to employees to track their billable hours.
              </div>
            </div>
          </form>
        </div>
      </div>

      @if(empty($error))
        <!-- Mapping Summary -->
        <div class="row mb-4">
          <div class="col-md-4">
            <div class="card">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-lg me-3 bg-label-primary">
                    <span class="avatar-initial rounded">
                      <i class="ti tabler-users"></i>
                    </span>
                  </div>
                  <div>
                    <h3 class="mb-0">{{ count($jiraUsers) }}</h3>
                    <small class="text-muted">Jira Users Found</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-lg me-3 bg-label-success">
                    <span class="avatar-initial rounded">
                      <i class="ti tabler-link"></i>
                    </span>
                  </div>
                  <div>
                    <h3 class="mb-0">{{ collect($jiraUsers)->where('isMapped', true)->count() }}</h3>
                    <small class="text-muted">Mapped</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-lg me-3 bg-label-warning">
                    <span class="avatar-initial rounded">
                      <i class="ti tabler-unlink"></i>
                    </span>
                  </div>
                  <div>
                    <h3 class="mb-0">{{ collect($jiraUsers)->where('isMapped', false)->count() }}</h3>
                    <small class="text-muted">Unmapped</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Mapping Table -->
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="ti tabler-list me-2"></i>User Mappings</h6>
            @if(count($jiraUsers) > 0)
              <button type="submit" form="mappingForm" class="btn btn-primary">
                <i class="ti tabler-device-floppy me-1"></i>Save All Mappings
              </button>
            @endif
          </div>
          <div class="card-body">
            @if(count($jiraUsers) === 0)
              <div class="text-center py-5 text-muted">
                <i class="ti tabler-user-off" style="font-size: 3rem;"></i>
                <p class="mt-3">No Jira users found with worklogs in the selected period.</p>
                <p class="small">Try selecting a different period or check your Jira settings.</p>
              </div>
            @else
              <form id="mappingForm" action="{{ route('payroll.billable-hours.jira-user-mapping.save') }}" method="POST">
                @csrf
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead class="table-light">
                      <tr>
                        <th style="width: 50px;">Avatar</th>
                        <th>Jira User</th>
                        <th>Hours Logged</th>
                        <th>Status</th>
                        <th style="width: 300px;">Link to Employee</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($jiraUsers as $index => $user)
                        <tr class="{{ $user['isMapped'] ? '' : 'table-warning' }}">
                          <td>
                            <input type="hidden" name="mappings[{{ $index }}][jira_account_id]" value="{{ $user['accountId'] }}">
                            @if($user['avatarUrl'])
                              <img src="{{ $user['avatarUrl'] }}" alt="{{ $user['displayName'] }}" class="rounded-circle" width="40" height="40">
                            @else
                              <div class="avatar avatar-sm">
                                <span class="avatar-initial rounded-circle bg-label-primary">
                                  {{ substr($user['displayName'], 0, 1) }}
                                </span>
                              </div>
                            @endif
                          </td>
                          <td>
                            <h6 class="mb-0">{{ $user['displayName'] }}</h6>
                            @if($user['emailAddress'])
                              <small class="text-muted">{{ $user['emailAddress'] }}</small>
                            @else
                              <small class="text-muted">{{ \Illuminate\Support\Str::limit($user['accountId'], 20) }}</small>
                            @endif
                          </td>
                          <td>
                            <span class="badge bg-success">{{ number_format($user['totalHours'], 2) }} hrs</span>
                          </td>
                          <td>
                            @if($user['isMapped'])
                              <span class="badge bg-label-success">
                                <i class="ti tabler-check me-1"></i>Mapped
                              </span>
                            @else
                              <span class="badge bg-label-warning">
                                <i class="ti tabler-alert-triangle me-1"></i>Not Mapped
                              </span>
                            @endif
                          </td>
                          <td>
                            <select class="form-select form-select-sm" name="mappings[{{ $index }}][employee_id]">
                              <option value="">-- Select Employee --</option>
                              @foreach($employees as $employee)
                                <option value="{{ $employee->id }}"
                                  {{ $user['mappedEmployeeId'] == $employee->id ? 'selected' : '' }}
                                  {{ $employee->jira_account_id && $employee->jira_account_id !== $user['accountId'] ? 'disabled' : '' }}>
                                  {{ $employee->name }}
                                  @if($employee->jira_account_id && $employee->jira_account_id !== $user['accountId'])
                                    (linked to another Jira user)
                                  @endif
                                </option>
                              @endforeach
                            </select>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
                <div class="mt-4 d-flex justify-content-end">
                  <button type="submit" class="btn btn-primary">
                    <i class="ti tabler-device-floppy me-1"></i>Save All Mappings
                  </button>
                </div>
              </form>
            @endif
          </div>
        </div>
      @endif
    </div>
  </div>
</div>

<!-- Manual Sync Modal -->
<div class="modal fade" id="manualSyncModal" tabindex="-1" aria-labelledby="manualSyncModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="manualSyncModalLabel">
          <i class="ti tabler-refresh me-2"></i>Manual Jira Sync
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
@endsection

@section('page-script')
<script>
  document.getElementById('syncPeriod').addEventListener('change', function() {
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
</script>
@endsection
