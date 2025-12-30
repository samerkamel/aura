@extends('layouts.layoutMaster')

@section('title', 'Profitability Analysis - ' . $project->name)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Profitability Analysis</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projects</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('projects.finance.index', $project) }}">Finance</a></li>
                    <li class="breadcrumb-item active">Profitability</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('projects.finance.index', $project) }}" class="btn btn-outline-primary">
            <i class="ti ti-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <!-- Profitability Status -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center border-end">
                            @php
                                $statusColors = [
                                    'excellent' => 'success',
                                    'good' => 'primary',
                                    'fair' => 'info',
                                    'low' => 'warning',
                                    'loss' => 'danger',
                                ];
                                $statusLabels = [
                                    'excellent' => 'Excellent',
                                    'good' => 'Good',
                                    'fair' => 'Fair',
                                    'low' => 'Low',
                                    'loss' => 'Loss',
                                ];
                            @endphp
                            <div class="avatar avatar-xl bg-label-{{ $statusColors[$profitability['profitability_status']] }} rounded-circle mb-2">
                                <i class="ti ti-chart-pie ti-32px"></i>
                            </div>
                            <h5 class="mb-0">{{ $statusLabels[$profitability['profitability_status']] }}</h5>
                            <small class="text-muted">Profitability Status</small>
                        </div>
                        <div class="col-md-9">
                            <div class="row g-4">
                                <div class="col-sm-4">
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Gross Profit</span>
                                        <h3 class="mb-0 {{ $profitability['gross_profit'] < 0 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($profitability['gross_profit'], 2) }}
                                        </h3>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Gross Margin</span>
                                        <h3 class="mb-0">{{ $profitability['gross_margin'] }}%</h3>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">ROI</span>
                                        <h3 class="mb-0 {{ $profitability['roi'] < 0 ? 'text-danger' : 'text-success' }}">
                                            {{ $profitability['roi'] }}%
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Revenue vs Costs Comparison -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Revenue vs Costs</h5>
                </div>
                <div class="card-body">
                    <div id="revenueVsCostsChart" style="min-height: 300px;"></div>
                </div>
            </div>
        </div>

        <!-- Cost Breakdown -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Cost Composition</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-6">
                            <div class="d-flex flex-column align-items-center">
                                <h4 class="mb-0">{{ number_format($profitability['labor_costs'], 2) }}</h4>
                                <span class="badge bg-label-primary">Labor Costs</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex flex-column align-items-center">
                                <h4 class="mb-0">{{ number_format($profitability['non_labor_costs'], 2) }}</h4>
                                <span class="badge bg-label-warning">Other Costs</span>
                            </div>
                        </div>
                    </div>
                    <div class="progress" style="height: 20px;">
                        @php
                            $laborPct = $profitability['total_costs'] > 0
                                ? ($profitability['labor_costs'] / $profitability['total_costs']) * 100
                                : 0;
                        @endphp
                        <div class="progress-bar bg-primary" style="width: {{ $laborPct }}%">
                            Labor {{ round($laborPct) }}%
                        </div>
                        <div class="progress-bar bg-warning" style="width: {{ 100 - $laborPct }}%">
                            Other {{ round(100 - $laborPct) }}%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Labor Efficiency -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Labor Efficiency</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Total Hours Logged</span>
                            <strong>{{ number_format($profitability['total_hours'], 1) }}h</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Average Cost Rate</span>
                            <strong>{{ number_format($profitability['average_cost_rate'], 2) }}/h</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Effective Billing Rate</span>
                            <strong>{{ number_format($profitability['effective_hourly_rate'], 2) }}/h</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Hourly Margin</span>
                            <strong class="{{ $profitability['hourly_margin'] < 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($profitability['hourly_margin'], 2) }}/h
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Burn Rate -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Burn Rate (30 Days)</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Daily Average</span>
                            <strong>{{ number_format($burnRate['daily'], 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Weekly Average</span>
                            <strong>{{ number_format($burnRate['weekly'], 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Monthly Average</span>
                            <strong>{{ number_format($burnRate['monthly'], 2) }}</strong>
                        </div>
                        <hr>
                        @if($burnRate['runway_days'])
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Budget Runway</span>
                                <strong class="{{ $burnRate['runway_days'] < 30 ? 'text-danger' : ($burnRate['runway_days'] < 60 ? 'text-warning' : 'text-success') }}">
                                    {{ $burnRate['runway_days'] }} days
                                </strong>
                            </div>
                        @else
                            <div class="text-center text-muted">
                                <i class="ti ti-infinity"></i> No burn rate data
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Financial Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Total Revenue</span>
                            <strong>{{ number_format($profitability['total_revenue'], 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Received Revenue</span>
                            <strong class="text-success">{{ number_format($profitability['received_revenue'], 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Total Costs</span>
                            <strong class="text-warning">{{ number_format($profitability['total_costs'], 2) }}</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <span><strong>Net Profit</strong></span>
                            <strong class="fs-5 {{ $profitability['gross_profit'] < 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($profitability['gross_profit'], 2) }}
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Trend -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">12-Month Financial Trend</h5>
        </div>
        <div class="card-body">
            <div id="monthlyTrendChart" style="min-height: 350px;"></div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Revenue vs Costs Chart
            const revenueVsCostsChart = new ApexCharts(document.querySelector('#revenueVsCostsChart'), {
                chart: {
                    type: 'bar',
                    height: 300,
                    toolbar: { show: false }
                },
                series: [{
                    name: 'Amount',
                    data: [
                        {{ $profitability['received_revenue'] }},
                        {{ $profitability['total_costs'] }},
                        {{ $profitability['gross_profit'] }}
                    ]
                }],
                xaxis: {
                    categories: ['Revenue', 'Costs', 'Profit']
                },
                colors: ['#28c76f', '#ff9f43', '{{ $profitability["gross_profit"] < 0 ? "#ea5455" : "#7367f0" }}'],
                plotOptions: {
                    bar: {
                        distributed: true,
                        borderRadius: 4,
                        columnWidth: '60%'
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return val.toLocaleString();
                    }
                },
                legend: { show: false }
            });
            revenueVsCostsChart.render();

            // Monthly Trend Chart
            const monthlyData = @json($monthlyTrend);
            const monthlyTrendChart = new ApexCharts(document.querySelector('#monthlyTrendChart'), {
                chart: {
                    type: 'line',
                    height: 350,
                    toolbar: { show: false }
                },
                series: [
                    { name: 'Revenue', data: monthlyData.map(d => d.revenue) },
                    { name: 'Costs', data: monthlyData.map(d => d.costs) },
                    { name: 'Profit', data: monthlyData.map(d => d.profit) }
                ],
                xaxis: {
                    categories: monthlyData.map(d => d.month_short)
                },
                colors: ['#28c76f', '#ff9f43', '#7367f0'],
                stroke: { width: 3, curve: 'smooth' },
                markers: { size: 4 },
                legend: { position: 'top' },
                tooltip: {
                    y: { formatter: val => val.toLocaleString() }
                },
                grid: {
                    borderColor: '#f1f1f1'
                }
            });
            monthlyTrendChart.render();
        });
    </script>
@endsection
