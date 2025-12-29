@extends('layouts/layoutMaster')

@section('title', 'Edit Employee - ' . $employee->name)

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Edit Employee</h5>
    <a href="{{ route('hr.employees.index') }}" class="btn btn-outline-secondary">
      <i class="ti tabler-arrow-left me-1"></i>Back to List
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
            <i class="ti tabler-id me-2"></i>Personal Information
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

          <!-- Arabic Name Field -->
          <div class="mb-3">
            <label class="form-label" for="name_ar">Arabic Name</label>
            <input
              type="text"
              class="form-control @error('name_ar') is-invalid @enderror"
              id="name_ar"
              name="name_ar"
              value="{{ old('name_ar', $employee->name_ar) }}"
              placeholder="أدخل الاسم بالعربية"
              dir="rtl">
            @error('name_ar')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Work Email Field -->
          <div class="mb-3">
            <label class="form-label" for="email">Work Email <span class="text-danger">*</span></label>
            <input
              type="email"
              class="form-control @error('email') is-invalid @enderror"
              id="email"
              name="email"
              value="{{ old('email', $employee->email) }}"
              placeholder="Enter work email address"
              required>
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Personal Email Field -->
          <div class="mb-3">
            <label class="form-label" for="personal_email">Personal Email</label>
            <input
              type="email"
              class="form-control @error('personal_email') is-invalid @enderror"
              id="personal_email"
              name="personal_email"
              value="{{ old('personal_email', $employee->personal_email) }}"
              placeholder="Enter personal email address">
            @error('personal_email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Attendance ID Field -->
          <div class="mb-3">
            <label class="form-label" for="attendance_id">Attendance ID</label>
            <input
              type="text"
              class="form-control @error('attendance_id') is-invalid @enderror"
              id="attendance_id"
              name="attendance_id"
              value="{{ old('attendance_id', $employee->attendance_id) }}"
              placeholder="Enter attendance machine ID">
            @error('attendance_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="form-text text-muted">ID used to match attendance records from the attendance machine</small>
          </div>

          <!-- National ID Field -->
          <div class="mb-3">
            <label class="form-label" for="national_id">National ID</label>
            <input
              type="text"
              class="form-control @error('national_id') is-invalid @enderror"
              id="national_id"
              name="national_id"
              value="{{ old('national_id', $employee->national_id) }}"
              placeholder="Enter national ID number">
            @error('national_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- National Insurance Number Field -->
          <div class="mb-3">
            <label class="form-label" for="national_insurance_number">National Insurance Number (NIN)</label>
            <input
              type="text"
              class="form-control @error('national_insurance_number') is-invalid @enderror"
              id="national_insurance_number"
              name="national_insurance_number"
              value="{{ old('national_insurance_number', $employee->national_insurance_number) }}"
              placeholder="Enter NIN">
            @error('national_insurance_number')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Jira Account ID Field -->
          <div class="mb-3">
            <label class="form-label" for="jira_account_id">Jira Account ID</label>
            <input
              type="text"
              class="form-control @error('jira_account_id') is-invalid @enderror"
              id="jira_account_id"
              name="jira_account_id"
              value="{{ old('jira_account_id', $employee->jira_account_id) }}"
              placeholder="Enter Jira account ID">
            @error('jira_account_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="form-text text-muted">Used for syncing billable hours from Jira API</small>
          </div>

          <!-- Jira Author Name Field -->
          <div class="mb-3">
            <label class="form-label" for="jira_author_name">Jira Author Name</label>
            <input
              type="text"
              class="form-control @error('jira_author_name') is-invalid @enderror"
              id="jira_author_name"
              name="jira_author_name"
              value="{{ old('jira_author_name', $employee->jira_author_name) }}"
              placeholder="e.g., Esra Abdelmoaty">
            @error('jira_author_name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="form-text text-muted">Exact name as it appears in Jira worklog CSV exports</small>
          </div>

          <!-- Billable Hours Applicable -->
          <div class="mb-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="billable_hours_applicable" name="billable_hours_applicable" value="1"
                     {{ old('billable_hours_applicable', $employee->billable_hours_applicable) ? 'checked' : '' }}>
              <label class="form-check-label" for="billable_hours_applicable">Billable Hours Applicable</label>
            </div>
            <small class="form-text text-muted">If unchecked, salary is based on attendance only (billable hours not tracked)</small>
          </div>

          <!-- Position Field -->
          <h6 class="text-muted mb-3 mt-4">
            <i class="ti tabler-briefcase me-2"></i>Position & Employment
          </h6>

          <div class="mb-3">
            <label class="form-label" for="position_id">Position</label>
            <select class="form-select @error('position_id') is-invalid @enderror" id="position_id" name="position_id">
              <option value="">Select Position</option>
              @foreach($positions as $pos)
                <option value="{{ $pos->id }}" {{ old('position_id', $employee->position_id) == $pos->id ? 'selected' : '' }}>
                  {{ $pos->full_title }}
                  @if($pos->department)
                    ({{ $pos->department }})
                  @endif
                </option>
              @endforeach
            </select>
            @error('position_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Team Field -->
          <div class="mb-3">
            <label class="form-label" for="team">Team</label>
            <select class="form-select @error('team') is-invalid @enderror" id="team" name="team">
              <option value="">Select Team</option>
              @foreach(\Modules\HR\Models\Employee::TEAMS as $key => $label)
                <option value="{{ $key }}" {{ old('team', $employee->team) == $key ? 'selected' : '' }}>
                  {{ $label }}
                </option>
              @endforeach
            </select>
            @error('team')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="form-text text-muted">Team for project reports (corresponds to income line items)</small>
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

          <!-- Employment Status Field -->
          <div class="mb-3">
            <label class="form-label" for="status">Employment Status</label>
            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" onchange="toggleTerminationDate()">
              <option value="active" {{ old('status', $employee->status) == 'active' ? 'selected' : '' }}>Active</option>
              <option value="resigned" {{ old('status', $employee->status) == 'resigned' ? 'selected' : '' }}>Resigned</option>
              <option value="terminated" {{ old('status', $employee->status) == 'terminated' ? 'selected' : '' }}>Terminated</option>
            </select>
            @error('status')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Termination Date Field (shown when status is not active) -->
          <div class="mb-3" id="termination-date-group" style="{{ old('status', $employee->status) == 'active' ? 'display: none;' : '' }}">
            <label class="form-label" for="termination_date">End of Service Date</label>
            <div class="input-group">
              <input
                type="date"
                class="form-control @error('termination_date') is-invalid @enderror"
                id="termination_date"
                name="termination_date"
                value="{{ old('termination_date', $employee->termination_date?->format('Y-m-d')) }}">
              @if($lastSignInDate)
              <button type="button" class="btn btn-outline-secondary" onclick="setLastSignInDate()" title="Use last sign-in date">
                <i class="ti tabler-calendar-event"></i>
              </button>
              @endif
            </div>
            @error('termination_date')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            @if($lastSignInDate)
            <small class="form-text text-info">
              <i class="ti tabler-info-circle me-1"></i>Last sign-in: <strong>{{ $lastSignInDate->format('M d, Y') }}</strong> ({{ $lastSignInDate->format('l') }})
              <a href="javascript:void(0)" onclick="setLastSignInDate()" class="ms-2">Use this date</a>
            </small>
            @else
            <small class="form-text text-warning">
              <i class="ti tabler-alert-triangle me-1"></i>No attendance records found for this employee
            </small>
            @endif
          </div>

          @if($canEditSalary)
          <!-- Salary Information (Permission Protected) -->
          <h6 class="text-muted mb-3 mt-4">
            <i class="ti tabler-currency-dollar me-2"></i>Salary Information
          </h6>

          <div class="mb-3">
            <label class="form-label" for="base_salary">Base Salary (EGP)</label>
            <input
              type="number"
              step="0.01"
              min="0"
              class="form-control @error('base_salary') is-invalid @enderror"
              id="base_salary"
              name="base_salary"
              value="{{ old('base_salary', $employee->base_salary) }}"
              placeholder="0.00">
            @error('base_salary')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label class="form-label" for="hourly_rate">Hourly Rate (EGP/hr)</label>
            <input
              type="number"
              step="0.01"
              min="0"
              class="form-control @error('hourly_rate') is-invalid @enderror"
              id="hourly_rate"
              name="hourly_rate"
              value="{{ old('hourly_rate', $employee->hourly_rate) }}"
              placeholder="0.00">
            @error('hourly_rate')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="form-text text-muted">Default rate used for project reports and billable hours</small>
          </div>

          <div class="mb-3">
            <label class="form-label" for="salary_change_reason">Salary Change Reason</label>
            <input
              type="text"
              class="form-control @error('salary_change_reason') is-invalid @enderror"
              id="salary_change_reason"
              name="salary_change_reason"
              placeholder="Enter reason for salary change (if any)">
            @error('salary_change_reason')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="form-text text-muted">This will be recorded in the salary history</small>
          </div>
          @endif
        </div>

        <!-- Contact & Bank Information Section -->
        <div class="col-md-6">
          <!-- Contact Information -->
          <h6 class="text-muted mb-3">
            <i class="ti tabler-phone me-2"></i>Contact Information
          </h6>

          <!-- Mobile Number Field -->
          <div class="mb-3">
            <label class="form-label" for="contact_info_mobile_number">Mobile Number</label>
            <input
              type="tel"
              class="form-control @error('contact_info.mobile_number') is-invalid @enderror"
              id="contact_info_mobile_number"
              name="contact_info[mobile_number]"
              value="{{ old('contact_info.mobile_number', $employee->contact_info['mobile_number'] ?? $employee->contact_info['phone'] ?? '') }}"
              placeholder="Enter mobile number">
            @error('contact_info.mobile_number')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Secondary Number Field -->
          <div class="mb-3">
            <label class="form-label" for="contact_info_secondary_number">Secondary Number</label>
            <input
              type="tel"
              class="form-control @error('contact_info.secondary_number') is-invalid @enderror"
              id="contact_info_secondary_number"
              name="contact_info[secondary_number]"
              value="{{ old('contact_info.secondary_number', $employee->contact_info['secondary_number'] ?? '') }}"
              placeholder="Enter home or secondary mobile number">
            @error('contact_info.secondary_number')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Current Address Field -->
          <div class="mb-3">
            <label class="form-label" for="contact_info_current_address">Current Address</label>
            <textarea
              class="form-control @error('contact_info.current_address') is-invalid @enderror"
              id="contact_info_current_address"
              name="contact_info[current_address]"
              rows="2"
              placeholder="Enter current address">{{ old('contact_info.current_address', $employee->contact_info['current_address'] ?? $employee->contact_info['address'] ?? '') }}</textarea>
            @error('contact_info.current_address')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Permanent Address Field -->
          <div class="mb-3">
            <label class="form-label" for="contact_info_permanent_address">Permanent Address</label>
            <textarea
              class="form-control @error('contact_info.permanent_address') is-invalid @enderror"
              id="contact_info_permanent_address"
              name="contact_info[permanent_address]"
              rows="2"
              placeholder="Enter permanent address">{{ old('contact_info.permanent_address', $employee->contact_info['permanent_address'] ?? '') }}</textarea>
            @error('contact_info.permanent_address')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Bank Information -->
          <h6 class="text-muted mb-3 mt-4">
            <i class="ti tabler-building-bank me-2"></i>Bank Information
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
          </div>

          <!-- Account ID Field -->
          <div class="mb-3">
            <label class="form-label" for="bank_info_account_id">Account ID</label>
            <input
              type="text"
              class="form-control @error('bank_info.account_id') is-invalid @enderror"
              id="bank_info_account_id"
              name="bank_info[account_id]"
              value="{{ old('bank_info.account_id', $employee->bank_info['account_id'] ?? '') }}"
              placeholder="Enter Account ID">
            @error('bank_info.account_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- IBAN Field -->
          <div class="mb-3">
            <label class="form-label" for="bank_info_iban">IBAN</label>
            <input
              type="text"
              class="form-control @error('bank_info.iban') is-invalid @enderror"
              id="bank_info_iban"
              name="bank_info[iban]"
              value="{{ old('bank_info.iban', $employee->bank_info['iban'] ?? '') }}"
              placeholder="Enter IBAN">
            @error('bank_info.iban')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text">
              <i class="ti tabler-shield-lock me-1"></i>Bank information is encrypted for security
            </div>
          </div>

          <!-- Emergency Contact -->
          <h6 class="text-muted mb-3 mt-4">
            <i class="ti tabler-urgent me-2"></i>Emergency Contact
          </h6>

          <!-- Emergency Contact Name -->
          <div class="mb-3">
            <label class="form-label" for="emergency_contact_name">Contact Name</label>
            <input
              type="text"
              class="form-control @error('emergency_contact.name') is-invalid @enderror"
              id="emergency_contact_name"
              name="emergency_contact[name]"
              value="{{ old('emergency_contact.name', $employee->emergency_contact['name'] ?? '') }}"
              placeholder="Enter emergency contact name">
            @error('emergency_contact.name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Emergency Contact Phone -->
          <div class="mb-3">
            <label class="form-label" for="emergency_contact_phone">Contact Phone</label>
            <input
              type="tel"
              class="form-control @error('emergency_contact.phone') is-invalid @enderror"
              id="emergency_contact_phone"
              name="emergency_contact[phone]"
              value="{{ old('emergency_contact.phone', $employee->emergency_contact['phone'] ?? '') }}"
              placeholder="Enter emergency contact phone">
            @error('emergency_contact.phone')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Emergency Contact Relationship -->
          <div class="mb-3">
            <label class="form-label" for="emergency_contact_relationship">Relationship</label>
            <input
              type="text"
              class="form-control @error('emergency_contact.relationship') is-invalid @enderror"
              id="emergency_contact_relationship"
              name="emergency_contact[relationship]"
              value="{{ old('emergency_contact.relationship', $employee->emergency_contact['relationship'] ?? '') }}"
              placeholder="e.g., Spouse, Parent, Sibling">
            @error('emergency_contact.relationship')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="row mt-4">
        <div class="col-12">
          <div class="d-flex justify-content-between">
            <div>
              <button type="submit" class="btn btn-primary me-2">
                <i class="ti tabler-device-floppy me-1"></i>Update Employee
              </button>
              <a href="{{ route('hr.employees.show', $employee) }}" class="btn btn-outline-secondary">
                <i class="ti tabler-x me-1"></i>Cancel
              </a>
            </div>
            <div>
              <!-- Delete form will be moved outside the update form -->
              <button type="button" class="btn btn-outline-danger" onclick="confirmDelete()">
                <i class="ti tabler-trash me-1"></i>Delete Employee
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
function confirmDelete() {
  if (confirm('Are you sure you want to delete this employee? This action cannot be undone.')) {
    document.getElementById('deleteForm').submit();
  }
}

function toggleTerminationDate() {
  const status = document.getElementById('status').value;
  const terminationGroup = document.getElementById('termination-date-group');

  if (status === 'active') {
    terminationGroup.style.display = 'none';
    document.getElementById('termination_date').value = '';
  } else {
    terminationGroup.style.display = 'block';
  }
}

function setLastSignInDate() {
  @if($lastSignInDate)
  document.getElementById('termination_date').value = '{{ $lastSignInDate->format("Y-m-d") }}';
  @endif
}
</script>
@endsection
