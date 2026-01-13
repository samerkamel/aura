@extends('layouts/layoutMaster')

@section('title', 'Bitbucket Commits - ' . $project->name)

@section('vendor-style')
@vite(['resources/assets/vendor/libs/toastr/toastr.scss'])
<style>
    .commit-card {
        transition: all 0.2s ease-in-out;
        border-left: 3px solid transparent;
    }
    .commit-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.08);
        border-left-color: var(--bs-primary);
    }
    .commit-hash {
        font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
        font-size: 0.8rem;
        background: #f3f3f3;
        padding: 2px 6px;
        border-radius: 4px;
    }
    .commit-stats {
        display: flex;
        gap: 0.5rem;
        font-size: 0.75rem;
    }
    .stat-additions {
        color: #28a745;
    }
    .stat-deletions {
        color: #dc3545;
    }
    .stat-files {
        color: #6c757d;
    }
    .author-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.75rem;
    }
    .commit-message {
        word-break: break-word;
    }
    .commit-message-full {
        white-space: pre-wrap;
        font-family: inherit;
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-top: 0.5rem;
    }
    .chart-container {
        height: 200px;
    }
</style>
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/toastr/toastr.js'])
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projects</a></li>
            <li class="breadcrumb-item"><a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></li>
            <li class="breadcrumb-item active">Bitbucket Commits</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="ti ti-git-commit me-2"></i>Bitbucket Commits
            </h4>
            <p class="text-muted mb-0">
                @if(count($linkedRepos) > 1)
                    Repositories:
                    @foreach($linkedRepos as $repo)
                        <span class="badge bg-primary me-1">{{ $repo }}</span>
                    @endforeach
                @elseif(count($linkedRepos) == 1)
                    Repository: <strong>{{ $linkedRepos[0] }}</strong>
                @else
                    <span class="text-warning">No repositories linked</span>
                @endif
                @if($project->bitbucket_last_sync_at)
                    <span class="ms-2">• Last synced {{ $project->bitbucket_last_sync_at->diffForHumans() }}</span>
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Back to Project
            </a>
            <button type="button" class="btn btn-primary" id="syncBtn">
                <i class="ti ti-refresh me-1"></i>Sync Commits
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="fw-bold mb-1">{{ number_format($stats['total_commits']) }}</h3>
                            <span class="text-muted">Total Commits</span>
                        </div>
                        <div class="avatar avatar-sm bg-label-primary rounded-circle">
                            <span class="avatar-initial"><i class="ti ti-git-commit"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="fw-bold mb-1 text-success">+{{ number_format($stats['total_additions']) }}</h3>
                            <span class="text-muted">Lines Added</span>
                        </div>
                        <div class="avatar avatar-sm bg-label-success rounded-circle">
                            <span class="avatar-initial"><i class="ti ti-plus"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="fw-bold mb-1 text-danger">-{{ number_format($stats['total_deletions']) }}</h3>
                            <span class="text-muted">Lines Removed</span>
                        </div>
                        <div class="avatar avatar-sm bg-label-danger rounded-circle">
                            <span class="avatar-initial"><i class="ti ti-minus"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="fw-bold mb-1">{{ $stats['unique_authors'] }}</h3>
                            <span class="text-muted">Contributors</span>
                        </div>
                        <div class="avatar avatar-sm bg-label-info rounded-circle">
                            <span class="avatar-initial"><i class="ti ti-users"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Commits List -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="row g-3">
                        @if($repositories->count() > 1)
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="repoFilter" onchange="applyFilters()">
                                <option value="">All Repositories</option>
                                @foreach($repositories as $repo)
                                    <option value="{{ $repo }}" {{ request('repo') == $repo ? 'selected' : '' }}>
                                        {{ $repo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                        @else
                        <div class="col-md-4">
                        @endif
                            <select class="form-select form-select-sm" id="authorFilter" onchange="applyFilters()">
                                <option value="">All Authors</option>
                                @foreach($authors as $author)
                                    <option value="{{ $author->author_email }}" {{ request('author') == $author->author_email ? 'selected' : '' }}>
                                        {{ $author->author_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control form-control-sm" id="startDate" value="{{ request('start_date') }}" onchange="applyFilters()" placeholder="From">
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control form-control-sm" id="endDate" value="{{ request('end_date') }}" onchange="applyFilters()" placeholder="To">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="clearFilters()">
                                Clear
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @forelse($commits as $commit)
                        <div class="commit-card p-3 border-bottom" data-bs-toggle="collapse" data-bs-target="#commit-{{ $commit->id }}" style="cursor: pointer;">
                            <div class="d-flex">
                                <div class="author-avatar me-3">
                                    {{ strtoupper(substr($commit->author_name, 0, 2)) }}
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div>
                                            <span class="fw-semibold">{{ $commit->author_name }}</span>
                                            <span class="text-muted ms-2">{{ $commit->committed_at->diffForHumans() }}</span>
                                        </div>
                                        <div class="commit-stats">
                                            <span class="stat-additions" title="Lines added">+{{ $commit->additions }}</span>
                                            <span class="stat-deletions" title="Lines removed">-{{ $commit->deletions }}</span>
                                            <span class="stat-files" title="Files changed">{{ $commit->files_count }} files</span>
                                        </div>
                                    </div>
                                    <div class="commit-message mb-2">
                                        {{ $commit->message_summary }}
                                    </div>
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <code class="commit-hash">{{ $commit->short_hash }}</code>
                                        @if($repositories->count() > 1 && $commit->repo_slug)
                                            <span class="badge bg-label-info">{{ $commit->repo_slug }}</span>
                                        @endif
                                        <small class="text-muted">{{ $commit->committed_at->format('M j, Y g:i A') }}</small>
                                        @if($commit->bitbucket_url)
                                            <a href="{{ $commit->bitbucket_url }}" target="_blank" class="text-muted small" onclick="event.stopPropagation();">
                                                <i class="ti ti-external-link"></i> View on Bitbucket
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Expanded Details -->
                            <div class="collapse" id="commit-{{ $commit->id }}">
                                @if($commit->message != $commit->message_summary)
                                    <div class="commit-message-full mt-3">{{ $commit->message }}</div>
                                @endif

                                @if($commit->files_count > 0)
                                    <div class="mt-3">
                                        <strong class="small">Changed Files:</strong>
                                        <ul class="mb-0 mt-2 small">
                                            @foreach(array_slice($commit->files_changed ?? [], 0, 10) as $file)
                                                <li><code>{{ $file }}</code></li>
                                            @endforeach
                                            @if($commit->files_count > 10)
                                                <li class="text-muted">... and {{ $commit->files_count - 10 }} more files</li>
                                            @endif
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <i class="ti ti-git-commit display-4 text-muted mb-3"></i>
                            <p class="text-muted mb-3">No commits found</p>
                            <button type="button" class="btn btn-primary btn-sm" id="syncEmptyBtn">
                                <i class="ti ti-refresh me-1"></i>Sync Commits
                            </button>
                        </div>
                    @endforelse
                </div>
                @if($commits->hasPages())
                    <div class="card-footer">
                        {{ $commits->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar Charts -->
        <div class="col-lg-4">
            <!-- Contributors Chart -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Top Contributors</h6>
                </div>
                <div class="card-body">
                    @foreach(array_slice($stats['by_author'], 0, 5) as $author)
                        <div class="d-flex align-items-center mb-3">
                            <div class="author-avatar me-3" style="width: 28px; height: 28px; font-size: 0.65rem;">
                                {{ strtoupper(substr($author['name'], 0, 2)) }}
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small">{{ $author['name'] }}</span>
                                    <span class="small fw-semibold">{{ $author['commits'] }} commits</span>
                                </div>
                                @php
                                    $percentage = $stats['total_commits'] > 0 ? ($author['commits'] / $stats['total_commits']) * 100 : 0;
                                @endphp
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar" role="progressbar" style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Activity Chart -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Commit Activity</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="activityChart"></canvas>
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
    // Sync Button
    const syncBtn = document.getElementById('syncBtn');
    const syncEmptyBtn = document.getElementById('syncEmptyBtn');

    function syncCommits(btn) {
        if (!btn) return;

        btn.disabled = true;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Syncing...';

        fetch('{{ route("projects.bitbucket.sync-commits", $project) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message || 'Commits synced successfully');
                setTimeout(() => location.reload(), 1000);
            } else {
                toastr.error(data.message || 'Sync failed');
            }
        })
        .catch(error => {
            toastr.error('An error occurred during sync');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    }

    if (syncBtn) {
        syncBtn.addEventListener('click', function() { syncCommits(this); });
    }
    if (syncEmptyBtn) {
        syncEmptyBtn.addEventListener('click', function() { syncCommits(this); });
    }

    // Activity Chart
    const byDate = @json($stats['by_date']);
    const labels = Object.keys(byDate).slice(-14); // Last 14 days
    const data = labels.map(date => byDate[date] || 0);

    const ctx = document.getElementById('activityChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels.map(d => {
                    const date = new Date(d);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Commits',
                    data: data,
                    backgroundColor: 'rgba(105, 108, 255, 0.8)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
});

function applyFilters() {
    const repoFilter = document.getElementById('repoFilter');
    const repo = repoFilter ? repoFilter.value : '';
    const author = document.getElementById('authorFilter').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    let url = new URL(window.location.href);
    url.searchParams.set('repo', repo);
    url.searchParams.set('author', author);
    url.searchParams.set('start_date', startDate);
    url.searchParams.set('end_date', endDate);

    // Remove empty params
    if (!repo) url.searchParams.delete('repo');
    if (!author) url.searchParams.delete('author');
    if (!startDate) url.searchParams.delete('start_date');
    if (!endDate) url.searchParams.delete('end_date');

    window.location.href = url.toString();
}

function clearFilters() {
    window.location.href = '{{ route("projects.bitbucket.commits", $project) }}';
}
</script>
@endsection
