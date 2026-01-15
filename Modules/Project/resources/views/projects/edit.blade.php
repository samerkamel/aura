@extends('layouts/layoutMaster')

@section('title', 'Edit Project')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

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
                <select class="form-select select2-customer @error('customer_id') is-invalid @enderror"
                        id="customer_id" name="customer_id"
                        data-selected="{{ old('customer_id', $project->customer_id) }}">
                  <option value="">Search or select customer...</option>
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

            <!-- Bitbucket Integration -->
            <div class="card bg-light mb-3">
              <div class="card-header py-2">
                <h6 class="mb-0">
                  <i class="ti ti-brand-bitbucket me-2"></i>Bitbucket Repository
                </h6>
              </div>
              <div class="card-body py-3">
                @if($project->hasBitbucketRepository())
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <span class="badge bg-success me-2">Linked</span>
                      <code>{{ $project->bitbucket_repo_slug }}</code>
                      @if($project->bitbucket_last_sync_at)
                        <small class="text-muted ms-2">Last synced: {{ $project->bitbucket_last_sync_at->diffForHumans() }}</small>
                      @endif
                    </div>
                    <div class="btn-group btn-group-sm">
                      <a href="{{ route('projects.bitbucket.commits', $project) }}" class="btn btn-outline-primary">
                        <i class="ti ti-git-commit me-1"></i>View Commits
                      </a>
                      <button type="button" class="btn btn-outline-danger" id="unlinkBitbucketBtn">
                        <i class="ti ti-unlink"></i>
                      </button>
                    </div>
                  </div>
                @else
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <span class="badge bg-secondary me-2">Not Linked</span>
                      <span class="text-muted">Link a Bitbucket repository to track commits</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#linkBitbucketModal">
                      <i class="ti ti-link me-1"></i>Link Repository
                    </button>
                  </div>
                @endif
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label" for="phase">Project Phase</label>
                <select class="form-select @error('phase') is-invalid @enderror" id="phase" name="phase">
                  <option value="">Select phase...</option>
                  @foreach($phases as $value => $label)
                    <option value="{{ $value }}" {{ old('phase', $project->phase) === $value ? 'selected' : '' }}>
                      {{ $label }}
                    </option>
                  @endforeach
                </select>
                @if($suggestedPhase)
                  <div class="form-text">
                    <span class="badge bg-label-{{ $suggestedPhase['confidence'] === 'high' ? 'success' : ($suggestedPhase['confidence'] === 'medium' ? 'warning' : 'secondary') }} me-1">
                      <i class="ti ti-bulb ti-xs me-1"></i>Suggested: {{ $phases[$suggestedPhase['phase']] }}
                    </span>
                    <small class="text-muted">{{ $suggestedPhase['reason'] }}</small>
                    @if($project->phase !== $suggestedPhase['phase'])
                      <button type="button" class="btn btn-link btn-sm p-0 ms-2" onclick="document.getElementById('phase').value='{{ $suggestedPhase['phase'] }}'">
                        Apply
                      </button>
                    @endif
                  </div>
                @endif
                @error('phase')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label" for="health_status">Health Status</label>
                <select class="form-select @error('health_status') is-invalid @enderror" id="health_status" name="health_status">
                  <option value="">Select health status...</option>
                  @foreach($healthStatuses as $value => $label)
                    <option value="{{ $value }}" {{ old('health_status', $project->health_status) === $value ? 'selected' : '' }}>
                      {{ $label }}
                    </option>
                  @endforeach
                </select>
                <div class="form-text">Current health status of the project</div>
                @error('health_status')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

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
                  <input class="form-check-input" type="checkbox" id="followups_disabled"
                         name="followups_disabled" value="1"
                         {{ old('followups_disabled', $project->followups_disabled) ? 'checked' : '' }}>
                  <label class="form-check-label" for="followups_disabled">
                    Disable Follow-ups
                  </label>
                </div>
                <div class="form-text">Exclude this project from the follow-ups tracking page</div>
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

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2 for customer dropdown
    function initCustomerSelect2() {
        if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
            setTimeout(initCustomerSelect2, 50);
            return;
        }

        const $select = $('.select2-customer');
        const preSelected = $select.data('selected');

        $select.select2({
            placeholder: 'Search or select customer...',
            allowClear: true,
            ajax: {
                url: '{{ route("administration.customers.api.index") }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { search: params.term || '' };
                },
                processResults: function(data) {
                    return {
                        results: data.customers ? data.customers.map(function(customer) {
                            return {
                                id: customer.id,
                                text: customer.text,
                                customerData: customer
                            };
                        }) : []
                    };
                },
                cache: true
            },
            minimumInputLength: 0
        });

        // Pre-select customer if provided
        if (preSelected) {
            $.ajax({
                url: '{{ route("administration.customers.api.index") }}',
                dataType: 'json'
            }).then(function(data) {
                if (data.customers) {
                    const customer = data.customers.find(c => c.id == preSelected);
                    if (customer) {
                        const option = new Option(customer.text, customer.id, true, true);
                        $select.append(option).trigger('change');
                    }
                }
            });
        }
    }

    initCustomerSelect2();

    // Bitbucket Repository Linking
    const unlinkBtn = document.getElementById('unlinkBitbucketBtn');
    if (unlinkBtn) {
        unlinkBtn.addEventListener('click', function() {
            if (!confirm('Are you sure you want to unlink this repository?')) return;

            fetch('{{ route("projects.bitbucket.unlink", $project) }}', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => {
                if (response.ok) {
                    location.reload();
                } else {
                    alert('Failed to unlink repository');
                }
            });
        });
    }

    // Load repositories for modal
    const loadReposBtn = document.getElementById('loadRepositoriesBtn');
    const repoSelect = document.getElementById('repo_slug');
    if (loadReposBtn && repoSelect) {
        loadReposBtn.addEventListener('click', loadRepositories);

        // Also load when modal is shown
        const modal = document.getElementById('linkBitbucketModal');
        if (modal) {
            modal.addEventListener('shown.bs.modal', function() {
                if (repoSelect.options.length <= 1) {
                    loadRepositories();
                }
            });
        }
    }

    function loadRepositories() {
        if (!loadReposBtn) return;

        loadReposBtn.disabled = true;
        loadReposBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        fetch('{{ route("projects.bitbucket.repositories") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.repositories) {
                    repoSelect.innerHTML = '<option value="">Select a repository...</option>';
                    data.repositories.forEach(repo => {
                        const option = document.createElement('option');
                        option.value = repo.slug;
                        option.textContent = repo.name + (repo.description ? ' - ' + repo.description.substring(0, 50) : '');
                        repoSelect.appendChild(option);
                    });
                } else {
                    alert(data.message || 'Failed to load repositories');
                }
            })
            .catch(error => {
                alert('Failed to load repositories');
            })
            .finally(() => {
                loadReposBtn.disabled = false;
                loadReposBtn.innerHTML = '<i class="ti ti-refresh"></i>';
            });
    }
});
</script>

<!-- Link Bitbucket Modal -->
<div class="modal fade" id="linkBitbucketModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('projects.bitbucket.link', $project) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ti ti-brand-bitbucket me-2"></i>Link Bitbucket Repository
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="repo_slug">Repository</label>
                        <div class="input-group">
                            <select class="form-select" id="repo_slug" name="repo_slug" required>
                                <option value="">Select a repository...</option>
                            </select>
                            <button type="button" class="btn btn-outline-secondary" id="loadRepositoriesBtn">
                                <i class="ti ti-refresh"></i>
                            </button>
                        </div>
                        <div class="form-text">Select the repository to link to this project</div>
                    </div>
                    <div class="alert alert-info small mb-0">
                        <i class="ti ti-info-circle me-1"></i>
                        Make sure Bitbucket is configured in <a href="{{ route('projects.bitbucket.settings') }}" target="_blank">Bitbucket Settings</a> before linking repositories.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-link me-1"></i>Link Repository
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
