@extends('layouts/layoutMaster')

@section('title', 'Income Schedule Details')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Income Schedule Details</h5>
                    <small class="text-muted">{{ $incomeSchedule->name }} - {{ $incomeSchedule->contract->client_name }}</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('accounting.income.contracts.show', $incomeSchedule->contract) }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Back to Contract
                    </a>
                    <a href="{{ route('accounting.income.schedules.edit', $incomeSchedule) }}" class="btn btn-primary">
                        <i class="ti ti-edit me-1"></i>Edit Schedule
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card-body">
                <div class="row">
                    <!-- Schedule Information -->
                    <div class="col-lg-8">
                        <!-- Basic Info -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Schedule Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Schedule Name</label>
                                        <div class="h6">{{ $incomeSchedule->name }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Contract</label>
                                        <div class="h6">
                                            <a href="{{ route('accounting.income.contracts.show', $incomeSchedule->contract) }}" class="text-decoration-none">
                                                {{ $incomeSchedule->contract->contract_number }} - {{ $incomeSchedule->contract->client_name }}
                                            </a>
                                        </div>
                                    </div>
                                    @if($incomeSchedule->description)
                                        <div class="col-12">
                                            <label class="form-label">Description</label>
                                            <div class="text-muted">{{ $incomeSchedule->description }}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Financial Details -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Financial Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Payment Amount</label>
                                        <div class="h5 text-success mb-0">{{ number_format($incomeSchedule->amount, 2) }} EGP</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Frequency</label>
                                        <div class="h6 mb-0">
                                            Every {{ $incomeSchedule->frequency_value }} {{ $incomeSchedule->frequency_type }}{{ $incomeSchedule->frequency_value > 1 ? 's' : '' }}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Monthly Equivalent</label>
                                        <div class="h6 text-info mb-0">{{ number_format($statistics['monthly_equivalent'], 2) }} EGP</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Yearly Equivalent</label>
                                        <div class="h6 text-warning mb-0">{{ number_format($statistics['yearly_equivalent'], 2) }} EGP</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Schedule Details -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Schedule Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Start Date</label>
                                        <div class="h6">{{ $incomeSchedule->start_date->format('F j, Y') }}</div>
                                        <small class="text-muted">{{ $incomeSchedule->start_date->diffForHumans() }}</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">End Date</label>
                                        @if($incomeSchedule->end_date)
                                            <div class="h6">{{ $incomeSchedule->end_date->format('F j, Y') }}</div>
                                            <small class="text-muted">{{ $incomeSchedule->end_date->diffForHumans() }}</small>
                                        @else
                                            <div class="h6 text-info">Ongoing</div>
                                            <small class="text-muted">No end date specified</small>
                                        @endif
                                    </div>

                                    @if($statistics['next_occurrence'])
                                        <div class="col-md-6">
                                            <label class="form-label">Next Payment</label>
                                            <div class="h6">{{ $statistics['next_occurrence']->format('F j, Y') }}</div>
                                            <small class="text-muted">{{ $statistics['next_occurrence']->diffForHumans() }}</small>
                                        </div>
                                    @endif

                                    <div class="col-md-6">
                                        <label class="form-label">Options</label>
                                        <div>
                                            @if($incomeSchedule->skip_weekends)
                                                <span class="badge bg-info me-1">Skip Weekends</span>
                                            @endif
                                            @if($incomeSchedule->excluded_dates && count($incomeSchedule->excluded_dates) > 0)
                                                <span class="badge bg-warning">{{ count($incomeSchedule->excluded_dates) }} Excluded Dates</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Excluded Dates -->
                        @if($incomeSchedule->excluded_dates && count($incomeSchedule->excluded_dates) > 0)
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Excluded Dates</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        @foreach($incomeSchedule->excluded_dates as $excludedDate)
                                            <div class="col-md-3 mb-2">
                                                <span class="badge bg-light text-dark">{{ \Carbon\Carbon::parse($excludedDate)->format('M j, Y') }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Upcoming Occurrences -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Upcoming Payments (Next 6 Months)</h6>
                            </div>
                            <div class="card-body">
                                @if(count($upcomingOccurrences) > 0)
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Payment Date</th>
                                                    <th>Amount</th>
                                                    <th>Days Until Payment</th>
                                                    <th>Month</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($upcomingOccurrences as $occurrence)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $occurrence->format('F j, Y') }}</strong><br>
                                                            <small class="text-muted">{{ $occurrence->format('l') }}</small>
                                                        </td>
                                                        <td class="text-success">
                                                            <strong>{{ number_format($incomeSchedule->amount, 2) }} EGP</strong>
                                                        </td>
                                                        <td>
                                                            @if($occurrence->isPast())
                                                                <span class="text-danger">{{ $occurrence->diffInDays(now()) }} days ago</span>
                                                            @else
                                                                <span class="text-info">{{ now()->diffInDays($occurrence) }} days</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-light text-dark">{{ $occurrence->format('M Y') }}</span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="mt-3">
                                        <div class="row text-center">
                                            <div class="col-md-4">
                                                <div class="h6 text-muted">Total Payments</div>
                                                <div class="h5 text-primary">{{ count($upcomingOccurrences) }}</div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="h6 text-muted">Total Amount</div>
                                                <div class="h5 text-success">{{ number_format($incomeSchedule->amount * count($upcomingOccurrences), 2) }} EGP</div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="h6 text-muted">Average Monthly</div>
                                                <div class="h5 text-info">{{ number_format(($incomeSchedule->amount * count($upcomingOccurrences)) / 6, 2) }} EGP</div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center py-4">
                                        <i class="ti ti-calendar-off text-muted mb-3" style="font-size: 3rem;"></i>
                                        <h6>No Upcoming Payments</h6>
                                        <p class="text-muted">This schedule has no payments scheduled for the next 6 months.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Side Panel -->
                    <div class="col-lg-4">
                        <!-- Status Card -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Status & Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Current Status</label>
                                    <div>
                                        <span class="badge bg-{{ $incomeSchedule->is_active ? 'success' : 'secondary' }}">
                                            {{ $incomeSchedule->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <form action="{{ route('accounting.income.schedules.toggle-status', $incomeSchedule) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline-{{ $incomeSchedule->is_active ? 'warning' : 'success' }} btn-sm w-100">
                                            <i class="ti ti-{{ $incomeSchedule->is_active ? 'pause' : 'play' }} me-1"></i>
                                            {{ $incomeSchedule->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>

                                    <a href="{{ route('accounting.income.schedules.edit', $incomeSchedule) }}" class="btn btn-primary btn-sm">
                                        <i class="ti ti-edit me-1"></i>Edit Schedule
                                    </a>

                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDelete()">
                                        <i class="ti ti-trash me-1"></i>Delete Schedule
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Statistics</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-muted">Upcoming Payments</span>
                                    <span class="badge bg-info">{{ $statistics['upcoming_count'] }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-muted">Monthly Equivalent</span>
                                    <span class="text-success">{{ number_format($statistics['monthly_equivalent'], 2) }} EGP</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-muted">Yearly Equivalent</span>
                                    <span class="text-warning">{{ number_format($statistics['yearly_equivalent'], 2) }} EGP</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Created</span>
                                    <small class="text-muted">{{ $incomeSchedule->created_at->format('M j, Y') }}</small>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Updated</span>
                                    <small class="text-muted">{{ $incomeSchedule->updated_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        </div>

                        <!-- Contract Details -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Related Contract</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Contract Number</label>
                                    <div class="h6">{{ $incomeSchedule->contract->contract_number }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Client Name</label>
                                    <div class="h6">{{ $incomeSchedule->contract->client_name }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Contract Total</label>
                                    <div class="h6 text-success">{{ number_format($incomeSchedule->contract->total_amount, 2) }} EGP</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Contract Status</label>
                                    <div>
                                        <span class="badge bg-{{ $incomeSchedule->contract->status === 'active' ? 'success' : ($incomeSchedule->contract->status === 'completed' ? 'info' : ($incomeSchedule->contract->status === 'cancelled' ? 'danger' : 'warning')) }}">
                                            {{ \Illuminate\Support\Str::ucfirst($incomeSchedule->contract->status) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <a href="{{ route('accounting.income.contracts.show', $incomeSchedule->contract) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="ti ti-file-text me-1"></i>View Contract
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteScheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Delete Income Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="ti ti-alert-triangle text-warning" style="font-size: 3rem;"></i>
                </div>
                <h6 class="text-center">Are you sure you want to delete this income schedule?</h6>
                <p class="text-center text-muted">
                    Schedule: <strong>{{ $incomeSchedule->name }}</strong><br>
                    Contract: <strong>{{ $incomeSchedule->contract->contract_number }}</strong>
                </p>
                <div class="alert alert-warning">
                    <i class="ti ti-info-circle me-2"></i>
                    This action cannot be undone. The schedule will be removed from cash flow projections.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('accounting.income.schedules.destroy', $incomeSchedule) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="ti ti-trash me-1"></i>Delete Schedule
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete() {
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteScheduleModal'));
    deleteModal.show();
}
</script>
@endsection