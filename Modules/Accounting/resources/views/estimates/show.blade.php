@extends('layouts/layoutMaster')

@section('title', 'Estimate: ' . $estimate->estimate_number)

@section('content')
<div class="row">
    <div class="col-12">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible mb-4" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible mb-4" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Estimate Header -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">{{ $estimate->estimate_number }}</h5>
                            <small class="text-muted">Created by {{ $estimate->createdBy->name }} on {{ $estimate->created_at->format('M d, Y') }}</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-{{ $estimate->status_color }} fs-6">{{ $estimate->status_label }}</span>
                            <a href="{{ route('accounting.estimates.index') }}" class="btn btn-outline-secondary">
                                <i class="ti ti-arrow-left me-1"></i>Back
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Client & Project Info -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Client Information</h6>
                                <h5 class="mb-1">{{ $estimate->client_name }}</h5>
                                @if($estimate->client_email)
                                    <p class="mb-1"><i class="ti ti-mail me-1"></i>{{ $estimate->client_email }}</p>
                                @endif
                                @if($estimate->client_address)
                                    <p class="mb-0"><i class="ti ti-map-pin me-1"></i>{{ $estimate->client_address }}</p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Estimate Details</h6>
                                <p class="mb-1"><strong>Title:</strong> {{ $estimate->title }}</p>
                                @if($estimate->project)
                                    <p class="mb-1"><strong>Project:</strong>
                                        <a href="{{ route('projects.show', $estimate->project) }}">
                                            {{ $estimate->project->name }}
                                        </a>
                                    </p>
                                @endif
                                <p class="mb-1"><strong>Issue Date:</strong> {{ $estimate->issue_date->format('M d, Y') }}</p>
                                @if($estimate->valid_until)
                                    <p class="mb-0">
                                        <strong>Valid Until:</strong> {{ $estimate->valid_until->format('M d, Y') }}
                                        @if($estimate->is_expired)
                                            <span class="badge bg-danger ms-1">Expired</span>
                                        @elseif($estimate->days_until_expiry !== null && $estimate->days_until_expiry <= 7)
                                            <span class="badge bg-warning ms-1">{{ $estimate->days_until_expiry }} days left</span>
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </div>
                        @if($estimate->description)
                            <hr>
                            <h6 class="text-muted mb-2">Description</h6>
                            <p class="mb-0">{{ $estimate->description }}</p>
                        @endif
                    </div>
                </div>

                <!-- Line Items -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Line Items</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Description</th>
                                    <th class="text-center" style="width: 100px;">Qty</th>
                                    <th class="text-center" style="width: 80px;">Unit</th>
                                    <th class="text-end" style="width: 120px;">Unit Price</th>
                                    <th class="text-end" style="width: 120px;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($estimate->items as $item)
                                    <tr>
                                        <td>
                                            <div class="fw-medium">{{ $item->description }}</div>
                                            @if($item->details)
                                                <small class="text-muted">{{ $item->details }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                                        <td class="text-center">{{ ucfirst($item->unit) }}</td>
                                        <td class="text-end">EGP {{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-end fw-medium">EGP {{ number_format($item->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end fw-medium">Subtotal:</td>
                                    <td class="text-end fw-medium">EGP {{ number_format($estimate->subtotal, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end">VAT ({{ $estimate->vat_rate }}%):</td>
                                    <td class="text-end">EGP {{ number_format($estimate->vat_amount, 2) }}</td>
                                </tr>
                                <tr class="table-primary">
                                    <td colspan="4" class="text-end fw-bold">Total:</td>
                                    <td class="text-end fw-bold">EGP {{ number_format($estimate->total, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Notes -->
                @if($estimate->notes || $estimate->internal_notes)
                    <div class="card mb-4">
                        <div class="card-body">
                            @if($estimate->notes)
                                <h6 class="text-muted mb-2">Notes & Terms</h6>
                                <p class="mb-0">{!! nl2br(e($estimate->notes)) !!}</p>
                            @endif

                            @if($estimate->internal_notes)
                                @if($estimate->notes)<hr>@endif
                                <h6 class="text-muted mb-2"><i class="ti ti-lock me-1"></i>Internal Notes</h6>
                                <p class="mb-0 text-muted">{!! nl2br(e($estimate->internal_notes)) !!}</p>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Contract Link -->
                @if($estimate->contract)
                    <div class="card mb-4">
                        <div class="card-body">
                            <h6 class="text-muted mb-2"><i class="ti ti-link me-1"></i>Converted to Contract</h6>
                            <a href="{{ route('accounting.income.contracts.show', $estimate->contract) }}" class="btn btn-outline-primary">
                                <i class="ti ti-file-text me-1"></i>View Contract: {{ $estimate->contract->contract_number }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('accounting.estimates.pdf', $estimate) }}" class="btn btn-primary">
                                <i class="ti ti-file-download me-1"></i>Download PDF
                            </a>

                            @if($estimate->canBeEdited())
                                <a href="{{ route('accounting.estimates.edit', $estimate) }}" class="btn btn-outline-primary">
                                    <i class="ti ti-pencil me-1"></i>Edit Estimate
                                </a>
                            @endif

                            <form action="{{ route('accounting.estimates.duplicate', $estimate) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary w-100">
                                    <i class="ti ti-copy me-1"></i>Duplicate
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Status Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Status Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            @if($estimate->canBeSent())
                                <form action="{{ route('accounting.estimates.send', $estimate) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-info w-100">
                                        <i class="ti ti-send me-1"></i>Mark as Sent
                                    </button>
                                </form>
                            @endif

                            @if($estimate->status === 'sent')
                                <form action="{{ route('accounting.estimates.approve', $estimate) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="ti ti-check me-1"></i>Approve
                                    </button>
                                </form>
                                <form action="{{ route('accounting.estimates.reject', $estimate) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="ti ti-x me-1"></i>Reject
                                    </button>
                                </form>
                            @endif

                            @if($estimate->canBeConverted())
                                <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#convertToContractModal">
                                    <i class="ti ti-transform me-1"></i>Convert to Contract
                                </button>
                            @endif

                            @if($estimate->status === 'draft')
                                <form action="{{ route('accounting.estimates.destroy', $estimate) }}" method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this estimate?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger w-100">
                                        <i class="ti ti-trash me-1"></i>Delete
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Items:</span>
                            <span>{{ $estimate->items->count() }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal:</span>
                            <span>EGP {{ number_format($estimate->subtotal, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">VAT ({{ $estimate->vat_rate }}%):</span>
                            <span>EGP {{ number_format($estimate->vat_amount, 2) }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Total:</span>
                            <span class="fw-bold text-primary fs-5">EGP {{ number_format($estimate->total, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Timeline</h6>
                    </div>
                    <div class="card-body">
                        <ul class="timeline mb-0">
                            <li class="timeline-item">
                                <span class="timeline-indicator timeline-indicator-primary">
                                    <i class="ti ti-plus"></i>
                                </span>
                                <div class="timeline-event">
                                    <div class="timeline-header">Created</div>
                                    <small class="text-muted">{{ $estimate->created_at->format('M d, Y H:i') }}</small>
                                </div>
                            </li>
                            @if($estimate->sent_at)
                                <li class="timeline-item">
                                    <span class="timeline-indicator timeline-indicator-info">
                                        <i class="ti ti-send"></i>
                                    </span>
                                    <div class="timeline-event">
                                        <div class="timeline-header">Sent</div>
                                        <small class="text-muted">{{ $estimate->sent_at->format('M d, Y H:i') }}</small>
                                    </div>
                                </li>
                            @endif
                            @if($estimate->approved_at)
                                <li class="timeline-item">
                                    <span class="timeline-indicator timeline-indicator-success">
                                        <i class="ti ti-check"></i>
                                    </span>
                                    <div class="timeline-event">
                                        <div class="timeline-header">Approved</div>
                                        <small class="text-muted">{{ $estimate->approved_at->format('M d, Y H:i') }}</small>
                                    </div>
                                </li>
                            @endif
                            @if($estimate->rejected_at)
                                <li class="timeline-item">
                                    <span class="timeline-indicator timeline-indicator-danger">
                                        <i class="ti ti-x"></i>
                                    </span>
                                    <div class="timeline-event">
                                        <div class="timeline-header">Rejected</div>
                                        <small class="text-muted">{{ $estimate->rejected_at->format('M d, Y H:i') }}</small>
                                    </div>
                                </li>
                            @endif
                            @if($estimate->converted_to_contract_id)
                                <li class="timeline-item">
                                    <span class="timeline-indicator timeline-indicator-primary">
                                        <i class="ti ti-transform"></i>
                                    </span>
                                    <div class="timeline-event">
                                        <div class="timeline-header">Converted to Contract</div>
                                        <small class="text-muted">{{ $estimate->contract->contract_number }}</small>
                                    </div>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Include Convert Modal --}}
@if($estimate->canBeConverted())
    @include('accounting::estimates.partials.convert-modal')
@endif
@endsection
