@extends('layouts/layoutMaster')

@section('title', 'User Management')

@section('vendor-style')
@vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/select2/select2.scss'
])
@endsection

@section('page-style')
<style>
.user-avatar {
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
.stats-card.roles { border-left-color: #ffc107; }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">User Management</h4>
        <a href="{{ route('administration.users.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-2"></i>Create User
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stats-card total h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-users ti-sm"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Total Users</small>
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-1">{{ $statistics['total_users'] }}</h6>
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
                                <i class="ti ti-user-check ti-sm"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Active Users</small>
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-1">{{ $statistics['active_users'] }}</h6>
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
                                <i class="ti ti-user-x ti-sm"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Inactive Users</small>
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-1">{{ $statistics['inactive_users'] }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card roles h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-shield-check ti-sm"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Total Roles</small>
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-1">{{ $statistics['roles_count'] }}</h6>
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
            <form method="GET" action="{{ route('administration.users.index') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" value="{{ request('search') }}"
                               placeholder="Name or email...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Role</label>
                        <select class="form-select select2" name="role_id">
                            <option value="">All Roles</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                                    {{ $role->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">Filter</button>
                            <a href="{{ route('administration.users.index') }}" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="datatables-users table border-top">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Roles</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr class="{{ !$user->is_active ? 'opacity-50' : '' }}">
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-3" style="background-color: {{ '#' . substr(md5($user->name), 0, 6) }};">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $user->name }}</h6>
                                    <small class="text-muted">ID: {{ $user->id }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="fw-medium">{{ $user->email }}</span>
                        </td>
                        <td>
                            @if($user->roles->count() > 0)
                                @foreach($user->roles as $role)
                                    <span class="badge bg-label-primary me-1">{{ $role->display_name }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">No roles</span>
                            @endif
                        </td>
                        <td>
                            @if($user->is_active)
                                <span class="badge bg-label-success status-badge">Active</span>
                            @else
                                <span class="badge bg-label-danger status-badge">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <span class="text-muted">{{ $user->created_at->format('M d, Y') }}</span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <a href="{{ route('administration.users.show', $user) }}"
                                   class="btn btn-sm btn-icon btn-outline-primary"
                                   data-bs-toggle="tooltip" title="View Details">
                                    <i class="ti ti-eye"></i>
                                </a>
                                <a href="{{ route('administration.users.edit', $user) }}"
                                   class="btn btn-sm btn-icon btn-outline-info"
                                   data-bs-toggle="tooltip" title="Edit User">
                                    <i class="ti ti-edit"></i>
                                </a>
                                @if($user->id !== auth()->id())
                                <form method="POST" action="{{ route('administration.users.toggle-status', $user) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="btn btn-sm btn-icon {{ $user->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                            data-bs-toggle="tooltip"
                                            title="{{ $user->is_active ? 'Deactivate' : 'Activate' }} User"
                                            onclick="return confirm('Are you sure you want to {{ $user->is_active ? 'deactivate' : 'activate' }} this user?')">
                                        <i class="ti ti-{{ $user->is_active ? 'user-x' : 'user-check' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('administration.users.destroy', $user) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-sm btn-icon btn-outline-danger"
                                            data-bs-toggle="tooltip" title="Delete User"
                                            onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
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
                                <i class="ti ti-users ti-3x mb-3 d-block"></i>
                                <h5>No users found</h5>
                                <p class="mb-0">No users match your current filters.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
        <div class="card-footer">
            {{ $users->appends(request()->query())->links() }}
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
            placeholder: 'Select role...',
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