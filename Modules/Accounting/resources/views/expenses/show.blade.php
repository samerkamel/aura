@extends('layouts/layoutMaster')

@section('title', $expenseSchedule->expense_type === 'one_time' ? 'One-time Expense Details' : 'Expense Schedule Details')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">{{ $expenseSchedule->name }}</h5>
                    <small class="text-muted">
                        {{ $expenseSchedule->expense_type === 'one_time' ? 'One-time Expense Details' : 'Expense Schedule Details' }}
                    </small>
                </div>
                <div class="d-flex gap-2">
                    @if($expenseSchedule->expense_type === 'recurring')
                        <a href="{{ route('accounting.expenses.edit', $expenseSchedule) }}" class="btn btn-primary">
                            <i class="ti tabler-edit me-1"></i>Edit Schedule
                        </a>
                    @endif
                    <a href="{{ route('accounting.expenses.index') }}" class="btn btn-outline-secondary">
                        <i class="ti tabler-arrow-left me-1"></i>Back to Expenses
                    </a>
                    <a href="{{ route('accounting.expenses.paid') }}" class="btn btn-outline-info">
                        <i class="ti tabler-receipt me-1"></i>All Paid Expenses
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card-body">
                <div class="row">
                    <!-- Main Content -->
                    <div class="col-lg-8">
                        <!-- Basic Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Expense Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Expense Name</label>
                                        <div class="fw-medium">{{ $expenseSchedule->name }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Type</label>
                                        <div>
                                            <span class="badge bg-{{ $expenseSchedule->expense_type === 'one_time' ? 'warning' : 'info' }}">
                                                {{ $expenseSchedule->expense_type === 'one_time' ? 'One-time Expense' : 'Recurring Schedule' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Category</label>
                                        <div>
                                            <span class="badge" style="background-color: {{ $expenseSchedule->category->color }}20; color: {{ $expenseSchedule->category->color }}; border: 1px solid {{ $expenseSchedule->category->color }}40;">
                                                {{ $expenseSchedule->category->name }}
                                            </span>
                                            @if($expenseSchedule->subcategory)
                                                <br><small class="text-muted mt-1">{{ $expenseSchedule->subcategory->name }}</small>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Amount</label>
                                        <div class="h5 text-danger mb-0">{{ number_format($expenseSchedule->amount, 2) }} EGP</div>
                                    </div>
                                    @if($expenseSchedule->description)
                                        <div class="col-12">
                                            <label class="form-label text-muted">Description</label>
                                            <div>{{ $expenseSchedule->description }}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Schedule Information (for recurring expenses) -->
                        @if($expenseSchedule->expense_type === 'recurring')
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Schedule Details</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted">Frequency</label>
                                            <div>
                                                <span class="badge bg-info">
                                                    {{ ucfirst(str_replace('-', ' ', $expenseSchedule->frequency_type)) }}
                                                    @if($expenseSchedule->frequency_value > 1)
                                                        (Every {{ $expenseSchedule->frequency_value }})
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-muted">Monthly Equivalent</label>
                                            <div class="fw-medium text-warning">{{ number_format($expenseSchedule->monthly_equivalent_amount, 2) }} EGP</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-muted">Start Date</label>
                                            <div>{{ $expenseSchedule->start_date ? $expenseSchedule->start_date->format('F j, Y') : 'N/A' }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-muted">End Date</label>
                                            <div>{{ $expenseSchedule->end_date ? $expenseSchedule->end_date->format('F j, Y') : 'Ongoing' }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-muted">Status</label>
                                            <div>
                                                <span class="badge bg-{{ $expenseSchedule->is_active ? 'success' : 'secondary' }}">
                                                    {{ $expenseSchedule->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </div>
                                        </div>
                                        @if($expenseSchedule->skip_weekends || ($expenseSchedule->excluded_dates && count($expenseSchedule->excluded_dates) > 0))
                                            <div class="col-12">
                                                <label class="form-label text-muted">Options</label>
                                                <div class="d-flex gap-2">
                                                    @if($expenseSchedule->skip_weekends)
                                                        <span class="badge bg-info">
                                                            <i class="ti tabler-calendar-off me-1"></i>Skip Weekends
                                                        </span>
                                                    @endif
                                                    @if($expenseSchedule->excluded_dates && count($expenseSchedule->excluded_dates) > 0)
                                                        <span class="badge bg-warning">
                                                            <i class="ti tabler-calendar-x me-1"></i>{{ count($expenseSchedule->excluded_dates) }} Excluded Dates
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- One-time Expense Details -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Expense Date & Payment</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted">Expense Date</label>
                                            <div class="fw-medium">{{ $expenseSchedule->expense_date ? $expenseSchedule->expense_date->format('F j, Y') : 'Not set' }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-muted">Payment Status</label>
                                            <div>
                                                <span class="badge bg-{{ $expenseSchedule->payment_status === 'paid' ? 'success' : ($expenseSchedule->payment_status === 'pending' ? 'warning' : 'secondary') }}">
                                                    {{ ucfirst($expenseSchedule->payment_status) }}
                                                </span>
                                                @if($expenseSchedule->payment_status !== 'paid' && auth()->user()->can('manage-expenses'))
                                                    <button type="button" class="btn btn-sm btn-success ms-2" data-bs-toggle="modal" data-bs-target="#markAsPaidModal">
                                                        <i class="ti tabler-check me-1"></i>Mark as Paid
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                        @if($expenseSchedule->payment_status === 'paid')
                                            <div class="col-md-6">
                                                <label class="form-label text-muted">Paid Date</label>
                                                <div>{{ $expenseSchedule->paid_date ? $expenseSchedule->paid_date->format('F j, Y') : 'Not recorded' }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted">Paid Amount</label>
                                                <div class="fw-medium text-success">{{ number_format($expenseSchedule->paid_amount ?? $expenseSchedule->amount, 2) }} EGP</div>
                                            </div>
                                            @if($expenseSchedule->paidFromAccount)
                                                <div class="col-md-6">
                                                    <label class="form-label text-muted">Paid From Account</label>
                                                    <div>
                                                        <strong>{{ $expenseSchedule->paidFromAccount->name }}</strong>
                                                        <br><span class="badge {{ $expenseSchedule->paidFromAccount->type_badge_class }}">
                                                            {{ $expenseSchedule->paidFromAccount->type_display }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endif
                                            @if($expenseSchedule->payment_notes)
                                                <div class="col-12">
                                                    <label class="form-label text-muted">Payment Notes</label>
                                                    <div class="text-info">{{ $expenseSchedule->payment_notes }}</div>
                                                </div>
                                            @endif
                                            @if($expenseSchedule->hasPaymentAttachment())
                                                <div class="col-12">
                                                    <label class="form-label text-muted">Payment Attachment</label>
                                                    <div>
                                                        <a href="{{ $expenseSchedule->payment_attachment_url }}" class="btn btn-sm btn-outline-primary" title="Download {{ $expenseSchedule->payment_attachment_original_name }}">
                                                            <i class="ti tabler-paperclip me-1"></i>{{ $expenseSchedule->payment_attachment_original_name }}
                                                        </a>
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Sidebar -->
                    <div class="col-lg-4">
                        <!-- Statistics -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    {{ $expenseSchedule->expense_type === 'one_time' ? 'Expense Summary' : 'Schedule Statistics' }}
                                </h6>
                            </div>
                            <div class="card-body">
                                @if($expenseSchedule->expense_type === 'recurring')
                                    <div class="mb-3">
                                        <label class="form-label">Monthly Equivalent</label>
                                        <div class="h5 text-warning mb-0">{{ number_format($statistics['monthly_equivalent'], 2) }} EGP</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Yearly Projection</label>
                                        <div class="h6 text-info mb-0">{{ number_format($statistics['yearly_equivalent'], 2) }} EGP</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Upcoming Payments (6 months)</label>
                                        <div class="fw-medium">{{ $statistics['upcoming_count'] }} payments</div>
                                    </div>
                                    @if($statistics['next_occurrence'])
                                        <div class="mb-0">
                                            <label class="form-label">Next Payment</label>
                                            <div class="fw-medium text-primary">{{ $statistics['next_occurrence']->format('M j, Y') }}</div>
                                            <small class="text-muted">{{ $statistics['next_occurrence']->diffForHumans() }}</small>
                                        </div>
                                    @endif
                                @else
                                    <div class="mb-3">
                                        <label class="form-label">Total Amount</label>
                                        <div class="h5 text-danger mb-0">{{ number_format($expenseSchedule->amount, 2) }} EGP</div>
                                    </div>
                                    @if($expenseSchedule->payment_status === 'paid')
                                        <div class="mb-3">
                                            <label class="form-label">Amount Paid</label>
                                            <div class="h6 text-success mb-0">{{ number_format($expenseSchedule->paid_amount ?? $expenseSchedule->amount, 2) }} EGP</div>
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label">Payment Date</label>
                                            <div class="fw-medium">{{ $expenseSchedule->paid_date ? $expenseSchedule->paid_date->format('M j, Y') : 'Not recorded' }}</div>
                                        </div>
                                    @else
                                        <div class="mb-0">
                                            <label class="form-label">Due Date</label>
                                            <div class="fw-medium text-warning">{{ $expenseSchedule->expense_date ? $expenseSchedule->expense_date->format('M j, Y') : 'Not set' }}</div>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    @if($expenseSchedule->expense_type === 'recurring')
                                        <a href="{{ route('accounting.expenses.edit', $expenseSchedule) }}" class="btn btn-primary">
                                            <i class="ti tabler-edit me-2"></i>Edit Schedule
                                        </a>
                                        <form action="{{ route('accounting.expenses.toggle-status', $expenseSchedule) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-{{ $expenseSchedule->is_active ? 'warning' : 'success' }} w-100">
                                                <i class="ti tabler-{{ $expenseSchedule->is_active ? 'pause' : 'play' }} me-2"></i>
                                                {{ $expenseSchedule->is_active ? 'Deactivate' : 'Activate' }} Schedule
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('accounting.expenses.destroy', $expenseSchedule) }}" method="POST"
                                          onsubmit="return confirm('Are you sure you want to delete this {{ $expenseSchedule->expense_type === 'one_time' ? 'expense' : 'expense schedule' }}? This action cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger w-100">
                                            <i class="ti tabler-trash me-2"></i>Delete {{ $expenseSchedule->expense_type === 'one_time' ? 'Expense' : 'Schedule' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mark as Paid Modal -->
@if($expenseSchedule->payment_status !== 'paid' && auth()->user()->can('manage-expenses'))
<div class="modal fade" id="markAsPaidModal" tabindex="-1" aria-labelledby="markAsPaidModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('accounting.expenses.mark-as-paid', $expenseSchedule) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="markAsPaidModalLabel">Mark Expense as Paid</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Paid Amount (EGP)</label>
                                <div class="input-group">
                                    <span class="input-group-text">EGP</span>
                                    <input type="number" name="paid_amount" class="form-control" step="0.01" min="0.01" value="{{ $expenseSchedule->amount }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Paid Date</label>
                                <input type="date" name="paid_date" class="form-control" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Paid From Account</label>
                        <select name="paid_from_account_id" class="form-select" required>
                            <option value="">Select Account</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">
                                    {{ $account->name }} ({{ $account->account_number }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Notes</label>
                        <textarea name="payment_notes" class="form-control" rows="3" placeholder="Optional payment notes"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Attachment</label>
                        <input type="file" name="payment_attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx">
                        <small class="text-muted">Upload invoice or proof of payment (PDF, images, Word docs, max 10MB)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Mark as Paid</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection