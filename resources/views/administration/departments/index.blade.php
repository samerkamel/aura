@extends('layouts/layoutMaster')

@section('title', 'Department Management')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
@endsection

@section('page-style')
<style>
.department-avatar {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: white;
    font-size: 0.75rem;
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
.stats-card.budget { border-left-color: #ffc107; }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">Department Management</h4>
        <a href="{{ route('administration.departments.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-2"></i>Create Department
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
                                <i class="ti ti-building ti-sm"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Total Departments</small>
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-1">{{ $statistics['total_departments'] }}</h6>
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
                                <i class="ti ti-building-store ti-sm"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Active Departments</small>
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-1">{{ $statistics['active_departments'] }}</h6>
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
                                <i class="ti ti-building-off ti-sm"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Inactive Departments</small>
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-1">{{ $statistics['inactive_departments'] }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card budget h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-coin ti-sm"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Total Budget</small>
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-1">{{ number_format($statistics['total_budget'], 0) }} EGP</h6>
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
            <form method="GET" action="{{ route('administration.departments.index') }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" value="{{ request('search') }}"
                               placeholder="Department name, code, or head...">
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
                            <a href="{{ route('administration.departments.index') }}" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Departments Table -->
    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="datatables-departments table border-top">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Head of Department</th>
                        <th>Contact</th>
                        <th>Budget Allocation</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $department)
                    <tr class="{{ !$department->is_active ? 'opacity-50' : '' }}">
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="department-avatar me-3" style="background-color: {{ '#' . substr(md5($department->code), 0, 6) }};">
                                    {{ $department->code }}
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $department->name }}</h6>
                                    <small class="text-muted">{{ $department->description ?? 'No description' }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($department->head_of_department)
                                <span class="fw-medium">{{ $department->head_of_department }}</span>
                            @else
                                <span class="text-muted">Not assigned</span>
                            @endif
                        </td>
                        <td>
                            <div>
                                @if($department->email)
                                    <small class="d-block">{{ $department->email }}</small>
                                @endif
                                @if($department->phone)
                                    <small class="d-block text-muted">{{ $department->phone }}</small>
                                @endif
                                @if(!$department->email && !$department->phone)
                                    <span class="text-muted">No contact info</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($department->budget_allocation)
                                <span class="fw-medium">{{ number_format($department->budget_allocation, 0) }} EGP</span>
                            @else
                                <span class="text-muted">Not set</span>
                            @endif
                        </td>
                        <td>
                            @if($department->is_active)
                                <span class="badge bg-label-success status-badge">Active</span>
                            @else
                                <span class="badge bg-label-danger status-badge">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <a href="{{ route('administration.departments.show', $department) }}"
                                   class="btn btn-sm btn-icon btn-outline-primary"
                                   data-bs-toggle="tooltip" title="View Details">
                                    <i class="ti ti-eye"></i>
                                </a>
                                <a href="{{ route('administration.departments.edit', $department) }}"
                                   class="btn btn-sm btn-icon btn-outline-info"
                                   data-bs-toggle="tooltip" title="Edit Department">
                                    <i class="ti ti-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('administration.departments.toggle-status', $department) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="btn btn-sm btn-icon {{ $department->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                            data-bs-toggle="tooltip"
                                            title="{{ $department->is_active ? 'Deactivate' : 'Activate' }} Department"
                                            onclick="return confirm('Are you sure you want to {{ $department->is_active ? 'deactivate' : 'activate' }} this department?')">
                                        <i class="ti ti-{{ $department->is_active ? 'toggle-right' : 'toggle-left' }}"></i>
                                    </button>
                                </form>
                                @if($department->contracts->count() === 0)
                                <form method="POST" action="{{ route('administration.departments.destroy', $department) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-sm btn-icon btn-outline-danger"
                                            data-bs-toggle="tooltip" title="Delete Department"
                                            onclick="return confirm('Are you sure you want to delete this department? This action cannot be undone.')">
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
                                <i class="ti ti-building-off ti-3x mb-3 d-block"></i>
                                <h5>No departments found</h5>
                                <p class="mb-0">No departments match your current filters.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($departments->hasPages())
        <div class="card-footer">
            {{ $departments->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection