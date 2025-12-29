@extends('layouts/layoutMaster')

@section('title', 'Internal Transaction Sequence Details')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Internal Transaction Sequence: {{ $internalSequence->name }}</h5>
                    <small class="text-muted">Sequence details and usage information</small>
                </div>
                <div class="d-flex gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="ti tabler-dots-vertical me-1"></i>Actions
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route('invoicing.internal-sequences.edit', $internalSequence) }}">
                                    <i class="ti tabler-edit me-2"></i>Edit Sequence
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="toggleSequence({{ $internalSequence->id }})">
                                    @if($internalSequence->is_active)
                                        <i class="ti tabler-pause me-2"></i>Deactivate
                                    @else
                                        <i class="ti tabler-play me-2"></i>Activate
                                    @endif
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-warning" href="#" onclick="resetSequence({{ $internalSequence->id }})">
                                    <i class="ti tabler-refresh me-2"></i>Reset Counter
                                </a>
                            </li>
                            @if($internalSequence->internalTransactions->count() == 0)
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="#" onclick="deleteSequence({{ $internalSequence->id }})">
                                        <i class="ti tabler-trash me-2"></i>Delete Sequence
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                    <a href="{{ route('invoicing.internal-sequences.index') }}" class="btn btn-outline-secondary">
                        <i class="ti tabler-arrow-left me-1"></i>Back to Sequences
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
                                <td>{{ $internalSequence->name }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Prefix:</td>
                                <td><code class="text-primary">{{ $internalSequence->prefix ?: 'None' }}</code></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Format:</td>
                                <td><code class="text-primary">{{ $internalSequence->format }}</code></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Current Number:</td>
                                <td>
                                    <span class="badge bg-label-info">{{ $internalSequence->current_number }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Starting Number:</td>
                                <td>{{ $internalSequence->starting_number }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Next Transaction:</td>
                                <td>
                                    <span class="badge bg-success">{{ $internalSequence->previewNextTransactionNumber() }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Status:</td>
                                <td>
                                    @if($internalSequence->is_active)
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
                                    @if($internalSequence->businessUnit)
                                        <span class="badge bg-label-primary">{{ $internalSequence->businessUnit->name }}</span>
                                    @else
                                        <span class="text-muted">All Business Units</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Sectors:</td>
                                <td>
                                    @if($internalSequence->sector_ids)
                                        @foreach($internalSequence->sectors() as $sector)
                                            <span class="badge bg-label-secondary me-1">{{ $sector->name }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">All Sectors</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Created:</td>
                                <td>{{ $internalSequence->created_at->format('M j, Y \\a\\t g:i A') }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Last Updated:</td>
                                <td>{{ $internalSequence->updated_at->format('M j, Y \\a\\t g:i A') }}</td>
                            </tr>
                            @if($internalSequence->description)
                            <tr>
                                <td class="fw-semibold">Description:</td>
                                <td>{{ $internalSequence->description }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>

                @if($internalSequence->description)
                <div class="mt-4">
                    <h6 class="mb-3">Description</h6>
                    <div class="alert alert-light">
                        {{ $internalSequence->description }}
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
                                                <i class="ti tabler-building-bank ti-sm"></i>
                                            </span>
                                        </div>
                                        <div class="text-end">
                                            <h5 class="mb-0">{{ $internalSequence->internalTransactions->count() }}</h5>
                                            <small>Total Transactions</small>
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
                                                <i class="ti tabler-check ti-sm"></i>
                                            </span>
                                        </div>
                                        <div class="text-end">
                                            <h5 class="mb-0">{{ $internalSequence->internalTransactions->where('status', 'approved')->count() }}</h5>
                                            <small>Approved</small>
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
                                                <i class="ti tabler-clock ti-sm"></i>
                                            </span>
                                        </div>
                                        <div class="text-end">
                                            <h5 class="mb-0">{{ $internalSequence->internalTransactions->where('status', 'pending')->count() }}</h5>
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
                                                <i class="ti tabler-currency-dollar ti-sm"></i>
                                            </span>
                                        </div>
                                        <div class="text-end">
                                            <h5 class="mb-0">{{ number_format($internalSequence->internalTransactions->sum('amount'), 0) }}</h5>
                                            <small>Total Amount (EGP)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                @if($internalSequence->internalTransactions->count() > 0)
                <div class="mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Recent Transactions</h6>
                        <a href="{{ route('invoicing.internal-transactions.index') }}?sequence={{ $internalSequence->id }}" class="btn btn-outline-primary btn-sm">
                            View All Transactions
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Transaction #</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($internalSequence->internalTransactions->sortByDesc('created_at')->take(10) as $transaction)
                                <tr>
                                    <td>
                                        <a href="{{ route('invoicing.internal-transactions.show', $transaction) }}" class="text-primary">
                                            {{ $transaction->transaction_number }}
                                        </a>
                                    </td>
                                    <td>{{ $transaction->fromBusinessUnit->name }}</td>
                                    <td>{{ $transaction->toBusinessUnit->name }}</td>
                                    <td>{{ $transaction->transaction_date->format('M j, Y') }}</td>
                                    <td>{{ number_format($transaction->amount, 2) }} EGP</td>
                                    <td>
                                        <span class="badge {{ $transaction->status_badge_class }}">
                                            {{ $transaction->status_display }}
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
        fetch(`/invoicing/admin/internal-sequences/${sequenceId}/toggle`, {
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
    if (confirm('Reset the sequence counter to 0? This will affect future transaction numbers.')) {
        fetch(`/invoicing/admin/internal-sequences/${sequenceId}/reset`, {
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
        fetch(`/invoicing/admin/internal-sequences/${sequenceId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '/invoicing/admin/internal-sequences';
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