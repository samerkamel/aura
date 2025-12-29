@extends('layouts/layoutMaster')

@section('title', 'Create Invoice Sequence')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Create Invoice Sequence</h5>
                    <small class="text-muted">Create a new invoice numbering sequence</small>
                </div>
                <a href="{{ route('invoicing.sequences.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Back to Sequences
                </a>
            </div>

            <form action="{{ route('invoicing.sequences.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <!-- Basic Information -->
                        <div class="col-md-6">
                            <h6 class="mb-3">Basic Information</h6>

                            <div class="mb-3">
                                <label class="form-label required">Sequence Name</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" required placeholder="e.g., Default Invoice Sequence">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">Prefix</label>
                                <input type="text" name="prefix" class="form-control @error('prefix') is-invalid @enderror"
                                       value="{{ old('prefix') }}" maxlength="10" required placeholder="e.g., INV, TECH">
                                @error('prefix')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Short prefix for invoice numbers (max 10 characters)</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">Number Format</label>
                                <input type="text" name="format" class="form-control @error('format') is-invalid @enderror"
                                       value="{{ old('format', '{PREFIX}-{YEAR}-{NUMBER:6}') }}" required>
                                @error('format')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Use placeholders: {PREFIX}, {YEAR}, {MONTH}, {NUMBER:6}</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">Starting Number</label>
                                <input type="number" name="starting_number" class="form-control @error('starting_number') is-invalid @enderror"
                                       value="{{ old('starting_number', 1) }}" min="1" required>
                                @error('starting_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">The first invoice will use this number</small>
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
                                        <option value="{{ $businessUnit->id }}" {{ old('business_unit_id') == $businessUnit->id ? 'selected' : '' }}>
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
                                        <option value="{{ $sector->id }}" {{ (old('sector_ids') && in_array($sector->id, old('sector_ids'))) ? 'selected' : '' }}>
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
                                           {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Active Sequence
                                    </label>
                                </div>
                                <small class="text-muted">Only active sequences can be used for new invoices</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3"
                                          placeholder="Optional description of this sequence">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Format Examples -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="mb-3">Format Examples</h6>
                            <div class="alert alert-info">
                                <h6 class="alert-heading"><i class="ti ti-info-circle me-2"></i>Format Guide</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>Available Placeholders:</strong></p>
                                        <ul class="mb-0">
                                            <li><code>{PREFIX}</code> - The sequence prefix</li>
                                            <li><code>{YEAR}</code> - Current year (e.g., 2025)</li>
                                            <li><code>{MONTH}</code> - Current month (e.g., 09)</li>
                                            <li><code>{NUMBER:X}</code> - Sequential number with X digits padding</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>Example Formats:</strong></p>
                                        <ul class="mb-0">
                                            <li><code>{PREFIX}-{YEAR}-{NUMBER:6}</code> → <span class="badge bg-primary">INV-2025-000001</span></li>
                                            <li><code>{PREFIX}-{YEAR}-{MONTH}-{NUMBER:4}</code> → <span class="badge bg-primary">TECH-2025-09-0001</span></li>
                                            <li><code>{PREFIX}{NUMBER:5}</code> → <span class="badge bg-primary">CONS00001</span></li>
                                            <li><code>{PREFIX}-{NUMBER:3}</code> → <span class="badge bg-primary">INV-001</span></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Live Preview -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="alert alert-light">
                                <h6 class="alert-heading"><i class="ti ti-eye me-2"></i>Preview</h6>
                                <p class="mb-1"><strong>Format:</strong> <code id="preview-format">{PREFIX}-{YEAR}-{NUMBER:6}</code></p>
                                <p class="mb-0"><strong>Next Invoice Number:</strong> <span id="preview-number" class="badge bg-success">INV-2025-000001</span></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Sequence Settings</h6>
                                    <div class="d-flex justify-content-between">
                                        <span>Starting Number:</span>
                                        <span id="preview-start">1</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Active:</span>
                                        <span id="preview-active" class="badge bg-success">Yes</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('invoicing.sequences.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-x me-1"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>Create Sequence
                    </button>
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
    const startingNumberInput = document.querySelector('input[name="starting_number"]');
    const activeCheckbox = document.querySelector('input[name="is_active"]');

    function updatePreview() {
        const prefix = prefixInput.value || 'PREFIX';
        const format = formatInput.value || '{PREFIX}-{YEAR}-{NUMBER:6}';
        const startingNumber = parseInt(startingNumberInput.value) || 1;
        const isActive = activeCheckbox.checked;

        // Update format display
        document.getElementById('preview-format').textContent = format;

        // Generate preview number
        const currentYear = new Date().getFullYear();
        const currentMonth = String(new Date().getMonth() + 1).padStart(2, '0');

        let previewNumber = format;
        previewNumber = previewNumber.replace('{PREFIX}', prefix);
        previewNumber = previewNumber.replace('{YEAR}', currentYear);
        previewNumber = previewNumber.replace('{MONTH}', currentMonth);

        // Handle {NUMBER:X} format
        previewNumber = previewNumber.replace(/\{NUMBER:(\d+)\}/g, function(match, digits) {
            return String(startingNumber).padStart(parseInt(digits), '0');
        });

        // Handle simple {NUMBER} format
        previewNumber = previewNumber.replace('{NUMBER}', String(startingNumber).padStart(4, '0'));

        document.getElementById('preview-number').textContent = previewNumber;
        document.getElementById('preview-start').textContent = startingNumber;

        const activeSpan = document.getElementById('preview-active');
        if (isActive) {
            activeSpan.textContent = 'Yes';
            activeSpan.className = 'badge bg-success';
        } else {
            activeSpan.textContent = 'No';
            activeSpan.className = 'badge bg-secondary';
        }
    }

    // Add event listeners
    if (formatInput) formatInput.addEventListener('input', updatePreview);
    if (prefixInput) prefixInput.addEventListener('input', updatePreview);
    if (startingNumberInput) startingNumberInput.addEventListener('input', updatePreview);
    if (activeCheckbox) activeCheckbox.addEventListener('change', updatePreview);

    // Initial preview update
    updatePreview();
});
</script>
@endsection