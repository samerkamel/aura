@extends('layouts/layoutMaster')

@section('title', 'Create Contract')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/select2/select2.js'])
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
                                        <div class="col-md-6">
                                            <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                            <div class="d-flex align-items-center">
                                                <select class="form-select select2-customer @error('customer_id') is-invalid @enderror"
                                                        id="customer_id" name="customer_id" style="width: calc(100% - 50px);" required>
                                                    <option value="">Search or select customer...</option>
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
                                            <label for="start_date" class="form-label">
                                                Start Date <span class="text-danger">*</span>
                                                <i class="ti ti-info-circle text-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="This date determines which month this contract appears in on the Income Sheet"></i>
                                            </label>
                                            <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                                   id="start_date" name="start_date" value="{{ old('start_date', now()->format('Y-m-d')) }}">
                                            @error('start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Used for Income Sheet monthly grouping</small>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                                   id="end_date" name="end_date" value="{{ old('end_date') }}">
                                            @error('end_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
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
                            @foreach($products ?? [] as $product)
                                <option value="{{ $product->id }}">
                                    {{ $product->name }} ({{ $product->code ?? 'N/A' }})
                                </option>
                            @endforeach
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

    // Wait for jQuery and Select2 to be available
    function initSelect2() {
        if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
            setTimeout(initSelect2, 50);
            return;
        }

        // Initialize Select2 for customer dropdown with AJAX search
        const preSelectedCustomerId = '{{ $selectedCustomerId ?? '' }}';

        $('.select2-customer').select2({
            placeholder: 'Search or select customer...',
            allowClear: true,
            ajax: {
                url: '{{ route("administration.customers.api.index") }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term || ''
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.customers ? data.customers.map(function(customer) {
                            return {
                                id: customer.id,
                                text: customer.text,
                                customerData: customer
                            };
                        }) : []
                    };
                },
                cache: true
            },
            minimumInputLength: 0
        }).on('select2:select', function(e) {
            const data = e.params.data;
            if (data && data.customerData) {
                document.getElementById('client_name').value = data.customerData.name || data.text;
                autoGenerateContractNumber(data.customerData.name || data.text);
            }
        });

        // Pre-select customer if provided
        if (preSelectedCustomerId) {
            $.ajax({
                url: '{{ route("administration.customers.api.index") }}',
                dataType: 'json'
            }).then(function(data) {
                if (data.customers) {
                    const customer = data.customers.find(c => c.id == preSelectedCustomerId);
                    if (customer) {
                        const option = new Option(customer.text, customer.id, true, true);
                        $('.select2-customer').append(option).trigger('change');
                        document.getElementById('client_name').value = customer.name;
                        autoGenerateContractNumber(customer.name);
                    }
                }
            });
        }
    }

    initSelect2();

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

        const name = formData.get('name');
        const type = formData.get('type');

        if (!type || !name) {
            alert('Please fill in all required fields.');
            return;
        }

        const saveBtn = this;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="spinner-border spinner-border-sm me-1"></i>Adding...';

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
            if (data.success) {
                // Add new customer to Select2 and select it
                const newOption = new Option(data.customer.text, data.customer.id, true, true);
                $('.select2-customer').append(newOption).trigger('change');

                document.getElementById('client_name').value = data.customer.name;
                autoGenerateContractNumber(data.customer.name);

                bootstrap.Modal.getInstance(document.getElementById('addCustomerModal')).hide();
                form.reset();
                document.getElementById('modal_company_field').style.display = 'none';
                showToast('success', data.message || 'Customer added successfully!');
            } else {
                showToast('error', data.error || 'Failed to add customer');
            }

            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="ti ti-check me-1"></i>Add Customer';
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Network error occurred');
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="ti ti-check me-1"></i>Add Customer';
        });
    });

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

    function showToast(type, message) {
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

        document.body.insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: 4000 });
        toast.show();
        toastElement.addEventListener('hidden.bs.toast', function() { this.remove(); });
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

        if (percentage > 100) {
            document.getElementById('allocation-progress').className = 'progress-bar bg-danger';
        } else if (percentage >= 90) {
            document.getElementById('allocation-progress').className = 'progress-bar bg-warning';
        } else {
            document.getElementById('allocation-progress').className = 'progress-bar bg-success';
        }

        const remainingElement = document.getElementById('remaining-amount');
        if (remaining < 0) {
            remainingElement.className = 'text-danger';
        } else if (remaining === 0) {
            remainingElement.className = 'text-success';
        } else {
            remainingElement.className = '';
        }
    }
});
</script>
@endsection
