@extends('layouts/layoutMaster')

@section('title', 'Internal Transaction Details')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Internal Transaction: {{ $internalTransaction->transaction_number }}</h5>
                    <small class="text-muted">Transaction details and approval status</small>
                </div>
                <div class="d-flex gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="ti ti-dots-vertical me-1"></i>Actions
                        </button>
                        <ul class="dropdown-menu">
                            @if($internalTransaction->status === 'pending')
                                <li>
                                    <a class="dropdown-item text-success" href="#" onclick="approveTransaction({{ $internalTransaction->id }})">
                                        <i class="ti ti-check me-2"></i>Approve Transaction
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger" href="#" onclick="rejectTransaction({{ $internalTransaction->id }})">
                                        <i class="ti ti-x me-2"></i>Reject Transaction
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                            @endif
                            <li>
                                <a class="dropdown-item" href="{{ route('invoicing.internal-transactions.edit', $internalTransaction) }}">
                                    <i class="ti ti-edit me-2"></i>Edit Transaction
                                </a>
                            </li>
                            @if($internalTransaction->status === 'draft')
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="#" onclick="deleteTransaction({{ $internalTransaction->id }})">
                                        <i class="ti ti-trash me-2"></i>Delete Transaction
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                    <a href="{{ route('invoicing.internal-transactions.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Back to Transactions
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
                    <!-- Transaction Information -->
                    <div class="col-md-6">
                        <h6 class="mb-3">Transaction Information</h6>

                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-semibold">Transaction Number:</td>
                                <td><code class="text-primary">{{ $internalTransaction->transaction_number }}</code></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Amount:</td>
                                <td>
                                    <span class="fw-bold text-primary">{{ number_format($internalTransaction->amount, 2) }} EGP</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Transaction Date:</td>
                                <td>{{ $internalTransaction->transaction_date->format('M j, Y') }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">From Business Unit:</td>
                                <td>
                                    <span class="badge bg-label-primary">{{ $internalTransaction->fromBusinessUnit->name }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">To Business Unit:</td>
                                <td>
                                    <span class="badge bg-label-info">{{ $internalTransaction->toBusinessUnit->name }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Status:</td>
                                <td>
                                    <span class="badge {{ $internalTransaction->status_badge_class }}">
                                        {{ $internalTransaction->status_display }}
                                    </span>
                                </td>
                            </tr>
                            @if($internalTransaction->approved_at)
                            <tr>
                                <td class="fw-semibold">Approved Date:</td>
                                <td>{{ $internalTransaction->approved_at->format('M j, Y \\a\\t g:i A') }}</td>
                            </tr>
                            @endif
                            @if($internalTransaction->approved_by)
                            <tr>
                                <td class="fw-semibold">Approved By:</td>
                                <td>{{ $internalTransaction->approvedBy->name }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>

                    <!-- Additional Details -->
                    <div class="col-md-6">
                        <h6 class="mb-3">Additional Details</h6>

                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-semibold">Sequence:</td>
                                <td>
                                    <a href="{{ route('invoicing.internal-sequences.show', $internalTransaction->internalSequence) }}" class="text-primary">
                                        {{ $internalTransaction->internalSequence->name }}
                                    </a>
                                </td>
                            </tr>
                            @if($internalTransaction->reference_number)
                            <tr>
                                <td class="fw-semibold">Reference:</td>
                                <td><code>{{ $internalTransaction->reference_number }}</code></td>
                            </tr>
                            @endif
                            <tr>
                                <td class="fw-semibold">Created:</td>
                                <td>{{ $internalTransaction->created_at->format('M j, Y \\a\\t g:i A') }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Last Updated:</td>
                                <td>{{ $internalTransaction->updated_at->format('M j, Y \\a\\t g:i A') }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Created By:</td>
                                <td>{{ $internalTransaction->createdBy->name ?? 'System' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if($internalTransaction->description)
                <div class="mt-4">
                    <h6 class="mb-3">Description</h6>
                    <div class="alert alert-light">
                        {{ $internalTransaction->description }}
                    </div>
                </div>
                @endif

                @if($internalTransaction->notes)
                <div class="mt-4">
                    <h6 class="mb-3">Notes</h6>
                    <div class="alert alert-light">
                        {{ $internalTransaction->notes }}
                    </div>
                </div>
                @endif

                <!-- Transaction Timeline -->
                <div class="mt-4">
                    <h6 class="mb-3">Transaction Timeline</h6>
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Transaction Created</h6>
                                <p class="mb-0 text-muted">{{ $internalTransaction->created_at->format('M j, Y \\a\\t g:i A') }}</p>
                                <small class="text-muted">Created by {{ $internalTransaction->createdBy->name ?? 'System' }}</small>
                            </div>
                        </div>

                        @if($internalTransaction->status === 'pending')
                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Pending Approval</h6>
                                <p class="mb-0 text-muted">Waiting for approval from authorized personnel</p>
                            </div>
                        </div>
                        @endif

                        @if($internalTransaction->status === 'approved')
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Transaction Approved</h6>
                                <p class="mb-0 text-muted">{{ $internalTransaction->approved_at->format('M j, Y \\a\\t g:i A') }}</p>
                                <small class="text-muted">Approved by {{ $internalTransaction->approvedBy->name }}</small>
                            </div>
                        </div>
                        @endif

                        @if($internalTransaction->status === 'rejected')
                        <div class="timeline-item">
                            <div class="timeline-marker bg-danger"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Transaction Rejected</h6>
                                <p class="mb-0 text-muted">{{ $internalTransaction->approved_at->format('M j, Y \\a\\t g:i A') }}</p>
                                <small class="text-muted">Rejected by {{ $internalTransaction->approvedBy->name }}</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Summary Card -->
                <div class="mt-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title">Transaction Summary</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="d-flex justify-content-between">
                                        <span>From:</span>
                                        <strong>{{ $internalTransaction->fromBusinessUnit->name }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex justify-content-between">
                                        <span>To:</span>
                                        <strong>{{ $internalTransaction->toBusinessUnit->name }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex justify-content-between">
                                        <span>Amount:</span>
                                        <strong class="text-primary">{{ number_format($internalTransaction->amount, 2) }} EGP</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
function approveTransaction(transactionId) {
    if (confirm('Approve this internal transaction?')) {
        fetch(`/invoicing/admin/internal-transactions/${transactionId}/approve`, {
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

function rejectTransaction(transactionId) {
    if (confirm('Reject this internal transaction?')) {
        fetch(`/invoicing/admin/internal-transactions/${transactionId}/reject`, {
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

function deleteTransaction(transactionId) {
    if (confirm('Delete this transaction? This action cannot be undone.')) {
        fetch(`/invoicing/admin/internal-transactions/${transactionId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '/invoicing/admin/internal-transactions';
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

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -23px;
    top: 5px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #e9ecef;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #dee2e6;
}
</style>
@endsection