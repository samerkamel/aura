@extends('layouts/layoutMaster')

@section('title', 'Estimates')

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
                                <h6 class="card-title mb-1">Total Estimates</h6>
                                <h4 class="mb-0">{{ $statistics['total_estimates'] }}</h4>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial rounded bg-label-primary">
                                    <i class="ti tabler-file-invoice ti-md"></i>
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
                                    <i class="ti tabler-pencil ti-md"></i>
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
                                <h6 class="card-title mb-1">Sent</h6>
                                <h4 class="mb-0">{{ $statistics['sent_count'] }}</h4>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial rounded bg-label-info">
                                    <i class="ti tabler-send ti-md"></i>
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
                                <h6 class="card-title mb-1">Approved Value</h6>
                                <h4 class="mb-0">EGP {{ number_format($statistics['approved_total'], 0) }}</h4>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial rounded bg-label-success">
                                    <i class="ti tabler-check ti-md"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estimates Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Estimates Management</h5>
                    <small class="text-muted">Create and manage client estimates and quotations</small>
                </div>
                <a href="{{ route('accounting.estimates.create') }}" class="btn btn-primary">
                    <i class="ti tabler-plus me-1"></i>New Estimate
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
                <form action="{{ route('accounting.estimates.index') }}" method="GET" class="row g-3">
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="all">All Statuses</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
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
                        <select name="project_id" class="form-select">
                            <option value="">All Projects</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="search" class="form-control" placeholder="Search..."
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="ti tabler-filter me-1"></i>Filter
                        </button>
                        <a href="{{ route('accounting.estimates.index') }}" class="btn btn-outline-secondary">
                            <i class="ti tabler-x"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead class="table-light">
                        <tr>
                            <th>Estimate #</th>
                            <th>Client</th>
                            <th>Title</th>
                            <th>Project</th>
                            <th>Issue Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($estimates as $estimate)
                            <tr>
                                <td>
                                    <a href="{{ route('accounting.estimates.show', $estimate) }}" class="fw-medium">
                                        {{ $estimate->estimate_number }}
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-medium">{{ $estimate->client_name }}</span>
                                        @if($estimate->customer)
                                            <small class="text-muted">{{ $estimate->customer->name }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ Str::limit($estimate->title, 40) }}</td>
                                <td>
                                    @if($estimate->project)
                                        <span class="badge bg-label-primary">{{ $estimate->project->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $estimate->issue_date->format('M d, Y') }}</td>
                                <td class="fw-medium">EGP {{ number_format($estimate->total, 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $estimate->status_color }}">
                                        {{ $estimate->status_label }}
                                    </span>
                                    @if($estimate->is_expired && $estimate->status === 'sent')
                                        <span class="badge bg-danger">Expired</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                            <i class="ti tabler-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="{{ route('accounting.estimates.show', $estimate) }}">
                                                <i class="ti tabler-eye me-1"></i> View
                                            </a>
                                            @if($estimate->canBeEdited())
                                                <a class="dropdown-item" href="{{ route('accounting.estimates.edit', $estimate) }}">
                                                    <i class="ti tabler-pencil me-1"></i> Edit
                                                </a>
                                            @endif
                                            <a class="dropdown-item" href="{{ route('accounting.estimates.pdf', $estimate) }}">
                                                <i class="ti tabler-file-download me-1"></i> Download PDF
                                            </a>
                                            <form action="{{ route('accounting.estimates.duplicate', $estimate) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="ti tabler-copy me-1"></i> Duplicate
                                                </button>
                                            </form>
                                            <div class="dropdown-divider"></div>
                                            @if($estimate->canBeSent())
                                                <form action="{{ route('accounting.estimates.send', $estimate) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="ti tabler-send me-1"></i> Mark as Sent
                                                    </button>
                                                </form>
                                            @endif
                                            @if($estimate->status === 'sent')
                                                <form action="{{ route('accounting.estimates.approve', $estimate) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-success">
                                                        <i class="ti tabler-check me-1"></i> Approve
                                                    </button>
                                                </form>
                                                <form action="{{ route('accounting.estimates.reject', $estimate) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="ti tabler-x me-1"></i> Reject
                                                    </button>
                                                </form>
                                            @endif
                                            @if($estimate->canBeConverted())
                                                <form action="{{ route('accounting.estimates.convert', $estimate) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-primary">
                                                        <i class="ti tabler-transform me-1"></i> Convert to Contract
                                                    </button>
                                                </form>
                                            @endif
                                            @if($estimate->status === 'draft')
                                                <div class="dropdown-divider"></div>
                                                <form action="{{ route('accounting.estimates.destroy', $estimate) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this estimate?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="ti tabler-trash me-1"></i> Delete
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
                                        <i class="ti tabler-file-off" style="font-size: 3rem;"></i>
                                        <p class="mt-2 mb-0">No estimates found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($estimates->hasPages())
                <div class="card-footer">
                    {{ $estimates->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
