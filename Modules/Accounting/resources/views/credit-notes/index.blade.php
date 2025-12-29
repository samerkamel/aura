@extends('layouts/layoutMaster')

@section('title', 'Credit Notes')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title mb-1">Total Credit Notes</h6>
                                <h4 class="mb-0">{{ $statistics['total_credit_notes'] }}</h4>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial rounded bg-label-primary">
                                    <i class="ti ti-receipt-refund ti-md"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title mb-1">Draft</h6>
                                <h4 class="mb-0">{{ $statistics['draft_count'] }}</h4>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial rounded bg-label-secondary">
                                    <i class="ti ti-pencil ti-md"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title mb-1">Open</h6>
                                <h4 class="mb-0">{{ $statistics['open_count'] }}</h4>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial rounded bg-label-success">
                                    <i class="ti ti-credit-card ti-md"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title mb-1">Available Credits</h6>
                                <h4 class="mb-0">EGP {{ number_format($statistics['available_credits'], 0) }}</h4>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial rounded bg-label-warning">
                                    <i class="ti ti-currency-dollar ti-md"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Credit Notes Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Credit Notes Management</h5>
                    <small class="text-muted">Create and manage customer credit notes</small>
                </div>
                <a href="{{ route('accounting.credit-notes.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i>New Credit Note
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Filters -->
            <div class="card-body pb-0">
                <form action="{{ route('accounting.credit-notes.index') }}" method="GET" class="row g-3">
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="all">All Statuses</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                            <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                            <option value="void" {{ request('status') == 'void' ? 'selected' : '' }}>Void</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="customer_id" class="form-select">
                            <option value="">All Customers</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="from_date" class="form-control" placeholder="From Date"
                               value="{{ request('from_date') }}">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="to_date" class="form-control" placeholder="To Date"
                               value="{{ request('to_date') }}">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="search" class="form-control" placeholder="Search..."
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="ti ti-filter me-1"></i>Filter
                        </button>
                        <a href="{{ route('accounting.credit-notes.index') }}" class="btn btn-outline-secondary">
                            <i class="ti ti-x"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead class="table-light">
                        <tr>
                            <th>Credit Note #</th>
                            <th>Customer</th>
                            <th>Invoice</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Remaining</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($creditNotes as $creditNote)
                            <tr>
                                <td>
                                    <a href="{{ route('accounting.credit-notes.show', $creditNote) }}" class="fw-medium">
                                        {{ $creditNote->credit_note_number }}
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-medium">{{ $creditNote->client_name }}</span>
                                        @if($creditNote->customer)
                                            <small class="text-muted">{{ $creditNote->customer->name }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($creditNote->invoice)
                                        <a href="{{ route('invoicing.invoices.show', $creditNote->invoice) }}" class="badge bg-label-info">
                                            {{ $creditNote->invoice->invoice_number }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $creditNote->credit_note_date->format('M d, Y') }}</td>
                                <td class="fw-medium">EGP {{ number_format($creditNote->total, 2) }}</td>
                                <td>
                                    @if($creditNote->remaining_credits > 0)
                                        <span class="text-success fw-medium">EGP {{ number_format($creditNote->remaining_credits, 2) }}</span>
                                    @else
                                        <span class="text-muted">EGP 0.00</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $creditNote->status_color }}">
                                        {{ $creditNote->status_label }}
                                    </span>
                                    @if($creditNote->status === 'open' && $creditNote->usage_percentage > 0)
                                        <small class="text-muted d-block">{{ $creditNote->usage_percentage }}% used</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="{{ route('accounting.credit-notes.show', $creditNote) }}">
                                                <i class="ti ti-eye me-1"></i> View
                                            </a>
                                            @if($creditNote->canBeEdited())
                                                <a class="dropdown-item" href="{{ route('accounting.credit-notes.edit', $creditNote) }}">
                                                    <i class="ti ti-pencil me-1"></i> Edit
                                                </a>
                                            @endif
                                            <a class="dropdown-item" href="{{ route('accounting.credit-notes.pdf', $creditNote) }}">
                                                <i class="ti ti-file-download me-1"></i> Download PDF
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            @if($creditNote->canBeOpened())
                                                <form action="{{ route('accounting.credit-notes.open', $creditNote) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="ti ti-credit-card me-1"></i> Mark as Open
                                                    </button>
                                                </form>
                                            @endif
                                            @if($creditNote->canBeVoided())
                                                <form action="{{ route('accounting.credit-notes.void', $creditNote) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to void this credit note?')">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="ti ti-ban me-1"></i> Void
                                                    </button>
                                                </form>
                                            @endif
                                            @if($creditNote->status === 'draft')
                                                <div class="dropdown-divider"></div>
                                                <form action="{{ route('accounting.credit-notes.destroy', $creditNote) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this credit note?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="ti ti-trash me-1"></i> Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="ti ti-receipt-off" style="font-size: 3rem;"></i>
                                        <p class="mt-2 mb-0">No credit notes found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($creditNotes->hasPages())
                <div class="card-footer">
                    {{ $creditNotes->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
