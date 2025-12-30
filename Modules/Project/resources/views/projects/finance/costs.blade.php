@use('Illuminate\Support\Str')
@extends('layouts.layoutMaster')

@section('title', 'Cost Management - ' . $project->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Cost Management</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projects</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('projects.finance.index', $project) }}">Finance</a></li>
                    <li class="breadcrumb-item active">Costs</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#generateLaborModal">
                <i class="ti ti-clock me-1"></i> Generate from Worklogs
            </button>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCostModal">
                <i class="ti ti-plus me-1"></i> Record Cost
            </button>
        </div>
    </div>

    <!-- Summary -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Cost by Type</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($breakdown['breakdown'] as $item)
                            <div class="col-6 col-md-4 col-lg-2 mb-3">
                                <div class="d-flex flex-column align-items-center text-center">
                                    <span class="avatar avatar-lg bg-label-{{ $item['color'] }} rounded mb-2">
                                        <i class="ti ti-{{ $item['type'] === 'labor' ? 'users' : ($item['type'] === 'expense' ? 'receipt' : ($item['type'] === 'contractor' ? 'briefcase' : ($item['type'] === 'infrastructure' ? 'server' : ($item['type'] === 'software' ? 'app-window' : 'dots')))) }} ti-26px"></i>
                                    </span>
                                    <small class="text-muted">{{ $item['label'] }}</small>
                                    <strong>{{ number_format($item['amount'], 2) }}</strong>
                                    <small class="text-muted">{{ $item['percentage'] }}%</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="text-center">
                        <h2 class="mb-1">{{ number_format($breakdown['total'], 2) }}</h2>
                        <span class="text-muted">Total Costs</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Cost Type</label>
                    <select name="cost_type" class="form-select">
                        <option value="">All Types</option>
                        @foreach($costTypes as $key => $label)
                            <option value="{{ $key }}" {{ request('cost_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Billable</label>
                    <select name="billable" class="form-select">
                        <option value="">All</option>
                        <option value="yes" {{ request('billable') === 'yes' ? 'selected' : '' }}>Billable</option>
                        <option value="no" {{ request('billable') === 'no' ? 'selected' : '' }}>Non-billable</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="{{ route('projects.finance.costs', $project) }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Costs Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Employee/Vendor</th>
                        <th>Budget</th>
                        <th class="text-end">Hours</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center">Billable</th>
                        <th class="text-center" style="width: 80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($costs as $cost)
                        <tr>
                            <td>{{ $cost->cost_date->format('M d, Y') }}</td>
                            <td>
                                <span class="badge bg-{{ $cost->cost_type_color }}">
                                    {{ $cost->cost_type_label }}
                                </span>
                                @if($cost->is_auto_generated)
                                    <span class="badge bg-label-secondary ms-1" title="Auto-generated">
                                        <i class="ti ti-robot ti-xs"></i>
                                    </span>
                                @endif
                            </td>
                            <td>
                                {{ $cost->description }}
                                @if($cost->notes)
                                    <br><small class="text-muted">{{ Str::limit($cost->notes, 50) }}</small>
                                @endif
                            </td>
                            <td>
                                @if($cost->employee)
                                    {{ $cost->employee->full_name }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($cost->budget)
                                    <span class="badge bg-label-{{ $cost->budget->category_color }}">
                                        {{ $cost->budget->category_label }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($cost->hours)
                                    {{ number_format($cost->hours, 1) }}h
                                    @if($cost->hourly_rate)
                                        <br><small class="text-muted">@{{ $cost->hourly_rate }}/h</small>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end fw-medium">{{ number_format($cost->amount, 2) }}</td>
                            <td class="text-center">
                                @if($cost->is_billable)
                                    <span class="badge bg-label-success"><i class="ti ti-check"></i></span>
                                @else
                                    <span class="badge bg-label-secondary"><i class="ti ti-x"></i></span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if(!$cost->is_auto_generated)
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="#"
                                               data-bs-toggle="modal"
                                               data-bs-target="#editCostModal"
                                               data-cost="{{ json_encode($cost) }}">
                                                <i class="ti ti-pencil me-1"></i> Edit
                                            </a>
                                            <form action="{{ route('projects.finance.costs.destroy', [$project, $cost]) }}"
                                                  method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger"
                                                        onclick="return confirm('Delete this cost entry?')">
                                                    <i class="ti ti-trash me-1"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="ti ti-receipt-off ti-lg text-muted"></i>
                                <p class="text-muted mb-0 mt-2">No costs recorded yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($costs->hasPages())
            <div class="card-footer">
                {{ $costs->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <!-- Add Cost Modal -->
    <div class="modal fade" id="addCostModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('projects.finance.costs.store', $project) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Record Cost</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cost Type</label>
                                <select name="cost_type" class="form-select" required>
                                    @foreach($costTypes as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="cost_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control" required placeholder="Cost description...">
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" name="amount" class="form-control" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Hours (Optional)</label>
                                <input type="number" name="hours" class="form-control" step="0.01" min="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Hourly Rate (Optional)</label>
                                <input type="number" name="hourly_rate" class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Budget Category (Optional)</label>
                                <select name="project_budget_id" class="form-select">
                                    <option value="">None</option>
                                    @foreach($budgets as $budget)
                                        <option value="{{ $budget->id }}">{{ $budget->category_label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Employee (Optional)</label>
                                <select name="employee_id" class="form-select">
                                    <option value="">None</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes (Optional)</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="hidden" name="is_billable" value="0">
                                <input type="checkbox" name="is_billable" class="form-check-input" value="1" checked>
                                <label class="form-check-label">This is a billable cost</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Record Cost</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Cost Modal -->
    <div class="modal fade" id="editCostModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editCostForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Cost</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cost Type</label>
                                <select name="cost_type" id="edit_cost_type" class="form-select" required>
                                    @foreach($costTypes as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="cost_date" id="edit_cost_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" id="edit_cost_description" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" name="amount" id="edit_cost_amount" class="form-control" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Hours</label>
                                <input type="number" name="hours" id="edit_cost_hours" class="form-control" step="0.01" min="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Hourly Rate</label>
                                <input type="number" name="hourly_rate" id="edit_cost_hourly_rate" class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Budget Category</label>
                                <select name="project_budget_id" id="edit_cost_budget" class="form-select">
                                    <option value="">None</option>
                                    @foreach($budgets as $budget)
                                        <option value="{{ $budget->id }}">{{ $budget->category_label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Employee</label>
                                <select name="employee_id" id="edit_cost_employee" class="form-select">
                                    <option value="">None</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" id="edit_cost_notes" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="hidden" name="is_billable" value="0">
                                <input type="checkbox" name="is_billable" id="edit_cost_billable" class="form-check-input" value="1">
                                <label class="form-check-label">This is a billable cost</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Cost</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Generate Labor Costs Modal -->
    <div class="modal fade" id="generateLaborModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('projects.finance.costs.generate-labor', $project) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Generate Labor Costs from Worklogs</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">
                            This will generate cost entries from worklogs that haven't been processed yet.
                            Costs will be calculated using employee hourly rates or the project default rate.
                        </p>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">From Date</label>
                                <input type="date" name="start_date" class="form-control"
                                       value="{{ now()->startOfMonth()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">To Date</label>
                                <input type="date" name="end_date" class="form-control"
                                       value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Generate Costs</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editModal = document.getElementById('editCostModal');
            editModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const cost = JSON.parse(button.dataset.cost);

                document.getElementById('editCostForm').action =
                    '{{ route("projects.finance.costs.update", [$project, ":id"]) }}'.replace(':id', cost.id);

                document.getElementById('edit_cost_type').value = cost.cost_type;
                document.getElementById('edit_cost_date').value = cost.cost_date.split('T')[0];
                document.getElementById('edit_cost_description').value = cost.description;
                document.getElementById('edit_cost_amount').value = cost.amount;
                document.getElementById('edit_cost_hours').value = cost.hours || '';
                document.getElementById('edit_cost_hourly_rate').value = cost.hourly_rate || '';
                document.getElementById('edit_cost_budget').value = cost.project_budget_id || '';
                document.getElementById('edit_cost_employee').value = cost.employee_id || '';
                document.getElementById('edit_cost_notes').value = cost.notes || '';
                document.getElementById('edit_cost_billable').checked = cost.is_billable;
            });
        });
    </script>
@endsection
