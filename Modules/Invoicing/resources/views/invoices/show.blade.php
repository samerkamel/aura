@extends('layouts/layoutMaster')

@section('title', 'Invoice Details')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Invoice #{{ $invoice->invoice_number }}</h5>
                    <small class="text-muted">Created {{ $invoice->created_at->format('M j, Y \a\t g:i A') }}</small>
                </div>
                <div class="d-flex gap-2">
                    @if($invoice->status === 'draft')
                        <a href="{{ route('invoicing.invoices.edit', $invoice) }}" class="btn btn-outline-primary">
                            <i class="ti ti-edit me-1"></i>Edit
                        </a>
                    @endif
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="ti ti-dots-vertical me-1"></i>Actions
                        </button>
                        <ul class="dropdown-menu">
                            @if(in_array($invoice->status, ['draft', 'sent']))
                                <li>
                                    <a class="dropdown-item" href="#" onclick="markAsSent({{ $invoice->id }})">
                                        <i class="ti ti-send me-2"></i>Mark as Sent
                                    </a>
                                </li>
                            @endif
                            @if(in_array($invoice->status, ['sent', 'overdue']))
                                <li>
                                    <a class="dropdown-item" href="#" onclick="markAsPaid({{ $invoice->id }})">
                                        <i class="ti ti-check me-2"></i>Mark as Paid
                                    </a>
                                </li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="printInvoice()">
                                    <i class="ti ti-printer me-2"></i>Print
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="downloadPDF()">
                                    <i class="ti ti-download me-2"></i>Download PDF
                                </a>
                            </li>
                            @if($invoice->status === 'draft')
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="#" onclick="cancelInvoice({{ $invoice->id }})">
                                        <i class="ti ti-x me-2"></i>Cancel Invoice
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                    <a href="{{ route('invoicing.invoices.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Back to Invoices
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card-body">
                <!-- Invoice Header -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="mb-3">Bill To:</h6>
                        <div class="ms-3">
                            <h6 class="mb-1">{{ $invoice->customer->name }}</h6>
                            @if($invoice->customer->email)
                                <p class="mb-1 text-muted">{{ $invoice->customer->email }}</p>
                            @endif
                            @if($invoice->customer->phone)
                                <p class="mb-1 text-muted">{{ $invoice->customer->phone }}</p>
                            @endif
                            @if($invoice->customer->address)
                                <p class="mb-0 text-muted">{{ $invoice->customer->address }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-md-end">
                            <span class="badge {{ $invoice->status_badge_class }} fs-6 mb-3">{{ $invoice->status_display }}</span>

                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td class="text-end pe-3"><strong>Invoice Date:</strong></td>
                                    <td>{{ $invoice->invoice_date->format('M j, Y') }}</td>
                                </tr>
                                @if($invoice->due_date)
                                <tr>
                                    <td class="text-end pe-3"><strong>Due Date:</strong></td>
                                    <td class="{{ $invoice->is_overdue ? 'text-danger fw-semibold' : '' }}">
                                        {{ $invoice->due_date->format('M j, Y') }}
                                        @if($invoice->is_overdue)
                                            <small class="d-block text-danger">{{ $invoice->days_overdue }} days overdue</small>
                                        @endif
                                    </td>
                                </tr>
                                @endif
                                @if($invoice->paid_date)
                                <tr>
                                    <td class="text-end pe-3"><strong>Paid Date:</strong></td>
                                    <td class="text-success">{{ $invoice->paid_date->format('M j, Y') }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td class="text-end pe-3"><strong>Business Unit:</strong></td>
                                    <td>{{ $invoice->businessUnit->name }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Invoice Items -->
                <div class="mb-4">
                    <h6 class="mb-3">Invoice Items</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Description</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-center">Tax Rate</th>
                                    <th class="text-end">Tax Amount</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->items as $item)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $item->description }}</div>
                                        @if($item->contract_payment_id)
                                            <small class="text-muted">
                                                <i class="ti ti-link me-1"></i>
                                                Linked to contract payment
                                            </small>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                                    <td class="text-end">{{ number_format($item->unit_price, 2) }} EGP</td>
                                    <td class="text-center">{{ number_format($item->tax_rate, 2) }}%</td>
                                    <td class="text-end">{{ number_format($item->tax_amount, 2) }} EGP</td>
                                    <td class="text-end fw-semibold">{{ number_format($item->total_amount, 2) }} EGP</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Invoice Totals and Notes -->
                <div class="row">
                    <div class="col-md-8">
                        @if($invoice->notes)
                        <div class="mb-4">
                            <h6 class="mb-2">Notes</h6>
                            <div class="border rounded p-3 bg-light">
                                {!! nl2br(e($invoice->notes)) !!}
                            </div>
                        </div>
                        @endif

                        <!-- Payment History -->
                        @if($invoice->status === 'paid' || $invoice->paid_amount > 0)
                        <div class="mb-4">
                            <h6 class="mb-3">Payment Information</h6>
                            <div class="border rounded p-3">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <strong>Amount Paid:</strong>
                                        <span class="text-success">{{ number_format($invoice->paid_amount, 2) }} EGP</span>
                                    </div>
                                    @if($invoice->paid_date)
                                    <div class="col-sm-6">
                                        <strong>Payment Date:</strong>
                                        {{ $invoice->paid_date->format('M j, Y') }}
                                    </div>
                                    @endif
                                </div>
                                @if($invoice->total_amount > $invoice->paid_amount)
                                <div class="mt-2">
                                    <strong>Remaining Balance:</strong>
                                    <span class="text-danger">{{ number_format($invoice->total_amount - $invoice->paid_amount, 2) }} EGP</span>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Invoice Summary</h6>
                                <table class="table table-borderless table-sm mb-0">
                                    <tr>
                                        <td>Subtotal:</td>
                                        <td class="text-end">{{ number_format($invoice->subtotal_amount, 2) }} EGP</td>
                                    </tr>
                                    <tr>
                                        <td>Tax:</td>
                                        <td class="text-end">{{ number_format($invoice->tax_amount, 2) }} EGP</td>
                                    </tr>
                                    <tr class="border-top">
                                        <td><strong>Total:</strong></td>
                                        <td class="text-end"><strong>{{ number_format($invoice->total_amount, 2) }} EGP</strong></td>
                                    </tr>
                                    @if($invoice->paid_amount > 0)
                                    <tr>
                                        <td class="text-success">Paid:</td>
                                        <td class="text-end text-success">-{{ number_format($invoice->paid_amount, 2) }} EGP</td>
                                    </tr>
                                    <tr class="border-top">
                                        <td><strong>Balance Due:</strong></td>
                                        <td class="text-end"><strong class="{{ $invoice->status === 'paid' ? 'text-success' : 'text-danger' }}">{{ number_format($invoice->total_amount - $invoice->paid_amount, 2) }} EGP</strong></td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Related Information -->
                @if($invoice->items->where('contract_payment_id', '!=', null)->count() > 0)
                <div class="mt-4">
                    <h6 class="mb-3">Related Contract Information</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Contract</th>
                                    <th>Payment Description</th>
                                    <th>Due Date</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->items->where('contract_payment_id', '!=', null) as $item)
                                @if($item->contractPayment)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.income.contracts.show', $item->contractPayment->contract) }}" class="text-primary">
                                            {{ $item->contractPayment->contract->contract_number }}
                                        </a>
                                    </td>
                                    <td>{{ $item->contractPayment->name }}</td>
                                    <td>
                                        @if($item->contractPayment->due_date)
                                            {{ $item->contractPayment->due_date->format('M j, Y') }}
                                        @else
                                            <span class="text-muted">Not scheduled</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($item->contractPayment->amount, 2) }} EGP</td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Payments Section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Payment History</h5>
                    <small class="text-muted">Track partial and full payments</small>
                </div>
                @if($invoice->status !== 'cancelled' && $invoice->remaining_amount > 0 && auth()->user()->can('manage-invoices'))
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                        <i class="ti ti-plus me-1"></i>Add Payment
                    </button>
                @endif
            </div>

            <div class="card-body">
                <!-- Payment Summary -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-sm-6">
                        <div class="card bg-label-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-label-info p-2 me-2 rounded">
                                            <i class="ti ti-currency-dollar ti-sm"></i>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <h5 class="mb-0">{{ number_format($invoice->total_amount, 2) }}</h5>
                                        <small>Total Amount (EGP)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="card bg-label-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-label-success p-2 me-2 rounded">
                                            <i class="ti ti-check ti-sm"></i>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <h5 class="mb-0">{{ number_format($invoice->paid_amount, 2) }}</h5>
                                        <small>Paid Amount (EGP)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="card bg-label-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-label-warning p-2 me-2 rounded">
                                            <i class="ti ti-clock ti-sm"></i>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <h5 class="mb-0">{{ number_format($invoice->remaining_amount, 2) }}</h5>
                                        <small>Remaining (EGP)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="card bg-label-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-label-primary p-2 me-2 rounded">
                                            <i class="ti ti-list ti-sm"></i>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <h5 class="mb-0">{{ $invoice->payments->count() }}</h5>
                                        <small>Total Payments</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payments Table -->
                @if($invoice->payments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                    <th>Notes</th>
                                    <th>Recorded By</th>
                                    @if(auth()->user()->can('manage-invoices'))
                                        <th>Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('M j, Y') }}</td>
                                    <td>
                                        <span class="fw-semibold text-success">{{ number_format($payment->amount, 2) }} EGP</span>
                                    </td>
                                    <td>
                                        @if($payment->payment_method)
                                            <span class="badge bg-label-secondary">{{ $payment->payment_method_display }}</span>
                                        @else
                                            <span class="text-muted">Not specified</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->reference_number)
                                            <code>{{ $payment->reference_number }}</code>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->notes)
                                            <span class="text-truncate" style="max-width: 200px;" title="{{ $payment->notes }}">
                                                {{ Str::limit($payment->notes, 50) }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $payment->createdBy->name }}</td>
                                    @if(auth()->user()->can('manage-invoices'))
                                        <td>
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <button type="button" class="dropdown-item" onclick="editPayment({{ $payment->id }})">
                                                        <i class="ti ti-edit me-2"></i>Edit
                                                    </button>
                                                    <button type="button" class="dropdown-item text-danger" onclick="deletePayment({{ $payment->id }})">
                                                        <i class="ti ti-trash me-2"></i>Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <div class="mb-3">
                            <i class="ti ti-currency-dollar display-6 text-muted"></i>
                        </div>
                        <h6 class="mb-2">No payments recorded</h6>
                        <p class="text-muted">No payments have been recorded for this invoice yet.</p>
                        @if($invoice->status !== 'cancelled' && auth()->user()->can('manage-invoices'))
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                                <i class="ti ti-plus me-1"></i>Add First Payment
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
@if($invoice->status !== 'cancelled' && $invoice->remaining_amount > 0 && auth()->user()->can('manage-invoices'))
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('invoicing.invoices.payments.store', $invoice) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addPaymentModalLabel">Add Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Amount (EGP)</label>
                                <div class="input-group">
                                    <span class="input-group-text">EGP</span>
                                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="{{ $invoice->remaining_amount }}" required>
                                </div>
                                <small class="text-muted">Maximum: {{ number_format($invoice->remaining_amount, 2) }} EGP</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Payment Date</label>
                                <input type="date" name="payment_date" class="form-control" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-select">
                                    <option value="">Select method</option>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="check">Check</option>
                                    <option value="card">Credit/Debit Card</option>
                                    <option value="online">Online Payment</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Reference Number</label>
                                <input type="text" name="reference_number" class="form-control" placeholder="Transaction/Check number">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Optional payment notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Mark as Paid Modal -->
<div class="modal fade" id="markAsPaidModal" tabindex="-1" aria-labelledby="markAsPaidModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="markAsPaidModalLabel">Mark Invoice as Paid</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="markAsPaidForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="paid_amount" class="form-label">Amount Paid (EGP) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="paid_amount" name="paid_amount" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="paid_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="paid_date" name="paid_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="account_id" class="form-label">Account to Receive Payment <span class="text-danger">*</span></label>
                        <select class="form-select" id="account_id" name="account_id" required>
                            <option value="">Select Account</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->formatted_balance }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="payment_notes" class="form-label">Payment Notes</label>
                        <textarea class="form-control" id="payment_notes" name="payment_notes" rows="3" placeholder="Optional notes about this payment..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Mark as Paid</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
function markAsSent(invoiceId) {
    if (confirm('Mark this invoice as sent?')) {
        fetch(`/invoicing/invoices/${invoiceId}/send`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('An error occurred');
            console.error('Error:', error);
        });
    }
}

function markAsPaid(invoiceId) {
    // Set up the modal form
    const form = document.getElementById('markAsPaidForm');
    const amountInput = document.getElementById('paid_amount');

    // Get total amount from invoice data (from the page)
    const totalAmount = '{{ $invoice->total_amount }}';

    form.action = `/invoicing/invoices/${invoiceId}/pay`;
    amountInput.value = totalAmount;
    amountInput.setAttribute('max', totalAmount);

    // Show the modal
    new bootstrap.Modal(document.getElementById('markAsPaidModal')).show();
}

function cancelInvoice(invoiceId) {
    if (confirm('Cancel this invoice? This action cannot be undone.')) {
        fetch(`/invoicing/invoices/${invoiceId}/cancel`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('An error occurred');
            console.error('Error:', error);
        });
    }
}

function printInvoice() {
    window.print();
}

function downloadPDF() {
    // Implementation for PDF download
    alert('PDF download functionality would be implemented here');
}

function editPayment(paymentId) {
    // TODO: Implement edit payment modal
    alert('Edit payment functionality would be implemented here');
}

function deletePayment(paymentId) {
    if (confirm('Delete this payment? This action cannot be undone.')) {
        fetch(`/invoicing/invoices/payments/${paymentId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('An error occurred');
            console.error('Error:', error);
        });
    }
}
</script>
@endsection