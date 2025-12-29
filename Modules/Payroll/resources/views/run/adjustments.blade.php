@extends('layouts/layoutMaster')

@section('title', 'Adjust Payroll')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header -->
  <div class="row">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">
              <i class="ti ti-adjustments me-2"></i>Adjust Payroll
            </h5>
            <small class="text-muted">
              Make adjustments before finalizing payroll for {{ $periodEnd->format('F Y') }}
            </small>
          </div>
          <a href="{{ route('payroll.run.review', ['period' => $selectedPeriod]) }}" class="btn btn-label-secondary">
            <i class="ti ti-arrow-left me-1"></i>Back to Review
          </a>
        </div>
        <div class="card-body">
          <!-- Period Info -->
          <div class="alert alert-info mb-0">
            <i class="ti ti-info-circle me-2"></i>
            <strong>Period:</strong> {{ $periodStart->format('M j, Y') }} - {{ $periodEnd->format('M j, Y') }}
            <span class="ms-3"><strong>Employees:</strong> {{ $payrollRuns->count() }}</span>
            <span class="ms-3">
              <strong>Status:</strong>
              @php
                $finalized = $payrollRuns->where('status', 'finalized')->count();
                $pending = $payrollRuns->where('status', 'pending_adjustment')->count();
              @endphp
              @if($finalized > 0)
                <span class="badge bg-success">{{ $finalized }} Finalized</span>
              @endif
              @if($pending > 0)
                <span class="badge bg-warning">{{ $pending }} Pending</span>
              @endif
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="ti ti-check me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if($errors->has('finalization'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="ti ti-alert-circle me-2"></i>{{ $errors->first('finalization') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- Adjustments Form -->
  <form method="POST" action="{{ route('payroll.run.adjustments.save') }}" id="adjustmentsForm">
    @csrf
    <input type="hidden" name="period" value="{{ $selectedPeriod }}">

    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div>
              <h5 class="mb-0">Employee Salary Adjustments</h5>
              <small class="text-muted">Adjust salaries, add bonuses or deductions</small>
            </div>
            <div>
              <button type="button" class="btn btn-label-info btn-sm me-2" onclick="recalculatePayroll()">
                <i class="ti ti-calculator me-1"></i>Recalculate
              </button>
              <button type="button" class="btn btn-label-warning btn-sm" onclick="resetAllAdjustments()">
                <i class="ti ti-refresh me-1"></i>Reset All
              </button>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width: 200px;">Employee</th>
                    <th class="text-center" style="width: 100px;">Performance</th>
                    <th class="text-end" style="width: 120px;">Base Salary</th>
                    <th class="text-end" style="width: 140px;">Calculated</th>
                    <th class="text-end" style="width: 140px;">Adjusted Salary</th>
                    <th class="text-end" style="width: 120px;">Bonus</th>
                    <th class="text-end" style="width: 120px;">Deduction</th>
                    <th class="text-end" style="width: 140px;">Final Amount</th>
                    <th style="width: 200px;">Notes</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($payrollRuns as $index => $run)
                    @php
                      $isFinalized = $run->status === 'finalized';
                      $snapshot = $run->calculation_snapshot ?? [];
                    @endphp
                    <tr class="{{ $run->is_adjusted ? 'table-warning' : '' }} {{ $isFinalized ? 'table-success' : '' }}">
                      <td>
                        <input type="hidden" name="adjustments[{{ $index }}][payroll_run_id]" value="{{ $run->id }}">
                        <div class="d-flex align-items-center">
                          <div class="avatar avatar-sm me-2">
                            <span class="avatar-initial rounded-circle bg-label-primary">
                              {{ substr($run->employee->name ?? 'NA', 0, 2) }}
                            </span>
                          </div>
                          <div>
                            <strong>{{ $run->employee->name ?? 'Unknown' }}</strong>
                            @if($run->is_adjusted)
                              <br><span class="badge bg-label-warning">Adjusted</span>
                            @endif
                            @if($isFinalized)
                              <br><span class="badge bg-label-success">Finalized</span>
                            @endif
                          </div>
                        </div>
                      </td>
                      <td class="text-center">
                        <span class="badge {{ $run->performance_percentage >= 90 ? 'bg-success' : ($run->performance_percentage >= 75 ? 'bg-warning' : 'bg-danger') }}">
                          {{ number_format($run->performance_percentage, 1) }}%
                        </span>
                        <br>
                        <small class="text-muted">
                          @if(isset($snapshot['attendance']))
                            {{ $snapshot['attendance']['net_attended_hours'] ?? 0 }}h
                          @endif
                        </small>
                      </td>
                      <td class="text-end">
                        <span class="text-muted">{{ number_format($run->base_salary, 2) }}</span>
                      </td>
                      <td class="text-end">
                        <strong>{{ number_format($run->calculated_salary, 2) }}</strong>
                      </td>
                      <td class="text-end">
                        <input type="number"
                               class="form-control form-control-sm text-end adjusted-salary"
                               name="adjustments[{{ $index }}][adjusted_salary]"
                               value="{{ $run->adjusted_salary }}"
                               step="0.01"
                               min="0"
                               placeholder="{{ number_format($run->calculated_salary, 2) }}"
                               data-calculated="{{ $run->calculated_salary }}"
                               data-row="{{ $index }}"
                               {{ $isFinalized ? 'disabled' : '' }}>
                      </td>
                      <td class="text-end">
                        <input type="number"
                               class="form-control form-control-sm text-end bonus-amount"
                               name="adjustments[{{ $index }}][bonus_amount]"
                               value="{{ $run->bonus_amount > 0 ? $run->bonus_amount : '' }}"
                               step="0.01"
                               min="0"
                               placeholder="0.00"
                               data-row="{{ $index }}"
                               {{ $isFinalized ? 'disabled' : '' }}>
                      </td>
                      <td class="text-end">
                        <input type="number"
                               class="form-control form-control-sm text-end deduction-amount"
                               name="adjustments[{{ $index }}][deduction_amount]"
                               value="{{ $run->deduction_amount > 0 ? $run->deduction_amount : '' }}"
                               step="0.01"
                               min="0"
                               placeholder="0.00"
                               data-row="{{ $index }}"
                               {{ $isFinalized ? 'disabled' : '' }}>
                      </td>
                      <td class="text-end">
                        <strong class="final-amount text-primary" data-row="{{ $index }}">
                          {{ number_format($run->final_salary, 2) }}
                        </strong>
                      </td>
                      <td>
                        <input type="text"
                               class="form-control form-control-sm"
                               name="adjustments[{{ $index }}][adjustment_notes]"
                               value="{{ $run->adjustment_notes }}"
                               placeholder="Notes..."
                               maxlength="500"
                               {{ $isFinalized ? 'disabled' : '' }}>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="9" class="text-center py-4">
                        <div class="empty-state">
                          <i class="ti ti-users-off display-4 text-muted"></i>
                          <h5 class="mt-3">No Payroll Data</h5>
                          <p class="text-muted">No payroll records found for this period.</p>
                        </div>
                      </td>
                    </tr>
                  @endforelse
                </tbody>
                @if($payrollRuns->count() > 0)
                  <tfoot class="table-light">
                    <tr>
                      <th colspan="3" class="text-end">Totals:</th>
                      <th class="text-end">{{ number_format($payrollRuns->sum('calculated_salary'), 2) }}</th>
                      <th class="text-end" id="total-adjusted">-</th>
                      <th class="text-end" id="total-bonus">{{ number_format($payrollRuns->sum('bonus_amount'), 2) }}</th>
                      <th class="text-end" id="total-deduction">{{ number_format($payrollRuns->sum('deduction_amount'), 2) }}</th>
                      <th class="text-end" id="total-final">{{ number_format($payrollRuns->sum('final_salary'), 2) }}</th>
                      <th></th>
                    </tr>
                  </tfoot>
                @endif
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    @if($payrollRuns->count() > 0)
      <div class="row mt-4">
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <p class="text-muted mb-0">
                    <i class="ti ti-info-circle me-1"></i>
                    Save your adjustments before finalizing. Finalized payroll cannot be modified.
                  </p>
                </div>
                <div class="btn-group">
                  @if($pending > 0)
                    <button type="submit" class="btn btn-primary">
                      <i class="ti ti-device-floppy me-1"></i>Save Adjustments
                    </button>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#finalizeModal">
                      <i class="ti ti-file-export me-1"></i>Finalize & Export
                    </button>
                  @else
                    <form method="POST" action="{{ route('payroll.run.finalize-adjusted') }}" class="d-inline">
                      @csrf
                      <input type="hidden" name="period" value="{{ $selectedPeriod }}">
                      <button type="submit" class="btn btn-success">
                        <i class="ti ti-download me-1"></i>Download Bank Sheet
                      </button>
                    </form>
                  @endif
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    @endif
  </form>

  <!-- Recalculate Form (outside main form to avoid nesting) -->
  <form method="POST" action="{{ route('payroll.run.recalculate') }}" id="recalculateForm" class="d-none">
    @csrf
    <input type="hidden" name="period" value="{{ $selectedPeriod }}">
  </form>
</div>

<!-- Finalize Confirmation Modal -->
<div class="modal fade" id="finalizeModal" tabindex="-1" aria-labelledby="finalizeModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="finalizeModalLabel">
          <i class="ti ti-alert-triangle text-warning me-2"></i>Confirm Finalization
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to finalize this payroll?</p>
        <div class="alert alert-warning mb-0">
          <strong>Warning:</strong> This action cannot be undone. All payroll records will be marked as finalized and the bank submission file will be generated.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
        <form method="POST" action="{{ route('payroll.run.finalize-adjusted') }}" class="d-inline">
          @csrf
          <input type="hidden" name="period" value="{{ $selectedPeriod }}">
          <button type="submit" class="btn btn-success">
            <i class="ti ti-check me-1"></i>Finalize & Export
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-style')
<style>
.table th {
  border-top: none;
  font-weight: 600;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-control-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.8125rem;
}

input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
  opacity: 1;
}

.table-warning {
  --bs-table-bg: rgba(255, 193, 7, 0.1);
}

.table-success {
  --bs-table-bg: rgba(40, 199, 111, 0.1);
}

.final-amount {
  font-size: 0.9rem;
}
</style>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Calculate final amounts on input change
  const inputs = document.querySelectorAll('.adjusted-salary, .bonus-amount, .deduction-amount');

  inputs.forEach(function(input) {
    input.addEventListener('input', function() {
      const row = this.dataset.row;
      calculateRowTotal(row);
      calculateTotals();
    });
  });

  // Initial totals calculation
  calculateTotals();
});

function calculateRowTotal(row) {
  const adjustedInput = document.querySelector(`.adjusted-salary[data-row="${row}"]`);
  const bonusInput = document.querySelector(`.bonus-amount[data-row="${row}"]`);
  const deductionInput = document.querySelector(`.deduction-amount[data-row="${row}"]`);
  const finalDisplay = document.querySelector(`.final-amount[data-row="${row}"]`);

  const calculated = parseFloat(adjustedInput.dataset.calculated) || 0;
  const adjusted = parseFloat(adjustedInput.value) || calculated;
  const bonus = parseFloat(bonusInput.value) || 0;
  const deduction = parseFloat(deductionInput.value) || 0;

  const final = adjusted + bonus - deduction;
  finalDisplay.textContent = formatNumber(final);
}

function calculateTotals() {
  let totalBonus = 0;
  let totalDeduction = 0;
  let totalFinal = 0;

  document.querySelectorAll('.bonus-amount').forEach(function(input) {
    totalBonus += parseFloat(input.value) || 0;
  });

  document.querySelectorAll('.deduction-amount').forEach(function(input) {
    totalDeduction += parseFloat(input.value) || 0;
  });

  document.querySelectorAll('.final-amount').forEach(function(el) {
    const value = parseFloat(el.textContent.replace(/,/g, '')) || 0;
    totalFinal += value;
  });

  document.getElementById('total-bonus').textContent = formatNumber(totalBonus);
  document.getElementById('total-deduction').textContent = formatNumber(totalDeduction);
  document.getElementById('total-final').textContent = formatNumber(totalFinal);
}

function formatNumber(num) {
  return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function resetAllAdjustments() {
  if (confirm('Are you sure you want to reset all adjustments? This will clear all manual changes.')) {
    document.querySelectorAll('.adjusted-salary').forEach(function(input) {
      input.value = '';
    });
    document.querySelectorAll('.bonus-amount').forEach(function(input) {
      input.value = '';
    });
    document.querySelectorAll('.deduction-amount').forEach(function(input) {
      input.value = '';
    });
    document.querySelectorAll('input[name*="adjustment_notes"]').forEach(function(input) {
      input.value = '';
    });

    // Recalculate totals
    document.querySelectorAll('.adjusted-salary').forEach(function(input) {
      const row = input.dataset.row;
      calculateRowTotal(row);
    });
    calculateTotals();
  }
}

function recalculatePayroll() {
  if (confirm('This will recalculate all payroll data from current employee records. Any unsaved adjustments will be lost. Continue?')) {
    document.getElementById('recalculateForm').submit();
  }
}
</script>
@endsection
