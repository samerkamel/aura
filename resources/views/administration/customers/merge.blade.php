@extends('layouts/layoutMaster')

@section('title', 'Merge Customers')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><i class="ti ti-git-merge me-2"></i>Merge Customers</h5>
                    <small class="text-muted">Combine duplicate customers into a single record</small>
                </div>
                <a href="{{ route('administration.customers.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Back to Customers
                </a>
            </div>

            <div class="card-body">
                <!-- Instructions -->
                <div class="alert alert-info mb-4">
                    <h6 class="alert-heading mb-2"><i class="ti ti-info-circle me-1"></i>How Merge Works</h6>
                    <ul class="mb-0 ps-3">
                        <li>Select 2 or more duplicate customers to merge</li>
                        <li>The customer with the <strong>earliest ID</strong> will be kept as the primary</li>
                        <li>All contracts, projects, invoices, estimates, and credit notes will be transferred to the primary customer</li>
                        <li>Missing information (email, phone, address, etc.) will be filled from duplicates</li>
                        <li>Contact persons and notes will be combined</li>
                        <li>Duplicate customer records will be <strong class="text-danger">permanently deleted</strong></li>
                    </ul>
                </div>

                <!-- Customer Selection -->
                <div class="mb-4">
                    <label class="form-label" for="customerSelect">Select Customers to Merge</label>
                    <select id="customerSelect" class="form-select select2" multiple data-placeholder="Search and select customers...">
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}"
                                    data-contracts="{{ $customer->contracts_count }}"
                                    data-projects="{{ $customer->projects_count }}"
                                    data-invoices="{{ $customer->invoices_count }}"
                                    {{ in_array($customer->id, (array)$selectedIds) ? 'selected' : '' }}>
                                #{{ $customer->id }} - {{ $customer->display_name }}
                                @if($customer->company_name && $customer->company_name !== $customer->name)
                                    ({{ $customer->company_name }})
                                @endif
                                @if($customer->email)
                                    - {{ $customer->email }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Search by name, company, or email. Select at least 2 customers.</small>
                </div>

                <!-- Preview Button -->
                <div class="mb-4">
                    <button type="button" id="previewBtn" class="btn btn-primary" disabled>
                        <i class="ti ti-eye me-1"></i>Preview Merge
                    </button>
                    <span id="selectionCount" class="ms-2 text-muted">0 customers selected</span>
                </div>

                <!-- Preview Panel (Hidden by default) -->
                <div id="previewPanel" style="display: none;">
                    <hr>
                    <h5 class="mb-3"><i class="ti ti-list-check me-2"></i>Merge Preview</h5>

                    <!-- Primary Customer -->
                    <div class="card bg-label-success mb-3">
                        <div class="card-body">
                            <h6 class="card-title mb-2">
                                <i class="ti ti-crown me-1"></i>Primary Customer (Will be kept)
                            </h6>
                            <div id="primaryInfo">
                                <!-- Filled via AJAX -->
                            </div>
                        </div>
                    </div>

                    <!-- Duplicates to be removed -->
                    <div class="card bg-label-danger mb-3">
                        <div class="card-body">
                            <h6 class="card-title mb-2">
                                <i class="ti ti-trash me-1"></i>Duplicates (Will be deleted)
                            </h6>
                            <div id="duplicatesInfo">
                                <!-- Filled via AJAX -->
                            </div>
                        </div>
                    </div>

                    <!-- Transfer Summary -->
                    <div class="card bg-label-warning mb-4">
                        <div class="card-body">
                            <h6 class="card-title mb-2">
                                <i class="ti ti-transfer me-1"></i>Records to be Transferred
                            </h6>
                            <div class="row" id="transferSummary">
                                <!-- Filled via AJAX -->
                            </div>
                        </div>
                    </div>

                    <!-- Merge Form -->
                    <form id="mergeForm" method="POST" action="{{ route('administration.customers.merge.process') }}">
                        @csrf
                        <div id="hiddenInputs">
                            <!-- Customer IDs will be added here -->
                        </div>

                        <div class="alert alert-danger mb-3">
                            <strong><i class="ti ti-alert-triangle me-1"></i>Warning!</strong>
                            This action cannot be undone. Please verify the information above before proceeding.
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirmMerge" required>
                            <label class="form-check-label" for="confirmMerge">
                                I understand that duplicate customers will be permanently deleted and this action cannot be undone.
                            </label>
                        </div>

                        <button type="submit" class="btn btn-danger" id="mergeBtn" disabled>
                            <i class="ti ti-git-merge me-1"></i>Merge Customers
                        </button>
                    </form>
                </div>

                <!-- Loading Spinner -->
                <div id="loadingSpinner" style="display: none;" class="text-center my-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading preview...</p>
                </div>

                <!-- Error Panel -->
                <div id="errorPanel" class="alert alert-danger" style="display: none;">
                    <i class="ti ti-alert-circle me-1"></i><span id="errorMessage"></span>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="bs-toast toast toast-placement-ex m-2 fade bg-success show top-0 end-0" role="alert">
        <div class="toast-header">
            <i class="ti ti-check text-success me-2"></i>
            <div class="me-auto fw-medium">Success!</div>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">{{ session('success') }}</div>
    </div>
@endif

@if(session('error'))
    <div class="bs-toast toast toast-placement-ex m-2 fade bg-danger show top-0 end-0" role="alert">
        <div class="toast-header">
            <i class="ti ti-x text-danger me-2"></i>
            <div class="me-auto fw-medium">Error!</div>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">{{ session('error') }}</div>
    </div>
@endif
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const customerSelect = $('#customerSelect');
    const previewBtn = document.getElementById('previewBtn');
    const previewPanel = document.getElementById('previewPanel');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const errorPanel = document.getElementById('errorPanel');
    const errorMessage = document.getElementById('errorMessage');
    const selectionCount = document.getElementById('selectionCount');
    const confirmCheckbox = document.getElementById('confirmMerge');
    const mergeBtn = document.getElementById('mergeBtn');

    // Initialize Select2
    customerSelect.select2({
        placeholder: 'Search and select customers...',
        allowClear: true,
        width: '100%'
    });

    // Handle selection change
    customerSelect.on('change', function() {
        const selected = customerSelect.val() || [];
        const count = selected.length;

        selectionCount.textContent = count + ' customer' + (count !== 1 ? 's' : '') + ' selected';
        previewBtn.disabled = count < 2;

        // Hide preview when selection changes
        previewPanel.style.display = 'none';
        errorPanel.style.display = 'none';
    });

    // Preview button click
    previewBtn.addEventListener('click', function() {
        const selectedIds = customerSelect.val();

        if (!selectedIds || selectedIds.length < 2) {
            showError('Please select at least 2 customers to merge.');
            return;
        }

        loadingSpinner.style.display = 'block';
        previewPanel.style.display = 'none';
        errorPanel.style.display = 'none';

        fetch('{{ route("administration.customers.merge.preview") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ customer_ids: selectedIds })
        })
        .then(response => response.json())
        .then(data => {
            loadingSpinner.style.display = 'none';

            if (data.error) {
                showError(data.error);
                return;
            }

            displayPreview(data.preview, selectedIds);
        })
        .catch(error => {
            loadingSpinner.style.display = 'none';
            showError('Failed to load preview: ' + error.message);
        });
    });

    // Display preview
    function displayPreview(preview, selectedIds) {
        // Primary customer info
        const primaryInfo = document.getElementById('primaryInfo');
        primaryInfo.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="avatar avatar-sm me-2">
                    <span class="avatar-initial rounded-circle bg-success">
                        <i class="ti ti-user"></i>
                    </span>
                </div>
                <div>
                    <strong>#${preview.primary.id} - ${preview.primary.name || 'N/A'}</strong>
                    ${preview.primary.company_name ? `<br><small class="text-muted">${preview.primary.company_name}</small>` : ''}
                    ${preview.primary.email ? `<br><small class="text-muted">${preview.primary.email}</small>` : ''}
                </div>
            </div>
        `;

        // Duplicates info
        const duplicatesInfo = document.getElementById('duplicatesInfo');
        let duplicatesHtml = '';
        preview.duplicates.forEach(dup => {
            duplicatesHtml += `
                <div class="d-flex align-items-center mb-2">
                    <div class="avatar avatar-sm me-2">
                        <span class="avatar-initial rounded-circle bg-danger">
                            <i class="ti ti-user"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <strong>#${dup.id} - ${dup.name || 'N/A'}</strong>
                        ${dup.company_name ? `<br><small class="text-muted">${dup.company_name}</small>` : ''}
                        ${dup.email ? `<br><small class="text-muted">${dup.email}</small>` : ''}
                    </div>
                    <div class="text-end">
                        <small class="text-muted">
                            ${dup.contracts > 0 ? `<span class="badge bg-label-primary me-1">${dup.contracts} contracts</span>` : ''}
                            ${dup.projects > 0 ? `<span class="badge bg-label-info me-1">${dup.projects} projects</span>` : ''}
                            ${dup.invoices > 0 ? `<span class="badge bg-label-success me-1">${dup.invoices} invoices</span>` : ''}
                            ${dup.estimates > 0 ? `<span class="badge bg-label-warning me-1">${dup.estimates} estimates</span>` : ''}
                            ${dup.credit_notes > 0 ? `<span class="badge bg-label-danger me-1">${dup.credit_notes} credit notes</span>` : ''}
                        </small>
                    </div>
                </div>
            `;
        });
        duplicatesInfo.innerHTML = duplicatesHtml || '<p class="text-muted mb-0">No duplicates selected</p>';

        // Transfer summary
        const transferSummary = document.getElementById('transferSummary');
        const totals = preview.totals;
        transferSummary.innerHTML = `
            <div class="col-md-2 col-4 text-center">
                <h4 class="mb-0">${totals.contracts}</h4>
                <small>Contracts</small>
            </div>
            <div class="col-md-2 col-4 text-center">
                <h4 class="mb-0">${totals.projects}</h4>
                <small>Projects</small>
            </div>
            <div class="col-md-2 col-4 text-center">
                <h4 class="mb-0">${totals.invoices}</h4>
                <small>Invoices</small>
            </div>
            <div class="col-md-2 col-4 text-center">
                <h4 class="mb-0">${totals.estimates}</h4>
                <small>Estimates</small>
            </div>
            <div class="col-md-2 col-4 text-center">
                <h4 class="mb-0">${totals.credit_notes}</h4>
                <small>Credit Notes</small>
            </div>
        `;

        // Hidden inputs for form
        const hiddenInputs = document.getElementById('hiddenInputs');
        hiddenInputs.innerHTML = selectedIds.map(id =>
            `<input type="hidden" name="customer_ids[]" value="${id}">`
        ).join('');

        // Reset confirmation
        confirmCheckbox.checked = false;
        mergeBtn.disabled = true;

        // Show preview panel
        previewPanel.style.display = 'block';
    }

    // Show error
    function showError(message) {
        errorMessage.textContent = message;
        errorPanel.style.display = 'block';
    }

    // Enable merge button when checkbox is checked
    confirmCheckbox.addEventListener('change', function() {
        mergeBtn.disabled = !this.checked;
    });

    // Confirm before submit
    document.getElementById('mergeForm').addEventListener('submit', function(e) {
        if (!confirm('Are you absolutely sure you want to merge these customers? This action cannot be undone.')) {
            e.preventDefault();
        }
    });

    // Trigger change event if there are pre-selected customers
    if (customerSelect.val() && customerSelect.val().length > 0) {
        customerSelect.trigger('change');
    }
});
</script>
@endsection
