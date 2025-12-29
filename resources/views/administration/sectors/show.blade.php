@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Sector Details - Administration')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}">
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Sector Overview -->
    <div class="card mb-4">
      <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="ti tabler-world me-2"></i>{{ $sector->name }}
        </h5>
        <div>
          <span class="badge bg-label-info me-2">{{ $sector->code }}</span>
          <span class="badge bg-label-{{ $sector->is_active ? 'success' : 'warning' }}">
            {{ $sector->is_active ? 'Active' : 'Inactive' }}
          </span>
        </div>
      </div>

      <div class="card-body">
        <div class="row">
          <div class="col-md-8">
            <h6 class="fw-semibold mb-2">Description</h6>
            <p class="text-muted mb-4">
              {{ $sector->description ?: 'No description provided' }}
            </p>

            <div class="row">
              <div class="col-sm-6">
                <h6 class="fw-semibold mb-2">Created</h6>
                <p class="text-muted">{{ $sector->created_at->format('M d, Y g:i A') }}</p>
              </div>
              <div class="col-sm-6">
                <h6 class="fw-semibold mb-2">Last Updated</h6>
                <p class="text-muted">{{ $sector->updated_at->format('M d, Y g:i A') }}</p>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="row text-center">
              <div class="col-6">
                <div class="border rounded p-3">
                  <h4 class="mb-1 text-primary">{{ $sector->business_units_count }}</h4>
                  <small class="text-muted">Total Business Units</small>
                </div>
              </div>
              <div class="col-6">
                <div class="border rounded p-3">
                  <h4 class="mb-1 text-success">{{ $sector->active_business_units_count }}</h4>
                  <small class="text-muted">Active Units</small>
                </div>
              </div>
            </div>

            @if($sector->total_budget > 0)
              <div class="border rounded p-3 mt-3 text-center">
                <h5 class="mb-1 text-info">${{ number_format($sector->total_budget) }}</h5>
                <small class="text-muted">Total Budget</small>
              </div>
            @endif

            @if($sector->total_contracts_value > 0)
              <div class="border rounded p-3 mt-3 text-center">
                <h5 class="mb-1 text-warning">${{ number_format($sector->total_contracts_value) }}</h5>
                <small class="text-muted">Contracts Value</small>
              </div>
            @endif
          </div>
        </div>
      </div>

      <div class="card-footer d-flex justify-content-between">
        <a href="{{ route('administration.sectors.index') }}" class="btn btn-secondary">
          <i class="ti tabler-arrow-left me-1"></i>Back to Sectors
        </a>
        <a href="{{ route('administration.sectors.edit', $sector) }}" class="btn btn-primary">
          <i class="ti tabler-edit me-1"></i>Edit Sector
        </a>
      </div>
    </div>

    <!-- Business Units in this Sector -->
    <div class="card">
      <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="ti tabler-building me-2"></i>Business Units ({{ $sector->business_units_count }})
        </h5>
        <a href="{{ route('administration.business-units.create') }}?sector_id={{ $sector->id }}" class="btn btn-primary btn-sm">
          <i class="ti tabler-plus me-1"></i>Add Business Unit
        </a>
      </div>

      <div class="card-body">
        @if($sector->businessUnits->count() > 0)
          <div class="table-responsive">
            <table class="table table-bordered" id="businessUnitsTable">
              <thead class="table-light">
                <tr>
                  <th>Business Unit</th>
                  <th>Type</th>
                  <th>Products</th>
                  <th>Contracts</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($sector->businessUnits as $businessUnit)
                  <tr>
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-2">
                          <span class="avatar-initial rounded-circle bg-label-primary">
                            <i class="ti tabler-building ti-sm"></i>
                          </span>
                        </div>
                        <div>
                          <h6 class="mb-0">{{ $businessUnit->name }}</h6>
                          <small class="text-muted">{{ $businessUnit->code }}</small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-label-secondary">{{ ucfirst(str_replace('_', ' ', $businessUnit->type)) }}</span>
                    </td>
                    <td class="text-center">
                      <span class="fw-semibold">{{ $businessUnit->products_count ?? 0 }}</span>
                    </td>
                    <td class="text-center">
                      <span class="fw-semibold">{{ $businessUnit->contracts_count ?? 0 }}</span>
                    </td>
                    <td>
                      <span class="badge bg-label-{{ $businessUnit->is_active ? 'success' : 'warning' }}">
                        {{ $businessUnit->is_active ? 'Active' : 'Inactive' }}
                      </span>
                    </td>
                    <td>
                      <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                          Actions
                        </button>
                        <ul class="dropdown-menu">
                          <li>
                            <a class="dropdown-item" href="{{ route('administration.business-units.show', $businessUnit) }}">
                              <i class="ti tabler-eye me-1"></i>View Details
                            </a>
                          </li>
                          <li>
                            <a class="dropdown-item" href="{{ route('administration.business-units.edit', $businessUnit) }}">
                              <i class="ti tabler-edit me-1"></i>Edit
                            </a>
                          </li>
                          @if($businessUnit->contracts_count == 0)
                            <li><hr class="dropdown-divider"></li>
                            <li>
                              <a class="dropdown-item text-warning" href="#" onclick="removeBU({{ $businessUnit->id }})">
                                <i class="ti tabler-unlink me-1"></i>Remove from Sector
                              </a>
                            </li>
                          @endif
                        </ul>
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="text-center py-5">
            <i class="ti tabler-building text-muted mb-3" style="font-size: 4rem;"></i>
            <h5>No Business Units</h5>
            <p class="text-muted">This sector doesn't have any business units yet</p>
            <a href="{{ route('administration.business-units.create') }}?sector_id={{ $sector->id }}" class="btn btn-primary">
              <i class="ti tabler-plus me-1"></i>Add First Business Unit
            </a>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<script>
// Initialize DataTable
document.addEventListener('DOMContentLoaded', function() {
  const table = document.getElementById('businessUnitsTable');
  if (table && table.rows.length > 1 && typeof jQuery !== 'undefined') {
    jQuery('#businessUnitsTable').DataTable({
      responsive: true,
      pageLength: 25,
      order: [[0, 'asc']],
      columnDefs: [
        {
          targets: [5], // Actions column
          orderable: false,
          searchable: false
        }
      ]
    });
  }
});

// Remove business unit from sector (placeholder function)
function removeBU(businessUnitId) {
  alert('Remove from sector functionality would be implemented here');
}
</script>
@endsection