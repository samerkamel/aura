@extends('layouts/layoutMaster')

@section('title', 'Expense Types')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Expense Types Management</h5>
                    <small class="text-muted">Manage expense types (CapEx, OpEx, CoS, etc.) for categorizing expenses</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('accounting.expenses.categories') }}" class="btn btn-outline-info">
                        <i class="ti tabler-category me-1"></i>Manage Categories
                    </a>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseTypeModal">
                        <i class="ti tabler-plus me-1"></i>New Expense Type
                    </button>
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

            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Sort Order</th>
                            <th>Type</th>
                            <th>Code</th>
                            <th>Description</th>
                            <th>Categories</th>
                            <th>Active Categories</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0" id="expense-types-tbody">
                        @forelse($expenseTypes as $expenseType)
                            <tr class="{{ !$expenseType->is_active ? 'opacity-50' : '' }}" data-expense-type-id="{{ $expenseType->id }}">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="ti tabler-grip-vertical text-muted me-2 cursor-pointer drag-handle"></i>
                                        <span class="badge bg-secondary">{{ $expenseType->sort_order }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded me-3" style="width: 12px; height: 12px; background-color: {{ $expenseType->color }};"></div>
                                        <div>
                                            <strong>{{ $expenseType->name }}</strong>
                                            @if(!$expenseType->is_active)
                                                <br><small class="text-muted fst-italic">Inactive</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" style="background-color: {{ $expenseType->color }}; color: white;">
                                        {{ $expenseType->code }}
                                    </span>
                                </td>
                                <td class="{{ !$expenseType->is_active ? 'text-muted' : '' }}">
                                    {{ $expenseType->description ?: 'No description' }}
                                </td>
                                <td>
                                    @if($expenseType->categories_count > 0)
                                        <span class="badge bg-info">{{ $expenseType->categories_count }} total</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td>
                                    @if($expenseType->active_categories_count > 0)
                                        <span class="badge bg-success">{{ $expenseType->active_categories_count }} active</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td>
                                    @if($expenseType->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                            <i class="ti tabler-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                               data-bs-target="#editExpenseTypeModal"
                                               data-id="{{ $expenseType->id }}"
                                               data-name="{{ $expenseType->name }}"
                                               data-code="{{ $expenseType->code }}"
                                               data-description="{{ $expenseType->description }}"
                                               data-color="{{ $expenseType->color }}"
                                               data-sort-order="{{ $expenseType->sort_order }}">
                                                <i class="ti tabler-edit me-2"></i>Edit
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <form action="{{ route('accounting.expense-types.toggle-status', $expenseType) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="dropdown-item">
                                                    <i class="ti tabler-{{ $expenseType->is_active ? 'pause' : 'play' }} me-2"></i>
                                                    {{ $expenseType->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                            @if($expenseType->categories_count == 0)
                                                <div class="dropdown-divider"></div>
                                                <button type="button" class="dropdown-item text-danger"
                                                        onclick="confirmDelete('{{ $expenseType->id }}', '{{ $expenseType->name }}')">
                                                    <i class="ti tabler-trash me-2"></i>Delete
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="ti tabler-category text-muted mb-3" style="font-size: 4rem;"></i>
                                        <h5>No expense types found</h5>
                                        <p class="text-muted">Create your first expense type to organize your expense categories</p>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseTypeModal">
                                            <i class="ti tabler-plus me-1"></i>Create Expense Type
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Expense Type Modal -->
<div class="modal fade" id="addExpenseTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Expense Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('accounting.expense-types.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add_name" name="name" required
                               placeholder="e.g., Capital Expenses, Research & Development">
                    </div>

                    <div class="mb-3">
                        <label for="add_code" class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add_code" name="code" required
                               placeholder="e.g., CapEx, OpEx, CoS" maxlength="20">
                        <small class="text-muted">Short code for display (max 20 characters)</small>
                    </div>

                    <div class="mb-3">
                        <label for="add_description" class="form-label">Description</label>
                        <textarea class="form-control" id="add_description" name="description" rows="3"
                                  placeholder="Optional description of this expense type"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="add_color" class="form-label">Color <span class="text-danger">*</span></label>
                        <div class="d-flex align-items-center">
                            <input type="color" class="form-control form-control-color me-3" id="add_color" name="color" value="#007bff" required>
                            <input type="text" class="form-control" id="add_color_text" readonly>
                        </div>
                        <small class="text-muted">Choose a color to help identify this expense type</small>
                    </div>

                    <div class="mb-3">
                        <label for="add_sort_order" class="form-label">Sort Order <small class="text-muted">(Optional)</small></label>
                        <input type="number" class="form-control" id="add_sort_order" name="sort_order" min="0" max="999" placeholder="Auto-assigned if left blank">
                        <small class="text-muted">Lower numbers appear first</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Expense Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Expense Type Modal -->
<div class="modal fade" id="editExpenseTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Expense Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editExpenseTypeForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_code" class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_code" name="code" required maxlength="20">
                    </div>

                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="edit_color" class="form-label">Color <span class="text-danger">*</span></label>
                        <div class="d-flex align-items-center">
                            <input type="color" class="form-control form-control-color me-3" id="edit_color" name="color" required>
                            <input type="text" class="form-control" id="edit_color_text" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_sort_order" class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="edit_sort_order" name="sort_order" min="0" max="999">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Expense Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteExpenseTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Delete Expense Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="ti tabler-alert-triangle text-warning" style="font-size: 3rem;"></i>
                </div>
                <h6 class="text-center">Are you sure you want to delete this expense type?</h6>
                <p class="text-center text-muted">
                    Expense Type: <strong id="deleteExpenseTypeName"></strong>
                </p>
                <div class="alert alert-warning">
                    <i class="ti tabler-info-circle me-2"></i>
                    This action cannot be undone. Only expense types with no categories can be deleted.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteExpenseTypeForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="ti tabler-trash me-1"></i>Delete Expense Type
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Color picker sync
    const addColorPicker = document.getElementById('add_color');
    const addColorText = document.getElementById('add_color_text');
    const editColorPicker = document.getElementById('edit_color');
    const editColorText = document.getElementById('edit_color_text');

    function syncColorInputs(colorPicker, textInput) {
        colorPicker.addEventListener('input', function() {
            textInput.value = this.value.toUpperCase();
        });
        textInput.value = colorPicker.value.toUpperCase();
    }

    syncColorInputs(addColorPicker, addColorText);
    syncColorInputs(editColorPicker, editColorText);

    // Edit expense type modal
    const editModal = document.getElementById('editExpenseTypeModal');
    editModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const expenseTypeId = button.getAttribute('data-id');
        const expenseTypeName = button.getAttribute('data-name');
        const expenseTypeCode = button.getAttribute('data-code');
        const expenseTypeDescription = button.getAttribute('data-description');
        const expenseTypeColor = button.getAttribute('data-color');
        const expenseTypeSortOrder = button.getAttribute('data-sort-order');

        const form = document.getElementById('editExpenseTypeForm');
        form.action = `/accounting/expense-types/${expenseTypeId}`;

        document.getElementById('edit_name').value = expenseTypeName;
        document.getElementById('edit_code').value = expenseTypeCode;
        document.getElementById('edit_description').value = expenseTypeDescription || '';
        document.getElementById('edit_color').value = expenseTypeColor;
        document.getElementById('edit_color_text').value = expenseTypeColor.toUpperCase();
        document.getElementById('edit_sort_order').value = expenseTypeSortOrder;
    });

    // Delete confirmation
    window.confirmDelete = function(expenseTypeId, expenseTypeName) {
        document.getElementById('deleteExpenseTypeName').textContent = expenseTypeName;
        document.getElementById('deleteExpenseTypeForm').action = `/accounting/expense-types/${expenseTypeId}`;

        const deleteModal = new bootstrap.Modal(document.getElementById('deleteExpenseTypeModal'));
        deleteModal.show();
    };

    // Sortable functionality for drag and drop reordering
    const tbody = document.getElementById('expense-types-tbody');
    if (tbody && typeof Sortable !== 'undefined') {
        Sortable.create(tbody, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function(evt) {
                const expenseTypeIds = Array.from(tbody.querySelectorAll('tr[data-expense-type-id]'))
                    .map(row => row.getAttribute('data-expense-type-id'));

                // Send AJAX request to update sort order
                fetch('/accounting/expense-types/update-sort-order', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        expense_types: expenseTypeIds
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update sort order badges
                        tbody.querySelectorAll('tr[data-expense-type-id]').forEach((row, index) => {
                            const badge = row.querySelector('.bg-secondary');
                            if (badge) badge.textContent = index + 1;
                        });
                    }
                })
                .catch(error => {
                    console.error('Error updating sort order:', error);
                });
            }
        });
    }
});
</script>
@endsection