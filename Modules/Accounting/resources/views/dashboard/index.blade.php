@extends('layouts/layoutMaster')

@section('title', 'Cash Flow Dashboard')

@section('page-script')
<script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
@endsection

@section('content')
<!-- Dashboard Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">Cash Flow Management</h4>
                <p class="text-muted mb-0">Monitor your financial health and plan ahead</p>
            </div>
            <div class="d-flex gap-2">
                @can('view-cash-flow-reports')
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#projectionModal">
                        <i class="ti ti-chart-line me-2"></i>View Projections
                    </button>
                @endcan

                @can('manage-expense-schedules')
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="ti ti-plus me-1"></i>Quick Add
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('accounting.expenses.create') }}">
                                <i class="ti ti-coin-off me-2"></i>New Expense Schedule
                            </a></li>
                            @can('manage-income-schedules')
                                <li><a class="dropdown-item" href="{{ route('accounting.income.contracts.create') }}">
                                    <i class="ti ti-file-text me-2"></i>New Contract
                                </a></li>
                            @endcan
                        </ul>
                    </div>
                @endcan

                @cannot('manage-expense-schedules')
                    <span class="badge bg-info">
                        <i class="ti ti-eye me-1"></i>Read-Only Access
                    </span>
                @endcannot
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="card-info">
                        <p class="card-text">Monthly Income</p>
                        <div class="d-flex align-items-end mb-2">
                            <h4 class="card-title mb-0 me-2">{{ number_format($monthlyIncome, 2) }} EGP</h4>
                            @if($incomeGrowth >= 0)
                                <small class="text-success">+{{ number_format($incomeGrowth, 1) }}%</small>
                            @else
                                <small class="text-danger">{{ number_format($incomeGrowth, 1) }}%</small>
                            @endif
                        </div>
                        <small class="text-muted">From active contracts</small>
                    </div>
                    <div class="avatar">
                        <div class="avatar-initial bg-success rounded">
                            <i class="ti ti-trending-up"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="card-info">
                        <p class="card-text">Monthly Expenses</p>
                        <div class="d-flex align-items-end mb-2">
                            <h4 class="card-title mb-0 me-2">{{ number_format($monthlyExpenses, 2) }} EGP</h4>
                            @if($expenseGrowth <= 0)
                                <small class="text-success">{{ number_format($expenseGrowth, 1) }}%</small>
                            @else
                                <small class="text-danger">+{{ number_format($expenseGrowth, 1) }}%</small>
                            @endif
                        </div>
                        <small class="text-muted">Recurring schedules</small>
                    </div>
                    <div class="avatar">
                        <div class="avatar-initial bg-danger rounded">
                            <i class="ti ti-trending-down"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="card-info">
                        <p class="card-text">Net Cash Flow</p>
                        <div class="d-flex align-items-end mb-2">
                            <h4 class="card-title mb-0 me-2 {{ $netCashFlow >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($netCashFlow, 2) }} EGP
                            </h4>
                        </div>
                        <small class="text-muted">Monthly average</small>
                    </div>
                    <div class="avatar">
                        <div class="avatar-initial {{ $netCashFlow >= 0 ? 'bg-primary' : 'bg-warning' }} rounded">
                            <i class="ti {{ $netCashFlow >= 0 ? 'ti-wallet' : 'ti-alert-triangle' }}"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="card-info">
                        <p class="card-text">Active Contracts</p>
                        <div class="d-flex align-items-end mb-2">
                            <h4 class="card-title mb-0 me-2">{{ $activeContracts }}</h4>
                        </div>
                        <small class="text-muted">Total value: {{ number_format($totalContractValue, 2) }} EGP</small>
                    </div>
                    <div class="avatar">
                        <div class="avatar-initial bg-info rounded">
                            <i class="ti ti-file-text"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Row -->
<div class="row">
    <!-- Cash Flow Chart -->
    <div class="col-xl-8 col-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <h5 class="card-title mb-0">Cash Flow Projection</h5>
                    <small class="text-muted">Income vs Expenses over time</small>
                </div>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="ti ti-calendar me-1"></i>{{ ucfirst($selectedPeriod ?? 'monthly') }}
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="?period=weekly">Weekly</a></li>
                        <li><a class="dropdown-item" href="?period=monthly">Monthly</a></li>
                        <li><a class="dropdown-item" href="?period=quarterly">Quarterly</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div id="cashFlowChart"></div>
                @if(!empty($deficitPeriods))
                    <div class="alert alert-warning mt-3">
                        <i class="ti ti-alert-triangle me-2"></i>
                        <strong>Cash Flow Alert:</strong> Potential deficits detected in {{ count($deficitPeriods) }} upcoming periods.
                        <a href="#" data-bs-toggle="modal" data-bs-target="#deficitModal">View Details</a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Upcoming Payments -->
    <div class="col-xl-4 col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Upcoming Payments</h5>
                <small class="text-muted">Next 30 days</small>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @forelse($upcomingPayments->take(8) as $payment)
                        <div class="col-12">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-3">
                                    <div class="avatar-initial rounded {{ $payment['type'] === 'expense' ? 'bg-danger' : 'bg-success' }}">
                                        <i class="ti {{ $payment['type'] === 'expense' ? 'ti-arrow-down' : 'ti-arrow-up' }}"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">{{ $payment['name'] }}</h6>
                                    <small class="text-muted">{{ $payment['date']->format('M j, Y') }}</small>
                                </div>
                                <div class="text-end">
                                    <h6 class="mb-0 {{ $payment['type'] === 'expense' ? 'text-danger' : 'text-success' }}">
                                        {{ $payment['type'] === 'expense' ? '-' : '+' }}{{ number_format($payment['amount'], 2) }} EGP
                                    </h6>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-3">
                            <i class="ti ti-calendar-off text-muted mb-2" style="font-size: 2rem;"></i>
                            <p class="text-muted">No upcoming payments</p>
                        </div>
                    @endforelse
                </div>
                @if($upcomingPayments->count() > 8)
                    <div class="text-center mt-3">
                        <a href="{{ route('accounting.reports') }}?tab=schedule" class="btn btn-outline-primary btn-sm">
                            View All ({{ $upcomingPayments->count() }})
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Bottom Row - Categories & Recent Activity -->
<div class="row">
    <!-- Expense Categories -->
    <div class="col-xl-6 col-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Expense Categories</h5>
                <a href="{{ route('accounting.expenses.categories') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-settings me-1"></i>Manage
                </a>
            </div>
            <div class="card-body">
                <div id="expenseCategoriesChart"></div>
            </div>
        </div>
    </div>

    <!-- Contract Status -->
    <div class="col-xl-6 col-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Contract Status</h5>
                <a href="{{ route('accounting.income.contracts.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-file-text me-1"></i>View All
                </a>
            </div>
            <div class="card-body">
                @forelse($recentContracts as $contract)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="mb-0">{{ $contract->client_name }}</h6>
                            <small class="text-muted">{{ \Illuminate\Support\Str::limit($contract->description, 40) }}</small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-{{ $contract->status === 'active' ? 'success' : 'warning' }} mb-1">
                                {{ ucfirst($contract->status) }}
                            </span>
                            <div class="text-muted small">{{ number_format($contract->total_amount, 0) }} EGP</div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-3">
                        <i class="ti ti-file-plus text-muted mb-2" style="font-size: 2rem;"></i>
                        <p class="text-muted">No contracts found</p>
                        <a href="{{ route('accounting.income.contracts.create') }}" class="btn btn-primary btn-sm">
                            Create First Contract
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Cash Flow Projection Modal -->
<div class="modal fade" id="projectionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cash Flow Projections</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Period Type</label>
                        <select class="form-select" id="projectionPeriod">
                            <option value="monthly">Monthly</option>
                            <option value="weekly">Weekly</option>
                            <option value="quarterly">Quarterly</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="projectionStart" value="{{ now()->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Duration</label>
                        <select class="form-select" id="projectionDuration">
                            <option value="6">6 periods</option>
                            <option value="12" selected>12 periods</option>
                            <option value="24">24 periods</option>
                        </select>
                    </div>
                </div>
                <div id="projectionResults">
                    <!-- Dynamic content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cash Flow Chart
    const cashFlowOptions = {
        series: [{
            name: 'Income',
            data: @json($cashFlowData['income'] ?? [])
        }, {
            name: 'Expenses',
            data: @json($cashFlowData['expenses'] ?? [])
        }, {
            name: 'Net Flow',
            data: @json($cashFlowData['netFlow'] ?? [])
        }],
        chart: {
            type: 'line',
            height: 350,
            toolbar: { show: false }
        },
        colors: ['#28c76f', '#ea5455', '#00cfe8'],
        xaxis: {
            categories: @json($cashFlowData['periods'] ?? [])
        },
        yaxis: {
            labels: {
                formatter: function(val) {
                    return val.toLocaleString() + ' EGP';
                }
            }
        },
        stroke: {
            width: 3,
            curve: 'smooth'
        }
    };

    if (document.querySelector("#cashFlowChart")) {
        new ApexCharts(document.querySelector("#cashFlowChart"), cashFlowOptions).render();
    }

    // Expense Categories Chart
    const categoryOptions = {
        series: @json($expenseCategories['amounts'] ?? []),
        chart: {
            type: 'donut',
            height: 300
        },
        labels: @json($expenseCategories['names'] ?? []),
        colors: @json($expenseCategories['colors'] ?? []),
        legend: {
            position: 'bottom'
        }
    };

    if (document.querySelector("#expenseCategoriesChart")) {
        new ApexCharts(document.querySelector("#expenseCategoriesChart"), categoryOptions).render();
    }
});
</script>
@endsection