@extends('layouts/layoutMaster')

@section('title', 'Customer Invoices')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Customer Invoices</h5>
                    <small class="text-muted">Manage customer invoices and payments</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('invoicing.invoices.link-projects') }}" class="btn btn-outline-success">
                        <i class="ti ti-link me-1"></i>Link to Projects
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ti ti-settings me-1"></i>Settings
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('invoicing.sequences.index') }}"><i class="ti ti-list-numbers me-2"></i>Invoice Sequences</a></li>
                            <li><a class="dropdown-item" href="{{ route('invoicing.internal-sequences.index') }}"><i class="ti ti-arrows-exchange me-2"></i>Internal Sequences</a></li>
                        </ul>
                    </div>
                    <a href="{{ route('invoicing.internal-transactions.index') }}" class="btn btn-outline-info">
                        <i class="ti ti-building-bank me-1"></i>Internal Transactions
                    </a>
                    <a href="{{ route('invoicing.invoices.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>New Invoice
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Filters -->
            <div class="card-body border-bottom">
                <form method="GET" id="filterForm" class="row g-3">
                    <!-- Preserve sort parameters -->
                    <input type="hidden" name="sort_by" value="{{ request('sort_by', 'invoice_date') }}">
                    <input type="hidden" name="sort_order" value="{{ request('sort_order', 'desc') }}">

                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select filter-select">
                            <option value="">All Statuses</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-select filter-select">
                            <option value="">All Customers</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" name="date_from" class="form-control filter-input" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" name="date_to" class="form-control filter-input" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Invoice number..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-filter me-1"></i>Filter
                            </button>
                            @if(request()->hasAny(['status', 'customer_id', 'date_from', 'date_to', 'search']))
                                <a href="{{ route('invoicing.invoices.index') }}" class="btn btn-outline-secondary">
                                    <i class="ti ti-x"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <!-- Invoice List -->
            <div class="card-body">
                @if($invoices->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                @php
                                    $currentSort = request('sort_by', 'invoice_date');
                                    $currentOrder = request('sort_order', 'desc');

                                    $getSortUrl = function($column) use ($currentSort, $currentOrder) {
                                        $newOrder = ($currentSort === $column && $currentOrder === 'desc') ? 'asc' : 'desc';
                                        return request()->fullUrlWithQuery(['sort_by' => $column, 'sort_order' => $newOrder]);
                                    };

                                    $getSortIcon = function($column) use ($currentSort, $currentOrder) {
                                        if ($currentSort !== $column) {
                                            return '<i class="ti ti-arrows-sort text-muted"></i>';
                                        }
                                        return $currentOrder === 'asc'
                                            ? '<i class="ti ti-sort-ascending text-primary"></i>'
                                            : '<i class="ti ti-sort-descending text-primary"></i>';
                                    };
                                @endphp
                                <tr>
                                    <th>
                                        <a href="{{ $getSortUrl('invoice_number') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1">
                                            Invoice # {!! $getSortIcon('invoice_number') !!}
                                        </a>
                                    </th>
                                    <th>Customer</th>
                                    <th>
                                        <a href="{{ $getSortUrl('invoice_date') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1">
                                            Date {!! $getSortIcon('invoice_date') !!}
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ $getSortUrl('due_date') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1">
                                            Due Date {!! $getSortIcon('due_date') !!}
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ $getSortUrl('total_amount') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1">
                                            Amount {!! $getSortIcon('total_amount') !!}
                                        </a>
                                    </th>
                                    <th>Project</th>
                                    <th>
                                        <a href="{{ $getSortUrl('status') }}" class="text-decoration-none text-dark d-flex align-items-center gap-1">
                                            Status {!! $getSortIcon('status') !!}
                                        </a>
                                    </th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoices as $invoice)
                                <tr data-invoice-id="{{ $invoice->id }}" data-total-amount="{{ $invoice->total_amount }}">
                                    <td>
                                        <a href="{{ route('invoicing.invoices.show', $invoice) }}" class="fw-semibold text-primary">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2">
                                                <span class="avatar-initial rounded-circle bg-label-primary">
                                                    {{ mb_substr($invoice->customer->display_name, 0, 2) }}
                                                </span>
                                            </div>
                                            <div>
                                                <a href="{{ route('administration.customers.show', $invoice->customer) }}" class="fw-medium text-body">
                                                    {{ $invoice->customer->display_name }}
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $invoice->invoice_date->format('M j, Y') }}</td>
                                    <td>
                                        @if($invoice->due_date)
                                            <span class="{{ $invoice->is_overdue ? 'text-danger fw-semibold' : '' }}">
                                                {{ $invoice->due_date->format('M j, Y') }}
                                            </span>
                                            @if($invoice->is_overdue)
                                                <br><small class="text-danger">{{ $invoice->days_overdue }} days overdue</small>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-semibold">{{ number_format($invoice->total_amount, 2) }} EGP</span>
                                        @if($invoice->paid_amount > 0 && $invoice->status !== 'paid')
                                            <br><small class="text-success">Paid: {{ number_format($invoice->paid_amount, 2) }} EGP</small>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            // Collect all unique projects (main project + line item projects)
                                            $linkedProjects = collect();

                                            // Add main project if exists
                                            if ($invoice->project) {
                                                $linkedProjects->push($invoice->project);
                                            }

                                            // Add line item projects
                                            foreach ($invoice->items as $item) {
                                                if ($item->project && !$linkedProjects->contains('id', $item->project->id)) {
                                                    $linkedProjects->push($item->project);
                                                }
                                            }
                                        @endphp
                                        @if($linkedProjects->isNotEmpty())
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($linkedProjects as $project)
                                                    <a href="{{ route('projects.show', $project) }}" class="badge bg-label-primary" title="{{ $project->name }}">
                                                        {{ $project->code }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $invoice->status_badge_class }}">
                                            {{ $invoice->status_display }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="ti ti-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="{{ route('invoicing.invoices.show', $invoice) }}">
                                                    <i class="ti ti-eye me-2"></i>View
                                                </a>
                                                @if($invoice->status === 'draft')
                                                    <a class="dropdown-item" href="{{ route('invoicing.invoices.edit', $invoice) }}">
                                                        <i class="ti ti-edit me-2"></i>Edit
                                                    </a>
                                                @endif
                                                @if(in_array($invoice->status, ['draft', 'sent']))
                                                    <button type="button" class="dropdown-item" onclick="markAsSent({{ $invoice->id }})">
                                                        <i class="ti ti-send me-2"></i>Mark as Sent
                                                    </button>
                                                @endif
                                                @if(in_array($invoice->status, ['sent', 'overdue']))
                                                    <button type="button" class="dropdown-item" onclick="markAsPaid({{ $invoice->id }})">
                                                        <i class="ti ti-check me-2"></i>Mark as Paid
                                                    </button>
                                                @endif
                                                <div class="dropdown-divider"></div>
                                                @if($invoice->status === 'draft')
                                                    <button type="button" class="dropdown-item text-danger" onclick="cancelInvoice({{ $invoice->id }})">
                                                        <i class="ti ti-x me-2"></i>Cancel
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="text-muted">
                            Showing {{ $invoices->firstItem() }} to {{ $invoices->lastItem() }} of {{ $invoices->total() }} invoices
                        </span>
                        {{ $invoices->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="ti ti-file-invoice display-6 text-muted"></i>
                        </div>
                        <h5 class="mb-2">No invoices found</h5>
                        <p class="text-muted">No invoices match your current filters.</p>
                        <a href="{{ route('invoicing.invoices.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i>Create First Invoice
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mt-4">
    <div class="col-lg-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge bg-label-primary p-2 me-2 rounded">
                            <i class="ti ti-file-invoice ti-sm"></i>
                        </span>
                    </div>
                    <div class="d-flex flex-column">
                        <small>Total Invoices</small>
                        <h6 class="mb-0">{{ $stats['total'] ?? 0 }}</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge bg-label-warning p-2 me-2 rounded">
                            <i class="ti ti-clock ti-sm"></i>
                        </span>
                    </div>
                    <div class="d-flex flex-column">
                        <small>Pending</small>
                        <h6 class="mb-0">{{ $stats['pending'] ?? 0 }}</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge bg-label-success p-2 me-2 rounded">
                            <i class="ti ti-check ti-sm"></i>
                        </span>
                    </div>
                    <div class="d-flex flex-column">
                        <small>Paid</small>
                        <h6 class="mb-0">{{ number_format($stats['paid_amount'] ?? 0, 0) }} EGP</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge bg-label-danger p-2 me-2 rounded">
                            <i class="ti ti-alert-circle ti-sm"></i>
                        </span>
                    </div>
                    <div class="d-flex flex-column">
                        <small>Overdue</small>
                        <h6 class="mb-0">{{ $stats['overdue'] ?? 0 }}</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mark as Paid Modal -->
<div class="modal fade" id="markAsPaidModal" tabindex="-1" aria-labelledby="markAsPaidModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="markAsPaidModalLabel">Mark Invoice as Paid</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="markAsPaidForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="paid_amount" class="form-label">Amount Paid (EGP) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="paid_amount" name="paid_amount" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="paid_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="paid_date" name="paid_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="account_id" class="form-label">Account to Receive Payment <span class="text-danger">*</span></label>
                        <select class="form-select" id="account_id" name="account_id" required>
                            <option value="">Select Account</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->formatted_balance }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="payment_notes" class="form-label">Payment Notes</label>
                        <textarea class="form-control" id="payment_notes" name="payment_notes" rows="3" placeholder="Optional notes about this payment..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Mark as Paid</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
// Auto-submit filter form on select change
document.querySelectorAll('.filter-select').forEach(function(select) {
    select.addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
});

// Auto-submit on date change
document.querySelectorAll('.filter-input').forEach(function(input) {
    input.addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
});

function markAsSent(invoiceId) {
    if (confirm('Mark this invoice as sent?')) {
        fetch(`/invoicing/invoices/${invoiceId}/send`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('An error occurred');
            console.error('Error:', error);
        });
    }
}

function markAsPaid(invoiceId) {
    // Get invoice details from the table row
    const invoiceRow = document.querySelector(`tr[data-invoice-id="${invoiceId}"]`);
    if (!invoiceRow) {
        alert('Invoice data not found');
        return;
    }

    const totalAmount = invoiceRow.getAttribute('data-total-amount');

    // Set up the modal form
    const form = document.getElementById('markAsPaidForm');
    const amountInput = document.getElementById('paid_amount');

    form.action = `/invoicing/invoices/${invoiceId}/pay`;
    amountInput.value = totalAmount;
    amountInput.setAttribute('max', totalAmount);

    // Show the modal
    new bootstrap.Modal(document.getElementById('markAsPaidModal')).show();
}

function cancelInvoice(invoiceId) {
    if (confirm('Cancel this invoice? This action cannot be undone.')) {
        fetch(`/invoicing/invoices/${invoiceId}/cancel`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('An error occurred');
            console.error('Error:', error);
        });
    }
}
</script>
@endsection