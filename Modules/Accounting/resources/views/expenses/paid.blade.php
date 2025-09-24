@extends('layouts/layoutMaster')

@section('title', 'Paid Expenses')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title mb-1">Total Paid Expenses</h6>
                                <h4 class="mb-0">{{ $statistics['total_count'] }}</h4>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial rounded bg-label-primary">
                                    <i class="ti ti-receipt ti-md"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title mb-1">Total Amount</h6>
                                <h4 class="mb-0">EGP {{ number_format($statistics['total_paid'], 2) }}</h4>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial rounded bg-label-danger">
                                    <i class="ti ti-wallet ti-md"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title mb-1">This Month</h6>
                                <h4 class="mb-0">EGP {{ number_format($statistics['this_month_total'], 2) }}</h4>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial rounded bg-label-warning">
                                    <i class="ti ti-calendar ti-md"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title mb-1">This Year</h6>
                                <h4 class="mb-0">EGP {{ number_format($statistics['this_year_total'], 2) }}</h4>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial rounded bg-label-success">
                                    <i class="ti ti-chart-line ti-md"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Paid Expenses Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Paid Expenses</h5>
                    <small class="text-muted">View all expenses that have been paid (one-time and scheduled)</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('accounting.expenses.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-calendar me-1"></i>Expense Schedules
                    </a>
                    <a href="{{ route('accounting.expenses.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>New Expense
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Filters -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('accounting.expenses.paid') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label for="start_date" class="form-label">From Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control"
                                   value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="end_date" class="form-label">To Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control"
                                   value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="category_id" class="form-label">Category</label>
                            <select name="category_id" id="category_id" class="form-select">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="account_id" class="form-label">Account</label>
                            <select name="account_id" id="account_id" class="form-select">
                                <option value="">All Accounts</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}" {{ request('account_id') == $account->id ? 'selected' : '' }}>
                                        {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="expense_type" class="form-label">Type</label>
                            <select name="expense_type" id="expense_type" class="form-select">
                                <option value="">All Types</option>
                                <option value="one_time" {{ request('expense_type') === 'one_time' ? 'selected' : '' }}>One-time</option>
                                <option value="recurring" {{ request('expense_type') === 'recurring' ? 'selected' : '' }}>Scheduled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ti ti-search me-1"></i>Filter
                            </button>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-10">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" name="search" id="search" class="form-control"
                                   value="{{ request('search') }}" placeholder="Search by expense name">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            @if(request()->hasAny(['start_date', 'end_date', 'category_id', 'account_id', 'expense_type', 'search']))
                                <a href="{{ route('accounting.expenses.paid') }}" class="btn btn-outline-secondary w-100">
                                    <i class="ti ti-x me-1"></i>Clear
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Expense Details</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Paid Date</th>
                            <th>Amount</th>
                            <th>Account</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse($paidExpenses as $expense)
                            <tr>
                                <td>
                                    <div>
                                        <strong>{{ $expense->name }}</strong>
                                        @if($expense->description)
                                            <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($expense->description, 50) }}</small>
                                        @endif
                                        @if($expense->payment_notes)
                                            <br><small class="text-info"><i class="ti ti-note me-1"></i>{{ \Illuminate\Support\Str::limit($expense->payment_notes, 40) }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span class="badge" style="background-color: {{ $expense->category->color }}20; color: {{ $expense->category->color }};">
                                            {{ $expense->category->name }}
                                        </span>
                                        @if($expense->subcategory)
                                            <br><small class="text-muted">{{ $expense->subcategory->name }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $expense->expense_type === 'one_time' ? 'warning' : 'info' }}">
                                        {{ $expense->expense_type === 'one_time' ? 'One-time' : 'Scheduled' }}
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <strong>{{ $expense->paid_date ? $expense->paid_date->format('M d, Y') : 'N/A' }}</strong>
                                        @if($expense->paid_date)
                                            <br><small class="text-muted">{{ $expense->paid_date->diffForHumans() }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong class="text-danger">{{ number_format($expense->paid_amount ?? $expense->amount, 2) }} EGP</strong>
                                        @if($expense->paid_amount != $expense->amount)
                                            <br><small class="text-muted">Original: {{ number_format($expense->amount, 2) }} EGP</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($expense->paidFromAccount)
                                        <div>
                                            <strong>{{ $expense->paidFromAccount->name }}</strong>
                                            <br><span class="badge {{ $expense->paidFromAccount->type_badge_class }}">
                                                {{ $expense->paidFromAccount->type_display }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-muted">No account specified</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="{{ route('accounting.expenses.show', $expense) }}">
                                                <i class="ti ti-eye me-2"></i>View Details
                                            </a>
                                            @if($expense->expense_type === 'recurring')
                                                <a class="dropdown-item" href="{{ route('accounting.expenses.edit', $expense) }}">
                                                    <i class="ti ti-edit me-2"></i>Edit Schedule
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="ti ti-receipt-off text-muted mb-3" style="font-size: 4rem;"></i>
                                        <h5>No paid expenses found</h5>
                                        <p class="text-muted">No expenses have been marked as paid yet, or they don't match your current filters</p>
                                        <a href="{{ route('accounting.expenses.create') }}" class="btn btn-primary">
                                            <i class="ti ti-plus me-1"></i>Create First Expense
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($paidExpenses->hasPages())
                <div class="card-footer">
                    {{ $paidExpenses->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection