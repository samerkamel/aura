@extends('layouts/layoutMaster')

@section('title', 'Edit Product')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold py-3 mb-0">Edit Product</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('administration.products.index') }}">Product Management</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('administration.products.show', $product) }}">{{ $product->name }}</a>
                    </li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('administration.products.show', $product) }}" class="btn btn-outline-info">
                <i class="ti ti-eye me-2"></i>View Details
            </a>
            <a href="{{ route('administration.products.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-2"></i>Back to Products
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Product Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('administration.products.update', $product) }}">
                        @csrf
                        @method('PUT')

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
                                                {{ old('business_unit_id', $product->business_unit_id) == $businessUnit->id ? 'selected' : '' }}>
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

                            <!-- Name -->
                            <div class="col-md-6">
                                <label class="form-label" for="name">Product Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name', $product->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Code -->
                            <div class="col-md-6">
                                <label class="form-label" for="code">Product Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror"
                                       id="code" name="code" value="{{ old('code', $product->code) }}" required maxlength="10"
                                       style="text-transform: uppercase;">
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Short code for the product (e.g., IT, HR, SALES)</small>
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <label class="form-label" for="description">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                          id="description" name="description" rows="3">{{ old('description', $product->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Head of Product -->
                            <div class="col-md-6">
                                <label class="form-label" for="head_of_product">Head of Product</label>
                                <input type="text" class="form-control @error('head_of_product') is-invalid @enderror"
                                       id="head_of_product" name="head_of_product" value="{{ old('head_of_product', $product->head_of_product) }}">
                                @error('head_of_product')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Note: Budget is now managed separately in the Budget History section below -->

                            <!-- Email -->
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email Address</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       id="email" name="email" value="{{ old('email', $product->email) }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Phone -->
                            <div class="col-md-6">
                                <label class="form-label" for="phone">Phone Number</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                       id="phone" name="phone" value="{{ old('phone', $product->phone) }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active"
                                           name="is_active" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Active Product
                                    </label>
                                </div>
                                <small class="text-muted">Inactive products cannot be assigned to contracts</small>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="pt-4 border-top mt-4">
                            <div class="d-flex justify-content-end gap-3">
                                <a href="{{ route('administration.products.show', $product) }}" class="btn btn-outline-secondary">
                                    <i class="ti ti-x me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-device-floppy me-1"></i>Update Product
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Annual Budget History -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti ti-calendar-dollar me-2"></i>Annual Budget History
                    </h5>
                    @if(count($availableYears) > 0)
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addBudgetModal">
                        <i class="ti ti-plus me-1"></i>Add Budget Year
                    </button>
                    @endif
                </div>
                <div class="card-body">
                    @if($product->budgets->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Year</th>
                                        <th class="text-end">Budget Amount</th>
                                        <th>Notes</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($product->budgets as $budget)
                                    <tr class="{{ $budget->budget_year == date('Y') ? 'table-info' : '' }}">
                                        <td>
                                            <span class="fw-semibold">{{ $budget->budget_year }}</span>
                                            @if($budget->budget_year == date('Y'))
                                                <span class="badge bg-label-info ms-1">Current</span>
                                            @endif
                                        </td>
                                        <td class="text-end fw-semibold">{{ number_format($budget->projected_revenue, 2) }} EGP</td>
                                        <td>
                                            @if($budget->notes)
                                                <small class="text-muted">{{ \Illuminate\Support\Str::limit($budget->notes, 50) }}</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editBudgetModal{{ $budget->id }}">
                                                <i class="ti ti-edit"></i>
                                            </button>
                                            <form action="{{ route('administration.products.budgets.destroy', [$product, $budget]) }}"
                                                  method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-icon btn-outline-danger"
                                                        onclick="return confirm('Are you sure you want to delete the {{ $budget->budget_year }} budget?')">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Edit Budget Modal for {{ $budget->budget_year }} -->
                                    <div class="modal fade" id="editBudgetModal{{ $budget->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('administration.products.budgets.update', [$product, $budget]) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit {{ $budget->budget_year }} Budget</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Year</label>
                                                            <input type="text" class="form-control" value="{{ $budget->budget_year }}" disabled>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="budget_amount_{{ $budget->id }}">Budget Amount (EGP) <span class="text-danger">*</span></label>
                                                            <input type="number" class="form-control" id="budget_amount_{{ $budget->id }}"
                                                                   name="budget_amount" value="{{ $budget->projected_revenue }}"
                                                                   min="0" step="0.01" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="notes_{{ $budget->id }}">Notes</label>
                                                            <textarea class="form-control" id="notes_{{ $budget->id }}"
                                                                      name="notes" rows="2">{{ $budget->notes }}</textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Update Budget</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="ti ti-calendar-off display-6 text-muted mb-3 d-block"></i>
                            <p class="text-muted mb-0">No budget history found. Add a budget for a specific year.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <!-- Product Preview -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-building me-2"></i>Product Preview
                    </h5>
                </div>
                <div class="card-body text-center">
                    <div class="mx-auto mb-3" style="width: 80px; height: 80px; border-radius: 12px; background-color: {{ '#' . substr(md5($product->code), 0, 6) }}; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; font-weight: 600;">
                        {{ $product->code }}
                    </div>
                    <h5>{{ $product->name }}</h5>
                    @if($product->head_of_product)
                        <p class="text-muted mb-0">{{ $product->head_of_product }}</p>
                    @endif
                    <span class="badge {{ $product->is_active ? 'bg-label-success' : 'bg-label-danger' }} mt-2">
                        {{ $product->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>

            <!-- Current Contracts -->
            @if($product->contracts->count() > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-file-text me-2"></i>Assigned Contracts
                    </h5>
                </div>
                <div class="card-body">
                    @foreach($product->contracts->take(5) as $contract)
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar avatar-xs flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-file-text ti-xs"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0">{{ $contract->contract_number }}</h6>
                            <small class="text-muted">
                                @if($contract->pivot->allocation_type === 'percentage')
                                    {{ $contract->pivot->allocation_percentage }}%
                                @else
                                    {{ number_format($contract->pivot->allocation_amount, 0) }} EGP
                                @endif
                            </small>
                        </div>
                    </div>
                    @endforeach
                    @if($product->contracts->count() > 5)
                        <small class="text-muted">and {{ $product->contracts->count() - 5 }} more contracts...</small>
                    @endif
                </div>
            </div>
            @endif

            <!-- Help -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-info-circle me-2"></i>Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6>Editing Product</h6>
                        <ul class="mb-0 ps-3">
                            <li>Product code must remain unique</li>
                            <li>Changes affect all assigned contracts</li>
                            <li>Deactivating will prevent new contract assignments</li>
                            <li>Budget allocation helps track spending</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Budget Modal -->
@if(count($availableYears) > 0)
<div class="modal fade" id="addBudgetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('administration.products.budgets.store', $product) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Budget for Year</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="new_budget_year">Year <span class="text-danger">*</span></label>
                        <select class="form-select" id="new_budget_year" name="budget_year" required>
                            @foreach($availableYears as $year)
                                <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="new_budget_amount">Budget Amount (EGP) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="new_budget_amount"
                               name="budget_amount" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="new_budget_notes">Notes</label>
                        <textarea class="form-control" id="new_budget_notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Budget</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Force uppercase for product code
    const codeInput = document.getElementById('code');
    if (codeInput) {
        codeInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }

    // Update preview when name changes
    const nameInput = document.getElementById('name');
    if (nameInput) {
        nameInput.addEventListener('input', function() {
            const name = this.value || '{{ $product->name }}';
            const previewTitle = document.querySelector('.card-body h5');
            if (previewTitle) {
                previewTitle.textContent = name;
            }
        });
    }
});
</script>
@endsection