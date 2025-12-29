@extends('layouts/layoutMaster')

@section('title', 'Attendance Rules')

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Rules Header Card -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <i class="ti tabler-rules me-2 text-primary" style="font-size: 1.5rem;"></i>
          <div>
            <h5 class="mb-0">Attendance Rules</h5>
            <small class="text-muted">Manage attendance rules and policies</small>
          </div>
        </div>
        <a href="{{ route('attendance.rules.create') }}" class="btn btn-primary">
          <i class="ti tabler-plus me-1"></i>Create Rule
        </a>
      </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ti tabler-check me-1"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <!-- Flexible Hours Rules Section -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
              <i class="ti tabler-clock me-2"></i>Flexible Hours Rules
            </h6>
            <a href="{{ route('attendance.rules.create') }}" class="btn btn-sm btn-outline-primary">
              <i class="ti tabler-plus me-1"></i>Add Flexible Hours
            </a>
          </div>
          <div class="card-body">
            @php
              $flexibleRules = $rules->where('rule_type', 'flexible_hours');
            @endphp

            @if($flexibleRules->count() > 0)
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Rule Name</th>
                      <th>Time Range</th>
                      <th>Created</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($flexibleRules as $rule)
                      <tr>
                        <td>
                          <strong>{{ $rule->rule_name }}</strong>
                        </td>
                        <td>
                          <div class="d-flex align-items-center">
                            <i class="ti tabler-clock me-2 text-info"></i>
                            <span>{{ $rule->config['from'] ?? 'N/A' }} - {{ $rule->config['to'] ?? 'N/A' }}</span>
                          </div>
                        </td>
                        <td>
                          <small class="text-muted">
                            {{ $rule->created_at->format('M d, Y') }}
                          </small>
                        </td>
                        <td>
                          <a href="{{ route('attendance.rules.create') }}"
                             class="btn btn-sm btn-outline-primary">
                            <i class="ti tabler-edit me-1"></i>Edit
                          </a>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <div class="text-center py-4">
                <div class="mb-3">
                  <i class="ti tabler-clock text-muted" style="font-size: 2rem;"></i>
                </div>
                <p class="text-muted mb-3">No flexible hours rules configured yet.</p>
                <a href="{{ route('attendance.rules.create') }}" class="btn btn-primary btn-sm">
                  <i class="ti tabler-plus me-1"></i>Create Flexible Hours Rule
                </a>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- Late-in Penalties Section -->
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
              <i class="ti tabler-alert-triangle me-2"></i>Late-in Penalties
            </h6>
            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#addPenaltyModal">
              <i class="ti tabler-plus me-1"></i>Add Penalty Rule
            </button>
          </div>
          <div class="card-body">
            @php
              $penaltyRules = $rules->where('rule_type', 'late_penalty');
            @endphp

            @if($penaltyRules->count() > 0)
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Rule Name</th>
                      <th>If Late By</th>
                      <th>Deduct</th>
                      <th>Created</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($penaltyRules as $rule)
                      <tr>
                        <td>
                          <strong>{{ $rule->rule_name }}</strong>
                        </td>
                        <td>
                          <div class="d-flex align-items-center">
                            <i class="ti tabler-clock-hour-4 me-2 text-warning"></i>
                            <span>{{ $rule->config['late_minutes'] ?? 'N/A' }} minutes</span>
                          </div>
                        </td>
                        <td>
                          <div class="d-flex align-items-center">
                            <i class="ti tabler-minus me-2 text-danger"></i>
                            <span>{{ $rule->config['penalty_minutes'] ?? 'N/A' }} minutes</span>
                          </div>
                        </td>
                        <td>
                          <small class="text-muted">
                            {{ $rule->created_at->format('M d, Y') }}
                          </small>
                        </td>
                        <td>
                          <form method="POST" action="{{ route('attendance.rules.destroy', $rule) }}"
                                class="d-inline"
                                onsubmit="return confirm('Are you sure you want to delete this penalty rule?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                              <i class="ti tabler-trash me-1"></i>Delete
                            </button>
                          </form>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <div class="text-center py-4">
                <div class="mb-3">
                  <i class="ti tabler-alert-triangle text-muted" style="font-size: 2rem;"></i>
                </div>
                <p class="text-muted mb-3">No late penalty rules configured yet.</p>
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#addPenaltyModal">
                  <i class="ti tabler-plus me-1"></i>Create First Penalty Rule
                </button>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
<br>
    <!-- Employee Permissions Section -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="ti tabler-user-check me-2"></i>Employee Permissions
            </h6>
          </div>
          <div class="card-body">
            @php
              $permissionRule = $rules->where('rule_type', 'permission')->first();
            @endphp

            <form method="POST" action="{{ route('attendance.rules.store') }}">
              @csrf
              <input type="hidden" name="rule_type" value="permission">

              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="max_per_month" class="form-label">Max Permissions Per Month <span class="text-danger">*</span></label>
                    <input type="number"
                           class="form-control @error('max_per_month') is-invalid @enderror"
                           id="max_per_month"
                           name="max_per_month"
                           value="{{ old('max_per_month', $permissionRule->config['max_per_month'] ?? '') }}"
                           min="1"
                           max="31"
                           placeholder="2"
                           required>
                    @error('max_per_month')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">Maximum number of permissions an employee can take per month</small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="minutes_per_permission" class="form-label">Length of Each Permission (minutes) <span class="text-danger">*</span></label>
                    <input type="number"
                           class="form-control @error('minutes_per_permission') is-invalid @enderror"
                           id="minutes_per_permission"
                           name="minutes_per_permission"
                           value="{{ old('minutes_per_permission', $permissionRule->config['minutes_per_permission'] ?? '') }}"
                           min="1"
                           max="1440"
                           placeholder="60"
                           required>
                    @error('minutes_per_permission')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">Duration of each permission in minutes</small>
                  </div>
                </div>
              </div>

              <div class="alert alert-info">
                <i class="ti tabler-info-circle me-2"></i>
                <strong>Example:</strong> Allow 2 permissions per month, each lasting 60 minutes. This time will not be treated as a penalty.
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                  <i class="ti tabler-device-floppy me-1"></i>
                  {{ $permissionRule ? 'Update Configuration' : 'Save Configuration' }}
                </button>
                @if($permissionRule)
                  <span class="text-muted align-self-center">
                    <i class="ti tabler-info-circle me-1"></i>Last updated: {{ $permissionRule->updated_at->format('M d, Y H:i') }}
                  </span>
                @endif
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Work-From-Home (WFH) Policy Section -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="ti tabler-home-2 me-2"></i>Work-From-Home (WFH) Policy
            </h6>
          </div>
          <div class="card-body">
            @php
              $wfhRule = $rules->where('rule_type', 'wfh_policy')->first();
            @endphp

            <form method="POST" action="{{ route('attendance.rules.store') }}">
              @csrf
              <input type="hidden" name="rule_type" value="wfh_policy">

              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="max_days_per_month" class="form-label">Max WFH Days Per Month <span class="text-danger">*</span></label>
                    <input type="number"
                           class="form-control @error('max_days_per_month') is-invalid @enderror"
                           id="max_days_per_month"
                           name="max_days_per_month"
                           value="{{ old('max_days_per_month', $wfhRule->config['max_days_per_month'] ?? '') }}"
                           min="1"
                           max="31"
                           placeholder="5"
                           required>
                    @error('max_days_per_month')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">Maximum number of WFH days allowed per month</small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="attendance_percentage" class="form-label">Attendance Value (%) <span class="text-danger">*</span></label>
                    <input type="number"
                           class="form-control @error('attendance_percentage') is-invalid @enderror"
                           id="attendance_percentage"
                           name="attendance_percentage"
                           value="{{ old('attendance_percentage', $wfhRule->config['attendance_percentage'] ?? '') }}"
                           min="0"
                           max="100"
                           placeholder="80"
                           required>
                    @error('attendance_percentage')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">How much a WFH day contributes to total attendance</small>
                  </div>
                </div>
              </div>

              <div class="alert alert-info">
                <i class="ti tabler-info-circle me-2"></i>
                <strong>Example:</strong> Allow 5 WFH days per month, each counting as 80% attendance. A WFH day will contribute 80% to the employee's total attendance score.
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                  <i class="ti tabler-device-floppy me-1"></i>
                  {{ $wfhRule ? 'Update WFH Policy' : 'Save WFH Policy' }}
                </button>
                @if($wfhRule)
                  <span class="text-muted align-self-center">
                    <i class="ti tabler-info-circle me-1"></i>Last updated: {{ $wfhRule->updated_at->format('M d, Y H:i') }}
                  </span>
                @endif
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Add Penalty Rule Modal -->
<div class="modal fade" id="addPenaltyModal" tabindex="-1" aria-labelledby="addPenaltyModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('attendance.rules.store') }}">
        @csrf
        <input type="hidden" name="rule_type" value="late_penalty">

        <div class="modal-header">
          <h5 class="modal-title" id="addPenaltyModalLabel">
            <i class="ti tabler-alert-triangle me-2"></i>Add Late Penalty Rule
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label for="rule_name" class="form-label">Rule Name <span class="text-danger">*</span></label>
            <input type="text"
                   class="form-control @error('rule_name') is-invalid @enderror"
                   id="rule_name"
                   name="rule_name"
                   value="{{ old('rule_name') }}"
                   placeholder="e.g., 15 Minutes Late Penalty"
                   required>
            @error('rule_name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="late_minutes" class="form-label">If Late By (minutes) <span class="text-danger">*</span></label>
                <input type="number"
                       class="form-control @error('late_minutes') is-invalid @enderror"
                       id="late_minutes"
                       name="late_minutes"
                       value="{{ old('late_minutes') }}"
                       min="1"
                       max="1440"
                       placeholder="15"
                       required>
                @error('late_minutes')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="penalty_minutes" class="form-label">Deduct (minutes) <span class="text-danger">*</span></label>
                <input type="number"
                       class="form-control @error('penalty_minutes') is-invalid @enderror"
                       id="penalty_minutes"
                       name="penalty_minutes"
                       value="{{ old('penalty_minutes') }}"
                       min="1"
                       max="1440"
                       placeholder="30"
                       required>
                @error('penalty_minutes')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="alert alert-info">
            <i class="ti tabler-info-circle me-2"></i>
            <strong>Example:</strong> If an employee is late by 15 minutes, deduct 30 minutes from their attendance.
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">
            <i class="ti tabler-plus me-1"></i>Add Penalty Rule
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@if($errors->any())
<script>
  document.addEventListener('DOMContentLoaded', function() {
    var modal = new bootstrap.Modal(document.getElementById('addPenaltyModal'));
    modal.show();
  });
</script>
@endif

@endsection
