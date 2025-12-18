@extends('layouts/layoutMaster')

@section('title', $project->name)

@section('page-style')
<style>
  .project-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 0.5rem;
    padding: 2rem;
    color: white;
    margin-bottom: 1.5rem;
  }
  .stat-card {
    text-align: center;
    padding: 1rem;
  }
  .stat-value {
    font-size: 1.75rem;
    font-weight: 700;
  }
  .project-code {
    background: rgba(255,255,255,0.2);
    padding: 0.25rem 0.75rem;
    border-radius: 0.25rem;
    font-family: monospace;
    font-size: 1rem;
  }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @if (session('success'))
    <div class="alert alert-success alert-dismissible mb-4" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- Project Header -->
  <div class="project-header">
    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <span class="project-code">{{ $project->code }}</span>
        @if($project->is_active)
          <span class="badge bg-success ms-2">Active</span>
        @else
          <span class="badge bg-secondary ms-2">Inactive</span>
        @endif
        @if($project->needs_monthly_report)
          <span class="badge bg-info ms-2"><i class="ti ti-report me-1"></i>Monthly Report</span>
        @endif
      </div>
      <div>
        <a href="{{ route('projects.edit', $project) }}" class="btn btn-light btn-sm">
          <i class="ti ti-pencil me-1"></i>Edit
        </a>
        <a href="{{ route('projects.index') }}" class="btn btn-outline-light btn-sm">
          <i class="ti ti-arrow-left me-1"></i>Back
        </a>
      </div>
    </div>
    <h2 class="mb-2">{{ $project->name }}</h2>
    @if($project->customer)
      <p class="mb-0 opacity-75">
        <i class="ti ti-building me-1"></i>{{ $project->customer->display_name }}
      </p>
    @endif
    @if($project->description)
      <p class="mb-0 mt-2 opacity-75">{{ $project->description }}</p>
    @endif
  </div>

  <!-- Stats Cards -->
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card h-100">
        <div class="card-body stat-card">
          <div class="stat-value text-primary">{{ number_format($totalHours, 1) }}</div>
          <small class="text-muted">Hours Logged (This Period)</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card h-100">
        <div class="card-body stat-card">
          <div class="stat-value text-success">EGP {{ number_format($totalContractValue, 0) }}</div>
          <small class="text-muted">Total Contract Value</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card h-100">
        <div class="card-body stat-card">
          <div class="stat-value text-info">EGP {{ number_format($totalPaid, 0) }}</div>
          <small class="text-muted">Total Paid</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card h-100">
        <div class="card-body stat-card">
          <div class="stat-value {{ $totalRemaining > 0 ? 'text-warning' : 'text-success' }}">EGP {{ number_format($totalRemaining, 0) }}</div>
          <small class="text-muted">Remaining Balance</small>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- Contracts/Invoices Section -->
    <div class="col-lg-6 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="ti ti-file-invoice me-2 text-primary"></i>Contracts & Invoices
          </h5>
          <span class="badge bg-primary">{{ $project->invoices->count() }}</span>
        </div>
        <div class="card-body">
          @if($project->invoices->count() > 0)
            <div class="table-responsive">
              <table class="table table-sm table-hover">
                <thead class="table-light">
                  <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">Paid</th>
                    <th class="text-center">Status</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($project->invoices as $invoice)
                    <tr>
                      <td>
                        <a href="{{ route('invoicing.invoices.show', $invoice) }}" class="fw-semibold">
                          {{ $invoice->invoice_number }}
                        </a>
                      </td>
                      <td>{{ $invoice->invoice_date->format('M d, Y') }}</td>
                      <td class="text-end">EGP {{ number_format($invoice->total_amount, 0) }}</td>
                      <td class="text-end">EGP {{ number_format($invoice->paid_amount, 0) }}</td>
                      <td class="text-center">
                        <span class="badge {{ $invoice->status_badge_class }}">
                          {{ $invoice->status_display }}
                        </span>
                      </td>
                    </tr>
                    @if($invoice->payments->count() > 0)
                      @foreach($invoice->payments as $payment)
                        <tr class="table-light">
                          <td colspan="2" class="ps-4 text-muted small">
                            <i class="ti ti-receipt me-1"></i>Payment - {{ $payment->payment_date->format('M d, Y') }}
                          </td>
                          <td></td>
                          <td class="text-end text-success small">+EGP {{ number_format($payment->amount, 0) }}</td>
                          <td class="text-center">
                            <span class="badge bg-label-success">{{ ucfirst($payment->payment_method ?? 'Other') }}</span>
                          </td>
                        </tr>
                      @endforeach
                    @endif
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <div class="text-center py-4">
              <i class="ti ti-file-off display-6 text-muted mb-3 d-block"></i>
              <p class="text-muted mb-3">No contracts linked to this project yet.</p>
              <a href="{{ route('invoicing.invoices.create') }}" class="btn btn-sm btn-primary">
                <i class="ti ti-plus me-1"></i>Create Invoice
              </a>
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Hours by Employee Section -->
    <div class="col-lg-6 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-3">
            <i class="ti ti-clock me-2 text-info"></i>Hours by Employee
          </h5>
          <!-- Date Filter -->
          <form action="{{ route('projects.show', $project) }}" method="GET" class="row g-2">
            <div class="col-5">
              <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
            </div>
            <div class="col-5">
              <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
            </div>
            <div class="col-2">
              <button type="submit" class="btn btn-sm btn-primary w-100">
                <i class="ti ti-filter"></i>
              </button>
            </div>
          </form>
        </div>
        <div class="card-body">
          @if($worklogsByEmployee->count() > 0)
            <div class="table-responsive">
              <table class="table table-sm">
                <thead class="table-light">
                  <tr>
                    <th>Employee</th>
                    <th class="text-end">Hours</th>
                    <th class="text-end">Entries</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($worklogsByEmployee as $employeeId => $data)
                    <tr>
                      <td>
                        @if($data['employee'])
                          <a href="{{ route('hr.employees.show', $data['employee']) }}">
                            {{ $data['employee']->name }}
                          </a>
                        @else
                          <span class="text-muted">Unknown (ID: {{ $employeeId }})</span>
                        @endif
                      </td>
                      <td class="text-end fw-semibold">{{ number_format($data['total_hours'], 1) }}h</td>
                      <td class="text-end">{{ $data['entries']->count() }}</td>
                    </tr>
                  @endforeach
                </tbody>
                <tfoot class="table-light">
                  <tr>
                    <th>Total</th>
                    <th class="text-end">{{ number_format($totalHours, 1) }}h</th>
                    <th class="text-end">{{ $worklogs->count() }}</th>
                  </tr>
                </tfoot>
              </table>
            </div>
          @else
            <div class="text-center py-4">
              <i class="ti ti-clock-off display-6 text-muted mb-3 d-block"></i>
              <p class="text-muted">No hours logged for this period.</p>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Worklogs Section -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i class="ti ti-list me-2 text-warning"></i>Recent Work Entries
      </h5>
      <span class="badge bg-secondary">{{ $worklogs->count() }} entries</span>
    </div>
    <div class="card-body">
      @if($worklogs->count() > 0)
        <div class="table-responsive">
          <table class="table table-sm table-hover">
            <thead class="table-light">
              <tr>
                <th>Date</th>
                <th>Employee</th>
                <th>Issue</th>
                <th>Description</th>
                <th class="text-end">Hours</th>
              </tr>
            </thead>
            <tbody>
              @foreach($worklogs->take(20) as $worklog)
                <tr>
                  <td>{{ $worklog->worklog_started->format('M d, Y') }}</td>
                  <td>
                    @if($worklog->employee)
                      {{ $worklog->employee->name }}
                    @else
                      <span class="text-muted">{{ $worklog->jira_author_name ?? 'Unknown' }}</span>
                    @endif
                  </td>
                  <td>
                    <span class="badge bg-label-primary">{{ $worklog->issue_key }}</span>
                  </td>
                  <td>{{ Str::limit($worklog->issue_summary ?? $worklog->comment, 50) }}</td>
                  <td class="text-end fw-semibold">{{ number_format($worklog->time_spent_hours, 1) }}h</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @if($worklogs->count() > 20)
          <div class="text-center mt-3">
            <small class="text-muted">Showing 20 of {{ $worklogs->count() }} entries</small>
          </div>
        @endif
      @else
        <div class="text-center py-4">
          <i class="ti ti-clock-off display-6 text-muted mb-3 d-block"></i>
          <p class="text-muted">No work entries found for this period.</p>
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
