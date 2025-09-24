@extends('layouts/layoutMaster')

@section('title', 'Edit Expense Schedule')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Edit Expense Schedule</h5>
                    <small class="text-muted">Update {{ $expenseSchedule->name }}</small>
                </div>
                <a href="{{ route('accounting.expenses.show', $expenseSchedule) }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Back to Details
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('accounting.expenses.update', $expenseSchedule) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <!-- Form fields similar to create but pre-filled -->
                        <div class="col-lg-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                   id="name" name="name" value="{{ old('name', $expenseSchedule->name) }}">
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12">
                                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                            <select class="form-select @error('category_id') is-invalid @enderror"
                                                    id="category_id" name="category_id">
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}"
                                                            {{ old('category_id', $expenseSchedule->category_id) == $category->id ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('category_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror"
                                                      id="description" name="description" rows="3">{{ old('description', $expenseSchedule->description) }}</textarea>
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
                                                       id="amount" name="amount" value="{{ old('amount', $expenseSchedule->amount) }}"
                                                       step="0.01" min="0" max="999999.99">
                                                @error('amount')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="frequency_type" class="form-label">Frequency Type <span class="text-danger">*</span></label>
                                            <select class="form-select @error('frequency_type') is-invalid @enderror"
                                                    id="frequency_type" name="frequency_type">
                                                <option value="weekly" {{ old('frequency_type', $expenseSchedule->frequency_type) === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                                <option value="bi-weekly" {{ old('frequency_type', $expenseSchedule->frequency_type) === 'bi-weekly' ? 'selected' : '' }}>Bi-weekly</option>
                                                <option value="monthly" {{ old('frequency_type', $expenseSchedule->frequency_type) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                                <option value="quarterly" {{ old('frequency_type', $expenseSchedule->frequency_type) === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                                <option value="yearly" {{ old('frequency_type', $expenseSchedule->frequency_type) === 'yearly' ? 'selected' : '' }}>Yearly</option>
                                            </select>
                                            @error('frequency_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="frequency_value" class="form-label">Frequency Interval <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control @error('frequency_value') is-invalid @enderror"
                                                   id="frequency_value" name="frequency_value"
                                                   value="{{ old('frequency_value', $expenseSchedule->frequency_value) }}"
                                                   min="1" max="100">
                                            @error('frequency_value')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Monthly Equivalent</label>
                                            <div class="input-group">
                                                <span class="input-group-text">EGP</span>
                                                <input type="text" class="form-control bg-light" id="monthlyEquivalent" readonly>
                                            </div>
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
                                                   id="start_date" name="start_date"
                                                   value="{{ old('start_date', $expenseSchedule->start_date->format('Y-m-d')) }}">
                                            @error('start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="end_date" class="form-label">End Date (Optional)</label>
                                            <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                                   id="end_date" name="end_date"
                                                   value="{{ old('end_date', $expenseSchedule->end_date ? $expenseSchedule->end_date->format('Y-m-d') : '') }}">
                                            @error('end_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input"
                                                       id="skip_weekends" name="skip_weekends" value="1"
                                                       {{ old('skip_weekends', $expenseSchedule->skip_weekends) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="skip_weekends">Skip weekends</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Current Status Panel -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Current Status</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <div>
                                            <span class="badge bg-{{ $expenseSchedule->is_active ? 'success' : 'secondary' }}">
                                                {{ $expenseSchedule->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Current Monthly Equivalent</label>
                                        <div class="h5 text-warning">{{ number_format($expenseSchedule->monthly_equivalent_amount, 2) }} EGP</div>
                                    </div>

                                    @if($expenseSchedule->next_payment_date)
                                        <div class="mb-3">
                                            <label class="form-label">Next Payment</label>
                                            <div>{{ $expenseSchedule->next_payment_date->format('M j, Y') }}</div>
                                            <small class="text-muted">{{ $expenseSchedule->next_payment_date->diffForHumans() }}</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-footer d-flex justify-content-between">
                                    <a href="{{ route('accounting.expenses.show', $expenseSchedule) }}" class="btn btn-outline-secondary">
                                        <i class="ti ti-x me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-device-floppy me-1"></i>Save Changes
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
});
</script>
@endsection