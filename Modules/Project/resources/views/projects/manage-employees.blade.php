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
            <div class="mb-3">
              <label for="allocation_percentage" class="form-label">Allocation %</label>
              <div class="input-group">
                <input type="number" class="form-control @error('allocation_percentage') is-invalid @enderror"
                       id="allocation_percentage" name="allocation_percentage"
                       min="0" max="100" value="100" step="5">
                <span class="input-group-text">%</span>
              </div>
              <small class="text-muted">Percentage of time allocated to this project</small>
              @error('allocation_percentage')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="row mb-3">
              <div class="col-6">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                       id="start_date" name="start_date" value="{{ date('Y-m-d') }}">
                @error('start_date')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-6">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                       id="end_date" name="end_date">
                <small class="text-muted">Optional</small>
                @error('end_date')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="mb-3">
              <label for="hourly_rate" class="form-label">Hourly Rate Override</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" class="form-control @error('hourly_rate') is-invalid @enderror"
                       id="hourly_rate" name="hourly_rate" step="0.01" min="0"
                       placeholder="Leave blank for default">
              </div>
              <small class="text-muted">Override employee's default rate for this project</small>
              @error('hourly_rate')
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
          <!-- Allocation Summary -->
          @php
            $totalAllocation = $project->employees->sum('pivot.allocation_percentage');
            $activeCount = $project->employees->filter(function($e) {
              return is_null($e->pivot->end_date) || \Carbon\Carbon::parse($e->pivot->end_date)->isFuture();
            })->count();
          @endphp
          <div class="row mb-4">
            <div class="col-md-4">
              <div class="d-flex align-items-center">
                <div class="avatar avatar-md bg-label-primary me-3 d-flex align-items-center justify-content-center">
                  <i class="ti ti-users ti-md"></i>
                </div>
                <div>
                  <h6 class="mb-0">{{ $project->employees->count() }}</h6>
                  <small class="text-muted">Total Members</small>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="d-flex align-items-center">
                <div class="avatar avatar-md bg-label-success me-3 d-flex align-items-center justify-content-center">
                  <i class="ti ti-user-check ti-md"></i>
                </div>
                <div>
                  <h6 class="mb-0">{{ $activeCount }}</h6>
                  <small class="text-muted">Currently Active</small>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="d-flex align-items-center">
                <div class="avatar avatar-md bg-label-{{ $totalAllocation > 500 ? 'warning' : 'info' }} me-3 d-flex align-items-center justify-content-center">
                  <i class="ti ti-percentage ti-md"></i>
                </div>
                <div>
                  <h6 class="mb-0">{{ number_format($totalAllocation, 0) }}%</h6>
                  <small class="text-muted">Total Allocation</small>
                </div>
              </div>
            </div>
          </div>

          @if($project->employees->count() > 0)
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Employee</th>
                    <th>Role</th>
                    <th class="text-center">Allocation</th>
                    <th>Period</th>
                    <th>Hourly Rate</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($project->employees->sortByDesc('pivot.role') as $employee)
                    @php
                      $isExpired = $employee->pivot->end_date && \Carbon\Carbon::parse($employee->pivot->end_date)->isPast();
                    @endphp
                    <tr class="{{ $isExpired ? 'table-secondary' : '' }}">
                      <td>
                        <div class="d-flex align-items-center">
                          <div class="avatar avatar-sm me-3" style="background-color: {{ '#' . substr(md5($employee->name), 0, 6) }}; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.75rem;">
                            {{ strtoupper(substr($employee->name, 0, 2)) }}
                          </div>
                          <div>
                            <h6 class="mb-0">
                              {{ $employee->name }}
                              @if($isExpired)
                                <span class="badge bg-label-secondary ms-1">Ended</span>
                              @endif
                            </h6>
                            <small class="text-muted">{{ $employee->position ?? $employee->email }}</small>
                          </div>
                        </div>
                      </td>
                      <td>
                        <span class="badge bg-label-{{ $employee->pivot->role === 'lead' ? 'warning' : 'primary' }}">
                          {{ $employee->pivot->role === 'lead' ? 'Lead' : 'Member' }}
                        </span>
                        @if($employee->pivot->auto_assigned)
                          <span class="badge bg-label-info" title="Auto-assigned from worklogs">
                            <i class="ti ti-robot"></i>
                          </span>
                        @endif
                      </td>
                      <td class="text-center">
                        @php
                          $alloc = $employee->pivot->allocation_percentage ?? 100;
                          $allocClass = $alloc >= 80 ? 'success' : ($alloc >= 50 ? 'warning' : 'secondary');
                        @endphp
                        <div class="d-flex flex-column align-items-center">
                          <span class="fw-semibold">{{ $alloc }}%</span>
                          <div class="progress" style="width: 60px; height: 6px;">
                            <div class="progress-bar bg-{{ $allocClass }}" role="progressbar"
                                 style="width: {{ $alloc }}%"></div>
                          </div>
                        </div>
                      </td>
                      <td>
                        <small>
                          @if($employee->pivot->start_date)
                            {{ \Carbon\Carbon::parse($employee->pivot->start_date)->format('M d, Y') }}
                          @else
                            {{ $employee->pivot->assigned_at ? \Carbon\Carbon::parse($employee->pivot->assigned_at)->format('M d, Y') : '-' }}
                          @endif
                          @if($employee->pivot->end_date)
                            <br>→ {{ \Carbon\Carbon::parse($employee->pivot->end_date)->format('M d, Y') }}
                          @else
                            <br><span class="text-muted">Ongoing</span>
                          @endif
                        </small>
                      </td>
                      <td>
                        @if($employee->pivot->hourly_rate)
                          <span class="fw-semibold">${{ number_format($employee->pivot->hourly_rate, 2) }}</span>
                        @else
                          <span class="text-muted">Default</span>
                        @endif
                      </td>
                      <td>
                        <div class="d-flex gap-1">
                          <button type="button" class="btn btn-sm btn-icon btn-outline-primary"
                                  data-bs-toggle="modal" data-bs-target="#editAllocationModal"
                                  onclick="editAllocation({{ $employee->id }}, '{{ $employee->name }}', '{{ $employee->pivot->role }}', {{ $employee->pivot->allocation_percentage ?? 100 }}, '{{ $employee->pivot->start_date }}', '{{ $employee->pivot->end_date }}', '{{ $employee->pivot->hourly_rate }}', '{{ addslashes($employee->pivot->notes) }}')">
                            <i class="ti ti-edit"></i>
                          </button>
                          <form method="POST" action="{{ route('projects.unassign-employee', $project) }}"
                                class="d-inline" onsubmit="return confirm('Remove {{ $employee->name }} from the project?')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                            <button type="submit" class="btn btn-sm btn-icon btn-outline-danger">
                              <i class="ti ti-user-minus"></i>
                            </button>
                          </form>
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

<!-- Edit Allocation Modal -->
<div class="modal fade" id="editAllocationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('projects.update-employee-role', $project) }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="employee_id" id="editEmployeeId">
        <div class="modal-header">
          <h5 class="modal-title">Edit Allocation - <span id="editEmployeeName"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="editRole" class="form-label">Role</label>
            <select class="form-select" id="editRole" name="role" required>
              <option value="member">Team Member</option>
              <option value="lead">Project Lead</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="editAllocation" class="form-label">Allocation %</label>
            <div class="input-group">
              <input type="number" class="form-control" id="editAllocation" name="allocation_percentage"
                     min="0" max="100" step="5" required>
              <span class="input-group-text">%</span>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-6">
              <label for="editStartDate" class="form-label">Start Date</label>
              <input type="date" class="form-control" id="editStartDate" name="start_date">
            </div>
            <div class="col-6">
              <label for="editEndDate" class="form-label">End Date</label>
              <input type="date" class="form-control" id="editEndDate" name="end_date">
            </div>
          </div>
          <div class="mb-3">
            <label for="editHourlyRate" class="form-label">Hourly Rate Override</label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input type="number" class="form-control" id="editHourlyRate" name="hourly_rate"
                     step="0.01" min="0" placeholder="Leave blank for default">
            </div>
          </div>
          <div class="mb-3">
            <label for="editNotes" class="form-label">Notes</label>
            <textarea class="form-control" id="editNotes" name="notes" rows="2"
                      placeholder="Optional notes about this assignment"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
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

function editAllocation(employeeId, employeeName, role, allocation, startDate, endDate, hourlyRate, notes) {
  document.getElementById('editEmployeeId').value = employeeId;
  document.getElementById('editEmployeeName').textContent = employeeName;
  document.getElementById('editRole').value = role;
  document.getElementById('editAllocation').value = allocation;
  document.getElementById('editStartDate').value = startDate || '';
  document.getElementById('editEndDate').value = endDate || '';
  document.getElementById('editHourlyRate').value = hourlyRate || '';
  document.getElementById('editNotes').value = notes || '';
}
</script>
@endsection
@endsection
