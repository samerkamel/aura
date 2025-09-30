@extends('layouts/layoutMaster')

@section('title', 'Invoice Sequences')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Invoice Sequences</h5>
                    <small class="text-muted">Manage invoice numbering sequences</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('invoicing.internal-sequences.index') }}" class="btn btn-outline-info">
                        <i class="ti ti-building-bank me-1"></i>Internal Sequences
                    </a>
                    <a href="{{ route('invoicing.sequences.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>New Sequence
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

            <div class="card-body">
                @if($sequences->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Format</th>
                                    <th>Next Number</th>
                                    <th>Current Count</th>
                                    <th>Business Unit</th>
                                    <th>Sectors</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sequences as $sequence)
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-semibold">{{ $sequence->name }}</span>
                                            @if($sequence->description)
                                                <small class="text-muted">{{ $sequence->description }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <code class="text-primary">{{ $sequence->format }}</code>
                                        <br>
                                        <small class="text-muted">Prefix: {{ $sequence->prefix ?: 'None' }}</small>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-success">{{ $sequence->previewNextInvoiceNumber() }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-info">{{ $sequence->current_number }}</span>
                                        <br>
                                        <small class="text-muted">Start: {{ $sequence->starting_number }}</small>
                                    </td>
                                    <td>
                                        @if($sequence->businessUnit)
                                            <span class="badge bg-label-primary">{{ $sequence->businessUnit->name }}</span>
                                        @else
                                            <span class="text-muted">All Units</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($sequence->sector_ids)
                                            @foreach($sequence->sectors() as $sector)
                                                <span class="badge bg-label-secondary me-1">{{ $sector->name }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">All Sectors</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($sequence->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="ti ti-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="{{ route('invoicing.sequences.show', $sequence) }}">
                                                    <i class="ti ti-eye me-2"></i>View Details
                                                </a>
                                                <a class="dropdown-item" href="{{ route('invoicing.sequences.edit', $sequence) }}">
                                                    <i class="ti ti-edit me-2"></i>Edit
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <button type="button" class="dropdown-item" onclick="toggleSequence({{ $sequence->id }})">
                                                    @if($sequence->is_active)
                                                        <i class="ti ti-pause me-2"></i>Deactivate
                                                    @else
                                                        <i class="ti ti-play me-2"></i>Activate
                                                    @endif
                                                </button>
                                                <button type="button" class="dropdown-item text-warning" onclick="resetSequence({{ $sequence->id }})">
                                                    <i class="ti ti-refresh me-2"></i>Reset Counter
                                                </button>
                                                @if($sequence->invoices_count == 0)
                                                    <div class="dropdown-divider"></div>
                                                    <button type="button" class="dropdown-item text-danger" onclick="deleteSequence({{ $sequence->id }})">
                                                        <i class="ti ti-trash me-2"></i>Delete
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
                @else
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="ti ti-file-invoice display-6 text-muted"></i>
                        </div>
                        <h5 class="mb-2">No invoice sequences found</h5>
                        <p class="text-muted">Create your first invoice sequence to start generating invoices.</p>
                        <a href="{{ route('invoicing.sequences.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i>Create First Sequence
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Sequence Usage Summary -->
@if($sequences->count() > 0)
<div class="row mt-4">
    <div class="col-lg-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge bg-label-primary p-2 me-2 rounded">
                            <i class="ti ti-list-numbers ti-sm"></i>
                        </span>
                    </div>
                    <div class="d-flex flex-column">
                        <small>Total Sequences</small>
                        <h6 class="mb-0">{{ $sequences->count() }}</h6>
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
                        <small>Active Sequences</small>
                        <h6 class="mb-0">{{ $sequences->where('is_active', true)->count() }}</h6>
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
                        <span class="badge bg-label-info p-2 me-2 rounded">
                            <i class="ti ti-file-invoice ti-sm"></i>
                        </span>
                    </div>
                    <div class="d-flex flex-column">
                        <small>Invoices Generated</small>
                        <h6 class="mb-0">{{ $sequences->sum('current_number') }}</h6>
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
                            <i class="ti ti-building ti-sm"></i>
                        </span>
                    </div>
                    <div class="d-flex flex-column">
                        <small>Business Units</small>
                        <h6 class="mb-0">{{ $sequences->where('business_unit_id', '!=', null)->count() }}</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('page-script')
<script>
function toggleSequence(sequenceId) {
    if (confirm('Change the status of this sequence?')) {
        fetch(`/invoicing/admin/sequences/${sequenceId}/toggle`, {
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

function resetSequence(sequenceId) {
    if (confirm('Reset the sequence counter to 0? This will affect future invoice numbers.')) {
        fetch(`/invoicing/admin/sequences/${sequenceId}/reset`, {
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

function deleteSequence(sequenceId) {
    if (confirm('Delete this sequence? This action cannot be undone.')) {
        fetch(`/invoicing/admin/sequences/${sequenceId}`, {
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