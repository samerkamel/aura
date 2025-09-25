@extends('layouts/layoutMaster')

@section('title', 'Product Details')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold py-3 mb-0">Product Details</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('administration.products.index') }}">Product Management</a>
                    </li>
                    <li class="breadcrumb-item active">{{ $department->name }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('administration.products.edit', $department) }}" class="btn btn-primary">
                <i class="ti ti-edit me-2"></i>Edit Product
            </a>
            <a href="{{ route('administration.products.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-2"></i>Back to Products
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Product Information -->
        <div class="col-xl-4 col-lg-5 col-md-5">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="mx-auto mb-3" style="width: 100px; height: 100px; border-radius: 12px; background-color: {{ '#' . substr(md5($department->code), 0, 6) }}; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; font-weight: 600;">
                            {{ $department->code }}
                        </div>
                        <h4 class="mb-1">{{ $department->name }}</h4>
                        <span class="badge {{ $department->is_active ? 'bg-label-success' : 'bg-label-danger' }}">
                            {{ $department->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="info-container">
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <span class="fw-medium me-2">Product Code:</span>
                                <span>{{ $department->code }}</span>
                            </li>
                            @if($department->description)
                            <li class="mb-3">
                                <span class="fw-medium me-2">Description:</span>
                                <span>{{ $department->description }}</span>
                            </li>
                            @endif
                            @if($department->head_of_product)
                            <li class="mb-3">
                                <span class="fw-medium me-2">Head of Product:</span>
                                <span>{{ $department->head_of_product }}</span>
                            </li>
                            @endif
                            @if($department->email)
                            <li class="mb-3">
                                <span class="fw-medium me-2">Email:</span>
                                <span>{{ $department->email }}</span>
                            </li>
                            @endif
                            @if($department->phone)
                            <li class="mb-3">
                                <span class="fw-medium me-2">Phone:</span>
                                <span>{{ $department->phone }}</span>
                            </li>
                            @endif
                            @if($department->budget_allocation)
                            <li class="mb-3">
                                <span class="fw-medium me-2">Budget Allocation:</span>
                                <span>{{ number_format($department->budget_allocation, 2) }} EGP</span>
                            </li>
                            @endif
                            <li class="mb-3">
                                <span class="fw-medium me-2">Created:</span>
                                <span>{{ $department->created_at->format('M d, Y g:i A') }}</span>
                            </li>
                            <li class="mb-3">
                                <span class="fw-medium me-2">Last Updated:</span>
                                <span>{{ $department->updated_at->format('M d, Y g:i A') }}</span>
                            </li>
                        </ul>

                        <!-- Quick Actions -->
                        <div class="d-grid gap-2 mt-4">
                            <form method="POST" action="{{ route('administration.products.toggle-status', $department) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="btn {{ $department->is_active ? 'btn-warning' : 'btn-success' }} w-100"
                                        onclick="return confirm('Are you sure you want to {{ $department->is_active ? 'deactivate' : 'activate' }} this product?')">
                                    <i class="ti ti-{{ $department->is_active ? 'toggle-right' : 'toggle-left' }} me-2"></i>
                                    {{ $department->is_active ? 'Deactivate Product' : 'Activate Product' }}
                                </button>
                            </form>
                            @if($department->contracts->count() === 0)
                            <form method="POST" action="{{ route('administration.products.destroy', $department) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="btn btn-danger w-100"
                                        onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">
                                    <i class="ti ti-trash me-2"></i>Delete Product
                                </button>
                            </form>
                            @else
                            <div class="alert alert-info">
                                <i class="ti ti-info-circle me-2"></i>
                                Cannot delete product with assigned contracts.
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contracts and Statistics -->
        <div class="col-xl-8 col-lg-7 col-md-7">
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="avatar mx-auto mb-2">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="ti ti-file-text"></i>
                                </span>
                            </div>
                            <h5 class="mb-1">{{ $department->contracts->count() }}</h5>
                            <small class="text-muted">Assigned Contracts</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="avatar mx-auto mb-2">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="ti ti-currency-dollar"></i>
                                </span>
                            </div>
                            <h5 class="mb-1">{{ number_format($department->total_contract_allocations, 0) }} EGP</h5>
                            <small class="text-muted">Total Allocated</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="avatar mx-auto mb-2">
                                <span class="avatar-initial rounded bg-label-warning">
                                    <i class="ti ti-percentage"></i>
                                </span>
                            </div>
                            @if($department->budget_allocation > 0)
                                <h5 class="mb-1">{{ number_format(($department->total_contract_allocations / $department->budget_allocation) * 100, 1) }}%</h5>
                                <small class="text-muted">Budget Usage</small>
                            @else
                                <h5 class="mb-1">-</h5>
                                <small class="text-muted">No Budget Set</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assigned Contracts -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti ti-file-text me-2"></i>Assigned Contracts
                    </h5>
                    <span class="badge bg-label-primary">{{ $department->contracts->count() }} {{ $department->contracts->count() === 1 ? 'Contract' : 'Contracts' }}</span>
                </div>
                <div class="card-body">
                    @if($department->contracts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Contract</th>
                                        <th>Client</th>
                                        <th>Total Amount</th>
                                        <th>Allocation</th>
                                        <th>Type</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($department->contracts as $contract)
                                    <tr>
                                        <td>
                                            <div>
                                                <h6 class="mb-0">{{ $contract->contract_number }}</h6>
                                                <small class="text-muted">{{ \Illuminate\Support\Str::limit($contract->description, 30) }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-medium">{{ $contract->client_name }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-medium">{{ number_format($contract->total_amount, 2) }} EGP</span>
                                        </td>
                                        <td>
                                            @if($contract->pivot->allocation_type === 'percentage')
                                                <span class="badge bg-label-info">{{ $contract->pivot->allocation_percentage }}%</span>
                                                <br><small class="text-muted">{{ number_format(($contract->pivot->allocation_percentage / 100) * $contract->total_amount, 2) }} EGP</small>
                                            @else
                                                <span class="badge bg-label-success">{{ number_format($contract->pivot->allocation_amount, 2) }} EGP</span>
                                                <br><small class="text-muted">{{ number_format(($contract->pivot->allocation_amount / $contract->total_amount) * 100, 1) }}%</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-label-{{ $contract->pivot->allocation_type === 'percentage' ? 'primary' : 'secondary' }}">
                                                {{ ucfirst($contract->pivot->allocation_type) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($contract->pivot->notes)
                                                <span class="text-muted" data-bs-toggle="tooltip" title="{{ $contract->pivot->notes }}">
                                                    {{ \Illuminate\Support\Str::limit($contract->pivot->notes, 20) }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="ti ti-file-off ti-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Assigned Contracts</h5>
                            <p class="text-muted mb-0">This product has no contracts assigned yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection