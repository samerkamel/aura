@extends('layouts/layoutMaster')

@section('title', 'Create Employee')

@section('page-style')
{{-- Page Css files --}}
@endsection

@section('vendor-script')
{{-- vendor files --}}
@endsection

@section('page-script')
{{-- Page js files --}}
@endsection

@section('content')
<div class="row">
  <div class="col-xl">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Create New Employee</h5>
        <small class="text-muted float-end">Employee Information</small>
      </div>
      <div class="card-body">

        @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
        @endif

        <form action="{{ route('hr.employees.store') }}" method="POST">
          @csrf

          <!-- Basic Information -->
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="name">Full Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                     value="{{ old('name') }}" placeholder="Enter full name" required>
              @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label" for="name_ar">Arabic Name</label>
              <input type="text" class="form-control @error('name_ar') is-invalid @enderror" id="name_ar" name="name_ar"
                     value="{{ old('name_ar') }}" placeholder="أدخل الاسم بالعربية" dir="rtl">
              @error('name_ar')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="email">Work Email <span class="text-danger">*</span></label>
              <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                     value="{{ old('email') }}" placeholder="Enter work email address" required>
              @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label" for="personal_email">Personal Email</label>
              <input type="email" class="form-control @error('personal_email') is-invalid @enderror" id="personal_email" name="personal_email"
                     value="{{ old('personal_email') }}" placeholder="Enter personal email address">
              @error('personal_email')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <!-- Identification -->
          <h6 class="mt-4 mb-3"><i class="ti tabler-id me-2"></i>Identification</h6>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label" for="attendance_id">Attendance ID</label>
              <input type="text" class="form-control @error('attendance_id') is-invalid @enderror" id="attendance_id" name="attendance_id"
                     value="{{ old('attendance_id') }}" placeholder="Enter attendance machine ID">
              @error('attendance_id')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="form-text text-muted">ID from attendance machine</small>
            </div>

            <div class="col-md-4 mb-3">
              <label class="form-label" for="national_id">National ID</label>
              <input type="text" class="form-control @error('national_id') is-invalid @enderror" id="national_id" name="national_id"
                     value="{{ old('national_id') }}" placeholder="Enter national ID number">
              @error('national_id')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-4 mb-3">
              <label class="form-label" for="national_insurance_number">National Insurance Number (NIN)</label>
              <input type="text" class="form-control @error('national_insurance_number') is-invalid @enderror" id="national_insurance_number" name="national_insurance_number"
                     value="{{ old('national_insurance_number') }}" placeholder="Enter NIN">
              @error('national_insurance_number')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="jira_account_id">Jira Account ID</label>
              <input type="text" class="form-control @error('jira_account_id') is-invalid @enderror" id="jira_account_id" name="jira_account_id"
                     value="{{ old('jira_account_id') }}" placeholder="Enter Jira account ID">
              @error('jira_account_id')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="form-text text-muted">Used for syncing billable hours from Jira API</small>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label" for="jira_author_name">Jira Author Name</label>
              <input type="text" class="form-control @error('jira_author_name') is-invalid @enderror" id="jira_author_name" name="jira_author_name"
                     value="{{ old('jira_author_name') }}" placeholder="e.g., Esra Abdelmoaty">
              @error('jira_author_name')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="form-text text-muted">Exact name as it appears in Jira worklog CSV exports</small>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="billable_hours_applicable" name="billable_hours_applicable" value="1"
                       {{ old('billable_hours_applicable', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="billable_hours_applicable">Billable Hours Applicable</label>
              </div>
              <small class="form-text text-muted">If unchecked, salary is based on attendance only (billable hours not tracked for this employee)</small>
            </div>
          </div>

          <!-- Position & Employment -->
          <h6 class="mt-4 mb-3"><i class="ti tabler-briefcase me-2"></i>Position & Employment</h6>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label" for="position_id">Position</label>
              <select class="form-select @error('position_id') is-invalid @enderror" id="position_id" name="position_id">
                <option value="">Select Position</option>
                @foreach($positions as $pos)
                  <option value="{{ $pos->id }}" {{ old('position_id') == $pos->id ? 'selected' : '' }}>
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

            <div class="col-md-4 mb-3">
              <label class="form-label" for="team">Team</label>
              <select class="form-select @error('team') is-invalid @enderror" id="team" name="team">
                <option value="">Select Team</option>
                @foreach(\Modules\HR\Models\Employee::TEAMS as $key => $label)
                  <option value="{{ $key }}" {{ old('team') == $key ? 'selected' : '' }}>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
              @error('team')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="form-text text-muted">Team for project reports</small>
            </div>

            <div class="col-md-4 mb-3">
              <label class="form-label" for="start_date">Start Date</label>
              <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date"
                     value="{{ old('start_date') }}">
              @error('start_date')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          @if($canEditSalary)
          <!-- Salary Information (Permission Protected) -->
          <h6 class="mt-4 mb-3"><i class="ti tabler-currency-dollar me-2"></i>Salary Information</h6>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="base_salary">Base Salary (EGP)</label>
              <input type="number" step="0.01" min="0" class="form-control @error('base_salary') is-invalid @enderror"
                     id="base_salary" name="base_salary" value="{{ old('base_salary') }}" placeholder="0.00">
              @error('base_salary')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label" for="hourly_rate">Hourly Rate (EGP/hr)</label>
              <input type="number" step="0.01" min="0" class="form-control @error('hourly_rate') is-invalid @enderror"
                     id="hourly_rate" name="hourly_rate" value="{{ old('hourly_rate') }}" placeholder="0.00">
              @error('hourly_rate')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="form-text text-muted">Default rate for project reports</small>
            </div>
          </div>
          @endif

          <!-- Contact Information -->
          <h6 class="mt-4 mb-3"><i class="ti tabler-phone me-2"></i>Contact Information</h6>
          <div class="row">
            <div class="col-md-3 mb-3">
              <label class="form-label" for="contact_info_mobile_number">Mobile Number</label>
              <input type="tel" class="form-control @error('contact_info.mobile_number') is-invalid @enderror"
                     id="contact_info_mobile_number" name="contact_info[mobile_number]"
                     value="{{ old('contact_info.mobile_number') }}" placeholder="Enter mobile number">
              @error('contact_info.mobile_number')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-3 mb-3">
              <label class="form-label" for="contact_info_secondary_number">Secondary Number</label>
              <input type="tel" class="form-control @error('contact_info.secondary_number') is-invalid @enderror"
                     id="contact_info_secondary_number" name="contact_info[secondary_number]"
                     value="{{ old('contact_info.secondary_number') }}" placeholder="Home or other mobile">
              @error('contact_info.secondary_number')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-3 mb-3">
              <label class="form-label" for="contact_info_current_address">Current Address</label>
              <textarea class="form-control @error('contact_info.current_address') is-invalid @enderror"
                        id="contact_info_current_address" name="contact_info[current_address]" rows="2"
                        placeholder="Enter current address">{{ old('contact_info.current_address') }}</textarea>
              @error('contact_info.current_address')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-3 mb-3">
              <label class="form-label" for="contact_info_permanent_address">Permanent Address</label>
              <textarea class="form-control @error('contact_info.permanent_address') is-invalid @enderror"
                        id="contact_info_permanent_address" name="contact_info[permanent_address]" rows="2"
                        placeholder="Enter permanent address">{{ old('contact_info.permanent_address') }}</textarea>
              @error('contact_info.permanent_address')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <!-- Bank Information -->
          <h6 class="mt-4 mb-3"><i class="ti tabler-building-bank me-2"></i>Bank Information</h6>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label" for="bank_info_bank_name">Bank Name</label>
              <input type="text" class="form-control @error('bank_info.bank_name') is-invalid @enderror"
                     id="bank_info_bank_name" name="bank_info[bank_name]"
                     value="{{ old('bank_info.bank_name') }}" placeholder="Enter bank name">
              @error('bank_info.bank_name')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-4 mb-3">
              <label class="form-label" for="bank_info_account_number">Account Number</label>
              <input type="text" class="form-control @error('bank_info.account_number') is-invalid @enderror"
                     id="bank_info_account_number" name="bank_info[account_number]"
                     value="{{ old('bank_info.account_number') }}" placeholder="Enter account number">
              @error('bank_info.account_number')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-3 mb-3">
              <label class="form-label" for="bank_info_account_id">Account ID</label>
              <input type="text" class="form-control @error('bank_info.account_id') is-invalid @enderror"
                     id="bank_info_account_id" name="bank_info[account_id]"
                     value="{{ old('bank_info.account_id') }}" placeholder="Enter Account ID">
              @error('bank_info.account_id')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-3 mb-3">
              <label class="form-label" for="bank_info_iban">IBAN</label>
              <input type="text" class="form-control @error('bank_info.iban') is-invalid @enderror"
                     id="bank_info_iban" name="bank_info[iban]"
                     value="{{ old('bank_info.iban') }}" placeholder="Enter IBAN">
              @error('bank_info.iban')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <!-- Emergency Contact -->
          <h6 class="mt-4 mb-3"><i class="ti tabler-urgent me-2"></i>Emergency Contact</h6>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label" for="emergency_contact_name">Contact Name</label>
              <input type="text" class="form-control @error('emergency_contact.name') is-invalid @enderror"
                     id="emergency_contact_name" name="emergency_contact[name]"
                     value="{{ old('emergency_contact.name') }}" placeholder="Enter emergency contact name">
              @error('emergency_contact.name')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-4 mb-3">
              <label class="form-label" for="emergency_contact_phone">Contact Phone</label>
              <input type="text" class="form-control @error('emergency_contact.phone') is-invalid @enderror"
                     id="emergency_contact_phone" name="emergency_contact[phone]"
                     value="{{ old('emergency_contact.phone') }}" placeholder="Enter emergency contact phone">
              @error('emergency_contact.phone')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-4 mb-3">
              <label class="form-label" for="emergency_contact_relationship">Relationship</label>
              <input type="text" class="form-control @error('emergency_contact.relationship') is-invalid @enderror"
                     id="emergency_contact_relationship" name="emergency_contact[relationship]"
                     value="{{ old('emergency_contact.relationship') }}" placeholder="e.g., Spouse, Parent, Sibling">
              @error('emergency_contact.relationship')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <!-- Form Actions -->
          <div class="mt-4">
            <button type="submit" class="btn btn-primary me-2">Create Employee</button>
            <a href="{{ route('hr.employees.index') }}" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
