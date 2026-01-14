@extends('layouts/layoutMaster')

@section('title', 'Budget ' . $budget->year . ' - Summary')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Budget {{ $budget->year }} - Summary</h1>
            <p class="text-muted mb-0">Budget P&L overview and finalization</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-label-{{ $budget->status === 'finalized' ? 'success' : ($budget->status === 'in_progress' ? 'warning' : 'secondary') }} fs-6">
                {{ ucfirst(str_replace('_', ' ', $budget->status)) }}
            </span>
            @if($budget->status !== 'finalized')
                @if($readiness['is_ready'])
                    <form action="{{ route('accounting.budgets.finalize', $budget->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to finalize this budget? This will lock all values.')">
                            <i class="ti ti-check me-1"></i> Finalize Budget
                        </button>
                    </form>
                @else
                    <button type="button" class="btn btn-outline-secondary" disabled title="Complete all required items before finalizing">
                        <i class="ti ti-lock me-1"></i> Not Ready
                    </button>
                @endif
            @else
                <form action="{{ route('accounting.budgets.revert', $budget->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning" onclick="return confirm('Are you sure you want to revert this budget to draft?')">
                        <i class="ti ti-arrow-back me-1"></i> Revert to Draft
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Tab Navigation --}}
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('accounting.budgets.growth', $budget->id) }}">Growth</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('accounting.budgets.capacity', $budget->id) }}">Capacity</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('accounting.budgets.collection', $budget->id) }}">Collection</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('accounting.budgets.result', $budget->id) }}">Result</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('accounting.budgets.personnel', $budget->id) }}">Personnel</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('accounting.budgets.expenses', $budget->id) }}">Expenses</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('accounting.budgets.summary', $budget->id) }}">Summary</a>
        </li>
    </ul>

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

    {{-- Readiness Alerts --}}
    @if(!$readiness['is_ready'] && $budget->status !== 'finalized')
        <div class="alert alert-warning mb-4">
            <h6 class="alert-heading"><i class="ti ti-alert-triangle me-2"></i>Budget Not Ready for Finalization</h6>
            <p class="mb-2">The following issues need to be resolved:</p>
            <ul class="mb-0">
                @foreach($readiness['errors'] as $section => $errors)
                    @foreach($errors as $error)
                        <li><strong>{{ ucfirst($section) }}:</strong> {{ $error }}</li>
                    @endforeach
                @endforeach
            </ul>
        </div>
    @endif

    {{-- P&L Statement Card --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="ti ti-report-money me-2"></i>Profit & Loss Statement - Budget {{ $budget->year }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tbody>
                        <tr class="table-primary">
                            <td><strong>Revenue</strong></td>
                            <td class="text-end" style="width: 200px;"><strong>{{ number_format($pnl['revenue'], 2) }}</strong></td>
                            <td class="text-end" style="width: 100px;"><strong>100%</strong></td>
                        </tr>
                        <tr>
                            <td class="ps-4">Less: Personnel Cost</td>
                            <td class="text-end text-danger">({{ number_format($pnl['personnel_cost'], 2) }})</td>
                            <td class="text-end text-muted">{{ number_format($pnl['revenue'] > 0 ? ($pnl['personnel_cost'] / $pnl['revenue']) * 100 : 0, 1) }}%</td>
                        </tr>
                        <tr class="table-light">
                            <td><strong>Gross Profit</strong></td>
                            <td class="text-end {{ $pnl['gross_profit'] >= 0 ? 'text-success' : 'text-danger' }}"><strong>{{ number_format($pnl['gross_profit'], 2) }}</strong></td>
                            <td class="text-end"><strong>{{ number_format($pnl['gross_margin'], 1) }}%</strong></td>
                        </tr>
                        <tr>
                            <td class="ps-4">Less: Operating Expenses (OpEx)</td>
                            <td class="text-end text-danger">({{ number_format($pnl['opex'], 2) }})</td>
                            <td class="text-end text-muted">{{ number_format($pnl['revenue'] > 0 ? ($pnl['opex'] / $pnl['revenue']) * 100 : 0, 1) }}%</td>
                        </tr>
                        <tr class="table-light">
                            <td><strong>Operating Profit (EBIT)</strong></td>
                            <td class="text-end {{ $pnl['operating_profit'] >= 0 ? 'text-success' : 'text-danger' }}"><strong>{{ number_format($pnl['operating_profit'], 2) }}</strong></td>
                            <td class="text-end"><strong>{{ number_format($pnl['operating_margin'], 1) }}%</strong></td>
                        </tr>
                        <tr>
                            <td class="ps-4">Less: Taxes</td>
                            <td class="text-end text-danger">({{ number_format($pnl['taxes'], 2) }})</td>
                            <td class="text-end text-muted">{{ number_format($pnl['revenue'] > 0 ? ($pnl['taxes'] / $pnl['revenue']) * 100 : 0, 1) }}%</td>
                        </tr>
                        <tr class="table-success">
                            <td><strong>Net Profit</strong></td>
                            <td class="text-end {{ $pnl['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}"><strong>{{ number_format($pnl['net_profit'], 2) }}</strong></td>
                            <td class="text-end"><strong>{{ number_format($pnl['net_margin'], 1) }}%</strong></td>
                        </tr>
                        <tr class="table-secondary">
                            <td><em>Capital Expenditure (CapEx)</em></td>
                            <td class="text-end"><em>{{ number_format($pnl['capex'], 2) }}</em></td>
                            <td class="text-end text-muted">{{ number_format($pnl['revenue'] > 0 ? ($pnl['capex'] / $pnl['revenue']) * 100 : 0, 1) }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row mb-4">
        {{-- Revenue by Product --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Revenue by Product</h5>
                </div>
                <div class="card-body">
                    <div id="revenueByProductChart" style="min-height: 300px;"></div>
                </div>
            </div>
        </div>

        {{-- Expense Breakdown --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Expense Breakdown</h5>
                </div>
                <div class="card-body">
                    <div id="expenseBreakdownChart" style="min-height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row mb-4">
        {{-- Revenue Summary --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="ti ti-trending-up me-2"></i>Revenue Budget</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Growth Target:</span>
                        <strong>{{ number_format($checklist['budget_summary']['total_growth_budget'], 0) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Capacity Target:</span>
                        <strong>{{ number_format($checklist['budget_summary']['total_capacity_budget'], 0) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Collection Target:</span>
                        <strong>{{ number_format($checklist['budget_summary']['total_collection_budget'], 0) }}</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Final Budget:</span>
                        <strong class="text-success fs-5">{{ number_format($checklist['budget_summary']['total_final_budget'], 0) }}</strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- Personnel Summary --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="ti ti-users me-2"></i>Personnel Budget</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Employees:</span>
                        <strong>{{ $checklist['personnel']['total_employees'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>New Hires Planned:</span>
                        <strong>{{ $checklist['personnel']['new_hires'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Current Salaries:</span>
                        <strong>{{ number_format($checklist['personnel']['total_current_salaries'], 0) }}</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-bold">Proposed Total:</span>
                        <strong class="text-primary fs-5">{{ number_format($checklist['personnel']['total_proposed_salaries'], 0) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Increase:</span>
                        <span class="badge bg-label-{{ $checklist['personnel']['total_increase_percentage'] >= 0 ? 'success' : 'danger' }}">
                            {{ $checklist['personnel']['total_increase_percentage'] >= 0 ? '+' : '' }}{{ number_format($checklist['personnel']['total_increase_percentage'], 1) }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Expense Summary --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="ti ti-receipt me-2"></i>Expense Budget</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Operating Expenses:</span>
                        <strong>{{ number_format($checklist['expenses']['opex'], 0) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Taxes:</span>
                        <strong>{{ number_format($checklist['expenses']['taxes'], 0) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Capital Expenditure:</span>
                        <strong>{{ number_format($checklist['expenses']['capex'], 0) }}</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Total Expenses:</span>
                        <strong class="text-danger fs-5">{{ number_format($checklist['expenses']['total_expenses'], 0) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Completion Status --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="ti ti-checklist me-2"></i>Budget Completion Status</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Result Tab Completion</h6>
                    <div class="progress mb-2" style="height: 25px;">
                        <div class="progress-bar {{ $checklist['result_completion']['percentage'] == 100 ? 'bg-success' : 'bg-warning' }}"
                             role="progressbar"
                             style="width: {{ $checklist['result_completion']['percentage'] }}%">
                            {{ number_format($checklist['result_completion']['percentage'], 0) }}%
                        </div>
                    </div>
                    <small class="text-muted">
                        {{ $checklist['result_completion']['completed'] }} of {{ $checklist['result_completion']['completed'] + $checklist['result_completion']['pending'] }} products completed
                    </small>
                </div>
                <div class="col-md-6">
                    <h6>Budget Status</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="ti ti-{{ $checklist['result_completion']['pending'] == 0 ? 'check text-success' : 'x text-danger' }} me-2"></i>
                            All products have final budget selected
                        </li>
                        <li class="mb-2">
                            <i class="ti ti-{{ $checklist['personnel']['total_employees'] > 0 ? 'check text-success' : 'x text-danger' }} me-2"></i>
                            Personnel entries initialized
                        </li>
                        <li class="mb-2">
                            <i class="ti ti-{{ $readiness['is_ready'] ? 'check text-success' : 'x text-danger' }} me-2"></i>
                            Ready for finalization
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <div class="d-flex justify-content-between mb-4">
        <a href="{{ route('accounting.budgets.expenses', $budget->id) }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i> Back to Expenses
        </a>
        <div>
            @if($budget->status === 'finalized')
                <a href="{{ route('accounting.budgets.finalization', $budget->id) }}" class="btn btn-outline-primary">
                    <i class="ti ti-file-check me-1"></i> View Finalization Details
                </a>
            @elseif($readiness['is_ready'])
                <a href="{{ route('accounting.budgets.finalization', $budget->id) }}" class="btn btn-success">
                    <i class="ti ti-check me-1"></i> Proceed to Finalize
                </a>
            @endif
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart data from controller
    const revenueByProduct = @json($chartData['revenueByProduct']);
    const expenseBreakdown = @json($chartData['expenseBreakdown']);

    // Revenue by Product Chart (Bar)
    const revenueLabels = Object.keys(revenueByProduct);
    const revenueData = Object.values(revenueByProduct);

    if (revenueData.some(v => v > 0)) {
        const revenueChart = new ApexCharts(document.querySelector('#revenueByProductChart'), {
            series: [{
                name: 'Revenue',
                data: revenueData
            }],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: { show: false }
            },
            plotOptions: {
                bar: { horizontal: true, columnWidth: '55%' }
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: revenueLabels,
                labels: {
                    formatter: val => val.toLocaleString()
                }
            },
            yaxis: {
                title: { text: 'Product' }
            },
            colors: ['#28c76f'],
            tooltip: {
                y: {
                    formatter: val => 'EGP ' + val.toLocaleString()
                }
            }
        });
        revenueChart.render();
    } else {
        document.querySelector('#revenueByProductChart').innerHTML = '<div class="text-center text-muted py-5">No revenue data yet</div>';
    }

    // Expense Breakdown Chart (Donut)
    const expenseLabels = Object.keys(expenseBreakdown);
    const expenseData = Object.values(expenseBreakdown);

    if (expenseData.some(v => v > 0)) {
        const expenseChart = new ApexCharts(document.querySelector('#expenseBreakdownChart'), {
            series: expenseData,
            chart: {
                type: 'donut',
                height: 300
            },
            labels: expenseLabels,
            colors: ['#7367f0', '#ea5455', '#ff9f43', '#28c76f'],
            legend: { position: 'bottom' },
            dataLabels: {
                enabled: true,
                formatter: (val, opts) => {
                    return opts.w.config.series[opts.seriesIndex].toLocaleString();
                }
            },
            tooltip: {
                y: {
                    formatter: val => 'EGP ' + val.toLocaleString()
                }
            }
        });
        expenseChart.render();
    } else {
        document.querySelector('#expenseBreakdownChart').innerHTML = '<div class="text-center text-muted py-5">No expense data yet</div>';
    }
});
</script>
@endsection
