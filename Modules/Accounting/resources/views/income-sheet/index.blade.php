@extends('layouts/layoutMaster')

@section('title', 'Income Sheet')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Income Sheet - {{ $selectedYear }}</h5>
                    <small class="text-muted">Monthly financial overview across all products</small>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <div class="d-flex align-items-center me-2">
                        <label class="form-label mb-0 me-2 text-nowrap">Fiscal Year:</label>
                        <select class="form-select form-select-sm" id="year-selector" style="width: auto;">
                            @foreach($availableYears as $year)
                                <option value="{{ $year }}" {{ $year == $selectedYear ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn btn-outline-success" onclick="window.print()">
                        <i class="ti ti-printer me-1"></i>Print
                    </button>
                    <a href="{{ route('accounting.income-sheet.export') }}" class="btn btn-outline-info">
                        <i class="ti ti-download me-1"></i>Export
                    </a>
                    <a href="{{ route('accounting.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th class="align-middle text-center" style="min-width: 150px;">Product</th>
                            <th class="align-middle text-center" style="min-width: 120px;">Category</th>
                            @for($month = 1; $month <= 12; $month++)
                                <th class="text-center" style="min-width: 100px;">{{ DateTime::createFromFormat('!m', $month)->format('M') }}</th>
                            @endfor
                            <th class="text-center bg-primary text-white" style="min-width: 120px;"><strong>Total</strong></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($incomeSheetData as $data)
                            @php $product = $data['product']; $financials = $data['financials']; @endphp

                            <!-- Balance Row -->
                            <tr class="product-start balance-row">
                                <td rowspan="5" class="align-middle text-center bg-body-tertiary">
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="avatar avatar-sm mb-2">
                                            <span class="avatar-initial rounded-circle bg-label-primary">
                                                <i class="ti ti-package ti-sm"></i>
                                            </span>
                                        </div>
                                        <div class="text-center">
                                            <strong style="font-size: 0.875rem;">{{ $product->name }}</strong>
                                            @if($product->code)
                                                <br><small class="text-muted">{{ $product->code }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-medium">
                                    <div class="d-flex align-items-center">
                                        <i class="ti ti-wallet text-danger me-2"></i>
                                        <span style="font-size: 0.875rem;">Balance</span>
                                    </div>
                                </td>
                                @for($month = 1; $month <= 12; $month++)
                                    <td class="text-end">
                                        <span class="small text-danger">
                                            {{ number_format($financials['months'][$month]['balance'], 0) }}
                                        </span>
                                    </td>
                                @endfor
                                <td class="text-end bg-body-secondary">
                                    <strong class="text-danger">
                                        {{ number_format($financials['totals']['balance'], 0) }}
                                    </strong>
                                </td>
                            </tr>

                            <!-- Contracts Row -->
                            <tr class="contract-row">
                                <td class="fw-medium">
                                    <div class="d-flex align-items-center">
                                        <i class="ti ti-file-check text-primary me-2"></i>
                                        <span style="font-size: 0.875rem;">Contracts</span>
                                    </div>
                                </td>
                                @for($month = 1; $month <= 12; $month++)
                                    <td class="text-end">
                                        <span class="small text-primary">
                                            {{ number_format($financials['months'][$month]['contracts'], 0) }}
                                        </span>
                                    </td>
                                @endfor
                                <td class="text-end bg-body-secondary">
                                    <strong class="text-primary">
                                        {{ number_format($financials['totals']['contracts'], 0) }}
                                    </strong>
                                </td>
                            </tr>

                            <!-- Expected Contracts Row -->
                            <tr class="expected-contract-row">
                                <td class="fw-medium">
                                    <div class="d-flex align-items-center">
                                        <i class="ti ti-file-clock text-warning me-2"></i>
                                        <span style="font-size: 0.875rem;">Ex. Contracts</span>
                                    </div>
                                </td>
                                @for($month = 1; $month <= 12; $month++)
                                    <td class="text-end">
                                        <span class="small text-warning">
                                            {{ number_format($financials['months'][$month]['expected_contracts'], 0) }}
                                        </span>
                                    </td>
                                @endfor
                                <td class="text-end bg-body-secondary">
                                    <strong class="text-warning">
                                        {{ number_format($financials['totals']['expected_contracts'], 0) }}
                                    </strong>
                                </td>
                            </tr>

                            <!-- Income Row -->
                            <tr class="income-row">
                                <td class="fw-medium">
                                    <div class="d-flex align-items-center">
                                        <i class="ti ti-currency-dollar text-success me-2"></i>
                                        <span style="font-size: 0.875rem;">Income</span>
                                    </div>
                                </td>
                                @for($month = 1; $month <= 12; $month++)
                                    <td class="text-end">
                                        <span class="small text-success">
                                            {{ number_format($financials['months'][$month]['income'], 0) }}
                                        </span>
                                    </td>
                                @endfor
                                <td class="text-end bg-body-secondary">
                                    <strong class="text-success">
                                        {{ number_format($financials['totals']['income'], 0) }}
                                    </strong>
                                </td>
                            </tr>

                            <!-- Expected Income Row -->
                            <tr class="product-end expected-income-row">
                                <td class="fw-medium">
                                    <div class="d-flex align-items-center">
                                        <i class="ti ti-hourglass me-2" style="color: #6f42c1;"></i>
                                        <span style="font-size: 0.875rem;">Ex. Income</span>
                                    </div>
                                </td>
                                @for($month = 1; $month <= 12; $month++)
                                    <td class="text-end">
                                        <span class="small" style="color: #6f42c1;">
                                            {{ number_format($financials['months'][$month]['expected_income'], 0) }}
                                        </span>
                                    </td>
                                @endfor
                                <td class="text-end bg-body-secondary">
                                    <strong style="color: #6f42c1;">
                                        {{ number_format($financials['totals']['expected_income'], 0) }}
                                    </strong>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="ti ti-package text-muted mb-3" style="font-size: 4rem;"></i>
                                        <h5>No Products Found</h5>
                                        <p class="text-muted">Create products and contracts to view the income sheet</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse

                        @if(count($incomeSheetData) > 0)
                            <!-- Totals Section -->
                            <tr class="balance-row totals-section totals-first-row">
                                <td rowspan="5" class="align-middle text-center bg-body-tertiary">
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="avatar avatar-md mb-2">
                                            <span class="avatar-initial rounded-circle bg-primary">
                                                <i class="ti ti-sum ti-md text-white"></i>
                                            </span>
                                        </div>
                                        <div class="text-center">
                                            <strong>TOTALS</strong>
                                            <br><small class="text-muted">All Products</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-medium">
                                    <div class="d-flex align-items-center">
                                        <i class="ti ti-wallet text-danger me-2"></i>
                                        <span style="font-size: 0.875rem;">Balance</span>
                                    </div>
                                </td>
                                @for($month = 1; $month <= 12; $month++)
                                    <td class="text-end">
                                        <span class="small text-danger">
                                            {{ number_format($grandTotals['months'][$month]['balance'], 0) }}
                                        </span>
                                    </td>
                                @endfor
                                <td class="text-end bg-body-secondary">
                                    <strong class="text-danger">
                                        {{ number_format($grandTotals['totals']['balance'], 0) }}
                                    </strong>
                                </td>
                            </tr>

                            <tr class="contract-row totals-section">
                                <td class="fw-medium">
                                    <div class="d-flex align-items-center">
                                        <i class="ti ti-file-check text-primary me-2"></i>
                                        <span style="font-size: 0.875rem;">Contracts</span>
                                    </div>
                                </td>
                                @for($month = 1; $month <= 12; $month++)
                                    <td class="text-end">
                                        <span class="small text-primary">
                                            {{ number_format($grandTotals['months'][$month]['contracts'], 0) }}
                                        </span>
                                    </td>
                                @endfor
                                <td class="text-end bg-body-secondary">
                                    <strong class="text-primary">
                                        {{ number_format($grandTotals['totals']['contracts'], 0) }}
                                    </strong>
                                </td>
                            </tr>

                            <tr class="expected-contract-row totals-section">
                                <td class="fw-medium">
                                    <div class="d-flex align-items-center">
                                        <i class="ti ti-file-clock text-warning me-2"></i>
                                        <span style="font-size: 0.875rem;">Ex. Contracts</span>
                                    </div>
                                </td>
                                @for($month = 1; $month <= 12; $month++)
                                    <td class="text-end">
                                        <span class="small text-warning">
                                            {{ number_format($grandTotals['months'][$month]['expected_contracts'], 0) }}
                                        </span>
                                    </td>
                                @endfor
                                <td class="text-end bg-body-secondary">
                                    <strong class="text-warning">
                                        {{ number_format($grandTotals['totals']['expected_contracts'], 0) }}
                                    </strong>
                                </td>
                            </tr>

                            <tr class="income-row totals-section">
                                <td class="fw-medium">
                                    <div class="d-flex align-items-center">
                                        <i class="ti ti-currency-dollar text-success me-2"></i>
                                        <span style="font-size: 0.875rem;">Income</span>
                                    </div>
                                </td>
                                @for($month = 1; $month <= 12; $month++)
                                    <td class="text-end">
                                        <span class="small text-success">
                                            {{ number_format($grandTotals['months'][$month]['income'], 0) }}
                                        </span>
                                    </td>
                                @endfor
                                <td class="text-end bg-body-secondary">
                                    <strong class="text-success">
                                        {{ number_format($grandTotals['totals']['income'], 0) }}
                                    </strong>
                                </td>
                            </tr>

                            <tr class="expected-income-row totals-section">
                                <td class="fw-medium">
                                    <div class="d-flex align-items-center">
                                        <i class="ti ti-hourglass me-2" style="color: #6f42c1;"></i>
                                        <span style="font-size: 0.875rem;">Ex. Income</span>
                                    </div>
                                </td>
                                @for($month = 1; $month <= 12; $month++)
                                    <td class="text-end">
                                        <span class="small" style="color: #6f42c1;">
                                            {{ number_format($grandTotals['months'][$month]['expected_income'], 0) }}
                                        </span>
                                    </td>
                                @endfor
                                <td class="text-end bg-body-secondary">
                                    <strong style="color: #6f42c1;">
                                        {{ number_format($grandTotals['totals']['expected_income'], 0) }}
                                    </strong>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Legend -->
            @if(count($incomeSheetData) > 0)
            <div class="card-body">
                <div class="alert alert-info">
                    <div class="d-flex">
                        <i class="ti ti-info-circle me-2 mt-1"></i>
                        <div>
                            <h6 class="mb-1">Income Sheet Legend</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="mb-0 small">
                                        <li><strong>Balance:</strong> Outstanding balance (contracts - paid income) up to each month</li>
                                        <li><strong>Contracts:</strong> Approved/active contracts created in each month</li>
                                        <li><strong>Ex. Contracts:</strong> Draft contracts created in each month</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="mb-0 small">
                                        <li><strong>Income:</strong> Contract payments received in each month</li>
                                        <li><strong>Ex. Income:</strong> Pending/overdue payments due in each month</li>
                                        <li><strong>Total:</strong> Yearly total for each category</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
@media print {
    .layout-navbar, .layout-menu, .navbar, .sidebar, .btn, .card-header, .alert, .breadcrumb,
    .page-header, .footer, .layout-footer, .customizer, .theme-customizer, .app-customizer,
    .floating-btn, .style-switcher, .settings-toggle, .style-switcher-toggle,
    #theme-settings-offcanvas, .offcanvas, .offcanvas-backdrop, #template-customizer {
        display: none !important;
        visibility: hidden !important;
    }
    .card-body { display: none !important; }
    body { margin: 0 !important; padding: 10px !important; background: white !important; font-size: 12px !important; }
    html { background: white !important; }
    .card { box-shadow: none !important; border: none !important; margin: 0 !important; padding: 0 !important; width: 100% !important; }
    .table-responsive { margin: 0 !important; padding: 0 !important; overflow: visible !important; width: 100% !important; }
    .table-bordered { border: 2px solid #000 !important; margin: 0 !important; width: 100% !important; font-size: 10px !important; }
    .table-bordered th, .table-bordered td { border: 1px solid #000 !important; font-size: 9px !important; padding: 3px !important; background: white !important; line-height: 1.2 !important; }
    .small { font-size: 8px !important; }
    .table th, .table td { position: static !important; left: auto !important; }
    .table-responsive::before { content: "Income Sheet - {{ date('Y') }}"; display: block; font-weight: bold; font-size: 16px; text-align: center; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 5px; }
}

.table-sm th, .table-sm td { padding: 0.25rem; font-size: 0.875rem; }
.small { font-size: 0.8rem; }
.table-responsive { overflow-x: auto; }

.table th:nth-child(1), .table th:nth-child(2) { position: sticky; z-index: 10; }
.table th:nth-child(1) { left: 0; }
.table th:nth-child(2) { left: 150px; }

.table tbody tr.product-start td:nth-child(1), .table tbody tr.product-start td:nth-child(2) { position: sticky; z-index: 10; }
.table tbody tr.product-start td:nth-child(1) { left: 0; }
.table tbody tr.product-start td:nth-child(2) { left: 150px; }

.table tbody tr:not(.product-start) td:nth-child(1) { position: sticky; left: 150px; z-index: 10; }

.table th:nth-child(1), .table th:nth-child(2),
.table tbody tr.product-start td:nth-child(1), .table tbody tr.product-start td:nth-child(2),
.table tbody tr:not(.product-start) td:nth-child(1) {
    background: var(--bs-body-bg) !important;
}

.table tbody tr.totals-first-row td:nth-child(1) { position: sticky; left: 0; z-index: 15; background: var(--bs-body-bg) !important; }
.table tbody tr.totals-first-row td:nth-child(2) { position: sticky; left: 150px; z-index: 10; background: var(--bs-body-bg) !important; }
.table tbody tr.totals-section:not(.totals-first-row) td:nth-child(1) { position: sticky; left: 150px; z-index: 10; background: var(--bs-body-bg) !important; }

.product-start td { border-top: 3px solid var(--bs-primary) !important; }
.product-end td { border-bottom: 3px solid var(--bs-primary) !important; }
.product-start:first-of-type td { border-top: none !important; }
.totals-first-row td { border-top: 4px solid var(--bs-primary) !important; }

.balance-row { background-color: rgba(220, 53, 69, 0.05) !important; }
.contract-row { background-color: rgba(13, 110, 253, 0.05) !important; }
.expected-contract-row { background-color: rgba(253, 126, 20, 0.05) !important; }
.income-row { background-color: rgba(25, 135, 84, 0.05) !important; }
.expected-income-row { background-color: rgba(111, 66, 193, 0.05) !important; }

[data-bs-theme="dark"] .balance-row { background-color: rgba(220, 53, 69, 0.1) !important; }
[data-bs-theme="dark"] .contract-row { background-color: rgba(13, 110, 253, 0.1) !important; }
[data-bs-theme="dark"] .expected-contract-row { background-color: rgba(253, 126, 20, 0.1) !important; }
[data-bs-theme="dark"] .income-row { background-color: rgba(25, 135, 84, 0.1) !important; }
[data-bs-theme="dark"] .expected-income-row { background-color: rgba(111, 66, 193, 0.1) !important; }
</style>
@endsection

@section('page-script')
<script>
document.getElementById('year-selector').addEventListener('change', function() {
    const year = this.value;
    window.location.href = '{{ route("accounting.income-sheet.index") }}?year=' + year;
});
</script>
@endsection
