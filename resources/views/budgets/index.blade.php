@extends('layouts/layoutMaster')

@section('title', 'Revenue Targets - ' . $businessUnit->name)

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Revenue Targets</h5>
                    <small class="text-muted">{{ $businessUnit->name }} ({{ $businessUnit->code }}) - {{ $year }}</small>
                </div>
                <div class="d-flex gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="ti ti-calendar me-1"></i>{{ $year }}
                        </button>
                        <ul class="dropdown-menu">
                            @foreach($years as $yearOption)
                                <li>
                                    <a class="dropdown-item {{ $yearOption == $year ? 'active' : '' }}"
                                       href="{{ route('budgets.index', [$businessUnit, 'year' => $yearOption]) }}">
                                        {{ $yearOption }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createProductModal">
                        <i class="ti ti-plus me-1"></i>Add Product to BU
                    </button>
                    <a href="{{ route('accounting.income-sheet.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Back to Income Sheet
                    </a>
                </div>
            </div>

            <div class="card-body">
                <!-- Budget Summary Cards -->
                <div class="row mb-4" id="budget-summary">
                    <div class="col-md-3">
                        <div class="card border border-primary">
                            <div class="card-body text-center">
                                <i class="ti ti-wallet text-primary mb-2" style="font-size: 2rem;"></i>
                                <h6 class="card-title text-primary">Total Target</h6>
                                <h4 class="mb-0" id="total-target">-</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border border-warning">
                            <div class="card-body text-center">
                                <i class="ti ti-credit-card text-warning mb-2" style="font-size: 2rem;"></i>
                                <h6 class="card-title text-warning">Projected</h6>
                                <h4 class="mb-0" id="total-projected">-</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border border-danger">
                            <div class="card-body text-center">
                                <i class="ti ti-minus text-danger mb-2" style="font-size: 2rem;"></i>
                                <h6 class="card-title text-success">Actual Revenue</h6>
                                <h4 class="mb-0" id="total-actual">-</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border border-success">
                            <div class="card-body text-center">
                                <i class="ti ti-percentage text-success mb-2" style="font-size: 2rem;"></i>
                                <h6 class="card-title text-info">Achievement</h6>
                                <h4 class="mb-0" id="avg-achievement">-</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Budgets Table -->
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Target Revenue</th>
                                <th>Projected</th>
                                <th>Actual Revenue</th>
                                <th>Remaining Target</th>
                                <th>Achievement</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="budgets-table">
                            @forelse($budgets as $budget)
                                <tr data-budget-id="{{ $budget->id }}">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2">
                                                <span class="avatar-initial rounded-circle bg-label-primary">
                                                    <i class="ti ti-package ti-sm"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <strong>{{ $budget->product->name }}</strong>
                                                <br><small class="text-muted">{{ $budget->product->code }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <strong>{{ number_format($budget->target_revenue, 0) }}</strong>
                                    </td>
                                    <td class="text-end">
                                        <span class="text-warning">{{ number_format($budget->projected_revenue, 0) }}</span>
                                        <br><small class="text-muted">{{ $budget->projection_percentage }}%</small>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex flex-column">
                                            <span class="text-primary">{{ number_format($budget->actual_revenue, 0) }}</span>
                                            <small class="text-muted">Total Contracts</small>
                                            <span class="text-success">{{ number_format($budget->paid_income, 0) }}</span>
                                            <small class="text-muted">Paid Income</small>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <span class="{{ $budget->remaining_target < 0 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($budget->remaining_target, 0) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-column gap-1">
                                            <div>
                                                <small class="text-muted d-block">Contract/Target</small>
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar {{ $budget->contract_target_percentage > 100 ? 'bg-success' : ($budget->contract_target_percentage > 80 ? 'bg-warning' : 'bg-danger') }}"
                                                         style="width: {{ min($budget->contract_target_percentage, 100) }}%"></div>
                                                </div>
                                                <small class="text-muted">{{ $budget->contract_target_percentage }}%</small>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">Paid/Target</small>
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar {{ $budget->paid_target_percentage > 100 ? 'bg-success' : ($budget->paid_target_percentage > 80 ? 'bg-warning' : 'bg-danger') }}"
                                                         style="width: {{ min($budget->paid_target_percentage, 100) }}%"></div>
                                                </div>
                                                <small class="text-muted">{{ $budget->paid_target_percentage }}%</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#" onclick="editBudget({{ $budget->id }})">
                                                    <i class="ti ti-edit me-1"></i>Edit Target
                                                </a></li>
                                                <li><a class="dropdown-item" href="#" onclick="allocateBudget({{ $budget->id }})">
                                                    <i class="ti ti-trending-up me-1"></i>Update Projection
                                                </a></li>
                                                <li><a class="dropdown-item" href="#" onclick="spendBudget({{ $budget->id }})">
                                                    <i class="ti ti-plus me-1"></i>Record Revenue
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item" href="{{ route('budgets.history', $budget) }}">
                                                    <i class="ti ti-history me-1"></i>View History
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteBudget({{ $budget->id }})">
                                                    <i class="ti ti-trash me-1"></i>Delete
                                                </a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="ti ti-building text-muted mb-3" style="font-size: 4rem;"></i>
                                            <h5>No Active Products</h5>
                                            <p class="text-muted">This business unit has no active products configured</p>
                                            <a href="{{ route('administration.products.index') }}" class="btn btn-primary">
                                                <i class="ti ti-plus me-1"></i>Manage Products
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($budgets->hasPages())
                    <div class="d-flex justify-content-center">
                        {{ $budgets->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Create Budget Modal -->
<div class="modal fade" id="createBudgetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Set Revenue Target</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createBudgetForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">Product <span class="text-danger">*</span></label>
                            <select class="form-select" name="product_id" required>
                                <option value="">Select Product</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Budget Year <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="budget_year" value="{{ $year }}" min="2020" max="2050" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Optional budget notes..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Budget</button>
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
                <h5 class="modal-title">Edit Revenue Target</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editBudgetForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit-budget-id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">Product</label>
                            <input type="text" class="form-control" id="edit-product-name" readonly>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Target Revenue <span class="text-muted">(from Product)</span></label>
                            <input type="number" class="form-control" name="budget_allocation" id="edit-budget-allocation" step="0.01" min="0">
                            <small class="text-muted">This will update the budget allocation in the product management section.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Projected Revenue</label>
                            <input type="number" class="form-control" name="projected_revenue" id="edit-projected-revenue" step="0.01" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Actual Revenue (Auto-calculated)</label>
                            <input type="text" class="form-control" id="edit-actual-revenue-display" readonly>
                            <small class="text-muted">Calculated from business unit contracts</small>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" id="edit-notes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Budget</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Allocate Budget Modal -->
<div class="modal fade" id="allocateBudgetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Revenue Projection</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="allocateBudgetForm">
                @csrf
                <input type="hidden" id="allocate-budget-id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">Product</label>
                            <input type="text" class="form-control" id="allocate-product-name" readonly>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Allocation Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="amount" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Describe the allocation purpose..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update Projection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Spend Budget Modal -->
<div class="modal fade" id="spendBudgetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Revenue Achievement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="spendBudgetForm">
                @csrf
                <input type="hidden" id="spend-budget-id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">Product</label>
                            <input type="text" class="form-control" id="spend-product-name" readonly>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Revenue Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="amount" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Describe what was purchased or paid for..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Record Revenue</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadBudgetSummary();

    // Create Budget Form
    document.getElementById('createBudgetForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('{{ route("budgets.store", $businessUnit) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while creating the budget.');
        });
    });

    // Edit Budget Form
    document.getElementById('editBudgetForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const budgetId = document.getElementById('edit-budget-id').value;
        const formData = new FormData(this);

        fetch(`/budgets/${budgetId}`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-HTTP-Method-Override': 'PUT'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    });

    // Allocate Budget Form
    document.getElementById('allocateBudgetForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const budgetId = document.getElementById('allocate-budget-id').value;
        const formData = new FormData(this);

        fetch(`/budgets/${budgetId}/allocate`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    });

    // Spend Budget Form
    document.getElementById('spendBudgetForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const budgetId = document.getElementById('spend-budget-id').value;
        const formData = new FormData(this);

        fetch(`/budgets/${budgetId}/spend`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    });
});

function loadBudgetSummary() {
    fetch('{{ route("budgets.summary", [$businessUnit, "year" => $year]) }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const summary = data.summary;
                document.getElementById('total-target').textContent = formatNumber(summary.total_target || 0);
                document.getElementById('total-projected').textContent = formatNumber(summary.total_projected || 0);
                document.getElementById('total-actual').textContent = formatNumber(summary.total_actual || 0);
                document.getElementById('avg-achievement').textContent = Math.round(summary.avg_achievement || 0) + '%';
            }
        });
}

function editBudget(budgetId) {
    // Fetch budget data from server
    fetch(`/budgets/${budgetId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const budget = data.budget;

                // Populate the form fields
                document.getElementById('edit-budget-id').value = budget.id;
                document.getElementById('edit-product-name').value = `${budget.product.name} (${budget.product.code})`;
                document.getElementById('edit-budget-allocation').value = budget.product.budget_allocation;
                document.getElementById('edit-projected-revenue').value = budget.projected_revenue;
                document.getElementById('edit-actual-revenue-display').value = new Intl.NumberFormat().format(budget.actual_revenue);
                document.getElementById('edit-notes').value = budget.notes || '';

                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('editBudgetModal'));
                modal.show();
            } else {
                alert('Error loading budget data: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading budget data.');
        });
}

function allocateBudget(budgetId) {
    // Fetch budget data to show product name
    fetch(`/budgets/${budgetId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('allocate-budget-id').value = budgetId;
                document.getElementById('allocate-product-name').value = `${data.budget.product.name} (${data.budget.product.code})`;

                const modal = new bootstrap.Modal(document.getElementById('allocateBudgetModal'));
                modal.show();
            }
        });
}

function spendBudget(budgetId) {
    // Fetch budget data to show product name
    fetch(`/budgets/${budgetId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('spend-budget-id').value = budgetId;
                document.getElementById('spend-product-name').value = `${data.budget.product.name} (${data.budget.product.code})`;

                const modal = new bootstrap.Modal(document.getElementById('spendBudgetModal'));
                modal.show();
            }
        });
}

function deleteBudget(budgetId) {
    if (confirm('Are you sure you want to delete this budget? This action cannot be undone.')) {
        fetch(`/budgets/${budgetId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function formatNumber(num) {
    return new Intl.NumberFormat().format(Math.round(num));
}
    // Create Product Form
    document.getElementById('createProductForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('{{ route("budgets.createProduct", $businessUnit) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while creating the product.');
        });
    });
</script>

<!-- Create Product Modal -->
<div class="modal fade" id="createProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Product to {{ $businessUnit->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createProductForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required placeholder="e.g., Digital Marketing">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="code" required placeholder="e.g., DM" maxlength="10">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="Brief description of the product/service"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Budget Allocation</label>
                            <input type="number" class="form-control" name="budget_allocation" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Head of Product</label>
                            <input type="text" class="form-control" name="head_of_product" placeholder="Product head name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" placeholder="product@company.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" placeholder="Contact phone">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Create Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection