@extends('layouts/layoutMaster')

@section('title', $position->title)

@section('content')
<div class="row">
  <!-- Position Details -->
  <div class="col-xl-4 col-lg-5">
    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-lg me-3">
              <span class="avatar-initial rounded-circle bg-label-primary">
                <i class="ti tabler-briefcase ti-md"></i>
              </span>
            </div>
            <div>
              <h4 class="mb-0">{{ $position->title }}</h4>
              @if($position->title_ar)
                <span class="text-muted" dir="rtl">{{ $position->title_ar }}</span>
              @endif
            </div>
          </div>
          @if($position->is_active)
            <span class="badge bg-success">Active</span>
          @else
            <span class="badge bg-secondary">Inactive</span>
          @endif
        </div>

        <div class="info-container">
          <ul class="list-unstyled mb-4">
            @if($position->department)
            <li class="mb-2">
              <span class="fw-medium me-1 text-heading">Department:</span>
              <span>{{ $position->department }}</span>
            </li>
            @endif

            @if($position->level)
            <li class="mb-2">
              <span class="fw-medium me-1 text-heading">Level:</span>
              <span class="badge bg-label-primary">{{ \Modules\HR\Models\Position::LEVELS[$position->level] ?? $position->level }}</span>
            </li>
            @endif

            @if($canViewSalary && ($position->min_salary || $position->max_salary))
            <li class="mb-2">
              <span class="fw-medium me-1 text-heading">Salary Range:</span>
              <span class="text-success">{{ $position->salary_range }}</span>
            </li>
            @endif

            <li class="mb-2">
              <span class="fw-medium me-1 text-heading">Employees:</span>
              <span class="badge bg-label-info">{{ $position->employees->count() }} assigned</span>
            </li>

            <li class="mb-2">
              <span class="fw-medium me-1 text-heading">Created:</span>
              <span>{{ $position->created_at->format('M d, Y') }}</span>
            </li>

            <li class="mb-2">
              <span class="fw-medium me-1 text-heading">Last Updated:</span>
              <span>{{ $position->updated_at->format('M d, Y') }}</span>
            </li>
          </ul>

          <div class="d-flex justify-content-center gap-2">
            <a href="{{ route('hr.positions.edit', $position) }}" class="btn btn-primary">
              <i class="ti tabler-edit me-1"></i>Edit
            </a>
            <form action="{{ route('hr.positions.toggle-status', $position) }}" method="POST" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-outline-{{ $position->is_active ? 'warning' : 'success' }}">
                @if($position->is_active)
                  <i class="ti tabler-ban me-1"></i>Deactivate
                @else
                  <i class="ti tabler-check me-1"></i>Activate
                @endif
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Position Details & Employees -->
  <div class="col-xl-8 col-lg-7">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Description & Requirements -->
    @if($position->description || $position->requirements)
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0">Position Details</h5>
      </div>
      <div class="card-body">
        @if($position->description)
        <h6 class="mb-2"><i class="ti tabler-file-description me-2"></i>Job Description</h6>
        <p class="mb-4">{{ $position->description }}</p>
        @endif

        @if($position->requirements)
        <h6 class="mb-2"><i class="ti tabler-checklist me-2"></i>Requirements</h6>
        <p class="mb-0">{{ $position->requirements }}</p>
        @endif
      </div>
    </div>
    @endif

    <!-- Assigned Employees -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Assigned Employees</h5>
        <span class="badge bg-primary">{{ $position->employees->count() }} employees</span>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Status</th>
              <th>Start Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($position->employees as $employee)
            <tr>
              <td>
                <a href="{{ route('hr.employees.show', $employee) }}" class="text-body">
                  <i class="ti tabler-user me-2"></i>
                  <strong>{{ $employee->name }}</strong>
                </a>
              </td>
              <td>{{ $employee->email }}</td>
              <td>
                @if($employee->status === 'active')
                  <span class="badge bg-success">Active</span>
                @elseif($employee->status === 'terminated')
                  <span class="badge bg-danger">Terminated</span>
                @else
                  <span class="badge bg-warning">Resigned</span>
                @endif
              </td>
              <td>{{ $employee->start_date ? $employee->start_date->format('M d, Y') : '-' }}</td>
              <td>
                <a href="{{ route('hr.employees.show', $employee) }}" class="btn btn-sm btn-icon btn-text-primary">
                  <i class="ti tabler-eye"></i>
                </a>
                <a href="{{ route('hr.employees.edit', $employee) }}" class="btn btn-sm btn-icon btn-text-primary">
                  <i class="ti tabler-edit"></i>
                </a>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="5" class="text-center py-4">
                <div class="d-flex flex-column align-items-center">
                  <i class="ti tabler-users text-muted" style="font-size: 2rem;"></i>
                  <p class="mt-2 mb-0 text-muted">No employees assigned to this position</p>
                </div>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
