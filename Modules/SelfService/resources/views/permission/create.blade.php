@extends('layouts/layoutMaster')

@section('title', 'Request Permission')

@section('content')
<div class="row">
  <div class="col-12 col-lg-8 mx-auto">
    <!-- Page Header -->
    <div class="card mb-4">
      <div class="card-header">
        <div class="d-flex align-items-center">
          <a href="{{ route('self-service.permission-requests.index') }}" class="btn btn-icon btn-outline-secondary me-3">
            <i class="ti tabler-arrow-left"></i>
          </a>
          <div>
            <h5 class="mb-0">
              <i class="ti tabler-clock-plus me-2"></i>Request Permission
            </h5>
            <small class="text-muted">Submit a new permission request (late arrival / early leave)</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Permission Allowance -->
    <div class="card mb-4">
      <div class="card-header">
        <h6 class="mb-0"><i class="ti tabler-wallet me-2"></i>Your Permission Allowance</h6>
      </div>
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="d-flex align-items-center">
              <div class="avatar avatar-lg me-3 bg-label-warning">
                <span class="avatar-initial rounded">
                  <i class="ti tabler-clock-pause"></i>
                </span>
              </div>
              <div>
                <h3 class="mb-0 {{ $permissionData['remaining'] > 0 ? 'text-success' : 'text-danger' }}">
                  {{ $permissionData['remaining'] }} remaining
                </h3>
                <small class="text-muted">{{ $permissionData['used'] }} used of {{ $permissionData['allowance'] }} this month</small>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="progress" style="height: 10px;">
              @php
                $usedPercent = $permissionData['allowance'] > 0 ? (($permissionData['used'] + $permissionData['pending']) / $permissionData['allowance']) * 100 : 0;
              @endphp
              <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $usedPercent }}%"></div>
            </div>
            <small class="text-muted d-block mt-1">
              Each permission = {{ $permissionData['minutes_per_permission'] }} minutes grace time
            </small>
            @if($permissionData['pending'] > 0)
              <small class="text-warning d-block">{{ $permissionData['pending'] }} request(s) pending</small>
            @endif
          </div>
        </div>
      </div>
    </div>

    @if($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if($permissionData['remaining'] <= 0)
      <div class="alert alert-warning">
        <i class="ti tabler-alert-triangle me-2"></i>
        <strong>Allowance Exceeded</strong> - You have used all your permissions for this month.
      </div>
    @endif

    <!-- Request Form -->
    <div class="card">
      <div class="card-body">
        <form action="{{ route('self-service.permission-requests.store') }}" method="POST">
          @csrf

          <div class="mb-3">
            <label class="form-label" for="date">Permission Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ old('date') }}" @if(!($allowPastDates ?? false)) min="{{ date('Y-m-d') }}" @endif required {{ $permissionData['remaining'] <= 0 ? 'disabled' : '' }}>
            @error('date')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            @if($allowPastDates ?? false)
              <small class="form-text text-warning"><i class="ti tabler-alert-triangle me-1"></i>Past dates are allowed for this request.</small>
            @else
              <small class="text-muted">Select the date you need the permission for</small>
            @endif
          </div>

          <div class="alert alert-light mb-3">
            <div class="d-flex align-items-center">
              <i class="ti tabler-clock me-2 text-warning" style="font-size: 1.5rem;"></i>
              <div>
                <strong>Permission Duration: {{ $permissionData['minutes_per_permission'] }} minutes</strong>
                <p class="mb-0 small text-muted">This extends your flexible hours deadline by {{ $permissionData['minutes_per_permission'] }} minutes on the selected date.</p>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="notes">Notes (Optional)</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3" placeholder="Reason for permission request (e.g., Doctor's appointment, Personal errand)..." {{ $permissionData['remaining'] <= 0 ? 'disabled' : '' }}>{{ old('notes') }}</textarea>
            @error('notes')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="alert alert-info">
            <i class="ti tabler-info-circle me-2"></i>
            @if($employee->manager)
              Your request will be sent to <strong>{{ $employee->manager->name }}</strong> for initial approval, then to admin for final approval.
            @else
              Your request will be sent directly to admin for approval.
            @endif
          </div>

          <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('self-service.permission-requests.index') }}" class="btn btn-outline-secondary">
              Cancel
            </a>
            <button type="submit" class="btn btn-primary" {{ $permissionData['remaining'] <= 0 ? 'disabled' : '' }}>
              <i class="ti tabler-send me-1"></i>Submit Request
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
