@extends('layouts/layoutMaster')

@section('title', 'Edit Project')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="ti ti-folder-cog me-2"></i>Edit Project: {{ $project->name }}
          </h5>
          <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-arrow-left me-1"></i>Back to Projects
          </a>
        </div>
        <div class="card-body">
          @if ($errors->any())
            <div class="alert alert-danger alert-dismissible" role="alert">
              <ul class="mb-0">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @endif

          <form action="{{ route('projects.update', $project) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label" for="name">Project Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                       id="name" name="name" value="{{ old('name', $project->name) }}"
                       placeholder="e.g., Visitor Management System" required>
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label" for="code">Project Code <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('code') is-invalid @enderror"
                       id="code" name="code" value="{{ old('code', $project->code) }}"
                       placeholder="e.g., VIS" maxlength="20" required>
                <div class="form-text">Short code used for Jira issue keys (e.g., VIS-123)</div>
                @error('code')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label" for="customer_id">Customer</label>
                <select class="form-select @error('customer_id') is-invalid @enderror"
                        id="customer_id" name="customer_id">
                  <option value="">-- No Customer --</option>
                  @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" {{ old('customer_id', $project->customer_id) == $customer->id ? 'selected' : '' }}>
                      {{ $customer->display_name }}
                    </option>
                  @endforeach
                </select>
                <div class="form-text">Link this project to a customer for billing purposes</div>
                @error('customer_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="description">Description</label>
              <textarea class="form-control @error('description') is-invalid @enderror"
                        id="description" name="description" rows="3"
                        placeholder="Project description...">{{ old('description', $project->description) }}</textarea>
              @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            @if($project->jira_project_id)
              <div class="mb-3">
                <label class="form-label">Jira Project ID</label>
                <input type="text" class="form-control" value="{{ $project->jira_project_id }}" disabled>
                <div class="form-text">This project is synced from Jira</div>
              </div>
            @endif

            <div class="row">
              <div class="col-md-6 mb-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="needs_monthly_report"
                         name="needs_monthly_report" value="1"
                         {{ old('needs_monthly_report', $project->needs_monthly_report) ? 'checked' : '' }}>
                  <label class="form-check-label" for="needs_monthly_report">
                    Needs Monthly Report
                  </label>
                </div>
                <div class="form-text">Enable this if the project requires monthly billing reports</div>
              </div>

              <div class="col-md-6 mb-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="is_active"
                         name="is_active" value="1"
                         {{ old('is_active', $project->is_active) ? 'checked' : '' }}>
                  <label class="form-check-label" for="is_active">
                    Active
                  </label>
                </div>
                <div class="form-text">Inactive projects won't appear in reports</div>
              </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-end gap-2">
              <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-x me-1"></i>Cancel
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="ti ti-check me-1"></i>Update Project
              </button>
            </div>
          </form>

          <!-- Delete form (outside the update form to avoid nested forms) -->
          <div class="mt-4 pt-4 border-top">
            <form action="{{ route('projects.destroy', $project) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Are you sure you want to delete this project?');">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-outline-danger">
                <i class="ti ti-trash me-1"></i>Delete Project
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
