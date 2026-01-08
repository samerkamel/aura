@extends('layouts/layoutMaster')

@section('title', 'PM Dashboard')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/fullcalendar/fullcalendar.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/fullcalendar/fullcalendar.js'])
@endsection

@section('page-style')
<style>
    .alert-card {
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: pointer;
    }
    .alert-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .priority-badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 3px;
    }
    .priority-urgent {
        background-color: #dc3545;
        color: white;
    }
    .priority-high {
        background-color: #fd7e14;
        color: white;
    }
    .priority-normal {
        background-color: #17a2b8;
        color: white;
    }
    .overdue-item {
        border-left: 3px solid #dc3545;
        padding-left: 0.75rem;
    }
    .today-item {
        border-left: 3px solid #fd7e14;
        padding-left: 0.75rem;
    }
    .upcoming-item {
        border-left: 3px solid #198754;
        padding-left: 0.75rem;
    }
    .stat-card {
        border-radius: 8px;
        transition: all 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
    }
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .stat-label {
        font-size: 0.875rem;
        color: #6c757d;
    }
    .project-health-bar {
        height: 8px;
        border-radius: 4px;
        background: #e9ecef;
        overflow: hidden;
    }
    .project-health-fill {
        height: 100%;
        transition: width 0.3s ease;
    }
    .activity-item {
        padding: 0.75rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .activity-item:last-child {
        border-bottom: none;
    }
    .activity-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .workload-bar {
        height: 6px;
        border-radius: 3px;
        background: #e9ecef;
        overflow: hidden;
    }
    .workload-fill {
        height: 100%;
        transition: width 0.3s ease;
    }
    .workload-fill.overloaded {
        background: linear-gradient(90deg, #28a745 0%, #ffc107 60%, #dc3545 100%);
    }
    /* Multi-segment workload bar */
    .workload-bar-multi {
        height: 8px;
        border-radius: 4px;
        background: #f0f0f0;
        overflow: hidden;
        display: flex;
        border: 1px solid #e0e0e0;
    }
    .workload-segment {
        height: 100%;
        transition: width 0.3s ease;
    }
    .quick-action-btn {
        border-radius: 8px;
        padding: 0.75rem;
        text-align: center;
        transition: all 0.2s;
    }
    .quick-action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
</style>
@endsection

@section('content')
<div class="flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">PM Dashboard</h4>
            <p class="text-muted mb-0">Your command center for project management</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('projects.pm-dashboard.calendar') }}" class="btn btn-outline-primary">
                <i class="ti ti-calendar me-1"></i> Calendar View
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#quickFollowupModal">
                <i class="ti ti-plus me-1"></i> Quick Follow-up
            </button>
        </div>
    </div>

    {{-- Alert Summary Cards --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card bg-label-danger h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-value text-danger">{{ $overdueFollowups->count() + $overdueMilestones->count() }}</div>
                            <div class="stat-label">Overdue Items</div>
                        </div>
                        <div class="avatar avatar-sm bg-danger">
                            <span class="avatar-initial rounded"><i class="ti ti-alert-triangle"></i></span>
                        </div>
                    </div>
                    <div class="mt-2 small">
                        <span class="text-danger">{{ $overdueFollowups->count() }} follow-ups</span>,
                        <span class="text-danger">{{ $overdueMilestones->count() }} milestones</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card bg-label-warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-value text-warning">{{ $todayFollowups->count() }}</div>
                            <div class="stat-label">Due Today</div>
                        </div>
                        <div class="avatar avatar-sm bg-warning">
                            <span class="avatar-initial rounded"><i class="ti ti-clock"></i></span>
                        </div>
                    </div>
                    <div class="mt-2 small text-muted">
                        Follow-ups scheduled for today
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card bg-label-info h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-value text-info">{{ $activeProjects }}</div>
                            <div class="stat-label">Active Projects</div>
                        </div>
                        <div class="avatar avatar-sm bg-info">
                            <span class="avatar-initial rounded"><i class="ti ti-briefcase"></i></span>
                        </div>
                    </div>
                    <div class="mt-2 small text-muted">
                        {{ $atRiskProjects->count() }} at risk
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card bg-label-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-value text-primary">{{ $pendingPayments->where('is_overdue', true)->count() }}</div>
                            <div class="stat-label">Overdue Payments</div>
                        </div>
                        <div class="avatar avatar-sm bg-primary">
                            <span class="avatar-initial rounded"><i class="ti ti-cash"></i></span>
                        </div>
                    </div>
                    <div class="mt-2 small text-muted">
                        {{ $pendingPayments->where('is_overdue', false)->count() }} upcoming
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Today's Focus --}}
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-target text-primary me-2"></i>Today's Focus
                    </h5>
                    <span class="badge bg-label-primary">{{ $todayFollowups->count() + $overdueFollowups->count() }} items</span>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    @if($overdueFollowups->count() > 0)
                        <h6 class="text-danger mb-3"><i class="ti ti-alert-circle me-1"></i>Overdue</h6>
                        @foreach($overdueFollowups->take(5) as $followup)
                            <div class="d-flex align-items-start mb-3 overdue-item">
                                <div class="flex-grow-1">
                                    <a href="{{ route('projects.show', $followup->project_id) }}#followups" class="fw-semibold text-body">
                                        {{ $followup->project->name }}
                                    </a>
                                    <p class="mb-1 small text-muted">{{ \Illuminate\Support\Str::limit($followup->notes, 80) }}</p>
                                    <span class="badge bg-danger">{{ $followup->next_followup_date->diffForHumans() }}</span>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('projects.show', $followup->project_id) }}#followups">View Project</a></li>
                                        <li><a class="dropdown-item mark-complete" href="#" data-id="{{ $followup->id }}">Mark Complete</a></li>
                                    </ul>
                                </div>
                            </div>
                        @endforeach
                    @endif

                    @if($todayFollowups->count() > 0)
                        <h6 class="text-warning mb-3 mt-4"><i class="ti ti-clock me-1"></i>Due Today</h6>
                        @foreach($todayFollowups as $followup)
                            <div class="d-flex align-items-start mb-3 today-item">
                                <div class="flex-grow-1">
                                    <a href="{{ route('projects.show', $followup->project_id) }}#followups" class="fw-semibold text-body">
                                        {{ $followup->project->name }}
                                    </a>
                                    <p class="mb-1 small text-muted">{{ \Illuminate\Support\Str::limit($followup->notes, 80) }}</p>
                                    <span class="badge bg-warning">Today</span>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('projects.show', $followup->project_id) }}#followups">View Project</a></li>
                                        <li><a class="dropdown-item mark-complete" href="#" data-id="{{ $followup->id }}">Mark Complete</a></li>
                                    </ul>
                                </div>
                            </div>
                        @endforeach
                    @endif

                    @if($todayFollowups->count() === 0 && $overdueFollowups->count() === 0)
                        <div class="text-center text-muted py-4">
                            <i class="ti ti-check-circle text-success mb-2" style="font-size: 3rem;"></i>
                            <p class="mb-0">All caught up! No follow-ups due today.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Upcoming Milestones --}}
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-flag text-success me-2"></i>Milestones
                    </h5>
                    <span class="badge bg-label-success">{{ $upcomingMilestones->count() + $overdueMilestones->count() }} items</span>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    @if($overdueMilestones->count() > 0)
                        <h6 class="text-danger mb-3"><i class="ti ti-alert-circle me-1"></i>Overdue</h6>
                        @foreach($overdueMilestones->take(5) as $milestone)
                            <div class="d-flex align-items-start mb-3 overdue-item">
                                <div class="flex-grow-1">
                                    <a href="{{ route('projects.planning.milestones', $milestone->project_id) }}" class="fw-semibold text-body">
                                        {{ $milestone->name }}
                                    </a>
                                    <p class="mb-1 small text-muted">{{ $milestone->project->name }}</p>
                                    <span class="badge bg-danger">{{ $milestone->due_date->diffForHumans() }}</span>
                                    <span class="badge bg-label-{{ $milestone->status === 'in_progress' ? 'info' : 'secondary' }} ms-1">{{ ucfirst(str_replace('_', ' ', $milestone->status)) }}</span>
                                </div>
                            </div>
                        @endforeach
                    @endif

                    @if($upcomingMilestones->count() > 0)
                        <h6 class="text-success mb-3 mt-4"><i class="ti ti-calendar-event me-1"></i>Upcoming</h6>
                        @foreach($upcomingMilestones->take(5) as $milestone)
                            <div class="d-flex align-items-start mb-3 upcoming-item">
                                <div class="flex-grow-1">
                                    <a href="{{ route('projects.planning.milestones', $milestone->project_id) }}" class="fw-semibold text-body">
                                        {{ $milestone->name }}
                                    </a>
                                    <p class="mb-1 small text-muted">{{ $milestone->project->name }}</p>
                                    <span class="badge bg-success">{{ $milestone->due_date->diffForHumans() }}</span>
                                    <span class="badge bg-label-{{ $milestone->status === 'in_progress' ? 'info' : 'secondary' }} ms-1">{{ ucfirst(str_replace('_', ' ', $milestone->status)) }}</span>
                                </div>
                            </div>
                        @endforeach
                    @endif

                    @if($upcomingMilestones->count() === 0 && $overdueMilestones->count() === 0)
                        <div class="text-center text-muted py-4">
                            <i class="ti ti-flag-check text-success mb-2" style="font-size: 3rem;"></i>
                            <p class="mb-0">No pending milestones in the next 14 days.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- At-Risk Projects --}}
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-heart-rate-monitor text-danger me-2"></i>At-Risk Projects
                    </h5>
                    @if($atRiskProjects->count() > 0)
                        <span class="badge bg-label-danger">{{ $atRiskProjects->count() }} projects</span>
                    @endif
                </div>
                <div class="card-body">
                    @if($atRiskProjects->count() > 0)
                        @foreach($atRiskProjects->take(5) as $project)
                            @php
                                $healthScore = $project->latestHealthSnapshot->health_score;
                                $healthColor = $healthScore >= 60 ? 'success' : ($healthScore >= 40 ? 'warning' : 'danger');
                            @endphp
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-grow-1">
                                    <a href="{{ route('projects.dashboard', $project->id) }}" class="fw-semibold text-body d-block">
                                        {{ $project->name }}
                                    </a>
                                    <div class="d-flex align-items-center mt-1">
                                        <div class="project-health-bar flex-grow-1 me-2" style="width: 100px;">
                                            <div class="project-health-fill bg-{{ $healthColor }}" style="width: {{ $healthScore }}%;"></div>
                                        </div>
                                        <span class="badge bg-{{ $healthColor }}">{{ round($healthScore) }}%</span>
                                    </div>
                                </div>
                                <a href="{{ route('projects.dashboard', $project->id) }}" class="btn btn-sm btn-icon btn-outline-primary">
                                    <i class="ti ti-external-link"></i>
                                </a>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="ti ti-heart text-success mb-2" style="font-size: 3rem;"></i>
                            <p class="mb-0">All projects are healthy!</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Team Workload --}}
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-users text-info me-2"></i>Team Workload (2 Weeks)
                    </h5>
                    <div class="d-flex gap-3 small">
                        <span><span class="badge bg-success">&nbsp;</span> Logged</span>
                        <span><span class="badge bg-warning">&nbsp;</span> Scheduled</span>
                        <span><span class="badge bg-light border">&nbsp;</span> Available</span>
                    </div>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    @if(count($teamWorkload) > 0)
                        @foreach($teamWorkload as $member)
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar avatar-sm me-3 bg-label-primary">
                                    @php
                                        $nameParts = explode(' ', $member['employee']->name ?? 'Unknown');
                                        $initials = strtoupper(substr($nameParts[0] ?? 'U', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                                    @endphp
                                    <span class="avatar-initial">{{ $initials }}</span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <a href="#" class="fw-semibold text-body employee-workload-link" data-employee-id="{{ $member['employee']->id }}">{{ $member['employee']->name ?? 'Unknown' }}</a>
                                        <span class="small {{ $member['is_overloaded'] ? 'text-danger fw-bold' : '' }}">
                                            {{ $member['logged_hours'] }}h logged
                                            @if($member['scheduled_hours'] > 0)
                                                + {{ $member['scheduled_hours'] }}h scheduled
                                            @endif
                                            / {{ $member['capacity'] }}h
                                        </span>
                                    </div>
                                    <div class="workload-bar-multi">
                                        {{-- Logged hours (green) --}}
                                        <div class="workload-segment bg-success" style="width: {{ $member['logged_percent'] }}%;" title="Logged: {{ $member['logged_hours'] }}h"></div>
                                        {{-- Scheduled hours (orange/warning) --}}
                                        <div class="workload-segment bg-warning" style="width: {{ $member['scheduled_percent'] }}%;" title="Scheduled: {{ $member['scheduled_hours'] }}h"></div>
                                        {{-- Unutilized remains as background --}}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="ti ti-clock text-muted mb-2" style="font-size: 3rem;"></i>
                            <p class="mb-0">No workload data for this period.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Quick Follow-up Modal --}}
<div class="modal fade" id="quickFollowupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick Follow-up</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickFollowupForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Project</label>
                        <select class="form-select" name="project_id" required>
                            <option value="">Select Project...</option>
                            @foreach(\Modules\Project\Models\Project::active()->orderBy('name')->get() as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type" required>
                            <option value="call">Phone Call</option>
                            <option value="email">Email</option>
                            <option value="meeting">Meeting</option>
                            <option value="message">Message</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3" required placeholder="What was discussed?"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Next Follow-up Date (Optional)</label>
                        <input type="date" class="form-control" name="next_followup_date" min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Follow-up</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Employee Workload Modal --}}
<div class="modal fade" id="employeeWorkloadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-user me-2"></i>
                    <span id="employeeWorkloadName">Employee Workload</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="employeeWorkloadContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading workload data...</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quick Follow-up Form
    const quickFollowupForm = document.getElementById('quickFollowupForm');
    if (quickFollowupForm) {
        quickFollowupForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            try {
                const response = await fetch('{{ route("projects.pm-dashboard.quick-followup") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('quickFollowupModal')).hide();
                    this.reset();
                    // Show success message and reload
                    Swal.fire({
                        icon: 'success',
                        title: 'Follow-up Logged',
                        text: 'Follow-up has been saved successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to save follow-up.'
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while saving the follow-up.'
                });
            }
        });
    }

    // Employee Workload Modal
    const employeeWorkloadModal = document.getElementById('employeeWorkloadModal');
    const employeeWorkloadLinks = document.querySelectorAll('.employee-workload-link');

    employeeWorkloadLinks.forEach(link => {
        link.addEventListener('click', async function(e) {
            e.preventDefault();
            const employeeId = this.dataset.employeeId;
            const employeeName = this.textContent;

            // Update modal title
            document.getElementById('employeeWorkloadName').textContent = employeeName + ' - Workload';

            // Show loading state
            document.getElementById('employeeWorkloadContent').innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading workload data...</p>
                </div>
            `;

            // Show modal
            const modal = new bootstrap.Modal(employeeWorkloadModal);
            modal.show();

            // Fetch employee workload data
            try {
                const response = await fetch(`{{ url('projects/pm-dashboard/api/employee-workload') }}/${employeeId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch employee workload');
                }

                const data = await response.json();
                renderEmployeeWorkload(data);
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('employeeWorkloadContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="ti ti-alert-circle me-2"></i>
                        Failed to load employee workload data.
                    </div>
                `;
            }
        });
    });

    function renderEmployeeWorkload(data) {
        const content = document.getElementById('employeeWorkloadContent');

        let html = `
            {{-- Period Summary --}}
            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <div class="card bg-label-success">
                        <div class="card-body p-3 text-center">
                            <h4 class="mb-1">${data.period.logged_hours}h</h4>
                            <small>Logged Hours</small>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card bg-label-warning">
                        <div class="card-body p-3 text-center">
                            <h4 class="mb-1">${data.period.scheduled_hours}h</h4>
                            <small>Scheduled Hours</small>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card bg-label-info">
                        <div class="card-body p-3 text-center">
                            <h4 class="mb-1">${data.period.task_count}</h4>
                            <small>Active Tasks</small>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-muted small mb-3">
                <i class="ti ti-calendar me-1"></i>
                Period: ${data.period.start} to ${data.period.end}
            </p>

            {{-- Tabs --}}
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#recentTasks" type="button">
                        <i class="ti ti-clock me-1"></i>Recent Work
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#scheduledTasks" type="button">
                        <i class="ti ti-calendar-event me-1"></i>Scheduled
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#monthlyHours" type="button">
                        <i class="ti ti-chart-bar me-1"></i>Monthly
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#upcomingLeaves" type="button">
                        <i class="ti ti-beach me-1"></i>Leaves
                    </button>
                </li>
            </ul>

            <div class="tab-content mt-3">
                {{-- Recent Tasks Tab --}}
                <div class="tab-pane fade show active" id="recentTasks">
                    ${renderRecentTasks(data.recent_tasks)}
                </div>

                {{-- Scheduled Tasks Tab --}}
                <div class="tab-pane fade" id="scheduledTasks">
                    ${renderScheduledTasks(data.scheduled_tasks)}
                </div>

                {{-- Monthly Hours Tab --}}
                <div class="tab-pane fade" id="monthlyHours">
                    ${renderMonthlyHours(data.monthly_hours)}
                </div>

                {{-- Upcoming Leaves Tab --}}
                <div class="tab-pane fade" id="upcomingLeaves">
                    ${renderUpcomingLeaves(data.upcoming_leaves)}
                </div>
            </div>
        `;

        content.innerHTML = html;
    }

    function renderRecentTasks(tasks) {
        if (!tasks || tasks.length === 0) {
            return `<div class="text-center text-muted py-4">
                <i class="ti ti-clipboard-off mb-2" style="font-size: 2rem;"></i>
                <p class="mb-0">No recent tasks in this period.</p>
            </div>`;
        }

        let html = '<div class="accordion" id="recentTasksAccordion">';
        tasks.forEach((task, index) => {
            const statusBadge = getStatusBadge(task.status_category);
            html += `
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button ${index > 0 ? 'collapsed' : ''}" type="button" data-bs-toggle="collapse" data-bs-target="#task${index}">
                            <span class="badge ${statusBadge} me-2">${task.status}</span>
                            <strong class="me-2">${task.issue_key}</strong>
                            <span class="text-truncate">${task.summary}</span>
                            <span class="badge bg-success ms-auto me-2">${task.total_hours}h</span>
                        </button>
                    </h2>
                    <div id="task${index}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" data-bs-parent="#recentTasksAccordion">
                        <div class="accordion-body">
                            <table class="table table-sm table-borderless mb-0">
                                <thead>
                                    <tr class="text-muted small">
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th class="text-end">Hours</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${task.worklogs.map(w => `
                                        <tr>
                                            <td class="small">${w.date}</td>
                                            <td class="small">${w.description || '-'}</td>
                                            <td class="text-end small">${w.hours}h</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        return html;
    }

    function renderScheduledTasks(tasks) {
        if (!tasks || tasks.length === 0) {
            return `<div class="text-center text-muted py-4">
                <i class="ti ti-calendar-off mb-2" style="font-size: 2rem;"></i>
                <p class="mb-0">No scheduled tasks for this period.</p>
            </div>`;
        }

        let html = '<div class="list-group list-group-flush">';
        tasks.forEach(task => {
            const statusBadge = getStatusBadge(task.status_category);
            const priorityBadge = getPriorityBadge(task.priority);
            html += `
                <div class="list-group-item px-0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge ${statusBadge}">${task.status}</span>
                                ${priorityBadge}
                                <strong>${task.issue_key}</strong>
                            </div>
                            <p class="mb-1">${task.summary}</p>
                            <small class="text-muted">
                                ${task.due_date ? `<i class="ti ti-calendar me-1"></i>Due: ${task.due_date}` : ''}
                                ${task.remaining_hours ? `<span class="ms-2"><i class="ti ti-clock me-1"></i>Est: ${task.remaining_hours}h</span>` : ''}
                            </small>
                        </div>
                        ${task.jira_url ? `<a href="${task.jira_url}" target="_blank" class="btn btn-sm btn-icon btn-outline-primary"><i class="ti ti-external-link"></i></a>` : ''}
                    </div>
                </div>
            `;
        });
        html += '</div>';
        return html;
    }

    function renderMonthlyHours(months) {
        if (!months || months.length === 0) {
            return `<div class="text-center text-muted py-4">
                <i class="ti ti-chart-bar-off mb-2" style="font-size: 2rem;"></i>
                <p class="mb-0">No monthly data available.</p>
            </div>`;
        }

        let html = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Month</th><th class="text-end">Hours Logged</th><th>Progress</th></tr></thead><tbody>';
        const maxHours = Math.max(...months.map(m => m.hours), 1);
        months.forEach(month => {
            const percent = Math.round((month.hours / maxHours) * 100);
            html += `
                <tr>
                    <td><strong>${month.month}</strong></td>
                    <td class="text-end">${month.hours}h</td>
                    <td style="width: 40%;">
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-primary" style="width: ${percent}%;"></div>
                        </div>
                    </td>
                </tr>
            `;
        });
        html += '</tbody></table></div>';
        return html;
    }

    function renderUpcomingLeaves(leaves) {
        if (!leaves || leaves.length === 0) {
            return `<div class="text-center text-muted py-4">
                <i class="ti ti-beach mb-2" style="font-size: 2rem;"></i>
                <p class="mb-0">No upcoming leaves scheduled.</p>
            </div>`;
        }

        let html = '<div class="list-group list-group-flush">';
        leaves.forEach(leave => {
            const statusClass = leave.status === 'approved' ? 'success' : (leave.status === 'pending' ? 'warning' : 'secondary');
            html += `
                <div class="list-group-item px-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${leave.type}</strong>
                            <p class="mb-0 small text-muted">
                                ${leave.start_date} - ${leave.end_date}
                                <span class="ms-2">(${leave.days} day${leave.days > 1 ? 's' : ''})</span>
                            </p>
                        </div>
                        <span class="badge bg-${statusClass}">${leave.status}</span>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        return html;
    }

    function getStatusBadge(statusCategory) {
        switch(statusCategory) {
            case 'new': return 'bg-secondary';
            case 'indeterminate': return 'bg-info';
            case 'done': return 'bg-success';
            default: return 'bg-secondary';
        }
    }

    function getPriorityBadge(priority) {
        const colors = {
            'Highest': 'danger',
            'High': 'warning',
            'Medium': 'info',
            'Low': 'secondary',
            'Lowest': 'light'
        };
        const color = colors[priority] || 'secondary';
        return priority ? `<span class="badge bg-label-${color}">${priority}</span>` : '';
    }

    // Auto-refresh dashboard data every 5 minutes
    setInterval(function() {
        fetch('{{ route("projects.pm-dashboard.data") }}')
            .then(response => response.json())
            .then(data => {
                // Update notification badge if needed
                if (data.unread_notifications > 0) {
                    const badge = document.querySelector('.notification-badge');
                    if (badge) {
                        badge.textContent = data.unread_notifications;
                        badge.classList.remove('d-none');
                    }
                }
            })
            .catch(error => console.error('Error refreshing data:', error));
    }, 300000); // 5 minutes
});
</script>
@endsection
