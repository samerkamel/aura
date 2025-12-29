@extends('layouts/layoutMaster')

@section('title', 'Edit Income Schedule')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Edit Income Schedule</h5>
                    <small class="text-muted">Update {{ $incomeSchedule->name }}</small>
                </div>
                <a href="{{ route('accounting.income.schedules.show', $incomeSchedule) }}" class="btn btn-outline-secondary">
                    <i class="ti tabler-arrow-left me-1"></i>Back to Details
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('accounting.income.schedules.update', $incomeSchedule) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <!-- Form fields -->
                        <div class="col-lg-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="contract_id" class="form-label">Contract <span class="text-danger">*</span></label>
                                            <select class="form-select @error('contract_id') is-invalid @enderror"
                                                    id="contract_id" name="contract_id">
                                                @foreach($contracts as $contract)
                                                    <option value="{{ $contract->id }}" {{ old('contract_id', $incomeSchedule->contract_id) == $contract->id ? 'selected' : '' }}>
                                                        {{ $contract->contract_number }} - {{ $contract->client_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('contract_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="name" class="form-label">Schedule Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                   id="name" name="name" value="{{ old('name', $incomeSchedule->name) }}"
                                                   placeholder="e.g., Monthly Retainer, Project Milestone">
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror"
                                                      id="description" name="description" rows="3"
                                                      placeholder="Description of this income schedule">{{ old('description', $incomeSchedule->description) }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Amount & Frequency -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Amount & Frequency</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">EGP</span>
                                                <input type="number" class="form-control @error('amount') is-invalid @enderror"
                                                       id="amount" name="amount" value="{{ old('amount', $incomeSchedule->amount) }}"
                                                       step="0.01" min="0" max="999999.99" placeholder="0.00">
                                                @error('amount')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="frequency_type" class="form-label">Frequency Type <span class="text-danger">*</span></label>
                                            <select class="form-select @error('frequency_type') is-invalid @enderror"
                                                    id="frequency_type" name="frequency_type">
                                                <option value="">Select Frequency</option>
                                                <option value="weekly" {{ old('frequency_type', $incomeSchedule->frequency_type) === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                                <option value="bi-weekly" {{ old('frequency_type', $incomeSchedule->frequency_type) === 'bi-weekly' ? 'selected' : '' }}>Bi-weekly</option>
                                                <option value="monthly" {{ old('frequency_type', $incomeSchedule->frequency_type) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                                <option value="quarterly" {{ old('frequency_type', $incomeSchedule->frequency_type) === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                                <option value="yearly" {{ old('frequency_type', $incomeSchedule->frequency_type) === 'yearly' ? 'selected' : '' }}>Yearly</option>
                                            </select>
                                            @error('frequency_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="frequency_value" class="form-label">Frequency Interval <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control @error('frequency_value') is-invalid @enderror"
                                                   id="frequency_value" name="frequency_value"
                                                   value="{{ old('frequency_value', $incomeSchedule->frequency_value) }}"
                                                   min="1" max="100" placeholder="1">
                                            @error('frequency_value')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">How often? (e.g., every 2 weeks, every 3 months)</small>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Monthly Equivalent</label>
                                            <div class="input-group">
                                                <span class="input-group-text">EGP</span>
                                                <input type="text" class="form-control bg-light" id="monthlyEquivalent" readonly placeholder="0.00">
                                            </div>
                                            <small class="text-muted">Calculated automatically based on amount and frequency</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Scheduling Options -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Scheduling Options</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                                   id="start_date" name="start_date" value="{{ old('start_date', $incomeSchedule->start_date->format('Y-m-d')) }}">
                                            @error('start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="end_date" class="form-label">End Date (Optional)</label>
                                            <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                                   id="end_date" name="end_date" value="{{ old('end_date', $incomeSchedule->end_date ? $incomeSchedule->end_date->format('Y-m-d') : '') }}">
                                            @error('end_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Leave blank for ongoing income</small>
                                        </div>

                                        <div class="col-12">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input"
                                                       id="skip_weekends" name="skip_weekends" value="1"
                                                       {{ old('skip_weekends', $incomeSchedule->skip_weekends) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="skip_weekends">Skip weekends</label>
                                                <small class="text-muted d-block">If a payment falls on a weekend, move it to the next business day</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Excluded Dates (Optional) -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Excluded Dates (Optional)</h6>
                                </div>
                                <div class="card-body">
                                    <div id="excludedDatesContainer">
                                        <div class="row g-2 mb-2" id="excludedDateTemplate" style="display: none;">
                                            <div class="col-md-10">
                                                <input type="date" class="form-control" name="excluded_dates[]">
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeExcludedDate(this)">
                                                    <i class="ti tabler-x"></i>
                                                </button>
                                            </div>
                                        </div>

                                        @if($incomeSchedule->excluded_dates && count($incomeSchedule->excluded_dates) > 0)
                                            @foreach($incomeSchedule->excluded_dates as $index => $excludedDate)
                                                <div class="row g-2 mb-2" id="excludedDate_{{ $index }}">
                                                    <div class="col-md-10">
                                                        <input type="date" class="form-control" name="excluded_dates[]" value="{{ \Carbon\Carbon::parse($excludedDate)->format('Y-m-d') }}">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeExcludedDate(this)">
                                                            <i class="ti tabler-x"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addExcludedDate()">
                                        <i class="ti tabler-plus me-1"></i>Add Excluded Date
                                    </button>
                                    <small class="text-muted d-block mt-2">Add specific dates when payments should not occur (e.g., holidays)</small>
                                </div>
                            </div>
                        </div>

                        <!-- Side Panel -->
                        <div class="col-lg-4">
                            <!-- Current Status Panel -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Current Status</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <div>
                                            <span class="badge bg-{{ $incomeSchedule->is_active ? 'success' : 'secondary' }}">
                                                {{ $incomeSchedule->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Current Monthly Equivalent</label>
                                        <div class="h5 text-info">{{ number_format($incomeSchedule->monthly_equivalent_amount, 2) }} EGP</div>
                                    </div>

                                    @if($incomeSchedule->next_payment_date)
                                        <div class="mb-3">
                                            <label class="form-label">Next Payment</label>
                                            <div>{{ $incomeSchedule->next_payment_date->format('M j, Y') }}</div>
                                            <small class="text-muted">{{ $incomeSchedule->next_payment_date->diffForHumans() }}</small>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Contract Details -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Current Contract</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Contract Number</label>
                                        <div class="h6">{{ $incomeSchedule->contract->contract_number }}</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Client</label>
                                        <div class="h6">{{ $incomeSchedule->contract->client_name }}</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Contract Total</label>
                                        <div class="h6 text-success">{{ number_format($incomeSchedule->contract->total_amount, 2) }} EGP</div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Update Guidelines</h6>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="ti tabler-info-circle me-2"></i>
                                        <strong>Important Notes</strong>
                                        <ul class="mb-0 mt-2">
                                            <li>Amount changes affect cash flow projections</li>
                                            <li>Frequency changes recalculate payment dates</li>
                                            <li>Date changes may affect upcoming payments</li>
                                            <li>Changes are applied immediately to active schedules</li>
                                        </ul>
                                    </div>

                                    <div class="alert alert-warning">
                                        <i class="ti tabler-alert-triangle me-2"></i>
                                        <strong>Schedule Impact</strong>
                                        <p class="mb-0">This schedule is currently {{ $incomeSchedule->is_active ? 'active' : 'inactive' }} and contributes to cash flow calculations.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Frequency Guide</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <span class="badge bg-primary me-2">Weekly</span>
                                        <small>Every 7 days</small>
                                    </div>
                                    <div class="mb-2">
                                        <span class="badge bg-primary me-2">Bi-weekly</span>
                                        <small>Every 14 days</small>
                                    </div>
                                    <div class="mb-2">
                                        <span class="badge bg-primary me-2">Monthly</span>
                                        <small>Same date each month</small>
                                    </div>
                                    <div class="mb-2">
                                        <span class="badge bg-primary me-2">Quarterly</span>
                                        <small>Every 3 months</small>
                                    </div>
                                    <div class="mb-0">
                                        <span class="badge bg-primary me-2">Yearly</span>
                                        <small>Annual payments</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-footer d-flex justify-content-between">
                                    <a href="{{ route('accounting.income.schedules.show', $incomeSchedule) }}" class="btn btn-outline-secondary">
                                        <i class="ti tabler-x me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti tabler-device-floppy me-1"></i>Save Changes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.getElementById('amount');
    const frequencyTypeSelect = document.getElementById('frequency_type');
    const frequencyValueInput = document.getElementById('frequency_value');
    const monthlyEquivalentInput = document.getElementById('monthlyEquivalent');

    function calculateMonthlyEquivalent() {
        const amount = parseFloat(amountInput.value) || 0;
        const frequencyType = frequencyTypeSelect.value;
        const frequencyValue = parseInt(frequencyValueInput.value) || 1;

        if (amount > 0 && frequencyType) {
            let multiplier;
            switch(frequencyType) {
                case 'weekly':
                    multiplier = 4.33 / frequencyValue;
                    break;
                case 'bi-weekly':
                    multiplier = 2.17 / frequencyValue;
                    break;
                case 'monthly':
                    multiplier = 1 / frequencyValue;
                    break;
                case 'quarterly':
                    multiplier = 1 / (frequencyValue * 3);
                    break;
                case 'yearly':
                    multiplier = 1 / (frequencyValue * 12);
                    break;
                default:
                    multiplier = 1;
            }

            const monthlyAmount = (amount * multiplier).toFixed(2);
            monthlyEquivalentInput.value = monthlyAmount;
        } else {
            monthlyEquivalentInput.value = '0.00';
        }
    }

    [amountInput, frequencyTypeSelect, frequencyValueInput].forEach(element => {
        element.addEventListener('input', calculateMonthlyEquivalent);
        element.addEventListener('change', calculateMonthlyEquivalent);
    });

    // Initial calculation
    calculateMonthlyEquivalent();

    // Excluded dates functionality
    let excludedDateIndex = {{ $incomeSchedule->excluded_dates ? count($incomeSchedule->excluded_dates) : 0 }};

    window.addExcludedDate = function() {
        const container = document.getElementById('excludedDatesContainer');
        const template = document.getElementById('excludedDateTemplate');
        const newRow = template.cloneNode(true);

        newRow.style.display = 'flex';
        newRow.id = 'excludedDate_' + excludedDateIndex++;

        container.appendChild(newRow);
    };

    window.removeExcludedDate = function(button) {
        button.closest('.row').remove();
    };
});
</script>
@endsection