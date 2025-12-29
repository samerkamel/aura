@extends('layouts/layoutMaster')

@section('title', 'Credit Note: ' . $creditNote->credit_note_number)

@section('content')
<div class="row">
    <div class="col-12">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible mb-4" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible mb-4" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Credit Note Header -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">{{ $creditNote->credit_note_number }}</h5>
                            <small class="text-muted">Created by {{ $creditNote->createdBy->name ?? 'System' }} on {{ $creditNote->created_at->format('M d, Y') }}</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-{{ $creditNote->status_color }} fs-6">{{ $creditNote->status_label }}</span>
                            <a href="{{ route('accounting.credit-notes.index') }}" class="btn btn-outline-secondary">
                                <i class="ti tabler-arrow-left me-1"></i>Back
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Client & Details -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Customer Information</h6>
                                <h5 class="mb-1">{{ $creditNote->client_name }}</h5>
                                @if($creditNote->customer)
                                    <p class="mb-1 text-muted">{{ $creditNote->customer->name }}</p>
                                @endif
                                @if($creditNote->client_email)
                                    <p class="mb-1"><i class="ti tabler-mail me-1"></i>{{ $creditNote->client_email }}</p>
                                @endif
                                @if($creditNote->client_address)
                                    <p class="mb-0"><i class="ti tabler-map-pin me-1"></i>{{ $creditNote->client_address }}</p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Credit Note Details</h6>
                                <p class="mb-1"><strong>Date:</strong> {{ $creditNote->credit_note_date->format('M d, Y') }}</p>
                                @if($creditNote->reference)
                                    <p class="mb-1"><strong>Reference:</strong> {{ $creditNote->reference }}</p>
                                @endif
                                @if($creditNote->invoice)
                                    <p class="mb-0"><strong>Related Invoice:</strong>
                                        <a href="{{ route('invoicing.invoices.show', $creditNote->invoice) }}">
                                            {{ $creditNote->invoice->invoice_number }}
                                        </a>
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Line Items -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Line Items</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Description</th>
                                    <th class="text-center" style="width: 100px;">Qty</th>
                                    <th class="text-center" style="width: 80px;">Unit</th>
                                    <th class="text-end" style="width: 120px;">Unit Price</th>
                                    <th class="text-end" style="width: 120px;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($creditNote->items as $item)
                                    <tr>
                                        <td>
                                            <div class="fw-medium">{{ $item->description }}</div>
                                            @if($item->details)
                                                <small class="text-muted">{{ $item->details }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                                        <td class="text-center">{{ ucfirst($item->unit) }}</td>
                                        <td class="text-end">EGP {{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-end fw-medium">EGP {{ number_format($item->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end fw-medium">Subtotal:</td>
                                    <td class="text-end fw-medium">EGP {{ number_format($creditNote->subtotal, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end">Tax ({{ $creditNote->tax_rate }}%):</td>
                                    <td class="text-end">EGP {{ number_format($creditNote->tax_amount, 2) }}</td>
                                </tr>
                                <tr class="table-warning">
                                    <td colspan="4" class="text-end fw-bold">Credit Total:</td>
                                    <td class="text-end fw-bold">EGP {{ number_format($creditNote->total, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Credit Applications -->
                @if($creditNote->applications->count() > 0)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0"><i class="ti tabler-check me-1"></i>Credit Applications</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Amount Applied</th>
                                        <th>Date</th>
                                        <th>Applied By</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($creditNote->applications as $application)
                                        <tr>
                                            <td>
                                                <a href="{{ route('invoicing.invoices.show', $application->invoice) }}">
                                                    {{ $application->invoice->invoice_number }}
                                                </a>
                                            </td>
                                            <td class="fw-medium text-success">EGP {{ number_format($application->amount_applied, 2) }}</td>
                                            <td>{{ $application->applied_date->format('M d, Y') }}</td>
                                            <td>{{ $application->appliedBy->name ?? 'System' }}</td>
                                            <td>{{ $application->notes ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Notes -->
                @if($creditNote->notes || $creditNote->terms || $creditNote->internal_notes)
                    <div class="card mb-4">
                        <div class="card-body">
                            @if($creditNote->notes)
                                <h6 class="text-muted mb-2">Notes</h6>
                                <p class="mb-0">{!! nl2br(e($creditNote->notes)) !!}</p>
                            @endif

                            @if($creditNote->terms)
                                @if($creditNote->notes)<hr>@endif
                                <h6 class="text-muted mb-2">Terms</h6>
                                <p class="mb-0">{!! nl2br(e($creditNote->terms)) !!}</p>
                            @endif

                            @if($creditNote->internal_notes)
                                <hr>
                                <h6 class="text-muted mb-2"><i class="ti tabler-lock me-1"></i>Internal Notes</h6>
                                <p class="mb-0 text-muted">{!! nl2br(e($creditNote->internal_notes)) !!}</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('accounting.credit-notes.pdf', $creditNote) }}" class="btn btn-primary">
                                <i class="ti tabler-file-download me-1"></i>Download PDF
                            </a>

                            @if($creditNote->canBeEdited())
                                <a href="{{ route('accounting.credit-notes.edit', $creditNote) }}" class="btn btn-outline-primary">
                                    <i class="ti tabler-pencil me-1"></i>Edit Credit Note
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Status Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Status Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            @if($creditNote->canBeOpened())
                                <form action="{{ route('accounting.credit-notes.open', $creditNote) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="ti tabler-credit-card me-1"></i>Mark as Open
                                    </button>
                                </form>
                            @endif

                            @if($creditNote->canBeVoided())
                                <form action="{{ route('accounting.credit-notes.void', $creditNote) }}" method="POST"
                                      onsubmit="return confirm('Are you sure you want to void this credit note?')">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger w-100">
                                        <i class="ti tabler-ban me-1"></i>Void Credit Note
                                    </button>
                                </form>
                            @endif

                            @if($creditNote->status === 'draft')
                                <form action="{{ route('accounting.credit-notes.destroy', $creditNote) }}" method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this credit note?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger w-100">
                                        <i class="ti tabler-trash me-1"></i>Delete
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Apply Credits -->
                @if($creditNote->canBeApplied() && $availableInvoices->count() > 0)
                    <div class="card mb-4">
                        <div class="card-header bg-label-warning">
                            <h6 class="card-title mb-0"><i class="ti tabler-credit-card me-1"></i>Apply Credit</h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('accounting.credit-notes.apply', $creditNote) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="invoice_id" class="form-label">Select Invoice</label>
                                    <select class="form-select" id="apply_invoice_id" name="invoice_id" required>
                                        <option value="">Choose Invoice</option>
                                        @foreach($availableInvoices as $invoice)
                                            <option value="{{ $invoice->id }}" data-remaining="{{ $invoice->remaining_amount }}">
                                                {{ $invoice->invoice_number }} - EGP {{ number_format($invoice->remaining_amount, 2) }} remaining
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Amount to Apply</label>
                                    <div class="input-group">
                                        <span class="input-group-text">EGP</span>
                                        <input type="number" class="form-control" id="apply_amount" name="amount"
                                               step="0.01" min="0.01" max="{{ $creditNote->remaining_credits }}"
                                               value="{{ $creditNote->remaining_credits }}" required>
                                    </div>
                                    <small class="text-muted">Available: EGP {{ number_format($creditNote->remaining_credits, 2) }}</small>
                                </div>
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <input type="text" class="form-control" name="notes" placeholder="Optional notes">
                                </div>
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="ti tabler-check me-1"></i>Apply Credit
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                <!-- Summary -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Credit Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Items:</span>
                            <span>{{ $creditNote->items->count() }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal:</span>
                            <span>EGP {{ number_format($creditNote->subtotal, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Tax ({{ $creditNote->tax_rate }}%):</span>
                            <span>EGP {{ number_format($creditNote->tax_amount, 2) }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-bold">Total Credit:</span>
                            <span class="fw-bold text-warning fs-5">EGP {{ number_format($creditNote->total, 2) }}</span>
                        </div>
                        @if($creditNote->applied_amount > 0)
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Applied:</span>
                                <span class="text-success">EGP {{ number_format($creditNote->applied_amount, 2) }}</span>
                            </div>
                        @endif
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Remaining:</span>
                            <span class="fw-medium {{ $creditNote->remaining_credits > 0 ? 'text-primary' : '' }}">
                                EGP {{ number_format($creditNote->remaining_credits, 2) }}
                            </span>
                        </div>
                        @if($creditNote->usage_percentage > 0)
                            <div class="progress mt-3" style="height: 6px;">
                                <div class="progress-bar bg-success" role="progressbar"
                                     style="width: {{ $creditNote->usage_percentage }}%"></div>
                            </div>
                            <small class="text-muted">{{ $creditNote->usage_percentage }}% used</small>
                        @endif
                    </div>
                </div>

                <!-- Timeline -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Timeline</h6>
                    </div>
                    <div class="card-body">
                        <ul class="timeline mb-0">
                            <li class="timeline-item">
                                <span class="timeline-indicator timeline-indicator-primary">
                                    <i class="ti tabler-plus"></i>
                                </span>
                                <div class="timeline-event">
                                    <div class="timeline-header">Created</div>
                                    <small class="text-muted">{{ $creditNote->created_at->format('M d, Y H:i') }}</small>
                                </div>
                            </li>
                            @if($creditNote->sent_at)
                                <li class="timeline-item">
                                    <span class="timeline-indicator timeline-indicator-success">
                                        <i class="ti tabler-credit-card"></i>
                                    </span>
                                    <div class="timeline-event">
                                        <div class="timeline-header">Opened</div>
                                        <small class="text-muted">{{ $creditNote->sent_at->format('M d, Y H:i') }}</small>
                                    </div>
                                </li>
                            @endif
                            @foreach($creditNote->applications as $application)
                                <li class="timeline-item">
                                    <span class="timeline-indicator timeline-indicator-warning">
                                        <i class="ti tabler-check"></i>
                                    </span>
                                    <div class="timeline-event">
                                        <div class="timeline-header">Applied EGP {{ number_format($application->amount_applied, 2) }}</div>
                                        <small class="text-muted">To {{ $application->invoice->invoice_number }} - {{ $application->applied_date->format('M d, Y') }}</small>
                                    </div>
                                </li>
                            @endforeach
                            @if($creditNote->status === 'closed')
                                <li class="timeline-item">
                                    <span class="timeline-indicator timeline-indicator-info">
                                        <i class="ti tabler-circle-check"></i>
                                    </span>
                                    <div class="timeline-event">
                                        <div class="timeline-header">Fully Applied</div>
                                        <small class="text-muted">All credits used</small>
                                    </div>
                                </li>
                            @endif
                            @if($creditNote->status === 'void')
                                <li class="timeline-item">
                                    <span class="timeline-indicator timeline-indicator-danger">
                                        <i class="ti tabler-ban"></i>
                                    </span>
                                    <div class="timeline-event">
                                        <div class="timeline-header">Voided</div>
                                        <small class="text-muted">Credit note cancelled</small>
                                    </div>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const invoiceSelect = document.getElementById('apply_invoice_id');
    const amountInput = document.getElementById('apply_amount');

    if (invoiceSelect && amountInput) {
        invoiceSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            if (selected.value) {
                const remaining = parseFloat(selected.dataset.remaining);
                const available = parseFloat({{ $creditNote->remaining_credits }});
                amountInput.max = Math.min(remaining, available);
                amountInput.value = Math.min(remaining, available);
            }
        });
    }
});
</script>
@endsection
