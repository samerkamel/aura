@extends('layouts/layoutMaster')

@section('title', 'Pending Approvals')

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Page Header -->
    <div class="card mb-4">
      <div class="card-header">
        <div>
          <h5 class="mb-0">
            <i class="ti ti-checklist me-2"></i>Pending Approvals
          </h5>
          <small class="text-muted">Review and approve employee requests</small>
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

    <!-- Pending Manager Approval (for managers) -->
    @if($employee->isManager() && $pendingManagerRequests->isNotEmpty())
    <div class="card mb-4">
      <div class="card-header bg-label-warning">
        <div class="d-flex justify-content-between align-items-center">
          <h6 class="mb-0">
            <i class="ti ti-user-check me-2"></i>Pending Your Approval (as Manager)
          </h6>
          <span class="badge bg-warning">{{ $pendingManagerRequests->count() }}</span>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Employee</th>
              <th>Type</th>
              <th>Date(s)</th>
              <th>Submitted</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($pendingManagerRequests as $request)
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-sm me-2 bg-label-primary">
                    <span class="avatar-initial rounded-circle">
                      {{ strtoupper(substr($request->employee->name ?? 'U', 0, 1)) }}
                    </span>
                  </div>
                  <div>
                    <strong>{{ $request->employee->name }}</strong>
                    <br><small class="text-muted">{{ $request->employee->position->name ?? '' }}</small>
                  </div>
                </div>
              </td>
              <td>
                @php
                  $typeIcon = match($request->request_type) {
                    'leave' => 'ti-calendar-off text-primary',
                    'wfh' => 'ti-home text-success',
                    'permission' => 'ti-clock-pause text-warning',
                    default => 'ti-file text-secondary'
                  };
                @endphp
                <span class="d-flex align-items-center">
                  <i class="ti {{ $typeIcon }} me-2"></i>
                  {{ $request->type_label }}
                </span>
                @if($request->request_type === 'leave' && $request->leavePolicy)
                  <small class="text-muted">{{ $request->leavePolicy->name }}</small>
                @endif
              </td>
              <td>
                {{ $request->start_date->format('M d, Y') }}
                @if($request->end_date && !$request->start_date->isSameDay($request->end_date))
                  <br><small class="text-muted">to {{ $request->end_date->format('M d, Y') }}</small>
                @endif
              </td>
              <td>
                <small class="text-muted">{{ $request->created_at->diffForHumans() }}</small>
              </td>
              <td>
                <a href="{{ route('self-service.approvals.show', $request) }}" class="btn btn-sm btn-primary">
                  <i class="ti ti-eye me-1"></i>Review
                </a>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endif

    <!-- Pending Admin Approval (for super admins) -->
    @if($isSuperAdmin && $pendingAdminRequests->isNotEmpty())
    <div class="card mb-4">
      <div class="card-header bg-label-info">
        <div class="d-flex justify-content-between align-items-center">
          <h6 class="mb-0">
            <i class="ti ti-shield-check me-2"></i>Pending Admin Final Approval
          </h6>
          <span class="badge bg-info">{{ $pendingAdminRequests->count() }}</span>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Employee</th>
              <th>Type</th>
              <th>Date(s)</th>
              <th>Manager Approved</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($pendingAdminRequests as $request)
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-sm me-2 bg-label-primary">
                    <span class="avatar-initial rounded-circle">
                      {{ strtoupper(substr($request->employee->name ?? 'U', 0, 1)) }}
                    </span>
                  </div>
                  <div>
                    <strong>{{ $request->employee->name }}</strong>
                    <br><small class="text-muted">{{ $request->employee->position->name ?? '' }}</small>
                  </div>
                </div>
              </td>
              <td>
                @php
                  $typeIcon = match($request->request_type) {
                    'leave' => 'ti-calendar-off text-primary',
                    'wfh' => 'ti-home text-success',
                    'permission' => 'ti-clock-pause text-warning',
                    default => 'ti-file text-secondary'
                  };
                @endphp
                <span class="d-flex align-items-center">
                  <i class="ti {{ $typeIcon }} me-2"></i>
                  {{ $request->type_label }}
                </span>
                @if($request->request_type === 'leave' && $request->leavePolicy)
                  <small class="text-muted">{{ $request->leavePolicy->name }}</small>
                @endif
              </td>
              <td>
                {{ $request->start_date->format('M d, Y') }}
                @if($request->end_date && !$request->start_date->isSameDay($request->end_date))
                  <br><small class="text-muted">to {{ $request->end_date->format('M d, Y') }}</small>
                @endif
              </td>
              <td>
                @if($request->manager_approved_at)
                  <span class="badge bg-success">
                    <i class="ti ti-check me-1"></i>{{ $request->managerApprover->name ?? 'Manager' }}
                  </span>
                  <br><small class="text-muted">{{ $request->manager_approved_at->format('M d, H:i') }}</small>
                @else
                  <span class="badge bg-secondary">N/A (No Manager)</span>
                @endif
              </td>
              <td>
                <a href="{{ route('self-service.approvals.show', $request) }}" class="btn btn-sm btn-primary">
                  <i class="ti ti-eye me-1"></i>Review
                </a>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endif

    <!-- All Pending Requests (super admin view) -->
    @if($isSuperAdmin && $allPendingRequests->isNotEmpty())
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <h6 class="mb-0">
            <i class="ti ti-list me-2"></i>All Pending Requests (Admin View)
          </h6>
          <span class="badge bg-primary">{{ $allPendingRequests->count() }}</span>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Employee</th>
              <th>Type</th>
              <th>Date(s)</th>
              <th>Current Status</th>
              <th>Submitted</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($allPendingRequests as $request)
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-sm me-2 bg-label-primary">
                    <span class="avatar-initial rounded-circle">
                      {{ strtoupper(substr($request->employee->name ?? 'U', 0, 1)) }}
                    </span>
                  </div>
                  <div>
                    <strong>{{ $request->employee->name }}</strong>
                    <br><small class="text-muted">{{ $request->employee->position->name ?? '' }}</small>
                  </div>
                </div>
              </td>
              <td>
                @php
                  $typeIcon = match($request->request_type) {
                    'leave' => 'ti-calendar-off text-primary',
                    'wfh' => 'ti-home text-success',
                    'permission' => 'ti-clock-pause text-warning',
                    default => 'ti-file text-secondary'
                  };
                @endphp
                <span class="d-flex align-items-center">
                  <i class="ti {{ $typeIcon }} me-2"></i>
                  {{ $request->type_label }}
                </span>
                @if($request->request_type === 'leave' && $request->leavePolicy)
                  <small class="text-muted">{{ $request->leavePolicy->name }}</small>
                @endif
              </td>
              <td>
                {{ $request->start_date->format('M d, Y') }}
                @if($request->end_date && !$request->start_date->isSameDay($request->end_date))
                  <br><small class="text-muted">to {{ $request->end_date->format('M d, Y') }}</small>
                @endif
              </td>
              <td>
                @php
                  $statusClass = match($request->status) {
                    'pending_manager' => 'bg-warning',
                    'pending_admin' => 'bg-info',
                    default => 'bg-secondary'
                  };
                @endphp
                <span class="badge {{ $statusClass }}">{{ $request->status_label }}</span>
              </td>
              <td>
                <small class="text-muted">{{ $request->created_at->diffForHumans() }}</small>
              </td>
              <td>
                <a href="{{ route('self-service.approvals.show', $request) }}" class="btn btn-sm btn-primary">
                  <i class="ti ti-eye me-1"></i>Review
                </a>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endif

    <!-- No Pending Requests -->
    @if(($pendingManagerRequests->isEmpty() || !$employee->isManager()) && ($pendingAdminRequests->isEmpty() || !$isSuperAdmin) && ($allPendingRequests->isEmpty() || !$isSuperAdmin))
    <div class="card">
      <div class="card-body text-center py-5">
        <i class="ti ti-check-square text-success" style="font-size: 3rem;"></i>
        <h5 class="mt-3">All Caught Up!</h5>
        <p class="text-muted">There are no pending requests requiring your approval.</p>
        <a href="{{ route('self-service.dashboard') }}" class="btn btn-outline-primary">
          <i class="ti ti-arrow-left me-1"></i>Back to Dashboard
        </a>
      </div>
    </div>
    @endif
  </div>
</div>
@endsection
