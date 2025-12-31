@extends('layouts/layoutMaster')

@section('title', 'Project Templates')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Page Header -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center">
              <div class="avatar avatar-lg me-4">
                <span class="avatar-initial rounded-circle bg-label-primary">
                  <i class="ti ti-template ti-lg"></i>
                </span>
              </div>
              <div>
                <h4 class="mb-0">Project Templates</h4>
                <p class="text-muted mb-0">Reusable project structures for quick project creation</p>
              </div>
            </div>
            <div class="d-flex gap-2">
              <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Back to Projects
              </a>
              <a href="{{ route('projects.templates.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>Create Template
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  @if (session('success'))
    <div class="alert alert-success alert-dismissible mb-4" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- Stats Cards -->
  <div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <p class="mb-1 text-muted">Total Templates</p>
              <h3 class="mb-0">{{ $templates->count() }}</h3>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="ti ti-template"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <p class="mb-1 text-muted">Active Templates</p>
              <h3 class="mb-0">{{ $templates->where('is_active', true)->count() }}</h3>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="ti ti-check"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <p class="mb-1 text-muted">Categories</p>
              <h3 class="mb-0">{{ $categories->count() }}</h3>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info">
                <i class="ti ti-folder"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <p class="mb-1 text-muted">Total Uses</p>
              <h3 class="mb-0">{{ $templates->sum('usage_count') }}</h3>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="ti ti-repeat"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Category Filter -->
  @if($categories->count() > 0)
  <div class="card mb-4">
    <div class="card-body py-3">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="text-muted me-2">Filter by category:</span>
        <a href="{{ route('projects.templates.index') }}" class="btn btn-sm {{ !request('category') ? 'btn-primary' : 'btn-outline-secondary' }}">
          All
        </a>
        @foreach($categories as $category)
          <a href="{{ route('projects.templates.index', ['category' => $category]) }}"
             class="btn btn-sm {{ request('category') == $category ? 'btn-primary' : 'btn-outline-secondary' }}">
            {{ $category }}
          </a>
        @endforeach
      </div>
    </div>
  </div>
  @endif

  <!-- Templates Grid -->
  <div class="row">
    @forelse($templates as $template)
      <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100 {{ !$template->is_active ? 'opacity-75' : '' }}">
          <div class="card-header pb-0">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h5 class="card-title mb-1">
                  <a href="{{ route('projects.templates.show', $template) }}" class="text-body">
                    {{ $template->name }}
                  </a>
                </h5>
                @if($template->category)
                  <span class="badge bg-label-info">{{ $template->category }}</span>
                @endif
                @if(!$template->is_active)
                  <span class="badge bg-label-secondary">Inactive</span>
                @endif
              </div>
              <div class="dropdown">
                <button class="btn btn-sm btn-icon btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                  <i class="ti ti-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                    <a class="dropdown-item" href="{{ route('projects.templates.show', $template) }}">
                      <i class="ti ti-eye me-2"></i>View Details
                    </a>
                  </li>
                  <li>
                    <a class="dropdown-item" href="{{ route('projects.templates.edit', $template) }}">
                      <i class="ti ti-edit me-2"></i>Edit
                    </a>
                  </li>
                  <li>
                    <a class="dropdown-item" href="{{ route('projects.templates.create-project', $template) }}">
                      <i class="ti ti-plus me-2"></i>Create Project
                    </a>
                  </li>
                  <li>
                    <form method="POST" action="{{ route('projects.templates.toggle-active', $template) }}" class="d-inline">
                      @csrf
                      <button type="submit" class="dropdown-item">
                        <i class="ti ti-{{ $template->is_active ? 'eye-off' : 'eye' }} me-2"></i>
                        {{ $template->is_active ? 'Deactivate' : 'Activate' }}
                      </button>
                    </form>
                  </li>
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <form method="POST" action="{{ route('projects.templates.destroy', $template) }}"
                          onsubmit="return confirm('Delete this template?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="dropdown-item text-danger">
                        <i class="ti ti-trash me-2"></i>Delete
                      </button>
                    </form>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          <div class="card-body">
            @if($template->description)
              <p class="text-muted small mb-3">{{ Str::limit($template->description, 100) }}</p>
            @endif

            <div class="d-flex flex-wrap gap-2 mb-3">
              @if($template->estimated_duration_days)
                <span class="badge bg-label-secondary">
                  <i class="ti ti-calendar me-1"></i>{{ $template->estimated_duration_days }} days
                </span>
              @endif
              @if($template->estimated_budget)
                <span class="badge bg-label-secondary">
                  <i class="ti ti-currency-dollar me-1"></i>{{ number_format($template->estimated_budget) }}
                </span>
              @endif
              @if(is_array($template->milestone_templates) && count($template->milestone_templates) > 0)
                <span class="badge bg-label-primary">
                  <i class="ti ti-flag me-1"></i>{{ count($template->milestone_templates) }} milestones
                </span>
              @endif
              @if(is_array($template->risk_templates) && count($template->risk_templates) > 0)
                <span class="badge bg-label-warning">
                  <i class="ti ti-alert-triangle me-1"></i>{{ count($template->risk_templates) }} risks
                </span>
              @endif
              @if(is_array($template->task_templates) && count($template->task_templates) > 0)
                <span class="badge bg-label-info">
                  <i class="ti ti-list-check me-1"></i>{{ count($template->task_templates) }} tasks
                </span>
              @endif
            </div>

            <div class="d-flex justify-content-between align-items-center text-muted small">
              <span>
                <i class="ti ti-repeat me-1"></i>Used {{ $template->usage_count }} times
              </span>
              <span>
                {{ $template->projects_count ?? 0 }} projects
              </span>
            </div>
          </div>
          <div class="card-footer bg-transparent pt-0">
            <a href="{{ route('projects.templates.create-project', $template) }}"
               class="btn btn-primary btn-sm w-100 {{ !$template->is_active ? 'disabled' : '' }}">
              <i class="ti ti-plus me-1"></i>Create Project from Template
            </a>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12">
        <div class="card">
          <div class="card-body text-center py-5">
            <i class="ti ti-template text-muted mb-3" style="font-size: 3rem;"></i>
            <h5 class="text-muted">No Templates Yet</h5>
            <p class="text-muted mb-4">Create your first project template to speed up project creation.</p>
            <a href="{{ route('projects.templates.create') }}" class="btn btn-primary">
              <i class="ti ti-plus me-1"></i>Create First Template
            </a>
          </div>
        </div>
      </div>
    @endforelse
  </div>
</div>
@endsection
