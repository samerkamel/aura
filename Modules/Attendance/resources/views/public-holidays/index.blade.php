@extends('layouts/layoutMaster')

@section('title', 'Public Holidays')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/flatpickr/flatpickr.js'])
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Flatpickr for date inputs
    const singleDateInput = document.getElementById('holiday-date');
    const startDateInput = document.getElementById('holiday-start-date');
    const endDateInput = document.getElementById('holiday-end-date');

    // Single date picker
    if (singleDateInput) {
        flatpickr(singleDateInput, {
            dateFormat: 'Y-m-d',
            defaultDate: new Date()
        });
    }

    // Start date picker
    if (startDateInput) {
        flatpickr(startDateInput, {
            dateFormat: 'Y-m-d',
            defaultDate: new Date(),
            onChange: function(selectedDates, dateStr, instance) {
                // Update end date minimum to selected start date
                if (endDateInput && selectedDates[0]) {
                    const endDateFlatpickr = endDateInput._flatpickr;
                    if (endDateFlatpickr) {
                        endDateFlatpickr.set('minDate', selectedDates[0]);
                    }
                }
            }
        });
    }

    // End date picker
    if (endDateInput) {
        flatpickr(endDateInput, {
            dateFormat: 'Y-m-d',
            defaultDate: new Date()
        });
    }

    // Handle date type toggle
    const dateTypeToggle = document.getElementById('date-type-toggle');
    const singleDateSection = document.getElementById('single-date-section');
    const dateRangeSection = document.getElementById('date-range-section');
    const isDateRangeInput = document.getElementById('is-date-range');
    const addTextSuffix = document.getElementById('add-text-suffix');

    if (dateTypeToggle && singleDateSection && dateRangeSection && isDateRangeInput) {
        dateTypeToggle.addEventListener('change', function() {
            const isRange = this.checked;
            isDateRangeInput.value = isRange ? '1' : '0';

            if (isRange) {
                singleDateSection.style.display = 'none';
                dateRangeSection.style.display = 'block';
                singleDateInput.removeAttribute('required');
                startDateInput.setAttribute('required', 'required');
                endDateInput.setAttribute('required', 'required');
                if (addTextSuffix) addTextSuffix.textContent = ' Range';
            } else {
                singleDateSection.style.display = 'block';
                dateRangeSection.style.display = 'none';
                singleDateInput.setAttribute('required', 'required');
                startDateInput.removeAttribute('required');
                endDateInput.removeAttribute('required');
                if (addTextSuffix) addTextSuffix.textContent = '';
            }
        });

        // Set initial state based on old input
        if (dateTypeToggle.checked) {
            dateTypeToggle.dispatchEvent(new Event('change'));
        }
    }
});
</script>
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Public Holidays Header Card -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <i class="ti ti-calendar-event me-2 text-primary" style="font-size: 1.5rem;"></i>
          <div>
            <h5 class="mb-0">Public Holidays</h5>
            <small class="text-muted">Manage public holidays for attendance calculations</small>
          </div>
        </div>
        <div class="d-flex gap-2">
          <!-- Year Filter -->
          <select class="form-select form-select-sm" id="year-filter" onchange="filterByYear(this.value)" style="width: auto;">
            @for($i = now()->year - 2; $i <= now()->year + 2; $i++)
              <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>{{ $i }}</option>
            @endfor
          </select>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHolidayModal">
            <i class="ti ti-plus me-1"></i>Add Holiday
          </button>
        </div>
      </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ti ti-check me-1"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ti ti-alert-circle me-1"></i>
        <ul class="mb-0">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <!-- Public Holidays List -->
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ti ti-calendar-stats me-2"></i>Holidays for {{ $year }}
        </h6>
      </div>
      <div class="card-body">
        @if($holidays->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Holiday Name</th>
                  <th>Date</th>
                  <th>Day of Week</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($holidays as $holiday)
                  <tr>
                    <td>
                      <div class="d-flex align-items-center">
                        <i class="ti ti-calendar-event me-2 text-primary"></i>
                        <span class="fw-medium">{{ $holiday->name }}</span>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-label-info">{{ $holiday->date->format('M d, Y') }}</span>
                    </td>
                    <td>
                      <span class="text-muted">{{ $holiday->date->format('l') }}</span>
                    </td>
                    <td>
                      <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                          <i class="ti ti-dots-vertical"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                          <button type="button" class="dropdown-item" onclick="openEditModal({{ $holiday->id }}, '{{ addslashes($holiday->name) }}', '{{ $holiday->date->format('Y-m-d') }}')">
                            <i class="ti ti-edit me-1"></i>Edit
                          </button>
                          <form action="{{ route('attendance.public-holidays.destroy', $holiday) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to delete this holiday?')">
                              <i class="ti ti-trash me-1"></i>Delete
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
            <div class="avatar avatar-xl mx-auto mb-3">
              <div class="avatar-initial bg-label-secondary rounded-circle">
                <i class="ti ti-calendar-off" style="font-size: 2rem;"></i>
              </div>
            </div>
            <h6 class="mb-1">No public holidays found</h6>
            <p class="text-muted mb-3">No public holidays have been configured for {{ $year }}</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHolidayModal">
              <i class="ti ti-plus me-1"></i>Add First Holiday
            </button>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Add Holiday Modal -->
<div class="modal fade" id="addHolidayModal" tabindex="-1" aria-labelledby="addHolidayModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addHolidayModalLabel">
          <i class="ti ti-calendar-plus me-2"></i>Add Public Holiday
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('attendance.public-holidays.store') }}" method="POST">
        @csrf
        <input type="hidden" id="is-date-range" name="is_date_range" value="0">
        <div class="modal-body">
          <div class="mb-3">
            <label for="holiday-name" class="form-label">Holiday Name</label>
            <input type="text" class="form-control" id="holiday-name" name="name" placeholder="e.g., New Year's Day" value="{{ old('name') }}" required>
            <div class="form-text">Enter a descriptive name for the holiday</div>
          </div>

          <!-- Date Type Toggle -->
          <div class="mb-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="date-type-toggle" {{ old('is_date_range') ? 'checked' : '' }}>
              <label class="form-check-label" for="date-type-toggle">
                <strong>Date Range</strong>
                <small class="text-muted d-block">Toggle to add holidays for multiple consecutive days</small>
              </label>
            </div>
          </div>

          <!-- Single Date Section -->
          <div id="single-date-section" style="{{ old('is_date_range') ? 'display: none;' : 'display: block;' }}">
            <div class="mb-3">
              <label for="holiday-date" class="form-label">Date</label>
              <input type="text" class="form-control" id="holiday-date" name="date" placeholder="Select date" value="{{ old('date') }}" {{ old('is_date_range') ? '' : 'required' }}>
              <div class="form-text">Select the date of the holiday</div>
            </div>
          </div>

          <!-- Date Range Section -->
          <div id="date-range-section" style="{{ old('is_date_range') ? 'display: block;' : 'display: none;' }}">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="holiday-start-date" class="form-label">Start Date</label>
                  <input type="text" class="form-control" id="holiday-start-date" name="start_date" placeholder="Select start date" value="{{ old('start_date') }}" {{ old('is_date_range') ? 'required' : '' }}>
                  <div class="form-text">First day of holiday period</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="holiday-end-date" class="form-label">End Date</label>
                  <input type="text" class="form-control" id="holiday-end-date" name="end_date" placeholder="Select end date" value="{{ old('end_date') }}" {{ old('is_date_range') ? 'required' : '' }}>
                  <div class="form-text">Last day of holiday period</div>
                </div>
              </div>
            </div>
            <div class="alert alert-info">
              <i class="ti ti-info-circle me-1"></i>
              <small>A separate holiday entry will be created for each day in the selected range.</small>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-check me-1"></i>Add Holiday<span id="add-text-suffix"></span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Holiday Modal -->
<div class="modal fade" id="editHolidayModal" tabindex="-1" aria-labelledby="editHolidayModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editHolidayModalLabel">
          <i class="ti ti-calendar-event me-2"></i>Edit Public Holiday
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="editHolidayForm" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body">
          @if($errors->has('edit_date'))
            <div class="alert alert-danger">
              {{ $errors->first('edit_date') }}
            </div>
          @endif
          <div class="mb-3">
            <label for="edit-holiday-name" class="form-label">Holiday Name</label>
            <input type="text" class="form-control" id="edit-holiday-name" name="name" placeholder="e.g., New Year's Day" required>
            <div class="form-text">Enter a descriptive name for the holiday</div>
          </div>
          <div class="mb-3">
            <label for="edit-holiday-date" class="form-label">Date</label>
            <input type="text" class="form-control" id="edit-holiday-date" name="date" placeholder="Select date" required>
            <div class="form-text">Select the date of the holiday</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-check me-1"></i>Update Holiday
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function filterByYear(year) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('year', year);
    window.location.href = currentUrl.toString();
}

// Initialize edit date picker
let editDatePicker = null;

document.addEventListener('DOMContentLoaded', function() {
    const editDateInput = document.getElementById('edit-holiday-date');
    if (editDateInput) {
        editDatePicker = flatpickr(editDateInput, {
            dateFormat: 'Y-m-d',
            allowInput: true
        });
    }
});

function openEditModal(id, name, date) {
    // Set form action URL
    const form = document.getElementById('editHolidayForm');
    form.action = '{{ url("attendance/public-holidays") }}/' + id;

    // Set form values
    document.getElementById('edit-holiday-name').value = name;

    // Set date value using flatpickr
    if (editDatePicker) {
        editDatePicker.setDate(date, true);
    } else {
        document.getElementById('edit-holiday-date').value = date;
    }

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('editHolidayModal'));
    modal.show();
}

// Auto-open modal if there are validation errors
@if($errors->any() && !$errors->has('edit_date'))
  document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('addHolidayModal'));
    modal.show();
  });
@endif

@if($errors->has('edit_date'))
  document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('editHolidayModal'));
    modal.show();
  });
@endif
</script>
@endsection
