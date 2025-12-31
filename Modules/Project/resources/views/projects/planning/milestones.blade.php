@extends('layouts/layoutMaster')

@section('title', 'Milestones - ' . $project->name)

@section('vendor-style')
@vite('resources/assets/vendor/libs/select2/select2.scss')
@vite('resources/assets/vendor/libs/flatpickr/flatpickr.scss')
@endsection

@section('vendor-script')
@vite('resources/assets/vendor/libs/select2/select2.js')
@vite('resources/assets/vendor/libs/flatpickr/flatpickr.js')
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Page Header -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center">
              <div class="avatar avatar-lg me-4">
                <span class="avatar-initial rounded-circle bg-label-primary">
                  <i class="ti ti-flag ti-lg"></i>
                </span>
              </div>
              <div>
                <h4 class="mb-0">{{ $project->name }}</h4>
                <p class="text-muted mb-0">
                  <span class="badge bg-label-primary me-2">{{ $project->code }}</span>
                  Project Milestones
                </p>
              </div>
            </div>
            <div class="d-flex gap-2">
              <a href="{{ route('projects.planning.timeline', $project) }}" class="btn btn-outline-secondary">
                <i class="ti ti-chart-line me-1"></i>Timeline View
              </a>
              <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMilestoneModal">
                <i class="ti ti-plus me-1"></i>Add Milestone
              </button>
            </div>
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

  <!-- Summary Cards -->
  @php
    $pendingCount = $milestones->where('status', 'pending')->count();
    $inProgressCount = $milestones->where('status', 'in_progress')->count();
    $completedCount = $milestones->where('status', 'completed')->count();
    $overdueCount = $milestones->filter(fn($m) => $m->isOverdue())->count();
  @endphp
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-md bg-label-secondary me-3 d-flex align-items-center justify-content-center">
              <i class="ti ti-clock ti-md"></i>
            </div>
            <div>
              <h4 class="mb-0">{{ $pendingCount }}</h4>
              <small class="text-muted">Pending</small>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-md bg-label-primary me-3 d-flex align-items-center justify-content-center">
              <i class="ti ti-progress ti-md"></i>
            </div>
            <div>
              <h4 class="mb-0">{{ $inProgressCount }}</h4>
              <small class="text-muted">In Progress</small>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-md bg-label-success me-3 d-flex align-items-center justify-content-center">
              <i class="ti ti-check ti-md"></i>
            </div>
            <div>
              <h4 class="mb-0">{{ $completedCount }}</h4>
              <small class="text-muted">Completed</small>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-md bg-label-danger me-3 d-flex align-items-center justify-content-center">
              <i class="ti ti-alert-triangle ti-md"></i>
            </div>
            <div>
              <h4 class="mb-0">{{ $overdueCount }}</h4>
              <small class="text-muted">Overdue</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Milestones List -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">
        <i class="ti ti-flag-2 me-2"></i>Milestones ({{ $milestones->count() }})
      </h5>
    </div>
    <div class="card-body">
      @if($milestones->count() > 0)
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Milestone</th>
                <th>Due Date</th>
                <th>Priority</th>
                <th>Status</th>
                <th class="text-center">Progress</th>
                <th>Assigned To</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($milestones as $milestone)
                @php
                  $isOverdue = $milestone->isOverdue();
                  $daysUntil = $milestone->daysUntilDue();
                @endphp
                <tr class="{{ $isOverdue ? 'table-danger' : '' }}">
                  <td>
                    <div>
                      <h6 class="mb-0">{{ $milestone->name }}</h6>
                      @if($milestone->description)
                        <small class="text-muted">{{ Str::limit($milestone->description, 60) }}</small>
                      @endif
                    </div>
                  </td>
                  <td>
                    @if($milestone->due_date)
                      <div>
                        {{ $milestone->due_date->format('M d, Y') }}
                        @if($isOverdue)
                          <br><span class="badge bg-danger">{{ abs($daysUntil) }} days overdue</span>
                        @elseif($daysUntil !== null && $daysUntil <= 7 && $daysUntil >= 0)
                          <br><span class="badge bg-warning">{{ $daysUntil }} days left</span>
                        @endif
                      </div>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    @php
                      $priorityColors = ['low' => 'secondary', 'medium' => 'info', 'high' => 'warning', 'critical' => 'danger'];
                    @endphp
                    <span class="badge bg-label-{{ $priorityColors[$milestone->priority] ?? 'secondary' }}">
                      {{ ucfirst($milestone->priority) }}
                    </span>
                  </td>
                  <td>
                    @php
                      $statusColors = ['pending' => 'secondary', 'in_progress' => 'primary', 'completed' => 'success', 'on_hold' => 'warning', 'cancelled' => 'danger'];
                    @endphp
                    <span class="badge bg-label-{{ $statusColors[$milestone->status] ?? 'secondary' }}">
                      {{ str_replace('_', ' ', ucfirst($milestone->status)) }}
                    </span>
                  </td>
                  <td class="text-center">
                    <div class="d-flex flex-column align-items-center">
                      <span class="fw-semibold">{{ number_format($milestone->progress_percentage, 0) }}%</span>
                      <div class="progress" style="width: 60px; height: 6px;">
                        <div class="progress-bar bg-{{ $milestone->progress_percentage >= 100 ? 'success' : 'primary' }}"
                             style="width: {{ $milestone->progress_percentage }}%"></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    @if($milestone->assignee)
                      <div class="d-flex align-items-center">
                        <div class="avatar avatar-xs me-2" style="background-color: {{ '#' . substr(md5($milestone->assignee->name), 0, 6) }}; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.625rem;">
                          {{ strtoupper(substr($milestone->assignee->name, 0, 2)) }}
                        </div>
                        <small>{{ $milestone->assignee->name }}</small>
                      </div>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    <div class="d-flex gap-1">
                      <button type="button" class="btn btn-sm btn-icon btn-outline-primary"
                              data-bs-toggle="modal" data-bs-target="#editMilestoneModal"
                              onclick="editMilestone({{ json_encode($milestone) }})">
                        <i class="ti ti-edit"></i>
                      </button>
                      <form method="POST" action="{{ route('projects.planning.milestones.destroy', [$project, $milestone]) }}"
                            class="d-inline" onsubmit="return confirm('Delete this milestone?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-icon btn-outline-danger">
                          <i class="ti ti-trash"></i>
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
          <i class="ti ti-flag ti-lg text-muted mb-3" style="font-size: 3rem;"></i>
          <h5 class="text-muted">No Milestones</h5>
          <p class="text-muted mb-4">This project doesn't have any milestones yet.</p>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMilestoneModal">
            <i class="ti ti-plus me-1"></i>Add First Milestone
          </button>
        </div>
      @endif
    </div>
  </div>
</div>

<!-- Add Milestone Modal -->
<div class="modal fade" id="addMilestoneModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="{{ route('projects.planning.milestones.store', $project) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Add Milestone</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-8 mb-3">
              <label for="name" class="form-label">Milestone Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="col-md-4 mb-3">
              <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="due_date" name="due_date" required>
            </div>
          </div>
          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
              <select class="form-select" id="priority" name="priority" required>
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
                <option value="critical">Critical</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="assigned_to" class="form-label">Assign To</label>
              <select class="form-select select2" id="assigned_to" name="assigned_to">
                <option value="">Not Assigned</option>
                @foreach($employees as $employee)
                  <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Deliverables</label>
            <div id="deliverables-container">
              <div class="input-group mb-2">
                <input type="text" class="form-control" name="deliverables[]" placeholder="Enter a deliverable">
                <button type="button" class="btn btn-outline-primary" onclick="addDeliverable()">
                  <i class="ti ti-plus"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Milestone</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Milestone Modal -->
<div class="modal fade" id="editMilestoneModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" id="editMilestoneForm">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title">Edit Milestone</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-8 mb-3">
              <label for="edit_name" class="form-label">Milestone Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="edit_name" name="name" required>
            </div>
            <div class="col-md-4 mb-3">
              <label for="edit_due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="edit_due_date" name="due_date" required>
            </div>
          </div>
          <div class="mb-3">
            <label for="edit_description" class="form-label">Description</label>
            <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="edit_priority" class="form-label">Priority <span class="text-danger">*</span></label>
              <select class="form-select" id="edit_priority" name="priority" required>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="critical">Critical</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="edit_status" class="form-label">Status <span class="text-danger">*</span></label>
              <select class="form-select" id="edit_status" name="status" required>
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="on_hold">On Hold</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="edit_progress" class="form-label">Progress %</label>
              <input type="number" class="form-control" id="edit_progress" name="progress_percentage"
                     min="0" max="100" step="5">
            </div>
          </div>
          <div class="mb-3">
            <label for="edit_assigned_to" class="form-label">Assign To</label>
            <select class="form-select" id="edit_assigned_to" name="assigned_to">
              <option value="">Not Assigned</option>
              @foreach($employees as $employee)
                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Deliverables</label>
            <div id="edit-deliverables-container"></div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addEditDeliverable()">
              <i class="ti ti-plus me-1"></i>Add Deliverable
            </button>
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
  if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
    jQuery('.select2').select2({
      theme: 'bootstrap-5',
      allowClear: true,
      dropdownParent: jQuery('#addMilestoneModal')
    });
  }
});

function addDeliverable() {
  const container = document.getElementById('deliverables-container');
  const newInput = document.createElement('div');
  newInput.className = 'input-group mb-2';
  newInput.innerHTML = `
    <input type="text" class="form-control" name="deliverables[]" placeholder="Enter a deliverable">
    <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">
      <i class="ti ti-x"></i>
    </button>
  `;
  container.appendChild(newInput);
}

function addEditDeliverable(value = '') {
  const container = document.getElementById('edit-deliverables-container');
  const newInput = document.createElement('div');
  newInput.className = 'input-group mb-2';
  newInput.innerHTML = `
    <input type="text" class="form-control" name="deliverables[]" value="${value}" placeholder="Enter a deliverable">
    <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">
      <i class="ti ti-x"></i>
    </button>
  `;
  container.appendChild(newInput);
}

function editMilestone(milestone) {
  const form = document.getElementById('editMilestoneForm');
  form.action = `/projects/{{ $project->id }}/planning/milestones/${milestone.id}`;

  document.getElementById('edit_name').value = milestone.name;
  document.getElementById('edit_description').value = milestone.description || '';
  document.getElementById('edit_due_date').value = milestone.due_date ? milestone.due_date.split('T')[0] : '';
  document.getElementById('edit_priority').value = milestone.priority;
  document.getElementById('edit_status').value = milestone.status;
  document.getElementById('edit_progress').value = milestone.progress_percentage || 0;
  document.getElementById('edit_assigned_to').value = milestone.assigned_to || '';

  // Handle deliverables
  const container = document.getElementById('edit-deliverables-container');
  container.innerHTML = '';
  if (milestone.deliverables && milestone.deliverables.length > 0) {
    milestone.deliverables.forEach(function(deliverable) {
      addEditDeliverable(deliverable);
    });
  } else {
    addEditDeliverable();
  }
}
</script>
@endsection
@endsection
