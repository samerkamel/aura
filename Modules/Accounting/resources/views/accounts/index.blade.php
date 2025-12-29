@extends('layouts/layoutMaster')

@section('title', 'Account Management')

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
                                <h6 class="card-title mb-1">Total Accounts</h6>
                                <h4 class="mb-0">{{ $statistics['total_accounts'] }}</h4>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial rounded bg-label-primary">
                                    <i class="ti tabler-credit-card ti-md"></i>
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
                                <h6 class="card-title mb-1">Active Accounts</h6>
                                <h4 class="mb-0">{{ $statistics['active_accounts'] }}</h4>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial rounded bg-label-success">
                                    <i class="ti tabler-check ti-md"></i>
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
                                <h6 class="card-title mb-1">Total Balance</h6>
                                <h4 class="mb-0">EGP {{ number_format($statistics['total_balance'], 2) }}</h4>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial rounded bg-label-info">
                                    <i class="ti tabler-wallet ti-md"></i>
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
                                <h6 class="card-title mb-1">Account Types</h6>
                                <h4 class="mb-0">{{ count($statistics['account_types']) }}</h4>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial rounded bg-label-warning">
                                    <i class="ti tabler-category ti-md"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accounts Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Financial Accounts</h5>
                    <small class="text-muted">Manage your financial accounts and track balances</small>
                </div>
                <a href="{{ route('accounting.accounts.create') }}" class="btn btn-primary">
                    <i class="ti tabler-plus me-1"></i>New Account
                </a>
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
                <form method="GET" action="{{ route('accounting.accounts.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="type" class="form-label">Filter by Type</label>
                            <select name="type" id="type" class="form-select">
                                <option value="">All Types</option>
                                <option value="cash" {{ request('type') === 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="bank" {{ request('type') === 'bank' ? 'selected' : '' }}>Bank Account</option>
                                <option value="credit_card" {{ request('type') === 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                                <option value="digital_wallet" {{ request('type') === 'digital_wallet' ? 'selected' : '' }}>Digital Wallet</option>
                                <option value="other" {{ request('type') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" name="search" id="search" class="form-control"
                                   value="{{ request('search') }}" placeholder="Account name, number, or bank">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ti tabler-search me-1"></i>Filter
                            </button>
                        </div>
                    </div>
                    @if(request()->hasAny(['type', 'status', 'search']))
                        <div class="row mt-2">
                            <div class="col-12">
                                <a href="{{ route('accounting.accounts.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="ti tabler-x me-1"></i>Clear Filters
                                </a>
                            </div>
                        </div>
                    @endif
                </form>
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Account Details</th>
                            <th>Type</th>
                            <th>Account Info</th>
                            <th>Starting Balance</th>
                            <th>Current Balance</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse($accounts as $account)
                            <tr>
                                <td>
                                    <div>
                                        <strong>{{ $account->name }}</strong>
                                        @if($account->description)
                                            <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($account->description, 50) }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ $account->type_badge_class }}">
                                        {{ $account->type_display }}
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        @if($account->bank_name)
                                            <strong>{{ $account->bank_name }}</strong><br>
                                        @endif
                                        @if($account->account_number)
                                            <small class="text-muted">{{ $account->account_number }}</small>
                                        @else
                                            <small class="text-muted">No account number</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ $account->formatted_balance }}</strong>
                                </td>
                                <td>
                                    <div>
                                        <strong class="{{ $account->current_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($account->current_balance, 2) }} {{ $account->currency }}
                                        </strong>
                                        @php
                                            $difference = $account->current_balance - $account->starting_balance;
                                        @endphp
                                        @if($difference != 0)
                                            <br><small class="{{ $difference > 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $difference > 0 ? '+' : '' }}{{ number_format($difference, 2) }}
                                            </small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $account->is_active ? 'success' : 'secondary' }}">
                                        {{ $account->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                            <i class="ti tabler-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="{{ route('accounting.accounts.show', $account) }}">
                                                <i class="ti tabler-eye me-2"></i>View Details
                                            </a>
                                            <a class="dropdown-item" href="{{ route('accounting.accounts.edit', $account) }}">
                                                <i class="ti tabler-edit me-2"></i>Edit Account
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <form action="{{ route('accounting.accounts.toggle-status', $account) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="dropdown-item">
                                                    <i class="ti tabler-{{ $account->is_active ? 'pause' : 'play' }} me-2"></i>
                                                    {{ $account->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                            <div class="dropdown-divider"></div>
                                            <form action="{{ route('accounting.accounts.destroy', $account) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this account? This action cannot be undone.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="ti tabler-trash me-2"></i>Delete Account
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="ti tabler-credit-card text-muted mb-3" style="font-size: 4rem;"></i>
                                        <h5>No accounts found</h5>
                                        <p class="text-muted">Create your first financial account to start tracking expenses</p>
                                        <a href="{{ route('accounting.accounts.create') }}" class="btn btn-primary">
                                            <i class="ti tabler-plus me-1"></i>Create Account
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($accounts->hasPages())
                <div class="card-footer">
                    {{ $accounts->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection