@extends('layouts/layoutMaster')

@section('title', 'Create Contract')

@section('vendor-style')
<!-- No Select2 needed - using vanilla JS implementation -->
@endsection

@section('vendor-script')
<!-- No Select2 needed - using vanilla JS implementation -->
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Create New Contract</h5>
                    <small class="text-muted">Set up a new client contract for income tracking</small>
                </div>
                <a href="{{ route('accounting.income.contracts.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Back to Contracts
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('accounting.income.contracts.store') }}" method="POST">
                    @csrf

                    <div class="row">
                        <!-- Main Content -->
                        <div class="col-lg-8">
                            <!-- Basic Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Contract Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <!-- Business Unit Selection (if user has access to multiple BUs) -->
                                        @if(isset($accessibleBusinessUnits) && $accessibleBusinessUnits->count() > 1)
                                        <div class="col-12">
                                            <label class="form-label" for="business_unit_id">Business Unit <span class="text-danger">*</span></label>
                                            <select class="form-select @error('business_unit_id') is-invalid @enderror"
                                                    id="business_unit_id" name="business_unit_id" required>
                                                <option value="">Select Business Unit</option>
                                                @foreach($accessibleBusinessUnits as $businessUnit)
                                                    <option value="{{ $businessUnit->id }}"
                                                            {{ old('business_unit_id', $currentBusinessUnit?->id) == $businessUnit->id ? 'selected' : '' }}>
                                                        {{ $businessUnit->name }} ({{ $businessUnit->code }})
                                                        @if($businessUnit->type === 'head_office')
                                                            - Head Office
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('business_unit_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        @endif

                                        <div class="col-md-6">
                                            <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                            <div class="d-flex">
                                                <select class="form-select @error('customer_id') is-invalid @enderror"
                                                        id="customer_id" name="customer_id" style="width: calc(100% - 40px);" required>
                                                    <option value="">Select Customer</option>
                                                    @if(old('customer_id'))
                                                        <!-- Will be populated by JavaScript on page load -->
                                                    @endif
                                                </select>
                                                <button type="button" class="btn btn-outline-primary ms-2"
                                                        data-bs-toggle="modal" data-bs-target="#addCustomerModal"
                                                        title="Add New Customer">
                                                    <i class="ti ti-plus"></i>
                                                </button>
                                            </div>
                                            @error('customer_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror

                                            <!-- Legacy client_name field (hidden, for backward compatibility) -->
                                            <input type="hidden" id="client_name" name="client_name" value="{{ old('client_name') }}">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="contract_number" class="form-label">Contract Number <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('contract_number') is-invalid @enderror"
                                                   id="contract_number" name="contract_number" value="{{ old('contract_number') }}"
                                                   placeholder="Auto-generated or enter manually">
                                            @error('contract_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Will auto-generate based on client name</small>
                                        </div>

                                        <div class="col-12">
                                            <label for="project_ids" class="form-label">Projects <small class="text-muted">(Optional - can select multiple)</small></label>
                                            <select class="form-select @error('project_ids') is-invalid @enderror"
                                                    id="project_ids" name="project_ids[]" multiple size="4">
                                                @foreach($projects ?? [] as $project)
                                                    <option value="{{ $project->id }}"
                                                        {{ (is_array(old('project_ids')) && in_array($project->id, old('project_ids'))) || ($selectedProjectId ?? null) == $project->id ? 'selected' : '' }}>
                                                        [{{ $project->code }}] {{ $project->name }}
                                                        @if($project->customer)
                                                            - {{ $project->customer->display_name }}
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('project_ids')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Link this contract to one or more projects. Hold Ctrl/Cmd to select multiple.</small>
                                        </div>

                                        <div class="col-12">
                                            <label for="description" class="form-label">Contract Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror"
                                                      id="description" name="description" rows="3"
                                                      placeholder="Brief description of the contract scope and deliverables">{{ old('description') }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Amount & Status -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Amount & Status</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="total_amount" class="form-label">Total Contract Value <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">EGP</span>
                                                <input type="number" class="form-control @error('total_amount') is-invalid @enderror"
                                                       id="total_amount" name="total_amount" value="{{ old('total_amount') }}"
                                                       step="0.01" min="0.01" max="999999999.99"
                                                       placeholder="0.00">
                                                @error('total_amount')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="status" class="form-label">Contract Status <span class="text-danger">*</span></label>
                                            <select class="form-select @error('status') is-invalid @enderror"
                                                    id="status" name="status">
                                                <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                                <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                            </select>
                                            @error('status')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_active"
                                                       id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_active">
                                                    Active contract (include in cash flow projections)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Product Allocation -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Product Allocation</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Product Assignments <small class="text-muted">(Optional)</small></label>
                                            <p class="text-muted small mb-3">Allocate this contract to one or more products for budget tracking.</p>

                                            <div id="product-allocations">
                                                <!-- Dynamic allocations will be added here -->
                                            </div>

                                            <button type="button" class="btn btn-outline-primary btn-sm" id="add-product">
                                                <i class="ti ti-plus me-1"></i>Add Product
                                            </button>
                                        </div>

                                        <div class="col-12" id="allocation-summary" style="display: none;">
                                            <div class="alert alert-info">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <strong>Allocation Summary</strong>
                                                    <div class="progress" style="width: 200px; height: 8px;">
                                                        <div class="progress-bar bg-success" id="allocation-progress" style="width: 0%"></div>
                                                    </div>
                                                </div>
                                                <div class="row text-sm">
                                                    <div class="col-6">
                                                        <strong>Total Allocated:</strong> <span id="total-allocated">0 EGP</span>
                                                    </div>
                                                    <div class="col-6">
                                                        <strong>Remaining:</strong> <span id="remaining-amount">0 EGP</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contract Duration -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Contract Duration</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                                   id="start_date" name="start_date" value="{{ old('start_date') }}">
                                            @error('start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="end_date" class="form-label">End Date <small class="text-muted">(Optional)</small></label>
                                            <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                                   id="end_date" name="end_date" value="{{ old('end_date') }}">
                                            @error('end_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Leave blank for ongoing contracts</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Contact Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="contact_email" class="form-label">Contact Email</label>
                                            <input type="email" class="form-control @error('contact_info.email') is-invalid @enderror"
                                                   id="contact_email" name="contact_info[email]"
                                                   value="{{ old('contact_info.email') }}"
                                                   placeholder="contact@company.com">
                                            @error('contact_info.email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="contact_phone" class="form-label">Contact Phone</label>
                                            <input type="text" class="form-control @error('contact_info.phone') is-invalid @enderror"
                                                   id="contact_phone" name="contact_info[phone]"
                                                   value="{{ old('contact_info.phone') }}"
                                                   placeholder="+20 123 456 789">
                                            @error('contact_info.phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Notes -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Additional Notes</h6>
                                </div>
                                <div class="card-body">
                                    <textarea class="form-control @error('notes') is-invalid @enderror"
                                              id="notes" name="notes" rows="4"
                                              placeholder="Any additional notes or special terms for this contract">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
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
                                        <a href="{{ route('administration.products.index') }}"
                                           class="btn btn-outline-primary" target="_blank">
                                            <i class="ti ti-building me-2"></i>Manage Products
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
                                        <h6>Contract Setup</h6>
                                        <small class="text-muted">Use unique contract numbers for easy tracking</small>
                                    </div>
                                    <div class="mb-3">
                                        <h6>Product Allocation</h6>
                                        <small class="text-muted">Assign contracts to products for budget tracking and reporting</small>
                                    </div>
                                    <div class="mb-0">
                                        <h6>Next Steps</h6>
                                        <small class="text-muted">After creating, you can add payment schedules to track expected income</small>
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
                                    <a href="{{ route('accounting.income.contracts.index') }}" class="btn btn-outline-secondary">
                                        <i class="ti ti-x me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-device-floppy me-1"></i>Create Contract
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

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addCustomerForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modal_type" class="form-label">Customer Type <span class="text-danger">*</span></label>
                            <select id="modal_type" name="type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="individual">Individual</option>
                                <option value="company">Company</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_name" class="form-label">Contact Person Name <span class="text-danger">*</span></label>
                            <input type="text" id="modal_name" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6" id="modal_company_field" style="display: none;">
                            <label for="modal_company_name" class="form-label">Company Name</label>
                            <input type="text" id="modal_company_name" name="company_name" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="modal_email" class="form-label">Email</label>
                            <input type="email" id="modal_email" name="email" class="form-control">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveCustomerBtn">
                    <i class="ti ti-check me-1"></i>Add Customer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Template for Product Allocation -->
<div id="allocation-template" style="display: none;">
    <div class="allocation-row">
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Product <span class="text-danger">*</span></label>
                        <select class="form-select allocation-product" name="products[INDEX][product_id]">
                            <option value="">Select product</option>
                            @if(isset($products) && $products->count() > 0)
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">
                                        {{ $product->name }} ({{ $product->code }})
                                    </option>
                                @endforeach
                            @else
                                <option value="" disabled>No products available for selected business unit</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select class="form-select allocation-type" name="products[INDEX][allocation_type]">
                            <option value="percentage">Percentage</option>
                            <option value="amount">Amount</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label allocation-value-label">Percentage (%) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control allocation-value"
                                   name="products[INDEX][allocation_percentage]"
                                   step="0.01" min="0" max="100" placeholder="0.00">
                            <input type="number" class="form-control allocation-value d-none"
                                   name="products[INDEX][allocation_amount]"
                                   step="0.01" min="0" placeholder="0.00">
                            <span class="input-group-text allocation-unit">%</span>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger remove-allocation w-100">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Notes <small class="text-muted">(Optional)</small></label>
                        <input type="text" class="form-control" name="products[INDEX][notes]"
                               placeholder="Additional notes for this allocation">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="calculated-amount text-muted small mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let allocationIndex = 0;

    // Initialize customer dropdown with vanilla JavaScript
    function initializeCustomerDropdown() {
        console.log('Initializing customer dropdown...');
        loadCustomersAsFallback();
    }

    // Fallback function to load customers without Select2/jQuery
    function loadCustomersAsFallback() {
        console.log('Loading customers as fallback...');

        // Use vanilla JavaScript fetch instead of jQuery
        fetch('{{ route("administration.customers.api.index") }}?' + new URLSearchParams({ search: '' }), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('customer_id');
            select.innerHTML = '<option value="">Select Customer</option>';

            // Pre-selected customer ID from query params
            const preSelectedCustomerId = '{{ $selectedCustomerId ?? '' }}';

            if (data.customers) {
                data.customers.forEach(function(customer) {
                    const option = document.createElement('option');
                    option.value = customer.id;
                    option.textContent = customer.text;
                    option.dataset.customerData = JSON.stringify(customer);

                    // Pre-select if matches
                    if (preSelectedCustomerId && customer.id == preSelectedCustomerId) {
                        option.selected = true;
                    }

                    select.appendChild(option);
                });

                // If customer was pre-selected, trigger the change logic
                if (preSelectedCustomerId && select.value) {
                    const selectedOption = select.options[select.selectedIndex];
                    const customerData = JSON.parse(selectedOption.dataset.customerData || '{}');
                    document.getElementById('client_name').value = customerData.name || selectedOption.textContent;
                    autoGenerateContractNumber(customerData.name || selectedOption.textContent);
                }
            }

            // Add change event listener
            select.addEventListener('change', function() {
                if (this.value) {
                    const customerData = JSON.parse(this.options[this.selectedIndex].dataset.customerData || '{}');

                    // Update hidden client_name field
                    document.getElementById('client_name').value = customerData.name || this.options[this.selectedIndex].textContent;

                    // Auto-generate contract number
                    autoGenerateContractNumber(customerData.name || this.options[this.selectedIndex].textContent);
                }
            });

            console.log('Fallback customer loading completed');
        })
        .catch(error => {
            console.error('Failed to load customers:', error);

            // Show error to user
            const select = document.getElementById('customer_id');
            select.innerHTML = '<option value="">Failed to load customers</option>';

            // Show toast if available
            if (typeof showToast === 'function') {
                showToast('error', 'Failed to load customers. Please refresh the page.');
            }
        });
    }

    // Call the initialization function
    initializeCustomerDropdown();

    // Handle customer type change in modal
    document.getElementById('modal_type').addEventListener('change', function() {
        const companyField = document.getElementById('modal_company_field');
        if (this.value === 'company') {
            companyField.style.display = 'block';
        } else {
            companyField.style.display = 'none';
            document.getElementById('modal_company_name').value = '';
        }
    });

    // Handle save customer button click
    document.getElementById('saveCustomerBtn').addEventListener('click', function() {
        const form = document.getElementById('addCustomerForm');
        const formData = new FormData(form);

        // Basic validation
        const name = formData.get('name');
        const type = formData.get('type');

        if (!type || !name) {
            alert('Please fill in all required fields.');
            return;
        }

        // Disable button and show loading
        const saveBtn = this;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="spinner-border spinner-border-sm me-1"></i>Adding...';

        // Try jQuery first, fall back to fetch
        if (typeof $ !== 'undefined') {
            // Send AJAX request using jQuery
            $.ajax({
                url: '{{ route("administration.customers.api.store") }}',
                method: 'POST',
                data: {
                    name: formData.get('name'),
                    type: formData.get('type'),
                    company_name: formData.get('company_name'),
                    email: formData.get('email'),
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    handleCustomerCreationSuccess(response, form, saveBtn);
                },
                error: function(xhr, status, error) {
                    handleCustomerCreationError(xhr, saveBtn);
                }
            });
        } else {
            // Use vanilla JavaScript fetch as fallback
            fetch('{{ route("administration.customers.api.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    name: formData.get('name'),
                    type: formData.get('type'),
                    company_name: formData.get('company_name'),
                    email: formData.get('email')
                })
            })
            .then(response => response.json())
            .then(data => {
                handleCustomerCreationSuccess(data, form, saveBtn);
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                handleCustomerCreationError({ responseJSON: { message: 'Network error occurred' } }, saveBtn);
            });
        }
    });

    // Handle successful customer creation
    function handleCustomerCreationSuccess(response, form, saveBtn) {
        if (response.success) {
            // Add new option to select
            const select = document.getElementById('customer_id');
            const newOption = document.createElement('option');
            newOption.value = response.customer.id;
            newOption.textContent = response.customer.text;
            newOption.selected = true;
            newOption.dataset.customerData = JSON.stringify(response.customer);
            select.appendChild(newOption);

            // Update hidden client_name field
            document.getElementById('client_name').value = response.customer.name;

            // Auto-generate contract number
            autoGenerateContractNumber(response.customer.name);

            // Close modal and reset form
            const modal = document.getElementById('addCustomerModal');
            if (typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getInstance(modal).hide();
            } else {
                modal.style.display = 'none';
            }

            form.reset();
            document.getElementById('modal_company_field').style.display = 'none';

            // Show success message
            showToast('success', response.message || 'Customer added successfully!');
        } else {
            showToast('error', response.error || 'Failed to add customer');
        }

        // Re-enable button
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="ti ti-check me-1"></i>Add Customer';
    }

    // Handle customer creation errors
    function handleCustomerCreationError(xhr, saveBtn) {
        console.error('Customer creation error:', xhr);
        let errorMessage = 'Failed to add customer';

        if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
            const errors = Object.values(xhr.responseJSON.errors).flat();
            errorMessage = errors.join(', ');
        } else if (xhr.status === 403) {
            errorMessage = 'Permission denied. Please contact administrator.';
        } else if (xhr.status === 422) {
            errorMessage = 'Validation error. Please check your input.';
        }

        showToast('error', errorMessage);

        // Re-enable button
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="ti ti-check me-1"></i>Add Customer';
    }

    // Auto-generate contract number function
    function autoGenerateContractNumber(customerName) {
        const contractNumberField = document.getElementById('contract_number');
        if (!contractNumberField.value && customerName) {
            const date = new Date();
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const customerCode = customerName.toUpperCase()
                .replace(/[^A-Z0-9\s]/g, '')
                .split(' ')
                .map(word => word.substring(0, 3))
                .join('')
                .substring(0, 6);

            contractNumberField.value = `CONT-${year}${month}-${customerCode}`;
        }
    }

    // Show toast notification
    function showToast(type, message) {
        // Create a unique ID for the toast
        const toastId = 'toast-' + Date.now();

        const toastHtml = `
            <div id="${toastId}" class="bs-toast toast position-fixed top-0 end-0 m-3 fade bg-${type === 'success' ? 'success' : 'danger'} show"
                 role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
                <div class="toast-header">
                    <i class="ti ti-${type === 'success' ? 'check' : 'x'} text-${type === 'success' ? 'success' : 'danger'} me-2"></i>
                    <div class="me-auto fw-medium">${type === 'success' ? 'Success!' : 'Error!'}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body text-white">${message}</div>
            </div>
        `;

        // Add toast to body
        document.body.insertAdjacentHTML('beforeend', toastHtml);

        // Initialize Bootstrap toast and show it
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            delay: 4000
        });

        toast.show();

        // Remove toast element after it's hidden
        toastElement.addEventListener('hidden.bs.toast', function() {
            this.remove();
        });
    }

    // Add new product allocation
    document.getElementById('add-product').addEventListener('click', function() {
        const template = document.getElementById('allocation-template').innerHTML;
        const newAllocation = template.replace(/INDEX/g, allocationIndex);
        document.getElementById('product-allocations').insertAdjacentHTML('beforeend', newAllocation);
        allocationIndex++;
        updateCalculations();
        document.getElementById('allocation-summary').style.display = 'block';
    });

    // Remove allocation
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-allocation')) {
            e.target.closest('.allocation-row').remove();
            updateCalculations();

            // Hide summary if no allocations
            if (document.querySelectorAll('.allocation-row').length === 0) {
                document.getElementById('allocation-summary').style.display = 'none';
            }
        }
    });

    // Handle allocation type change
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('allocation-type')) {
            const row = e.target.closest('.allocation-row');
            const type = e.target.value;
            const percentageInput = row.querySelector('input[name*="[allocation_percentage]"]');
            const amountInput = row.querySelector('input[name*="[allocation_amount]"]');
            const label = row.querySelector('.allocation-value-label');
            const unit = row.querySelector('.allocation-unit');

            if (type === 'percentage') {
                percentageInput.classList.remove('d-none');
                amountInput.classList.add('d-none');
                label.textContent = 'Percentage (%)';
                unit.textContent = '%';
                amountInput.value = '';
            } else {
                percentageInput.classList.add('d-none');
                amountInput.classList.remove('d-none');
                label.textContent = 'Amount (EGP)';
                unit.textContent = 'EGP';
                percentageInput.value = '';
            }
            updateCalculations();
        }
    });

    // Handle value changes
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('allocation-value') || e.target.id === 'total_amount') {
            updateCalculations();
        }
    });

    // Update calculations
    function updateCalculations() {
        const totalAmount = parseFloat(document.getElementById('total_amount').value) || 0;
        let totalAllocated = 0;

        document.querySelectorAll('.allocation-row').forEach(function(row) {
            const type = row.querySelector('.allocation-type').value;
            let allocation = 0;

            if (type === 'percentage') {
                const percentage = parseFloat(row.querySelector('input[name*="[allocation_percentage]"]').value) || 0;
                allocation = (percentage / 100) * totalAmount;
                row.querySelector('.calculated-amount').textContent = `Calculated: ${allocation.toLocaleString()} EGP`;
            } else {
                allocation = parseFloat(row.querySelector('input[name*="[allocation_amount]"]').value) || 0;
                if (totalAmount > 0) {
                    const percentage = (allocation / totalAmount) * 100;
                    row.querySelector('.calculated-amount').textContent = `Calculated: ${percentage.toFixed(2)}% of contract`;
                }
            }

            totalAllocated += allocation;
        });

        const remaining = totalAmount - totalAllocated;
        const percentage = totalAmount > 0 ? (totalAllocated / totalAmount) * 100 : 0;

        document.getElementById('total-allocated').textContent = totalAllocated.toLocaleString() + ' EGP';
        document.getElementById('remaining-amount').textContent = remaining.toLocaleString() + ' EGP';
        document.getElementById('allocation-progress').style.width = Math.min(percentage, 100) + '%';

        // Update progress bar color
        if (percentage > 100) {
            document.getElementById('allocation-progress').className = 'progress-bar bg-danger';
        } else if (percentage >= 90) {
            document.getElementById('allocation-progress').className = 'progress-bar bg-warning';
        } else {
            document.getElementById('allocation-progress').className = 'progress-bar bg-success';
        }

        // Update remaining amount color
        const remainingElement = document.getElementById('remaining-amount');
        if (remaining < 0) {
            remainingElement.className = 'text-danger';
        } else if (remaining === 0) {
            remainingElement.className = 'text-success';
        } else {
            remainingElement.className = '';
        }
    }

    // Handle business unit selection change to update products
    const businessUnitSelect = document.getElementById('business_unit_id');
    if (businessUnitSelect) {
        businessUnitSelect.addEventListener('change', function() {
            const businessUnitId = this.value;
            console.log('Business unit changed:', businessUnitId);

            if (businessUnitId) {
                // Fetch products for the selected business unit
                fetch(`{{ route('accounting.income.contracts.api.products-by-business-unit') }}?business_unit_id=${businessUnitId}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Products fetched:', data);
                    if (data.success) {
                        updateProductDropdowns(data.products);
                    } else {
                        console.error('Failed to fetch products:', data.error);
                        showToast('error', 'Failed to load products for selected business unit');
                    }
                })
                .catch(error => {
                    console.error('Error fetching products:', error);
                    showToast('error', 'Error loading products. Please try again.');
                });
            } else {
                // Clear product dropdowns if no business unit selected
                updateProductDropdowns([]);
            }
        });
    }

    // Function to update all product dropdowns
    function updateProductDropdowns(products) {
        console.log('Updating product dropdowns with:', products);

        // Update all existing product dropdowns
        document.querySelectorAll('.allocation-product').forEach(function(select) {
            const currentValue = select.value;

            // Clear existing options except the first one
            select.innerHTML = '<option value="">Select product</option>';

            if (products.length > 0) {
                products.forEach(function(product) {
                    const option = document.createElement('option');
                    option.value = product.id;
                    option.textContent = `${product.name} (${product.code})`;

                    // Restore selection if it was previously selected and still exists
                    if (currentValue == product.id) {
                        option.selected = true;
                    }

                    select.appendChild(option);
                });
            } else {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'No products available for selected business unit';
                option.disabled = true;
                select.appendChild(option);
            }
        });

        // Update the template for new allocations
        updateAllocationTemplate(products);
    }

    // Function to update the allocation template
    function updateAllocationTemplate(products) {
        const template = document.getElementById('allocation-template');
        if (template) {
            let templateHTML = template.innerHTML;

            // Find the select element in the template
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = templateHTML;
            const selectElement = tempDiv.querySelector('.allocation-product');

            if (selectElement) {
                selectElement.innerHTML = '<option value="">Select product</option>';

                if (products.length > 0) {
                    products.forEach(function(product) {
                        const option = document.createElement('option');
                        option.value = product.id;
                        option.textContent = `${product.name} (${product.code})`;
                        selectElement.appendChild(option);
                    });
                } else {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'No products available for selected business unit';
                    option.disabled = true;
                    selectElement.appendChild(option);
                }

                template.innerHTML = tempDiv.innerHTML;
            }
        }
    }

    // Note: Auto-generate contract number is now handled by customer selection above
});
</script>
@endsection