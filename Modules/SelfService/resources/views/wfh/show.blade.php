@extends('layouts/layoutMaster')

@section('title', 'WFH Request Details')

@section('content')
<div class="row">
  <div class="col-12 col-lg-8 mx-auto">
    <!-- Page Header -->
    <div class="card mb-4">
      <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center">
            <a href="{{ route('self-service.wfh-requests.index') }}" class="btn btn-icon btn-outline-secondary me-3">
              <i class="ti tabler-arrow-left"></i>
            </a>
            <div>
              <h5 class="mb-0">
                <i class="ti tabler-home me-2"></i>WFH Request Details
              </h5>
              <small class="text-muted">Request #{{ $wfh_request->id }}</small>
            </div>
          </div>
          @php
            $statusClass = match($wfh_request->status) {
              'pending_manager' => 'bg-warning',
              'pending_admin' => 'bg-info',
              'approved' => 'bg-success',
              'rejected' => 'bg-danger',
              'cancelled' => 'bg-secondary',
              default => 'bg-secondary'
            };
          @endphp
          <span class="badge {{ $statusClass }} fs-6">{{ $wfh_request->status_label }}</span>
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

    <!-- Request Details -->
    <div class="card mb-4">
      <div class="card-header">
        <h6 class="mb-0"><i class="ti tabler-info-circle me-2"></i>Request Information</h6>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="text-muted d-block mb-1">WFH Date</label>
            <strong>{{ $wfh_request->start_date->format('l, M d, Y') }}</strong>
          </div>
          <div class="col-md-6 mb-3">
            <label class="text-muted d-block mb-1">Request Type</label>
            <strong>Work From Home</strong>
          </div>
          <div class="col-12">
            <label class="text-muted d-block mb-1">Notes</label>
            <p class="mb-0">{{ $wfh_request->notes ?: 'No notes provided' }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Approval Timeline -->
    <div class="card mb-4">
      <div class="card-header">
        <h6 class="mb-0"><i class="ti tabler-list-check me-2"></i>Approval Timeline</h6>
      </div>
      <div class="card-body">
        <ul class="timeline">
          <!-- Submitted -->
          <li class="timeline-item">
            <span class="timeline-indicator timeline-indicator-success">
              <i class="ti tabler-send"></i>
            </span>
            <div class="timeline-event">
              <div class="timeline-header">
                <h6 class="mb-0">Request Submitted</h6>
              </div>
              <p class="text-muted mb-0">
                {{ $wfh_request->created_at->format('M d, Y H:i') }}
              </p>
            </div>
          </li>

          <!-- Manager Approval -->
          @if($wfh_request->manager_id)
          <li class="timeline-item">
            @if($wfh_request->manager_approved_at)
              <span class="timeline-indicator timeline-indicator-success">
                <i class="ti tabler-check"></i>
              </span>
              <div class="timeline-event">
                <div class="timeline-header">
                  <h6 class="mb-0">Manager Approved</h6>
                </div>
                <p class="text-muted mb-0">
                  Approved by {{ $wfh_request->managerApprover->name ?? 'Manager' }}
                  on {{ $wfh_request->manager_approved_at->format('M d, Y H:i') }}
                </p>
              </div>
            @elseif($wfh_request->status === 'pending_manager')
              <span class="timeline-indicator timeline-indicator-warning">
                <i class="ti tabler-clock"></i>
              </span>
              <div class="timeline-event">
                <div class="timeline-header">
                  <h6 class="mb-0">Pending Manager Approval</h6>
                </div>
                <p class="text-muted mb-0">
                  Waiting for {{ $wfh_request->manager->name ?? 'Manager' }}
                </p>
              </div>
            @elseif($wfh_request->status === 'rejected' && !$wfh_request->manager_approved_at)
              <span class="timeline-indicator timeline-indicator-danger">
                <i class="ti tabler-x"></i>
              </span>
              <div class="timeline-event">
                <div class="timeline-header">
                  <h6 class="mb-0">Rejected by Manager</h6>
                </div>
                <p class="text-muted mb-0">
                  Rejected by {{ $wfh_request->rejector->name ?? 'Manager' }}
                  on {{ $wfh_request->rejected_at?->format('M d, Y H:i') }}
                </p>
              </div>
            @endif
          </li>
          @endif

          <!-- Admin Approval -->
          @if($wfh_request->status !== 'cancelled')
          <li class="timeline-item">
            @if($wfh_request->admin_approved_at)
              <span class="timeline-indicator timeline-indicator-success">
                <i class="ti tabler-check"></i>
              </span>
              <div class="timeline-event">
                <div class="timeline-header">
                  <h6 class="mb-0">Admin Approved (Final)</h6>
                </div>
                <p class="text-muted mb-0">
                  Approved by {{ $wfh_request->adminApprover->name ?? 'Admin' }}
                  on {{ $wfh_request->admin_approved_at->format('M d, Y H:i') }}
                </p>
              </div>
            @elseif($wfh_request->status === 'pending_admin')
              <span class="timeline-indicator timeline-indicator-warning">
                <i class="ti tabler-clock"></i>
              </span>
              <div class="timeline-event">
                <div class="timeline-header">
                  <h6 class="mb-0">Pending Admin Approval</h6>
                </div>
                <p class="text-muted mb-0">
                  Waiting for admin final approval
                </p>
              </div>
            @elseif($wfh_request->status === 'rejected' && $wfh_request->manager_approved_at)
              <span class="timeline-indicator timeline-indicator-danger">
                <i class="ti tabler-x"></i>
              </span>
              <div class="timeline-event">
                <div class="timeline-header">
                  <h6 class="mb-0">Rejected by Admin</h6>
                </div>
                <p class="text-muted mb-0">
                  Rejected by {{ $wfh_request->rejector->name ?? 'Admin' }}
                  on {{ $wfh_request->rejected_at?->format('M d, Y H:i') }}
                </p>
              </div>
            @endif
          </li>
          @endif

          <!-- Cancelled -->
          @if($wfh_request->status === 'cancelled')
          <li class="timeline-item">
            <span class="timeline-indicator timeline-indicator-secondary">
              <i class="ti tabler-ban"></i>
            </span>
            <div class="timeline-event">
              <div class="timeline-header">
                <h6 class="mb-0">Cancelled</h6>
              </div>
              <p class="text-muted mb-0">
                Request was cancelled on {{ $wfh_request->cancelled_at?->format('M d, Y H:i') }}
              </p>
            </div>
          </li>
          @endif
        </ul>
      </div>
    </div>

    <!-- Rejection Reason -->
    @if($wfh_request->status === 'rejected' && $wfh_request->rejection_reason)
    <div class="alert alert-danger">
      <h6 class="alert-heading"><i class="ti tabler-alert-circle me-2"></i>Rejection Reason</h6>
      <p class="mb-0">{{ $wfh_request->rejection_reason }}</p>
    </div>
    @endif

    <!-- Actions -->
    <div class="d-flex justify-content-between">
      <a href="{{ route('self-service.wfh-requests.index') }}" class="btn btn-outline-secondary">
        <i class="ti tabler-arrow-left me-1"></i>Back to List
      </a>
      @if($wfh_request->canBeCancelled())
        <form action="{{ route('self-service.wfh-requests.cancel', $wfh_request) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this request?')">
          @csrf
          <button type="submit" class="btn btn-danger">
            <i class="ti tabler-x me-1"></i>Cancel Request
          </button>
        </form>
      @endif
    </div>
  </div>
</div>
@endsection

@section('page-style')
<style>
.timeline {
  position: relative;
  padding-left: 30px;
  list-style: none;
}
.timeline-item {
  position: relative;
  padding-bottom: 1.5rem;
}
.timeline-item:before {
  content: '';
  position: absolute;
  left: -23px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #e9ecef;
}
.timeline-item:last-child:before {
  display: none;
}
.timeline-indicator {
  position: absolute;
  left: -30px;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 8px;
  color: white;
}
.timeline-indicator-success { background: #28c76f; }
.timeline-indicator-warning { background: #ff9f43; }
.timeline-indicator-danger { background: #ea5455; }
.timeline-indicator-secondary { background: #6c757d; }
.timeline-event {
  padding-left: 10px;
}
</style>
@endsection
