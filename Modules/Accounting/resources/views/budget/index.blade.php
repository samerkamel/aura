@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3">Budget Planning</h1>
            <p class="text-muted">Manage and plan budgets for different fiscal years</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('accounting.budgets.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create New Budget
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Budgets Table Card -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Available Budgets</h5>
        </div>
        <div class="card-body">
            @if($budgets->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Last Modified</th>
                            <th>Progress</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($budgets as $budget)
                        <tr>
                            <td>
                                <strong>{{ $budget->year }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-{{ $budget->status === 'finalized' ? 'success' : 'warning' }}">
                                    {{ ucfirst($budget->status) }}
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">{{ $budget->created_at->format('M d, Y') }}</small>
                            </td>
                            <td>
                                <small class="text-muted">{{ $budget->updated_at->format('M d, Y') }}</small>
                            </td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar" role="progressbar"
                                         style="width: {{ $budget->getCompletionPercentage() }}%"
                                         aria-valuenow="{{ $budget->getCompletionPercentage() }}"
                                         aria-valuemin="0" aria-valuemax="100">
                                        {{ $budget->getCompletionPercentage() }}%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('accounting.budgets.growth', $budget->id) }}"
                                       class="btn btn-outline-primary" title="Edit Budget">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <button type="button" class="btn btn-outline-danger"
                                            data-bs-toggle="modal" data-bs-target="#deleteModal"
                                            data-budget-id="{{ $budget->id }}"
                                            data-budget-year="{{ $budget->year }}"
                                            title="Delete Budget">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $budgets->links() }}
            </div>
            @else
            <div class="text-center py-5">
                <p class="text-muted mb-3">No budgets found. Create one to get started.</p>
                <a href="{{ route('accounting.budgets.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create First Budget
                </a>
            </div>
            @endif
        </div>
    </div>

    <!-- Empty State Info Card -->
    @if($budgets->count() === 0)
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-lightbulb"></i> Getting Started</h5>
        </div>
        <div class="card-body">
            <p>The Budget Planning system guides you through a 9-tab process:</p>
            <ol>
                <li><strong>Growth Tab</strong> - Project growth using historical trendlines (Linear, Logarithmic, Polynomial)</li>
                <li><strong>Capacity Tab</strong> - Calculate based on headcount, billable hours, and pricing</li>
                <li><strong>Collection Tab</strong> - Model payment patterns and collection periods</li>
                <li><strong>Result Tab</strong> - Consolidate three methods and select final value</li>
                <li><strong>Personnel Tab</strong> - Plan employee allocations to products</li>
                <li><strong>OpEx Tab</strong> - Budget operational expenses</li>
                <li><strong>Tax Tab</strong> - Plan tax provisions</li>
                <li><strong>CapEx Tab</strong> - Budget capital expenditures</li>
                <li><strong>P&L Tab</strong> - Review consolidated budget summary</li>
            </ol>
            <p class="text-muted small mt-3">Once all sections are complete, you can finalize the budget for the fiscal year.</p>
        </div>
    </div>
    @endif
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Budget</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the budget for <strong id="budgetYear"></strong>?</p>
                <p class="text-danger small">This action cannot be undone. All budget data for this year will be permanently deleted.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Budget</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .progress {
        background-color: #e9ecef;
    }

    .progress-bar {
        font-size: 0.75rem;
        font-weight: 600;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }

    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const budgetId = button.getAttribute('data-budget-id');
            const budgetYear = button.getAttribute('data-budget-year');

            document.getElementById('budgetYear').textContent = budgetYear;

            const deleteForm = document.getElementById('deleteForm');
            deleteForm.action = `/accounting/budgets/${budgetId}`;
        });
    }
});
</script>
@endsection
