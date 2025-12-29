@extends('layouts/layoutMaster')

@section('title', 'Review Request')

@section('content')
<div class="row">
  <div class="col-12 col-lg-8 mx-auto">
    <!-- Page Header -->
    <div class="card mb-4">
      <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center">
            <a href="{{ route('self-service.approvals.index') }}" class="btn btn-icon btn-outline-secondary me-3">
              <i class="ti tabler-arrow-left"></i>
            </a>
            <div>
              <h5 class="mb-0">
                <i class="ti tabler-checklist me-2"></i>Review Request
              </h5>
              <small class="text-muted">Request #{{ $selfServiceRequest->id }}</small>
            </div>
          </div>
          @php
            $statusClass = match($selfServiceRequest->status) {
              'pending_manager' => 'bg-warning',
              'pending_admin' => 'bg-info',
              'approved' => 'bg-success',
              'rejected' => 'bg-danger',
              'cancelled' => 'bg-secondary',
              default => 'bg-secondary'
            };
          @endphp
          <span class="badge {{ $statusClass }} fs-6">{{ $selfServiceRequest->status_label }}</span>
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

    <!-- Employee Info -->
    <div class="card mb-4">
      <div class="card-header">
        <h6 class="mb-0"><i class="ti tabler-user me-2"></i>Employee Information</h6>
      </div>
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="avatar avatar-lg me-3 bg-label-primary">
            <span class="avatar-initial rounded-circle">
              {{ strtoupper(substr($selfServiceRequest->employee->name ?? 'U', 0, 1)) }}
            </span>
          </div>
          <div>
            <h5 class="mb-1">{{ $selfServiceRequest->employee->name }}</h5>
            <p class="text-muted mb-0">
              {{ $selfServiceRequest->employee->position->name ?? 'Employee' }}
              @if($selfServiceRequest->employee->department)
                - {{ $selfServiceRequest->employee->department->name }}
              @endif
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Request Details -->
    <div class="card mb-4">
      <div class="card-header">
        <h6 class="mb-0"><i class="ti tabler-info-circle me-2"></i>Request Details</h6>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="text-muted d-block mb-1">Request Type</label>
            @php
              $typeIcon = match($selfServiceRequest->request_type) {
                'leave' => 'ti-calendar-off text-primary',
                'wfh' => 'ti-home text-success',
                'permission' => 'ti-clock-pause text-warning',
                default => 'ti-file text-secondary'
              };
            @endphp
            <strong>
              <i class="ti {{ $typeIcon }} me-2"></i>{{ $selfServiceRequest->type_label }}
            </strong>
            @if($selfServiceRequest->request_type === 'leave' && $selfServiceRequest->leavePolicy)
              <br><small class="text-muted">{{ $selfServiceRequest->leavePolicy->name }}</small>
            @endif
          </div>
          <div class="col-md-6 mb-3">
            <label class="text-muted d-block mb-1">Duration</label>
            <strong>
              @if($selfServiceRequest->request_type === 'permission')
                {{ $selfServiceRequest->request_data['minutes'] ?? 120 }} minutes
              @else
                {{ $selfServiceRequest->days_count }} day(s)
              @endif
            </strong>
          </div>
          <div class="col-md-6 mb-3">
            <label class="text-muted d-block mb-1">Start Date</label>
            <strong>{{ $selfServiceRequest->start_date->format('l, M d, Y') }}</strong>
          </div>
          <div class="col-md-6 mb-3">
            <label class="text-muted d-block mb-1">End Date</label>
            <strong>{{ $selfServiceRequest->end_date->format('l, M d, Y') }}</strong>
          </div>
          <div class="col-12 mb-3">
            <label class="text-muted d-block mb-1">Submitted</label>
            <strong>{{ $selfServiceRequest->created_at->format('M d, Y H:i') }}</strong>
            <small class="text-muted">({{ $selfServiceRequest->created_at->diffForHumans() }})</small>
          </div>
          <div class="col-12">
            <label class="text-muted d-block mb-1">Notes</label>
            <p class="mb-0">{{ $selfServiceRequest->notes ?: 'No notes provided' }}</p>
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
                {{ $selfServiceRequest->created_at->format('M d, Y H:i') }}
              </p>
            </div>
          </li>

          <!-- Manager Approval -->
          @if($selfServiceRequest->manager_id)
          <li class="timeline-item">
            @if($selfServiceRequest->manager_approved_at)
              <span class="timeline-indicator timeline-indicator-success">
                <i class="ti tabler-check"></i>
              </span>
              <div class="timeline-event">
                <div class="timeline-header">
                  <h6 class="mb-0">Manager Approved</h6>
                </div>
                <p class="text-muted mb-0">
                  Approved by {{ $selfServiceRequest->managerApprover->name ?? 'Manager' }}
                  on {{ $selfServiceRequest->manager_approved_at->format('M d, Y H:i') }}
                </p>
              </div>
            @elseif($selfServiceRequest->status === 'pending_manager')
              <span class="timeline-indicator timeline-indicator-warning">
                <i class="ti tabler-clock"></i>
              </span>
              <div class="timeline-event">
                <div class="timeline-header">
                  <h6 class="mb-0">Pending Manager Approval</h6>
                </div>
                <p class="text-muted mb-0">
                  Waiting for {{ $selfServiceRequest->manager->name ?? 'Manager' }}
                </p>
              </div>
            @endif
          </li>
          @endif

          <!-- Admin Approval -->
          <li class="timeline-item">
            @if($selfServiceRequest->admin_approved_at)
              <span class="timeline-indicator timeline-indicator-success">
                <i class="ti tabler-check"></i>
              </span>
              <div class="timeline-event">
                <div class="timeline-header">
                  <h6 class="mb-0">Admin Approved (Final)</h6>
                </div>
                <p class="text-muted mb-0">
                  Approved by {{ $selfServiceRequest->adminApprover->name ?? 'Admin' }}
                  on {{ $selfServiceRequest->admin_approved_at->format('M d, Y H:i') }}
                </p>
              </div>
            @elseif($selfServiceRequest->status === 'pending_admin')
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
            @endif
          </li>
        </ul>
      </div>
    </div>

    <!-- Action Buttons -->
    @if($selfServiceRequest->isPending())
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0"><i class="ti tabler-settings me-2"></i>Actions</h6>
      </div>
      <div class="card-body">
        @if($canApproveAsManager || $canApproveAsAdmin)
        <div class="row">
          <div class="col-md-6 mb-3">
            <form action="{{ route('self-service.approvals.approve', $selfServiceRequest) }}" method="POST" onsubmit="return confirm('Are you sure you want to approve this request?')">
              @csrf
              <button type="submit" class="btn btn-success w-100 btn-lg">
                <i class="ti tabler-check me-2"></i>
                @if($canApproveAsManager && !$isSuperAdmin)
                  Approve (Forward to Admin)
                @else
                  Approve (Final)
                @endif
              </button>
            </form>
            <small class="text-muted d-block mt-2">
              @if($canApproveAsManager && !$isSuperAdmin)
                This will forward the request to admin for final approval.
              @else
                This will finalize the request and create the corresponding record.
              @endif
            </small>
          </div>
          <div class="col-md-6 mb-3">
            <button type="button" class="btn btn-danger w-100 btn-lg" data-bs-toggle="modal" data-bs-target="#rejectModal">
              <i class="ti tabler-x me-2"></i>Reject
            </button>
            <small class="text-muted d-block mt-2">
              Reject this request with a reason.
            </small>
          </div>
        </div>
        @else
        <div class="alert alert-warning mb-0">
          <i class="ti tabler-alert-circle me-2"></i>
          You do not have permission to approve or reject this request at its current status.
        </div>
        @endif
      </div>
    </div>
    @endif

    <!-- Back Button -->
    <div class="mt-4">
      <a href="{{ route('self-service.approvals.index') }}" class="btn btn-outline-secondary">
        <i class="ti tabler-arrow-left me-1"></i>Back to Approvals
      </a>
    </div>
  </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="{{ route('self-service.approvals.reject', $selfServiceRequest) }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="rejectModalLabel">
            <i class="ti tabler-x me-2 text-danger"></i>Reject Request
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted">Please provide a reason for rejecting this request. This will be visible to the employee.</p>
          <div class="mb-3">
            <label class="form-label" for="rejection_reason">Rejection Reason <span class="text-danger">*</span></label>
            <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required maxlength="500" placeholder="Enter the reason for rejection..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">
            <i class="ti tabler-x me-1"></i>Reject Request
          </button>
        </div>
      </form>
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
