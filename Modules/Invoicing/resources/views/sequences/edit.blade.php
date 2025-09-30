@extends('layouts/layoutMaster')

@section('title', 'Edit Invoice Sequence')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Edit Invoice Sequence</h5>
                    <small class="text-muted">Modify invoice sequence settings</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('invoicing.sequences.show', $invoiceSequence) }}" class="btn btn-outline-info">
                        <i class="ti ti-eye me-1"></i>View Sequence
                    </a>
                    <a href="{{ route('invoicing.sequences.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Back to Sequences
                    </a>
                </div>
            </div>

            <form action="{{ route('invoicing.sequences.update', $invoiceSequence) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <!-- Basic Information -->
                        <div class="col-md-6">
                            <h6 class="mb-3">Basic Information</h6>

                            <div class="mb-3">
                                <label class="form-label required">Sequence Name</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $invoiceSequence->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">Prefix</label>
                                <input type="text" name="prefix" class="form-control @error('prefix') is-invalid @enderror"
                                       value="{{ old('prefix', $invoiceSequence->prefix) }}" maxlength="10" required>
                                @error('prefix')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Short prefix for invoice numbers (e.g., INV, TECH)</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">Number Format</label>
                                <input type="text" name="format" class="form-control @error('format') is-invalid @enderror"
                                       value="{{ old('format', $invoiceSequence->format) }}" required>
                                @error('format')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Use placeholders: {PREFIX}, {YEAR}, {MONTH}, {NUMBER:6}</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">Starting Number</label>
                                <input type="number" name="starting_number" class="form-control @error('starting_number') is-invalid @enderror"
                                       value="{{ old('starting_number', $invoiceSequence->starting_number) }}" min="1" required>
                                @error('starting_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Current: {{ $invoiceSequence->current_number }}</small>
                            </div>
                        </div>

                        <!-- Access Control -->
                        <div class="col-md-6">
                            <h6 class="mb-3">Access Control</h6>

                            <div class="mb-3">
                                <label class="form-label">Business Unit</label>
                                <select name="business_unit_id" class="form-select @error('business_unit_id') is-invalid @enderror">
                                    <option value="">All Business Units</option>
                                    @foreach($businessUnits as $businessUnit)
                                        <option value="{{ $businessUnit->id }}" {{ (old('business_unit_id', $invoiceSequence->business_unit_id) == $businessUnit->id) ? 'selected' : '' }}>
                                            {{ $businessUnit->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('business_unit_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Restrict to specific business unit (optional)</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Sectors</label>
                                <select name="sector_ids[]" class="form-select @error('sector_ids') is-invalid @enderror" multiple>
                                    @foreach($sectors as $sector)
                                        <option value="{{ $sector->id }}"
                                                {{ (old('sector_ids', $invoiceSequence->sector_ids ?: []) && in_array($sector->id, old('sector_ids', $invoiceSequence->sector_ids ?: []))) ? 'selected' : '' }}>
                                            {{ $sector->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('sector_ids')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Hold Ctrl/Cmd to select multiple. Leave empty for all sectors.</small>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                           {{ old('is_active', $invoiceSequence->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Active Sequence
                                    </label>
                                </div>
                                <small class="text-muted">Only active sequences can be used for new invoices</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3"
                                          placeholder="Optional description of this sequence">{{ old('description', $invoiceSequence->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Preview Section -->
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="mb-3">Current Status</h6>
                            <div class="alert alert-info">
                                <h6 class="alert-heading"><i class="ti ti-info-circle me-2"></i>Sequence Information</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Current Number:</strong> {{ $invoiceSequence->current_number }}</p>
                                        <p class="mb-1"><strong>Invoices Created:</strong> {{ $invoiceSequence->invoices->count() }}</p>
                                        <p class="mb-0"><strong>Status:</strong>
                                            <span class="badge {{ $invoiceSequence->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $invoiceSequence->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Next Number:</strong> {{ $invoiceSequence->previewNextInvoiceNumber() }}</p>
                                        <p class="mb-1"><strong>Business Unit:</strong> {{ $invoiceSequence->businessUnit->name ?? 'All Units' }}</p>
                                        <p class="mb-0"><strong>Sectors:</strong>
                                            @if($invoiceSequence->sector_ids)
                                                {{ $invoiceSequence->sectors()->pluck('name')->join(', ') }}
                                            @else
                                                All Sectors
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-3">Format Preview</h6>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="mb-2">
                                        <strong>Current Format:</strong>
                                        <code class="d-block mt-1">{{ $invoiceSequence->format }}</code>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Example Output:</strong>
                                        <span class="badge bg-primary d-block mt-1">{{ $invoiceSequence->previewNextInvoiceNumber() }}</span>
                                    </div>
                                    <small class="text-muted">
                                        Available placeholders:<br>
                                        • {PREFIX} - Sequence prefix<br>
                                        • {YEAR} - Current year<br>
                                        • {MONTH} - Current month<br>
                                        • {NUMBER:X} - Number with X digits
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('invoicing.sequences.show', $invoiceSequence) }}" class="btn btn-outline-secondary">
                        <i class="ti ti-x me-1"></i>Cancel
                    </a>
                    <div>
                        @if($invoiceSequence->invoices->count() > 0)
                            <span class="text-muted me-3">
                                <i class="ti ti-alert-triangle me-1"></i>
                                {{ $invoiceSequence->invoices->count() }} invoices use this sequence
                            </span>
                        @endif
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i>Update Sequence
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
    const formatInput = document.querySelector('input[name="format"]');
    const prefixInput = document.querySelector('input[name="prefix"]');

    // Update preview when format or prefix changes
    function updatePreview() {
        // This would be enhanced to show live preview
        // For now, users can see the current preview on the right
    }

    if (formatInput) {
        formatInput.addEventListener('input', updatePreview);
    }

    if (prefixInput) {
        prefixInput.addEventListener('input', updatePreview);
    }
});
</script>
@endsection