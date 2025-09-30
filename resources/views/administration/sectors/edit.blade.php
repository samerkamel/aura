@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Edit Sector - Administration')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom">
        <h5 class="card-title mb-0">
          <i class="ti ti-world me-2"></i>Edit Sector: {{ $sector->name }}
        </h5>
      </div>

      <form action="{{ route('administration.sectors.update', $sector) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="name">Sector Name <span class="text-danger">*</span></label>
              <input type="text"
                     class="form-control @error('name') is-invalid @enderror"
                     id="name"
                     name="name"
                     value="{{ old('name', $sector->name) }}"
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
                     value="{{ old('code', $sector->code) }}"
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
                        placeholder="Enter sector description">{{ old('description', $sector->description) }}</textarea>
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
                       {{ old('is_active', $sector->is_active) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">
                  Active
                </label>
              </div>
              <small class="text-muted">Active sectors can have business units assigned to them</small>
            </div>

            @if($sector->business_units_count > 0)
              <div class="col-12 mb-3">
                <div class="alert alert-info">
                  <i class="ti ti-info-circle me-2"></i>
                  <strong>Note:</strong> This sector has {{ $sector->business_units_count }} business unit(s) assigned to it.
                  Deactivating this sector may affect business unit operations.
                </div>
              </div>
            @endif
          </div>
        </div>

        <div class="card-footer d-flex justify-content-between">
          <a href="{{ route('administration.sectors.index') }}" class="btn btn-secondary">
            <i class="ti ti-arrow-left me-1"></i>Back to Sectors
          </a>
          <div>
            <a href="{{ route('administration.sectors.show', $sector) }}" class="btn btn-info me-2">
              <i class="ti ti-eye me-1"></i>View Details
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="ti ti-check me-1"></i>Update Sector
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Force uppercase for code field
document.getElementById('code').addEventListener('input', function() {
  this.value = this.value.toUpperCase();
});
</script>
@endsection