@extends('layouts/layoutMaster')

@section('title', 'Budget ' . $budget->year . ' - Collection')

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
            <h1 class="h3">Budget {{ $budget->year }} - Collection Tab</h1>
            <p class="text-muted">Analyze payment balances and collection patterns to project income</p>
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
                    <a class="nav-link active" href="{{ route('accounting.budgets.collection', $budget->id) }}">
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

    <!-- Chart Section -->
    <div class="row mb-4">
        <!-- Collection Analysis Chart -->
        <div class="col-12 col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Collection Analysis by Product</h5>
                    <small class="text-muted">Balance comparison and collection months</small>
                </div>
                <div class="card-body">
                    <div id="collectionBarChart" style="min-height: 350px;"></div>
                </div>
            </div>
        </div>

        <!-- Collection Months Summary -->
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Collection Months Summary</h5>
                </div>
                <div class="card-body">
                    <div id="collectionMonthsChart" style="min-height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Collection Tab Content -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Collection-Based Budget Projections</h5>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-success" id="calculate-all-income-btn">
                    <i class="ti ti-calculator"></i> Calculate All Income
                </button>
                <button class="btn btn-sm btn-outline-primary" id="populate-collection-btn"
                        data-route="{{ route('accounting.budgets.collection.populate', $budget->id) }}">
                    <i class="ti ti-download"></i> Populate from Payment History
                </button>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('accounting.budgets.collection.update', $budget->id) }}" id="collection-form">
                @csrf

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Beginning Balance<br><small class="text-muted">Start of {{ $budget->year - 1 }}</small></th>
                                <th class="text-end">End Balance<br><small class="text-muted">End of {{ $budget->year - 1 }}</small></th>
                                <th class="text-end">Avg Balance</th>
                                <th class="text-end">Avg Monthly<br>Payment</th>
                                <th class="text-end">Collection<br>Months</th>
                                <th class="text-end">Budgeted<br>Income</th>
                                <th class="text-center">Patterns</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($collectionEntries as $entry)
                            <tr class="collection-entry-row" data-entry-id="{{ $entry->id }}">
                                <td>
                                    <input type="hidden" name="collection_entries[{{ $loop->index }}][id]" value="{{ $entry->id }}">
                                    <strong>{{ $entry->product->name }}</strong>
                                </td>
                                <td>
                                    <input type="number" name="collection_entries[{{ $loop->index }}][beginning_balance]"
                                           class="form-control form-control-sm text-end beginning-balance" step="0.01"
                                           value="{{ $entry->beginning_balance }}"
                                           placeholder="0.00">
                                </td>
                                <td>
                                    <input type="number" name="collection_entries[{{ $loop->index }}][end_balance]"
                                           class="form-control form-control-sm text-end end-balance" step="0.01"
                                           value="{{ $entry->end_balance }}"
                                           placeholder="0.00">
                                </td>
                                <td>
                                    <input type="number" name="collection_entries[{{ $loop->index }}][avg_balance]"
                                           class="form-control form-control-sm text-end avg-balance" step="0.01"
                                           value="{{ $entry->avg_balance }}"
                                           placeholder="0.00">
                                </td>
                                <td>
                                    <input type="number" name="collection_entries[{{ $loop->index }}][avg_payment_per_month]"
                                           class="form-control form-control-sm text-end avg-payment" step="0.01"
                                           value="{{ $entry->avg_payment_per_month }}"
                                           placeholder="0.00">
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-info-subtle text-info">
                                            <i class="ti ti-calendar"></i>
                                        </span>
                                        <input type="text" class="form-control text-end collection-months fw-bold"
                                               value="{{ number_format($entry->projected_collection_months ?? 0, 2) }}" readonly>
                                    </div>
                                    <small class="text-muted">
                                        Last yr: {{ number_format($entry->last_year_collection_months ?? 0, 1) }}m |
                                        Budgeted: {{ number_format($entry->budgeted_collection_months ?? 0, 1) }}m
                                    </small>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-success-subtle text-success">EGP</span>
                                        <input type="text" class="form-control text-end budgeted-income fw-bold"
                                               value="{{ number_format($entry->budgeted_income ?? 0, 2) }}" readonly>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-icon btn-outline-primary manage-patterns-btn" type="button"
                                            data-entry-id="{{ $entry->id }}"
                                            data-product-name="{{ $entry->product->name }}"
                                            data-bs-toggle="modal" data-bs-target="#patternModal"
                                            title="Manage payment patterns">
                                        <i class="ti ti-chart-pie"></i>
                                        <span class="badge bg-primary rounded-pill ms-1 pattern-count-{{ $entry->id }}">{{ $entry->patterns->count() }}</span>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td>Total</td>
                                <td class="text-end" id="total-beginning">{{ number_format($collectionEntries->sum('beginning_balance'), 2) }}</td>
                                <td class="text-end" id="total-end">{{ number_format($collectionEntries->sum('end_balance'), 2) }}</td>
                                <td class="text-end" id="total-avg-balance">{{ number_format($collectionEntries->sum('avg_balance'), 2) }}</td>
                                <td class="text-end" id="total-avg-payment">{{ number_format($collectionEntries->sum('avg_payment_per_month'), 2) }}</td>
                                <td class="text-end text-info" id="total-months">-</td>
                                <td class="text-end text-success" id="total-income">{{ number_format($collectionEntries->sum('budgeted_income'), 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Form Actions -->
                <div class="d-flex justify-content-between mt-4">
                    <div>
                        <a href="{{ route('accounting.budgets.capacity', $budget->id) }}" class="btn btn-secondary">
                            <i class="ti ti-arrow-left"></i> Back to Capacity
                        </a>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy"></i> Save Collection Budget
                        </button>
                        <a href="{{ route('accounting.budgets.result', $budget->id) }}" class="btn btn-success">
                            Next: Result <i class="ti ti-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Card -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Collection Budget Summary</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <h6 class="text-muted">Total Products</h6>
                        <h3>{{ $collectionEntries->count() }}</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h6 class="text-muted">With Patterns</h6>
                        <h3>{{ $collectionEntries->filter(fn($e) => $e->patterns->count() > 0)->count() }}</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h6 class="text-muted">Total Budgeted Income</h6>
                        <h3 id="summary-income">{{ number_format($collectionEntries->sum('budgeted_income'), 0) }}</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h6 class="text-muted">Avg Collection Months</h6>
                        @php
                            $validEntries = $collectionEntries->filter(fn($e) => $e->projected_collection_months > 0);
                            $avgMonths = $validEntries->count() > 0 ? $validEntries->avg('projected_collection_months') : 0;
                        @endphp
                        <h3 id="summary-avg-months">{{ number_format($avgMonths, 1) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Card -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="ti ti-info-circle"></i> How Collection Method Works</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Formula</h6>
                    <p class="text-muted mb-2">
                        <strong>Collection Months</strong> = Average Balance / Average Monthly Payment<br>
                        <strong>Budgeted Income</strong> = End Balance / Projected Collection Months * 12
                    </p>
                </div>
                <div class="col-md-6">
                    <h6>Payment Patterns</h6>
                    <p class="text-muted mb-2">
                        Define how contracts are typically paid (e.g., 50% upfront, 50% on completion).
                        This affects the "Budgeted Collection Months" calculation.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pattern Modal -->
<div class="modal fade" id="patternModal" tabindex="-1" aria-labelledby="patternModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="patternModalLabel">Payment Patterns - <span id="modal-product-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal-entry-id">

                <!-- Existing Patterns -->
                <div id="existing-patterns-container">
                    <h6>Existing Patterns</h6>
                    <div id="patterns-list">
                        <p class="text-muted">Loading patterns...</p>
                    </div>
                </div>

                <hr>

                <!-- Add New Pattern -->
                <h6>Add New Pattern</h6>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Pattern Name</label>
                        <input type="text" class="form-control" id="new-pattern-name" placeholder="e.g., Upfront Payment">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Contract % <small class="text-muted">(of all contracts)</small></label>
                        <input type="number" class="form-control" id="new-contract-percentage" value="100" min="0" max="100">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Quick Template</label>
                        <select class="form-control" id="pattern-template">
                            <option value="">Custom</option>
                            <option value="upfront">100% Upfront</option>
                            <option value="quarterly">Quarterly (25% each)</option>
                            <option value="monthly">Monthly (8.33% each)</option>
                            <option value="50-50">50% Upfront, 50% End</option>
                            <option value="30-70">30% Upfront, 70% End</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label">Monthly Distribution (must sum to 100%)</label>
                        <div class="row g-2">
                            @for($m = 1; $m <= 12; $m++)
                            <div class="col-md-2 col-4">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">M{{ $m }}</span>
                                    <input type="number" class="form-control monthly-pct" data-month="{{ $m }}"
                                           value="0" min="0" max="100" step="0.01">
                                </div>
                            </div>
                            @endfor
                        </div>
                        <small class="text-muted">Total: <span id="monthly-total">0</span>%</small>
                    </div>
                </div>

                <button type="button" class="btn btn-primary" id="add-pattern-btn">
                    <i class="ti ti-plus"></i> Add Pattern
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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

.collection-entry-row:hover {
    background-color: rgba(0,123,255,0.05);
}

.pattern-card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    background: #fafafa;
}

.pattern-card .pattern-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.pattern-distribution {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.pattern-month {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    background: #e8f4fd;
    border-radius: 4px;
}
</style>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const budgetId = {{ $budget->id }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const productData = @json($chartData);

    let barChart = null;
    let monthsChart = null;
    let currentEntryId = null;

    // Format number helper
    function formatNumber(num) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(num);
    }

    // Initialize bar chart
    function initBarChart() {
        const categories = productData.map(p => p.name);

        const options = {
            series: [
                { name: 'Beginning Balance', data: productData.map(p => p.beginning_balance) },
                { name: 'End Balance', data: productData.map(p => p.end_balance) },
                { name: 'Budgeted Income', data: productData.map(p => p.budgeted_income) }
            ],
            chart: {
                type: 'bar',
                height: 350,
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
                title: { text: 'Amount (EGP)' },
                labels: {
                    formatter: function(val) {
                        return new Intl.NumberFormat('en-US', { notation: 'compact' }).format(val);
                    }
                }
            },
            fill: { opacity: 1 },
            colors: ['#A8D5E2', '#F39C12', '#27AE60'],
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

        barChart = new ApexCharts(document.querySelector('#collectionBarChart'), options);
        barChart.render();
    }

    // Initialize collection months chart
    function initMonthsChart() {
        const options = {
            series: productData.map(p => p.projected_collection_months),
            chart: {
                type: 'donut',
                height: 350
            },
            labels: productData.map(p => p.name),
            colors: ['#3498DB', '#27AE60', '#F39C12', '#E74C3C', '#9B59B6', '#1ABC9C', '#34495E'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '60%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Avg Months',
                                formatter: function(w) {
                                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    const count = w.globals.seriesTotals.filter(v => v > 0).length;
                                    return count > 0 ? (total / count).toFixed(1) : '0';
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: {
                formatter: function(val, opts) {
                    return opts.w.config.series[opts.seriesIndex].toFixed(1) + 'm';
                }
            },
            legend: {
                position: 'bottom',
                fontSize: '11px'
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toFixed(2) + ' months';
                    }
                }
            }
        };

        monthsChart = new ApexCharts(document.querySelector('#collectionMonthsChart'), options);
        monthsChart.render();
    }

    // Calculate collection months and income for a row
    function calculateRowMetrics(row) {
        const avgBalance = parseFloat(row.querySelector('.avg-balance').value) || 0;
        const avgPayment = parseFloat(row.querySelector('.avg-payment').value) || 0;
        const endBalance = parseFloat(row.querySelector('.end-balance').value) || 0;

        // Last year collection months = avg balance / avg payment
        let lastYearMonths = avgPayment > 0 ? avgBalance / avgPayment : 0;

        // For simplicity, assume projected = last year (patterns would adjust budgeted months)
        let projectedMonths = lastYearMonths;

        // Budgeted income = end balance / projected months * 12
        let budgetedIncome = projectedMonths > 0 ? (endBalance / projectedMonths) * 12 : 0;

        row.querySelector('.collection-months').value = projectedMonths.toFixed(2);
        row.querySelector('.budgeted-income').value = formatNumber(budgetedIncome);

        return { projectedMonths, budgetedIncome };
    }

    // Calculate all rows
    function calculateAll() {
        let totalIncome = 0;
        let totalMonths = 0;
        let validCount = 0;

        document.querySelectorAll('.collection-entry-row').forEach(row => {
            const metrics = calculateRowMetrics(row);
            totalIncome += metrics.budgetedIncome;
            if (metrics.projectedMonths > 0) {
                totalMonths += metrics.projectedMonths;
                validCount++;
            }
        });

        document.getElementById('total-income').textContent = formatNumber(totalIncome);
        document.getElementById('summary-income').textContent = new Intl.NumberFormat('en-US').format(Math.round(totalIncome));

        const avgMonths = validCount > 0 ? totalMonths / validCount : 0;
        document.getElementById('summary-avg-months').textContent = avgMonths.toFixed(1);

        updateTotals();
    }

    // Update totals
    function updateTotals() {
        let totalBeginning = 0, totalEnd = 0, totalAvgBalance = 0, totalAvgPayment = 0;

        document.querySelectorAll('.collection-entry-row').forEach(row => {
            totalBeginning += parseFloat(row.querySelector('.beginning-balance').value) || 0;
            totalEnd += parseFloat(row.querySelector('.end-balance').value) || 0;
            totalAvgBalance += parseFloat(row.querySelector('.avg-balance').value) || 0;
            totalAvgPayment += parseFloat(row.querySelector('.avg-payment').value) || 0;
        });

        document.getElementById('total-beginning').textContent = formatNumber(totalBeginning);
        document.getElementById('total-end').textContent = formatNumber(totalEnd);
        document.getElementById('total-avg-balance').textContent = formatNumber(totalAvgBalance);
        document.getElementById('total-avg-payment').textContent = formatNumber(totalAvgPayment);
    }

    // Input change handlers
    document.querySelectorAll('.beginning-balance, .end-balance, .avg-balance, .avg-payment').forEach(input => {
        input.addEventListener('change', function() {
            const row = this.closest('tr');

            // Auto-calculate avg balance if beginning/end changed
            if (this.classList.contains('beginning-balance') || this.classList.contains('end-balance')) {
                const begin = parseFloat(row.querySelector('.beginning-balance').value) || 0;
                const end = parseFloat(row.querySelector('.end-balance').value) || 0;
                row.querySelector('.avg-balance').value = ((begin + end) / 2).toFixed(2);
            }

            calculateAll();
        });
    });

    // Calculate all button
    document.getElementById('calculate-all-income-btn')?.addEventListener('click', function() {
        calculateAll();
    });

    // Populate from history button
    document.getElementById('populate-collection-btn')?.addEventListener('click', function() {
        if (confirm('This will calculate balances from payment history for ' + ({{ $budget->year }} - 1) + '. Continue?')) {
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
                    alert(data.message || 'Failed to populate collection data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btn.innerHTML = originalText;
                btn.disabled = false;
                alert('Failed to populate collection data');
            });
        }
    });

    // Pattern modal handling
    document.querySelectorAll('.manage-patterns-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            currentEntryId = this.dataset.entryId;
            const productName = this.dataset.productName;

            document.getElementById('modal-entry-id').value = currentEntryId;
            document.getElementById('modal-product-name').textContent = productName;

            loadPatterns(currentEntryId);
        });
    });

    // Load patterns for an entry
    function loadPatterns(entryId) {
        const entry = productData.find(p => p.id == entryId);
        const container = document.getElementById('patterns-list');

        if (!entry || !entry.patterns || entry.patterns.length === 0) {
            container.innerHTML = '<p class="text-muted">No patterns defined. Add a pattern below.</p>';
            return;
        }

        let html = '';
        entry.patterns.forEach(pattern => {
            html += `
                <div class="pattern-card">
                    <div class="pattern-header">
                        <strong>${pattern.name}</strong>
                        <div>
                            <span class="badge bg-primary">${pattern.contract_percentage}% of contracts</span>
                            <span class="badge bg-info">${pattern.collection_months.toFixed(2)} months</span>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-pattern-btn"
                                    data-pattern-id="${pattern.id}">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;

        // Attach delete handlers
        container.querySelectorAll('.delete-pattern-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (confirm('Delete this pattern?')) {
                    deletePattern(this.dataset.patternId);
                }
            });
        });
    }

    // Pattern template handler
    document.getElementById('pattern-template')?.addEventListener('change', function() {
        const template = this.value;
        const inputs = document.querySelectorAll('.monthly-pct');

        // Reset all
        inputs.forEach(input => input.value = 0);

        switch(template) {
            case 'upfront':
                inputs[0].value = 100;
                document.getElementById('new-pattern-name').value = 'Upfront Payment';
                break;
            case 'quarterly':
                inputs[0].value = 25;
                inputs[3].value = 25;
                inputs[6].value = 25;
                inputs[9].value = 25;
                document.getElementById('new-pattern-name').value = 'Quarterly Payments';
                break;
            case 'monthly':
                inputs.forEach(input => input.value = 8.33);
                document.getElementById('new-pattern-name').value = 'Monthly Payments';
                break;
            case '50-50':
                inputs[0].value = 50;
                inputs[11].value = 50;
                document.getElementById('new-pattern-name').value = '50-50 Split';
                break;
            case '30-70':
                inputs[0].value = 30;
                inputs[11].value = 70;
                document.getElementById('new-pattern-name').value = '30-70 Split';
                break;
        }

        updateMonthlyTotal();
    });

    // Monthly percentage change handler
    document.querySelectorAll('.monthly-pct').forEach(input => {
        input.addEventListener('change', updateMonthlyTotal);
    });

    function updateMonthlyTotal() {
        let total = 0;
        document.querySelectorAll('.monthly-pct').forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        document.getElementById('monthly-total').textContent = total.toFixed(2);
    }

    // Add pattern button
    document.getElementById('add-pattern-btn')?.addEventListener('click', function() {
        const entryId = document.getElementById('modal-entry-id').value;
        const patternName = document.getElementById('new-pattern-name').value;
        const contractPct = document.getElementById('new-contract-percentage').value;

        const monthlyPcts = [];
        document.querySelectorAll('.monthly-pct').forEach(input => {
            monthlyPcts.push(parseFloat(input.value) || 0);
        });

        const total = monthlyPcts.reduce((a, b) => a + b, 0);
        if (Math.abs(total - 100) > 1) {
            alert('Monthly percentages must sum to 100%. Current total: ' + total.toFixed(2) + '%');
            return;
        }

        if (!patternName) {
            alert('Please enter a pattern name');
            return;
        }

        const btn = this;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="ti ti-loader ti-spin"></i> Adding...';
        btn.disabled = true;

        fetch('{{ route("accounting.budgets.collection.patterns.add", $budget->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': csrfToken,
            },
            body: JSON.stringify({
                collection_entry_id: entryId,
                pattern_name: patternName,
                contract_percentage: contractPct,
                monthly_percentages: monthlyPcts
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                btn.innerHTML = originalText;
                btn.disabled = false;
                alert(data.message || 'Failed to add pattern');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.innerHTML = originalText;
            btn.disabled = false;
            alert('Failed to add pattern');
        });
    });

    // Delete pattern
    function deletePattern(patternId) {
        fetch(`/accounting/budgets/${budgetId}/collection/patterns/${patternId}`, {
            method: 'DELETE',
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
                alert(data.message || 'Failed to delete pattern');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete pattern');
        });
    }

    // Initialize
    initBarChart();
    initMonthsChart();
    calculateAll();
});
</script>
@endsection
