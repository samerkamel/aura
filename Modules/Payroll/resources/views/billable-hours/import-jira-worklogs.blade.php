@extends('layouts/layoutMaster')

@section('title', 'Import Jira Worklogs')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-md-12">
      <!-- Page Header -->
      <div class="card mb-4">
        <div class="card-header">
          <div class="d-flex align-items-center">
            <a href="{{ route('payroll.billable-hours.index') }}" class="btn btn-icon btn-outline-secondary me-3">
              <i class="ti ti-arrow-left"></i>
            </a>
            <div>
              <h5 class="mb-0">
                <i class="ti ti-brand-jira me-2"></i>Import Jira Worklogs
              </h5>
              <small class="text-muted">Upload Jira worklog CSV exports</small>
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

      <div class="row">
        <div class="col-md-8">
          <!-- Upload Form -->
          <div class="card mb-4">
            <div class="card-header">
              <h6 class="mb-0">
                <i class="ti ti-file-upload me-2"></i>Upload CSV File
              </h6>
            </div>
            <div class="card-body">
              <div class="alert alert-info mb-4">
                <h6><i class="ti ti-info-circle me-2"></i>Expected CSV Format</h6>
                <p class="mb-2">Your CSV file should be exported from Jira with the following columns:</p>
                <ul class="mb-2">
                  <li><strong>Author</strong> - The name of the person who logged the work</li>
                  <li><strong>Issue</strong> - The Jira issue key (e.g., MR-15, VIS-185)</li>
                  <li><strong>Issue Summary</strong> - Description of the issue</li>
                  <li><strong>Work log started</strong> - When the work was started</li>
                  <li><strong>Work log created</strong> - When the worklog was created</li>
                  <li><strong>Work log time zone</strong> - Timezone (optional)</li>
                  <li><strong>Time spent</strong> - Hours spent (decimal)</li>
                  <li><strong>Work log comment</strong> - Comment (optional)</li>
                </ul>
                <small class="text-muted">
                  <strong>Note:</strong> Duplicate entries are automatically skipped based on employee, issue, and start time.
                </small>
              </div>

              <form action="{{ route('payroll.billable-hours.import-jira-worklogs.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                  <label for="csv_file" class="form-label">Select CSV File <span class="text-danger">*</span></label>
                  <input type="file" class="form-control @error('csv_file') is-invalid @enderror"
                         id="csv_file" name="csv_file" accept=".csv,.txt" required>
                  @error('csv_file')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                  <small class="text-muted">Maximum file size: 10MB</small>
                </div>

                <div class="d-flex justify-content-end gap-2">
                  <a href="{{ route('payroll.billable-hours.index') }}" class="btn btn-outline-secondary">
                    Cancel
                  </a>
                  <button type="submit" class="btn btn-primary">
                    <i class="ti ti-upload me-1"></i>Import Worklogs
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <!-- Mapped Employees -->
          <div class="card">
            <div class="card-header">
              <h6 class="mb-0">
                <i class="ti ti-users me-2"></i>Mapped Employees
              </h6>
            </div>
            <div class="card-body">
              @if($mappedEmployees->isEmpty())
                <div class="alert alert-warning mb-0">
                  <i class="ti ti-alert-triangle me-2"></i>
                  <strong>No employees mapped!</strong>
                  <p class="mb-0 mt-2">
                    You need to set the "Jira Author Name" field on employees before importing worklogs.
                    Go to <a href="{{ route('hr.employees.index') }}">Employee Management</a> to configure this.
                  </p>
                </div>
              @else
                <p class="text-muted small mb-3">
                  The following employees have Jira Author Names configured and will be matched during import:
                </p>
                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead>
                      <tr>
                        <th>Employee</th>
                        <th>Jira Author Name</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($mappedEmployees as $employee)
                        <tr>
                          <td>{{ $employee->name }}</td>
                          <td><code>{{ $employee->jira_author_name }}</code></td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
                <small class="text-muted">
                  <strong>{{ $mappedEmployees->count() }}</strong> employees configured
                </small>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
