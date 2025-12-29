@extends('layouts/layoutMaster')

@section('title', 'Create Internal Transaction')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Create Internal Transaction</h5>
                    <small class="text-muted">Create a new transaction between business units</small>
                </div>
                <a href="{{ route('invoicing.internal-transactions.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Back to Transactions
                </a>
            </div>

            <form action="{{ route('invoicing.internal-transactions.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <!-- Transaction Information -->
                        <div class="col-md-6">
                            <h6 class="mb-3">Transaction Information</h6>

                            <div class="mb-3">
                                <label class="form-label required">From Business Unit</label>
                                <select name="from_business_unit_id" class="form-select @error('from_business_unit_id') is-invalid @enderror" required>
                                    <option value="">Select Business Unit</option>
                                    @foreach($businessUnits as $businessUnit)
                                        <option value="{{ $businessUnit->id }}" {{ old('from_business_unit_id') == $businessUnit->id ? 'selected' : '' }}>
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
                                        <option value="{{ $businessUnit->id }}" {{ old('to_business_unit_id') == $businessUnit->id ? 'selected' : '' }}>
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
                                       value="{{ old('transaction_date', date('Y-m-d')) }}" required>
                                @error('transaction_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Sequence and Amount -->
                        <div class="col-md-6">
                            <h6 class="mb-3">Sequence & Amount</h6>

                            <div class="mb-3">
                                <label class="form-label">Transaction Sequence</label>
                                <select name="internal_sequence_id" class="form-select @error('internal_sequence_id') is-invalid @enderror">
                                    <option value="">Auto-select sequence</option>
                                    @foreach($sequences as $sequence)
                                        <option value="{{ $sequence->id }}" {{ old('internal_sequence_id') == $sequence->id ? 'selected' : '' }}>
                                            {{ $sequence->name }} ({{ $sequence->previewNextTransactionNumber() }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('internal_sequence_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Leave blank to auto-select based on business unit and sector</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">Total Amount</label>
                                <div class="input-group">
                                    <input type="number" name="total_amount" class="form-control @error('total_amount') is-invalid @enderror"
                                           value="{{ old('total_amount') }}" min="0.01" step="0.01" required placeholder="0.00">
                                    <span class="input-group-text">EGP</span>
                                </div>
                                @error('total_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Reference</label>
                                <input type="text" name="reference" class="form-control @error('reference') is-invalid @enderror"
                                       value="{{ old('reference') }}" placeholder="Optional reference number">
                                @error('reference')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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
                                       value="{{ old('description') }}" required placeholder="Brief description of the transaction">
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="4"
                                          placeholder="Additional details, terms, or notes about this transaction...">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Preview -->
                    <div class="row mt-4">
                        <div class="col-md-8">
                            <div class="alert alert-info">
                                <h6 class="alert-heading"><i class="ti ti-info-circle me-2"></i>Transaction Preview</h6>
                                <div id="transaction-preview">
                                    <p class="mb-1"><strong>From:</strong> <span id="preview-from">Not selected</span></p>
                                    <p class="mb-1"><strong>To:</strong> <span id="preview-to">Not selected</span></p>
                                    <p class="mb-1"><strong>Amount:</strong> <span id="preview-amount">0.00 EGP</span></p>
                                    <p class="mb-0"><strong>Status:</strong> <span class="badge bg-secondary">Draft</span></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Transaction Summary</h6>
                                    <div class="d-flex justify-content-between">
                                        <span>Amount:</span>
                                        <span id="summary-amount">0.00 EGP</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Status:</span>
                                        <span class="badge bg-secondary">Draft</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>Total:</span>
                                        <span id="summary-total">0.00 EGP</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('invoicing.internal-transactions.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-x me-1"></i>Cancel
                    </a>
                    <div>
                        <button type="submit" name="action" value="draft" class="btn btn-outline-primary me-2">
                            <i class="ti ti-device-floppy me-1"></i>Save as Draft
                        </button>
                        <button type="submit" name="action" value="submit" class="btn btn-primary">
                            <i class="ti ti-send me-1"></i>Save & Submit for Approval
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
document.addEventListener('DOMContentLoaded', function() {
    const fromSelect = document.querySelector('select[name="from_business_unit_id"]');
    const toSelect = document.querySelector('select[name="to_business_unit_id"]');
    const amountInput = document.querySelector('input[name="total_amount"]');

    // Update preview when form changes
    function updatePreview() {
        const fromText = fromSelect.options[fromSelect.selectedIndex].text;
        const toText = toSelect.options[toSelect.selectedIndex].text;
        const amount = parseFloat(amountInput.value) || 0;

        document.getElementById('preview-from').textContent = fromSelect.value ? fromText : 'Not selected';
        document.getElementById('preview-to').textContent = toSelect.value ? toText : 'Not selected';
        document.getElementById('preview-amount').textContent = amount.toFixed(2) + ' EGP';
        document.getElementById('summary-amount').textContent = amount.toFixed(2) + ' EGP';
        document.getElementById('summary-total').textContent = amount.toFixed(2) + ' EGP';
    }

    // Prevent selecting same business unit for from and to
    function validateBusinessUnits() {
        if (fromSelect.value && toSelect.value && fromSelect.value === toSelect.value) {
            alert('From and To business units cannot be the same.');
            toSelect.value = '';
            updatePreview();
        }
    }

    fromSelect.addEventListener('change', function() {
        validateBusinessUnits();
        updatePreview();
    });

    toSelect.addEventListener('change', function() {
        validateBusinessUnits();
        updatePreview();
    });

    amountInput.addEventListener('input', updatePreview);

    // Initial preview update
    updatePreview();
});
</script>
@endsection