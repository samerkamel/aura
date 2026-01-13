@extends('layouts/layoutMaster')

@section('title', 'Link Projects to Bitbucket')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/toastr/toastr.scss'])
<style>
    .project-row {
        transition: background-color 0.2s;
    }
    .project-row:hover {
        background-color: #f8f9fa;
    }
    .project-row.linked {
        background-color: #d4edda;
    }
    .repo-select {
        min-width: 250px;
    }
    .status-badge {
        min-width: 80px;
    }
</style>
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/toastr/toastr.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projects</a></li>
            <li class="breadcrumb-item"><a href="{{ route('projects.bitbucket.settings') }}">Bitbucket Settings</a></li>
            <li class="breadcrumb-item active">Link Projects</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="ti ti-link me-2"></i>Bulk Link Projects to Repositories
            </h4>
            <p class="text-muted mb-0">Link multiple projects to Bitbucket repositories at once</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" id="refreshReposBtn">
                <i class="ti ti-refresh me-1"></i>Refresh Repositories
            </button>
            <button type="button" class="btn btn-primary" id="saveAllBtn" disabled>
                <i class="ti ti-device-floppy me-1"></i>Save All Links
            </button>
        </div>
    </div>

    @if(!$isConfigured)
        <div class="alert alert-warning">
            <i class="ti ti-alert-triangle me-2"></i>
            Bitbucket is not configured. Please <a href="{{ route('projects.bitbucket.settings') }}">configure your settings</a> first.
        </div>
    @else
        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-label-primary">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0">{{ $projects->count() }}</h4>
                                <small>Total Projects</small>
                            </div>
                            <i class="ti ti-folder display-6 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-label-success">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0" id="linkedCount">{{ $projects->where('bitbucket_repo_slug', '!=', null)->count() }}</h4>
                                <small>Linked</small>
                            </div>
                            <i class="ti ti-link display-6 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-label-warning">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0" id="unlinkedCount">{{ $projects->where('bitbucket_repo_slug', null)->count() }}</h4>
                                <small>Not Linked</small>
                            </div>
                            <i class="ti ti-unlink display-6 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-label-info">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0" id="repoCount">-</h4>
                                <small>Available Repos</small>
                            </div>
                            <i class="ti ti-git-branch display-6 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="card mb-4">
            <div class="card-body py-3">
                <div class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-search"></i></span>
                            <input type="text" class="form-control" id="searchInput" placeholder="Search projects...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="filterStatus">
                            <option value="">All Projects</option>
                            <option value="linked">Linked Only</option>
                            <option value="unlinked">Not Linked Only</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="filterActive">
                            <option value="">All Status</option>
                            <option value="active" selected>Active Only</option>
                            <option value="inactive">Inactive Only</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-secondary w-100" id="clearFilters">
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th>Project</th>
                            <th>Code</th>
                            <th>Status</th>
                            <th>Repository</th>
                            <th style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="projectsTable">
                        @foreach($projects as $project)
                            <tr class="project-row {{ $project->bitbucket_repo_slug ? 'linked' : '' }}"
                                data-project-id="{{ $project->id }}"
                                data-project-name="{{ strtolower($project->name) }}"
                                data-project-code="{{ strtolower($project->code) }}"
                                data-is-linked="{{ $project->bitbucket_repo_slug ? 'linked' : 'unlinked' }}"
                                data-is-active="{{ $project->is_active ? 'active' : 'inactive' }}">
                                <td>
                                    <input type="checkbox" class="form-check-input project-checkbox" value="{{ $project->id }}">
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $project->name }}</div>
                                    @if($project->customer)
                                        <small class="text-muted">{{ $project->customer->display_name }}</small>
                                    @endif
                                </td>
                                <td><code>{{ $project->code }}</code></td>
                                <td>
                                    @if($project->bitbucket_repo_slug)
                                        <span class="badge bg-success status-badge">Linked</span>
                                    @else
                                        <span class="badge bg-secondary status-badge">Not Linked</span>
                                    @endif
                                </td>
                                <td>
                                    <select class="form-select form-select-sm repo-select"
                                            data-project-id="{{ $project->id }}"
                                            data-original="{{ $project->bitbucket_repo_slug }}">
                                        <option value="">-- Select Repository --</option>
                                        @if($project->bitbucket_repo_slug)
                                            <option value="{{ $project->bitbucket_repo_slug }}" selected>
                                                {{ $project->bitbucket_repo_slug }} (current)
                                            </option>
                                        @endif
                                    </select>
                                </td>
                                <td>
                                    @if($project->bitbucket_repo_slug)
                                        <button type="button" class="btn btn-sm btn-outline-danger unlink-btn"
                                                data-project-id="{{ $project->id }}">
                                            <i class="ti ti-unlink"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let repositories = [];
    let pendingChanges = new Map();

    // Load repositories on page load
    loadRepositories();

    // Refresh repositories button
    document.getElementById('refreshReposBtn')?.addEventListener('click', loadRepositories);

    function loadRepositories() {
        const btn = document.getElementById('refreshReposBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Loading...';
        }

        fetch('{{ route("projects.bitbucket.repositories") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.repositories) {
                    repositories = data.repositories;
                    document.getElementById('repoCount').textContent = repositories.length;
                    populateRepoSelects();
                    toastr.success(`Loaded ${repositories.length} repositories`);
                } else {
                    toastr.error(data.message || 'Failed to load repositories');
                }
            })
            .catch(error => {
                toastr.error('Failed to load repositories');
            })
            .finally(() => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ti ti-refresh me-1"></i>Refresh Repositories';
                }
            });
    }

    function populateRepoSelects() {
        document.querySelectorAll('.repo-select').forEach(select => {
            const currentValue = select.dataset.original;
            const projectId = select.dataset.projectId;

            // Clear options except first
            select.innerHTML = '<option value="">-- Select Repository --</option>';

            // Add all repos
            repositories.forEach(repo => {
                const option = document.createElement('option');
                option.value = repo.slug;
                option.textContent = repo.name;
                if (repo.slug === currentValue) {
                    option.selected = true;
                    option.textContent += ' (current)';
                }
                select.appendChild(option);
            });
        });
    }

    // Track changes
    document.querySelectorAll('.repo-select').forEach(select => {
        select.addEventListener('change', function() {
            const projectId = this.dataset.projectId;
            const originalValue = this.dataset.original || '';
            const newValue = this.value;

            if (newValue !== originalValue) {
                pendingChanges.set(projectId, newValue);
                this.classList.add('border-warning');
            } else {
                pendingChanges.delete(projectId);
                this.classList.remove('border-warning');
            }

            updateSaveButton();
        });
    });

    function updateSaveButton() {
        const btn = document.getElementById('saveAllBtn');
        if (btn) {
            btn.disabled = pendingChanges.size === 0;
            btn.innerHTML = pendingChanges.size > 0
                ? `<i class="ti ti-device-floppy me-1"></i>Save ${pendingChanges.size} Change${pendingChanges.size > 1 ? 's' : ''}`
                : '<i class="ti ti-device-floppy me-1"></i>Save All Links';
        }
    }

    // Save all changes
    document.getElementById('saveAllBtn')?.addEventListener('click', async function() {
        if (pendingChanges.size === 0) return;

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

        let success = 0;
        let failed = 0;

        for (const [projectId, repoSlug] of pendingChanges) {
            try {
                const response = await fetch(`/projects/${projectId}/bitbucket/link`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ repo_slug: repoSlug })
                });

                if (response.ok) {
                    success++;
                    // Update UI
                    const row = document.querySelector(`tr[data-project-id="${projectId}"]`);
                    const select = document.querySelector(`select[data-project-id="${projectId}"]`);
                    if (row && select) {
                        if (repoSlug) {
                            row.classList.add('linked');
                            row.dataset.isLinked = 'linked';
                            row.querySelector('.status-badge').className = 'badge bg-success status-badge';
                            row.querySelector('.status-badge').textContent = 'Linked';
                        }
                        select.dataset.original = repoSlug;
                        select.classList.remove('border-warning');
                    }
                } else {
                    failed++;
                }
            } catch (error) {
                failed++;
            }
        }

        pendingChanges.clear();
        updateSaveButton();
        updateCounts();

        if (success > 0) {
            toastr.success(`Successfully linked ${success} project${success > 1 ? 's' : ''}`);
        }
        if (failed > 0) {
            toastr.error(`Failed to link ${failed} project${failed > 1 ? 's' : ''}`);
        }

        this.disabled = false;
        this.innerHTML = '<i class="ti ti-device-floppy me-1"></i>Save All Links';
    });

    // Unlink buttons
    document.querySelectorAll('.unlink-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const projectId = this.dataset.projectId;
            if (!confirm('Are you sure you want to unlink this repository?')) return;

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            try {
                const response = await fetch(`/projects/${projectId}/bitbucket/unlink`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                if (response.ok) {
                    const row = document.querySelector(`tr[data-project-id="${projectId}"]`);
                    const select = document.querySelector(`select[data-project-id="${projectId}"]`);
                    if (row && select) {
                        row.classList.remove('linked');
                        row.dataset.isLinked = 'unlinked';
                        row.querySelector('.status-badge').className = 'badge bg-secondary status-badge';
                        row.querySelector('.status-badge').textContent = 'Not Linked';
                        select.value = '';
                        select.dataset.original = '';
                        this.remove();
                    }
                    updateCounts();
                    toastr.success('Repository unlinked');
                } else {
                    toastr.error('Failed to unlink repository');
                    this.disabled = false;
                    this.innerHTML = '<i class="ti ti-unlink"></i>';
                }
            } catch (error) {
                toastr.error('Failed to unlink repository');
                this.disabled = false;
                this.innerHTML = '<i class="ti ti-unlink"></i>';
            }
        });
    });

    // Search and filter
    const searchInput = document.getElementById('searchInput');
    const filterStatus = document.getElementById('filterStatus');
    const filterActive = document.getElementById('filterActive');

    function applyFilters() {
        const search = searchInput.value.toLowerCase();
        const status = filterStatus.value;
        const active = filterActive.value;

        document.querySelectorAll('.project-row').forEach(row => {
            const name = row.dataset.projectName;
            const code = row.dataset.projectCode;
            const isLinked = row.dataset.isLinked;
            const isActive = row.dataset.isActive;

            let show = true;

            // Search filter
            if (search && !name.includes(search) && !code.includes(search)) {
                show = false;
            }

            // Status filter
            if (status && isLinked !== status) {
                show = false;
            }

            // Active filter
            if (active && isActive !== active) {
                show = false;
            }

            row.style.display = show ? '' : 'none';
        });
    }

    searchInput?.addEventListener('input', applyFilters);
    filterStatus?.addEventListener('change', applyFilters);
    filterActive?.addEventListener('change', applyFilters);

    document.getElementById('clearFilters')?.addEventListener('click', function() {
        searchInput.value = '';
        filterStatus.value = '';
        filterActive.value = '';
        applyFilters();
    });

    // Apply default filter (active only)
    applyFilters();

    // Select all checkbox
    document.getElementById('selectAll')?.addEventListener('change', function() {
        const isChecked = this.checked;
        document.querySelectorAll('.project-row:not([style*="display: none"]) .project-checkbox').forEach(cb => {
            cb.checked = isChecked;
        });
    });

    function updateCounts() {
        const linked = document.querySelectorAll('.project-row.linked').length;
        const unlinked = document.querySelectorAll('.project-row:not(.linked)').length;
        document.getElementById('linkedCount').textContent = linked;
        document.getElementById('unlinkedCount').textContent = unlinked;
    }
});
</script>
@endsection
