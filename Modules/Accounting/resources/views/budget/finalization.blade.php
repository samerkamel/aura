@extends('layouts/layoutMaster')

@section('title', 'Budget ' . $budget->year . ' - Finalization')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Budget {{ $budget->year }} - Finalization</h1>
            <p class="text-muted mb-0">Review and finalize the budget</p>
        </div>
        <span class="badge bg-label-{{ $budget->status === 'finalized' ? 'success' : ($budget->status === 'in_progress' ? 'warning' : 'secondary') }} fs-6">
            {{ ucfirst(str_replace('_', ' ', $budget->status)) }}
        </span>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        {{-- Readiness Status --}}
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header {{ $readiness['is_ready'] ? 'bg-success' : 'bg-warning' }} text-white">
                    <h5 class="mb-0">
                        <i class="ti ti-{{ $readiness['is_ready'] ? 'check' : 'alert-triangle' }} me-2"></i>
                        {{ $readiness['is_ready'] ? 'Ready for Finalization' : 'Not Ready for Finalization' }}
                    </h5>
                </div>
                <div class="card-body">
                    @if($readiness['is_ready'])
                        <div class="alert alert-success mb-3">
                            <i class="ti ti-check-circle me-2"></i>
                            All requirements met. You can finalize this budget.
                        </div>
                    @else
                        <div class="alert alert-warning mb-3">
                            <i class="ti ti-alert-circle me-2"></i>
                            The following issues need to be resolved:
                        </div>
                        <ul class="list-group list-group-flush">
                            @foreach($readiness['errors'] as $section => $errors)
                                @foreach($errors as $error)
                                    <li class="list-group-item list-group-item-warning">
                                        <i class="ti ti-x text-danger me-2"></i>
                                        <strong>{{ ucfirst($section) }}:</strong> {{ $error }}
                                    </li>
                                @endforeach
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            {{-- Previous Year Comparison --}}
            @if(!empty($comparison))
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ti ti-chart-arrows me-2"></i>Year-over-Year Comparison</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-end">{{ $budget->year - 1 }}</th>
                                        <th class="text-end">{{ $budget->year }}</th>
                                        <th class="text-end">Change</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($comparison as $item)
                                        <tr>
                                            <td>{{ $item['product'] }}</td>
                                            <td class="text-end">{{ number_format($item['previous_year'], 0) }}</td>
                                            <td class="text-end">{{ number_format($item['current_year'], 0) }}</td>
                                            <td class="text-end">
                                                <span class="badge bg-label-{{ $item['difference_percentage'] >= 0 ? 'success' : 'danger' }}">
                                                    {{ $item['difference_percentage'] >= 0 ? '+' : '' }}{{ number_format($item['difference_percentage'], 1) }}%
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Checklist Summary --}}
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ti ti-list-check me-2"></i>Budget Checklist</h5>
                </div>
                <div class="card-body">
                    {{-- Result Completion --}}
                    <h6>Result Tab</h6>
                    <div class="mb-3">
                        <div class="progress mb-2" style="height: 20px;">
                            <div class="progress-bar {{ $readiness['checklist']['result_completion']['percentage'] == 100 ? 'bg-success' : 'bg-warning' }}"
                                 style="width: {{ $readiness['checklist']['result_completion']['percentage'] }}%">
                                {{ number_format($readiness['checklist']['result_completion']['percentage'], 0) }}%
                            </div>
                        </div>
                        <small class="text-muted">
                            {{ $readiness['checklist']['result_completion']['completed'] }} completed,
                            {{ $readiness['checklist']['result_completion']['pending'] }} pending
                        </small>
                    </div>

                    {{-- Personnel Summary --}}
                    <h6>Personnel</h6>
                    <ul class="list-unstyled mb-3">
                        <li><i class="ti ti-users me-2"></i>Employees: {{ $readiness['checklist']['personnel']['total_employees'] }}</li>
                        <li><i class="ti ti-user-plus me-2"></i>New Hires: {{ $readiness['checklist']['personnel']['new_hires'] }}</li>
                        <li><i class="ti ti-currency-dollar me-2"></i>Total Proposed: {{ number_format($readiness['checklist']['personnel']['total_proposed_salaries'], 0) }}</li>
                    </ul>

                    {{-- Expenses Summary --}}
                    <h6>Expenses</h6>
                    <ul class="list-unstyled mb-3">
                        <li><i class="ti ti-building-store me-2"></i>OpEx: {{ number_format($readiness['checklist']['expenses']['opex'], 0) }}</li>
                        <li><i class="ti ti-receipt-tax me-2"></i>Taxes: {{ number_format($readiness['checklist']['expenses']['taxes'], 0) }}</li>
                        <li><i class="ti ti-building me-2"></i>CapEx: {{ number_format($readiness['checklist']['expenses']['capex'], 0) }}</li>
                    </ul>

                    {{-- Budget Summary --}}
                    <h6>Budget Totals</h6>
                    <div class="bg-light p-3 rounded">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Revenue Budget:</span>
                            <strong class="text-success">{{ number_format($readiness['checklist']['budget_summary']['total_final_budget'], 0) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Total Expenses:</span>
                            <strong class="text-danger">{{ number_format($readiness['checklist']['expenses']['total_expenses'], 0) }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Finalization History --}}
            @if($history)
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="ti ti-clock-check me-2"></i>Finalization Record</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Finalized At:</strong> {{ $history['finalized_at']->format('F j, Y, g:i a') }}</p>
                        <p class="mb-0"><strong>Finalized By:</strong> {{ $history['finalized_by'] }}</p>
                    </div>
                </div>
            @endif

            {{-- Actions --}}
            <div class="card">
                <div class="card-body">
                    @if($budget->status !== 'finalized')
                        @if($readiness['is_ready'])
                            <form action="{{ route('accounting.budgets.finalize', $budget->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success btn-lg w-100" onclick="return confirm('Are you sure you want to finalize this budget? This will lock all values and update product yearly targets.')">
                                    <i class="ti ti-check me-2"></i> Finalize Budget {{ $budget->year }}
                                </button>
                            </form>
                        @else
                            <button type="button" class="btn btn-secondary btn-lg w-100" disabled>
                                <i class="ti ti-lock me-2"></i> Cannot Finalize - Resolve Issues First
                            </button>
                        @endif
                    @else
                        <form action="{{ route('accounting.budgets.revert', $budget->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-lg w-100" onclick="return confirm('Are you sure you want to revert this budget to draft? This will allow further editing.')">
                                <i class="ti ti-arrow-back me-2"></i> Revert to Draft
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <div class="d-flex justify-content-between mt-4">
        <a href="{{ route('accounting.budgets.summary', $budget->id) }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i> Back to Summary
        </a>
        <a href="{{ route('accounting.budgets.index') }}" class="btn btn-outline-primary">
            <i class="ti ti-list me-1"></i> All Budgets
        </a>
    </div>
</div>
@endsection
