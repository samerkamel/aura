@extends('layouts/layoutMaster')

@section('title', 'Edit Role')

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
            <h4 class="fw-bold py-3 mb-0">Edit Role</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('administration.roles.index') }}">Roles Management</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('administration.roles.show', $role) }}">{{ $role->display_name }}</a>
                    </li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('administration.roles.show', $role) }}" class="btn btn-outline-info">
                <i class="ti tabler-eye me-2"></i>View Details
            </a>
            <a href="{{ route('administration.roles.index') }}" class="btn btn-outline-secondary">
                <i class="ti tabler-arrow-left me-2"></i>Back to Roles
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('administration.roles.update', $role) }}">
        @csrf
        @method('PUT')
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
                                       id="name" name="name" value="{{ old('name', $role->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Internal name (lowercase, no spaces)</small>
                            </div>

                            <!-- Display Name -->
                            <div class="col-md-6">
                                <label class="form-label" for="display_name">Display Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('display_name') is-invalid @enderror"
                                       id="display_name" name="display_name" value="{{ old('display_name', $role->display_name) }}" required>
                                @error('display_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Human-readable name</small>
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <label class="form-label" for="description">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                          id="description" name="description" rows="3">{{ old('description', $role->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Brief description of the role's purpose</small>
                            </div>

                            <!-- Status -->
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active"
                                           name="is_active" {{ old('is_active', $role->is_active) ? 'checked' : '' }}>
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
                                            <i class="ti tabler-folder me-2"></i>{{ ucfirst(str_replace('-', ' ', $category)) }}
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
                                                   {{ in_array($permission->id, old('permissions', $role->permissions->pluck('id')->toArray())) ? 'checked' : '' }}>
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
                                <i class="ti tabler-key-off ti-3x text-muted mb-3"></i>
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
                <!-- Role Preview -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="ti tabler-shield me-2"></i>Role Preview
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mx-auto mb-3" style="width: 80px; height: 80px; border-radius: 50%; background-color: {{ '#' . substr(md5($role->name), 0, 6) }}; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; font-weight: 600;">
                            {{ strtoupper(substr($role->display_name, 0, 2)) }}
                        </div>
                        <h5>{{ $role->display_name }}</h5>
                        <p class="text-muted mb-0">{{ $role->name }}</p>
                        <span class="badge {{ $role->is_active ? 'bg-label-success' : 'bg-label-danger' }} mt-2">
                            {{ $role->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>

                <!-- Current Users -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="ti tabler-users me-2"></i>Assigned Users
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($role->users->count() > 0)
                            @foreach($role->users->take(5) as $user)
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar avatar-xs flex-shrink-0 me-3" style="background-color: {{ '#' . substr(md5($user->name), 0, 6) }}; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.75rem;">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">{{ $user->name }}</h6>
                                </div>
                            </div>
                            @endforeach
                            @if($role->users->count() > 5)
                                <small class="text-muted">and {{ $role->users->count() - 5 }} more users...</small>
                            @endif
                        @else
                            <p class="text-muted mb-0">No users assigned</p>
                        @endif
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti tabler-device-floppy me-2"></i>Update Role
                            </button>
                            <a href="{{ route('administration.roles.show', $role) }}" class="btn btn-outline-secondary">
                                <i class="ti tabler-x me-2"></i>Cancel
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
@vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all permissions
    const selectAllBtn = document.getElementById('selectAll');
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = true);
            updateCategoryButtons();
        });
    }

    // Deselect all permissions
    const deselectAllBtn = document.getElementById('deselectAll');
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function() {
            document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = false);
            updateCategoryButtons();
        });
    }

    // Category select all
    document.querySelectorAll('.category-select-all').forEach(function(button) {
        button.addEventListener('click', function() {
            const category = this.dataset.category;
            const categoryCheckboxes = document.querySelectorAll(`.permission-checkbox[data-category="${category}"]`);
            const uncheckedCount = Array.from(categoryCheckboxes).filter(cb => !cb.checked).length;
            const isAllSelected = uncheckedCount === 0;

            if (isAllSelected) {
                categoryCheckboxes.forEach(cb => cb.checked = false);
                this.textContent = 'Select All';
            } else {
                categoryCheckboxes.forEach(cb => cb.checked = true);
                this.textContent = 'Deselect All';
            }
        });
    });

    // Update category button text when individual permissions are changed
    document.querySelectorAll('.permission-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            updateCategoryButtons();
        });
    });

    // Function to update all category button states
    function updateCategoryButtons() {
        document.querySelectorAll('.category-select-all').forEach(function(button) {
            const category = button.dataset.category;
            const categoryCheckboxes = document.querySelectorAll(`.permission-checkbox[data-category="${category}"]`);
            const checkedCount = Array.from(categoryCheckboxes).filter(cb => cb.checked).length;
            const totalCount = categoryCheckboxes.length;

            if (checkedCount === totalCount) {
                button.textContent = 'Deselect All';
            } else {
                button.textContent = 'Select All';
            }
        });
    }

    // Initialize category button states
    updateCategoryButtons();

    // Update preview when display name changes
    const displayNameInput = document.getElementById('display_name');
    if (displayNameInput) {
        displayNameInput.addEventListener('input', function() {
            const displayName = this.value || '{{ $role->display_name }}';
            const initials = displayName.substring(0, 2).toUpperCase();
            const previewTitle = document.querySelector('.card-body h5');
            const previewInitials = document.querySelector('.mx-auto');
            if (previewTitle) previewTitle.textContent = displayName;
            if (previewInitials) previewInitials.textContent = initials;
        });
    }
});
</script>
@endsection