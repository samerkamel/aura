@extends('layouts.layoutMaster')

@section('title', 'Linked Invoices - ' . $project->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Linked Invoices</h4>
            <p class="text-muted mb-0">{{ $project->name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('projects.finance.index', $project) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back to Finance
            </a>
            <a href="{{ route('invoicing.invoices.create', ['project_id' => $project->id, 'customer_id' => $project->customer_id]) }}" class="btn btn-info">
                <i class="ti ti-plus me-1"></i> New Invoice
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted">Total Invoiced</span>
                            <div class="d-flex align-items-center mt-2">
                                <h4 class="mb-0 me-2">{{ number_format($totals['total_invoiced'], 2) }}</h4>
                            </div>
                        </div>
                        <span class="avatar avatar-lg rounded">
                            <span class="avatar-initial bg-label-primary rounded"><i class="ti ti-file-invoice ti-26px"></i></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted">Paid</span>
                            <div class="d-flex align-items-center mt-2">
                                <h4 class="mb-0 me-2 text-success">{{ number_format($totals['total_paid'], 2) }}</h4>
                            </div>
                        </div>
                        <span class="avatar avatar-lg rounded">
                            <span class="avatar-initial bg-label-success rounded"><i class="ti ti-check ti-26px"></i></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted">Pending</span>
                            <div class="d-flex align-items-center mt-2">
                                <h4 class="mb-0 me-2 text-warning">{{ number_format($totals['total_pending'], 2) }}</h4>
                            </div>
                        </div>
                        <span class="avatar avatar-lg rounded">
                            <span class="avatar-initial bg-label-warning rounded"><i class="ti ti-clock ti-26px"></i></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted">Overdue</span>
                            <div class="d-flex align-items-center mt-2">
                                <h4 class="mb-0 me-2 text-danger">{{ number_format($totals['total_overdue'], 2) }}</h4>
                            </div>
                        </div>
                        <span class="avatar avatar-lg rounded">
                            <span class="avatar-initial bg-label-danger rounded"><i class="ti ti-alert-triangle ti-26px"></i></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Invoices ({{ $invoices->count() }})</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Due Date</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Paid</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>
                                <a href="{{ route('invoicing.invoices.show', $invoice) }}" class="fw-semibold">
                                    {{ $invoice->invoice_number }}
                                </a>
                            </td>
                            <td>{{ $invoice->customer?->display_name ?? '-' }}</td>
                            <td>{{ $invoice->invoice_date?->format('M d, Y') ?? '-' }}</td>
                            <td>
                                @if($invoice->due_date)
                                    {{ $invoice->due_date->format('M d, Y') }}
                                    @if($invoice->status !== 'paid' && $invoice->due_date->isPast())
                                        <span class="badge bg-danger">Overdue</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($invoice->total_amount ?? 0, 2) }}</td>
                            <td class="text-end">{{ number_format($invoice->paid_amount ?? 0, 2) }}</td>
                            <td class="text-center">
                                @php
                                    $statusColors = [
                                        'draft' => 'secondary',
                                        'sent' => 'info',
                                        'viewed' => 'primary',
                                        'partial' => 'warning',
                                        'paid' => 'success',
                                        'overdue' => 'danger',
                                        'cancelled' => 'dark',
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$invoice->status] ?? 'secondary' }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('invoicing.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="ti ti-file-off ti-lg text-muted mb-3 d-block"></i>
                                <p class="text-muted mb-3">No invoices linked to this project</p>
                                <a href="{{ route('invoicing.invoices.create', ['project_id' => $project->id, 'customer_id' => $project->customer_id]) }}" class="btn btn-info">
                                    <i class="ti ti-plus me-1"></i> Create Invoice
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
