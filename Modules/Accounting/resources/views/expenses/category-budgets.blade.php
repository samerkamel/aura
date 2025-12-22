@extends('layouts/layoutMaster')

@section('title', 'Category Budgets')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Expense Category Budgets</h5>
                    <small class="text-muted">Manage annual budget allocations for expense categories (% of monthly revenue)</small>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <form method="GET" action="{{ route('accounting.expenses.categories.budgets') }}" class="d-flex align-items-center gap-2">
                        <label class="form-label mb-0 me-2">Year:</label>
                        <select name="year" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                            @foreach($availableYears as $y)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </form>
                    <a href="{{ route('accounting.expenses.categories') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Back to Categories
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

            <div class="card-body">
                <div class="alert alert-info">
                    <i class="ti ti-info-circle me-2"></i>
                    <strong>Budget Percentage:</strong> The percentage represents the portion of total monthly revenue allocated to each expense category.
                    For example, if a category has a 10% budget and monthly revenue is 100,000 EGP, the budget for that category would be 10,000 EGP/month.
                </div>
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Type</th>
                            <th class="text-center">{{ $year }} Budget %</th>
                            <th class="text-end">YTD Spending</th>
                            <th class="text-end">Avg/Month</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @php
                            $totalBudgetPercentage = 0;
                        @endphp
                        @forelse($categories as $category)
                            @php
                                $budget = $category->budgets->first();
                                $budgetPercentage = $budget ? $budget->budget_percentage : 0;
                                $totalBudgetPercentage += $budgetPercentage;
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded me-3" style="width: 12px; height: 12px; background-color: {{ $category->color }};"></div>
                                        <strong>{{ $category->name }}</strong>
                                    </div>
                                </td>
                                <td>
                                    @if($category->expenseType)
                                        <span class="badge" style="background-color: {{ $category->expenseType->color }}; color: white;">
                                            {{ $category->expenseType->code }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($budget)
                                        <span class="badge bg-primary fs-6">{{ number_format($budget->budget_percentage, 2) }}%</span>
                                    @else
                                        <span class="text-muted">Not set</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($category->ytd_spending > 0)
                                        <strong class="text-danger">{{ number_format($category->ytd_spending, 2) }} EGP</strong>
                                    @else
                                        <span class="text-muted">0.00 EGP</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($category->ytd_average_per_month > 0)
                                        <strong class="text-warning">{{ number_format($category->ytd_average_per_month, 2) }} EGP</strong>
                                    @else
                                        <span class="text-muted">0.00 EGP</span>
                                    @endif
                                </td>
                                <td>
                                    @if($budget && $budget->notes)
                                        <span class="text-muted" data-bs-toggle="tooltip" title="{{ $budget->notes }}">
                                            {{ \Illuminate\Support\Str::limit($budget->notes, 30) }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($budget)
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editBudgetModal"
                                                data-category-id="{{ $category->id }}"
                                                data-category-name="{{ $category->name }}"
                                                data-budget-id="{{ $budget->id }}"
                                                data-budget-percentage="{{ $budget->budget_percentage }}"
                                                data-budget-notes="{{ $budget->notes }}"
                                                title="Edit Budget">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                        <form action="{{ route('accounting.expenses.categories.budgets.destroy', [$category, $budget]) }}"
                                              method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-icon btn-outline-danger"
                                                    title="Delete Budget"
                                                    onclick="return confirm('Are you sure you want to delete this budget?')">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-sm btn-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#addBudgetModal"
                                                data-category-id="{{ $category->id }}"
                                                data-category-name="{{ $category->name }}">
                                            <i class="ti ti-plus me-1"></i>Set Budget
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="ti ti-category text-muted mb-3" style="font-size: 4rem;"></i>
                                        <h5>No main categories found</h5>
                                        <p class="text-muted">Create main expense categories first to set budgets</p>
                                        <a href="{{ route('accounting.expenses.categories') }}" class="btn btn-primary">
                                            <i class="ti ti-plus me-1"></i>Manage Categories
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($categories->count() > 0)
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="2"><strong>Total</strong></td>
                            <td class="text-center">
                                <span class="badge {{ $totalBudgetPercentage > 100 ? 'bg-danger' : ($totalBudgetPercentage == 100 ? 'bg-success' : 'bg-warning') }} fs-6">
                                    {{ number_format($totalBudgetPercentage, 2) }}%
                                </span>
                                @if($totalBudgetPercentage > 100)
                                    <br><small class="text-danger">Exceeds 100%!</small>
                                @elseif($totalBudgetPercentage < 100)
                                    <br><small class="text-warning">{{ number_format(100 - $totalBudgetPercentage, 2) }}% unallocated</small>
                                @endif
                            </td>
                            <td class="text-end">
                                <strong>{{ number_format($categories->sum('ytd_spending'), 2) }} EGP</strong>
                            </td>
                            <td class="text-end">
                                <strong>{{ number_format($categories->sum('ytd_average_per_month'), 2) }} EGP</strong>
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Budget Modal -->
<div class="modal fade" id="addBudgetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Set Budget for <span id="addBudgetCategoryName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addBudgetForm" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="budget_year" value="{{ $year }}">

                    <div class="mb-3">
                        <label for="add_budget_percentage" class="form-label">
                            Budget Percentage <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="add_budget_percentage"
                                   name="budget_percentage" step="0.01" min="0" max="100" required>
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="text-muted">Percentage of monthly revenue allocated to this category</small>
                    </div>

                    <div class="mb-3">
                        <label for="add_budget_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="add_budget_notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Budget</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Budget Modal -->
<div class="modal fade" id="editBudgetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Budget for <span id="editBudgetCategoryName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editBudgetForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_budget_percentage" class="form-label">
                            Budget Percentage <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="edit_budget_percentage"
                                   name="budget_percentage" step="0.01" min="0" max="100" required>
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="text-muted">Percentage of monthly revenue allocated to this category</small>
                    </div>

                    <div class="mb-3">
                        <label for="edit_budget_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="edit_budget_notes" name="notes" rows="2"></textarea>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function(tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Add Budget Modal
    const addBudgetModal = document.getElementById('addBudgetModal');
    addBudgetModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const categoryId = button.getAttribute('data-category-id');
        const categoryName = button.getAttribute('data-category-name');

        document.getElementById('addBudgetCategoryName').textContent = categoryName;
        document.getElementById('addBudgetForm').action = `/accounting/expenses/categories/${categoryId}/budgets`;
        document.getElementById('add_budget_percentage').value = '';
        document.getElementById('add_budget_notes').value = '';
    });

    // Edit Budget Modal
    const editBudgetModal = document.getElementById('editBudgetModal');
    editBudgetModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const categoryId = button.getAttribute('data-category-id');
        const categoryName = button.getAttribute('data-category-name');
        const budgetId = button.getAttribute('data-budget-id');
        const budgetPercentage = button.getAttribute('data-budget-percentage');
        const budgetNotes = button.getAttribute('data-budget-notes');

        document.getElementById('editBudgetCategoryName').textContent = categoryName;
        document.getElementById('editBudgetForm').action = `/accounting/expenses/categories/${categoryId}/budgets/${budgetId}`;
        document.getElementById('edit_budget_percentage').value = budgetPercentage;
        document.getElementById('edit_budget_notes').value = budgetNotes || '';
    });
});
</script>
@endsection
