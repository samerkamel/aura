@extends('layouts/layoutMaster')

@section('title', 'Attendance Settings')

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Settings Header Card -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <i class="ti ti-settings me-2 text-primary" style="font-size: 1.5rem;"></i>
          <div>
            <h5 class="mb-0">Attendance Settings</h5>
            <small class="text-muted">Configure standard work hours and weekend days</small>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Settings Form -->
      <div class="col-md-8 mx-auto">
        <div class="card">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="ti ti-clock me-2"></i>Work Schedule Configuration
            </h6>
          </div>
          <div class="card-body">
            @if(session('success'))
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ti ti-check me-1"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            @endif

            <form method="POST" action="{{ route('attendance.settings.update') }}">
              @csrf
              @method('PUT')

              <!-- Work Hours Per Day -->
              <div class="mb-4">
                <label for="work_hours_per_day" class="form-label">
                  Standard Work Hours per Day <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <input type="number"
                         class="form-control @error('work_hours_per_day') is-invalid @enderror"
                         id="work_hours_per_day"
                         name="work_hours_per_day"
                         value="{{ old('work_hours_per_day', $workHoursPerDay) }}"
                         step="0.5"
                         min="0.5"
                         max="24"
                         required>
                  <span class="input-group-text">hours</span>
                </div>
                <div class="form-text">
                  <i class="ti ti-info-circle me-1"></i>
                  Enter the standard number of work hours required per day (e.g., 8)
                </div>
                @error('work_hours_per_day')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Weekend Days Selection -->
              <div class="mb-4">
                <label class="form-label">
                  Official Weekend Days <span class="text-danger">*</span>
                </label>
                <div class="form-text mb-3">
                  <i class="ti ti-info-circle me-1"></i>
                  Select the days that are considered weekends (non-working days)
                </div>

                @php
                  $days = [
                    'sunday' => 'Sunday',
                    'monday' => 'Monday',
                    'tuesday' => 'Tuesday',
                    'wednesday' => 'Wednesday',
                    'thursday' => 'Thursday',
                    'friday' => 'Friday',
                    'saturday' => 'Saturday'
                  ];
                  $selectedWeekendDays = old('weekend_days', $weekendDays);
                @endphp

                <div class="row">
                  @foreach($days as $value => $label)
                    <div class="col-md-6 col-lg-4 mb-2">
                      <div class="form-check">
                        <input class="form-check-input @error('weekend_days') is-invalid @enderror"
                               type="checkbox"
                               name="weekend_days[]"
                               value="{{ $value }}"
                               id="weekend_{{ $value }}"
                               {{ in_array($value, $selectedWeekendDays) ? 'checked' : '' }}>
                        <label class="form-check-label" for="weekend_{{ $value }}">
                          {{ $label }}
                        </label>
                      </div>
                    </div>
                  @endforeach
                </div>
                @error('weekend_days')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>

              <!-- Current Settings Summary -->
              <div class="alert alert-info">
                <h6><i class="ti ti-info-circle me-1"></i>Current Settings Summary</h6>
                <ul class="mb-0">
                  <li><strong>Work Hours per Day:</strong> {{ $workHoursPerDay }} hours</li>
                  <li><strong>Weekend Days:</strong>
                    @if(is_array($weekendDays) && count($weekendDays) > 0)
                      {{ implode(', ', array_map('ucfirst', $weekendDays)) }}
                    @else
                      None configured
                    @endif
                  </li>
                </ul>
              </div>

              <!-- Submit Button -->
              <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                  <i class="ti ti-device-floppy me-1"></i>Save Settings
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
// Form validation enhancement
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const checkboxes = document.querySelectorAll('input[name="weekend_days[]"]');

    form.addEventListener('submit', function(e) {
        const checkedBoxes = document.querySelectorAll('input[name="weekend_days[]"]:checked');

        if (checkedBoxes.length === 0) {
            e.preventDefault();
            alert('Please select at least one weekend day.');
            return false;
        }

        if (checkedBoxes.length === 7) {
            e.preventDefault();
            alert('You cannot select all days as weekends. At least one working day is required.');
            return false;
        }
    });
});
</script>
@endsection
