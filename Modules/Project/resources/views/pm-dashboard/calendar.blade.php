@extends('layouts/layoutMaster')

@section('title', 'PM Calendar')

@section('vendor-style')
@vite([
    'resources/assets/vendor/libs/@fullcalendar/core/index.global.min.js',
])
<style>
    .fc-event {
        cursor: pointer;
    }
    .fc-event:hover {
        opacity: 0.8;
    }
    .fc .fc-daygrid-day.fc-day-today {
        background-color: rgba(105, 108, 255, 0.08);
    }
    .calendar-legend {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .calendar-legend-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .calendar-legend-color {
        width: 12px;
        height: 12px;
        border-radius: 3px;
    }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="ti ti-calendar me-2"></i>PM Calendar
            </h4>
            <p class="text-muted mb-0">View all project deadlines, follow-ups, and payments</p>
        </div>
        <a href="{{ route('projects.pm-dashboard.index') }}" class="btn btn-outline-primary">
            <i class="ti ti-arrow-left me-1"></i>Back to Dashboard
        </a>
    </div>

    <!-- Legend -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="calendar-legend">
                <div class="calendar-legend-item">
                    <span class="calendar-legend-color" style="background-color: #6f42c1;"></span>
                    <span>Follow-ups</span>
                </div>
                <div class="calendar-legend-item">
                    <span class="calendar-legend-color" style="background-color: #198754;"></span>
                    <span>Milestones (On Time)</span>
                </div>
                <div class="calendar-legend-item">
                    <span class="calendar-legend-color" style="background-color: #dc3545;"></span>
                    <span>Overdue Items</span>
                </div>
                <div class="calendar-legend-item">
                    <span class="calendar-legend-color" style="background-color: #0d6efd;"></span>
                    <span>Payments</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar -->
    <div class="card">
        <div class="card-body">
            <div id="pm-calendar"></div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('pm-calendar');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listMonth'
        },
        buttonText: {
            today: 'Today',
            month: 'Month',
            week: 'Week',
            list: 'List'
        },
        events: {
            url: '{{ route("projects.pm-dashboard.api.calendar-events") }}',
            failure: function() {
                console.error('Error loading calendar events');
            }
        },
        eventClick: function(info) {
            if (info.event.url) {
                window.location.href = info.event.url;
                info.jsEvent.preventDefault();
            }
        },
        eventDidMount: function(info) {
            // Add tooltip
            info.el.title = info.event.title;
        },
        loading: function(isLoading) {
            // Could add loading indicator here
        },
        height: 'auto',
        firstDay: 0, // Sunday
        dayMaxEvents: 3, // Show "+more" link when too many events
        moreLinkClick: 'day' // Show day view when clicking "+more"
    });

    calendar.render();
});
</script>
@endsection
