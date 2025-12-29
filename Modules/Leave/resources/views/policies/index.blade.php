@extends('layouts/layoutMaster')

@section('title', 'Leave Policy Management')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">Leave Policy Management</h5>
        <p class="card-text">Configure company-wide PTO and Sick Leave policies including annual grants, accrual rates, and caps.</p>
      </div>
    </div>
  </div>
</div>
<br>
@if(session('success'))
<div class="alert alert-success alert-dismissible" role="alert">
  {{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible" role="alert">
  <ul class="mb-0">
    @foreach($errors->all() as $error)
      <li>{{ $error }}</li>
    @endforeach
  </ul>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row">
  <!-- PTO Policy Configuration -->
  <div class="col-12 col-lg-8">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="ti tabler-calendar-event me-2"></i>PTO Policy Configuration
        </h5>
      </div>
      <div class="card-body">
        <form action="{{ route('leave.policies.update-pto') }}" method="POST" id="ptoForm">
          @csrf
          @method('PUT')

          <div class="mb-3">
            <label for="pto_name" class="form-label">Policy Name</label>
            <input type="text" class="form-control" id="pto_name" name="name"
                   value="{{ $ptoPolicies->first()->name ?? 'Standard PTO Policy' }}" required>
          </div>

          <div class="mb-3">
            <label for="pto_description" class="form-label">Description</label>
            <textarea class="form-control" id="pto_description" name="description" rows="2">{{ $ptoPolicies->first()->description ?? '' }}</textarea>
          </div>

          <div class="mb-3">
            <label for="initial_days" class="form-label">Initial Days Granted</label>
            <input type="number" class="form-control" id="initial_days" name="initial_days"
                   value="{{ $ptoPolicies->first()->initial_days ?? 6 }}" min="0" required>
            <small class="form-text text-muted">Number of PTO days granted to every employee at the start of the year.</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Accrual Tiers by Years of Service</label>
            <div id="ptoTiers">
              @if($ptoPolicies->first() && $ptoPolicies->first()->tiers->count() > 0)
                @foreach($ptoPolicies->first()->tiers as $index => $tier)
                  <div class="tier-row mb-3 p-3 border rounded">
                    <div class="row align-items-center">
                      <div class="col-md-3">
                        <label class="form-label">Min Years</label>
                        <input type="number" class="form-control" name="tiers[{{ $index }}][min_years]"
                               value="{{ $tier->min_years }}" min="0" required>
                      </div>
                      <div class="col-md-3">
                        <label class="form-label">Max Years</label>
                        <input type="number" class="form-control" name="tiers[{{ $index }}][max_years]"
                               value="{{ $tier->max_years }}" min="0" placeholder="Unlimited">
                      </div>
                      <div class="col-md-3">
                        <label class="form-label">Annual Days</label>
                        <input type="number" class="form-control" name="tiers[{{ $index }}][annual_days]"
                               value="{{ $tier->annual_days }}" min="1" required>
                      </div>
                      <div class="col-md-2">
                        <label class="form-label">Monthly Rate</label>
                        <input type="text" class="form-control monthly-rate" readonly
                               value="{{ $tier->monthly_accrual_rate }}">
                      </div>
                      <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-danger remove-tier">
                          <i class="ti tabler-trash"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                @endforeach
              @else
                <div class="tier-row mb-3 p-3 border rounded">
                  <div class="row align-items-center">
                    <div class="col-md-3">
                      <label class="form-label">Min Years</label>
                      <input type="number" class="form-control" name="tiers[0][min_years]" value="0" min="0" required>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Max Years</label>
                      <input type="number" class="form-control" name="tiers[0][max_years]" value="2" min="0" placeholder="Unlimited">
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Annual Days</label>
                      <input type="number" class="form-control" name="tiers[0][annual_days]" value="15" min="1" required>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">Monthly Rate</label>
                      <input type="text" class="form-control monthly-rate" readonly value="1.25">
                    </div>
                    <div class="col-md-1">
                      <button type="button" class="btn btn-sm btn-danger remove-tier">
                        <i class="ti tabler-trash"></i>
                      </button>
                    </div>
                  </div>
                </div>
              @endif
            </div>
            <button type="button" class="btn btn-sm btn-secondary" id="addTier">
              <i class="ti tabler-plus me-1"></i>Add Tier
            </button>
          </div>

          <button type="submit" class="btn btn-primary">
            <i class="ti tabler-device-floppy me-1"></i>Save PTO Policy
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Sick Leave Policy Configuration -->
  <div class="col-12 col-lg-4">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="ti tabler-medical-cross me-2"></i>Sick Leave Policy Configuration
        </h5>
      </div>
      <div class="card-body">
        <form action="{{ route('leave.policies.update-sick-leave') }}" method="POST">
          @csrf
          @method('PUT')

          <div class="mb-3">
            <label for="sick_name" class="form-label">Policy Name</label>
            <input type="text" class="form-control" id="sick_name" name="name"
                   value="{{ $sickLeavePolicies->first()->name ?? 'Standard Sick Leave Policy' }}" required>
          </div>

          <div class="mb-3">
            <label for="sick_description" class="form-label">Description</label>
            <textarea class="form-control" id="sick_description" name="description" rows="2">{{ $sickLeavePolicies->first()->description ?? '' }}</textarea>
          </div>

          <div class="mb-3">
            <label for="sick_days" class="form-label">Days Granted</label>
            <input type="number" class="form-control" id="sick_days" name="days"
                   value="{{ $sickLeavePolicies->first()->config['days'] ?? 60 }}" min="1" required>
          </div>

          <div class="mb-3">
            <label for="sick_period" class="form-label">Time Period (Years)</label>
            <input type="number" class="form-control" id="sick_period" name="period_in_years"
                   value="{{ $sickLeavePolicies->first()->config['period_in_years'] ?? 3 }}" min="1" required>
            <small class="form-text text-muted">The time period for which the sick leave days are granted.</small>
          </div>

          <div class="mb-3">
            <div class="alert alert-info">
              <i class="ti tabler-info-circle me-2"></i>
              <strong>Current Policy:</strong>
              <span id="sickLeavePreview">
                {{ $sickLeavePolicies->first()->config['days'] ?? 60 }} days every
                {{ $sickLeavePolicies->first()->config['period_in_years'] ?? 3 }} years
              </span>
            </div>
          </div>

          <button type="submit" class="btn btn-primary">
            <i class="ti tabler-device-floppy me-1"></i>Save Sick Leave Policy
          </button>
        </form>
      </div>
    </div>

    <!-- Emergency Leave Policy Configuration -->
    <div class="card mt-4">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="ti tabler-urgent me-2"></i>Emergency Leave Policy Configuration
        </h5>
      </div>
      <div class="card-body">
        <form action="{{ route('leave.policies.update-emergency') }}" method="POST">
          @csrf
          @method('PUT')

          <div class="mb-3">
            <label for="emergency_name" class="form-label">Policy Name</label>
            <input type="text" class="form-control" id="emergency_name" name="name"
                   value="{{ $emergencyLeavePolicies->first()->name ?? 'Emergency Leave' }}" required>
          </div>

          <div class="mb-3">
            <label for="emergency_description" class="form-label">Description</label>
            <textarea class="form-control" id="emergency_description" name="description" rows="2">{{ $emergencyLeavePolicies->first()->description ?? 'Emergency leave for urgent personal or family matters' }}</textarea>
          </div>

          <div class="mb-3">
            <label for="emergency_days" class="form-label">Days Per Year</label>
            <input type="number" class="form-control" id="emergency_days" name="days_per_year"
                   value="{{ $emergencyLeavePolicies->first()->initial_days ?? 6 }}" min="1" required>
            <small class="form-text text-muted">Number of emergency leave days granted each year.</small>
          </div>

          <div class="mb-3">
            <div class="alert alert-warning">
              <i class="ti tabler-alert-triangle me-2"></i>
              <strong>Current Policy:</strong>
              <span id="emergencyLeavePreview">
                {{ $emergencyLeavePolicies->first()->initial_days ?? 6 }} days per year
              </span>
            </div>
          </div>

          <button type="submit" class="btn btn-primary">
            <i class="ti tabler-device-floppy me-1"></i>Save Emergency Leave Policy
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  let tierIndex = {{ $ptoPolicies->first() ? $ptoPolicies->first()->tiers->count() : 1 }};

  // Function to calculate monthly accrual rate
  function calculateMonthlyRate(annualDays) {
    return (annualDays / 12).toFixed(2);
  }

  // Update monthly accrual rates when annual days change
  document.addEventListener('input', function(e) {
    if (e.target.name && e.target.name.includes('[annual_days]')) {
      const tierRow = e.target.closest('.tier-row');
      const monthlyRateInput = tierRow.querySelector('.monthly-rate');
      monthlyRateInput.value = calculateMonthlyRate(e.target.value);
    }
  });

  // Add new tier functionality
  document.getElementById('addTier').addEventListener('click', function() {
    const tierHtml = `
      <div class="tier-row mb-3 p-3 border rounded">
        <div class="row align-items-center">
          <div class="col-md-3">
            <label class="form-label">Min Years</label>
            <input type="number" class="form-control" name="tiers[${tierIndex}][min_years]" value="0" min="0" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Max Years</label>
            <input type="number" class="form-control" name="tiers[${tierIndex}][max_years]" min="0" placeholder="Unlimited">
          </div>
          <div class="col-md-3">
            <label class="form-label">Annual Days</label>
            <input type="number" class="form-control" name="tiers[${tierIndex}][annual_days]" value="15" min="1" required>
          </div>
          <div class="col-md-2">
            <label class="form-label">Monthly Rate</label>
            <input type="text" class="form-control monthly-rate" readonly value="1.25">
          </div>
          <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-danger remove-tier">
              <i class="ti tabler-trash"></i>
            </button>
          </div>
        </div>
      </div>
    `;
    document.getElementById('ptoTiers').insertAdjacentHTML('beforeend', tierHtml);
    tierIndex++;
  });

  // Remove tier functionality
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-tier') || e.target.closest('.remove-tier')) {
      const tierRow = e.target.closest('.tier-row');
      if (document.querySelectorAll('.tier-row').length > 1) {
        tierRow.remove();
      } else {
        alert('At least one tier is required.');
      }
    }
  });

  // Update sick leave preview
  document.addEventListener('input', function(e) {
    if (e.target.id === 'sick_days' || e.target.id === 'sick_period') {
      const days = document.getElementById('sick_days').value;
      const period = document.getElementById('sick_period').value;
      document.getElementById('sickLeavePreview').textContent = `${days} days every ${period} years`;
    }
  });

  // Update emergency leave preview
  document.addEventListener('input', function(e) {
    if (e.target.id === 'emergency_days') {
      const days = document.getElementById('emergency_days').value;
      document.getElementById('emergencyLeavePreview').textContent = `${days} days per year`;
    }
  });
});
</script>
@endsection
