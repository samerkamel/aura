@extends('layouts/layoutMaster')

@section('title', 'Payroll Settings')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="ti ti-settings me-2"></i>Payroll Settings
          </h5>
          <small class="text-muted">
            Configure calculation weights
          </small>
        </div>
        <div class="card-body">
          @if (session('success'))
            <div class="alert alert-success alert-dismissible" role="alert">
              {{ session('success') }}
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @endif

          @if (session('error'))
            <div class="alert alert-danger alert-dismissible" role="alert">
              {{ session('error') }}
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @endif

          @if ($errors->any())
            <div class="alert alert-danger">
              <h6><i class="ti ti-alert-circle me-2"></i>Validation Errors</h6>
              <ul class="mb-0">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <form action="{{ route('payroll.settings.store') }}" method="POST" id="payrollSettingsForm">
            @csrf

            <div class="row">
              <div class="col-md-12">
                <div class="alert alert-info">
                  <i class="ti ti-info-circle me-2"></i>
                  Configure how much each component contributes to the final payroll calculation. The total must equal exactly 100%.
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-floating form-floating-outline mb-4">
                  <input
                    type="number"
                    class="form-control @error('attendance_weight') is-invalid @enderror"
                    id="attendance_weight"
                    name="attendance_weight"
                    value="{{ old('attendance_weight', $attendanceWeight) }}"
                    min="0"
                    max="100"
                    step="0.01"
                    placeholder="Enter attendance weight"
                    required
                  >
                  <label for="attendance_weight">Attendance Weight (%)</label>
                  @error('attendance_weight')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-floating form-floating-outline mb-4">
                  <input
                    type="number"
                    class="form-control @error('billable_hours_weight') is-invalid @enderror"
                    id="billable_hours_weight"
                    name="billable_hours_weight"
                    value="{{ old('billable_hours_weight', $billableHoursWeight) }}"
                    min="0"
                    max="100"
                    step="0.01"
                    placeholder="Enter billable hours weight"
                    required
                  >
                  <label for="billable_hours_weight">Billable Hours Weight (%)</label>
                  @error('billable_hours_weight')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="alert alert-secondary d-flex justify-content-between align-items-center">
                  <span>
                    <i class="ti ti-calculator me-2"></i>Total Weight:
                  </span>
                  <span id="totalWeight" class="fw-bold">{{ $attendanceWeight + $billableHoursWeight }}%</span>
                </div>
                <div id="weightValidationMessage" class="alert alert-warning d-none">
                  <i class="ti ti-alert-triangle me-2"></i>
                  The total weight must equal exactly 100%.
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <button type="submit" class="btn btn-primary me-2" id="saveButton">
                  <i class="ti ti-device-floppy me-2"></i>Save Settings
                </button>
                <a href="{{ route('payroll.index') }}" class="btn btn-outline-secondary">
                  <i class="ti ti-arrow-left me-2"></i>Back to Payroll
                </a>
              </div>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const attendanceWeightInput = document.getElementById('attendance_weight');
    const billableHoursWeightInput = document.getElementById('billable_hours_weight');
    const totalWeightDisplay = document.getElementById('totalWeight');
    const saveButton = document.getElementById('saveButton');
    const validationMessage = document.getElementById('weightValidationMessage');

    function updateTotalAndValidation() {
        const attendanceWeight = parseFloat(attendanceWeightInput.value) || 0;
        const billableHoursWeight = parseFloat(billableHoursWeightInput.value) || 0;
        const total = attendanceWeight + billableHoursWeight;

        // Update total display
        totalWeightDisplay.textContent = total.toFixed(2) + '%';

        // Check if total equals 100
        const isValid = Math.abs(total - 100) < 0.01; // Allow for small floating-point errors

        if (isValid) {
            saveButton.disabled = false;
            saveButton.classList.remove('btn-secondary');
            saveButton.classList.add('btn-primary');
            validationMessage.classList.add('d-none');
            totalWeightDisplay.classList.remove('text-danger');
            totalWeightDisplay.classList.add('text-success');
        } else {
            saveButton.disabled = true;
            saveButton.classList.remove('btn-primary');
            saveButton.classList.add('btn-secondary');
            validationMessage.classList.remove('d-none');
            totalWeightDisplay.classList.remove('text-success');
            totalWeightDisplay.classList.add('text-danger');
        }
    }

    // Add event listeners
    attendanceWeightInput.addEventListener('input', updateTotalAndValidation);
    attendanceWeightInput.addEventListener('change', updateTotalAndValidation);
    billableHoursWeightInput.addEventListener('input', updateTotalAndValidation);
    billableHoursWeightInput.addEventListener('change', updateTotalAndValidation);

    // Initial validation
    updateTotalAndValidation();

    // Form submission prevention if invalid
    document.getElementById('payrollSettingsForm').addEventListener('submit', function(e) {
        const attendanceWeight = parseFloat(attendanceWeightInput.value) || 0;
        const billableHoursWeight = parseFloat(billableHoursWeightInput.value) || 0;
        const total = attendanceWeight + billableHoursWeight;

        if (Math.abs(total - 100) >= 0.01) {
            e.preventDefault();
            alert('The total weight must equal exactly 100% before saving.');
            return false;
        }
    });
});
</script>
@endsection
