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
              <label class="form-label" for="email">Email Address <span class="text-danger">*</span></label>
              <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                     value="{{ old('email') }}" placeholder="Enter email address" required>
              @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="position">Position</label>
              <input type="text" class="form-control @error('position') is-invalid @enderror" id="position" name="position"
                     value="{{ old('position') }}" placeholder="Enter position">
              @error('position')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label" for="start_date">Start Date</label>
              <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date"
                     value="{{ old('start_date') }}">
              @error('start_date')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-12 mb-3">
              <label class="form-label" for="base_salary">Base Salary <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" step="0.01" min="0" class="form-control @error('base_salary') is-invalid @enderror"
                       id="base_salary" name="base_salary" value="{{ old('base_salary') }}"
                       placeholder="Enter base salary" required>
              </div>
              @error('base_salary')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <!-- Contact Information -->
          <h6 class="mt-4 mb-3">Contact Information</h6>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="contact_info_phone">Phone Number</label>
              <input type="text" class="form-control @error('contact_info.phone') is-invalid @enderror"
                     id="contact_info_phone" name="contact_info[phone]"
                     value="{{ old('contact_info.phone') }}" placeholder="Enter phone number">
              @error('contact_info.phone')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label" for="contact_info_address">Address</label>
              <textarea class="form-control @error('contact_info.address') is-invalid @enderror"
                        id="contact_info_address" name="contact_info[address]" rows="3"
                        placeholder="Enter address">{{ old('contact_info.address') }}</textarea>
              @error('contact_info.address')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <!-- Bank Information -->
          <h6 class="mt-4 mb-3">Bank Information</h6>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="bank_info_bank_name">Bank Name</label>
              <input type="text" class="form-control @error('bank_info.bank_name') is-invalid @enderror"
                     id="bank_info_bank_name" name="bank_info[bank_name]"
                     value="{{ old('bank_info.bank_name') }}" placeholder="Enter bank name">
              @error('bank_info.bank_name')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label" for="bank_info_account_number">Account Number</label>
              <input type="text" class="form-control @error('bank_info.account_number') is-invalid @enderror"
                     id="bank_info_account_number" name="bank_info[account_number]"
                     value="{{ old('bank_info.account_number') }}" placeholder="Enter account number">
              @error('bank_info.account_number')
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
