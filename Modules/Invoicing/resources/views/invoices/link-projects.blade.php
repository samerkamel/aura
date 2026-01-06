@extends('layouts/layoutMaster')

@section('title', 'Link Invoices to Projects')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
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
                    <h5 class="mb-0"><i class="ti ti-link me-2"></i>Link Invoices to Projects</h5>
                    <small class="text-muted">Quickly link multiple invoices to their respective projects</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('invoicing.invoices.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Back to Invoices
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
                        <label class="form-label">From Date</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Show</label>
                        <select name="unlinked_only" class="form-select" onchange="this.form.submit()">
                            <option value="1" {{ request('unlinked_only', '1') == '1' ? 'selected' : '' }}>Unlinked invoices only</option>
                            <option value="0" {{ request('unlinked_only') == '0' ? 'selected' : '' }}>All invoices</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="{{ route('invoicing.invoices.link-projects') }}" class="btn btn-outline-secondary w-100">
                            <i class="ti ti-x me-1"></i>Clear
                        </a>
                    </div>
                </form>
            </div>

            @if($invoices->count() > 0)
                <form action="{{ route('invoicing.invoices.update-project-links') }}" method="POST" id="linkForm">
                    @csrf

                    <!-- Action Bar -->
                    <div class="card-body border-bottom bg-light py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted">{{ $invoices->total() }} invoice(s) found</span>
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

                    <!-- Invoice Table -->
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th class="text-end">Amount</th>
                                        <th>Status</th>
                                        <th style="min-width: 350px;">Link to Project</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoices as $index => $invoice)
                                        @php
                                            $customerId = $invoice->customer_id;
                                            $suggestion = $customerProjectSuggestions[$customerId] ?? null;
                                            $customerProjects = $projects->get($customerId, collect());
                                            $hasAutoSuggestion = $suggestion && ($suggestion['auto_select'] ?? false);
                                        @endphp
                                        <tr class="{{ $hasAutoSuggestion && !$invoice->project_id ? 'table-warning' : '' }}">
                                            <td>
                                                <input type="hidden" name="links[{{ $index }}][invoice_id]" value="{{ $invoice->id }}">
                                                <a href="{{ route('invoicing.invoices.show', $invoice) }}" class="fw-semibold text-primary">
                                                    {{ $invoice->invoice_number }}
                                                </a>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-sm me-2">
                                                        <span class="avatar-initial rounded-circle bg-label-primary">
                                                            {{ mb_substr($invoice->customer->display_name, 0, 2) }}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <span class="fw-medium">{{ $invoice->customer->display_name }}</span>
                                                        @if($hasAutoSuggestion && !$invoice->project_id)
                                                            <br><small class="text-success"><i class="ti ti-wand me-1"></i>Auto-select available</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $invoice->invoice_date->format('M j, Y') }}</td>
                                            <td class="text-end">
                                                <span class="fw-semibold">{{ number_format($invoice->total_amount, 2) }} EGP</span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $invoice->status_badge_class }}">
                                                    {{ $invoice->status_display }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($customerProjects->count() > 0)
                                                    <select name="links[{{ $index }}][project_id]"
                                                            class="form-select form-select-sm project-select select2"
                                                            data-invoice-id="{{ $invoice->id }}"
                                                            data-customer-id="{{ $customerId }}"
                                                            data-original-value="{{ $invoice->project_id }}"
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
                                                            @endphp
                                                            <option value="{{ $project->id }}"
                                                                {{ $invoice->project_id == $project->id ? 'selected' : '' }}
                                                                {{ $hasAutoSuggestion && $suggestion['project_id'] == $project->id ? 'data-suggested="true"' : '' }}
                                                                data-status="{{ $project->is_active ? 'active' : 'inactive' }}"
                                                                data-phase="{{ $project->phase }}">
                                                                {{ $project->code }} - {{ $project->name }} {{ $statusLabel }}
                                                                @if($hasAutoSuggestion && $suggestion['project_id'] == $project->id)
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
                    @if($invoices->hasPages())
                        <div class="card-footer">
                            {{ $invoices->appends(request()->query())->links() }}
                        </div>
                    @endif
                </form>
            @else
                <div class="card-body text-center py-5">
                    <div class="mb-3">
                        <i class="ti ti-check-all display-6 text-success"></i>
                    </div>
                    <h5 class="mb-2">All invoices are linked!</h5>
                    <p class="text-muted">There are no unlinked invoices matching your filters.</p>
                    <a href="{{ route('invoicing.invoices.link-projects', ['unlinked_only' => 0]) }}" class="btn btn-outline-primary">
                        Show All Invoices
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
                        <small class="text-muted">Unlinked Invoices (Total)</small>
                        <h6 class="mb-0">
                            {{ \Modules\Invoicing\Models\Invoice::whereNull('project_id')->count() }}
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
                        <small class="text-muted">Linked Invoices (Total)</small>
                        <h6 class="mb-0">
                            {{ \Modules\Invoicing\Models\Invoice::whereNotNull('project_id')->count() }}
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

$(document).ready(function() {
    // Initialize Select2 for all project selects
    $('.project-select.select2').each(function() {
        $(this).select2({
            placeholder: 'Search projects...',
            allowClear: true,
            width: '100%',
            dropdownAutoWidth: true
        });
    });

    // Handle Select2 change event
    $('.project-select.select2').on('change', function() {
        trackChange(this);
    });
});

function trackChange(select) {
    const invoiceId = select.dataset.invoiceId;
    const originalValue = select.dataset.originalValue || '';
    const currentValue = select.value;

    // Get the Select2 container for visual feedback
    const $select = $(select);
    const $container = $select.next('.select2-container');

    if (currentValue !== originalValue) {
        changedSelects.add(invoiceId);
        $container.addClass('border border-success border-2 rounded');
    } else {
        changedSelects.delete(invoiceId);
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
    document.querySelectorAll('.project-select').forEach(select => {
        const autoSuggestion = select.dataset.autoSuggestion;
        const originalValue = select.dataset.originalValue || '';

        // Only auto-select if no project is currently selected and we have a suggestion
        if (!originalValue && autoSuggestion) {
            // Use Select2's API to set value
            $(select).val(autoSuggestion).trigger('change');
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
