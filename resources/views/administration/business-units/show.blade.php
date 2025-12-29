@extends('layouts/layoutMaster')

@section('title', $businessUnit->name . ' - Business Unit Details')

@section('vendor-style')
@vite('resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss')
@vite('resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss')
@endsection

@section('vendor-script')
@vite('resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')
@endsection

@section('content')
<div class="row">
  <!-- Business Unit Header -->
  <div class="col-12 mb-6">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-lg me-4">
              <span class="avatar-initial rounded-circle bg-label-{{ $businessUnit->type === 'head_office' ? 'info' : 'primary' }}">
                <i class="ti {{ $businessUnit->type === 'head_office' ? 'ti-building-skyscraper' : 'ti-building' }} ti-lg"></i>
              </span>
            </div>
            <div>
              <h4 class="mb-0">{{ $businessUnit->name }}</h4>
              <div class="d-flex align-items-center gap-2 mt-1">
                <span class="badge bg-label-{{ $businessUnit->type === 'head_office' ? 'info' : 'primary' }}">
                  {{ $businessUnit->type === 'head_office' ? 'Head Office' : 'Business Unit' }}
                </span>
                <span class="badge bg-label-{{ $businessUnit->is_active ? 'success' : 'secondary' }}">
                  {{ $businessUnit->is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
              <p class="text-muted mb-0 mt-2">{{ $businessUnit->code }}</p>
              @if($businessUnit->description)
                <p class="text-muted mb-0 mt-1">{{ $businessUnit->description }}</p>
              @endif
            </div>
          </div>
          <div class="d-flex gap-2">
            @can('manage-business-units')
              <a href="{{ route('administration.business-units.edit', $businessUnit) }}" class="btn btn-primary">
                <i class="ti ti-edit me-1"></i>Edit
              </a>
            @endcan
            @can('assign-users-to-business-units')
              <a href="{{ route('administration.business-units.manage-users', $businessUnit) }}" class="btn btn-outline-primary">
                <i class="ti ti-users me-1"></i>Manage Users
              </a>
            @endcan
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistics -->
  <div class="col-12 mb-6">
    <div class="row g-6">
      <div class="col-md-6 col-xl-3">
        <div class="card bg-label-primary">
          <div class="card-body text-center">
            <div class="avatar mx-auto mb-2">
              <span class="avatar-initial rounded-circle bg-primary">
                <i class="ti ti-users ti-lg"></i>
              </span>
            </div>
            <span class="d-block text-nowrap">Total Users</span>
            <h2 class="mb-0">{{ $statistics['total_users'] }}</h2>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="card bg-label-success">
          <div class="card-body text-center">
            <div class="avatar mx-auto mb-2">
              <span class="avatar-initial rounded-circle bg-success">
                <i class="ti ti-building-store ti-lg"></i>
              </span>
            </div>
            <span class="d-block text-nowrap">Products</span>
            <h2 class="mb-0">{{ $statistics['active_products'] }}</h2>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="card bg-label-info">
          <div class="card-body text-center">
            <div class="avatar mx-auto mb-2">
              <span class="avatar-initial rounded-circle bg-info">
                <i class="ti ti-currency ti-lg"></i>
              </span>
            </div>
            <span class="d-block text-nowrap">Total Budget</span>
            <h2 class="mb-0">EGP {{ number_format($statistics['total_budget'], 0) }}</h2>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="card bg-label-warning">
          <div class="card-body text-center">
            <div class="avatar mx-auto mb-2">
              <span class="avatar-initial rounded-circle bg-warning">
                <i class="ti ti-file-text ti-lg"></i>
              </span>
            </div>
            <span class="d-block text-nowrap">Active Contracts</span>
            <h2 class="mb-0">{{ $statistics['active_contracts'] }}</h2>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabs Navigation -->
  <div class="col-12">
    <div class="nav-align-top">
      <ul class="nav nav-pills mb-6" role="tablist">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#users" aria-controls="users" aria-selected="true">
            <i class="ti ti-users me-1"></i>Users ({{ $businessUnit->users->count() }})
          </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#products" aria-controls="products" aria-selected="false">
            <i class="ti ti-building-store me-1"></i>Products ({{ $businessUnit->departments->count() }})
          </button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#contracts" aria-controls="contracts" aria-selected="false">
            <i class="ti ti-file-text me-1"></i>Contracts ({{ $businessUnit->contracts->count() }})
          </button>
        </li>
      </ul>

      <!-- Tab Content -->
      <div class="tab-content p-0">
        <!-- Users Tab -->
        <div class="tab-pane fade show active" id="users" role="tabpanel">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">Assigned Users</h5>
              @can('assign-users-to-business-units')
                <a href="{{ route('administration.business-units.manage-users', $businessUnit) }}" class="btn btn-sm btn-primary">
                  <i class="ti ti-user-plus me-1"></i>Manage Users
                </a>
              @endcan
            </div>
            <div class="card-body">
              @if($businessUnit->users->count() > 0)
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>User</th>
                        <th>Role in BU</th>
                        <th>System Roles</th>
                        <th>Added Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($businessUnit->users as $user)
                        <tr>
                          <td>
                            <div class="d-flex align-items-center">
                              <div class="avatar avatar-sm me-2">
                                <img src="{{ $user->profile_photo_url ?? asset('assets/img/avatars/1.png') }}" class="rounded-circle" alt="{{ $user->name }}">
                              </div>
                              <div>
                                <h6 class="mb-0">{{ $user->name }}</h6>
                                <small class="text-muted">{{ $user->email }}</small>
                              </div>
                            </div>
                          </td>
                          <td>
                            <span class="badge bg-label-primary">{{ ucfirst($user->pivot->role) }}</span>
                          </td>
                          <td>
                            @foreach($user->roles as $role)
                              <span class="badge bg-label-secondary me-1">{{ $role->display_name }}</span>
                            @endforeach
                          </td>
                          <td>{{ $user->pivot->created_at->format('M d, Y') }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @else
                <div class="text-center py-4">
                  <i class="ti ti-users ti-lg text-muted mb-2"></i>
                  <p class="text-muted">No users assigned to this business unit yet.</p>
                  @can('assign-users-to-business-units')
                    <a href="{{ route('administration.business-units.manage-users', $businessUnit) }}" class="btn btn-sm btn-primary">
                      <i class="ti ti-user-plus me-1"></i>Assign Users
                    </a>
                  @endcan
                </div>
              @endif
            </div>
          </div>
        </div>

        <!-- Products Tab -->
        <div class="tab-pane fade" id="products" role="tabpanel">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">Products</h5>
              @can('manage-products')
                <a href="{{ route('administration.products.create') }}" class="btn btn-sm btn-primary">
                  <i class="ti ti-plus me-1"></i>Add Product
                </a>
              @endcan
            </div>
            <div class="card-body">
              @if($businessUnit->departments->count() > 0)
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Product</th>
                        <th>Head</th>
                        <th>Budget</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($businessUnit->departments as $department)
                        <tr>
                          <td>
                            <div>
                              <h6 class="mb-0">{{ $department->name }}</h6>
                              <small class="text-muted">{{ $department->code }}</small>
                            </div>
                          </td>
                          <td>{{ $department->head_of_product ?? 'Not assigned' }}</td>
                          <td>EGP {{ number_format($department->budget_allocation ?? 0, 2) }}</td>
                          <td>
                            <span class="badge bg-label-{{ $department->is_active ? 'success' : 'secondary' }}">
                              {{ $department->is_active ? 'Active' : 'Inactive' }}
                            </span>
                          </td>
                          <td>
                            <a href="{{ route('administration.products.show', $department) }}" class="btn btn-sm btn-icon btn-outline-primary">
                              <i class="ti ti-eye"></i>
                            </a>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @else
                <div class="text-center py-4">
                  <i class="ti ti-building-store ti-lg text-muted mb-2"></i>
                  <p class="text-muted">No products created for this business unit yet.</p>
                  @can('manage-products')
                    <a href="{{ route('administration.products.create') }}" class="btn btn-sm btn-primary">
                      <i class="ti ti-plus me-1"></i>Create Product
                    </a>
                  @endcan
                </div>
              @endif
            </div>
          </div>
        </div>

        <!-- Contracts Tab -->
        <div class="tab-pane fade" id="contracts" role="tabpanel">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title mb-0">Contracts</h5>
            </div>
            <div class="card-body">
              @if($businessUnit->contracts->count() > 0)
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Contract</th>
                        <th>Client</th>
                        <th>Value</th>
                        <th>Status</th>
                        <th>Products</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($businessUnit->contracts as $contract)
                        <tr>
                          <td>
                            <div>
                              <h6 class="mb-0">{{ $contract->contract_number }}</h6>
                              <small class="text-muted">{{ \Illuminate\Support\Str::limit($contract->description, 30) }}</small>
                            </div>
                          </td>
                          <td>{{ $contract->client_name }}</td>
                          <td>EGP {{ number_format($contract->total_amount, 2) }}</td>
                          <td>
                            <span class="badge bg-label-{{ $contract->status === 'active' ? 'success' : 'secondary' }}">
                              {{ ucfirst($contract->status) }}
                            </span>
                          </td>
                          <td>
                            <span class="badge bg-label-info">{{ $contract->departments->count() }} products</span>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @else
                <div class="text-center py-4">
                  <i class="ti ti-file-text ti-lg text-muted mb-2"></i>
                  <p class="text-muted">No contracts assigned to this business unit yet.</p>
                </div>
              @endif
            </div>
          </div>
        </div>
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
@endsection