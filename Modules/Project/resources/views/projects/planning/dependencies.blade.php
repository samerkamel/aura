@extends('layouts/layoutMaster')

@section('title', 'Dependencies - ' . $project->name)

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
                <span class="avatar-initial rounded-circle bg-label-secondary">
                  <i class="ti ti-link ti-lg"></i>
                </span>
              </div>
              <div>
                <h4 class="mb-0">{{ $project->name }}</h4>
                <p class="text-muted mb-0">
                  <span class="badge bg-label-primary me-2">{{ $project->code }}</span>
                  Project Dependencies
                </p>
              </div>
            </div>
            <div class="d-flex gap-2">
              <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Back to Project
              </a>
              <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDependencyModal">
                <i class="ti ti-plus me-1"></i>Add Dependency
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

  <div class="row">
    <!-- This Project Depends On -->
    <div class="col-md-6 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="ti ti-arrow-right me-2"></i>This Project Depends On ({{ $project->dependencyRecords->count() }})
          </h5>
          <p class="text-muted mb-0 small">Projects that must progress before this one</p>
        </div>
        <div class="card-body">
          @if($project->dependencyRecords->count() > 0)
            <div class="list-group list-group-flush">
              @foreach($project->dependencyRecords as $dep)
                <div class="list-group-item px-0">
                  <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                      <h6 class="mb-1">
                        <a href="{{ route('projects.show', $dep->dependsOnProject) }}">
                          {{ $dep->dependsOnProject->name }}
                        </a>
                        <span class="badge bg-label-secondary ms-2">{{ $dep->dependsOnProject->code }}</span>
                      </h6>
                      <div class="mb-2">
                        <span class="badge bg-label-info">{{ $dep->getDependencyTypeLabel() }}</span>
                        @if($dep->lag_days != 0)
                          <span class="badge bg-label-secondary">
                            {{ $dep->lag_days > 0 ? '+' : '' }}{{ $dep->lag_days }} days lag
                          </span>
                        @endif
                        <span class="badge bg-label-{{ $dep->getStatusBadgeClass() }}">{{ ucfirst($dep->status) }}</span>
                      </div>
                      @if($dep->description)
                        <small class="text-muted">{{ $dep->description }}</small>
                      @endif
                      <div class="mt-2">
                        <small class="text-muted">
                          <i class="ti ti-calendar me-1"></i>
                          Earliest start: {{ $dep->calculateEarliestStartDate()?->format('M d, Y') ?? 'TBD' }}
                        </small>
                      </div>
                    </div>
                    <div class="d-flex gap-1">
                      <button type="button" class="btn btn-sm btn-icon btn-outline-primary"
                              data-bs-toggle="modal" data-bs-target="#editDependencyModal"
                              onclick="editDependency({{ json_encode($dep) }})">
                        <i class="ti ti-edit"></i>
                      </button>
                      <form method="POST" action="{{ route('projects.planning.dependencies.destroy', [$project, $dep]) }}"
                            class="d-inline" onsubmit="return confirm('Remove this dependency?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-icon btn-outline-danger">
                          <i class="ti ti-trash"></i>
                        </button>
                      </form>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <div class="text-center py-4">
              <i class="ti ti-link-off text-muted mb-2" style="font-size: 2rem;"></i>
              <p class="text-muted mb-0">No dependencies defined</p>
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Projects That Depend On This -->
    <div class="col-md-6 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="ti ti-arrow-left me-2"></i>Projects That Depend On This ({{ $project->dependents->count() }})
          </h5>
          <p class="text-muted mb-0 small">Projects waiting for this one to progress</p>
        </div>
        <div class="card-body">
          @if($project->dependents->count() > 0)
            <div class="list-group list-group-flush">
              @foreach($project->dependents as $dependent)
                <div class="list-group-item px-0">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h6 class="mb-1">
                        <a href="{{ route('projects.show', $dependent) }}">
                          {{ $dependent->name }}
                        </a>
                        <span class="badge bg-label-secondary ms-2">{{ $dependent->code }}</span>
                      </h6>
                      <span class="badge bg-label-info">
                        {{ \Modules\Project\Models\ProjectDependency::DEPENDENCY_TYPES[$dependent->pivot->dependency_type] ?? $dependent->pivot->dependency_type }}
                      </span>
                      <span class="badge bg-label-{{ $dependent->pivot->status === 'active' ? 'primary' : ($dependent->pivot->status === 'resolved' ? 'success' : 'danger') }}">
                        {{ ucfirst($dependent->pivot->status) }}
                      </span>
                    </div>
                    <a href="{{ route('projects.planning.dependencies', $dependent) }}" class="btn btn-sm btn-outline-secondary">
                      <i class="ti ti-external-link"></i>
                    </a>
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <div class="text-center py-4">
              <i class="ti ti-link-off text-muted mb-2" style="font-size: 2rem;"></i>
              <p class="text-muted mb-0">No projects depend on this one</p>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Dependency Types Reference -->
  <div class="card">
    <div class="card-header">
      <h5 class="card-title mb-0">
        <i class="ti ti-info-circle me-2"></i>Dependency Types Reference
      </h5>
    </div>
    <div class="card-body">
      <div class="row">
        @foreach(\Modules\Project\Models\ProjectDependency::DEPENDENCY_TYPE_DESCRIPTIONS as $type => $desc)
          <div class="col-md-6 col-lg-3 mb-3">
            <div class="p-3 border rounded">
              <h6 class="mb-2">{{ \Modules\Project\Models\ProjectDependency::DEPENDENCY_TYPES[$type] }}</h6>
              <small class="text-muted">{{ $desc }}</small>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</div>

<!-- Add Dependency Modal -->
<div class="modal fade" id="addDependencyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('projects.planning.dependencies.store', $project) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Add Dependency</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="depends_on_project_id" class="form-label">This project depends on <span class="text-danger">*</span></label>
            <select class="form-select select2" id="depends_on_project_id" name="depends_on_project_id" required>
              <option value="">Select a project...</option>
              @foreach($availableProjects as $p)
                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->code }})</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label for="dependency_type" class="form-label">Dependency Type <span class="text-danger">*</span></label>
            <select class="form-select" id="dependency_type" name="dependency_type" required>
              @foreach(\Modules\Project\Models\ProjectDependency::DEPENDENCY_TYPES as $value => $label)
                <option value="{{ $value }}" {{ $value === 'finish_to_start' ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
            <small class="text-muted" id="dependency_type_help">
              {{ \Modules\Project\Models\ProjectDependency::DEPENDENCY_TYPE_DESCRIPTIONS['finish_to_start'] }}
            </small>
          </div>
          <div class="mb-3">
            <label for="lag_days" class="form-label">Lag Days</label>
            <input type="number" class="form-control" id="lag_days" name="lag_days" value="0">
            <small class="text-muted">Positive = delay after dependency. Negative = lead time before.</small>
          </div>
          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="2"
                      placeholder="Optional notes about this dependency"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Dependency</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Dependency Modal -->
<div class="modal fade" id="editDependencyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="editDependencyForm">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title">Edit Dependency</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Depends On</label>
            <input type="text" class="form-control" id="edit_depends_on_name" readonly>
          </div>
          <div class="mb-3">
            <label for="edit_dependency_type" class="form-label">Dependency Type <span class="text-danger">*</span></label>
            <select class="form-select" id="edit_dependency_type" name="dependency_type" required>
              @foreach(\Modules\Project\Models\ProjectDependency::DEPENDENCY_TYPES as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label for="edit_lag_days" class="form-label">Lag Days</label>
            <input type="number" class="form-control" id="edit_lag_days" name="lag_days">
          </div>
          <div class="mb-3">
            <label for="edit_status" class="form-label">Status <span class="text-danger">*</span></label>
            <select class="form-select" id="edit_status" name="status" required>
              <option value="active">Active</option>
              <option value="resolved">Resolved</option>
              <option value="broken">Broken</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="edit_description" class="form-label">Description</label>
            <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
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
const dependencyDescriptions = @json(\Modules\Project\Models\ProjectDependency::DEPENDENCY_TYPE_DESCRIPTIONS);

document.addEventListener('DOMContentLoaded', function() {
  if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
    jQuery('.select2').select2({
      theme: 'bootstrap-5',
      allowClear: true,
      dropdownParent: jQuery('#addDependencyModal')
    });
  }

  // Update description when dependency type changes
  document.getElementById('dependency_type').addEventListener('change', function() {
    document.getElementById('dependency_type_help').textContent = dependencyDescriptions[this.value] || '';
  });
});

function editDependency(dep) {
  const form = document.getElementById('editDependencyForm');
  form.action = `/projects/{{ $project->id }}/planning/dependencies/${dep.id}`;

  document.getElementById('edit_depends_on_name').value = dep.depends_on_project?.name || 'Unknown Project';
  document.getElementById('edit_dependency_type').value = dep.dependency_type;
  document.getElementById('edit_lag_days').value = dep.lag_days || 0;
  document.getElementById('edit_status').value = dep.status;
  document.getElementById('edit_description').value = dep.description || '';
}
</script>
@endsection
@endsection
