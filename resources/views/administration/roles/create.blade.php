@extends('layouts/layoutMaster')

@section('title', 'Create Role')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
@endsection

@section('page-style')
<style>
.permission-category {
    border: 1px solid #e7eaf3;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}
.permission-category .category-header {
    background-color: #f8f9fa;
    padding: 1rem;
    border-bottom: 1px solid #e7eaf3;
    border-radius: 0.5rem 0.5rem 0 0;
}
.permission-list {
    padding: 1rem;
}
.permission-item {
    display: flex;
    align-items: flex-start;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f1f1f1;
}
.permission-item:last-child {
    border-bottom: none;
}
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold py-3 mb-0">Create Role</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('administration.roles.index') }}">Roles Management</a>
                    </li>
                    <li class="breadcrumb-item active">Create Role</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('administration.roles.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-2"></i>Back to Roles
        </a>
    </div>

    <form method="POST" action="{{ route('administration.roles.store') }}">
        @csrf
        <div class="row">
            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Role Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Name -->
                            <div class="col-md-6">
                                <label class="form-label" for="name">Role Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Internal name (lowercase, no spaces)</small>
                            </div>

                            <!-- Display Name -->
                            <div class="col-md-6">
                                <label class="form-label" for="display_name">Display Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('display_name') is-invalid @enderror"
                                       id="display_name" name="display_name" value="{{ old('display_name') }}" required>
                                @error('display_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Human-readable name</small>
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <label class="form-label" for="description">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                          id="description" name="description" rows="3">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Brief description of the role's purpose</small>
                            </div>

                            <!-- Status -->
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active"
                                           name="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Active Role
                                    </label>
                                </div>
                                <small class="text-muted">Inactive roles cannot be assigned to users</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Permissions -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Role Permissions</h5>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="selectAll">Select All</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAll">Deselect All</button>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($permissions->count() > 0)
                            @foreach($permissions as $category => $categoryPermissions)
                            <div class="permission-category">
                                <div class="category-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">
                                            <i class="ti ti-folder me-2"></i>{{ ucfirst(str_replace('-', ' ', $category)) }}
                                        </h6>
                                        <div>
                                            <button type="button" class="btn btn-xs btn-outline-primary category-select-all" data-category="{{ $category }}">
                                                Select All
                                            </button>
                                            <span class="badge bg-label-primary">{{ $categoryPermissions->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="permission-list">
                                    @foreach($categoryPermissions as $permission)
                                    <div class="permission-item">
                                        <div class="form-check me-3">
                                            <input class="form-check-input permission-checkbox"
                                                   type="checkbox"
                                                   name="permissions[]"
                                                   value="{{ $permission->id }}"
                                                   id="permission_{{ $permission->id }}"
                                                   data-category="{{ $category }}"
                                                   {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}>
                                        </div>
                                        <div class="flex-grow-1">
                                            <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                <h6 class="mb-1">{{ $permission->display_name }}</h6>
                                                <small class="text-muted">{{ $permission->description ?? $permission->name }}</small>
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center py-4">
                                <i class="ti ti-key-off ti-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Permissions Available</h5>
                                <p class="text-muted mb-0">No permissions have been created yet.</p>
                            </div>
                        @endif
                        @error('permissions')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <!-- Help Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="ti ti-info-circle me-2"></i>Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6>Creating a New Role</h6>
                            <ul class="mb-0 ps-3">
                                <li>Role name must be unique and lowercase</li>
                                <li>Display name is shown to users</li>
                                <li>Select relevant permissions for this role</li>
                                <li>Inactive roles cannot be assigned to users</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-2"></i>Create Role
                            </button>
                            <a href="{{ route('administration.roles.index') }}" class="btn btn-outline-secondary">
                                <i class="ti ti-x me-2"></i>Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Auto-generate role name from display name
    $('#display_name').on('input', function() {
        const displayName = $(this).val();
        const roleName = displayName.toLowerCase()
            .replace(/[^a-z0-9\s]/g, '')
            .replace(/\s+/g, '-');
        $('#name').val(roleName);
    });

    // Select all permissions
    $('#selectAll').on('click', function() {
        $('.permission-checkbox').prop('checked', true);
    });

    // Deselect all permissions
    $('#deselectAll').on('click', function() {
        $('.permission-checkbox').prop('checked', false);
    });

    // Category select all
    $('.category-select-all').on('click', function() {
        const category = $(this).data('category');
        const isAllSelected = $(`.permission-checkbox[data-category="${category}"]:not(:checked)`).length === 0;

        if (isAllSelected) {
            $(`.permission-checkbox[data-category="${category}"]`).prop('checked', false);
            $(this).text('Select All');
        } else {
            $(`.permission-checkbox[data-category="${category}"]`).prop('checked', true);
            $(this).text('Deselect All');
        }
    });

    // Update category button text when individual permissions are changed
    $('.permission-checkbox').on('change', function() {
        const category = $(this).data('category');
        const categoryCheckboxes = $(`.permission-checkbox[data-category="${category}"]`);
        const checkedCount = categoryCheckboxes.filter(':checked').length;
        const totalCount = categoryCheckboxes.length;
        const button = $(`.category-select-all[data-category="${category}"]`);

        if (checkedCount === totalCount) {
            button.text('Deselect All');
        } else {
            button.text('Select All');
        }
    });

    // Initialize category button states
    $('.category-select-all').each(function() {
        const category = $(this).data('category');
        const categoryCheckboxes = $(`.permission-checkbox[data-category="${category}"]`);
        const checkedCount = categoryCheckboxes.filter(':checked').length;
        const totalCount = categoryCheckboxes.length;

        if (checkedCount === totalCount) {
            $(this).text('Deselect All');
        }
    });
});
</script>
@endsection