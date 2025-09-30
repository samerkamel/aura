@extends('layouts/layoutMaster')

@section('title', 'Business Units - Administration')

@section('vendor-style')
{{-- No DataTables needed for simple table --}}
@endsection

@section('vendor-script')
{{-- No DataTables needed for simple table --}}
@endsection

@section('page-script')
{{-- No DataTables needed for simple table --}}
@endsection

@section('content')
<div class="row">
  <!-- Business Units Overview -->
  <div class="col-12 mb-6">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="ti ti-building me-2"></i>Business Units Management
        </h5>
        @can('manage-business-units')
          <a href="{{ route('administration.business-units.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>Add Business Unit
          </a>
        @endcan
      </div>

      <!-- Statistics Cards -->
      <div class="card-body">
        <div class="row g-4 mb-4">
          <div class="col-sm-6 col-xl-3">
            <div class="card bg-label-primary">
              <div class="card-body text-center">
                <div class="avatar mx-auto mb-2">
                  <span class="avatar-initial rounded-circle bg-primary">
                    <i class="ti ti-building ti-lg"></i>
                  </span>
                </div>
                <span class="d-block text-nowrap">Total BUs</span>
                <h2 class="mb-0">{{ $statistics['total_business_units'] }}</h2>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-xl-3">
            <div class="card bg-label-success">
              <div class="card-body text-center">
                <div class="avatar mx-auto mb-2">
                  <span class="avatar-initial rounded-circle bg-success">
                    <i class="ti ti-check ti-lg"></i>
                  </span>
                </div>
                <span class="d-block text-nowrap">Active BUs</span>
                <h2 class="mb-0">{{ $statistics['active_business_units'] }}</h2>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-xl-3">
            <div class="card bg-label-info">
              <div class="card-body text-center">
                <div class="avatar mx-auto mb-2">
                  <span class="avatar-initial rounded-circle bg-info">
                    <i class="ti ti-building-skyscraper ti-lg"></i>
                  </span>
                </div>
                <span class="d-block text-nowrap">Head Office</span>
                <h2 class="mb-0">{{ $statistics['head_office_units'] }}</h2>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-xl-3">
            <div class="card bg-label-warning">
              <div class="card-body text-center">
                <div class="avatar mx-auto mb-2">
                  <span class="avatar-initial rounded-circle bg-warning">
                    <i class="ti ti-packages ti-lg"></i>
                  </span>
                </div>
                <span class="d-block text-nowrap">Total Products</span>
                <h2 class="mb-0">{{ $statistics['total_products'] }}</h2>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Business Units Table -->
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Business Units List</h5>
        <div class="d-flex gap-2">
          <div class="btn-group">
            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
              <i class="ti ti-filter me-1"></i>Filter
            </button>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['status' => 'all']) }}">All Status</a></li>
              <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['status' => 'active']) }}">Active Only</a></li>
              <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['status' => 'inactive']) }}">Inactive Only</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['type' => 'business_unit']) }}">Business Units</a></li>
              <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['type' => 'head_office']) }}">Head Office</a></li>
            </ul>
          </div>
        </div>
      </div>

      <div class="card-datatable table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Business Unit</th>
              <th>Type</th>
              <th>Users</th>
              <th>Products</th>
              <th>Contracts</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($businessUnits as $businessUnit)
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm me-3">
                      <span class="avatar-initial rounded-circle bg-label-{{ $businessUnit->type === 'head_office' ? 'info' : 'primary' }}">
                        <i class="ti {{ $businessUnit->type === 'head_office' ? 'ti-building-skyscraper' : 'ti-building' }}"></i>
                      </span>
                    </div>
                    <div>
                      <h6 class="mb-0">{{ $businessUnit->name }}</h6>
                      <small class="text-muted">{{ $businessUnit->code }}</small>
                      @if($businessUnit->description)
                        <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($businessUnit->description, 50) }}</small>
                      @endif
                    </div>
                  </div>
                </td>
                <td>
                  <span class="badge bg-label-{{ $businessUnit->type === 'head_office' ? 'info' : 'primary' }}">
                    {{ $businessUnit->type === 'head_office' ? 'Head Office' : 'Business Unit' }}
                  </span>
                </td>
                <td>
                  <span class="badge bg-label-secondary">{{ $businessUnit->users->count() }} users</span>
                </td>
                <td>
                  <span class="badge bg-label-success">{{ $businessUnit->products_count }} products</span>
                </td>
                <td>
                  <span class="badge bg-label-warning">{{ $businessUnit->contracts_count }} contracts</span>
                </td>
                <td>
                  <span class="badge bg-label-{{ $businessUnit->is_active ? 'success' : 'secondary' }}">
                    {{ $businessUnit->is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td>
                  <div class="dropdown">
                    <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                      <i class="ti ti-dots-vertical"></i>
                    </button>
                    <div class="dropdown-menu">
                      <a class="dropdown-item" href="{{ route('administration.business-units.show', $businessUnit) }}">
                        <i class="ti ti-eye me-2"></i>View Details
                      </a>
                      @can('manage-business-units')
                        <a class="dropdown-item" href="{{ route('administration.business-units.edit', $businessUnit) }}">
                          <i class="ti ti-edit me-2"></i>Edit
                        </a>
                        @can('assign-users-to-business-units')
                          <a class="dropdown-item" href="{{ route('administration.business-units.manage-users', $businessUnit) }}">
                            <i class="ti ti-users me-2"></i>Manage Users
                          </a>
                        @endcan
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('administration.business-units.toggle-status', $businessUnit) }}" class="d-inline">
                          @csrf
                          @method('PATCH')
                          <button type="submit" class="dropdown-item">
                            <i class="ti {{ $businessUnit->is_active ? 'ti-ban' : 'ti-check' }} me-2"></i>
                            {{ $businessUnit->is_active ? 'Deactivate' : 'Activate' }}
                          </button>
                        </form>
                        @if($businessUnit->products_count == 0 && $businessUnit->contracts_count == 0)
                          <form method="POST" action="{{ route('administration.business-units.destroy', $businessUnit) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this business unit?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dropdown-item text-danger">
                              <i class="ti ti-trash me-2"></i>Delete
                            </button>
                          </form>
                        @endif
                      @endcan
                    </div>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@if(session('success'))
  <div class="bs-toast toast toast-placement-ex m-2 fade bg-success show top-0 end-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
    <div class="toast-header">
      <i class="ti ti-check text-success me-2"></i>
      <div class="me-auto fw-medium">Success!</div>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">{{ session('success') }}</div>
  </div>
@endif

@if(session('error'))
  <div class="bs-toast toast toast-placement-ex m-2 fade bg-danger show top-0 end-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
    <div class="toast-header">
      <i class="ti ti-x text-danger me-2"></i>
      <div class="me-auto fw-medium">Error!</div>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">{{ session('error') }}</div>
  </div>
@endif
@endsection