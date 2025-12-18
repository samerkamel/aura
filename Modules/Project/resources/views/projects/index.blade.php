@extends('layouts/layoutMaster')

@section('title', 'Projects')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="ti ti-folder me-2"></i>Projects
          </h5>
          <div class="d-flex gap-2">
            <form action="{{ route('projects.sync-jira') }}" method="POST" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-outline-primary btn-sm">
                <i class="ti ti-refresh me-1"></i>Sync from Jira
              </button>
            </form>
            <a href="{{ route('projects.create') }}" class="btn btn-primary btn-sm">
              <i class="ti ti-plus me-1"></i>Add Project
            </a>
          </div>
        </div>
        <div class="card-body">
          @if (session('success'))
            <div class="alert alert-success alert-dismissible" role="alert">
              {{ session('success') }}
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @endif

          @if (session('error'))
            <div class="alert alert-danger alert-dismissible" role="alert">
              {{ session('error') }}
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @endif

          <!-- Filters -->
          <div class="card mb-4">
            <div class="card-body">
              <form action="{{ route('projects.index') }}" method="GET" class="row g-3">
                <div class="col-md-2">
                  <label class="form-label">Search</label>
                  <input type="text" class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name or code...">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Customer</label>
                  <select class="form-select" name="customer_id">
                    <option value="">All Customers</option>
                    @foreach($customers as $customer)
                      <option value="{{ $customer->id }}" {{ ($filters['customer_id'] ?? '') == $customer->id ? 'selected' : '' }}>
                        {{ $customer->display_name }}
                      </option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label">Status</label>
                  <select class="form-select" name="status">
                    <option value="">All</option>
                    <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label">Monthly Report</label>
                  <select class="form-select" name="needs_report">
                    <option value="">All</option>
                    <option value="1" {{ ($filters['needs_report'] ?? '') === '1' ? 'selected' : '' }}>Needs Report</option>
                    <option value="0" {{ ($filters['needs_report'] ?? '') === '0' ? 'selected' : '' }}>No Report</option>
                  </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                  <button type="submit" class="btn btn-primary me-2">
                    <i class="ti ti-filter me-1"></i>Filter
                  </button>
                  <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-x me-1"></i>Clear
                  </a>
                </div>
              </form>
            </div>
          </div>

          <!-- Projects Table -->
          <div class="table-responsive">
            <table class="table table-bordered table-hover">
              <thead class="table-light">
                <tr>
                  <th>Code</th>
                  <th>Name</th>
                  <th>Customer</th>
                  <th>Description</th>
                  <th class="text-center">Monthly Report</th>
                  <th class="text-center">Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($projects as $project)
                  <tr>
                    <td>
                      <span class="badge bg-label-primary">{{ $project->code }}</span>
                    </td>
                    <td>
                      <a href="{{ route('projects.show', $project) }}" class="fw-semibold text-body">
                        {{ $project->name }}
                      </a>
                      @if($project->jira_project_id)
                        <br><small class="text-muted"><i class="ti ti-brand-jira"></i> Jira ID: {{ $project->jira_project_id }}</small>
                      @endif
                    </td>
                    <td>
                      @if($project->customer)
                        <a href="{{ route('administration.customers.show', $project->customer) }}" class="text-primary">
                          {{ $project->customer->display_name }}
                        </a>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                    <td>{{ \Illuminate\Support\Str::limit($project->description, 50) ?? '-' }}</td>
                    <td class="text-center">
                      <form action="{{ route('projects.toggle-monthly-report', $project) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm {{ $project->needs_monthly_report ? 'btn-success' : 'btn-outline-secondary' }}">
                          @if($project->needs_monthly_report)
                            <i class="ti ti-check me-1"></i>Yes
                          @else
                            <i class="ti ti-x me-1"></i>No
                          @endif
                        </button>
                      </form>
                    </td>
                    <td class="text-center">
                      @if($project->is_active)
                        <span class="badge bg-success">Active</span>
                      @else
                        <span class="badge bg-secondary">Inactive</span>
                      @endif
                    </td>
                    <td>
                      <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                          <i class="ti ti-dots-vertical"></i>
                        </button>
                        <div class="dropdown-menu">
                          <a class="dropdown-item" href="{{ route('projects.show', $project) }}">
                            <i class="ti ti-eye me-1"></i> View
                          </a>
                          <a class="dropdown-item" href="{{ route('projects.edit', $project) }}">
                            <i class="ti ti-pencil me-1"></i> Edit
                          </a>
                          <div class="dropdown-divider"></div>
                          <form action="{{ route('projects.destroy', $project) }}" method="POST" class="d-inline"
                                onsubmit="return confirm('Are you sure you want to delete this project?');">
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
                      <i class="ti ti-folder-off me-2"></i>No projects found.
                      <a href="{{ route('projects.create') }}">Create one</a> or
                      <form action="{{ route('projects.sync-jira') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-link p-0">sync from Jira</button>
                      </form>.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          @if($projects->hasPages())
            <div class="d-flex justify-content-center mt-4">
              {{ $projects->links() }}
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
