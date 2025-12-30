@use('Illuminate\Support\Str')
@extends('layouts.layoutMaster')

@section('title', 'Revenue Management - ' . $project->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Revenue Management</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projects</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('projects.finance.index', $project) }}">Finance</a></li>
                    <li class="breadcrumb-item active">Revenue</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRevenueModal">
            <i class="ti ti-plus me-1"></i> Record Revenue
        </button>
    </div>

    <!-- Summary -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-lg bg-label-primary rounded me-3">
                            <i class="ti ti-cash ti-26px"></i>
                        </span>
                        <div>
                            <span class="text-muted d-block">Total Revenue</span>
                            <h5 class="mb-0">{{ number_format($breakdown['total'], 2) }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-lg bg-label-success rounded me-3">
                            <i class="ti ti-check ti-26px"></i>
                        </span>
                        <div>
                            <span class="text-muted d-block">Received</span>
                            <h5 class="mb-0">{{ number_format($breakdown['received'], 2) }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-lg bg-label-warning rounded me-3">
                            <i class="ti ti-clock ti-26px"></i>
                        </span>
                        <div>
                            <span class="text-muted d-block">Pending</span>
                            <h5 class="mb-0">{{ number_format($breakdown['pending'], 2) }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-lg bg-label-info rounded me-3">
                            <i class="ti ti-percentage ti-26px"></i>
                        </span>
                        <div>
                            <span class="text-muted d-block">Collection Rate</span>
                            <h5 class="mb-0">{{ $breakdown['collection_rate'] }}%</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Revenue Type</label>
                    <select name="revenue_type" class="form-select">
                        <option value="">All Types</option>
                        @foreach($revenueTypes as $key => $label)
                            <option value="{{ $key }}" {{ request('revenue_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="{{ route('projects.finance.revenues', $project) }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Revenue Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Received</th>
                        <th class="text-end">Outstanding</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($revenues as $revenue)
                        <tr>
                            <td>{{ $revenue->revenue_date->format('M d, Y') }}</td>
                            <td>
                                <span class="badge bg-{{ $revenue->revenue_type_color }}">
                                    {{ $revenue->revenue_type_label }}
                                </span>
                            </td>
                            <td>
                                {{ $revenue->description }}
                                @if($revenue->notes)
                                    <br><small class="text-muted">{{ Str::limit($revenue->notes, 50) }}</small>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($revenue->amount, 2) }}</td>
                            <td class="text-end text-success">{{ number_format($revenue->amount_received, 2) }}</td>
                            <td class="text-end {{ $revenue->outstanding_amount > 0 ? 'text-warning' : '' }}">
                                {{ number_format($revenue->outstanding_amount, 2) }}
                            </td>
                            <td>
                                @if($revenue->due_date)
                                    <span class="{{ $revenue->isOverdue() ? 'text-danger' : '' }}">
                                        {{ $revenue->due_date->format('M d, Y') }}
                                    </span>
                                    @if($revenue->isOverdue())
                                        <br><small class="text-danger">{{ $revenue->due_date->diffForHumans() }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-label-{{ $revenue->status_color }}">
                                    {{ $revenue->status_label }}
                                </span>
                                @if($revenue->payment_percentage > 0 && $revenue->payment_percentage < 100)
                                    <br><small class="text-muted">{{ $revenue->payment_percentage }}% paid</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        @if($revenue->outstanding_amount > 0)
                                            <a class="dropdown-item" href="#"
                                               data-bs-toggle="modal"
                                               data-bs-target="#recordPaymentModal"
                                               data-revenue="{{ json_encode($revenue) }}">
                                                <i class="ti ti-cash me-1"></i> Record Payment
                                            </a>
                                        @endif
                                        <a class="dropdown-item" href="#"
                                           data-bs-toggle="modal"
                                           data-bs-target="#editRevenueModal"
                                           data-revenue="{{ json_encode($revenue) }}">
                                            <i class="ti ti-pencil me-1"></i> Edit
                                        </a>
                                        <form action="{{ route('projects.finance.revenues.destroy', [$project, $revenue]) }}"
                                              method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger"
                                                    onclick="return confirm('Delete this revenue entry?')">
                                                <i class="ti ti-trash me-1"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="ti ti-cash-off ti-lg text-muted"></i>
                                <p class="text-muted mb-0 mt-2">No revenue recorded yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($revenues->hasPages())
            <div class="card-footer">
                {{ $revenues->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <!-- Add Revenue Modal -->
    <div class="modal fade" id="addRevenueModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('projects.finance.revenues.store', $project) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Record Revenue</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Revenue Type</label>
                                <select name="revenue_type" class="form-select" required>
                                    @foreach($revenueTypes as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    @foreach($statuses as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control" required placeholder="Revenue description...">
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" name="amount" class="form-control" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Revenue Date</label>
                                <input type="date" name="revenue_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Due Date (Optional)</label>
                                <input type="date" name="due_date" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Amount Received (Optional)</label>
                                <input type="number" name="amount_received" class="form-control" step="0.01" min="0" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Received Date (Optional)</label>
                                <input type="date" name="received_date" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes (Optional)</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Record Revenue</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Revenue Modal -->
    <div class="modal fade" id="editRevenueModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editRevenueForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Revenue</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Revenue Type</label>
                                <select name="revenue_type" id="edit_revenue_type" class="form-select" required>
                                    @foreach($revenueTypes as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="edit_revenue_status" class="form-select" required>
                                    @foreach($statuses as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" id="edit_revenue_description" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" name="amount" id="edit_revenue_amount" class="form-control" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Revenue Date</label>
                                <input type="date" name="revenue_date" id="edit_revenue_date" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Due Date</label>
                                <input type="date" name="due_date" id="edit_revenue_due_date" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Amount Received</label>
                                <input type="number" name="amount_received" id="edit_revenue_received" class="form-control" step="0.01" min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Received Date</label>
                                <input type="date" name="received_date" id="edit_revenue_received_date" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" id="edit_revenue_notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Revenue</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Record Payment Modal -->
    <div class="modal fade" id="recordPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="recordPaymentForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Record Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <strong>Outstanding Amount:</strong> <span id="payment_outstanding"></span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Amount</label>
                            <input type="number" name="amount" id="payment_amount" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Date</label>
                            <input type="date" name="received_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Record Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Edit Revenue Modal
            const editModal = document.getElementById('editRevenueModal');
            editModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const revenue = JSON.parse(button.dataset.revenue);

                document.getElementById('editRevenueForm').action =
                    '{{ route("projects.finance.revenues.update", [$project, ":id"]) }}'.replace(':id', revenue.id);

                document.getElementById('edit_revenue_type').value = revenue.revenue_type;
                document.getElementById('edit_revenue_status').value = revenue.status;
                document.getElementById('edit_revenue_description').value = revenue.description;
                document.getElementById('edit_revenue_amount').value = revenue.amount;
                document.getElementById('edit_revenue_date').value = revenue.revenue_date.split('T')[0];
                document.getElementById('edit_revenue_due_date').value = revenue.due_date ? revenue.due_date.split('T')[0] : '';
                document.getElementById('edit_revenue_received').value = revenue.amount_received || 0;
                document.getElementById('edit_revenue_received_date').value = revenue.received_date ? revenue.received_date.split('T')[0] : '';
                document.getElementById('edit_revenue_notes').value = revenue.notes || '';
            });

            // Record Payment Modal
            const paymentModal = document.getElementById('recordPaymentModal');
            paymentModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const revenue = JSON.parse(button.dataset.revenue);
                const outstanding = revenue.amount - revenue.amount_received;

                document.getElementById('recordPaymentForm').action =
                    '{{ route("projects.finance.revenues.record-payment", [$project, ":id"]) }}'.replace(':id', revenue.id);

                document.getElementById('payment_outstanding').textContent = outstanding.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                document.getElementById('payment_amount').max = outstanding;
                document.getElementById('payment_amount').value = outstanding;
            });
        });
    </script>
@endsection
