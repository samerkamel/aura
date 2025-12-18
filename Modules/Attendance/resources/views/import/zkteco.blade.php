@extends('layouts/layoutMaster')

@section('title', 'Import ZKTeco Attendance')

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Import Header Card -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <i class="ti ti-fingerprint me-2 text-primary" style="font-size: 1.5rem;"></i>
          <div>
            <h5 class="mb-0">Import ZKTeco Fingerprint Attendance</h5>
            <small class="text-muted">Upload a .dat file from ZKTeco fingerprint device</small>
          </div>
        </div>
        <div class="d-flex gap-2">
          <a href="{{ route('attendance.import.create') }}" class="btn btn-outline-secondary">
            <i class="ti ti-file-text me-1"></i>CSV Import
          </a>
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
              <i class="ti ti-file-upload me-2"></i>ZKTeco DAT File Upload
            </h6>
          </div>
          <div class="card-body">
            @if(isset($error))
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ti ti-alert-circle me-2"></i>{{ $error }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            @endif

            <!-- ZKTeco Format Information -->
            <div class="alert alert-info mb-4">
              <h6><i class="ti ti-info-circle me-2"></i>ZKTeco File Format</h6>
              <p class="mb-2">The system accepts <strong>attlog.dat</strong> files exported from ZKTeco fingerprint devices.</p>
              <div class="row">
                <div class="col-md-6">
                  <p class="mb-1"><strong>Expected Format:</strong></p>
                  <code class="d-block mb-2" style="font-size: 0.85rem;">USER_ID&lt;tab&gt;DATETIME&lt;tab&gt;...</code>
                </div>
                <div class="col-md-6">
                  <p class="mb-1"><strong>Import Logic:</strong></p>
                  <ul class="mb-0 ps-3">
                    <li>First punch of day = <strong>Check In</strong></li>
                    <li>Last punch of day = <strong>Check Out</strong></li>
                    <li>Work day extends until 4:00 AM</li>
                  </ul>
                </div>
              </div>
              <hr class="my-2">
              <small class="text-muted">
                <i class="ti ti-alert-triangle me-1"></i>
                <strong>Important:</strong> The USER_ID in the file must match the employee's <strong>Attendance ID</strong> in the system.
                Make sure employees have their Attendance ID configured correctly.
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

            <form method="POST" action="{{ route('attendance.import.zkteco.preview') }}" enctype="multipart/form-data">
              @csrf

              <!-- File Upload -->
              <div class="mb-4">
                <label for="dat_file" class="form-label">
                  ZKTeco DAT File <span class="text-danger">*</span>
                </label>
                <input type="file"
                       class="form-control @error('dat_file') is-invalid @enderror"
                       id="dat_file"
                       name="dat_file"
                       accept=".dat,.txt"
                       required>
                <div class="form-text">
                  <i class="ti ti-file-text me-1"></i>
                  Accepts .dat or .txt files. Maximum file size: 50MB
                </div>
                @error('dat_file')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Action Buttons -->
              <div class="d-flex justify-content-between">
                <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary">
                  <i class="ti ti-arrow-left me-1"></i>Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                  <i class="ti ti-eye me-1"></i>Preview Import
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
document.getElementById('dat_file').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const fileSize = (file.size / 1024 / 1024).toFixed(2); // Convert to MB
        const fileName = file.name;

        // Update form text to show selected file info
        const formText = e.target.parentNode.querySelector('.form-text');
        formText.innerHTML = `<i class="ti ti-file-check me-1 text-success"></i>Selected: ${fileName} (${fileSize} MB)`;

        // Validate file size (50MB limit)
        if (file.size > 50 * 1024 * 1024) {
            formText.innerHTML = `<i class="ti ti-alert-triangle me-1 text-danger"></i>Error: File size exceeds 50MB limit`;
        }
    }
});
</script>
@endsection
@endsection
