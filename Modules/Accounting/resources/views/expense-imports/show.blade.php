@extends('layouts/layoutMaster')

@section('title', 'Review Import - ' . $expenseImport->file_name)

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss'])
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Header Card -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">
                        <span class="badge {{ $expenseImport->status_badge_class }} me-2">{{ $expenseImport->status_display }}</span>
                        {{ $expenseImport->file_name }}
                    </h5>
                    <small class="text-muted">Imported {{ $expenseImport->created_at->diffForHumans() }} by {{ $expenseImport->createdBy->name ?? 'N/A' }}</small>
                </div>
                <div class="d-flex gap-2">
                    @if(in_array($expenseImport->status, ['reviewing', 'previewing']))
                        <a href="{{ route('accounting.expense-imports.preview', $expenseImport) }}" class="btn btn-primary">
                            <i class="ti ti-eye-check me-1"></i>Preview & Execute
                        </a>
                    @endif
                    <a href="{{ route('accounting.expense-imports.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Back
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-6">
                <div class="card bg-label-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-3 bg-primary">
                                <i class="ti ti-list ti-md"></i>
                            </div>
                            <div>
                                <h4 class="mb-0">{{ $expenseImport->total_rows }}</h4>
                                <small>Total Rows</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card bg-label-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-3 bg-success">
                                <i class="ti ti-check ti-md"></i>
                            </div>
                            <div>
                                <h4 class="mb-0">{{ $expenseImport->valid_rows }}</h4>
                                <small>Valid</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card bg-label-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-3 bg-warning">
                                <i class="ti ti-alert-triangle ti-md"></i>
                            </div>
                            <div>
                                <h4 class="mb-0">{{ $expenseImport->warning_rows }}</h4>
                                <small>Warnings</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card bg-label-danger">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-3 bg-danger">
                                <i class="ti ti-x ti-md"></i>
                            </div>
                            <div>
                                <h4 class="mb-0">{{ $expenseImport->error_rows }}</h4>
                                <small>Errors</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Value Mapping Section -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0"><i class="ti ti-link me-2"></i>Value Mapping</h6>
                    <small class="text-muted">Map imported values to existing entities. Changes apply to all matching rows.</small>
                </div>
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" id="showAllMappings">
                    <label class="form-check-label" for="showAllMappings">Show All (including mapped)</label>
                </div>
            </div>
            <div class="card-body">
                @if($mappingCounts['types']['unmapped'] == 0 && $mappingCounts['categories']['unmapped'] == 0 && $mappingCounts['customers']['unmapped'] == 0)
                    <div class="alert alert-success mb-0" id="allMappedAlert">
                        <i class="ti ti-check me-2"></i>All values have been mapped! Toggle "Show All" to review or change mappings.
                    </div>
                @endif
                <div class="row" id="mappingSection">
                    <!-- Expense Type Mapping -->
                    <div class="col-md-6 col-lg-3 mb-3">
                        <label class="form-label">
                            Expense Types
                            <span class="badge bg-label-primary ms-1" id="typesCount">{{ $mappingCounts['types']['unmapped'] }}/{{ $mappingCounts['types']['total'] }}</span>
                        </label>
                        {{-- Unmapped types (shown by default) --}}
                        @foreach($unmappedTypes as $rawType)
                            @if($rawType && !in_array($rawType, ['Income', 'Investment']))
                            <div class="input-group input-group-sm mb-2 mapping-item unmapped-item" data-field="expense_type">
                                <span class="input-group-text text-truncate" style="max-width: 120px;" title="{{ $rawType }}">{{ \Illuminate\Support\Str::limit($rawType, 12) }}</span>
                                <select class="form-select form-select-sm mapping-select" data-field="expense_type" data-raw="{{ $rawType }}">
                                    <option value="">-- Select --</option>
                                    @foreach($expenseTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        @endforeach
                        {{-- Mapped types (hidden by default) --}}
                        @foreach(array_diff($allTypes, $unmappedTypes) as $rawType)
                            @if($rawType && !in_array($rawType, ['Income', 'Investment']))
                            <div class="input-group input-group-sm mb-2 mapping-item mapped-item" data-field="expense_type" style="display: none;">
                                <span class="input-group-text text-truncate bg-success-subtle" style="max-width: 120px;" title="{{ $rawType }}">{{ \Illuminate\Support\Str::limit($rawType, 12) }}</span>
                                <select class="form-select form-select-sm mapping-select" data-field="expense_type" data-raw="{{ $rawType }}">
                                    <option value="">-- Select --</option>
                                    @foreach($expenseTypes as $type)
                                        @php
                                            $isSelected = $expenseImport->rows()->where('expense_type_raw', $rawType)->whereNotNull('expense_type_id')->value('expense_type_id') == $type->id;
                                        @endphp
                                        <option value="{{ $type->id }}" {{ $isSelected ? 'selected' : '' }}>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        @endforeach
                    </div>

                    <!-- Category Mapping -->
                    <div class="col-md-6 col-lg-3 mb-3">
                        <label class="form-label">
                            Categories
                            <span class="badge bg-label-info ms-1" id="categoriesCount">{{ $mappingCounts['categories']['unmapped'] }}/{{ $mappingCounts['categories']['total'] }}</span>
                        </label>
                        {{-- Unmapped categories (shown by default) --}}
                        @foreach($unmappedCategories as $rawCategory)
                            @if($rawCategory)
                            <div class="input-group input-group-sm mb-2 mapping-item unmapped-item" data-field="category">
                                <span class="input-group-text text-truncate" style="max-width: 120px;" title="{{ $rawCategory }}">{{ \Illuminate\Support\Str::limit($rawCategory, 12) }}</span>
                                <select class="form-select form-select-sm mapping-select" data-field="category" data-raw="{{ $rawCategory }}">
                                    <option value="">-- Select --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ str_repeat('│  ', $category->tree_depth) }}{{ $category->tree_depth > 0 ? '├─ ' : '' }}{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        @endforeach
                        {{-- Mapped categories (hidden by default) --}}
                        @foreach(array_diff($allCategories, $unmappedCategories) as $rawCategory)
                            @if($rawCategory)
                            <div class="input-group input-group-sm mb-2 mapping-item mapped-item" data-field="category" style="display: none;">
                                <span class="input-group-text text-truncate bg-success-subtle" style="max-width: 120px;" title="{{ $rawCategory }}">{{ \Illuminate\Support\Str::limit($rawCategory, 12) }}</span>
                                <select class="form-select form-select-sm mapping-select" data-field="category" data-raw="{{ $rawCategory }}">
                                    <option value="">-- Select --</option>
                                    @foreach($categories as $category)
                                        @php
                                            $isSelected = $expenseImport->rows()->where('category_raw', $rawCategory)->whereNotNull('category_id')->value('category_id') == $category->id;
                                        @endphp
                                        <option value="{{ $category->id }}" {{ $isSelected ? 'selected' : '' }}>{{ str_repeat('│  ', $category->tree_depth) }}{{ $category->tree_depth > 0 ? '├─ ' : '' }}{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        @endforeach
                    </div>

                    <!-- Customer Mapping -->
                    <div class="col-md-6 col-lg-3 mb-3">
                        <label class="form-label">
                            Customers
                            <span class="badge bg-label-success ms-1" id="customersCount">{{ $mappingCounts['customers']['unmapped'] }}/{{ $mappingCounts['customers']['total'] }}</span>
                        </label>
                        {{-- Unmapped customers (shown by default) --}}
                        @foreach($unmappedCustomers as $rawCustomer)
                            @if($rawCustomer)
                            <div class="input-group input-group-sm mb-2 mapping-item unmapped-item" data-field="customer">
                                <span class="input-group-text text-truncate" style="max-width: 120px;" title="{{ $rawCustomer }}">{{ \Illuminate\Support\Str::limit($rawCustomer, 12) }}</span>
                                <select class="form-select form-select-sm mapping-select" data-field="customer" data-raw="{{ $rawCustomer }}">
                                    <option value="">-- Create New --</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->display_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        @endforeach
                        {{-- Mapped customers (hidden by default) --}}
                        @foreach(array_diff($allCustomers, $unmappedCustomers) as $rawCustomer)
                            @if($rawCustomer)
                            <div class="input-group input-group-sm mb-2 mapping-item mapped-item" data-field="customer" style="display: none;">
                                <span class="input-group-text text-truncate bg-success-subtle" style="max-width: 120px;" title="{{ $rawCustomer }}">{{ \Illuminate\Support\Str::limit($rawCustomer, 12) }}</span>
                                <select class="form-select form-select-sm mapping-select" data-field="customer" data-raw="{{ $rawCustomer }}">
                                    <option value="">-- Create New --</option>
                                    @foreach($customers as $customer)
                                        @php
                                            $isSelected = $expenseImport->rows()->where('customer_raw', $rawCustomer)->whereNotNull('customer_id')->value('customer_id') == $customer->id;
                                        @endphp
                                        <option value="{{ $customer->id }}" {{ $isSelected ? 'selected' : '' }}>{{ $customer->display_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        @endforeach
                    </div>

                    <!-- Bulk Actions -->
                    <div class="col-md-6 col-lg-3 mb-3">
                        <label class="form-label">Bulk Actions</label>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllRows">
                                <i class="ti ti-checkbox me-1"></i>Select All Visible
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllRows">
                                <i class="ti ti-square me-1"></i>Deselect All
                            </button>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-warning dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" id="bulkActionBtn" disabled>
                                    <i class="ti ti-edit me-1"></i>Bulk Update Selected
                                </button>
                                <div class="dropdown-menu w-100">
                                    <h6 class="dropdown-header">Set Action</h6>
                                    <a class="dropdown-item bulk-action-item" href="#" data-field="action" data-value="skip">Skip Selected</a>
                                    <a class="dropdown-item bulk-action-item" href="#" data-field="action" data-value="create_expense">Create Expense</a>
                                    <a class="dropdown-item bulk-action-item" href="#" data-field="action" data-value="create_income">Create Income</a>
                                    <div class="dropdown-divider"></div>
                                    <h6 class="dropdown-header">Set Income Without Invoice</h6>
                                    <a class="dropdown-item bulk-action-item" href="#" data-field="income_without_invoice" data-value="1">Yes</a>
                                    <a class="dropdown-item bulk-action-item" href="#" data-field="income_without_invoice" data-value="0">No</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="ti ti-table me-2"></i>Import Data</h6>
                <small class="text-muted">Click on a row to edit. Use checkboxes for bulk operations.</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover" id="importDataTable">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAllCheckbox"></th>
                                <th>#</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Item</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Action</th>
                                <th>Invoice</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expenseImport->rows as $row)
                            <tr data-row-id="{{ $row->id }}" class="{{ $row->status === 'error' ? 'table-danger' : ($row->status === 'warning' ? 'table-warning' : '') }}">
                                <td>
                                    <input type="checkbox" class="row-checkbox" value="{{ $row->id }}">
                                </td>
                                <td>{{ $row->row_number }}</td>
                                <td>
                                    <span class="badge {{ $row->status_badge_class }}">{{ ucfirst($row->status) }}</span>
                                    @if($row->validation_messages)
                                        <i class="ti ti-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="{{ collect($row->validation_messages)->pluck('message')->join(', ') }}"></i>
                                    @endif
                                </td>
                                <td>{{ $row->expense_date?->format('d/m/Y') ?? '-' }}</td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $row->item_description }}">
                                        {{ $row->item_description }}
                                    </span>
                                </td>
                                <td>
                                    @if($row->expense_type_id)
                                        <span class="badge bg-label-primary">{{ $row->expenseType->name ?? 'Mapped' }}</span>
                                    @else
                                        <span class="text-muted">{{ $row->expense_type_raw }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($row->category_id)
                                        <span class="badge bg-label-info">{{ $row->category->name ?? 'Mapped' }}</span>
                                    @else
                                        <span class="text-muted">{{ \Illuminate\Support\Str::limit($row->category_raw, 15) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($row->customer_id)
                                        <span class="badge bg-label-success">{{ $row->customer->display_name ?? 'Mapped' }}</span>
                                    @elseif($row->create_customer)
                                        <span class="badge bg-label-warning">Create: {{ \Illuminate\Support\Str::limit($row->customer_raw, 15) }}</span>
                                    @else
                                        <span class="text-muted">{{ \Illuminate\Support\Str::limit($row->customer_raw, 15) }}</span>
                                    @endif
                                </td>
                                <td class="{{ $row->is_income ? 'text-success' : 'text-danger' }}">
                                    {{ $row->is_income ? '+' : '-' }}{{ number_format(abs($row->total_amount), 2) }}
                                </td>
                                <td>
                                    <select class="form-select form-select-sm row-action-select" data-row-id="{{ $row->id }}" style="width: 120px;">
                                        <option value="create_expense" {{ $row->action === 'create_expense' ? 'selected' : '' }}>Expense</option>
                                        <option value="create_income" {{ $row->action === 'create_income' ? 'selected' : '' }}>Income</option>
                                        <option value="link_invoice" {{ $row->action === 'link_invoice' ? 'selected' : '' }}>Link Invoice</option>
                                        <option value="balance_swap" {{ $row->action === 'balance_swap' ? 'selected' : '' }}>Balance Swap</option>
                                        <option value="skip" {{ $row->action === 'skip' ? 'selected' : '' }}>Skip</option>
                                    </select>
                                </td>
                                <td>
                                    @if($row->is_income && $row->action !== 'skip')
                                        @if($row->invoice_id)
                                            <span class="badge bg-success">{{ $row->invoice->invoice_number ?? 'Linked' }}</span>
                                        @elseif($row->income_without_invoice)
                                            <span class="badge bg-secondary">No Invoice</span>
                                        @else
                                            <button type="button" class="btn btn-xs btn-outline-primary link-invoice-btn" data-row-id="{{ $row->id }}" data-customer-id="{{ $row->customer_id }}">
                                                <i class="ti ti-link"></i>
                                            </button>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Link Invoice Modal -->
<div class="modal fade" id="linkInvoiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Link to Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="linkInvoiceRowId">
                <div class="mb-3">
                    <label class="form-label">Search Invoice</label>
                    <select id="invoiceSelect" class="form-select" style="width: 100%;"></select>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="incomeWithoutInvoice">
                    <label class="form-check-label" for="incomeWithoutInvoice">Income without invoice</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveLinkInvoice">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const importId = {{ $expenseImport->id }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Initialize tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));

    // Toggle show all mappings
    const showAllToggle = document.getElementById('showAllMappings');
    const mappedItems = document.querySelectorAll('.mapped-item');
    const allMappedAlert = document.getElementById('allMappedAlert');

    showAllToggle.addEventListener('change', function() {
        mappedItems.forEach(item => {
            item.style.display = this.checked ? 'flex' : 'none';
        });
        if (allMappedAlert) {
            allMappedAlert.style.display = this.checked ? 'none' : 'block';
        }
    });

    // Initialize Select2 for invoice search
    $('#invoiceSelect').select2({
        dropdownParent: $('#linkInvoiceModal'),
        ajax: {
            url: '{{ route("accounting.expense-imports.search-invoices") }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term,
                    customer_id: $('#linkInvoiceModal').data('customerId')
                };
            },
            processResults: function(data) {
                return { results: data.results };
            }
        },
        placeholder: 'Search by invoice number or customer...',
        minimumInputLength: 1,
        allowClear: true
    });

    // Mapping selects
    document.querySelectorAll('.mapping-select').forEach(select => {
        select.addEventListener('change', function() {
            const field = this.dataset.field;
            const rawValue = this.dataset.raw;
            const mappedId = this.value;

            fetch(`/accounting/expense-imports/${importId}/map-value`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    field: field,
                    raw_value: rawValue,
                    mapped_id: mappedId || null,
                    create_new: field === 'customer' && !mappedId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        });
    });

    // Row action selects
    document.querySelectorAll('.row-action-select').forEach(select => {
        select.addEventListener('change', function() {
            const rowId = this.dataset.rowId;
            const action = this.value;

            fetch(`/accounting/expense-imports/rows/${rowId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ action: action })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update row styling
                    const row = document.querySelector(`tr[data-row-id="${rowId}"]`);
                    if (action === 'skip') {
                        row.classList.add('table-secondary');
                    } else {
                        row.classList.remove('table-secondary');
                    }
                }
            });
        });
    });

    // Checkbox handling
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const bulkActionBtn = document.getElementById('bulkActionBtn');

    function updateBulkActionBtn() {
        const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
        bulkActionBtn.disabled = checkedCount === 0;
        bulkActionBtn.textContent = checkedCount > 0 ? `Bulk Update (${checkedCount})` : 'Bulk Update Selected';
    }

    selectAllCheckbox.addEventListener('change', function() {
        rowCheckboxes.forEach(cb => cb.checked = this.checked);
        updateBulkActionBtn();
    });

    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkActionBtn);
    });

    document.getElementById('selectAllRows').addEventListener('click', function() {
        rowCheckboxes.forEach(cb => cb.checked = true);
        selectAllCheckbox.checked = true;
        updateBulkActionBtn();
    });

    document.getElementById('deselectAllRows').addEventListener('click', function() {
        rowCheckboxes.forEach(cb => cb.checked = false);
        selectAllCheckbox.checked = false;
        updateBulkActionBtn();
    });

    // Bulk actions
    document.querySelectorAll('.bulk-action-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const field = this.dataset.field;
            const value = this.dataset.value;
            const selectedIds = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);

            if (selectedIds.length === 0) return;

            fetch(`/accounting/expense-imports/${importId}/bulk-update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    row_ids: selectedIds,
                    field: field,
                    value: value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        });
    });

    // Link invoice buttons
    document.querySelectorAll('.link-invoice-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const rowId = this.dataset.rowId;
            const customerId = this.dataset.customerId;

            document.getElementById('linkInvoiceRowId').value = rowId;
            $('#linkInvoiceModal').data('customerId', customerId);
            $('#invoiceSelect').val(null).trigger('change');
            document.getElementById('incomeWithoutInvoice').checked = false;

            new bootstrap.Modal(document.getElementById('linkInvoiceModal')).show();
        });
    });

    // Save invoice link
    document.getElementById('saveLinkInvoice').addEventListener('click', function() {
        const rowId = document.getElementById('linkInvoiceRowId').value;
        const invoiceId = $('#invoiceSelect').val();
        const withoutInvoice = document.getElementById('incomeWithoutInvoice').checked;

        fetch(`/accounting/expense-imports/rows/${rowId}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                invoice_id: invoiceId || null,
                income_without_invoice: withoutInvoice,
                action: invoiceId ? 'link_invoice' : (withoutInvoice ? 'create_income' : null)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('linkInvoiceModal')).hide();
                location.reload();
            }
        });
    });
});
</script>
@endsection
