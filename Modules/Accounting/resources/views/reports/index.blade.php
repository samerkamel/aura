@extends('layouts/layoutMaster')

@section('title', 'Cash Flow Reports')

@section('page-script')
<script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Report Header -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="mb-1">Cash Flow Reports & Analysis</h4>
                        <p class="text-muted mb-0">Comprehensive financial planning and analysis tools</p>
                    </div>
                    <div class="col-md-4 text-end">
                        @can('export-financial-reports')
                            <div class="dropdown">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="ti tabler-download me-1"></i>Export Reports
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="exportReport('pdf')">
                                        <i class="ti tabler-file-type-pdf me-2"></i>Export as PDF
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="exportReport('excel')">
                                        <i class="ti tabler-file-spreadsheet me-2"></i>Export as Excel
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="exportReport('csv')">
                                        <i class="ti tabler-file-type-csv me-2"></i>Export as CSV
                                    </a></li>
                                </ul>
                            </div>
                        @else
                            <span class="badge bg-secondary">
                                <i class="ti tabler-lock me-1"></i>Export Restricted
                            </span>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Controls -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Report Period</label>
                        <select name="period" class="form-select" id="periodSelect">
                            <option value="monthly" {{ $selectedPeriod === 'monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="weekly" {{ $selectedPeriod === 'weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="quarterly" {{ $selectedPeriod === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Duration</label>
                        <select name="duration" class="form-select">
                            <option value="6" {{ $duration == 6 ? 'selected' : '' }}>6 periods</option>
                            <option value="12" {{ $duration == 12 ? 'selected' : '' }}>12 periods</option>
                            <option value="18" {{ $duration == 18 ? 'selected' : '' }}>18 periods</option>
                            <option value="24" {{ $duration == 24 ? 'selected' : '' }}>24 periods</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Report Type</label>
                        <select name="type" class="form-select">
                            <option value="projection" {{ $reportType === 'projection' ? 'selected' : '' }}>Cash Flow Projection</option>
                            <option value="historical" {{ $reportType === 'historical' ? 'selected' : '' }}>Historical Analysis</option>
                            <option value="deficit" {{ $reportType === 'deficit' ? 'selected' : '' }}>Deficit Analysis</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti tabler-refresh me-1"></i>Generate
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="nav-align-top mb-4">
            <ul class="nav nav-pills mb-3" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="overview-tab" data-bs-toggle="pill" data-bs-target="#overview" role="tab">
                        <i class="ti tabler-chart-line me-1"></i>Overview
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="projections-tab" data-bs-toggle="pill" data-bs-target="#projections" role="tab">
                        <i class="ti tabler-crystal-ball me-1"></i>Projections
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="schedule-tab" data-bs-toggle="pill" data-bs-target="#schedule" role="tab">
                        <i class="ti tabler-calendar-stats me-1"></i>Payment Schedule
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="deficit-tab" data-bs-toggle="pill" data-bs-target="#deficit" role="tab">
                        <i class="ti tabler-alert-triangle me-1"></i>Deficit Analysis
                        @if(count($deficitPeriods) > 0)
                            <span class="badge bg-danger ms-1">{{ count($deficitPeriods) }}</span>
                        @endif
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Overview Tab -->
                <div class="tab-pane fade show active" id="overview" role="tabpanel">
                    <div class="row">
                        <!-- Summary Cards -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title text-nowrap mb-2">Total Income</h5>
                                            <h4 class="text-success mb-2">{{ number_format($summaryData['totalIncome'], 2) }} EGP</h4>
                                            <small class="text-muted">Over {{ $duration }} periods</small>
                                        </div>
                                        <div class="avatar">
                                            <span class="avatar-initial rounded bg-success">
                                                <i class="ti tabler-trending-up ti-sm"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title text-nowrap mb-2">Total Expenses</h5>
                                            <h4 class="text-danger mb-2">{{ number_format($summaryData['totalExpenses'], 2) }} EGP</h4>
                                            <small class="text-muted">Over {{ $duration }} periods</small>
                                        </div>
                                        <div class="avatar">
                                            <span class="avatar-initial rounded bg-danger">
                                                <i class="ti tabler-trending-down ti-sm"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title text-nowrap mb-2">Net Cash Flow</h5>
                                            <h4 class="mb-2 {{ $summaryData['netCashFlow'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($summaryData['netCashFlow'], 2) }} EGP
                                            </h4>
                                            <small class="text-muted">Total projected</small>
                                        </div>
                                        <div class="avatar">
                                            <span class="avatar-initial rounded {{ $summaryData['netCashFlow'] >= 0 ? 'bg-primary' : 'bg-warning' }}">
                                                <i class="ti tabler-wallet ti-sm"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title text-nowrap mb-2">Avg. Monthly</h5>
                                            <h4 class="mb-2 {{ $summaryData['avgMonthly'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($summaryData['avgMonthly'], 2) }} EGP
                                            </h4>
                                            <small class="text-muted">Average per period</small>
                                        </div>
                                        <div class="avatar">
                                            <span class="avatar-initial rounded bg-info">
                                                <i class="ti tabler-chart-bar ti-sm"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Main Chart -->
                        <div class="col-12 mb-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between">
                                    <h5 class="card-title mb-0">Cash Flow Trend Analysis</h5>
                                    <small class="text-muted">{{ ucfirst($selectedPeriod) }} view</small>
                                </div>
                                <div class="card-body">
                                    <div id="mainCashFlowChart"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Categories Breakdown -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Income by Contract</h5>
                                </div>
                                <div class="card-body">
                                    <div id="incomeBreakdownChart"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Expenses by Category</h5>
                                </div>
                                <div class="card-body">
                                    <div id="expenseBreakdownChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Projections Tab -->
                <div class="tab-pane fade" id="projections" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Detailed Cash Flow Projections</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Period</th>
                                        <th class="text-end">Income</th>
                                        <th class="text-end">Expenses</th>
                                        <th class="text-end">Net Flow</th>
                                        <th class="text-end">Running Balance</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $runningBalance = 0; @endphp
                                    @foreach($projectionData as $index => $period)
                                        @php $runningBalance += $period['netFlow']; @endphp
                                        <tr class="{{ $period['netFlow'] < 0 ? 'table-warning' : '' }}">
                                            <td>
                                                <strong>{{ $period['period'] }}</strong>
                                                <br><small class="text-muted">{{ $period['dates'] ?? '' }}</small>
                                            </td>
                                            <td class="text-end text-success">
                                                <strong>{{ number_format($period['income'], 2) }} EGP</strong>
                                            </td>
                                            <td class="text-end text-danger">
                                                <strong>{{ number_format($period['expenses'], 2) }} EGP</strong>
                                            </td>
                                            <td class="text-end">
                                                <strong class="{{ $period['netFlow'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ number_format($period['netFlow'], 2) }} EGP
                                                </strong>
                                            </td>
                                            <td class="text-end">
                                                <strong class="{{ $runningBalance >= 0 ? 'text-primary' : 'text-danger' }}">
                                                    {{ number_format($runningBalance, 2) }} EGP
                                                </strong>
                                            </td>
                                            <td class="text-center">
                                                @if($period['netFlow'] < 0)
                                                    <span class="badge bg-warning">Deficit</span>
                                                @elseif($runningBalance < 0)
                                                    <span class="badge bg-danger">Negative</span>
                                                @else
                                                    <span class="badge bg-success">Positive</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Payment Schedule Tab -->
                <div class="tab-pane fade" id="schedule" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Upcoming Payment Schedule</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Name</th>
                                        <th>Source/Category</th>
                                        <th class="text-end">Amount</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($upcomingPayments as $payment)
                                        <tr>
                                            <td>
                                                <strong>{{ $payment['date']->format('M j, Y') }}</strong>
                                                <br><small class="text-muted">{{ $payment['date']->format('l') }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $payment['type'] === 'income' ? 'success' : 'danger' }}">
                                                    <i class="ti tabler-arrow-{{ $payment['type'] === 'income' ? 'up' : 'down' }} me-1"></i>
                                                    {{ ucfirst($payment['type']) }}
                                                </span>
                                            </td>
                                            <td>
                                                <strong>{{ $payment['name'] }}</strong>
                                                @if(isset($payment['description']) && $payment['description'])
                                                    <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($payment['description'], 40) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($payment['type'] === 'income')
                                                    {{ $payment['source'] ?? 'Unknown Contract' }}
                                                @else
                                                    <span class="badge rounded-pill" style="background-color: {{ $payment['color'] ?? '#ccc' }}20; color: {{ $payment['color'] ?? '#666' }};">
                                                        {{ $payment['category'] ?? 'Uncategorized' }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <strong class="{{ $payment['type'] === 'income' ? 'text-success' : 'text-danger' }}">
                                                    {{ $payment['type'] === 'income' ? '+' : '-' }}{{ number_format($payment['amount'], 2) }} EGP
                                                </strong>
                                            </td>
                                            <td class="text-center">
                                                <small class="text-muted">{{ $payment['date']->diffForHumans() }}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Deficit Analysis Tab -->
                <div class="tab-pane fade" id="deficit" role="tabpanel">
                    <div class="row">
                        @if(count($deficitPeriods) > 0)
                            <div class="col-12 mb-4">
                                <div class="alert alert-warning">
                                    <i class="ti tabler-alert-triangle me-2"></i>
                                    <strong>Cash Flow Warning:</strong> {{ count($deficitPeriods) }} periods with potential deficits detected.
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Deficit Period Analysis</h5>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Period</th>
                                                    <th class="text-end">Deficit Amount</th>
                                                    <th class="text-end">Running Balance</th>
                                                    <th>Risk Level</th>
                                                    <th>Recommendations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($deficitPeriods as $deficit)
                                                    <tr class="table-danger">
                                                        <td>
                                                            <strong>{{ $deficit['period'] }}</strong>
                                                            @if(isset($deficit['dates']))
                                                                <br><small class="text-muted">{{ $deficit['dates'] }}</small>
                                                            @endif
                                                        </td>
                                                        <td class="text-end">
                                                            <strong class="text-danger">-{{ number_format(abs($deficit['netFlow']), 2) }} EGP</strong>
                                                        </td>
                                                        <td class="text-end">
                                                            <strong class="{{ $deficit['runningBalance'] < 0 ? 'text-danger' : 'text-warning' }}">
                                                                {{ number_format($deficit['runningBalance'], 2) }} EGP
                                                            </strong>
                                                        </td>
                                                        <td>
                                                            @php
                                                                $riskLevel = abs($deficit['netFlow']) > 10000 ? 'High' : (abs($deficit['netFlow']) > 5000 ? 'Medium' : 'Low');
                                                                $badgeColor = $riskLevel === 'High' ? 'danger' : ($riskLevel === 'Medium' ? 'warning' : 'info');
                                                            @endphp
                                                            <span class="badge bg-{{ $badgeColor }}">{{ $riskLevel }} Risk</span>
                                                        </td>
                                                        <td>
                                                            <ul class="mb-0 small">
                                                                @if($deficit['runningBalance'] < 0)
                                                                    <li>Consider delaying non-critical expenses</li>
                                                                    <li>Accelerate accounts receivable</li>
                                                                @endif
                                                                @if(abs($deficit['netFlow']) > 5000)
                                                                    <li>Review and adjust expense schedules</li>
                                                                    <li>Consider additional income sources</li>
                                                                @endif
                                                            </ul>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body text-center py-5">
                                        <i class="ti tabler-check-circle text-success mb-3" style="font-size: 4rem;"></i>
                                        <h5 class="text-success">No Deficits Detected</h5>
                                        <p class="text-muted">Your cash flow projections look healthy for the selected period.</p>
                                        <div class="alert alert-success">
                                            <i class="ti tabler-thumb-up me-2"></i>
                                            Your projected cash flow maintains positive balances throughout the analysis period.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Main Cash Flow Chart
    const mainChartOptions = {
        series: [{
            name: 'Income',
            type: 'column',
            data: @json($chartData['income'] ?? [])
        }, {
            name: 'Expenses',
            type: 'column',
            data: @json($chartData['expenses'] ?? [])
        }, {
            name: 'Net Flow',
            type: 'line',
            data: @json($chartData['netFlow'] ?? [])
        }],
        chart: {
            height: 400,
            type: 'line',
            toolbar: { show: false }
        },
        colors: ['#28c76f', '#ea5455', '#00cfe8'],
        xaxis: {
            categories: @json($chartData['periods'] ?? [])
        },
        yaxis: {
            labels: {
                formatter: function(val) {
                    return val.toLocaleString() + ' EGP';
                }
            }
        },
        stroke: {
            width: [0, 0, 3]
        }
    };

    if (document.querySelector("#mainCashFlowChart")) {
        new ApexCharts(document.querySelector("#mainCashFlowChart"), mainChartOptions).render();
    }

    // Income Breakdown Chart
    const incomeBreakdownOptions = {
        series: @json($incomeBreakdown['amounts'] ?? []),
        chart: {
            type: 'donut',
            height: 300
        },
        labels: @json($incomeBreakdown['labels'] ?? []),
        colors: ['#00cfe8', '#28c76f', '#ff9f43', '#ea5455', '#7367f0'],
        legend: {
            position: 'bottom'
        }
    };

    if (document.querySelector("#incomeBreakdownChart")) {
        new ApexCharts(document.querySelector("#incomeBreakdownChart"), incomeBreakdownOptions).render();
    }

    // Expense Breakdown Chart
    const expenseBreakdownOptions = {
        series: @json($expenseBreakdown['amounts'] ?? []),
        chart: {
            type: 'donut',
            height: 300
        },
        labels: @json($expenseBreakdown['labels'] ?? []),
        colors: @json($expenseBreakdown['colors'] ?? []),
        legend: {
            position: 'bottom'
        }
    };

    if (document.querySelector("#expenseBreakdownChart")) {
        new ApexCharts(document.querySelector("#expenseBreakdownChart"), expenseBreakdownOptions).render();
    }
});

function exportReport(format) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("accounting.reports") }}';

    // Add CSRF token
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    form.appendChild(csrfToken);

    // Add export format
    const formatInput = document.createElement('input');
    formatInput.type = 'hidden';
    formatInput.name = 'export';
    formatInput.value = format;
    form.appendChild(formatInput);

    // Add current parameters
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.forEach((value, key) => {
        if (key !== 'export') {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
</script>
@endsection