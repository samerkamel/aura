@extends('layouts/layoutMaster')

@section('title', 'Self Service Portal')

@section('page-style')
<style>
  .quick-action-card {
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
  }
  .quick-action-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  }
  .balance-card {
    border-left: 4px solid;
  }
  .balance-card.leave {
    border-left-color: #7367f0;
  }
  .balance-card.wfh {
    border-left-color: #28c76f;
  }
  .balance-card.permission {
    border-left-color: #ff9f43;
  }
</style>
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Welcome Header -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="avatar avatar-lg me-3 bg-label-primary">
            <span class="avatar-initial rounded-circle">
              {{ strtoupper(substr($employee->name ?? 'U', 0, 1)) }}
            </span>
          </div>
          <div class="flex-grow-1">
            <h4 class="mb-1">Welcome, {{ $employee->name }}</h4>
            <p class="text-muted mb-0">
              {{ $employee->position->name ?? 'Employee' }}
              @if($employee->department)
                - {{ $employee->department->name }}
              @endif
            </p>
          </div>
          <div class="d-none d-md-block">
            <span class="badge bg-label-info fs-6">
              <i class="ti ti-calendar me-1"></i>
              {{ now()->format('l, F j, Y') }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Balances Overview -->
    <div class="row mb-4">
      <!-- Leave Balance -->
      @foreach($leaveBalances as $balance)
      <div class="col-md-4 col-sm-6 mb-3">
        <div class="card balance-card leave h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <p class="text-muted mb-1">{{ $balance['policy_name'] }}</p>
                <h3 class="mb-0">
                  {{ number_format($balance['remaining_days'], 1) }}
                  <small class="text-muted fs-6">/ {{ number_format($balance['entitled_days'], 1) }} days</small>
                </h3>
              </div>
              <div class="avatar bg-label-primary">
                <span class="avatar-initial rounded">
                  <i class="ti ti-calendar-off"></i>
                </span>
              </div>
            </div>
            <div class="progress mt-3" style="height: 6px;">
              @php
                $usedPercent = $balance['entitled_days'] > 0 ? (($balance['entitled_days'] - $balance['remaining_days']) / $balance['entitled_days']) * 100 : 0;
              @endphp
              <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $usedPercent }}%" aria-valuenow="{{ $usedPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <small class="text-muted d-block mt-2">
              {{ number_format($balance['used_days'], 1) }} used
            </small>
          </div>
        </div>
      </div>
      @endforeach

      <!-- WFH Allowance -->
      <div class="col-md-4 col-sm-6 mb-3">
        <div class="card balance-card wfh h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <p class="text-muted mb-1">WFH This Month</p>
                <h3 class="mb-0">
                  {{ $wfhData['remaining'] }}
                  <small class="text-muted fs-6">/ {{ $wfhData['allowance'] }} days</small>
                </h3>
              </div>
              <div class="avatar bg-label-success">
                <span class="avatar-initial rounded">
                  <i class="ti ti-home"></i>
                </span>
              </div>
            </div>
            <div class="progress mt-3" style="height: 6px;">
              @php
                $wfhUsedPercent = $wfhData['allowance'] > 0 ? (($wfhData['used'] + $wfhData['pending']) / $wfhData['allowance']) * 100 : 0;
              @endphp
              <div class="progress-bar bg-success" role="progressbar" style="width: {{ $wfhUsedPercent }}%" aria-valuenow="{{ $wfhUsedPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <small class="text-muted d-block mt-2">
              {{ $wfhData['used'] }} used
              @if($wfhData['pending'] > 0)
                , {{ $wfhData['pending'] }} pending
              @endif
            </small>
          </div>
        </div>
      </div>

      <!-- Permission Allowance -->
      <div class="col-md-4 col-sm-6 mb-3">
        <div class="card balance-card permission h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <p class="text-muted mb-1">Permissions This Month</p>
                <h3 class="mb-0">
                  {{ $permissionData['remaining'] }}
                  <small class="text-muted fs-6">/ {{ $permissionData['allowance'] }}</small>
                </h3>
              </div>
              <div class="avatar bg-label-warning">
                <span class="avatar-initial rounded">
                  <i class="ti ti-clock-pause"></i>
                </span>
              </div>
            </div>
            <div class="progress mt-3" style="height: 6px;">
              @php
                $permUsedPercent = $permissionData['allowance'] > 0 ? (($permissionData['used'] + $permissionData['pending']) / $permissionData['allowance']) * 100 : 0;
              @endphp
              <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $permUsedPercent }}%" aria-valuenow="{{ $permUsedPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <small class="text-muted d-block mt-2">
              {{ $permissionData['used'] }} used ({{ $permissionData['minutes_per_permission'] }}m each)
              @if($permissionData['pending'] > 0)
                , {{ $permissionData['pending'] }} pending
              @endif
            </small>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
      <div class="col-12">
        <h5 class="mb-3"><i class="ti ti-bolt me-2"></i>Quick Actions</h5>
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <a href="{{ route('self-service.leave-requests.create') }}" class="text-decoration-none">
          <div class="card quick-action-card bg-label-primary h-100">
            <div class="card-body text-center py-4">
              <div class="avatar avatar-lg mb-3 bg-primary">
                <span class="avatar-initial rounded-circle">
                  <i class="ti ti-calendar-plus"></i>
                </span>
              </div>
              <h6 class="mb-1">Request Leave</h6>
              <small class="text-muted">Submit vacation request</small>
            </div>
          </div>
        </a>
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <a href="{{ route('self-service.wfh-requests.create') }}" class="text-decoration-none">
          <div class="card quick-action-card bg-label-success h-100">
            <div class="card-body text-center py-4">
              <div class="avatar avatar-lg mb-3 bg-success">
                <span class="avatar-initial rounded-circle">
                  <i class="ti ti-home-plus"></i>
                </span>
              </div>
              <h6 class="mb-1">Request WFH</h6>
              <small class="text-muted">Work from home</small>
            </div>
          </div>
        </a>
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <a href="{{ route('self-service.permission-requests.create') }}" class="text-decoration-none">
          <div class="card quick-action-card bg-label-warning h-100">
            <div class="card-body text-center py-4">
              <div class="avatar avatar-lg mb-3 bg-warning">
                <span class="avatar-initial rounded-circle">
                  <i class="ti ti-clock-plus"></i>
                </span>
              </div>
              <h6 class="mb-1">Request Permission</h6>
              <small class="text-muted">Late arrival/Early leave</small>
            </div>
          </div>
        </a>
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <a href="{{ route('self-service.attendance') }}" class="text-decoration-none">
          <div class="card quick-action-card bg-label-info h-100">
            <div class="card-body text-center py-4">
              <div class="avatar avatar-lg mb-3 bg-info">
                <span class="avatar-initial rounded-circle">
                  <i class="ti ti-calendar-stats"></i>
                </span>
              </div>
              <h6 class="mb-1">My Attendance</h6>
              <small class="text-muted">View records</small>
            </div>
          </div>
        </a>
      </div>
    </div>

    <div class="row">
      <!-- Recent Requests -->
      <div class="col-lg-8 mb-4">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="ti ti-list me-2"></i>Recent Requests</h5>
            <div class="dropdown">
              <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                View All
              </button>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="{{ route('self-service.leave-requests.index') }}">Leave Requests</a></li>
                <li><a class="dropdown-item" href="{{ route('self-service.wfh-requests.index') }}">WFH Requests</a></li>
                <li><a class="dropdown-item" href="{{ route('self-service.permission-requests.index') }}">Permission Requests</a></li>
              </ul>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead class="table-light">
                <tr>
                  <th>Type</th>
                  <th>Date(s)</th>
                  <th>Status</th>
                  <th>Submitted</th>
                </tr>
              </thead>
              <tbody>
                @forelse($recentRequests as $request)
                <tr>
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
                      @if($request->request_type === 'leave' && $request->leavePolicy)
                        <br><small class="text-muted">{{ $request->leavePolicy->name }}</small>
                      @endif
                    </span>
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
                        'pending_manager', 'pending_admin' => 'bg-label-warning',
                        'approved' => 'bg-label-success',
                        'rejected' => 'bg-label-danger',
                        'cancelled' => 'bg-label-secondary',
                        default => 'bg-label-secondary'
                      };
                    @endphp
                    <span class="badge {{ $statusClass }}">{{ $request->status_label }}</span>
                  </td>
                  <td>
                    <small class="text-muted">{{ $request->created_at->diffForHumans() }}</small>
                  </td>
                </tr>
                @empty
                <tr>
                  <td colspan="4" class="text-center py-4">
                    <i class="ti ti-inbox text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2 mb-0">No recent requests</p>
                  </td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Pending Approvals (for managers) -->
      @php
        $pendingApprovalCount = $pendingApprovalsCount + $pendingAdminApprovalsCount;
      @endphp
      <div class="col-lg-4 mb-4">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="ti ti-checklist me-2"></i>Pending Approvals</h5>
            @if($pendingApprovalCount > 0)
              <span class="badge bg-danger">{{ $pendingApprovalCount }}</span>
            @endif
          </div>
          <div class="card-body">
            @if($pendingApprovalCount > 0)
              <div class="alert alert-warning d-flex align-items-center">
                <i class="ti ti-alert-circle me-2"></i>
                <div>
                  You have <strong>{{ $pendingApprovalCount }}</strong> request(s) waiting for your approval.
                </div>
              </div>
              <a href="{{ route('self-service.approvals.index') }}" class="btn btn-primary w-100">
                <i class="ti ti-eye me-1"></i>View Pending Requests
              </a>
            @else
              <div class="text-center py-4">
                <i class="ti ti-check text-success" style="font-size: 2rem;"></i>
                <p class="text-muted mt-2 mb-0">No pending approvals</p>
              </div>
            @endif
          </div>
        </div>

        <!-- Manager Info (if has manager) -->
        @if($employee->manager)
        <div class="card mt-4">
          <div class="card-header">
            <h6 class="mb-0"><i class="ti ti-user-check me-2"></i>Your Manager</h6>
          </div>
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="avatar avatar-md me-3 bg-label-primary">
                <span class="avatar-initial rounded-circle">
                  {{ strtoupper(substr($employee->manager->name ?? 'M', 0, 1)) }}
                </span>
              </div>
              <div>
                <h6 class="mb-0">{{ $employee->manager->name }}</h6>
                <small class="text-muted">{{ $employee->manager->position->name ?? '' }}</small>
              </div>
            </div>
            <small class="text-muted d-block mt-3">
              <i class="ti ti-info-circle me-1"></i>
              Your requests will be sent to your manager for initial approval.
            </small>
          </div>
        </div>
        @else
        <div class="card mt-4">
          <div class="card-body">
            <small class="text-muted">
              <i class="ti ti-info-circle me-1"></i>
              Your requests will be sent directly to admin for approval.
            </small>
          </div>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
