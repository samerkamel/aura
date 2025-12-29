@extends('layouts/layoutMaster')

@section('title', 'Incomplete Attendance')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    <!-- Header Card -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0">
                    <i class="ti ti-user-exclamation me-2"></i>Incomplete Attendance
                </h5>
                <small class="text-muted">Employees with missing check-in or check-out on working days</small>
            </div>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <!-- Employee Filter -->
                <select id="employee-filter" class="select2 form-select" multiple data-placeholder="All Employees" style="width: 250px;">
                    @foreach($allEmployees as $emp)
                        <option value="{{ $emp->id }}" {{ in_array($emp->id, $selectedEmployeeIds) ? 'selected' : '' }}>{{ $emp->name }}</option>
                    @endforeach
                </select>

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

                <!-- Payroll Cycle Toggle -->
                <div class="form-check form-switch ms-2">
                    <input class="form-check-input" type="checkbox" id="payroll-cycle" {{ $usePayrollCycle ? 'checked' : '' }}>
                    <label class="form-check-label" for="payroll-cycle" title="26th to 25th">Payroll Cycle</label>
                </div>

                <button type="button" class="btn btn-primary" onclick="applyFilters()">
                    <i class="ti ti-filter me-1"></i>Filter
                </button>

                @if(!empty($selectedEmployeeIds))
                <button type="button" class="btn btn-outline-secondary" onclick="clearEmployeeFilter()">
                    <i class="ti ti-x me-1"></i>Clear
                </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ti ti-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ti ti-alert-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Bulk Actions -->
    @if(count($incompleteRecords) > 0)
    <div class="card mb-4" id="bulk-actions-card" style="display: none;">
        <div class="card-body py-3">
            <form action="{{ route('attendance.incomplete-attendance.bulk-leave') }}" method="POST" id="bulk-form">
                @csrf
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="text-muted">
                        <strong id="selected-count">0</strong> record(s) selected
                    </span>
                    <select name="leave_policy_id" class="form-select" style="width: auto;" required>
                        <option value="">Select Leave Type...</option>
                        @foreach($leavePolicies as $policy)
                            <option value="{{ $policy->id }}">{{ $policy->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="notes" class="form-control" style="width: 200px;" placeholder="Notes (optional)">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>Apply Leave to Selected
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Results Card -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="ti ti-list me-2"></i>
                @if(count($incompleteRecords) > 0)
                    Found {{ count($incompleteRecords) }} incomplete record(s)
                @else
                    No incomplete attendance found
                @endif
                <span class="badge bg-label-secondary ms-2">{{ $dateRangeDisplay }}</span>
                @if($usePayrollCycle)
                    <span class="badge bg-label-primary ms-2" title="26th to 25th">Payroll Cycle</span>
                @endif
                @if(!empty($selectedEmployeeIds))
                    <span class="badge bg-label-info ms-2">{{ count($selectedEmployeeIds) }} employee(s) filtered</span>
                @endif
            </h6>
            @if(count($incompleteRecords) > 0)
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="select-all">
                <label class="form-check-label" for="select-all">Select All</label>
            </div>
            @endif
        </div>
        <div class="card-body">
            @if(count($incompleteRecords) > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width: 40px;"></th>
                                <th>Employee</th>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Issue</th>
                                <th>Check-In</th>
                                <th>Check-Out</th>
                                <th class="text-center">Billable Hours</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($incompleteRecords as $record)
                                <tr>
                                    <td>
                                        <input type="checkbox"
                                               class="form-check-input record-checkbox"
                                               value="{{ $record['employee']->id }}|{{ $record['date']->format('Y-m-d') }}"
                                               form="bulk-form"
                                               name="records[]">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2 bg-label-primary">
                                                <span class="avatar-initial rounded-circle">
                                                    {{ strtoupper(substr($record['employee']->name, 0, 1)) }}
                                                </span>
                                            </div>
                                            <span class="fw-medium">{{ $record['employee']->name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $record['date_formatted'] }}</td>
                                    <td>
                                        <span class="badge bg-label-info">{{ $record['day_name'] }}</span>
                                    </td>
                                    <td>
                                        @if($record['issue'] === 'no_attendance')
                                            <span class="badge bg-danger">{{ $record['issue_label'] }}</span>
                                        @elseif($record['issue'] === 'no_check_in')
                                            <span class="badge bg-warning">{{ $record['issue_label'] }}</span>
                                        @else
                                            <span class="badge bg-warning">{{ $record['issue_label'] }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($record['check_in'])
                                            <span class="text-success">{{ $record['check_in'] }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($record['check_out'])
                                            <span class="text-success">{{ $record['check_out'] }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($record['billable_hours'] > 0)
                                            <span class="badge bg-success" title="Has billable hours - likely WFH">
                                                {{ number_format($record['billable_hours'], 1) }}h
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="ti ti-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><h6 class="dropdown-header">Add Leave</h6></li>
                                                @foreach($leavePolicies as $policy)
                                                <li>
                                                    <a class="dropdown-item" href="#"
                                                       onclick="addLeave({{ $record['employee']->id }}, '{{ $record['date']->format('Y-m-d') }}', {{ $policy->id }}, '{{ $policy->name }}', '{{ $record['employee']->name }}', '{{ $record['date_formatted'] }}')">
                                                        @if($policy->type === 'pto')
                                                            <i class="ti ti-beach me-2 text-info"></i>
                                                        @elseif($policy->type === 'sick_leave')
                                                            <i class="ti ti-medical-cross me-2 text-danger"></i>
                                                        @else
                                                            <i class="ti ti-calendar-event me-2 text-warning"></i>
                                                        @endif
                                                        {{ $policy->name }}
                                                    </a>
                                                </li>
                                                @endforeach
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="#"
                                                       onclick="addWfh({{ $record['employee']->id }}, '{{ $record['date']->format('Y-m-d') }}', '{{ $record['employee']->name }}', '{{ $record['date_formatted'] }}')">
                                                        <i class="ti ti-home me-2 text-primary"></i>Work From Home
                                                    </a>
                                                </li>
                                            </ul>
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
                            <i class="ti ti-check fs-3"></i>
                        </span>
                    </div>
                    <h6>All Clear!</h6>
                    <p class="text-muted mb-0">
                        No incomplete attendance records found for {{ $months[$month] }} {{ $year }}.
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
                        <i class="ti ti-info-circle"></i>
                    </span>
                </div>
                <div>
                    <h6 class="mb-1">About This Page</h6>
                    <p class="mb-0 text-muted small">
                        This page shows employees who have incomplete attendance on working days
                        (excluding {{ implode(' & ', $weekendDays) }}, public holidays, approved leave, and WFH days).
                        Issues detected:
                    </p>
                    <ul class="mb-0 mt-2 text-muted small">
                        <li><span class="badge bg-danger">No Attendance</span> - Employee didn't check in or out at all</li>
                        <li><span class="badge bg-warning">Missing Check-In</span> - Employee has check-out but no check-in</li>
                        <li><span class="badge bg-warning">Missing Check-Out</span> - Employee has check-in but no check-out</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Leave Modal -->
<div class="modal fade" id="addLeaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('attendance.incomplete-attendance.add-leave') }}" method="POST">
                @csrf
                <input type="hidden" name="employee_id" id="leave-employee-id">
                <input type="hidden" name="date" id="leave-date">
                <input type="hidden" name="leave_policy_id" id="leave-policy-id">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ti ti-calendar-plus me-2"></i>Add <span id="leave-type-name">Leave</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <input type="text" class="form-control" id="leave-employee-name" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="text" class="form-control" id="leave-date-display" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="leave-notes">Notes (Optional)</label>
                        <textarea class="form-control" id="leave-notes" name="notes" rows="2" placeholder="e.g., Personal day off"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>Add Leave
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add WFH Modal -->
<div class="modal fade" id="addWfhModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('attendance.incomplete-attendance.add-wfh') }}" method="POST">
                @csrf
                <input type="hidden" name="employee_id" id="wfh-employee-id">
                <input type="hidden" name="date" id="wfh-date">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ti ti-home me-2"></i>Add Work From Home
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <input type="text" class="form-control" id="wfh-employee-name" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="text" class="form-control" id="wfh-date-display" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="wfh-notes">Notes (Optional)</label>
                        <textarea class="form-control" id="wfh-notes" name="notes" rows="2" placeholder="e.g., Working remotely">Added from incomplete attendance review</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-home me-1"></i>Add WFH
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('page-script')
<script>
    // Initialize Select2 when document is ready
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('#employee-filter').select2({
                placeholder: 'All Employees',
                allowClear: true,
                width: '250px'
            });
        }
    });

    // Fallback initialization after a short delay
    setTimeout(function() {
        if (typeof $ !== 'undefined' && $.fn.select2 && !$('#employee-filter').hasClass('select2-hidden-accessible')) {
            $('#employee-filter').select2({
                placeholder: 'All Employees',
                allowClear: true,
                width: '250px'
            });
        }
    }, 500);

    function applyFilters() {
        const month = document.getElementById('month-filter').value;
        const year = document.getElementById('year-filter').value;
        const payrollCycle = document.getElementById('payroll-cycle').checked ? '1' : '0';
        const employees = $('#employee-filter').val() || [];

        let url = `{{ route('attendance.incomplete-attendance.index') }}?month=${month}&year=${year}&payroll_cycle=${payrollCycle}`;

        if (employees.length > 0) {
            employees.forEach(empId => {
                url += `&employees[]=${empId}`;
            });
        }

        window.location.href = url;
    }

    function clearEmployeeFilter() {
        const month = document.getElementById('month-filter').value;
        const year = document.getElementById('year-filter').value;
        const payrollCycle = document.getElementById('payroll-cycle').checked ? '1' : '0';
        window.location.href = `{{ route('attendance.incomplete-attendance.index') }}?month=${month}&year=${year}&payroll_cycle=${payrollCycle}`;
    }

    function addLeave(employeeId, date, policyId, policyName, employeeName, dateFormatted) {
        document.getElementById('leave-employee-id').value = employeeId;
        document.getElementById('leave-date').value = date;
        document.getElementById('leave-policy-id').value = policyId;
        document.getElementById('leave-type-name').textContent = policyName;
        document.getElementById('leave-employee-name').value = employeeName;
        document.getElementById('leave-date-display').value = dateFormatted;
        document.getElementById('leave-notes').value = '';

        const modal = new bootstrap.Modal(document.getElementById('addLeaveModal'));
        modal.show();
    }

    function addWfh(employeeId, date, employeeName, dateFormatted) {
        document.getElementById('wfh-employee-id').value = employeeId;
        document.getElementById('wfh-date').value = date;
        document.getElementById('wfh-employee-name').value = employeeName;
        document.getElementById('wfh-date-display').value = dateFormatted;
        document.getElementById('wfh-notes').value = 'Added from incomplete attendance review';

        const modal = new bootstrap.Modal(document.getElementById('addWfhModal'));
        modal.show();
    }

    // Auto-apply filters on change
    document.getElementById('month-filter').addEventListener('change', applyFilters);
    document.getElementById('year-filter').addEventListener('change', applyFilters);

    // Checkbox functionality for bulk actions
    const selectAllCheckbox = document.getElementById('select-all');
    const recordCheckboxes = document.querySelectorAll('.record-checkbox');
    const bulkActionsCard = document.getElementById('bulk-actions-card');
    const selectedCountSpan = document.getElementById('selected-count');

    function updateBulkActionsVisibility() {
        const checkedCount = document.querySelectorAll('.record-checkbox:checked').length;
        if (bulkActionsCard) {
            bulkActionsCard.style.display = checkedCount > 0 ? 'block' : 'none';
        }
        if (selectedCountSpan) {
            selectedCountSpan.textContent = checkedCount;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            recordCheckboxes.forEach(cb => cb.checked = this.checked);
            updateBulkActionsVisibility();
        });
    }

    recordCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const allChecked = document.querySelectorAll('.record-checkbox:checked').length === recordCheckboxes.length;
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allChecked;
            }
            updateBulkActionsVisibility();
        });
    });
</script>
@endsection
