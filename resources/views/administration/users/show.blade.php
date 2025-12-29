@extends('layouts/layoutMaster')

@section('title', 'User Details')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold py-3 mb-0">User Details</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('administration.users.index') }}">User Management</a>
                    </li>
                    <li class="breadcrumb-item active">{{ $user->name }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('administration.users.edit', $user) }}" class="btn btn-primary">
                <i class="ti tabler-edit me-2"></i>Edit User
            </a>
            <a href="{{ route('administration.users.index') }}" class="btn btn-outline-secondary">
                <i class="ti tabler-arrow-left me-2"></i>Back to Users
            </a>
        </div>
    </div>

    <div class="row">
        <!-- User Information -->
        <div class="col-xl-4 col-lg-5 col-md-5">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="mx-auto mb-3" style="width: 100px; height: 100px; border-radius: 50%; background-color: {{ '#' . substr(md5($user->name), 0, 6) }}; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; font-weight: 600;">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <h4 class="mb-1">{{ $user->name }}</h4>
                        <span class="badge {{ $user->is_active ? 'bg-label-success' : 'bg-label-danger' }}">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="info-container">
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <span class="fw-medium me-2">Email:</span>
                                <span>{{ $user->email }}</span>
                            </li>
                            <li class="mb-3">
                                <span class="fw-medium me-2">Status:</span>
                                <span class="badge {{ $user->is_active ? 'bg-label-success' : 'bg-label-danger' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </li>
                            <li class="mb-3">
                                <span class="fw-medium me-2">User ID:</span>
                                <span>#{{ $user->id }}</span>
                            </li>
                            <li class="mb-3">
                                <span class="fw-medium me-2">Created:</span>
                                <span>{{ $user->created_at->format('M d, Y g:i A') }}</span>
                            </li>
                            <li class="mb-3">
                                <span class="fw-medium me-2">Last Updated:</span>
                                <span>{{ $user->updated_at->format('M d, Y g:i A') }}</span>
                            </li>
                        </ul>

                        <!-- Quick Actions -->
                        <div class="d-grid gap-2 mt-4">
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('administration.users.toggle-status', $user) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="btn {{ $user->is_active ? 'btn-warning' : 'btn-success' }} w-100"
                                        onclick="return confirm('Are you sure you want to {{ $user->is_active ? 'deactivate' : 'activate' }} this user?')">
                                    <i class="ti tabler-{{ $user->is_active ? 'user-x' : 'user-check' }} me-2"></i>
                                    {{ $user->is_active ? 'Deactivate User' : 'Activate User' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('administration.users.destroy', $user) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="btn btn-danger w-100"
                                        onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                    <i class="ti tabler-trash me-2"></i>Delete User
                                </button>
                            </form>
                            @else
                            <div class="alert alert-info">
                                <i class="ti tabler-info-circle me-2"></i>
                                You cannot modify your own account from this page.
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Roles and Permissions -->
        <div class="col-xl-8 col-lg-7 col-md-7">
            <!-- User Roles -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti tabler-shield-check me-2"></i>User Roles
                    </h5>
                    <span class="badge bg-label-primary">{{ $user->roles->count() }} {{ $user->roles->count() === 1 ? 'Role' : 'Roles' }}</span>
                </div>
                <div class="card-body">
                    @if($user->roles->count() > 0)
                        <div class="row">
                            @foreach($user->roles as $role)
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-center p-3 border rounded">
                                    <div class="avatar flex-shrink-0 me-3">
                                        <span class="avatar-initial rounded bg-label-primary">
                                            <i class="ti tabler-shield"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">{{ $role->display_name }}</h6>
                                        <small class="text-muted">{{ $role->description }}</small>
                                        <div class="mt-1">
                                            <small class="text-primary">{{ $role->permissions->count() }} permissions</small>
                                        </div>
                                    </div>
                                    <span class="badge {{ $role->is_active ? 'bg-label-success' : 'bg-label-danger' }}">
                                        {{ $role->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="ti tabler-shield-x ti-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Roles Assigned</h5>
                            <p class="text-muted mb-0">This user has no roles assigned. Click edit to assign roles.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- User Permissions -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti tabler-key me-2"></i>User Permissions
                    </h5>
                    @php
                        $allPermissions = $user->roles->flatMap->permissions->unique('id');
                    @endphp
                    <span class="badge bg-label-info">{{ $allPermissions->count() }} {{ $allPermissions->count() === 1 ? 'Permission' : 'Permissions' }}</span>
                </div>
                <div class="card-body">
                    @if($allPermissions->count() > 0)
                        @php
                            $groupedPermissions = $allPermissions->groupBy('category');
                        @endphp
                        @foreach($groupedPermissions as $category => $permissions)
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">
                                <i class="ti tabler-folder me-2"></i>{{ ucfirst(str_replace('-', ' ', $category)) }}
                            </h6>
                            <div class="row">
                                @foreach($permissions as $permission)
                                <div class="col-lg-4 col-md-6 mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="ti tabler-check text-success me-2"></i>
                                        <span>{{ $permission->display_name }}</span>
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
                            <p class="text-muted mb-0">This user has no permissions assigned through roles.</p>
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