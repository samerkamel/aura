@extends('layouts/layoutMaster')

@section('title', 'Manage Users - ' . $businessUnit->name)

@section('vendor-style')
@vite('resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss')
@vite('resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss')
@vite('resources/assets/vendor/libs/select2/select2.scss')
@endsection

@section('vendor-script')
@vite('resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')
@vite('resources/assets/vendor/libs/select2/select2.js')
@endsection

@section('content')
<div class="row">
  <!-- Business Unit Header -->
  <div class="col-12 mb-6">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-lg me-4">
              <span class="avatar-initial rounded-circle bg-label-{{ $businessUnit->type === 'head_office' ? 'info' : 'primary' }}">
                <i class="ti {{ $businessUnit->type === 'head_office' ? 'ti-building-skyscraper' : 'ti-building' }} ti-lg"></i>
              </span>
            </div>
            <div>
              <h4 class="mb-0">{{ $businessUnit->name }}</h4>
              <p class="text-muted mb-0">Manage User Access</p>
            </div>
          </div>
          <a href="{{ route('administration.business-units.show', $businessUnit) }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Back to Details
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Add User Form -->
  <div class="col-12 mb-6">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="ti ti-user-plus me-2"></i>Assign User to Business Unit
        </h5>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('administration.business-units.assign-user', $businessUnit) }}">
          @csrf
          <div class="row align-items-end">
            <div class="col-12 col-md-5 mb-3">
              <label for="user_id" class="form-label">Select User</label>
              <select class="form-select select2 @error('user_id') is-invalid @enderror" id="user_id" name="user_id" required>
                <option value="">Choose a user...</option>
                @foreach($allUsers as $user)
                  @if(!in_array($user->id, $assignedUserIds))
                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                      {{ $user->name }} ({{ $user->email }})
                    </option>
                  @endif
                @endforeach
              </select>
              @error('user_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-12 col-md-4 mb-3">
              <label for="role" class="form-label">Role in Business Unit</label>
              <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                <option value="">Select role...</option>
                <option value="member" {{ old('role') == 'member' ? 'selected' : '' }}>Member</option>
                <option value="manager" {{ old('role') == 'manager' ? 'selected' : '' }}>Manager</option>
                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
              </select>
              @error('role')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-12 col-md-3 mb-3">
              <button type="submit" class="btn btn-primary w-100">
                <i class="ti ti-plus me-1"></i>Assign User
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Assigned Users -->
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="ti ti-users me-2"></i>Assigned Users ({{ $businessUnit->users->count() }})
        </h5>
      </div>
      <div class="card-body">
        @if($businessUnit->users->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover dt-advanced-search">
              <thead>
                <tr>
                  <th>User</th>
                  <th>System Roles</th>
                  <th>BU Role</th>
                  <th>Assigned Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($businessUnit->users as $user)
                  <tr>
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-3">
                          <img src="{{ $user->profile_photo_url ?? asset('assets/img/avatars/1.png') }}" class="rounded-circle" alt="{{ $user->name }}">
                        </div>
                        <div>
                          <h6 class="mb-0">{{ $user->name }}</h6>
                          <small class="text-muted">{{ $user->email }}</small>
                          @if($user->is_active)
                            <span class="badge bg-label-success ms-1">Active</span>
                          @else
                            <span class="badge bg-label-secondary ms-1">Inactive</span>
                          @endif
                        </div>
                      </div>
                    </td>
                    <td>
                      @if($user->roles->count() > 0)
                        @foreach($user->roles as $role)
                          <span class="badge bg-label-info me-1">{{ $role->display_name }}</span>
                        @endforeach
                      @else
                        <span class="text-muted">No roles assigned</span>
                      @endif
                    </td>
                    <td>
                      <span class="badge bg-label-{{ $user->pivot->role === 'admin' ? 'danger' : ($user->pivot->role === 'manager' ? 'warning' : 'primary') }}">
                        {{ ucfirst($user->pivot->role) }}
                      </span>
                    </td>
                    <td>{{ $user->pivot->created_at->format('M d, Y \a\t g:i A') }}</td>
                    <td>
                      <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                          <i class="ti ti-dots-vertical"></i>
                        </button>
                        <div class="dropdown-menu">
                          <a class="dropdown-item" href="{{ route('administration.users.show', $user) }}">
                            <i class="ti ti-eye me-2"></i>View User
                          </a>
                          <div class="dropdown-divider"></div>
                          <form method="POST" action="{{ route('administration.business-units.unassign-user', $businessUnit) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to unassign this user from the business unit?')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <button type="submit" class="dropdown-item text-danger">
                              <i class="ti ti-user-minus me-2"></i>Unassign User
                            </button>
                          </form>
                        </div>
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="text-center py-5">
            <i class="ti ti-users ti-lg text-muted mb-3"></i>
            <h5 class="text-muted">No Users Assigned</h5>
            <p class="text-muted mb-4">This business unit doesn't have any assigned users yet.</p>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

@if(session('success'))
  <div class="bs-toast toast toast-placement-ex m-2 fade bg-success show top-0 end-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
    <div class="toast-header">
      <i class="ti ti-check text-success me-2"></i>
      <div class="me-auto fw-medium">Success!</div>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">{{ session('success') }}</div>
  </div>
@endif

@if(session('error'))
  <div class="bs-toast toast toast-placement-ex m-2 fade bg-danger show top-0 end-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
    <div class="toast-header">
      <i class="ti ti-x text-danger me-2"></i>
      <div class="me-auto fw-medium">Error!</div>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">{{ session('error') }}</div>
  </div>
@endif

@if($errors->any())
  <div class="bs-toast toast toast-placement-ex m-2 fade bg-danger show top-0 end-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
    <div class="toast-header">
      <i class="ti ti-x text-danger me-2"></i>
      <div class="me-auto fw-medium">Validation Error!</div>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">
      Please check the form for errors and try again.
    </div>
  </div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Initialize Select2
  const select2Elements = document.querySelectorAll('.select2');
  if (select2Elements.length && typeof jQuery !== 'undefined') {
    jQuery('.select2').select2({
      theme: 'bootstrap5',
      placeholder: 'Choose a user...',
      allowClear: true
    });
  }
});
</script>
@endsection