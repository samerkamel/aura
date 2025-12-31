@extends('layouts/layoutMaster')

@section('title', 'Timeline - ' . $project->name)

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
                  <i class="ti ti-chart-line ti-lg"></i>
                </span>
              </div>
              <div>
                <h4 class="mb-0">{{ $project->name }}</h4>
                <p class="text-muted mb-0">
                  <span class="badge bg-label-primary me-2">{{ $project->code }}</span>
                  Project Timeline
                </p>
              </div>
            </div>
            <div class="d-flex gap-2">
              <a href="{{ route('projects.planning.milestones', $project) }}" class="btn btn-outline-secondary">
                <i class="ti ti-flag me-1"></i>Milestones
              </a>
              <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Back to Project
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Project Duration Overview -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <h6 class="text-muted mb-3">Project Timeline</h6>
          @if($project->planned_start_date && $project->planned_end_date)
            @php
              $totalDays = $project->planned_start_date->diffInDays($project->planned_end_date);
              $elapsedDays = min($totalDays, max(0, $project->planned_start_date->diffInDays(now())));
              $progress = $totalDays > 0 ? ($elapsedDays / $totalDays) * 100 : 0;
            @endphp
            <div class="d-flex justify-content-between mb-2">
              <span>{{ $project->planned_start_date->format('M d, Y') }}</span>
              <span class="fw-semibold">{{ number_format($progress, 0) }}% elapsed</span>
              <span>{{ $project->planned_end_date->format('M d, Y') }}</span>
            </div>
            <div class="progress" style="height: 10px;">
              <div class="progress-bar" style="width: {{ $progress }}%"></div>
            </div>
            <div class="mt-2 text-center">
              <small class="text-muted">
                {{ $totalDays }} total days |
                {{ $elapsedDays }} days elapsed |
                {{ max(0, $totalDays - $elapsedDays) }} days remaining
              </small>
            </div>
          @else
            <div class="alert alert-warning mb-0">
              <i class="ti ti-alert-circle me-2"></i>
              No planned dates set for this project. Set start and end dates to see the timeline.
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Milestones Timeline -->
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="card-title mb-0">
        <i class="ti ti-flag me-2"></i>Milestones Timeline
      </h5>
    </div>
    <div class="card-body">
      @if($project->milestones->count() > 0)
        <div class="timeline timeline-center">
          @foreach($project->milestones->sortBy('due_date') as $index => $milestone)
            @php
              $isOverdue = $milestone->isOverdue();
              $isCompleted = $milestone->status === 'completed';
              $statusColors = [
                'pending' => 'secondary',
                'in_progress' => 'primary',
                'completed' => 'success',
                'on_hold' => 'warning',
                'cancelled' => 'danger'
              ];
              $color = $isOverdue && !$isCompleted ? 'danger' : ($statusColors[$milestone->status] ?? 'secondary');
            @endphp
            <div class="timeline-item {{ $index % 2 == 0 ? 'timeline-item-left' : 'timeline-item-right' }}">
              <span class="timeline-indicator timeline-indicator-{{ $color }}">
                <i class="ti ti-{{ $isCompleted ? 'check' : ($isOverdue ? 'alert-triangle' : 'flag') }}"></i>
              </span>
              <div class="timeline-event card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0">{{ $milestone->name }}</h6>
                    <span class="badge bg-label-{{ $color }}">
                      {{ str_replace('_', ' ', ucfirst($milestone->status)) }}
                    </span>
                  </div>
                  @if($milestone->description)
                    <p class="text-muted mb-2 small">{{ Str::limit($milestone->description, 100) }}</p>
                  @endif
                  <div class="d-flex justify-content-between align-items-center">
                    <small class="text-{{ $isOverdue && !$isCompleted ? 'danger' : 'muted' }}">
                      <i class="ti ti-calendar me-1"></i>
                      {{ $milestone->due_date ? $milestone->due_date->format('M d, Y') : 'No date' }}
                    </small>
                    @if($milestone->progress_percentage > 0)
                      <small class="text-muted">
                        <i class="ti ti-progress me-1"></i>{{ number_format($milestone->progress_percentage, 0) }}%
                      </small>
                    @endif
                  </div>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @else
        <div class="text-center py-5">
          <i class="ti ti-flag text-muted mb-3" style="font-size: 3rem;"></i>
          <h5 class="text-muted">No Milestones</h5>
          <p class="text-muted mb-4">Add milestones to see them on the timeline.</p>
          <a href="{{ route('projects.planning.milestones', $project) }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>Add Milestones
          </a>
        </div>
      @endif
    </div>
  </div>

  <!-- Dependencies Impact -->
  @if($project->dependencies->count() > 0 || $project->dependents->count() > 0)
  <div class="card">
    <div class="card-header">
      <h5 class="card-title mb-0">
        <i class="ti ti-link me-2"></i>Dependencies Impact
      </h5>
    </div>
    <div class="card-body">
      <div class="row">
        @if($project->dependencies->count() > 0)
          <div class="col-md-6">
            <h6 class="text-muted mb-3">Blocked By</h6>
            @foreach($project->dependencies as $dep)
              <div class="d-flex align-items-center mb-2 p-2 border rounded">
                <div class="avatar avatar-sm bg-label-secondary me-2">
                  <i class="ti ti-folder"></i>
                </div>
                <div class="flex-grow-1">
                  <span>{{ $dep->name }}</span>
                  <br>
                  <small class="text-muted">{{ $dep->code }}</small>
                </div>
                @if($dep->actual_end_date)
                  <span class="badge bg-success">Completed</span>
                @elseif($dep->planned_end_date)
                  <small class="text-muted">Due: {{ $dep->planned_end_date->format('M d') }}</small>
                @endif
              </div>
            @endforeach
          </div>
        @endif
        @if($project->dependents->count() > 0)
          <div class="col-md-6">
            <h6 class="text-muted mb-3">Blocking</h6>
            @foreach($project->dependents as $dependent)
              <div class="d-flex align-items-center mb-2 p-2 border rounded">
                <div class="avatar avatar-sm bg-label-warning me-2">
                  <i class="ti ti-folder"></i>
                </div>
                <div class="flex-grow-1">
                  <span>{{ $dependent->name }}</span>
                  <br>
                  <small class="text-muted">{{ $dependent->code }}</small>
                </div>
                <span class="badge bg-warning">Waiting</span>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>
  </div>
  @endif
</div>

<style>
.timeline {
  position: relative;
  padding: 20px 0;
}
.timeline::before {
  content: '';
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  width: 2px;
  height: 100%;
  background: #e9ecef;
}
.timeline-item {
  position: relative;
  width: 50%;
  padding: 20px 40px;
  margin-bottom: 20px;
}
.timeline-item-left {
  left: 0;
  padding-right: 40px;
  text-align: right;
}
.timeline-item-right {
  left: 50%;
  padding-left: 40px;
}
.timeline-indicator {
  position: absolute;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  border: 2px solid;
  z-index: 1;
}
.timeline-item-left .timeline-indicator {
  right: -20px;
}
.timeline-item-right .timeline-indicator {
  left: -20px;
}
.timeline-indicator-success { border-color: #28a745; color: #28a745; }
.timeline-indicator-primary { border-color: #696cff; color: #696cff; }
.timeline-indicator-warning { border-color: #ffc107; color: #ffc107; }
.timeline-indicator-danger { border-color: #dc3545; color: #dc3545; }
.timeline-indicator-secondary { border-color: #8592a3; color: #8592a3; }
.timeline-event {
  margin: 0;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
@media (max-width: 768px) {
  .timeline::before { left: 20px; }
  .timeline-item { width: 100%; left: 0; padding-left: 60px; padding-right: 20px; text-align: left; }
  .timeline-indicator { left: 0 !important; right: auto !important; }
}
</style>
@endsection
