@extends('layouts/layoutMaster')

@section('title', 'Edit Template - ' . $template->name)

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
                <h4 class="mb-0">Edit Template</h4>
                <p class="text-muted mb-0">{{ $template->name }}</p>
              </div>
            </div>
            <a href="{{ route('projects.templates.show', $template) }}" class="btn btn-outline-secondary">
              <i class="ti ti-arrow-left me-1"></i>Back to Template
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

  <form method="POST" action="{{ route('projects.templates.update', $template) }}">
    @csrf
    @method('PUT')

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
                   value="{{ old('name', $template->name) }}" required>
          </div>
          <div class="col-md-4 mb-3">
            <label for="category" class="form-label">Category</label>
            <input type="text" class="form-control" id="category" name="category"
                   value="{{ old('category', $template->category) }}">
          </div>
          <div class="col-md-2 mb-3">
            <label for="is_active" class="form-label">Status</label>
            <div class="form-check form-switch mt-2">
              <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                     {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
              <label class="form-check-label" for="is_active">Active</label>
            </div>
          </div>
          <div class="col-12 mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $template->description) }}</textarea>
          </div>
          <div class="col-md-4 mb-3">
            <label for="estimated_duration_days" class="form-label">Estimated Duration (days)</label>
            <input type="number" class="form-control" id="estimated_duration_days" name="estimated_duration_days"
                   value="{{ old('estimated_duration_days', $template->estimated_duration_days) }}" min="1">
          </div>
          <div class="col-md-4 mb-3">
            <label for="estimated_budget" class="form-label">Estimated Budget</label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input type="number" class="form-control" id="estimated_budget" name="estimated_budget"
                     value="{{ old('estimated_budget', $template->estimated_budget) }}" min="0" step="0.01">
            </div>
          </div>
          <div class="col-md-4 mb-3">
            <label for="default_settings[estimated_hours]" class="form-label">Estimated Hours</label>
            <input type="number" class="form-control" id="default_settings_estimated_hours"
                   name="default_settings[estimated_hours]"
                   value="{{ old('default_settings.estimated_hours', $template->default_settings['estimated_hours'] ?? '') }}"
                   min="0" step="0.5">
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
                     name="default_settings[hourly_rate]"
                     value="{{ old('default_settings.hourly_rate', $template->default_settings['hourly_rate'] ?? '') }}"
                     min="0" step="0.01">
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <label for="default_settings[billing_type]" class="form-label">Billing Type</label>
            @php $billingType = old('default_settings.billing_type', $template->default_settings['billing_type'] ?? ''); @endphp
            <select class="form-select" id="default_settings_billing_type" name="default_settings[billing_type]">
              <option value="">Select billing type...</option>
              <option value="fixed" {{ $billingType == 'fixed' ? 'selected' : '' }}>Fixed Price</option>
              <option value="hourly" {{ $billingType == 'hourly' ? 'selected' : '' }}>Hourly</option>
              <option value="milestone" {{ $billingType == 'milestone' ? 'selected' : '' }}>Milestone-based</option>
              <option value="retainer" {{ $billingType == 'retainer' ? 'selected' : '' }}>Retainer</option>
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
          @if(is_array($template->milestone_templates) && count($template->milestone_templates) > 0)
            @foreach($template->milestone_templates as $index => $milestone)
              <div class="border rounded p-3 mb-3" id="milestone-{{ $index }}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="mb-0">Milestone {{ $index + 1 }}</h6>
                  <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeMilestone({{ $index }})">
                    <i class="ti ti-trash"></i>
                  </button>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="milestone_templates[{{ $index }}][name]"
                           value="{{ $milestone['name'] ?? '' }}" required>
                  </div>
                  <div class="col-md-3 mb-3">
                    <label class="form-label">Day Offset</label>
                    <input type="number" class="form-control" name="milestone_templates[{{ $index }}][offset_days]"
                           value="{{ $milestone['offset_days'] ?? '' }}" min="0">
                  </div>
                  <div class="col-md-3 mb-3">
                    <label class="form-label">Priority</label>
                    <select class="form-select" name="milestone_templates[{{ $index }}][priority]">
                      <option value="low" {{ ($milestone['priority'] ?? '') == 'low' ? 'selected' : '' }}>Low</option>
                      <option value="medium" {{ ($milestone['priority'] ?? 'medium') == 'medium' ? 'selected' : '' }}>Medium</option>
                      <option value="high" {{ ($milestone['priority'] ?? '') == 'high' ? 'selected' : '' }}>High</option>
                      <option value="critical" {{ ($milestone['priority'] ?? '') == 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="milestone_templates[{{ $index }}][description]" rows="2">{{ $milestone['description'] ?? '' }}</textarea>
                  </div>
                </div>
              </div>
            @endforeach
          @else
            <p class="text-muted mb-0" id="no-milestones-msg">No milestones added yet.</p>
          @endif
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
          @if(is_array($template->risk_templates) && count($template->risk_templates) > 0)
            @foreach($template->risk_templates as $index => $risk)
              <div class="border rounded p-3 mb-3" id="risk-{{ $index }}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="mb-0">Risk {{ $index + 1 }}</h6>
                  <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRisk({{ $index }})">
                    <i class="ti ti-trash"></i>
                  </button>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="risk_templates[{{ $index }}][title]"
                           value="{{ $risk['title'] ?? '' }}" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="risk_templates[{{ $index }}][category]">
                      <option value="technical" {{ ($risk['category'] ?? '') == 'technical' ? 'selected' : '' }}>Technical</option>
                      <option value="resource" {{ ($risk['category'] ?? '') == 'resource' ? 'selected' : '' }}>Resource</option>
                      <option value="schedule" {{ ($risk['category'] ?? '') == 'schedule' ? 'selected' : '' }}>Schedule</option>
                      <option value="budget" {{ ($risk['category'] ?? '') == 'budget' ? 'selected' : '' }}>Budget</option>
                      <option value="scope" {{ ($risk['category'] ?? '') == 'scope' ? 'selected' : '' }}>Scope</option>
                      <option value="external" {{ ($risk['category'] ?? '') == 'external' ? 'selected' : '' }}>External</option>
                      <option value="other" {{ ($risk['category'] ?? '') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Probability</label>
                    <select class="form-select" name="risk_templates[{{ $index }}][probability]">
                      <option value="low" {{ ($risk['probability'] ?? '') == 'low' ? 'selected' : '' }}>Low</option>
                      <option value="medium" {{ ($risk['probability'] ?? 'medium') == 'medium' ? 'selected' : '' }}>Medium</option>
                      <option value="high" {{ ($risk['probability'] ?? '') == 'high' ? 'selected' : '' }}>High</option>
                      <option value="very_high" {{ ($risk['probability'] ?? '') == 'very_high' ? 'selected' : '' }}>Very High</option>
                    </select>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Impact</label>
                    <select class="form-select" name="risk_templates[{{ $index }}][impact]">
                      <option value="low" {{ ($risk['impact'] ?? '') == 'low' ? 'selected' : '' }}>Low</option>
                      <option value="medium" {{ ($risk['impact'] ?? 'medium') == 'medium' ? 'selected' : '' }}>Medium</option>
                      <option value="high" {{ ($risk['impact'] ?? '') == 'high' ? 'selected' : '' }}>High</option>
                      <option value="critical" {{ ($risk['impact'] ?? '') == 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                  </div>
                </div>
              </div>
            @endforeach
          @else
            <p class="text-muted mb-0" id="no-risks-msg">No risks added yet.</p>
          @endif
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
          @if(is_array($template->task_templates) && count($template->task_templates) > 0)
            @foreach($template->task_templates as $index => $task)
              <div class="border rounded p-3 mb-3" id="task-{{ $index }}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="mb-0">Task {{ $index + 1 }}</h6>
                  <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeTask({{ $index }})">
                    <i class="ti ti-trash"></i>
                  </button>
                </div>
                <div class="row">
                  <div class="col-md-8 mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="task_templates[{{ $index }}][name]"
                           value="{{ $task['name'] ?? '' }}" required>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Estimated Hours</label>
                    <input type="number" class="form-control" name="task_templates[{{ $index }}][estimated_hours]"
                           value="{{ $task['estimated_hours'] ?? '' }}" min="0" step="0.5">
                  </div>
                </div>
              </div>
            @endforeach
          @else
            <p class="text-muted mb-0" id="no-tasks-msg">No tasks added yet.</p>
          @endif
        </div>
      </div>
    </div>

    <!-- Submit -->
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-end gap-2">
          <a href="{{ route('projects.templates.show', $template) }}" class="btn btn-outline-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-check me-1"></i>Save Changes
          </button>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
let milestoneIndex = {{ is_array($template->milestone_templates) ? count($template->milestone_templates) : 0 }};
let riskIndex = {{ is_array($template->risk_templates) ? count($template->risk_templates) : 0 }};
let taskIndex = {{ is_array($template->task_templates) ? count($template->task_templates) : 0 }};

function addMilestone() {
  const noMsg = document.getElementById('no-milestones-msg');
  if (noMsg) noMsg.style.display = 'none';
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
          <textarea class="form-control" name="milestone_templates[${milestoneIndex}][description]" rows="2"></textarea>
        </div>
      </div>
    </div>
  `;
  container.insertAdjacentHTML('beforeend', html);
  milestoneIndex++;
}

function removeMilestone(index) {
  document.getElementById(`milestone-${index}`).remove();
}

function addRisk() {
  const noMsg = document.getElementById('no-risks-msg');
  if (noMsg) noMsg.style.display = 'none';
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
          <input type="text" class="form-control" name="risk_templates[${riskIndex}][title]" required>
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
}

function addTask() {
  const noMsg = document.getElementById('no-tasks-msg');
  if (noMsg) noMsg.style.display = 'none';
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
          <input type="text" class="form-control" name="task_templates[${taskIndex}][name]" required>
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Estimated Hours</label>
          <input type="number" class="form-control" name="task_templates[${taskIndex}][estimated_hours]" min="0" step="0.5">
        </div>
      </div>
    </div>
  `;
  container.insertAdjacentHTML('beforeend', html);
  taskIndex++;
}

function removeTask(index) {
  document.getElementById(`task-${index}`).remove();
}
</script>
@endsection
