@extends('layouts/layoutMaster')

@section('title', 'My Permission Requests')

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Page Header -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0">
            <i class="ti ti-clock-pause me-2"></i>My Permission Requests
          </h5>
          <small class="text-muted">View and manage your permission requests (late arrival / early leave)</small>
        </div>
        <a href="{{ route('self-service.permission-requests.create') }}" class="btn btn-primary">
          <i class="ti ti-plus me-1"></i>New Request
        </a>
      </div>
    </div>

    <!-- Permission Allowance Summary -->
    <div class="row mb-4">
      <div class="col-md-6 col-lg-4">
        <div class="card bg-label-warning">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <p class="mb-1">Permissions This Month</p>
                <h4 class="mb-0">{{ $permissionData['remaining'] }} <small class="text-muted">/ {{ $permissionData['allowance'] }}</small></h4>
                <small class="text-muted">
                  {{ $permissionData['used'] }} used ({{ $permissionData['minutes_per_permission'] }}m each)
                  @if($permissionData['pending'] > 0)
                    , {{ $permissionData['pending'] }} pending
                  @endif
                </small>
              </div>
              <i class="ti ti-clock-check" style="font-size: 2rem; opacity: 0.5;"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        <i class="ti ti-check me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show">
        <i class="ti ti-alert-circle me-2"></i>{{ session('error') }}
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
              <th>Duration</th>
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
                <span class="badge bg-label-info">{{ $request->request_data['minutes'] ?? $permissionData['minutes_per_permission'] }} minutes</span>
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
                  <br><small class="text-danger">{{ Str::limit($request->rejection_reason, 30) }}</small>
                @endif
              </td>
              <td>
                <small class="text-muted">{{ Str::limit($request->notes, 40) ?: '-' }}</small>
              </td>
              <td>
                <small class="text-muted">{{ $request->created_at->format('M d, Y H:i') }}</small>
              </td>
              <td>
                <div class="d-flex gap-1">
                  <a href="{{ route('self-service.permission-requests.show', $request) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                    <i class="ti ti-eye"></i>
                  </a>
                  @if($request->canBeCancelled())
                    <form action="{{ route('self-service.permission-requests.cancel', $request) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this request?')">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel Request">
                        <i class="ti ti-x"></i>
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="6" class="text-center py-5">
                <i class="ti ti-inbox text-muted" style="font-size: 3rem;"></i>
                <h6 class="mt-2">No permission requests found</h6>
                <p class="text-muted">You haven't submitted any permission requests yet.</p>
                <a href="{{ route('self-service.permission-requests.create') }}" class="btn btn-primary">
                  <i class="ti ti-plus me-1"></i>Submit Your First Request
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
