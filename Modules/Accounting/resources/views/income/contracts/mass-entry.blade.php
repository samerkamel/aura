@extends('layouts/layoutMaster')

@section('title', 'Mass Contract Entry')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
<style>
    .contract-card {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        margin-bottom: 1rem;
        transition: all 0.2s;
    }
    .contract-card:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .contract-card.has-errors {
        border-color: #dc3545;
        background-color: #fff5f5;
    }
    .contract-card.is-valid {
        border-color: #198754;
    }
    .contract-card-header {
        background: #f8f9fa;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e9ecef;
        border-radius: 8px 8px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .contract-card-body {
        padding: 1rem;
    }
    .row-number {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #6c757d;
        color: white;
        border-radius: 50%;
        font-weight: 600;
        font-size: 0.875rem;
    }
    .row-number.valid { background: #198754; }
    .row-number.invalid { background: #dc3545; }
    .validation-status {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
    }
    .summary-bar {
        position: sticky;
        top: 60px;
        z-index: 100;
        background: white;
        border-bottom: 2px solid #e9ecef;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .badge-btn {
        cursor: pointer;
        transition: all 0.2s;
    }
    .badge-btn:hover {
        transform: scale(1.05);
    }
    .error-list {
        font-size: 0.8rem;
        margin-top: 0.5rem;
        padding: 0.5rem;
        background: #fff5f5;
        border-radius: 4px;
    }
    .error-list li {
        color: #dc3545;
    }
    .milestone-row {
        background: #f8f9fa;
        padding: 0.5rem;
        border-radius: 4px;
        margin-bottom: 0.5rem;
    }
</style>
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        {{-- Header --}}
        <div class="card mb-3">
            <div class="card-body d-flex justify-content-between align-items-center py-3">
                <div>
                    <h4 class="mb-0">Mass Contract Entry</h4>
                    <small class="text-muted">Add multiple contracts at once. All contracts will be validated before saving.</small>
                </div>
                <div>
                    <a href="{{ route('accounting.income.contracts.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Back to Contracts
                    </a>
                </div>
            </div>
        </div>

        {{-- Summary Bar --}}
        <div class="summary-bar card">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-4">
                        <div>
                            <span class="text-muted">Contracts:</span>
                            <strong id="contract-count">0</strong>
                        </div>
                        <div>
                            <span class="text-muted">Total Value:</span>
                            <strong id="total-value">EGP 0.00</strong>
                        </div>
                        <div id="validation-summary">
                            <span class="badge bg-secondary">Not validated</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <button type="button" class="btn btn-outline-primary me-2" id="add-contract-btn">
                        <i class="ti ti-plus me-1"></i>Add Contract
                    </button>
                    <button type="button" class="btn btn-warning me-2" id="validate-btn">
                        <i class="ti ti-check me-1"></i>Validate All
                    </button>
                    <button type="button" class="btn btn-success" id="save-btn" disabled>
                        <i class="ti ti-device-floppy me-1"></i>Save All
                    </button>
                </div>
            </div>
        </div>

        {{-- Contracts Container --}}
        <div id="contracts-container">
            {{-- Contract cards will be added here dynamically --}}
        </div>

        {{-- Empty State --}}
        <div id="empty-state" class="card">
            <div class="card-body text-center py-5">
                <i class="ti ti-file-plus display-4 text-muted mb-3"></i>
                <h5 class="text-muted">No contracts added yet</h5>
                <p class="text-muted mb-4">Click the button below to start adding contracts</p>
                <button type="button" class="btn btn-primary" id="add-first-contract-btn">
                    <i class="ti ti-plus me-1"></i>Add First Contract
                </button>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="card mt-3" id="bottom-actions" style="display: none;">
            <div class="card-body d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-outline-danger" id="clear-all-btn">
                        <i class="ti ti-trash me-1"></i>Clear All
                    </button>
                </div>
                <div>
                    <button type="button" class="btn btn-warning me-2" id="validate-btn-bottom">
                        <i class="ti ti-check me-1"></i>Validate All
                    </button>
                    <button type="button" class="btn btn-success" id="save-btn-bottom" disabled>
                        <i class="ti ti-device-floppy me-1"></i>Save All Valid Contracts
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Product Allocation Modal --}}
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Product Allocation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="product-modal-row-index">

                <div class="alert alert-info mb-3">
                    <i class="ti ti-info-circle me-1"></i>
                    Allocate contract value across products. You can use percentage or fixed amount.
                </div>

                <div class="mb-3">
                    <label class="form-label">Contract Total: <strong id="product-modal-total">EGP 0.00</strong></label>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" id="product-allocation-progress" style="width: 0%"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <small>Allocated: <span id="product-allocated">EGP 0.00</span></small>
                        <small>Remaining: <span id="product-remaining">EGP 0.00</span></small>
                    </div>
                </div>

                <div id="product-allocations-container">
                    {{-- Product allocation rows will be added here --}}
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="add-product-allocation">
                    <i class="ti ti-plus me-1"></i>Add Product
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-products-btn">Save Products</button>
            </div>
        </div>
    </div>
</div>

{{-- Payment Schedule Modal --}}
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="payment-modal-row-index">

                <div class="mb-3">
                    <label class="form-label">Contract Total: <strong id="payment-modal-total">EGP 0.00</strong></label>
                </div>

                {{-- Payment Type Tabs --}}
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#recurring-tab" type="button">
                            <i class="ti ti-repeat me-1"></i>Simple Recurring
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#milestones-tab" type="button">
                            <i class="ti ti-list-check me-1"></i>Custom Milestones
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- Recurring Tab --}}
                    <div class="tab-pane fade show active" id="recurring-tab" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Frequency</label>
                                <select class="form-select" id="payment-frequency">
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="bi-annual">Bi-Annual (6 months)</option>
                                    <option value="annual">Annual</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Calculated Payments</label>
                                <div class="alert alert-light py-2 mb-0">
                                    <span id="recurring-preview">Select dates to preview</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Milestones Tab --}}
                    <div class="tab-pane fade" id="milestones-tab" role="tabpanel">
                        <div class="mb-3">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" id="milestone-progress" style="width: 0%"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small>Total: <span id="milestone-total">EGP 0.00</span></small>
                                <small>Remaining: <span id="milestone-remaining">EGP 0.00</span></small>
                            </div>
                        </div>

                        <div id="milestones-container">
                            {{-- Milestone rows will be added here --}}
                        </div>

                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="add-milestone">
                            <i class="ti ti-plus me-1"></i>Add Milestone
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" id="clear-payment-btn">Clear Schedule</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-payment-btn">Save Schedule</button>
            </div>
        </div>
    </div>
</div>

{{-- Project Selection Modal --}}
<div class="modal fade" id="projectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Projects</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="project-modal-row-index">

                <div class="mb-3">
                    <label class="form-label">Link contract to projects:</label>
                    <select class="form-select" id="project-select" multiple size="8">
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" data-customer="{{ $project->customer_id }}">
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Hold Ctrl/Cmd to select multiple projects</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-projects-btn">Save Projects</button>
            </div>
        </div>
    </div>
</div>

{{-- Contract Card Template --}}
<template id="contract-template">
    <div class="contract-card" data-row-index="INDEX">
        <div class="contract-card-header">
            <div class="d-flex align-items-center gap-2">
                <span class="row-number">INDEX_DISPLAY</span>
                <span class="contract-client-name text-muted">New Contract</span>
                <span class="validation-status"></span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary duplicate-row" title="Duplicate">
                    <i class="ti ti-copy"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove">
                    <i class="ti ti-trash"></i>
                </button>
            </div>
        </div>
        <div class="contract-card-body">
            <div class="row g-3">
                {{-- Row 1: Customer, Client Name --}}
                <div class="col-md-4">
                    <label class="form-label">Customer <span class="text-danger">*</span></label>
                    <select class="form-select customer-select" data-field="customer_id" required>
                        <option value="">Select customer...</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" data-name="{{ $customer->name }}">
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Client Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" data-field="client_name" placeholder="Contract client name" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Total Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">EGP</span>
                        <input type="number" class="form-control amount-input" data-field="total_amount"
                               step="0.01" min="0.01" max="99999999.99" placeholder="0.00" required>
                    </div>
                </div>

                {{-- Row 2: Dates --}}
                <div class="col-md-3">
                    <label class="form-label">Start Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control date-input" data-field="start_date" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control date-input" data-field="end_date" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Description</label>
                    <input type="text" class="form-control" data-field="description" placeholder="Brief description">
                </div>

                {{-- Row 3: Badges for Projects, Products, Payments --}}
                <div class="col-12">
                    <div class="d-flex gap-3 align-items-center">
                        <span class="badge bg-label-primary badge-btn open-projects-modal" title="Click to manage projects">
                            <i class="ti ti-briefcase me-1"></i>Projects: <span class="project-count">0</span>
                        </span>
                        <span class="badge bg-label-info badge-btn open-products-modal" title="Click to manage products">
                            <i class="ti ti-package me-1"></i>Products: <span class="product-count">0</span>
                        </span>
                        <span class="badge bg-label-success badge-btn open-payment-modal" title="Click to manage payment schedule">
                            <i class="ti ti-calendar-dollar me-1"></i>Payments: <span class="payment-summary">Not set</span>
                        </span>
                    </div>
                </div>
            </div>

            {{-- Error display --}}
            <div class="error-list" style="display: none;">
                <strong><i class="ti ti-alert-circle me-1"></i>Validation Errors:</strong>
                <ul class="mb-0 ps-3 mt-1"></ul>
            </div>
        </div>
    </div>
</template>

{{-- Product Allocation Row Template --}}
<template id="product-allocation-template">
    <div class="product-allocation-row border rounded p-2 mb-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label form-label-sm">Product</label>
                <select class="form-select form-select-sm product-select">
                    <option value="">Select product...</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label form-label-sm">Type</label>
                <select class="form-select form-select-sm allocation-type-select">
                    <option value="percentage">Percentage (%)</option>
                    <option value="amount">Fixed Amount (EGP)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label form-label-sm">Value</label>
                <input type="number" class="form-control form-control-sm allocation-value" step="0.01" min="0" placeholder="0">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-sm btn-outline-danger remove-product-allocation">
                    <i class="ti ti-trash"></i>
                </button>
            </div>
        </div>
        <div class="text-muted small mt-1 calculated-product-amount"></div>
    </div>
</template>

{{-- Milestone Row Template --}}
<template id="milestone-template">
    <div class="milestone-row">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label form-label-sm">Milestone Name</label>
                <input type="text" class="form-control form-control-sm milestone-name" placeholder="e.g., Phase 1 Delivery">
            </div>
            <div class="col-md-3">
                <label class="form-label form-label-sm">Amount (EGP)</label>
                <input type="number" class="form-control form-control-sm milestone-amount" step="0.01" min="0" placeholder="0.00">
            </div>
            <div class="col-md-3">
                <label class="form-label form-label-sm">Due Date</label>
                <input type="date" class="form-control form-control-sm milestone-date">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-sm btn-outline-danger remove-milestone">
                    <i class="ti ti-trash"></i>
                </button>
            </div>
        </div>
    </div>
</template>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // State
    let contracts = [];
    let rowIndex = 0;
    let currentModalRowIndex = null;

    // DOM Elements
    const container = document.getElementById('contracts-container');
    const emptyState = document.getElementById('empty-state');
    const bottomActions = document.getElementById('bottom-actions');
    const contractTemplate = document.getElementById('contract-template');
    const productModal = new bootstrap.Modal(document.getElementById('productModal'));
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
    const projectModal = new bootstrap.Modal(document.getElementById('projectModal'));

    // Initialize
    updateUI();

    // Add contract buttons
    document.getElementById('add-contract-btn').addEventListener('click', addContract);
    document.getElementById('add-first-contract-btn').addEventListener('click', addContract);

    // Validate and Save buttons
    document.getElementById('validate-btn').addEventListener('click', validateAll);
    document.getElementById('validate-btn-bottom').addEventListener('click', validateAll);
    document.getElementById('save-btn').addEventListener('click', saveAll);
    document.getElementById('save-btn-bottom').addEventListener('click', saveAll);

    // Clear all
    document.getElementById('clear-all-btn').addEventListener('click', function() {
        if (confirm('Are you sure you want to clear all contracts?')) {
            contracts = [];
            rowIndex = 0;
            container.innerHTML = '';
            updateUI();
        }
    });

    // Add Contract
    function addContract(duplicateData = null) {
        const index = rowIndex++;
        const html = contractTemplate.innerHTML
            .replace(/INDEX/g, index)
            .replace(/INDEX_DISPLAY/g, contracts.length + 1);

        container.insertAdjacentHTML('beforeend', html);

        const card = container.querySelector(`[data-row-index="${index}"]`);

        // Initialize contract data
        const contractData = duplicateData ? { ...duplicateData } : {
            customer_id: '',
            client_name: '',
            total_amount: '',
            start_date: '',
            end_date: '',
            description: '',
            project_ids: [],
            products: [],
            payment_schedule: null
        };
        contractData.row_index = index;
        contracts.push(contractData);

        // Pre-fill if duplicating
        if (duplicateData) {
            card.querySelector('[data-field="customer_id"]').value = duplicateData.customer_id || '';
            card.querySelector('[data-field="client_name"]').value = duplicateData.client_name || '';
            card.querySelector('[data-field="total_amount"]').value = duplicateData.total_amount || '';
            card.querySelector('[data-field="start_date"]').value = duplicateData.start_date || '';
            card.querySelector('[data-field="end_date"]').value = duplicateData.end_date || '';
            card.querySelector('[data-field="description"]').value = duplicateData.description || '';
            updateCardBadges(card, contractData);
        }

        // Set up event listeners for this card
        setupCardListeners(card, index);

        updateUI();
    }

    // Setup card event listeners
    function setupCardListeners(card, index) {
        // Customer select change
        card.querySelector('.customer-select').addEventListener('change', function() {
            const contractData = contracts.find(c => c.row_index === index);
            contractData.customer_id = this.value;

            // Auto-fill client name from customer
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.dataset.name) {
                const clientNameInput = card.querySelector('[data-field="client_name"]');
                if (!clientNameInput.value) {
                    clientNameInput.value = selectedOption.dataset.name;
                    contractData.client_name = selectedOption.dataset.name;
                }
                card.querySelector('.contract-client-name').textContent = selectedOption.dataset.name;
            }
            clearValidation();
        });

        // Field inputs
        card.querySelectorAll('[data-field]').forEach(input => {
            input.addEventListener('input', function() {
                const field = this.dataset.field;
                const contractData = contracts.find(c => c.row_index === index);
                contractData[field] = this.value;

                if (field === 'client_name') {
                    card.querySelector('.contract-client-name').textContent = this.value || 'New Contract';
                }
                clearValidation();
                updateSummary();
            });
        });

        // Remove button
        card.querySelector('.remove-row').addEventListener('click', function() {
            if (contracts.length === 1) {
                if (!confirm('This will remove the last contract. Continue?')) return;
            }
            contracts = contracts.filter(c => c.row_index !== index);
            card.remove();
            renumberCards();
            updateUI();
            clearValidation();
        });

        // Duplicate button
        card.querySelector('.duplicate-row').addEventListener('click', function() {
            const contractData = contracts.find(c => c.row_index === index);
            addContract({ ...contractData, row_index: null });
        });

        // Open modals
        card.querySelector('.open-projects-modal').addEventListener('click', function() {
            openProjectModal(index);
        });

        card.querySelector('.open-products-modal').addEventListener('click', function() {
            openProductModal(index);
        });

        card.querySelector('.open-payment-modal').addEventListener('click', function() {
            openPaymentModal(index);
        });
    }

    // Project Modal
    function openProjectModal(index) {
        currentModalRowIndex = index;
        document.getElementById('project-modal-row-index').value = index;

        const contractData = contracts.find(c => c.row_index === index);
        const projectSelect = document.getElementById('project-select');

        // Reset selection
        Array.from(projectSelect.options).forEach(opt => {
            opt.selected = contractData.project_ids.includes(parseInt(opt.value));
        });

        projectModal.show();
    }

    document.getElementById('save-projects-btn').addEventListener('click', function() {
        const index = parseInt(document.getElementById('project-modal-row-index').value);
        const contractData = contracts.find(c => c.row_index === index);
        const projectSelect = document.getElementById('project-select');

        contractData.project_ids = Array.from(projectSelect.selectedOptions).map(opt => parseInt(opt.value));

        const card = container.querySelector(`[data-row-index="${index}"]`);
        updateCardBadges(card, contractData);

        projectModal.hide();
        clearValidation();
    });

    // Product Modal
    function openProductModal(index) {
        currentModalRowIndex = index;
        document.getElementById('product-modal-row-index').value = index;

        const contractData = contracts.find(c => c.row_index === index);
        const total = parseFloat(contractData.total_amount) || 0;
        document.getElementById('product-modal-total').textContent = 'EGP ' + total.toLocaleString();

        // Clear and populate
        const container = document.getElementById('product-allocations-container');
        container.innerHTML = '';

        if (contractData.products && contractData.products.length > 0) {
            contractData.products.forEach(p => addProductAllocationRow(p));
        }

        updateProductAllocationProgress();
        productModal.show();
    }

    function addProductAllocationRow(data = null) {
        const template = document.getElementById('product-allocation-template');
        const container = document.getElementById('product-allocations-container');
        container.insertAdjacentHTML('beforeend', template.innerHTML);

        const row = container.lastElementChild;

        if (data) {
            row.querySelector('.product-select').value = data.product_id || '';
            row.querySelector('.allocation-type-select').value = data.allocation_type || 'percentage';
            row.querySelector('.allocation-value').value = data.allocation_type === 'percentage'
                ? data.allocation_percentage
                : data.allocation_amount;
        }

        row.querySelector('.remove-product-allocation').addEventListener('click', function() {
            row.remove();
            updateProductAllocationProgress();
        });

        row.querySelector('.allocation-value').addEventListener('input', updateProductAllocationProgress);
        row.querySelector('.allocation-type-select').addEventListener('change', updateProductAllocationProgress);
    }

    document.getElementById('add-product-allocation').addEventListener('click', function() {
        addProductAllocationRow();
    });

    function updateProductAllocationProgress() {
        const index = parseInt(document.getElementById('product-modal-row-index').value);
        const contractData = contracts.find(c => c.row_index === index);
        const total = parseFloat(contractData?.total_amount) || 0;

        let allocated = 0;
        document.querySelectorAll('#product-allocations-container .product-allocation-row').forEach(row => {
            const type = row.querySelector('.allocation-type-select').value;
            const value = parseFloat(row.querySelector('.allocation-value').value) || 0;

            if (type === 'percentage') {
                allocated += (value / 100) * total;
                row.querySelector('.calculated-product-amount').textContent =
                    `= EGP ${((value / 100) * total).toLocaleString()}`;
            } else {
                allocated += value;
                row.querySelector('.calculated-product-amount').textContent =
                    `= ${total > 0 ? ((value / total) * 100).toFixed(2) : 0}% of total`;
            }
        });

        const remaining = total - allocated;
        const percent = total > 0 ? (allocated / total) * 100 : 0;

        document.getElementById('product-allocated').textContent = 'EGP ' + allocated.toLocaleString();
        document.getElementById('product-remaining').textContent = 'EGP ' + remaining.toLocaleString();
        document.getElementById('product-allocation-progress').style.width = Math.min(percent, 100) + '%';
        document.getElementById('product-allocation-progress').className =
            'progress-bar ' + (percent > 100 ? 'bg-danger' : 'bg-success');
    }

    document.getElementById('save-products-btn').addEventListener('click', function() {
        const index = parseInt(document.getElementById('product-modal-row-index').value);
        const contractData = contracts.find(c => c.row_index === index);

        contractData.products = [];
        document.querySelectorAll('#product-allocations-container .product-allocation-row').forEach(row => {
            const productId = row.querySelector('.product-select').value;
            if (productId) {
                const type = row.querySelector('.allocation-type-select').value;
                const value = parseFloat(row.querySelector('.allocation-value').value) || 0;
                contractData.products.push({
                    product_id: parseInt(productId),
                    allocation_type: type,
                    allocation_percentage: type === 'percentage' ? value : null,
                    allocation_amount: type === 'amount' ? value : null
                });
            }
        });

        const card = container.querySelector(`[data-row-index="${index}"]`);
        updateCardBadges(card, contractData);

        productModal.hide();
        clearValidation();
    });

    // Payment Modal
    function openPaymentModal(index) {
        currentModalRowIndex = index;
        document.getElementById('payment-modal-row-index').value = index;

        const contractData = contracts.find(c => c.row_index === index);
        const total = parseFloat(contractData.total_amount) || 0;
        document.getElementById('payment-modal-total').textContent = 'EGP ' + total.toLocaleString();

        // Clear milestones
        document.getElementById('milestones-container').innerHTML = '';

        // Restore existing schedule
        if (contractData.payment_schedule) {
            if (contractData.payment_schedule.type === 'recurring') {
                document.querySelector('[data-bs-target="#recurring-tab"]').click();
                document.getElementById('payment-frequency').value = contractData.payment_schedule.frequency || 'monthly';
            } else if (contractData.payment_schedule.type === 'milestones') {
                document.querySelector('[data-bs-target="#milestones-tab"]').click();
                contractData.payment_schedule.milestones?.forEach(m => addMilestoneRow(m));
            }
        }

        updateRecurringPreview();
        updateMilestoneProgress();
        paymentModal.show();
    }

    function addMilestoneRow(data = null) {
        const template = document.getElementById('milestone-template');
        const container = document.getElementById('milestones-container');
        container.insertAdjacentHTML('beforeend', template.innerHTML);

        const row = container.lastElementChild;

        if (data) {
            row.querySelector('.milestone-name').value = data.name || '';
            row.querySelector('.milestone-amount').value = data.amount || '';
            row.querySelector('.milestone-date').value = data.due_date || '';
        }

        row.querySelector('.remove-milestone').addEventListener('click', function() {
            row.remove();
            updateMilestoneProgress();
        });

        row.querySelector('.milestone-amount').addEventListener('input', updateMilestoneProgress);
    }

    document.getElementById('add-milestone').addEventListener('click', function() {
        addMilestoneRow();
    });

    function updateRecurringPreview() {
        const index = parseInt(document.getElementById('payment-modal-row-index').value);
        const contractData = contracts.find(c => c.row_index === index);

        if (!contractData?.start_date || !contractData?.end_date || !contractData?.total_amount) {
            document.getElementById('recurring-preview').textContent = 'Set dates and amount in contract first';
            return;
        }

        const frequency = document.getElementById('payment-frequency').value;
        const startDate = new Date(contractData.start_date);
        const endDate = new Date(contractData.end_date);
        const total = parseFloat(contractData.total_amount);

        const months = { monthly: 1, quarterly: 3, 'bi-annual': 6, annual: 12 };
        const totalMonths = Math.max(1, Math.ceil((endDate - startDate) / (30 * 24 * 60 * 60 * 1000)));
        const paymentCount = Math.max(1, Math.ceil(totalMonths / months[frequency]));
        const paymentAmount = total / paymentCount;

        document.getElementById('recurring-preview').innerHTML =
            `<strong>${paymentCount}</strong> payments of <strong>EGP ${paymentAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>`;
    }

    document.getElementById('payment-frequency').addEventListener('change', updateRecurringPreview);

    function updateMilestoneProgress() {
        const index = parseInt(document.getElementById('payment-modal-row-index').value);
        const contractData = contracts.find(c => c.row_index === index);
        const total = parseFloat(contractData?.total_amount) || 0;

        let milestoneTotal = 0;
        document.querySelectorAll('#milestones-container .milestone-row').forEach(row => {
            milestoneTotal += parseFloat(row.querySelector('.milestone-amount').value) || 0;
        });

        const remaining = total - milestoneTotal;
        const percent = total > 0 ? (milestoneTotal / total) * 100 : 0;

        document.getElementById('milestone-total').textContent = 'EGP ' + milestoneTotal.toLocaleString();
        document.getElementById('milestone-remaining').textContent = 'EGP ' + remaining.toLocaleString();
        document.getElementById('milestone-progress').style.width = Math.min(percent, 100) + '%';
        document.getElementById('milestone-progress').className =
            'progress-bar ' + (percent > 100 ? 'bg-danger' : 'bg-success');
    }

    document.getElementById('clear-payment-btn').addEventListener('click', function() {
        const index = parseInt(document.getElementById('payment-modal-row-index').value);
        const contractData = contracts.find(c => c.row_index === index);
        contractData.payment_schedule = null;

        document.getElementById('milestones-container').innerHTML = '';
        document.getElementById('payment-frequency').value = 'monthly';

        const card = document.querySelector(`[data-row-index="${index}"]`);
        updateCardBadges(card, contractData);

        paymentModal.hide();
    });

    document.getElementById('save-payment-btn').addEventListener('click', function() {
        const index = parseInt(document.getElementById('payment-modal-row-index').value);
        const contractData = contracts.find(c => c.row_index === index);

        const activeTab = document.querySelector('#paymentModal .nav-link.active').dataset.bsTarget;

        if (activeTab === '#recurring-tab') {
            contractData.payment_schedule = {
                type: 'recurring',
                frequency: document.getElementById('payment-frequency').value
            };
        } else {
            const milestones = [];
            document.querySelectorAll('#milestones-container .milestone-row').forEach(row => {
                const name = row.querySelector('.milestone-name').value;
                const amount = row.querySelector('.milestone-amount').value;
                if (name && amount) {
                    milestones.push({
                        name: name,
                        amount: parseFloat(amount),
                        due_date: row.querySelector('.milestone-date').value || null
                    });
                }
            });

            if (milestones.length > 0) {
                contractData.payment_schedule = {
                    type: 'milestones',
                    milestones: milestones
                };
            } else {
                contractData.payment_schedule = null;
            }
        }

        const card = document.querySelector(`[data-row-index="${index}"]`);
        updateCardBadges(card, contractData);

        paymentModal.hide();
        clearValidation();
    });

    // Update card badges
    function updateCardBadges(card, data) {
        card.querySelector('.project-count').textContent = data.project_ids?.length || 0;
        card.querySelector('.product-count').textContent = data.products?.length || 0;

        if (data.payment_schedule) {
            if (data.payment_schedule.type === 'recurring') {
                card.querySelector('.payment-summary').textContent =
                    data.payment_schedule.frequency.charAt(0).toUpperCase() + data.payment_schedule.frequency.slice(1);
            } else {
                card.querySelector('.payment-summary').textContent =
                    data.payment_schedule.milestones?.length + ' milestones';
            }
        } else {
            card.querySelector('.payment-summary').textContent = 'Not set';
        }
    }

    // Renumber cards
    function renumberCards() {
        document.querySelectorAll('.contract-card').forEach((card, idx) => {
            card.querySelector('.row-number').textContent = idx + 1;
        });
    }

    // Update UI
    function updateUI() {
        const hasContracts = contracts.length > 0;
        emptyState.style.display = hasContracts ? 'none' : 'block';
        bottomActions.style.display = hasContracts ? 'block' : 'none';
        updateSummary();
    }

    // Update summary
    function updateSummary() {
        document.getElementById('contract-count').textContent = contracts.length;

        const total = contracts.reduce((sum, c) => sum + (parseFloat(c.total_amount) || 0), 0);
        document.getElementById('total-value').textContent = 'EGP ' + total.toLocaleString();
    }

    // Clear validation
    function clearValidation() {
        document.getElementById('validation-summary').innerHTML = '<span class="badge bg-secondary">Not validated</span>';
        document.getElementById('save-btn').disabled = true;
        document.getElementById('save-btn-bottom').disabled = true;

        document.querySelectorAll('.contract-card').forEach(card => {
            card.classList.remove('has-errors', 'is-valid');
            card.querySelector('.row-number').classList.remove('valid', 'invalid');
            card.querySelector('.validation-status').innerHTML = '';
            card.querySelector('.error-list').style.display = 'none';
        });
    }

    // Validate all
    function validateAll() {
        const btn = document.getElementById('validate-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Validating...';

        fetch('{{ route("accounting.income.contracts.mass-entry.validate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ contracts: contracts })
        })
        .then(res => res.json())
        .then(data => {
            // Update validation summary
            if (data.valid) {
                document.getElementById('validation-summary').innerHTML =
                    `<span class="badge bg-success"><i class="ti ti-check me-1"></i>${data.total_valid} valid</span>`;
                document.getElementById('save-btn').disabled = false;
                document.getElementById('save-btn-bottom').disabled = false;
            } else {
                document.getElementById('validation-summary').innerHTML =
                    `<span class="badge bg-success me-1">${data.total_valid} valid</span>
                     <span class="badge bg-danger">${data.total_invalid} errors</span>`;
                document.getElementById('save-btn').disabled = data.total_valid === 0;
                document.getElementById('save-btn-bottom').disabled = data.total_valid === 0;
            }

            // Update individual cards
            contracts.forEach((contract, idx) => {
                const card = document.querySelector(`[data-row-index="${contract.row_index}"]`);
                const errors = data.errors[idx] || [];

                if (errors.length > 0) {
                    card.classList.add('has-errors');
                    card.classList.remove('is-valid');
                    card.querySelector('.row-number').classList.add('invalid');
                    card.querySelector('.row-number').classList.remove('valid');
                    card.querySelector('.validation-status').innerHTML =
                        '<span class="badge bg-danger"><i class="ti ti-x"></i> Invalid</span>';

                    const errorList = card.querySelector('.error-list');
                    errorList.querySelector('ul').innerHTML = errors.map(e => `<li>${e}</li>`).join('');
                    errorList.style.display = 'block';
                } else {
                    card.classList.remove('has-errors');
                    card.classList.add('is-valid');
                    card.querySelector('.row-number').classList.remove('invalid');
                    card.querySelector('.row-number').classList.add('valid');
                    card.querySelector('.validation-status').innerHTML =
                        '<span class="badge bg-success"><i class="ti ti-check"></i> Valid</span>';
                    card.querySelector('.error-list').style.display = 'none';
                }
            });
        })
        .catch(err => {
            console.error('Validation error:', err);
            alert('Validation failed. Please try again.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-check me-1"></i>Validate All';
        });
    }

    // Save all
    function saveAll() {
        if (!confirm('Save all valid contracts? This action cannot be undone.')) return;

        const btn = document.getElementById('save-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

        fetch('{{ route("accounting.income.contracts.mass-entry.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ contracts: contracts })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.href = data.redirect_url;
            } else {
                alert(data.message);
                // Update errors if any
                if (data.errors) {
                    Object.entries(data.errors).forEach(([idx, errors]) => {
                        const contract = contracts[idx];
                        if (contract) {
                            const card = document.querySelector(`[data-row-index="${contract.row_index}"]`);
                            if (card) {
                                card.classList.add('has-errors');
                                const errorList = card.querySelector('.error-list');
                                errorList.querySelector('ul').innerHTML = errors.map(e => `<li>${e}</li>`).join('');
                                errorList.style.display = 'block';
                            }
                        }
                    });
                }
            }
        })
        .catch(err => {
            console.error('Save error:', err);
            alert('Failed to save contracts. Please try again.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-device-floppy me-1"></i>Save All';
        });
    }
});
</script>
@endsection
