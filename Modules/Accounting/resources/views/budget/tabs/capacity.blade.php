@extends('layouts/layoutMaster')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3">Budget {{ $budget->year }} - Capacity Tab</h1>
            <p class="text-muted">Configure headcount, hourly rates, and billable percentages</p>
        </div>
        <div class="col-md-4 text-end">
            <span class="badge bg-{{ $budget->status === 'finalized' ? 'success' : 'warning' }}">
                {{ ucfirst($budget->status) }}
            </span>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="card mb-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('accounting.budgets.growth', $budget->id) }}">
                        Growth
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('accounting.budgets.capacity', $budget->id) }}">
                        Capacity
                    </a>
                </li>
                <li class="nav-item">
                    <span class="nav-link disabled text-muted">Collection</span>
                </li>
                <li class="nav-item">
                    <span class="nav-link disabled text-muted">Result</span>
                </li>
                <li class="nav-item">
                    <span class="nav-link disabled text-muted">Personnel</span>
                </li>
                <li class="nav-item">
                    <span class="nav-link disabled text-muted">Expenses</span>
                </li>
                <li class="nav-item ms-auto">
                    <span class="nav-link disabled text-muted">Summary</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Capacity Tab Content -->
    <div class="tab-content">
        <div class="tab-pane fade show active" id="capacity-tab">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Capacity-Based Budget Projections</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('accounting.budgets.capacity.update', $budget->id) }}">
                        @csrf

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Avail. Hours</th>
                                        <th>Next Yr Headcount</th>
                                        <th>Hires</th>
                                        <th>Weighted HC</th>
                                        <th>Hourly Rate</th>
                                        <th>Billable %</th>
                                        <th>Est. Income</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($capacityEntries as $entry)
                                    <tr class="capacity-entry-row" data-entry-id="{{ $entry->id }}">
                                        <td>
                                            <strong>{{ $entry->product->name }}</strong>
                                        </td>
                                        <td>
                                            <input type="hidden" name="capacity_entries[{{ $loop->index }}][id]" value="{{ $entry->id }}">
                                            <input type="number" name="capacity_entries[{{ $loop->index }}][last_year_available_hours]"
                                                   class="form-control form-control-sm" step="0.01"
                                                   value="{{ $entry->last_year_available_hours }}"
                                                   placeholder="Hours">
                                        </td>
                                        <td>
                                            <input type="number" name="capacity_entries[{{ $loop->index }}][next_year_headcount]"
                                                   class="form-control form-control-sm" step="0.01"
                                                   value="{{ $entry->next_year_headcount }}"
                                                   placeholder="Count">
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-info manage-hires-btn"
                                                    data-entry-id="{{ $entry->id }}"
                                                    data-hires="{{ $entry->hires->toJson() }}"
                                                    data-bs-toggle="modal" data-bs-target="#hiresModal"
                                                    title="Manage Hires">
                                                <i class="fas fa-users"></i>
                                                <span class="badge bg-secondary">{{ $entry->hires->count() }}</span>
                                            </button>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm weighted-headcount"
                                                   value="{{ $entry->next_year_headcount !== null ? number_format($entry->calculateWeightedHeadcount(), 2) : '—' }}"
                                                   readonly>
                                        </td>
                                        <td>
                                            <input type="number" name="capacity_entries[{{ $loop->index }}][next_year_avg_hourly_price]"
                                                   class="form-control form-control-sm" step="0.01"
                                                   value="{{ $entry->next_year_avg_hourly_price }}"
                                                   placeholder="Rate">
                                        </td>
                                        <td>
                                            <input type="number" name="capacity_entries[{{ $loop->index }}][next_year_billable_pct]"
                                                   class="form-control form-control-sm" step="0.1"
                                                   value="{{ $entry->next_year_billable_pct }}"
                                                   min="0" max="100"
                                                   placeholder="100">
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control estimated-income"
                                                       value="{{ ($entry->next_year_avg_hourly_price !== null && $entry->next_year_billable_pct !== null) ? number_format($entry->calculateBudgetedIncome(), 2) : '—' }}"
                                                       readonly>
                                                <button class="btn btn-outline-secondary calculate-btn" type="button"
                                                        data-entry-id="{{ $entry->id }}"
                                                        data-route="{{ route('accounting.budgets.capacity.calculate', $budget->id) }}">
                                                    <i class="fas fa-calculator"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge" id="status-{{ $entry->id }}">
                                                @if($entry->next_year_avg_hourly_price && $entry->next_year_billable_pct)
                                                    <i class="fas fa-check text-success"></i>
                                                @else
                                                    <i class="fas fa-exclamation-circle text-warning"></i>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Help Text -->
                        <div class="alert alert-info mt-3">
                            <h6><i class="fas fa-lightbulb"></i> Tips</h6>
                            <ul class="mb-0">
                                <li>Enter last year's available hours (total billable hours)</li>
                                <li>Specify next year's expected headcount at start of year</li>
                                <li>Add hires that occur mid-year (automatically weighted)</li>
                                <li>Set average hourly rate and billable percentage</li>
                                <li>Click Calculate to compute weighted headcount and estimated income</li>
                                <li>Formula: Available Hours × Weighted HC × Hourly Rate × Billable %</li>
                            </ul>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Capacity Budget
                            </button>
                            <a href="{{ route('accounting.budgets.growth', $budget->id) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Growth
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Capacity Budget Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h6 class="text-muted">Total Products</h6>
                                <h3>{{ $capacityEntries->count() }}</h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h6 class="text-muted">Total Headcount</h6>
                                <h3>{{ number_format($capacityEntries->sum(fn($e) => $e->calculateWeightedHeadcount()), 2) }}</h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h6 class="text-muted">Total Est. Income</h6>
                                <h3>{{ number_format($capacityEntries->sum(fn($e) => $e->calculateBudgetedIncome() ?? 0), 0) }}</h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h6 class="text-muted">Completion</h6>
                                <h3>{{ $capacityEntries->count() > 0 ? round($capacityEntries->filter(fn($e) => $e->next_year_avg_hourly_price && $e->next_year_billable_pct)->count() / $capacityEntries->count() * 100, 0) : 0 }}%</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hires Management Modal -->
<div class="modal fade" id="hiresModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Hires - <span id="modalProductName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Add Hire Form -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Add New Hire</h6>
                    </div>
                    <div class="card-body">
                        <form id="addHireForm">
                            <input type="hidden" id="capacityEntryId">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="hireMonth" class="form-label">Hire Month</label>
                                    <select id="hireMonth" class="form-select" required>
                                        <option value="">Select Month</option>
                                        <option value="1">January</option>
                                        <option value="2">February</option>
                                        <option value="3">March</option>
                                        <option value="4">April</option>
                                        <option value="5">May</option>
                                        <option value="6">June</option>
                                        <option value="7">July</option>
                                        <option value="8">August</option>
                                        <option value="9">September</option>
                                        <option value="10">October</option>
                                        <option value="11">November</option>
                                        <option value="12">December</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="hireCount" class="form-label">Number of Hires</label>
                                    <input type="number" id="hireCount" class="form-control" step="0.1" min="0.1" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm mt-3">
                                <i class="fas fa-plus"></i> Add Hire
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Hires List -->
                <div id="hiresList">
                    <!-- Populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
    .stat-card {
        padding: 1rem;
        border-left: 4px solid #007bff;
        background-color: #f8f9fa;
        border-radius: 4px;
    }

    .stat-card h6 {
        font-weight: 600;
        font-size: 0.875rem;
    }

    .stat-card h3 {
        margin: 0.5rem 0 0 0;
        font-weight: 700;
    }

    .manage-hires-btn {
        white-space: nowrap;
    }

    .hire-item {
        padding: 0.75rem;
        background-color: #f8f9fa;
        border-radius: 4px;
        margin-bottom: 0.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .hire-item-info {
        display: flex;
        gap: 2rem;
    }

    .hire-item-info small {
        color: #6c757d;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentEntryId = null;

    // Manage hires button handler
    document.querySelectorAll('.manage-hires-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const entryId = this.dataset.entryId;
            const hiresData = JSON.parse(this.dataset.hires || '[]');
            const row = document.querySelector(`[data-entry-id="${entryId}"]`);
            const productName = row.querySelector('strong').textContent;

            currentEntryId = entryId;
            document.getElementById('capacityEntryId').value = entryId;
            document.getElementById('modalProductName').textContent = productName;

            loadHires(hiresData);
        });
    });

    // Load hires for entry
    function loadHires(hiresData) {
        const hiresList = document.getElementById('hiresList');
        const months = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        hiresList.innerHTML = '';

        if (!hiresData || hiresData.length === 0) {
            hiresList.innerHTML = '<p class="text-muted">No hires recorded for this product</p>';
            return;
        }

        hiresData.forEach(hire => {
            const monthsEmployed = (12 - hire.hire_month) + 1;
            const weightedContribution = (parseFloat(hire.hire_count) * monthsEmployed / 12).toFixed(2);

            const hireItem = document.createElement('div');
            hireItem.className = 'hire-item';
            hireItem.innerHTML = `
                <div class="hire-item-info">
                    <div>
                        <strong>${months[hire.hire_month]}</strong>
                        <small class="d-block text-muted">${hire.hire_count} hire(s)</small>
                    </div>
                    <div>
                        <small class="text-muted">Months: ${monthsEmployed}</small>
                        <small class="d-block text-muted">Weighted: ${weightedContribution}</small>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger delete-hire-btn"
                        data-hire-id="${hire.id}">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            hiresList.appendChild(hireItem);
        });

        // Attach delete handlers
        hiresList.querySelectorAll('.delete-hire-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                deleteHire(this.dataset.hireId);
            });
        });
    }

    // Delete hire
    function deleteHire(hireId) {
        if (!confirm('Are you sure you want to delete this hire?')) return;

        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const deleteRoute = `{{ url('accounting/budgets/' . $budget->id . '/capacity/hires') }}/${hireId}`;

        fetch(deleteRoute, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-Token': csrfToken,
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to delete hire');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete hire');
        });
    }

    // Add hire form submission
    document.getElementById('addHireForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const entryId = document.getElementById('capacityEntryId').value;
        const month = document.getElementById('hireMonth').value;
        const count = document.getElementById('hireCount').value;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        fetch('{{ route("accounting.budgets.capacity.hires.add", $budget->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': csrfToken,
            },
            body: JSON.stringify({
                capacity_entry_id: entryId,
                hire_month: month,
                hire_count: count,
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reset form and reload hires
                document.getElementById('addHireForm').reset();
                loadHires(entryId);
                // Reload the page to see the hire count badge update
                setTimeout(() => location.reload(), 500);
            } else {
                alert(data.message || 'Failed to add hire');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to add hire');
        });
    });

    // Calculate button handler
    document.querySelectorAll('.calculate-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const entryId = this.dataset.entryId;
            const route = this.dataset.route;
            const row = this.closest('tr');

            fetch(route, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ capacity_entry_id: entryId })
            })
            .then(response => response.json())
            .then(data => {
                row.querySelector('.weighted-headcount').value = data.weighted_headcount.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                row.querySelector('.estimated-income').value = data.budgeted_income.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to calculate capacity income');
            });
        });
    });
});
</script>
@endsection
