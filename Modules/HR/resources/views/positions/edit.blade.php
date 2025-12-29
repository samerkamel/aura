@extends('layouts/layoutMaster')

@section('title', 'Edit Position')

@section('content')
<div class="row">
  <div class="col-xl-8">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Edit Position</h5>
        <small class="text-muted float-end">{{ $position->title }}</small>
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

        <form action="{{ route('hr.positions.update', $position) }}" method="POST">
          @csrf
          @method('PUT')

          <!-- Basic Information -->
          <h6 class="mb-3"><i class="ti tabler-briefcase me-2"></i>Basic Information</h6>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="title">Position Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title"
                     value="{{ old('title', $position->title) }}" placeholder="e.g., Software Engineer" required>
              @error('title')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label" for="title_ar">Arabic Title</label>
              <input type="text" class="form-control @error('title_ar') is-invalid @enderror" id="title_ar" name="title_ar"
                     value="{{ old('title_ar', $position->title_ar) }}" placeholder="مهندس برمجيات" dir="rtl">
              @error('title_ar')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="department">Department</label>
              <input type="text" class="form-control @error('department') is-invalid @enderror" id="department" name="department"
                     value="{{ old('department', $position->department) }}" placeholder="e.g., Engineering, Marketing, HR">
              @error('department')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label" for="level">Level</label>
              <select class="form-select @error('level') is-invalid @enderror" id="level" name="level">
                <option value="">Select Level</option>
                @foreach($levels as $key => $label)
                  <option value="{{ $key }}" {{ old('level', $position->level) == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select>
              @error('level')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          @if($canEditSalary)
          <!-- Salary Range -->
          <h6 class="mt-4 mb-3"><i class="ti tabler-currency-dollar me-2"></i>Salary Range</h6>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="min_salary">Minimum Salary (EGP)</label>
              <input type="number" step="0.01" min="0" class="form-control @error('min_salary') is-invalid @enderror"
                     id="min_salary" name="min_salary" value="{{ old('min_salary', $position->min_salary) }}" placeholder="0.00">
              @error('min_salary')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label" for="max_salary">Maximum Salary (EGP)</label>
              <input type="number" step="0.01" min="0" class="form-control @error('max_salary') is-invalid @enderror"
                     id="max_salary" name="max_salary" value="{{ old('max_salary', $position->max_salary) }}" placeholder="0.00">
              @error('max_salary')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          @endif

          <!-- Description & Requirements -->
          <h6 class="mt-4 mb-3"><i class="ti tabler-file-description me-2"></i>Description & Requirements</h6>
          <div class="mb-3">
            <label class="form-label" for="description">Job Description</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                      rows="4" placeholder="Enter position description...">{{ old('description', $position->description) }}</textarea>
            @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label class="form-label" for="requirements">Requirements</label>
            <textarea class="form-control @error('requirements') is-invalid @enderror" id="requirements" name="requirements"
                      rows="4" placeholder="Enter position requirements...">{{ old('requirements', $position->requirements) }}</textarea>
            @error('requirements')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <!-- Status -->
          <div class="mb-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                     {{ old('is_active', $position->is_active) ? 'checked' : '' }}>
              <label class="form-check-label" for="is_active">Active Position</label>
            </div>
            <small class="text-muted">Inactive positions cannot be assigned to employees</small>
          </div>

          <!-- Form Actions -->
          <div class="mt-4">
            <button type="submit" class="btn btn-primary me-2">Update Position</button>
            <a href="{{ route('hr.positions.index') }}" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
