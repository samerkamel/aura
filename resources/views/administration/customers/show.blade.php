@extends('layouts/layoutMaster')

@section('title', 'Customer Details - Administration')

@section('vendor-style')
@endsection

@section('vendor-script')
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Year filter change
    document.getElementById('yearFilter').addEventListener('change', function() {
        const url = new URL(window.location.href);
        url.searchParams.set('year', this.value);
        window.location.href = url.toString();
    });
});
</script>
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
              <i class="ti tabler-edit me-1"></i>Edit
            </a>
          @endcan
          <a href="{{ route('administration.customers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti tabler-arrow-left me-1"></i>Back
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
                <i class="ti tabler-mail me-1"></i>
                <a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>
              </div>
            </div>
            @endif

            @if($customer->phone)
            <div class="mb-3">
              <label class="form-label text-muted">Phone</label>
              <div>
                <i class="ti tabler-phone me-1"></i>
                <a href="tel:{{ $customer->phone }}">{{ $customer->phone }}</a>
              </div>
            </div>
            @endif

            @if($customer->website)
            <div class="mb-3">
              <label class="form-label text-muted">Website</label>
              <div>
                <i class="ti tabler-world me-1"></i>
                <a href="{{ $customer->website }}" target="_blank">{{ $customer->website }}</a>
              </div>
            </div>
            @endif

            @if($customer->address)
            <div class="mb-3">
              <label class="form-label text-muted">Address</label>
              <div>
                <i class="ti tabler-map-pin me-1"></i>
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
    <!-- Year Filter -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="ti tabler-calendar me-2"></i>Filter by Year
        </h5>
      </div>
      <div class="card-body">
        <select id="yearFilter" class="form-select">
          <option value="lifetime" {{ $selectedYear === 'lifetime' ? 'selected' : '' }}>Lifetime (All Time)</option>
          @foreach($years as $year)
            <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <!-- Statistics -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="ti tabler-chart-bar me-2"></i>Statistics
          @if($selectedYear !== 'lifetime')
            <small class="text-muted">({{ $selectedYear }})</small>
          @endif
        </h5>
      </div>
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span><i class="ti tabler-folder me-1"></i> Projects</span>
          <span class="badge bg-label-info fs-6">{{ $totals['projects_count'] }}</span>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
          <span><i class="ti tabler-clock me-1"></i> Total Hours</span>
          <span class="badge bg-label-primary fs-6">{{ number_format($totals['projects_hours'], 1) }}h</span>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-3">
          <span><i class="ti tabler-file-text me-1"></i> Contracts</span>
          <span class="badge bg-label-success fs-6">{{ $totals['contracts_count'] }}</span>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
          <span><i class="ti tabler-currency-dollar me-1"></i> Contract Value</span>
          <span class="badge bg-label-warning fs-6">{{ number_format($totals['contracts_value'], 0) }}</span>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-3">
          <span><i class="ti tabler-file-invoice me-1"></i> Invoices</span>
          <span class="badge bg-label-primary fs-6">{{ $totals['invoices_count'] }}</span>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
          <span><i class="ti tabler-receipt me-1"></i> Invoice Total</span>
          <span class="badge bg-label-info fs-6">{{ number_format($totals['invoices_value'], 0) }}</span>
        </div>

        <div class="d-flex justify-content-between align-items-center">
          <span><i class="ti tabler-check me-1"></i> Paid</span>
          <span class="badge bg-label-success fs-6">{{ number_format($totals['invoices_paid'], 0) }}</span>
        </div>
      </div>
    </div>

    <!-- Actions -->
    @can('manage-customers')
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="ti tabler-settings me-2"></i>Actions
        </h5>
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <a href="{{ route('administration.customers.edit', $customer) }}" class="btn btn-outline-primary">
            <i class="ti tabler-edit me-1"></i>Edit Customer
          </a>

          @if($customer->contracts_count == 0)
            <form method="POST" action="{{ route('administration.customers.destroy', $customer) }}"
                  onsubmit="return confirm('Are you sure you want to delete this customer? This action cannot be undone.')">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-outline-danger w-100">
                <i class="ti tabler-trash me-1"></i>Delete Customer
              </button>
            </form>
          @else
            <button type="button" class="btn btn-outline-secondary disabled" disabled>
              <i class="ti tabler-info-circle me-1"></i>Cannot delete (has contracts)
            </button>
          @endif
        </div>
      </div>
    </div>
    @endcan
  </div>
</div>

<!-- Tabs for Projects, Contracts, Invoices -->
<div class="row mt-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
          <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#projects-tab" role="tab">
              <i class="ti tabler-folder me-1"></i>Projects
              <span class="badge bg-info ms-1">{{ $projects->count() }}</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#contracts-tab" role="tab">
              <i class="ti tabler-file-text me-1"></i>Contracts
              <span class="badge bg-success ms-1">{{ $contracts->count() }}</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#invoices-tab" role="tab">
              <i class="ti tabler-file-invoice me-1"></i>Invoices
              <span class="badge bg-primary ms-1">{{ $invoices->count() }}</span>
            </a>
          </li>
        </ul>
      </div>
      <div class="card-body">
        <div class="tab-content">
          <!-- Projects Tab -->
          <div class="tab-pane fade show active" id="projects-tab" role="tabpanel">
            @if($projects->count() > 0)
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Code</th>
                      <th>Project Name</th>
                      <th>Hours</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($projects as $project)
                    <tr>
                      <td>
                        <span class="badge bg-label-primary">{{ $project->code }}</span>
                      </td>
                      <td>
                        <div>
                          <h6 class="mb-0">{{ $project->name }}</h6>
                          @if($project->description)
                            <small class="text-muted">{{ \Illuminate\Support\Str::limit($project->description, 50) }}</small>
                          @endif
                        </div>
                      </td>
                      <td>
                        <span class="fw-medium">{{ number_format($project->filtered_hours, 1) }}h</span>
                      </td>
                      <td>
                        <span class="badge bg-label-{{ $project->is_active ? 'success' : 'secondary' }}">
                          {{ $project->is_active ? 'Active' : 'Inactive' }}
                        </span>
                      </td>
                      <td>
                        <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-primary">
                          <i class="ti tabler-eye"></i>
                        </a>
                      </td>
                    </tr>
                    @endforeach
                  </tbody>
                  <tfoot>
                    <tr class="table-light">
                      <th colspan="2" class="text-end">Total Hours:</th>
                      <th>{{ number_format($projects->sum('filtered_hours'), 1) }}h</th>
                      <th colspan="2"></th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            @else
              <div class="text-center py-5">
                <i class="ti tabler-folder-off text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-3">No projects found for this customer
                  @if($selectedYear !== 'lifetime')
                    in {{ $selectedYear }}
                  @endif
                </p>
              </div>
            @endif
          </div>

          <!-- Contracts Tab -->
          <div class="tab-pane fade" id="contracts-tab" role="tabpanel">
            @if($contracts->count() > 0)
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Contract</th>
                      <th>Product</th>
                      <th>Amount</th>
                      <th>Period</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($contracts as $contract)
                    <tr>
                      <td>
                        <div>
                          <h6 class="mb-0">{{ $contract->contract_number ?? 'Contract #' . $contract->id }}</h6>
                          <small class="text-muted">{{ \Illuminate\Support\Str::limit($contract->description ?? 'No description', 40) }}</small>
                        </div>
                      </td>
                      <td>
                        @if($contract->products->count() > 0)
                          @foreach($contract->products as $product)
                            <span class="badge bg-label-info">{{ $product->name }}</span>
                          @endforeach
                        @else
                          <span class="text-muted">-</span>
                        @endif
                      </td>
                      <td>
                        <span class="fw-medium">{{ number_format($contract->total_amount ?? 0, 0) }}</span>
                      </td>
                      <td>
                        @if($contract->start_date)
                          {{ $contract->start_date->format('M Y') }}
                          @if($contract->end_date)
                            - {{ $contract->end_date->format('M Y') }}
                          @endif
                        @else
                          <span class="text-muted">-</span>
                        @endif
                      </td>
                      <td>
                        <span class="badge bg-label-{{ $contract->status === 'active' ? 'success' : ($contract->status === 'completed' ? 'info' : 'secondary') }}">
                          {{ ucfirst($contract->status) }}
                        </span>
                      </td>
                      <td>
                        <a href="{{ route('accounting.income.contracts.show', $contract) }}" class="btn btn-sm btn-outline-primary">
                          <i class="ti tabler-eye"></i>
                        </a>
                      </td>
                    </tr>
                    @endforeach
                  </tbody>
                  <tfoot>
                    <tr class="table-light">
                      <th colspan="2" class="text-end">Total Value:</th>
                      <th>{{ number_format($contracts->sum('total_amount'), 0) }}</th>
                      <th colspan="3"></th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            @else
              <div class="text-center py-5">
                <i class="ti tabler-file-off text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-3">No contracts found for this customer
                  @if($selectedYear !== 'lifetime')
                    in {{ $selectedYear }}
                  @endif
                </p>
              </div>
            @endif
          </div>

          <!-- Invoices Tab -->
          <div class="tab-pane fade" id="invoices-tab" role="tabpanel">
            @if($invoices->count() > 0)
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Invoice #</th>
                      <th>Project</th>
                      <th>Date</th>
                      <th>Amount</th>
                      <th>Paid</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($invoices as $invoice)
                    <tr>
                      <td>
                        <span class="fw-medium">{{ $invoice->invoice_number }}</span>
                      </td>
                      <td>
                        @if($invoice->project)
                          <span class="badge bg-label-info">{{ $invoice->project->code }}</span>
                        @else
                          <span class="text-muted">-</span>
                        @endif
                      </td>
                      <td>
                        {{ $invoice->invoice_date->format('M j, Y') }}
                      </td>
                      <td>
                        <span class="fw-medium">{{ number_format($invoice->total_amount, 0) }}</span>
                      </td>
                      <td>
                        <span class="{{ $invoice->paid_amount >= $invoice->total_amount ? 'text-success' : 'text-warning' }}">
                          {{ number_format($invoice->paid_amount, 0) }}
                        </span>
                      </td>
                      <td>
                        <span class="badge {{ $invoice->status_badge_class }}">
                          {{ $invoice->status_display }}
                        </span>
                      </td>
                      <td>
                        <a href="{{ route('invoicing.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary">
                          <i class="ti tabler-eye"></i>
                        </a>
                      </td>
                    </tr>
                    @endforeach
                  </tbody>
                  <tfoot>
                    <tr class="table-light">
                      <th colspan="3" class="text-end">Totals:</th>
                      <th>{{ number_format($invoices->sum('total_amount'), 0) }}</th>
                      <th>{{ number_format($invoices->sum('paid_amount'), 0) }}</th>
                      <th colspan="2"></th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            @else
              <div class="text-center py-5">
                <i class="ti tabler-file-invoice text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-3">No invoices found for this customer
                  @if($selectedYear !== 'lifetime')
                    in {{ $selectedYear }}
                  @endif
                </p>
              </div>
            @endif
          </div>
        </div>
      </div>
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
@endsection
