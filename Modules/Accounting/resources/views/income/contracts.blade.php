@extends('layouts/layoutMaster')

@section('title', 'Contract Management')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Contract Management</h5>
                    <small class="text-muted">Manage client contracts and their income schedules</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('accounting.income.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Back to Income
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-outline-info dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ti ti-upload me-1"></i>Import
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('accounting.income.contracts.mass-entry') }}">
                                    <i class="ti ti-forms me-2"></i>Mass Entry Form
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('accounting.income.contracts.import') }}">
                                    <i class="ti ti-file-spreadsheet me-2"></i>Import from Excel
                                </a>
                            </li>
                        </ul>
                    </div>
                    <a href="{{ route('accounting.income.contracts.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>New Contract
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Filters -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('accounting.income.contracts.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Filter by Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>All Statuses</option>
                                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" name="search" id="search" class="form-control"
                                   value="{{ request('search') }}" placeholder="Client name or contract number">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ti ti-search me-1"></i>Filter
                            </button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('accounting.income.contracts.index') }}" class="btn btn-outline-secondary w-100">
                                <i class="ti ti-x me-1"></i>Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th>Contract #</th>
                            <th>Client / Project</th>
                            <th>Amount</th>
                            <th>Duration</th>
                            <th>Payments</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse($contracts as $contract)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input contract-checkbox" value="{{ $contract->id }}">
                                </td>
                                <td>
                                    <a href="{{ route('accounting.income.contracts.show', $contract) }}" class="fw-bold text-primary">
                                        {{ $contract->contract_number }}
                                    </a>
                                    @if($contract->description)
                                        <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($contract->description, 30) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div>
                                        @if($contract->customer)
                                            <a href="{{ route('administration.customers.show', $contract->customer) }}" class="fw-semibold">
                                                {{ $contract->client_name }}
                                            </a>
                                        @else
                                            <span class="fw-semibold">{{ $contract->client_name }}</span>
                                        @endif
                                        @if($contract->projects->isNotEmpty())
                                            <div class="mt-1">
                                                @foreach($contract->projects->take(2) as $project)
                                                    <a href="{{ route('projects.show', $project) }}" class="badge bg-label-primary me-1" title="{{ $project->name }}">
                                                        <i class="ti ti-folder ti-xs me-1"></i>{{ $project->code ?? \Illuminate\Support\Str::limit($project->name, 15) }}
                                                    </a>
                                                @endforeach
                                                @if($contract->projects->count() > 2)
                                                    <span class="badge bg-label-secondary">+{{ $contract->projects->count() - 2 }} more</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <strong class="text-success">{{ number_format($contract->total_amount, 2) }} EGP</strong>
                                    <br><small class="text-muted">
                                        Paid: {{ number_format($contract->paid_amount, 2) }} EGP
                                    </small>
                                </td>
                                <td>
                                    <div>
                                        <span>{{ $contract->start_date->format('M j, Y') }}</span>
                                        @if($contract->end_date)
                                            <br><small class="text-muted">to {{ $contract->end_date->format('M j, Y') }}</small>
                                        @else
                                            <br><small class="text-muted">Ongoing</small>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-column gap-1">
                                        <span class="badge bg-label-info">{{ $contract->payments->count() }} Total</span>
                                        <span class="badge bg-label-success">{{ $contract->payments->where('status', 'paid')->count() }} Paid</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $contract->status === 'active' ? 'success' : ($contract->status === 'completed' ? 'info' : ($contract->status === 'cancelled' ? 'danger' : 'warning')) }}">
                                        {{ \Illuminate\Support\Str::ucfirst($contract->status) }}
                                    </span>
                                    @if(!$contract->is_active)
                                        <br><small class="text-muted">Inactive</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="{{ route('accounting.income.contracts.show', $contract) }}">
                                                <i class="ti ti-eye me-2"></i>View Details
                                            </a>
                                            <a class="dropdown-item" href="{{ route('accounting.income.contracts.edit', $contract) }}">
                                                <i class="ti ti-edit me-2"></i>Edit Contract
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="{{ route('accounting.income.schedules.create', $contract) }}">
                                                <i class="ti ti-calendar-plus me-2"></i>Add Income Schedule
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <form action="{{ route('accounting.income.contracts.toggle-status', $contract) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="dropdown-item">
                                                    <i class="ti ti-{{ $contract->is_active ? 'pause' : 'play' }} me-2"></i>
                                                    {{ $contract->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                            <div class="dropdown-divider"></div>
                                            <button type="button" class="dropdown-item text-danger"
                                                    onclick="confirmDelete('{{ $contract->id }}', '{{ $contract->contract_number }}', '{{ $contract->client_name }}')">
                                                <i class="ti ti-trash me-2"></i>Delete
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="ti ti-file-text text-muted mb-3" style="font-size: 4rem;"></i>
                                        <h5>No contracts found</h5>
                                        <p class="text-muted">Create your first contract to start managing income schedules</p>
                                        <a href="{{ route('accounting.income.contracts.create') }}" class="btn btn-primary">
                                            <i class="ti ti-plus me-1"></i>Create Contract
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($contracts->hasPages())
                <div class="card-footer">
                    {{ $contracts->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Bulk Actions -->
<div class="row mt-3" id="bulkActions" style="display: none;">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('accounting.income.contracts.bulk-action') }}" method="POST" id="bulkActionForm">
                    @csrf
                    <input type="hidden" name="contracts" id="selectedContracts">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <select name="action" id="bulkAction" class="form-select" required>
                                <option value="">Select Action</option>
                                <option value="activate">Activate Selected</option>
                                <option value="deactivate">Deactivate Selected</option>
                                <option value="set_status">Change Status</option>
                                <option value="delete">Delete Selected</option>
                            </select>
                        </div>
                        <div class="col-md-3" id="statusSelect" style="display: none;">
                            <select name="status" class="form-select">
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">Apply Action</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="clearSelection()">Cancel</button>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted">
                                <span id="selectedCount">0</span> contract(s) selected
                            </span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteContractModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Delete Contract</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="ti ti-alert-triangle text-warning" style="font-size: 3rem;"></i>
                </div>
                <h6 class="text-center">Are you sure you want to delete this contract?</h6>
                <p class="text-center text-muted">
                    Contract: <strong id="deleteContractNumber"></strong><br>
                    Client: <strong id="deleteContractClient"></strong>
                </p>
                <div class="alert alert-warning">
                    <i class="ti ti-info-circle me-2"></i>
                    This action cannot be undone. All associated income schedules will also be deleted.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteContractForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="ti ti-trash me-1"></i>Delete Contract
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const contractCheckboxes = document.querySelectorAll('.contract-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCountSpan = document.getElementById('selectedCount');
    const bulkActionSelect = document.getElementById('bulkAction');
    const statusSelect = document.getElementById('statusSelect');

    // Select all functionality
    selectAllCheckbox.addEventListener('change', function() {
        contractCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActions();
    });

    // Individual checkbox change
    contractCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });

    // Show/hide status select based on action
    bulkActionSelect.addEventListener('change', function() {
        if (this.value === 'set_status') {
            statusSelect.style.display = 'block';
        } else {
            statusSelect.style.display = 'none';
        }
    });

    function updateBulkActions() {
        const selectedCheckboxes = document.querySelectorAll('.contract-checkbox:checked');
        const count = selectedCheckboxes.length;

        selectedCountSpan.textContent = count;

        if (count > 0) {
            bulkActions.style.display = 'block';
            const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);
            document.getElementById('selectedContracts').value = JSON.stringify(selectedIds);
        } else {
            bulkActions.style.display = 'none';
            selectAllCheckbox.checked = false;
        }

        // Update select all checkbox state
        const totalCheckboxes = contractCheckboxes.length;
        if (count === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (count === totalCheckboxes) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
        }
    }

    // Clear selection
    window.clearSelection = function() {
        contractCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        updateBulkActions();
    };

    // Delete confirmation
    window.confirmDelete = function(contractId, contractNumber, clientName) {
        document.getElementById('deleteContractNumber').textContent = contractNumber;
        document.getElementById('deleteContractClient').textContent = clientName;
        document.getElementById('deleteContractForm').action = `/accounting/income/contracts/${contractId}`;

        const deleteModal = new bootstrap.Modal(document.getElementById('deleteContractModal'));
        deleteModal.show();
    };
});
</script>
@endsection