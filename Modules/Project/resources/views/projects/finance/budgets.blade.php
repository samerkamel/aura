@extends('layouts.layoutMaster')

@section('title', 'Budget Management - ' . $project->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Budget Management</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projects</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('projects.finance.index', $project) }}">Finance</a></li>
                    <li class="breadcrumb-item active">Budgets</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBudgetModal">
            <i class="ti ti-plus me-1"></i> Add Budget Category
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-lg rounded me-3">
                            <span class="avatar-initial bg-label-primary rounded"><i class="ti ti-wallet ti-26px"></i></span>
                        </span>
                        <div>
                            <span class="text-muted d-block">Total Planned</span>
                            <h5 class="mb-0">{{ number_format($breakdown['total_planned'], 2) }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-lg rounded me-3">
                            <span class="avatar-initial bg-label-warning rounded"><i class="ti ti-receipt ti-26px"></i></span>
                        </span>
                        <div>
                            <span class="text-muted d-block">Total Spent</span>
                            <h5 class="mb-0">{{ number_format($breakdown['total_actual'], 2) }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-lg rounded me-3">
                            <span class="avatar-initial bg-label-success rounded"><i class="ti ti-coin ti-26px"></i></span>
                        </span>
                        <div>
                            <span class="text-muted d-block">Remaining</span>
                            <h5 class="mb-0 {{ $breakdown['total_remaining'] < 0 ? 'text-danger' : '' }}">
                                {{ number_format($breakdown['total_remaining'], 2) }}
                            </h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-lg rounded me-3">
                            <span class="avatar-initial bg-label-info rounded"><i class="ti ti-percentage ti-26px"></i></span>
                        </span>
                        <div>
                            <span class="text-muted d-block">Utilization</span>
                            <h5 class="mb-0">{{ $breakdown['overall_utilization'] }}%</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Budgets Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Budget Categories</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Description</th>
                        <th class="text-end">Planned</th>
                        <th class="text-end">Actual</th>
                        <th class="text-end">Remaining</th>
                        <th style="width: 200px;">Utilization</th>
                        <th>Period</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($budgets as $budget)
                        <tr>
                            <td>
                                <span class="badge bg-{{ $budget->category_color }}">
                                    {{ $budget->category_label }}
                                </span>
                            </td>
                            <td>{{ $budget->description ?: '-' }}</td>
                            <td class="text-end">{{ number_format($budget->planned_amount, 2) }}</td>
                            <td class="text-end">{{ number_format($budget->actual_amount, 2) }}</td>
                            <td class="text-end {{ $budget->remaining_amount < 0 ? 'text-danger' : '' }}">
                                {{ number_format($budget->remaining_amount, 2) }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                        <div class="progress-bar bg-{{ $budget->status_color }}"
                                             role="progressbar"
                                             style="width: {{ min($budget->utilization_percentage, 100) }}%">
                                        </div>
                                    </div>
                                    <small>{{ $budget->utilization_percentage }}%</small>
                                </div>
                            </td>
                            <td>
                                @if($budget->period_start || $budget->period_end)
                                    <small>
                                        {{ $budget->period_start?->format('M d') ?? '...' }}
                                        -
                                        {{ $budget->period_end?->format('M d, Y') ?? '...' }}
                                    </small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($budget->is_active)
                                    <span class="badge bg-label-success">Active</span>
                                @else
                                    <span class="badge bg-label-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="#"
                                           data-bs-toggle="modal"
                                           data-bs-target="#editBudgetModal"
                                           data-budget="{{ json_encode($budget) }}">
                                            <i class="ti ti-pencil me-1"></i> Edit
                                        </a>
                                        <form action="{{ route('projects.finance.budgets.destroy', [$project, $budget]) }}"
                                              method="POST"
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger"
                                                    onclick="return confirm('Are you sure you want to delete this budget?')">
                                                <i class="ti ti-trash me-1"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="ti ti-wallet-off ti-lg text-muted"></i>
                                <p class="text-muted mb-0 mt-2">No budget categories configured yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Budget Modal -->
    <div class="modal fade" id="addBudgetModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('projects.finance.budgets.store', $project) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Budget Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select" required>
                                <option value="">Select category...</option>
                                @foreach($categories as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description (Optional)</label>
                            <input type="text" name="description" class="form-control" placeholder="Budget description...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Planned Amount</label>
                            <input type="number" name="planned_amount" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Period Start (Optional)</label>
                                <input type="date" name="period_start" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Period End (Optional)</label>
                                <input type="date" name="period_end" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Budget</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Budget Modal -->
    <div class="modal fade" id="editBudgetModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editBudgetForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Budget Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" id="edit_category" class="form-select" required>
                                @foreach($categories as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description (Optional)</label>
                            <input type="text" name="description" id="edit_description" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Planned Amount</label>
                            <input type="number" name="planned_amount" id="edit_planned_amount" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Period Start</label>
                                <input type="date" name="period_start" id="edit_period_start" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Period End</label>
                                <input type="date" name="period_end" id="edit_period_end" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="edit_is_active" class="form-check-input" value="1">
                                <label class="form-check-label" for="edit_is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Budget</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editModal = document.getElementById('editBudgetModal');
            editModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const budget = JSON.parse(button.dataset.budget);

                document.getElementById('editBudgetForm').action =
                    '{{ route("projects.finance.budgets.update", [$project, ":id"]) }}'.replace(':id', budget.id);

                document.getElementById('edit_category').value = budget.category;
                document.getElementById('edit_description').value = budget.description || '';
                document.getElementById('edit_planned_amount').value = budget.planned_amount;
                document.getElementById('edit_period_start').value = budget.period_start ? budget.period_start.split('T')[0] : '';
                document.getElementById('edit_period_end').value = budget.period_end ? budget.period_end.split('T')[0] : '';
                document.getElementById('edit_is_active').checked = budget.is_active;
            });
        });
    </script>
@endsection
