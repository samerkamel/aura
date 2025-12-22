@extends('layouts/layoutMaster')

@section('title', 'Manage Team - ' . $project->name)

@section('vendor-style')
@vite('resources/assets/vendor/libs/select2/select2.scss')
@endsection

@section('vendor-script')
@vite('resources/assets/vendor/libs/select2/select2.js')
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Project Header -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
              <div class="avatar avatar-lg me-4">
                <span class="avatar-initial rounded-circle bg-label-primary">
                  <i class="ti ti-folder ti-lg"></i>
                </span>
              </div>
              <div>
                <h4 class="mb-0">{{ $project->name }}</h4>
                <p class="text-muted mb-0">
                  <span class="badge bg-label-primary me-2">{{ $project->code }}</span>
                  Manage Team Members
                </p>
              </div>
            </div>
            <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-secondary">
              <i class="ti ti-arrow-left me-1"></i>Back to Project
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  @if (session('success'))
    <div class="alert alert-success alert-dismissible mb-4" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if (session('error'))
    <div class="alert alert-danger alert-dismissible mb-4" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="row">
    <!-- Left Column: Add Employee & Sync -->
    <div class="col-xl-4 col-lg-5">
      <!-- Assign Employee Form -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="ti ti-user-plus me-2"></i>Assign Employee
          </h5>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('projects.assign-employee', $project) }}">
            @csrf
            <div class="mb-3">
              <label for="employee_id" class="form-label">Select Employee</label>
              <select class="form-select select2 @error('employee_id') is-invalid @enderror" id="employee_id" name="employee_id" required>
                <option value="">Choose an employee...</option>
                @foreach($availableEmployees as $employee)
                  <option value="{{ $employee->id }}">
                    {{ $employee->name }} {{ $employee->position ? '- ' . $employee->position : '' }}
                  </option>
                @endforeach
              </select>
              @error('employee_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="mb-3">
              <label for="role" class="form-label">Role in Project</label>
              <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                <option value="member">Team Member</option>
                <option value="lead">Project Lead</option>
              </select>
              @error('role')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <button type="submit" class="btn btn-primary w-100">
              <i class="ti ti-plus me-1"></i>Assign Employee
            </button>
          </form>
        </div>
      </div>

      <!-- Sync from Worklogs -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="ti ti-refresh me-2"></i>Auto-Assign from Worklogs
          </h5>
        </div>
        <div class="card-body">
          <p class="text-muted mb-3">
            Automatically assign employees who have logged time on this project's tasks in Jira.
          </p>
          @if($unassignedWorklogEmployees->count() > 0)
            <div class="alert alert-info mb-3">
              <strong>{{ $unassignedWorklogEmployees->count() }}</strong> employee(s) with worklogs not yet assigned:
              <ul class="mb-0 mt-2">
                @foreach($unassignedWorklogEmployees->take(5) as $emp)
                  <li>{{ $emp->name }}</li>
                @endforeach
                @if($unassignedWorklogEmployees->count() > 5)
                  <li>...and {{ $unassignedWorklogEmployees->count() - 5 }} more</li>
                @endif
              </ul>
            </div>
          @else
            <div class="alert alert-success mb-3">
              <i class="ti ti-check me-1"></i>All employees with worklogs are already assigned.
            </div>
          @endif
          <form method="POST" action="{{ route('projects.sync-employees-worklogs', $project) }}">
            @csrf
            <button type="submit" class="btn btn-outline-primary w-100" {{ $unassignedWorklogEmployees->count() === 0 ? 'disabled' : '' }}>
              <i class="ti ti-refresh me-1"></i>Sync Employees from Worklogs
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Right Column: Assigned Employees -->
    <div class="col-xl-8 col-lg-7">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">
            <i class="ti ti-users me-2"></i>Team Members ({{ $project->employees->count() }})
          </h5>
        </div>
        <div class="card-body">
          @if($project->employees->count() > 0)
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Employee</th>
                    <th>Position</th>
                    <th>Role</th>
                    <th>Assignment</th>
                    <th>Date Assigned</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($project->employees->sortByDesc('pivot.role') as $employee)
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <div class="avatar avatar-sm me-3" style="background-color: {{ '#' . substr(md5($employee->name), 0, 6) }}; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.75rem;">
                            {{ strtoupper(substr($employee->name, 0, 2)) }}
                          </div>
                          <div>
                            <h6 class="mb-0">{{ $employee->name }}</h6>
                            <small class="text-muted">{{ $employee->email }}</small>
                          </div>
                        </div>
                      </td>
                      <td>{{ $employee->position ?? '-' }}</td>
                      <td>
                        <span class="badge bg-label-{{ $employee->pivot->role === 'lead' ? 'warning' : 'primary' }}">
                          {{ $employee->pivot->role === 'lead' ? 'Project Lead' : 'Team Member' }}
                        </span>
                      </td>
                      <td>
                        @if($employee->pivot->auto_assigned)
                          <span class="badge bg-label-info" title="Auto-assigned from worklogs">
                            <i class="ti ti-robot me-1"></i>Auto
                          </span>
                        @else
                          <span class="badge bg-label-secondary">
                            <i class="ti ti-user me-1"></i>Manual
                          </span>
                        @endif
                      </td>
                      <td>
                        {{ $employee->pivot->assigned_at ? \Carbon\Carbon::parse($employee->pivot->assigned_at)->format('M d, Y') : '-' }}
                      </td>
                      <td>
                        <div class="dropdown">
                          <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                            <i class="ti ti-dots-vertical"></i>
                          </button>
                          <div class="dropdown-menu">
                            @if($employee->pivot->role === 'member')
                              <form method="POST" action="{{ route('projects.update-employee-role', $project) }}" class="d-inline">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                                <input type="hidden" name="role" value="lead">
                                <button type="submit" class="dropdown-item">
                                  <i class="ti ti-arrow-up me-2"></i>Promote to Lead
                                </button>
                              </form>
                            @else
                              <form method="POST" action="{{ route('projects.update-employee-role', $project) }}" class="d-inline">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                                <input type="hidden" name="role" value="member">
                                <button type="submit" class="dropdown-item">
                                  <i class="ti ti-arrow-down me-2"></i>Change to Member
                                </button>
                              </form>
                            @endif
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('projects.unassign-employee', $project) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this employee from the project?')">
                              @csrf
                              @method('DELETE')
                              <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                              <button type="submit" class="dropdown-item text-danger">
                                <i class="ti ti-user-minus me-2"></i>Remove from Project
                              </button>
                            </form>
                          </div>
                        </div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <div class="text-center py-5">
              <i class="ti ti-users-group ti-lg text-muted mb-3" style="font-size: 3rem;"></i>
              <h5 class="text-muted">No Team Members</h5>
              <p class="text-muted mb-4">This project doesn't have any assigned employees yet.</p>
              <p class="text-muted">Use the form on the left to assign employees or sync from worklogs.</p>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Initialize Select2
  const select2Elements = document.querySelectorAll('.select2');
  if (select2Elements.length && typeof jQuery !== 'undefined') {
    jQuery('.select2').select2({
      theme: 'bootstrap-5',
      placeholder: 'Choose an employee...',
      allowClear: true
    });
  }
});
</script>
@endsection
@endsection
