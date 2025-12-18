@extends('layouts/layoutMaster')

@section('title', 'Employee Details - ' . $employee->name)

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Employee Header Card -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <i class="ti ti-user me-2 text-primary" style="font-size: 1.5rem;"></i>
          <div>
            <h5 class="mb-0">{{ $employee->name }}</h5>
            <small class="text-muted">
              @if($employee->position_id && $employee->positionRelation)
                {{ $employee->positionRelation->full_title }}
                @if($employee->positionRelation->department)
                  <span class="badge bg-label-info ms-1">{{ $employee->positionRelation->department }}</span>
                @endif
              @elseif($employee->position && is_string($employee->position))
                {{ $employee->position }}
              @else
                No Position Assigned
              @endif
            </small>
          </div>
        </div>
        <div class="d-flex gap-2">
          <a href="{{ route('documents.select-template', $employee) }}" class="btn btn-success">
            <i class="ti ti-file-text me-1"></i>Generate Document
          </a>
          <a href="{{ route('hr.employees.edit', $employee) }}" class="btn btn-primary">
            <i class="ti ti-edit me-1"></i>Edit Employee
          </a>
          @if($employee->status === 'active')
            <a href="{{ route('hr.employees.offboarding.show', $employee) }}" class="btn btn-warning">
              <i class="ti ti-user-off me-1"></i>Process Off-boarding
            </a>
          @endif
          <a href="{{ route('hr.employees.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Back to List
          </a>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Personal Information -->
      <div class="col-md-6 mb-4">
        <div class="card h-100">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="ti ti-id me-2"></i>Personal Information
            </h6>
          </div>
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Full Name:</strong>
              </div>
              <div class="col-sm-8">
                {{ $employee->name }}
              </div>
            </div>
            @if($employee->name_ar)
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Arabic Name:</strong>
              </div>
              <div class="col-sm-8" dir="rtl">
                {{ $employee->name_ar }}
              </div>
            </div>
            @endif
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Work Email:</strong>
              </div>
              <div class="col-sm-8">
                <a href="mailto:{{ $employee->email }}">{{ $employee->email }}</a>
              </div>
            </div>
            @if($employee->personal_email)
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Personal Email:</strong>
              </div>
              <div class="col-sm-8">
                <a href="mailto:{{ $employee->personal_email }}">{{ $employee->personal_email }}</a>
              </div>
            </div>
            @endif
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Attendance ID:</strong>
              </div>
              <div class="col-sm-8">
                @if($employee->attendance_id)
                  <span class="font-monospace">{{ $employee->attendance_id }}</span>
                @else
                  <span class="text-muted">Not Set</span>
                @endif
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>National ID:</strong>
              </div>
              <div class="col-sm-8">
                @if($employee->national_id)
                  <span class="font-monospace">{{ $employee->national_id }}</span>
                @else
                  <span class="text-muted">Not Set</span>
                @endif
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>NIN:</strong>
              </div>
              <div class="col-sm-8">
                @if($employee->national_insurance_number)
                  <span class="font-monospace">{{ $employee->national_insurance_number }}</span>
                @else
                  <span class="text-muted">Not Set</span>
                @endif
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Position:</strong>
              </div>
              <div class="col-sm-8">
                @if($employee->position_id && $employee->positionRelation)
                  <a href="{{ route('hr.positions.show', $employee->positionRelation) }}" class="text-decoration-none">
                    <span class="badge bg-label-primary">{{ $employee->positionRelation->full_title }}</span>
                  </a>
                  @if($employee->positionRelation->department)
                    <small class="text-muted ms-1">({{ $employee->positionRelation->department }})</small>
                  @endif
                @elseif($employee->position && is_string($employee->position))
                  <span class="badge bg-label-secondary">{{ $employee->position }}</span>
                @else
                  <span class="text-muted">Not Assigned</span>
                @endif
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Status:</strong>
              </div>
              <div class="col-sm-8">
                @if($employee->status === 'active')
                  <span class="badge bg-success">Active</span>
                @elseif($employee->status === 'terminated')
                  <span class="badge bg-danger">Terminated</span>
                @else
                  <span class="badge bg-warning">Resigned</span>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Employment Details -->
      <div class="col-md-6 mb-4">
        <div class="card h-100">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="ti ti-briefcase me-2"></i>Employment Details
            </h6>
          </div>
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Start Date:</strong>
              </div>
              <div class="col-sm-8">
                {{ $employee->start_date ? $employee->start_date->format('F d, Y') : 'Not Set' }}
              </div>
            </div>
            @if($canViewSalary)
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Base Salary:</strong>
              </div>
              <div class="col-sm-8">
                @if($employee->base_salary)
                  <span class="text-success fw-bold">EGP {{ number_format($employee->base_salary, 2) }}</span>
                @else
                  <span class="text-muted">Not Set</span>
                @endif
              </div>
            </div>
            @endif
            @if($employee->termination_date)
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Termination Date:</strong>
              </div>
              <div class="col-sm-8">
                <span class="text-danger">{{ $employee->termination_date->format('F d, Y') }}</span>
              </div>
            </div>
            @endif
          </div>
        </div>
      </div>

      <!-- Contact Information -->
      @if($employee->contact_info)
      <div class="col-md-6 mb-4">
        <div class="card h-100">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="ti ti-phone me-2"></i>Contact Information
            </h6>
          </div>
          <div class="card-body">
            @if(isset($employee->contact_info['mobile_number']) || isset($employee->contact_info['phone']))
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Mobile Number:</strong>
              </div>
              <div class="col-sm-8">
                @php $mobileNumber = $employee->contact_info['mobile_number'] ?? $employee->contact_info['phone'] ?? null; @endphp
                <a href="tel:{{ $mobileNumber }}">{{ $mobileNumber }}</a>
              </div>
            </div>
            @endif
            @if(isset($employee->contact_info['secondary_number']))
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Secondary Number:</strong>
              </div>
              <div class="col-sm-8">
                <a href="tel:{{ $employee->contact_info['secondary_number'] }}">{{ $employee->contact_info['secondary_number'] }}</a>
              </div>
            </div>
            @endif
            @if(isset($employee->contact_info['current_address']))
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Current Address:</strong>
              </div>
              <div class="col-sm-8">
                {{ $employee->contact_info['current_address'] }}
              </div>
            </div>
            @elseif(isset($employee->contact_info['address']))
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Current Address:</strong>
              </div>
              <div class="col-sm-8">
                {{ $employee->contact_info['address'] }}
              </div>
            </div>
            @endif
            @if(isset($employee->contact_info['permanent_address']))
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Permanent Address:</strong>
              </div>
              <div class="col-sm-8">
                {{ $employee->contact_info['permanent_address'] }}
              </div>
            </div>
            @endif
          </div>
        </div>
      </div>
      @endif

      <!-- Bank Information -->
      @if($employee->bank_info)
      <div class="col-md-6 mb-4">
        <div class="card h-100">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="ti ti-building-bank me-2"></i>Bank Information
            </h6>
          </div>
          <div class="card-body">
            @if(isset($employee->bank_info['bank_name']))
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Bank Name:</strong>
              </div>
              <div class="col-sm-8">
                {{ $employee->bank_info['bank_name'] }}
              </div>
            </div>
            @endif
            @if(isset($employee->bank_info['account_number']))
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Account Number:</strong>
              </div>
              <div class="col-sm-8">
                <span class="font-monospace">{{ $employee->bank_info['account_number'] }}</span>
              </div>
            </div>
            @endif
            @if(isset($employee->bank_info['account_id']))
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Account ID:</strong>
              </div>
              <div class="col-sm-8">
                <span class="font-monospace">{{ $employee->bank_info['account_id'] }}</span>
              </div>
            </div>
            @endif
            @if(isset($employee->bank_info['iban']))
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>IBAN:</strong>
              </div>
              <div class="col-sm-8">
                <span class="font-monospace">{{ $employee->bank_info['iban'] }}</span>
              </div>
            </div>
            @endif
          </div>
        </div>
      </div>
      @endif

      <!-- Emergency Contact -->
      @if($employee->emergency_contact && (isset($employee->emergency_contact['name']) || isset($employee->emergency_contact['phone'])))
      <div class="col-md-6 mb-4">
        <div class="card h-100">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="ti ti-urgent me-2"></i>Emergency Contact
            </h6>
          </div>
          <div class="card-body">
            @if(isset($employee->emergency_contact['name']))
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Name:</strong>
              </div>
              <div class="col-sm-8">
                {{ $employee->emergency_contact['name'] }}
              </div>
            </div>
            @endif
            @if(isset($employee->emergency_contact['phone']))
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Phone:</strong>
              </div>
              <div class="col-sm-8">
                <a href="tel:{{ $employee->emergency_contact['phone'] }}">{{ $employee->emergency_contact['phone'] }}</a>
              </div>
            </div>
            @endif
            @if(isset($employee->emergency_contact['relationship']))
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Relationship:</strong>
              </div>
              <div class="col-sm-8">
                {{ $employee->emergency_contact['relationship'] }}
              </div>
            </div>
            @endif
          </div>
        </div>
      </div>
      @endif
    </div>

    @can('manage-leave-records')
    <!-- Leave & WFH Management Section -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
              <i class="ti ti-calendar-event me-2 text-info"></i>Leave & Work From Home Management
            </h6>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#logLeaveModal">
                <i class="ti ti-calendar-plus me-1"></i>Log Leave
              </button>
              <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#logWfhModal">
                <i class="ti ti-home me-1"></i>Log WFH Days
              </button>
            </div>
          </div>
          <div class="card-body">
            <!-- Leave Balance Summary -->
            <div class="row mb-4">
              <div class="col-12">
                <h6 class="text-muted mb-3">Leave Balance Summary for {{ now()->year }}</h6>
                <div id="leave-balance-container">
                  @php
                    $leaveBalanceService = app(\Modules\Leave\Services\LeaveBalanceService::class);
                    $balanceSummary = $leaveBalanceService->getLeaveBalanceSummary($employee);
                  @endphp

                  @if(count($balanceSummary['balances']) > 0)
                  <div class="row">
                    @foreach($balanceSummary['balances'] as $balance)
                    <div class="col-md-4 mb-3">
                      <div class="card border-start border-{{ $balance['remaining_days'] > 0 ? 'success' : 'warning' }}">
                        <div class="card-body">
                          <h6 class="card-title">{{ $balance['policy_name'] }}</h6>
                          <div class="d-flex justify-content-between">
                            <small class="text-muted">Entitled:</small>
                            <span class="badge bg-primary">{{ $balance['entitled_days'] }}</span>
                          </div>
                          <div class="d-flex justify-content-between">
                            <small class="text-muted">Used:</small>
                            <span class="badge bg-secondary">{{ $balance['used_days'] }}</span>
                          </div>
                          <div class="d-flex justify-content-between">
                            <small class="text-muted">Remaining:</small>
                            <span class="badge bg-{{ $balance['remaining_days'] > 0 ? 'success' : 'warning' }}">
                              {{ $balance['remaining_days'] }}
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                    @endforeach
                  </div>
                  @else
                  <div class="alert alert-info">
                    <i class="ti ti-info-circle me-2"></i>
                    No leave policies configured for this employee.
                  </div>
                  @endif
                </div>
              </div>
            </div>

            <!-- Recent Leave Records -->
            <div class="row mb-4">
              <div class="col-12">
                <h6 class="text-muted mb-3">Recent Leave Records</h6>
                <div id="recent-leave-records">
                  @php
                    $recentLeaveRecords = \Modules\Leave\Models\LeaveRecord::where('employee_id', $employee->id)
                        ->with(['leavePolicy', 'createdBy'])
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get();
                  @endphp

                  @if($recentLeaveRecords->count() > 0)
                  <div class="table-responsive">
                    <table class="table table-sm">
                      <thead class="table-light">
                        <tr>
                          <th>Leave Type</th>
                          <th>Start Date</th>
                          <th>End Date</th>
                          <th>Days</th>
                          <th>Status</th>
                          <th>Logged By</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach($recentLeaveRecords as $record)
                        <tr>
                          <td>{{ $record->leavePolicy->name }}</td>
                          <td>{{ $record->start_date->format('M d, Y') }}</td>
                          <td>{{ $record->end_date->format('M d, Y') }}</td>
                          <td>{{ $record->getDaysCount() }}</td>
                          <td>
                            <span class="badge bg-{{ $record->status === 'approved' ? 'success' : ($record->status === 'rejected' ? 'danger' : 'warning') }}">
                              {{ ucfirst($record->status) }}
                            </span>
                          </td>
                          <td>{{ $record->createdBy->name }}</td>
                          <td>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteLeaveRecord({{ $record->id }})">
                              <i class="ti ti-trash"></i>
                            </button>
                          </td>
                        </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                  @else
                  <div class="text-center py-3">
                    <i class="ti ti-calendar-off display-6 text-muted mb-2"></i>
                    <p class="text-muted">No leave records found</p>
                  </div>
                  @endif
                </div>
              </div>
            </div>

            <!-- Recent WFH Records -->
            <div class="row">
              <div class="col-12">
                <h6 class="text-muted mb-3">Recent WFH Days</h6>
                <div id="recent-wfh-records">
                  @php
                    $recentWfhRecords = \Modules\Attendance\Models\WfhRecord::where('employee_id', $employee->id)
                        ->with('createdBy')
                        ->orderBy('date', 'desc')
                        ->limit(10)
                        ->get();
                  @endphp

                  @if($recentWfhRecords->count() > 0)
                  <div class="d-flex flex-wrap gap-2">
                    @foreach($recentWfhRecords as $record)
                    <div class="badge bg-light text-dark border">
                      {{ $record->date->format('M d, Y') }}
                      <button class="btn btn-sm btn-link p-0 ms-1 text-danger" onclick="deleteWfhRecord({{ $record->id }})">
                        <i class="ti ti-x"></i>
                      </button>
                    </div>
                    @endforeach
                  </div>
                  @else
                  <div class="text-center py-3">
                    <i class="ti ti-home-off display-6 text-muted mb-2"></i>
                    <p class="text-muted">No WFH days recorded</p>
                  </div>
                  @endif
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    @endcan

    <!-- Permission Overrides Section (Super Admin Only) -->
    @can('manage-permission-overrides')
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
              <i class="ti ti-clock-plus me-2 text-warning"></i>Extra Permissions - {{ now()->format('F Y') }}
            </h6>
            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#addExtraPermissionModal">
              <i class="ti ti-plus me-1"></i>Add Extra Permission
            </button>
          </div>
          <div class="card-body">
            <div id="permission-overrides-container">
              @php
                $currentOverrides = \Modules\Attendance\Models\PermissionOverride::where('employee_id', $employee->id)
                    ->where('payroll_period_start_date', now()->startOfMonth())
                    ->with('grantedBy')
                    ->get();
                $totalExtraPermissions = $currentOverrides->sum('extra_permissions_granted');
              @endphp

              @if($currentOverrides->count() > 0)
              <div class="alert alert-info mb-3">
                <i class="ti ti-info-circle me-2"></i>
                <strong>Total Extra Permissions for {{ now()->format('F Y') }}:</strong> {{ $totalExtraPermissions }}
              </div>

              <div class="table-responsive">
                <table class="table table-sm">
                  <thead class="table-light">
                    <tr>
                      <th>Date Granted</th>
                      <th>Extra Permissions</th>
                      <th>Granted By</th>
                      <th>Reason</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($currentOverrides as $override)
                    <tr>
                      <td>
                        <i class="ti ti-calendar me-1 text-primary"></i>
                        {{ $override->created_at->format('M d, Y') }}
                        <small class="text-muted d-block">{{ $override->created_at->format('g:i A') }}</small>
                      </td>
                      <td>
                        <span class="badge bg-warning">+{{ $override->extra_permissions_granted }}</span>
                      </td>
                      <td>{{ $override->grantedBy->name }}</td>
                      <td>{{ $override->reason ?: 'No reason provided' }}</td>
                    </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
              @else
              <div class="text-center py-4">
                <i class="ti ti-clock-off display-6 text-muted mb-3"></i>
                <h6 class="text-muted">No extra permissions granted this month</h6>
                <p class="text-muted small">Click "Add Extra Permission" to grant additional permissions beyond the standard monthly allowance.</p>
              </div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
    @endcan

    @if($canViewSalary)
    <!-- Salary History Section (Permission Protected) -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="ti ti-chart-line me-2"></i>Salary History
            </h6>
          </div>
          <div class="card-body">
            @if($employee->salaryHistories->count() > 0)
            <div class="table-responsive">
              <table class="table table-hover">
                <thead class="table-light">
                  <tr>
                    <th>Change Date</th>
                    <th>Old Salary</th>
                    <th>New Salary</th>
                    <th>Change</th>
                    <th>Reason</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($employee->salaryHistories->sortByDesc('change_date') as $history)
                  <tr>
                    <td>
                      <i class="ti ti-calendar me-2 text-primary"></i>
                      {{ $history->change_date->format('M d, Y') }}
                      <small class="text-muted d-block">{{ $history->change_date->format('g:i A') }}</small>
                    </td>
                    <td>
                      <span class="text-muted">EGP {{ $history->formatted_old_salary }}</span>
                    </td>
                    <td>
                      <span class="fw-bold text-success">EGP {{ $history->formatted_new_salary }}</span>
                    </td>
                    <td>
                      @if($history->salary_change >= 0)
                        <span class="badge bg-success">
                          <i class="ti ti-arrow-up me-1"></i>EGP {{ $history->formatted_salary_change }}
                        </span>
                      @else
                        <span class="badge bg-danger">
                          <i class="ti ti-arrow-down me-1"></i>EGP {{ $history->formatted_salary_change }}
                        </span>
                      @endif
                    </td>
                    <td>
                      {{ $history->reason ?: 'No reason provided' }}
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            @else
            <div class="text-center py-4">
              <i class="ti ti-chart-line text-muted" style="font-size: 3rem;"></i>
              <h6 class="mt-2">No salary history</h6>
              <p class="text-muted">Salary changes will appear here when the employee's base salary is updated.</p>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>
    @endif

    <!-- Assigned Assets Section -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
              <i class="ti ti-device-laptop me-2 text-info"></i>Assigned Assets
            </h6>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#assignAssetModal">
                <i class="ti ti-plus me-1"></i>Assign Asset
              </button>
              <a href="{{ route('assetmanager.assets.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="ti ti-device-laptop me-1"></i>Manage Assets
              </a>
            </div>
          </div>
          <div class="card-body">
            @php
              $assignedAssets = \Modules\AssetManager\Models\Asset::whereHas('employees', function($query) use ($employee) {
                  $query->where('employee_id', $employee->id)
                        ->whereNull('returned_date');
              })->with(['employees' => function($query) use ($employee) {
                  $query->where('employee_id', $employee->id)
                        ->whereNull('returned_date');
              }])->get();
            @endphp

            @if($assignedAssets->count() > 0)
            <div class="table-responsive">
              <table class="table table-hover">
                <thead class="table-light">
                  <tr>
                    <th>Asset</th>
                    <th>Type</th>
                    <th>Serial Number</th>
                    <th>Assigned Date</th>
                    <th>Condition</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($assignedAssets as $asset)
                  @php
                    $assignment = $asset->employees->first();
                  @endphp
                  <tr>
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-2">
                          <span class="avatar-initial rounded bg-label-info">
                            <i class="ti ti-device-laptop"></i>
                          </span>
                        </div>
                        <div>
                          <h6 class="mb-0">{{ $asset->name }}</h6>
                          @if($asset->brand)
                            <small class="text-muted">{{ $asset->brand }}</small>
                          @endif
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark">{{ ucwords(str_replace('_', ' ', $asset->type)) }}</span>
                    </td>
                    <td>
                      <span class="font-monospace">{{ $asset->serial_number }}</span>
                    </td>
                    <td>
                      <div>
                        <i class="ti ti-calendar me-1 text-primary"></i>
                        {{ $assignment->pivot->assigned_date ? \Carbon\Carbon::parse($assignment->pivot->assigned_date)->format('M d, Y') : 'Unknown' }}
                      </div>
                      @if($assignment->pivot->assigned_date)
                        <small class="text-muted">
                          {{ \Carbon\Carbon::parse($assignment->pivot->assigned_date)->diffForHumans() }}
                        </small>
                      @endif
                    </td>
                    <td>
                      @if($asset->condition === 'excellent')
                        <span class="badge bg-success">Excellent</span>
                      @elseif($asset->condition === 'good')
                        <span class="badge bg-info">Good</span>
                      @elseif($asset->condition === 'fair')
                        <span class="badge bg-warning">Fair</span>
                      @else
                        <span class="badge bg-danger">Poor</span>
                      @endif
                    </td>
                    <td>
                      <div class="d-flex gap-1">
                        <a href="{{ route('assetmanager.assets.show', $asset) }}"
                           class="btn btn-sm btn-outline-info"
                           title="View Asset Details">
                          <i class="ti ti-eye"></i>
                        </a>
                        <button type="button"
                                class="btn btn-sm btn-outline-danger"
                                onclick="unassignAsset({{ $asset->id }}, '{{ $asset->name }}')"
                                title="Un-assign Asset">
                          <i class="ti ti-user-minus"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <!-- Assignment History Summary -->
            @php
              $assignmentHistory = \Modules\AssetManager\Models\Asset::whereHas('employees', function($query) use ($employee) {
                  $query->where('employee_id', $employee->id);
              })->with(['employees' => function($query) use ($employee) {
                  $query->where('employee_id', $employee->id);
              }])->get();
              $totalAssignments = $assignmentHistory->sum(function($asset) {
                  return $asset->employees->count();
              });
            @endphp

            @if($totalAssignments > $assignedAssets->count())
            <div class="alert alert-light mt-3">
              <div class="d-flex align-items-center">
                <i class="ti ti-info-circle me-2 text-info"></i>
                <div>
                  <strong>Assignment History:</strong> This employee has been assigned {{ $totalAssignments }} asset(s) in total,
                  with {{ $assignedAssets->count() }} currently active assignment(s).
                  <button type="button" class="btn btn-sm btn-link p-0 ms-2" data-bs-toggle="collapse" data-bs-target="#assignmentHistory">
                    View Full History
                  </button>
                </div>
              </div>
            </div>

            <div class="collapse" id="assignmentHistory">
              <div class="card card-body mt-2">
                <h6 class="mb-3">Complete Assignment History</h6>
                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead>
                      <tr>
                        <th>Asset</th>
                        <th>Assigned Date</th>
                        <th>Returned Date</th>
                        <th>Duration</th>
                        <th>Notes</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($assignmentHistory as $asset)
                        @foreach($asset->employees as $assignment)
                        <tr>
                          <td>
                            <strong>{{ $asset->name }}</strong>
                            @if($asset->serial_number)
                              <br><small class="text-muted font-monospace">{{ $asset->serial_number }}</small>
                            @endif
                          </td>
                          <td>
                            {{ $assignment->pivot->assigned_date ? \Carbon\Carbon::parse($assignment->pivot->assigned_date)->format('M d, Y') : 'Unknown' }}
                          </td>
                          <td>
                            @if($assignment->pivot->returned_date)
                              {{ \Carbon\Carbon::parse($assignment->pivot->returned_date)->format('M d, Y') }}
                            @else
                              <span class="badge bg-success">Currently Assigned</span>
                            @endif
                          </td>
                          <td>
                            @if($assignment->pivot->assigned_date && $assignment->pivot->returned_date)
                              {{ \Carbon\Carbon::parse($assignment->pivot->assigned_date)->diffInDays(\Carbon\Carbon::parse($assignment->pivot->returned_date)) }} days
                            @elseif($assignment->pivot->assigned_date)
                              {{ \Carbon\Carbon::parse($assignment->pivot->assigned_date)->diffForHumans(null, true) }}
                            @else
                              Unknown
                            @endif
                          </td>
                          <td>
                            {{ $assignment->pivot->notes ?: 'No notes' }}
                          </td>
                        </tr>
                        @endforeach
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            @endif

            @else
            <div class="text-center py-4">
              <i class="ti ti-device-laptop-off text-muted" style="font-size: 3rem;"></i>
              <h6 class="mt-2">No assets assigned</h6>
              <p class="text-muted">This employee doesn't have any assets assigned currently.</p>
              <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#assignAssetModal">
                <i class="ti ti-plus me-1"></i>Assign First Asset
              </button>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- Documents Section -->
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
              <i class="ti ti-file-text me-2"></i>Documents
            </h6>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
              <i class="ti ti-upload me-1"></i>Upload New Document
            </button>
          </div>
          <div class="card-body">
            @if($employee->documents->count() > 0)
            <div class="table-responsive">
              <table class="table table-hover">
                <thead class="table-light">
                  <tr>
                    <th>Document Type</th>
                    <th>Issue Date</th>
                    <th>Expiry Date</th>
                    <th>File Size</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($employee->documents->sortBy('document_type') as $document)
                  <tr>
                    <td>
                      <i class="ti ti-file me-2 text-primary"></i>
                      {{ $document->document_type }}
                    </td>
                    <td>
                      {{ $document->issue_date ? $document->issue_date->format('M d, Y') : 'Not Set' }}
                    </td>
                    <td>
                      @if($document->expiry_date)
                        @if($document->is_expired)
                          <span class="badge bg-danger">Expired: {{ $document->expiry_date->format('M d, Y') }}</span>
                        @elseif($document->is_expiring_soon)
                          <span class="badge bg-warning">Expires: {{ $document->expiry_date->format('M d, Y') }}</span>
                        @else
                          <span class="text-success">{{ $document->expiry_date->format('M d, Y') }}</span>
                        @endif
                      @else
                        <span class="text-muted">No Expiry</span>
                      @endif
                    </td>
                    <td>
                      <small class="text-muted">{{ $document->formatted_file_size }}</small>
                    </td>
                    <td>
                      <div class="d-flex gap-1">
                        <a href="{{ route('hr.employees.documents.download', [$employee, $document]) }}"
                           class="btn btn-sm btn-outline-primary" title="Download">
                          <i class="ti ti-download"></i>
                        </a>
                        <form action="{{ route('hr.employees.documents.destroy', [$employee, $document]) }}"
                              method="POST" class="d-inline">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-outline-danger"
                                  onclick="return confirm('Are you sure you want to delete this document?')"
                                  title="Delete">
                            <i class="ti ti-trash"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            @else
            <div class="text-center py-4">
              <i class="ti ti-file-plus text-muted" style="font-size: 3rem;"></i>
              <h6 class="mt-2">No documents uploaded</h6>
              <p class="text-muted">Upload documents like passport, contract, etc.</p>
              <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                <i class="ti ti-upload me-1"></i>Upload First Document
              </button>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocumentModal" tabindex="-1" aria-labelledby="uploadDocumentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('hr.employees.documents.store', $employee) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="uploadDocumentModalLabel">
            <i class="ti ti-upload me-2"></i>Upload New Document
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Document Type -->
          <div class="mb-3">
            <label class="form-label" for="document_type">Document Type <span class="text-danger">*</span></label>
            <input
              type="text"
              class="form-control @error('document_type') is-invalid @enderror"
              id="document_type"
              name="document_type"
              value="{{ old('document_type') }}"
              placeholder="e.g., Passport, Contract, ID Card"
              required>
            @error('document_type')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- File Upload -->
          <div class="mb-3">
            <label class="form-label" for="document_file">Document File <span class="text-danger">*</span></label>
            <input
              type="file"
              class="form-control @error('document_file') is-invalid @enderror"
              id="document_file"
              name="document_file"
              accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.bmp,.svg,.txt,.rtf,.xls,.xlsx,.ppt,.pptx"
              required>
            <div class="form-text">
              Accepted formats: PDF, DOC, DOCX, JPG, JPEG, PNG, GIF, BMP, SVG, TXT, RTF, XLS, XLSX, PPT, PPTX (Max: 5MB)
            </div>
            @error('document_file')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Issue Date -->
          <div class="mb-3">
            <label class="form-label" for="issue_date">Issue Date</label>
            <input
              type="date"
              class="form-control @error('issue_date') is-invalid @enderror"
              id="issue_date"
              name="issue_date"
              value="{{ old('issue_date') }}">
            @error('issue_date')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Expiry Date -->
          <div class="mb-3">
            <label class="form-label" for="expiry_date">Expiry Date</label>
            <input
              type="date"
              class="form-control @error('expiry_date') is-invalid @enderror"
              id="expiry_date"
              name="expiry_date"
              value="{{ old('expiry_date') }}">
            @error('expiry_date')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>Cancel
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-upload me-1"></i>Upload Document
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@can('manage-permission-overrides')
<!-- Add Extra Permission Modal (Super Admin Only) -->
<div class="modal fade" id="addExtraPermissionModal" tabindex="-1" aria-labelledby="addExtraPermissionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('attendance.permission-overrides.store') }}" method="POST">
        @csrf
        <input type="hidden" name="employee_id" value="{{ $employee->id }}">
        <div class="modal-header">
          <h5 class="modal-title" id="addExtraPermissionModalLabel">
            <i class="ti ti-clock-plus me-2"></i>Grant Extra Permissions
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-warning">
            <i class="ti ti-alert-triangle me-2"></i>
            <strong>Granting extra permissions for:</strong> {{ $employee->name }}<br>
            <small>Payroll Period: {{ now()->format('F Y') }}</small>
          </div>

          <!-- Extra Permissions Granted -->
          <div class="mb-3">
            <label class="form-label" for="extra_permissions_granted">Number of Extra Permissions <span class="text-danger">*</span></label>
            <input
              type="number"
              class="form-control @error('extra_permissions_granted') is-invalid @enderror"
              id="extra_permissions_granted"
              name="extra_permissions_granted"
              value="{{ old('extra_permissions_granted', 1) }}"
              min="1"
              max="10"
              required>
            @error('extra_permissions_granted')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="form-text text-muted">Maximum 10 extra permissions can be granted at once</small>
          </div>

          <!-- Reason -->
          <div class="mb-3">
            <label class="form-label" for="reason">Reason for Exception</label>
            <textarea
              class="form-control @error('reason') is-invalid @enderror"
              id="reason"
              name="reason"
              rows="3"
              placeholder="Optional: Provide a reason for granting these extra permissions...">{{ old('reason') }}</textarea>
            @error('reason')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="alert alert-info">
            <i class="ti ti-info-circle me-2"></i>
            <strong>Note:</strong> These extra permissions will be added to the employee's standard monthly allowance for {{ now()->format('F Y') }} only.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>Cancel
          </button>
          <button type="submit" class="btn btn-warning">
            <i class="ti ti-clock-plus me-1"></i>Grant Extra Permissions
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan

<!-- Assign Asset Modal -->
<div class="modal fade" id="assignAssetModal" tabindex="-1" aria-labelledby="assignAssetModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="assignAssetForm">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="assignAssetModalLabel">
            <i class="ti ti-device-laptop me-2"></i>Assign Asset to {{ $employee->name }}
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Asset Selection -->
          <div class="mb-3">
            <label class="form-label" for="asset_id">Available Asset <span class="text-danger">*</span></label>
            <select class="form-select" id="asset_id" name="asset_id" required>
              <option value="">Select Asset</option>
              @php
                $availableAssets = \Modules\AssetManager\Models\Asset::where('status', 'available')->get();
              @endphp
              @foreach($availableAssets as $asset)
                <option value="{{ $asset->id }}" data-asset-name="{{ $asset->name }}" data-serial="{{ $asset->serial_number }}">
                  {{ $asset->name }}
                  @if($asset->brand) - {{ $asset->brand }} @endif
                  @if($asset->serial_number) ({{ $asset->serial_number }}) @endif
                </option>
              @endforeach
            </select>
            @if($availableAssets->count() === 0)
              <small class="text-muted">No assets are currently available for assignment.</small>
            @endif
          </div>

          <!-- Assignment Date -->
          <div class="mb-3">
            <label class="form-label" for="assigned_date">Assignment Date <span class="text-danger">*</span></label>
            <input
              type="date"
              class="form-control"
              id="assigned_date"
              name="assigned_date"
              value="{{ now()->format('Y-m-d') }}"
              max="{{ now()->format('Y-m-d') }}"
              required>
          </div>

          <!-- Notes -->
          <div class="mb-3">
            <label class="form-label" for="assignment_notes">Assignment Notes</label>
            <textarea
              class="form-control"
              id="assignment_notes"
              name="notes"
              rows="3"
              placeholder="Optional notes about this assignment..."></textarea>
          </div>

          <div class="alert alert-info">
            <i class="ti ti-info-circle me-2"></i>
            <strong>Note:</strong> The asset status will automatically change to "assigned" once assigned to this employee.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>Cancel
          </button>
          <button type="submit" class="btn btn-info">
            <i class="ti ti-device-laptop me-1"></i>Assign Asset
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@can('manage-leave-records')
<!-- Log Leave Modal -->
<div class="modal fade" id="logLeaveModal" tabindex="-1" aria-labelledby="logLeaveModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="logLeaveForm">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="logLeaveModalLabel">
            <i class="ti ti-calendar-plus me-2"></i>Log Leave for {{ $employee->name }}
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Leave Policy -->
          <div class="mb-3">
            <label class="form-label" for="leave_policy_id">Leave Type <span class="text-danger">*</span></label>
            <select class="form-select" id="leave_policy_id" name="leave_policy_id" required>
              <option value="">Select Leave Type</option>
              @foreach(\Modules\Leave\Models\LeavePolicy::active()->get() as $policy)
                <option value="{{ $policy->id }}">{{ $policy->name }}</option>
              @endforeach
            </select>
          </div>

          <!-- Start Date -->
          <div class="mb-3">
            <label class="form-label" for="start_date">Start Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="start_date" name="start_date" required>
          </div>

          <!-- End Date -->
          <div class="mb-3">
            <label class="form-label" for="end_date">End Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="end_date" name="end_date" required>
          </div>

          <!-- Notes -->
          <div class="mb-3">
            <label class="form-label" for="leave_notes">Notes</label>
            <textarea class="form-control" id="leave_notes" name="notes" rows="3" placeholder="Optional notes..."></textarea>
          </div>

          <div class="alert alert-info">
            <i class="ti ti-info-circle me-2"></i>
            <strong>Note:</strong> Leave will be automatically approved and counted towards the employee's leave balance.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>Cancel
          </button>
          <button type="submit" class="btn btn-info">
            <i class="ti ti-calendar-plus me-1"></i>Log Leave
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Log WFH Modal -->
<div class="modal fade" id="logWfhModal" tabindex="-1" aria-labelledby="logWfhModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="logWfhForm">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="logWfhModalLabel">
            <i class="ti ti-home me-2"></i>Log WFH Days for {{ $employee->name }}
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- WFH Dates -->
          <div class="mb-3">
            <label class="form-label" for="wfh_dates">WFH Dates <span class="text-danger">*</span></label>
            <div id="wfh-dates-container">
              <div class="input-group mb-2">
                <input type="date" class="form-control wfh-date-input" name="dates[]" required>
                <button type="button" class="btn btn-outline-secondary" onclick="addWfhDateInput()">
                  <i class="ti ti-plus"></i>
                </button>
              </div>
            </div>
            <small class="text-muted">You can add multiple dates for bulk WFH logging.</small>
          </div>

          <!-- Notes -->
          <div class="mb-3">
            <label class="form-label" for="wfh_notes">Notes</label>
            <textarea class="form-control" id="wfh_notes" name="notes" rows="3" placeholder="Optional notes..."></textarea>
          </div>

          <div class="alert alert-warning">
            <i class="ti ti-alert-triangle me-2"></i>
            <strong>WFH Policy:</strong> The system will validate that the monthly WFH allowance is not exceeded.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>Cancel
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-home me-1"></i>Log WFH Days
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Add WFH date input
function addWfhDateInput() {
    const container = document.getElementById('wfh-dates-container');
    const newInput = document.createElement('div');
    newInput.className = 'input-group mb-2';
    newInput.innerHTML = `
        <input type="date" class="form-control wfh-date-input" name="dates[]" required>
        <button type="button" class="btn btn-outline-danger" onclick="removeWfhDateInput(this)">
            <i class="ti ti-minus"></i>
        </button>
    `;
    container.appendChild(newInput);
}

// Remove WFH date input
function removeWfhDateInput(button) {
    button.parentElement.remove();
}

// Handle Leave Form Submission
document.getElementById('logLeaveForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');

    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="ti ti-loader me-1"></i>Logging Leave...';

    fetch(`/api/v1/employees/{{ $employee->id }}/leave-records`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal and refresh page
            bootstrap.Modal.getInstance(document.getElementById('logLeaveModal')).hide();
            location.reload();
        } else {
            alert(data.message || 'An error occurred');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while logging leave');
    })
    .finally(() => {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="ti ti-calendar-plus me-1"></i>Log Leave';
    });
});

// Handle WFH Form Submission
document.getElementById('logWfhForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');

    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="ti ti-loader me-1"></i>Logging WFH Days...';

    fetch(`/api/v1/employees/{{ $employee->id }}/wfh-records`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal and refresh page
            bootstrap.Modal.getInstance(document.getElementById('logWfhModal')).hide();
            location.reload();
        } else {
            alert(data.message || 'An error occurred');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while logging WFH days');
    })
    .finally(() => {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="ti ti-home me-1"></i>Log WFH Days';
    });
});

// Delete Leave Record
function deleteLeaveRecord(recordId) {
    if (confirm('Are you sure you want to delete this leave record?')) {
        fetch(`/api/v1/employees/{{ $employee->id }}/leave-records/${recordId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'An error occurred');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting leave record');
        });
    }
}

// Delete WFH Record
function deleteWfhRecord(recordId) {
    if (confirm('Are you sure you want to delete this WFH record?')) {
        fetch(`/api/v1/employees/{{ $employee->id }}/wfh-records/${recordId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'An error occurred');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting WFH record');
        });
    }
}

// Handle Asset Assignment Form Submission
document.getElementById('assignAssetForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const assetSelect = document.getElementById('asset_id');
    const selectedOption = assetSelect.options[assetSelect.selectedIndex];

    if (!formData.get('asset_id')) {
        alert('Please select an asset to assign');
        return;
    }

    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="ti ti-loader me-1"></i>Assigning Asset...';

    fetch(`{{ route('assetmanager.employee-assets.assign', $employee) }}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal and refresh page
            bootstrap.Modal.getInstance(document.getElementById('assignAssetModal')).hide();
            location.reload();
        } else {
            alert(data.message || 'An error occurred');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while assigning asset');
    })
    .finally(() => {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="ti ti-device-laptop me-1"></i>Assign Asset';
    });
});

// Un-assign Asset Function
function unassignAsset(assetId, assetName) {
    if (confirm(`Are you sure you want to un-assign "${assetName}" from {{ $employee->name }}?`)) {
        const submitData = new FormData();
        submitData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        submitData.append('asset_id', assetId);
        submitData.append('returned_date', new Date().toISOString().split('T')[0]);
        submitData.append('notes', 'Asset returned - un-assigned from employee profile');

        fetch(`{{ route('assetmanager.employee-assets.unassign', $employee) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: submitData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'An error occurred');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while un-assigning asset');
        });
    }
}
</script>
@endcan

<!-- Assign Asset Modal -->
<div class="modal fade" id="assignAssetModal" tabindex="-1" aria-labelledby="assignAssetModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="assignAssetForm">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="assignAssetModalLabel">
            <i class="ti ti-device-laptop me-2"></i>Assign Asset to {{ $employee->name }}
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Asset Selection -->
          <div class="mb-3">
            <label class="form-label" for="asset_id">Available Assets <span class="text-danger">*</span></label>
            <select class="form-select" id="asset_id" name="asset_id" required>
              <option value="">Select an asset</option>
              @php
                $availableAssets = \Modules\AssetManager\Models\Asset::where('status', 'available')->get();
              @endphp
              @foreach($availableAssets as $availableAsset)
                <option value="{{ $availableAsset->id }}">
                  {{ $availableAsset->name }}
                  @if($availableAsset->serial_number)
                    ({{ $availableAsset->serial_number }})
                  @endif
                </option>
              @endforeach
            </select>
          </div>

          <!-- Assignment Date -->
          <div class="mb-3">
            <label class="form-label" for="assigned_date">Assignment Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="assigned_date" name="assigned_date" value="{{ date('Y-m-d') }}" required>
          </div>

          <!-- Notes -->
          <div class="mb-3">
            <label class="form-label" for="assignment_notes">Notes</label>
            <textarea class="form-control" id="assignment_notes" name="notes" rows="3" placeholder="Optional notes about the assignment..."></textarea>
          </div>

          <div class="alert alert-info">
            <i class="ti ti-info-circle me-2"></i>
            <strong>Note:</strong> The asset will be marked as assigned and will no longer be available for other employees until returned.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>Cancel
          </button>
          <button type="submit" class="btn btn-info">
            <i class="ti ti-device-laptop me-1"></i>Assign Asset
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection
