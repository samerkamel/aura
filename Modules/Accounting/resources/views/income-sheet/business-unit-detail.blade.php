@extends('layouts/layoutMaster')

@section('title', 'Income Sheet - ' . $businessUnit->name)

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Income Sheet - {{ $businessUnit->name }} - {{ date('Y') }}</h5>
                    <small class="text-muted">Monthly financial overview by products for {{ $businessUnit->name }}</small>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-success" onclick="window.print()">
                        <i class="ti ti-printer me-1"></i>Print
                    </button>
                    <a href="{{ route('accounting.income-sheet.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Back to Income Sheet
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
                                            <span class="avatar-initial rounded-circle bg-label-info">
                                                <i class="ti ti-package ti-sm"></i>
                                            </span>
                                        </div>
                                        <div class="text-center">
                                            <strong class="d-block">{{ $product->name }}</strong>
                                            <small class="text-muted">{{ $product->description ?? 'Product' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center align-middle bg-label-danger">
                                    <strong>Balance</strong>
                                </td>
                                @for($month = 1; $month <= 12; $month++)
                                    <td class="text-center balance-cell">
                                        <strong class="text-danger">
                                            {{ number_format($financials['months'][$month]['balance'], 0) }}
                                        </strong>
                                    </td>
                                @endfor
                                <td class="text-center bg-danger text-white">
                                    <strong>{{ number_format($financials['totals']['balance'], 0) }}</strong>
                                </td>
                            </tr>

                            <!-- Contracts Row -->
                            <tr class="contracts-row">
                                <td class="text-center align-middle bg-label-primary">
                                    <strong>Contracts</strong>
                                </td>
                                @for($month = 1; $month <= 12; $month++)
                                    <td class="text-center contracts-cell">
                                        <strong class="text-primary">
                                            {{ number_format($financials['months'][$month]['contracts'], 0) }}
                                        </strong>
                                    </td>
                                @endfor
                                <td class="text-center bg-primary text-white">
                                    <strong>{{ number_format($financials['totals']['contracts'], 0) }}</strong>
                                </td>
                            </tr>

                            <!-- Expected Contracts Row -->
                            <tr class="expected-contracts-row">
                                <td class="text-center align-middle bg-label-warning">
                                    <strong>Expected Contracts</strong>
                                </td>
                                @for($month = 1; $month <= 12; $month++)
                                    <td class="text-center expected-contracts-cell">
                                        <strong class="text-warning">
                                            {{ number_format($financials['months'][$month]['expected_contracts'], 0) }}
                                        </strong>
                                    </td>
                                @endfor
                                <td class="text-center bg-warning text-white">
                                    <strong>{{ number_format($financials['totals']['expected_contracts'], 0) }}</strong>
                                </td>
                            </tr>

                            <!-- Income Row -->
                            <tr class="income-row">
                                <td class="text-center align-middle bg-label-success">
                                    <strong>Income</strong>
                                </td>
                                @for($month = 1; $month <= 12; $month++)
                                    <td class="text-center income-cell">
                                        <strong class="text-success">
                                            {{ number_format($financials['months'][$month]['income'], 0) }}
                                        </strong>
                                    </td>
                                @endfor
                                <td class="text-center bg-success text-white">
                                    <strong>{{ number_format($financials['totals']['income'], 0) }}</strong>
                                </td>
                            </tr>

                            <!-- Expected Income Row -->
                            <tr class="expected-income-row product-end">
                                <td class="text-center align-middle" style="background-color: rgba(111, 66, 193, 0.1);">
                                    <strong>Expected Income</strong>
                                </td>
                                @for($month = 1; $month <= 12; $month++)
                                    <td class="text-center expected-income-cell">
                                        <strong style="color: #6f42c1;">
                                            {{ number_format($financials['months'][$month]['expected_income'], 0) }}
                                        </strong>
                                    </td>
                                @endfor
                                <td class="text-center text-white" style="background-color: #6f42c1;">
                                    <strong>{{ number_format($financials['totals']['expected_income'], 0) }}</strong>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="15" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="ti ti-package-off display-6 text-muted mb-3"></i>
                                        <h5 class="text-muted">No Products Found</h5>
                                        <p class="text-muted mb-0">No products are configured for {{ $businessUnit->name }}.</p>
                                        <a href="{{ route('administration.products.index') }}" class="btn btn-outline-primary mt-3">
                                            <i class="ti ti-plus me-1"></i>Add Products
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse

                        <!-- Grand Total Row -->
                        @if(count($incomeSheetData) > 0)
                            <tr class="table-dark grand-total-row">
                                <td colspan="2" class="text-center align-middle">
                                    <strong class="fs-5">GRAND TOTAL - {{ $businessUnit->name }}</strong>
                                </td>
                                @for($month = 1; $month <= 12; $month++)
                                    <td class="text-center">
                                        <div class="small-stats">
                                            <div class="mb-1">
                                                <strong class="text-danger">{{ number_format($grandTotals['months'][$month]['balance'], 0) }}</strong>
                                            </div>
                                            <div class="mb-1">
                                                <small class="text-primary">{{ number_format($grandTotals['months'][$month]['contracts'], 0) }}</small>
                                            </div>
                                            <div class="mb-1">
                                                <small class="text-warning">{{ number_format($grandTotals['months'][$month]['expected_contracts'], 0) }}</small>
                                            </div>
                                            <div class="mb-1">
                                                <small class="text-success">{{ number_format($grandTotals['months'][$month]['income'], 0) }}</small>
                                            </div>
                                            <div>
                                                <small style="color: #6f42c1;">{{ number_format($grandTotals['months'][$month]['expected_income'], 0) }}</small>
                                            </div>
                                        </div>
                                    </td>
                                @endfor
                                <td class="text-center bg-dark text-white">
                                    <div class="total-stats">
                                        <div class="mb-1">
                                            <strong class="text-light">{{ number_format($grandTotals['totals']['balance'], 0) }}</strong>
                                        </div>
                                        <div class="mb-1">
                                            <small>{{ number_format($grandTotals['totals']['contracts'], 0) }}</small>
                                        </div>
                                        <div class="mb-1">
                                            <small>{{ number_format($grandTotals['totals']['expected_contracts'], 0) }}</small>
                                        </div>
                                        <div class="mb-1">
                                            <small>{{ number_format($grandTotals['totals']['income'], 0) }}</small>
                                        </div>
                                        <div>
                                            <small>{{ number_format($grandTotals['totals']['expected_income'], 0) }}</small>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Legend -->
            <div class="card-footer">
                <div class="row text-center">
                    <div class="col-md-2">
                        <div class="d-flex align-items-center justify-content-center">
                            <span class="badge bg-danger me-2">Balance</span>
                            <small class="text-muted">Cumulative income received</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="d-flex align-items-center justify-content-center">
                            <span class="badge bg-primary me-2">Contracts</span>
                            <small class="text-muted">Approved contracts value</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="d-flex align-items-center justify-content-center">
                            <span class="badge bg-warning me-2">Expected</span>
                            <small class="text-muted">Draft contracts value</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center justify-content-center">
                            <span class="badge bg-success me-2">Income</span>
                            <small class="text-muted">Monthly income received</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center justify-content-center">
                            <span class="badge me-2" style="background-color: #6f42c1; color: white;">Expected Income</span>
                            <small class="text-muted">Pending payments due</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-style')
<style>
    /* Custom styles for the income sheet */
    .small-stats div {
        font-size: 0.75rem;
        line-height: 1.2;
    }

    .total-stats div {
        font-size: 0.8rem;
        line-height: 1.3;
    }

    .balance-row {
        border-top: 2px solid #e7e7e7;
    }

    .product-end {
        border-bottom: 2px solid #e7e7e7;
    }

    .grand-total-row {
        border-top: 3px solid #000;
    }

    .product-start td:first-child {
        border-left: 3px solid #007bff;
    }

    /* Print styles */
    @media print {
        .card-header .d-flex .btn {
            display: none !important;
        }

        .table {
            font-size: 0.8rem;
        }

        .small-stats div,
        .total-stats div {
            font-size: 0.7rem;
        }
    }
</style>
@endsection