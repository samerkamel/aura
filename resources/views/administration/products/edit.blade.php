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
                        <a href="{{ route('administration.products.show', $department) }}">{{ $department->name }}</a>
                    </li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('administration.products.show', $department) }}" class="btn btn-outline-info">
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
                    <form method="POST" action="{{ route('administration.products.update', $department) }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <!-- Name -->
                            <div class="col-md-6">
                                <label class="form-label" for="name">Product Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name', $department->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Code -->
                            <div class="col-md-6">
                                <label class="form-label" for="code">Product Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror"
                                       id="code" name="code" value="{{ old('code', $department->code) }}" required maxlength="10"
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
                                          id="description" name="description" rows="3">{{ old('description', $department->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Head of Product -->
                            <div class="col-md-6">
                                <label class="form-label" for="head_of_product">Head of Product</label>
                                <input type="text" class="form-control @error('head_of_product') is-invalid @enderror"
                                       id="head_of_product" name="head_of_product" value="{{ old('head_of_product', $department->head_of_product) }}">
                                @error('head_of_product')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Budget Allocation -->
                            <div class="col-md-6">
                                <label class="form-label" for="budget_allocation">Budget Allocation (EGP)</label>
                                <input type="number" class="form-control @error('budget_allocation') is-invalid @enderror"
                                       id="budget_allocation" name="budget_allocation" value="{{ old('budget_allocation', $department->budget_allocation) }}"
                                       min="0" step="0.01">
                                @error('budget_allocation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email Address</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       id="email" name="email" value="{{ old('email', $department->email) }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Phone -->
                            <div class="col-md-6">
                                <label class="form-label" for="phone">Phone Number</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                       id="phone" name="phone" value="{{ old('phone', $department->phone) }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active"
                                           name="is_active" {{ old('is_active', $department->is_active) ? 'checked' : '' }}>
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
                                <a href="{{ route('administration.products.show', $department) }}" class="btn btn-outline-secondary">
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
                    <div class="mx-auto mb-3" style="width: 80px; height: 80px; border-radius: 12px; background-color: {{ '#' . substr(md5($department->code), 0, 6) }}; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; font-weight: 600;">
                        {{ $department->code }}
                    </div>
                    <h5>{{ $department->name }}</h5>
                    @if($department->head_of_product)
                        <p class="text-muted mb-0">{{ $department->head_of_product }}</p>
                    @endif
                    <span class="badge {{ $department->is_active ? 'bg-label-success' : 'bg-label-danger' }} mt-2">
                        {{ $department->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>

            <!-- Current Contracts -->
            @if($department->contracts->count() > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-file-text me-2"></i>Assigned Contracts
                    </h5>
                </div>
                <div class="card-body">
                    @foreach($department->contracts->take(5) as $contract)
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
                    @if($department->contracts->count() > 5)
                        <small class="text-muted">and {{ $department->contracts->count() - 5 }} more contracts...</small>
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
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Force uppercase for product code
    $('#code').on('input', function() {
        $(this).val($(this).val().toUpperCase());
    });

    // Update preview when name changes
    $('#name').on('input', function() {
        const name = $(this).val() || '{{ $department->name }}';
        $('.card-body h5').text(name);
    });
});
</script>
@endsection