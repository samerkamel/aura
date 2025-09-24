@extends('layouts/layoutMaster')

@section('title', 'Expense Categories')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Expense Categories</h5>
                    <small class="text-muted">Organize and manage expense categories with YTD tracking</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('accounting.expenses.paid') }}" class="btn btn-outline-info">
                        <i class="ti ti-receipt me-1"></i>Paid Expenses
                    </a>
                    <a href="{{ route('accounting.expenses.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Back to Expenses
                    </a>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="ti ti-plus me-1"></i>New Category
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
                            <th>Category</th>
                            <th>Description</th>
                            <th>Total YTD</th>
                            <th>Average per Month YTD</th>
                            <th>Average Scheduled per Month</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse($categories as $category)
                            <tr class="{{ !$category->is_active ? 'opacity-50' : '' }}">
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($category->parent_id)
                                            <div class="me-2 text-muted">└─</div>
                                        @endif
                                        <div class="rounded me-3" style="width: 12px; height: 12px; background-color: {{ $category->color }};"></div>
                                        <div>
                                            <strong>{{ $category->name }}</strong>
                                            @if($category->parent)
                                                <br><small class="text-muted">Under: {{ $category->parent->name }}</small>
                                            @elseif($category->subcategories->count() > 0)
                                                <br><small class="text-info">{{ $category->subcategories->count() }} subcategories</small>
                                            @endif
                                            @if(!$category->is_active)
                                                <br><small class="text-muted fst-italic">Inactive</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="{{ !$category->is_active ? 'text-muted' : '' }}">
                                    {{ $category->description ?: 'No description' }}
                                </td>
                                <td>
                                    @if($category->ytd_total > 0)
                                        <strong class="text-danger">{{ number_format($category->ytd_total, 2) }} EGP</strong>
                                    @else
                                        <span class="text-muted">0.00 EGP</span>
                                    @endif
                                </td>
                                <td>
                                    @if($category->ytd_average_per_month > 0)
                                        <strong class="text-warning">{{ number_format($category->ytd_average_per_month, 2) }} EGP</strong>
                                    @else
                                        <span class="text-muted">0.00 EGP</span>
                                    @endif
                                </td>
                                <td>
                                    @if($category->average_scheduled_per_month > 0)
                                        <strong class="text-info">{{ number_format($category->average_scheduled_per_month, 2) }} EGP</strong>
                                    @else
                                        <span class="text-muted">0.00 EGP</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                               data-bs-target="#editCategoryModal"
                                               data-id="{{ $category->id }}"
                                               data-name="{{ $category->name }}"
                                               data-description="{{ $category->description }}"
                                               data-color="{{ $category->color }}">
                                                <i class="ti ti-edit me-2"></i>Edit
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <form action="{{ route('accounting.expenses.categories.toggle-status', $category) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="dropdown-item">
                                                    <i class="ti ti-{{ $category->is_active ? 'pause' : 'play' }} me-2"></i>
                                                    {{ $category->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                            @if($category->expense_schedules_count == 0)
                                                <div class="dropdown-divider"></div>
                                                <button type="button" class="dropdown-item text-danger"
                                                        onclick="confirmDelete('{{ $category->id }}', '{{ $category->name }}')">
                                                    <i class="ti ti-trash me-2"></i>Delete
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="ti ti-category text-muted mb-3" style="font-size: 4rem;"></i>
                                        <h5>No categories found</h5>
                                        <p class="text-muted">Create your first expense category to organize your expenses</p>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                            <i class="ti ti-plus me-1"></i>Create Category
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

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('accounting.expenses.categories.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add_name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="add_parent_id" class="form-label">Parent Category <small class="text-muted">(Optional)</small></label>
                        <select class="form-select" id="add_parent_id" name="parent_id">
                            <option value="">Main Category</option>
                            @foreach($parentCategories as $parentCategory)
                                <option value="{{ $parentCategory->id }}">{{ $parentCategory->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Leave blank to create a main category, or select a parent to create a subcategory</small>
                    </div>

                    <div class="mb-3">
                        <label for="add_sort_order" class="form-label">Sort Order <small class="text-muted">(Optional)</small></label>
                        <input type="number" class="form-control" id="add_sort_order" name="sort_order" min="0" placeholder="0">
                        <small class="text-muted">Higher numbers appear later in lists</small>
                    </div>

                    <div class="mb-3">
                        <label for="add_description" class="form-label">Description</label>
                        <textarea class="form-control" id="add_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="add_color" class="form-label">Color <span class="text-danger">*</span></label>
                        <div class="d-flex align-items-center">
                            <input type="color" class="form-control form-control-color me-3" id="add_color" name="color" value="#FF6B6B" required>
                            <input type="text" class="form-control" id="add_color_text" readonly>
                        </div>
                        <small class="text-muted">Choose a color to help identify this category</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCategoryForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="ti ti-alert-triangle text-warning" style="font-size: 3rem;"></i>
                </div>
                <h6 class="text-center">Are you sure you want to delete this category?</h6>
                <p class="text-center text-muted">
                    Category: <strong id="deleteCategoryName"></strong>
                </p>
                <div class="alert alert-warning">
                    <i class="ti ti-info-circle me-2"></i>
                    This action cannot be undone. Only categories with no associated expense schedules can be deleted.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteCategoryForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="ti ti-trash me-1"></i>Delete Category
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

    // Edit category modal
    const editModal = document.getElementById('editCategoryModal');
    editModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const categoryId = button.getAttribute('data-id');
        const categoryName = button.getAttribute('data-name');
        const categoryDescription = button.getAttribute('data-description');
        const categoryColor = button.getAttribute('data-color');

        const form = document.getElementById('editCategoryForm');
        form.action = `/accounting/expenses/categories/${categoryId}`;

        document.getElementById('edit_name').value = categoryName;
        document.getElementById('edit_description').value = categoryDescription;
        document.getElementById('edit_color').value = categoryColor;
        document.getElementById('edit_color_text').value = categoryColor.toUpperCase();
    });

    // Delete confirmation
    window.confirmDelete = function(categoryId, categoryName) {
        document.getElementById('deleteCategoryName').textContent = categoryName;
        document.getElementById('deleteCategoryForm').action = `/accounting/expenses/categories/${categoryId}`;

        const deleteModal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
        deleteModal.show();
    };
});
</script>
@endsection