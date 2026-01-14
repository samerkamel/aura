@extends('layouts/layoutMaster')

@section('title', 'Link Contracts to Projects')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('page-style')
<style>
.select2-container--default .select2-dropdown {
    max-width: 400px;
}
.select2-container--default .select2-results__option {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><i class="ti ti-link me-2"></i>Link Contracts to Projects</h5>
                    <small class="text-muted">Quickly link multiple contracts to their respective projects</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('accounting.income.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Back to Contracts
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Filters -->
            <div class="card-body border-bottom">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-select" onchange="this.form.submit()">
                            <option value="">All Customers</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                    @if($customer->projects_count == 1)
                                        (1 project)
                                    @elseif($customer->projects_count > 1)
                                        ({{ $customer->projects_count }} projects)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Year</label>
                        <select name="year" class="form-select" onchange="this.form.submit()">
                            <option value="">All Years</option>
                            @foreach($years as $year)
                                <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Show</label>
                        <select name="unlinked_only" class="form-select" onchange="this.form.submit()">
                            <option value="1" {{ request('unlinked_only', '1') == '1' ? 'selected' : '' }}>Unlinked contracts only</option>
                            <option value="0" {{ request('unlinked_only') == '0' ? 'selected' : '' }}>All contracts</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="{{ route('accounting.income.contracts.link-projects') }}" class="btn btn-outline-secondary w-100">
                            <i class="ti ti-x me-1"></i>Clear
                        </a>
                    </div>
                </form>
            </div>

            @if($contracts->count() > 0)
                <form action="{{ route('accounting.income.contracts.update-project-links') }}" method="POST" id="linkForm">
                    @csrf

                    <!-- Action Bar -->
                    <div class="card-body border-bottom bg-light py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted">{{ $contracts->total() }} contract(s) found</span>
                                <span class="ms-3 text-success" id="changesCount" style="display: none;">
                                    <i class="ti ti-check me-1"></i><span id="changesNumber">0</span> change(s) pending
                                </span>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="autoSelectAll()">
                                    <i class="ti ti-wand me-1"></i>Auto-Select for Single-Project Customers
                                </button>
                                <button type="submit" class="btn btn-success btn-sm" id="saveBtn" disabled>
                                    <i class="ti ti-device-floppy me-1"></i>Save Changes
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Contracts Table -->
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Contract #</th>
                                        <th>Client</th>
                                        <th>Year</th>
                                        <th class="text-end">Amount</th>
                                        <th>Current Projects</th>
                                        <th style="min-width: 350px;">Link to Project</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contracts as $index => $contract)
                                        @php
                                            $customerId = $contract->customer_id;
                                            $suggestion = $customerProjectSuggestions[$customerId] ?? null;
                                            $customerProjects = $projects->get($customerId, collect());
                                            $hasAutoSuggestion = $suggestion && ($suggestion['auto_select'] ?? false);
                                            $isLinked = $contract->projects->isNotEmpty();
                                        @endphp
                                        <tr class="{{ $hasAutoSuggestion && !$isLinked ? 'table-warning' : '' }}">
                                            <td>
                                                <input type="hidden" name="links[{{ $index }}][contract_id]" value="{{ $contract->id }}">
                                                <a href="{{ route('accounting.income.contracts.show', $contract) }}" class="fw-semibold text-primary">
                                                    {{ $contract->contract_number }}
                                                </a>
                                                @if($contract->description)
                                                    <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($contract->description, 30) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <span class="fw-medium">{{ $contract->customer?->display_name ?? $contract->client_name }}</span>
                                                        @if($hasAutoSuggestion && !$isLinked)
                                                            <br><small class="text-success"><i class="ti ti-wand me-1"></i>Auto-select available</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $contract->start_date ? $contract->start_date->format('Y') : '-' }}</td>
                                            <td class="text-end">
                                                <span class="fw-semibold">{{ number_format($contract->total_amount, 0) }} EGP</span>
                                            </td>
                                            <td>
                                                @if($isLinked)
                                                    @foreach($contract->projects->take(2) as $project)
                                                        <span class="badge bg-label-success me-1">{{ $project->code ?? $project->name }}</span>
                                                    @endforeach
                                                    @if($contract->projects->count() > 2)
                                                        <span class="badge bg-label-secondary">+{{ $contract->projects->count() - 2 }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($customerProjects->count() > 0)
                                                    <select name="links[{{ $index }}][project_id]"
                                                            class="form-select form-select-sm project-select select2"
                                                            data-contract-id="{{ $contract->id }}"
                                                            data-customer-id="{{ $customerId }}"
                                                            data-original-value=""
                                                            data-auto-suggestion="{{ $hasAutoSuggestion ? $suggestion['project_id'] : '' }}"
                                                            data-placeholder="Search projects...">
                                                        <option value="">-- Select Project --</option>
                                                        @foreach($customerProjects as $project)
                                                            @php
                                                                $statusLabel = '';
                                                                if (!$project->is_active) {
                                                                    $statusLabel = '[INACTIVE]';
                                                                } elseif ($project->phase === 'closure') {
                                                                    $statusLabel = '[CLOSED]';
                                                                }
                                                                $alreadyLinked = $contract->projects->contains('id', $project->id);
                                                            @endphp
                                                            <option value="{{ $project->id }}"
                                                                {{ $alreadyLinked ? 'disabled' : '' }}
                                                                {{ $hasAutoSuggestion && $suggestion['project_id'] == $project->id ? 'data-suggested="true"' : '' }}
                                                                data-status="{{ $project->is_active ? 'active' : 'inactive' }}"
                                                                data-phase="{{ $project->phase }}">
                                                                {{ $project->code }} - {{ $project->name }} {{ $statusLabel }}
                                                                @if($alreadyLinked)
                                                                    (already linked)
                                                                @elseif($hasAutoSuggestion && $suggestion['project_id'] == $project->id)
                                                                    (suggested)
                                                                @endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <span class="text-muted">
                                                        <i class="ti ti-alert-circle me-1"></i>No projects for this customer
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    @if($contracts->hasPages())
                        <div class="card-footer">
                            {{ $contracts->appends(request()->query())->links() }}
                        </div>
                    @endif
                </form>
            @else
                <div class="card-body text-center py-5">
                    <div class="mb-3">
                        <i class="ti ti-check-all display-6 text-success"></i>
                    </div>
                    <h5 class="mb-2">All contracts are linked!</h5>
                    <p class="text-muted">There are no unlinked contracts matching your filters.</p>
                    <a href="{{ route('accounting.income.contracts.link-projects', ['unlinked_only' => 0]) }}" class="btn btn-outline-primary">
                        Show All Contracts
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
                        <span class="badge bg-label-success p-2 rounded">
                            <i class="ti ti-wand ti-sm"></i>
                        </span>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">Single-Project Customers</small>
                        <h6 class="mb-0">
                            @php
                                $singleProjectCount = collect($customerProjectSuggestions)->filter(fn($s) => $s['auto_select'] ?? false)->count();
                            @endphp
                            {{ $singleProjectCount }} customers
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
                        <span class="badge bg-label-warning p-2 rounded">
                            <i class="ti ti-link-off ti-sm"></i>
                        </span>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">Unlinked Contracts (Total)</small>
                        <h6 class="mb-0">
                            {{ \Modules\Accounting\Models\Contract::whereDoesntHave('projects')->count() }}
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
                        <small class="text-muted">Linked Contracts (Total)</small>
                        <h6 class="mb-0">
                            {{ \Modules\Accounting\Models\Contract::whereHas('projects')->count() }}
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

document.addEventListener('DOMContentLoaded', function() {
    const initSelect2 = function() {
        if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
            setTimeout(initSelect2, 100);
            return;
        }
        initializeProjectSelects();
    };
    initSelect2();
});

function initializeProjectSelects() {
    $('.project-select.select2').each(function() {
        $(this).select2({
            placeholder: 'Search projects...',
            allowClear: true,
            width: '100%'
        });
    });

    $('.project-select.select2').on('change', function() {
        trackChange(this);
    });
}

function trackChange(selectElement) {
    const select = selectElement.jquery ? selectElement[0] : selectElement;
    const $select = $(select);

    const contractId = $select.attr('data-contract-id');
    const originalValue = $select.attr('data-original-value') || '';
    const currentValue = $select.val() || '';

    const $container = $select.next('.select2-container');

    if (currentValue !== originalValue) {
        changedSelects.add(contractId);
        $container.addClass('border border-success border-2 rounded');
    } else {
        changedSelects.delete(contractId);
        $container.removeClass('border border-success border-2 rounded');
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

function autoSelectAll() {
    $('.project-select').each(function() {
        const $select = $(this);
        const autoSuggestion = $select.attr('data-auto-suggestion') || '';
        const originalValue = $select.attr('data-original-value') || '';

        if (!originalValue && autoSuggestion) {
            $select.val(autoSuggestion).trigger('change');
        }
    });
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
