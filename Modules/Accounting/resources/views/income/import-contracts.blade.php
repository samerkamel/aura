@extends('layouts/layoutMaster')

@section('title', 'Import Contracts')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold py-3 mb-0">Import Contracts</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('accounting.income.contracts.index') }}">Contracts</a>
                    </li>
                    <li class="breadcrumb-item active">CSV Import</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('accounting.income.contracts.import.sample') }}" class="btn btn-outline-info">
                <i class="ti ti-download me-2"></i>Download Sample CSV
            </a>
            <a href="{{ route('accounting.income.contracts.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-2"></i>Back to Contracts
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

                    <form method="POST" action="{{ route('accounting.income.contracts.import.process') }}" enctype="multipart/form-data">
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
                            <a href="{{ route('accounting.income.contracts.index') }}" class="btn btn-outline-secondary">
                                <i class="ti ti-x me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-upload me-1"></i>Import Contracts
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
                        <i class="ti ti-info-circle me-2"></i>CSV Format Guide
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6>Required Fields</h6>
                        <ul class="mb-0">
                            <li><strong>client_name:</strong> Client name</li>
                            <li><strong>contract_number:</strong> Unique contract number</li>
                            <li><strong>total_amount:</strong> Contract value (decimal)</li>
                        </ul>
                    </div>

                    <div class="alert alert-secondary">
                        <h6>Optional Fields</h6>
                        <ul class="mb-0">
                            <li><strong>description:</strong> Contract description</li>
                            <li><strong>start_date:</strong> Start date (YYYY-MM-DD)</li>
                            <li><strong>end_date:</strong> End date (YYYY-MM-DD)</li>
                            <li><strong>customer_id:</strong> Customer ID number</li>
                            <li><strong>business_unit_id:</strong> Business Unit ID</li>
                            <li><strong>contact_info:</strong> Contact information</li>
                            <li><strong>notes:</strong> Additional notes</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Customers Reference -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-users me-2"></i>Available Customers
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customers->take(10) as $customer)
                                <tr>
                                    <td>{{ $customer->id }}</td>
                                    <td>{{ $customer->display_name }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted">No customers available</td>
                                </tr>
                                @endforelse
                                @if($customers->count() > 10)
                                <tr>
                                    <td colspan="2" class="text-center text-muted">
                                        <small>... and {{ $customers->count() - 10 }} more</small>
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Business Units Reference -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-building me-2"></i>Available Business Units
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Business Unit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($businessUnits as $bu)
                                <tr>
                                    <td>{{ $bu->id }}</td>
                                    <td>{{ $bu->name }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted">No business units available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // File upload validation
    $('#csv_file').on('change', function() {
        const file = this.files[0];
        if (file) {
            const fileName = file.name;
            const fileExtension = fileName.split('.').pop().toLowerCase();

            if (!['csv', 'txt'].includes(fileExtension)) {
                alert('Please select a CSV or TXT file.');
                $(this).val('');
                return;
            }

            if (file.size > 2 * 1024 * 1024) { // 2MB limit
                alert('File size must be less than 2MB.');
                $(this).val('');
                return;
            }
        }
    });

    // Form validation
    $form = $('form').on('submit', function(e) {
        const fileInput = $('#csv_file')[0];
        if (!fileInput.files.length) {
            e.preventDefault();
            alert('Please select a CSV file to upload.');
            return false;
        }
    });
});
</script>
@endsection