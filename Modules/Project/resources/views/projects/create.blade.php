@extends('layouts/layoutMaster')

@section('title', 'Create Project')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="ti ti-folder-plus me-2"></i>Create Project
          </h5>
          <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-arrow-left me-1"></i>Back to Projects
          </a>
        </div>
        <div class="card-body">
          @if ($errors->any())
            <div class="alert alert-danger alert-dismissible" role="alert">
              <ul class="mb-0">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @endif

          <form action="{{ route('projects.store') }}" method="POST">
            @csrf

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label" for="name">Project Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                       id="name" name="name" value="{{ old('name') }}"
                       placeholder="e.g., Visitor Management System" required>
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label" for="code">Project Code <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('code') is-invalid @enderror"
                       id="code" name="code" value="{{ old('code') }}"
                       placeholder="e.g., VIS" maxlength="20" required>
                <div class="form-text">Short code used for Jira issue keys (e.g., VIS-123)</div>
                @error('code')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label" for="customer_id">Customer</label>
                <select class="form-select select2-customer @error('customer_id') is-invalid @enderror"
                        id="customer_id" name="customer_id"
                        data-selected="{{ old('customer_id') }}">
                  <option value="">Search or select customer...</option>
                </select>
                <div class="form-text">Link this project to a customer for billing purposes</div>
                @error('customer_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="description">Description</label>
              <textarea class="form-control @error('description') is-invalid @enderror"
                        id="description" name="description" rows="3"
                        placeholder="Project description...">{{ old('description') }}</textarea>
              @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="needs_monthly_report"
                         name="needs_monthly_report" value="1" {{ old('needs_monthly_report') ? 'checked' : '' }}>
                  <label class="form-check-label" for="needs_monthly_report">
                    Needs Monthly Report
                  </label>
                </div>
                <div class="form-text">Enable this if the project requires monthly billing reports</div>
              </div>

              <div class="col-md-6 mb-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="is_active"
                         name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                  <label class="form-check-label" for="is_active">
                    Active
                  </label>
                </div>
                <div class="form-text">Inactive projects won't appear in reports</div>
              </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-end gap-2">
              <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-x me-1"></i>Cancel
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="ti ti-check me-1"></i>Create Project
              </button>
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
    // Initialize Select2 for customer dropdown
    function initCustomerSelect2() {
        if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
            setTimeout(initCustomerSelect2, 50);
            return;
        }

        const $select = $('.select2-customer');
        const preSelected = $select.data('selected');

        $select.select2({
            placeholder: 'Search or select customer...',
            allowClear: true,
            ajax: {
                url: '{{ route("administration.customers.api.index") }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { search: params.term || '' };
                },
                processResults: function(data) {
                    return {
                        results: data.customers ? data.customers.map(function(customer) {
                            return {
                                id: customer.id,
                                text: customer.text,
                                customerData: customer
                            };
                        }) : []
                    };
                },
                cache: true
            },
            minimumInputLength: 0
        });

        // Pre-select customer if provided
        if (preSelected) {
            $.ajax({
                url: '{{ route("administration.customers.api.index") }}',
                dataType: 'json'
            }).then(function(data) {
                if (data.customers) {
                    const customer = data.customers.find(c => c.id == preSelected);
                    if (customer) {
                        const option = new Option(customer.text, customer.id, true, true);
                        $select.append(option).trigger('change');
                    }
                }
            });
        }
    }

    initCustomerSelect2();
});
</script>
@endsection
