@extends('layouts/layoutMaster')

@section('title', 'Budget ' . $budget->year . ' - Expenses')

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
            <h1 class="mb-1">Budget {{ $budget->year }} - Expenses Tab</h1>
            <p class="text-muted mb-0">Manage OpEx, Tax, and CapEx budget entries</p>
        </div>
        <span class="badge bg-label-{{ $budget->status === 'finalized' ? 'success' : ($budget->status === 'in_progress' ? 'warning' : 'secondary') }} fs-6">
            {{ ucfirst(str_replace('_', ' ', $budget->status)) }}
        </span>
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
            <a class="nav-link active" href="{{ route('accounting.budgets.expenses', $budget->id) }}">Expenses</a>
        </li>
        <li class="nav-item">
            <span class="nav-link disabled">Summary</span>
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

    {{-- Charts Row --}}
    <div class="row mb-4">
        {{-- Expense by Type Chart --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Expense Breakdown by Type</h5>
                </div>
                <div class="card-body">
                    <div id="typeChart" style="min-height: 300px;"></div>
                </div>
            </div>
        </div>

        {{-- Year-over-Year Comparison Chart --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Year-over-Year Comparison</h5>
                </div>
                <div class="card-body">
                    <div id="comparisonChart" style="min-height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Global Actions --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="ti ti-settings me-2"></i>Global Actions</h5>
            @if($expenseEntries->isEmpty())
                <button type="button" class="btn btn-primary" id="initializeExpensesBtn">
                    <i class="ti ti-database-import me-1"></i> Initialize from Categories
                </button>
            @endif
        </div>
        @if(!$expenseEntries->isEmpty())
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Apply Global OpEx Increase</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="globalOpexIncrease" value="0" step="0.1" min="-100" max="100">
                            <span class="input-group-text">%</span>
                            <button type="button" class="btn btn-outline-primary" id="applyOpexIncreaseBtn">Apply</button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Apply Global Tax Increase</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="globalTaxIncrease" value="0" step="0.1" min="-100" max="100">
                            <span class="input-group-text">%</span>
                            <button type="button" class="btn btn-outline-primary" id="applyTaxIncreaseBtn">Apply</button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Populate from Actuals</label>
                        <div>
                            <button type="button" class="btn btn-outline-secondary" id="populateFromActualsBtn">
                                <i class="ti ti-refresh me-1"></i> Load Last Year Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if($expenseEntries->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="ti ti-receipt display-4 text-muted mb-3 d-block"></i>
                <h5>No Expense Entries</h5>
                <p class="text-muted">Click "Initialize from Categories" to load expense categories into this budget.</p>
            </div>
        </div>
    @else
        <form action="{{ route('accounting.budgets.expenses.update', $budget->id) }}" method="POST" id="expensesForm">
            @csrf

            {{-- OpEx Section --}}
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="ti ti-building-store me-2"></i>Operating Expenses (OpEx)</h5>
                </div>
                <div class="card-body">
                    @if($opexEntries->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th class="text-end">Last Year Total</th>
                                        <th class="text-end">Last Year Avg/Month</th>
                                        <th style="width: 120px;">Increase %</th>
                                        <th style="width: 150px;">Override Amount</th>
                                        <th class="text-end">Proposed Total</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($opexEntries as $index => $entry)
                                        <tr class="{{ $entry->is_override ? 'table-warning' : '' }}">
                                            <td>
                                                <input type="hidden" name="expenses[opex][{{ $index }}][id]" value="{{ $entry->id }}">
                                                <strong>{{ $entry->category?->name ?? 'Unknown' }}</strong>
                                                @if($entry->category?->parent)
                                                    <br><small class="text-muted">{{ $entry->category->parent->name }}</small>
                                                @endif
                                            </td>
                                            <td class="text-end">{{ number_format($entry->last_year_total, 2) }}</td>
                                            <td class="text-end">{{ number_format($entry->last_year_avg_monthly, 2) }}</td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number"
                                                           class="form-control increase-pct"
                                                           name="expenses[opex][{{ $index }}][increase_percentage]"
                                                           value="{{ $entry->increase_percentage ?? 0 }}"
                                                           step="0.1"
                                                           {{ $entry->is_override ? 'disabled' : '' }}
                                                           data-last-year="{{ $entry->last_year_total }}">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">EGP</span>
                                                    <input type="number"
                                                           class="form-control override-amount"
                                                           name="expenses[opex][{{ $index }}][override_amount]"
                                                           value="{{ $entry->is_override ? $entry->proposed_amount : '' }}"
                                                           step="0.01"
                                                           min="0"
                                                           placeholder="Override">
                                                </div>
                                            </td>
                                            <td class="text-end proposed-total">
                                                <strong>{{ number_format($entry->proposed_total ?? $entry->last_year_total, 2) }}</strong>
                                            </td>
                                            <td>
                                                @if($entry->is_override)
                                                    <span class="badge bg-warning">Override</span>
                                                @else
                                                    <span class="badge bg-secondary">Calculated</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="5"><strong>OpEx Total</strong></td>
                                        <td class="text-end"><strong>{{ number_format($summary['opex_total'], 2) }}</strong></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No OpEx entries found.</p>
                    @endif
                </div>
            </div>

            {{-- Tax Section --}}
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="ti ti-receipt-tax me-2"></i>Taxes</h5>
                </div>
                <div class="card-body">
                    @if($taxEntries->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th class="text-end">Last Year Total</th>
                                        <th class="text-end">Last Year Avg/Month</th>
                                        <th style="width: 120px;">Increase %</th>
                                        <th style="width: 150px;">Override Amount</th>
                                        <th class="text-end">Proposed Total</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($taxEntries as $index => $entry)
                                        <tr class="{{ $entry->is_override ? 'table-warning' : '' }}">
                                            <td>
                                                <input type="hidden" name="expenses[tax][{{ $index }}][id]" value="{{ $entry->id }}">
                                                <strong>{{ $entry->category?->name ?? 'Unknown' }}</strong>
                                            </td>
                                            <td class="text-end">{{ number_format($entry->last_year_total, 2) }}</td>
                                            <td class="text-end">{{ number_format($entry->last_year_avg_monthly, 2) }}</td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number"
                                                           class="form-control increase-pct"
                                                           name="expenses[tax][{{ $index }}][increase_percentage]"
                                                           value="{{ $entry->increase_percentage ?? 0 }}"
                                                           step="0.1"
                                                           {{ $entry->is_override ? 'disabled' : '' }}
                                                           data-last-year="{{ $entry->last_year_total }}">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">EGP</span>
                                                    <input type="number"
                                                           class="form-control override-amount"
                                                           name="expenses[tax][{{ $index }}][override_amount]"
                                                           value="{{ $entry->is_override ? $entry->proposed_amount : '' }}"
                                                           step="0.01"
                                                           min="0"
                                                           placeholder="Override">
                                                </div>
                                            </td>
                                            <td class="text-end proposed-total">
                                                <strong>{{ number_format($entry->proposed_total ?? $entry->last_year_total, 2) }}</strong>
                                            </td>
                                            <td>
                                                @if($entry->is_override)
                                                    <span class="badge bg-warning">Override</span>
                                                @else
                                                    <span class="badge bg-secondary">Calculated</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="5"><strong>Tax Total</strong></td>
                                        <td class="text-end"><strong>{{ number_format($summary['tax_total'], 2) }}</strong></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No Tax entries found.</p>
                    @endif
                </div>
            </div>

            {{-- CapEx Section --}}
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="ti ti-building me-2"></i>Capital Expenditure (CapEx)</h5>
                </div>
                <div class="card-body">
                    @if($capexEntries->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th class="text-end">Last Year Total</th>
                                        <th class="text-end">Last Year Avg/Month</th>
                                        <th style="width: 120px;">Increase %</th>
                                        <th style="width: 150px;">Override Amount</th>
                                        <th class="text-end">Proposed Total</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($capexEntries as $index => $entry)
                                        <tr class="{{ $entry->is_override ? 'table-warning' : '' }}">
                                            <td>
                                                <input type="hidden" name="expenses[capex][{{ $index }}][id]" value="{{ $entry->id }}">
                                                <strong>{{ $entry->category?->name ?? 'Unknown' }}</strong>
                                                @if($entry->category?->parent)
                                                    <br><small class="text-muted">{{ $entry->category->parent->name }}</small>
                                                @endif
                                            </td>
                                            <td class="text-end">{{ number_format($entry->last_year_total, 2) }}</td>
                                            <td class="text-end">{{ number_format($entry->last_year_avg_monthly, 2) }}</td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number"
                                                           class="form-control increase-pct"
                                                           name="expenses[capex][{{ $index }}][increase_percentage]"
                                                           value="{{ $entry->increase_percentage ?? 0 }}"
                                                           step="0.1"
                                                           {{ $entry->is_override ? 'disabled' : '' }}
                                                           data-last-year="{{ $entry->last_year_total }}">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">EGP</span>
                                                    <input type="number"
                                                           class="form-control override-amount"
                                                           name="expenses[capex][{{ $index }}][override_amount]"
                                                           value="{{ $entry->is_override ? $entry->proposed_amount : '' }}"
                                                           step="0.01"
                                                           min="0"
                                                           placeholder="Override">
                                                </div>
                                            </td>
                                            <td class="text-end proposed-total">
                                                <strong>{{ number_format($entry->proposed_total ?? $entry->last_year_total, 2) }}</strong>
                                            </td>
                                            <td>
                                                @if($entry->is_override)
                                                    <span class="badge bg-warning">Override</span>
                                                @else
                                                    <span class="badge bg-secondary">Calculated</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="5"><strong>CapEx Total</strong></td>
                                        <td class="text-end"><strong>{{ number_format($summary['capex_total'], 2) }}</strong></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No CapEx entries found.</p>
                    @endif
                </div>
            </div>

            {{-- Navigation Buttons --}}
            <div class="d-flex justify-content-between mt-3 mb-4">
                <a href="{{ route('accounting.budgets.personnel', $budget->id) }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Back to Personnel
                </a>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i> Save Expenses Budget
                    </button>
                    <button type="button" class="btn btn-success" disabled>
                        Next: Summary <i class="ti ti-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>
        </form>
    @endif

    {{-- Summary Card --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="ti ti-chart-bar me-2"></i>Expenses Budget Summary</h5>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-2 col-sm-4">
                    <div class="text-center">
                        <h6 class="text-muted mb-1">OpEx Entries</h6>
                        <h3 class="mb-0">{{ $summary['opex_count'] }}</h3>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4">
                    <div class="text-center">
                        <h6 class="text-muted mb-1">OpEx Total</h6>
                        <h3 class="mb-0 text-primary">{{ number_format($summary['opex_total'], 0) }}</h3>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4">
                    <div class="text-center">
                        <h6 class="text-muted mb-1">Tax Entries</h6>
                        <h3 class="mb-0">{{ $summary['tax_count'] }}</h3>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4">
                    <div class="text-center">
                        <h6 class="text-muted mb-1">Tax Total</h6>
                        <h3 class="mb-0 text-danger">{{ number_format($summary['tax_total'], 0) }}</h3>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4">
                    <div class="text-center">
                        <h6 class="text-muted mb-1">CapEx Total</h6>
                        <h3 class="mb-0 text-success">{{ number_format($summary['capex_total'], 0) }}</h3>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4">
                    <div class="text-center">
                        <h6 class="text-muted mb-1">Grand Total</h6>
                        <h3 class="mb-0">{{ number_format($summary['grand_total'], 0) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart data from controller
    const byType = @json($chartData['byType']);
    const comparison = @json($chartData['comparison']);

    // Type Breakdown Chart (Donut)
    const typeLabels = Object.keys(byType);
    const typeData = Object.values(byType);

    if (typeData.some(v => v > 0)) {
        const typeChart = new ApexCharts(document.querySelector('#typeChart'), {
            series: typeData,
            chart: {
                type: 'donut',
                height: 300
            },
            labels: typeLabels,
            colors: ['#7367f0', '#ea5455', '#28c76f'],
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
        typeChart.render();
    } else {
        document.querySelector('#typeChart').innerHTML = '<div class="text-center text-muted py-5">No expense data yet</div>';
    }

    // Comparison Chart (Bar)
    const compChart = new ApexCharts(document.querySelector('#comparisonChart'), {
        series: [{
            name: 'Amount',
            data: [comparison.last_year, comparison.proposed]
        }],
        chart: {
            type: 'bar',
            height: 300,
            toolbar: { show: false }
        },
        plotOptions: {
            bar: { horizontal: false, columnWidth: '55%' }
        },
        dataLabels: { enabled: false },
        xaxis: { categories: ['Last Year', 'Proposed'] },
        yaxis: {
            title: { text: 'Amount (EGP)' },
            labels: {
                formatter: val => val.toLocaleString()
            }
        },
        colors: ['#7367f0'],
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: 'vertical',
                shadeIntensity: 0.3,
                opacityFrom: 0.9,
                opacityTo: 0.6,
            }
        }
    });
    compChart.render();

    // Initialize Expenses Button
    const initBtn = document.getElementById('initializeExpensesBtn');
    if (initBtn) {
        initBtn.addEventListener('click', function() {
            if (confirm('This will create expense entries from all active expense categories. Continue?')) {
                fetch('{{ route('accounting.budgets.expenses.initialize', $budget->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Failed to initialize');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                });
            }
        });
    }

    // Apply Global OpEx Increase
    const applyOpexBtn = document.getElementById('applyOpexIncreaseBtn');
    if (applyOpexBtn) {
        applyOpexBtn.addEventListener('click', function() {
            const pct = parseFloat(document.getElementById('globalOpexIncrease').value) || 0;
            if (confirm(`Apply ${pct}% increase to all OpEx entries?`)) {
                fetch('{{ route('accounting.budgets.expenses.apply-global', $budget->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ type: 'opex', percentage: pct })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Failed to apply increase');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                });
            }
        });
    }

    // Apply Global Tax Increase
    const applyTaxBtn = document.getElementById('applyTaxIncreaseBtn');
    if (applyTaxBtn) {
        applyTaxBtn.addEventListener('click', function() {
            const pct = parseFloat(document.getElementById('globalTaxIncrease').value) || 0;
            if (confirm(`Apply ${pct}% increase to all Tax entries?`)) {
                fetch('{{ route('accounting.budgets.expenses.apply-global', $budget->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ type: 'tax', percentage: pct })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Failed to apply increase');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                });
            }
        });
    }

    // Populate from Actuals Button
    const populateBtn = document.getElementById('populateFromActualsBtn');
    if (populateBtn) {
        populateBtn.addEventListener('click', function() {
            if (confirm('This will load last year expense data for all categories. Continue?')) {
                fetch('{{ route('accounting.budgets.expenses.populate', $budget->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Failed to populate data');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                });
            }
        });
    }

    // Increase % Change Handler - Calculate proposed total
    document.querySelectorAll('.increase-pct').forEach(input => {
        input.addEventListener('change', function() {
            const row = this.closest('tr');
            const lastYear = parseFloat(this.dataset.lastYear) || 0;
            const pct = parseFloat(this.value) || 0;
            const proposedTotal = lastYear * (1 + pct / 100);

            const proposedCell = row.querySelector('.proposed-total');
            proposedCell.innerHTML = `<strong>${proposedTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>`;

            // Clear override when percentage is changed
            const overrideInput = row.querySelector('.override-amount');
            if (overrideInput) {
                overrideInput.value = '';
            }
        });
    });

    // Override Amount Handler
    document.querySelectorAll('.override-amount').forEach(input => {
        input.addEventListener('change', function() {
            const row = this.closest('tr');
            const overrideVal = parseFloat(this.value);

            if (!isNaN(overrideVal) && overrideVal > 0) {
                const proposedCell = row.querySelector('.proposed-total');
                proposedCell.innerHTML = `<strong>${overrideVal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>`;

                // Disable the percentage input when override is set
                const pctInput = row.querySelector('.increase-pct');
                if (pctInput) {
                    pctInput.disabled = true;
                }
            } else {
                // Re-enable percentage input if override cleared
                const pctInput = row.querySelector('.increase-pct');
                if (pctInput) {
                    pctInput.disabled = false;
                    pctInput.dispatchEvent(new Event('change'));
                }
            }
        });
    });
});
</script>
@endsection
