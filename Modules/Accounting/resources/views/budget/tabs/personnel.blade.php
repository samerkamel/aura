@extends('layouts.app')

@section('title', "Budget {$budget->year} - Personnel")

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="mb-1">Budget {{ $budget->year }} - Personnel Tab</h1>
                <p class="text-muted mb-0">Manage employee salaries and product/G&A allocations</p>
            </div>
            <span class="badge bg-label-{{ $budget->status === 'finalized' ? 'success' : ($budget->status === 'in_progress' ? 'warning' : 'secondary') }} fs-6">
                {{ ucfirst(str_replace('_', ' ', $budget->status)) }}
            </span>
        </div>

        {{-- Tab Navigation --}}
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('accounting.budgets.growth', $budget->id) }}">Growth</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('accounting.budgets.capacity', $budget->id) }}">Capacity</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('accounting.budgets.collection', $budget->id) }}">Collection</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('accounting.budgets.result', $budget->id) }}">Result</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('accounting.budgets.personnel', $budget->id) }}">Personnel</a>
            </li>
            <li class="nav-item">
                <span class="nav-link disabled">Expenses</span>
            </li>
            <li class="nav-item">
                <span class="nav-link disabled">Summary</span>
            </li>
        </ul>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Charts Row --}}
        <div class="row mb-4">
            {{-- Personnel by Department Chart --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Personnel Cost by Department</h5>
                    </div>
                    <div class="card-body">
                        <div id="departmentChart" style="min-height: 300px;"></div>
                    </div>
                </div>
            </div>

            {{-- Allocation by Product Chart --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Salary Allocation by Product</h5>
                    </div>
                    <div class="card-body">
                        <div id="allocationChart" style="min-height: 300px;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Personnel Table --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Personnel Budget Entries</h5>
                <div>
                    @if($personnelEntries->isEmpty())
                        <button type="button" class="btn btn-primary" id="initializePersonnelBtn">
                            <i class="ti ti-users me-1"></i> Initialize from Employees
                        </button>
                    @else
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#newHireModal">
                            <i class="ti ti-user-plus me-1"></i> Add New Hire
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if($personnelEntries->isEmpty())
                    <div class="text-center py-5">
                        <i class="ti ti-users-group display-4 text-muted mb-3 d-block"></i>
                        <h5>No Personnel Entries</h5>
                        <p class="text-muted">Click "Initialize from Employees" to load active employees into this budget.</p>
                    </div>
                @else
                    <form action="{{ route('accounting.budgets.personnel.update', $budget->id) }}" method="POST" id="personnelForm">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-hover" id="personnelTable">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Team</th>
                                        <th>Current Salary</th>
                                        <th>Proposed Salary</th>
                                        <th>Change %</th>
                                        <th>New Hire</th>
                                        <th>Hire Month</th>
                                        <th>Allocations</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($personnelEntries as $index => $entry)
                                        <tr data-entry-id="{{ $entry->id }}">
                                            <td>
                                                <input type="hidden" name="personnel_entries[{{ $index }}][id]" value="{{ $entry->id }}">
                                                @if($entry->employee)
                                                    <strong>{{ $entry->employee->name }}</strong>
                                                    <br><small class="text-muted">{{ $entry->employee->position }}</small>
                                                @else
                                                    <span class="badge bg-label-info">New Hire</span>
                                                @endif
                                            </td>
                                            <td>{{ $entry->employee?->team ?? '-' }}</td>
                                            <td>{{ number_format($entry->current_salary, 2) }}</td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">EGP</span>
                                                    <input type="number"
                                                           class="form-control proposed-salary"
                                                           name="personnel_entries[{{ $index }}][proposed_salary]"
                                                           value="{{ $entry->proposed_salary ?? $entry->current_salary }}"
                                                           step="0.01"
                                                           min="0"
                                                           data-current="{{ $entry->current_salary }}">
                                                </div>
                                            </td>
                                            <td class="change-pct">
                                                @php
                                                    $current = (float) $entry->current_salary;
                                                    $proposed = (float) ($entry->proposed_salary ?? $entry->current_salary);
                                                    $changePct = $current > 0 ? (($proposed - $current) / $current) * 100 : 0;
                                                @endphp
                                                <span class="badge bg-label-{{ $changePct > 0 ? 'success' : ($changePct < 0 ? 'danger' : 'secondary') }}">
                                                    {{ $changePct >= 0 ? '+' : '' }}{{ number_format($changePct, 1) }}%
                                                </span>
                                            </td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input type="hidden" name="personnel_entries[{{ $index }}][is_new_hire]" value="0">
                                                    <input class="form-check-input new-hire-check"
                                                           type="checkbox"
                                                           name="personnel_entries[{{ $index }}][is_new_hire]"
                                                           value="1"
                                                           {{ $entry->is_new_hire ? 'checked' : '' }}
                                                           {{ $entry->employee ? '' : 'disabled' }}>
                                                </div>
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm hire-month-select"
                                                        name="personnel_entries[{{ $index }}][hire_month]"
                                                        {{ !$entry->is_new_hire ? 'disabled' : '' }}>
                                                    <option value="">-</option>
                                                    @for($m = 1; $m <= 12; $m++)
                                                        <option value="{{ $m }}" {{ $entry->hire_month == $m ? 'selected' : '' }}>
                                                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                                        </option>
                                                    @endfor
                                                </select>
                                            </td>
                                            <td>
                                                @php
                                                    $totalAlloc = $entry->allocations->sum('allocation_percentage');
                                                @endphp
                                                <button type="button"
                                                        class="btn btn-sm {{ $totalAlloc == 100 ? 'btn-outline-success' : 'btn-outline-warning' }} allocations-btn"
                                                        data-entry-id="{{ $entry->id }}"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#allocationsModal">
                                                    {{ number_format($totalAlloc, 0) }}%
                                                </button>
                                            </td>
                                            <td>
                                                @if(!$entry->employee)
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-danger delete-entry-btn"
                                                            data-entry-id="{{ $entry->id }}">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="2"><strong>Total</strong></td>
                                        <td><strong>{{ number_format($summary['total_current_salaries'], 2) }}</strong></td>
                                        <td><strong>{{ number_format($summary['total_proposed_salaries'], 2) }}</strong></td>
                                        <td>
                                            <span class="badge bg-label-{{ $summary['total_increase_percentage'] > 0 ? 'success' : ($summary['total_increase_percentage'] < 0 ? 'danger' : 'secondary') }}">
                                                {{ $summary['total_increase_percentage'] >= 0 ? '+' : '' }}{{ number_format($summary['total_increase_percentage'], 1) }}%
                                            </span>
                                        </td>
                                        <td colspan="4"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                            <a href="{{ route('accounting.budgets.result', $budget->id) }}" class="btn btn-outline-secondary">
                                <i class="ti ti-arrow-left me-1"></i> Back to Result
                            </a>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-device-floppy me-1"></i> Save Personnel Budget
                                </button>
                                <button type="button" class="btn btn-success" disabled>
                                    Next: Expenses <i class="ti ti-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        {{-- Summary Card --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ti ti-chart-bar me-2"></i>Personnel Budget Summary</h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-2 col-sm-4">
                        <div class="text-center">
                            <h6 class="text-muted mb-1">Total Employees</h6>
                            <h3 class="mb-0">{{ $summary['employee_count'] }}</h3>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <div class="text-center">
                            <h6 class="text-muted mb-1">New Hires</h6>
                            <h3 class="mb-0">{{ $summary['new_hires_count'] }}</h3>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <div class="text-center">
                            <h6 class="text-muted mb-1">Current Total</h6>
                            <h3 class="mb-0">{{ number_format($summary['total_current_salaries'], 0) }}</h3>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <div class="text-center">
                            <h6 class="text-muted mb-1">Proposed Total</h6>
                            <h3 class="mb-0">{{ number_format($summary['total_proposed_salaries'], 0) }}</h3>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <div class="text-center">
                            <h6 class="text-muted mb-1">Increase Amount</h6>
                            <h3 class="mb-0 {{ $summary['total_increase_amount'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $summary['total_increase_amount'] >= 0 ? '+' : '' }}{{ number_format($summary['total_increase_amount'], 0) }}
                            </h3>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <div class="text-center">
                            <h6 class="text-muted mb-1">Increase %</h6>
                            <h3 class="mb-0 {{ $summary['total_increase_percentage'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $summary['total_increase_percentage'] >= 0 ? '+' : '' }}{{ number_format($summary['total_increase_percentage'], 1) }}%
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- New Hire Modal --}}
    <div class="modal fade" id="newHireModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Hire</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('accounting.budgets.personnel.new-hire.add', $budget->id) }}" method="POST" id="newHireForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Position Name</label>
                            <input type="text" class="form-control" name="position_name" required placeholder="e.g., Senior Developer">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Team</label>
                            <select class="form-select" name="team">
                                <option value="">Select Team</option>
                                <option value="PHP">PHP</option>
                                <option value=".NET">.NET</option>
                                <option value="Mobile">Mobile</option>
                                <option value="Design">Design</option>
                                <option value="Websites">Websites</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Proposed Salary</label>
                            <div class="input-group">
                                <span class="input-group-text">EGP</span>
                                <input type="number" class="form-control" name="proposed_salary" required step="0.01" min="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expected Hire Month</label>
                            <select class="form-select" name="hire_month" required>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}">{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add New Hire</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Allocations Modal --}}
    <div class="modal fade" id="allocationsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Allocations</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="allocationsForm">
                    <div class="modal-body">
                        <input type="hidden" id="allocationEntryId" value="">
                        <p class="text-muted">Allocate the employee's salary across products and G&A. Total must equal 100%.</p>

                        <div class="mb-3">
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar" role="progressbar" id="allocationProgress" style="width: 0%">0%</div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="allocationsTable">
                                <thead>
                                    <tr>
                                        <th>Product / G&A</th>
                                        <th style="width: 150px;">Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($products as $product)
                                        <tr>
                                            <td>{{ $product->name }}</td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number"
                                                           class="form-control allocation-input"
                                                           data-product-id="{{ $product->id }}"
                                                           value="0"
                                                           step="1"
                                                           min="0"
                                                           max="100">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="table-warning">
                                        <td><strong>G&A (General & Administrative)</strong></td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number"
                                                       class="form-control allocation-input"
                                                       data-is-ga="true"
                                                       value="0"
                                                       step="1"
                                                       min="0"
                                                       max="100">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td><strong>Total</strong></td>
                                        <td><strong id="allocationTotal">0%</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- Quick Templates --}}
                        <div class="mt-3">
                            <label class="form-label">Quick Templates:</label>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-secondary template-btn" data-template="100-ga">100% G&A</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary template-btn" data-template="100-first">100% First Product</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary template-btn" data-template="50-50">50% Product / 50% G&A</button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="saveAllocationsBtn">Save Allocations</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart data from controller
    const byDepartment = @json($chartData['byDepartment']);
    const allocations = @json($chartData['allocations']);
    const personnelEntries = @json($personnelEntries);

    // Department Chart
    const deptLabels = Object.keys(byDepartment);
    const deptCurrentData = deptLabels.map(d => byDepartment[d].current_total);
    const deptProposedData = deptLabels.map(d => byDepartment[d].proposed_total);

    if (deptLabels.length > 0) {
        const departmentChart = new ApexCharts(document.querySelector('#departmentChart'), {
            series: [{
                name: 'Current Salary',
                data: deptCurrentData
            }, {
                name: 'Proposed Salary',
                data: deptProposedData
            }],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: { show: false }
            },
            plotOptions: {
                bar: { horizontal: false, columnWidth: '55%' }
            },
            dataLabels: { enabled: false },
            xaxis: { categories: deptLabels },
            yaxis: {
                title: { text: 'Salary (EGP)' },
                labels: {
                    formatter: val => val.toLocaleString()
                }
            },
            colors: ['#6c757d', '#7367f0'],
            legend: { position: 'top' }
        });
        departmentChart.render();
    }

    // Allocation Chart
    const allocLabels = Object.values(allocations).map(a => a.name);
    const allocData = Object.values(allocations).map(a => a.total);

    if (allocData.some(v => v > 0)) {
        const allocationChart = new ApexCharts(document.querySelector('#allocationChart'), {
            series: allocData,
            chart: {
                type: 'donut',
                height: 300
            },
            labels: allocLabels,
            legend: { position: 'bottom' },
            dataLabels: {
                enabled: true,
                formatter: (val, opts) => {
                    return opts.w.config.series[opts.seriesIndex].toLocaleString();
                }
            },
            tooltip: {
                y: {
                    formatter: val => 'EGP ' + val.toLocaleString()
                }
            }
        });
        allocationChart.render();
    } else {
        document.querySelector('#allocationChart').innerHTML = '<div class="text-center text-muted py-5">No allocations defined yet</div>';
    }

    // Initialize Personnel Button
    const initBtn = document.getElementById('initializePersonnelBtn');
    if (initBtn) {
        initBtn.addEventListener('click', function() {
            if (confirm('This will create personnel entries for all active employees. Continue?')) {
                fetch('{{ route('accounting.budgets.personnel.initialize', $budget->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Failed to initialize');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                });
            }
        });
    }

    // Proposed Salary Change Handler
    document.querySelectorAll('.proposed-salary').forEach(input => {
        input.addEventListener('change', function() {
            const current = parseFloat(this.dataset.current) || 0;
            const proposed = parseFloat(this.value) || 0;
            const changePct = current > 0 ? ((proposed - current) / current) * 100 : 0;

            const row = this.closest('tr');
            const pctCell = row.querySelector('.change-pct');
            const badgeClass = changePct > 0 ? 'success' : (changePct < 0 ? 'danger' : 'secondary');
            pctCell.innerHTML = `<span class="badge bg-label-${badgeClass}">${changePct >= 0 ? '+' : ''}${changePct.toFixed(1)}%</span>`;
        });
    });

    // New Hire Checkbox Handler
    document.querySelectorAll('.new-hire-check').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const row = this.closest('tr');
            const monthSelect = row.querySelector('.hire-month-select');
            monthSelect.disabled = !this.checked;
            if (!this.checked) {
                monthSelect.value = '';
            }
        });
    });

    // Allocations Modal
    let currentEntryId = null;

    document.querySelectorAll('.allocations-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            currentEntryId = this.dataset.entryId;
            document.getElementById('allocationEntryId').value = currentEntryId;

            // Find entry and load existing allocations
            const entry = personnelEntries.find(e => e.id == currentEntryId);
            if (entry) {
                // Reset all inputs
                document.querySelectorAll('.allocation-input').forEach(input => {
                    input.value = 0;
                });

                // Load existing allocations
                if (entry.allocations) {
                    entry.allocations.forEach(alloc => {
                        if (alloc.product_id === null) {
                            const gaInput = document.querySelector('.allocation-input[data-is-ga="true"]');
                            if (gaInput) gaInput.value = alloc.allocation_percentage;
                        } else {
                            const input = document.querySelector(`.allocation-input[data-product-id="${alloc.product_id}"]`);
                            if (input) input.value = alloc.allocation_percentage;
                        }
                    });
                }

                updateAllocationTotal();
            }
        });
    });

    // Update allocation total
    function updateAllocationTotal() {
        let total = 0;
        document.querySelectorAll('.allocation-input').forEach(input => {
            total += parseFloat(input.value) || 0;
        });

        document.getElementById('allocationTotal').textContent = total + '%';

        const progress = document.getElementById('allocationProgress');
        progress.style.width = Math.min(total, 100) + '%';
        progress.textContent = total + '%';
        progress.className = 'progress-bar ' + (total === 100 ? 'bg-success' : (total > 100 ? 'bg-danger' : 'bg-warning'));
    }

    document.querySelectorAll('.allocation-input').forEach(input => {
        input.addEventListener('input', updateAllocationTotal);
    });

    // Template buttons
    document.querySelectorAll('.template-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const template = this.dataset.template;

            // Reset all
            document.querySelectorAll('.allocation-input').forEach(input => {
                input.value = 0;
            });

            if (template === '100-ga') {
                document.querySelector('.allocation-input[data-is-ga="true"]').value = 100;
            } else if (template === '100-first') {
                const firstProduct = document.querySelector('.allocation-input[data-product-id]');
                if (firstProduct) firstProduct.value = 100;
            } else if (template === '50-50') {
                const firstProduct = document.querySelector('.allocation-input[data-product-id]');
                if (firstProduct) firstProduct.value = 50;
                document.querySelector('.allocation-input[data-is-ga="true"]').value = 50;
            }

            updateAllocationTotal();
        });
    });

    // Save Allocations
    document.getElementById('saveAllocationsBtn').addEventListener('click', function() {
        const entryId = document.getElementById('allocationEntryId').value;
        const allocations = [];

        document.querySelectorAll('.allocation-input').forEach(input => {
            const pct = parseFloat(input.value) || 0;
            if (pct > 0) {
                if (input.dataset.isGa === 'true') {
                    allocations.push({ is_ga: true, percentage: pct });
                } else {
                    allocations.push({ product_id: input.dataset.productId, percentage: pct });
                }
            }
        });

        // Check total
        const total = allocations.reduce((sum, a) => sum + a.percentage, 0);
        if (Math.abs(total - 100) > 0.01) {
            alert('Allocations must sum to 100%. Current total: ' + total + '%');
            return;
        }

        fetch(`{{ url('accounting/budgets/' . $budget->id . '/personnel') }}/${entryId}/allocations`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ allocations: allocations })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('allocationsModal')).hide();
                window.location.reload();
            } else {
                alert(data.message || 'Failed to save allocations');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    });

    // Delete Entry Button
    document.querySelectorAll('.delete-entry-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this new hire entry?')) {
                const entryId = this.dataset.entryId;
                fetch(`{{ url('accounting/budgets/' . $budget->id . '/personnel') }}/${entryId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Failed to delete entry');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                });
            }
        });
    });
});
</script>
@endsection
