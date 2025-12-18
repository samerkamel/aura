@extends('layouts/layoutMaster')

@section('title', 'Generate Project Report')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-md-8 mx-auto">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="ti ti-report-analytics me-2"></i>Generate Project Report
          </h5>
          <a href="{{ route('projects.reports.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-arrow-left me-1"></i>Back to Reports
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

          <!-- Reportable Projects Info -->
          <div class="alert alert-info mb-4">
            <h6 class="alert-heading mb-2"><i class="ti ti-info-circle me-1"></i>Projects to be included:</h6>
            @if($reportableProjects->count() > 0)
              <div class="d-flex flex-wrap gap-2">
                @foreach($reportableProjects as $project)
                  <span class="badge bg-label-primary">{{ $project->code }}: {{ $project->name }}</span>
                @endforeach
              </div>
            @else
              <p class="mb-0">No projects are marked for monthly reports. <a href="{{ route('projects.index') }}">Manage projects</a></p>
            @endif
          </div>

          <form action="{{ route('projects.reports.generate') }}" method="POST">
            @csrf

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label" for="start_date">Start Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                       id="start_date" name="start_date"
                       value="{{ old('start_date', now()->subMonth()->startOfMonth()->format('Y-m-d')) }}" required>
                @error('start_date')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label" for="end_date">End Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                       id="end_date" name="end_date"
                       value="{{ old('end_date', now()->subMonth()->endOfMonth()->format('Y-m-d')) }}" required>
                @error('end_date')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="alert alert-secondary mb-4">
              <h6 class="alert-heading mb-2"><i class="ti ti-calendar me-1"></i>Quick Date Ranges</h6>
              <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary quick-date"
                        data-start="{{ now()->subMonth()->startOfMonth()->format('Y-m-d') }}"
                        data-end="{{ now()->subMonth()->endOfMonth()->format('Y-m-d') }}">
                  Last Month
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary quick-date"
                        data-start="{{ now()->startOfMonth()->format('Y-m-d') }}"
                        data-end="{{ now()->format('Y-m-d') }}">
                  This Month (to date)
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary quick-date"
                        data-start="{{ now()->subMonth()->day(26)->format('Y-m-d') }}"
                        data-end="{{ now()->day(25)->format('Y-m-d') }}">
                  Payroll Period (26th-25th)
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary quick-date"
                        data-start="{{ now()->subMonths(2)->day(26)->format('Y-m-d') }}"
                        data-end="{{ now()->subMonth()->day(25)->format('Y-m-d') }}">
                  Previous Payroll Period
                </button>
              </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-end gap-2">
              <a href="{{ route('projects.reports.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-x me-1"></i>Cancel
              </a>
              <button type="submit" class="btn btn-primary" @if($reportableProjects->count() == 0) disabled @endif>
                <i class="ti ti-report-analytics me-1"></i>Generate Report Preview
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
document.querySelectorAll('.quick-date').forEach(function(btn) {
  btn.addEventListener('click', function() {
    document.getElementById('start_date').value = this.dataset.start;
    document.getElementById('end_date').value = this.dataset.end;
  });
});
</script>
@endsection
