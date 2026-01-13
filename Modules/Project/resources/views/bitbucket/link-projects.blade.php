@extends('layouts/layoutMaster')

@section('title', 'Link Projects to Bitbucket')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
<style>
    .project-row {
        transition: background-color 0.2s;
    }
    .project-row:hover {
        background-color: #f8f9fa;
    }
    .project-row.has-repos {
        background-color: #d4edda;
    }
    .repo-badge {
        font-size: 0.75rem;
        margin: 2px;
    }
    .select2-container {
        min-width: 300px !important;
    }
    .linked-repos {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin-top: 4px;
    }
</style>
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/select2/select2.js'])
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
                <i class="ti ti-link me-2"></i>Link Projects to Repositories
            </h4>
            <p class="text-muted mb-0">Link multiple repositories to each project</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" id="refreshReposBtn">
                <i class="ti ti-refresh me-1"></i>Refresh Repositories
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
                                <h4 class="mb-0" id="linkedCount">{{ $projects->filter(fn($p) => $p->hasBitbucketRepository())->count() }}</h4>
                                <small>With Repositories</small>
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
                                <h4 class="mb-0" id="unlinkedCount">{{ $projects->filter(fn($p) => !$p->hasBitbucketRepository())->count() }}</h4>
                                <small>No Repositories</small>
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
                            <option value="linked">With Repositories</option>
                            <option value="unlinked">No Repositories</option>
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
                            <th>Project</th>
                            <th>Code</th>
                            <th>Linked Repositories</th>
                            <th style="width: 350px;">Add Repository</th>
                        </tr>
                    </thead>
                    <tbody id="projectsTable">
                        @foreach($projects as $project)
                            @php
                                $linkedRepos = $project->bitbucketRepositories;
                                $hasRepos = $project->hasBitbucketRepository();
                            @endphp
                            <tr class="project-row {{ $hasRepos ? 'has-repos' : '' }}"
                                data-project-id="{{ $project->id }}"
                                data-project-name="{{ strtolower($project->name) }}"
                                data-project-code="{{ strtolower($project->code) }}"
                                data-is-linked="{{ $hasRepos ? 'linked' : 'unlinked' }}"
                                data-is-active="{{ $project->is_active ? 'active' : 'inactive' }}">
                                <td>
                                    <div class="fw-semibold">{{ $project->name }}</div>
                                    @if($project->customer)
                                        <small class="text-muted">{{ $project->customer->display_name }}</small>
                                    @endif
                                </td>
                                <td><code>{{ $project->code }}</code></td>
                                <td>
                                    <div class="linked-repos" id="repos-{{ $project->id }}">
                                        @foreach($linkedRepos as $repo)
                                            <span class="badge bg-primary repo-badge" data-repo-slug="{{ $repo->repo_slug }}">
                                                {{ $repo->display_name }}
                                                <i class="ti ti-x ms-1 remove-repo" style="cursor: pointer;"
                                                   data-project-id="{{ $project->id }}"
                                                   data-repo-slug="{{ $repo->repo_slug }}"></i>
                                            </span>
                                        @endforeach
                                        @if($project->bitbucket_repo_slug && !$linkedRepos->contains('repo_slug', $project->bitbucket_repo_slug))
                                            <span class="badge bg-secondary repo-badge" data-repo-slug="{{ $project->bitbucket_repo_slug }}">
                                                {{ $project->bitbucket_repo_slug }} (legacy)
                                                <i class="ti ti-x ms-1 remove-repo" style="cursor: pointer;"
                                                   data-project-id="{{ $project->id }}"
                                                   data-repo-slug="{{ $project->bitbucket_repo_slug }}"></i>
                                            </span>
                                        @endif
                                        @if(!$hasRepos)
                                            <span class="text-muted small">No repositories linked</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <select class="form-select form-select-sm repo-select" data-project-id="{{ $project->id }}">
                                            <option value="">Select repository to add...</option>
                                        </select>
                                        <button type="button" class="btn btn-primary btn-sm add-repo-btn" data-project-id="{{ $project->id }}">
                                            <i class="ti ti-plus"></i>
                                        </button>
                                    </div>
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
            const projectId = select.dataset.projectId;
            const linkedReposContainer = document.getElementById(`repos-${projectId}`);
            const linkedSlugs = Array.from(linkedReposContainer?.querySelectorAll('[data-repo-slug]') || [])
                .map(el => el.dataset.repoSlug);

            select.innerHTML = '<option value="">Select repository to add...</option>';

            repositories.forEach(repo => {
                // Skip if already linked
                if (linkedSlugs.includes(repo.slug)) return;

                const option = document.createElement('option');
                option.value = repo.slug;
                option.textContent = repo.name;
                select.appendChild(option);
            });
        });
    }

    // Add repository button click
    document.querySelectorAll('.add-repo-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const projectId = this.dataset.projectId;
            const select = document.querySelector(`select[data-project-id="${projectId}"]`);
            const repoSlug = select?.value;

            if (!repoSlug) {
                toastr.warning('Please select a repository');
                return;
            }

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

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

                const data = await response.json();

                if (response.ok && data.success) {
                    // Add badge to the linked repos container
                    const container = document.getElementById(`repos-${projectId}`);
                    const noReposText = container.querySelector('.text-muted');
                    if (noReposText) noReposText.remove();

                    const badge = document.createElement('span');
                    badge.className = 'badge bg-primary repo-badge';
                    badge.dataset.repoSlug = repoSlug;
                    badge.innerHTML = `${data.repository?.name || repoSlug} <i class="ti ti-x ms-1 remove-repo" style="cursor: pointer;" data-project-id="${projectId}" data-repo-slug="${repoSlug}"></i>`;
                    container.appendChild(badge);

                    // Attach remove handler
                    badge.querySelector('.remove-repo').addEventListener('click', handleRemoveRepo);

                    // Update row class
                    document.querySelector(`tr[data-project-id="${projectId}"]`).classList.add('has-repos');
                    document.querySelector(`tr[data-project-id="${projectId}"]`).dataset.isLinked = 'linked';

                    // Reset select and remove option
                    select.value = '';
                    const optionToRemove = select.querySelector(`option[value="${repoSlug}"]`);
                    if (optionToRemove) optionToRemove.remove();

                    updateCounts();
                    toastr.success(`Linked ${data.repository?.name || repoSlug}`);
                } else {
                    toastr.error(data.message || 'Failed to link repository');
                }
            } catch (error) {
                toastr.error('Failed to link repository');
            } finally {
                this.disabled = false;
                this.innerHTML = '<i class="ti ti-plus"></i>';
            }
        });
    });

    // Remove repository handler
    function handleRemoveRepo() {
        const projectId = this.dataset.projectId;
        const repoSlug = this.dataset.repoSlug;
        const badge = this.closest('.repo-badge');

        if (!confirm(`Unlink ${repoSlug} from this project?`)) return;

        fetch(`/projects/${projectId}/bitbucket/unlink`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ repo_slug: repoSlug })
        })
        .then(response => {
            if (response.ok) {
                badge.remove();

                // Check if any repos left
                const container = document.getElementById(`repos-${projectId}`);
                if (!container.querySelector('.repo-badge')) {
                    container.innerHTML = '<span class="text-muted small">No repositories linked</span>';
                    document.querySelector(`tr[data-project-id="${projectId}"]`).classList.remove('has-repos');
                    document.querySelector(`tr[data-project-id="${projectId}"]`).dataset.isLinked = 'unlinked';
                }

                // Re-add option to select
                populateRepoSelects();
                updateCounts();
                toastr.success('Repository unlinked');
            } else {
                toastr.error('Failed to unlink repository');
            }
        })
        .catch(() => toastr.error('Failed to unlink repository'));
    }

    // Attach remove handlers to existing badges
    document.querySelectorAll('.remove-repo').forEach(el => {
        el.addEventListener('click', handleRemoveRepo);
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

            if (search && !name.includes(search) && !code.includes(search)) {
                show = false;
            }
            if (status && isLinked !== status) {
                show = false;
            }
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

    applyFilters();

    function updateCounts() {
        const linked = document.querySelectorAll('.project-row.has-repos').length;
        const total = document.querySelectorAll('.project-row').length;
        document.getElementById('linkedCount').textContent = linked;
        document.getElementById('unlinkedCount').textContent = total - linked;
    }
});
</script>
@endsection
