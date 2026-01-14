@extends('layouts/layoutMaster')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3">Budget {{ $budget->year }} - Growth Tab</h1>
            <p class="text-muted">Enter historical data and configure trendline projections</p>
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
                    <a class="nav-link active" href="{{ route('accounting.budgets.growth', $budget->id) }}">
                        Growth
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('accounting.budgets.capacity', $budget->id) }}">
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

    <!-- Growth Tab Content -->
    <div class="tab-content">
        <div class="tab-pane fade show active" id="growth-tab">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Growth-Based Budget Projections</h5>
                    <button class="btn btn-sm btn-outline-primary" id="populate-historical-btn"
                            data-route="{{ route('accounting.budgets.growth.populate-historical', $budget->id) }}">
                        <i class="fas fa-download"></i> Populate from Contracts
                    </button>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('accounting.budgets.growth.update', $budget->id) }}">
                        @csrf

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>{{ $budget->year - 3 }}</th>
                                        <th>{{ $budget->year - 2 }}</th>
                                        <th>{{ $budget->year - 1 }}</th>
                                        <th>Trendline Type</th>
                                        <th>Polynomial Order</th>
                                        <th>Projected ({{ $budget->year }})</th>
                                        <th>Budgeted ({{ $budget->year }})</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($growthEntries as $entry)
                                    <tr class="growth-entry-row" data-entry-id="{{ $entry->id }}">
                                        <td>
                                            <strong>{{ $entry->product->name }}</strong>
                                        </td>
                                        <td>
                                            <input type="hidden" name="growth_entries[{{ $loop->index }}][id]" value="{{ $entry->id }}">
                                            <input type="number" name="growth_entries[{{ $loop->index }}][year_minus_3]"
                                                   class="form-control form-control-sm" step="0.01"
                                                   value="{{ $entry->year_minus_3 }}"
                                                   placeholder="Enter amount">
                                        </td>
                                        <td>
                                            <input type="number" name="growth_entries[{{ $loop->index }}][year_minus_2]"
                                                   class="form-control form-control-sm" step="0.01"
                                                   value="{{ $entry->year_minus_2 }}"
                                                   placeholder="Enter amount">
                                        </td>
                                        <td>
                                            <input type="number" name="growth_entries[{{ $loop->index }}][year_minus_1]"
                                                   class="form-control form-control-sm" step="0.01"
                                                   value="{{ $entry->year_minus_1 }}"
                                                   placeholder="Enter amount">
                                        </td>
                                        <td>
                                            <select name="growth_entries[{{ $loop->index }}][trendline_type]"
                                                    class="form-control form-control-sm trendline-type"
                                                    data-entry-id="{{ $entry->id }}">
                                                <option value="linear" {{ $entry->trendline_type === 'linear' ? 'selected' : '' }}>Linear</option>
                                                <option value="logarithmic" {{ $entry->trendline_type === 'logarithmic' ? 'selected' : '' }}>Logarithmic</option>
                                                <option value="polynomial" {{ $entry->trendline_type === 'polynomial' ? 'selected' : '' }}>Polynomial</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="growth_entries[{{ $loop->index }}][polynomial_order]"
                                                   class="form-control form-control-sm polynomial-order"
                                                   min="2" max="3"
                                                   value="{{ $entry->polynomial_order ?? 2 }}"
                                                   {{ $entry->trendline_type !== 'polynomial' ? 'disabled' : '' }}>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control projected-value"
                                                       value="{{ $entry->budgeted_value ? number_format($entry->budgeted_value, 2) : '—' }}"
                                                       readonly>
                                                <button class="btn btn-outline-secondary calculate-btn" type="button"
                                                        data-entry-id="{{ $entry->id }}"
                                                        data-route="{{ route('accounting.budgets.growth.calculate-trendline', $budget->id) }}">
                                                    <i class="fas fa-calculator"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" name="growth_entries[{{ $loop->index }}][budgeted_value]"
                                                   class="form-control form-control-sm budgeted-value" step="0.01"
                                                   value="{{ $entry->budgeted_value }}"
                                                   placeholder="Amount">
                                        </td>
                                        <td>
                                            <span class="badge" id="status-{{ $entry->id }}">
                                                @if($entry->hasEnoughDataForTrendline())
                                                    <i class="fas fa-check text-success"></i>
                                                @else
                                                    <i class="fas fa-info-circle text-warning"></i>
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
                                <li>Enter 3 years of historical data (Year -3, Year -2, Year -1)</li>
                                <li>Select a trendline type: <strong>Linear</strong> (straight line), <strong>Logarithmic</strong> (curved, slowing growth), or <strong>Polynomial</strong> (curved)</li>
                                <li>Click the <strong>Calculate</strong> button to compute the projection</li>
                                <li>You can manually override the projected value in the "Budgeted Value" column</li>
                                <li>At least 2 historical data points are required for projection</li>
                            </ul>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Growth Budget
                            </button>
                            <a href="{{ route('accounting.budgets.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Growth Budget Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h6 class="text-muted">Total Products</h6>
                                <h3>{{ $growthEntries->count() }}</h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h6 class="text-muted">With Data</h6>
                                <h3>{{ $growthEntries->filter(fn($e) => $e->hasEnoughDataForTrendline())->count() }}</h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h6 class="text-muted">Total Budgeted</h6>
                                <h3>{{ number_format($growthEntries->sum('budgeted_value'), 0) }}</h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h6 class="text-muted">Completion</h6>
                                <h3>{{ $growthEntries->count() > 0 ? round($growthEntries->filter(fn($e) => $e->budgeted_value !== null)->count() / $growthEntries->count() * 100, 0) : 0 }}%</h3>
                            </div>
                        </div>
                    </div>
                </div>
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
</style>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Trendline type change handler
    document.querySelectorAll('.trendline-type').forEach(select => {
        select.addEventListener('change', function() {
            const polynomialOrderInput = this.closest('tr').querySelector('.polynomial-order');
            if (this.value === 'polynomial') {
                polynomialOrderInput.disabled = false;
            } else {
                polynomialOrderInput.disabled = true;
            }
        });
    });

    // Calculate button handler
    document.querySelectorAll('.calculate-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const entryId = this.dataset.entryId;
            const route = this.dataset.route;
            const row = this.closest('tr');

            // Get current form values (not saved database values)
            const trendlineType = row.querySelector('.trendline-type').value;
            const polynomialOrder = row.querySelector('.polynomial-order').value;

            fetch(route, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    growth_entry_id: entryId,
                    trendline_type: trendlineType,
                    polynomial_order: polynomialOrder
                })
            })
            .then(response => response.json())
            .then(data => {
                row.querySelector('.projected-value').value = data.projection.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                // Auto-fill budgeted value if empty
                const budgetedInput = row.querySelector('.budgeted-value');
                if (!budgetedInput.value) {
                    budgetedInput.value = data.projection;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to calculate trendline');
            });
        });
    });

    // Populate historical data button
    document.getElementById('populate-historical-btn')?.addEventListener('click', function() {
        if (confirm('This will calculate income from paid contracts for {{ $budget->year - 3 }}, {{ $budget->year - 2 }}, and {{ $budget->year - 1 }} for each product. Continue?')) {
            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            btn.disabled = true;

            const route = this.dataset.route;
            fetch(route, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    alert(data.message || 'Failed to populate historical data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btn.innerHTML = originalText;
                btn.disabled = false;
                alert('Failed to populate historical data');
            });
        }
    });
});
</script>
@endsection
