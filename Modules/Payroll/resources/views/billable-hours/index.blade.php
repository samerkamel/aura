@extends('layouts/layoutMaster')

@section('title', 'Manage Billable Hours')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="ti ti-clock me-2"></i>Manage Billable Hours
          </h5>
          <small class="text-muted">
            Period: {{ $currentPeriod->format('F Y') }}
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
              <h6><i class="ti ti-alert-circle me-2"></i>Validation Errors</h6>
              <ul class="mb-0">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <!-- CSV Import Section -->
          <div class="card mb-4">
            <div class="card-header">
              <h6 class="mb-0">
                <i class="ti ti-file-upload me-2"></i>CSV Import
              </h6>
            </div>
            <div class="card-body">
              <div class="alert alert-info mb-3">
                <h6><i class="ti ti-info-circle me-2"></i>CSV Format Requirements</h6>
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
                      <i class="ti ti-upload me-1"></i>Import CSV
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <!-- Manual Entry Section -->
          <div class="card">
            <div class="card-header">
              <h6 class="mb-0">
                <i class="ti ti-edit me-2"></i>Manual Entry
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
                        <th>Current Billable Hours</th>
                        <th>New Hours</th>
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
                            <span class="badge bg-label-info">
                              {{ number_format($employee->current_billable_hours, 2) }} hrs
                            </span>
                          </td>
                          <td>
                            <input type="number"
                                   class="form-control @error('hours.' . $employee->id) is-invalid @enderror"
                                   name="hours[{{ $employee->id }}]"
                                   value="{{ old('hours.' . $employee->id, $employee->current_billable_hours) }}"
                                   step="0.01"
                                   min="0"
                                   max="999.99"
                                   placeholder="0.00">
                            @error('hours.' . $employee->id)
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </td>
                        </tr>
                      @empty
                        <tr>
                          <td colspan="4" class="text-center text-muted">
                            <i class="ti ti-users-off me-2"></i>No active employees found.
                          </td>
                        </tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>

                @if($employees->isNotEmpty())
                  <div class="d-flex justify-content-between mt-3">
                    <button type="button" class="btn btn-outline-secondary" onclick="clearAllHours()">
                      <i class="ti ti-x me-1"></i>Clear All
                    </button>
                    <button type="submit" class="btn btn-primary">
                      <i class="ti ti-device-floppy me-1"></i>Save All Changes
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
