@extends('layouts/layoutMaster')

@section('title', 'Import Contracts from Excel')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold py-3 mb-0">Import Contracts from Excel</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('accounting.income.contracts.index') }}">Contracts</a>
                    </li>
                    <li class="breadcrumb-item active">Excel Import</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('accounting.income.contracts.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-2"></i>Back to Contracts
        </a>
    </div>

    <!-- Step Indicator -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="d-flex justify-content-center align-items-center flex-wrap gap-4">
                <div class="d-flex align-items-center step-item" id="step-indicator-upload">
                    <div class="avatar avatar-sm me-2 bg-primary">
                        <span class="avatar-initial rounded-circle"><i class="ti ti-upload ti-sm"></i></span>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-semibold">Upload</h6>
                        <small class="text-muted">Select File & Year</small>
                    </div>
                </div>
                <i class="ti ti-chevron-right text-muted d-none d-sm-block"></i>
                <div class="d-flex align-items-center step-item" id="step-indicator-preview">
                    <div class="avatar avatar-sm me-2 bg-label-secondary">
                        <span class="avatar-initial rounded-circle"><i class="ti ti-eye ti-sm"></i></span>
                    </div>
                    <div>
                        <h6 class="mb-0">Preview</h6>
                        <small class="text-muted">Review & Map Data</small>
                    </div>
                </div>
                <i class="ti ti-chevron-right text-muted d-none d-sm-block"></i>
                <div class="d-flex align-items-center step-item" id="step-indicator-import">
                    <div class="avatar avatar-sm me-2 bg-label-secondary">
                        <span class="avatar-initial rounded-circle"><i class="ti ti-check ti-sm"></i></span>
                    </div>
                    <div>
                        <h6 class="mb-0">Import</h6>
                        <small class="text-muted">Confirm & Process</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 1: Upload -->
    <div id="step-upload-content">
        <div class="row">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ti ti-file-spreadsheet me-2"></i>Upload Excel File</h5>
                    </div>
                    <div class="card-body">
                        <form id="uploadForm">
                            <div class="row mb-4">
                                <div class="col-md-8">
                                    <label class="form-label" for="excel_file">Excel File <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="excel_file" name="file"
                                           accept=".xls,.xlsx" required>
                                    <div class="form-text">
                                        Supported formats: .xls, .xlsx (Max 10MB)
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="import_year">Year <span class="text-danger">*</span></label>
                                    <select class="form-select" id="import_year" name="year" required>
                                        @for($y = date('Y'); $y >= 2015; $y--)
                                            <option value="{{ $y }}">{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>

                            <div class="alert alert-info d-flex align-items-start">
                                <i class="ti ti-info-circle me-2 mt-1"></i>
                                <div>
                                    <strong>Expected Excel Format:</strong>
                                    <ul class="mb-0 mt-1">
                                        <li>Multiple sheets by product type (PHP, Mobile, Websites, .Net, Products, Design, Hosting)</li>
                                        <li>Each customer has 5 rows: Balance (رصيد), Contract (تعاقد), Expected Contract (توقع تعاقد), Paid (مدفوع), Expected (متوقع)</li>
                                        <li>Monthly columns for January through December</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-3">
                                <a href="{{ route('accounting.income.contracts.index') }}" class="btn btn-outline-secondary">
                                    <i class="ti ti-x me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary" id="btnPreview">
                                    <span class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
                                    <i class="ti ti-eye me-1"></i>Preview Data
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ti ti-package me-2"></i>Product Mapping</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Excel sheet names will be mapped to these products:</p>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Sheet Name</th>
                                        <th>Product</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                    $sheetMapping = [
                                        'PHP' => 'Custom Software',
                                        'Mobile' => 'Mobile Applications',
                                        'Websites' => 'Websites',
                                        '.Net' => '.NET Development',
                                        'Products' => 'Products',
                                        'Design' => 'Design',
                                        'Hosting' => 'Hosting',
                                    ];
                                    @endphp
                                    @foreach($sheetMapping as $sheet => $product)
                                    <tr>
                                        <td><code>{{ $sheet }}</code></td>
                                        <td>{{ $product }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Preview -->
    <div id="step-preview-content" class="d-none">
        <div class="row">
            <!-- Stats Cards -->
            <div class="col-12 mb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-label-primary">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-primary">Total Contracts</h6>
                                        <h3 class="mb-0" id="stat-total-contracts">0</h3>
                                    </div>
                                    <i class="ti ti-file-text ti-xl text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-label-success">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-success">Matched Customers</h6>
                                        <h3 class="mb-0" id="stat-matched-customers">0</h3>
                                    </div>
                                    <i class="ti ti-user-check ti-xl text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-label-warning">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-warning">Unmatched</h6>
                                        <h3 class="mb-0" id="stat-unmatched-customers">0</h3>
                                    </div>
                                    <i class="ti ti-user-question ti-xl text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-label-danger">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-danger">Duplicates</h6>
                                        <h3 class="mb-0" id="stat-duplicates">0</h3>
                                    </div>
                                    <i class="ti ti-copy ti-xl text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Mapping -->
            <div class="col-xl-4 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="ti ti-users me-2"></i>Customer Mapping</h5>
                        <span class="badge bg-label-primary" id="customer-mapping-count">0</span>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <div id="customer-mapping-container">
                            <!-- Customer mappings will be rendered here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Mapping -->
            <div class="col-xl-3 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="ti ti-package me-2"></i>Product Mapping</h5>
                        <span class="badge bg-label-info" id="product-mapping-count">0</span>
                    </div>
                    <div class="card-body">
                        <div id="product-mapping-container">
                            <!-- Product mappings will be rendered here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contracts Preview -->
            <div class="col-xl-5 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="ti ti-list me-2"></i>Contracts to Import</h5>
                        <div class="d-flex gap-2 align-items-center">
                            <button class="btn btn-xs btn-outline-primary" id="btnSelectAll">Select All</button>
                            <button class="btn btn-xs btn-outline-secondary" id="btnDeselectAll">Deselect All</button>
                        </div>
                    </div>
                    <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="sticky-top bg-white">
                                <tr>
                                    <th width="30"><input type="checkbox" class="form-check-input" id="selectAllContracts"></th>
                                    <th>Customer</th>
                                    <th>Product</th>
                                    <th class="text-end">Amount</th>
                                    <th width="60">Status</th>
                                </tr>
                            </thead>
                            <tbody id="contracts-preview-table">
                                <!-- Contracts will be rendered here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="col-12">
                <div class="card">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-outline-secondary" id="btnBackToUpload">
                                <i class="ti ti-arrow-left me-1"></i>Back to Upload
                            </button>
                            <div class="d-flex gap-2">
                                <span class="text-muted align-self-center" id="selected-count">0 contracts selected</span>
                                <button type="button" class="btn btn-primary" id="btnProcessImport" disabled>
                                    <span class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
                                    <i class="ti ti-download me-1"></i>Import Selected Contracts
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3: Results -->
    <div id="step-results-content" class="d-none">
        <div class="card">
            <div class="card-body text-center py-5">
                <div id="import-success" class="d-none">
                    <i class="ti ti-circle-check text-success" style="font-size: 5rem;"></i>
                    <h3 class="mt-3">Import Completed!</h3>
                    <p class="text-muted" id="import-result-message">Successfully imported contracts.</p>
                    <a href="{{ route('accounting.income.contracts.index') }}" class="btn btn-primary mt-3">
                        <i class="ti ti-arrow-left me-1"></i>Go to Contracts
                    </a>
                </div>
                <div id="import-error" class="d-none">
                    <i class="ti ti-circle-x text-danger" style="font-size: 5rem;"></i>
                    <h3 class="mt-3">Import Failed</h3>
                    <p class="text-muted" id="import-error-message">An error occurred during import.</p>
                    <button type="button" class="btn btn-outline-secondary mt-3" id="btnRetry">
                        <i class="ti ti-refresh me-1"></i>Try Again
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Duplicate Warning Modal -->
<div class="modal fade" id="duplicateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="ti ti-alert-triangle me-2"></i>Potential Duplicate Found</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>This contract may already exist in the system:</p>
                <div id="duplicate-details" class="alert alert-light">
                    <!-- Duplicate details will be shown here -->
                </div>
                <p class="mb-0">Do you want to import this contract anyway?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Skip</button>
                <button type="button" class="btn btn-warning" id="btnImportAnyway">Import Anyway</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // State management
    let importData = null;
    let contractsToImport = [];
    const customers = @json($customers);
    const products = @json($products);
    const $ = window.jQuery;


    // Initialize Select2 for any dynamic selects
    function initSelect2(selector) {
        if ($ && $.fn.select2) {
            $(selector).select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select customer...',
                allowClear: true
            });
        }
    }

    // Step 1: Upload and Preview
    $('#uploadForm').on('submit', function(e) {
        e.preventDefault();

        const fileInput = $('#excel_file')[0];
        if (!fileInput.files.length) {
            alert('Please select an Excel file.');
            return;
        }

        const file = fileInput.files[0];
        if (file.size > 10 * 1024 * 1024) {
            alert('File size must be less than 10MB.');
            return;
        }

        const formData = new FormData();
        formData.append('file', file);
        formData.append('year', $('#import_year').val());

        const $btn = $('#btnPreview');
        $btn.prop('disabled', true);
        $btn.find('.spinner-border').removeClass('d-none');
        $btn.find('.ti-eye').addClass('d-none');

        $.ajax({
            url: '{{ route("accounting.income.contracts.import.preview") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    importData = response.data;
                    renderPreview(response.data, response.stats);
                    showStep('preview');
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'Failed to parse Excel file. Please check the format.';
                alert(error);
            },
            complete: function() {
                $btn.prop('disabled', false);
                $btn.find('.spinner-border').addClass('d-none');
                $btn.find('.ti-eye').removeClass('d-none');
            }
        });
    });

    // Render preview data
    function renderPreview(data, stats) {
        // Update stats
        $('#stat-total-contracts').text(stats.total_contracts);
        $('#stat-matched-customers').text(stats.matched_customers);
        $('#stat-unmatched-customers').text(stats.total_customers - stats.matched_customers);

        let duplicateCount = data.contracts.filter(c => c.potential_duplicate).length;
        $('#stat-duplicates').text(duplicateCount);

        // Render customer mappings
        renderCustomerMappings(data.customers);

        // Render product mappings
        renderProductMappings(data.products);

        // Render contracts table
        renderContractsTable(data.contracts);

        // Update counts
        updateSelectedCount();
    }

    // Render customer mapping UI
    function renderCustomerMappings(customerMappings) {
        const $container = $('#customer-mapping-container');
        $container.empty();

        let count = 0;
        Object.entries(customerMappings).forEach(([name, match]) => {
            count++;
            const matchClass = match ? (match.match_type === 'exact' ? 'success' : 'warning') : 'danger';
            const matchText = match ? match.name : 'Not Found';
            const matchIcon = match ? 'ti-check' : 'ti-x';

            const html = `
                <div class="customer-mapping mb-3 pb-2 border-bottom" data-customer-name="${escapeHtml(name)}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-medium text-truncate" style="max-width: 150px;" title="${escapeHtml(name)}">${escapeHtml(name)}</span>
                        <span class="badge bg-label-${matchClass}">
                            <i class="ti ${matchIcon} me-1"></i>${match ? match.match_type : 'unmatched'}
                        </span>
                    </div>
                    <select class="form-select form-select-sm customer-select" data-customer-name="${escapeHtml(name)}">
                        <option value="">-- Select or Create --</option>
                        ${match ? `<option value="${match.id}" selected>${escapeHtml(match.name)}</option>` : ''}
                        ${customers.filter(c => !match || c.id !== match.id).map(c =>
                            `<option value="${c.id}">${escapeHtml(c.display_name || c.company_name || c.name)}</option>`
                        ).join('')}
                        <option value="create_new" class="text-primary">+ Create New Customer</option>
                    </select>
                </div>
            `;
            $container.append(html);
        });

        $('#customer-mapping-count').text(count);
        initSelect2('.customer-select');
    }

    // Render product mapping UI
    function renderProductMappings(productMappings) {
        const $container = $('#product-mapping-container');
        $container.empty();

        let count = 0;
        Object.entries(productMappings).forEach(([sheetName, mapping]) => {
            if (sheetName === 'Total') return;
            count++;

            const html = `
                <div class="product-mapping mb-3 pb-2 border-bottom" data-sheet="${escapeHtml(sheetName)}">
                    <label class="form-label small mb-1">${escapeHtml(sheetName)}</label>
                    <select class="form-select form-select-sm product-select" data-sheet="${escapeHtml(sheetName)}">
                        <option value="">-- Select --</option>
                        ${products.map(p =>
                            `<option value="${p.id}" ${mapping.product_id === p.id ? 'selected' : ''}>${escapeHtml(p.name)}</option>`
                        ).join('')}
                    </select>
                </div>
            `;
            $container.append(html);
        });

        $('#product-mapping-count').text(count);
    }

    // Render contracts table
    function renderContractsTable(contracts) {
        const $tbody = $('#contracts-preview-table');
        $tbody.empty();
        contractsToImport = [];

        contracts.forEach((contract, index) => {
            const isDuplicate = contract.potential_duplicate;
            const rowClass = isDuplicate ? 'table-warning' : '';
            const customer = importData.customers[contract.customer_name];

            const html = `
                <tr class="${rowClass}" data-index="${index}">
                    <td>
                        <input type="checkbox" class="form-check-input contract-checkbox"
                               data-index="${index}" ${!isDuplicate ? 'checked' : ''}>
                    </td>
                    <td>
                        <span class="text-truncate d-inline-block" style="max-width: 120px;"
                              title="${escapeHtml(contract.customer_name)}">
                            ${escapeHtml(contract.customer_name)}
                        </span>
                        ${!customer ? '<br><small class="text-danger">No match</small>' : ''}
                    </td>
                    <td><small class="text-muted">${escapeHtml(contract.product_sheet)}</small></td>
                    <td class="text-end">${formatCurrency(contract.total_amount)}</td>
                    <td>
                        ${isDuplicate ?
                            `<span class="badge bg-warning cursor-pointer" data-bs-toggle="tooltip"
                                   title="Click for details" onclick="showDuplicate(${index})">
                                <i class="ti ti-copy"></i>
                            </span>` :
                            `<span class="badge bg-success"><i class="ti ti-check"></i></span>`
                        }
                    </td>
                </tr>
            `;
            $tbody.append(html);

            // Track for import
            contractsToImport.push({
                ...contract,
                import: !isDuplicate,
                customer_id: customer?.id || null,
                product_id: importData.products[contract.product_sheet]?.product_id || null
            });
        });

        // Initialize tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();

        updateSelectedCount();
    }

    // Show duplicate details
    window.showDuplicate = function(index) {
        const contract = contractsToImport[index];
        if (!contract.potential_duplicate) return;

        const dup = contract.potential_duplicate;
        $('#duplicate-details').html(`
            <strong>Existing Contract:</strong> ${dup.contract_number}<br>
            <strong>Customer:</strong> ${dup.customer}<br>
            <strong>Amount:</strong> ${formatCurrency(dup.total_amount)}<br>
            <strong>Period:</strong> ${dup.start_date} to ${dup.end_date}
        `);

        $('#btnImportAnyway').data('index', index);
        $('#duplicateModal').modal('show');
    };

    // Import duplicate anyway
    $('#btnImportAnyway').on('click', function() {
        const index = $(this).data('index');
        contractsToImport[index].import = true;
        $(`tr[data-index="${index}"] .contract-checkbox`).prop('checked', true);
        $('#duplicateModal').modal('hide');
        updateSelectedCount();
    });

    // Update selected count
    function updateSelectedCount() {
        const count = contractsToImport.filter(c => c.import).length;
        $('#selected-count').text(count + ' contracts selected');
        $('#btnProcessImport').prop('disabled', count === 0);
    }

    // Handle contract checkbox change
    $(document).on('change', '.contract-checkbox', function() {
        const index = $(this).data('index');
        contractsToImport[index].import = $(this).is(':checked');
        updateSelectedCount();
    });

    // Select All / Deselect All
    $('#selectAllContracts, #btnSelectAll').on('click', function() {
        $('.contract-checkbox').prop('checked', true);
        contractsToImport.forEach(c => c.import = true);
        updateSelectedCount();
    });

    $('#btnDeselectAll').on('click', function() {
        $('.contract-checkbox').prop('checked', false);
        contractsToImport.forEach(c => c.import = false);
        updateSelectedCount();
    });

    // Handle customer mapping changes
    $(document).on('change', '.customer-select', function() {
        const customerName = $(this).data('customer-name');
        const customerId = $(this).val();

        if (customerId === 'create_new') {
            // TODO: Open create customer modal
            alert('Create new customer functionality coming soon. Please select an existing customer or create one separately.');
            $(this).val('');
            return;
        }

        // Update all contracts with this customer name
        contractsToImport.forEach(c => {
            if (c.customer_name === customerName) {
                c.customer_id = customerId ? parseInt(customerId) : null;
            }
        });
    });

    // Handle product mapping changes
    $(document).on('change', '.product-select', function() {
        const sheetName = $(this).data('sheet');
        const productId = $(this).val();

        // Update all contracts from this sheet
        contractsToImport.forEach(c => {
            if (c.product_sheet === sheetName) {
                c.product_id = productId ? parseInt(productId) : null;
            }
        });
    });

    // Process Import
    $('#btnProcessImport').on('click', function() {
        const toImport = contractsToImport.filter(c => c.import);
        if (toImport.length === 0) {
            alert('Please select at least one contract to import.');
            return;
        }

        // Collect customer and product mappings
        const customerMappings = {};
        $('.customer-select').each(function() {
            const name = $(this).data('customer-name');
            const id = $(this).val();
            if (id && id !== 'create_new') {
                customerMappings[name] = parseInt(id);
            }
        });

        const productMappings = {};
        $('.product-select').each(function() {
            const sheet = $(this).data('sheet');
            const id = $(this).val();
            if (id) {
                productMappings[sheet] = parseInt(id);
            }
        });

        const $btn = $(this);
        $btn.prop('disabled', true);
        $btn.find('.spinner-border').removeClass('d-none');

        $.ajax({
            url: '{{ route("accounting.income.contracts.import.process") }}',
            method: 'POST',
            data: JSON.stringify({
                contracts: contractsToImport,
                customer_mappings: customerMappings,
                product_mappings: productMappings
            }),
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('#import-result-message').text(response.message);
                    $('#import-success').removeClass('d-none');
                    $('#import-error').addClass('d-none');
                } else {
                    $('#import-error-message').text(response.message);
                    $('#import-error').removeClass('d-none');
                    $('#import-success').addClass('d-none');
                }
                showStep('results');
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'Import failed. Please try again.';
                $('#import-error-message').text(error);
                $('#import-error').removeClass('d-none');
                $('#import-success').addClass('d-none');
                showStep('results');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $btn.find('.spinner-border').addClass('d-none');
            }
        });
    });

    // Navigation
    $('#btnBackToUpload').on('click', function() {
        showStep('upload');
    });

    $('#btnRetry').on('click', function() {
        showStep('upload');
    });

    // Show step
    function showStep(step) {
        $('#step-upload-content, #step-preview-content, #step-results-content').addClass('d-none');

        // Reset all step indicators
        $('#step-indicator-upload .avatar').removeClass('bg-primary bg-success').addClass('bg-label-secondary');
        $('#step-indicator-preview .avatar').removeClass('bg-primary bg-success').addClass('bg-label-secondary');
        $('#step-indicator-import .avatar').removeClass('bg-primary bg-success').addClass('bg-label-secondary');

        if (step === 'upload') {
            $('#step-upload-content').removeClass('d-none');
            $('#step-indicator-upload .avatar').removeClass('bg-label-secondary').addClass('bg-primary');
        } else if (step === 'preview') {
            $('#step-preview-content').removeClass('d-none');
            $('#step-indicator-upload .avatar').removeClass('bg-label-secondary').addClass('bg-success');
            $('#step-indicator-preview .avatar').removeClass('bg-label-secondary').addClass('bg-primary');
        } else if (step === 'results') {
            $('#step-results-content').removeClass('d-none');
            $('#step-indicator-upload .avatar').removeClass('bg-label-secondary').addClass('bg-success');
            $('#step-indicator-preview .avatar').removeClass('bg-label-secondary').addClass('bg-success');
            $('#step-indicator-import .avatar').removeClass('bg-label-secondary').addClass('bg-success');
        }
    }

    // Utility functions
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-EG', {
            style: 'currency',
            currency: 'EGP',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize first step
    showStep('upload');
});
</script>
@endsection
