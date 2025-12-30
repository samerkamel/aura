@extends('layouts.layoutMaster')

@section('title', 'Financial Dashboard - ' . $project->name)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Financial Dashboard</h4>
            <p class="text-muted mb-0">{{ $project->name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('projects.finance.budgets', $project) }}" class="btn btn-outline-primary">
                <i class="ti ti-wallet me-1"></i> Budgets
            </a>
            <a href="{{ route('projects.finance.costs', $project) }}" class="btn btn-outline-warning">
                <i class="ti ti-receipt me-1"></i> Costs
            </a>
            <a href="{{ route('projects.finance.revenues', $project) }}" class="btn btn-outline-success">
                <i class="ti ti-cash me-1"></i> Revenue
            </a>
            <a href="{{ route('projects.finance.profitability', $project) }}" class="btn btn-outline-info">
                <i class="ti ti-chart-bar me-1"></i> Profitability
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted">Total Budget</span>
                            <div class="d-flex align-items-center mt-2">
                                <h4 class="mb-0 me-2">{{ number_format($dashboard['summary']['total_budget'], 2) }}</h4>
                                <span class="badge bg-label-{{ $dashboard['summary']['budget_status'] === 'healthy' ? 'success' : ($dashboard['summary']['budget_status'] === 'warning' ? 'warning' : 'danger') }}">
                                    {{ $dashboard['summary']['budget_utilization'] }}% used
                                </span>
                            </div>
                        </div>
                        <span class="avatar avatar-lg bg-label-primary rounded">
                            <i class="ti ti-wallet ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted">Total Costs</span>
                            <div class="d-flex align-items-center mt-2">
                                <h4 class="mb-0 me-2">{{ number_format($dashboard['summary']['total_spent'], 2) }}</h4>
                            </div>
                        </div>
                        <span class="avatar avatar-lg bg-label-warning rounded">
                            <i class="ti ti-receipt ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted">Total Revenue</span>
                            <div class="d-flex align-items-center mt-2">
                                <h4 class="mb-0 me-2">{{ number_format($dashboard['summary']['received_revenue'], 2) }}</h4>
                                @if($dashboard['summary']['pending_revenue'] > 0)
                                    <span class="badge bg-label-info">
                                        +{{ number_format($dashboard['summary']['pending_revenue'], 2) }} pending
                                    </span>
                                @endif
                            </div>
                        </div>
                        <span class="avatar avatar-lg bg-label-success rounded">
                            <i class="ti ti-cash ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted">Gross Profit</span>
                            <div class="d-flex align-items-center mt-2">
                                <h4 class="mb-0 me-2 {{ $dashboard['summary']['gross_profit'] < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($dashboard['summary']['gross_profit'], 2) }}
                                </h4>
                                <span class="badge bg-label-{{ $dashboard['summary']['is_profitable'] ? 'success' : 'danger' }}">
                                    {{ $dashboard['summary']['gross_margin'] }}% margin
                                </span>
                            </div>
                        </div>
                        <span class="avatar avatar-lg bg-label-{{ $dashboard['summary']['is_profitable'] ? 'success' : 'danger' }} rounded">
                            <i class="ti ti-chart-line ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Monthly Trend Chart -->
        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">Monthly Financial Trend</h5>
                </div>
                <div class="card-body">
                    <div id="monthlyTrendChart" style="min-height: 350px;"></div>
                </div>
            </div>
        </div>

        <!-- Burn Rate -->
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Burn Rate (30 Days)</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Daily Average</span>
                            <strong>{{ number_format($dashboard['burn_rate']['daily'], 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Weekly Average</span>
                            <strong>{{ number_format($dashboard['burn_rate']['weekly'], 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Monthly Average</span>
                            <strong>{{ number_format($dashboard['burn_rate']['monthly'], 2) }}</strong>
                        </div>
                        <hr>
                        @if($dashboard['burn_rate']['runway_days'])
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Budget Runway</span>
                                <strong class="{{ $dashboard['burn_rate']['runway_days'] < 30 ? 'text-danger' : 'text-success' }}">
                                    {{ $dashboard['burn_rate']['runway_days'] }} days
                                </strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Projected Depletion</span>
                                <strong>{{ $dashboard['burn_rate']['runway_date'] }}</strong>
                            </div>
                        @else
                            <div class="text-center text-muted py-3">
                                <i class="ti ti-infinity ti-lg"></i>
                                <p class="mb-0 mt-2">No burn rate data</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Cost Breakdown -->
        <div class="col-12 col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">Cost Breakdown</h5>
                    <span class="badge bg-label-warning">{{ number_format($dashboard['cost_breakdown']['total'], 2) }}</span>
                </div>
                <div class="card-body">
                    <div id="costBreakdownChart" style="min-height: 250px;"></div>
                    <div class="mt-3">
                        @foreach($dashboard['cost_breakdown']['breakdown'] as $item)
                            @if($item['amount'] > 0)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-{{ $item['color'] }} me-2">&nbsp;</span>
                                        {{ $item['label'] }}
                                    </div>
                                    <span>{{ number_format($item['amount'], 2) }} ({{ $item['percentage'] }}%)</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Breakdown -->
        <div class="col-12 col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">Revenue Breakdown</h5>
                    <span class="badge bg-label-success">{{ $dashboard['revenue_breakdown']['collection_rate'] }}% collected</span>
                </div>
                <div class="card-body">
                    <div id="revenueBreakdownChart" style="min-height: 250px;"></div>
                    <div class="mt-3">
                        @foreach($dashboard['revenue_breakdown']['breakdown'] as $item)
                            @if($item['amount'] > 0)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-{{ $item['color'] }} me-2">&nbsp;</span>
                                        {{ $item['label'] }}
                                    </div>
                                    <span>
                                        {{ number_format($item['received'], 2) }}
                                        @if($item['pending'] > 0)
                                            <small class="text-muted">(+{{ number_format($item['pending'], 2) }})</small>
                                        @endif
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Budget Categories -->
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">Budget Categories</h5>
                    <a href="{{ route('projects.finance.budgets', $project) }}" class="btn btn-sm btn-outline-primary">
                        Manage Budgets
                    </a>
                </div>
                <div class="card-body">
                    @if(count($dashboard['budget_breakdown']['categories']) > 0)
                        @foreach($dashboard['budget_breakdown']['categories'] as $budget)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>{{ $budget['category_label'] }}</span>
                                    <small>{{ number_format($budget['actual'], 2) }} / {{ number_format($budget['planned'], 2) }}</small>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-{{ $budget['status_color'] }}"
                                         role="progressbar"
                                         style="width: {{ min($budget['utilization'], 100) }}%"
                                         aria-valuenow="{{ $budget['utilization'] }}"
                                         aria-valuemin="0"
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="ti ti-wallet-off ti-lg"></i>
                            <p class="mb-0 mt-2">No budgets configured</p>
                            <a href="{{ route('projects.finance.budgets', $project) }}" class="btn btn-sm btn-primary mt-2">
                                Add Budget
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Upcoming & Overdue Payments -->
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payments</h5>
                </div>
                <div class="card-body">
                    @if($dashboard['overdue_payments']->count() > 0)
                        <h6 class="text-danger mb-2">
                            <i class="ti ti-alert-triangle me-1"></i>
                            Overdue ({{ $dashboard['overdue_payments']->count() }})
                        </h6>
                        @foreach($dashboard['overdue_payments'] as $payment)
                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-danger-subtle rounded">
                                <div>
                                    <span class="fw-medium">{{ $payment->description }}</span>
                                    <br>
                                    <small class="text-muted">Due: {{ $payment->due_date->format('M d, Y') }}</small>
                                </div>
                                <span class="text-danger fw-bold">{{ number_format($payment->outstanding_amount, 2) }}</span>
                            </div>
                        @endforeach
                        <hr>
                    @endif

                    @if($dashboard['upcoming_payments']->count() > 0)
                        <h6 class="text-muted mb-2">Upcoming Payments</h6>
                        @foreach($dashboard['upcoming_payments'] as $payment)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <span>{{ $payment->description }}</span>
                                    @if($payment->due_date)
                                        <br>
                                        <small class="text-muted">Due: {{ $payment->due_date->format('M d, Y') }}</small>
                                    @endif
                                </div>
                                <span class="fw-medium">{{ number_format($payment->outstanding_amount, 2) }}</span>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="ti ti-check ti-lg"></i>
                            <p class="mb-0 mt-2">No pending payments</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Monthly Trend Chart
            const monthlyTrendData = @json($dashboard['monthly_trend']);
            const trendChart = new ApexCharts(document.querySelector('#monthlyTrendChart'), {
                chart: {
                    type: 'area',
                    height: 350,
                    toolbar: { show: false }
                },
                series: [
                    { name: 'Revenue', data: monthlyTrendData.map(d => d.revenue) },
                    { name: 'Costs', data: monthlyTrendData.map(d => d.costs) },
                    { name: 'Profit', data: monthlyTrendData.map(d => d.profit) }
                ],
                xaxis: {
                    categories: monthlyTrendData.map(d => d.month_short)
                },
                colors: ['#28c76f', '#ff9f43', '#7367f0'],
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { opacityFrom: 0.5, opacityTo: 0.1 } },
                legend: { position: 'top' },
                tooltip: {
                    y: { formatter: val => val.toLocaleString() }
                }
            });
            trendChart.render();

            // Cost Breakdown Chart
            const costData = @json($dashboard['cost_breakdown']['breakdown']);
            const activeCosts = costData.filter(d => d.amount > 0);
            if (activeCosts.length > 0) {
                const costChart = new ApexCharts(document.querySelector('#costBreakdownChart'), {
                    chart: { type: 'donut', height: 250 },
                    series: activeCosts.map(d => d.amount),
                    labels: activeCosts.map(d => d.label),
                    legend: { show: false }
                });
                costChart.render();
            }

            // Revenue Breakdown Chart
            const revenueData = @json($dashboard['revenue_breakdown']['breakdown']);
            const activeRevenue = revenueData.filter(d => d.amount > 0);
            if (activeRevenue.length > 0) {
                const revenueChart = new ApexCharts(document.querySelector('#revenueBreakdownChart'), {
                    chart: { type: 'donut', height: 250 },
                    series: activeRevenue.map(d => d.received),
                    labels: activeRevenue.map(d => d.label),
                    legend: { show: false }
                });
                revenueChart.render();
            }
        });
    </script>
@endsection
