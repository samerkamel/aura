@extends('layouts/layoutMaster')

@section('title', 'Create Department')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold py-3 mb-0">Create Department</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('administration.departments.index') }}">Department Management</a>
                    </li>
                    <li class="breadcrumb-item active">Create Department</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('administration.departments.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-2"></i>Back to Departments
        </a>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Department Information</h5>
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

                    <form method="POST" action="{{ route('administration.departments.store') }}">
                        @csrf

                        <div class="row g-3">
                            <!-- Name -->
                            <div class="col-md-6">
                                <label class="form-label" for="name">Department Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Code -->
                            <div class="col-md-6">
                                <label class="form-label" for="code">Department Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror"
                                       id="code" name="code" value="{{ old('code') }}" required maxlength="10"
                                       style="text-transform: uppercase;">
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Short code for the department (e.g., IT, HR, SALES)</small>
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

                            <!-- Head of Department -->
                            <div class="col-md-6">
                                <label class="form-label" for="head_of_department">Head of Department</label>
                                <input type="text" class="form-control @error('head_of_department') is-invalid @enderror"
                                       id="head_of_department" name="head_of_department" value="{{ old('head_of_department') }}">
                                @error('head_of_department')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Budget Allocation -->
                            <div class="col-md-6">
                                <label class="form-label" for="budget_allocation">Budget Allocation (EGP)</label>
                                <input type="number" class="form-control @error('budget_allocation') is-invalid @enderror"
                                       id="budget_allocation" name="budget_allocation" value="{{ old('budget_allocation') }}"
                                       min="0" step="0.01">
                                @error('budget_allocation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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
                                        Active Department
                                    </label>
                                </div>
                                <small class="text-muted">Inactive departments cannot be assigned to contracts</small>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="pt-4 border-top mt-4">
                            <div class="d-flex justify-content-end gap-3">
                                <a href="{{ route('administration.departments.index') }}" class="btn btn-outline-secondary">
                                    <i class="ti ti-x me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-device-floppy me-1"></i>Create Department
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
                        <h6>Creating a New Department</h6>
                        <ul class="mb-0 ps-3">
                            <li>Department name and code are required</li>
                            <li>Department codes must be unique</li>
                            <li>Budget allocation is optional but recommended</li>
                            <li>Departments can be assigned to contracts for cost allocation</li>
                            <li>Inactive departments cannot receive new contract assignments</li>
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
    // Auto-generate department code from name
    $('#name').on('input', function() {
        if (!$('#code').val()) {
            const departmentName = $(this).val();
            let departmentCode = departmentName.toUpperCase()
                .replace(/[^A-Z0-9\s]/g, '')
                .split(' ')
                .map(word => word.substring(0, 3))
                .join('')
                .substring(0, 10);

            $('#code').val(departmentCode);
        }
    });

    // Force uppercase for department code
    $('#code').on('input', function() {
        $(this).val($(this).val().toUpperCase());
    });
});
</script>
@endsection