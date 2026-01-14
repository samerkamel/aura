@extends('layouts/layoutMaster')

@section('title', 'Budget ' . $budget->year . ' - Result')

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
            <h1 class="h3">Budget {{ $budget->year }} - Result Tab</h1>
            <p class="text-muted">Compare Growth vs Collection methods and select final budget values</p>
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
                    <a class="nav-link" href="{{ route('accounting.budgets.growth', $budget->id) }}">
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
                    <a class="nav-link active" href="{{ route('accounting.budgets.result', $budget->id) }}">
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

    <!-- Comparison Chart -->
    <div class="row mb-4">
        <div class="col-12 col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Growth vs Collection Comparison</h5>
                        <small class="text-muted">Select the best method for each product</small>
                    </div>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary active" id="chart-grouped">Grouped</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="chart-stacked">Stacked</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="comparisonBarChart" style="min-height: 400px;"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Method Distribution</h5>
                </div>
                <div class="card-body">
                    <div id="methodPieChart" style="min-height: 300px;"></div>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span><span class="badge bg-primary me-2">Growth</span> Total:</span>
                            <strong id="growth-total">{{ number_format($growthEntries->sum('budgeted_value'), 0) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span><span class="badge bg-success me-2">Collection</span> Total:</span>
                            <strong id="collection-total">{{ number_format($collectionEntries->sum('budgeted_income'), 0) }}</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span><strong>Difference:</strong></span>
                            @php
                                $diff = $growthEntries->sum('budgeted_value') - $collectionEntries->sum('budgeted_income');
                                $diffPct = $collectionEntries->sum('budgeted_income') > 0
                                    ? ($diff / $collectionEntries->sum('budgeted_income')) * 100
                                    : 0;
                            @endphp
                            <strong class="{{ $diff >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 0) }}
                                ({{ number_format($diffPct, 1) }}%)
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Result Selection Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Final Budget Selection</h5>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" id="select-all-growth-btn">
                    <i class="ti ti-trending-up"></i> Select All Growth
                </button>
                <button class="btn btn-sm btn-outline-success" id="select-all-collection-btn">
                    <i class="ti ti-coins"></i> Select All Collection
                </button>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('accounting.budgets.result.update', $budget->id) }}" id="result-form">
                @csrf

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Growth Value</th>
                                <th class="text-end">Collection Value</th>
                                <th class="text-end">Variance</th>
                                <th class="text-center">Selected Method</th>
                                <th class="text-end">Final Budget</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($resultEntries as $entry)
                            @php
                                $growthEntry = $growthEntries->firstWhere('product_id', $entry->product_id);
                                $collectionEntry = $collectionEntries->firstWhere('product_id', $entry->product_id);

                                $growthVal = $growthEntry->budgeted_value ?? 0;
                                $collectionVal = $collectionEntry->budgeted_income ?? 0;

                                $variance = $growthVal - $collectionVal;
                                $variancePct = $collectionVal > 0 ? ($variance / $collectionVal) * 100 : 0;

                                // Determine currently selected method
                                $currentMethod = 'growth';
                                if ($entry->final_value !== null) {
                                    if (abs($entry->final_value - $collectionVal) < 0.01) {
                                        $currentMethod = 'collection';
                                    } elseif (abs($entry->final_value - $growthVal) < 0.01) {
                                        $currentMethod = 'growth';
                                    } else {
                                        $currentMethod = 'manual';
                                    }
                                }
                            @endphp
                            <tr class="result-entry-row" data-entry-id="{{ $entry->id }}"
                                data-growth-value="{{ $growthVal }}"
                                data-collection-value="{{ $collectionVal }}">
                                <td>
                                    <input type="hidden" name="result_entries[{{ $loop->index }}][id]" value="{{ $entry->id }}">
                                    <strong>{{ $entry->product->name }}</strong>
                                </td>
                                <td class="text-end">
                                    <span class="growth-value {{ $currentMethod === 'growth' ? 'fw-bold text-primary' : '' }}">
                                        {{ number_format($growthVal, 2) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="collection-value {{ $currentMethod === 'collection' ? 'fw-bold text-success' : '' }}">
                                        {{ number_format($collectionVal, 2) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="{{ $variance >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $variance >= 0 ? '+' : '' }}{{ number_format($variance, 0) }}
                                        <small>({{ number_format($variancePct, 1) }}%)</small>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <select name="result_entries[{{ $loop->index }}][selected_method]"
                                            class="form-control form-control-sm method-select"
                                            data-entry-id="{{ $entry->id }}">
                                        <option value="growth" {{ $currentMethod === 'growth' ? 'selected' : '' }}>
                                            Growth ({{ number_format($growthVal, 0) }})
                                        </option>
                                        <option value="collection" {{ $currentMethod === 'collection' ? 'selected' : '' }}>
                                            Collection ({{ number_format($collectionVal, 0) }})
                                        </option>
                                        <option value="manual" {{ $currentMethod === 'manual' ? 'selected' : '' }}>
                                            Manual Override
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-success-subtle text-success">EGP</span>
                                        <input type="number" name="result_entries[{{ $loop->index }}][manual_override]"
                                               class="form-control text-end final-value fw-bold"
                                               value="{{ $entry->final_value ?? $growthVal }}"
                                               step="0.01" {{ $currentMethod !== 'manual' ? 'readonly' : '' }}>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td>Total</td>
                                <td class="text-end text-primary" id="total-growth">{{ number_format($growthEntries->sum('budgeted_value'), 2) }}</td>
                                <td class="text-end text-success" id="total-collection">{{ number_format($collectionEntries->sum('budgeted_income'), 2) }}</td>
                                <td class="text-end">-</td>
                                <td></td>
                                <td class="text-end text-dark" id="total-final">{{ number_format($resultEntries->sum('final_value') ?? $growthEntries->sum('budgeted_value'), 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Form Actions -->
                <div class="d-flex justify-content-between mt-4">
                    <div>
                        <a href="{{ route('accounting.budgets.collection', $budget->id) }}" class="btn btn-secondary">
                            <i class="ti ti-arrow-left"></i> Back to Collection
                        </a>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy"></i> Save Final Budget
                        </button>
                        <a href="{{ route('accounting.budgets.personnel', $budget->id) }}" class="btn btn-success">
                            Next: Personnel <i class="ti ti-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Card -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Result Budget Summary</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card stat-primary">
                        <h6 class="text-muted">Total Products</h6>
                        <h3>{{ $resultEntries->count() }}</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card stat-info">
                        <h6 class="text-muted">Using Growth</h6>
                        <h3 id="summary-growth-count">0</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card stat-success">
                        <h6 class="text-muted">Using Collection</h6>
                        <h3 id="summary-collection-count">0</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card stat-dark">
                        <h6 class="text-muted">Total Final Budget</h6>
                        <h3 id="summary-final">0</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recommendation Card -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="ti ti-bulb"></i> Recommendation</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-0">
                <h6><i class="ti ti-info-circle"></i> How to Choose</h6>
                <ul class="mb-0">
                    <li><strong>Growth Method:</strong> Best when you have consistent historical data and expect similar growth patterns.</li>
                    <li><strong>Collection Method:</strong> Best when your payment collection cycle is predictable and you want to budget based on cash flow.</li>
                    <li><strong>Manual Override:</strong> Use when you have specific knowledge about expected changes (new contracts, market shifts, etc.).</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    padding: 1rem;
    border-radius: 4px;
    background-color: #f8f9fa;
}

.stat-card.stat-primary { border-left: 4px solid #007bff; }
.stat-card.stat-success { border-left: 4px solid #28a745; }
.stat-card.stat-info { border-left: 4px solid #17a2b8; }
.stat-card.stat-dark { border-left: 4px solid #343a40; }

.stat-card h6 {
    font-weight: 600;
    font-size: 0.875rem;
}

.stat-card h3 {
    margin: 0.5rem 0 0 0;
    font-weight: 700;
}

.result-entry-row:hover {
    background-color: rgba(0,123,255,0.05);
}

.method-select {
    min-width: 160px;
}
</style>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const comparisonData = @json($comparisonData);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    let barChart = null;
    let pieChart = null;

    // Format number helper
    function formatNumber(num) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(num);
    }

    // Initialize comparison bar chart
    function initBarChart() {
        const categories = comparisonData.map(p => p.name);

        const options = {
            series: [
                { name: 'Growth Value', data: comparisonData.map(p => p.growth_value) },
                { name: 'Collection Value', data: comparisonData.map(p => p.collection_value) }
            ],
            chart: {
                type: 'bar',
                height: 400,
                toolbar: { show: true }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    borderRadius: 4
                }
            },
            dataLabels: { enabled: false },
            stroke: { show: true, width: 2, colors: ['transparent'] },
            xaxis: {
                categories: categories,
                labels: { rotate: -45, style: { fontSize: '11px' } }
            },
            yaxis: {
                title: { text: 'Budget (EGP)' },
                labels: {
                    formatter: function(val) {
                        return new Intl.NumberFormat('en-US', { notation: 'compact' }).format(val);
                    }
                }
            },
            fill: { opacity: 1 },
            colors: ['#3498DB', '#27AE60'],
            tooltip: {
                y: {
                    formatter: function(val) {
                        return formatNumber(val) + ' EGP';
                    }
                }
            },
            legend: {
                position: 'top'
            }
        };

        barChart = new ApexCharts(document.querySelector('#comparisonBarChart'), options);
        barChart.render();
    }

    // Initialize method distribution pie chart
    function initPieChart() {
        updatePieChart();
    }

    function updatePieChart() {
        let growthCount = 0, collectionCount = 0, manualCount = 0;

        document.querySelectorAll('.method-select').forEach(select => {
            switch(select.value) {
                case 'growth': growthCount++; break;
                case 'collection': collectionCount++; break;
                case 'manual': manualCount++; break;
            }
        });

        const data = [growthCount, collectionCount, manualCount].filter(v => v > 0);
        const labels = [];
        const colors = [];

        if (growthCount > 0) { labels.push('Growth'); colors.push('#3498DB'); }
        if (collectionCount > 0) { labels.push('Collection'); colors.push('#27AE60'); }
        if (manualCount > 0) { labels.push('Manual'); colors.push('#F39C12'); }

        if (pieChart) {
            pieChart.updateOptions({
                series: data,
                labels: labels,
                colors: colors
            });
        } else {
            const options = {
                series: data,
                chart: {
                    type: 'pie',
                    height: 300
                },
                labels: labels,
                colors: colors,
                legend: {
                    position: 'bottom'
                },
                dataLabels: {
                    formatter: function(val, opts) {
                        return opts.w.config.series[opts.seriesIndex] + ' products';
                    }
                }
            };

            pieChart = new ApexCharts(document.querySelector('#methodPieChart'), options);
            pieChart.render();
        }

        // Update summary counts
        document.getElementById('summary-growth-count').textContent = growthCount;
        document.getElementById('summary-collection-count').textContent = collectionCount;
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

    // Method select change handler
    document.querySelectorAll('.method-select').forEach(select => {
        select.addEventListener('change', function() {
            const row = this.closest('tr');
            const method = this.value;
            const finalInput = row.querySelector('.final-value');
            const growthVal = parseFloat(row.dataset.growthValue) || 0;
            const collectionVal = parseFloat(row.dataset.collectionValue) || 0;

            if (method === 'growth') {
                finalInput.value = growthVal.toFixed(2);
                finalInput.readOnly = true;
            } else if (method === 'collection') {
                finalInput.value = collectionVal.toFixed(2);
                finalInput.readOnly = true;
            } else {
                finalInput.readOnly = false;
                finalInput.focus();
            }

            // Update styling
            row.querySelector('.growth-value').classList.toggle('fw-bold', method === 'growth');
            row.querySelector('.growth-value').classList.toggle('text-primary', method === 'growth');
            row.querySelector('.collection-value').classList.toggle('fw-bold', method === 'collection');
            row.querySelector('.collection-value').classList.toggle('text-success', method === 'collection');

            updateTotals();
            updatePieChart();
        });
    });

    // Manual value change handler
    document.querySelectorAll('.final-value').forEach(input => {
        input.addEventListener('change', updateTotals);
    });

    // Select all growth button
    document.getElementById('select-all-growth-btn')?.addEventListener('click', function() {
        document.querySelectorAll('.method-select').forEach(select => {
            select.value = 'growth';
            select.dispatchEvent(new Event('change'));
        });
    });

    // Select all collection button
    document.getElementById('select-all-collection-btn')?.addEventListener('click', function() {
        document.querySelectorAll('.method-select').forEach(select => {
            select.value = 'collection';
            select.dispatchEvent(new Event('change'));
        });
    });

    // Update totals
    function updateTotals() {
        let total = 0;

        document.querySelectorAll('.result-entry-row').forEach(row => {
            total += parseFloat(row.querySelector('.final-value').value) || 0;
        });

        document.getElementById('total-final').textContent = formatNumber(total);
        document.getElementById('summary-final').textContent = new Intl.NumberFormat('en-US').format(Math.round(total));
    }

    // Initialize
    initBarChart();
    initPieChart();
    updateTotals();
});
</script>
@endsection
