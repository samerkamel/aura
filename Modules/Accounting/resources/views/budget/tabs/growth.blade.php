@extends('layouts/layoutMaster')

@section('title', 'Budget ' . $budget->year . ' - Growth')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3">Budget {{ $budget->year }} - Growth Tab</h1>
            <p class="text-muted">Enter historical data and configure trendline projections</p>
        </div>
        <div class="col-md-4 text-end">
            <span class="badge bg-{{ $budget->status === 'finalized' ? 'success' : 'warning' }}">
                {{ ucfirst($budget->status) }}
            </span>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="card mb-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('accounting.budgets.growth', $budget->id) }}">
                        Growth
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('accounting.budgets.capacity', $budget->id) }}">
                        Capacity
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('accounting.budgets.collection', $budget->id) }}">
                        Collection
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('accounting.budgets.result', $budget->id) }}">
                        Result
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('accounting.budgets.personnel', $budget->id) }}">Personnel</a>
                </li>
                <li class="nav-item">
                    <span class="nav-link disabled text-muted">Expenses</span>
                </li>
                <li class="nav-item ms-auto">
                    <span class="nav-link disabled text-muted">Summary</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="row mb-4">
        <!-- Bar Chart - Historical + Projected by Product -->
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Revenue by Product - Historical & Projected</h5>
                        <small class="text-muted">Comparing {{ $budget->year - 3 }}, {{ $budget->year - 2 }}, {{ $budget->year - 1 }} with projected {{ $budget->year }}</small>
                    </div>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary active" id="chart-grouped">Grouped</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="chart-stacked">Stacked</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="revenueBarChart" style="min-height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Trendline Charts Row -->
    <div class="row mb-4" id="trendline-charts-row">
        @foreach($growthEntries as $entry)
        <div class="col-md-4 col-lg-3 mb-4">
            <div class="card h-100">
                <div class="card-header py-2">
                    <h6 class="mb-0">{{ $entry->product->name }}</h6>
                </div>
                <div class="card-body p-2">
                    <div id="trendline-chart-{{ $entry->id }}" style="height: 180px;"></div>
                    <div class="text-center mt-2">
                        <span class="badge bg-label-primary">
                            Projected: <span class="projected-display-{{ $entry->id }}">Calculating...</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Growth Tab Content -->
    <div class="tab-content">
        <div class="tab-pane fade show active" id="growth-tab">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Growth-Based Budget Projections</h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-success" id="calculate-all-btn">
                            <i class="ti ti-calculator"></i> Calculate All Projections
                        </button>
                        <button class="btn btn-sm btn-outline-primary" id="populate-historical-btn"
                                data-route="{{ route('accounting.budgets.growth.populate-historical', $budget->id) }}">
                            <i class="ti ti-download"></i> Populate from Contracts
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('accounting.budgets.growth.update', $budget->id) }}" id="growth-form">
                        @csrf

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-end">{{ $budget->year - 3 }}</th>
                                        <th class="text-end">{{ $budget->year - 2 }}</th>
                                        <th class="text-end">{{ $budget->year - 1 }}</th>
                                        <th>Trendline</th>
                                        <th class="text-end">Projected {{ $budget->year }}</th>
                                        <th class="text-end">Budgeted {{ $budget->year }}</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($growthEntries as $entry)
                                    <tr class="growth-entry-row" data-entry-id="{{ $entry->id }}"
                                        data-year-minus-3="{{ $entry->year_minus_3 ?? 0 }}"
                                        data-year-minus-2="{{ $entry->year_minus_2 ?? 0 }}"
                                        data-year-minus-1="{{ $entry->year_minus_1 ?? 0 }}">
                                        <td>
                                            <input type="hidden" name="growth_entries[{{ $loop->index }}][id]" value="{{ $entry->id }}">
                                            <strong>{{ $entry->product->name }}</strong>
                                        </td>
                                        <td>
                                            <input type="number" name="growth_entries[{{ $loop->index }}][year_minus_3]"
                                                   class="form-control form-control-sm text-end year-minus-3" step="0.01"
                                                   value="{{ $entry->year_minus_3 }}"
                                                   placeholder="0.00">
                                        </td>
                                        <td>
                                            <input type="number" name="growth_entries[{{ $loop->index }}][year_minus_2]"
                                                   class="form-control form-control-sm text-end year-minus-2" step="0.01"
                                                   value="{{ $entry->year_minus_2 }}"
                                                   placeholder="0.00">
                                        </td>
                                        <td>
                                            <input type="number" name="growth_entries[{{ $loop->index }}][year_minus_1]"
                                                   class="form-control form-control-sm text-end year-minus-1" step="0.01"
                                                   value="{{ $entry->year_minus_1 }}"
                                                   placeholder="0.00">
                                        </td>
                                        <td>
                                            <select name="growth_entries[{{ $loop->index }}][trendline_type]"
                                                    class="form-control form-control-sm trendline-type"
                                                    data-entry-id="{{ $entry->id }}">
                                                <option value="linear" {{ $entry->trendline_type === 'linear' ? 'selected' : '' }}>Linear</option>
                                                <option value="logarithmic" {{ $entry->trendline_type === 'logarithmic' ? 'selected' : '' }}>Logarithmic</option>
                                                <option value="polynomial" {{ $entry->trendline_type === 'polynomial' ? 'selected' : '' }}>Polynomial</option>
                                            </select>
                                            <input type="hidden" name="growth_entries[{{ $loop->index }}][polynomial_order]"
                                                   class="polynomial-order" value="{{ $entry->polynomial_order ?? 2 }}">
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-success-subtle text-success">
                                                    <i class="ti ti-trending-up"></i>
                                                </span>
                                                <input type="text" class="form-control text-end projected-value fw-bold"
                                                       value="—" readonly>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" name="growth_entries[{{ $loop->index }}][budgeted_value]"
                                                   class="form-control form-control-sm text-end budgeted-value fw-bold" step="0.01"
                                                   value="{{ $entry->budgeted_value }}"
                                                   placeholder="0.00">
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-icon btn-outline-primary calculate-btn" type="button"
                                                    data-entry-id="{{ $entry->id }}"
                                                    data-route="{{ route('accounting.budgets.growth.calculate-trendline', $budget->id) }}"
                                                    title="Calculate projection">
                                                <i class="ti ti-calculator"></i>
                                            </button>
                                            <button class="btn btn-sm btn-icon btn-outline-success use-projection-btn" type="button"
                                                    title="Use projected value as budget">
                                                <i class="ti ti-check"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr class="fw-bold">
                                        <td>Total</td>
                                        <td class="text-end" id="total-year-3">{{ number_format($growthEntries->sum('year_minus_3'), 2) }}</td>
                                        <td class="text-end" id="total-year-2">{{ number_format($growthEntries->sum('year_minus_2'), 2) }}</td>
                                        <td class="text-end" id="total-year-1">{{ number_format($growthEntries->sum('year_minus_1'), 2) }}</td>
                                        <td></td>
                                        <td class="text-end text-success" id="total-projected">—</td>
                                        <td class="text-end text-primary" id="total-budgeted">{{ number_format($growthEntries->sum('budgeted_value'), 2) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between mt-4">
                            <div>
                                <a href="{{ route('accounting.budgets.index') }}" class="btn btn-secondary">
                                    <i class="ti ti-arrow-left"></i> Back to Budgets
                                </a>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-device-floppy"></i> Save Growth Budget
                                </button>
                                <a href="{{ route('accounting.budgets.capacity', $budget->id) }}" class="btn btn-success">
                                    Next: Capacity <i class="ti ti-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Growth Budget Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h6 class="text-muted">Total Products</h6>
                                <h3>{{ $growthEntries->count() }}</h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h6 class="text-muted">With Data</h6>
                                <h3>{{ $growthEntries->filter(fn($e) => $e->hasEnoughDataForTrendline())->count() }}</h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h6 class="text-muted">Total Budgeted</h6>
                                <h3 id="summary-budgeted">{{ number_format($growthEntries->sum('budgeted_value'), 0) }}</h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h6 class="text-muted">Completion</h6>
                                <h3 id="summary-completion">{{ $growthEntries->count() > 0 ? round($growthEntries->filter(fn($e) => $e->budgeted_value !== null)->count() / $growthEntries->count() * 100, 0) : 0 }}%</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    padding: 1rem;
    border-left: 4px solid #007bff;
    background-color: #f8f9fa;
    border-radius: 4px;
}

.stat-card h6 {
    font-weight: 600;
    font-size: 0.875rem;
}

.stat-card h3 {
    margin: 0.5rem 0 0 0;
    font-weight: 700;
}

.btn-icon {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.growth-entry-row:hover {
    background-color: rgba(0,123,255,0.05);
}
</style>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const budgetYear = {{ $budget->year }};
    const years = [budgetYear - 3, budgetYear - 2, budgetYear - 1, budgetYear];
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Data for charts (prepared by controller)
    const productData = @json($chartData);

    let projectedValues = {};
    let barChart = null;
    let trendlineCharts = {};

    // Trendline calculation functions
    function calculateLinear(data) {
        const validData = data.filter(v => v !== null && v !== undefined && v > 0);
        if (validData.length < 2) return validData[validData.length - 1] || 0;

        const n = validData.length;
        const x = validData.map((_, i) => i + 1);
        const y = validData;

        const sumX = x.reduce((a, b) => a + b, 0);
        const sumY = y.reduce((a, b) => a + b, 0);
        const sumXY = x.reduce((acc, xi, i) => acc + xi * y[i], 0);
        const sumX2 = x.reduce((acc, xi) => acc + xi * xi, 0);

        const m = (n * sumXY - sumX * sumY) / (n * sumX2 - sumX * sumX);
        const b = (sumY - m * sumX) / n;

        return Math.max(0, m * (n + 1) + b);
    }

    function calculateLogarithmic(data) {
        const validData = data.filter(v => v !== null && v !== undefined && v > 0);
        if (validData.length < 2) return validData[validData.length - 1] || 0;

        const n = validData.length;
        const x = validData.map((_, i) => i + 1);
        const y = validData;
        const lnX = x.map(xi => Math.log(xi));

        const sumLnX = lnX.reduce((a, b) => a + b, 0);
        const sumY = y.reduce((a, b) => a + b, 0);
        const sumLnX2 = lnX.reduce((acc, ln) => acc + ln * ln, 0);
        const sumYLnX = lnX.reduce((acc, ln, i) => acc + y[i] * ln, 0);

        const denom = n * sumLnX2 - sumLnX * sumLnX;
        if (Math.abs(denom) < 1e-10) return validData[validData.length - 1] || 0;

        const a = (n * sumYLnX - sumLnX * sumY) / denom;
        const b = (sumY - a * sumLnX) / n;

        return Math.max(0, a * Math.log(n + 1) + b);
    }

    function calculatePolynomial(data) {
        // Simplified quadratic fit
        const validData = data.filter(v => v !== null && v !== undefined && v > 0);
        if (validData.length < 2) return validData[validData.length - 1] || 0;
        if (validData.length === 2) return calculateLinear(data);

        const n = validData.length;
        const x = validData.map((_, i) => i + 1);
        const y = validData;

        // For simplicity, use linear for now if not enough points
        if (n < 3) return calculateLinear(data);

        // Simple quadratic using last 3 points
        const x1 = 1, x2 = 2, x3 = 3;
        const y1 = validData[0], y2 = validData[1], y3 = validData[2];

        const a = ((y3 - y1) - (x3 - x1) * (y2 - y1) / (x2 - x1)) /
                  ((x3 * x3 - x1 * x1) - (x3 - x1) * (x2 * x2 - x1 * x1) / (x2 - x1));
        const b = (y2 - y1 - a * (x2 * x2 - x1 * x1)) / (x2 - x1);
        const c = y1 - a * x1 * x1 - b * x1;

        const nextX = n + 1;
        return Math.max(0, a * nextX * nextX + b * nextX + c);
    }

    function calculateProjection(data, type) {
        switch (type) {
            case 'logarithmic': return calculateLogarithmic(data);
            case 'polynomial': return calculatePolynomial(data);
            default: return calculateLinear(data);
        }
    }

    function formatNumber(num) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(num);
    }

    // Calculate all projections on page load
    function calculateAllProjections() {
        let totalProjected = 0;

        document.querySelectorAll('.growth-entry-row').forEach(row => {
            const entryId = row.dataset.entryId;
            const y3 = parseFloat(row.querySelector('.year-minus-3').value) || 0;
            const y2 = parseFloat(row.querySelector('.year-minus-2').value) || 0;
            const y1 = parseFloat(row.querySelector('.year-minus-1').value) || 0;
            const type = row.querySelector('.trendline-type').value;

            const data = [y3, y2, y1];
            const projection = calculateProjection(data, type);

            projectedValues[entryId] = projection;
            row.querySelector('.projected-value').value = formatNumber(projection);

            // Update the mini chart display
            const displayEl = document.querySelector('.projected-display-' + entryId);
            if (displayEl) {
                displayEl.textContent = formatNumber(projection);
            }

            totalProjected += projection;
        });

        document.getElementById('total-projected').textContent = formatNumber(totalProjected);
        updateBarChart();
        updateTrendlineCharts();
    }

    // Initialize bar chart
    function initBarChart() {
        const categories = productData.map(p => p.name);
        const series = [
            { name: years[0].toString(), data: productData.map(p => p.year_minus_3) },
            { name: years[1].toString(), data: productData.map(p => p.year_minus_2) },
            { name: years[2].toString(), data: productData.map(p => p.year_minus_1) },
            { name: years[3].toString() + ' (Projected)', data: productData.map(p => projectedValues[p.id] || 0) }
        ];

        const options = {
            series: series,
            chart: {
                type: 'bar',
                height: 400,
                toolbar: { show: true },
                animations: { enabled: true }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    borderRadius: 4,
                    dataLabels: { position: 'top' }
                }
            },
            dataLabels: { enabled: false },
            stroke: { show: true, width: 2, colors: ['transparent'] },
            xaxis: {
                categories: categories,
                labels: { rotate: -45, style: { fontSize: '12px' } }
            },
            yaxis: {
                title: { text: 'Revenue (EGP)' },
                labels: {
                    formatter: function(val) {
                        return new Intl.NumberFormat('en-US', { notation: 'compact' }).format(val);
                    }
                }
            },
            fill: { opacity: 1 },
            colors: ['#A8D5E2', '#6BB3D9', '#3498DB', '#27AE60'],
            tooltip: {
                y: {
                    formatter: function(val) {
                        return formatNumber(val) + ' EGP';
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left'
            }
        };

        barChart = new ApexCharts(document.querySelector('#revenueBarChart'), options);
        barChart.render();
    }

    // Update bar chart with new projections
    function updateBarChart() {
        if (!barChart) return;

        const projectedData = productData.map(p => projectedValues[p.id] || 0);
        barChart.updateSeries([
            { name: years[0].toString(), data: productData.map(p => p.year_minus_3) },
            { name: years[1].toString(), data: productData.map(p => p.year_minus_2) },
            { name: years[2].toString(), data: productData.map(p => p.year_minus_1) },
            { name: years[3].toString() + ' (Projected)', data: projectedData }
        ]);
    }

    // Initialize mini trendline charts
    function initTrendlineCharts() {
        productData.forEach(product => {
            const chartEl = document.querySelector('#trendline-chart-' + product.id);
            if (!chartEl) return;

            const data = [product.year_minus_3, product.year_minus_2, product.year_minus_1];
            const projection = projectedValues[product.id] || 0;

            const options = {
                series: [{
                    name: 'Revenue',
                    data: [...data, projection]
                }],
                chart: {
                    type: 'line',
                    height: 180,
                    sparkline: { enabled: false },
                    toolbar: { show: false },
                    animations: { enabled: true }
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                markers: {
                    size: 5,
                    colors: ['#3498DB'],
                    strokeWidth: 0
                },
                colors: ['#3498DB'],
                xaxis: {
                    categories: years.map(String),
                    labels: { style: { fontSize: '10px' } }
                },
                yaxis: {
                    labels: {
                        show: false
                    }
                },
                grid: {
                    padding: { left: 10, right: 10 }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return formatNumber(val);
                        }
                    }
                },
                annotations: {
                    points: [{
                        x: years[3].toString(),
                        y: projection,
                        marker: {
                            size: 6,
                            fillColor: '#27AE60',
                            strokeColor: '#fff',
                            strokeWidth: 2
                        },
                        label: {
                            text: 'Projected',
                            style: { fontSize: '10px' }
                        }
                    }]
                }
            };

            trendlineCharts[product.id] = new ApexCharts(chartEl, options);
            trendlineCharts[product.id].render();
        });
    }

    // Update trendline charts
    function updateTrendlineCharts() {
        productData.forEach(product => {
            const chart = trendlineCharts[product.id];
            if (!chart) return;

            const row = document.querySelector(`.growth-entry-row[data-entry-id="${product.id}"]`);
            if (!row) return;

            const y3 = parseFloat(row.querySelector('.year-minus-3').value) || 0;
            const y2 = parseFloat(row.querySelector('.year-minus-2').value) || 0;
            const y1 = parseFloat(row.querySelector('.year-minus-1').value) || 0;
            const projection = projectedValues[product.id] || 0;

            chart.updateSeries([{
                name: 'Revenue',
                data: [y3, y2, y1, projection]
            }]);
        });
    }

    // Chart type toggle
    document.getElementById('chart-grouped')?.addEventListener('click', function() {
        this.classList.add('active');
        document.getElementById('chart-stacked').classList.remove('active');
        barChart.updateOptions({ chart: { stacked: false } });
    });

    document.getElementById('chart-stacked')?.addEventListener('click', function() {
        this.classList.add('active');
        document.getElementById('chart-grouped').classList.remove('active');
        barChart.updateOptions({ chart: { stacked: true } });
    });

    // Trendline type change handler
    document.querySelectorAll('.trendline-type').forEach(select => {
        select.addEventListener('change', function() {
            calculateAllProjections();
        });
    });

    // Historical value change handler (recalculate on input change)
    document.querySelectorAll('.year-minus-3, .year-minus-2, .year-minus-1').forEach(input => {
        input.addEventListener('change', function() {
            calculateAllProjections();
        });
    });

    // Calculate button handler (server-side calculation)
    document.querySelectorAll('.calculate-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const entryId = this.dataset.entryId;
            const route = this.dataset.route;
            const row = this.closest('tr');

            const trendlineType = row.querySelector('.trendline-type').value;
            const polynomialOrder = row.querySelector('.polynomial-order').value;

            fetch(route, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({
                    growth_entry_id: entryId,
                    trendline_type: trendlineType,
                    polynomial_order: polynomialOrder
                })
            })
            .then(response => response.json())
            .then(data => {
                projectedValues[entryId] = data.projection;
                row.querySelector('.projected-value').value = formatNumber(data.projection);

                const displayEl = document.querySelector('.projected-display-' + entryId);
                if (displayEl) displayEl.textContent = formatNumber(data.projection);

                updateBarChart();
                updateTrendlineCharts();
                updateTotals();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to calculate trendline');
            });
        });
    });

    // Use projection as budget button
    document.querySelectorAll('.use-projection-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const entryId = row.dataset.entryId;
            const projection = projectedValues[entryId] || 0;

            row.querySelector('.budgeted-value').value = projection.toFixed(2);
            updateTotals();
        });
    });

    // Calculate all button
    document.getElementById('calculate-all-btn')?.addEventListener('click', function() {
        calculateAllProjections();

        // Also copy all projections to budgeted if empty
        document.querySelectorAll('.growth-entry-row').forEach(row => {
            const budgetedInput = row.querySelector('.budgeted-value');
            const entryId = row.dataset.entryId;
            if (!budgetedInput.value || budgetedInput.value === '0') {
                budgetedInput.value = (projectedValues[entryId] || 0).toFixed(2);
            }
        });
        updateTotals();
    });

    // Update totals
    function updateTotals() {
        let totalBudgeted = 0;
        let completedCount = 0;

        document.querySelectorAll('.growth-entry-row').forEach(row => {
            const val = parseFloat(row.querySelector('.budgeted-value').value) || 0;
            totalBudgeted += val;
            if (val > 0) completedCount++;
        });

        document.getElementById('total-budgeted').textContent = formatNumber(totalBudgeted);
        document.getElementById('summary-budgeted').textContent = new Intl.NumberFormat('en-US').format(Math.round(totalBudgeted));

        const totalCount = document.querySelectorAll('.growth-entry-row').length;
        const completion = totalCount > 0 ? Math.round(completedCount / totalCount * 100) : 0;
        document.getElementById('summary-completion').textContent = completion + '%';
    }

    // Populate historical data button
    document.getElementById('populate-historical-btn')?.addEventListener('click', function() {
        if (confirm('This will calculate income from paid contracts for ' + years.slice(0, 3).join(', ') + ' for each product. Continue?')) {
            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="ti ti-loader ti-spin"></i> Loading...';
            btn.disabled = true;

            fetch(this.dataset.route, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-Token': csrfToken,
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    alert(data.message || 'Failed to populate historical data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btn.innerHTML = originalText;
                btn.disabled = false;
                alert('Failed to populate historical data');
            });
        }
    });

    // Initialize
    initBarChart();
    calculateAllProjections();
    setTimeout(initTrendlineCharts, 100); // Small delay to ensure bar chart is ready
});
</script>
@endsection
