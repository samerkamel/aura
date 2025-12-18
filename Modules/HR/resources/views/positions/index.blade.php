@extends('layouts/layoutMaster')

@section('title', 'Positions')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Position Management</h5>
    <a href="{{ route('hr.positions.create') }}" class="btn btn-primary">
      <i class="ti ti-plus me-1"></i>Create Position
    </a>
  </div>

  @if(session('success'))
  <div class="alert alert-success alert-dismissible mx-3" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  @endif

  @if(session('error'))
  <div class="alert alert-danger alert-dismissible mx-3" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  @endif

  <!-- Filters -->
  <div class="card-body pb-0">
    <form method="GET" class="row g-3">
      <div class="col-md-3">
        <label class="form-label">Search</label>
        <input type="text" name="search" class="form-control" placeholder="Search positions..."
               value="{{ request('search') }}">
      </div>
      <div class="col-md-2">
        <label class="form-label">Department</label>
        <select name="department" class="form-select">
          <option value="">All Departments</option>
          @foreach($departments as $dept)
            <option value="{{ $dept }}" {{ request('department') == $dept ? 'selected' : '' }}>
              {{ $dept }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Level</label>
        <select name="level" class="form-select">
          <option value="">All Levels</option>
          @foreach(\Modules\HR\Models\Position::LEVELS as $key => $label)
            <option value="{{ $key }}" {{ request('level') == $key ? 'selected' : '' }}>
              {{ $label }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="">All Status</option>
          <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
          <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
      </div>
      <div class="col-md-3 d-flex align-items-end gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="ti ti-search me-1"></i>Filter
        </button>
        <a href="{{ route('hr.positions.index') }}" class="btn btn-outline-secondary">
          <i class="ti ti-refresh me-1"></i>Reset
        </a>
      </div>
    </form>
  </div>

  <div class="table-responsive text-nowrap mt-3">
    <table class="table">
      <thead>
        <tr>
          <th>Title</th>
          <th>Department</th>
          <th>Level</th>
          @if($canViewSalary)
          <th>Salary Range</th>
          @endif
          <th>Employees</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        @forelse($positions as $position)
        <tr>
          <td>
            <a href="{{ route('hr.positions.show', $position) }}" class="text-body">
              <i class="ti ti-briefcase me-2"></i>
              <strong>{{ $position->title }}</strong>
              @if($position->title_ar)
                <span class="text-muted ms-1">({{ $position->title_ar }})</span>
              @endif
            </a>
          </td>
          <td>{{ $position->department ?? '-' }}</td>
          <td>
            @if($position->level && isset(\Modules\HR\Models\Position::LEVELS[$position->level]))
              <span class="badge bg-label-primary">{{ \Modules\HR\Models\Position::LEVELS[$position->level] }}</span>
            @else
              <span class="text-muted">-</span>
            @endif
          </td>
          @if($canViewSalary)
          <td>
            @if($position->salary_range)
              <span class="text-success">{{ $position->salary_range }}</span>
            @else
              <span class="text-muted">Not Set</span>
            @endif
          </td>
          @endif
          <td>
            <span class="badge bg-label-info">{{ $position->employees_count }} employee(s)</span>
          </td>
          <td>
            @if($position->is_active)
              <span class="badge bg-success">Active</span>
            @else
              <span class="badge bg-secondary">Inactive</span>
            @endif
          </td>
          <td>
            <div class="dropdown">
              <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                <i class="ti ti-dots-vertical"></i>
              </button>
              <div class="dropdown-menu">
                <a class="dropdown-item" href="{{ route('hr.positions.show', $position) }}">
                  <i class="ti ti-eye me-2"></i>View
                </a>
                <a class="dropdown-item" href="{{ route('hr.positions.edit', $position) }}">
                  <i class="ti ti-edit me-2"></i>Edit
                </a>
                <form action="{{ route('hr.positions.toggle-status', $position) }}" method="POST" class="d-inline">
                  @csrf
                  <button type="submit" class="dropdown-item">
                    @if($position->is_active)
                      <i class="ti ti-ban me-2"></i>Deactivate
                    @else
                      <i class="ti ti-check me-2"></i>Activate
                    @endif
                  </button>
                </form>
                <div class="dropdown-divider"></div>
                <form action="{{ route('hr.positions.destroy', $position) }}" method="POST" class="d-inline">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="dropdown-item text-danger"
                          onclick="return confirm('Are you sure you want to delete this position?')">
                    <i class="ti ti-trash me-2"></i>Delete
                  </button>
                </form>
              </div>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="{{ $canViewSalary ? 7 : 6 }}" class="text-center py-4">
            <div class="d-flex flex-column align-items-center">
              <i class="ti ti-briefcase text-muted" style="font-size: 3rem;"></i>
              <h6 class="mt-2">No positions found</h6>
              <p class="text-muted">Start by creating your first position</p>
              <a href="{{ route('hr.positions.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>Create Position
              </a>
            </div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($positions->hasPages())
  <div class="card-footer">
    {{ $positions->links() }}
  </div>
  @endif
</div>
@endsection
