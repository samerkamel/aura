@extends('layouts/layoutMaster')

@section('title', 'Edit ' . $businessUnit->name . ' - Business Unit')

@section('vendor-style')
@vite('resources/assets/vendor/libs/bs-stepper/bs-stepper.scss')
@vite('resources/assets/vendor/libs/bootstrap-select/bootstrap-select.scss')
@vite('resources/assets/vendor/libs/select2/select2.scss')
@endsection

@section('page-style')
@vite('resources/assets/vendor/scss/pages/page-icons.scss')
@endsection

@section('vendor-script')
@vite('resources/assets/vendor/libs/bs-stepper/bs-stepper.js')
@vite('resources/assets/vendor/libs/bootstrap-select/bootstrap-select.js')
@vite('resources/assets/vendor/libs/select2/select2.js')
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Form Header -->
    <div class="card mb-6">
      <div class="card-header">
        <h4 class="card-title mb-0">
          <i class="ti ti-edit me-2"></i>Edit Business Unit
        </h4>
        <p class="card-text">Update business unit information and settings</p>
      </div>
    </div>

    <!-- Edit Form -->
    <div class="card">
      <div class="card-body">
        <form method="POST" action="{{ route('administration.business-units.update', $businessUnit) }}">
          @csrf
          @method('PUT')
          <div class="row">
            <!-- Basic Information -->
            <div class="col-12 col-lg-8">
              <div class="card mb-6">
                <div class="card-header">
                  <h5 class="card-title mb-0">Basic Information</h5>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-12 col-md-6 mb-6">
                      <label for="name" class="form-label">Business Unit Name <span class="text-danger">*</span></label>
                      <input type="text" class="form-control @error('name') is-invalid @enderror"
                             id="name" name="name" value="{{ old('name', $businessUnit->name) }}"
                             placeholder="Enter business unit name" required>
                      @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>

                    <div class="col-12 col-md-6 mb-6">
                      <label for="code" class="form-label">Business Unit Code <span class="text-danger">*</span></label>
                      <input type="text" class="form-control @error('code') is-invalid @enderror"
                             id="code" name="code" value="{{ old('code', $businessUnit->code) }}"
                             placeholder="BU001" required maxlength="10" style="text-transform: uppercase;">
                      @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                      <small class="form-text text-muted">Unique identifier for the business unit (max 10 characters)</small>
                    </div>

                    <div class="col-12 mb-6">
                      <label for="description" class="form-label">Description</label>
                      <textarea class="form-control @error('description') is-invalid @enderror"
                                id="description" name="description" rows="4"
                                placeholder="Optional description of the business unit's purpose or focus">{{ old('description', $businessUnit->description) }}</textarea>
                      @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Settings -->
            <div class="col-12 col-lg-4">
              <div class="card">
                <div class="card-header">
                  <h5 class="card-title mb-0">Settings</h5>
                </div>
                <div class="card-body">
                  <div class="mb-6">
                    <label for="type" class="form-label">Business Unit Type <span class="text-danger">*</span></label>
                    <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                      <option value="">Select type</option>
                      <option value="business_unit" {{ old('type', $businessUnit->type) == 'business_unit' ? 'selected' : '' }}>
                        Business Unit
                      </option>
                      <option value="head_office" {{ old('type', $businessUnit->type) == 'head_office' ? 'selected' : '' }}>
                        Head Office
                      </option>
                    </select>
                    @error('type')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                      Head Office units can manage company-wide expenses
                    </small>
                  </div>

                  <div class="mb-6">
                    <label for="sector_id" class="form-label">Sector</label>
                    <select class="form-select @error('sector_id') is-invalid @enderror" id="sector_id" name="sector_id">
                      <option value="0" {{ old('sector_id', $businessUnit->sector_id) == '0' ? 'selected' : '' }}>
                        All Sectors (Head Office Access)
                      </option>
                      @foreach($sectors as $sector)
                        <option value="{{ $sector->id }}" {{ old('sector_id', $businessUnit->sector_id) == $sector->id ? 'selected' : '' }}>
                          {{ $sector->name }}
                        </option>
                      @endforeach
                    </select>
                    @error('sector_id')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                      Select 0 for head office units with access to all sectors
                    </small>
                  </div>

                  <div class="mb-6">
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                             {{ old('is_active', $businessUnit->is_active) ? 'checked' : '' }}>
                      <label class="form-check-label" for="is_active">
                        Active Status
                      </label>
                    </div>
                    <small class="form-text text-muted">
                      Inactive business units cannot be selected by users
                    </small>
                  </div>

                  <!-- Current Statistics -->
                  <div class="alert alert-info">
                    <h6 class="alert-heading mb-2">Current Status</h6>
                    <div class="row text-center">
                      <div class="col-6">
                        <small class="d-block text-muted">Users</small>
                        <strong>{{ $businessUnit->users()->count() }}</strong>
                      </div>
                      <div class="col-6">
                        <small class="d-block text-muted">Products</small>
                        <strong>{{ $businessUnit->products()->count() }}</strong>
                      </div>
                    </div>
                    <div class="row text-center mt-2">
                      <div class="col-6">
                        <small class="d-block text-muted">Contracts</small>
                        <strong>{{ $businessUnit->contracts()->count() }}</strong>
                      </div>
                      <div class="col-6">
                        <small class="d-block text-muted">Created</small>
                        <strong>{{ $businessUnit->created_at->format('M Y') }}</strong>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      @if($businessUnit->products()->count() == 0 && $businessUnit->contracts()->count() == 0)
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                          <i class="ti ti-trash me-1"></i>Delete Business Unit
                        </button>
                      @endif
                    </div>
                    <div class="d-flex gap-3">
                      <a href="{{ route('administration.business-units.show', $businessUnit) }}" class="btn btn-outline-secondary">
                        <i class="ti ti-x me-1"></i>Cancel
                      </a>
                      <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>Update Business Unit
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
@if($businessUnit->products()->count() == 0 && $businessUnit->contracts()->count() == 0)
  <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Confirm Deletion</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="text-center">
            <i class="ti ti-alert-triangle ti-lg text-warning mb-3"></i>
            <h6>Are you sure you want to delete this business unit?</h6>
            <p class="text-muted mb-0">
              This action cannot be undone. The business unit "{{ $businessUnit->name }}" will be permanently removed.
            </p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <form method="POST" action="{{ route('administration.business-units.destroy', $businessUnit) }}" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Yes, Delete</button>
          </form>
        </div>
      </div>
    </div>
  </div>
@endif

@if($errors->any())
  <div class="bs-toast toast toast-placement-ex m-2 fade bg-danger show top-0 end-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
    <div class="toast-header">
      <i class="ti ti-x text-danger me-2"></i>
      <div class="me-auto fw-medium">Validation Error!</div>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">
      Please check the form for errors and try again.
    </div>
  </div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Auto-uppercase the code field
  document.getElementById('code').addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
  });
});
</script>
@endsection