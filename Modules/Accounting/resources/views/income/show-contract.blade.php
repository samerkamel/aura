@extends('layouts/layoutMaster')

@section('title', 'Contract Details - ' . $contract->contract_number)

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Contract Header -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">{{ $contract->client_name }}</h4>
                    <span class="text-muted">Contract {{ $contract->contract_number }}</span>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-{{ $contract->status === 'active' ? 'success' : 'secondary' }} fs-6">
                        {{ ucfirst($contract->status) }}
                    </span>
                    <a href="{{ route('accounting.income.contracts.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Back
                    </a>
                    <a href="{{ route('accounting.income.contracts.edit', $contract) }}" class="btn btn-primary">
                        <i class="ti ti-edit me-1"></i>Edit Contract
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Contract Information</h6>
                        <table class="table table-borderless">
                            <tr>
                                <td width="40%" class="text-muted">Contract Value:</td>
                                <td><strong>EGP {{ number_format($contract->total_amount, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Start Date:</td>
                                <td>{{ $contract->start_date ? $contract->start_date->format('M d, Y') : 'Not set' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">End Date:</td>
                                <td>{{ $contract->end_date ? $contract->end_date->format('M d, Y') : 'Ongoing' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Duration:</td>
                                <td>
                                    @if($contract->start_date && $contract->end_date)
                                        {{ $contract->start_date->diffInDays($contract->end_date) }} days
                                    @else
                                        Ongoing
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Created:</td>
                                <td>
                                    <small class="text-muted">{{ $contract->created_at->format('M d, Y \a\t h:i A') }}</small>
                                </td>
                            </tr>
                        </table>
                        <div class="alert alert-info py-2 mt-2">
                            <i class="ti ti-info-circle me-1"></i>
                            <small><strong>Note:</strong> The <strong>Start Date</strong> determines which month this contract appears in on the Income Sheet. Edit the contract to change the income recognition month.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Payment Summary</h6>
                        <table class="table table-borderless">
                            <tr>
                                <td width="40%" class="text-muted">Total Scheduled:</td>
                                <td><strong>EGP {{ number_format($statistics['total_payments_scheduled'], 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Amount Paid:</td>
                                <td><span class="text-success">EGP {{ number_format($statistics['amount_paid'], 2) }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Amount Pending:</td>
                                <td><span class="text-warning">EGP {{ number_format($statistics['amount_pending'], 2) }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Unassigned:</td>
                                <td>
                                    <span class="{{ $statistics['unassigned_amount'] < 0 ? 'text-danger' : 'text-info' }}">
                                        EGP {{ number_format($statistics['unassigned_amount'], 2) }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Payment Progress</span>
                        <span class="fw-medium">{{ number_format($statistics['progress_percentage'], 1) }}%</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-success" role="progressbar"
                             style="width: {{ $statistics['progress_percentage'] }}%"
                             aria-valuenow="{{ $statistics['progress_percentage'] }}"
                             aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                </div>

                @if($contract->description)
                <div class="mt-3">
                    <h6>Description</h6>
                    <p class="text-muted">{{ $contract->description }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Payment Milestones Section -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Payment Milestones</h5>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recurringPaymentsModal">
                        <i class="ti ti-refresh me-1"></i>Setup Recurring
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                        <i class="ti ti-plus me-1"></i>Add Milestone
                    </button>
                </div>
            </div>

            <div class="card-body">
                @if($contract->payments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Payment Name</th>
                                    <th>Amount</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Invoice/Credit</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contract->payments as $payment)
                                <tr>
                                    <td>{{ $payment->sequence_number ?: $loop->iteration }}</td>
                                    <td>
                                        <div>
                                            <strong>{{ $payment->name }}</strong>
                                            @if($payment->description)
                                                <br><small class="text-muted">{{ $payment->description }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <strong>EGP {{ number_format($payment->amount, 2) }}</strong>
                                        @if($payment->status === 'paid' && $payment->paid_amount != $payment->amount)
                                            <br><small class="text-success">Paid: EGP {{ number_format($payment->paid_amount, 2) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->due_date)
                                            <div>
                                                {{ $payment->due_date->format('M d, Y') }}
                                                @if($payment->is_overdue)
                                                    <br><small class="text-danger">{{ abs($payment->days_until_due) }} days overdue</small>
                                                @elseif($payment->status === 'pending')
                                                    <br><small class="text-muted">{{ $payment->days_until_due }} days left</small>
                                                @endif
                                            </div>
                                        @else
                                            <div>
                                                <span class="text-muted">To be scheduled</span>
                                                <br><small class="text-info">Planning milestone</small>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $payment->status_badge_class }}">
                                            {{ $payment->status_display }}
                                        </span>
                                        @if($payment->is_milestone)
                                            <br><small class="text-muted">Milestone</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->invoice_id && $payment->invoice)
                                            <a href="{{ route('invoicing.invoices.show', $payment->invoice) }}" class="text-decoration-none">
                                                <span class="badge bg-{{ $payment->invoice->status_badge_class }}">
                                                    <i class="ti ti-file-invoice me-1"></i>{{ $payment->invoice->invoice_number }}
                                                </span>
                                            </a>
                                            <br><small class="text-{{ $payment->invoice->status === 'paid' ? 'success' : 'muted' }}">
                                                {{ ucfirst($payment->invoice->status) }}
                                            </small>
                                        @elseif($payment->credit_note_id && $payment->creditNote)
                                            <a href="{{ route('accounting.credit-notes.show', $payment->creditNote) }}" class="text-decoration-none">
                                                <span class="badge bg-info">
                                                    <i class="ti ti-receipt-refund me-1"></i>{{ $payment->creditNote->credit_note_number }}
                                                </span>
                                            </a>
                                            <br><small class="text-info">Credit Note</small>
                                        @else
                                            <span class="badge bg-label-secondary">
                                                <i class="ti ti-file-off me-1"></i>No Invoice
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @if(!$payment->due_date)
                                                    <li><a class="dropdown-item" href="#" onclick="schedulePayment({{ $payment->id }})">
                                                        <i class="ti ti-calendar-plus me-2"></i>Schedule Date
                                                    </a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                @endif

                                                {{-- Invoice Actions --}}
                                                @if(!$payment->invoice_id && !$payment->credit_note_id)
                                                    <li class="dropdown-header">Invoice Actions</li>
                                                    <li><a class="dropdown-item" href="#" onclick="generateInvoice({{ $payment->id }})">
                                                        <i class="ti ti-file-plus me-2"></i>Generate Invoice
                                                    </a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="openLinkInvoiceModal({{ $payment->id }})">
                                                        <i class="ti ti-link me-2"></i>Link to Invoice
                                                    </a></li>
                                                    @if($payment->status !== 'paid')
                                                        <li><a class="dropdown-item" href="#" onclick="openRecordPaymentModal({{ $payment->id }}, '{{ $payment->name }}', {{ $payment->amount }})">
                                                            <i class="ti ti-cash me-2"></i>Record Payment (Credit Note)
                                                        </a></li>
                                                    @endif
                                                    <li><hr class="dropdown-divider"></li>
                                                @elseif($payment->invoice_id)
                                                    <li class="dropdown-header">Invoice Actions</li>
                                                    <li><a class="dropdown-item" href="{{ route('invoicing.invoices.show', $payment->invoice) }}">
                                                        <i class="ti ti-eye me-2"></i>View Invoice
                                                    </a></li>
                                                    <li><a class="dropdown-item text-warning" href="#" onclick="unlinkInvoice({{ $payment->id }})">
                                                        <i class="ti ti-unlink me-2"></i>Unlink Invoice
                                                    </a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                @elseif($payment->credit_note_id)
                                                    <li class="dropdown-header">Credit Note</li>
                                                    <li><a class="dropdown-item" href="{{ route('accounting.credit-notes.show', $payment->creditNote) }}">
                                                        <i class="ti ti-eye me-2"></i>View Credit Note
                                                    </a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                @endif

                                                {{-- Payment Status Actions --}}
                                                @if($payment->status === 'pending' && $payment->due_date && !$payment->invoice_id)
                                                    <li><a class="dropdown-item" href="#" onclick="markAsPaid({{ $payment->id }})">
                                                        <i class="ti ti-check me-2"></i>Mark as Paid
                                                    </a></li>
                                                @endif

                                                <li><a class="dropdown-item" href="#" onclick="editPayment({{ $payment->id }})">
                                                    <i class="ti ti-edit me-2"></i>Edit
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="deletePayment({{ $payment->id }})">
                                                    <i class="ti ti-trash me-2"></i>Delete
                                                </a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="2">Total</th>
                                    <th>EGP {{ number_format($statistics['total_payments_scheduled'], 2) }}</th>
                                    <th colspan="4">
                                        @if($statistics['unassigned_amount'] != 0)
                                            <span class="{{ $statistics['unassigned_amount'] < 0 ? 'text-danger' : 'text-info' }}">
                                                {{ $statistics['unassigned_amount'] < 0 ? 'Over-committed by' : 'Unassigned:' }}
                                                EGP {{ number_format(abs($statistics['unassigned_amount']), 2) }}
                                            </span>
                                        @else
                                            <span class="text-success">Fully allocated</span>
                                        @endif
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <div class="mb-3">
                            <i class="ti ti-calendar-plus" style="font-size: 3rem; color: #ddd;"></i>
                        </div>
                        <h6 class="text-muted">No Payment Milestones</h6>
                        <p class="text-muted mb-3">Add payment milestones to track project phases and income.</p>
                        <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                            <i class="ti ti-plus me-1"></i>Add First Milestone
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recurringPaymentsModal">
                            <i class="ti ti-refresh me-1"></i>Setup Recurring
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Payment Milestone</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('accounting.income.contracts.payments.store', $contract) }}" method="POST" id="addPaymentForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Payment Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" placeholder="e.g., Project Kickoff Payment" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Optional description"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">Payment Type <span class="text-danger">*</span></label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="payment_type" id="fixedAmount" value="fixed" checked>
                                <label class="form-check-label" for="fixedAmount">Fixed Amount</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="payment_type" id="percentage" value="percentage">
                                <label class="form-check-label" for="percentage">Percentage of Contract</label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div id="fixedAmountInput">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">EGP</span>
                                    <input type="number" class="form-control" name="amount" id="amountField" step="0.01" min="0" required
                                           max="{{ $statistics['unassigned_amount'] > 0 ? $statistics['unassigned_amount'] : $contract->total_amount }}">
                                </div>
                                @if($statistics['unassigned_amount'] > 0)
                                    <small class="text-muted">Available: EGP {{ number_format($statistics['unassigned_amount'], 2) }}</small>
                                @else
                                    <small class="text-warning">Contract is fully allocated or over-committed</small>
                                @endif
                            </div>
                            <div id="percentageInput" style="display: none;">
                                <label class="form-label">Percentage <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="percentage" id="percentageField" step="0.01" min="0" max="100">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Contract value: EGP {{ number_format($contract->total_amount, 2) }}</small>
                                <div class="mt-2">
                                    <small class="text-success">Calculated amount: <span id="calculatedAmount">EGP 0.00</span></small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="schedule_later" id="scheduleLater" value="1">
                                <label class="form-check-label" for="scheduleLater">
                                    Schedule date later (planning milestone)
                                </label>
                            </div>
                            <div id="dueDateField">
                                <label class="form-label">Due Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="due_date" id="dueDateInput" required>
                                <small class="text-muted">When payment is expected</small>
                            </div>
                            <div id="plannedDateField" style="display: none;">
                                <label class="form-label">Due Date</label>
                                <input type="date" class="form-control" name="due_date" id="plannedDateInput">
                                <small class="text-muted">Optional: Set later when date is confirmed</small>
                            </div>
                        </div>
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

<!-- Recurring Payments Modal -->
<div class="modal fade" id="recurringPaymentsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Setup Recurring Payments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('accounting.income.contracts.recurring-payments.generate', $contract) }}" method="POST" id="recurringPaymentsForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="ti ti-info-circle me-2"></i>
                        This will distribute the contract value (EGP {{ number_format($contract->total_amount, 2) }})
                        equally across all payment dates based on your frequency settings.
                        <br><strong>Note:</strong> This will replace any existing recurring payments.
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Frequency <span class="text-danger">*</span></label>
                            <select class="form-select" name="frequency_type" id="frequencyType" required>
                                <option value="">Select frequency</option>
                                <option value="weekly">Weekly</option>
                                <option value="bi-weekly">Bi-weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Every</label>
                            <input type="number" class="form-control" name="frequency_value" value="1" min="1" max="12">
                            <small class="text-muted">e.g., every 2 months, every 3 weeks</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="start_date" required
                                   value="{{ $contract->start_date ? $contract->start_date->format('Y-m-d') : now()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date"
                                   value="{{ $contract->end_date ? $contract->end_date->format('Y-m-d') : '' }}">
                            <small class="text-muted">Leave empty to use contract end date</small>
                        </div>
                    </div>

                    <div id="paymentPreview" class="mt-3" style="display: none;">
                        <h6>Payment Preview</h6>
                        <div class="alert alert-light">
                            <div id="previewContent"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-outline-primary" id="previewBtn">Preview</button>
                    <button type="submit" class="btn btn-primary" style="display: none;" id="generateBtn">Generate Payments</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Link Invoice Modal -->
<div class="modal fade" id="linkInvoiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Link to Existing Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="linkInvoiceForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="ti ti-info-circle me-2"></i>
                        Select an existing invoice to link to this payment. The payment status will sync with the invoice status.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Select Invoice <span class="text-danger">*</span></label>
                        <select class="form-select" name="invoice_id" id="invoiceSelect" required>
                            <option value="">Loading invoices...</option>
                        </select>
                    </div>

                    <div id="invoiceDetails" style="display: none;" class="alert alert-light">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Invoice Total:</small>
                                <div id="invoiceTotal" class="fw-bold"></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Status:</small>
                                <div id="invoiceStatus"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-link me-1"></i>Link Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Record Payment Modal (Credit Note) -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Payment Without Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="recordPaymentForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle me-2"></i>
                        <strong>Advance Payment:</strong> This will create a credit note that can be applied to a future invoice.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payment</label>
                        <input type="text" class="form-control" id="paymentNameDisplay" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">EGP</span>
                            <input type="text" class="form-control" id="paymentAmountDisplay" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="payment_date" required value="{{ now()->format('Y-m-d') }}">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" name="payment_method">
                                <option value="">Select method</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cash">Cash</option>
                                <option value="check">Check</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reference Number</label>
                            <input type="text" class="form-control" name="payment_reference" placeholder="e.g., Transfer #123">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Optional payment notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="ti ti-cash me-1"></i>Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fixedAmountRadio = document.getElementById('fixedAmount');
    const percentageRadio = document.getElementById('percentage');
    const fixedAmountInput = document.getElementById('fixedAmountInput');
    const percentageInput = document.getElementById('percentageInput');
    const amountField = document.getElementById('amountField');
    const percentageField = document.getElementById('percentageField');
    const calculatedAmount = document.getElementById('calculatedAmount');
    const contractValue = {{ $contract->total_amount }};

    // Handle payment type switching
    function togglePaymentType() {
        if (percentageRadio.checked) {
            fixedAmountInput.style.display = 'none';
            percentageInput.style.display = 'block';
            amountField.required = false;
            percentageField.required = true;
            amountField.value = '';
        } else {
            fixedAmountInput.style.display = 'block';
            percentageInput.style.display = 'none';
            amountField.required = true;
            percentageField.required = false;
            percentageField.value = '';
        }
    }

    // Calculate amount from percentage
    function calculateFromPercentage() {
        const percentage = parseFloat(percentageField.value) || 0;
        const amount = (contractValue * percentage) / 100;
        calculatedAmount.textContent = 'EGP ' + amount.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        // Set the amount in a hidden field for form submission
        let hiddenAmountField = document.getElementById('calculatedAmountField');
        if (!hiddenAmountField) {
            hiddenAmountField = document.createElement('input');
            hiddenAmountField.type = 'hidden';
            hiddenAmountField.name = 'calculated_amount';
            hiddenAmountField.id = 'calculatedAmountField';
            document.getElementById('addPaymentForm').appendChild(hiddenAmountField);
        }
        hiddenAmountField.value = amount;
    }

    fixedAmountRadio.addEventListener('change', togglePaymentType);
    percentageRadio.addEventListener('change', togglePaymentType);
    percentageField.addEventListener('input', calculateFromPercentage);

    // Handle schedule later checkbox
    const scheduleLaterCheckbox = document.getElementById('scheduleLater');
    const dueDateField = document.getElementById('dueDateField');
    const plannedDateField = document.getElementById('plannedDateField');
    const dueDateInput = document.getElementById('dueDateInput');
    const plannedDateInput = document.getElementById('plannedDateInput');

    function toggleScheduleLater() {
        if (scheduleLaterCheckbox.checked) {
            dueDateField.style.display = 'none';
            plannedDateField.style.display = 'block';
            dueDateInput.required = false;
            dueDateInput.value = '';
            plannedDateInput.name = 'due_date';
            dueDateInput.name = '';
        } else {
            dueDateField.style.display = 'block';
            plannedDateField.style.display = 'none';
            dueDateInput.required = true;
            plannedDateInput.value = '';
            dueDateInput.name = 'due_date';
            plannedDateInput.name = '';
        }
    }

    scheduleLaterCheckbox.addEventListener('change', toggleScheduleLater);

    // Payment status update functionality
    const paymentStatusForms = document.querySelectorAll('.payment-status-form');
    paymentStatusForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (confirm('Update payment status?')) {
                this.submit();
            }
        });
    });

    // Preview recurring payments
    const previewBtn = document.getElementById('previewBtn');
    const generateBtn = document.getElementById('generateBtn');
    const paymentPreview = document.getElementById('paymentPreview');
    const previewContent = document.getElementById('previewContent');
    const frequencyTypeSelect = document.getElementById('frequencyType');
    const frequencyValueInput = document.querySelector('input[name="frequency_value"]');
    const recurringStartDate = document.querySelector('#recurringPaymentsModal input[name="start_date"]');
    const recurringEndDate = document.querySelector('#recurringPaymentsModal input[name="end_date"]');
    const contractAmount = {{ $contract->total_amount }};

    previewBtn.addEventListener('click', function() {
        const frequencyType = frequencyTypeSelect.value;
        const frequencyValue = parseInt(frequencyValueInput.value) || 1;
        const startDateVal = recurringStartDate.value;
        const endDateVal = recurringEndDate.value;

        if (!frequencyType) {
            alert('Please select a frequency');
            return;
        }

        if (!startDateVal) {
            alert('Please select a start date');
            return;
        }

        if (!endDateVal) {
            alert('Please select an end date');
            return;
        }

        // Calculate payment dates
        const paymentDates = [];
        let current = new Date(startDateVal);
        const endDate = new Date(endDateVal);

        while (current <= endDate) {
            paymentDates.push(new Date(current));

            // Calculate next date based on frequency
            switch (frequencyType) {
                case 'weekly':
                    current.setDate(current.getDate() + (7 * frequencyValue));
                    break;
                case 'bi-weekly':
                    current.setDate(current.getDate() + (14 * frequencyValue));
                    break;
                case 'monthly':
                    current.setMonth(current.getMonth() + frequencyValue);
                    break;
                case 'quarterly':
                    current.setMonth(current.getMonth() + (3 * frequencyValue));
                    break;
                case 'yearly':
                    current.setFullYear(current.getFullYear() + frequencyValue);
                    break;
            }
        }

        if (paymentDates.length === 0) {
            alert('No payments can be generated with these dates. Please check your start and end dates.');
            return;
        }

        const amountPerPayment = contractAmount / paymentDates.length;

        // Build preview HTML
        let html = `<p><strong>Total Payments:</strong> ${paymentDates.length}</p>`;
        html += `<p><strong>Amount Per Payment:</strong> EGP ${amountPerPayment.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>`;
        html += '<p class="mb-2"><strong>Payment Schedule:</strong></p>';
        html += '<ul class="list-unstyled mb-0" style="max-height: 150px; overflow-y: auto;">';

        paymentDates.forEach((date, index) => {
            const dateStr = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            html += `<li><small>${index + 1}. ${dateStr} - EGP ${amountPerPayment.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</small></li>`;
        });

        html += '</ul>';

        previewContent.innerHTML = html;
        paymentPreview.style.display = 'block';
        generateBtn.style.display = 'inline-block';
    });

    // Reset preview when inputs change
    [frequencyTypeSelect, frequencyValueInput, recurringStartDate, recurringEndDate].forEach(el => {
        el.addEventListener('change', function() {
            paymentPreview.style.display = 'none';
            generateBtn.style.display = 'none';
        });
    });
});

function markAsPaid(paymentId) {
    if (confirm('Mark this payment as paid?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/accounting/income/contracts/{{ $contract->id }}/payments/${paymentId}/status`;

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';

        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'PATCH';

        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = 'paid';

        form.appendChild(csrfToken);
        form.appendChild(methodInput);
        form.appendChild(statusInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function schedulePayment(paymentId) {
    const date = prompt('Enter due date (YYYY-MM-DD):');
    if (date) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/accounting/income/contracts/{{ $contract->id }}/payments/${paymentId}/status`;

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';

        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'PATCH';

        const dueDateInput = document.createElement('input');
        dueDateInput.type = 'hidden';
        dueDateInput.name = 'due_date';
        dueDateInput.value = date;

        form.appendChild(csrfToken);
        form.appendChild(methodInput);
        form.appendChild(dueDateInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function editPayment(paymentId) {
    alert('Edit payment functionality will be implemented in the backend');
}

function deletePayment(paymentId) {
    if (confirm('Are you sure you want to delete this payment?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/accounting/income/contracts/{{ $contract->id }}/payments/${paymentId}`;

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';

        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';

        form.appendChild(csrfToken);
        form.appendChild(methodInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Invoice Integration Functions
function generateInvoice(paymentId) {
    if (confirm('Generate a new invoice from this payment? The payment will be linked to the new invoice.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/accounting/income/contracts/{{ $contract->id }}/payments/${paymentId}/generate-invoice`;

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';

        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}

function openLinkInvoiceModal(paymentId) {
    const modal = new bootstrap.Modal(document.getElementById('linkInvoiceModal'));
    const form = document.getElementById('linkInvoiceForm');
    const select = document.getElementById('invoiceSelect');
    const detailsDiv = document.getElementById('invoiceDetails');

    // Reset and set form action
    form.action = `/accounting/income/contracts/{{ $contract->id }}/payments/${paymentId}/link-invoice`;
    select.innerHTML = '<option value="">Loading invoices...</option>';
    detailsDiv.style.display = 'none';

    // Load available invoices
    fetch(`/accounting/income/contracts/{{ $contract->id }}/available-invoices`)
        .then(response => response.json())
        .then(data => {
            if (data.length === 0) {
                select.innerHTML = '<option value="">No available invoices found</option>';
            } else {
                let options = '<option value="">Select an invoice</option>';
                data.forEach(invoice => {
                    options += `<option value="${invoice.id}" data-total="${invoice.total}" data-status="${invoice.status}">
                        ${invoice.invoice_number} - ${invoice.customer_name} (EGP ${parseFloat(invoice.total).toLocaleString('en-US', {minimumFractionDigits: 2})})
                    </option>`;
                });
                select.innerHTML = options;
            }
        })
        .catch(error => {
            console.error('Error loading invoices:', error);
            select.innerHTML = '<option value="">Error loading invoices</option>';
        });

    // Handle invoice selection to show details
    select.onchange = function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            document.getElementById('invoiceTotal').textContent = 'EGP ' + parseFloat(selectedOption.dataset.total).toLocaleString('en-US', {minimumFractionDigits: 2});
            const statusBadge = getStatusBadge(selectedOption.dataset.status);
            document.getElementById('invoiceStatus').innerHTML = statusBadge;
            detailsDiv.style.display = 'block';
        } else {
            detailsDiv.style.display = 'none';
        }
    };

    modal.show();
}

function getStatusBadge(status) {
    const badges = {
        'draft': '<span class="badge bg-secondary">Draft</span>',
        'sent': '<span class="badge bg-info">Sent</span>',
        'paid': '<span class="badge bg-success">Paid</span>',
        'overdue': '<span class="badge bg-danger">Overdue</span>',
        'cancelled': '<span class="badge bg-dark">Cancelled</span>',
        'partial': '<span class="badge bg-warning">Partial</span>'
    };
    return badges[status] || `<span class="badge bg-secondary">${status}</span>`;
}

function openRecordPaymentModal(paymentId, paymentName, paymentAmount) {
    const modal = new bootstrap.Modal(document.getElementById('recordPaymentModal'));
    const form = document.getElementById('recordPaymentForm');

    // Set form action
    form.action = `/accounting/income/contracts/{{ $contract->id }}/payments/${paymentId}/record-payment`;

    // Populate display fields
    document.getElementById('paymentNameDisplay').value = paymentName;
    document.getElementById('paymentAmountDisplay').value = parseFloat(paymentAmount).toLocaleString('en-US', {minimumFractionDigits: 2});

    modal.show();
}

function unlinkInvoice(paymentId) {
    if (confirm('Are you sure you want to unlink this invoice from the payment? The payment status will be reset to pending.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/accounting/income/contracts/{{ $contract->id }}/payments/${paymentId}/unlink-invoice`;

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';

        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';

        form.appendChild(csrfToken);
        form.appendChild(methodInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection