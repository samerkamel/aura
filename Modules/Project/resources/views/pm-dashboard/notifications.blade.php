@extends('layouts/layoutMaster')

@section('title', 'PM Notifications')

@section('vendor-style')
@vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="ti ti-bell me-2"></i>PM Notifications
            </h4>
            <p class="text-muted mb-0">View and manage your project notifications</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('projects.pm-dashboard.index') }}" class="btn btn-outline-primary">
                <i class="ti ti-arrow-left me-1"></i>Back to Dashboard
            </a>
            <button type="button" class="btn btn-primary" id="markAllRead">
                <i class="ti ti-mail-opened me-1"></i>Mark All Read
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('projects.pm-dashboard.notifications') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="all">All Types</option>
                        @foreach(\Modules\Project\Models\PMNotification::TYPES as $key => $label)
                            <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select" onchange="this.form.submit()">
                        <option value="all">All Priorities</option>
                        @foreach(\Modules\Project\Models\PMNotification::PRIORITIES as $key => $label)
                            <option value="{{ $key }}" {{ request('priority') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All</option>
                        <option value="unread" {{ request('status') == 'unread' ? 'selected' : '' }}>Unread</option>
                        <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>Read</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <a href="{{ route('projects.pm-dashboard.notifications') }}" class="btn btn-outline-secondary w-100">
                        <i class="ti ti-x me-1"></i>Clear Filters
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="card">
        <div class="card-body p-0">
            @forelse($notifications as $notification)
                <div class="notification-item d-flex align-items-start p-3 border-bottom {{ $notification->read_at ? 'bg-lighter' : '' }}"
                     data-id="{{ $notification->id }}">
                    <div class="flex-shrink-0 me-3">
                        <div class="avatar">
                            <span class="avatar-initial rounded-circle bg-label-{{ $notification->priority_color }}">
                                <i class="ti {{ $notification->type_icon }}"></i>
                            </span>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div>
                                <h6 class="mb-0 {{ $notification->read_at ? 'text-muted' : '' }}">
                                    {{ $notification->title }}
                                    @if(!$notification->read_at)
                                        <span class="badge bg-primary badge-dot ms-1"></span>
                                    @endif
                                </h6>
                                @if($notification->project)
                                    <small class="text-muted">
                                        <i class="ti ti-folder me-1"></i>{{ $notification->project->name }}
                                    </small>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-label-{{ $notification->priority_color }}">
                                    {{ ucfirst($notification->priority) }}
                                </span>
                                <div class="dropdown">
                                    <button class="btn btn-text-secondary btn-sm btn-icon dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @if(!$notification->read_at)
                                            <li>
                                                <a class="dropdown-item mark-read" href="javascript:void(0);"
                                                   data-id="{{ $notification->id }}">
                                                    <i class="ti ti-check me-2"></i>Mark as Read
                                                </a>
                                            </li>
                                        @endif
                                        @if($notification->action_url)
                                            <li>
                                                <a class="dropdown-item" href="{{ $notification->action_url }}">
                                                    <i class="ti ti-external-link me-2"></i>View Details
                                                </a>
                                            </li>
                                        @endif
                                        <li>
                                            <a class="dropdown-item text-danger dismiss-notification" href="javascript:void(0);"
                                               data-id="{{ $notification->id }}">
                                                <i class="ti ti-trash me-2"></i>Dismiss
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <p class="mb-1 {{ $notification->read_at ? 'text-muted' : '' }}">
                            {{ $notification->message }}
                        </p>
                        <div class="d-flex align-items-center gap-3">
                            <small class="text-muted">
                                <i class="ti ti-clock me-1"></i>{{ $notification->created_at->diffForHumans() }}
                            </small>
                            @if($notification->due_at)
                                <small class="{{ $notification->is_overdue ? 'text-danger' : 'text-muted' }}">
                                    <i class="ti ti-calendar me-1"></i>
                                    Due: {{ $notification->due_at->format('M d, Y') }}
                                    @if($notification->is_overdue)
                                        (Overdue)
                                    @endif
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="ti ti-bell-off text-muted mb-3" style="font-size: 4rem;"></i>
                    <h5 class="text-muted">No Notifications</h5>
                    <p class="text-muted mb-0">You're all caught up! No notifications match your filters.</p>
                </div>
            @endforelse
        </div>
        @if($notifications->hasPages())
            <div class="card-footer">
                {{ $notifications->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Mark all as read
    document.getElementById('markAllRead')?.addEventListener('click', function() {
        fetch('{{ route("projects.pm-dashboard.notifications.mark-all-read") }}', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the page to show updated state
                window.location.reload();
            }
        })
        .catch(error => console.error('Error:', error));
    });

    // Mark single notification as read
    document.querySelectorAll('.mark-read').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            fetch('{{ route("projects.pm-dashboard.notifications.mark-read") }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ notification_id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = document.querySelector(`.notification-item[data-id="${id}"]`);
                    if (item) {
                        item.classList.add('bg-lighter');
                        const title = item.querySelector('h6');
                        if (title) {
                            title.classList.add('text-muted');
                            const badge = title.querySelector('.badge-dot');
                            if (badge) badge.remove();
                        }
                        const message = item.querySelector('p');
                        if (message) message.classList.add('text-muted');
                    }
                    // Hide the mark as read option
                    this.closest('li').remove();
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });

    // Dismiss notification
    document.querySelectorAll('.dismiss-notification').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Are you sure you want to dismiss this notification?')) return;

            const id = this.dataset.id;
            fetch('{{ route("projects.pm-dashboard.notifications.dismiss") }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ notification_id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = document.querySelector(`.notification-item[data-id="${id}"]`);
                    if (item) {
                        item.style.transition = 'opacity 0.3s ease';
                        item.style.opacity = '0';
                        setTimeout(() => item.remove(), 300);
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
});
</script>
@endsection
