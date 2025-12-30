@extends('layouts/layoutMaster')

@section('title', 'Project Follow-ups')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Summary Cards -->
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card border-start border-danger border-4">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-lg bg-label-danger me-3">
              <i class="ti ti-alert-circle ti-md"></i>
            </div>
            <div>
              <h3 class="mb-0">{{ $summary['overdue'] }}</h3>
              <small class="text-muted">Overdue</small>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-start border-warning border-4">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-lg bg-label-warning me-3">
              <i class="ti ti-clock ti-md"></i>
            </div>
            <div>
              <h3 class="mb-0">{{ $summary['due_soon'] }}</h3>
              <small class="text-muted">Due Soon</small>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-start border-success border-4">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-lg bg-label-success me-3">
              <i class="ti ti-check ti-md"></i>
            </div>
            <div>
              <h3 class="mb-0">{{ $summary['up_to_date'] }}</h3>
              <small class="text-muted">Up to Date</small>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-start border-secondary border-4">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-lg bg-label-secondary me-3">
              <i class="ti ti-minus ti-md"></i>
            </div>
            <div>
              <h3 class="mb-0">{{ $summary['no_followups'] }}</h3>
              <small class="text-muted">No Follow-ups</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Projects Needing Follow-up -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i class="ti ti-phone-call me-2"></i>Projects Needing Follow-up
      </h5>
      <div>
        <span class="badge bg-primary">{{ $summary['total'] }} Active Projects</span>
      </div>
    </div>
    <div class="card-body">
      @if (session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
          {{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif

      @if($projects->isEmpty())
        <div class="text-center py-5">
          <i class="ti ti-mood-happy ti-xl text-success mb-3"></i>
          <h5>All caught up!</h5>
          <p class="text-muted">No projects need follow-up at this time.</p>
        </div>
      @else
        <div class="table-responsive">
          <table class="table table-hover">
            <thead class="table-light">
              <tr>
                <th>Project</th>
                <th>Customer</th>
                <th>Follow-up Status</th>
                <th>Last Follow-up</th>
                <th>Next Due</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($projects as $project)
                <tr>
                  <td>
                    <a href="{{ route('projects.show', $project) }}" class="fw-semibold text-body">
                      {{ $project->name }}
                    </a>
                    <br>
                    <small class="badge bg-label-primary">{{ $project->code }}</small>
                  </td>
                  <td>
                    @if($project->customer)
                      {{ $project->customer->display_name }}
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    <span class="badge bg-{{ $project->followup_status_color }}">
                      {{ $project->followup_status_label }}
                    </span>
                  </td>
                  <td>
                    @if($project->last_followup_date)
                      {{ $project->last_followup_date->format('M d, Y') }}
                      <br>
                      <small class="text-muted">{{ $project->last_followup_date->diffForHumans() }}</small>
                    @else
                      <span class="text-muted">Never</span>
                    @endif
                  </td>
                  <td>
                    @if($project->next_followup_date)
                      @if($project->next_followup_date->isPast())
                        <span class="text-danger fw-semibold">{{ $project->next_followup_date->format('M d, Y') }}</span>
                      @elseif($project->next_followup_date->diffInDays(now()) <= 3)
                        <span class="text-warning fw-semibold">{{ $project->next_followup_date->format('M d, Y') }}</span>
                      @else
                        {{ $project->next_followup_date->format('M d, Y') }}
                      @endif
                    @else
                      <span class="text-muted">Not set</span>
                    @endif
                  </td>
                  <td class="text-center">
                    <button type="button" class="btn btn-primary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#followupModal"
                            data-project-id="{{ $project->id }}"
                            data-project-name="{{ $project->name }}"
                            data-project-code="{{ $project->code }}">
                      <i class="ti ti-phone-plus me-1"></i>Log Follow-up
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                            onclick="viewHistory({{ $project->id }}, '{{ $project->name }}')">
                      <i class="ti ti-history"></i>
                    </button>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>
</div>

<!-- Log Follow-up Modal -->
<div class="modal fade" id="followupModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="ti ti-phone-call me-2"></i>Log Follow-up: <span id="modalProjectName"></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="followupForm" method="POST">
        @csrf
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Follow-up Type</label>
              <select class="form-select" name="type" required>
                <option value="call">Phone Call</option>
                <option value="email">Email</option>
                <option value="meeting">Meeting</option>
                <option value="message">Message</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Contact Person</label>
              <input type="text" class="form-control" name="contact_person" placeholder="Who did you contact?">
            </div>
            <div class="col-md-6">
              <label class="form-label">Follow-up Date</label>
              <input type="date" class="form-control" name="followup_date" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Outcome</label>
              <select class="form-select" name="outcome" required>
                <option value="positive">Positive</option>
                <option value="neutral" selected>Neutral</option>
                <option value="needs_attention">Needs Attention</option>
                <option value="escalation">Escalation Required</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Notes</label>
              <textarea class="form-control" name="notes" rows="4" required placeholder="Summary of the follow-up..."></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Schedule Next Follow-up</label>
              <input type="date" class="form-control" name="next_followup_date" min="{{ date('Y-m-d') }}">
              <small class="text-muted">Leave empty for default (7 days)</small>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-check me-1"></i>Save Follow-up
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="ti ti-history me-2"></i>Follow-up History: <span id="historyProjectName"></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="historyLoading" class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
        <div id="historyContent" style="display: none;">
          <!-- Activity Summary -->
          <div class="row mb-4" id="activitySummary">
            <!-- Filled by JS -->
          </div>

          <!-- Follow-up Timeline -->
          <div class="timeline-wrapper" id="historyTimeline">
            <!-- Filled by JS -->
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const followupModal = document.getElementById('followupModal');

  followupModal.addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const projectId = button.getAttribute('data-project-id');
    const projectName = button.getAttribute('data-project-name');
    const projectCode = button.getAttribute('data-project-code');

    document.getElementById('modalProjectName').textContent = projectName + ' (' + projectCode + ')';
    document.getElementById('followupForm').action = '/projects/' + projectId + '/followups';
  });
});

function viewHistory(projectId, projectName) {
  const modal = new bootstrap.Modal(document.getElementById('historyModal'));
  document.getElementById('historyProjectName').textContent = projectName;
  document.getElementById('historyLoading').style.display = 'block';
  document.getElementById('historyContent').style.display = 'none';

  modal.show();

  fetch('/projects/' + projectId + '/followups/history')
    .then(response => response.json())
    .then(data => {
      document.getElementById('historyLoading').style.display = 'none';
      document.getElementById('historyContent').style.display = 'block';

      // Activity Summary
      const activityHtml = `
        <div class="col-md-4">
          <div class="card bg-label-primary">
            <div class="card-body">
              <h6 class="mb-0">${data.activity.recent_worklog_count}</h6>
              <small>Worklogs (30 days)</small>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card bg-label-info">
            <div class="card-body">
              <h6 class="mb-0">${data.activity.last_activity_date || 'Never'}</h6>
              <small>Last Activity</small>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card bg-label-${data.project.followup_status_color}">
            <div class="card-body">
              <h6 class="mb-0">${data.project.followup_status_label}</h6>
              <small>Follow-up Status</small>
            </div>
          </div>
        </div>
      `;
      document.getElementById('activitySummary').innerHTML = activityHtml;

      // Timeline
      if (data.followups.length === 0) {
        document.getElementById('historyTimeline').innerHTML = `
          <div class="text-center py-4 text-muted">
            <i class="ti ti-history-off ti-xl mb-2"></i>
            <p>No follow-ups recorded yet.</p>
          </div>
        `;
      } else {
        let timelineHtml = '<ul class="timeline">';
        data.followups.forEach(f => {
          timelineHtml += `
            <li class="timeline-item timeline-item-transparent">
              <span class="timeline-point timeline-point-${f.outcome_color}"></span>
              <div class="timeline-event">
                <div class="timeline-header mb-1">
                  <h6 class="mb-0">${f.type_label}</h6>
                  <small class="text-muted">${f.followup_date_formatted}</small>
                </div>
                <p class="mb-2">${f.notes}</p>
                <div class="d-flex gap-2 flex-wrap">
                  <span class="badge bg-${f.outcome_color}">${f.outcome_label}</span>
                  ${f.contact_person ? `<span class="badge bg-label-secondary"><i class="ti ti-user me-1"></i>${f.contact_person}</span>` : ''}
                  <span class="badge bg-label-info"><i class="ti ti-user me-1"></i>${f.user}</span>
                </div>
              </div>
            </li>
          `;
        });
        timelineHtml += '</ul>';
        document.getElementById('historyTimeline').innerHTML = timelineHtml;
      }
    })
    .catch(error => {
      console.error('Error:', error);
      document.getElementById('historyLoading').style.display = 'none';
      document.getElementById('historyContent').innerHTML = '<div class="alert alert-danger">Failed to load history.</div>';
      document.getElementById('historyContent').style.display = 'block';
    });
}
</script>

<style>
.timeline {
  position: relative;
  padding-left: 1.5rem;
  list-style: none;
}
.timeline-item {
  position: relative;
  padding-bottom: 1.5rem;
  padding-left: 1.5rem;
  border-left: 1px solid #e4e6e8;
}
.timeline-item:last-child {
  border-left-color: transparent;
}
.timeline-point {
  position: absolute;
  left: -0.5rem;
  width: 1rem;
  height: 1rem;
  border-radius: 50%;
  background-color: #6c757d;
}
.timeline-point-success { background-color: #28c76f; }
.timeline-point-warning { background-color: #ff9f43; }
.timeline-point-danger { background-color: #ea5455; }
.timeline-point-secondary { background-color: #6c757d; }
.timeline-event {
  padding: 0.75rem 1rem;
  background-color: #f8f9fa;
  border-radius: 0.375rem;
}
</style>
@endsection
