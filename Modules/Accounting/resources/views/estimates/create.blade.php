@extends('layouts/layoutMaster')

@section('title', 'Create Estimate')

@section('content')
<div class="row">
    <div class="col-12">
        <form action="{{ route('accounting.estimates.store') }}" method="POST" id="estimateForm">
            @csrf

            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <!-- Client Information -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">Create New Estimate</h5>
                                <small class="text-muted">Create a quotation for your client</small>
                            </div>
                            <a href="{{ route('accounting.estimates.index') }}" class="btn btn-outline-secondary">
                                <i class="ti tabler-arrow-left me-1"></i>Back to Estimates
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="customer_id" class="form-label">Customer</label>
                                    <select class="form-select @error('customer_id') is-invalid @enderror"
                                            id="customer_id" name="customer_id">
                                        <option value="">Select Customer (Optional)</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}"
                                                    data-name="{{ $customer->display_name }}"
                                                    data-email="{{ $customer->email }}"
                                                    data-address="{{ $customer->address }}"
                                                {{ old('customer_id', $selectedProject->customer_id ?? '') == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->display_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="project_id" class="form-label">Project</label>
                                    <select class="form-select @error('project_id') is-invalid @enderror"
                                            id="project_id" name="project_id">
                                        <option value="">Select Project (Optional)</option>
                                        @foreach($projects as $project)
                                            <option value="{{ $project->id }}"
                                                {{ old('project_id', $selectedProjectId) == $project->id ? 'selected' : '' }}>
                                                [{{ $project->code }}] {{ $project->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('project_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-12">
                                    <label for="client_name" class="form-label">Client Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('client_name') is-invalid @enderror"
                                           id="client_name" name="client_name"
                                           value="{{ old('client_name', $selectedProject->customer->display_name ?? '') }}"
                                           placeholder="Enter client/company name" required>
                                    @error('client_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="client_email" class="form-label">Client Email</label>
                                    <input type="email" class="form-control @error('client_email') is-invalid @enderror"
                                           id="client_email" name="client_email"
                                           value="{{ old('client_email', $selectedProject->customer->email ?? '') }}"
                                           placeholder="client@example.com">
                                    @error('client_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="client_address" class="form-label">Client Address</label>
                                    <input type="text" class="form-control @error('client_address') is-invalid @enderror"
                                           id="client_address" name="client_address"
                                           value="{{ old('client_address', $selectedProject->customer->address ?? '') }}"
                                           placeholder="Enter client address">
                                    @error('client_address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estimate Details -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Estimate Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror"
                                           id="title" name="title" value="{{ old('title') }}"
                                           placeholder="Estimate title/subject" required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                              id="description" name="description" rows="3"
                                              placeholder="Brief description of this estimate">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="issue_date" class="form-label">Issue Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('issue_date') is-invalid @enderror"
                                           id="issue_date" name="issue_date"
                                           value="{{ old('issue_date', date('Y-m-d')) }}" required>
                                    @error('issue_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="valid_until" class="form-label">Valid Until</label>
                                    <input type="date" class="form-control @error('valid_until') is-invalid @enderror"
                                           id="valid_until" name="valid_until"
                                           value="{{ old('valid_until', date('Y-m-d', strtotime('+30 days'))) }}">
                                    @error('valid_until')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="vat_rate" class="form-label">VAT Rate <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control @error('vat_rate') is-invalid @enderror"
                                               id="vat_rate" name="vat_rate"
                                               value="{{ old('vat_rate', $companySettings->default_vat_rate) }}"
                                               step="0.01" min="0" max="100" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                    @error('vat_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Line Items -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">Line Items</h6>
                            <button type="button" class="btn btn-sm btn-primary" onclick="addItem()">
                                <i class="ti tabler-plus me-1"></i>Add Item
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="itemsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 30%;">Description <span class="text-danger">*</span></th>
                                            <th style="width: 15%;">Details</th>
                                            <th style="width: 10%;">Qty <span class="text-danger">*</span></th>
                                            <th style="width: 10%;">Unit</th>
                                            <th style="width: 15%;">Unit Price <span class="text-danger">*</span></th>
                                            <th style="width: 15%;">Amount</th>
                                            <th style="width: 5%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsBody">
                                        <!-- Items will be added here dynamically -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="5" class="text-end fw-medium">Subtotal:</td>
                                            <td class="fw-medium" id="subtotalDisplay">EGP 0.00</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-end">VAT (<span id="vatRateDisplay">{{ $companySettings->default_vat_rate }}</span>%):</td>
                                            <td id="vatDisplay">EGP 0.00</td>
                                            <td></td>
                                        </tr>
                                        <tr class="table-primary">
                                            <td colspan="5" class="text-end fw-bold">Total:</td>
                                            <td class="fw-bold" id="totalDisplay">EGP 0.00</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            @error('items')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Notes & Terms</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="notes" class="form-label">Client Notes</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror"
                                              id="notes" name="notes" rows="3"
                                              placeholder="Notes and terms to appear on the estimate">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="internal_notes" class="form-label">Internal Notes</label>
                                    <textarea class="form-control @error('internal_notes') is-invalid @enderror"
                                              id="internal_notes" name="internal_notes" rows="2"
                                              placeholder="Internal notes (not shown to client)">{{ old('internal_notes') }}</textarea>
                                    @error('internal_notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Actions -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                <i class="ti tabler-device-floppy me-1"></i>Save Estimate
                            </button>
                            <a href="{{ route('accounting.estimates.index') }}" class="btn btn-outline-secondary w-100">
                                Cancel
                            </a>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Items:</span>
                                <span id="itemCountDisplay">0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal:</span>
                                <span id="sidebarSubtotal">EGP 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">VAT:</span>
                                <span id="sidebarVat">EGP 0.00</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">Total:</span>
                                <span class="fw-bold text-primary" id="sidebarTotal">EGP 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Define global variables and functions BEFORE DOMContentLoaded
let itemIndex = 0;

function addItem() {
    const tbody = document.getElementById('itemsBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <input type="text" class="form-control form-control-sm" name="items[${itemIndex}][description]"
                   placeholder="Item description" required>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm" name="items[${itemIndex}][details]"
                   placeholder="Details">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm item-qty" name="items[${itemIndex}][quantity]"
                   value="1" step="0.01" min="0.01" required onchange="calculateTotals()">
        </td>
        <td>
            <select class="form-select form-select-sm" name="items[${itemIndex}][unit]">
                <option value="unit">Unit</option>
                <option value="hour">Hour</option>
                <option value="day">Day</option>
                <option value="month">Month</option>
                <option value="piece">Piece</option>
                <option value="service">Service</option>
            </select>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm item-price" name="items[${itemIndex}][unit_price]"
                   value="0" step="0.01" min="0" required onchange="calculateTotals()">
        </td>
        <td class="item-amount">EGP 0.00</td>
        <td>
            <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="removeItem(this)">
                <i class="ti tabler-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(row);
    itemIndex++;
    calculateTotals();
}

function removeItem(btn) {
    const tbody = document.getElementById('itemsBody');
    if (tbody.children.length > 1) {
        btn.closest('tr').remove();
        calculateTotals();
    }
}

function calculateTotals() {
    const rows = document.querySelectorAll('#itemsBody tr');
    let subtotal = 0;
    let itemCount = 0;

    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        const amount = qty * price;
        row.querySelector('.item-amount').textContent = 'EGP ' + amount.toFixed(2);
        subtotal += amount;
        itemCount++;
    });

    const vatRate = parseFloat(document.getElementById('vat_rate').value) || 0;
    const vat = subtotal * (vatRate / 100);
    const total = subtotal + vat;

    // Update displays
    document.getElementById('subtotalDisplay').textContent = 'EGP ' + subtotal.toFixed(2);
    document.getElementById('vatDisplay').textContent = 'EGP ' + vat.toFixed(2);
    document.getElementById('totalDisplay').textContent = 'EGP ' + total.toFixed(2);

    // Update sidebar
    document.getElementById('itemCountDisplay').textContent = itemCount;
    document.getElementById('sidebarSubtotal').textContent = 'EGP ' + subtotal.toFixed(2);
    document.getElementById('sidebarVat').textContent = 'EGP ' + vat.toFixed(2);
    document.getElementById('sidebarTotal').textContent = 'EGP ' + total.toFixed(2);
}

document.addEventListener('DOMContentLoaded', function() {
    // Customer selection autofill
    document.getElementById('customer_id').addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        if (selected.value) {
            document.getElementById('client_name').value = selected.dataset.name || '';
            document.getElementById('client_email').value = selected.dataset.email || '';
            document.getElementById('client_address').value = selected.dataset.address || '';
        }
    });

    // VAT rate change
    document.getElementById('vat_rate').addEventListener('change', function() {
        document.getElementById('vatRateDisplay').textContent = this.value;
        calculateTotals();
    });

    // Add initial item
    addItem();
});
</script>
@endsection
