@extends('layouts/layoutMaster')

@section('title', 'Report Preview')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="ti tabler-report-analytics me-2"></i>Report Preview
            <small class="text-muted ms-2">
              {{ $startDate->format('M d, Y') }} - {{ $endDate->format('M d, Y') }}
            </small>
          </h5>
          <a href="{{ route('projects.reports.create') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti tabler-arrow-left me-1"></i>Change Dates
          </a>
        </div>
        <div class="card-body">
          @if (session('error'))
            <div class="alert alert-danger alert-dismissible" role="alert">
              {{ session('error') }}
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @endif

          <form action="{{ route('projects.reports.store') }}" method="POST" id="reportForm">
            @csrf
            <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
            <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">

            <div class="alert alert-info mb-4">
              <i class="ti tabler-info-circle me-2"></i>
              <strong>Edit rates below</strong> - Rates are automatically filled for repeated employees.
              @if($canEditHours)
                <br><strong>Super Admin:</strong> You can also edit hours and add custom projects.
              @endif
              Click "Save Report" when done to save this report for future reference and exports.
            </div>

            @if(!empty($reportData['projects']))
              @foreach($reportData['projects'] as $projectIndex => $project)
                <div class="card mb-4 border project-card" data-project-index="{{ $projectIndex }}">
                  <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                      <span class="badge bg-label-primary me-2">{{ $project['project_code'] }}</span>
                      {{ $project['project_name'] }}
                    </h6>
                    <span class="badge bg-secondary project-hours-total" data-project="{{ $projectIndex }}">
                      {{ number_format($project['total_hours'], 2) }} hrs
                    </span>
                  </div>
                  <div class="card-body p-0">
                    <div class="table-responsive">
                      <table class="table table-bordered mb-0">
                        <thead class="table-light">
                          <tr>
                            <th style="width: 30%">Employee</th>
                            <th style="width: 15%">Team</th>
                            <th class="text-end" style="width: 12%">Hours</th>
                            <th style="width: 18%">Rate (EGP/hr)</th>
                            <th class="text-end" style="width: 15%">Amount</th>
                          </tr>
                        </thead>
                        <tbody>
                          @foreach($project['employees'] as $employee)
                            @php
                              $rateKey = $project['project_id'] . '_' . $employee['employee_id'];
                            @endphp
                            <tr class="employee-row" data-team="{{ $employee['team'] ?? '' }}">
                              <td>{{ $employee['employee_name'] }}</td>
                              <td>
                                <select class="form-select form-select-sm team-select"
                                        name="teams[{{ $rateKey }}]"
                                        data-employee="{{ $employee['employee_id'] }}">
                                  <option value="">--</option>
                                  @foreach(\Modules\HR\Models\Employee::TEAMS as $teamKey => $teamLabel)
                                    <option value="{{ $teamKey }}" {{ ($employee['team'] ?? '') == $teamKey ? 'selected' : '' }}>
                                      {{ $teamLabel }}
                                    </option>
                                  @endforeach
                                </select>
                              </td>
                              <td class="text-end">
                                @if($canEditHours)
                                  <input type="number" class="form-control form-control-sm hours-input"
                                         name="hours[{{ $rateKey }}]"
                                         value="{{ $employee['hours'] }}"
                                         data-employee="{{ $employee['employee_id'] }}"
                                         data-project="{{ $projectIndex }}"
                                         step="0.01" min="0">
                                @else
                                  <strong class="hours-display">{{ number_format($employee['hours'], 2) }}</strong>
                                @endif
                              </td>
                              <td>
                                <input type="number" class="form-control form-control-sm rate-input"
                                       name="rates[{{ $rateKey }}]"
                                       value="{{ $employee['rate'] }}"
                                       data-hours="{{ $employee['hours'] }}"
                                       data-employee="{{ $employee['employee_id'] }}"
                                       data-project="{{ $projectIndex }}"
                                       step="0.01" min="0">
                              </td>
                              <td class="text-end">
                                <strong class="amount-display">{{ number_format($employee['amount'], 2) }} EGP</strong>
                              </td>
                            </tr>
                          @endforeach
                        </tbody>
                        <tfoot class="table-light">
                          <tr>
                            <td class="text-end"><strong>Project Total:</strong></td>
                            <td></td>
                            <td class="text-end">
                              <strong class="project-hours" data-project="{{ $projectIndex }}">{{ number_format($project['total_hours'], 2) }}</strong>
                            </td>
                            <td class="text-end">
                              <strong class="project-avg-rate" data-project="{{ $projectIndex }}">
                                @php
                                  $avgRate = $project['total_hours'] > 0 ? $project['total_amount'] / $project['total_hours'] : 0;
                                @endphp
                                {{ number_format($avgRate, 2) }} EGP/hr
                              </strong>
                            </td>
                            <td class="text-end">
                              <strong class="project-amount" data-project="{{ $projectIndex }}">
                                {{ number_format($project['total_amount'], 2) }} EGP
                              </strong>
                            </td>
                          </tr>
                        </tfoot>
                      </table>
                    </div>
                  </div>
                </div>
              @endforeach
            @else
              <div class="alert alert-warning mb-4">
                <i class="ti tabler-alert-triangle me-2"></i>
                No worklog data found for the selected date range. You can still add custom projects below.
              </div>
            @endif

            <!-- Custom Projects Section -->
            @if($canEditHours)
              <div class="card mb-4 border border-success">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                  <h6 class="mb-0">
                    <i class="ti tabler-plus me-2"></i>Add Custom Project
                  </h6>
                  <button type="button" class="btn btn-light btn-sm" id="addProjectBtn">
                    <i class="ti tabler-plus me-1"></i>Add Project
                  </button>
                </div>
                <div class="card-body" id="customProjectsContainer">
                  <p class="text-muted mb-0" id="noCustomProjects">
                    Click "Add Project" to add a project that has no logged hours in Jira.
                  </p>
                </div>
              </div>
            @endif

            <!-- Team Summary -->
            <div class="card mb-4 border border-info">
              <div class="card-header bg-info text-white">
                <h6 class="mb-0">
                  <i class="ti tabler-users-group me-2"></i>Summary by Team
                </h6>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-bordered mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Team</th>
                        <th class="text-end">Hours</th>
                        <th class="text-end">Amount (EGP)</th>
                      </tr>
                    </thead>
                    <tbody id="teamSummaryBody">
                      @foreach(\Modules\HR\Models\Employee::TEAMS as $teamKey => $teamLabel)
                        <tr data-team="{{ $teamKey }}">
                          <td><strong>{{ $teamLabel }}</strong></td>
                          <td class="text-end team-hours">0.00</td>
                          <td class="text-end team-amount">0.00</td>
                        </tr>
                      @endforeach
                      <tr data-team="">
                        <td><em class="text-muted">Unassigned</em></td>
                        <td class="text-end team-hours">0.00</td>
                        <td class="text-end team-amount">0.00</td>
                      </tr>
                    </tbody>
                    <tfoot class="table-light">
                      <tr>
                        <td><strong>Total</strong></td>
                        <td class="text-end"><strong id="teamSummaryTotalHours">0.00</strong></td>
                        <td class="text-end"><strong id="teamSummaryTotalAmount">0.00</strong></td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>

            <!-- Grand Total -->
            <div class="card border-primary">
              <div class="card-body">
                <div class="row align-items-center">
                  <div class="col-md-3">
                    <h5 class="mb-0">Grand Total</h5>
                  </div>
                  <div class="col-md-3 text-end">
                    <h5 class="mb-0">
                      <span id="grandTotalHours">{{ number_format($reportData['total_hours'], 2) }}</span> hours
                    </h5>
                  </div>
                  <div class="col-md-3 text-end">
                    <h5 class="mb-0 text-muted">
                      @php
                        $grandAvgRate = $reportData['total_hours'] > 0 ? $reportData['total_amount'] / $reportData['total_hours'] : 0;
                      @endphp
                      <span id="grandAvgRate">{{ number_format($grandAvgRate, 2) }}</span> EGP/hr
                    </h5>
                  </div>
                  <div class="col-md-3 text-end">
                    <h4 class="mb-0 text-primary">
                      <span id="grandTotalAmount">{{ number_format($reportData['total_amount'], 2) }}</span> EGP
                    </h4>
                  </div>
                </div>
              </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between">
              <a href="{{ route('projects.reports.create') }}" class="btn btn-outline-secondary">
                <i class="ti tabler-arrow-left me-1"></i>Back to Date Selection
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="ti tabler-device-floppy me-1"></i>Save Report
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Custom Project Template -->
<template id="customProjectTemplate">
  <div class="custom-project-card card mb-3 border border-success">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center flex-grow-1 me-3">
        <div class="form-check form-switch me-3">
          <input class="form-check-input project-type-switch" type="checkbox" id="projectType_INDEX">
          <label class="form-check-label" for="projectType_INDEX">Custom Name</label>
        </div>
        <div class="project-select-wrapper flex-grow-1" style="display: block;">
          <select class="form-select form-select-sm project-select" name="custom_projects[INDEX][project_id]">
            <option value="">-- Select Project --</option>
            @foreach($allProjects as $proj)
              <option value="{{ $proj->id }}">{{ $proj->code }} - {{ $proj->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="project-name-wrapper flex-grow-1" style="display: none;">
          <input type="text" class="form-control form-control-sm project-name-input"
                 name="custom_projects[INDEX][name]" placeholder="Enter project name">
        </div>
      </div>
      <button type="button" class="btn btn-outline-danger btn-sm remove-project-btn">
        <i class="ti tabler-trash"></i>
      </button>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered mb-0">
          <thead class="table-light">
            <tr>
              <th style="width: 28%">Employee</th>
              <th style="width: 13%">Team</th>
              <th class="text-end" style="width: 12%">Hours</th>
              <th style="width: 17%">Rate (EGP/hr)</th>
              <th class="text-end" style="width: 15%">Amount</th>
              <th style="width: 5%"></th>
            </tr>
          </thead>
          <tbody class="custom-employees-tbody">
            <!-- Employee rows will be added here -->
          </tbody>
          <tfoot class="table-light">
            <tr>
              <td colspan="6">
                <button type="button" class="btn btn-outline-success btn-sm add-employee-btn">
                  <i class="ti tabler-plus me-1"></i>Add Employee
                </button>
              </td>
            </tr>
            <tr>
              <td class="text-end"><strong>Project Total:</strong></td>
              <td></td>
              <td class="text-end">
                <strong class="custom-project-hours">0.00</strong>
              </td>
              <td class="text-end">
                <strong class="custom-project-avg-rate">0.00 EGP/hr</strong>
              </td>
              <td class="text-end">
                <strong class="custom-project-amount">0.00 EGP</strong>
              </td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</template>

<!-- Custom Employee Row Template -->
<template id="customEmployeeTemplate">
  <tr class="custom-employee-row">
    <td>
      <div class="d-flex align-items-center">
        <div class="form-check form-switch me-2">
          <input class="form-check-input employee-type-switch" type="checkbox" id="empType_PROJ_EMP">
          <label class="form-check-label small" for="empType_PROJ_EMP">Custom</label>
        </div>
        <div class="employee-select-wrapper flex-grow-1" style="display: block;">
          <select class="form-select form-select-sm employee-select"
                  name="custom_projects[PROJ][employees][EMP][employee_id]">
            <option value="">-- Select --</option>
            @foreach($allEmployees as $emp)
              <option value="{{ $emp->id }}" data-team="{{ $emp->team }}">{{ $emp->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="employee-name-wrapper flex-grow-1" style="display: none;">
          <input type="text" class="form-control form-control-sm employee-name-input"
                 name="custom_projects[PROJ][employees][EMP][name]" placeholder="Name">
        </div>
      </div>
    </td>
    <td>
      <select class="form-select form-select-sm custom-team-select"
              name="custom_projects[PROJ][employees][EMP][team]">
        <option value="">--</option>
        @foreach(\Modules\HR\Models\Employee::TEAMS as $teamKey => $teamLabel)
          <option value="{{ $teamKey }}">{{ $teamLabel }}</option>
        @endforeach
      </select>
    </td>
    <td>
      <input type="number" class="form-control form-control-sm custom-hours-input"
             name="custom_projects[PROJ][employees][EMP][hours]"
             value="0" step="0.01" min="0">
    </td>
    <td>
      <input type="number" class="form-control form-control-sm custom-rate-input"
             name="custom_projects[PROJ][employees][EMP][rate]"
             value="0" step="0.01" min="0">
    </td>
    <td class="text-end">
      <strong class="custom-amount-display">0.00 EGP</strong>
    </td>
    <td class="text-center">
      <button type="button" class="btn btn-outline-danger btn-sm remove-employee-btn">
        <i class="ti tabler-x"></i>
      </button>
    </td>
  </tr>
</template>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const canEditHours = {{ $canEditHours ? 'true' : 'false' }};
  const rateInputs = document.querySelectorAll('.rate-input');
  const hoursInputs = document.querySelectorAll('.hours-input');
  const employeeRates = {};
  let customProjectIndex = 0;

  // Helper function to format numbers with thousand separators
  function formatNumber(num, decimals = 2) {
    return num.toLocaleString('en-US', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    });
  }

  // Initialize rates from existing values
  rateInputs.forEach(function(input) {
    const employeeId = input.dataset.employee;
    const rate = parseFloat(input.value) || 0;
    if (rate > 0 && !employeeRates[employeeId]) {
      employeeRates[employeeId] = rate;
    }
  });

  // Add event listeners for team dropdowns
  document.querySelectorAll('.team-select').forEach(function(select) {
    select.addEventListener('change', function() {
      recalculateTotals();
    });
  });

  // Handle rate changes
  rateInputs.forEach(function(input) {
    // Use 'change' event for auto-fill (fires when user finishes editing)
    input.addEventListener('change', function() {
      handleRateChangeWithAutoFill(this);
    });
    // Use 'input' event only for real-time amount calculation (no auto-fill)
    input.addEventListener('input', function() {
      handleRateChangeAmountOnly(this);
    });
  });

  // Calculate amount for current row only (real-time as user types)
  function handleRateChangeAmountOnly(input) {
    const newRate = parseFloat(input.value) || 0;
    const row = input.closest('tr');

    // Get hours from input or data attribute
    let hours;
    if (canEditHours) {
      const hoursInput = row.querySelector('.hours-input');
      hours = hoursInput ? parseFloat(hoursInput.value) || 0 : parseFloat(input.dataset.hours) || 0;
    } else {
      hours = parseFloat(input.dataset.hours) || 0;
    }

    // Update amount display
    const amountDisplay = row.querySelector('.amount-display');
    const amount = hours * newRate;
    amountDisplay.textContent = formatNumber(amount) + ' EGP';

    recalculateTotals();
  }

  // Handle rate change with auto-fill (fires when user finishes editing)
  function handleRateChangeWithAutoFill(input) {
    const employeeId = input.dataset.employee;
    const newRate = parseFloat(input.value) || 0;
    const row = input.closest('tr');

    // Get hours from input or data attribute
    let hours;
    if (canEditHours) {
      const hoursInput = row.querySelector('.hours-input');
      hours = hoursInput ? parseFloat(hoursInput.value) || 0 : parseFloat(input.dataset.hours) || 0;
    } else {
      hours = parseFloat(input.dataset.hours) || 0;
    }

    // Update amount display
    const amountDisplay = row.querySelector('.amount-display');
    const amount = hours * newRate;
    amountDisplay.textContent = formatNumber(amount) + ' EGP';

    // Store and auto-fill rate for same employee
    if (newRate > 0) {
      employeeRates[employeeId] = newRate;

      // Auto-fill empty rates for same employee (only on change event)
      rateInputs.forEach(function(otherInput) {
        if (otherInput !== input &&
            otherInput.dataset.employee === employeeId &&
            (parseFloat(otherInput.value) === 0 || otherInput.value === '')) {
          otherInput.value = newRate;
          // Trigger calculation for auto-filled input
          const otherRow = otherInput.closest('tr');
          const otherAmountDisplay = otherRow.querySelector('.amount-display');
          let otherHours;
          if (canEditHours) {
            const otherHoursInput = otherRow.querySelector('.hours-input');
            otherHours = otherHoursInput ? parseFloat(otherHoursInput.value) || 0 : parseFloat(otherInput.dataset.hours) || 0;
          } else {
            otherHours = parseFloat(otherInput.dataset.hours) || 0;
          }
          otherAmountDisplay.textContent = formatNumber(otherHours * newRate) + ' EGP';
        }
      });
    }

    recalculateTotals();
  }

  // Handle hours changes (for super admin)
  if (canEditHours) {
    hoursInputs.forEach(function(input) {
      input.addEventListener('change', function() {
        handleHoursChange(this);
      });
      input.addEventListener('input', function() {
        handleHoursChange(this);
      });
    });
  }

  function handleHoursChange(input) {
    const row = input.closest('tr');
    const rateInput = row.querySelector('.rate-input');
    const hours = parseFloat(input.value) || 0;
    const rate = parseFloat(rateInput.value) || 0;

    // Update the rate input's data-hours attribute
    rateInput.dataset.hours = hours;

    // Update amount display
    const amountDisplay = row.querySelector('.amount-display');
    const amount = hours * rate;
    amountDisplay.textContent = formatNumber(amount) + ' EGP';

    recalculateTotals();
  }

  function recalculateTotals() {
    let grandTotalHours = 0;
    let grandTotalAmount = 0;

    // Team summary tracking
    const teamTotals = {};

    // Calculate totals for existing projects
    const projectCards = document.querySelectorAll('.project-card');
    projectCards.forEach(function(card) {
      const projectIndex = card.dataset.projectIndex;
      let projectHours = 0;
      let projectAmount = 0;

      card.querySelectorAll('.employee-row').forEach(function(row) {
        let hours;
        if (canEditHours) {
          const hoursInput = row.querySelector('.hours-input');
          hours = hoursInput ? parseFloat(hoursInput.value) || 0 : 0;
        } else {
          const hoursDisplay = row.querySelector('.hours-display');
          hours = hoursDisplay ? parseFloat(hoursDisplay.textContent.replace(/,/g, '')) || 0 : 0;
        }

        const rateInput = row.querySelector('.rate-input');
        const rate = rateInput ? parseFloat(rateInput.value) || 0 : 0;
        const amount = hours * rate;

        // Get team from dropdown
        const teamSelect = row.querySelector('.team-select');
        const team = teamSelect ? teamSelect.value : '';

        // Track team totals
        if (!teamTotals[team]) {
          teamTotals[team] = { hours: 0, amount: 0 };
        }
        teamTotals[team].hours += hours;
        teamTotals[team].amount += amount;

        projectHours += hours;
        projectAmount += amount;
      });

      // Update project totals
      const projectHoursEl = card.querySelector('.project-hours');
      const projectAmountEl = card.querySelector('.project-amount');
      const projectHoursTotalEl = card.querySelector('.project-hours-total');
      const projectAvgRateEl = card.querySelector('.project-avg-rate');

      if (projectHoursEl) projectHoursEl.textContent = formatNumber(projectHours);
      if (projectAmountEl) projectAmountEl.textContent = formatNumber(projectAmount) + ' EGP';
      if (projectHoursTotalEl) projectHoursTotalEl.textContent = formatNumber(projectHours) + ' hrs';

      // Calculate and update average rate
      const avgRate = projectHours > 0 ? projectAmount / projectHours : 0;
      if (projectAvgRateEl) projectAvgRateEl.textContent = avgRate.toFixed(2) + ' EGP/hr';

      grandTotalHours += projectHours;
      grandTotalAmount += projectAmount;
    });

    // Calculate totals for custom projects
    const customProjects = document.querySelectorAll('.custom-project-card');
    customProjects.forEach(function(card) {
      let projectHours = 0;
      let projectAmount = 0;

      card.querySelectorAll('.custom-employee-row').forEach(function(row) {
        const hours = parseFloat(row.querySelector('.custom-hours-input').value) || 0;
        const rate = parseFloat(row.querySelector('.custom-rate-input').value) || 0;
        const amount = hours * rate;

        // Get team from dropdown
        const teamSelect = row.querySelector('.custom-team-select');
        const team = teamSelect ? teamSelect.value : '';

        // Track team totals
        if (!teamTotals[team]) {
          teamTotals[team] = { hours: 0, amount: 0 };
        }
        teamTotals[team].hours += hours;
        teamTotals[team].amount += amount;

        projectHours += hours;
        projectAmount += amount;
      });

      // Update custom project totals
      card.querySelector('.custom-project-hours').textContent = formatNumber(projectHours);
      card.querySelector('.custom-project-amount').textContent = formatNumber(projectAmount) + ' EGP';

      // Calculate and update average rate for custom projects
      const customAvgRate = projectHours > 0 ? projectAmount / projectHours : 0;
      card.querySelector('.custom-project-avg-rate').textContent = formatNumber(customAvgRate) + ' EGP/hr';

      grandTotalHours += projectHours;
      grandTotalAmount += projectAmount;
    });

    // Update team summary
    let teamSummaryTotalHours = 0;
    let teamSummaryTotalAmount = 0;
    const teamSummaryBody = document.getElementById('teamSummaryBody');
    if (teamSummaryBody) {
      teamSummaryBody.querySelectorAll('tr[data-team]').forEach(function(row) {
        const team = row.dataset.team;
        const data = teamTotals[team] || { hours: 0, amount: 0 };
        row.querySelector('.team-hours').textContent = formatNumber(data.hours);
        row.querySelector('.team-amount').textContent = formatNumber(data.amount);
        teamSummaryTotalHours += data.hours;
        teamSummaryTotalAmount += data.amount;
      });

      const totalHoursEl = document.getElementById('teamSummaryTotalHours');
      const totalAmountEl = document.getElementById('teamSummaryTotalAmount');
      if (totalHoursEl) totalHoursEl.textContent = formatNumber(teamSummaryTotalHours);
      if (totalAmountEl) totalAmountEl.textContent = formatNumber(teamSummaryTotalAmount);
    }

    // Update grand totals
    document.getElementById('grandTotalHours').textContent = formatNumber(grandTotalHours);
    document.getElementById('grandTotalAmount').textContent = formatNumber(grandTotalAmount);

    // Calculate and update grand average rate
    const grandAvgRate = grandTotalHours > 0 ? grandTotalAmount / grandTotalHours : 0;
    document.getElementById('grandAvgRate').textContent = formatNumber(grandAvgRate);
  }

  // Custom Projects functionality
  if (canEditHours) {
    const addProjectBtn = document.getElementById('addProjectBtn');
    const customProjectsContainer = document.getElementById('customProjectsContainer');
    const noCustomProjects = document.getElementById('noCustomProjects');
    const projectTemplate = document.getElementById('customProjectTemplate');
    const employeeTemplate = document.getElementById('customEmployeeTemplate');

    addProjectBtn.addEventListener('click', function() {
      noCustomProjects.style.display = 'none';

      const projectHtml = projectTemplate.innerHTML
        .replace(/INDEX/g, customProjectIndex)
        .replace(/projectType_INDEX/g, 'projectType_' + customProjectIndex);

      const tempDiv = document.createElement('div');
      tempDiv.innerHTML = projectHtml;
      const projectCard = tempDiv.firstElementChild;

      customProjectsContainer.appendChild(projectCard);

      // Initialize event listeners for new project
      initProjectCard(projectCard, customProjectIndex);

      // Auto-add first employee
      addEmployeeToProject(projectCard, customProjectIndex, 0);

      customProjectIndex++;
    });

    function initProjectCard(card, projectIdx) {
      // Remove project button
      card.querySelector('.remove-project-btn').addEventListener('click', function() {
        card.remove();
        if (customProjectsContainer.querySelectorAll('.custom-project-card').length === 0) {
          noCustomProjects.style.display = 'block';
        }
        recalculateTotals();
      });

      // Project type switch (existing vs custom name)
      const projectTypeSwitch = card.querySelector('.project-type-switch');
      const projectSelectWrapper = card.querySelector('.project-select-wrapper');
      const projectNameWrapper = card.querySelector('.project-name-wrapper');

      projectTypeSwitch.addEventListener('change', function() {
        if (this.checked) {
          projectSelectWrapper.style.display = 'none';
          projectNameWrapper.style.display = 'block';
          card.querySelector('.project-select').value = '';
        } else {
          projectSelectWrapper.style.display = 'block';
          projectNameWrapper.style.display = 'none';
          card.querySelector('.project-name-input').value = '';
        }
      });

      // Update hidden name field when project is selected
      card.querySelector('.project-select').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption && selectedOption.value) {
          card.querySelector('.project-name-input').value = selectedOption.text;
        }
      });

      // Add employee button
      let employeeIdx = 0;
      card.querySelector('.add-employee-btn').addEventListener('click', function() {
        addEmployeeToProject(card, projectIdx, employeeIdx);
        employeeIdx++;
      });
    }

    function addEmployeeToProject(card, projectIdx, employeeIdx) {
      const tbody = card.querySelector('.custom-employees-tbody');

      const employeeHtml = employeeTemplate.innerHTML
        .replace(/PROJ/g, projectIdx)
        .replace(/EMP/g, employeeIdx)
        .replace(/empType_PROJ_EMP/g, 'empType_' + projectIdx + '_' + employeeIdx);

      // Use tbody element to parse tr elements correctly
      const tempTbody = document.createElement('tbody');
      tempTbody.innerHTML = employeeHtml;
      const employeeRow = tempTbody.firstElementChild;

      tbody.appendChild(employeeRow);

      initEmployeeRow(employeeRow);
    }

    function initEmployeeRow(row) {
      // Remove employee button
      row.querySelector('.remove-employee-btn').addEventListener('click', function() {
        row.remove();
        recalculateTotals();
      });

      // Employee type switch (existing vs custom name)
      const employeeTypeSwitch = row.querySelector('.employee-type-switch');
      const employeeSelectWrapper = row.querySelector('.employee-select-wrapper');
      const employeeNameWrapper = row.querySelector('.employee-name-wrapper');

      employeeTypeSwitch.addEventListener('change', function() {
        if (this.checked) {
          employeeSelectWrapper.style.display = 'none';
          employeeNameWrapper.style.display = 'block';
          row.querySelector('.employee-select').value = '';
        } else {
          employeeSelectWrapper.style.display = 'block';
          employeeNameWrapper.style.display = 'none';
          row.querySelector('.employee-name-input').value = '';
        }
      });

      // Update name and team when employee is selected
      row.querySelector('.employee-select').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption && selectedOption.value) {
          row.querySelector('.employee-name-input').value = selectedOption.text;
          // Auto-set team from employee's profile
          const employeeTeam = selectedOption.dataset.team || '';
          const teamSelect = row.querySelector('.custom-team-select');
          if (teamSelect) {
            teamSelect.value = employeeTeam;
          }
          recalculateTotals();
        }
      });

      // Team change handler
      const teamSelect = row.querySelector('.custom-team-select');
      if (teamSelect) {
        teamSelect.addEventListener('change', function() {
          recalculateTotals();
        });
      }

      // Hours and rate input handlers
      const hoursInput = row.querySelector('.custom-hours-input');
      const rateInput = row.querySelector('.custom-rate-input');
      const amountDisplay = row.querySelector('.custom-amount-display');

      function updateAmount() {
        const hours = parseFloat(hoursInput.value) || 0;
        const rate = parseFloat(rateInput.value) || 0;
        amountDisplay.textContent = formatNumber(hours * rate) + ' EGP';
        recalculateTotals();
      }

      hoursInput.addEventListener('change', updateAmount);
      hoursInput.addEventListener('input', updateAmount);
      rateInput.addEventListener('change', updateAmount);
      rateInput.addEventListener('input', updateAmount);
    }
  }

  // Initial calculation
  recalculateTotals();
});
</script>
@endsection
