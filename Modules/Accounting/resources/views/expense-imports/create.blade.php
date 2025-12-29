@extends('layouts/layoutMaster')

@section('title', 'Import Expenses')

@section('content')
<div class="row">
    <div class="col-lg-8 col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Upload Expense File</h5>
                <small class="text-muted">Import expenses from CSV or Excel file</small>
            </div>

            <form action="{{ route('accounting.expense-imports.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-4">
                        <label class="form-label required">Select File</label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".csv,.xlsx,.xls" required>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Supported formats: CSV, XLSX, XLS (max 10MB)</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Optional notes about this import...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="alert alert-info">
                        <h6><i class="ti ti-info-circle me-2"></i>Expected File Format</h6>
                        <p class="mb-2">The file should have the following columns:</p>
                        <ul class="mb-0">
                            <li><strong>Date</strong> - DD/MM/YYYY format</li>
                            <li><strong>Item</strong> - Description of the expense</li>
                            <li><strong>Type</strong> - Cost of Sales, OpEx, Income, Tax, Payroll, Investment</li>
                            <li><strong>Category</strong> - Expense category</li>
                            <li><strong>Sub Category</strong> - Expense subcategory</li>
                            <li><strong>Customer</strong> - Customer/Department name</li>
                            <li><strong>Account Columns</strong> - Samer, Simon, Fadi, Adel, CapEx Cash, Cash, Bank (QNB)EGP, Margins</li>
                            <li><strong>Total (EGP)</strong> - Total amount (negative for income)</li>
                        </ul>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('accounting.expense-imports.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-upload me-1"></i>Upload & Parse
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4 col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Import Process</h6>
            </div>
            <div class="card-body">
                <div class="d-flex mb-3">
                    <div class="flex-shrink-0">
                        <span class="badge bg-primary rounded-pill">1</span>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1">Upload File</h6>
                        <small class="text-muted">Upload your CSV/Excel file</small>
                    </div>
                </div>

                <div class="d-flex mb-3">
                    <div class="flex-shrink-0">
                        <span class="badge bg-secondary rounded-pill">2</span>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1">Review & Map</h6>
                        <small class="text-muted">Review data, map values to existing entities, bulk edit</small>
                    </div>
                </div>

                <div class="d-flex mb-3">
                    <div class="flex-shrink-0">
                        <span class="badge bg-secondary rounded-pill">3</span>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1">Preview Changes</h6>
                        <small class="text-muted">See exactly what will be created</small>
                    </div>
                </div>

                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <span class="badge bg-secondary rounded-pill">4</span>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1">Execute</h6>
                        <small class="text-muted">Dry run or commit changes</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Available Accounts</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li><i class="ti ti-wallet text-primary me-2"></i>Samer</li>
                    <li><i class="ti ti-wallet text-primary me-2"></i>Simon</li>
                    <li><i class="ti ti-wallet text-primary me-2"></i>Fadi</li>
                    <li><i class="ti ti-wallet text-primary me-2"></i>Adel</li>
                    <li><i class="ti ti-cash text-success me-2"></i>CapEx Cash</li>
                    <li><i class="ti ti-cash text-success me-2"></i>Cash</li>
                    <li><i class="ti ti-building-bank text-info me-2"></i>Bank (QNB)EGP</li>
                    <li><i class="ti ti-cash text-warning me-2"></i>Margins</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
