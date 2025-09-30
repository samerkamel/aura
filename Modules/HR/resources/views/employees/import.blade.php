@extends('layouts/layoutMaster')

@section('title', 'Import Employees')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Import Employees</h5>
                    <small class="text-muted">Import employee data from CSV or Excel files</small>
                </div>
                <a href="{{ route('hr.employees.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Back to Employees
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card-body">
                <div class="row">
                    <!-- Import Instructions -->
                    <div class="col-lg-6 mb-4">
                        <div class="card bg-label-info">
                            <div class="card-header">
                                <h6 class="card-title mb-0"><i class="ti ti-info-circle me-2"></i>Import Instructions</h6>
                            </div>
                            <div class="card-body">
                                <h6>Required Columns:</h6>
                                <ul class="mb-3">
                                    <li><strong>name</strong> - Full name of the employee</li>
                                    <li><strong>email</strong> - Email address (used for duplicate checking)</li>
                                    <li><strong>position</strong> - Job title/position</li>
                                    <li><strong>start_date</strong> - Start date (YYYY-MM-DD or DD/MM/YYYY)</li>
                                    <li><strong>base_salary</strong> - Monthly salary amount</li>
                                </ul>

                                <h6>Optional Columns:</h6>
                                <ul class="mb-3">
                                    <li><strong>phone</strong> - Phone number</li>
                                    <li><strong>address</strong> - Home address</li>
                                </ul>

                                <div class="alert alert-warning">
                                    <i class="ti ti-alert-triangle me-2"></i>
                                    <strong>Note:</strong> If an employee with the same email exists, their information will be updated with the new data.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Import Form -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0"><i class="ti ti-upload me-2"></i>Upload File</h6>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('hr.employees.import.process') }}" method="POST" enctype="multipart/form-data">
                                    @csrf

                                    <div class="mb-4">
                                        <label for="import_file" class="form-label">Select File</label>
                                        <input type="file"
                                               class="form-control @error('import_file') is-invalid @enderror"
                                               id="import_file"
                                               name="import_file"
                                               accept=".csv,.xlsx,.xls"
                                               required>
                                        @error('import_file')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">
                                            Supported formats: CSV, Excel (.xlsx, .xls). Maximum file size: 10MB
                                        </small>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ti ti-upload me-2"></i>Import Employees
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Sample Template -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0"><i class="ti ti-download me-2"></i>Sample Template</h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">Download a sample template to see the expected format:</p>
                                <button type="button" class="btn btn-outline-info w-100" onclick="downloadSampleCSV()">
                                    <i class="ti ti-file-type-csv me-2"></i>Download Sample CSV
                                </button>
                            </div>
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
function downloadSampleCSV() {
    // Create sample CSV content
    const csvContent = [
        ['name', 'email', 'position', 'start_date', 'base_salary', 'phone', 'address'],
        ['John Doe', 'john.doe@company.com', 'Software Developer', '2024-01-15', '5000.00', '+1234567890', '123 Main St, City, Country'],
        ['Jane Smith', 'jane.smith@company.com', 'Marketing Manager', '2024-02-01', '6000.00', '+1234567891', '456 Oak Ave, City, Country'],
        ['Mike Johnson', 'mike.johnson@company.com', 'Sales Representative', '2024-01-20', '4500.00', '+1234567892', '789 Pine Rd, City, Country']
    ];

    // Convert to CSV string
    const csvString = csvContent.map(row =>
        row.map(field => `"${field}"`).join(',')
    ).join('\n');

    // Create and download file
    const blob = new Blob([csvString], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'employee_import_template.csv';
    link.click();
    window.URL.revokeObjectURL(url);
}
</script>
@endsection