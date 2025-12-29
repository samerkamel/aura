@extends('layouts/layoutMaster')

@section('title', 'Missing Attendance Days')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/flatpickr/flatpickr.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    <!-- Header Card -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">
                    <i class="ti tabler-calendar-off me-2"></i>Missing Attendance Days
                </h5>
                <small class="text-muted">Working days with no check-ins from any employees</small>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <!-- Month Filter -->
                <select id="month-filter" class="form-select" style="width: auto;">
                    @foreach($months as $m => $monthName)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ $monthName }}</option>
                    @endforeach
                </select>

                <!-- Year Filter -->
                <select id="year-filter" class="form-select" style="width: auto;">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>

                <button type="button" class="btn btn-primary" onclick="applyFilters()">
                    <i class="ti tabler-filter me-1"></i>Filter
                </button>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ti tabler-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ti tabler-alert-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Results Card -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="ti tabler-list me-2"></i>
                @if(count($missingDates) > 0)
                    Found {{ count($missingDates) }} day(s) with no attendance
                @else
                    No missing attendance days found
                @endif
                <span class="badge bg-label-secondary ms-2">{{ $months[$month] }} {{ $year }}</span>
            </h6>
        </div>
        <div class="card-body">
            @if(count($missingDates) > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Day</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($missingDates as $missing)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2 bg-label-warning">
                                                <span class="avatar-initial rounded-circle">
                                                    <i class="ti tabler-calendar-event"></i>
                                                </span>
                                            </div>
                                            <span class="fw-medium">{{ $missing['formatted'] }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-info">{{ $missing['day_name'] }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <!-- Add as Holiday Button -->
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#addHolidayModal"
                                                    onclick="setHolidayDate('{{ $missing['date']->format('Y-m-d') }}', '{{ $missing['formatted'] }}')">
                                                <i class="ti tabler-calendar-plus me-1"></i>Add as Holiday
                                            </button>
                                            <!-- Set WFH for All Button -->
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#setWfhModal"
                                                    onclick="setWfhDate('{{ $missing['date']->format('Y-m-d') }}', '{{ $missing['formatted'] }}')">
                                                <i class="ti tabler-home me-1"></i>Set WFH for All
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <div class="avatar avatar-lg mb-3 bg-label-success">
                        <span class="avatar-initial rounded-circle">
                            <i class="ti tabler-check fs-3"></i>
                        </span>
                    </div>
                    <h6>All Clear!</h6>
                    <p class="text-muted mb-0">
                        All working days in {{ $months[$month] }} {{ $year }} have attendance records.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Info Card -->
    <div class="card mt-4">
        <div class="card-body">
            <div class="d-flex align-items-start">
                <div class="avatar avatar-sm me-3 bg-label-info">
                    <span class="avatar-initial rounded-circle">
                        <i class="ti tabler-info-circle"></i>
                    </span>
                </div>
                <div>
                    <h6 class="mb-1">About This Page</h6>
                    <p class="mb-0 text-muted small">
                        This page shows working days (excluding {{ implode(' & ', $weekendDays) }} and public holidays)
                        where no employees recorded any check-ins. Use the action buttons to either:
                    </p>
                    <ul class="mb-0 mt-2 text-muted small">
                        <li><strong>Add as Holiday:</strong> Mark the date as a public holiday (will affect all attendance calculations)</li>
                        <li><strong>Set WFH for All:</strong> Create Work From Home records for all active employees on that date</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Holiday Modal -->
<div class="modal fade" id="addHolidayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('attendance.missing-attendance.add-holiday') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ti tabler-calendar-plus me-2"></i>Add Public Holiday
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="date" id="holiday-date-input">

                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="text" class="form-control" id="holiday-date-display" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="holiday-name">Holiday Name <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control"
                               id="holiday-name"
                               name="name"
                               placeholder="e.g., Company Day Off"
                               required>
                        <div class="form-text">Enter a descriptive name for this holiday</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="ti tabler-calendar-plus me-1"></i>Add Holiday
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Set WFH Modal -->
<div class="modal fade" id="setWfhModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('attendance.missing-attendance.set-wfh') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ti tabler-home me-2"></i>Set WFH for All Employees
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="date" id="wfh-date-input">

                    <div class="alert alert-info mb-3">
                        <div class="d-flex">
                            <i class="ti tabler-info-circle me-2 mt-1"></i>
                            <div>
                                <strong>This will create WFH records</strong> for all active employees on the selected date.
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="text" class="form-control" id="wfh-date-display" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="wfh-notes">Notes (Optional)</label>
                        <textarea class="form-control"
                                  id="wfh-notes"
                                  name="notes"
                                  rows="2"
                                  placeholder="e.g., Office closed due to maintenance">Bulk WFH - No office attendance recorded</textarea>
                        <div class="form-text">This note will be added to all WFH records</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti tabler-home me-1"></i>Set WFH for All
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('page-script')
<script>
    function applyFilters() {
        const month = document.getElementById('month-filter').value;
        const year = document.getElementById('year-filter').value;
        window.location.href = `{{ route('attendance.missing-attendance.index') }}?month=${month}&year=${year}`;
    }

    function setHolidayDate(date, formatted) {
        document.getElementById('holiday-date-input').value = date;
        document.getElementById('holiday-date-display').value = formatted;
        document.getElementById('holiday-name').value = '';
        document.getElementById('holiday-name').focus();
    }

    function setWfhDate(date, formatted) {
        document.getElementById('wfh-date-input').value = date;
        document.getElementById('wfh-date-display').value = formatted;
    }

    // Allow filter on Enter key
    document.getElementById('month-filter').addEventListener('change', applyFilters);
    document.getElementById('year-filter').addEventListener('change', applyFilters);
</script>
@endsection
