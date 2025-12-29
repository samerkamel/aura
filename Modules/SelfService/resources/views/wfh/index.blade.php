@extends('layouts/layoutMaster')

@section('title', 'My WFH Requests')

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Page Header -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0">
            <i class="ti tabler-home me-2"></i>My WFH Requests
          </h5>
          <small class="text-muted">View and manage your work from home requests</small>
        </div>
        <a href="{{ route('self-service.wfh-requests.create') }}" class="btn btn-primary">
          <i class="ti tabler-plus me-1"></i>New Request
        </a>
      </div>
    </div>

    <!-- WFH Allowance Summary -->
    <div class="row mb-4">
      <div class="col-md-6 col-lg-4">
        <div class="card bg-label-success">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <p class="mb-1">WFH This Month</p>
                <h4 class="mb-0">{{ $wfhData['remaining'] }} <small class="text-muted">/ {{ $wfhData['allowance'] }} days</small></h4>
                <small class="text-muted">
                  {{ $wfhData['used'] }} used
                  @if($wfhData['pending'] > 0)
                    , {{ $wfhData['pending'] }} pending
                  @endif
                </small>
              </div>
              <i class="ti tabler-home-check" style="font-size: 2rem; opacity: 0.5;"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        <i class="ti tabler-check me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show">
        <i class="ti tabler-alert-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <!-- Requests Table -->
    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>Date</th>
              <th>Status</th>
              <th>Notes</th>
              <th>Submitted</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($requests as $request)
            <tr>
              <td>
                <strong>{{ $request->start_date->format('l, M d, Y') }}</strong>
              </td>
              <td>
                @php
                  $statusClass = match($request->status) {
                    'pending_manager' => 'bg-warning',
                    'pending_admin' => 'bg-info',
                    'approved' => 'bg-success',
                    'rejected' => 'bg-danger',
                    'cancelled' => 'bg-secondary',
                    default => 'bg-secondary'
                  };
                @endphp
                <span class="badge {{ $statusClass }}">{{ $request->status_label }}</span>
                @if($request->status === 'rejected' && $request->rejection_reason)
                  <br><small class="text-danger">{{ \Illuminate\Support\Str::limit($request->rejection_reason, 30) }}</small>
                @endif
              </td>
              <td>
                <small class="text-muted">{{ \Illuminate\Support\Str::limit($request->notes, 40) ?: '-' }}</small>
              </td>
              <td>
                <small class="text-muted">{{ $request->created_at->format('M d, Y H:i') }}</small>
              </td>
              <td>
                <div class="d-flex gap-1">
                  <a href="{{ route('self-service.wfh-requests.show', $request) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                    <i class="ti tabler-eye"></i>
                  </a>
                  @if($request->canBeCancelled())
                    <form action="{{ route('self-service.wfh-requests.cancel', $request) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this request?')">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel Request">
                        <i class="ti tabler-x"></i>
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="5" class="text-center py-5">
                <i class="ti tabler-inbox text-muted" style="font-size: 3rem;"></i>
                <h6 class="mt-2">No WFH requests found</h6>
                <p class="text-muted">You haven't submitted any WFH requests yet.</p>
                <a href="{{ route('self-service.wfh-requests.create') }}" class="btn btn-primary">
                  <i class="ti tabler-plus me-1"></i>Submit Your First Request
                </a>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($requests->hasPages())
      <div class="card-footer">
        {{ $requests->links() }}
      </div>
      @endif
    </div>
  </div>
</div>
@endsection
