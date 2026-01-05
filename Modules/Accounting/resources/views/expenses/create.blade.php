@extends('layouts/layoutMaster')

@section('title', 'Create Expense')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Create New Expense</h5>
                    <small class="text-muted">Add a recurring expense schedule or one-time expense</small>
                </div>
                <a href="{{ route('accounting.expenses.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Back to Expenses
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('accounting.expenses.store') }}" method="POST">
                    @csrf

                    <div class="row">
                        <!-- Main Content -->
                        <div class="col-lg-8">
                            <!-- Expense Type Selection -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Expense Type</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Select Expense Type <span class="text-danger">*</span></label>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="expense_type"
                                                               id="expenseTypeRecurring" value="recurring"
                                                               {{ old('expense_type') === 'recurring' ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="expenseTypeRecurring">
                                                            <strong>Recurring Expense</strong>
                                                            <br><small class="text-muted">Regular scheduled expenses (rent, salaries, etc.)</small>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="expense_type"
                                                               id="expenseTypeOneTime" value="one_time"
                                                               {{ old('expense_type', 'one_time') === 'one_time' ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="expenseTypeOneTime">
                                                            <strong>One-time Expense</strong>
                                                            <br><small class="text-muted">Single occurrence expenses (equipment, repairs, etc.)</small>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Info alert that changes based on expense type -->
                                        <div class="col-12" id="expenseTypeInfo">
                                            <div class="alert alert-info mb-0" id="recurringInfo">
                                                <i class="ti ti-info-circle me-2"></i>
                                                Recurring expenses are included in cash flow projections and forecasts.
                                            </div>
                                            <div class="alert alert-warning mb-0 d-none" id="oneTimeInfo">
                                                <i class="ti ti-alert-circle me-2"></i>
                                                <strong>Note:</strong> One-time expenses are <strong>not</strong> included in cash flow projections. They are tracked as actual expenses only.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Basic Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="name" class="form-label">Expense Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                   id="name" name="name" value="{{ old('name') }}"
                                                   placeholder="e.g., Office Rent, Employee Salaries">
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="expense_type_category" class="form-label">Expense Type <span class="text-danger">*</span></label>
                                            <select class="form-select @error('expense_type_category') is-invalid @enderror"
                                                    id="expense_type_category" name="expense_type_category">
                                                <option value="">Select expense type first</option>
                                                @foreach($expenseTypes as $expenseType)
                                                    <option value="{{ $expenseType->id }}"
                                                            {{ old('expense_type_category') == $expenseType->id ? 'selected' : '' }}
                                                            data-color="{{ $expenseType->color }}">
                                                        {{ $expenseType->code }} - {{ $expenseType->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('expense_type_category')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                            <select class="form-select @error('category_id') is-invalid @enderror"
                                                    id="category_id" name="category_id" disabled>
                                                <option value="">Select expense type first</option>
                                            </select>
                                            @error('category_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-12">
                                            <label for="subcategory_id" class="form-label">Subcategory <small class="text-muted">(Optional)</small></label>
                                            <select class="form-select @error('subcategory_id') is-invalid @enderror"
                                                    id="subcategory_id" name="subcategory_id" disabled>
                                                <option value="">Select a subcategory</option>
                                            </select>
                                            @error('subcategory_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror"
                                                      id="description" name="description" rows="3"
                                                      placeholder="Optional description or notes">{{ old('description') }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Amount & Dates -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Amount & Dates</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">EGP</span>
                                                <input type="number" class="form-control @error('amount') is-invalid @enderror"
                                                       id="amount" name="amount" value="{{ old('amount') }}"
                                                       step="0.01" min="0" max="999999.99"
                                                       placeholder="0.00">
                                                @error('amount')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- One-time expense date -->
                                        <div class="col-md-6" id="oneTimeDate" style="display: none;">
                                            <label for="expense_date" class="form-label">Expense Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('expense_date') is-invalid @enderror"
                                                   id="expense_date" name="expense_date" value="{{ old('expense_date') }}">
                                            @error('expense_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Recurring expense start date -->
                                        <div class="col-md-6" id="recurringStartDate">
                                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                                   id="start_date" name="start_date" value="{{ old('start_date') }}">
                                            @error('start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Recurring expense end date -->
                                        <div class="col-md-6" id="recurringEndDate">
                                            <label for="end_date" class="form-label">End Date <small class="text-muted">(Optional)</small></label>
                                            <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                                   id="end_date" name="end_date" value="{{ old('end_date') }}">
                                            @error('end_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Leave blank for ongoing expenses</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Frequency Settings (Recurring Only) -->
                            <div class="card mb-4" id="frequencySettings">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Frequency Settings</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="frequency_type" class="form-label">Frequency Type <span class="text-danger">*</span></label>
                                            <select class="form-select @error('frequency_type') is-invalid @enderror"
                                                    id="frequency_type" name="frequency_type">
                                                <option value="">Select frequency</option>
                                                @foreach($frequencyOptions as $key => $label)
                                                    <option value="{{ $key }}" {{ old('frequency_type') === $key ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('frequency_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="frequency_value" class="form-label">
                                                Frequency Interval <span class="text-danger">*</span>
                                                <i class="ti ti-info-circle" data-bs-toggle="tooltip"
                                                   title="How often the frequency occurs (e.g., every 2 weeks, every 3 months)"></i>
                                            </label>
                                            <input type="number" class="form-control @error('frequency_value') is-invalid @enderror"
                                                   id="frequency_value" name="frequency_value"
                                                   value="{{ old('frequency_value', 1) }}"
                                                   min="1" max="100">
                                            @error('frequency_value')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted" id="frequencyHelper">Every 1 time</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Payment Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" name="mark_as_paid"
                                                       id="markAsPaid" value="1" {{ old('mark_as_paid') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="markAsPaid">
                                                    Mark as already paid
                                                </label>
                                            </div>
                                        </div>

                                        <div id="paymentFields" style="display: none;">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="paid_from_account_id" class="form-label">Paid From Account <span class="text-danger">*</span></label>
                                                    <select class="form-select @error('paid_from_account_id') is-invalid @enderror"
                                                            id="paid_from_account_id" name="paid_from_account_id">
                                                        <option value="">Select account</option>
                                                        @foreach($accounts as $account)
                                                            <option value="{{ $account->id }}"
                                                                    {{ old('paid_from_account_id') == $account->id ? 'selected' : '' }}>
                                                                {{ $account->name }} ({{ $account->formatted_balance }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('paid_from_account_id')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="paid_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                                                    <input type="date" class="form-control @error('paid_date') is-invalid @enderror"
                                                           id="paid_date" name="paid_date" value="{{ old('paid_date', date('Y-m-d')) }}">
                                                    @error('paid_date')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="paid_amount" class="form-label">Paid Amount</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">EGP</span>
                                                        <input type="number" class="form-control @error('paid_amount') is-invalid @enderror"
                                                               id="paid_amount" name="paid_amount" value="{{ old('paid_amount') }}"
                                                               step="0.01" min="0" placeholder="Same as expense amount">
                                                        @error('paid_amount')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <small class="text-muted">Leave blank to use the expense amount</small>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="payment_notes" class="form-label">Payment Notes</label>
                                                    <textarea class="form-control @error('payment_notes') is-invalid @enderror"
                                                              id="payment_notes" name="payment_notes" rows="2"
                                                              placeholder="Optional payment notes">{{ old('payment_notes') }}</textarea>
                                                    @error('payment_notes')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="col-lg-4">
                            <!-- Quick Actions -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Quick Actions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('accounting.expenses.categories') }}"
                                           class="btn btn-outline-primary" target="_blank">
                                            <i class="ti ti-category me-2"></i>Manage Categories
                                        </a>
                                        <a href="{{ route('accounting.accounts.index') }}"
                                           class="btn btn-outline-info" target="_blank">
                                            <i class="ti ti-credit-card me-2"></i>Manage Accounts
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Help -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Help</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <h6><i class="ti ti-repeat text-primary me-1"></i>Recurring Expenses</h6>
                                        <small class="text-muted">Regular scheduled expenses like rent, salaries, utilities. These are <strong>included</strong> in cash flow projections.</small>
                                    </div>
                                    <div class="mb-3">
                                        <h6><i class="ti ti-calendar-event text-warning me-1"></i>One-time Expenses</h6>
                                        <small class="text-muted">Single occurrence expenses like equipment purchases, repairs. These are <strong>not</strong> included in projections.</small>
                                    </div>
                                    <div class="mb-0">
                                        <h6><i class="ti ti-category text-info me-1"></i>Subcategories</h6>
                                        <small class="text-muted">Use subcategories for detailed expense tracking within main categories.</small>
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
                                    <a href="{{ route('accounting.expenses.index') }}" class="btn btn-outline-secondary">
                                        <i class="ti ti-x me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-device-floppy me-1"></i>Create Expense
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
    const expenseTypeRecurring = document.getElementById('expenseTypeRecurring');
    const expenseTypeOneTime = document.getElementById('expenseTypeOneTime');
    const frequencySettings = document.getElementById('frequencySettings');
    const oneTimeDate = document.getElementById('oneTimeDate');
    const recurringStartDate = document.getElementById('recurringStartDate');
    const recurringEndDate = document.getElementById('recurringEndDate');
    const categorySelect = document.getElementById('category_id');
    const subcategorySelect = document.getElementById('subcategory_id');
    const expenseTypeSelect = document.getElementById('expense_type_category');
    const markAsPaidCheckbox = document.getElementById('markAsPaid');
    const paymentFields = document.getElementById('paymentFields');
    const amountField = document.getElementById('amount');
    const paidAmountField = document.getElementById('paid_amount');

    // Category and subcategory data
    @php
        $categoriesByTypeData = $expenseTypes->mapWithKeys(function($type) {
            return [$type->id => $type->activeCategories->map(function($category) {
                return ['id' => $category->id, 'name' => $category->name, 'color' => $category->color];
            })];
        });

        // Build subcategories with all nested levels
        $subcategoriesByCategoryData = [];
        foreach ($categories as $category) {
            $allSubs = collect();
            $addDescendants = function($parent, $depth = 0) use (&$addDescendants, &$allSubs) {
                foreach ($parent->subcategories()->active()->orderBy('sort_order')->orderBy('name')->get() as $sub) {
                    $prefix = str_repeat('── ', $depth);
                    $allSubs->push([
                        'id' => $sub->id,
                        'name' => $prefix . $sub->name,
                        'depth' => $depth
                    ]);
                    $addDescendants($sub, $depth + 1);
                }
            };
            $addDescendants($category);
            $subcategoriesByCategoryData[$category->id] = $allSubs;
        }
    @endphp
    const categoriesByType = @json($categoriesByTypeData);
    const subcategoriesByCategory = @json($subcategoriesByCategoryData);

    // Handle expense type change
    function toggleExpenseType() {
        const recurringInfo = document.getElementById('recurringInfo');
        const oneTimeInfo = document.getElementById('oneTimeInfo');

        if (expenseTypeOneTime.checked) {
            frequencySettings.style.display = 'none';
            oneTimeDate.style.display = 'block';
            recurringStartDate.style.display = 'none';
            recurringEndDate.style.display = 'none';

            // Show one-time info, hide recurring info
            recurringInfo.classList.add('d-none');
            oneTimeInfo.classList.remove('d-none');

            // Clear recurring fields
            document.getElementById('frequency_type').value = '';
            document.getElementById('frequency_value').value = 1;
            document.getElementById('start_date').value = '';
            document.getElementById('end_date').value = '';
        } else {
            frequencySettings.style.display = 'block';
            oneTimeDate.style.display = 'none';
            recurringStartDate.style.display = 'block';
            recurringEndDate.style.display = 'block';

            // Show recurring info, hide one-time info
            recurringInfo.classList.remove('d-none');
            oneTimeInfo.classList.add('d-none');

            // Clear one-time fields
            document.getElementById('expense_date').value = '';
        }
    }

    // Handle expense type change to populate categories
    function updateCategoriesByType() {
        const expenseTypeId = expenseTypeSelect.value;
        categorySelect.innerHTML = '<option value="">Select a category</option>';
        subcategorySelect.innerHTML = '<option value="">Select a subcategory</option>';

        if (expenseTypeId && categoriesByType[expenseTypeId]) {
            categorySelect.disabled = false;
            subcategorySelect.disabled = true;

            categoriesByType[expenseTypeId].forEach(function(category) {
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = category.name;
                option.setAttribute('data-color', category.color);
                categorySelect.appendChild(option);
            });
        } else {
            categorySelect.disabled = true;
            subcategorySelect.disabled = true;
        }
    }

    // Handle category change to populate subcategories
    function updateSubcategories() {
        const categoryId = categorySelect.value;
        subcategorySelect.innerHTML = '<option value="">Select a subcategory</option>';

        if (categoryId && subcategoriesByCategory[categoryId]) {
            subcategorySelect.disabled = false;
            subcategoriesByCategory[categoryId].forEach(function(subcategory) {
                const option = document.createElement('option');
                option.value = subcategory.id;
                option.textContent = subcategory.name;
                subcategorySelect.appendChild(option);
            });
        } else {
            subcategorySelect.disabled = true;
        }
    }

    // Handle payment fields visibility
    function togglePaymentFields() {
        if (markAsPaidCheckbox.checked) {
            paymentFields.style.display = 'block';
            document.getElementById('paid_from_account_id').required = true;
            document.getElementById('paid_date').required = true;
        } else {
            paymentFields.style.display = 'none';
            document.getElementById('paid_from_account_id').required = false;
            document.getElementById('paid_date').required = false;
        }
    }

    // Sync paid amount with expense amount
    function syncPaidAmount() {
        if (paidAmountField.value === '' || paidAmountField.value === '0') {
            paidAmountField.value = amountField.value;
        }
    }

    // Event listeners
    expenseTypeRecurring.addEventListener('change', toggleExpenseType);
    expenseTypeOneTime.addEventListener('change', toggleExpenseType);
    expenseTypeSelect.addEventListener('change', updateCategoriesByType);
    categorySelect.addEventListener('change', updateSubcategories);
    markAsPaidCheckbox.addEventListener('change', togglePaymentFields);
    amountField.addEventListener('input', syncPaidAmount);

    // Initialize
    toggleExpenseType();
    updateCategoriesByType();
    updateSubcategories();
    togglePaymentFields();

    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });

    // Enable subcategory select before form submission to ensure value is sent
    // Disabled form elements are not submitted in HTML forms
    document.querySelector('form').addEventListener('submit', function() {
        subcategorySelect.disabled = false;
    });
});
</script>
@endsection