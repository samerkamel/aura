@extends('layouts/layoutMaster')

@section('title', 'Budget ' . $budget->year . ' - Growth')

@section('vendor-script')
@vite(['resources/assets/vendor/libs/chartjs/chartjs.js'])
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-regression-trendline/dist/chartjs-plugin-regression-trendline.min.js"></script>
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
                    <a class="nav-link" href="{{ route('accounting.budgets.expenses', $budget->id) }}">Expenses</a>
                </li>
                <li class="nav-item ms-auto">
                    <a class="nav-link" href="{{ route('accounting.budgets.summary', $budget->id) }}">Summary</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Per-Product Charts with Bars and Trendlines -->
    <div class="row mb-4" id="trendline-charts-row">
        @foreach($growthEntries as $entry)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">{{ $entry->product->name }}</h6>
                    <span class="badge bg-label-{{ $entry->trendline_type === 'linear' ? 'primary' : ($entry->trendline_type === 'logarithmic' ? 'warning' : 'success') }} trendline-badge-{{ $entry->id }}">
                        {{ ucfirst($entry->trendline_type ?? 'linear') }}
                    </span>
                </div>
                <div class="card-body p-2">
                    <canvas id="trendline-chart-{{ $entry->id }}" style="height: 280px;"></canvas>
                    <div class="text-center mt-2">
                        <span class="badge bg-success fs-6">
                            {{ $budget->year }} Projected: <span class="projected-display-{{ $entry->id }}">Calculating...</span>
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
    let trendlineCharts = {};
    let regressionCoeffs = {}; // Store regression coefficients per product

    // Linear regression: y = mx + b
    function linearRegression(data) {
        const validData = data.map((v, i) => ({ x: i + 1, y: v || 0 })).filter(d => d.y > 0);
        if (validData.length < 2) {
            const lastValid = data.filter(v => v > 0).pop() || 0;
            return { m: 0, b: lastValid, predict: (x) => lastValid };
        }

        const n = validData.length;
        const sumX = validData.reduce((acc, d) => acc + d.x, 0);
        const sumY = validData.reduce((acc, d) => acc + d.y, 0);
        const sumXY = validData.reduce((acc, d) => acc + d.x * d.y, 0);
        const sumX2 = validData.reduce((acc, d) => acc + d.x * d.x, 0);

        const denom = n * sumX2 - sumX * sumX;
        if (Math.abs(denom) < 1e-10) {
            return { m: 0, b: sumY / n, predict: (x) => sumY / n };
        }

        const m = (n * sumXY - sumX * sumY) / denom;
        const b = (sumY - m * sumX) / n;

        return { m, b, predict: (x) => Math.max(0, m * x + b) };
    }

    // Logarithmic regression: y = a * ln(x) + b
    function logarithmicRegression(data) {
        const validData = data.map((v, i) => ({ x: i + 1, y: v || 0 })).filter(d => d.y > 0);
        if (validData.length < 2) {
            const lastValid = data.filter(v => v > 0).pop() || 0;
            return { a: 0, b: lastValid, predict: (x) => lastValid };
        }

        const n = validData.length;
        const lnX = validData.map(d => Math.log(d.x));
        const sumLnX = lnX.reduce((acc, v) => acc + v, 0);
        const sumY = validData.reduce((acc, d) => acc + d.y, 0);
        const sumLnX2 = lnX.reduce((acc, v) => acc + v * v, 0);
        const sumYLnX = lnX.reduce((acc, v, i) => acc + validData[i].y * v, 0);

        const denom = n * sumLnX2 - sumLnX * sumLnX;
        if (Math.abs(denom) < 1e-10) {
            return { a: 0, b: sumY / n, predict: (x) => sumY / n };
        }

        const a = (n * sumYLnX - sumLnX * sumY) / denom;
        const b = (sumY - a * sumLnX) / n;

        return { a, b, predict: (x) => Math.max(0, a * Math.log(x) + b) };
    }

    // Polynomial regression (quadratic): y = ax² + bx + c
    function polynomialRegression(data) {
        const validData = data.map((v, i) => ({ x: i + 1, y: v || 0 })).filter(d => d.y > 0);
        if (validData.length < 3) {
            return linearRegression(data);
        }

        const n = validData.length;
        const x = validData.map(d => d.x);
        const y = validData.map(d => d.y);

        let sumX = 0, sumX2 = 0, sumX3 = 0, sumX4 = 0;
        let sumY = 0, sumXY = 0, sumX2Y = 0;

        for (let i = 0; i < n; i++) {
            const xi = x[i], yi = y[i];
            sumX += xi;
            sumX2 += xi * xi;
            sumX3 += xi * xi * xi;
            sumX4 += xi * xi * xi * xi;
            sumY += yi;
            sumXY += xi * yi;
            sumX2Y += xi * xi * yi;
        }

        const D = n * (sumX2 * sumX4 - sumX3 * sumX3) -
                  sumX * (sumX * sumX4 - sumX2 * sumX3) +
                  sumX2 * (sumX * sumX3 - sumX2 * sumX2);

        if (Math.abs(D) < 1e-10) {
            return linearRegression(data);
        }

        const Da = sumY * (sumX2 * sumX4 - sumX3 * sumX3) -
                   sumX * (sumXY * sumX4 - sumX2Y * sumX3) +
                   sumX2 * (sumXY * sumX3 - sumX2Y * sumX2);

        const Db = n * (sumXY * sumX4 - sumX2Y * sumX3) -
                   sumY * (sumX * sumX4 - sumX2 * sumX3) +
                   sumX2 * (sumX * sumX2Y - sumX2 * sumXY);

        const Dc = n * (sumX2 * sumX2Y - sumX3 * sumXY) -
                   sumX * (sumX * sumX2Y - sumX2 * sumXY) +
                   sumY * (sumX * sumX3 - sumX2 * sumX2);

        const c = Da / D;
        const b = Db / D;
        const a = Dc / D;

        return { a, b, c, predict: (xVal) => Math.max(0, a * xVal * xVal + b * xVal + c) };
    }

    // Get regression based on type
    function getRegression(data, type) {
        switch (type) {
            case 'logarithmic': return logarithmicRegression(data);
            case 'polynomial': return polynomialRegression(data);
            default: return linearRegression(data);
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
            const regression = getRegression(data, type);
            regressionCoeffs[entryId] = { regression, type };

            // Project to year 4 (next year)
            const projection = regression.predict(4);

            projectedValues[entryId] = projection;
            row.querySelector('.projected-value').value = formatNumber(projection);

            // Update the mini chart display
            const displayEl = document.querySelector('.projected-display-' + entryId);
            if (displayEl) {
                displayEl.textContent = formatNumber(projection);
            }

            // Update trendline badge
            const badgeEl = document.querySelector('.trendline-badge-' + entryId);
            if (badgeEl) {
                badgeEl.textContent = type.charAt(0).toUpperCase() + type.slice(1);
                badgeEl.className = 'badge bg-label-' + (type === 'linear' ? 'primary' : (type === 'logarithmic' ? 'warning' : 'success')) + ' trendline-badge-' + entryId;
            }

            totalProjected += projection;
        });

        document.getElementById('total-projected').textContent = formatNumber(totalProjected);
        updateTrendlineCharts();
    }

    // Map trendline type to plugin type
    function getPluginType(type) {
        switch (type) {
            case 'logarithmic': return 'logarithmic';
            case 'polynomial': return 'polynomial';
            default: return 'linear';
        }
    }

    // Get trendline color based on type
    function getTrendlineColor(type) {
        switch (type) {
            case 'logarithmic': return '#F39C12';
            case 'polynomial': return '#27AE60';
            default: return '#3498DB';
        }
    }

    // Initialize trendline charts with Chart.js
    function initTrendlineCharts() {
        productData.forEach(product => {
            const canvas = document.getElementById('trendline-chart-' + product.id);
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            const data = [product.year_minus_3 || 0, product.year_minus_2 || 0, product.year_minus_1 || 0];
            const projection = projectedValues[product.id] || 0;
            const regData = regressionCoeffs[product.id];
            const type = regData ? regData.type : 'linear';
            const trendlineColor = getTrendlineColor(type);

            // Include projected value in data for trendline calculation
            const allData = [...data, projection];

            const chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: years.map(String),
                    datasets: [{
                        label: 'Revenue',
                        data: allData,
                        backgroundColor: [
                            'rgba(168, 213, 226, 0.8)',
                            'rgba(168, 213, 226, 0.8)',
                            'rgba(168, 213, 226, 0.8)',
                            'rgba(39, 174, 96, 0.8)' // Projected year in green
                        ],
                        borderColor: [
                            'rgba(168, 213, 226, 1)',
                            'rgba(168, 213, 226, 1)',
                            'rgba(168, 213, 226, 1)',
                            'rgba(39, 174, 96, 1)'
                        ],
                        borderWidth: 1,
                        borderRadius: 4,
                        regressionTrendline: {
                            showLine: true
                        }
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                font: { size: 10 },
                                boxWidth: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return formatNumber(context.raw) + ' EGP';
                                }
                            }
                        },
                        regressionTrendline: {
                            enabled: true,
                            type: getPluginType(type),
                            degree: 2, // For polynomial
                            steps: 100,
                            color: trendlineColor,
                            borderWidth: 3
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                font: { size: 11 }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                font: { size: 10 },
                                callback: function(val) {
                                    if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                                    if (val >= 1000) return (val / 1000).toFixed(0) + 'K';
                                    return val;
                                }
                            }
                        }
                    }
                }
            });

            trendlineCharts[product.id] = chart;
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
            const type = row.querySelector('.trendline-type').value;
            const projection = projectedValues[product.id] || 0;
            const trendlineColor = getTrendlineColor(type);

            // Update chart data
            chart.data.datasets[0].data = [y3, y2, y1, projection];

            // Update trendline options
            chart.options.plugins.regressionTrendline.type = getPluginType(type);
            chart.options.plugins.regressionTrendline.color = trendlineColor;

            chart.update();
        });
    }

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
    calculateAllProjections();
    setTimeout(initTrendlineCharts, 100); // Small delay to ensure projections are calculated
});
</script>
@endsection
