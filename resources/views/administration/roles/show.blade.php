@extends('layouts/layoutMaster')

@section('title', 'Role Details')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold py-3 mb-0">Role Details</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('administration.roles.index') }}">Roles Management</a>
                    </li>
                    <li class="breadcrumb-item active">{{ $role->display_name }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('administration.roles.edit', $role) }}" class="btn btn-primary">
                <i class="ti tabler-edit me-2"></i>Edit Role
            </a>
            <a href="{{ route('administration.roles.index') }}" class="btn btn-outline-secondary">
                <i class="ti tabler-arrow-left me-2"></i>Back to Roles
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Role Information -->
        <div class="col-xl-4 col-lg-5 col-md-5">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="mx-auto mb-3" style="width: 100px; height: 100px; border-radius: 50%; background-color: {{ '#' . substr(md5($role->name), 0, 6) }}; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; font-weight: 600;">
                            {{ strtoupper(substr($role->display_name, 0, 2)) }}
                        </div>
                        <h4 class="mb-1">{{ $role->display_name }}</h4>
                        <span class="badge {{ $role->is_active ? 'bg-label-success' : 'bg-label-danger' }}">
                            {{ $role->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="info-container">
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <span class="fw-medium me-2">Internal Name:</span>
                                <span>{{ $role->name }}</span>
                            </li>
                            <li class="mb-3">
                                <span class="fw-medium me-2">Description:</span>
                                <span>{{ $role->description ?? 'No description provided' }}</span>
                            </li>
                            <li class="mb-3">
                                <span class="fw-medium me-2">Status:</span>
                                <span class="badge {{ $role->is_active ? 'bg-label-success' : 'bg-label-danger' }}">
                                    {{ $role->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </li>
                            <li class="mb-3">
                                <span class="fw-medium me-2">Role ID:</span>
                                <span>#{{ $role->id }}</span>
                            </li>
                            <li class="mb-3">
                                <span class="fw-medium me-2">Created:</span>
                                <span>{{ $role->created_at->format('M d, Y g:i A') }}</span>
                            </li>
                            <li class="mb-3">
                                <span class="fw-medium me-2">Last Updated:</span>
                                <span>{{ $role->updated_at->format('M d, Y g:i A') }}</span>
                            </li>
                        </ul>

                        <!-- Quick Actions -->
                        <div class="d-grid gap-2 mt-4">
                            <form method="POST" action="{{ route('administration.roles.toggle-status', $role) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="btn {{ $role->is_active ? 'btn-warning' : 'btn-success' }} w-100"
                                        onclick="return confirm('Are you sure you want to {{ $role->is_active ? 'deactivate' : 'activate' }} this role?')">
                                    <i class="ti tabler-{{ $role->is_active ? 'toggle-right' : 'toggle-left' }} me-2"></i>
                                    {{ $role->is_active ? 'Deactivate Role' : 'Activate Role' }}
                                </button>
                            </form>
                            @if($role->users->count() === 0)
                            <form method="POST" action="{{ route('administration.roles.destroy', $role) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="btn btn-danger w-100"
                                        onclick="return confirm('Are you sure you want to delete this role? This action cannot be undone.')">
                                    <i class="ti tabler-trash me-2"></i>Delete Role
                                </button>
                            </form>
                            @else
                            <div class="alert alert-info">
                                <i class="ti tabler-info-circle me-2"></i>
                                Cannot delete role with assigned users.
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users and Permissions -->
        <div class="col-xl-8 col-lg-7 col-md-7">
            <!-- Assigned Users -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti tabler-users me-2"></i>Assigned Users
                    </h5>
                    <span class="badge bg-label-primary">{{ $role->users->count() }} {{ $role->users->count() === 1 ? 'User' : 'Users' }}</span>
                </div>
                <div class="card-body">
                    @if($role->users->count() > 0)
                        <div class="row">
                            @foreach($role->users as $user)
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-center p-3 border rounded">
                                    <div class="avatar flex-shrink-0 me-3" style="background-color: {{ '#' . substr(md5($user->name), 0, 6) }}; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">{{ $user->name }}</h6>
                                        <small class="text-muted">{{ $user->email }}</small>
                                        <div class="mt-1">
                                            <span class="badge {{ $user->is_active ? 'bg-label-success' : 'bg-label-danger' }}">
                                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>
                                    <a href="{{ route('administration.users.show', $user) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="ti tabler-eye"></i>
                                    </a>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="ti tabler-users-off ti-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Users Assigned</h5>
                            <p class="text-muted mb-0">This role has not been assigned to any users yet.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Role Permissions -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti tabler-key me-2"></i>Role Permissions
                    </h5>
                    <span class="badge bg-label-info">{{ $role->permissions->count() }} {{ $role->permissions->count() === 1 ? 'Permission' : 'Permissions' }}</span>
                </div>
                <div class="card-body">
                    @if($role->permissions->count() > 0)
                        @foreach($permissionsByCategory as $category => $permissions)
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">
                                <i class="ti tabler-folder me-2"></i>{{ ucfirst(str_replace('-', ' ', $category)) }}
                                <span class="badge bg-label-primary ms-2">{{ $permissions->count() }}</span>
                            </h6>
                            <div class="row">
                                @foreach($permissions as $permission)
                                <div class="col-lg-6 col-md-12 mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="ti tabler-check text-success me-2"></i>
                                        <div>
                                            <span class="fw-medium">{{ $permission->display_name }}</span>
                                            @if($permission->description)
                                            <br><small class="text-muted">{{ $permission->description }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="ti tabler-key-off ti-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Permissions</h5>
                            <p class="text-muted mb-0">This role has no permissions assigned.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection