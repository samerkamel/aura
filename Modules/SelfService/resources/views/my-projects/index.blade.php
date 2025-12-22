@extends('layouts/layoutMaster')

@section('title', 'My Projects')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="ti ti-folder me-2"></i>My Projects
          </h5>
          <span class="badge bg-primary">{{ $projects->count() }} projects</span>
        </div>
        <div class="card-body">
          @if($projects->count() > 0)
            <div class="row">
              @foreach($projects as $project)
                <div class="col-lg-4 col-md-6 mb-4">
                  <div class="card h-100 border shadow-none">
                    <div class="card-body">
                      <div class="d-flex align-items-center mb-3">
                        <div class="avatar avatar-md me-3">
                          <span class="avatar-initial rounded bg-label-primary">
                            {{ strtoupper(substr($project->code, 0, 2)) }}
                          </span>
                        </div>
                        <div>
                          <h5 class="mb-0">
                            <a href="{{ route('self-service.projects.show', $project) }}" class="text-body">
                              {{ $project->name }}
                            </a>
                          </h5>
                          <small class="text-muted">{{ $project->code }}</small>
                        </div>
                      </div>

                      @if($project->description)
                        <p class="text-muted small mb-3">
                          {{ \Illuminate\Support\Str::limit($project->description, 100) }}
                        </p>
                      @endif

                      @if($project->customer)
                        <div class="mb-3">
                          <small class="text-muted">
                            <i class="ti ti-building me-1"></i>{{ $project->customer->display_name }}
                          </small>
                        </div>
                      @endif

                      <div class="d-flex justify-content-between align-items-center">
                        <div>
                          @if($project->pivot->role === 'lead')
                            <span class="badge bg-label-warning">
                              <i class="ti ti-star me-1"></i>Project Lead
                            </span>
                          @else
                            <span class="badge bg-label-info">
                              <i class="ti ti-user me-1"></i>Team Member
                            </span>
                          @endif
                        </div>
                        <a href="{{ route('self-service.projects.show', $project) }}" class="btn btn-sm btn-primary">
                          <i class="ti ti-eye me-1"></i>View
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <div class="text-center py-5">
              <i class="ti ti-folder-off display-1 text-muted mb-3"></i>
              <h4 class="text-muted">No Projects Assigned</h4>
              <p class="text-muted">You are not currently assigned to any projects.</p>
              <p class="text-muted small">Projects are assigned when you log work on them or by your manager.</p>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
