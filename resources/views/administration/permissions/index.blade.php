@extends('layouts/layoutMaster')

@section('title', 'Permissions Management')


@section('page-style')
<style>
.permission-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
.stats-card {
    border-left: 4px solid;
    border-radius: 0.5rem;
}
.stats-card.total { border-left-color: #007bff; }
.stats-card.categories { border-left-color: #28a745; }
.stats-card.assigned { border-left-color: #ffc107; }
.stats-card.unassigned { border-left-color: #dc3545; }
.category-header {
    background-color: #f8f9fa;
    border-left: 4px solid #007bff;
    margin: 1rem 0 0.5rem 0;
    padding: 0.75rem 1rem;
    border-radius: 0.25rem;
}
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">Permissions Management</h4>
        <a href="{{ route('administration.roles.index') }}" class="btn btn-outline-primary">
            <i class="ti ti-shield-check me-2"></i>Manage Roles
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
        <div class="col-md-3 mb-3">
            <div class="card stats-card categories h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-folder ti-sm"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Categories</small>
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-1">{{ $statistics['categories_count'] }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card assigned h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-link ti-sm"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Assigned</small>
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-1">{{ $statistics['assigned_permissions'] }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card unassigned h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-unlink ti-sm"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Unassigned</small>
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-1">{{ $statistics['unassigned_permissions'] }}</h6>
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
            <form method="GET" action="{{ route('administration.permissions.index') }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" value="{{ request('search') }}"
                               placeholder="Permission name or description...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('-', ' ', $category)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">Filter</button>
                            <a href="{{ route('administration.permissions.index') }}" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Permissions Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">All Permissions</h5>
        </div>
        <div class="card-body">
            @if($permissions->count() > 0)
                @php
                    $currentCategory = null;
                    $groupedPermissions = $permissions->groupBy('category');
                @endphp

                @foreach($groupedPermissions as $category => $categoryPermissions)
                    <div class="category-header">
                        <h6 class="mb-0">
                            <i class="ti ti-folder me-2"></i>{{ ucfirst(str_replace('-', ' ', $category)) }}
                            <span class="badge bg-label-primary ms-2">{{ $categoryPermissions->count() }}</span>
                        </h6>
                    </div>

                    <div class="table-responsive mb-4">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Permission</th>
                                    <th>Description</th>
                                    <th>Assigned Roles</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categoryPermissions as $permission)
                                <tr>
                                    <td>
                                        <div>
                                            <h6 class="mb-0">{{ $permission->display_name }}</h6>
                                            <small class="text-muted">{{ $permission->name }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $permission->description ?? 'No description' }}</span>
                                    </td>
                                    <td>
                                        @if($permission->roles->count() > 0)
                                            @foreach($permission->roles as $role)
                                                <span class="badge bg-label-primary me-1">{{ $role->display_name }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($permission->roles->count() > 0)
                                            <span class="badge bg-label-success permission-badge">Assigned</span>
                                        @else
                                            <span class="badge bg-label-danger permission-badge">Unassigned</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            @else
                <div class="text-center py-4">
                    <div class="text-muted">
                        <i class="ti ti-key-off ti-3x mb-3 d-block"></i>
                        <h5>No permissions found</h5>
                        <p class="mb-0">No permissions match your current filters.</p>
                    </div>
                </div>
            @endif
        </div>

        @if($permissions->hasPages())
        <div class="card-footer">
            {{ $permissions->appends(request()->query())->links() }}
        </div>
        @endif
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

    console.log('Permissions page loaded successfully');
});
</script>
@endsection