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
            <a class="nav-link" href="{{ route('accounting.budgets.summary', $budget->id) }}">Summary</a>
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
                <hr class="my-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Sync Category Types</label>
                        <div>
                            <button type="button" class="btn btn-outline-info" id="syncTypesBtn">
                                <i class="ti ti-category me-1"></i> Move to Tax/CapEx Sections
                            </button>
                            <small class="text-muted d-block mt-1">Auto-detect and move categories to correct sections based on expense type</small>
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
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="ti ti-building-store me-2"></i>Operating Expenses (OpEx)</h5>
                    <span class="badge bg-light text-primary">{{ $opexEntries->count() }} categories</span>
                </div>
                <div class="card-body">
                    @if($opexHierarchy->count() > 0)
                        <div class="accordion" id="opexAccordion">
                            @php $opexIndex = 0; @endphp
                            @foreach($opexHierarchy as $groupIndex => $group)
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#opexGroup{{ $groupIndex }}"
                                                aria-expanded="true">
                                            <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                                <span>
                                                    <i class="ti ti-folder me-2"></i>
                                                    <strong>{{ $group['parent']?->name ?? 'Uncategorized' }}</strong>
                                                    @if(!$group['is_parent_only'])
                                                        <span class="badge bg-secondary ms-2">{{ $group['entries']->count() }} items</span>
                                                    @endif
                                                </span>
                                                <span class="text-primary fw-bold">
                                                    EGP {{ number_format($group['subtotal'], 2) }}
                                                </span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="opexGroup{{ $groupIndex }}" class="accordion-collapse collapse show">
                                        <div class="accordion-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-hover mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Category</th>
                                                            <th class="text-end">Last Year Total</th>
                                                            <th class="text-end">Last Year Avg/Month</th>
                                                            <th style="width: 160px;">Increase %</th>
                                                            <th style="width: 150px;">Override Amount</th>
                                                            <th class="text-end">Proposed Total</th>
                                                            <th>Type</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($group['entries'] as $entry)
                                                            <tr class="{{ $entry->is_override ? 'table-warning' : '' }}">
                                                                <td>
                                                                    <input type="hidden" name="expenses[opex][{{ $opexIndex }}][id]" value="{{ $entry->id }}">
                                                                    @if($entry->category?->parent_id)
                                                                        <span class="text-muted me-2">└─</span>
                                                                    @endif
                                                                    <strong>{{ $entry->category?->name ?? 'Unknown' }}</strong>
                                                                </td>
                                                                <td class="text-end">{{ number_format($entry->last_year_total, 2) }}</td>
                                                                <td class="text-end">{{ number_format($entry->last_year_avg_monthly, 2) }}</td>
                                                                <td>
                                                                    <div class="input-group input-group-sm">
                                                                        <input type="number"
                                                                               class="form-control increase-pct"
                                                                               name="expenses[opex][{{ $opexIndex }}][increase_percentage]"
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
                                                                               name="expenses[opex][{{ $opexIndex }}][override_amount]"
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
                                                            @php $opexIndex++; @endphp
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="card-footer bg-light mt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>OpEx Total</strong>
                                <strong class="text-primary fs-5">EGP {{ number_format($summary['opex_total'], 2) }}</strong>
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-0">No OpEx entries found.</p>
                    @endif
                </div>
            </div>

            {{-- Tax Section --}}
            <div class="card mb-4">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="ti ti-receipt-tax me-2"></i>Taxes</h5>
                    <span class="badge bg-light text-danger">{{ $taxEntries->count() }} categories</span>
                </div>
                <div class="card-body">
                    @if($taxHierarchy->count() > 0)
                        <div class="accordion" id="taxAccordion">
                            @php $taxIndex = 0; @endphp
                            @foreach($taxHierarchy as $groupIndex => $group)
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#taxGroup{{ $groupIndex }}"
                                                aria-expanded="true">
                                            <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                                <span>
                                                    <i class="ti ti-folder me-2"></i>
                                                    <strong>{{ $group['parent']?->name ?? 'Uncategorized' }}</strong>
                                                    @if(!$group['is_parent_only'])
                                                        <span class="badge bg-secondary ms-2">{{ $group['entries']->count() }} items</span>
                                                    @endif
                                                </span>
                                                <span class="text-danger fw-bold">
                                                    EGP {{ number_format($group['subtotal'], 2) }}
                                                </span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="taxGroup{{ $groupIndex }}" class="accordion-collapse collapse show">
                                        <div class="accordion-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-hover mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Category</th>
                                                            <th class="text-end">Last Year Total</th>
                                                            <th class="text-end">Last Year Avg/Month</th>
                                                            <th style="width: 160px;">Increase %</th>
                                                            <th style="width: 150px;">Override Amount</th>
                                                            <th class="text-end">Proposed Total</th>
                                                            <th>Type</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($group['entries'] as $entry)
                                                            <tr class="{{ $entry->is_override ? 'table-warning' : '' }}">
                                                                <td>
                                                                    <input type="hidden" name="expenses[tax][{{ $taxIndex }}][id]" value="{{ $entry->id }}">
                                                                    @if($entry->category?->parent_id)
                                                                        <span class="text-muted me-2">└─</span>
                                                                    @endif
                                                                    <strong>{{ $entry->category?->name ?? 'Unknown' }}</strong>
                                                                </td>
                                                                <td class="text-end">{{ number_format($entry->last_year_total, 2) }}</td>
                                                                <td class="text-end">{{ number_format($entry->last_year_avg_monthly, 2) }}</td>
                                                                <td>
                                                                    <div class="input-group input-group-sm">
                                                                        <input type="number"
                                                                               class="form-control increase-pct"
                                                                               name="expenses[tax][{{ $taxIndex }}][increase_percentage]"
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
                                                                               name="expenses[tax][{{ $taxIndex }}][override_amount]"
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
                                                            @php $taxIndex++; @endphp
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="card-footer bg-light mt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>Tax Total</strong>
                                <strong class="text-danger fs-5">EGP {{ number_format($summary['tax_total'], 2) }}</strong>
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-0">No Tax entries found.</p>
                    @endif
                </div>
            </div>

            {{-- CapEx Section --}}
            <div class="card mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="ti ti-building me-2"></i>Capital Expenditure (CapEx)</h5>
                    <span class="badge bg-light text-success">{{ $capexEntries->count() }} categories</span>
                </div>
                <div class="card-body">
                    @if($capexHierarchy->count() > 0)
                        <div class="accordion" id="capexAccordion">
                            @php $capexIndex = 0; @endphp
                            @foreach($capexHierarchy as $groupIndex => $group)
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#capexGroup{{ $groupIndex }}"
                                                aria-expanded="true">
                                            <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                                <span>
                                                    <i class="ti ti-folder me-2"></i>
                                                    <strong>{{ $group['parent']?->name ?? 'Uncategorized' }}</strong>
                                                    @if(!$group['is_parent_only'])
                                                        <span class="badge bg-secondary ms-2">{{ $group['entries']->count() }} items</span>
                                                    @endif
                                                </span>
                                                <span class="text-success fw-bold">
                                                    EGP {{ number_format($group['subtotal'], 2) }}
                                                </span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="capexGroup{{ $groupIndex }}" class="accordion-collapse collapse show">
                                        <div class="accordion-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-hover mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Category</th>
                                                            <th class="text-end">Last Year Total</th>
                                                            <th class="text-end">Last Year Avg/Month</th>
                                                            <th style="width: 160px;">Increase %</th>
                                                            <th style="width: 150px;">Override Amount</th>
                                                            <th class="text-end">Proposed Total</th>
                                                            <th>Type</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($group['entries'] as $entry)
                                                            <tr class="{{ $entry->is_override ? 'table-warning' : '' }}">
                                                                <td>
                                                                    <input type="hidden" name="expenses[capex][{{ $capexIndex }}][id]" value="{{ $entry->id }}">
                                                                    @if($entry->category?->parent_id)
                                                                        <span class="text-muted me-2">└─</span>
                                                                    @endif
                                                                    <strong>{{ $entry->category?->name ?? 'Unknown' }}</strong>
                                                                </td>
                                                                <td class="text-end">{{ number_format($entry->last_year_total, 2) }}</td>
                                                                <td class="text-end">{{ number_format($entry->last_year_avg_monthly, 2) }}</td>
                                                                <td>
                                                                    <div class="input-group input-group-sm">
                                                                        <input type="number"
                                                                               class="form-control increase-pct"
                                                                               name="expenses[capex][{{ $capexIndex }}][increase_percentage]"
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
                                                                               name="expenses[capex][{{ $capexIndex }}][override_amount]"
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
                                                            @php $capexIndex++; @endphp
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="card-footer bg-light mt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>CapEx Total</strong>
                                <strong class="text-success fs-5">EGP {{ number_format($summary['capex_total'], 2) }}</strong>
                            </div>
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
                    <a href="{{ route('accounting.budgets.summary', $budget->id) }}" class="btn btn-success">
                        Next: Summary <i class="ti ti-arrow-right ms-1"></i>
                    </a>
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

    // Sync Types Button
    const syncTypesBtn = document.getElementById('syncTypesBtn');
    if (syncTypesBtn) {
        syncTypesBtn.addEventListener('click', function() {
            if (confirm('This will move Tax and CapEx categories to their correct sections based on expense type. Continue?')) {
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Syncing...';

                fetch('{{ route('accounting.budgets.expenses.sync-types', $budget->id) }}', {
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
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert(data.message || 'Failed to sync types');
                        this.disabled = false;
                        this.innerHTML = '<i class="ti ti-category me-1"></i> Move to Tax/CapEx Sections';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                    this.disabled = false;
                    this.innerHTML = '<i class="ti ti-category me-1"></i> Move to Tax/CapEx Sections';
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
