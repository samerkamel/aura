@extends('layouts/layoutMaster')

@section('title', 'Edit Contract')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Edit Contract</h5>
                    <small class="text-muted">Update {{ $contract->contract_number }} - {{ $contract->client_name }}</small>
                </div>
                <a href="{{ route('accounting.income.contracts.show', $contract) }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Back to Details
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('accounting.income.contracts.update', $contract) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <!-- Basic Information -->
                        <div class="col-lg-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Contract Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="client_name" class="form-label">Client Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('client_name') is-invalid @enderror"
                                                   id="client_name" name="client_name" value="{{ old('client_name', $contract->client_name) }}"
                                                   placeholder="e.g., ABC Company Ltd.">
                                            @error('client_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="contract_number" class="form-label">Contract Number <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('contract_number') is-invalid @enderror"
                                                   id="contract_number" name="contract_number" value="{{ old('contract_number', $contract->contract_number) }}"
                                                   placeholder="e.g., CONT-2024-001">
                                            @error('contract_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror"
                                                      id="description" name="description" rows="3"
                                                      placeholder="Brief description of the contract">{{ old('description', $contract->description) }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Details -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Financial Details</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="total_amount" class="form-label">Total Contract Amount <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">EGP</span>
                                                <input type="number" class="form-control @error('total_amount') is-invalid @enderror"
                                                       id="total_amount" name="total_amount" value="{{ old('total_amount', $contract->total_amount) }}"
                                                       step="0.01" min="0" max="99999999.99"
                                                       placeholder="0.00">
                                                @error('total_amount')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                            <select class="form-select @error('status') is-invalid @enderror"
                                                    id="status" name="status">
                                                <option value="">Select Status</option>
                                                <option value="draft" {{ old('status', $contract->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                                                <option value="active" {{ old('status', $contract->status) === 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="completed" {{ old('status', $contract->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                                                <option value="cancelled" {{ old('status', $contract->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                            </select>
                                            @error('status')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
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
                                                @foreach($contract->products as $index => $product)
                                                <div class="allocation-row">
                                                    <div class="card mb-3">
                                                        <div class="card-body">
                                                            <div class="row g-3">
                                                                <div class="col-md-4">
                                                                    <label class="form-label">Product <span class="text-danger">*</span></label>
                                                                    <select class="form-select allocation-product" name="products[{{ $index }}][product_id]">
                                                                        <option value="">Select product</option>
                                                                        @foreach($products as $prod)
                                                                            <option value="{{ $prod->id }}" {{ $product->id == $prod->id ? 'selected' : '' }}>
                                                                                {{ $prod->name }} {{ $prod->code ? '(' . $prod->code . ')' : '' }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <label class="form-label">Type <span class="text-danger">*</span></label>
                                                                    <select class="form-select allocation-type" name="products[{{ $index }}][allocation_type]">
                                                                        <option value="percentage" {{ $product->pivot->allocation_type == 'percentage' ? 'selected' : '' }}>Percentage</option>
                                                                        <option value="amount" {{ $product->pivot->allocation_type == 'amount' ? 'selected' : '' }}>Amount</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <label class="form-label allocation-value-label">
                                                                        {{ $product->pivot->allocation_type == 'percentage' ? 'Percentage (%)' : 'Amount (EGP)' }} <span class="text-danger">*</span>
                                                                    </label>
                                                                    <div class="input-group">
                                                                        <input type="number" class="form-control allocation-value {{ $product->pivot->allocation_type == 'amount' ? 'd-none' : '' }}"
                                                                               name="products[{{ $index }}][allocation_percentage]"
                                                                               step="0.01" min="0" max="100"
                                                                               value="{{ $product->pivot->allocation_percentage }}"
                                                                               placeholder="0.00">
                                                                        <input type="number" class="form-control allocation-value {{ $product->pivot->allocation_type == 'percentage' ? 'd-none' : '' }}"
                                                                               name="products[{{ $index }}][allocation_amount]"
                                                                               step="0.01" min="0"
                                                                               value="{{ $product->pivot->allocation_amount }}"
                                                                               placeholder="0.00">
                                                                        <span class="input-group-text allocation-unit">{{ $product->pivot->allocation_type == 'percentage' ? '%' : 'EGP' }}</span>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2 d-flex align-items-end">
                                                                    <button type="button" class="btn btn-outline-danger remove-allocation w-100">
                                                                        <i class="ti ti-trash"></i>
                                                                    </button>
                                                                </div>
                                                                <div class="col-md-8">
                                                                    <label class="form-label">Notes <small class="text-muted">(Optional)</small></label>
                                                                    <input type="text" class="form-control" name="products[{{ $index }}][notes]"
                                                                           value="{{ $product->pivot->notes }}"
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
                                                @endforeach
                                            </div>

                                            <button type="button" class="btn btn-outline-primary btn-sm" id="add-product">
                                                <i class="ti ti-plus me-1"></i>Add Product
                                            </button>
                                        </div>

                                        @if($contract->products->count() > 0)
                                        <div class="col-12" id="allocation-summary">
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
                                        @else
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
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Project Allocation -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Project Allocation</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Project Assignments <small class="text-muted">(Optional)</small></label>
                                            <p class="text-muted small mb-3">Link this contract to projects. Revenue will be distributed based on allocation.</p>

                                            <div id="project-allocations">
                                                @foreach($contract->projects as $index => $project)
                                                <div class="project-allocation-row">
                                                    <div class="card mb-3 border-primary">
                                                        <div class="card-body">
                                                            <div class="row g-3">
                                                                <div class="col-md-4">
                                                                    <label class="form-label">Project <span class="text-danger">*</span></label>
                                                                    <select class="form-select project-allocation-project" name="project_allocations[{{ $index }}][project_id]">
                                                                        <option value="">Select project</option>
                                                                        @foreach($projects as $proj)
                                                                            <option value="{{ $proj->id }}" {{ $project->id == $proj->id ? 'selected' : '' }}>
                                                                                {{ $proj->name }} {{ $proj->code ? '(' . $proj->code . ')' : '' }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <label class="form-label">Type <span class="text-danger">*</span></label>
                                                                    <select class="form-select project-allocation-type" name="project_allocations[{{ $index }}][allocation_type]">
                                                                        <option value="percentage" {{ ($project->pivot->allocation_type ?? 'percentage') == 'percentage' ? 'selected' : '' }}>Percentage</option>
                                                                        <option value="amount" {{ ($project->pivot->allocation_type ?? '') == 'amount' ? 'selected' : '' }}>Amount</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <label class="form-label project-allocation-value-label">
                                                                        {{ ($project->pivot->allocation_type ?? 'percentage') == 'percentage' ? 'Percentage (%)' : 'Amount (EGP)' }} <span class="text-danger">*</span>
                                                                    </label>
                                                                    <div class="input-group">
                                                                        <input type="number" class="form-control project-allocation-value {{ ($project->pivot->allocation_type ?? 'percentage') == 'amount' ? 'd-none' : '' }}"
                                                                               name="project_allocations[{{ $index }}][allocation_percentage]"
                                                                               step="0.01" min="0" max="100"
                                                                               value="{{ $project->pivot->allocation_percentage }}"
                                                                               placeholder="0.00">
                                                                        <input type="number" class="form-control project-allocation-value {{ ($project->pivot->allocation_type ?? 'percentage') == 'percentage' ? 'd-none' : '' }}"
                                                                               name="project_allocations[{{ $index }}][allocation_amount]"
                                                                               step="0.01" min="0"
                                                                               value="{{ $project->pivot->allocation_amount }}"
                                                                               placeholder="0.00">
                                                                        <span class="input-group-text project-allocation-unit">{{ ($project->pivot->allocation_type ?? 'percentage') == 'percentage' ? '%' : 'EGP' }}</span>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2 d-flex align-items-end">
                                                                    <button type="button" class="btn btn-outline-danger remove-project-allocation w-100">
                                                                        <i class="ti ti-trash"></i>
                                                                    </button>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label class="form-label">Notes <small class="text-muted">(Optional)</small></label>
                                                                    <input type="text" class="form-control" name="project_allocations[{{ $index }}][notes]"
                                                                           value="{{ $project->pivot->notes }}"
                                                                           placeholder="Notes for this allocation">
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <label class="form-label">&nbsp;</label>
                                                                    <div class="form-check mt-2">
                                                                        <input type="checkbox" class="form-check-input project-is-primary"
                                                                               name="project_allocations[{{ $index }}][is_primary]" value="1"
                                                                               {{ $project->pivot->is_primary ? 'checked' : '' }}>
                                                                        <label class="form-check-label">Primary Project</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="project-calculated-amount text-muted small mt-2"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>

                                            <button type="button" class="btn btn-outline-primary btn-sm" id="add-project">
                                                <i class="ti ti-plus me-1"></i>Add Project
                                            </button>
                                        </div>

                                        <div class="col-12" id="project-allocation-summary" style="{{ $contract->projects->count() > 0 ? '' : 'display: none;' }}">
                                            <div class="alert alert-primary">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <strong>Project Allocation Summary</strong>
                                                    <div class="progress" style="width: 200px; height: 8px;">
                                                        <div class="progress-bar bg-primary" id="project-allocation-progress" style="width: 0%"></div>
                                                    </div>
                                                </div>
                                                <div class="row text-sm">
                                                    <div class="col-6">
                                                        <strong>Total Allocated:</strong> <span id="project-total-allocated">0 EGP</span>
                                                    </div>
                                                    <div class="col-6">
                                                        <strong>Remaining:</strong> <span id="project-remaining-amount">0 EGP</span>
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
                                                   id="start_date" name="start_date" value="{{ old('start_date', $contract->start_date->format('Y-m-d')) }}">
                                            @error('start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Used for Income Sheet monthly grouping</small>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="end_date" class="form-label">End Date (Optional)</label>
                                            <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                                   id="end_date" name="end_date" value="{{ old('end_date', $contract->end_date ? $contract->end_date->format('Y-m-d') : '') }}">
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
                                                   value="{{ old('contact_info.email', $contract->contact_info['email'] ?? '') }}"
                                                   placeholder="contact@company.com">
                                            @error('contact_info.email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="contact_phone" class="form-label">Contact Phone</label>
                                            <input type="text" class="form-control @error('contact_info.phone') is-invalid @enderror"
                                                   id="contact_phone" name="contact_info[phone]"
                                                   value="{{ old('contact_info.phone', $contract->contact_info['phone'] ?? '') }}"
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
                                              placeholder="Any additional notes or special terms for this contract">{{ old('notes', $contract->notes) }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Side Panel -->
                        <div class="col-lg-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Current Status</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Current Status</label>
                                        <div>
                                            <span class="badge bg-{{ $contract->status === 'active' ? 'success' : ($contract->status === 'completed' ? 'info' : ($contract->status === 'cancelled' ? 'danger' : 'warning')) }} me-2">
                                                {{ \Illuminate\Support\Str::ucfirst($contract->status) }}
                                            </span>
                                            <span class="badge bg-{{ $contract->is_active ? 'success' : 'secondary' }}">
                                                {{ $contract->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Current Total Amount</label>
                                        <div class="h5 text-success">{{ number_format($contract->total_amount, 2) }} EGP</div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Payment Milestones</label>
                                        <div>{{ $contract->payments->count() }} payments</div>
                                        <small class="text-muted">{{ $contract->payments->where('status', '!=', 'cancelled')->count() }} active</small>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Update Guidelines</h6>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="ti ti-info-circle me-2"></i>
                                        <strong>Important Notes</strong>
                                        <ul class="mb-0 mt-2">
                                            <li>Changing the contract number affects all related records</li>
                                            <li>Status changes affect cash flow projections</li>
                                            <li>Amount changes don't affect existing payments automatically</li>
                                            <li>Date changes may affect payment calculations</li>
                                        </ul>
                                    </div>

                                    @if($contract->payments->count() > 0)
                                        <div class="alert alert-warning">
                                            <i class="ti ti-alert-triangle me-2"></i>
                                            <strong>Active Payments</strong>
                                            <p class="mb-0">This contract has payment milestones. Consider their impact when making changes.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Status Guide</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <span class="badge bg-warning me-2">Draft</span>
                                        <small>Contract in preparation</small>
                                    </div>
                                    <div class="mb-2">
                                        <span class="badge bg-success me-2">Active</span>
                                        <small>Contract is active and generating income</small>
                                    </div>
                                    <div class="mb-2">
                                        <span class="badge bg-info me-2">Completed</span>
                                        <small>Contract has been fulfilled</small>
                                    </div>
                                    <div class="mb-0">
                                        <span class="badge bg-danger me-2">Cancelled</span>
                                        <small>Contract was terminated</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-footer d-flex justify-content-between">
                                    <a href="{{ route('accounting.income.contracts.show', $contract) }}" class="btn btn-outline-secondary">
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
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}">
                                    {{ $prod->name }} {{ $prod->code ? '(' . $prod->code . ')' : '' }}
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

<!-- Hidden Template for Project Allocation -->
<div id="project-allocation-template" style="display: none;">
    <div class="project-allocation-row">
        <div class="card mb-3 border-primary">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Project <span class="text-danger">*</span></label>
                        <select class="form-select project-allocation-project" name="project_allocations[INDEX][project_id]">
                            <option value="">Select project</option>
                            @foreach($projects as $proj)
                                <option value="{{ $proj->id }}">
                                    {{ $proj->name }} {{ $proj->code ? '(' . $proj->code . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select class="form-select project-allocation-type" name="project_allocations[INDEX][allocation_type]">
                            <option value="percentage">Percentage</option>
                            <option value="amount">Amount</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label project-allocation-value-label">Percentage (%) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control project-allocation-value"
                                   name="project_allocations[INDEX][allocation_percentage]"
                                   step="0.01" min="0" max="100" placeholder="0.00">
                            <input type="number" class="form-control project-allocation-value d-none"
                                   name="project_allocations[INDEX][allocation_amount]"
                                   step="0.01" min="0" placeholder="0.00">
                            <span class="input-group-text project-allocation-unit">%</span>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger remove-project-allocation w-100">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Notes <small class="text-muted">(Optional)</small></label>
                        <input type="text" class="form-control" name="project_allocations[INDEX][notes]"
                               placeholder="Notes for this allocation">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="form-check mt-2">
                            <input type="checkbox" class="form-check-input project-is-primary"
                                   name="project_allocations[INDEX][is_primary]" value="1">
                            <label class="form-check-label">Primary Project</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="project-calculated-amount text-muted small mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let allocationIndex = {{ $contract->products->count() }};
    let projectAllocationIndex = {{ $contract->projects->count() }};

    // Add new product allocation
    document.getElementById('add-product').addEventListener('click', function() {
        const template = document.getElementById('allocation-template').innerHTML;
        const newAllocation = template.replace(/INDEX/g, allocationIndex);
        document.getElementById('product-allocations').insertAdjacentHTML('beforeend', newAllocation);
        allocationIndex++;
        updateCalculations();
        document.getElementById('allocation-summary').style.display = 'block';
    });

    // Add new project allocation
    document.getElementById('add-project').addEventListener('click', function() {
        const template = document.getElementById('project-allocation-template').innerHTML;
        const newAllocation = template.replace(/INDEX/g, projectAllocationIndex);
        document.getElementById('project-allocations').insertAdjacentHTML('beforeend', newAllocation);
        projectAllocationIndex++;
        updateProjectCalculations();
        document.getElementById('project-allocation-summary').style.display = 'block';
    });

    // Remove allocation (product or project)
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-allocation')) {
            e.target.closest('.allocation-row').remove();
            updateCalculations();

            // Hide summary if no allocations
            if (document.querySelectorAll('.allocation-row').length === 0) {
                document.getElementById('allocation-summary').style.display = 'none';
            }
        }

        if (e.target.closest('.remove-project-allocation')) {
            e.target.closest('.project-allocation-row').remove();
            updateProjectCalculations();

            // Hide summary if no project allocations
            if (document.querySelectorAll('.project-allocation-row').length === 0) {
                document.getElementById('project-allocation-summary').style.display = 'none';
            }
        }
    });

    // Handle allocation type change (product)
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

        // Handle project allocation type change
        if (e.target.classList.contains('project-allocation-type')) {
            const row = e.target.closest('.project-allocation-row');
            const type = e.target.value;
            const percentageInput = row.querySelector('input[name*="[allocation_percentage]"]');
            const amountInput = row.querySelector('input[name*="[allocation_amount]"]');
            const label = row.querySelector('.project-allocation-value-label');
            const unit = row.querySelector('.project-allocation-unit');

            if (type === 'percentage') {
                percentageInput.classList.remove('d-none');
                amountInput.classList.add('d-none');
                label.innerHTML = 'Percentage (%) <span class="text-danger">*</span>';
                unit.textContent = '%';
                amountInput.value = '';
            } else {
                percentageInput.classList.add('d-none');
                amountInput.classList.remove('d-none');
                label.innerHTML = 'Amount (EGP) <span class="text-danger">*</span>';
                unit.textContent = 'EGP';
                percentageInput.value = '';
            }
            updateProjectCalculations();
        }

        // Handle primary project checkbox - only one can be checked
        if (e.target.classList.contains('project-is-primary')) {
            if (e.target.checked) {
                document.querySelectorAll('.project-is-primary').forEach(function(checkbox) {
                    if (checkbox !== e.target) {
                        checkbox.checked = false;
                    }
                });
            }
        }
    });

    // Handle value changes
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('allocation-value') || e.target.id === 'total_amount') {
            updateCalculations();
        }
        if (e.target.classList.contains('project-allocation-value') || e.target.id === 'total_amount') {
            updateProjectCalculations();
        }
    });

    // Update product calculations
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

        if (document.getElementById('total-allocated')) {
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
    }

    // Update project calculations
    function updateProjectCalculations() {
        const totalAmount = parseFloat(document.getElementById('total_amount').value) || 0;
        let totalAllocated = 0;

        document.querySelectorAll('.project-allocation-row').forEach(function(row) {
            const type = row.querySelector('.project-allocation-type').value;
            let allocation = 0;

            if (type === 'percentage') {
                const percentage = parseFloat(row.querySelector('input[name*="[allocation_percentage]"]').value) || 0;
                allocation = (percentage / 100) * totalAmount;
                row.querySelector('.project-calculated-amount').textContent = `Calculated: ${allocation.toLocaleString()} EGP`;
            } else {
                allocation = parseFloat(row.querySelector('input[name*="[allocation_amount]"]').value) || 0;
                if (totalAmount > 0) {
                    const percentage = (allocation / totalAmount) * 100;
                    row.querySelector('.project-calculated-amount').textContent = `Calculated: ${percentage.toFixed(2)}% of contract`;
                }
            }

            totalAllocated += allocation;
        });

        const remaining = totalAmount - totalAllocated;
        const percentage = totalAmount > 0 ? (totalAllocated / totalAmount) * 100 : 0;

        if (document.getElementById('project-total-allocated')) {
            document.getElementById('project-total-allocated').textContent = totalAllocated.toLocaleString() + ' EGP';
            document.getElementById('project-remaining-amount').textContent = remaining.toLocaleString() + ' EGP';
            document.getElementById('project-allocation-progress').style.width = Math.min(percentage, 100) + '%';

            // Update progress bar color
            if (percentage > 100) {
                document.getElementById('project-allocation-progress').className = 'progress-bar bg-danger';
            } else if (percentage >= 90) {
                document.getElementById('project-allocation-progress').className = 'progress-bar bg-warning';
            } else {
                document.getElementById('project-allocation-progress').className = 'progress-bar bg-primary';
            }

            // Update remaining amount color
            const remainingElement = document.getElementById('project-remaining-amount');
            if (remaining < 0) {
                remainingElement.className = 'text-danger';
            } else if (remaining === 0) {
                remainingElement.className = 'text-success';
            } else {
                remainingElement.className = '';
            }
        }
    }

    // Initialize calculations on page load
    updateCalculations();
    updateProjectCalculations();
});
</script>
@endsection