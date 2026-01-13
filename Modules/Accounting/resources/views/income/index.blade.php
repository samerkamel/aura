@extends('layouts/layoutMaster')

@section('title', 'Contracts & Payment Milestones')

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
                                <h6 class="card-title mb-1">Total Contracts</h6>
                                <h4 class="mb-0">{{ $statistics['total_contracts'] }}</h4>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial rounded bg-label-primary">
                                    <i class="ti ti-file-text ti-md"></i>
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
                                <h6 class="card-title mb-1">Active Contracts</h6>
                                <h4 class="mb-0">{{ $statistics['active_contracts'] }}</h4>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial rounded bg-label-success">
                                    <i class="ti ti-check ti-md"></i>
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
                                <h6 class="card-title mb-1">Total Contract Value</h6>
                                <h4 class="mb-0">EGP {{ number_format($statistics['total_contract_value'], 0) }}</h4>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial rounded bg-label-info">
                                    <i class="ti ti-currency-dollar ti-md"></i>
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
                                <h6 class="card-title mb-1">Payments Scheduled</h6>
                                <h4 class="mb-0">EGP {{ number_format($statistics['total_payments_scheduled'], 0) }}</h4>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial rounded bg-label-warning">
                                    <i class="ti ti-calendar-dollar ti-md"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contracts Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Contracts Management</h5>
                    <small class="text-muted">Manage client contracts and their payment milestones</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('accounting.income.contracts.mass-entry') }}" class="btn btn-outline-primary">
                        <i class="ti ti-table me-1"></i>Mass Entry
                    </a>
                    <a href="{{ route('accounting.income.contracts.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>New Contract
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Contract #</th>
                            <th>Client / Project</th>
                            <th>Value</th>
                            <th>Duration</th>
                            <th>Payment Progress</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse($contracts as $contract)
                            <tr>
                                <td>
                                    <a href="{{ route('accounting.income.contracts.show', $contract) }}" class="fw-bold text-primary">
                                        {{ $contract->contract_number }}
                                    </a>
                                    @if($contract->description)
                                        <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($contract->description, 30) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div>
                                        @if($contract->customer)
                                            <a href="{{ route('administration.customers.show', $contract->customer) }}" class="fw-semibold">
                                                {{ $contract->client_name }}
                                            </a>
                                        @else
                                            <span class="fw-semibold">{{ $contract->client_name }}</span>
                                        @endif
                                        @if($contract->projects->isNotEmpty())
                                            <div class="mt-1">
                                                @foreach($contract->projects->take(2) as $project)
                                                    <a href="{{ route('projects.show', $project) }}" class="badge bg-label-primary me-1" title="{{ $project->name }}">
                                                        <i class="ti ti-folder ti-xs me-1"></i>{{ $project->code ?? \Illuminate\Support\Str::limit($project->name, 15) }}
                                                    </a>
                                                @endforeach
                                                @if($contract->projects->count() > 2)
                                                    <span class="badge bg-label-secondary">+{{ $contract->projects->count() - 2 }} more</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <strong class="text-success">EGP {{ number_format($contract->total_amount, 2) }}</strong>
                                </td>
                                <td>
                                    @if($contract->start_date && $contract->end_date)
                                    <div>
                                        <small class="text-muted">{{ $contract->start_date->format('M j, Y') }}</small>
                                        <br><small class="text-muted">to {{ $contract->end_date->format('M j, Y') }}</small>
                                    </div>
                                    @else
                                        <small class="text-muted">Ongoing</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <small class="text-muted">{{ $contract->payments->count() }} payments</small>
                                                <small class="fw-medium">{{ number_format($contract->payment_progress_percentage, 1) }}%</small>
                                            </div>
                                            <div class="progress" style="height: 4px;">
                                                <div class="progress-bar bg-success" role="progressbar"
                                                     style="width: {{ $contract->payment_progress_percentage }}%"
                                                     aria-valuenow="{{ $contract->payment_progress_percentage }}"
                                                     aria-valuemin="0" aria-valuemax="100">
                                                </div>
                                            </div>
                                            <small class="text-success">EGP {{ number_format($contract->paid_amount, 0) }} paid</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $contract->status === 'active' ? 'success' : ($contract->status === 'completed' ? 'info' : 'warning') }}">
                                        {{ ucfirst($contract->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="{{ route('accounting.income.contracts.show', $contract) }}">
                                                <i class="ti ti-eye me-2"></i>View Details & Payments
                                            </a>
                                            <a class="dropdown-item" href="{{ route('accounting.income.contracts.edit', $contract) }}">
                                                <i class="ti ti-edit me-2"></i>Edit Contract
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <form action="{{ route('accounting.income.contracts.toggle-status', $contract) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="dropdown-item">
                                                    <i class="ti ti-{{ $contract->is_active ? 'pause' : 'play' }} me-2"></i>
                                                    {{ $contract->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                            <div class="dropdown-divider"></div>
                                            <form action="{{ route('accounting.income.contracts.destroy', $contract) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this contract? This will also delete all associated payments.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="ti ti-trash me-2"></i>Delete Contract
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="mb-3">
                                        <i class="ti ti-file-text" style="font-size: 3rem; color: #ddd;"></i>
                                    </div>
                                    <h6 class="text-muted">No contracts found</h6>
                                    <p class="text-muted mb-3">Create your first contract to start managing income and payments.</p>
                                    <a href="{{ route('accounting.income.contracts.create') }}" class="btn btn-primary">
                                        <i class="ti ti-plus me-1"></i>Create First Contract
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($contracts->hasPages())
                <div class="card-footer">
                    {{ $contracts->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection