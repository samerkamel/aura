@extends('layouts/layoutMaster')

@section('title', 'Product Management')

@section('vendor-style')
@vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/select2/select2.scss'
])
@endsection

@section('page-style')
<style>
.product-avatar {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: white;
    font-size: 0.75rem;
}
.status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
.stats-card {
    border-left: 4px solid;
    border-radius: 0.5rem;
}
.stats-card.total { border-left-color: #007bff; }
.stats-card.active { border-left-color: #28a745; }
.stats-card.inactive { border-left-color: #dc3545; }
.stats-card.budget { border-left-color: #ffc107; }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Business Unit Context Header -->
    @if($currentBusinessUnit)
    <div class="alert alert-info mb-4">
        <div class="d-flex align-items-center">
            <div class="avatar avatar-sm me-3">
                <span class="avatar-initial rounded-circle bg-label-{{ $currentBusinessUnit->type === 'head_office' ? 'info' : 'primary' }}">
                    <i class="ti {{ $currentBusinessUnit->type === 'head_office' ? 'ti-building-skyscraper' : 'ti-building' }} ti-sm"></i>
                </span>
            </div>
            <div class="flex-grow-1">
                <h6 class="mb-0">{{ $currentBusinessUnit->name }} - Product Management</h6>
                <small class="text-muted">Viewing products for {{ $currentBusinessUnit->code }}</small>
            </div>
        </div>
    </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">Product Management</h4>
        <a href="{{ route('administration.products.create') }}" class="btn btn-primary">
            <i class="ti tabler-plus me-2"></i>Create Product
        </a>
    </div>

    <!-- Fiscal Year Header -->
    <div class="alert alert-{{ $selectedFiscalYear == $currentFiscalYear ? 'success' : 'warning' }} mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <i class="ti tabler-calendar-event ti-lg me-2"></i>
                <div>
                    <h6 class="mb-0">Fiscal Year {{ $selectedFiscalYear }}</h6>
                    <small>{{ $statistics['fiscal_year_start']->format('M d, Y') }} - {{ $statistics['fiscal_year_end']->format('M d, Y') }}</small>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <span class="badge bg-{{ $selectedFiscalYear == $currentFiscalYear ? 'success' : 'secondary' }} me-2">
                    {{ number_format($statistics['fiscal_year_progress'], 1) }}% of year
                </span>
                @if($selectedFiscalYear != $currentFiscalYear)
                    <a href="{{ route('administration.products.index', array_merge(request()->except('fiscal_year'), ['fiscal_year' => $currentFiscalYear])) }}"
                       class="btn btn-sm btn-success">
                        <i class="ti tabler-arrow-back me-1"></i>Current FY
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stats-card total h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti tabler-file-text ti-sm"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Total Contract Value</small>
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-1">{{ number_format($statistics['total_contracts'], 0) }} EGP</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card active h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti tabler-building-store ti-sm"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Active Products</small>
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-1">{{ $statistics['active_products'] }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card inactive h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ti tabler-calendar ti-sm"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">FY{{ $selectedFiscalYear }} YTD Budget</small>
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-1">{{ number_format($statistics['total_ytd_budget'], 0) }} EGP</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card budget h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti tabler-coin ti-sm"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">FY{{ $selectedFiscalYear }} Total Budget</small>
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-1">{{ number_format($statistics['total_budget'], 0) }} EGP</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('administration.products.index') }}">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Fiscal Year</label>
                        <select class="form-select" name="fiscal_year" onchange="this.form.submit()">
                            @foreach($fiscalYearOptions as $year => $label)
                                <option value="{{ $year }}" {{ $selectedFiscalYear == $year ? 'selected' : '' }}>
                                    FY {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" value="{{ request('search') }}"
                               placeholder="Product name, code, or head...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Business Unit</label>
                        <select class="form-select" name="business_unit_id">
                            <option value="">All Business Units</option>
                            @foreach($accessibleBusinessUnits as $businessUnit)
                                <option value="{{ $businessUnit->id }}" {{ request('business_unit_id') == $businessUnit->id ? 'selected' : '' }}>
                                    {{ $businessUnit->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">Filter</button>
                            <a href="{{ route('administration.products.index') }}" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="datatables-products table border-top">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Business Unit</th>
                        <th>Contracts</th>
                        <th>FY{{ $selectedFiscalYear }} YTD</th>
                        <th>FY{{ $selectedFiscalYear }} Budget</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr class="{{ !$product->is_active ? 'opacity-50' : '' }}">
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="product-avatar me-3" style="background-color: {{ '#' . substr(md5($product->code), 0, 6) }};">
                                    {{ $product->code }}
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $product->name }}</h6>
                                    <small class="text-muted">{{ $product->description ?? 'No description' }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($product->businessUnit)
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-xs me-2">
                                        <span class="avatar-initial rounded-circle bg-label-{{ $product->businessUnit->type === 'head_office' ? 'info' : 'primary' }}">
                                            <i class="ti {{ $product->businessUnit->type === 'head_office' ? 'ti-building-skyscraper' : 'ti-building' }} ti-xs"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="fw-medium">{{ $product->businessUnit->name }}</span>
                                        <small class="d-block text-muted">{{ $product->businessUnit->code }}</small>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted">No assignment</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="fw-medium me-2">{{ number_format($product->contract_value, 0) }} EGP</span>
                                @if($product->ytd_budget > 0)
                                    <span class="badge bg-{{ $product->achievement_percentage >= 100 ? 'success' : ($product->achievement_percentage >= 75 ? 'warning' : 'danger') }}">
                                        {{ number_format($product->achievement_percentage, 0) }}%
                                    </span>
                                @else
                                    <span class="badge bg-secondary">N/A</span>
                                @endif
                            </div>
                            <small class="text-muted">{{ $product->contracts->count() }} contracts</small>
                        </td>
                        <td>
                            <span class="fw-medium">{{ number_format($product->ytd_budget, 0) }} EGP</span>
                            <small class="d-block text-muted">
                                {{ number_format($statistics['fiscal_year_progress'], 0) }}% of FY{{ $selectedFiscalYear }}
                            </small>
                        </td>
                        <td>
                            @if($product->current_year_budget > 0)
                                <div class="d-flex align-items-center">
                                    <span class="fw-medium me-2">{{ number_format($product->current_year_budget, 0) }} EGP</span>
                                    <span class="badge bg-label-info">{{ number_format($product->budget_percentage, 1) }}%</span>
                                </div>
                                <small class="text-muted d-block">
                                    FY{{ $selectedFiscalYear }} budget ({{ number_format($product->budget_percentage, 1) }}% of total)
                                </small>
                            @else
                                <span class="text-muted">No FY{{ $selectedFiscalYear }} budget</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <a href="{{ route('administration.products.show', $product) }}"
                                   class="btn btn-sm btn-icon btn-outline-primary"
                                   data-bs-toggle="tooltip" title="View Details">
                                    <i class="ti tabler-eye"></i>
                                </a>
                                <a href="{{ route('administration.products.edit', $product) }}"
                                   class="btn btn-sm btn-icon btn-outline-info"
                                   data-bs-toggle="tooltip" title="Edit Product">
                                    <i class="ti tabler-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('administration.products.toggle-status', $product) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="btn btn-sm btn-icon {{ $product->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                            data-bs-toggle="tooltip"
                                            title="{{ $product->is_active ? 'Deactivate' : 'Activate' }} Product"
                                            onclick="return confirm('Are you sure you want to {{ $product->is_active ? 'deactivate' : 'activate' }} this product?')">
                                        <i class="ti tabler-{{ $product->is_active ? 'toggle-right' : 'toggle-left' }}"></i>
                                    </button>
                                </form>
                                @if($product->contracts->count() === 0)
                                <form method="POST" action="{{ route('administration.products.destroy', $product) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-sm btn-icon btn-outline-danger"
                                            data-bs-toggle="tooltip" title="Delete Product"
                                            onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">
                                        <i class="ti tabler-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="text-muted">
                                <i class="ti tabler-building-off ti-3x mb-3 d-block"></i>
                                <h5>No products found</h5>
                                <p class="mb-0">No products match your current filters.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
        <div class="card-footer">
            {{ $products->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@section('vendor-script')
@vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/select2/select2.js'
])
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection