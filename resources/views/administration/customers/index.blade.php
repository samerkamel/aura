@extends('layouts/layoutMaster')

@section('title', 'Customers - Administration')

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
  <!-- Customers Overview -->
  <div class="col-12 mb-6">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="ti tabler-users me-2"></i>Customer Management
        </h5>
        @can('manage-customers')
          <div class="d-flex gap-2">
            <a href="{{ route('administration.customers.import') }}" class="btn btn-outline-info btn-sm" title="Import Customers from CSV">
              <i class="ti tabler-upload"></i>
            </a>
            <a href="{{ route('administration.customers.create') }}" class="btn btn-primary">
              <i class="ti tabler-plus me-1"></i>Add Customer
            </a>
          </div>
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
                    <i class="ti tabler-users ti-lg"></i>
                  </span>
                </div>
                <span class="d-block text-nowrap">Total Customers</span>
                <h2 class="mb-0">{{ $statistics['total_customers'] }}</h2>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-xl-3">
            <div class="card bg-label-success">
              <div class="card-body text-center">
                <div class="avatar mx-auto mb-2">
                  <span class="avatar-initial rounded-circle bg-success">
                    <i class="ti tabler-check ti-lg"></i>
                  </span>
                </div>
                <span class="d-block text-nowrap">Active Customers</span>
                <h2 class="mb-0">{{ $statistics['active_customers'] }}</h2>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-xl-3">
            <div class="card bg-label-info">
              <div class="card-body text-center">
                <div class="avatar mx-auto mb-2">
                  <span class="avatar-initial rounded-circle bg-info">
                    <i class="ti tabler-building ti-lg"></i>
                  </span>
                </div>
                <span class="d-block text-nowrap">Companies</span>
                <h2 class="mb-0">{{ $statistics['company_customers'] }}</h2>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-xl-3">
            <div class="card bg-label-warning">
              <div class="card-body text-center">
                <div class="avatar mx-auto mb-2">
                  <span class="avatar-initial rounded-circle bg-warning">
                    <i class="ti tabler-user ti-lg"></i>
                  </span>
                </div>
                <span class="d-block text-nowrap">Individuals</span>
                <h2 class="mb-0">{{ $statistics['individual_customers'] }}</h2>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Customers Table -->
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Customers List</h5>
        <div class="d-flex gap-2">
          <!-- Search Form -->
          <form method="GET" class="d-flex">
            <input type="search" name="search" value="{{ request('search') }}" class="form-control me-2" placeholder="Search customers..." style="width: 200px;">
            <button type="submit" class="btn btn-outline-primary">
              <i class="ti tabler-search"></i>
            </button>
          </form>
          <div class="btn-group">
            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
              <i class="ti tabler-filter me-1"></i>Filter
            </button>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['status' => '']) }}">All Status</a></li>
              <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['status' => 'active']) }}">Active Only</a></li>
              <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['status' => 'inactive']) }}">Inactive Only</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['type' => '']) }}">All Types</a></li>
              <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['type' => 'company']) }}">Companies</a></li>
              <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['type' => 'individual']) }}">Individuals</a></li>
            </ul>
          </div>
        </div>
      </div>

      <div class="card-datatable table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Customer</th>
              <th>Type</th>
              <th>Contact</th>
              <th>Contracts</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($customers as $customer)
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm me-3">
                      <span class="avatar-initial rounded-circle bg-label-{{ $customer->type === 'company' ? 'info' : 'primary' }}">
                        <i class="ti {{ $customer->type === 'company' ? 'ti-building' : 'ti-user' }}"></i>
                      </span>
                    </div>
                    <div>
                      <h6 class="mb-0">{{ $customer->display_name }}</h6>
                      @if($customer->type === 'company' && $customer->name !== $customer->company_name)
                        <small class="text-muted">Contact: {{ $customer->name }}</small>
                      @endif
                      @if($customer->tax_id)
                        <br><small class="text-muted">Tax ID: {{ $customer->tax_id }}</small>
                      @endif
                    </div>
                  </div>
                </td>
                <td>
                  <span class="badge bg-label-{{ $customer->type === 'company' ? 'info' : 'primary' }}">
                    {{ ucfirst($customer->type) }}
                  </span>
                </td>
                <td>
                  <div>
                    @if($customer->email)
                      <div><i class="ti tabler-mail me-1"></i>{{ $customer->email }}</div>
                    @endif
                    @if($customer->phone)
                      <div><i class="ti tabler-phone me-1"></i>{{ $customer->phone }}</div>
                    @endif
                    @if($customer->website)
                      <div><i class="ti tabler-world me-1"></i><a href="{{ $customer->website }}" target="_blank">{{ $customer->website }}</a></div>
                    @endif
                  </div>
                </td>
                <td>
                  <span class="badge bg-label-warning">{{ $customer->contracts_count }} contracts</span>
                </td>
                <td>
                  <span class="badge bg-label-{{ $customer->status === 'active' ? 'success' : 'secondary' }}">
                    {{ ucfirst($customer->status) }}
                  </span>
                </td>
                <td>
                  <div class="dropdown">
                    <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                      <i class="ti tabler-dots-vertical"></i>
                    </button>
                    <div class="dropdown-menu">
                      <a class="dropdown-item" href="{{ route('administration.customers.show', $customer) }}">
                        <i class="ti tabler-eye me-2"></i>View Details
                      </a>
                      @can('manage-customers')
                        <a class="dropdown-item" href="{{ route('administration.customers.edit', $customer) }}">
                          <i class="ti tabler-edit me-2"></i>Edit
                        </a>
                        @if($customer->contracts_count == 0)
                          <div class="dropdown-divider"></div>
                          <form method="POST" action="{{ route('administration.customers.destroy', $customer) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this customer?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dropdown-item text-danger">
                              <i class="ti tabler-trash me-2"></i>Delete
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

      @if($customers->hasPages())
        <div class="card-footer">
          {{ $customers->withQueryString()->links() }}
        </div>
      @endif
    </div>
  </div>
</div>

@if(session('success'))
  <div class="bs-toast toast toast-placement-ex m-2 fade bg-success show top-0 end-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
    <div class="toast-header">
      <i class="ti tabler-check text-success me-2"></i>
      <div class="me-auto fw-medium">Success!</div>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">{{ session('success') }}</div>
  </div>
@endif

@if(session('error'))
  <div class="bs-toast toast toast-placement-ex m-2 fade bg-danger show top-0 end-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
    <div class="toast-header">
      <i class="ti tabler-x text-danger me-2"></i>
      <div class="me-auto fw-medium">Error!</div>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">{{ session('error') }}</div>
  </div>
@endif
@endsection