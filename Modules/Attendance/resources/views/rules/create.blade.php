@extends('layouts/layoutMaster')

@section('title', 'Create Flexible Hours Rule')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/flatpickr/flatpickr.scss'
])
@endsection

@section('page-style')
@vite([
  'resources/assets/vendor/scss/pages/page-misc.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/flatpickr/flatpickr.js'
])
@endsection

@section('page-script')
@vite([
  'resources/assets/js/forms-pickers.js'
])
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Header Card -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <i class="ti ti-clock me-2 text-primary" style="font-size: 1.5rem;"></i>
          <div>
            <h5 class="mb-0">
              {{ $existingRule ? 'Update' : 'Create' }} Flexible Hours Rule
            </h5>
            <small class="text-muted">Define flexible start time range for employees</small>
          </div>
        </div>
        <a href="{{ route('attendance.rules.index') }}" class="btn btn-outline-secondary">
          <i class="ti ti-arrow-left me-1"></i>Back to Rules
        </a>
      </div>
    </div>

    <div class="row">
      <div class="col-md-8 mx-auto">
        <div class="card">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="ti ti-settings me-2"></i>Flexible Hours Configuration
            </h6>
          </div>
          <div class="card-body">
            @if($existingRule)
              <div class="alert alert-info" role="alert">
                <i class="ti ti-info-circle me-2"></i>
                <strong>Updating Existing Rule:</strong> Only one flexible hours rule is allowed. This will update the current rule.
              </div>
            @endif

            @if ($errors->any())
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h6 class="alert-heading">
                  <i class="ti ti-x me-2"></i>Validation Errors
                </h6>
                <ul class="mb-0">
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            @endif

            <form method="POST" action="{{ route('attendance.rules.store') }}">
              @csrf

              <!-- Rule Name -->
              <div class="mb-4">
                <label for="rule_name" class="form-label">
                  Rule Name <span class="text-danger">*</span>
                </label>
                <input type="text"
                       class="form-control @error('rule_name') is-invalid @enderror"
                       id="rule_name"
                       name="rule_name"
                       value="{{ old('rule_name', $existingRule->rule_name ?? 'Flexible Start Time') }}"
                       placeholder="Enter rule name (e.g., Flexible Start Time)"
                       required>
                @error('rule_name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">Give this rule a descriptive name</div>
              </div>

              <!-- Time Range Configuration -->
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-4">
                    <label for="start_time_from" class="form-label">
                      Start Time From <span class="text-danger">*</span>
                    </label>
                    <input type="time"
                           class="form-control @error('start_time_from') is-invalid @enderror"
                           id="start_time_from"
                           name="start_time_from"
                           value="{{ old('start_time_from', $existingRule->config['from'] ?? '08:00') }}"
                           required>
                    @error('start_time_from')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Earliest allowed start time</div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="mb-4">
                    <label for="start_time_to" class="form-label">
                      Start Time To <span class="text-danger">*</span>
                    </label>
                    <input type="time"
                           class="form-control @error('start_time_to') is-invalid @enderror"
                           id="start_time_to"
                           name="start_time_to"
                           value="{{ old('start_time_to', $existingRule->config['to'] ?? '10:00') }}"
                           required>
                    @error('start_time_to')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Latest allowed start time</div>
                  </div>
                </div>
              </div>

              <!-- Example Display -->
              <div class="alert alert-light" role="alert">
                <h6 class="alert-heading">
                  <i class="ti ti-lightbulb me-2"></i>Example
                </h6>
                <p class="mb-0">
                  If you set the range from <strong>08:00</strong> to <strong>10:00</strong>, employees can start work anytime between 8:00 AM and 10:00 AM without being marked as late.
                </p>
              </div>

              <!-- Action Buttons -->
              <div class="d-flex justify-content-between">
                <a href="{{ route('attendance.rules.index') }}" class="btn btn-outline-secondary">
                  <i class="ti ti-x me-1"></i>Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                  <i class="ti ti-check me-1"></i>
                  {{ $existingRule ? 'Update Rule' : 'Create Rule' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add time validation to ensure 'to' time is after 'from' time
    const fromInput = document.getElementById('start_time_from');
    const toInput = document.getElementById('start_time_to');

    function validateTimeRange() {
        if (fromInput.value && toInput.value) {
            const fromTime = new Date('2000-01-01 ' + fromInput.value);
            const toTime = new Date('2000-01-01 ' + toInput.value);

            if (toTime <= fromTime) {
                toInput.setCustomValidity('To time must be later than From time');
            } else {
                toInput.setCustomValidity('');
            }
        }
    }

    fromInput.addEventListener('change', validateTimeRange);
    toInput.addEventListener('change', validateTimeRange);
});
</script>
@endsection
