@extends('layouts/layoutMaster')

@section('title', $project->name . ' - Bulk Create Tasks')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
@endsection

@section('page-style')
<style>
  .project-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 0.5rem;
    padding: 1.5rem;
    color: white;
    margin-bottom: 1.5rem;
  }
  .project-code {
    background: rgba(255,255,255,0.2);
    padding: 0.25rem 0.75rem;
    border-radius: 0.25rem;
    font-family: monospace;
    font-size: 0.9rem;
  }
  .bulk-table-container {
    overflow-x: auto;
  }
  .bulk-table {
    min-width: 1200px;
  }
  .bulk-table th {
    background: #f8f9fa;
    font-weight: 600;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 10;
  }
  .bulk-table td {
    padding: 0.25rem;
    vertical-align: top;
  }
  .bulk-table input,
  .bulk-table select,
  .bulk-table textarea {
    border: 1px solid transparent;
    background: transparent;
    width: 100%;
    padding: 0.375rem 0.5rem;
    border-radius: 0.25rem;
    transition: all 0.15s ease;
  }
  .bulk-table input:focus,
  .bulk-table select:focus,
  .bulk-table textarea:focus {
    border-color: #667eea;
    background: white;
    outline: none;
    box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.25);
  }
  .bulk-table input:hover,
  .bulk-table select:hover,
  .bulk-table textarea:hover {
    background: #f8f9fa;
  }
  .bulk-table tr:nth-child(even) {
    background: #fafbfc;
  }
  .bulk-table tr:hover {
    background: #f0f4ff;
  }
  .bulk-table .row-number {
    width: 40px;
    text-align: center;
    color: #6c757d;
    font-size: 0.85rem;
    font-weight: 500;
  }
  .bulk-table .summary-col {
    min-width: 300px;
  }
  .bulk-table .description-col {
    min-width: 250px;
  }
  .bulk-table .type-col,
  .bulk-table .priority-col,
  .bulk-table .assignee-col {
    min-width: 140px;
  }
  .bulk-table .date-col {
    min-width: 130px;
  }
  .bulk-table .action-col {
    width: 50px;
  }
  .btn-remove-row {
    opacity: 0.3;
    transition: opacity 0.15s;
  }
  .bulk-table tr:hover .btn-remove-row {
    opacity: 1;
  }
  .required-indicator {
    color: #dc3545;
  }
  .row-error {
    background: #fff5f5 !important;
  }
  .row-error input,
  .row-error select {
    border-color: #dc3545;
  }
  .keyboard-hint {
    font-size: 0.75rem;
    color: #6c757d;
  }
  .keyboard-hint kbd {
    background: #e9ecef;
    padding: 0.1rem 0.4rem;
    border-radius: 0.2rem;
    font-size: 0.7rem;
  }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @if (session('error'))
    <div class="alert alert-danger alert-dismissible mb-4" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- Project Header -->
  <div class="project-header">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <span class="project-code">{{ $project->code }}</span>
        <h4 class="mb-0 mt-2">{{ $project->name }}</h4>
        @if($project->customer)
          <small class="opacity-75">{{ $project->customer->display_name }}</small>
        @endif
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('projects.tasks', $project) }}" class="btn btn-outline-light btn-sm">
          <i class="ti ti-arrow-left me-1"></i>Back to Tasks
        </a>
      </div>
    </div>
  </div>

  <!-- Bulk Create Form -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h5 class="mb-0"><i class="ti ti-table me-2"></i>Bulk Create Tasks</h5>
        <p class="text-muted mb-0 mt-1">Fill in the table below to create multiple tasks at once. Tasks will be created in Jira.</p>
      </div>
      <div class="keyboard-hint d-none d-md-block">
        <kbd>Tab</kbd> to move forward &nbsp;|&nbsp;
        <kbd>Shift</kbd>+<kbd>Tab</kbd> to move back &nbsp;|&nbsp;
        <kbd>Enter</kbd> to add row
      </div>
    </div>
    <div class="card-body">
      <form action="{{ route('projects.store-bulk-tasks', $project) }}" method="POST" id="bulkCreateForm">
        @csrf
        <div class="bulk-table-container">
          <table class="table bulk-table mb-0" id="bulkTasksTable">
            <thead>
              <tr>
                <th class="row-number">#</th>
                <th class="summary-col">Summary <span class="required-indicator">*</span></th>
                <th class="type-col">Type <span class="required-indicator">*</span></th>
                <th class="priority-col">Priority</th>
                <th class="assignee-col">Assignee</th>
                <th class="date-col">Due Date</th>
                <th class="description-col">Description</th>
                <th class="action-col"></th>
              </tr>
            </thead>
            <tbody id="taskRows">
              <!-- Rows will be added dynamically -->
            </tbody>
          </table>
        </div>

        <div class="mt-3 d-flex justify-content-between align-items-center">
          <button type="button" class="btn btn-outline-primary" id="addRowBtn">
            <i class="ti ti-plus me-1"></i>Add Row
          </button>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" id="clearAllBtn">
              <i class="ti ti-trash me-1"></i>Clear All
            </button>
            <button type="submit" class="btn btn-primary" id="submitBtn">
              <i class="ti ti-send me-1"></i>Create Tasks in Jira
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Tips Card -->
  <div class="card mt-4">
    <div class="card-body">
      <h6 class="card-title"><i class="ti ti-bulb me-2"></i>Tips</h6>
      <ul class="mb-0 text-muted">
        <li>Use <kbd>Tab</kbd> to quickly move between fields</li>
        <li>Press <kbd>Enter</kbd> in the last row to add a new row</li>
        <li>Only rows with a summary will be created - empty rows are ignored</li>
        <li>Tasks are created sequentially, so the order is preserved</li>
      </ul>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const issueTypes = @json($issueTypes);
  const priorities = @json($priorities);
  const assignees = @json($assignees);
  const tableBody = document.getElementById('taskRows');
  let rowCount = 0;

  function createRow() {
    rowCount++;
    const row = document.createElement('tr');
    row.dataset.rowIndex = rowCount;

    // Issue type options
    let issueTypeOptions = issueTypes.map(type =>
      `<option value="${type}" ${type === 'Task' ? 'selected' : ''}>${type}</option>`
    ).join('');

    // Priority options
    let priorityOptions = '<option value="">--</option>' + priorities.map(p =>
      `<option value="${p}" ${p === 'Medium' ? 'selected' : ''}>${p}</option>`
    ).join('');

    // Assignee options
    let assigneeOptions = '<option value="">Unassigned</option>' + assignees.map(a =>
      `<option value="${a.account_id}">${a.display_name}</option>`
    ).join('');

    row.innerHTML = `
      <td class="row-number">${rowCount}</td>
      <td class="summary-col">
        <input type="text" name="tasks[${rowCount}][summary]" placeholder="Task summary..."
               class="summary-input" maxlength="255" autocomplete="off">
      </td>
      <td class="type-col">
        <select name="tasks[${rowCount}][issue_type]" class="type-select">
          ${issueTypeOptions}
        </select>
      </td>
      <td class="priority-col">
        <select name="tasks[${rowCount}][priority]">
          ${priorityOptions}
        </select>
      </td>
      <td class="assignee-col">
        <select name="tasks[${rowCount}][assignee_account_id]">
          ${assigneeOptions}
        </select>
      </td>
      <td class="date-col">
        <input type="date" name="tasks[${rowCount}][due_date]">
      </td>
      <td class="description-col">
        <input type="text" name="tasks[${rowCount}][description]" placeholder="Description..."
               maxlength="500" autocomplete="off">
      </td>
      <td class="action-col">
        <button type="button" class="btn btn-sm btn-icon btn-text-danger btn-remove-row" title="Remove row">
          <i class="ti ti-x"></i>
        </button>
      </td>
    `;

    // Add event listeners
    const removeBtn = row.querySelector('.btn-remove-row');
    removeBtn.addEventListener('click', function() {
      if (tableBody.children.length > 1) {
        row.remove();
        updateRowNumbers();
      }
    });

    // Add Enter key handler on last input to add new row
    const inputs = row.querySelectorAll('input, select');
    const lastInput = row.querySelector('td.description-col input');
    lastInput.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        const newRow = createRow();
        tableBody.appendChild(newRow);
        newRow.querySelector('.summary-input').focus();
      }
    });

    return row;
  }

  function updateRowNumbers() {
    const rows = tableBody.querySelectorAll('tr');
    rows.forEach((row, index) => {
      row.querySelector('.row-number').textContent = index + 1;
    });
  }

  // Add initial rows
  for (let i = 0; i < 5; i++) {
    tableBody.appendChild(createRow());
  }

  // Add row button
  document.getElementById('addRowBtn').addEventListener('click', function() {
    const newRow = createRow();
    tableBody.appendChild(newRow);
    newRow.querySelector('.summary-input').focus();
  });

  // Clear all button
  document.getElementById('clearAllBtn').addEventListener('click', function() {
    Swal.fire({
      title: 'Clear all rows?',
      text: 'This will remove all entered data.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, clear all',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        tableBody.innerHTML = '';
        rowCount = 0;
        for (let i = 0; i < 5; i++) {
          tableBody.appendChild(createRow());
        }
        tableBody.querySelector('.summary-input').focus();
      }
    });
  });

  // Form submission
  document.getElementById('bulkCreateForm').addEventListener('submit', function(e) {
    // Count non-empty rows
    const summaryInputs = tableBody.querySelectorAll('.summary-input');
    let filledRows = 0;
    let hasError = false;

    summaryInputs.forEach(input => {
      const row = input.closest('tr');
      if (input.value.trim()) {
        filledRows++;
        row.classList.remove('row-error');
      }
    });

    if (filledRows === 0) {
      e.preventDefault();
      Swal.fire({
        title: 'No tasks to create',
        text: 'Please enter at least one task summary.',
        icon: 'warning'
      });
      return;
    }

    // Show loading state
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Creating ' + filledRows + ' task(s)...';
  });

  // Focus first input
  tableBody.querySelector('.summary-input').focus();
});
</script>
@endsection
