@extends('layouts/layoutMaster')

@section('title', 'Employees')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Employee Management</h5>
    <a href="{{ route('hr.employees.create') }}" class="btn btn-primary">
      <i class="ti ti-plus me-1"></i>Create Employee
    </a>
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
          <th>Base Salary</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        @forelse($employees as $employee)
        <tr>
          <td>
            <i class="ti ti-user me-2"></i>
            <strong>{{ $employee->name }}</strong>
          </td>
          <td>{{ $employee->email }}</td>
          <td>{{ $employee->position ?? 'Not Specified' }}</td>
          <td>{{ $employee->start_date ? $employee->start_date->format('M d, Y') : 'Not Set' }}</td>
          <td>
            @if($employee->base_salary)
              <span class="text-success fw-bold">${{ number_format($employee->base_salary, 2) }}</span>
            @else
              <span class="text-muted">Not Set</span>
            @endif
          </td>
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
          <td colspan="7" class="text-center py-4">
            <div class="d-flex flex-column align-items-center">
              <i class="ti ti-user-plus text-muted" style="font-size: 3rem;"></i>
              <h6 class="mt-2">No employees found</h6>
              <p class="text-muted">Start by creating your first employee</p>
              <a href="{{ route('hr.employees.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>Create Employee
              </a>
            </div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
