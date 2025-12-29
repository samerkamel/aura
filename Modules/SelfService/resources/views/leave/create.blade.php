@extends('layouts/layoutMaster')

@section('title', 'Request Leave')

@section('content')
<div class="row">
  <div class="col-12 col-lg-8 mx-auto">
    <!-- Page Header -->
    <div class="card mb-4">
      <div class="card-header">
        <div class="d-flex align-items-center">
          <a href="{{ route('self-service.leave-requests.index') }}" class="btn btn-icon btn-outline-secondary me-3">
            <i class="ti tabler-arrow-left"></i>
          </a>
          <div>
            <h5 class="mb-0">
              <i class="ti tabler-calendar-plus me-2"></i>Request Leave
            </h5>
            <small class="text-muted">Submit a new leave request</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Leave Balances -->
    <div class="card mb-4">
      <div class="card-header">
        <h6 class="mb-0"><i class="ti tabler-wallet me-2"></i>Your Leave Balances</h6>
      </div>
      <div class="card-body">
        <div class="row">
          @foreach($leaveBalances as $balance)
          <div class="col-md-4 mb-3">
            <div class="border rounded p-3 text-center">
              <h5 class="mb-1 {{ $balance['remaining_days'] > 0 ? 'text-success' : 'text-danger' }}">
                {{ number_format($balance['remaining_days'], 1) }} days
              </h5>
              <small class="text-muted">{{ $balance['policy_name'] }}</small>
              <div class="progress mt-2" style="height: 4px;">
                @php
                  $usedPercent = $balance['entitled_days'] > 0 ? (($balance['entitled_days'] - $balance['remaining_days']) / $balance['entitled_days']) * 100 : 0;
                @endphp
                <div class="progress-bar" role="progressbar" style="width: {{ $usedPercent }}%"></div>
              </div>
              <small class="text-muted d-block mt-1">{{ number_format($balance['used_days'], 1) }} used of {{ number_format($balance['entitled_days'], 1) }}</small>
            </div>
          </div>
          @endforeach
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

    <!-- Request Form -->
    <div class="card">
      <div class="card-body">
        <form action="{{ route('self-service.leave-requests.store') }}" method="POST">
          @csrf

          <div class="mb-3">
            <label class="form-label" for="leave_policy_id">Leave Type <span class="text-danger">*</span></label>
            <select class="form-select @error('leave_policy_id') is-invalid @enderror" id="leave_policy_id" name="leave_policy_id" required>
              <option value="">Select leave type...</option>
              @foreach($leavePolicies as $policy)
                @php
                  $balance = collect($leaveBalances)->firstWhere('policy_id', $policy->id);
                  $remaining = $balance['remaining_days'] ?? 0;
                @endphp
                <option value="{{ $policy->id }}" {{ old('leave_policy_id') == $policy->id ? 'selected' : '' }} {{ $remaining <= 0 ? 'disabled' : '' }}>
                  {{ $policy->name }} ({{ number_format($remaining, 1) }} days remaining)
                </option>
              @endforeach
            </select>
            @error('leave_policy_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="start_date">Start Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date') }}" @if(!($allowPastDates ?? false)) min="{{ date('Y-m-d') }}" @endif required>
              @error('start_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              @if($allowPastDates ?? false)
                <small class="form-text text-warning"><i class="ti tabler-alert-triangle me-1"></i>Past dates are allowed for this request.</small>
              @endif
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label" for="end_date">End Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date') }}" required>
              @error('end_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="notes">Notes (Optional)</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3" placeholder="Reason for leave or any additional information...">{{ old('notes') }}</textarea>
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
            <a href="{{ route('self-service.leave-requests.index') }}" class="btn btn-outline-secondary">
              Cancel
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="ti tabler-send me-1"></i>Submit Request
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const startDate = document.getElementById('start_date');
  const endDate = document.getElementById('end_date');

  startDate.addEventListener('change', function() {
    endDate.min = this.value;
    if (endDate.value && endDate.value < this.value) {
      endDate.value = this.value;
    }
  });
});
</script>
@endsection
