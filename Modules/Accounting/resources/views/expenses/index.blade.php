@extends('layouts/layoutMaster')

@section('title', 'Expense Schedules')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Expense Schedules</h5>
                    <small class="text-muted">Manage recurring expenses and their schedules</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('accounting.expenses.paid') }}" class="btn btn-outline-info">
                        <i class="ti tabler-receipt me-1"></i>Paid Expenses
                    </a>
                    <a href="{{ route('accounting.expenses.categories') }}" class="btn btn-outline-secondary">
                        <i class="ti tabler-category me-1"></i>Categories
                    </a>
                    <a href="{{ route('accounting.expenses.create') }}" class="btn btn-primary">
                        <i class="ti tabler-plus me-1"></i>New Schedule
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
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Frequency</label>
                        <select name="frequency" class="form-select">
                            <option value="">All Frequencies</option>
                            <option value="weekly" {{ request('frequency') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="bi-weekly" {{ request('frequency') === 'bi-weekly' ? 'selected' : '' }}>Bi-weekly</option>
                            <option value="monthly" {{ request('frequency') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="quarterly" {{ request('frequency') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                            <option value="yearly" {{ request('frequency') === 'yearly' ? 'selected' : '' }}>Yearly</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="ti tabler-search me-1"></i>Filter
                        </button>
                        <a href="{{ route('accounting.expenses.index') }}" class="btn btn-outline-secondary">
                            <i class="ti tabler-x me-1"></i>Clear
                        </a>
                    </div>
                </form>
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="form-check-input" id="selectAll"></th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Frequency</th>
                            <th>Next Payment</th>
                            <th>Monthly Equiv.</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse($expenseSchedules as $schedule)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input schedule-checkbox" value="{{ $schedule->id }}">
                                </td>
                                <td>
                                    <div>
                                        <strong>{{ $schedule->name }}</strong>
                                        @if($schedule->description)
                                            <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($schedule->description, 50) }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill" style="background-color: {{ $schedule->category->color }}20; color: {{ $schedule->category->color }}; border: 1px solid {{ $schedule->category->color }}40;">
                                        {{ $schedule->category->name }}
                                    </span>
                                </td>
                                <td>
                                    <strong>{{ number_format($schedule->amount, 2) }} EGP</strong>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ ucfirst(str_replace('-', ' ', $schedule->frequency_type)) }}
                                        @if($schedule->frequency_value > 1)
                                            ({{ $schedule->frequency_value }}x)
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    @if($schedule->next_payment_date)
                                        {{ $schedule->next_payment_date->format('M j, Y') }}
                                        <br><small class="text-muted">{{ $schedule->next_payment_date->diffForHumans() }}</small>
                                    @else
                                        <span class="text-muted">Not calculated</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-warning">{{ number_format($schedule->monthly_equivalent_amount, 2) }} EGP</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $schedule->is_active ? 'success' : 'secondary' }}">
                                        {{ $schedule->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                            <i class="ti tabler-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="{{ route('accounting.expenses.show', $schedule) }}">
                                                <i class="ti tabler-eye me-2"></i>View Details
                                            </a>
                                            <a class="dropdown-item" href="{{ route('accounting.expenses.edit', $schedule) }}">
                                                <i class="ti tabler-edit me-2"></i>Edit
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <form action="{{ route('accounting.expenses.toggle-status', $schedule) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="dropdown-item">
                                                    <i class="ti tabler-{{ $schedule->is_active ? 'pause' : 'play' }} me-2"></i>
                                                    {{ $schedule->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                            <div class="dropdown-divider"></div>
                                            <form action="{{ route('accounting.expenses.destroy', $schedule) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger"
                                                        onclick="return confirm('Are you sure you want to delete this expense schedule?')">
                                                    <i class="ti tabler-trash me-2"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="ti tabler-coin-off text-muted mb-3" style="font-size: 4rem;"></i>
                                        <h5>No expense schedules found</h5>
                                        <p class="text-muted">Start by creating your first recurring expense schedule</p>
                                        <a href="{{ route('accounting.expenses.create') }}" class="btn btn-primary">
                                            <i class="ti tabler-plus me-1"></i>Create Expense Schedule
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($expenseSchedules->count() > 0)
                <!-- Bulk Actions -->
                <div class="card-footer">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center" style="display: none;" id="bulkActions">
                                <span class="me-3">
                                    <span id="selectedCount">0</span> items selected
                                </span>
                                <form method="POST" action="{{ route('accounting.expenses.bulk-action') }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="schedules" id="selectedSchedules">
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="action" value="activate" class="btn btn-sm btn-success">
                                            <i class="ti tabler-play me-1"></i>Activate
                                        </button>
                                        <button type="submit" name="action" value="deactivate" class="btn btn-sm btn-warning">
                                            <i class="ti tabler-pause me-1"></i>Deactivate
                                        </button>
                                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to delete the selected schedules?')">
                                            <i class="ti tabler-trash me-1"></i>Delete
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-6">
                            {{ $expenseSchedules->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const scheduleCheckboxes = document.querySelectorAll('.schedule-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    const selectedSchedules = document.getElementById('selectedSchedules');

    function updateBulkActions() {
        const checkedBoxes = document.querySelectorAll('.schedule-checkbox:checked');
        const count = checkedBoxes.length;

        selectedCount.textContent = count;
        bulkActions.style.display = count > 0 ? 'flex' : 'none';

        const scheduleIds = Array.from(checkedBoxes).map(cb => cb.value);
        selectedSchedules.value = JSON.stringify(scheduleIds);

        selectAllCheckbox.indeterminate = count > 0 && count < scheduleCheckboxes.length;
        selectAllCheckbox.checked = count === scheduleCheckboxes.length;
    }

    selectAllCheckbox.addEventListener('change', function() {
        scheduleCheckboxes.forEach(cb => cb.checked = this.checked);
        updateBulkActions();
    });

    scheduleCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkActions);
    });
});
</script>
@endsection