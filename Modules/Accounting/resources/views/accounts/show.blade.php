@extends('layouts/layoutMaster')

@section('title', 'Account Details - ' . $account->name)

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">{{ $account->name }}</h5>
                    <small class="text-muted">{{ $account->type_display }} Account</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('accounting.accounts.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Back to Accounts
                    </a>
                    <a href="{{ route('accounting.accounts.edit', $account) }}" class="btn btn-primary">
                        <i class="ti ti-edit me-1"></i>Edit Account
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card-body">
                <div class="row">
                    <!-- Account Information -->
                    <div class="col-lg-8">
                        <!-- Account Details -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Account Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Account Name</label>
                                        <div class="fw-medium">{{ $account->name }}</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Account Type</label>
                                        <div>
                                            <span class="badge {{ $account->type_badge_class }}">
                                                {{ $account->type_display }}
                                            </span>
                                        </div>
                                    </div>
                                    @if($account->bank_name)
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Bank Name</label>
                                            <div class="fw-medium">{{ $account->bank_name }}</div>
                                        </div>
                                    @endif
                                    @if($account->account_number)
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Account Number</label>
                                            <div class="fw-medium">{{ $account->account_number }}</div>
                                        </div>
                                    @endif
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Currency</label>
                                        <div class="fw-medium">{{ $account->currency }}</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Status</label>
                                        <div>
                                            <span class="badge bg-{{ $account->is_active ? 'success' : 'secondary' }}">
                                                {{ $account->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>
                                    @if($account->description)
                                        <div class="col-12 mb-3">
                                            <label class="form-label">Description</label>
                                            <div>{{ $account->description }}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Recent Transactions -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Recent Expense Payments</h6>
                            </div>
                            @if($account->expenseSchedules->count() > 0)
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Expense</th>
                                                <th>Category</th>
                                                <th>Amount</th>
                                                <th>Payment Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($account->expenseSchedules as $expense)
                                                <tr>
                                                    <td>
                                                        <div>
                                                            <strong>{{ $expense->name }}</strong>
                                                            @if($expense->description)
                                                                <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($expense->description, 40) }}</small>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">{{ $expense->full_category_name }}</small>
                                                    </td>
                                                    <td>
                                                        <strong class="text-danger">-{{ number_format($expense->paid_amount ?? $expense->amount, 2) }} {{ $account->currency }}</strong>
                                                    </td>
                                                    <td>
                                                        @if($expense->paid_date)
                                                            {{ $expense->paid_date->format('M d, Y') }}
                                                        @else
                                                            <span class="text-muted">Not paid</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge {{ $expense->payment_status_badge_class }}">
                                                            {{ $expense->payment_status_display }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="card-body text-center py-5">
                                    <i class="ti ti-receipt text-muted mb-3" style="font-size: 3rem;"></i>
                                    <h6 class="text-muted">No expense payments</h6>
                                    <p class="text-muted mb-0">No expenses have been paid from this account yet.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-lg-4">
                        <!-- Balance Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Balance Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label">Starting Balance</label>
                                    <div class="h5 mb-0">{{ number_format($account->starting_balance, 2) }} {{ $account->currency }}</div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Current Balance</label>
                                    <div class="h4 mb-1 {{ $account->current_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($account->current_balance, 2) }} {{ $account->currency }}
                                    </div>
                                    @php
                                        $difference = $account->current_balance - $account->starting_balance;
                                    @endphp
                                    @if($difference != 0)
                                        <small class="{{ $difference > 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $difference > 0 ? '+' : '' }}{{ number_format($difference, 2) }} {{ $account->currency }}
                                            from starting balance
                                        </small>
                                    @endif
                                </div>
                                <div class="progress mb-3" style="height: 8px;">
                                    @php
                                        $maxBalance = max(abs($account->starting_balance), abs($account->current_balance), 1000);
                                        $startPercent = (($account->starting_balance + $maxBalance) / ($maxBalance * 2)) * 100;
                                        $currentPercent = (($account->current_balance + $maxBalance) / ($maxBalance * 2)) * 100;
                                    @endphp
                                    <div class="progress-bar bg-info" role="progressbar"
                                         style="width: {{ min($startPercent, $currentPercent) }}%"></div>
                                    <div class="progress-bar bg-{{ $account->current_balance >= $account->starting_balance ? 'success' : 'warning' }}"
                                         role="progressbar"
                                         style="width: {{ abs($currentPercent - $startPercent) }}%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Account Statistics</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>Total Expenses Paid</span>
                                        <strong class="text-danger">{{ number_format($statistics['total_expenses_paid'], 2) }} {{ $account->currency }}</strong>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>Pending Expenses</span>
                                        <strong>{{ $statistics['pending_expenses'] }}</strong>
                                    </div>
                                </div>
                                <div class="mb-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>Last Transaction</span>
                                        <strong>
                                            @if($statistics['last_transaction_date'])
                                                {{ $statistics['last_transaction_date']->format('M d, Y') }}
                                            @else
                                                <span class="text-muted">None</span>
                                            @endif
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Account Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="{{ route('accounting.accounts.edit', $account) }}" class="btn btn-outline-primary">
                                        <i class="ti ti-edit me-2"></i>Edit Account Details
                                    </a>
                                    <form action="{{ route('accounting.accounts.toggle-status', $account) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline-{{ $account->is_active ? 'warning' : 'success' }} w-100">
                                            <i class="ti ti-{{ $account->is_active ? 'pause' : 'play' }} me-2"></i>
                                            {{ $account->is_active ? 'Deactivate' : 'Activate' }} Account
                                        </button>
                                    </form>
                                    @if($account->expenseSchedules->count() === 0)
                                        <form action="{{ route('accounting.accounts.destroy', $account) }}" method="POST"
                                              onsubmit="return confirm('Are you sure you want to delete this account? This action cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger w-100">
                                                <i class="ti ti-trash me-2"></i>Delete Account
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection