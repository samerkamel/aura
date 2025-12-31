@extends('layouts/layoutMaster')

@section('title', 'Time Estimates - ' . $project->name)

@section('vendor-style')
@vite('resources/assets/vendor/libs/select2/select2.scss')
@endsection

@section('vendor-script')
@vite('resources/assets/vendor/libs/select2/select2.js')
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
                <span class="avatar-initial rounded-circle bg-label-info">
                  <i class="ti ti-clock ti-lg"></i>
                </span>
              </div>
              <div>
                <h4 class="mb-0">{{ $project->name }}</h4>
                <p class="text-muted mb-0">
                  <span class="badge bg-label-primary me-2">{{ $project->code }}</span>
                  Time Estimates & Forecasting
                </p>
              </div>
            </div>
            <div class="d-flex gap-2">
              <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Back to Project
              </a>
              <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEstimateModal">
                <i class="ti ti-plus me-1"></i>Add Estimate
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

  <!-- Summary Cards -->
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <h6 class="text-muted mb-2">Estimated Hours</h6>
              <h3 class="mb-0">{{ number_format($summary['total_estimated'], 1) }}h</h3>
            </div>
            <div class="avatar avatar-md bg-label-primary">
              <i class="ti ti-clock ti-md"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <h6 class="text-muted mb-2">Actual Hours</h6>
              <h3 class="mb-0">{{ number_format($summary['total_actual'], 1) }}h</h3>
            </div>
            <div class="avatar avatar-md bg-label-info">
              <i class="ti ti-hourglass ti-md"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <h6 class="text-muted mb-2">Remaining Hours</h6>
              <h3 class="mb-0">{{ number_format($summary['total_remaining'], 1) }}h</h3>
            </div>
            <div class="avatar avatar-md bg-label-warning">
              <i class="ti ti-clock-pause ti-md"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <h6 class="text-muted mb-2">Variance</h6>
              @php
                $varianceClass = $summary['variance'] > 0 ? 'danger' : ($summary['variance'] < 0 ? 'success' : 'secondary');
                $varianceSign = $summary['variance'] > 0 ? '+' : '';
              @endphp
              <h3 class="mb-0 text-{{ $varianceClass }}">{{ $varianceSign }}{{ number_format($summary['variance'], 1) }}h</h3>
            </div>
            <div class="avatar avatar-md bg-label-{{ $varianceClass }}">
              <i class="ti ti-chart-{{ $summary['variance'] > 0 ? 'arrows-vertical' : 'arrows' }} ti-md"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Progress Overview -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col-md-8">
              <h6 class="text-muted mb-2">Overall Progress</h6>
              @php
                $progressPercent = $summary['total_estimated'] > 0
                  ? min(100, ($summary['total_actual'] / $summary['total_estimated']) * 100)
                  : 0;
              @endphp
              <div class="progress" style="height: 20px;">
                <div class="progress-bar bg-{{ $progressPercent > 100 ? 'danger' : 'primary' }}"
                     style="width: {{ min($progressPercent, 100) }}%">
                  {{ number_format($progressPercent, 0) }}%
                </div>
              </div>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
              <div class="d-flex justify-content-md-end gap-4">
                <div class="text-center">
                  <span class="badge bg-success fs-6">{{ $summary['completed_count'] }}</span>
                  <br><small class="text-muted">Completed</small>
                </div>
                <div class="text-center">
                  <span class="badge bg-primary fs-6">{{ $summary['in_progress_count'] }}</span>
                  <br><small class="text-muted">In Progress</small>
                </div>
                <div class="text-center">
                  <span class="badge bg-secondary fs-6">{{ $summary['not_started_count'] }}</span>
                  <br><small class="text-muted">Not Started</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Estimates List -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">
        <i class="ti ti-list me-2"></i>Time Estimates ({{ $estimates->count() }})
      </h5>
    </div>
    <div class="card-body">
      @if($estimates->count() > 0)
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Task</th>
                <th>Milestone</th>
                <th class="text-end">Estimated</th>
                <th class="text-end">Actual</th>
                <th class="text-end">Variance</th>
                <th>Status</th>
                <th>Assigned To</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($estimates as $estimate)
                <tr>
                  <td>
                    <div>
                      <h6 class="mb-0">{{ $estimate->task_name }}</h6>
                      @if($estimate->jira_issue_key)
                        <small class="text-muted"><i class="ti ti-brand-jira"></i> {{ $estimate->jira_issue_key }}</small>
                      @endif
                    </div>
                  </td>
                  <td>
                    @if($estimate->milestone)
                      <span class="badge bg-label-secondary">{{ $estimate->milestone->name }}</span>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td class="text-end">
                    <strong>{{ number_format($estimate->estimated_hours, 1) }}h</strong>
                  </td>
                  <td class="text-end">
                    {{ number_format($estimate->actual_hours, 1) }}h
                  </td>
                  <td class="text-end">
                    @if($estimate->variance_hours !== null)
                      @php
                        $vClass = $estimate->variance_hours > 0 ? 'danger' : ($estimate->variance_hours < 0 ? 'success' : 'secondary');
                        $vSign = $estimate->variance_hours > 0 ? '+' : '';
                      @endphp
                      <span class="text-{{ $vClass }}">
                        {{ $vSign }}{{ number_format($estimate->variance_hours, 1) }}h
                        @if($estimate->variance_percentage)
                          <br><small>({{ $vSign }}{{ number_format($estimate->variance_percentage, 0) }}%)</small>
                        @endif
                      </span>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    <span class="badge bg-label-{{ $estimate->getStatusBadgeClass() }}">
                      {{ str_replace('_', ' ', ucfirst($estimate->status)) }}
                    </span>
                  </td>
                  <td>
                    @if($estimate->assignee)
                      <small>{{ $estimate->assignee->name }}</small>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    <div class="d-flex gap-1">
                      <button type="button" class="btn btn-sm btn-icon btn-outline-primary"
                              data-bs-toggle="modal" data-bs-target="#editEstimateModal"
                              onclick="editEstimate({{ json_encode($estimate) }})">
                        <i class="ti ti-edit"></i>
                      </button>
                      <form method="POST" action="{{ route('projects.planning.time-estimates.destroy', [$project, $estimate]) }}"
                            class="d-inline" onsubmit="return confirm('Delete this estimate?')">
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
            <tfoot class="table-light">
              <tr>
                <th colspan="2">Total</th>
                <th class="text-end">{{ number_format($summary['total_estimated'], 1) }}h</th>
                <th class="text-end">{{ number_format($summary['total_actual'], 1) }}h</th>
                <th class="text-end text-{{ $summary['variance'] > 0 ? 'danger' : 'success' }}">
                  {{ $summary['variance'] > 0 ? '+' : '' }}{{ number_format($summary['variance'], 1) }}h
                </th>
                <th colspan="3"></th>
              </tr>
            </tfoot>
          </table>
        </div>
      @else
        <div class="text-center py-5">
          <i class="ti ti-clock ti-lg text-muted mb-3" style="font-size: 3rem;"></i>
          <h5 class="text-muted">No Time Estimates</h5>
          <p class="text-muted mb-4">Add time estimates to track and forecast project hours.</p>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEstimateModal">
            <i class="ti ti-plus me-1"></i>Add First Estimate
          </button>
        </div>
      @endif
    </div>
  </div>
</div>

<!-- Add Estimate Modal -->
<div class="modal fade" id="addEstimateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="{{ route('projects.planning.time-estimates.store', $project) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Add Time Estimate</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-8 mb-3">
              <label for="task_name" class="form-label">Task Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="task_name" name="task_name" required>
            </div>
            <div class="col-md-4 mb-3">
              <label for="estimated_hours" class="form-label">Estimated Hours <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="estimated_hours" name="estimated_hours"
                     min="0" step="0.5" required>
            </div>
          </div>
          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="milestone_id" class="form-label">Milestone</label>
              <select class="form-select" id="milestone_id" name="milestone_id">
                <option value="">None</option>
                @foreach($project->milestones as $milestone)
                  <option value="{{ $milestone->id }}">{{ $milestone->name }}</option>
                @endforeach
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
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="estimated_start_date" class="form-label">Estimated Start</label>
              <input type="date" class="form-control" id="estimated_start_date" name="estimated_start_date">
            </div>
            <div class="col-md-6 mb-3">
              <label for="estimated_end_date" class="form-label">Estimated End</label>
              <input type="date" class="form-control" id="estimated_end_date" name="estimated_end_date">
            </div>
          </div>
          <div class="mb-3">
            <label for="jira_issue_key" class="form-label">Jira Issue Key</label>
            <input type="text" class="form-control" id="jira_issue_key" name="jira_issue_key"
                   placeholder="e.g., PROJ-123">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Estimate</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Estimate Modal -->
<div class="modal fade" id="editEstimateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" id="editEstimateForm">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title">Edit Time Estimate</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_task_name" class="form-label">Task Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="edit_task_name" name="task_name" required>
            </div>
            <div class="col-md-3 mb-3">
              <label for="edit_estimated_hours" class="form-label">Estimated</label>
              <input type="number" class="form-control" id="edit_estimated_hours" name="estimated_hours"
                     min="0" step="0.5" required>
            </div>
            <div class="col-md-3 mb-3">
              <label for="edit_actual_hours" class="form-label">Actual</label>
              <input type="number" class="form-control" id="edit_actual_hours" name="actual_hours"
                     min="0" step="0.5" required>
            </div>
          </div>
          <div class="mb-3">
            <label for="edit_description" class="form-label">Description</label>
            <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="edit_milestone_id" class="form-label">Milestone</label>
              <select class="form-select" id="edit_milestone_id" name="milestone_id">
                <option value="">None</option>
                @foreach($project->milestones as $milestone)
                  <option value="{{ $milestone->id }}">{{ $milestone->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="edit_status" class="form-label">Status</label>
              <select class="form-select" id="edit_status" name="status" required>
                <option value="not_started">Not Started</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="on_hold">On Hold</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="edit_assigned_to" class="form-label">Assign To</label>
              <select class="form-select" id="edit_assigned_to" name="assigned_to">
                <option value="">Not Assigned</option>
                @foreach($employees as $employee)
                  <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Estimated Period</label>
              <div class="input-group">
                <input type="date" class="form-control" id="edit_estimated_start_date" name="estimated_start_date">
                <span class="input-group-text">to</span>
                <input type="date" class="form-control" id="edit_estimated_end_date" name="estimated_end_date">
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Actual Period</label>
              <div class="input-group">
                <input type="date" class="form-control" id="edit_actual_start_date" name="actual_start_date">
                <span class="input-group-text">to</span>
                <input type="date" class="form-control" id="edit_actual_end_date" name="actual_end_date">
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label for="edit_jira_issue_key" class="form-label">Jira Issue Key</label>
            <input type="text" class="form-control" id="edit_jira_issue_key" name="jira_issue_key">
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
  if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
    jQuery('.select2').select2({
      theme: 'bootstrap-5',
      allowClear: true,
      dropdownParent: jQuery('#addEstimateModal')
    });
  }
});

function editEstimate(estimate) {
  const form = document.getElementById('editEstimateForm');
  form.action = `/projects/{{ $project->id }}/planning/time-estimates/${estimate.id}`;

  document.getElementById('edit_task_name').value = estimate.task_name;
  document.getElementById('edit_description').value = estimate.description || '';
  document.getElementById('edit_estimated_hours').value = estimate.estimated_hours;
  document.getElementById('edit_actual_hours').value = estimate.actual_hours || 0;
  document.getElementById('edit_milestone_id').value = estimate.milestone_id || '';
  document.getElementById('edit_status').value = estimate.status;
  document.getElementById('edit_assigned_to').value = estimate.assigned_to || '';
  document.getElementById('edit_estimated_start_date').value = estimate.estimated_start_date ? estimate.estimated_start_date.split('T')[0] : '';
  document.getElementById('edit_estimated_end_date').value = estimate.estimated_end_date ? estimate.estimated_end_date.split('T')[0] : '';
  document.getElementById('edit_actual_start_date').value = estimate.actual_start_date ? estimate.actual_start_date.split('T')[0] : '';
  document.getElementById('edit_actual_end_date').value = estimate.actual_end_date ? estimate.actual_end_date.split('T')[0] : '';
  document.getElementById('edit_jira_issue_key').value = estimate.jira_issue_key || '';
}
</script>
@endsection
@endsection
