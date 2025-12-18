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
                                    <li><strong>email</strong> - Work email address (used for duplicate checking)</li>
                                </ul>

                                <h6>Optional Columns:</h6>
                                <ul class="mb-3">
                                    <li><strong>name_ar</strong> - Arabic name</li>
                                    <li><strong>personal_email</strong> - Personal email address</li>
                                    <li><strong>attendance_id</strong> - ID to match attendance machine records</li>
                                    <li><strong>national_id</strong> - National ID number</li>
                                    <li><strong>national_insurance_number</strong> - National Insurance Number (NIN)</li>
                                    <li><strong>start_date</strong> - Start date (YYYY-MM-DD or DD/MM/YYYY)</li>
                                    <li><strong>mobile_number</strong> - Mobile phone number</li>
                                    <li><strong>secondary_number</strong> - Secondary/home phone number</li>
                                    <li><strong>current_address</strong> - Current address</li>
                                    <li><strong>permanent_address</strong> - Permanent address</li>
                                    <li><strong>bank_name</strong> - Bank name</li>
                                    <li><strong>account_number</strong> - Bank account number</li>
                                    <li><strong>account_id</strong> - Account ID</li>
                                    <li><strong>iban</strong> - IBAN</li>
                                    <li><strong>emergency_contact_name</strong> - Emergency contact name</li>
                                    <li><strong>emergency_contact_phone</strong> - Emergency contact phone</li>
                                    <li><strong>emergency_contact_relationship</strong> - Relationship (e.g., Spouse, Parent)</li>
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
        ['name', 'name_ar', 'email', 'personal_email', 'attendance_id', 'national_id', 'national_insurance_number', 'start_date', 'mobile_number', 'secondary_number', 'current_address', 'permanent_address', 'bank_name', 'account_number', 'account_id', 'iban', 'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship'],
        ['John Doe', 'جون دو', 'john.doe@company.com', 'john.personal@email.com', 'ATT001', '12345678901234', 'NIN123456', '2024-01-15', '+1234567890', '+1234567800', '123 Main St, City', '456 Home St, Town', 'ABC Bank', '1234567890', 'ACC001', 'EG123456789012345678901234', 'Jane Doe', '+1234567899', 'Spouse'],
        ['Jane Smith', 'جين سميث', 'jane.smith@company.com', 'jane.personal@email.com', 'ATT002', '12345678901235', 'NIN123457', '2024-02-01', '+1234567891', '', '456 Oak Ave, City', '789 Family Rd, Village', 'XYZ Bank', '0987654321', 'ACC002', 'EG987654321098765432109876', 'John Smith', '+1234567898', 'Parent'],
        ['Mike Johnson', 'مايك جونسون', 'mike.johnson@company.com', '', 'ATT003', '12345678901236', 'NIN123458', '2024-01-20', '+1234567892', '+1234567801', '789 Pine Rd, City', '321 Origin St, Hometown', 'DEF Bank', '5678901234', 'ACC003', 'EG567890123456789012345678', 'Sarah Johnson', '+1234567897', 'Sibling']
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