@extends('layouts/layoutMaster')

@section('title', 'Link Projects to Bitbucket')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
<style>
    .project-row, .repo-row {
        transition: background-color 0.2s;
    }
    .project-row:hover, .repo-row:hover {
        background-color: #f8f9fa;
    }
    .project-row.has-repos, .repo-row.has-projects {
        background-color: #d4edda;
    }
    .repo-badge, .project-badge {
        font-size: 0.75rem;
        margin: 2px;
    }
    .linked-repos, .linked-projects {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin-top: 4px;
    }
    .repo-add-container, .project-add-container {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .repo-add-container .select2-container,
    .project-add-container .select2-container {
        flex: 1;
        min-width: 250px;
    }
    .repo-add-container .select2-selection,
    .project-add-container .select2-selection {
        height: 31px !important;
        font-size: 0.875rem;
    }
    .repo-add-container .select2-selection__rendered,
    .project-add-container .select2-selection__rendered {
        line-height: 29px !important;
    }
    .repo-add-container .select2-selection__arrow,
    .project-add-container .select2-selection__arrow {
        height: 29px !important;
    }
    .view-switch {
        display: flex;
        gap: 0;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #dee2e6;
    }
    .view-switch .btn {
        border-radius: 0;
        border: none;
        padding: 8px 20px;
    }
    .view-switch .btn.active {
        background-color: #7367f0;
        color: white;
    }
    .view-switch .btn:not(.active) {
        background-color: #f8f9fa;
        color: #697a8d;
    }
    .sortable {
        cursor: pointer;
        user-select: none;
    }
    .sortable:hover {
        background-color: #e9ecef;
    }
    .sortable::after {
        content: ' ↕';
        opacity: 0.3;
        font-size: 0.75rem;
    }
    .sortable.asc::after {
        content: ' ↑';
        opacity: 1;
    }
    .sortable.desc::after {
        content: ' ↓';
        opacity: 1;
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
                <i class="ti ti-link me-2"></i><span id="viewTitle">Link Projects to Repositories</span>
            </h4>
            <p class="text-muted mb-0" id="viewSubtitle">Link multiple repositories to each project</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <!-- View Switch -->
            <div class="view-switch me-2">
                <button type="button" class="btn active" id="projectsViewBtn" data-view="projects">
                    <i class="ti ti-folder me-1"></i>Projects
                </button>
                <button type="button" class="btn" id="reposViewBtn" data-view="repos">
                    <i class="ti ti-git-branch me-1"></i>Repositories
                </button>
            </div>
            <button type="button" class="btn btn-outline-secondary" id="refreshReposBtn">
                <i class="ti ti-refresh me-1"></i>Refresh
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
                                <small id="linkedLabel">Projects Linked</small>
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
                                <small id="unlinkedLabel">Not Linked</small>
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
                            <input type="text" class="form-control" id="searchInput" placeholder="Search...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="filterStatus">
                            <option value="">All</option>
                            <option value="linked">Linked</option>
                            <option value="unlinked">Not Linked</option>
                        </select>
                    </div>
                    <div class="col-md-3" id="activeFilterContainer">
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

        <!-- Projects View Table -->
        <div class="card" id="projectsView">
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
                                    <div class="repo-add-container">
                                        <select class="form-select form-select-sm repo-select" data-project-id="{{ $project->id }}">
                                            <option value="">Search repository...</option>
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

        <!-- Repositories View Table -->
        <div class="card" id="reposView" style="display: none;">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="sortable" data-sort="name" data-direction="">Repository</th>
                            <th class="sortable" data-sort="bbproject" data-direction="">BB Project</th>
                            <th class="sortable" data-sort="updated" data-direction="">Last Updated</th>
                            <th class="sortable" data-sort="projects" data-direction="">Linked Projects</th>
                            <th style="width: 300px;">Add Project</th>
                        </tr>
                    </thead>
                    <tbody id="reposTable">
                        <!-- Populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

@php
$projectsJson = $projects->map(function($p) {
    return [
        'id' => $p->id,
        'name' => $p->name,
        'code' => $p->code,
        'customer' => $p->customer ? $p->customer->display_name : null,
        'is_active' => $p->is_active,
        'repos' => $p->getAllBitbucketRepoSlugs()
    ];
})->values();
@endphp

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let repositories = [];
    let currentView = 'projects';
    let repoSortField = 'name';
    let repoSortDirection = 'asc';

    // Projects data from server
    const projectsData = @json($projectsJson);

    // Load repositories on page load
    loadRepositories();

    // Setup sortable headers
    document.querySelectorAll('#reposView .sortable').forEach(th => {
        th.addEventListener('click', function() {
            const field = this.dataset.sort;
            if (repoSortField === field) {
                repoSortDirection = repoSortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                repoSortField = field;
                repoSortDirection = 'asc';
            }
            // Update header classes
            document.querySelectorAll('#reposView .sortable').forEach(h => {
                h.classList.remove('asc', 'desc');
            });
            this.classList.add(repoSortDirection);
            populateReposTable();
        });
    });

    // View switch handlers
    document.getElementById('projectsViewBtn')?.addEventListener('click', () => switchView('projects'));
    document.getElementById('reposViewBtn')?.addEventListener('click', () => switchView('repos'));

    function switchView(view) {
        currentView = view;

        // Update button states
        document.getElementById('projectsViewBtn').classList.toggle('active', view === 'projects');
        document.getElementById('reposViewBtn').classList.toggle('active', view === 'repos');

        // Update title and subtitle
        if (view === 'projects') {
            document.getElementById('viewTitle').textContent = 'Link Projects to Repositories';
            document.getElementById('viewSubtitle').textContent = 'Link multiple repositories to each project';
            document.getElementById('linkedLabel').textContent = 'Projects Linked';
            document.getElementById('unlinkedLabel').textContent = 'Not Linked';
            document.getElementById('searchInput').placeholder = 'Search projects...';
            document.getElementById('activeFilterContainer').style.display = '';
        } else {
            document.getElementById('viewTitle').textContent = 'Link Repositories to Projects';
            document.getElementById('viewSubtitle').textContent = 'Link multiple projects to each repository';
            document.getElementById('linkedLabel').textContent = 'Repos Linked';
            document.getElementById('unlinkedLabel').textContent = 'Not Linked';
            document.getElementById('searchInput').placeholder = 'Search repositories...';
            document.getElementById('activeFilterContainer').style.display = 'none';
        }

        // Show/hide tables
        document.getElementById('projectsView').style.display = view === 'projects' ? '' : 'none';
        document.getElementById('reposView').style.display = view === 'repos' ? '' : 'none';

        // Update counts
        updateCounts();

        // Clear filters
        document.getElementById('searchInput').value = '';
        document.getElementById('filterStatus').value = '';
        if (view === 'projects') {
            document.getElementById('filterActive').value = 'active';
        }
        applyFilters();
    }

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
                    populateReposTable();
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
                    btn.innerHTML = '<i class="ti ti-refresh me-1"></i>Refresh';
                }
            });
    }

    function populateRepoSelects() {
        // Sort repositories by name
        const sortedRepos = [...repositories].sort((a, b) =>
            a.name.toLowerCase().localeCompare(b.name.toLowerCase())
        );

        document.querySelectorAll('.repo-select').forEach(select => {
            const projectId = select.dataset.projectId;
            const linkedReposContainer = document.getElementById(`repos-${projectId}`);
            const linkedSlugs = Array.from(linkedReposContainer?.querySelectorAll('[data-repo-slug]') || [])
                .map(el => el.dataset.repoSlug);

            // Destroy existing Select2 if any
            if ($(select).hasClass('select2-hidden-accessible')) {
                $(select).select2('destroy');
            }

            select.innerHTML = '<option value="">Select repository to add...</option>';

            sortedRepos.forEach(repo => {
                // Skip if already linked
                if (linkedSlugs.includes(repo.slug)) return;

                const option = document.createElement('option');
                option.value = repo.slug;
                option.textContent = repo.name;
                select.appendChild(option);
            });

            // Initialize Select2 with search
            $(select).select2({
                placeholder: 'Search repository...',
                allowClear: true,
                width: '100%',
                dropdownParent: $(select).parent()
            });
        });
    }

    function populateReposTable() {
        const tbody = document.getElementById('reposTable');
        if (!tbody) return;

        // Calculate linked projects count for each repo
        const reposWithData = repositories.map(repo => ({
            ...repo,
            linkedCount: projectsData.filter(p => p.repos.includes(repo.slug)).length
        }));

        // Sort repositories based on current sort state
        const sortedRepos = [...reposWithData].sort((a, b) => {
            let comparison = 0;
            if (repoSortField === 'name') {
                comparison = a.name.toLowerCase().localeCompare(b.name.toLowerCase());
            } else if (repoSortField === 'bbproject') {
                const projA = (a.project_name || '').toLowerCase();
                const projB = (b.project_name || '').toLowerCase();
                comparison = projA.localeCompare(projB);
            } else if (repoSortField === 'updated') {
                const dateA = a.updated_on ? new Date(a.updated_on).getTime() : 0;
                const dateB = b.updated_on ? new Date(b.updated_on).getTime() : 0;
                comparison = dateA - dateB;
            } else if (repoSortField === 'projects') {
                comparison = a.linkedCount - b.linkedCount;
            }
            return repoSortDirection === 'asc' ? comparison : -comparison;
        });

        // Sort projects by name for dropdown
        const sortedProjects = [...projectsData].sort((a, b) =>
            a.name.toLowerCase().localeCompare(b.name.toLowerCase())
        );

        tbody.innerHTML = '';

        sortedRepos.forEach(repo => {
            // Find projects linked to this repo
            const linkedProjects = projectsData.filter(p => p.repos.includes(repo.slug));
            const hasProjects = linkedProjects.length > 0;

            const tr = document.createElement('tr');
            tr.className = `repo-row ${hasProjects ? 'has-projects' : ''}`;
            tr.dataset.repoSlug = repo.slug;
            tr.dataset.repoName = repo.name.toLowerCase();
            tr.dataset.isLinked = hasProjects ? 'linked' : 'unlinked';

            // Repository info
            const tdRepo = document.createElement('td');
            tdRepo.innerHTML = `
                <div class="fw-semibold">${repo.name}</div>
                <small class="text-muted">${repo.slug}</small>
            `;
            tr.appendChild(tdRepo);

            // BB Project
            const tdBBProject = document.createElement('td');
            if (repo.project_key && repo.project_name) {
                const projectLink = repo.project_link ?
                    `<a href="${repo.project_link}" target="_blank" class="text-decoration-none">` : '';
                const projectLinkEnd = repo.project_link ? '</a>' : '';
                tdBBProject.innerHTML = `
                    ${projectLink}
                    <span class="badge bg-label-secondary">${repo.project_key}</span>
                    <div class="small text-muted">${repo.project_name}</div>
                    ${projectLinkEnd}
                `;
            } else {
                tdBBProject.innerHTML = '<span class="text-muted">-</span>';
            }
            tr.appendChild(tdBBProject);

            // Last Updated
            const tdDate = document.createElement('td');
            let dateDisplay = '-';
            if (repo.updated_on) {
                const date = new Date(repo.updated_on);
                const now = new Date();
                const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));

                if (diffDays === 0) {
                    dateDisplay = '<span class="badge bg-success">Today</span>';
                } else if (diffDays === 1) {
                    dateDisplay = '<span class="badge bg-success">Yesterday</span>';
                } else if (diffDays <= 7) {
                    dateDisplay = `<span class="badge bg-info">${diffDays} days ago</span>`;
                } else if (diffDays <= 30) {
                    dateDisplay = `<span class="badge bg-warning">${Math.floor(diffDays / 7)} weeks ago</span>`;
                } else {
                    dateDisplay = `<small class="text-muted">${date.toLocaleDateString()}</small>`;
                }
            }
            tdDate.innerHTML = dateDisplay;
            tr.appendChild(tdDate);

            // Linked projects
            const tdProjects = document.createElement('td');
            const projectsContainer = document.createElement('div');
            projectsContainer.className = 'linked-projects';
            projectsContainer.id = `projects-${repo.slug}`;

            if (hasProjects) {
                linkedProjects.forEach(p => {
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-info project-badge';
                    badge.dataset.projectId = p.id;
                    badge.innerHTML = `${p.name} <i class="ti ti-x ms-1 remove-project-from-repo" style="cursor: pointer;" data-project-id="${p.id}" data-repo-slug="${repo.slug}"></i>`;
                    projectsContainer.appendChild(badge);
                });
            } else {
                projectsContainer.innerHTML = '<span class="text-muted small">No projects linked</span>';
            }
            tdProjects.appendChild(projectsContainer);
            tr.appendChild(tdProjects);

            // Add project dropdown
            const tdAdd = document.createElement('td');
            const addContainer = document.createElement('div');
            addContainer.className = 'project-add-container';

            const select = document.createElement('select');
            select.className = 'form-select form-select-sm project-select';
            select.dataset.repoSlug = repo.slug;
            select.innerHTML = '<option value="">Select project to add...</option>';

            sortedProjects.forEach(p => {
                // Skip if already linked
                if (p.repos.includes(repo.slug)) return;

                const option = document.createElement('option');
                option.value = p.id;
                option.textContent = `${p.name} (${p.code})`;
                select.appendChild(option);
            });

            const addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.className = 'btn btn-info btn-sm add-project-btn';
            addBtn.dataset.repoSlug = repo.slug;
            addBtn.innerHTML = '<i class="ti ti-plus"></i>';

            addContainer.appendChild(select);
            addContainer.appendChild(addBtn);
            tdAdd.appendChild(addContainer);
            tr.appendChild(tdAdd);

            tbody.appendChild(tr);
        });

        // Initialize Select2 for project selects
        $('.project-select').each(function() {
            $(this).select2({
                placeholder: 'Search project...',
                allowClear: true,
                width: '100%',
                dropdownParent: $(this).parent()
            });
        });

        // Attach event handlers
        attachRepoViewHandlers();

        // Update counts
        updateCounts();
    }

    function attachRepoViewHandlers() {
        // Add project button click
        document.querySelectorAll('.add-project-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const repoSlug = this.dataset.repoSlug;
                const select = document.querySelector(`select.project-select[data-repo-slug="${repoSlug}"]`);
                const projectId = select?.value;

                if (!projectId) {
                    toastr.warning('Please select a project');
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
                        // Update projectsData
                        const project = projectsData.find(p => p.id == projectId);
                        if (project && !project.repos.includes(repoSlug)) {
                            project.repos.push(repoSlug);
                        }

                        // Add badge to linked projects container
                        const container = document.getElementById(`projects-${repoSlug}`);
                        const noProjectsText = container.querySelector('.text-muted');
                        if (noProjectsText) noProjectsText.remove();

                        const badge = document.createElement('span');
                        badge.className = 'badge bg-info project-badge';
                        badge.dataset.projectId = projectId;
                        badge.innerHTML = `${project?.name || 'Project'} <i class="ti ti-x ms-1 remove-project-from-repo" style="cursor: pointer;" data-project-id="${projectId}" data-repo-slug="${repoSlug}"></i>`;
                        container.appendChild(badge);

                        // Attach remove handler
                        badge.querySelector('.remove-project-from-repo').addEventListener('click', handleRemoveProjectFromRepo);

                        // Update row class
                        const row = document.querySelector(`tr[data-repo-slug="${repoSlug}"]`);
                        row?.classList.add('has-projects');
                        if (row) row.dataset.isLinked = 'linked';

                        // Remove option from select
                        const optionToRemove = select.querySelector(`option[value="${projectId}"]`);
                        if (optionToRemove) optionToRemove.remove();
                        $(select).val('').trigger('change');

                        // Also update the projects view
                        updateProjectsViewForRepo(projectId, repoSlug, 'add');

                        updateCounts();
                        toastr.success(`Linked ${project?.name || 'project'} to ${repoSlug}`);
                    } else {
                        toastr.error(data.message || 'Failed to link project');
                    }
                } catch (error) {
                    toastr.error('Failed to link project');
                } finally {
                    this.disabled = false;
                    this.innerHTML = '<i class="ti ti-plus"></i>';
                }
            });
        });

        // Remove project from repo handler
        document.querySelectorAll('.remove-project-from-repo').forEach(el => {
            el.addEventListener('click', handleRemoveProjectFromRepo);
        });
    }

    function handleRemoveProjectFromRepo() {
        const projectId = this.dataset.projectId;
        const repoSlug = this.dataset.repoSlug;
        const badge = this.closest('.project-badge');
        const project = projectsData.find(p => p.id == projectId);

        if (!confirm(`Unlink ${project?.name || 'project'} from ${repoSlug}?`)) return;

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
                // Update projectsData
                if (project) {
                    project.repos = project.repos.filter(r => r !== repoSlug);
                }

                badge.remove();

                // Check if any projects left
                const container = document.getElementById(`projects-${repoSlug}`);
                if (!container.querySelector('.project-badge')) {
                    container.innerHTML = '<span class="text-muted small">No projects linked</span>';
                    const row = document.querySelector(`tr[data-repo-slug="${repoSlug}"]`);
                    row?.classList.remove('has-projects');
                    if (row) row.dataset.isLinked = 'unlinked';
                }

                // Re-add option to project select
                const select = document.querySelector(`select.project-select[data-repo-slug="${repoSlug}"]`);
                if (select && project) {
                    const option = document.createElement('option');
                    option.value = projectId;
                    option.textContent = `${project.name} (${project.code})`;
                    select.appendChild(option);
                }

                // Also update the projects view
                updateProjectsViewForRepo(projectId, repoSlug, 'remove');

                updateCounts();
                toastr.success('Project unlinked');
            } else {
                toastr.error('Failed to unlink project');
            }
        })
        .catch(() => toastr.error('Failed to unlink project'));
    }

    function updateProjectsViewForRepo(projectId, repoSlug, action) {
        const reposContainer = document.getElementById(`repos-${projectId}`);
        const projectRow = document.querySelector(`tr.project-row[data-project-id="${projectId}"]`);

        if (!reposContainer || !projectRow) return;

        if (action === 'add') {
            // Add badge
            const noReposText = reposContainer.querySelector('.text-muted');
            if (noReposText) noReposText.remove();

            const repo = repositories.find(r => r.slug === repoSlug);
            const badge = document.createElement('span');
            badge.className = 'badge bg-primary repo-badge';
            badge.dataset.repoSlug = repoSlug;
            badge.innerHTML = `${repo?.name || repoSlug} <i class="ti ti-x ms-1 remove-repo" style="cursor: pointer;" data-project-id="${projectId}" data-repo-slug="${repoSlug}"></i>`;
            reposContainer.appendChild(badge);

            badge.querySelector('.remove-repo').addEventListener('click', handleRemoveRepo);
            projectRow.classList.add('has-repos');
            projectRow.dataset.isLinked = 'linked';

            // Remove from dropdown
            const repoSelect = document.querySelector(`select.repo-select[data-project-id="${projectId}"]`);
            const optionToRemove = repoSelect?.querySelector(`option[value="${repoSlug}"]`);
            if (optionToRemove) optionToRemove.remove();
        } else {
            // Remove badge
            const badge = reposContainer.querySelector(`.repo-badge[data-repo-slug="${repoSlug}"]`);
            if (badge) badge.remove();

            if (!reposContainer.querySelector('.repo-badge')) {
                reposContainer.innerHTML = '<span class="text-muted small">No repositories linked</span>';
                projectRow.classList.remove('has-repos');
                projectRow.dataset.isLinked = 'unlinked';
            }

            // Re-add to dropdown
            const repoSelect = document.querySelector(`select.repo-select[data-project-id="${projectId}"]`);
            const repo = repositories.find(r => r.slug === repoSlug);
            if (repoSelect && repo) {
                const option = document.createElement('option');
                option.value = repoSlug;
                option.textContent = repo.name;
                repoSelect.appendChild(option);
            }
        }
    }

    // Add repository button click (projects view)
    document.querySelectorAll('.add-repo-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const projectId = this.dataset.projectId;
            const select = document.querySelector(`select.repo-select[data-project-id="${projectId}"]`);
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
                    // Update projectsData
                    const project = projectsData.find(p => p.id == projectId);
                    if (project && !project.repos.includes(repoSlug)) {
                        project.repos.push(repoSlug);
                    }

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
                    $(select).val('').trigger('change');
                    const optionToRemove = select.querySelector(`option[value="${repoSlug}"]`);
                    if (optionToRemove) optionToRemove.remove();

                    // Also update the repos view
                    updateReposViewForProject(projectId, repoSlug, 'add');

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

    // Remove repository handler (projects view)
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
                // Update projectsData
                const project = projectsData.find(p => p.id == projectId);
                if (project) {
                    project.repos = project.repos.filter(r => r !== repoSlug);
                }

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

                // Also update the repos view
                updateReposViewForProject(projectId, repoSlug, 'remove');

                updateCounts();
                toastr.success('Repository unlinked');
            } else {
                toastr.error('Failed to unlink repository');
            }
        })
        .catch(() => toastr.error('Failed to unlink repository'));
    }

    function updateReposViewForProject(projectId, repoSlug, action) {
        const projectsContainer = document.getElementById(`projects-${repoSlug}`);
        const repoRow = document.querySelector(`tr.repo-row[data-repo-slug="${repoSlug}"]`);

        if (!projectsContainer || !repoRow) return;

        const project = projectsData.find(p => p.id == projectId);

        if (action === 'add') {
            // Add badge
            const noProjectsText = projectsContainer.querySelector('.text-muted');
            if (noProjectsText) noProjectsText.remove();

            const badge = document.createElement('span');
            badge.className = 'badge bg-info project-badge';
            badge.dataset.projectId = projectId;
            badge.innerHTML = `${project?.name || 'Project'} <i class="ti ti-x ms-1 remove-project-from-repo" style="cursor: pointer;" data-project-id="${projectId}" data-repo-slug="${repoSlug}"></i>`;
            projectsContainer.appendChild(badge);

            badge.querySelector('.remove-project-from-repo').addEventListener('click', handleRemoveProjectFromRepo);
            repoRow.classList.add('has-projects');
            repoRow.dataset.isLinked = 'linked';

            // Remove from dropdown
            const projectSelect = document.querySelector(`select.project-select[data-repo-slug="${repoSlug}"]`);
            const optionToRemove = projectSelect?.querySelector(`option[value="${projectId}"]`);
            if (optionToRemove) optionToRemove.remove();
        } else {
            // Remove badge
            const badge = projectsContainer.querySelector(`.project-badge[data-project-id="${projectId}"]`);
            if (badge) badge.remove();

            if (!projectsContainer.querySelector('.project-badge')) {
                projectsContainer.innerHTML = '<span class="text-muted small">No projects linked</span>';
                repoRow.classList.remove('has-projects');
                repoRow.dataset.isLinked = 'unlinked';
            }

            // Re-add to dropdown
            const projectSelect = document.querySelector(`select.project-select[data-repo-slug="${repoSlug}"]`);
            if (projectSelect && project) {
                const option = document.createElement('option');
                option.value = projectId;
                option.textContent = `${project.name} (${project.code})`;
                projectSelect.appendChild(option);
            }
        }
    }

    // Attach remove handlers to existing badges (projects view)
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

        if (currentView === 'projects') {
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
        } else {
            document.querySelectorAll('.repo-row').forEach(row => {
                const name = row.dataset.repoName;
                const slug = row.dataset.repoSlug?.toLowerCase();
                const isLinked = row.dataset.isLinked;

                let show = true;

                if (search && !name.includes(search) && !slug?.includes(search)) {
                    show = false;
                }
                if (status && isLinked !== status) {
                    show = false;
                }

                row.style.display = show ? '' : 'none';
            });
        }
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
        if (currentView === 'projects') {
            const linked = document.querySelectorAll('.project-row.has-repos').length;
            const total = document.querySelectorAll('.project-row').length;
            document.getElementById('linkedCount').textContent = linked;
            document.getElementById('unlinkedCount').textContent = total - linked;
        } else {
            const linked = document.querySelectorAll('.repo-row.has-projects').length;
            const total = document.querySelectorAll('.repo-row').length;
            document.getElementById('linkedCount').textContent = linked;
            document.getElementById('unlinkedCount').textContent = total - linked;
        }
    }
});
</script>
@endsection
