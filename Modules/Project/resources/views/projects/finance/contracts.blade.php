@extends('layouts.layoutMaster')

@section('title', 'Linked Contracts - ' . $project->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Linked Contracts</h4>
            <p class="text-muted mb-0">{{ $project->name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('projects.finance.index', $project) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back to Finance
            </a>
            <a href="{{ route('accounting.income.contracts.create', ['project_id' => $project->id, 'customer_id' => $project->customer_id]) }}" class="btn btn-success">
                <i class="ti ti-plus me-1"></i> New Contract
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted">Total Contract Value</span>
                            <div class="d-flex align-items-center mt-2">
                                <h4 class="mb-0 me-2">{{ number_format($totals['contract_value'], 2) }}</h4>
                            </div>
                        </div>
                        <span class="avatar avatar-lg rounded">
                            <span class="avatar-initial bg-label-primary rounded"><i class="ti ti-file-text ti-26px"></i></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted">Paid Amount</span>
                            <div class="d-flex align-items-center mt-2">
                                <h4 class="mb-0 me-2 text-success">{{ number_format($totals['paid_amount'], 2) }}</h4>
                            </div>
                        </div>
                        <span class="avatar avatar-lg rounded">
                            <span class="avatar-initial bg-label-success rounded"><i class="ti ti-check ti-26px"></i></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted">Pending Amount</span>
                            <div class="d-flex align-items-center mt-2">
                                <h4 class="mb-0 me-2 text-warning">{{ number_format($totals['pending_amount'], 2) }}</h4>
                            </div>
                        </div>
                        <span class="avatar avatar-lg rounded">
                            <span class="avatar-initial bg-label-warning rounded"><i class="ti ti-clock ti-26px"></i></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contracts List -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Contracts ({{ $contracts->count() }})</h5>
        </div>
        <div class="card-body">
            @if($contracts->count() > 0)
                @foreach($contracts as $contract)
                    <div class="card mb-3 border">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <h6 class="mb-1">
                                        <a href="{{ route('accounting.income.contracts.show', $contract) }}" class="text-primary">
                                            {{ $contract->contract_number }}
                                        </a>
                                        <span class="badge bg-{{ $contract->status_color }} ms-2">{{ ucfirst($contract->status) }}</span>
                                    </h6>
                                    <small class="text-muted">{{ $contract->customer?->display_name ?? $contract->client_name }}</small>
                                    @if($contract->pivot && $contract->pivot->allocation_percentage)
                                        <br><small class="text-info">{{ $contract->pivot->allocation_percentage }}% allocated to this project</small>
                                    @endif
                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="mb-1">
                                        <span class="fw-bold">{{ number_format($contract->total_amount, 2) }}</span>
                                        <span class="text-muted">total</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        @php
                                            $progress = $contract->total_amount > 0
                                                ? min(100, ($contract->paid_amount / $contract->total_amount) * 100)
                                                : 0;
                                        @endphp
                                        <div class="progress-bar bg-success" style="width: {{ $progress }}%"></div>
                                    </div>
                                    <small class="text-muted">{{ number_format($contract->paid_amount, 2) }} paid ({{ round($progress) }}%)</small>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        @foreach($contract->payments->take(4) as $payment)
                                            <span class="badge bg-{{ $payment->status === 'paid' ? 'success' : ($payment->status === 'overdue' ? 'danger' : 'secondary') }}"
                                                  title="{{ $payment->name }}: {{ number_format($payment->amount, 2) }}">
                                                {{ Str::limit($payment->name, 15) }}
                                            </span>
                                        @endforeach
                                        @if($contract->payments->count() > 4)
                                            <span class="badge bg-light text-dark">+{{ $contract->payments->count() - 4 }} more</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center py-5">
                    <i class="ti ti-file-off ti-lg text-muted mb-3 d-block"></i>
                    <p class="text-muted mb-3">No contracts linked to this project</p>
                    <a href="{{ route('accounting.income.contracts.create', ['project_id' => $project->id, 'customer_id' => $project->customer_id]) }}" class="btn btn-success">
                        <i class="ti ti-plus me-1"></i> Create Contract
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
