@extends('layouts/layoutMaster')

@section('title', 'Roles Management')

@section('vendor-style')
@vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/select2/select2.scss'
])
@endsection

@section('page-style')
<style>
.role-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: white;
}
.status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
.stats-card {
    border-left: 4px solid;
    border-radius: 0.5rem;
}
.stats-card.total { border-left-color: #007bff; }
.stats-card.active { border-left-color: #28a745; }
.stats-card.inactive { border-left-color: #dc3545; }
.stats-card.permissions { border-left-color: #ffc107; }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">Roles Management</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('administration.permissions.index') }}" class="btn btn-outline-info">
                <i class="ti ti-key me-2"></i>View Permissions
            </a>
            <a href="{{ route('administration.roles.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-2"></i>Create Role
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stats-card total h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-shield ti-sm"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Total Roles</small>
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-1">{{ $statistics['total_roles'] }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card active h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-shield-check ti-sm"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Active Roles</small>
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-1">{{ $statistics['active_roles'] }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card inactive h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-shield-x ti-sm"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Inactive Roles</small>
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-1">{{ $statistics['inactive_roles'] }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card permissions h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-key ti-sm"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Total Permissions</small>
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-1">{{ $statistics['total_permissions'] }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('administration.roles.index') }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" value="{{ request('search') }}"
                               placeholder="Role name or description...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">Filter</button>
                            <a href="{{ route('administration.roles.index') }}" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Roles Table -->
    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="datatables-roles table border-top">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Description</th>
                        <th>Users</th>
                        <th>Permissions</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                    <tr class="{{ !$role->is_active ? 'opacity-50' : '' }}">
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="role-avatar me-3" style="background-color: {{ '#' . substr(md5($role->name), 0, 6) }};">
                                    {{ strtoupper(substr($role->display_name, 0, 2)) }}
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $role->display_name }}</h6>
                                    <small class="text-muted">{{ $role->name }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="text-muted">{{ $role->description ?? 'No description' }}</span>
                        </td>
                        <td>
                            <span class="badge bg-label-info">{{ $role->users->count() }} {{ $role->users->count() === 1 ? 'User' : 'Users' }}</span>
                        </td>
                        <td>
                            <span class="badge bg-label-primary">{{ $role->permissions->count() }} {{ $role->permissions->count() === 1 ? 'Permission' : 'Permissions' }}</span>
                        </td>
                        <td>
                            @if($role->is_active)
                                <span class="badge bg-label-success status-badge">Active</span>
                            @else
                                <span class="badge bg-label-danger status-badge">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <a href="{{ route('administration.roles.show', $role) }}"
                                   class="btn btn-sm btn-icon btn-outline-primary"
                                   data-bs-toggle="tooltip" title="View Details">
                                    <i class="ti ti-eye"></i>
                                </a>
                                <a href="{{ route('administration.roles.edit', $role) }}"
                                   class="btn btn-sm btn-icon btn-outline-info"
                                   data-bs-toggle="tooltip" title="Edit Role">
                                    <i class="ti ti-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('administration.roles.toggle-status', $role) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="btn btn-sm btn-icon {{ $role->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                            data-bs-toggle="tooltip"
                                            title="{{ $role->is_active ? 'Deactivate' : 'Activate' }} Role"
                                            onclick="return confirm('Are you sure you want to {{ $role->is_active ? 'deactivate' : 'activate' }} this role?')">
                                        <i class="ti ti-{{ $role->is_active ? 'toggle-right' : 'toggle-left' }}"></i>
                                    </button>
                                </form>
                                @if($role->users->count() === 0)
                                <form method="POST" action="{{ route('administration.roles.destroy', $role) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-sm btn-icon btn-outline-danger"
                                            data-bs-toggle="tooltip" title="Delete Role"
                                            onclick="return confirm('Are you sure you want to delete this role? This action cannot be undone.')">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="text-muted">
                                <i class="ti ti-shield-off ti-3x mb-3 d-block"></i>
                                <h5>No roles found</h5>
                                <p class="mb-0">No roles match your current filters.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($roles->hasPages())
        <div class="card-footer">
            {{ $roles->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@section('vendor-script')
@vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/select2/select2.js'
])
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    const select2Elements = document.querySelectorAll('.select2');
    if (select2Elements.length && typeof jQuery !== 'undefined') {
        jQuery('.select2').select2({
            placeholder: 'Select status...',
            allowClear: true
        });
    }

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection