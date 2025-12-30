<div class="kanban-card {{ $issue->isOverdue() ? 'issue-overdue' : '' }}" data-jira-url="{{ $issue->jira_url }}">
  <div class="d-flex justify-content-between align-items-start mb-1">
    <a href="{{ $issue->jira_url }}" target="_blank" class="issue-key text-decoration-none">
      {{ $issue->issue_key }}
    </a>
    <span class="issue-type-icon bg-{{ $issue->issue_type_color }}" title="{{ $issue->issue_type }}">
      <i class="ti {{ $issue->issue_type_icon }} text-white"></i>
    </span>
  </div>
  <p class="issue-summary mb-2">{{ Str::limit($issue->summary, 80) }}</p>
  <div class="issue-meta">
    @if($issue->priority)
      <span class="badge bg-{{ $issue->priority_color }} badge-sm" style="font-size: 0.65rem;">
        {{ $issue->priority }}
      </span>
    @endif
    @if($issue->due_date)
      <span class="badge {{ $issue->isOverdue() ? 'bg-danger' : 'bg-light text-dark' }} badge-sm" style="font-size: 0.65rem;">
        <i class="ti ti-calendar me-1"></i>{{ $issue->due_date->format('M d') }}
      </span>
    @endif
    @if($issue->story_points)
      <span class="badge bg-light text-dark badge-sm" style="font-size: 0.65rem;">
        {{ $issue->story_points }} pts
      </span>
    @endif
    <div class="ms-auto">
      @if($issue->assignee)
        <span class="issue-assignee-avatar" title="{{ $issue->assignee->name }}">
          {{ substr($issue->assignee->name, 0, 2) }}
        </span>
      @elseif($issue->assignee_email)
        <span class="issue-assignee-avatar" title="{{ $issue->assignee_email }}">
          {{ strtoupper(substr(Str::before($issue->assignee_email, '@'), 0, 2)) }}
        </span>
      @endif
    </div>
  </div>
</div>
