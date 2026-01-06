@extends('layouts.layoutMaster')

@section('title', 'Linked Expenses - ' . $project->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Linked Expenses</h4>
            <p class="text-muted mb-0">{{ $project->name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('projects.finance.index', $project) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back to Finance
            </a>
            <a href="{{ route('accounting.expenses.create', ['project_id' => $project->id]) }}" class="btn btn-warning">
                <i class="ti ti-plus me-1"></i> New Expense
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted">Total Expenses</span>
                            <div class="d-flex align-items-center mt-2">
                                <h4 class="mb-0 me-2">{{ number_format($totals['total_amount'], 2) }}</h4>
                            </div>
                        </div>
                        <span class="avatar avatar-lg rounded">
                            <span class="avatar-initial bg-label-warning rounded"><i class="ti ti-receipt ti-26px"></i></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted">Paid</span>
                            <div class="d-flex align-items-center mt-2">
                                <h4 class="mb-0 me-2 text-success">{{ number_format($totals['paid_amount'], 2) }}</h4>
                            </div>
                        </div>
                        <span class="avatar avatar-lg rounded">
                            <span class="avatar-initial bg-label-success rounded"><i class="ti ti-check ti-26px"></i></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted">Pending</span>
                            <div class="d-flex align-items-center mt-2">
                                <h4 class="mb-0 me-2 text-warning">{{ number_format($totals['pending_amount'], 2) }}</h4>
                            </div>
                        </div>
                        <span class="avatar avatar-lg rounded">
                            <span class="avatar-initial bg-label-warning rounded"><i class="ti ti-clock ti-26px"></i></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Expenses Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Expenses ({{ $expenses->total() }})</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center">Payment Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                        <tr>
                            <td>
                                <a href="{{ route('accounting.expenses.show', $expense) }}" class="fw-semibold">
                                    {{ $expense->name }}
                                </a>
                                @if($expense->description)
                                    <br><small class="text-muted">{{ Str::limit($expense->description, 50) }}</small>
                                @endif
                            </td>
                            <td>
                                {{ $expense->category?->name ?? '-' }}
                                @if($expense->subcategory)
                                    <br><small class="text-muted">{{ $expense->subcategory->name }}</small>
                                @endif
                            </td>
                            <td>
                                @if($expense->expense_type === 'recurring')
                                    <span class="badge bg-info">Recurring</span>
                                @else
                                    <span class="badge bg-secondary">One-time</span>
                                @endif
                            </td>
                            <td>{{ $expense->start_date?->format('M d, Y') ?? $expense->expense_date?->format('M d, Y') ?? '-' }}</td>
                            <td class="text-end">{{ number_format($expense->amount, 2) }}</td>
                            <td class="text-center">
                                @php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'paid' => 'success',
                                        'partially_paid' => 'info',
                                        'cancelled' => 'secondary',
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$expense->payment_status] ?? 'secondary' }}">
                                    {{ ucfirst(str_replace('_', ' ', $expense->payment_status)) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('accounting.expenses.show', $expense) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-eye"></i>
                                </a>
                                <a href="{{ route('accounting.expenses.edit', $expense) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="ti ti-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="ti ti-receipt-off ti-lg text-muted mb-3 d-block"></i>
                                <p class="text-muted mb-3">No expenses linked to this project</p>
                                <a href="{{ route('accounting.expenses.create', ['project_id' => $project->id]) }}" class="btn btn-warning">
                                    <i class="ti ti-plus me-1"></i> Add Expense
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($expenses->hasPages())
            <div class="card-footer">
                {{ $expenses->links() }}
            </div>
        @endif
    </div>
@endsection
