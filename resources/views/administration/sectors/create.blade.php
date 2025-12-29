@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Create Sector - Administration')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom">
        <h5 class="card-title mb-0">
          <i class="ti tabler-world me-2"></i>Create New Sector
        </h5>
      </div>

      <form action="{{ route('administration.sectors.store') }}" method="POST">
        @csrf
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="name">Sector Name <span class="text-danger">*</span></label>
              <input type="text"
                     class="form-control @error('name') is-invalid @enderror"
                     id="name"
                     name="name"
                     value="{{ old('name') }}"
                     placeholder="Enter sector name"
                     required>
              @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label" for="code">Sector Code <span class="text-danger">*</span></label>
              <input type="text"
                     class="form-control @error('code') is-invalid @enderror"
                     id="code"
                     name="code"
                     value="{{ old('code') }}"
                     placeholder="Enter sector code (e.g., TECH)"
                     maxlength="10"
                     style="text-transform: uppercase;"
                     required>
              @error('code')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="text-muted">Unique identifier for the sector (max 10 characters)</small>
            </div>

            <div class="col-12 mb-3">
              <label class="form-label" for="description">Description</label>
              <textarea class="form-control @error('description') is-invalid @enderror"
                        id="description"
                        name="description"
                        rows="4"
                        placeholder="Enter sector description">{{ old('description') }}</textarea>
              @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-12 mb-3">
              <div class="form-check">
                <input class="form-check-input"
                       type="checkbox"
                       id="is_active"
                       name="is_active"
                       value="1"
                       {{ old('is_active', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">
                  Active
                </label>
              </div>
              <small class="text-muted">Active sectors can have business units assigned to them</small>
            </div>
          </div>
        </div>

        <div class="card-footer d-flex justify-content-between">
          <a href="{{ route('administration.sectors.index') }}" class="btn btn-secondary">
            <i class="ti tabler-arrow-left me-1"></i>Back to Sectors
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="ti tabler-check me-1"></i>Create Sector
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Auto-generate code from name
document.getElementById('name').addEventListener('input', function() {
  const name = this.value;
  const codeField = document.getElementById('code');

  // Only auto-generate if code field is empty
  if (!codeField.value) {
    // Take first word, remove spaces, convert to uppercase, limit to 10 chars
    const autoCode = name.split(' ')[0].replace(/[^a-zA-Z]/g, '').toUpperCase().substring(0, 10);
    codeField.value = autoCode;
  }
});

// Force uppercase for code field
document.getElementById('code').addEventListener('input', function() {
  this.value = this.value.toUpperCase();
});
</script>
@endsection