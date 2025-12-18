@extends('layouts/layoutMaster')

@section('title', 'Employees')

@section('content')
<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Employee Management</h5>
      <div class="d-flex gap-2">
        <a href="{{ route('hr.employees.import.show') }}" class="btn btn-outline-info">
          <i class="ti ti-upload me-1"></i>Import Employees
        </a>
        <a href="{{ route('hr.employees.create') }}" class="btn btn-primary">
          <i class="ti ti-plus me-1"></i>Create Employee
        </a>
      </div>
    </div>
    <!-- Status Filter Buttons -->
    <div class="btn-group" role="group" aria-label="Employee status filter">
      <a href="{{ route('hr.employees.index', ['status' => 'active']) }}"
         class="btn {{ $status === 'active' ? 'btn-primary' : 'btn-outline-primary' }}">
        <i class="ti ti-user-check me-1"></i>Active
        <span class="badge bg-white text-primary ms-1">{{ $activeCount }}</span>
      </a>
      <a href="{{ route('hr.employees.index', ['status' => 'inactive']) }}"
         class="btn {{ $status === 'inactive' ? 'btn-secondary' : 'btn-outline-secondary' }}">
        <i class="ti ti-user-off me-1"></i>Inactive
        <span class="badge {{ $status === 'inactive' ? 'bg-white text-secondary' : 'bg-secondary' }} ms-1">{{ $inactiveCount }}</span>
      </a>
      <a href="{{ route('hr.employees.index', ['status' => 'all']) }}"
         class="btn {{ $status === 'all' ? 'btn-info' : 'btn-outline-info' }}">
        <i class="ti ti-users me-1"></i>All
        <span class="badge {{ $status === 'all' ? 'bg-white text-info' : 'bg-info' }} ms-1">{{ $activeCount + $inactiveCount }}</span>
      </a>
    </div>
  </div>

  @if(session('success'))
  <div class="alert alert-success alert-dismissible" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  @endif

  <div class="table-responsive text-nowrap">
    <table class="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Position</th>
          <th>Start Date</th>
          @if($status !== 'active')
          <th>End Date</th>
          @endif
          @if($canViewSalary)
          <th>Base Salary</th>
          @endif
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        @forelse($employees as $employee)
        <tr>
          <td>
            <a href="{{ route('hr.employees.show', $employee) }}" class="text-body">
              <i class="ti ti-user me-2"></i>
              <strong>{{ $employee->name }}</strong>
            </a>
          </td>
          <td>{{ $employee->email }}</td>
          <td>
            @if($employee->position_id && $employee->positionRelation)
              <span class="badge bg-label-primary">{{ $employee->positionRelation->full_title }}</span>
            @elseif($employee->position && is_string($employee->position))
              <span class="badge bg-label-secondary">{{ $employee->position }}</span>
            @else
              <span class="text-muted">Not Assigned</span>
            @endif
          </td>
          <td>{{ $employee->start_date ? $employee->start_date->format('M d, Y') : 'Not Set' }}</td>
          @if($status !== 'active')
          <td>
            @if($employee->termination_date)
              <span class="text-danger">{{ $employee->termination_date->format('M d, Y') }}</span>
            @else
              <span class="text-muted">Not Set</span>
            @endif
          </td>
          @endif
          @if($canViewSalary)
          <td>
            @if($employee->base_salary)
              <span class="text-success fw-bold">EGP {{ number_format($employee->base_salary, 2) }}</span>
            @else
              <span class="text-muted">Not Set</span>
            @endif
          </td>
          @endif
          <td>
            @if($employee->status === 'active')
              <span class="badge bg-success">Active</span>
            @elseif($employee->status === 'terminated')
              <span class="badge bg-danger">Terminated</span>
            @else
              <span class="badge bg-warning">Resigned</span>
            @endif
          </td>
          <td>
            <div class="dropdown">
              <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                <i class="ti ti-dots-vertical"></i>
              </button>
              <div class="dropdown-menu">
                <a class="dropdown-item" href="{{ route('hr.employees.show', $employee) }}">
                  <i class="ti ti-eye me-2"></i>View
                </a>
                <a class="dropdown-item" href="{{ route('hr.employees.edit', $employee) }}">
                  <i class="ti ti-edit me-2"></i>Edit
                </a>
                <div class="dropdown-divider"></div>
                <form action="{{ route('hr.employees.destroy', $employee) }}" method="POST" class="d-inline">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="dropdown-item text-danger"
                          onclick="return confirm('Are you sure you want to delete this employee?')">
                    <i class="ti ti-trash me-2"></i>Delete
                  </button>
                </form>
              </div>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          @php
            $colCount = 6; // Base columns: Name, Email, Position, Start Date, Status, Actions
            if ($status !== 'active') $colCount++; // End Date column
            if ($canViewSalary) $colCount++; // Salary column
          @endphp
          <td colspan="{{ $colCount }}" class="text-center py-4">
            <div class="d-flex flex-column align-items-center">
              @if($status === 'active')
                <i class="ti ti-user-plus text-muted" style="font-size: 3rem;"></i>
                <h6 class="mt-2">No active employees found</h6>
                <p class="text-muted">Start by creating your first employee</p>
                <a href="{{ route('hr.employees.create') }}" class="btn btn-primary">
                  <i class="ti ti-plus me-1"></i>Create Employee
                </a>
              @elseif($status === 'inactive')
                <i class="ti ti-user-off text-muted" style="font-size: 3rem;"></i>
                <h6 class="mt-2">No inactive employees</h6>
                <p class="text-muted">All employees are currently active</p>
              @else
                <i class="ti ti-users text-muted" style="font-size: 3rem;"></i>
                <h6 class="mt-2">No employees found</h6>
                <p class="text-muted">Start by creating your first employee</p>
                <a href="{{ route('hr.employees.create') }}" class="btn btn-primary">
                  <i class="ti ti-plus me-1"></i>Create Employee
                </a>
              @endif
            </div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
