@extends('layouts/layoutMaster')

@section('title', 'Create Invoice')

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
                    <h5 class="mb-0">Create New Invoice</h5>
                    <small class="text-muted">Create a new customer invoice</small>
                </div>
                <a href="{{ route('invoicing.invoices.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Back to Invoices
                </a>
            </div>

            <form action="{{ route('invoicing.invoices.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <!-- Customer Information -->
                        <div class="col-md-6">
                            <h6 class="mb-3">Customer Information</h6>

                            <div class="mb-3">
                                <label class="form-label required">Customer</label>
                                <select name="customer_id" id="customer_id" class="form-select select2-customer @error('customer_id') is-invalid @enderror" required
                                        data-selected="{{ old('customer_id', $selectedCustomerId ?? '') }}">
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
                                        <option value="{{ $project->id }}" {{ old('project_id', $selectedProjectId ?? '') == $project->id ? 'selected' : '' }}>
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
                                <label class="form-label">Invoice Sequence</label>
                                <select name="invoice_sequence_id" class="form-select @error('invoice_sequence_id') is-invalid @enderror">
                                    @if($sequences->count() > 1)
                                        <option value="">Auto-select (default sequence)</option>
                                    @endif
                                    @foreach($sequences as $index => $sequence)
                                        <option value="{{ $sequence->id }}" {{ old('invoice_sequence_id', $sequences->count() === 1 ? $sequence->id : '') == $sequence->id ? 'selected' : '' }}>
                                            {{ $sequence->name }} (Next: {{ $sequence->previewNextInvoiceNumber() }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('invoice_sequence_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if($sequences->isEmpty())
                                    <div class="text-danger mt-1">
                                        <i class="ti ti-alert-circle me-1"></i>No active sequences. <a href="{{ route('invoicing.sequences.create') }}">Create one</a>
                                    </div>
                                @else
                                    <small class="text-muted">Select sequence or leave default</small>
                                @endif
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label required">Invoice Date</label>
                                        <input type="date" name="invoice_date" class="form-control @error('invoice_date') is-invalid @enderror"
                                               value="{{ old('invoice_date', date('Y-m-d')) }}" required>
                                        @error('invoice_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label required">Due Date</label>
                                        <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror"
                                               value="{{ old('due_date', date('Y-m-d', strtotime('+1 week'))) }}" required>
                                        @error('due_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
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
                            <div class="invoice-item border rounded p-3 mb-3">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label required">Description</label>
                                        <input type="text" name="items[0][description]" class="form-control @error('items.0.description') is-invalid @enderror"
                                               value="{{ old('items.0.description') }}" required>
                                        @error('items.0.description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Project</label>
                                        <select name="items[0][project_id]" class="form-select form-select-sm item-project">
                                            <option value="">No Project</option>
                                            @foreach($projects as $project)
                                                <option value="{{ $project->id }}" {{ old('items.0.project_id') == $project->id ? 'selected' : '' }}>
                                                    {{ $project->code ?? $project->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label required">Qty</label>
                                        <input type="number" name="items[0][quantity]" class="form-control item-quantity @error('items.0.quantity') is-invalid @enderror"
                                               value="{{ old('items.0.quantity', 1) }}" min="0.01" step="0.01" required onchange="calculateItemTotal(this)">
                                        @error('items.0.quantity')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label required">Unit Price</label>
                                        <input type="number" name="items[0][unit_price]" class="form-control item-price @error('items.0.unit_price') is-invalid @enderror"
                                               value="{{ old('items.0.unit_price') }}" min="0.01" step="0.01" required onchange="calculateItemTotal(this)">
                                        @error('items.0.unit_price')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Tax %</label>
                                        <input type="number" name="items[0][tax_rate]" class="form-control item-tax @error('items.0.tax_rate') is-invalid @enderror"
                                               value="{{ old('items.0.tax_rate', 0) }}" min="0" max="100" step="0.01" onchange="calculateItemTotal(this)">
                                        @error('items.0.tax_rate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
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
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Totals -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3"
                                          placeholder="Additional notes or terms...">{{ old('notes') }}</textarea>
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
                                        <span id="subtotal">0.00 EGP</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Tax:</span>
                                        <span id="total-tax">0.00 EGP</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>Total:</span>
                                        <span id="grand-total">0.00 EGP</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('invoicing.invoices.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-x me-1"></i>Cancel
                    </a>
                    <div>
                        <button type="submit" name="action" value="draft" class="btn btn-outline-primary me-2">
                            <i class="ti ti-device-floppy me-1"></i>Save as Draft
                        </button>
                        <button type="submit" name="action" value="send" class="btn btn-primary">
                            <i class="ti ti-send me-1"></i>Save & Send
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
let itemIndex = 1;

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

    document.getElementById('subtotal').textContent = subtotal.toFixed(2) + ' EGP';
    document.getElementById('total-tax').textContent = totalTax.toFixed(2) + ' EGP';
    document.getElementById('grand-total').textContent = grandTotal.toFixed(2) + ' EGP';
}

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