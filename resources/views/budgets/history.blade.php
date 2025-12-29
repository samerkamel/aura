@extends('layouts/layoutMaster')

@section('title', 'Budget History - ' . $budget->product->name)

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Budget History</h5>
                    <small class="text-muted">
                        {{ $budget->product->name }} ({{ $budget->product->code }}) -
                        {{ $budget->businessUnit->name }} - {{ $budget->budget_year }}
                    </small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('budgets.index', $budget->businessUnit) }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Back to Budgets
                    </a>
                </div>
            </div>

            <div class="card-body">
                <!-- Budget Summary -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border border-primary">
                            <div class="card-body text-center p-3">
                                <i class="ti ti-wallet text-primary mb-2" style="font-size: 1.5rem;"></i>
                                <h6 class="card-title text-primary mb-1">Budget Amount</h6>
                                <h5 class="mb-0">{{ number_format($budget->budget_amount, 0) }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border border-warning">
                            <div class="card-body text-center p-3">
                                <i class="ti ti-credit-card text-warning mb-2" style="font-size: 1.5rem;"></i>
                                <h6 class="card-title text-warning mb-1">Allocated</h6>
                                <h5 class="mb-0">{{ number_format($budget->allocated_amount, 0) }}</h5>
                                <small class="text-muted">{{ $budget->allocation_percentage }}%</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border border-danger">
                            <div class="card-body text-center p-3">
                                <i class="ti ti-minus text-danger mb-2" style="font-size: 1.5rem;"></i>
                                <h6 class="card-title text-danger mb-1">Spent</h6>
                                <h5 class="mb-0">{{ number_format($budget->spent_amount, 0) }}</h5>
                                <small class="text-muted">{{ $budget->utilization_percentage }}%</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border border-{{ $budget->remaining_amount >= 0 ? 'success' : 'danger' }}">
                            <div class="card-body text-center p-3">
                                <i class="ti ti-{{ $budget->remaining_amount >= 0 ? 'check' : 'alert-triangle' }} text-{{ $budget->remaining_amount >= 0 ? 'success' : 'danger' }} mb-2" style="font-size: 1.5rem;"></i>
                                <h6 class="card-title text-{{ $budget->remaining_amount >= 0 ? 'success' : 'danger' }} mb-1">Remaining</h6>
                                <h5 class="mb-0">{{ number_format($budget->remaining_amount, 0) }}</h5>
                                @if($budget->remaining_amount < 0)
                                    <small class="text-danger">Over Budget</small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History Timeline -->
                <div class="row">
                    <div class="col-12">
                        <h6 class="mb-3">Activity History</h6>

                        @if($histories->count() > 0)
                            <div class="timeline timeline-center">
                                @foreach($histories as $history)
                                    <div class="timeline-item">
                                        <div class="timeline-point timeline-point-{{ $history->action_color }}">
                                            <i class="ti {{ $history->action_icon }}"></i>
                                        </div>
                                        <div class="timeline-event">
                                            <div class="timeline-header d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">{{ $history->description }}</h6>
                                                <small class="text-muted">{{ $history->created_at->diffForHumans() }}</small>
                                            </div>
                                            <div class="timeline-body">
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <span class="badge bg-label-{{ $history->action_color }} me-2">
                                                                {{ ucfirst(str_replace('_', ' ', $history->action)) }}
                                                            </span>
                                                            <small class="text-muted">
                                                                by {{ $history->user->name }}
                                                            </small>
                                                        </div>

                                                        @if($history->amount_changed)
                                                            <div class="alert alert-{{ $history->action_color }} alert-dismissible fade show p-2" role="alert">
                                                                <strong>Amount: </strong>{{ number_format($history->amount_changed, 2) }}
                                                            </div>
                                                        @endif

                                                        @if($history->old_values && count($history->old_values) > 0)
                                                            <div class="accordion accordion-flush" id="accordion{{ $history->id }}">
                                                                <div class="accordion-item">
                                                                    <h2 class="accordion-header">
                                                                        <button class="accordion-button collapsed p-2" type="button"
                                                                                data-bs-toggle="collapse"
                                                                                data-bs-target="#collapse{{ $history->id }}">
                                                                            <small>View Changes</small>
                                                                        </button>
                                                                    </h2>
                                                                    <div id="collapse{{ $history->id }}" class="accordion-collapse collapse"
                                                                         data-bs-parent="#accordion{{ $history->id }}">
                                                                        <div class="accordion-body p-2">
                                                                            <div class="table-responsive">
                                                                                <table class="table table-sm">
                                                                                    <thead>
                                                                                        <tr>
                                                                                            <th>Field</th>
                                                                                            <th>Old Value</th>
                                                                                            <th>New Value</th>
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody>
                                                                                        @foreach($history->old_values as $field => $oldValue)
                                                                                            @if(isset($history->new_values[$field]) && $history->new_values[$field] != $oldValue)
                                                                                                <tr>
                                                                                                    <td><strong>{{ ucfirst(str_replace('_', ' ', $field)) }}</strong></td>
                                                                                                    <td>
                                                                                                        <span class="text-danger">
                                                                                                            {{ is_numeric($oldValue) ? number_format($oldValue, 2) : $oldValue }}
                                                                                                        </span>
                                                                                                    </td>
                                                                                                    <td>
                                                                                                        <span class="text-success">
                                                                                                            {{ is_numeric($history->new_values[$field]) ? number_format($history->new_values[$field], 2) : $history->new_values[$field] }}
                                                                                                        </span>
                                                                                                    </td>
                                                                                                </tr>
                                                                                            @endif
                                                                                        @endforeach
                                                                                    </tbody>
                                                                                </table>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="col-md-4 text-end">
                                                        <small class="text-muted">
                                                            {{ $history->created_at->format('M d, Y H:i') }}
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Pagination -->
                            @if($histories->hasPages())
                                <div class="d-flex justify-content-center mt-4">
                                    {{ $histories->links() }}
                                </div>
                            @endif
                        @else
                            <div class="text-center py-5">
                                <i class="ti ti-history text-muted mb-3" style="font-size: 4rem;"></i>
                                <h5>No History Found</h5>
                                <p class="text-muted">No budget activities have been recorded yet.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding: 0;
}

.timeline:before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    height: 100%;
    width: 2px;
    background: var(--bs-border-color);
    transform: translateX(-50%);
}

.timeline-item {
    position: relative;
    margin-bottom: 2rem;
}

.timeline-point {
    position: absolute;
    left: 50%;
    top: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transform: translateX(-50%);
    z-index: 1;
}

.timeline-point-success { background: var(--bs-success); color: white; }
.timeline-point-info { background: var(--bs-info); color: white; }
.timeline-point-warning { background: var(--bs-warning); color: white; }
.timeline-point-primary { background: var(--bs-primary); color: white; }
.timeline-point-secondary { background: var(--bs-secondary); color: white; }
.timeline-point-danger { background: var(--bs-danger); color: white; }
.timeline-point-dark { background: var(--bs-dark); color: white; }

.timeline-event {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 0.375rem;
    padding: 1rem;
    margin-left: calc(50% + 30px);
    width: calc(50% - 30px);
}

.timeline-item:nth-child(even) .timeline-event {
    margin-left: 0;
    margin-right: calc(50% + 30px);
}

.timeline-header {
    margin-bottom: 0.5rem;
}

@media (max-width: 768px) {
    .timeline:before {
        left: 20px;
    }

    .timeline-point {
        left: 20px;
    }

    .timeline-event {
        margin-left: 60px;
        width: calc(100% - 60px);
    }

    .timeline-item:nth-child(even) .timeline-event {
        margin-left: 60px;
        margin-right: 0;
    }
}
</style>
@endsection