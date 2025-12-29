@extends('layouts/layoutMaster')

@section('title', 'My Leave Requests')

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Page Header -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0">
            <i class="ti tabler-calendar-off me-2"></i>My Leave Requests
          </h5>
          <small class="text-muted">View and manage your leave requests</small>
        </div>
        <a href="{{ route('self-service.leave-requests.create') }}" class="btn btn-primary">
          <i class="ti tabler-plus me-1"></i>New Request
        </a>
      </div>
    </div>

    <!-- Leave Balances Summary -->
    <div class="row mb-4">
      @foreach($leaveBalances as $balance)
      <div class="col-md-4 col-sm-6 mb-3">
        <div class="card bg-label-primary">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <p class="mb-1">{{ $balance['policy_name'] }}</p>
                <h4 class="mb-0">{{ number_format($balance['remaining_days'], 1) }} <small class="text-muted">/ {{ number_format($balance['entitled_days'], 1) }} days</small></h4>
              </div>
              <i class="ti tabler-calendar-stats" style="font-size: 2rem; opacity: 0.5;"></i>
            </div>
          </div>
        </div>
      </div>
      @endforeach
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
              <th>Leave Type</th>
              <th>Date(s)</th>
              <th>Days</th>
              <th>Status</th>
              <th>Submitted</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($requests as $request)
            <tr>
              <td>
                <strong>{{ $request->leavePolicy->name ?? 'Unknown' }}</strong>
              </td>
              <td>
                {{ $request->start_date->format('M d, Y') }}
                @if(!$request->start_date->isSameDay($request->end_date))
                  <br><small class="text-muted">to {{ $request->end_date->format('M d, Y') }}</small>
                @endif
              </td>
              <td>
                <span class="badge bg-label-info">{{ $request->days_count }} day(s)</span>
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
                <small class="text-muted">{{ $request->created_at->format('M d, Y H:i') }}</small>
              </td>
              <td>
                <div class="d-flex gap-1">
                  <a href="{{ route('self-service.leave-requests.show', $request) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                    <i class="ti tabler-eye"></i>
                  </a>
                  @if($request->canBeCancelled())
                    <form action="{{ route('self-service.leave-requests.cancel', $request) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this request?')">
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
              <td colspan="6" class="text-center py-5">
                <i class="ti tabler-inbox text-muted" style="font-size: 3rem;"></i>
                <h6 class="mt-2">No leave requests found</h6>
                <p class="text-muted">You haven't submitted any leave requests yet.</p>
                <a href="{{ route('self-service.leave-requests.create') }}" class="btn btn-primary">
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
