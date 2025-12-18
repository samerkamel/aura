@extends('layouts/layoutMaster')

@section('title', 'My Profile')

@section('page-style')
<style>
  .profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 0.5rem 0.5rem 0 0;
    padding: 2rem;
    color: white;
  }
  .profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid white;
    background-color: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    font-weight: 600;
    color: #667eea;
  }
  .info-card {
    transition: all 0.3s ease;
  }
  .info-card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
  }
  .stat-card {
    text-align: center;
    padding: 1.5rem;
  }
  .stat-value {
    font-size: 2rem;
    font-weight: 700;
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
      <div class="profile-header">
        <div class="d-flex align-items-center gap-4">
          <div class="profile-avatar">
            {{ strtoupper(substr($user->name, 0, 2)) }}
          </div>
          <div>
            <h2 class="mb-1 text-white">{{ $user->name }}</h2>
            <p class="mb-2 opacity-75">
              <i class="ti ti-mail me-1"></i>{{ $user->email }}
            </p>
            @if($employee)
            <div class="d-flex gap-2 flex-wrap">
              @if($employee->positionRelation)
              <span class="badge bg-white text-primary">
                <i class="ti ti-briefcase me-1"></i>{{ $employee->positionRelation->full_title }}
              </span>
              @endif
              <span class="badge bg-white text-success">
                <i class="ti ti-calendar me-1"></i>Joined {{ $employee->start_date ? $employee->start_date->format('M Y') : 'N/A' }}
              </span>
            </div>
            @endif
          </div>
        </div>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4 text-center border-end">
            <div class="stat-card">
              <div class="stat-value text-primary">
                @if($employee)
                  {{ $employee->start_date ? $employee->start_date->diffInYears(now()) : 0 }}
                @else
                  0
                @endif
              </div>
              <small class="text-muted">Years of Service</small>
            </div>
          </div>
          <div class="col-md-4 text-center border-end">
            <div class="stat-card">
              <div class="stat-value text-success">
                @php
                  $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames() : collect([]);
                @endphp
                {{ $roles->count() }}
              </div>
              <small class="text-muted">Assigned Roles</small>
            </div>
          </div>
          <div class="col-md-4 text-center">
            <div class="stat-card">
              <div class="stat-value text-info">
                {{ floor($user->created_at->diffInDays(now())) }}
              </div>
              <small class="text-muted">Days Since Registration</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Account Information -->
      <div class="col-md-6 mb-4">
        <div class="card info-card h-100">
          <div class="card-header">
            <h5 class="mb-0">
              <i class="ti ti-user me-2 text-primary"></i>Account Information
            </h5>
          </div>
          <div class="card-body">
            <form action="{{ route('profile.update') }}" method="POST">
              @csrf
              @method('PUT')

              <div class="mb-3">
                <label class="form-label" for="name">Full Name</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                       id="name" name="name" value="{{ old('name', $user->name) }}" required>
                @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-3">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror"
                       id="email" name="email" value="{{ old('email', $user->email) }}" required>
                @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-3">
                <label class="form-label">Roles</label>
                <div>
                  @if(method_exists($user, 'getRoleNames'))
                    @foreach($user->getRoleNames() as $role)
                    <span class="badge bg-label-primary me-1">{{ ucfirst(str_replace('-', ' ', $role)) }}</span>
                    @endforeach
                  @else
                    <span class="text-muted">No roles assigned</span>
                  @endif
                </div>
              </div>

              <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-1"></i>Save Changes
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Change Password -->
      <div class="col-md-6 mb-4">
        <div class="card info-card h-100">
          <div class="card-header">
            <h5 class="mb-0">
              <i class="ti ti-lock me-2 text-warning"></i>Change Password
            </h5>
          </div>
          <div class="card-body">
            <form action="{{ route('profile.password') }}" method="POST">
              @csrf
              @method('PUT')

              <div class="mb-3">
                <label class="form-label" for="current_password">Current Password</label>
                <div class="input-group">
                  <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                         id="current_password" name="current_password" required>
                  <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                    <i class="ti ti-eye"></i>
                  </button>
                </div>
                @error('current_password')
                <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-3">
                <label class="form-label" for="password">New Password</label>
                <div class="input-group">
                  <input type="password" class="form-control @error('password') is-invalid @enderror"
                         id="password" name="password" required>
                  <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                    <i class="ti ti-eye"></i>
                  </button>
                </div>
                @error('password')
                <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
                <small class="text-muted">Minimum 8 characters</small>
              </div>

              <div class="mb-3">
                <label class="form-label" for="password_confirmation">Confirm New Password</label>
                <div class="input-group">
                  <input type="password" class="form-control"
                         id="password_confirmation" name="password_confirmation" required>
                  <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_confirmation')">
                    <i class="ti ti-eye"></i>
                  </button>
                </div>
              </div>

              <button type="submit" class="btn btn-warning">
                <i class="ti ti-lock me-1"></i>Change Password
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    @if($employee)
    <!-- Employee Information -->
    <div class="row">
      <div class="col-md-6 mb-4">
        <div class="card info-card h-100">
          <div class="card-header">
            <h5 class="mb-0">
              <i class="ti ti-id me-2 text-info"></i>Personal Details
            </h5>
          </div>
          <div class="card-body">
            <table class="table table-borderless mb-0">
              <tbody>
                <tr>
                  <td class="text-muted" width="40%">Full Name</td>
                  <td><strong>{{ $employee->name }}</strong></td>
                </tr>
                @if($employee->name_ar)
                <tr>
                  <td class="text-muted">Arabic Name</td>
                  <td dir="rtl"><strong>{{ $employee->name_ar }}</strong></td>
                </tr>
                @endif
                <tr>
                  <td class="text-muted">Work Email</td>
                  <td><a href="mailto:{{ $employee->email }}">{{ $employee->email }}</a></td>
                </tr>
                @if($employee->personal_email)
                <tr>
                  <td class="text-muted">Personal Email</td>
                  <td><a href="mailto:{{ $employee->personal_email }}">{{ $employee->personal_email }}</a></td>
                </tr>
                @endif
                @if($employee->national_id)
                <tr>
                  <td class="text-muted">National ID</td>
                  <td><span class="font-monospace">{{ $employee->national_id }}</span></td>
                </tr>
                @endif
                @if($employee->contact_info && isset($employee->contact_info['mobile_number']))
                <tr>
                  <td class="text-muted">Mobile</td>
                  <td><a href="tel:{{ $employee->contact_info['mobile_number'] }}">{{ $employee->contact_info['mobile_number'] }}</a></td>
                </tr>
                @endif
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-md-6 mb-4">
        <div class="card info-card h-100">
          <div class="card-header">
            <h5 class="mb-0">
              <i class="ti ti-briefcase me-2 text-success"></i>Employment Details
            </h5>
          </div>
          <div class="card-body">
            <table class="table table-borderless mb-0">
              <tbody>
                <tr>
                  <td class="text-muted" width="40%">Position</td>
                  <td>
                    @if($employee->positionRelation)
                    <span class="badge bg-label-primary">{{ $employee->positionRelation->full_title }}</span>
                    @else
                    <span class="text-muted">Not Assigned</span>
                    @endif
                  </td>
                </tr>
                @if($employee->positionRelation && $employee->positionRelation->department)
                <tr>
                  <td class="text-muted">Department</td>
                  <td><span class="badge bg-label-info">{{ $employee->positionRelation->department }}</span></td>
                </tr>
                @endif
                <tr>
                  <td class="text-muted">Start Date</td>
                  <td>{{ $employee->start_date ? $employee->start_date->format('F d, Y') : 'Not Set' }}</td>
                </tr>
                <tr>
                  <td class="text-muted">Status</td>
                  <td>
                    @if($employee->status === 'active')
                    <span class="badge bg-success">Active</span>
                    @else
                    <span class="badge bg-danger">{{ ucfirst($employee->status) }}</span>
                    @endif
                  </td>
                </tr>
                @if($employee->manager)
                <tr>
                  <td class="text-muted">Manager</td>
                  <td>{{ $employee->manager->name }}</td>
                </tr>
                @endif
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Links -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="ti ti-link me-2 text-primary"></i>Quick Links
        </h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <a href="{{ route('self-service.attendance') }}" class="btn btn-outline-primary w-100">
              <i class="ti ti-clock me-2"></i>My Attendance
            </a>
          </div>
          <div class="col-md-3">
            <a href="{{ route('self-service.leave-requests.index') }}" class="btn btn-outline-info w-100">
              <i class="ti ti-calendar me-2"></i>Leave Requests
            </a>
          </div>
          <div class="col-md-3">
            <a href="{{ route('self-service.wfh-requests.index') }}" class="btn btn-outline-success w-100">
              <i class="ti ti-home me-2"></i>WFH Requests
            </a>
          </div>
          <div class="col-md-3">
            <a href="{{ route('self-service.permission-requests.index') }}" class="btn btn-outline-warning w-100">
              <i class="ti ti-clock-pause me-2"></i>Permission Requests
            </a>
          </div>
        </div>
      </div>
    </div>
    @endif
  </div>
</div>
@endsection

@section('page-script')
<script>
function togglePassword(fieldId) {
  const field = document.getElementById(fieldId);
  const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
  field.setAttribute('type', type);
}
</script>
@endsection
