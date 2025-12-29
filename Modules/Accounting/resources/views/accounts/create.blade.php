@extends('layouts/layoutMaster')

@section('title', 'Create Account')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Create New Account</h5>
                    <small class="text-muted">Add a financial account to track expenses and balances</small>
                </div>
                <a href="{{ route('accounting.accounts.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Back to Accounts
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('accounting.accounts.store') }}" method="POST">
                    @csrf

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
                                                   id="name" name="name" value="{{ old('name') }}"
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
                                                <option value="cash" {{ old('type') === 'cash' ? 'selected' : '' }}>Cash</option>
                                                <option value="bank" {{ old('type') === 'bank' ? 'selected' : '' }}>Bank Account</option>
                                                <option value="credit_card" {{ old('type') === 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                                                <option value="digital_wallet" {{ old('type') === 'digital_wallet' ? 'selected' : '' }}>Digital Wallet</option>
                                                <option value="other" {{ old('type') === 'other' ? 'selected' : '' }}>Other</option>
                                            </select>
                                            @error('type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="currency" class="form-label">Currency <span class="text-danger">*</span></label>
                                            <select class="form-select @error('currency') is-invalid @enderror"
                                                    id="currency" name="currency">
                                                <option value="EGP" {{ old('currency', 'EGP') === 'EGP' ? 'selected' : '' }}>Egyptian Pound (EGP)</option>
                                                <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>US Dollar (USD)</option>
                                                <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>Euro (EUR)</option>
                                                <option value="GBP" {{ old('currency') === 'GBP' ? 'selected' : '' }}>British Pound (GBP)</option>
                                                <option value="SAR" {{ old('currency') === 'SAR' ? 'selected' : '' }}>Saudi Riyal (SAR)</option>
                                                <option value="AED" {{ old('currency') === 'AED' ? 'selected' : '' }}>UAE Dirham (AED)</option>
                                            </select>
                                            @error('currency')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror"
                                                      id="description" name="description" rows="3"
                                                      placeholder="Optional description of this account">{{ old('description') }}</textarea>
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
                                        <div class="col-md-6" id="bankNameField" style="display: none;">
                                            <label for="bank_name" class="form-label">Bank Name</label>
                                            <input type="text" class="form-control @error('bank_name') is-invalid @enderror"
                                                   id="bank_name" name="bank_name" value="{{ old('bank_name') }}"
                                                   placeholder="e.g., Commercial International Bank">
                                            @error('bank_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="account_number" class="form-label">Account Number</label>
                                            <input type="text" class="form-control @error('account_number') is-invalid @enderror"
                                                   id="account_number" name="account_number" value="{{ old('account_number') }}"
                                                   placeholder="Optional account identifier">
                                            @error('account_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="starting_balance" class="form-label">Starting Balance <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text" id="currencySymbol">EGP</span>
                                                <input type="number" class="form-control @error('starting_balance') is-invalid @enderror"
                                                       id="starting_balance" name="starting_balance" value="{{ old('starting_balance', '0') }}"
                                                       step="0.01" placeholder="0.00">
                                                @error('starting_balance')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <small class="text-muted">Current balance in this account (can be negative for credit cards)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="col-lg-4">
                            <!-- Account Type Guide -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Account Types</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge bg-success me-2">Cash</span>
                                            <small>Physical cash on hand</small>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge bg-primary me-2">Bank</span>
                                            <small>Bank checking/savings accounts</small>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge bg-warning me-2">Credit Card</span>
                                            <small>Credit card accounts</small>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge bg-info me-2">Digital Wallet</span>
                                            <small>PayPal, Stripe, mobile wallets</small>
                                        </div>
                                    </div>
                                    <div class="mb-0">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge bg-secondary me-2">Other</span>
                                            <small>Investment accounts, etc.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Help -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Tips</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <h6>Starting Balance</h6>
                                        <small class="text-muted">Enter the current amount in this account. For credit cards, this might be negative.</small>
                                    </div>
                                    <div class="mb-3">
                                        <h6>Account Number</h6>
                                        <small class="text-muted">Optional field for your reference. Last 4 digits for security.</small>
                                    </div>
                                    <div class="mb-0">
                                        <h6>Currency</h6>
                                        <small class="text-muted">Each account can have its own currency. Most expenses should match account currency.</small>
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
                                    <a href="{{ route('accounting.accounts.index') }}" class="btn btn-outline-secondary">
                                        <i class="ti ti-x me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-device-floppy me-1"></i>Create Account
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
    updateCurrencySymbol();
});
</script>
@endsection