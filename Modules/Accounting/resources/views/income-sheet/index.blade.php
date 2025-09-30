@extends('layouts/layoutMaster')

@section('title', 'Income Sheet')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Income Sheet - {{ date('Y') }}</h5>
                    <small class="text-muted">Monthly financial overview across all business units</small>
                </div>
                <div class="d-flex gap-2">
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
                            <th class="align-middle text-center" style="min-width: 150px;">Business Unit</th>
                            <th class="align-middle text-center" style="min-width: 120px;">Category</th>
                            @for($month = 1; $month <= 12; $month++)
                                <th class="text-center" style="min-width: 100px;">{{ DateTime::createFromFormat('!m', $month)->format('M') }}</th>
                            @endfor
                            <th class="text-center bg-primary text-white" style="min-width: 120px;"><strong>Total</strong></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($incomeSheetData as $data)
                            @php $businessUnit = $data['business_unit']; $financials = $data['financials']; @endphp

                            <!-- Balance Row -->
                            <tr class="business-unit-start balance-row">
                                <td rowspan="5" class="align-middle text-center bg-body-tertiary">
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="avatar avatar-sm mb-2">
                                            <span class="avatar-initial rounded-circle bg-label-{{ $businessUnit->type === 'head_office' ? 'info' : 'primary' }}">
                                                <i class="ti {{ $businessUnit->type === 'head_office' ? 'ti-building-skyscraper' : 'ti-building' }} ti-sm"></i>
                                            </span>
                                        </div>
                                        <div class="text-center">
                                            <strong style="font-size: 0.875rem;">{{ $businessUnit->name }}</strong>
                                            <br><small class="text-muted">{{ $businessUnit->code }}</small>
                                            @if($businessUnit->type === 'head_office')
                                                <br><span class="badge bg-info" style="font-size: 0.75rem;">HQ</span>
                                            @endif
                                            <br>
                                            <div class="d-flex flex-column gap-1 mt-2">
                                                <a href="{{ route('budgets.index', $businessUnit) }}"
                                                   class="btn btn-primary btn-sm"
                                                   style="font-size: 0.7rem; padding: 0.25rem 0.5rem;"
                                                   title="Manage Budget">
                                                    <i class="ti ti-wallet ti-xs me-1"></i>Budget
                                                </a>
                                                <a href="{{ route('accounting.income-sheet.business-unit', $businessUnit) }}"
                                                   class="btn btn-info btn-sm"
                                                   style="font-size: 0.7rem; padding: 0.25rem 0.5rem;"
                                                   title="View Detailed Income Sheet">
                                                    <i class="ti ti-chart-line ti-xs me-1"></i>Details
                                                </a>
                                            </div>
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
                            <tr class="business-unit-end expected-income-row">
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
                                        <i class="ti ti-building-store text-muted mb-3" style="font-size: 4rem;"></i>
                                        <h5>No Business Units Found</h5>
                                        <p class="text-muted">Create business units to view the income sheet</p>
                                        <a href="{{ route('administration.business-units.create') }}" class="btn btn-primary">
                                            <i class="ti ti-plus me-1"></i>Create Business Unit
                                        </a>
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
                                            <br><small class="text-muted">All BUs</small>
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
                                        <li><strong>Balance:</strong> Cumulative income received up to each month</li>
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

<!-- CSS Updated {{ time() }} - Sticky columns fix -->
<style>
@media print {
    /* Hide all layout elements except the table */
    .layout-navbar,
    .layout-menu,
    .navbar,
    .sidebar,
    .btn,
    .card-header,
    .alert,
    .breadcrumb,
    .page-header,
    .footer,
    .layout-footer,
    .customizer,
    .theme-customizer,
    .app-customizer,
    .floating-btn,
    .style-switcher,
    .settings-toggle,
    .style-switcher-toggle,
    #theme-settings-offcanvas,
    .offcanvas,
    .offcanvas-backdrop,
    #template-customizer {
        display: none !important;
        visibility: hidden !important;
    }

    /* Hide the legend card body specifically */
    .card-body {
        display: none !important;
    }

    /* Reset all layout styles */
    body {
        margin: 0 !important;
        padding: 10px !important;
        background: white !important;
        background-image: none !important;
        background-attachment: initial !important;
        background-repeat: initial !important;
        font-size: 12px !important;
    }

    /* Remove any page background gradients */
    html {
        background: white !important;
        background-image: none !important;
        background-color: white !important;
    }

    /* Override any theme backgrounds */
    *, *::before, *::after {
        background-image: none !important;
    }

    /* Ensure clean page background */
    @page {
        margin: 0.5in;
        background: white;
    }

    .card {
        box-shadow: none !important;
        border: none !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    .table-responsive {
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
        width: 100% !important;
    }

    .table-bordered {
        border: 2px solid #000 !important;
        margin: 0 !important;
        width: 100% !important;
        font-size: 10px !important;
    }

    .table-bordered th,
    .table-bordered td {
        border: 1px solid #000 !important;
        font-size: 9px !important;
        padding: 3px !important;
        background: white !important;
        line-height: 1.2 !important;
    }

    .small {
        font-size: 8px !important;
    }

    /* Override any background colors for print */
    .table-dark,
    .table-dark td,
    .table-dark th {
        background: #f0f0f0 !important;
        color: #000 !important;
        font-weight: bold !important;
    }

    /* Remove sticky positioning for print */
    .table th,
    .table td {
        position: static !important;
        left: auto !important;
    }

    /* Add a simple header for print */
    .table-responsive::before {
        content: "Income Sheet - 2025";
        display: block;
        font-weight: bold;
        font-size: 16px;
        text-align: center;
        margin-bottom: 10px;
        border-bottom: 2px solid #000;
        padding-bottom: 5px;
    }

    /* Ensure avatars and icons are visible and properly sized */
    .avatar,
    .ti {
        font-size: 10px !important;
        display: inline-block !important;
        visibility: visible !important;
    }

    /* Make sure icon fonts load in print */
    .ti::before {
        font-family: 'tabler-icons' !important;
        display: inline-block !important;
    }

    /* Keep row backgrounds visible in print */
    .balance-row {
        background-color: rgba(220, 53, 69, 0.1) !important;
    }

    .contract-row {
        background-color: rgba(13, 110, 253, 0.1) !important;
    }

    .expected-contract-row {
        background-color: rgba(253, 126, 20, 0.1) !important;
    }

    .income-row {
        background-color: rgba(25, 135, 84, 0.1) !important;
    }

    .expected-income-row {
        background-color: rgba(111, 66, 193, 0.1) !important;
    }
}

/* Compact layout for better fit */
.table-sm th,
.table-sm td {
    padding: 0.25rem;
    font-size: 0.875rem;
}

.small {
    font-size: 0.8rem;
}

/* Freeze first columns */
.table-responsive {
    overflow-x: auto;
}

/* For header row - both BU and Category columns */
.table th:nth-child(1),
.table th:nth-child(2) {
    position: sticky;
    z-index: 10;
}

.table th:nth-child(1) {
    left: 0;
}

.table th:nth-child(2) {
    left: 150px;
}

/* For BU rows (first row of each BU group) - BU column (td:nth-child(1)) and Category column (td:nth-child(2)) */
.table tbody tr.business-unit-start td:nth-child(1),
.table tbody tr.business-unit-start td:nth-child(2) {
    position: sticky;
    z-index: 10;
}

.table tbody tr.business-unit-start td:nth-child(1) {
    left: 0;
}

.table tbody tr.business-unit-start td:nth-child(2) {
    left: 150px;
}

/* For other BU rows (2nd, 3rd, 4th, 5th rows of each BU group) - only Category column (td:nth-child(1) since BU cell spans) */
.table tbody tr:not(.business-unit-start) td:nth-child(1) {
    position: sticky;
    left: 150px;
    z-index: 10;
}

/* Sticky backgrounds - theme-aware */
/* Header backgrounds - default light mode */
.table th:nth-child(1),
.table th:nth-child(2) {
    background: var(--bs-body-bg) !important;
    background-color: var(--bs-body-bg) !important;
}

/* BU rows (first row) backgrounds - default light mode */
.table tbody tr.business-unit-start td:nth-child(1),
.table tbody tr.business-unit-start td:nth-child(2) {
    background: var(--bs-body-bg) !important;
    background-color: var(--bs-body-bg) !important;
}

/* Other BU rows (2nd-5th rows) category column background - default light mode */
.table tbody tr:not(.business-unit-start) td:nth-child(1) {
    background: var(--bs-body-bg) !important;
    background-color: var(--bs-body-bg) !important;
}


/* Override row background colors for sticky columns to maintain solid backgrounds */
/* For BU first rows - category column is td:nth-child(2) */
.balance-row.business-unit-start td:nth-child(2),
.contract-row.business-unit-start td:nth-child(2),
.expected-contract-row.business-unit-start td:nth-child(2),
.income-row.business-unit-start td:nth-child(2),
.expected-income-row.business-unit-start td:nth-child(2) {
    background: var(--bs-body-bg) !important;
    background-color: var(--bs-body-bg) !important;
}

/* For BU other rows - category column is td:nth-child(1) */
.balance-row:not(.business-unit-start) td:nth-child(1),
.contract-row:not(.business-unit-start) td:nth-child(1),
.expected-contract-row:not(.business-unit-start) td:nth-child(1),
.income-row:not(.business-unit-start) td:nth-child(1),
.expected-income-row:not(.business-unit-start) td:nth-child(1) {
    background: var(--bs-body-bg) !important;
    background-color: var(--bs-body-bg) !important;
}


/* Dark mode compatibility for background classes */
[data-bs-theme="dark"] .bg-body-tertiary {
    background: var(--bs-dark-bg-subtle) !important;
}

[data-bs-theme="dark"] .bg-body-secondary {
    background: var(--bs-dark-border-subtle) !important;
}

/* Totals section sticky columns */
/* First totals row - has both TOTALS column (td:nth-child(1)) and Balance category (td:nth-child(2)) */
.table tbody tr.totals-first-row td:nth-child(1) {
    position: sticky;
    left: 0;
    z-index: 15;
    background: var(--bs-body-bg) !important;
    background-color: var(--bs-body-bg) !important;
}

.table tbody tr.totals-first-row td:nth-child(2) {
    position: sticky;
    left: 150px;
    z-index: 10;
    background: var(--bs-body-bg) !important;
    background-color: var(--bs-body-bg) !important;
}

/* Other totals rows - only have category column (td:nth-child(1)) since TOTALS spans */
.table tbody tr.totals-section:not(.totals-first-row) td:nth-child(1) {
    position: sticky;
    left: 150px;
    z-index: 10;
    background: var(--bs-body-bg) !important;
    background-color: var(--bs-body-bg) !important;
}


/* Dark mode compatibility for other backgrounds */
[data-bs-theme="dark"] .bg-primary {
    background: var(--bs-primary) !important;
}

[data-bs-theme="dark"] .card {
    background: var(--bs-dark-bg-subtle);
    border-color: var(--bs-dark-border-subtle);
}

/* Business Unit Separators */
.business-unit-start td {
    border-top: 3px solid var(--bs-primary) !important;
}

.business-unit-end td {
    border-bottom: 3px solid var(--bs-primary) !important;
}

/* First business unit shouldn't have top border */
.business-unit-start:first-of-type td {
    border-top: none !important;
}

/* Totals section should have extra spacing */
.totals-first-row td {
    border-top: 4px solid var(--bs-primary) !important;
}

/* Light background shading for financial rows */
.balance-row {
    background-color: rgba(220, 53, 69, 0.05) !important; /* Very light red */
}

.contract-row {
    background-color: rgba(13, 110, 253, 0.05) !important; /* Very light blue */
}

.expected-contract-row {
    background-color: rgba(253, 126, 20, 0.05) !important; /* Very light orange */
}

.income-row {
    background-color: rgba(25, 135, 84, 0.05) !important; /* Very light green */
}

.expected-income-row {
    background-color: rgba(111, 66, 193, 0.05) !important; /* Very light purple */
}

/* Dark mode compatibility for row shading */
[data-bs-theme="dark"] .balance-row {
    background-color: rgba(220, 53, 69, 0.1) !important;
}

[data-bs-theme="dark"] .contract-row {
    background-color: rgba(13, 110, 253, 0.1) !important;
}

[data-bs-theme="dark"] .expected-contract-row {
    background-color: rgba(253, 126, 20, 0.1) !important;
}

[data-bs-theme="dark"] .income-row {
    background-color: rgba(25, 135, 84, 0.1) !important;
}

[data-bs-theme="dark"] .expected-income-row {
    background-color: rgba(111, 66, 193, 0.1) !important;
}

/* Ensure table-dark rows maintain their dark styling while adding subtle color tinting */
.table-dark.balance-row {
    background-color: rgba(33, 37, 41, 0.9) !important;
    background-image: linear-gradient(rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.1));
}

.table-dark.contract-row {
    background-color: rgba(33, 37, 41, 0.9) !important;
    background-image: linear-gradient(rgba(13, 110, 253, 0.1), rgba(13, 110, 253, 0.1));
}

.table-dark.expected-contract-row {
    background-color: rgba(33, 37, 41, 0.9) !important;
    background-image: linear-gradient(rgba(253, 126, 20, 0.1), rgba(253, 126, 20, 0.1));
}

.table-dark.income-row {
    background-color: rgba(33, 37, 41, 0.9) !important;
    background-image: linear-gradient(rgba(25, 135, 84, 0.1), rgba(25, 135, 84, 0.1));
}

.table-dark.expected-income-row {
    background-color: rgba(33, 37, 41, 0.9) !important;
    background-image: linear-gradient(rgba(111, 66, 193, 0.1), rgba(111, 66, 193, 0.1));
}
</style>
@endsection