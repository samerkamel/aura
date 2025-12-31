@extends('layouts/layoutMaster')

@section('title', 'Create Template')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Page Header -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
              <div class="avatar avatar-lg me-4">
                <span class="avatar-initial rounded-circle bg-label-primary">
                  <i class="ti ti-template ti-lg"></i>
                </span>
              </div>
              <div>
                <h4 class="mb-0">Create Project Template</h4>
                <p class="text-muted mb-0">Define reusable project structure with milestones, risks, and tasks</p>
              </div>
            </div>
            <a href="{{ route('projects.templates.index') }}" class="btn btn-outline-secondary">
              <i class="ti ti-arrow-left me-1"></i>Back to Templates
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger mb-4">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('projects.templates.store') }}">
    @csrf

    <!-- Basic Information -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="ti ti-info-circle me-2"></i>Basic Information</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="name" class="form-label">Template Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name"
                   value="{{ old('name') }}" required placeholder="e.g., Web Application Project">
          </div>
          <div class="col-md-6 mb-3">
            <label for="category" class="form-label">Category</label>
            <input type="text" class="form-control" id="category" name="category"
                   value="{{ old('category') }}" placeholder="e.g., Development, Marketing, Consulting">
          </div>
          <div class="col-12 mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3"
                      placeholder="Describe what this template is used for...">{{ old('description') }}</textarea>
          </div>
          <div class="col-md-4 mb-3">
            <label for="estimated_duration_days" class="form-label">Estimated Duration (days)</label>
            <input type="number" class="form-control" id="estimated_duration_days" name="estimated_duration_days"
                   value="{{ old('estimated_duration_days') }}" min="1" placeholder="30">
          </div>
          <div class="col-md-4 mb-3">
            <label for="estimated_budget" class="form-label">Estimated Budget</label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input type="number" class="form-control" id="estimated_budget" name="estimated_budget"
                     value="{{ old('estimated_budget') }}" min="0" step="0.01" placeholder="10000">
            </div>
          </div>
          <div class="col-md-4 mb-3">
            <label for="default_settings[estimated_hours]" class="form-label">Estimated Hours</label>
            <input type="number" class="form-control" id="default_settings_estimated_hours"
                   name="default_settings[estimated_hours]" value="{{ old('default_settings.estimated_hours') }}"
                   min="0" step="0.5" placeholder="100">
          </div>
        </div>
      </div>
    </div>

    <!-- Default Settings -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="ti ti-settings me-2"></i>Default Settings</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="default_settings[hourly_rate]" class="form-label">Default Hourly Rate</label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input type="number" class="form-control" id="default_settings_hourly_rate"
                     name="default_settings[hourly_rate]" value="{{ old('default_settings.hourly_rate') }}"
                     min="0" step="0.01" placeholder="75">
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <label for="default_settings[billing_type]" class="form-label">Billing Type</label>
            <select class="form-select" id="default_settings_billing_type" name="default_settings[billing_type]">
              <option value="">Select billing type...</option>
              <option value="fixed" {{ old('default_settings.billing_type') == 'fixed' ? 'selected' : '' }}>Fixed Price</option>
              <option value="hourly" {{ old('default_settings.billing_type') == 'hourly' ? 'selected' : '' }}>Hourly</option>
              <option value="milestone" {{ old('default_settings.billing_type') == 'milestone' ? 'selected' : '' }}>Milestone-based</option>
              <option value="retainer" {{ old('default_settings.billing_type') == 'retainer' ? 'selected' : '' }}>Retainer</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Milestone Templates -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="ti ti-flag me-2"></i>Milestone Templates</h5>
        <button type="button" class="btn btn-sm btn-primary" onclick="addMilestone()">
          <i class="ti ti-plus me-1"></i>Add Milestone
        </button>
      </div>
      <div class="card-body">
        <div id="milestones-container">
          <p class="text-muted mb-0" id="no-milestones-msg">No milestones added yet. Click "Add Milestone" to define project milestones.</p>
        </div>
      </div>
    </div>

    <!-- Risk Templates -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="ti ti-alert-triangle me-2"></i>Risk Templates</h5>
        <button type="button" class="btn btn-sm btn-primary" onclick="addRisk()">
          <i class="ti ti-plus me-1"></i>Add Risk
        </button>
      </div>
      <div class="card-body">
        <div id="risks-container">
          <p class="text-muted mb-0" id="no-risks-msg">No risks added yet. Click "Add Risk" to define potential risks.</p>
        </div>
      </div>
    </div>

    <!-- Task Templates -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="ti ti-list-check me-2"></i>Task Templates</h5>
        <button type="button" class="btn btn-sm btn-primary" onclick="addTask()">
          <i class="ti ti-plus me-1"></i>Add Task
        </button>
      </div>
      <div class="card-body">
        <div id="tasks-container">
          <p class="text-muted mb-0" id="no-tasks-msg">No tasks added yet. Click "Add Task" to define project tasks.</p>
        </div>
      </div>
    </div>

    <!-- Submit -->
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-end gap-2">
          <a href="{{ route('projects.templates.index') }}" class="btn btn-outline-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-check me-1"></i>Create Template
          </button>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
let milestoneIndex = 0;
let riskIndex = 0;
let taskIndex = 0;

function addMilestone() {
  document.getElementById('no-milestones-msg').style.display = 'none';
  const container = document.getElementById('milestones-container');
  const html = `
    <div class="border rounded p-3 mb-3" id="milestone-${milestoneIndex}">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0">Milestone ${milestoneIndex + 1}</h6>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeMilestone(${milestoneIndex})">
          <i class="ti ti-trash"></i>
        </button>
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="milestone_templates[${milestoneIndex}][name]" required placeholder="e.g., Project Kickoff">
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">Day Offset</label>
          <input type="number" class="form-control" name="milestone_templates[${milestoneIndex}][offset_days]" placeholder="0" min="0">
          <small class="text-muted">Days from project start</small>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">Priority</label>
          <select class="form-select" name="milestone_templates[${milestoneIndex}][priority]">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
            <option value="critical">Critical</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="milestone_templates[${milestoneIndex}][description]" rows="2" placeholder="Milestone description..."></textarea>
        </div>
      </div>
    </div>
  `;
  container.insertAdjacentHTML('beforeend', html);
  milestoneIndex++;
}

function removeMilestone(index) {
  document.getElementById(`milestone-${index}`).remove();
  if (document.querySelectorAll('#milestones-container .border').length === 0) {
    document.getElementById('no-milestones-msg').style.display = 'block';
  }
}

function addRisk() {
  document.getElementById('no-risks-msg').style.display = 'none';
  const container = document.getElementById('risks-container');
  const html = `
    <div class="border rounded p-3 mb-3" id="risk-${riskIndex}">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0">Risk ${riskIndex + 1}</h6>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRisk(${riskIndex})">
          <i class="ti ti-trash"></i>
        </button>
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Title <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="risk_templates[${riskIndex}][title]" required placeholder="e.g., Resource Unavailability">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Category</label>
          <select class="form-select" name="risk_templates[${riskIndex}][category]">
            <option value="technical">Technical</option>
            <option value="resource">Resource</option>
            <option value="schedule">Schedule</option>
            <option value="budget">Budget</option>
            <option value="scope">Scope</option>
            <option value="external">External</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Probability</label>
          <select class="form-select" name="risk_templates[${riskIndex}][probability]">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
            <option value="very_high">Very High</option>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Impact</label>
          <select class="form-select" name="risk_templates[${riskIndex}][impact]">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
            <option value="critical">Critical</option>
          </select>
        </div>
      </div>
    </div>
  `;
  container.insertAdjacentHTML('beforeend', html);
  riskIndex++;
}

function removeRisk(index) {
  document.getElementById(`risk-${index}`).remove();
  if (document.querySelectorAll('#risks-container .border').length === 0) {
    document.getElementById('no-risks-msg').style.display = 'block';
  }
}

function addTask() {
  document.getElementById('no-tasks-msg').style.display = 'none';
  const container = document.getElementById('tasks-container');
  const html = `
    <div class="border rounded p-3 mb-3" id="task-${taskIndex}">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0">Task ${taskIndex + 1}</h6>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeTask(${taskIndex})">
          <i class="ti ti-trash"></i>
        </button>
      </div>
      <div class="row">
        <div class="col-md-8 mb-3">
          <label class="form-label">Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="task_templates[${taskIndex}][name]" required placeholder="e.g., Set up development environment">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Estimated Hours</label>
          <input type="number" class="form-control" name="task_templates[${taskIndex}][estimated_hours]" min="0" step="0.5" placeholder="4">
        </div>
      </div>
    </div>
  `;
  container.insertAdjacentHTML('beforeend', html);
  taskIndex++;
}

function removeTask(index) {
  document.getElementById(`task-${index}`).remove();
  if (document.querySelectorAll('#tasks-container .border').length === 0) {
    document.getElementById('no-tasks-msg').style.display = 'block';
  }
}
</script>
@endsection
