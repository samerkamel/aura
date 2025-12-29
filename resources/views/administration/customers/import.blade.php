@extends('layouts/layoutMaster')

@section('title', 'Import Customers')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold py-3 mb-0">Import Customers</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('administration.customers.index') }}">Customers</a>
                    </li>
                    <li class="breadcrumb-item active">CSV Import</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('administration.customers.import.sample') }}" class="btn btn-outline-info">
                <i class="ti tabler-download me-2"></i>Download Sample CSV
            </a>
            <a href="{{ route('administration.customers.index') }}" class="btn btn-outline-secondary">
                <i class="ti tabler-arrow-left me-2"></i>Back to Customers
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Upload CSV File</h5>
                </div>
                <div class="card-body">
                    @if(session('import_errors') && count(session('import_errors')) > 0)
                        <div class="alert alert-warning alert-dismissible" role="alert">
                            <h6 class="alert-heading">Import Errors</h6>
                            <ul class="mb-0">
                                @foreach(session('import_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('administration.customers.import.process') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label" for="csv_file">CSV File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control @error('csv_file') is-invalid @enderror"
                                   id="csv_file" name="csv_file" accept=".csv,.txt" required>
                            @error('csv_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Maximum file size: 2MB. Supported formats: .csv, .txt
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-3">
                            <a href="{{ route('administration.customers.index') }}" class="btn btn-outline-secondary">
                                <i class="ti tabler-x me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti tabler-upload me-1"></i>Import Customers
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <!-- CSV Format Guide -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti tabler-info-circle me-2"></i>CSV Format Guide
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6>Required Fields</h6>
                        <ul class="mb-0">
                            <li><strong>name:</strong> Customer name</li>
                        </ul>
                    </div>

                    <div class="alert alert-secondary">
                        <h6>Optional Fields</h6>
                        <ul class="mb-0">
                            <li><strong>email:</strong> Email address</li>
                            <li><strong>phone:</strong> Phone number</li>
                            <li><strong>address:</strong> Physical address</li>
                            <li><strong>company_name:</strong> Company name (for companies)</li>
                            <li><strong>tax_id:</strong> Tax identification number</li>
                            <li><strong>website:</strong> Website URL</li>
                            <li><strong>contact_persons:</strong> JSON array of contacts</li>
                            <li><strong>notes:</strong> Additional notes</li>
                            <li><strong>type:</strong> "individual" or "company"</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Contact Persons Format -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti tabler-users me-2"></i>Contact Persons Format
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h6>JSON Format</h6>
                        <p class="mb-2">Use JSON array for multiple contacts:</p>
                        <code>["John Doe - Manager", "Jane Smith - Developer"]</code>

                        <p class="mt-3 mb-2">Single contact:</p>
                        <code>["John Doe - CEO"]</code>

                        <p class="mt-3 mb-0">Or simple text will be converted automatically.</p>
                    </div>
                </div>
            </div>

            <!-- Customer Types -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti tabler-tag me-2"></i>Customer Types
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="avatar avatar-md mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-label-primary">
                                        <i class="ti tabler-user ti-md"></i>
                                    </span>
                                </div>
                                <h6>Individual</h6>
                                <small class="text-muted">Personal clients</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="avatar avatar-md mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-label-info">
                                        <i class="ti tabler-building ti-md"></i>
                                    </span>
                                </div>
                                <h6>Company</h6>
                                <small class="text-muted">Corporate clients</small>
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
document.addEventListener('DOMContentLoaded', function() {
    // File upload validation
    const csvFileInput = document.getElementById('csv_file');
    if (csvFileInput) {
        csvFileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const fileName = file.name;
                const fileExtension = fileName.split('.').pop().toLowerCase();

                if (!['csv', 'txt'].includes(fileExtension)) {
                    alert('Please select a CSV or TXT file.');
                    this.value = '';
                    return;
                }

                if (file.size > 2 * 1024 * 1024) { // 2MB limit
                    alert('File size must be less than 2MB.');
                    this.value = '';
                    return;
                }
            }
        });
    }

    // Form validation
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const fileInput = document.getElementById('csv_file');
            if (!fileInput || !fileInput.files.length) {
                e.preventDefault();
                alert('Please select a CSV file to upload.');
                return false;
            }
        });
    }
});
</script>
@endsection