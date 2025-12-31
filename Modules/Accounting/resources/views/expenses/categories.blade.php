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
                <div class="d-flex gap-2 align-items-center">
                    <form method="GET" action="{{ route('accounting.expenses.categories') }}" class="d-flex align-items-center gap-2">
                        <label class="form-label mb-0 me-2">Year:</label>
                        <select name="year" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                            @foreach($availableYears as $y)
                                <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </form>
                    <a href="{{ route('accounting.expenses.categories.budgets', ['year' => $currentYear]) }}" class="btn btn-warning">
                        <i class="ti ti-percentage me-1"></i>Manage Budgets
                    </a>
                    <a href="{{ route('accounting.expenses.paid') }}" class="btn btn-outline-info">
                        <i class="ti ti-receipt me-1"></i>Paid Expenses
                    </a>
                    <a href="{{ route('accounting.expenses.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Back to Expenses
                    </a>
                    <a href="{{ route('accounting.expenses.categories.import') }}" class="btn btn-outline-info btn-sm" title="Import Categories from CSV">
                        <i class="ti ti-upload"></i>
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

            @if(isset($revenueSummary))
            <div class="card-body pt-0">
                <div class="alert alert-light border mb-3">
                    <div class="row text-center mb-2">
                        <div class="col-12">
                            <small class="text-muted fw-bold">PLANNED (Budget Targets)</small>
                        </div>
                    </div>
                    <div class="row text-center">
                        <div class="col-md-3">
                            <small class="text-muted d-block">{{ $currentYear }} Revenue Target</small>
                            <strong class="text-primary">{{ number_format($revenueSummary['total_yearly_revenue'], 0) }} EGP</strong>
                            @if($revenueSummary['total_yearly_revenue'] == 0)
                                <br><small class="text-warning">No budget set</small>
                            @endif
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Monthly Revenue (Plan)</small>
                            <strong class="text-primary">{{ number_format($revenueSummary['total_monthly_revenue'], 0) }} EGP</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Monthly Net Income ({{ 100 - $revenueSummary['tier1_percentage'] }}%)</small>
                            <strong class="text-success">{{ number_format($revenueSummary['monthly_net_income'], 0) }} EGP</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">
                                @if($revenueSummary['is_current_year'])
                                    Months Elapsed ({{ $currentYear }})
                                @elseif($revenueSummary['is_future_year'])
                                    Full Year Projection
                                @else
                                    Full Year ({{ $currentYear }})
                                @endif
                            </small>
                            <strong>{{ $revenueSummary['months_elapsed'] }} / 12</strong>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="row text-center mb-2">
                        <div class="col-12">
                            <small class="text-muted fw-bold">ACTUAL (From Paid Invoices)</small>
                        </div>
                    </div>
                    <div class="row text-center">
                        <div class="col-md-3">
                            <small class="text-muted d-block">YTD Revenue (Actual)</small>
                            <strong class="text-info">{{ number_format($revenueSummary['actual_ytd_revenue'], 0) }} EGP</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Monthly Avg Revenue (Actual)</small>
                            <strong class="text-info">{{ number_format($revenueSummary['actual_monthly_revenue'], 0) }} EGP</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Monthly Net Income (Actual)</small>
                            <strong class="text-success">{{ number_format($revenueSummary['actual_monthly_net_income'], 0) }} EGP</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">YTD Net Income (Actual)</small>
                            <strong class="text-success">{{ number_format($revenueSummary['actual_ytd_net_income'], 0) }} EGP</strong>
                        </div>
                    </div>
                </div>

                <!-- View Controls -->
                <div class="d-flex justify-content-between align-items-center mb-0">
                    <div class="d-flex gap-3 align-items-center">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="expandAllBtn">
                            <i class="ti ti-arrows-maximize me-1"></i>Expand All
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="collapseAllBtn">
                            <i class="ti ti-arrows-minimize me-1"></i>Collapse All
                        </button>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="hideEmptyCategories">
                        <label class="form-check-label" for="hideEmptyCategories">
                            Hide empty categories <small class="text-muted">(no budget & no expenses)</small>
                        </label>
                    </div>
                </div>
            </div>
            @endif

            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th class="text-center">Budget %</th>
                            <th class="text-end">Plan (Monthly)</th>
                            <th class="text-end">Plan (YTD)</th>
                            <th class="text-end text-info">
                                Available (Monthly)
                                <i class="ti ti-info-circle" data-bs-toggle="tooltip" title="Budget % × Actual Monthly Income"></i>
                            </th>
                            <th class="text-end text-info">
                                Available (YTD)
                                <i class="ti ti-info-circle" data-bs-toggle="tooltip" title="Budget % × Actual YTD Income"></i>
                            </th>
                            <th class="text-end">
                                Actual YTD
                                <i class="ti ti-info-circle text-muted" data-bs-toggle="tooltip" title="Parent categories include all expenses from their subcategories"></i>
                            </th>
                            <th class="text-end">Remaining</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse($categories as $category)
                            @php
                                $depth = $category->depth ?? 0;
                                $hasChildren = $category->subcategories && $category->subcategories->count() > 0;
                                $isEmptyCategory = ($category->planned_monthly ?? 0) == 0 && ($category->ytd_total ?? 0) == 0;
                                $parentId = $category->parent_id ?? '';
                            @endphp
                            <tr class="category-row {{ !$category->is_active ? 'opacity-50' : '' }}"
                                data-category-id="{{ $category->id }}"
                                data-parent-id="{{ $parentId }}"
                                data-has-children="{{ $hasChildren ? 'true' : 'false' }}"
                                data-is-empty="{{ $isEmptyCategory ? 'true' : 'false' }}"
                                data-depth="{{ $depth }}">
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($depth > 0)
                                            <div class="me-2 text-muted" style="font-family: monospace;">
                                                {{ str_repeat('│  ', $depth - 1) }}└─
                                            </div>
                                        @endif
                                        @if($hasChildren)
                                            <div class="rounded me-3 collapse-toggle"
                                                 data-target-parent="{{ $category->id }}"
                                                 title="Click to collapse/expand subcategories"
                                                 style="width: 12px; height: 12px; background-color: {{ $category->color }}; cursor: pointer;"></div>
                                        @else
                                            <div class="rounded me-3" style="width: 12px; height: 12px; background-color: {{ $category->color }};"></div>
                                        @endif
                                        <div>
                                            <span class="d-inline-flex align-items-center gap-2">
                                                <strong @if($category->description) data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $category->description }}" @endif style="cursor: {{ $category->description ? 'help' : 'default' }}">{{ $category->name }}</strong>
                                                @if(!$category->parent_id)
                                                    @if($category->tier == 1)
                                                        <span class="badge bg-label-primary" data-bs-toggle="tooltip" title="From Total Revenue">R</span>
                                                    @else
                                                        <span class="badge bg-label-warning" data-bs-toggle="tooltip" title="From Net Income">NI</span>
                                                    @endif
                                                    @if($category->expenseType)
                                                        <span class="badge" style="background-color: {{ $category->expenseType->color }}; color: white;">
                                                            {{ $category->expenseType->code }}
                                                        </span>
                                                    @endif
                                                @endif
                                            </span>
                                            @if($category->name_ar)
                                                <br><span class="text-muted" dir="rtl">{{ $category->name_ar }}</span>
                                            @endif
                                            @if($category->parent)
                                                <br><small class="text-muted">Under: {{ $category->parent->full_name }}</small>
                                            @elseif($category->subcategories->count() > 0)
                                                <br><small class="text-info">{{ $category->subcategories->count() }} direct subcategories</small>
                                            @endif
                                            @if(!$category->is_active)
                                                <br><small class="text-muted fst-italic">Inactive</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if(!$category->parent_id && $category->budget_percentage > 0)
                                        <span class="badge bg-label-info">{{ number_format($category->budget_percentage, 2) }}%</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($category->planned_monthly > 0)
                                        <strong class="text-info">{{ number_format($category->planned_monthly, 0) }}</strong>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($category->planned_ytd > 0)
                                        <strong class="text-info">{{ number_format($category->planned_ytd, 0) }}</strong>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($category->available_monthly > 0)
                                        <strong class="text-info">{{ number_format($category->available_monthly, 0) }}</strong>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($category->available_ytd > 0)
                                        <strong class="text-info">{{ number_format($category->available_ytd, 0) }}</strong>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($category->ytd_total > 0)
                                        @php
                                            $variance = $category->available_ytd > 0 ? (($category->ytd_total - $category->available_ytd) / $category->available_ytd) * 100 : 0;
                                            $varianceClass = $category->ytd_total > $category->available_ytd ? 'text-danger' : 'text-success';
                                            $hasSubcategories = $category->subcategories && $category->subcategories->count() > 0;
                                        @endphp
                                        <strong class="{{ $category->available_ytd > 0 ? $varianceClass : 'text-warning' }}">{{ number_format($category->ytd_total, 0) }}</strong>
                                        @if($hasSubcategories)
                                            <i class="ti ti-hierarchy-2 text-info ms-1" data-bs-toggle="tooltip" title="Includes expenses from {{ $category->subcategories->count() }} subcategories"></i>
                                        @endif
                                        @if($category->available_ytd > 0)
                                            <br><small class="{{ $varianceClass }}">({{ $variance >= 0 ? '+' : '' }}{{ number_format($variance, 1) }}%)</small>
                                        @endif
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @php
                                        $remaining = ($category->available_ytd ?? 0) - ($category->ytd_total ?? 0);
                                        $remainingClass = $remaining >= 0 ? 'text-success' : 'text-danger';
                                    @endphp
                                    @if($category->available_ytd > 0 || $category->ytd_total > 0)
                                        <strong class="{{ $remainingClass }}">{{ number_format($remaining, 0) }}</strong>
                                    @else
                                        <span class="text-muted">-</span>
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
                                               data-name-ar="{{ $category->name_ar }}"
                                               data-description="{{ $category->description }}"
                                               data-color="{{ $category->color }}"
                                               data-parent-id="{{ $category->parent_id }}"
                                               data-expense-type-id="{{ $category->expense_type_id }}"
                                               data-sort-order="{{ $category->sort_order ?? 0 }}">
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
                                <td colspan="9" class="text-center py-5">
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
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_name" class="form-label">Category Name (English) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_name" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_name_ar" class="form-label">Category Name (Arabic)</label>
                            <input type="text" class="form-control" id="add_name_ar" name="name_ar" dir="rtl">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="add_parent_id" class="form-label">Parent Category <small class="text-muted">(Optional)</small></label>
                        <select class="form-select" id="add_parent_id" name="parent_id">
                            <option value="">── Main Category (Top Level)</option>
                            @foreach($parentCategories as $parentCategory)
                                <option value="{{ $parentCategory->id }}">
                                    {{ str_repeat('│  ', $parentCategory->tree_depth) }}├─ {{ $parentCategory->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Select a parent to create nested categories at any level</small>
                    </div>

                    <div class="mb-3" id="expense_type_field">
                        <label for="add_expense_type_id" class="form-label">Expense Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="add_expense_type_id" name="expense_type_id" required>
                            <option value="">Select expense type</option>
                            @foreach($expenseTypes as $expenseType)
                                <option value="{{ $expenseType->id }}" data-color="{{ $expenseType->color }}">
                                    {{ $expenseType->code }} - {{ $expenseType->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Only required for main categories. Subcategories inherit their parent's type.</small>
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
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_name" class="form-label">Category Name (English) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_name_ar" class="form-label">Category Name (Arabic)</label>
                            <input type="text" class="form-control" id="edit_name_ar" name="name_ar" dir="rtl">
                        </div>
                    </div>

                    <div class="mb-3" id="edit_expense_type_field">
                        <label for="edit_expense_type_id" class="form-label">Expense Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_expense_type_id" name="expense_type_id" required>
                            <option value="">Select expense type</option>
                            @foreach($expenseTypes as $expenseType)
                                <option value="{{ $expenseType->id }}" data-color="{{ $expenseType->color }}">
                                    {{ $expenseType->code }} - {{ $expenseType->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Only available for main categories. Subcategories inherit their parent's type.</small>
                    </div>

                    <div class="mb-3">
                        <label for="edit_sort_order" class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="edit_sort_order" name="sort_order" min="0" placeholder="0">
                        <small class="text-muted">Categories are sorted by tier first, then by this order within each tier (lower numbers appear first)</small>
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

    // Handle parent category selection to show/hide expense type field
    const parentSelect = document.getElementById('add_parent_id');
    const expenseTypeField = document.getElementById('expense_type_field');
    const expenseTypeSelect = document.getElementById('add_expense_type_id');

    function toggleExpenseTypeField() {
        if (parentSelect.value === '') {
            // Main category - show expense type field and make it required
            expenseTypeField.style.display = 'block';
            expenseTypeSelect.required = true;
        } else {
            // Subcategory - hide expense type field and make it not required
            expenseTypeField.style.display = 'none';
            expenseTypeSelect.required = false;
            expenseTypeSelect.value = '';
        }
    }

    parentSelect.addEventListener('change', toggleExpenseTypeField);
    toggleExpenseTypeField(); // Initialize on page load

    // Handle edit expense type field visibility
    function toggleEditExpenseTypeField(isMainCategory) {
        const editExpenseTypeField = document.getElementById('edit_expense_type_field');
        const editExpenseTypeSelect = document.getElementById('edit_expense_type_id');

        if (isMainCategory) {
            // Main category - show expense type field and make it required
            editExpenseTypeField.style.display = 'block';
            editExpenseTypeSelect.required = true;
        } else {
            // Subcategory - hide expense type field and make it not required
            editExpenseTypeField.style.display = 'none';
            editExpenseTypeSelect.required = false;
            editExpenseTypeSelect.value = '';
        }
    }

    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Edit category modal
    const editModal = document.getElementById('editCategoryModal');
    editModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const categoryId = button.getAttribute('data-id');
        const categoryName = button.getAttribute('data-name');
        const categoryNameAr = button.getAttribute('data-name-ar');
        const categoryDescription = button.getAttribute('data-description');
        const categoryColor = button.getAttribute('data-color');
        const parentId = button.getAttribute('data-parent-id');
        const expenseTypeId = button.getAttribute('data-expense-type-id');
        const sortOrder = button.getAttribute('data-sort-order');

        const form = document.getElementById('editCategoryForm');
        form.action = `/accounting/expenses/categories/${categoryId}`;

        document.getElementById('edit_name').value = categoryName;
        document.getElementById('edit_name_ar').value = categoryNameAr || '';
        document.getElementById('edit_description').value = categoryDescription || '';
        document.getElementById('edit_color').value = categoryColor;
        document.getElementById('edit_color_text').value = categoryColor.toUpperCase();
        document.getElementById('edit_sort_order').value = sortOrder || 0;

        // Handle expense type field based on whether this is a main category
        const isMainCategory = !parentId || parentId === 'null' || parentId === '';
        toggleEditExpenseTypeField(isMainCategory);

        if (isMainCategory && expenseTypeId) {
            document.getElementById('edit_expense_type_id').value = expenseTypeId;
        }
    });

    // Delete confirmation
    window.confirmDelete = function(categoryId, categoryName) {
        document.getElementById('deleteCategoryName').textContent = categoryName;
        document.getElementById('deleteCategoryForm').action = `/accounting/expenses/categories/${categoryId}`;

        const deleteModal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
        deleteModal.show();
    };

    // ========================================
    // Collapsible Categories & Filtering
    // ========================================

    // Get all category rows
    const categoryRows = document.querySelectorAll('.category-row');

    // Helper function to get all descendant rows of a category
    function getDescendantRows(parentId) {
        const descendants = [];
        const directChildren = document.querySelectorAll(`.category-row[data-parent-id="${parentId}"]`);

        directChildren.forEach(child => {
            descendants.push(child);
            const childId = child.dataset.categoryId;
            // Recursively get children of this child
            descendants.push(...getDescendantRows(childId));
        });

        return descendants;
    }

    // Helper function to check if a category should be visible based on empty filter
    function shouldShowCategory(row, hideEmpty) {
        if (!hideEmpty) return true;

        const isEmpty = row.dataset.isEmpty === 'true';
        if (!isEmpty) return true;

        // Even if empty, show if any descendants are not empty
        const categoryId = row.dataset.categoryId;
        const descendants = getDescendantRows(categoryId);

        for (const descendant of descendants) {
            if (descendant.dataset.isEmpty === 'false') {
                return true;
            }
        }

        return false;
    }

    // Helper function to check if parent is collapsed
    function isParentCollapsed(row) {
        let parentId = row.dataset.parentId;

        while (parentId) {
            const parentRow = document.querySelector(`.category-row[data-category-id="${parentId}"]`);
            if (!parentRow) break;

            const toggleBtn = parentRow.querySelector('.collapse-toggle');
            if (toggleBtn && toggleBtn.dataset.collapsed === 'true') {
                return true;
            }

            parentId = parentRow.dataset.parentId;
        }

        return false;
    }

    // Apply visibility based on both collapse state and empty filter
    function applyVisibility() {
        const hideEmpty = document.getElementById('hideEmptyCategories')?.checked ?? false;

        categoryRows.forEach(row => {
            const shouldShow = shouldShowCategory(row, hideEmpty);
            const parentCollapsed = isParentCollapsed(row);

            if (!shouldShow || parentCollapsed) {
                row.style.display = 'none';
            } else {
                row.style.display = '';
            }
        });
    }

    // Toggle collapse for a parent category
    function toggleCollapse(parentId, collapse) {
        const parentRow = document.querySelector(`.category-row[data-category-id="${parentId}"]`);
        const toggleDot = parentRow?.querySelector('.collapse-toggle');

        if (!toggleDot) return;

        const descendants = getDescendantRows(parentId);

        if (collapse) {
            toggleDot.dataset.collapsed = 'true';
            // Add visual indicator for collapsed state - ring/border
            toggleDot.style.boxShadow = '0 0 0 3px rgba(0,0,0,0.3)';
            descendants.forEach(d => d.style.display = 'none');
        } else {
            toggleDot.dataset.collapsed = 'false';
            // Remove visual indicator
            toggleDot.style.boxShadow = 'none';
            // Re-apply visibility considering the empty filter
            applyVisibility();
        }
    }

    // Individual collapse toggle (colored dots)
    document.querySelectorAll('.collapse-toggle').forEach(dot => {
        dot.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const parentId = this.dataset.targetParent;
            const isCollapsed = this.dataset.collapsed === 'true';

            toggleCollapse(parentId, !isCollapsed);
        });
    });

    // Expand All button
    document.getElementById('expandAllBtn')?.addEventListener('click', function() {
        document.querySelectorAll('.collapse-toggle').forEach(dot => {
            dot.dataset.collapsed = 'false';
            dot.style.boxShadow = 'none';
        });
        applyVisibility();
    });

    // Collapse All button
    document.getElementById('collapseAllBtn')?.addEventListener('click', function() {
        // Only collapse top-level parent categories (those without parent_id)
        document.querySelectorAll('.category-row[data-has-children="true"]').forEach(row => {
            if (!row.dataset.parentId) {
                const parentId = row.dataset.categoryId;
                toggleCollapse(parentId, true);
            }
        });
    });

    // Hide empty categories toggle
    document.getElementById('hideEmptyCategories')?.addEventListener('change', function() {
        applyVisibility();
    });
});
</script>
@endsection