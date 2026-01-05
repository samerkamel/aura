@extends('layouts/layoutMaster')

@section('title', 'Create Credit Note')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <form action="{{ route('accounting.credit-notes.store') }}" method="POST" id="creditNoteForm">
            @csrf

            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <!-- Client Information -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">Create New Credit Note</h5>
                                <small class="text-muted">Issue a credit note for your customer</small>
                            </div>
                            <a href="{{ route('accounting.credit-notes.index') }}" class="btn btn-outline-secondary">
                                <i class="ti ti-arrow-left me-1"></i>Back to Credit Notes
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                    <select class="form-select select2-customer @error('customer_id') is-invalid @enderror"
                                            id="customer_id" name="customer_id" required
                                            data-selected="{{ old('customer_id', $selectedInvoice->customer_id ?? '') }}">
                                        <option value="">Search or select customer...</option>
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
                                                    data-customer="{{ $invoice->customer_id }}"
                                                {{ old('invoice_id', $selectedInvoiceId) == $invoice->id ? 'selected' : '' }}>
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
                                           value="{{ old('client_name', $selectedInvoice->customer->display_name ?? '') }}"
                                           placeholder="Enter client/company name" required>
                                    @error('client_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="client_email" class="form-label">Client Email</label>
                                    <input type="email" class="form-control @error('client_email') is-invalid @enderror"
                                           id="client_email" name="client_email"
                                           value="{{ old('client_email', $selectedInvoice->customer->email ?? '') }}"
                                           placeholder="client@example.com">
                                    @error('client_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="client_address" class="form-label">Client Address</label>
                                    <input type="text" class="form-control @error('client_address') is-invalid @enderror"
                                           id="client_address" name="client_address"
                                           value="{{ old('client_address', $selectedInvoice->customer->address ?? '') }}"
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
                                    <label for="credit_note_number_display" class="form-label">Credit Note #</label>
                                    <input type="text" class="form-control bg-light" id="credit_note_number_display" readonly>
                                    <small class="text-muted">Auto-generated based on date</small>
                                </div>

                                <div class="col-md-4">
                                    <label for="credit_note_date" class="form-label">Credit Note Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('credit_note_date') is-invalid @enderror"
                                           id="credit_note_date" name="credit_note_date"
                                           value="{{ old('credit_note_date', date('Y-m-d')) }}" required>
                                    @error('credit_note_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="reference" class="form-label">Reference</label>
                                    <input type="text" class="form-control @error('reference') is-invalid @enderror"
                                           id="reference" name="reference"
                                           value="{{ old('reference') }}"
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
                                               value="{{ old('tax_rate', $companySettings->default_vat_rate ?? 14) }}"
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
                                        <!-- Items will be added here dynamically -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="5" class="text-end fw-medium">Subtotal:</td>
                                            <td class="fw-medium" id="subtotalDisplay">EGP 0.00</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-end">Tax (<span id="taxRateDisplay">{{ $companySettings->default_vat_rate ?? 14 }}</span>%):</td>
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
                                              placeholder="Notes to appear on the credit note">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="terms" class="form-label">Terms</label>
                                    <textarea class="form-control @error('terms') is-invalid @enderror"
                                              id="terms" name="terms" rows="2"
                                              placeholder="Terms and conditions">{{ old('terms') }}</textarea>
                                    @error('terms')
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
                                <i class="ti ti-device-floppy me-1"></i>Save Credit Note
                            </button>
                            <a href="{{ route('accounting.credit-notes.index') }}" class="btn btn-outline-secondary w-100">
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

                    @if($selectedInvoice)
                    <!-- Related Invoice Info -->
                    <div class="card mt-4">
                        <div class="card-header bg-label-info">
                            <h6 class="card-title mb-0">Related Invoice</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Invoice:</strong> {{ $selectedInvoice->invoice_number }}</p>
                            <p class="mb-1"><strong>Amount:</strong> EGP {{ number_format($selectedInvoice->total_amount, 2) }}</p>
                            <p class="mb-1"><strong>Paid:</strong> EGP {{ number_format($selectedInvoice->paid_amount, 2) }}</p>
                            <p class="mb-0"><strong>Remaining:</strong> EGP {{ number_format($selectedInvoice->remaining_amount, 2) }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<script>
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
    // Initialize Select2 for customer dropdown
    function initCustomerSelect2() {
        if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
            setTimeout(initCustomerSelect2, 50);
            return;
        }

        const $select = $('.select2-customer');
        const preSelected = $select.data('selected');

        $select.select2({
            placeholder: 'Search or select customer...',
            allowClear: true,
            ajax: {
                url: '{{ route("administration.customers.api.index") }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { search: params.term || '' };
                },
                processResults: function(data) {
                    return {
                        results: data.customers ? data.customers.map(function(customer) {
                            return {
                                id: customer.id,
                                text: customer.text,
                                customerData: customer
                            };
                        }) : []
                    };
                },
                cache: true
            },
            minimumInputLength: 0
        });

        // Pre-select customer if provided
        if (preSelected) {
            $.ajax({
                url: '{{ route("administration.customers.api.index") }}',
                dataType: 'json'
            }).then(function(data) {
                if (data.customers) {
                    const customer = data.customers.find(c => c.id == preSelected);
                    if (customer) {
                        const option = new Option(customer.text, customer.id, true, true);
                        $select.append(option).trigger('change');
                    }
                }
            });
        }

        // Customer selection autofill
        $select.on('select2:select', function(e) {
            const customerData = e.params.data.customerData;
            if (customerData) {
                document.getElementById('client_name').value = customerData.display_name || customerData.name || '';
                document.getElementById('client_email').value = customerData.email || '';
                document.getElementById('client_address').value = customerData.address || '';
            }
        });

        $select.on('select2:clear', function() {
            document.getElementById('client_name').value = '';
            document.getElementById('client_email').value = '';
            document.getElementById('client_address').value = '';
        });
    }

    initCustomerSelect2();

    // Tax rate change
    document.getElementById('tax_rate').addEventListener('change', function() {
        document.getElementById('taxRateDisplay').textContent = this.value;
        calculateTotals();
    });

    // Credit note number auto-generation based on date
    const creditNoteDateField = document.getElementById('credit_note_date');
    const creditNoteNumberField = document.getElementById('credit_note_number_display');

    function loadNextCreditNoteNumber(creditNoteDate = null) {
        let url = '{{ route("accounting.credit-notes.next-number") }}';
        if (creditNoteDate) {
            url += '?credit_note_date=' + encodeURIComponent(creditNoteDate);
        }
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.next_number) {
                    creditNoteNumberField.value = data.next_number;
                }
            })
            .catch(error => console.error('Error fetching credit note number:', error));
    }

    // Load initial credit note number based on current date
    loadNextCreditNoteNumber(creditNoteDateField.value);

    // Update credit note number when date changes
    creditNoteDateField.addEventListener('change', function() {
        loadNextCreditNoteNumber(this.value);
    });

    // Add initial item
    addItem();
});
</script>
@endsection
