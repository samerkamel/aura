@extends('layouts/layoutMaster')

@section('title', 'Edit Internal Transaction')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Edit Internal Transaction #{{ $internalTransaction->transaction_number }}</h5>
                    <small class="text-muted">Modify transaction details</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('invoicing.internal-transactions.show', $internalTransaction) }}" class="btn btn-outline-info">
                        <i class="ti tabler-eye me-1"></i>View Transaction
                    </a>
                    <a href="{{ route('invoicing.internal-transactions.index') }}" class="btn btn-outline-secondary">
                        <i class="ti tabler-arrow-left me-1"></i>Back to Transactions
                    </a>
                </div>
            </div>

            @if($internalTransaction->status !== 'draft')
                <div class="alert alert-warning mx-4 mt-3" role="alert">
                    <i class="ti tabler-alert-triangle me-2"></i>
                    This transaction has status "{{ $internalTransaction->status_display }}". Changes may affect accounting records.
                </div>
            @endif

            <form action="{{ route('invoicing.internal-transactions.update', $internalTransaction) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <!-- Transaction Information -->
                        <div class="col-md-6">
                            <h6 class="mb-3">Transaction Information</h6>

                            <div class="mb-3">
                                <label class="form-label">Transaction Number</label>
                                <input type="text" class="form-control" value="{{ $internalTransaction->transaction_number }}" readonly>
                                <small class="text-muted">Transaction number cannot be changed</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">From Business Unit</label>
                                <select name="from_business_unit_id" class="form-select @error('from_business_unit_id') is-invalid @enderror" required>
                                    <option value="">Select Business Unit</option>
                                    @foreach($businessUnits as $businessUnit)
                                        <option value="{{ $businessUnit->id }}" {{ (old('from_business_unit_id', $internalTransaction->from_business_unit_id) == $businessUnit->id) ? 'selected' : '' }}>
                                            {{ $businessUnit->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('from_business_unit_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">To Business Unit</label>
                                <select name="to_business_unit_id" class="form-select @error('to_business_unit_id') is-invalid @enderror" required>
                                    <option value="">Select Business Unit</option>
                                    @foreach($businessUnits as $businessUnit)
                                        <option value="{{ $businessUnit->id }}" {{ (old('to_business_unit_id', $internalTransaction->to_business_unit_id) == $businessUnit->id) ? 'selected' : '' }}>
                                            {{ $businessUnit->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('to_business_unit_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">Transaction Date</label>
                                <input type="date" name="transaction_date" class="form-control @error('transaction_date') is-invalid @enderror"
                                       value="{{ old('transaction_date', $internalTransaction->transaction_date->format('Y-m-d')) }}" required>
                                @error('transaction_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Sequence and Amount -->
                        <div class="col-md-6">
                            <h6 class="mb-3">Amount & Reference</h6>

                            <div class="mb-3">
                                <label class="form-label required">Total Amount</label>
                                <div class="input-group">
                                    <input type="number" name="total_amount" class="form-control @error('total_amount') is-invalid @enderror"
                                           value="{{ old('total_amount', $internalTransaction->total_amount) }}" min="0.01" step="0.01" required>
                                    <span class="input-group-text">EGP</span>
                                </div>
                                @error('total_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Reference</label>
                                <input type="text" name="reference" class="form-control @error('reference') is-invalid @enderror"
                                       value="{{ old('reference', $internalTransaction->reference) }}" placeholder="Optional reference number">
                                @error('reference')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Current Status</label>
                                <div class="d-flex align-items-center">
                                    <span class="badge {{ $internalTransaction->status_badge_class }} me-2">{{ $internalTransaction->status_display }}</span>
                                    <span class="badge {{ $internalTransaction->approval_status_badge_class }}">{{ $internalTransaction->approval_status_display }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Description and Notes -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="mb-3">Transaction Details</h6>

                            <div class="mb-3">
                                <label class="form-label required">Description</label>
                                <input type="text" name="description" class="form-control @error('description') is-invalid @enderror"
                                       value="{{ old('description', $internalTransaction->description) }}" required placeholder="Brief description of the transaction">
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="4"
                                          placeholder="Additional details, terms, or notes about this transaction...">{{ old('notes', $internalTransaction->notes) }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Summary -->
                    <div class="row mt-4">
                        <div class="col-md-8">
                            <div class="alert alert-info">
                                <h6 class="alert-heading"><i class="ti tabler-info-circle me-2"></i>Transaction Summary</h6>
                                <div id="transaction-preview">
                                    <p class="mb-1"><strong>From:</strong> {{ $internalTransaction->fromBusinessUnit->name }}</p>
                                    <p class="mb-1"><strong>To:</strong> {{ $internalTransaction->toBusinessUnit->name }}</p>
                                    <p class="mb-1"><strong>Amount:</strong> {{ number_format($internalTransaction->total_amount, 2) }} EGP</p>
                                    <p class="mb-0"><strong>Status:</strong> <span class="badge {{ $internalTransaction->status_badge_class }}">{{ $internalTransaction->status_display }}</span></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Transaction History</h6>
                                    <div class="d-flex justify-content-between">
                                        <span>Created:</span>
                                        <span>{{ $internalTransaction->created_at->format('M j, Y') }}</span>
                                    </div>
                                    @if($internalTransaction->approved_at)
                                    <div class="d-flex justify-content-between">
                                        <span>Approved:</span>
                                        <span>{{ $internalTransaction->approved_at->format('M j, Y') }}</span>
                                    </div>
                                    @endif
                                    <hr>
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>Amount:</span>
                                        <span>{{ number_format($internalTransaction->total_amount, 2) }} EGP</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('invoicing.internal-transactions.show', $internalTransaction) }}" class="btn btn-outline-secondary">
                        <i class="ti tabler-x me-1"></i>Cancel
                    </a>
                    <div>
                        <button type="submit" name="action" value="draft" class="btn btn-outline-primary me-2">
                            <i class="ti tabler-device-floppy me-1"></i>Save Changes
                        </button>
                        @if($internalTransaction->status === 'draft')
                            <button type="submit" name="action" value="submit" class="btn btn-primary">
                                <i class="ti tabler-send me-1"></i>Save & Submit for Approval
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
document.addEventListener('DOMContentLoaded', function() {
    const fromSelect = document.querySelector('select[name="from_business_unit_id"]');
    const toSelect = document.querySelector('select[name="to_business_unit_id"]');

    // Prevent selecting same business unit for from and to
    function validateBusinessUnits() {
        if (fromSelect.value && toSelect.value && fromSelect.value === toSelect.value) {
            alert('From and To business units cannot be the same.');
            toSelect.value = '{{ $internalTransaction->to_business_unit_id }}'; // Reset to original value
        }
    }

    fromSelect.addEventListener('change', validateBusinessUnits);
    toSelect.addEventListener('change', validateBusinessUnits);
});
</script>
@endsection