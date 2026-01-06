@extends('layouts/layoutMaster')

@section('title', 'Link Projects to Customers')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><i class="ti ti-link me-2"></i>Link Projects to Customers</h5>
                    <small class="text-muted">Quickly link multiple projects to their respective customers</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Back to Projects
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Filters -->
            <div class="card-body border-bottom">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Project name or code...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Phase</label>
                        <select name="phase" class="form-select">
                            <option value="active" {{ $phaseFilter === 'active' ? 'selected' : '' }}>Active Projects</option>
                            <option value="all" {{ $phaseFilter === 'all' ? 'selected' : '' }}>All Phases</option>
                            <option value="closure" {{ $phaseFilter === 'closure' ? 'selected' : '' }}>Closure Only</option>
                            @foreach($phases as $value => $label)
                                @if($value !== 'closure')
                                    <option value="{{ $value }}" {{ $phaseFilter === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Current Customer</label>
                        <select name="customer_id" class="form-select">
                            <option value="">All</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Show</label>
                        <select name="unlinked_only" class="form-select">
                            <option value="1" {{ request('unlinked_only', '1') == '1' ? 'selected' : '' }}>Unlinked only</option>
                            <option value="0" {{ request('unlinked_only') == '0' ? 'selected' : '' }}>All projects</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-filter me-1"></i>Filter
                        </button>
                        <a href="{{ route('projects.link-customers') }}" class="btn btn-outline-secondary">
                            <i class="ti ti-x me-1"></i>Clear
                        </a>
                    </div>
                </form>
            </div>

            @if($projects->count() > 0)
                <form action="{{ route('projects.update-customer-links') }}" method="POST" id="linkForm">
                    @csrf

                    <!-- Action Bar -->
                    <div class="card-body border-bottom bg-light py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted">{{ $projects->total() }} project(s) found</span>
                                <span class="ms-3 text-success" id="changesCount" style="display: none;">
                                    <i class="ti ti-check me-1"></i><span id="changesNumber">0</span> change(s) pending
                                </span>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success btn-sm" id="saveBtn" disabled>
                                    <i class="ti ti-device-floppy me-1"></i>Save Changes
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Projects Table -->
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Code</th>
                                        <th>Project Name</th>
                                        <th>Phase</th>
                                        <th>Current Customer</th>
                                        <th style="min-width: 280px;">Link to Customer</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($projects as $index => $project)
                                        <tr>
                                            <td>
                                                <input type="hidden" name="links[{{ $index }}][project_id]" value="{{ $project->id }}">
                                                <a href="{{ route('projects.show', $project) }}" class="badge bg-label-primary">
                                                    {{ $project->code }}
                                                </a>
                                            </td>
                                            <td>
                                                <a href="{{ route('projects.show', $project) }}" class="fw-semibold text-body">
                                                    {{ \Illuminate\Support\Str::limit($project->name, 40) }}
                                                </a>
                                            </td>
                                            <td>
                                                @if($project->phase)
                                                    <span class="badge bg-label-info">
                                                        {{ $phases[$project->phase] ?? $project->phase }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($project->customer)
                                                    <span class="text-muted">
                                                        {{ $project->customer->display_name }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-label-warning">
                                                        <i class="ti ti-alert-circle me-1"></i>Not linked
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <select name="links[{{ $index }}][customer_id]"
                                                        class="form-select form-select-sm customer-select"
                                                        data-project-id="{{ $project->id }}"
                                                        data-original-value="{{ $project->customer_id }}"
                                                        onchange="trackChange(this)">
                                                    <option value="">-- Select Customer --</option>
                                                    @foreach($customers as $customer)
                                                        <option value="{{ $customer->id }}"
                                                            {{ $project->customer_id == $customer->id ? 'selected' : '' }}>
                                                            {{ $customer->display_name }}
                                                            @if($customer->projects_count > 0)
                                                                ({{ $customer->projects_count }} project{{ $customer->projects_count > 1 ? 's' : '' }})
                                                            @endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    @if($projects->hasPages())
                        <div class="card-footer">
                            {{ $projects->appends(request()->query())->links() }}
                        </div>
                    @endif
                </form>
            @else
                <div class="card-body text-center py-5">
                    <div class="mb-3">
                        <i class="ti ti-check-all display-6 text-success"></i>
                    </div>
                    <h5 class="mb-2">All projects are linked!</h5>
                    <p class="text-muted">There are no unlinked projects matching your filters.</p>
                    <a href="{{ route('projects.link-customers', ['unlinked_only' => 0]) }}" class="btn btn-outline-primary">
                        Show All Projects
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mt-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-label-warning p-2 rounded">
                            <i class="ti ti-link-off ti-sm"></i>
                        </span>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">Unlinked Projects</small>
                        <h6 class="mb-0">
                            {{ \Modules\Project\Models\Project::whereNull('customer_id')->count() }}
                        </h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-label-primary p-2 rounded">
                            <i class="ti ti-link ti-sm"></i>
                        </span>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">Linked Projects</small>
                        <h6 class="mb-0">
                            {{ \Modules\Project\Models\Project::whereNotNull('customer_id')->count() }}
                        </h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-label-success p-2 rounded">
                            <i class="ti ti-users ti-sm"></i>
                        </span>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">Total Customers</small>
                        <h6 class="mb-0">
                            {{ $customers->count() }}
                        </h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
let changedSelects = new Set();

function trackChange(select) {
    const projectId = select.dataset.projectId;
    const originalValue = select.dataset.originalValue || '';
    const currentValue = select.value;

    if (currentValue !== originalValue) {
        changedSelects.add(projectId);
        select.classList.add('border-success');
        select.classList.add('border-2');
    } else {
        changedSelects.delete(projectId);
        select.classList.remove('border-success');
        select.classList.remove('border-2');
    }

    updateUI();
}

function updateUI() {
    const count = changedSelects.size;
    const changesCountEl = document.getElementById('changesCount');
    const changesNumberEl = document.getElementById('changesNumber');
    const saveBtn = document.getElementById('saveBtn');

    if (count > 0) {
        changesCountEl.style.display = 'inline';
        changesNumberEl.textContent = count;
        saveBtn.disabled = false;
    } else {
        changesCountEl.style.display = 'none';
        saveBtn.disabled = true;
    }
}

// Keyboard shortcut: Ctrl/Cmd + S to save
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        if (changedSelects.size > 0) {
            document.getElementById('linkForm').submit();
        }
    }
});
</script>
@endsection
