@extends('layouts/layoutMaster')

@section('title', 'I&E Report')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Income & Expenses Report</h5>
                    <small class="text-muted">Budget vs Actual expense analysis for {{ $currentYear }}</small>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <form method="GET" action="{{ route('accounting.reports.income-expenses') }}" class="d-flex align-items-center gap-2">
                        <label class="form-label mb-0 me-2">Year:</label>
                        <select name="year" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                            @foreach($availableYears as $y)
                                <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </form>
                    <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                        <i class="ti ti-printer me-1"></i>Print
                    </button>
                </div>
            </div>

            @if(isset($revenueSummary))
            <div class="card-body pt-0">
                <div class="alert alert-light border mb-0">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <small class="text-muted d-block">{{ $currentYear }} Revenue Target</small>
                            <strong class="text-primary">{{ number_format($revenueSummary['total_yearly_revenue'], 0) }} EGP</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Monthly Revenue</small>
                            <strong class="text-primary">{{ number_format($revenueSummary['total_monthly_revenue'], 0) }} EGP</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Monthly Net Income ({{ number_format(100 - $revenueSummary['tier1_percentage'], 1) }}%)</small>
                            <strong class="text-success">{{ number_format($revenueSummary['monthly_net_income'], 0) }} EGP</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">
                                @if($revenueSummary['is_current_year'])
                                    Months Elapsed ({{ $currentYear }})
                                @elseif($revenueSummary['is_future_year'])
                                    Full Year Projection
                                @else
                                    Full Year ({{ $currentYear }})
                                @endif
                            </small>
                            <strong>{{ $revenueSummary['months_elapsed'] }} / 12</strong>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="table-responsive">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Category</th>
                            <th class="text-center">Budget %</th>
                            <th class="text-end">Plan (Monthly)</th>
                            <th class="text-end">Plan (YTD)</th>
                            <th class="text-end">Actual YTD</th>
                            <th class="text-end">Variance</th>
                            <th class="text-end">Avg/Month</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $currentTier = null; @endphp
                        @foreach($categories as $category)
                            @if(!$category->parent_id && $category->tier != $currentTier)
                                @if($currentTier == 1)
                                    {{-- Tier 1 Subtotal Row --}}
                                    <tr class="table-primary fw-bold">
                                        <td colspan="2">Subtotal - From Revenue (R)</td>
                                        <td class="text-end">{{ number_format($tier1Total['planned_monthly'], 0) }}</td>
                                        <td class="text-end">{{ number_format($tier1Total['planned_ytd'], 0) }}</td>
                                        <td class="text-end">{{ number_format($tier1Total['ytd_total'], 0) }}</td>
                                        <td class="text-end">
                                            @php
                                                $variance = $tier1Total['planned_ytd'] > 0 ? (($tier1Total['ytd_total'] - $tier1Total['planned_ytd']) / $tier1Total['planned_ytd']) * 100 : 0;
                                            @endphp
                                            <span class="{{ $variance > 0 ? 'text-danger' : 'text-success' }}">
                                                {{ $variance >= 0 ? '+' : '' }}{{ number_format($variance, 1) }}%
                                            </span>
                                        </td>
                                        <td class="text-end">{{ number_format($revenueSummary['months_elapsed'] > 0 ? $tier1Total['ytd_total'] / $revenueSummary['months_elapsed'] : 0, 0) }}</td>
                                    </tr>
                                    <tr class="table-success">
                                        <td colspan="7" class="text-center fw-bold">
                                            Net Income After Tier 1: {{ number_format($revenueSummary['monthly_net_income'], 0) }} EGP/month
                                            ({{ number_format(100 - $revenueSummary['tier1_percentage'], 1) }}% of Revenue)
                                        </td>
                                    </tr>
                                @endif
                                @php $currentTier = $category->tier; @endphp
                                {{-- Tier Header --}}
                                <tr class="table-secondary">
                                    <td colspan="7" class="fw-bold">
                                        @if($category->tier == 1)
                                            <i class="ti ti-arrow-right me-1"></i> From Total Revenue (R)
                                        @else
                                            <i class="ti ti-arrow-right me-1"></i> From Net Income (NI)
                                        @endif
                                    </td>
                                </tr>
                            @endif
                            <tr class="{{ !$category->is_active ? 'opacity-50' : '' }}">
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($category->parent_id)
                                            <div class="me-2 text-muted ps-3">└─</div>
                                        @endif
                                        <div class="rounded me-2" style="width: 10px; height: 10px; background-color: {{ $category->color }};"></div>
                                        <div>
                                            <span @if($category->description) data-bs-toggle="tooltip" title="{{ $category->description }}" @endif>
                                                {{ $category->name }}
                                                @if($category->name_ar)
                                                    <small class="text-muted" dir="rtl">({{ $category->name_ar }})</small>
                                                @endif
                                            </span>
                                            @if(!$category->parent_id && $category->expenseType)
                                                <span class="badge ms-1" style="background-color: {{ $category->expenseType->color }}; color: white; font-size: 0.65rem;">
                                                    {{ $category->expenseType->code }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if(!$category->parent_id && $category->budget_percentage > 0)
                                        <span class="badge bg-label-info">{{ number_format($category->budget_percentage, 2) }}%</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($category->planned_monthly > 0)
                                        {{ number_format($category->planned_monthly, 0) }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($category->planned_ytd > 0)
                                        {{ number_format($category->planned_ytd, 0) }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($category->ytd_total > 0)
                                        @php
                                            $variance = $category->planned_ytd > 0 ? (($category->ytd_total - $category->planned_ytd) / $category->planned_ytd) * 100 : 0;
                                            $varianceClass = $category->ytd_total > $category->planned_ytd ? 'text-danger' : 'text-success';
                                        @endphp
                                        <span class="{{ $category->planned_ytd > 0 ? $varianceClass : '' }}">{{ number_format($category->ytd_total, 0) }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($category->planned_ytd > 0 && $category->ytd_total > 0)
                                        @php
                                            $variance = (($category->ytd_total - $category->planned_ytd) / $category->planned_ytd) * 100;
                                        @endphp
                                        <span class="{{ $variance > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ $variance >= 0 ? '+' : '' }}{{ number_format($variance, 1) }}%
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($category->ytd_average_per_month > 0)
                                        {{ number_format($category->ytd_average_per_month, 0) }}
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach

                        {{-- Tier 2 Subtotal Row --}}
                        <tr class="table-warning fw-bold">
                            <td colspan="2">Subtotal - From Net Income (NI)</td>
                            <td class="text-end">{{ number_format($tier2Total['planned_monthly'], 0) }}</td>
                            <td class="text-end">{{ number_format($tier2Total['planned_ytd'], 0) }}</td>
                            <td class="text-end">{{ number_format($tier2Total['ytd_total'], 0) }}</td>
                            <td class="text-end">
                                @php
                                    $variance = $tier2Total['planned_ytd'] > 0 ? (($tier2Total['ytd_total'] - $tier2Total['planned_ytd']) / $tier2Total['planned_ytd']) * 100 : 0;
                                @endphp
                                <span class="{{ $variance > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $variance >= 0 ? '+' : '' }}{{ number_format($variance, 1) }}%
                                </span>
                            </td>
                            <td class="text-end">{{ number_format($revenueSummary['months_elapsed'] > 0 ? $tier2Total['ytd_total'] / $revenueSummary['months_elapsed'] : 0, 0) }}</td>
                        </tr>

                        {{-- Grand Total Row --}}
                        <tr class="table-dark fw-bold">
                            <td colspan="2">GRAND TOTAL</td>
                            <td class="text-end">{{ number_format($grandTotal['planned_monthly'], 0) }}</td>
                            <td class="text-end">{{ number_format($grandTotal['planned_ytd'], 0) }}</td>
                            <td class="text-end">{{ number_format($grandTotal['ytd_total'], 0) }}</td>
                            <td class="text-end">
                                @php
                                    $variance = $grandTotal['planned_ytd'] > 0 ? (($grandTotal['ytd_total'] - $grandTotal['planned_ytd']) / $grandTotal['planned_ytd']) * 100 : 0;
                                @endphp
                                <span class="{{ $variance > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $variance >= 0 ? '+' : '' }}{{ number_format($variance, 1) }}%
                                </span>
                            </td>
                            <td class="text-end">{{ number_format($revenueSummary['months_elapsed'] > 0 ? $grandTotal['ytd_total'] / $revenueSummary['months_elapsed'] : 0, 0) }}</td>
                        </tr>

                        {{-- Remaining Balance Row --}}
                        <tr class="table-info fw-bold">
                            <td colspan="2">Remaining Balance (Revenue - Total Expenses)</td>
                            <td class="text-end">{{ number_format($revenueSummary['total_monthly_revenue'] - $grandTotal['planned_monthly'], 0) }}</td>
                            <td class="text-end">{{ number_format(($revenueSummary['total_monthly_revenue'] * $revenueSummary['months_elapsed']) - $grandTotal['planned_ytd'], 0) }}</td>
                            <td class="text-end">{{ number_format(($revenueSummary['total_monthly_revenue'] * $revenueSummary['months_elapsed']) - $grandTotal['ytd_total'], 0) }}</td>
                            <td class="text-end">-</td>
                            <td class="text-end">-</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                <small class="text-muted">
                    <i class="ti ti-info-circle me-1"></i>
                    Report generated on {{ now()->format('M d, Y H:i') }}.
                    <strong class="text-primary">R</strong> = Calculated from Total Revenue |
                    <strong class="text-warning">NI</strong> = Calculated from Net Income after Tier 1 deductions
                </small>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .card-header .d-flex.gap-2 { display: none !important; }
    .btn { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
    .table { font-size: 10px !important; }
    body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection
