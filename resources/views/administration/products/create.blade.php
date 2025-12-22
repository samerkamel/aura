@extends('layouts/layoutMaster')

@section('title', 'Create Product')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Business Unit Context Header -->
    @if($currentBusinessUnit)
    <div class="alert alert-info mb-4">
        <div class="d-flex align-items-center">
            <div class="avatar avatar-sm me-3">
                <span class="avatar-initial rounded-circle bg-label-{{ $currentBusinessUnit->type === 'head_office' ? 'info' : 'primary' }}">
                    <i class="ti {{ $currentBusinessUnit->type === 'head_office' ? 'ti-building-skyscraper' : 'ti-building' }} ti-sm"></i>
                </span>
            </div>
            <div class="flex-grow-1">
                <h6 class="mb-0">Creating Product for {{ $currentBusinessUnit->name }}</h6>
                <small class="text-muted">This product will be assigned to {{ $currentBusinessUnit->code }}</small>
            </div>
        </div>
    </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold py-3 mb-0">Create Product</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('administration.products.index') }}">Product Management</a>
                    </li>
                    <li class="breadcrumb-item active">Create Product</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('administration.products.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-2"></i>Back to Products
        </a>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Product Information</h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('administration.products.store') }}">
                        @csrf

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

                            <!-- Name -->
                            <div class="col-md-6">
                                <label class="form-label" for="name">Product Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Code -->
                            <div class="col-md-6">
                                <label class="form-label" for="code">Product Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror"
                                       id="code" name="code" value="{{ old('code') }}" required maxlength="10"
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
                                          id="description" name="description" rows="3">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Head of Product -->
                            <div class="col-md-6">
                                <label class="form-label" for="head_of_product">Head of Product</label>
                                <input type="text" class="form-control @error('head_of_product') is-invalid @enderror"
                                       id="head_of_product" name="head_of_product" value="{{ old('head_of_product') }}">
                                @error('head_of_product')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Annual Budget Section -->
                            <div class="col-12">
                                <hr class="my-3">
                                <h6 class="mb-3"><i class="ti ti-calendar-dollar me-2"></i>Annual Budget</h6>
                            </div>

                            <!-- Budget Year -->
                            <div class="col-md-6">
                                <label class="form-label" for="budget_year">Budget Year <span class="text-danger">*</span></label>
                                <select class="form-select @error('budget_year') is-invalid @enderror"
                                        id="budget_year" name="budget_year" required>
                                    @for($year = date('Y') + 1; $year >= date('Y') - 5; $year--)
                                        <option value="{{ $year }}" {{ old('budget_year', date('Y')) == $year ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endfor
                                </select>
                                @error('budget_year')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Budget Allocation -->
                            <div class="col-md-6">
                                <label class="form-label" for="budget_allocation">Budget Amount (EGP) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('budget_allocation') is-invalid @enderror"
                                       id="budget_allocation" name="budget_allocation" value="{{ old('budget_allocation') }}"
                                       min="0" step="0.01" required>
                                @error('budget_allocation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Annual target budget for this product</small>
                            </div>

                            <!-- Email -->
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email Address</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       id="email" name="email" value="{{ old('email') }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Phone -->
                            <div class="col-md-6">
                                <label class="form-label" for="phone">Phone Number</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                       id="phone" name="phone" value="{{ old('phone') }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active"
                                           name="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
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
                                <a href="{{ route('administration.products.index') }}" class="btn btn-outline-secondary">
                                    <i class="ti ti-x me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-device-floppy me-1"></i>Create Product
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <!-- Help Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-info-circle me-2"></i>Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6>Creating a New Product</h6>
                        <ul class="mb-0 ps-3">
                            <li>Product name and code are required</li>
                            <li>Product codes must be unique</li>
                            <li>Products can be assigned to contracts for cost allocation</li>
                            <li>Inactive products cannot receive new contract assignments</li>
                        </ul>
                    </div>
                    <div class="alert alert-warning mt-3">
                        <h6><i class="ti ti-calendar-dollar me-1"></i>Annual Budget</h6>
                        <ul class="mb-0 ps-3">
                            <li>Budgets are tracked per year</li>
                            <li>You can add budgets for different years after creation</li>
                            <li>Budget history is maintained for reporting</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const codeInput = document.getElementById('code');

    // Auto-generate product code from name
    if (nameInput && codeInput) {
        nameInput.addEventListener('input', function() {
            if (!codeInput.value) {
                const productName = this.value;
                let productCode = productName.toUpperCase()
                    .replace(/[^A-Z0-9\s]/g, '')
                    .split(' ')
                    .map(word => word.substring(0, 3))
                    .join('')
                    .substring(0, 10);

                codeInput.value = productCode;
            }
        });
    }

    // Force uppercase for product code
    if (codeInput) {
        codeInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }
});
</script>
@endsection