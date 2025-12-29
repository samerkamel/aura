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
                        </table>
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
                                    <th>Type</th>
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
                                    </td>
                                    <td>
                                        <span class="badge {{ $payment->is_milestone ? 'bg-info' : 'bg-secondary' }}">
                                            {{ $payment->is_milestone ? 'Milestone' : 'Recurring' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu">
                                                @if(!$payment->due_date)
                                                    <li><a class="dropdown-item" href="#" onclick="schedulePayment({{ $payment->id }})">
                                                        <i class="ti ti-calendar-plus me-2"></i>Schedule Date
                                                    </a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                @endif
                                                @if($payment->status === 'pending' && $payment->due_date)
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
                            <select class="form-select" name="frequency" required>
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
    document.getElementById('previewBtn').addEventListener('click', function() {
        // You can implement preview logic here later
        alert('Preview functionality will be implemented');
        document.getElementById('generateBtn').style.display = 'inline-block';
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
</script>
@endsection