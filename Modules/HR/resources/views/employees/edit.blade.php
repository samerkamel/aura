@extends('layouts/layoutMaster')

@section('title', 'Edit Employee - ' . $employee->name)

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Edit Employee</h5>
    <a href="{{ route('hr.employees.index') }}" class="btn btn-outline-secondary">
      <i class="ti ti-arrow-left me-1"></i>Back to List
    </a>
  </div>

  <div class="card-body">
    <form action="{{ route('hr.employees.update', $employee) }}" method="POST">
      @csrf
      @method('PUT')

      <div class="row">
        <!-- Personal Information Section -->
        <div class="col-md-6">
          <h6 class="text-muted mb-3">
            <i class="ti ti-id me-2"></i>Personal Information
          </h6>

          <!-- Name Field -->
          <div class="mb-3">
            <label class="form-label" for="name">Full Name <span class="text-danger">*</span></label>
            <input
              type="text"
              class="form-control @error('name') is-invalid @enderror"
              id="name"
              name="name"
              value="{{ old('name', $employee->name) }}"
              placeholder="Enter full name"
              required>
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Email Field -->
          <div class="mb-3">
            <label class="form-label" for="email">Email Address <span class="text-danger">*</span></label>
            <input
              type="email"
              class="form-control @error('email') is-invalid @enderror"
              id="email"
              name="email"
              value="{{ old('email', $employee->email) }}"
              placeholder="Enter email address"
              required>
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Position Field -->
          <div class="mb-3">
            <label class="form-label" for="position">Position</label>
            <input
              type="text"
              class="form-control @error('position') is-invalid @enderror"
              id="position"
              name="position"
              value="{{ old('position', $employee->position) }}"
              placeholder="Enter job position">
            @error('position')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Start Date Field -->
          <div class="mb-3">
            <label class="form-label" for="start_date">Start Date</label>
            <input
              type="date"
              class="form-control @error('start_date') is-invalid @enderror"
              id="start_date"
              name="start_date"
              value="{{ old('start_date', $employee->start_date?->format('Y-m-d')) }}">
            @error('start_date')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Base Salary Field -->
          <div class="mb-3">
            <label class="form-label" for="base_salary">Base Salary <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input
                type="number"
                class="form-control @error('base_salary') is-invalid @enderror"
                id="base_salary"
                name="base_salary"
                value="{{ old('base_salary', $employee->base_salary) }}"
                placeholder="0.00"
                step="0.01"
                min="0"
                required>
              @error('base_salary')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <!-- Reason for Salary Change Field -->
          <div class="mb-3">
            <label class="form-label" for="salary_change_reason">Reason for Salary Change</label>
            <input
              type="text"
              class="form-control @error('salary_change_reason') is-invalid @enderror"
              id="salary_change_reason"
              name="salary_change_reason"
              value="{{ old('salary_change_reason') }}"
              placeholder="e.g., Annual Increase, Promotion, Performance Review">
            <div class="form-text">Optional. Only required if changing the base salary.</div>
            @error('salary_change_reason')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>

        <!-- Contact & Bank Information Section -->
        <div class="col-md-6">
          <!-- Contact Information -->
          <h6 class="text-muted mb-3">
            <i class="ti ti-phone me-2"></i>Contact Information
          </h6>

          <!-- Phone Field -->
          <div class="mb-3">
            <label class="form-label" for="contact_info_phone">Phone Number</label>
            <input
              type="tel"
              class="form-control @error('contact_info.phone') is-invalid @enderror"
              id="contact_info_phone"
              name="contact_info[phone]"
              value="{{ old('contact_info.phone', $employee->contact_info['phone'] ?? '') }}"
              placeholder="Enter phone number">
            @error('contact_info.phone')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Address Field -->
          <div class="mb-3">
            <label class="form-label" for="contact_info_address">Address</label>
            <textarea
              class="form-control @error('contact_info.address') is-invalid @enderror"
              id="contact_info_address"
              name="contact_info[address]"
              rows="3"
              placeholder="Enter full address">{{ old('contact_info.address', $employee->contact_info['address'] ?? '') }}</textarea>
            @error('contact_info.address')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Bank Information -->
          <h6 class="text-muted mb-3 mt-4">
            <i class="ti ti-building-bank me-2"></i>Bank Information
          </h6>

          <!-- Bank Name Field -->
          <div class="mb-3">
            <label class="form-label" for="bank_info_bank_name">Bank Name</label>
            <input
              type="text"
              class="form-control @error('bank_info.bank_name') is-invalid @enderror"
              id="bank_info_bank_name"
              name="bank_info[bank_name]"
              value="{{ old('bank_info.bank_name', $employee->bank_info['bank_name'] ?? '') }}"
              placeholder="Enter bank name">
            @error('bank_info.bank_name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Account Number Field -->
          <div class="mb-3">
            <label class="form-label" for="bank_info_account_number">Account Number</label>
            <input
              type="text"
              class="form-control @error('bank_info.account_number') is-invalid @enderror"
              id="bank_info_account_number"
              name="bank_info[account_number]"
              value="{{ old('bank_info.account_number', $employee->bank_info['account_number'] ?? '') }}"
              placeholder="Enter account number">
            @error('bank_info.account_number')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text">
              <i class="ti ti-shield-lock me-1"></i>Bank information is encrypted for security
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="row mt-4">
        <div class="col-12">
          <div class="d-flex justify-content-between">
            <div>
              <button type="submit" class="btn btn-primary me-2">
                <i class="ti ti-device-floppy me-1"></i>Update Employee
              </button>
              <a href="{{ route('hr.employees.show', $employee) }}" class="btn btn-outline-secondary">
                <i class="ti ti-x me-1"></i>Cancel
              </a>
            </div>
            <div>
              <!-- Delete form will be moved outside the update form -->
              <button type="button" class="btn btn-outline-danger" onclick="confirmDelete()">
                <i class="ti ti-trash me-1"></i>Delete Employee
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

    <!-- Delete form - moved outside the update form -->
    <form id="deleteForm" action="{{ route('hr.employees.destroy', $employee) }}" method="POST" style="display: none;">
      @csrf
      @method('DELETE')
    </form>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Auto-format salary input
  const salaryInput = document.getElementById('base_salary');
  if (salaryInput) {
    salaryInput.addEventListener('blur', function() {
      if (this.value) {
        const value = parseFloat(this.value);
        if (!isNaN(value)) {
          this.value = value.toFixed(2);
        }
      }
    });
  }
});

function confirmDelete() {
  if (confirm('Are you sure you want to delete this employee? This action cannot be undone.')) {
    document.getElementById('deleteForm').submit();
  }
}
</script>
@endsection
