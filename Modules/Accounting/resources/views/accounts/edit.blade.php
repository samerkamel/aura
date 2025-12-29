@extends('layouts/layoutMaster')

@section('title', 'Edit Account - ' . $account->name)

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Edit Account</h5>
                    <small class="text-muted">Update {{ $account->name }} account details</small>
                </div>
                <a href="{{ route('accounting.accounts.show', $account) }}" class="btn btn-outline-secondary">
                    <i class="ti tabler-arrow-left me-1"></i>Back to Account
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('accounting.accounts.update', $account) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <!-- Main Content -->
                        <div class="col-lg-8">
                            <!-- Basic Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Account Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="name" class="form-label">Account Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                   id="name" name="name" value="{{ old('name', $account->name) }}"
                                                   placeholder="e.g., Main Cash Account, CIB Checking">
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="type" class="form-label">Account Type <span class="text-danger">*</span></label>
                                            <select class="form-select @error('type') is-invalid @enderror"
                                                    id="type" name="type">
                                                <option value="">Select account type</option>
                                                <option value="cash" {{ old('type', $account->type) === 'cash' ? 'selected' : '' }}>Cash</option>
                                                <option value="bank" {{ old('type', $account->type) === 'bank' ? 'selected' : '' }}>Bank Account</option>
                                                <option value="credit_card" {{ old('type', $account->type) === 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                                                <option value="digital_wallet" {{ old('type', $account->type) === 'digital_wallet' ? 'selected' : '' }}>Digital Wallet</option>
                                                <option value="other" {{ old('type', $account->type) === 'other' ? 'selected' : '' }}>Other</option>
                                            </select>
                                            @error('type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="currency" class="form-label">Currency <span class="text-danger">*</span></label>
                                            <select class="form-select @error('currency') is-invalid @enderror"
                                                    id="currency" name="currency">
                                                <option value="EGP" {{ old('currency', $account->currency) === 'EGP' ? 'selected' : '' }}>Egyptian Pound (EGP)</option>
                                                <option value="USD" {{ old('currency', $account->currency) === 'USD' ? 'selected' : '' }}>US Dollar (USD)</option>
                                                <option value="EUR" {{ old('currency', $account->currency) === 'EUR' ? 'selected' : '' }}>Euro (EUR)</option>
                                                <option value="GBP" {{ old('currency', $account->currency) === 'GBP' ? 'selected' : '' }}>British Pound (GBP)</option>
                                                <option value="SAR" {{ old('currency', $account->currency) === 'SAR' ? 'selected' : '' }}>Saudi Riyal (SAR)</option>
                                                <option value="AED" {{ old('currency', $account->currency) === 'AED' ? 'selected' : '' }}>UAE Dirham (AED)</option>
                                            </select>
                                            @error('currency')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror"
                                                      id="description" name="description" rows="3"
                                                      placeholder="Optional description of this account">{{ old('description', $account->description) }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Account Details -->
                            <div class="card mb-4" id="accountDetails">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Account Details</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6" id="bankNameField" style="{{ $account->type === 'bank' ? 'display: block;' : 'display: none;' }}">
                                            <label for="bank_name" class="form-label">Bank Name</label>
                                            <input type="text" class="form-control @error('bank_name') is-invalid @enderror"
                                                   id="bank_name" name="bank_name" value="{{ old('bank_name', $account->bank_name) }}"
                                                   placeholder="e.g., Commercial International Bank">
                                            @error('bank_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="account_number" class="form-label">Account Number</label>
                                            <input type="text" class="form-control @error('account_number') is-invalid @enderror"
                                                   id="account_number" name="account_number" value="{{ old('account_number', $account->account_number) }}"
                                                   placeholder="Optional account identifier">
                                            @error('account_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="starting_balance" class="form-label">Starting Balance <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text" id="currencySymbol">{{ $account->currency }}</span>
                                                <input type="number" class="form-control @error('starting_balance') is-invalid @enderror"
                                                       id="starting_balance" name="starting_balance" value="{{ old('starting_balance', $account->starting_balance) }}"
                                                       step="0.01" placeholder="0.00">
                                                @error('starting_balance')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <small class="text-muted">Adjusting this will affect the current balance proportionally</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="col-lg-4">
                            <!-- Current Status -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Current Status</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Account Type</label>
                                        <div>
                                            <span class="badge {{ $account->type_badge_class }}">
                                                {{ $account->type_display }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Current Balance</label>
                                        <div class="h5 {{ $account->current_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($account->current_balance, 2) }} {{ $account->currency }}
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <div>
                                            <span class="badge bg-{{ $account->is_active ? 'success' : 'secondary' }}">
                                                {{ $account->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label">Associated Expenses</label>
                                        <div>{{ $account->expenseSchedules->count() }} expenses</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Update Guidelines -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Update Guidelines</h6>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="ti tabler-info-circle me-2"></i>
                                        <strong>Important Notes</strong>
                                        <ul class="mb-0 mt-2">
                                            <li>Changing starting balance adjusts current balance</li>
                                            <li>Currency changes affect all calculations</li>
                                            <li>Account type changes may affect display</li>
                                        </ul>
                                    </div>

                                    @if($account->expenseSchedules->count() > 0)
                                        <div class="alert alert-warning">
                                            <i class="ti tabler-alert-triangle me-2"></i>
                                            <strong>Active Expenses</strong>
                                            <p class="mb-0">This account has associated expense payments. Consider their impact when making changes.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Account Type Guide -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Account Types</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <span class="badge bg-success me-2">Cash</span>
                                        <small>Physical cash on hand</small>
                                    </div>
                                    <div class="mb-2">
                                        <span class="badge bg-primary me-2">Bank</span>
                                        <small>Bank checking/savings accounts</small>
                                    </div>
                                    <div class="mb-2">
                                        <span class="badge bg-warning me-2">Credit Card</span>
                                        <small>Credit card accounts</small>
                                    </div>
                                    <div class="mb-2">
                                        <span class="badge bg-info me-2">Digital Wallet</span>
                                        <small>PayPal, Stripe, mobile wallets</small>
                                    </div>
                                    <div class="mb-0">
                                        <span class="badge bg-secondary me-2">Other</span>
                                        <small>Investment accounts, etc.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-footer d-flex justify-content-between">
                                    <a href="{{ route('accounting.accounts.show', $account) }}" class="btn btn-outline-secondary">
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
    const typeSelect = document.getElementById('type');
    const bankNameField = document.getElementById('bankNameField');
    const currencySelect = document.getElementById('currency');
    const currencySymbol = document.getElementById('currencySymbol');

    // Handle account type changes
    function toggleBankFields() {
        if (typeSelect.value === 'bank') {
            bankNameField.style.display = 'block';
            document.getElementById('bank_name').required = true;
        } else {
            bankNameField.style.display = 'none';
            document.getElementById('bank_name').required = false;
        }
    }

    // Handle currency symbol update
    function updateCurrencySymbol() {
        currencySymbol.textContent = currencySelect.value;
    }

    // Event listeners
    typeSelect.addEventListener('change', toggleBankFields);
    currencySelect.addEventListener('change', updateCurrencySymbol);

    // Initialize
    toggleBankFields();
});
</script>
@endsection