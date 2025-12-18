@extends('layouts/layoutMaster')

@section('title', 'Invoice Payments')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Invoice Payments</h5>
                    <small class="text-muted">Track all invoice payments across business units</small>
                </div>
                <a href="{{ route('invoicing.invoices.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-file-invoice me-1"></i>Back to Invoices
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <form method="GET" action="{{ route('invoicing.payments.index') }}">
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">Business Unit</label>
                                    <select name="business_unit" class="form-select">
                                        <option value="">All Units</option>
                                        @foreach($businessUnits as $unit)
                                            <option value="{{ $unit->id }}" {{ request('business_unit') == $unit->id ? 'selected' : '' }}>
                                                {{ $unit->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Payment Method</label>
                                    <select name="payment_method" class="form-select">
                                        <option value="">All Methods</option>
                                        @foreach($paymentMethods as $value => $label)
                                            <option value="{{ $value }}" {{ request('payment_method') == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">From Date</label>
                                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">To Date</label>
                                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Search</label>
                                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Invoice #, reference, customer...">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-1">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ti ti-search"></i>
                                        </button>
                                        <a href="{{ route('invoicing.payments.index') }}" class="btn btn-outline-secondary">
                                            <i class="ti ti-x"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Summary Statistics -->
                <div class="row mb-4">
                    <div class="col-lg-4 col-sm-6">
                        <div class="card bg-label-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-label-success p-2 me-2 rounded">
                                            <i class="ti ti-currency-dollar ti-sm"></i>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <h5 class="mb-0">{{ number_format($totalPayments, 2) }}</h5>
                                        <small>Total Payments (EGP)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6">
                        <div class="card bg-label-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-label-info p-2 me-2 rounded">
                                            <i class="ti ti-list ti-sm"></i>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <h5 class="mb-0">{{ number_format($paymentsCount) }}</h5>
                                        <small>Total Records</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6">
                        <div class="card bg-label-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-label-primary p-2 me-2 rounded">
                                            <i class="ti ti-calendar ti-sm"></i>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <h5 class="mb-0">{{ $payments->count() }}</h5>
                                        <small>Showing This Page</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($payments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Payment Date</th>
                                    <th>Invoice</th>
                                    <th>Customer</th>
                                    <th>Business Unit</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                    <th>Notes</th>
                                    <th>Recorded By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payments as $payment)
                                <tr>
                                    <td>
                                        <span class="fw-semibold">{{ $payment->payment_date->format('M j, Y') }}</span>
                                        <br>
                                        <small class="text-muted">{{ $payment->created_at->format('g:i A') }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('invoicing.invoices.show', $payment->invoice) }}" class="text-primary">
                                            {{ $payment->invoice->invoice_number }}
                                        </a>
                                        <br>
                                        <small class="text-muted">{{ $payment->invoice->invoice_date->format('M j, Y') }}</small>
                                    </td>
                                    <td>
                                        <span class="fw-semibold">{{ $payment->invoice->customer->name }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-primary">{{ $payment->invoice->businessUnit->name }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-success">{{ number_format($payment->amount, 2) }} EGP</span>
                                    </td>
                                    <td>
                                        @if($payment->payment_method)
                                            <span class="badge bg-label-secondary">{{ $payment->payment_method_display }}</span>
                                        @else
                                            <span class="text-muted">Not specified</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->reference_number)
                                            <code class="text-primary">{{ $payment->reference_number }}</code>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->notes)
                                            <span class="text-truncate" style="max-width: 150px;" title="{{ $payment->notes }}">
                                                {{ \Illuminate\Support\Str::limit($payment->notes, 30) }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $payment->createdBy->name }}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="ti ti-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="{{ route('invoicing.invoices.show', $payment->invoice) }}">
                                                    <i class="ti ti-eye me-2"></i>View Invoice
                                                </a>
                                                @if(auth()->user()->can('manage-invoices'))
                                                    <div class="dropdown-divider"></div>
                                                    <button type="button" class="dropdown-item" onclick="editPayment({{ $payment->id }})">
                                                        <i class="ti ti-edit me-2"></i>Edit Payment
                                                    </button>
                                                    <button type="button" class="dropdown-item text-danger" onclick="deletePayment({{ $payment->id }})">
                                                        <i class="ti ti-trash me-2"></i>Delete Payment
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
                    @if($payments->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $payments->appends(request()->query())->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="ti ti-currency-dollar display-6 text-muted"></i>
                        </div>
                        <h5 class="mb-2">No payments found</h5>
                        <p class="text-muted">No invoice payments match your current filters.</p>
                        <a href="{{ route('invoicing.payments.index') }}" class="btn btn-outline-secondary">
                            <i class="ti ti-refresh me-1"></i>Clear Filters
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
function editPayment(paymentId) {
    // TODO: Implement edit payment modal
    alert('Edit payment functionality would be implemented here');
}

function deletePayment(paymentId) {
    if (confirm('Delete this payment? This action cannot be undone.')) {
        fetch(`/invoicing/invoices/payments/${paymentId}`, {
            method: 'DELETE',
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