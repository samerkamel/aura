@extends('layouts/layoutMaster')

@section('title', 'Import Attendance')

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Import Header Card -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <i class="ti ti-upload me-2 text-primary" style="font-size: 1.5rem;"></i>
          <div>
            <h5 class="mb-0">Import Attendance from CSV</h5>
            <small class="text-muted">Upload a CSV file containing employee attendance logs</small>
          </div>
        </div>
        <div>
          <a href="{{ route('attendance.index') }}" class="btn btn-secondary">
            <i class="ti ti-arrow-left me-1"></i>Back to Attendance
          </a>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Import Form -->
      <div class="col-md-8 mx-auto">
        <div class="card">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="ti ti-file-upload me-2"></i>CSV File Upload
            </h6>
          </div>
          <div class="card-body">
            <!-- CSV Format Information -->
            <div class="alert alert-info mb-4">
              <h6><i class="ti ti-info-circle me-2"></i>CSV Format Requirements</h6>
              <p class="mb-2">Your CSV file must contain the following columns:</p>
              <ul class="mb-2">
                @foreach($expectedHeaders as $header)
                  <li><strong>{{ $header }}</strong></li>
                @endforeach
              </ul>
              <p class="mb-2"><strong>Valid LogType values:</strong> {{ implode(', ', $validLogTypes) }}</p>
              <small class="text-muted">
                <strong>Example:</strong> EmployeeID: 123, DateTime: 2025-06-14 09:00:00, LogType: sign_in
              </small>
            </div>

            @if ($errors->any())
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h6><i class="ti ti-alert-circle me-2"></i>Validation Errors</h6>
                <ul class="mb-0">
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            @endif

            <form method="POST" action="{{ route('attendance.import.store') }}" enctype="multipart/form-data">
              @csrf

              <!-- File Upload -->
              <div class="mb-4">
                <label for="csv_file" class="form-label">
                  CSV File <span class="text-danger">*</span>
                </label>
                <input type="file"
                       class="form-control @error('csv_file') is-invalid @enderror"
                       id="csv_file"
                       name="csv_file"
                       accept=".csv"
                       required>
                <div class="form-text">
                  <i class="ti ti-file-text me-1"></i>
                  Only CSV files are accepted. Maximum file size: 10MB
                </div>
                @error('csv_file')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Action Buttons -->
              <div class="d-flex justify-content-between">
                <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary">
                  <i class="ti ti-arrow-left me-1"></i>Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                  <i class="ti ti-upload me-1"></i>Import CSV
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@section('page-script')
<script>
// File upload preview and validation
document.getElementById('csv_file').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const fileSize = (file.size / 1024 / 1024).toFixed(2); // Convert to MB
        const fileName = file.name;

        // Update form text to show selected file info
        const formText = e.target.parentNode.querySelector('.form-text');
        formText.innerHTML = `<i class="ti ti-file-check me-1 text-success"></i>Selected: ${fileName} (${fileSize} MB)`;

        // Validate file type
        if (!file.name.toLowerCase().endsWith('.csv')) {
            formText.innerHTML = `<i class="ti ti-alert-triangle me-1 text-warning"></i>Warning: Please select a CSV file`;
        }

        // Validate file size (10MB limit)
        if (file.size > 10 * 1024 * 1024) {
            formText.innerHTML = `<i class="ti ti-alert-triangle me-1 text-danger"></i>Error: File size exceeds 10MB limit`;
        }
    }
});
</script>
@endsection
@endsection
