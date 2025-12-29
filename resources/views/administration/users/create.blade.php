@extends('layouts/layoutMaster')

@section('title', 'Create User')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold py-3 mb-0">Create User</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('administration.users.index') }}">User Management</a>
                    </li>
                    <li class="breadcrumb-item active">Create User</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('administration.users.index') }}" class="btn btn-outline-secondary">
            <i class="ti tabler-arrow-left me-2"></i>Back to Users
        </a>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">User Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('administration.users.store') }}">
                        @csrf

                        <div class="row g-3">
                            <!-- Name -->
                            <div class="col-md-6">
                                <label class="form-label" for="name">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       id="email" name="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Password -->
                            <div class="col-md-6">
                                <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                                           id="password" name="password" required>
                                    <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                        <i class="ti tabler-eye"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Minimum 8 characters</small>
                            </div>

                            <!-- Confirm Password -->
                            <div class="col-md-6">
                                <label class="form-label" for="password_confirmation">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror"
                                           id="password_confirmation" name="password_confirmation" required>
                                    <button type="button" class="btn btn-outline-secondary" id="togglePasswordConfirm">
                                        <i class="ti tabler-eye"></i>
                                    </button>
                                </div>
                                @error('password_confirmation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Roles -->
                            <div class="col-12">
                                <label class="form-label">Roles</label>
                                <select class="form-select select2 @error('roles') is-invalid @enderror"
                                        name="roles[]" multiple>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}"
                                                {{ in_array($role->id, old('roles', [])) ? 'selected' : '' }}>
                                            {{ $role->display_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('roles')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Select one or more roles for this user</small>
                            </div>

                            <!-- Status -->
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active"
                                           name="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Active User
                                    </label>
                                </div>
                                <small class="text-muted">Inactive users cannot log in to the system</small>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="pt-4 border-top mt-4">
                            <div class="d-flex justify-content-end gap-3">
                                <a href="{{ route('administration.users.index') }}" class="btn btn-outline-secondary">
                                    <i class="ti tabler-x me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti tabler-device-floppy me-1"></i>Create User
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <!-- Help Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti tabler-info-circle me-2"></i>Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6>Creating a New User</h6>
                        <ul class="mb-0 ps-3">
                            <li>All fields marked with * are required</li>
                            <li>Email addresses must be unique</li>
                            <li>Password must be at least 8 characters</li>
                            <li>Users can have multiple roles</li>
                            <li>Inactive users cannot access the system</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Available Roles -->
            @if($roles->count() > 0)
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti tabler-shield-check me-2"></i>Available Roles
                    </h5>
                </div>
                <div class="card-body">
                    @foreach($roles as $role)
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar avatar-xs flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti tabler-shield ti-xs"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0">{{ $role->display_name }}</h6>
                            <small class="text-muted">{{ $role->description }}</small>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    const select2Elements = document.querySelectorAll('.select2');
    if (select2Elements.length && typeof jQuery !== 'undefined') {
        jQuery('.select2').select2({
            placeholder: 'Select roles...',
            allowClear: true
        });
    }

    // Password visibility toggle
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const icon = this.querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('ti-eye');
                icon.classList.add('ti-eye-off');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('ti-eye-off');
                icon.classList.add('ti-eye');
            }
        });
    }

    const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
    const passwordConfirmInput = document.getElementById('password_confirmation');
    if (togglePasswordConfirm && passwordConfirmInput) {
        togglePasswordConfirm.addEventListener('click', function() {
            const icon = this.querySelector('i');
            if (passwordConfirmInput.type === 'password') {
                passwordConfirmInput.type = 'text';
                icon.classList.remove('ti-eye');
                icon.classList.add('ti-eye-off');
            } else {
                passwordConfirmInput.type = 'password';
                icon.classList.remove('ti-eye-off');
                icon.classList.add('ti-eye');
            }
        });
    }

    // Form validation
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('password_confirmation').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
                document.getElementById('password_confirmation').focus();
                return false;
            }
        });
    }
});
</script>
@endsection