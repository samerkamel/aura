@extends('layouts/layoutMaster')

@section('title', 'Edit Invoice')

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
                    <h5 class="mb-0">Edit Invoice #{{ $invoice->invoice_number }}</h5>
                    <small class="text-muted">Modify invoice details</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('invoicing.invoices.show', $invoice) }}" class="btn btn-outline-info">
                        <i class="ti ti-eye me-1"></i>View Invoice
                    </a>
                    <a href="{{ route('invoicing.invoices.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Back to Invoices
                    </a>
                </div>
            </div>

            @if($invoice->status !== 'draft')
                <div class="alert alert-warning mx-4 mt-3" role="alert">
                    <i class="ti ti-alert-triangle me-2"></i>
                    This invoice has status "{{ $invoice->status_display }}". Changes may affect accounting records.
                </div>
            @endif

            <form action="{{ route('invoicing.invoices.update', $invoice) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <!-- Customer Information -->
                        <div class="col-md-6">
                            <h6 class="mb-3">Customer Information</h6>

                            <div class="mb-3">
                                <label class="form-label required">Customer</label>
                                <select name="customer_id" id="customer_id" class="form-select select2-customer @error('customer_id') is-invalid @enderror" required
                                        data-selected="{{ old('customer_id', $invoice->customer_id) }}">
                                    <option value="">Search or select customer...</option>
                                </select>
                                @error('customer_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Project</label>
                                <select name="project_id" class="form-select @error('project_id') is-invalid @enderror">
                                    <option value="">No Project</option>
                                    @foreach($projects as $project)
                                        <option value="{{ $project->id }}" {{ (old('project_id', $invoice->project_id) == $project->id) ? 'selected' : '' }}>
                                            [{{ $project->code }}] {{ $project->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('project_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Link this invoice to a project</small>
                            </div>
                        </div>

                        <!-- Invoice Information -->
                        <div class="col-md-6">
                            <h6 class="mb-3">Invoice Information</h6>

                            <div class="mb-3">
                                <label class="form-label">Invoice Number</label>
                                <input type="text" class="form-control" value="{{ $invoice->invoice_number }}" readonly>
                                <small class="text-muted">Invoice number cannot be changed</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label required">Invoice Date</label>
                                        <input type="date" name="invoice_date" class="form-control @error('invoice_date') is-invalid @enderror"
                                               value="{{ old('invoice_date', $invoice->invoice_date->format('Y-m-d')) }}" required>
                                        @error('invoice_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Due Date</label>
                                        <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror"
                                               value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}">
                                        @error('due_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Currency</label>
                                        <select name="currency" id="currency" class="form-select @error('currency') is-invalid @enderror" onchange="updateCurrencyDisplay()">
                                            @foreach(\Modules\Invoicing\Models\Invoice::CURRENCIES as $code => $info)
                                                <option value="{{ $code }}" {{ (old('currency', $invoice->currency ?? 'EGP') == $code) ? 'selected' : '' }}>
                                                    {{ $code }} - {{ $info['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('currency')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Exchange Rate <small class="text-muted">(to EGP)</small></label>
                                        <input type="number" name="exchange_rate" id="exchange_rate" class="form-control @error('exchange_rate') is-invalid @enderror"
                                               value="{{ old('exchange_rate', $invoice->exchange_rate ?? 1) }}" min="0.000001" step="0.000001" onchange="calculateGrandTotal()">
                                        @error('exchange_rate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">1 <span id="currency-code">{{ $invoice->currency ?? 'EGP' }}</span> = <span id="rate-display">{{ $invoice->exchange_rate ?? 1 }}</span> EGP</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Invoice Items -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Invoice Items</h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addInvoiceItem()">
                                <i class="ti ti-plus me-1"></i>Add Item
                            </button>
                        </div>

                        <div id="invoice-items">
                            @foreach($invoice->items as $index => $item)
                            <div class="invoice-item border rounded p-3 mb-3">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label required">Description</label>
                                        <input type="text" name="items[{{ $index }}][description]" class="form-control @error('items.'.$index.'.description') is-invalid @enderror"
                                               value="{{ old('items.'.$index.'.description', $item->description) }}" required>
                                        @error('items.'.$index.'.description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Project</label>
                                        <select name="items[{{ $index }}][project_id]" class="form-select form-select-sm item-project">
                                            <option value="">No Project</option>
                                            @foreach($projects as $project)
                                                <option value="{{ $project->id }}" {{ (old('items.'.$index.'.project_id', $item->project_id) == $project->id) ? 'selected' : '' }}>
                                                    {{ $project->code ?? $project->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label required">Qty</label>
                                        <input type="number" name="items[{{ $index }}][quantity]" class="form-control item-quantity @error('items.'.$index.'.quantity') is-invalid @enderror"
                                               value="{{ old('items.'.$index.'.quantity', $item->quantity) }}" min="0.01" step="0.01" required onchange="calculateItemTotal(this)">
                                        @error('items.'.$index.'.quantity')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label required">Unit Price</label>
                                        <input type="number" name="items[{{ $index }}][unit_price]" class="form-control item-price @error('items.'.$index.'.unit_price') is-invalid @enderror"
                                               value="{{ old('items.'.$index.'.unit_price', $item->unit_price) }}" min="0.01" step="0.01" required onchange="calculateItemTotal(this)">
                                        @error('items.'.$index.'.unit_price')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Tax %</label>
                                        <input type="number" name="items[{{ $index }}][tax_rate]" class="form-control item-tax @error('items.'.$index.'.tax_rate') is-invalid @enderror"
                                               value="{{ old('items.'.$index.'.tax_rate', $item->tax_rate) }}" min="0" max="100" step="0.01" onchange="calculateItemTotal(this)">
                                        @error('items.'.$index.'.tax_rate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Total</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control item-total" readonly value="{{ number_format($item->total, 2) }}">
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeInvoiceItem(this)" title="Remove Item">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Invoice Totals -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3"
                                          placeholder="Additional notes or terms...">{{ old('notes', $invoice->notes) }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Invoice Summary</h6>
                                    <div class="d-flex justify-content-between">
                                        <span>Subtotal:</span>
                                        <span id="subtotal">{{ number_format($invoice->subtotal, 2) }} <span class="currency-display">{{ $invoice->currency ?? 'EGP' }}</span></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Tax:</span>
                                        <span id="total-tax">{{ number_format($invoice->tax_amount, 2) }} <span class="currency-display">{{ $invoice->currency ?? 'EGP' }}</span></span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>Total:</span>
                                        <span id="grand-total">{{ number_format($invoice->total_amount, 2) }} <span class="currency-display">{{ $invoice->currency ?? 'EGP' }}</span></span>
                                    </div>
                                    <div id="egp-equivalent" class="mt-2 text-muted small" style="{{ ($invoice->currency ?? 'EGP') === 'EGP' ? 'display:none;' : '' }}">
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <span>Total in EGP:</span>
                                            <span id="total-egp">{{ number_format(($invoice->total_in_base ?? $invoice->total_amount), 2) }} EGP</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('invoicing.invoices.show', $invoice) }}" class="btn btn-outline-secondary">
                        <i class="ti ti-x me-1"></i>Cancel
                    </a>
                    <div>
                        <button type="submit" name="action" value="draft" class="btn btn-outline-primary me-2">
                            <i class="ti ti-device-floppy me-1"></i>Save Changes
                        </button>
                        @if($invoice->status === 'draft')
                            <button type="submit" name="action" value="send" class="btn btn-primary">
                                <i class="ti ti-send me-1"></i>Save & Send
                            </button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
let itemIndex = {{ $invoice->items->count() }};

// Projects data for dynamic item creation
const projectsData = @json($projects->map(fn($p) => ['id' => $p->id, 'code' => $p->code, 'name' => $p->name]));

function getProjectOptions() {
    let options = '<option value="">No Project</option>';
    projectsData.forEach(p => {
        options += `<option value="${p.id}">${p.code || p.name}</option>`;
    });
    return options;
}

function addInvoiceItem() {
    const itemsContainer = document.getElementById('invoice-items');
    const newItem = document.createElement('div');
    newItem.className = 'invoice-item border rounded p-3 mb-3';
    newItem.innerHTML = `
        <div class="row">
            <div class="col-md-3">
                <label class="form-label required">Description</label>
                <input type="text" name="items[${itemIndex}][description]" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Project</label>
                <select name="items[${itemIndex}][project_id]" class="form-select form-select-sm item-project">
                    ${getProjectOptions()}
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label required">Qty</label>
                <input type="number" name="items[${itemIndex}][quantity]" class="form-control item-quantity"
                       value="1" min="0.01" step="0.01" required onchange="calculateItemTotal(this)">
            </div>
            <div class="col-md-2">
                <label class="form-label required">Unit Price</label>
                <input type="number" name="items[${itemIndex}][unit_price]" class="form-control item-price"
                       min="0.01" step="0.01" required onchange="calculateItemTotal(this)">
            </div>
            <div class="col-md-2">
                <label class="form-label">Tax %</label>
                <input type="number" name="items[${itemIndex}][tax_rate]" class="form-control item-tax"
                       value="0" min="0" max="100" step="0.01" onchange="calculateItemTotal(this)">
            </div>
            <div class="col-md-2">
                <label class="form-label">Total</label>
                <div class="input-group">
                    <input type="text" class="form-control item-total" readonly value="0.00">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeInvoiceItem(this)" title="Remove Item">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    itemsContainer.appendChild(newItem);
    itemIndex++;
}

function removeInvoiceItem(button) {
    const item = button.closest('.invoice-item');
    if (document.querySelectorAll('.invoice-item').length > 1) {
        item.remove();
        calculateGrandTotal();
    } else {
        alert('You must have at least one item on the invoice.');
    }
}

function calculateItemTotal(input) {
    const item = input.closest('.invoice-item');
    const quantity = parseFloat(item.querySelector('.item-quantity').value) || 0;
    const price = parseFloat(item.querySelector('.item-price').value) || 0;
    const taxRate = parseFloat(item.querySelector('.item-tax').value) || 0;

    const subtotal = quantity * price;
    const tax = subtotal * (taxRate / 100);
    const total = subtotal + tax;

    item.querySelector('.item-total').value = total.toFixed(2);
    calculateGrandTotal();
}

function calculateGrandTotal() {
    let subtotal = 0;
    let totalTax = 0;

    document.querySelectorAll('.invoice-item').forEach(item => {
        const quantity = parseFloat(item.querySelector('.item-quantity').value) || 0;
        const price = parseFloat(item.querySelector('.item-price').value) || 0;
        const taxRate = parseFloat(item.querySelector('.item-tax').value) || 0;

        const itemSubtotal = quantity * price;
        const itemTax = itemSubtotal * (taxRate / 100);

        subtotal += itemSubtotal;
        totalTax += itemTax;
    });

    const grandTotal = subtotal + totalTax;
    const currency = document.getElementById('currency').value;
    const exchangeRate = parseFloat(document.getElementById('exchange_rate').value) || 1;

    document.getElementById('subtotal').innerHTML = subtotal.toFixed(2) + ' <span class="currency-display">' + currency + '</span>';
    document.getElementById('total-tax').innerHTML = totalTax.toFixed(2) + ' <span class="currency-display">' + currency + '</span>';
    document.getElementById('grand-total').innerHTML = grandTotal.toFixed(2) + ' <span class="currency-display">' + currency + '</span>';

    // Show EGP equivalent if not EGP
    const egpEquivalent = document.getElementById('egp-equivalent');
    if (currency !== 'EGP') {
        const totalInEgp = grandTotal * exchangeRate;
        document.getElementById('total-egp').textContent = totalInEgp.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' EGP';
        egpEquivalent.style.display = 'block';
    } else {
        egpEquivalent.style.display = 'none';
    }
}

function updateCurrencyDisplay() {
    const currency = document.getElementById('currency').value;
    const exchangeRate = document.getElementById('exchange_rate');

    // Update currency code display
    document.getElementById('currency-code').textContent = currency;

    // Update all currency displays
    document.querySelectorAll('.currency-display').forEach(el => {
        el.textContent = currency;
    });

    // Update item total currency symbols
    document.querySelectorAll('.input-group-text').forEach(el => {
        if (el.textContent.match(/^(EGP|\$|€|£|SAR|AED)$/)) {
            el.textContent = currency;
        }
    });

    // If EGP, set exchange rate to 1
    if (currency === 'EGP') {
        exchangeRate.value = 1;
        document.getElementById('rate-display').textContent = '1';
    }

    calculateGrandTotal();
}

// Update rate display when exchange rate changes
document.getElementById('exchange_rate').addEventListener('input', function() {
    document.getElementById('rate-display').textContent = this.value;
    calculateGrandTotal();
});

// Calculate initial totals on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateGrandTotal();

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
    }

    initCustomerSelect2();
});
</script>
@endsection