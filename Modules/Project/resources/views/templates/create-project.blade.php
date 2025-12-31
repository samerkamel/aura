@extends('layouts/layoutMaster')

@section('title', 'Create Project from Template')

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
                <span class="avatar-initial rounded-circle bg-label-success">
                  <i class="ti ti-folder-plus ti-lg"></i>
                </span>
              </div>
              <div>
                <h4 class="mb-0">Create Project from Template</h4>
                <p class="text-muted mb-0">
                  Using template: <strong>{{ $template->name }}</strong>
                  @if($template->category)
                    <span class="badge bg-label-info ms-2">{{ $template->category }}</span>
                  @endif
                </p>
              </div>
            </div>
            <div class="d-flex gap-2">
              <a href="{{ route('projects.templates.show', $template) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Back to Template
              </a>
            </div>
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

  <div class="row">
    <div class="col-lg-8">
      <form method="POST" action="{{ route('projects.templates.store-project', $template) }}">
        @csrf

        <!-- Project Details -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0"><i class="ti ti-folder me-2"></i>Project Details</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-8 mb-3">
                <label for="name" class="form-label">Project Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name"
                       value="{{ old('name') }}" required placeholder="Enter project name">
              </div>
              <div class="col-md-4 mb-3">
                <label for="code" class="form-label">Project Code <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="code" name="code"
                       value="{{ old('code') }}" required placeholder="e.g., PRJ-001" maxlength="20">
              </div>
              <div class="col-md-6 mb-3">
                <label for="customer_id" class="form-label">Customer</label>
                <select class="form-select select2" id="customer_id" name="customer_id">
                  <option value="">No customer selected</option>
                  @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                      {{ $customer->display_name }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label for="project_manager_id" class="form-label">Project Manager</label>
                <select class="form-select select2" id="project_manager_id" name="project_manager_id">
                  <option value="">Not assigned</option>
                  @php
                    $employees = \Modules\HR\Models\Employee::active()->orderBy('first_name')->get();
                  @endphp
                  @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ old('project_manager_id') == $employee->id ? 'selected' : '' }}>
                      {{ $employee->full_name }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label for="planned_start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                <input type="text" class="form-control flatpickr" id="planned_start_date" name="planned_start_date"
                       value="{{ old('planned_start_date', now()->format('Y-m-d')) }}" required>
                @if($template->estimated_duration_days)
                  <small class="text-muted">
                    End date will be calculated: Start + {{ $template->estimated_duration_days }} days
                  </small>
                @endif
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Template Duration</label>
                <input type="text" class="form-control" value="{{ $template->estimated_duration_days ?? 'Not set' }} days" readonly disabled>
              </div>
              <div class="col-12 mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"
                          placeholder="Project description...">{{ old('description', $template->description) }}</textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Submit -->
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div class="text-muted small">
                <i class="ti ti-info-circle me-1"></i>
                This will create a new project with all template milestones, risks, and tasks.
              </div>
              <div class="d-flex gap-2">
                <a href="{{ route('projects.templates.show', $template) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                  <i class="ti ti-plus me-1"></i>Create Project
                </button>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- Template Summary Sidebar -->
    <div class="col-lg-4">
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0"><i class="ti ti-template me-2"></i>Template Summary</h5>
        </div>
        <div class="card-body">
          <h6 class="mb-3">{{ $template->name }}</h6>
          @if($template->description)
            <p class="text-muted small mb-3">{{ Str::limit($template->description, 100) }}</p>
          @endif

          <ul class="list-group list-group-flush">
            @if($template->estimated_duration_days)
              <li class="list-group-item d-flex justify-content-between px-0">
                <span><i class="ti ti-calendar me-2"></i>Duration</span>
                <strong>{{ $template->estimated_duration_days }} days</strong>
              </li>
            @endif
            @if($template->estimated_budget)
              <li class="list-group-item d-flex justify-content-between px-0">
                <span><i class="ti ti-currency-dollar me-2"></i>Budget</span>
                <strong>${{ number_format($template->estimated_budget, 2) }}</strong>
              </li>
            @endif
            @if(isset($template->default_settings['estimated_hours']))
              <li class="list-group-item d-flex justify-content-between px-0">
                <span><i class="ti ti-clock me-2"></i>Est. Hours</span>
                <strong>{{ $template->default_settings['estimated_hours'] }}h</strong>
              </li>
            @endif
          </ul>
        </div>
      </div>

      <!-- What Will Be Created -->
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0"><i class="ti ti-checklist me-2"></i>Will Be Created</h5>
        </div>
        <div class="card-body">
          <div class="d-flex flex-column gap-3">
            @if(is_array($template->milestone_templates) && count($template->milestone_templates) > 0)
              <div class="d-flex align-items-center">
                <div class="avatar avatar-sm me-3">
                  <span class="avatar-initial rounded bg-label-primary">
                    <i class="ti ti-flag"></i>
                  </span>
                </div>
                <div>
                  <strong>{{ count($template->milestone_templates) }} Milestones</strong>
                  <div class="text-muted small">
                    @foreach(array_slice($template->milestone_templates, 0, 3) as $m)
                      {{ $m['name'] ?? 'Milestone' }}{{ !$loop->last ? ', ' : '' }}
                    @endforeach
                    @if(count($template->milestone_templates) > 3)
                      ...
                    @endif
                  </div>
                </div>
              </div>
            @endif

            @if(is_array($template->risk_templates) && count($template->risk_templates) > 0)
              <div class="d-flex align-items-center">
                <div class="avatar avatar-sm me-3">
                  <span class="avatar-initial rounded bg-label-warning">
                    <i class="ti ti-alert-triangle"></i>
                  </span>
                </div>
                <div>
                  <strong>{{ count($template->risk_templates) }} Risks</strong>
                  <div class="text-muted small">
                    Pre-identified project risks
                  </div>
                </div>
              </div>
            @endif

            @if(is_array($template->task_templates) && count($template->task_templates) > 0)
              @php
                $totalHours = collect($template->task_templates)->sum('estimated_hours');
              @endphp
              <div class="d-flex align-items-center">
                <div class="avatar avatar-sm me-3">
                  <span class="avatar-initial rounded bg-label-info">
                    <i class="ti ti-list-check"></i>
                  </span>
                </div>
                <div>
                  <strong>{{ count($template->task_templates) }} Tasks</strong>
                  @if($totalHours > 0)
                    <div class="text-muted small">
                      {{ $totalHours }} estimated hours
                    </div>
                  @endif
                </div>
              </div>
            @endif

            @if((!is_array($template->milestone_templates) || count($template->milestone_templates) == 0) &&
                (!is_array($template->risk_templates) || count($template->risk_templates) == 0) &&
                (!is_array($template->task_templates) || count($template->task_templates) == 0))
              <div class="text-muted">
                <i class="ti ti-info-circle me-1"></i>
                No milestones, risks, or tasks defined in this template.
              </div>
            @endif
          </div>
        </div>
      </div>
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
      allowClear: true
    });
  }

  // Initialize Flatpickr
  if (typeof flatpickr !== 'undefined') {
    flatpickr('.flatpickr', {
      dateFormat: 'Y-m-d',
      defaultDate: 'today'
    });
  }
});
</script>
@endsection
@endsection
