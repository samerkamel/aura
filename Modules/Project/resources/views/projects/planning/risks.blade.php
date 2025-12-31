@extends('layouts/layoutMaster')

@section('title', 'Risk Management - ' . $project->name)

@section('vendor-style')
@vite('resources/assets/vendor/libs/select2/select2.scss')
@endsection

@section('vendor-script')
@vite('resources/assets/vendor/libs/select2/select2.js')
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Page Header -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center">
              <div class="avatar avatar-lg me-4">
                <span class="avatar-initial rounded-circle bg-label-warning">
                  <i class="ti ti-alert-triangle ti-lg"></i>
                </span>
              </div>
              <div>
                <h4 class="mb-0">{{ $project->name }}</h4>
                <p class="text-muted mb-0">
                  <span class="badge bg-label-primary me-2">{{ $project->code }}</span>
                  Risk Management
                </p>
              </div>
            </div>
            <div class="d-flex gap-2">
              <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Back to Project
              </a>
              <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRiskModal">
                <i class="ti ti-plus me-1"></i>Add Risk
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  @if (session('success'))
    <div class="alert alert-success alert-dismissible mb-4" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- Risk Summary Cards -->
  @php
    $activeRisks = $risks->whereNotIn('status', ['resolved', 'accepted']);
    $highRisks = $risks->filter(fn($r) => $r->getRiskLevel() === 'critical' || $r->getRiskLevel() === 'high');
    $resolvedRisks = $risks->where('status', 'resolved');
    $totalCostImpact = $activeRisks->sum('potential_cost_impact');
  @endphp
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card h-100 border-start border-4 border-primary">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-md bg-label-primary me-3 d-flex align-items-center justify-content-center">
              <i class="ti ti-list ti-md"></i>
            </div>
            <div>
              <h4 class="mb-0">{{ $activeRisks->count() }}</h4>
              <small class="text-muted">Active Risks</small>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card h-100 border-start border-4 border-danger">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-md bg-label-danger me-3 d-flex align-items-center justify-content-center">
              <i class="ti ti-alert-circle ti-md"></i>
            </div>
            <div>
              <h4 class="mb-0">{{ $highRisks->count() }}</h4>
              <small class="text-muted">High/Critical</small>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card h-100 border-start border-4 border-success">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-md bg-label-success me-3 d-flex align-items-center justify-content-center">
              <i class="ti ti-check ti-md"></i>
            </div>
            <div>
              <h4 class="mb-0">{{ $resolvedRisks->count() }}</h4>
              <small class="text-muted">Resolved</small>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card h-100 border-start border-4 border-warning">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-md bg-label-warning me-3 d-flex align-items-center justify-content-center">
              <i class="ti ti-currency-dollar ti-md"></i>
            </div>
            <div>
              <h4 class="mb-0">${{ number_format($totalCostImpact) }}</h4>
              <small class="text-muted">Potential Impact</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Risk Matrix Visualization -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">Risk Matrix</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered text-center mb-0">
              <thead>
                <tr>
                  <th rowspan="2" class="align-middle">Impact</th>
                  <th colspan="4">Probability</th>
                </tr>
                <tr>
                  <th>Low</th>
                  <th>Medium</th>
                  <th>High</th>
                  <th>Very High</th>
                </tr>
              </thead>
              <tbody>
                @php
                  $impactLevels = ['critical', 'high', 'medium', 'low'];
                  $probLevels = ['low', 'medium', 'high', 'very_high'];
                  $cellColors = [
                    'critical' => ['warning', 'danger', 'danger', 'danger'],
                    'high' => ['info', 'warning', 'danger', 'danger'],
                    'medium' => ['success', 'info', 'warning', 'danger'],
                    'low' => ['success', 'success', 'info', 'warning'],
                  ];
                @endphp
                @foreach($impactLevels as $impact)
                  <tr>
                    <th>{{ ucfirst($impact) }}</th>
                    @foreach($probLevels as $idx => $prob)
                      @php
                        $cellRisks = $risks->filter(fn($r) => $r->impact === $impact && $r->probability === $prob && !in_array($r->status, ['resolved', 'accepted']));
                      @endphp
                      <td class="bg-label-{{ $cellColors[$impact][$idx] }}">
                        @if($cellRisks->count() > 0)
                          <span class="badge bg-{{ $cellColors[$impact][$idx] }}">{{ $cellRisks->count() }}</span>
                        @else
                          -
                        @endif
                      </td>
                    @endforeach
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Risks List -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">
        <i class="ti ti-list me-2"></i>All Risks ({{ $risks->count() }})
      </h5>
    </div>
    <div class="card-body">
      @if($risks->count() > 0)
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Risk</th>
                <th>Category</th>
                <th class="text-center">Score</th>
                <th>Status</th>
                <th>Owner</th>
                <th>Impact</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($risks as $risk)
                <tr>
                  <td>
                    <div>
                      <h6 class="mb-0">{{ $risk->title }}</h6>
                      @if($risk->description)
                        <small class="text-muted">{{ Str::limit($risk->description, 50) }}</small>
                      @endif
                    </div>
                  </td>
                  <td>
                    <span class="badge bg-label-secondary">{{ ucfirst($risk->category) }}</span>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-{{ $risk->getRiskLevelColor() }} fs-6">
                      {{ $risk->risk_score }}
                    </span>
                    <br>
                    <small class="text-{{ $risk->getRiskLevelColor() }}">{{ ucfirst($risk->getRiskLevel()) }}</small>
                  </td>
                  <td>
                    @php
                      $statusColors = [
                        'identified' => 'secondary',
                        'analyzing' => 'info',
                        'mitigating' => 'primary',
                        'monitoring' => 'warning',
                        'resolved' => 'success',
                        'accepted' => 'dark',
                      ];
                    @endphp
                    <span class="badge bg-label-{{ $statusColors[$risk->status] ?? 'secondary' }}">
                      {{ ucfirst($risk->status) }}
                    </span>
                  </td>
                  <td>
                    @if($risk->owner)
                      <small>{{ $risk->owner->name }}</small>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    <small>
                      @if($risk->potential_cost_impact)
                        <i class="ti ti-currency-dollar"></i>${{ number_format($risk->potential_cost_impact) }}
                      @endif
                      @if($risk->potential_delay_days)
                        <br><i class="ti ti-clock"></i>{{ $risk->potential_delay_days }} days
                      @endif
                      @if(!$risk->potential_cost_impact && !$risk->potential_delay_days)
                        <span class="text-muted">-</span>
                      @endif
                    </small>
                  </td>
                  <td>
                    <div class="d-flex gap-1">
                      <button type="button" class="btn btn-sm btn-icon btn-outline-primary"
                              data-bs-toggle="modal" data-bs-target="#editRiskModal"
                              onclick="editRisk({{ json_encode($risk) }})">
                        <i class="ti ti-edit"></i>
                      </button>
                      <form method="POST" action="{{ route('projects.planning.risks.destroy', [$project, $risk]) }}"
                            class="d-inline" onsubmit="return confirm('Delete this risk?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-icon btn-outline-danger">
                          <i class="ti ti-trash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="text-center py-5">
          <i class="ti ti-shield-check ti-lg text-success mb-3" style="font-size: 3rem;"></i>
          <h5 class="text-muted">No Risks Identified</h5>
          <p class="text-muted mb-4">Great! This project has no identified risks.</p>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRiskModal">
            <i class="ti ti-plus me-1"></i>Add Risk
          </button>
        </div>
      @endif
    </div>
  </div>
</div>

<!-- Add Risk Modal -->
<div class="modal fade" id="addRiskModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="{{ route('projects.planning.risks.store', $project) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Add Risk</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="title" class="form-label">Risk Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="title" name="title" required>
          </div>
          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
              <select class="form-select" id="category" name="category" required>
                <option value="technical">Technical</option>
                <option value="resource">Resource</option>
                <option value="schedule">Schedule</option>
                <option value="budget">Budget</option>
                <option value="scope">Scope</option>
                <option value="external">External</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="probability" class="form-label">Probability <span class="text-danger">*</span></label>
              <select class="form-select" id="probability" name="probability" required>
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
                <option value="very_high">Very High</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="impact" class="form-label">Impact <span class="text-danger">*</span></label>
              <select class="form-select" id="impact" name="impact" required>
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
                <option value="critical">Critical</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="owner_id" class="form-label">Risk Owner</label>
              <select class="form-select select2" id="owner_id" name="owner_id">
                <option value="">Not Assigned</option>
                @foreach($employees as $employee)
                  <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="target_resolution_date" class="form-label">Target Resolution Date</label>
              <input type="date" class="form-control" id="target_resolution_date" name="target_resolution_date">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="potential_cost_impact" class="form-label">Potential Cost Impact ($)</label>
              <input type="number" class="form-control" id="potential_cost_impact" name="potential_cost_impact" min="0" step="100">
            </div>
            <div class="col-md-6 mb-3">
              <label for="potential_delay_days" class="form-label">Potential Delay (days)</label>
              <input type="number" class="form-control" id="potential_delay_days" name="potential_delay_days" min="0">
            </div>
          </div>
          <div class="mb-3">
            <label for="mitigation_plan" class="form-label">Mitigation Plan</label>
            <textarea class="form-control" id="mitigation_plan" name="mitigation_plan" rows="2"
                      placeholder="Steps to reduce the probability or impact of this risk"></textarea>
          </div>
          <div class="mb-3">
            <label for="contingency_plan" class="form-label">Contingency Plan</label>
            <textarea class="form-control" id="contingency_plan" name="contingency_plan" rows="2"
                      placeholder="Actions to take if this risk materializes"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Risk</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Risk Modal -->
<div class="modal fade" id="editRiskModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" id="editRiskForm">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title">Edit Risk</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="edit_title" class="form-label">Risk Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="edit_title" name="title" required>
          </div>
          <div class="mb-3">
            <label for="edit_description" class="form-label">Description</label>
            <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
          </div>
          <div class="row">
            <div class="col-md-3 mb-3">
              <label for="edit_category" class="form-label">Category</label>
              <select class="form-select" id="edit_category" name="category" required>
                <option value="technical">Technical</option>
                <option value="resource">Resource</option>
                <option value="schedule">Schedule</option>
                <option value="budget">Budget</option>
                <option value="scope">Scope</option>
                <option value="external">External</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="col-md-3 mb-3">
              <label for="edit_probability" class="form-label">Probability</label>
              <select class="form-select" id="edit_probability" name="probability" required>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="very_high">Very High</option>
              </select>
            </div>
            <div class="col-md-3 mb-3">
              <label for="edit_impact" class="form-label">Impact</label>
              <select class="form-select" id="edit_impact" name="impact" required>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="critical">Critical</option>
              </select>
            </div>
            <div class="col-md-3 mb-3">
              <label for="edit_status" class="form-label">Status</label>
              <select class="form-select" id="edit_status" name="status" required>
                <option value="identified">Identified</option>
                <option value="analyzing">Analyzing</option>
                <option value="mitigating">Mitigating</option>
                <option value="monitoring">Monitoring</option>
                <option value="resolved">Resolved</option>
                <option value="accepted">Accepted</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_owner_id" class="form-label">Risk Owner</label>
              <select class="form-select" id="edit_owner_id" name="owner_id">
                <option value="">Not Assigned</option>
                @foreach($employees as $employee)
                  <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_target_resolution_date" class="form-label">Target Resolution Date</label>
              <input type="date" class="form-control" id="edit_target_resolution_date" name="target_resolution_date">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_potential_cost_impact" class="form-label">Potential Cost Impact ($)</label>
              <input type="number" class="form-control" id="edit_potential_cost_impact" name="potential_cost_impact" min="0" step="100">
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_potential_delay_days" class="form-label">Potential Delay (days)</label>
              <input type="number" class="form-control" id="edit_potential_delay_days" name="potential_delay_days" min="0">
            </div>
          </div>
          <div class="mb-3">
            <label for="edit_mitigation_plan" class="form-label">Mitigation Plan</label>
            <textarea class="form-control" id="edit_mitigation_plan" name="mitigation_plan" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <label for="edit_contingency_plan" class="form-label">Contingency Plan</label>
            <textarea class="form-control" id="edit_contingency_plan" name="contingency_plan" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
    jQuery('.select2').select2({
      theme: 'bootstrap-5',
      allowClear: true,
      dropdownParent: jQuery('#addRiskModal')
    });
  }
});

function editRisk(risk) {
  const form = document.getElementById('editRiskForm');
  form.action = `/projects/{{ $project->id }}/planning/risks/${risk.id}`;

  document.getElementById('edit_title').value = risk.title;
  document.getElementById('edit_description').value = risk.description || '';
  document.getElementById('edit_category').value = risk.category;
  document.getElementById('edit_probability').value = risk.probability;
  document.getElementById('edit_impact').value = risk.impact;
  document.getElementById('edit_status').value = risk.status;
  document.getElementById('edit_owner_id').value = risk.owner_id || '';
  document.getElementById('edit_target_resolution_date').value = risk.target_resolution_date ? risk.target_resolution_date.split('T')[0] : '';
  document.getElementById('edit_potential_cost_impact').value = risk.potential_cost_impact || '';
  document.getElementById('edit_potential_delay_days').value = risk.potential_delay_days || '';
  document.getElementById('edit_mitigation_plan').value = risk.mitigation_plan || '';
  document.getElementById('edit_contingency_plan').value = risk.contingency_plan || '';
}
</script>
@endsection
@endsection
