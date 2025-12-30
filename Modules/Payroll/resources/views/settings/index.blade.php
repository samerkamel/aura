@extends('layouts/layoutMaster')

@section('title', 'Payroll Settings')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="ti ti-settings me-2"></i>Payroll Settings
          </h5>
          <small class="text-muted">
            Configure calculation weights
          </small>
        </div>
        <div class="card-body">
          @if (session('success'))
            <div class="alert alert-success alert-dismissible" role="alert">
              {{ session('success') }}
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @endif

          @if (session('error'))
            <div class="alert alert-danger alert-dismissible" role="alert">
              {{ session('error') }}
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @endif

          @if ($errors->any())
            <div class="alert alert-danger">
              <h6><i class="ti ti-alert-circle me-2"></i>Validation Errors</h6>
              <ul class="mb-0">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <form action="{{ route('payroll.settings.store') }}" method="POST" id="payrollSettingsForm">
            @csrf

            <div class="row">
              <div class="col-md-12">
                <div class="alert alert-info">
                  <i class="ti ti-info-circle me-2"></i>
                  Configure how much each component contributes to the final payroll calculation. The total must equal exactly 100%.
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-floating form-floating-outline mb-4">
                  <input
                    type="number"
                    class="form-control @error('attendance_weight') is-invalid @enderror"
                    id="attendance_weight"
                    name="attendance_weight"
                    value="{{ old('attendance_weight', $attendanceWeight) }}"
                    min="0"
                    max="100"
                    step="0.01"
                    placeholder="Enter attendance weight"
                    required
                  >
                  <label for="attendance_weight">Attendance Weight (%)</label>
                  @error('attendance_weight')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-floating form-floating-outline mb-4">
                  <input
                    type="number"
                    class="form-control @error('billable_hours_weight') is-invalid @enderror"
                    id="billable_hours_weight"
                    name="billable_hours_weight"
                    value="{{ old('billable_hours_weight', $billableHoursWeight) }}"
                    min="0"
                    max="100"
                    step="0.01"
                    placeholder="Enter billable hours weight"
                    required
                  >
                  <label for="billable_hours_weight">Billable Hours Weight (%)</label>
                  @error('billable_hours_weight')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="alert alert-secondary d-flex justify-content-between align-items-center">
                  <span>
                    <i class="ti ti-calculator me-2"></i>Total Weight:
                  </span>
                  <span id="totalWeight" class="fw-bold">{{ $attendanceWeight + $billableHoursWeight }}%</span>
                </div>
                <div id="weightValidationMessage" class="alert alert-warning d-none">
                  <i class="ti ti-alert-triangle me-2"></i>
                  The total weight must equal exactly 100%.
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <button type="submit" class="btn btn-primary me-2" id="saveButton">
                  <i class="ti ti-device-floppy me-2"></i>Save Settings
                </button>
                <a href="{{ route('payroll.index') }}" class="btn btn-outline-secondary">
                  <i class="ti ti-arrow-left me-2"></i>Back to Payroll
                </a>
              </div>
            </div>

          </form>
        </div>
      </div>

      <!-- Labor Cost Multiplier -->
      <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="ti ti-calculator me-2"></i>Labor Cost Multiplier
          </h5>
          <small class="text-muted">
            For project cost calculations
          </small>
        </div>
        <div class="card-body">
          <div class="alert alert-info mb-4">
            <i class="ti ti-info-circle me-2"></i>
            This multiplier is applied to labor costs when calculating project expenses.
            <br>
            <small class="text-muted">Formula: (Salary / Billable Hours) × Worked Hours × <strong>Multiplier</strong></small>
          </div>

          <form action="{{ route('payroll.settings.labor-cost-multiplier.store') }}" method="POST">
            @csrf
            <div class="row align-items-end">
              <div class="col-md-4">
                <div class="form-floating form-floating-outline mb-3">
                  <input
                    type="number"
                    class="form-control @error('labor_cost_multiplier') is-invalid @enderror"
                    id="labor_cost_multiplier"
                    name="labor_cost_multiplier"
                    value="{{ old('labor_cost_multiplier', $laborCostMultiplier) }}"
                    min="1"
                    max="10"
                    step="0.01"
                    placeholder="2.9"
                    required
                  >
                  <label for="labor_cost_multiplier">Multiplier Value</label>
                  @error('labor_cost_multiplier')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>
              <div class="col-md-4 mb-3">
                <button type="submit" class="btn btn-primary">
                  <i class="ti ti-device-floppy me-2"></i>Save Multiplier
                </button>
              </div>
              <div class="col-md-4 mb-3">
                <span class="badge bg-label-primary">Current: {{ $laborCostMultiplier }}×</span>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Target Billable Hours per Period -->
      <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="ti ti-target me-2"></i>Target Billable Hours
          </h5>
          <small class="text-muted">
            Set monthly targets (default: 6 hrs/day, max 120)
          </small>
        </div>
        <div class="card-body">
          <div class="alert alert-info mb-4">
            <i class="ti ti-info-circle me-2"></i>
            Override the default target billable hours for specific months. Leave empty to use the calculated default (6 hours × working days, capped at 120).
          </div>

          <div class="table-responsive">
            <table class="table table-bordered">
              <thead class="table-light">
                <tr>
                  <th>Period</th>
                  <th>Default Target</th>
                  <th>Custom Target</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($periodSettings as $setting)
                  <tr>
                    <td><strong>{{ $setting['period_label'] }}</strong></td>
                    <td>
                      <span class="badge bg-label-secondary">{{ $setting['default_target'] }} hrs</span>
                    </td>
                    <td>
                      <form action="{{ route('payroll.settings.period.store') }}" method="POST" class="d-flex align-items-center gap-2">
                        @csrf
                        <input type="hidden" name="period" value="{{ $setting['period'] }}">
                        <input type="number" name="target_billable_hours" class="form-control form-control-sm" style="width: 100px;"
                               value="{{ $setting['target_billable_hours'] }}" placeholder="{{ $setting['default_target'] }}"
                               min="0" max="999" step="0.01">
                        <button type="submit" class="btn btn-sm btn-primary">
                          <i class="ti ti-check"></i>
                        </button>
                      </form>
                    </td>
                    <td>
                      @if($setting['target_billable_hours'] !== null)
                        <span class="badge bg-success">Custom: {{ $setting['target_billable_hours'] }} hrs</span>
                      @else
                        <span class="badge bg-label-secondary">Using Default</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Jira Integration Settings -->
      <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="ti ti-brand-jira me-2"></i>Jira Integration Settings
          </h5>
          <small class="text-muted">
            Configure Jira API connection
          </small>
        </div>
        <div class="card-body">
          <div class="alert alert-info">
            <i class="ti ti-info-circle me-2"></i>
            Configure your Jira connection to automatically sync billable hours from worklogs.
          </div>

          <form action="{{ route('payroll.settings.jira.store') }}" method="POST" id="jiraSettingsForm">
            @csrf

            <div class="row">
              <div class="col-md-6">
                <div class="form-floating form-floating-outline mb-4">
                  <input
                    type="url"
                    class="form-control @error('jira_base_url') is-invalid @enderror"
                    id="jira_base_url"
                    name="jira_base_url"
                    value="{{ old('jira_base_url', $jiraSettings->base_url) }}"
                    placeholder="https://yourcompany.atlassian.net"
                    required
                  >
                  <label for="jira_base_url">Jira Base URL</label>
                  @error('jira_base_url')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-floating form-floating-outline mb-4">
                  <input
                    type="email"
                    class="form-control @error('jira_email') is-invalid @enderror"
                    id="jira_email"
                    name="jira_email"
                    value="{{ old('jira_email', $jiraSettings->email) }}"
                    placeholder="user@company.com"
                    required
                  >
                  <label for="jira_email">Jira Email</label>
                  @error('jira_email')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-floating form-floating-outline mb-4">
                  <input
                    type="password"
                    class="form-control @error('jira_api_token') is-invalid @enderror"
                    id="jira_api_token"
                    name="jira_api_token"
                    placeholder="{{ $jiraSettings->api_token ? '••••••••••••••••' : 'Enter API token' }}"
                  >
                  <label for="jira_api_token">API Token {{ $jiraSettings->api_token ? '(saved)' : '' }}</label>
                  @error('jira_api_token')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                  @if($jiraSettings->api_token)
                    <small class="form-text text-success">
                      <i class="ti ti-check me-1"></i>API token is saved. Leave empty to keep current token.
                    </small>
                  @else
                    <small class="form-text text-muted">
                      Generate at: <a href="https://id.atlassian.com/manage-profile/security/api-tokens" target="_blank">Atlassian Account Settings</a>
                    </small>
                  @endif
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-floating form-floating-outline mb-4">
                  <input
                    type="text"
                    class="form-control @error('jira_billable_projects') is-invalid @enderror"
                    id="jira_billable_projects"
                    name="jira_billable_projects"
                    value="{{ old('jira_billable_projects', $jiraSettings->billable_projects) }}"
                    placeholder="Leave empty for all projects"
                  >
                  <label for="jira_billable_projects">Billable Projects (comma-separated)</label>
                  @error('jira_billable_projects')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                  <small class="form-text text-muted">
                    Leave empty to include worklogs from ALL projects
                  </small>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-check form-switch mb-4">
                  <input
                    type="hidden"
                    name="jira_sync_enabled"
                    value="0"
                  >
                  <input
                    class="form-check-input"
                    type="checkbox"
                    id="jira_sync_enabled"
                    name="jira_sync_enabled"
                    value="1"
                    {{ old('jira_sync_enabled', $jiraSettings->sync_enabled) ? 'checked' : '' }}
                  >
                  <label class="form-check-label" for="jira_sync_enabled">
                    Enable Jira Sync
                  </label>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-floating form-floating-outline mb-4">
                  <select
                    class="form-select @error('jira_sync_frequency') is-invalid @enderror"
                    id="jira_sync_frequency"
                    name="jira_sync_frequency"
                  >
                    <option value="daily" {{ old('jira_sync_frequency', $jiraSettings->sync_frequency) === 'daily' ? 'selected' : '' }}>Daily</option>
                    <option value="weekly" {{ old('jira_sync_frequency', $jiraSettings->sync_frequency) === 'weekly' ? 'selected' : '' }}>Weekly</option>
                    <option value="monthly" {{ old('jira_sync_frequency', $jiraSettings->sync_frequency) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                  </select>
                  <label for="jira_sync_frequency">Sync Frequency</label>
                  @error('jira_sync_frequency')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <button type="button" class="btn btn-outline-info me-2" onclick="testJiraConnection()">
                  <i class="ti ti-plug me-2"></i>Test Connection
                </button>
                <button type="submit" class="btn btn-primary me-2">
                  <i class="ti ti-device-floppy me-2"></i>Save Jira Settings
                </button>
                @if($jiraSettings->isConfigured())
                  <span class="badge bg-success ms-2">
                    <i class="ti ti-check me-1"></i>Configured
                  </span>
                @endif
              </div>
            </div>

          </form>
        </div>
      </div>

    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const attendanceWeightInput = document.getElementById('attendance_weight');
    const billableHoursWeightInput = document.getElementById('billable_hours_weight');
    const totalWeightDisplay = document.getElementById('totalWeight');
    const saveButton = document.getElementById('saveButton');
    const validationMessage = document.getElementById('weightValidationMessage');

    function updateTotalAndValidation() {
        const attendanceWeight = parseFloat(attendanceWeightInput.value) || 0;
        const billableHoursWeight = parseFloat(billableHoursWeightInput.value) || 0;
        const total = attendanceWeight + billableHoursWeight;

        // Update total display
        totalWeightDisplay.textContent = total.toFixed(2) + '%';

        // Check if total equals 100
        const isValid = Math.abs(total - 100) < 0.01; // Allow for small floating-point errors

        if (isValid) {
            saveButton.disabled = false;
            saveButton.classList.remove('btn-secondary');
            saveButton.classList.add('btn-primary');
            validationMessage.classList.add('d-none');
            totalWeightDisplay.classList.remove('text-danger');
            totalWeightDisplay.classList.add('text-success');
        } else {
            saveButton.disabled = true;
            saveButton.classList.remove('btn-primary');
            saveButton.classList.add('btn-secondary');
            validationMessage.classList.remove('d-none');
            totalWeightDisplay.classList.remove('text-success');
            totalWeightDisplay.classList.add('text-danger');
        }
    }

    // Add event listeners
    attendanceWeightInput.addEventListener('input', updateTotalAndValidation);
    attendanceWeightInput.addEventListener('change', updateTotalAndValidation);
    billableHoursWeightInput.addEventListener('input', updateTotalAndValidation);
    billableHoursWeightInput.addEventListener('change', updateTotalAndValidation);

    // Initial validation
    updateTotalAndValidation();

    // Form submission prevention if invalid
    document.getElementById('payrollSettingsForm').addEventListener('submit', function(e) {
        const attendanceWeight = parseFloat(attendanceWeightInput.value) || 0;
        const billableHoursWeight = parseFloat(billableHoursWeightInput.value) || 0;
        const total = attendanceWeight + billableHoursWeight;

        if (Math.abs(total - 100) >= 0.01) {
            e.preventDefault();
            alert('The total weight must equal exactly 100% before saving.');
            return false;
        }
    });
});

function testJiraConnection() {
    const button = event.target;
    const originalText = button.innerHTML;

    // Show loading state
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Testing...';

    // Get current form values
    const baseUrl = document.getElementById('jira_base_url').value;
    const email = document.getElementById('jira_email').value;
    const apiToken = document.getElementById('jira_api_token').value;
    const hasStoredToken = {{ $jiraSettings->api_token ? 'true' : 'false' }};

    if (!baseUrl || !email) {
        alert('Please fill in the Jira Base URL and Email before testing.');
        button.disabled = false;
        button.innerHTML = originalText;
        return;
    }

    if (!apiToken && !hasStoredToken) {
        alert('Please enter an API token before testing.');
        button.disabled = false;
        button.innerHTML = originalText;
        return;
    }
    
    // Make AJAX request to test endpoint
    fetch('{{ route("payroll.settings.jira.test") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            jira_base_url: baseUrl,
            jira_email: email,
            jira_api_token: apiToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Connection test successful! ✓');
        } else {
            alert('Connection test failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Connection test failed: Network error');
    })
    .finally(() => {
        // Restore button state
        button.disabled = false;
        button.innerHTML = originalText;
    });
}
</script>
@endsection
