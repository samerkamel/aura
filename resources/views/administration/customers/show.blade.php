@extends('layouts/layoutMaster')

@section('title', 'Customer Details - Administration')

@section('vendor-style')
@endsection

@section('vendor-script')
@endsection

@section('page-script')
@endsection

@section('content')
<div class="row">
  <!-- Customer Details Card -->
  <div class="col-md-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="ti {{ $customer->type === 'company' ? 'ti-building' : 'ti-user' }} me-2"></i>
          {{ $customer->display_name }}
        </h5>
        <div>
          @can('manage-customers')
            <a href="{{ route('administration.customers.edit', $customer) }}" class="btn btn-primary btn-sm">
              <i class="ti ti-edit me-1"></i>Edit
            </a>
          @endcan
          <a href="{{ route('administration.customers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-arrow-left me-1"></i>Back
          </a>
        </div>
      </div>

      <div class="card-body">
        <!-- Customer Information -->
        <div class="row">
          <div class="col-md-6">
            <h6 class="fw-bold mb-3">Basic Information</h6>

            <div class="mb-3">
              <label class="form-label text-muted">Customer Type</label>
              <div>
                <span class="badge bg-label-{{ $customer->type === 'company' ? 'info' : 'primary' }} fs-6">
                  {{ ucfirst($customer->type) }}
                </span>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label text-muted">Contact Person</label>
              <div class="fw-medium">{{ $customer->name }}</div>
            </div>

            @if($customer->type === 'company' && $customer->company_name)
            <div class="mb-3">
              <label class="form-label text-muted">Company Name</label>
              <div class="fw-medium">{{ $customer->company_name }}</div>
            </div>
            @endif

            @if($customer->tax_id)
            <div class="mb-3">
              <label class="form-label text-muted">Tax ID / Registration Number</label>
              <div class="fw-medium">{{ $customer->tax_id }}</div>
            </div>
            @endif

            <div class="mb-3">
              <label class="form-label text-muted">Status</label>
              <div>
                <span class="badge bg-label-{{ $customer->status === 'active' ? 'success' : 'secondary' }} fs-6">
                  {{ ucfirst($customer->status) }}
                </span>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <h6 class="fw-bold mb-3">Contact Information</h6>

            @if($customer->email)
            <div class="mb-3">
              <label class="form-label text-muted">Email</label>
              <div>
                <i class="ti ti-mail me-1"></i>
                <a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>
              </div>
            </div>
            @endif

            @if($customer->phone)
            <div class="mb-3">
              <label class="form-label text-muted">Phone</label>
              <div>
                <i class="ti ti-phone me-1"></i>
                <a href="tel:{{ $customer->phone }}">{{ $customer->phone }}</a>
              </div>
            </div>
            @endif

            @if($customer->website)
            <div class="mb-3">
              <label class="form-label text-muted">Website</label>
              <div>
                <i class="ti ti-world me-1"></i>
                <a href="{{ $customer->website }}" target="_blank">{{ $customer->website }}</a>
              </div>
            </div>
            @endif

            @if($customer->address)
            <div class="mb-3">
              <label class="form-label text-muted">Address</label>
              <div>
                <i class="ti ti-map-pin me-1"></i>
                <span class="text-wrap">{{ $customer->address }}</span>
              </div>
            </div>
            @endif
          </div>
        </div>

        @if($customer->notes)
        <div class="row mt-4">
          <div class="col-12">
            <h6 class="fw-bold mb-3">Notes</h6>
            <div class="bg-light p-3 rounded">
              {{ $customer->notes }}
            </div>
          </div>
        </div>
        @endif
      </div>
    </div>
  </div>

  <!-- Statistics & Actions Card -->
  <div class="col-md-4">
    <!-- Statistics -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="ti ti-chart-bar me-2"></i>Statistics
        </h5>
      </div>
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span>Total Contracts</span>
          <span class="badge bg-label-primary fs-6">{{ $customer->contracts_count ?? 0 }}</span>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
          <span>Active Contracts</span>
          <span class="badge bg-label-success fs-6">{{ $customer->active_contracts_count ?? 0 }}</span>
        </div>

        @if($customer->total_contract_value > 0)
        <div class="d-flex justify-content-between align-items-center">
          <span>Total Value</span>
          <span class="badge bg-label-warning fs-6">EGP {{ number_format($customer->total_contract_value, 2) }}</span>
        </div>
        @endif
      </div>
    </div>

    <!-- Actions -->
    @can('manage-customers')
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="ti ti-settings me-2"></i>Actions
        </h5>
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <a href="{{ route('administration.customers.edit', $customer) }}" class="btn btn-outline-primary">
            <i class="ti ti-edit me-1"></i>Edit Customer
          </a>

          @if($customer->contracts_count == 0)
            <form method="POST" action="{{ route('administration.customers.destroy', $customer) }}"
                  onsubmit="return confirm('Are you sure you want to delete this customer? This action cannot be undone.')">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-outline-danger w-100">
                <i class="ti ti-trash me-1"></i>Delete Customer
              </button>
            </form>
          @else
            <button type="button" class="btn btn-outline-secondary disabled" disabled>
              <i class="ti ti-info-circle me-1"></i>Cannot delete (has contracts)
            </button>
          @endif
        </div>
      </div>
    </div>
    @endcan
  </div>
</div>

<!-- Contracts Section -->
@if($customer->contracts && $customer->contracts->count() > 0)
<div class="row mt-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="ti ti-file-text me-2"></i>Contracts ({{ $customer->contracts->count() }})
        </h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Contract</th>
                <th>Business Unit</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($customer->contracts as $contract)
              <tr>
                <td>
                  <div>
                    <h6 class="mb-0">{{ $contract->title ?? 'Contract #' . $contract->id }}</h6>
                    <small class="text-muted">{{ \Illuminate\Support\Str::limit($contract->description ?? 'No description', 50) }}</small>
                  </div>
                </td>
                <td>
                  @if($contract->businessUnit)
                    <span class="badge bg-label-info">{{ $contract->businessUnit->name }}</span>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td>
                  <span class="fw-medium">EGP {{ number_format($contract->total_amount ?? 0, 2) }}</span>
                </td>
                <td>
                  <span class="badge bg-label-{{ $contract->status === 'active' ? 'success' : ($contract->status === 'completed' ? 'info' : 'secondary') }}">
                    {{ ucfirst($contract->status) }}
                  </span>
                </td>
                <td>
                  {{ $contract->created_at->format('M j, Y') }}
                </td>
                <td>
                  <a href="#" class="btn btn-sm btn-outline-primary">View</a>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endif

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