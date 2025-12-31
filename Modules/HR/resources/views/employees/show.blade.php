@extends('layouts/layoutMaster')

@section('title', 'Employee Profile - ' . $employee->name)

@section('page-style')
<style>
  .profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 0.5rem 0.5rem 0 0;
    padding: 2rem;
    color: white;
    position: relative;
  }
  .profile-header.terminated {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  }
  .profile-header.resigned {
    background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
    color: #333;
  }
  .profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 4px solid white;
    background-color: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: 600;
    color: #667eea;
  }
  .profile-header.terminated .profile-avatar { color: #f5576c; }
  .profile-header.resigned .profile-avatar { color: #fcb69f; }
  .stat-card {
    text-align: center;
    padding: 1.5rem;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
  }
  .stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
  }
  .stat-value {
    font-size: 1.75rem;
    font-weight: 700;
  }
  .info-card {
    transition: all 0.3s ease;
  }
  .info-card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
  }
  .info-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #eee;
  }
  .info-item:last-child {
    border-bottom: none;
  }
  .info-label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .quick-action-btn {
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
    background: white;
    color: #333;
  }
  .quick-action-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.1);
    background: #f8f9fa;
    color: #333;
  }
  .quick-action-btn i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
  }
  .nav-pills-custom .nav-link {
    border-radius: 0.5rem;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
  }
  .nav-pills-custom .nav-link.active {
    background-color: #667eea;
  }
</style>
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible mb-4" role="alert">
      <i class="ti ti-check me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Profile Header Card -->
    <div class="card mb-4">
      <div class="profile-header {{ $employee->status }}">
        <div class="d-flex flex-column flex-md-row align-items-center gap-4">
          <div class="profile-avatar">
            {{ strtoupper(substr($employee->name, 0, 2)) }}
          </div>
          <div class="text-center text-md-start flex-grow-1">
            <h2 class="mb-1 {{ $employee->status !== 'active' ? 'text-dark' : 'text-white' }}">{{ $employee->name }}</h2>
            @if($employee->name_ar)
              <p class="mb-2 opacity-75" dir="rtl">{{ $employee->name_ar }}</p>
            @endif
            <div class="d-flex gap-2 flex-wrap justify-content-center justify-content-md-start">
              @if($employee->positionRelation)
              <span class="badge bg-white text-primary">
                <i class="ti ti-briefcase me-1"></i>{{ $employee->positionRelation->full_title }}
              </span>
              @elseif($employee->position)
              <span class="badge bg-white text-secondary">
                <i class="ti ti-briefcase me-1"></i>{{ $employee->position }}
              </span>
              @endif
              @if($employee->positionRelation && $employee->positionRelation->department)
              <span class="badge bg-white text-info">
                <i class="ti ti-building me-1"></i>{{ $employee->positionRelation->department }}
              </span>
              @endif
              @if($employee->status === 'active')
                <span class="badge bg-success"><i class="ti ti-check me-1"></i>Active</span>
              @elseif($employee->status === 'terminated')
                <span class="badge bg-danger"><i class="ti ti-x me-1"></i>Terminated</span>
              @else
                <span class="badge bg-warning"><i class="ti ti-door-exit me-1"></i>Resigned</span>
              @endif
            </div>
          </div>
          <!-- Action Buttons -->
          <div class="d-flex gap-2 flex-wrap justify-content-center">
            <a href="{{ route('documents.select-template', $employee) }}" class="btn btn-light btn-sm" title="Generate Document">
              <i class="ti ti-file-text me-1"></i><span class="d-none d-lg-inline">Generate Doc</span>
            </a>
            <a href="{{ route('hr.employees.edit', $employee) }}" class="btn btn-light btn-sm" title="Edit">
              <i class="ti ti-edit me-1"></i><span class="d-none d-lg-inline">Edit</span>
            </a>
            @if($employee->status === 'active')
            <a href="{{ route('hr.employees.offboarding.show', $employee) }}" class="btn btn-warning btn-sm" title="Off-boarding">
              <i class="ti ti-user-off me-1"></i><span class="d-none d-lg-inline">Off-board</span>
            </a>
            @endif
            <a href="{{ route('hr.employees.index') }}" class="btn btn-outline-light btn-sm" title="Back">
              <i class="ti ti-arrow-left"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3 col-6">
            <div class="stat-card bg-label-primary">
              <div class="stat-value text-primary">
                {{ $employee->start_date ? $employee->start_date->diffInYears(now()) : 0 }}
              </div>
              <small class="text-muted">Years of Service</small>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="stat-card bg-label-success">
              <div class="stat-value text-success">
                @php
                  $assignedAssets = \Modules\AssetManager\Models\Asset::whereHas('employees', function($query) use ($employee) {
                      $query->where('employee_id', $employee->id)->whereNull('returned_date');
                  })->count();
                @endphp
                {{ $assignedAssets }}
              </div>
              <small class="text-muted">Assigned Assets</small>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="stat-card bg-label-info">
              <div class="stat-value text-info">
                {{ $employee->documents->count() }}
              </div>
              <small class="text-muted">Documents</small>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="stat-card bg-label-warning">
              <div class="stat-value text-warning">
                @if($canViewSalary && $employee->base_salary)
                  {{ number_format($employee->base_salary / 1000, 1) }}K
                @else
                  -
                @endif
              </div>
              <small class="text-muted">Base Salary (EGP)</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3">
          @can('manage-leave-records')
          <div class="col-auto">
            <button type="button" class="quick-action-btn" data-bs-toggle="modal" data-bs-target="#logLeaveModal">
              <i class="ti ti-calendar-plus text-info"></i>
              <small>Log Leave</small>
            </button>
          </div>
          <div class="col-auto">
            <button type="button" class="quick-action-btn" data-bs-toggle="modal" data-bs-target="#logWfhModal">
              <i class="ti ti-home text-primary"></i>
              <small>Log WFH</small>
            </button>
          </div>
          @endcan
          <div class="col-auto">
            <button type="button" class="quick-action-btn" data-bs-toggle="modal" data-bs-target="#assignAssetModal">
              <i class="ti ti-device-laptop text-success"></i>
              <small>Assign Asset</small>
            </button>
          </div>
          <div class="col-auto">
            <button type="button" class="quick-action-btn" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
              <i class="ti ti-upload text-warning"></i>
              <small>Upload Doc</small>
            </button>
          </div>
          @can('manage-permission-overrides')
          <div class="col-auto">
            <button type="button" class="quick-action-btn" data-bs-toggle="modal" data-bs-target="#addExtraPermissionModal">
              <i class="ti ti-clock-plus text-danger"></i>
              <small>Extra Permission</small>
            </button>
          </div>
          @endcan
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Personal Information -->
      <div class="col-lg-6 mb-4">
        <div class="card info-card h-100">
          <div class="card-header">
            <h6 class="mb-0"><i class="ti ti-id me-2 text-primary"></i>Personal Information</h6>
          </div>
          <div class="card-body">
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">Full Name</span>
              <span class="fw-semibold">{{ $employee->name }}</span>
            </div>
            @if($employee->name_ar)
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">Arabic Name</span>
              <span class="fw-semibold" dir="rtl">{{ $employee->name_ar }}</span>
            </div>
            @endif
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">Work Email</span>
              <a href="mailto:{{ $employee->email }}">{{ $employee->email }}</a>
            </div>
            @if($employee->personal_email)
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">Personal Email</span>
              <a href="mailto:{{ $employee->personal_email }}">{{ $employee->personal_email }}</a>
            </div>
            @endif
            @if($employee->attendance_id)
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">Attendance ID</span>
              <span class="font-monospace">{{ $employee->attendance_id }}</span>
            </div>
            @endif
            @if($employee->national_id)
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">National ID</span>
              <span class="font-monospace">{{ $employee->national_id }}</span>
            </div>
            @endif
            @if($employee->national_insurance_number)
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">Insurance Number</span>
              <span class="font-monospace">{{ $employee->national_insurance_number }}</span>
            </div>
            @endif
          </div>
        </div>
      </div>

      <!-- Employment Details -->
      <div class="col-lg-6 mb-4">
        <div class="card info-card h-100">
          <div class="card-header">
            <h6 class="mb-0"><i class="ti ti-briefcase me-2 text-success"></i>Employment Details</h6>
          </div>
          <div class="card-body">
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">Position</span>
              @if($employee->positionRelation)
                <span class="badge bg-label-primary">{{ $employee->positionRelation->full_title }}</span>
              @elseif($employee->position)
                <span class="badge bg-label-secondary">{{ $employee->position }}</span>
              @else
                <span class="text-muted">Not Assigned</span>
              @endif
            </div>
            @if($employee->positionRelation && $employee->positionRelation->department)
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">Department</span>
              <span class="badge bg-label-info">{{ $employee->positionRelation->department }}</span>
            </div>
            @endif
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">Start Date</span>
              <span>{{ $employee->start_date ? $employee->start_date->format('M d, Y') : 'Not Set' }}</span>
            </div>
            @if($employee->termination_date)
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">End Date</span>
              <span class="text-danger">{{ $employee->termination_date->format('M d, Y') }}</span>
            </div>
            @endif
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">Status</span>
              @if($employee->status === 'active')
                <span class="badge bg-success">Active</span>
              @elseif($employee->status === 'terminated')
                <span class="badge bg-danger">Terminated</span>
              @else
                <span class="badge bg-warning">Resigned</span>
              @endif
            </div>
            @if($canViewSalary)
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">Base Salary</span>
              @if($employee->base_salary)
                <span class="text-success fw-bold">EGP {{ number_format($employee->base_salary, 2) }}</span>
              @else
                <span class="text-muted">Not Set</span>
              @endif
            </div>
            @if($employee->hourly_rate)
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">Hourly Rate</span>
              <span class="text-info fw-bold">EGP {{ number_format($employee->hourly_rate, 2) }}</span>
            </div>
            @endif
            @endif
          </div>
        </div>
      </div>

      <!-- Contact Information -->
      @if($employee->contact_info && count(array_filter($employee->contact_info)))
      <div class="col-lg-6 mb-4">
        <div class="card info-card h-100">
          <div class="card-header">
            <h6 class="mb-0"><i class="ti ti-phone me-2 text-info"></i>Contact Information</h6>
          </div>
          <div class="card-body">
            @if(isset($employee->contact_info['mobile_number']) && $employee->contact_info['mobile_number'])
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">Mobile</span>
              <a href="tel:{{ $employee->contact_info['mobile_number'] }}">{{ $employee->contact_info['mobile_number'] }}</a>
            </div>
            @endif
            @if(isset($employee->contact_info['secondary_number']) && $employee->contact_info['secondary_number'])
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">Secondary</span>
              <a href="tel:{{ $employee->contact_info['secondary_number'] }}">{{ $employee->contact_info['secondary_number'] }}</a>
            </div>
            @endif
            @if(isset($employee->contact_info['current_address']) && $employee->contact_info['current_address'])
            <div class="info-item">
              <span class="info-label d-block mb-1">Current Address</span>
              <span>{{ $employee->contact_info['current_address'] }}</span>
            </div>
            @endif
            @if(isset($employee->contact_info['permanent_address']) && $employee->contact_info['permanent_address'])
            <div class="info-item">
              <span class="info-label d-block mb-1">Permanent Address</span>
              <span>{{ $employee->contact_info['permanent_address'] }}</span>
            </div>
            @endif
          </div>
        </div>
      </div>
      @endif

      <!-- Bank Information -->
      @if($employee->bank_info && count(array_filter($employee->bank_info)))
      <div class="col-lg-6 mb-4">
        <div class="card info-card h-100">
          <div class="card-header">
            <h6 class="mb-0"><i class="ti ti-building-bank me-2 text-warning"></i>Bank Information</h6>
          </div>
          <div class="card-body">
            @if(isset($employee->bank_info['bank_name']) && $employee->bank_info['bank_name'])
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">Bank</span>
              <span>{{ $employee->bank_info['bank_name'] }}</span>
            </div>
            @endif
            @if(isset($employee->bank_info['account_number']) && $employee->bank_info['account_number'])
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">Account No.</span>
              <span class="font-monospace">{{ $employee->bank_info['account_number'] }}</span>
            </div>
            @endif
            @if(isset($employee->bank_info['iban']) && $employee->bank_info['iban'])
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">IBAN</span>
              <span class="font-monospace small">{{ $employee->bank_info['iban'] }}</span>
            </div>
            @endif
            @if(isset($employee->bank_info['account_id']) && $employee->bank_info['account_id'])
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">Bank Employee ID</span>
              <span class="font-monospace">{{ $employee->bank_info['account_id'] }}</span>
            </div>
            @endif
            @if(isset($employee->bank_info['currency']) && $employee->bank_info['currency'])
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">Salary Currency</span>
              <span>{{ $employee->bank_info['currency'] }}</span>
            </div>
            @endif
          </div>
        </div>
      </div>
      @endif

      <!-- Emergency Contact -->
      @if($employee->emergency_contact && (isset($employee->emergency_contact['name']) || isset($employee->emergency_contact['phone'])))
      <div class="col-lg-6 mb-4">
        <div class="card info-card h-100">
          <div class="card-header">
            <h6 class="mb-0"><i class="ti ti-urgent me-2 text-danger"></i>Emergency Contact</h6>
          </div>
          <div class="card-body">
            @if(isset($employee->emergency_contact['name']) && $employee->emergency_contact['name'])
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">Name</span>
              <span>{{ $employee->emergency_contact['name'] }}</span>
            </div>
            @endif
            @if(isset($employee->emergency_contact['phone']) && $employee->emergency_contact['phone'])
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">Phone</span>
              <a href="tel:{{ $employee->emergency_contact['phone'] }}">{{ $employee->emergency_contact['phone'] }}</a>
            </div>
            @endif
            @if(isset($employee->emergency_contact['relationship']) && $employee->emergency_contact['relationship'])
            <div class="info-item d-flex justify-content-between">
              <span class="info-label">Relationship</span>
              <span>{{ $employee->emergency_contact['relationship'] }}</span>
            </div>
            @endif
          </div>
        </div>
      </div>
      @endif
    </div>

    @can('manage-leave-records')
    <!-- Leave & WFH Section -->
    <div class="card mb-4">
      <div class="card-header">
        <h6 class="mb-0"><i class="ti ti-calendar-event me-2 text-info"></i>Leave & Work From Home</h6>
      </div>
      <div class="card-body">
        <!-- Leave Balance Summary -->
        @php
          $leaveBalanceService = app(\Modules\Leave\Services\LeaveBalanceService::class);
          $balanceSummary = $leaveBalanceService->getLeaveBalanceSummary($employee);
        @endphp

        @if(count($balanceSummary['balances']) > 0)
        <div class="row g-3 mb-4">
          @foreach($balanceSummary['balances'] as $balance)
          <div class="col-md-4">
            <div class="card border-start border-{{ $balance['remaining_days'] > 0 ? 'success' : 'warning' }} border-3">
              <div class="card-body py-3">
                <h6 class="card-title mb-2">{{ $balance['policy_name'] }}</h6>
                <div class="d-flex justify-content-between mb-1">
                  <small class="text-muted">Entitled</small>
                  <span class="badge bg-primary">{{ number_format($balance['entitled_days'], 0) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <small class="text-muted">Used</small>
                  <span class="badge bg-secondary">{{ number_format($balance['used_days'], 0) }}</span>
                </div>
                <div class="d-flex justify-content-between">
                  <small class="text-muted">Remaining</small>
                  <span class="badge bg-{{ $balance['remaining_days'] > 0 ? 'success' : 'warning' }}">{{ number_format($balance['remaining_days'], 0) }}</span>
                </div>
              </div>
            </div>
          </div>
          @endforeach
        </div>
        @else
        <div class="alert alert-info mb-4">
          <i class="ti ti-info-circle me-2"></i>No leave policies configured for this employee.
        </div>
        @endif

        <!-- Recent Leave Records -->
        @php
          $recentLeaveRecords = \Modules\Leave\Models\LeaveRecord::where('employee_id', $employee->id)
              ->with(['leavePolicy', 'createdBy'])
              ->orderBy('created_at', 'desc')
              ->limit(5)
              ->get();
        @endphp

        @if($recentLeaveRecords->count() > 0)
        <h6 class="text-muted mb-3">Recent Leave Records</h6>
        <div class="table-responsive mb-4">
          <table class="table table-sm table-hover">
            <thead class="table-light">
              <tr>
                <th>Type</th>
                <th>Period</th>
                <th>Days</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @foreach($recentLeaveRecords as $record)
              <tr>
                <td>{{ $record->leavePolicy->name }}</td>
                <td>{{ $record->start_date->format('M d') }} - {{ $record->end_date->format('M d, Y') }}</td>
                <td>{{ $record->getDaysCount() }}</td>
                <td>
                  <span class="badge bg-{{ $record->status === 'approved' ? 'success' : ($record->status === 'rejected' ? 'danger' : 'warning') }}">
                    {{ ucfirst($record->status) }}
                  </span>
                </td>
                <td>
                  <button class="btn btn-sm btn-icon btn-text-danger" onclick="deleteLeaveRecord({{ $record->id }})">
                    <i class="ti ti-trash"></i>
                  </button>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @endif

        <!-- Recent WFH Records -->
        @php
          $recentWfhRecords = \Modules\Attendance\Models\WfhRecord::where('employee_id', $employee->id)
              ->orderBy('date', 'desc')
              ->limit(10)
              ->get();
        @endphp

        @if($recentWfhRecords->count() > 0)
        <h6 class="text-muted mb-3">Recent WFH Days</h6>
        <div class="d-flex flex-wrap gap-2">
          @foreach($recentWfhRecords as $record)
          <span class="badge bg-light text-dark border">
            {{ $record->date->format('M d, Y') }}
            <button class="btn btn-sm btn-link p-0 ms-1 text-danger" onclick="deleteWfhRecord({{ $record->id }})">
              <i class="ti ti-x" style="font-size: 0.75rem;"></i>
            </button>
          </span>
          @endforeach
        </div>
        @endif
      </div>
    </div>
    @endcan

    <!-- Assigned Assets Section -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="ti ti-device-laptop me-2 text-success"></i>Assigned Assets</h6>
        <a href="{{ route('assetmanager.assets.index') }}" class="btn btn-sm btn-outline-secondary">
          <i class="ti ti-external-link me-1"></i>Manage Assets
        </a>
      </div>
      <div class="card-body">
        @php
          $assignedAssets = \Modules\AssetManager\Models\Asset::whereHas('employees', function($query) use ($employee) {
              $query->where('employee_id', $employee->id)->whereNull('returned_date');
          })->with(['employees' => function($query) use ($employee) {
              $query->where('employee_id', $employee->id)->whereNull('returned_date');
          }])->get();
        @endphp

        @if($assignedAssets->count() > 0)
        <div class="row g-3">
          @foreach($assignedAssets as $asset)
          @php $assignment = $asset->employees->first(); @endphp
          <div class="col-md-6 col-lg-4">
            <div class="card border h-100">
              <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                  <div>
                    <h6 class="mb-1">{{ $asset->name }}</h6>
                    <small class="text-muted">{{ $asset->brand ?? ucwords(str_replace('_', ' ', $asset->type)) }}</small>
                  </div>
                  <span class="badge bg-label-{{ $asset->condition === 'excellent' ? 'success' : ($asset->condition === 'good' ? 'info' : 'warning') }}">
                    {{ ucfirst($asset->condition) }}
                  </span>
                </div>
                @if($asset->serial_number)
                <div class="mt-2">
                  <small class="text-muted">Serial:</small>
                  <span class="font-monospace small">{{ $asset->serial_number }}</span>
                </div>
                @endif
                <div class="mt-2">
                  <small class="text-muted">Assigned:</small>
                  <span class="small">{{ $assignment->pivot->assigned_date ? \Carbon\Carbon::parse($assignment->pivot->assigned_date)->format('M d, Y') : 'Unknown' }}</span>
                </div>
                <div class="mt-3 d-flex gap-2">
                  <a href="{{ route('assetmanager.assets.show', $asset) }}" class="btn btn-sm btn-outline-info">
                    <i class="ti ti-eye"></i>
                  </a>
                  <button type="button" class="btn btn-sm btn-outline-danger" onclick="unassignAsset({{ $asset->id }}, '{{ $asset->name }}')">
                    <i class="ti ti-user-minus"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
          @endforeach
        </div>
        @else
        <div class="text-center py-4">
          <i class="ti ti-device-laptop-off text-muted" style="font-size: 3rem;"></i>
          <h6 class="mt-2">No assets assigned</h6>
          <p class="text-muted mb-3">This employee doesn't have any assets assigned currently.</p>
          <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#assignAssetModal">
            <i class="ti ti-plus me-1"></i>Assign Asset
          </button>
        </div>
        @endif
      </div>
    </div>

    <!-- Documents Section -->
    <div class="card mb-4">
      <div class="card-header">
        <h6 class="mb-0"><i class="ti ti-file-text me-2 text-warning"></i>Documents</h6>
      </div>
      <div class="card-body">
        @if($employee->documents->count() > 0)
        <div class="table-responsive">
          <table class="table table-hover">
            <thead class="table-light">
              <tr>
                <th>Document</th>
                <th>Issue Date</th>
                <th>Expiry</th>
                <th>Size</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @foreach($employee->documents->sortBy('document_type') as $document)
              <tr>
                <td>
                  <i class="ti ti-file me-2 text-primary"></i>{{ $document->document_type }}
                </td>
                <td>{{ $document->issue_date ? $document->issue_date->format('M d, Y') : '-' }}</td>
                <td>
                  @if($document->expiry_date)
                    @if($document->is_expired)
                      <span class="badge bg-danger">Expired</span>
                    @elseif($document->is_expiring_soon)
                      <span class="badge bg-warning">{{ $document->expiry_date->format('M d, Y') }}</span>
                    @else
                      <span class="text-success">{{ $document->expiry_date->format('M d, Y') }}</span>
                    @endif
                  @else
                    <span class="text-muted">No Expiry</span>
                  @endif
                </td>
                <td><small class="text-muted">{{ $document->formatted_file_size }}</small></td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="{{ route('hr.employees.documents.download', [$employee, $document]) }}" class="btn btn-sm btn-icon btn-text-primary">
                      <i class="ti ti-download"></i>
                    </a>
                    <form action="{{ route('hr.employees.documents.destroy', [$employee, $document]) }}" method="POST" class="d-inline">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-icon btn-text-danger" onclick="return confirm('Delete this document?')">
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
          <p class="text-muted mb-3">Upload documents like passport, contract, etc.</p>
          <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
            <i class="ti ti-upload me-1"></i>Upload Document
          </button>
        </div>
        @endif
      </div>
    </div>

    @if($canViewSalary && $employee->salaryHistory->count() > 0)
    <!-- Salary History Section -->
    <div class="card mb-4">
      <div class="card-header">
        <h6 class="mb-0"><i class="ti ti-chart-line me-2 text-success"></i>Salary History</h6>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm table-hover">
            <thead class="table-light">
              <tr>
                <th>Effective Date</th>
                <th>Salary</th>
                <th>Change</th>
                <th>Reason</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach($employee->salaryHistory->sortByDesc('effective_date')->take(5) as $history)
              <tr>
                <td>{{ $history->effective_date->format('M d, Y') }}</td>
                <td class="fw-bold">{{ number_format($history->base_salary, 2) }} {{ $history->currency }}</td>
                <td>
                  @if($history->change_percentage !== null)
                    @if($history->change_percentage >= 0)
                      <span class="badge bg-success"><i class="ti ti-arrow-up me-1"></i>+{{ number_format($history->change_percentage, 1) }}%</span>
                    @else
                      <span class="badge bg-danger"><i class="ti ti-arrow-down me-1"></i>{{ number_format($history->change_percentage, 1) }}%</span>
                    @endif
                  @else
                    <span class="badge bg-secondary">Initial</span>
                  @endif
                </td>
                <td><small>{{ $history->reason_label }}</small></td>
                <td>
                  @if($history->isCurrent())
                    <span class="badge bg-success">Current</span>
                  @else
                    <small class="text-muted">Until {{ $history->end_date?->format('M d, Y') }}</small>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
    @endif
  </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocumentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('hr.employees.documents.store', $employee) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title"><i class="ti ti-upload me-2"></i>Upload Document</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Document Type <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="document_type" placeholder="e.g., Passport, Contract" required>
          </div>
          <div class="mb-3">
            <label class="form-label">File <span class="text-danger">*</span></label>
            <input type="file" class="form-control" name="document_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
            <small class="text-muted">Max 5MB. PDF, DOC, JPG, PNG</small>
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Issue Date</label>
              <input type="date" class="form-control" name="issue_date">
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Expiry Date</label>
              <input type="date" class="form-control" name="expiry_date">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Upload</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Assign Asset Modal -->
<div class="modal fade" id="assignAssetModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="assignAssetForm">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title"><i class="ti ti-device-laptop me-2"></i>Assign Asset</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Asset <span class="text-danger">*</span></label>
            <select class="form-select" name="asset_id" required>
              <option value="">Select Asset</option>
              @php $availableAssets = \Modules\AssetManager\Models\Asset::where('status', 'available')->get(); @endphp
              @foreach($availableAssets as $asset)
              <option value="{{ $asset->id }}">{{ $asset->name }} {{ $asset->serial_number ? "({$asset->serial_number})" : '' }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Assignment Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" name="assigned_date" value="{{ date('Y-m-d') }}" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea class="form-control" name="notes" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-info">Assign</button>
        </div>
      </form>
    </div>
  </div>
</div>

@can('manage-leave-records')
<!-- Log Leave Modal -->
<div class="modal fade" id="logLeaveModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="logLeaveForm">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title"><i class="ti ti-calendar-plus me-2"></i>Log Leave</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Leave Type <span class="text-danger">*</span></label>
            <select class="form-select" name="leave_policy_id" required>
              <option value="">Select Type</option>
              @foreach(\Modules\Leave\Models\LeavePolicy::active()->get() as $policy)
              <option value="{{ $policy->id }}">{{ $policy->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Start Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="start_date" required>
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">End Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="end_date" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea class="form-control" name="notes" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-info">Log Leave</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Log WFH Modal -->
<div class="modal fade" id="logWfhModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="logWfhForm">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title"><i class="ti ti-home me-2"></i>Log WFH Days</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">WFH Dates <span class="text-danger">*</span></label>
            <div id="wfh-dates-container">
              <div class="input-group mb-2">
                <input type="date" class="form-control" name="dates[]" required>
                <button type="button" class="btn btn-outline-secondary" onclick="addWfhDateInput()">
                  <i class="ti ti-plus"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea class="form-control" name="notes" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Log WFH</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan

@can('manage-permission-overrides')
<!-- Extra Permission Modal -->
<div class="modal fade" id="addExtraPermissionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('attendance.permission-overrides.store') }}" method="POST">
        @csrf
        <input type="hidden" name="employee_id" value="{{ $employee->id }}">
        <div class="modal-header">
          <h5 class="modal-title"><i class="ti ti-clock-plus me-2"></i>Grant Extra Permissions</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-warning py-2">
            <small><strong>Period:</strong> {{ now()->format('F Y') }}</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Extra Permissions <span class="text-danger">*</span></label>
            <input type="number" class="form-control" name="extra_permissions_granted" value="1" min="1" max="10" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Reason</label>
            <textarea class="form-control" name="reason" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">Grant</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan
@endsection

@section('page-script')
<script>
function addWfhDateInput() {
  const container = document.getElementById('wfh-dates-container');
  const div = document.createElement('div');
  div.className = 'input-group mb-2';
  div.innerHTML = `
    <input type="date" class="form-control" name="dates[]" required>
    <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()"><i class="ti ti-minus"></i></button>
  `;
  container.appendChild(div);
}

@can('manage-leave-records')
document.getElementById('logLeaveForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const btn = this.querySelector('button[type="submit"]');
  btn.disabled = true;
  btn.innerHTML = '<i class="ti ti-loader me-1"></i>Saving...';

  fetch(`/api/v1/employees/{{ $employee->id }}/leave-records`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
    body: new FormData(this)
  })
  .then(r => r.json())
  .then(data => { if(data.success) location.reload(); else alert(data.message || 'Error'); })
  .catch(() => alert('Error logging leave'))
  .finally(() => { btn.disabled = false; btn.innerHTML = 'Log Leave'; });
});

document.getElementById('logWfhForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const btn = this.querySelector('button[type="submit"]');
  btn.disabled = true;
  btn.innerHTML = '<i class="ti ti-loader me-1"></i>Saving...';

  fetch(`/api/v1/employees/{{ $employee->id }}/wfh-records`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
    body: new FormData(this)
  })
  .then(r => r.json())
  .then(data => { if(data.success) location.reload(); else alert(data.message || 'Error'); })
  .catch(() => alert('Error logging WFH'))
  .finally(() => { btn.disabled = false; btn.innerHTML = 'Log WFH'; });
});

function deleteLeaveRecord(id) {
  if(confirm('Delete this leave record?')) {
    fetch(`/api/v1/employees/{{ $employee->id }}/leave-records/${id}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
    }).then(r => r.json()).then(data => { if(data.success) location.reload(); else alert(data.message); });
  }
}

function deleteWfhRecord(id) {
  if(confirm('Delete this WFH record?')) {
    fetch(`/api/v1/employees/{{ $employee->id }}/wfh-records/${id}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
    }).then(r => r.json()).then(data => { if(data.success) location.reload(); else alert(data.message); });
  }
}
@endcan

document.getElementById('assignAssetForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const btn = this.querySelector('button[type="submit"]');
  btn.disabled = true;
  btn.innerHTML = '<i class="ti ti-loader me-1"></i>Assigning...';

  fetch(`{{ route('assetmanager.employee-assets.assign', $employee) }}`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
    body: new FormData(this)
  })
  .then(r => r.json())
  .then(data => { if(data.success) location.reload(); else alert(data.message || 'Error'); })
  .catch(() => alert('Error assigning asset'))
  .finally(() => { btn.disabled = false; btn.innerHTML = 'Assign'; });
});

function unassignAsset(assetId, assetName) {
  if(confirm(`Un-assign "${assetName}"?`)) {
    const formData = new FormData();
    formData.append('asset_id', assetId);
    formData.append('returned_date', new Date().toISOString().split('T')[0]);
    formData.append('notes', 'Asset returned');

    fetch(`{{ route('assetmanager.employee-assets.unassign', $employee) }}`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
      body: formData
    })
    .then(r => r.json())
    .then(data => { if(data.success) location.reload(); else alert(data.message); });
  }
}
</script>
@endsection
