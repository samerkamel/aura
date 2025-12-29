@extends('layouts/layoutMaster')

@section('title', 'Import Expense Categories')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold py-3 mb-0">Import Expense Categories</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('accounting.expenses.categories') }}">Categories</a>
                    </li>
                    <li class="breadcrumb-item active">CSV Import</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('accounting.expenses.categories.import.sample') }}" class="btn btn-outline-info">
                <i class="ti tabler-download me-2"></i>Download Sample CSV
            </a>
            <a href="{{ route('accounting.expenses.categories') }}" class="btn btn-outline-secondary">
                <i class="ti tabler-arrow-left me-2"></i>Back to Categories
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

                    <form method="POST" action="{{ route('accounting.expenses.categories.import.process') }}" enctype="multipart/form-data">
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
                            <a href="{{ route('accounting.expenses.categories') }}" class="btn btn-outline-secondary">
                                <i class="ti tabler-x me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti tabler-upload me-1"></i>Import Categories
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
                            <li><strong>name:</strong> Category name (unique)</li>
                        </ul>
                    </div>

                    <div class="alert alert-secondary">
                        <h6>Optional Fields</h6>
                        <ul class="mb-0">
                            <li><strong>description:</strong> Category description</li>
                            <li><strong>color:</strong> Hex color (#007bff)</li>
                            <li><strong>parent_id:</strong> Parent category ID</li>
                            <li><strong>sort_order:</strong> Display order number</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Parent Categories Reference -->
            @if($parentCategories->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti tabler-category me-2"></i>Available Parent Categories
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Category Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($parentCategories as $category)
                                <tr>
                                    <td>{{ $category->id }}</td>
                                    <td>
                                        <span class="badge" style="background-color: {{ $category->color }}">
                                            {{ $category->name }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
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
    $('form').on('submit', function(e) {
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