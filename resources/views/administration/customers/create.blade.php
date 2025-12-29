@extends('layouts/layoutMaster')

@section('title', 'Add Customer - Administration')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Wait for jQuery to be available (loaded via Vite)
    const initPage = function() {
        if (typeof $ === 'undefined' || typeof jQuery === 'undefined') {
            setTimeout(initPage, 50);
            return;
        }

        // Initialize Select2
        $('.select2').select2();

        // Toggle company fields based on type
        $('#type').on('change', function() {
            const isCompany = $(this).val() === 'company';
            $('#company-fields').toggle(isCompany);
            if (!isCompany) {
                $('#company_name, #tax_id, #website').val('');
            }
        });

        // Trigger initial toggle
        $('#type').trigger('change');
    };
    initPage();
});
</script>
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="ti tabler-user-plus me-2"></i>Add New Customer
        </h5>
        <a href="{{ route('administration.customers.index') }}" class="btn btn-outline-secondary">
          <i class="ti tabler-arrow-left me-1"></i>Back to Customers
        </a>
      </div>

      <div class="card-body">
        <form action="{{ route('administration.customers.store') }}" method="POST">
          @csrf

          <div class="row">
            <!-- Customer Type -->
            <div class="col-md-6 mb-4">
              <label for="type" class="form-label">Customer Type <span class="text-danger">*</span></label>
              <select id="type" name="type" class="form-select select2 @error('type') is-invalid @enderror" required>
                <option value="">Select Type</option>
                <option value="individual" {{ old('type') === 'individual' ? 'selected' : '' }}>Individual</option>
                <option value="company" {{ old('type') === 'company' ? 'selected' : '' }}>Company</option>
              </select>
              @error('type')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Status -->
            <div class="col-md-6 mb-4">
              <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
              <select id="status" name="status" class="form-select select2 @error('status') is-invalid @enderror" required>
                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
              </select>
              @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row">
            <!-- Contact Person Name -->
            <div class="col-md-6 mb-4">
              <label for="name" class="form-label">Contact Person Name <span class="text-danger">*</span></label>
              <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                     value="{{ old('name') }}" placeholder="Enter contact person name" required>
              @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="text-muted">For companies, this is the primary contact person</small>
            </div>

            <!-- Email -->
            <div class="col-md-6 mb-4">
              <label for="email" class="form-label">Email</label>
              <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                     value="{{ old('email') }}" placeholder="Enter email address">
              @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <!-- Company Fields (shown only for company type) -->
          <div id="company-fields" style="display: none;">
            <div class="row">
              <!-- Company Name -->
              <div class="col-md-6 mb-4">
                <label for="company_name" class="form-label">Company Name</label>
                <input type="text" id="company_name" name="company_name" class="form-control @error('company_name') is-invalid @enderror"
                       value="{{ old('company_name') }}" placeholder="Enter company name">
                @error('company_name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Tax ID -->
              <div class="col-md-6 mb-4">
                <label for="tax_id" class="form-label">Tax ID / Registration Number</label>
                <input type="text" id="tax_id" name="tax_id" class="form-control @error('tax_id') is-invalid @enderror"
                       value="{{ old('tax_id') }}" placeholder="Enter tax ID or registration number">
                @error('tax_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="row">
              <!-- Website -->
              <div class="col-md-6 mb-4">
                <label for="website" class="form-label">Website</label>
                <input type="url" id="website" name="website" class="form-control @error('website') is-invalid @enderror"
                       value="{{ old('website') }}" placeholder="https://example.com">
                @error('website')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="row">
            <!-- Phone -->
            <div class="col-md-6 mb-4">
              <label for="phone" class="form-label">Phone Number</label>
              <input type="tel" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror"
                     value="{{ old('phone') }}" placeholder="Enter phone number">
              @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <!-- Address -->
          <div class="mb-4">
            <label for="address" class="form-label">Address</label>
            <textarea id="address" name="address" class="form-control @error('address') is-invalid @enderror"
                      rows="3" placeholder="Enter full address">{{ old('address') }}</textarea>
            @error('address')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Notes -->
          <div class="mb-4">
            <label for="notes" class="form-label">Notes</label>
            <textarea id="notes" name="notes" class="form-control @error('notes') is-invalid @enderror"
                      rows="3" placeholder="Any additional notes or comments">{{ old('notes') }}</textarea>
            @error('notes')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="d-flex justify-content-end gap-3">
            <a href="{{ route('administration.customers.index') }}" class="btn btn-outline-secondary">
              <i class="ti tabler-x me-1"></i>Cancel
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="ti tabler-check me-1"></i>Save Customer
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@if($errors->any())
  <div class="bs-toast toast toast-placement-ex m-2 fade bg-danger show top-0 end-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
    <div class="toast-header">
      <i class="ti tabler-x text-danger me-2"></i>
      <div class="me-auto fw-medium">Validation Error!</div>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">Please check the form for errors and try again.</div>
  </div>
@endif
@endsection