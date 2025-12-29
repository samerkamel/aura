@extends('layouts/layoutMaster')

@section('title', 'Invoice Sequence Details')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Invoice Sequence: {{ $invoiceSequence->name }}</h5>
                    <small class="text-muted">Sequence details and usage information</small>
                </div>
                <div class="d-flex gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="ti ti-dots-vertical me-1"></i>Actions
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route('invoicing.sequences.edit', $invoiceSequence) }}">
                                    <i class="ti ti-edit me-2"></i>Edit Sequence
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="toggleSequence({{ $invoiceSequence->id }})">
                                    @if($invoiceSequence->is_active)
                                        <i class="ti ti-pause me-2"></i>Deactivate
                                    @else
                                        <i class="ti ti-play me-2"></i>Activate
                                    @endif
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-warning" href="#" onclick="resetSequence({{ $invoiceSequence->id }})">
                                    <i class="ti ti-refresh me-2"></i>Reset Counter
                                </a>
                            </li>
                            @if($invoiceSequence->invoices->count() == 0)
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="#" onclick="deleteSequence({{ $invoiceSequence->id }})">
                                        <i class="ti ti-trash me-2"></i>Delete Sequence
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                    <a href="{{ route('invoicing.sequences.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Back to Sequences
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card-body">
                <div class="row">
                    <!-- Sequence Information -->
                    <div class="col-md-6">
                        <h6 class="mb-3">Sequence Information</h6>

                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-semibold">Name:</td>
                                <td>{{ $invoiceSequence->name }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Prefix:</td>
                                <td><code class="text-primary">{{ $invoiceSequence->prefix ?: 'None' }}</code></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Format:</td>
                                <td><code class="text-primary">{{ $invoiceSequence->format }}</code></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Current Number:</td>
                                <td>
                                    <span class="badge bg-label-info">{{ $invoiceSequence->current_number }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Starting Number:</td>
                                <td>{{ $invoiceSequence->starting_number }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Next Invoice:</td>
                                <td>
                                    <span class="badge bg-success">{{ $invoiceSequence->previewNextInvoiceNumber() }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Status:</td>
                                <td>
                                    @if($invoiceSequence->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Access Control -->
                    <div class="col-md-6">
                        <h6 class="mb-3">Access Control</h6>

                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-semibold">Business Unit:</td>
                                <td>
                                    @if($invoiceSequence->businessUnit)
                                        <span class="badge bg-label-primary">{{ $invoiceSequence->businessUnit->name }}</span>
                                    @else
                                        <span class="text-muted">All Business Units</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Sectors:</td>
                                <td>
                                    @if($invoiceSequence->sector_ids)
                                        @foreach($invoiceSequence->sectors() as $sector)
                                            <span class="badge bg-label-secondary me-1">{{ $sector->name }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">All Sectors</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Created:</td>
                                <td>{{ $invoiceSequence->created_at->format('M j, Y \a\t g:i A') }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Last Updated:</td>
                                <td>{{ $invoiceSequence->updated_at->format('M j, Y \a\t g:i A') }}</td>
                            </tr>
                            @if($invoiceSequence->description)
                            <tr>
                                <td class="fw-semibold">Description:</td>
                                <td>{{ $invoiceSequence->description }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>

                @if($invoiceSequence->description)
                <div class="mt-4">
                    <h6 class="mb-3">Description</h6>
                    <div class="alert alert-light">
                        {{ $invoiceSequence->description }}
                    </div>
                </div>
                @endif

                <!-- Usage Statistics -->
                <div class="mt-4">
                    <h6 class="mb-3">Usage Statistics</h6>
                    <div class="row">
                        <div class="col-lg-3 col-sm-6">
                            <div class="card bg-label-primary">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-label-primary p-2 me-2 rounded">
                                                <i class="ti ti-file-invoice ti-sm"></i>
                                            </span>
                                        </div>
                                        <div class="text-end">
                                            <h5 class="mb-0">{{ $invoiceSequence->invoices->count() }}</h5>
                                            <small>Total Invoices</small>
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
                                            <h5 class="mb-0">{{ $invoiceSequence->invoices->where('status', 'paid')->count() }}</h5>
                                            <small>Paid Invoices</small>
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
                                            <h5 class="mb-0">{{ $invoiceSequence->invoices->whereIn('status', ['sent', 'overdue'])->count() }}</h5>
                                            <small>Pending</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                                            <h5 class="mb-0">{{ number_format($invoiceSequence->invoices->sum('total_amount'), 0) }}</h5>
                                            <small>Total Amount (EGP)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Invoices -->
                @if($invoiceSequence->invoices->count() > 0)
                <div class="mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Recent Invoices</h6>
                        <a href="{{ route('invoicing.invoices.index') }}?sequence={{ $invoiceSequence->id }}" class="btn btn-outline-primary btn-sm">
                            View All Invoices
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoiceSequence->invoices->sortByDesc('created_at')->take(10) as $invoice)
                                <tr>
                                    <td>
                                        <a href="{{ route('invoicing.invoices.show', $invoice) }}" class="text-primary">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td>{{ $invoice->customer->name }}</td>
                                    <td>{{ $invoice->invoice_date->format('M j, Y') }}</td>
                                    <td>{{ number_format($invoice->total_amount, 2) }} EGP</td>
                                    <td>
                                        <span class="badge {{ $invoice->status_badge_class }}">
                                            {{ $invoice->status_display }}
                                        </span>
                                    </td>
                                </tr>
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
@endsection

@section('page-script')
<script>
function toggleSequence(sequenceId) {
    if (confirm('Change the status of this sequence?')) {
        fetch(`/invoicing/admin/sequences/${sequenceId}/toggle`, {
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

function resetSequence(sequenceId) {
    if (confirm('Reset the sequence counter to 0? This will affect future invoice numbers.')) {
        fetch(`/invoicing/admin/sequences/${sequenceId}/reset`, {
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

function deleteSequence(sequenceId) {
    if (confirm('Delete this sequence? This action cannot be undone.')) {
        fetch(`/invoicing/admin/sequences/${sequenceId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '/invoicing/admin/sequences';
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