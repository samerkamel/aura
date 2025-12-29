@extends('layouts/layoutMaster')

@section('title', 'Edit Credit Note: ' . $creditNote->credit_note_number)

@section('content')
<div class="row">
    <div class="col-12">
        <form action="{{ route('accounting.credit-notes.update', $creditNote) }}" method="POST" id="creditNoteForm">
            @csrf
            @method('PUT')

            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <!-- Client Information -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">Edit Credit Note: {{ $creditNote->credit_note_number }}</h5>
                                <small class="text-muted">Update credit note details</small>
                            </div>
                            <a href="{{ route('accounting.credit-notes.show', $creditNote) }}" class="btn btn-outline-secondary">
                                <i class="ti ti-arrow-left me-1"></i>Back to Credit Note
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                    <select class="form-select @error('customer_id') is-invalid @enderror"
                                            id="customer_id" name="customer_id" required>
                                        <option value="">Select Customer</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}"
                                                    data-name="{{ $customer->display_name }}"
                                                    data-email="{{ $customer->email }}"
                                                    data-address="{{ $customer->address }}"
                                                {{ old('customer_id', $creditNote->customer_id) == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->display_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="invoice_id" class="form-label">Related Invoice</label>
                                    <select class="form-select @error('invoice_id') is-invalid @enderror"
                                            id="invoice_id" name="invoice_id">
                                        <option value="">No Related Invoice</option>
                                        @foreach($invoices as $invoice)
                                            <option value="{{ $invoice->id }}"
                                                {{ old('invoice_id', $creditNote->invoice_id) == $invoice->id ? 'selected' : '' }}>
                                                {{ $invoice->invoice_number }} - {{ $invoice->customer->name ?? 'N/A' }} (EGP {{ number_format($invoice->total_amount, 2) }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('invoice_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-12">
                                    <label for="client_name" class="form-label">Client Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('client_name') is-invalid @enderror"
                                           id="client_name" name="client_name"
                                           value="{{ old('client_name', $creditNote->client_name) }}"
                                           placeholder="Enter client/company name" required>
                                    @error('client_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="client_email" class="form-label">Client Email</label>
                                    <input type="email" class="form-control @error('client_email') is-invalid @enderror"
                                           id="client_email" name="client_email"
                                           value="{{ old('client_email', $creditNote->client_email) }}"
                                           placeholder="client@example.com">
                                    @error('client_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="client_address" class="form-label">Client Address</label>
                                    <input type="text" class="form-control @error('client_address') is-invalid @enderror"
                                           id="client_address" name="client_address"
                                           value="{{ old('client_address', $creditNote->client_address) }}"
                                           placeholder="Enter client address">
                                    @error('client_address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Credit Note Details -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Credit Note Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="credit_note_date" class="form-label">Credit Note Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('credit_note_date') is-invalid @enderror"
                                           id="credit_note_date" name="credit_note_date"
                                           value="{{ old('credit_note_date', $creditNote->credit_note_date->format('Y-m-d')) }}" required>
                                    @error('credit_note_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="reference" class="form-label">Reference</label>
                                    <input type="text" class="form-control @error('reference') is-invalid @enderror"
                                           id="reference" name="reference"
                                           value="{{ old('reference', $creditNote->reference) }}"
                                           placeholder="Reference number">
                                    @error('reference')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="tax_rate" class="form-label">Tax Rate <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control @error('tax_rate') is-invalid @enderror"
                                               id="tax_rate" name="tax_rate"
                                               value="{{ old('tax_rate', $creditNote->tax_rate) }}"
                                               step="0.01" min="0" max="100" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                    @error('tax_rate')
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
                                <i class="ti ti-plus me-1"></i>Add Item
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
                                        <!-- Existing items will be loaded here -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="5" class="text-end fw-medium">Subtotal:</td>
                                            <td class="fw-medium" id="subtotalDisplay">EGP 0.00</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-end">Tax (<span id="taxRateDisplay">{{ $creditNote->tax_rate }}</span>%):</td>
                                            <td id="taxDisplay">EGP 0.00</td>
                                            <td></td>
                                        </tr>
                                        <tr class="table-warning">
                                            <td colspan="5" class="text-end fw-bold">Credit Total:</td>
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
                                              placeholder="Notes to appear on the credit note">{{ old('notes', $creditNote->notes) }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="terms" class="form-label">Terms</label>
                                    <textarea class="form-control @error('terms') is-invalid @enderror"
                                              id="terms" name="terms" rows="2"
                                              placeholder="Terms and conditions">{{ old('terms', $creditNote->terms) }}</textarea>
                                    @error('terms')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="internal_notes" class="form-label">Internal Notes</label>
                                    <textarea class="form-control @error('internal_notes') is-invalid @enderror"
                                              id="internal_notes" name="internal_notes" rows="2"
                                              placeholder="Internal notes (not shown to client)">{{ old('internal_notes', $creditNote->internal_notes) }}</textarea>
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
                                <i class="ti ti-device-floppy me-1"></i>Update Credit Note
                            </button>
                            <a href="{{ route('accounting.credit-notes.show', $creditNote) }}" class="btn btn-outline-secondary w-100">
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
                                <span class="text-muted">Tax:</span>
                                <span id="sidebarTax">EGP 0.00</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">Credit Total:</span>
                                <span class="fw-bold text-warning" id="sidebarTotal">EGP 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let itemIndex = 0;

// Existing items data
const existingItems = @json($creditNote->items);

function addItem(data = null) {
    const tbody = document.getElementById('itemsBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <input type="text" class="form-control form-control-sm" name="items[${itemIndex}][description]"
                   placeholder="Item description" value="${data ? data.description : ''}" required>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm" name="items[${itemIndex}][details]"
                   placeholder="Details" value="${data ? (data.details || '') : ''}">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm item-qty" name="items[${itemIndex}][quantity]"
                   value="${data ? data.quantity : 1}" step="0.01" min="0.01" required onchange="calculateTotals()">
        </td>
        <td>
            <select class="form-select form-select-sm" name="items[${itemIndex}][unit]">
                <option value="unit" ${data && data.unit === 'unit' ? 'selected' : ''}>Unit</option>
                <option value="hour" ${data && data.unit === 'hour' ? 'selected' : ''}>Hour</option>
                <option value="day" ${data && data.unit === 'day' ? 'selected' : ''}>Day</option>
                <option value="month" ${data && data.unit === 'month' ? 'selected' : ''}>Month</option>
                <option value="piece" ${data && data.unit === 'piece' ? 'selected' : ''}>Piece</option>
                <option value="service" ${data && data.unit === 'service' ? 'selected' : ''}>Service</option>
            </select>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm item-price" name="items[${itemIndex}][unit_price]"
                   value="${data ? data.unit_price : 0}" step="0.01" min="0" required onchange="calculateTotals()">
        </td>
        <td class="item-amount">EGP 0.00</td>
        <td>
            <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="removeItem(this)">
                <i class="ti ti-trash"></i>
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

    const taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
    const tax = subtotal * (taxRate / 100);
    const total = subtotal + tax;

    // Update displays
    document.getElementById('subtotalDisplay').textContent = 'EGP ' + subtotal.toFixed(2);
    document.getElementById('taxDisplay').textContent = 'EGP ' + tax.toFixed(2);
    document.getElementById('totalDisplay').textContent = 'EGP ' + total.toFixed(2);

    // Update sidebar
    document.getElementById('itemCountDisplay').textContent = itemCount;
    document.getElementById('sidebarSubtotal').textContent = 'EGP ' + subtotal.toFixed(2);
    document.getElementById('sidebarTax').textContent = 'EGP ' + tax.toFixed(2);
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

    // Tax rate change
    document.getElementById('tax_rate').addEventListener('change', function() {
        document.getElementById('taxRateDisplay').textContent = this.value;
        calculateTotals();
    });

    // Load existing items
    if (existingItems.length > 0) {
        existingItems.forEach(item => addItem(item));
    } else {
        addItem();
    }
});
</script>
@endsection
