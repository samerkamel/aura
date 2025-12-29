@extends('layouts/layoutMaster')

@section('title', 'Project Reports')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="ti ti-report me-2"></i>Project Reports
          </h5>
          <a href="{{ route('projects.reports.create') }}" class="btn btn-primary btn-sm">
            <i class="ti ti-plus me-1"></i>Generate New Report
          </a>
        </div>
        <div class="card-body">
          @if (session('success'))
            <div class="alert alert-success alert-dismissible" role="alert">
              {{ session('success') }}
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @endif

          <!-- Filters -->
          <div class="card mb-4">
            <div class="card-body">
              <form action="{{ route('projects.reports.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">From Date</label>
                  <input type="date" class="form-control" name="from" value="{{ $filters['from'] ?? '' }}">
                </div>
                <div class="col-md-4">
                  <label class="form-label">To Date</label>
                  <input type="date" class="form-control" name="to" value="{{ $filters['to'] ?? '' }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                  <button type="submit" class="btn btn-primary me-2">
                    <i class="ti ti-filter me-1"></i>Filter
                  </button>
                  <a href="{{ route('projects.reports.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-x me-1"></i>Clear
                  </a>
                </div>
              </form>
            </div>
          </div>

          <!-- Reports Table -->
          <div class="table-responsive">
            <table class="table table-bordered table-hover">
              <thead class="table-light">
                <tr>
                  <th>Report Name</th>
                  <th>Period</th>
                  <th class="text-end">Total Hours</th>
                  <th class="text-end">Total Amount</th>
                  <th>Created By</th>
                  <th>Created At</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($reports as $report)
                  <tr>
                    <td>
                      <a href="{{ route('projects.reports.show', $report) }}" class="fw-semibold text-primary">
                        {{ $report->name }}
                      </a>
                    </td>
                    <td>
                      {{ $report->start_date->format('M d, Y') }} - {{ $report->end_date->format('M d, Y') }}
                    </td>
                    <td class="text-end">
                      <strong>{{ number_format($report->total_hours, 2) }}</strong>
                    </td>
                    <td class="text-end">
                      <strong>{{ number_format($report->total_amount, 2) }} EGP</strong>
                    </td>
                    <td>{{ $report->createdBy->name ?? 'N/A' }}</td>
                    <td>{{ $report->created_at->format('M d, Y H:i') }}</td>
                    <td>
                      <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                          <i class="ti ti-dots-vertical"></i>
                        </button>
                        <div class="dropdown-menu">
                          <a class="dropdown-item" href="{{ route('projects.reports.show', $report) }}">
                            <i class="ti ti-eye me-1"></i> View
                          </a>
                          <a class="dropdown-item" href="{{ route('projects.reports.export-pdf', $report) }}" target="_blank">
                            <i class="ti ti-file-type-pdf me-1"></i> Export PDF
                          </a>
                          <a class="dropdown-item" href="{{ route('projects.reports.export-excel', $report) }}">
                            <i class="ti ti-file-spreadsheet me-1"></i> Export Excel
                          </a>
                          <div class="dropdown-divider"></div>
                          <form action="{{ route('projects.reports.destroy', $report) }}" method="POST" class="d-inline"
                                onsubmit="return confirm('Are you sure you want to delete this report?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dropdown-item text-danger">
                              <i class="ti ti-trash me-1"></i> Delete
                            </button>
                          </form>
                        </div>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                      <i class="ti ti-report-off me-2"></i>No reports found.
                      <a href="{{ route('projects.reports.create') }}">Generate one</a>
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          @if($reports->hasPages())
            <div class="d-flex justify-content-center mt-4">
              {{ $reports->links() }}
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
