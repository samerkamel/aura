@extends('layouts/layoutMaster')

@section('title', 'Process Off-boarding - ' . $employee->name)

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Off-boarding Header Card -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <i class="ti tabler-user-off me-2 text-warning" style="font-size: 1.5rem;"></i>
          <div>
            <h5 class="mb-0">Process Off-boarding</h5>
            <small class="text-muted">{{ $employee->name }} - {{ $employee->position ?? 'No Position' }}</small>
          </div>
        </div>
        <div class="d-flex gap-2">
          <a href="{{ route('hr.employees.show', $employee) }}" class="btn btn-outline-secondary">
            <i class="ti tabler-arrow-left me-1"></i>Back to Employee
          </a>
        </div>
      </div>
    </div>

    <form method="POST" action="{{ route('hr.employees.offboarding.process', $employee) }}">
      @csrf

      <div class="row">
        <!-- Off-boarding Form -->
        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header">
              <h6 class="mb-0">
                <i class="ti tabler-forms me-2"></i>Off-boarding Details
              </h6>
            </div>
            <div class="card-body">
              <!-- Termination Date -->
              <div class="mb-3">
                <label for="termination_date" class="form-label">Termination Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('termination_date') is-invalid @enderror"
                       id="termination_date" name="termination_date"
                       value="{{ old('termination_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required>
                @error('termination_date')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Status -->
              <div class="mb-3">
                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                  <option value="">Select Status</option>
                  <option value="terminated" {{ old('status') === 'terminated' ? 'selected' : '' }}>Terminated</option>
                  <option value="resigned" {{ old('status') === 'resigned' ? 'selected' : '' }}>Resigned</option>
                </select>
                @error('status')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Notes -->
              <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control @error('notes') is-invalid @enderror"
                          id="notes" name="notes" rows="4"
                          placeholder="Optional notes about the off-boarding process...">{{ old('notes') }}</textarea>
                @error('notes')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
        </div>

        <!-- Final Pay Calculation -->
        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header">
              <h6 class="mb-0">
                <i class="ti tabler-currency-dollar me-2"></i>Final Pay Calculation
              </h6>
            </div>
            <div class="card-body">
              <div class="alert alert-info">
                <small><i class="ti tabler-info-circle me-1"></i>This calculation is based on the current date. It will be recalculated based on the actual termination date when you submit the form.</small>
              </div>

              <div class="row mb-2">
                <div class="col-sm-6"><strong>Employee:</strong></div>
                <div class="col-sm-6">{{ $payrollCalculation['employee_name'] }}</div>
              </div>

              <div class="row mb-2">
                <div class="col-sm-6"><strong>Base Salary:</strong></div>
                <div class="col-sm-6">${{ number_format($payrollCalculation['base_salary'], 2) }}</div>
              </div>

              <div class="row mb-2">
                <div class="col-sm-6"><strong>Period:</strong></div>
                <div class="col-sm-6">{{ $payrollCalculation['last_payroll_date'] }} to {{ $payrollCalculation['termination_date'] }}</div>
              </div>

              <div class="row mb-2">
                <div class="col-sm-6"><strong>Working Days:</strong></div>
                <div class="col-sm-6">{{ $payrollCalculation['working_days'] }} days</div>
              </div>

              <div class="row mb-2">
                <div class="col-sm-6"><strong>Daily Rate:</strong></div>
                <div class="col-sm-6">${{ number_format($payrollCalculation['daily_rate'], 2) }}</div>
              </div>

              <hr>

              <div class="row">
                <div class="col-sm-6"><strong>Final Amount:</strong></div>
                <div class="col-sm-6"><strong class="text-success">${{ number_format($payrollCalculation['pro_rated_amount'], 2) }}</strong></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Assigned Assets Checklist -->
      <div class="row">
        <div class="col-12 mb-4">
          <div class="card">
            <div class="card-header">
              <h6 class="mb-0">
                <i class="ti tabler-device-laptop me-2"></i>Assigned Assets Checklist
              </h6>
            </div>
            <div class="card-body">
              @if($employee->currentAssets->count() > 0)
                <div class="alert alert-warning">
                  <i class="ti tabler-alert-triangle me-1"></i>
                  <strong>Important:</strong> The following assets are currently assigned to this employee and will be marked as returned when the off-boarding is processed.
                </div>

                <div class="table-responsive">
                  <table class="table table-borderless">
                    <thead>
                      <tr>
                        <th>Asset Name</th>
                        <th>Type</th>
                        <th>Serial Number</th>
                        <th>Assigned Date</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($employee->currentAssets as $asset)
                        <tr>
                          <td>
                            <div class="d-flex align-items-center">
                              <i class="ti tabler-device-desktop me-2 text-muted"></i>
                              {{ $asset->name }}
                            </div>
                          </td>
                          <td>{{ $asset->type }}</td>
                          <td>{{ $asset->serial_number ?? 'N/A' }}</td>
                          <td>{{ $asset->pivot->assigned_date ? \Carbon\Carbon::parse($asset->pivot->assigned_date)->format('M d, Y') : 'N/A' }}</td>
                          <td>
                            <span class="badge bg-warning">To be Returned</span>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @else
                <div class="alert alert-success">
                  <i class="ti tabler-check me-1"></i>
                  No assets are currently assigned to this employee.
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>

      <!-- Confirmation and Submit -->
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <div class="alert alert-danger">
                <h6><i class="ti tabler-alert-triangle me-1"></i>Confirmation Required</h6>
                <p class="mb-0">
                  By submitting this form, you confirm that:
                </p>
                <ul class="mb-0 mt-2">
                  <li>The termination/resignation date is accurate</li>
                  <li>All assigned assets will be marked as returned</li>
                  <li>The final pay calculation will be processed</li>
                  <li>The employee status will be permanently updated</li>
                </ul>
              </div>

              <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('hr.employees.show', $employee) }}" class="btn btn-outline-secondary">
                  <i class="ti tabler-x me-1"></i>Cancel
                </a>
                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to process this off-boarding? This action cannot be undone.')">
                  <i class="ti tabler-check me-1"></i>Process Off-boarding
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@section('page-script')
<script>
// Update final pay calculation when termination date changes
document.getElementById('termination_date').addEventListener('change', function() {
    // You could add AJAX call here to recalculate final pay based on selected date
    // For now, we'll show a note that calculation will be updated on submit
});
</script>
@endsection
