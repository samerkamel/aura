@extends('layouts/layoutMaster')

@section('title', 'Run & Review Payroll')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header -->
  <div class="row">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="ti tabler-calculator me-2"></i>Run & Review Payroll
          </h5>
          <small class="text-muted">
            Review payroll calculations for {{ $periodStart->format('F Y') }}
          </small>
        </div>
        <div class="card-body">
          <!-- Period Selection -->
          <form method="GET" class="mb-4">
            <div class="row align-items-end">
              <div class="col-md-4">
                <label for="period" class="form-label">Select Payroll Period</label>
                <select name="period" id="period" class="form-select" onchange="this.form.submit()">
                  @foreach($periodOptions as $option)
                    <option value="{{ $option['value'] }}" {{ $option['selected'] ? 'selected' : '' }}>
                      {{ $option['label'] }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-8">
                <div class="alert alert-info mb-0">
                  <i class="ti tabler-info-circle me-2"></i>
                  <strong>Period:</strong> {{ $periodStart->format('M j, Y') }} - {{ $periodEnd->format('M j, Y') }}
                  <span class="ms-3"><strong>Employees:</strong> {{ $employeeSummaries->count() }}</span>
                  @if($employeeSummaries->count() > 0)
                    <span class="ms-3"><strong>Required Hours:</strong> {{ $employeeSummaries->first()['required_monthly_hours'] }}h</span>
                    <span class="ms-3"><strong>Target Billable:</strong> {{ $employeeSummaries->first()['target_billable_hours'] }}h</span>
                  @endif
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Payroll Summary Table -->
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Employee Payroll Summary</h5>
          <small class="text-muted">Detailed breakdown of performance calculations</small>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead class="table-light">
                <tr>
                  <th>Employee</th>
                  <th class="text-center">Net Attended<br><small class="text-muted">Hours</small></th>
                  <th class="text-center">Attendance<br><small class="text-muted">%</small></th>
                  <th class="text-center">Billable<br><small class="text-muted">Hours</small></th>
                  <th class="text-center">Billable<br><small class="text-muted">%</small></th>
                  <th class="text-center">Final Performance<br><small class="text-muted">%</small></th>
                  <th class="text-center">Additional Info</th>
                </tr>
              </thead>
              <tbody>
                @forelse($employeeSummaries as $summary)
                  <tr>
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-3">
                          <span class="avatar-initial rounded-circle bg-label-primary">
                            {{ substr($summary['employee']->name, 0, 2) }}
                          </span>
                        </div>
                        <div>
                          <strong>{{ $summary['employee']->name }}</strong>
                          <br>
                          <small class="text-muted">{{ $summary['employee']->position }}</small>
                        </div>
                      </div>
                    </td>
                    <td class="text-center">
                      <span class="badge bg-label-info">{{ $summary['net_attended_hours'] }}h</span>
                    </td>
                    <td class="text-center">
                      <span class="badge {{ $summary['attendance_percentage'] >= 90 ? 'bg-label-success' : ($summary['attendance_percentage'] >= 75 ? 'bg-label-warning' : 'bg-label-danger') }}">
                        {{ $summary['attendance_percentage'] }}%
                      </span>
                      <br>
                      <small class="text-muted">({{ $summary['attendance_weight'] }}% weight)</small>
                    </td>
                    <td class="text-center">
                      @if(isset($summary['billable_hours_applicable']) && !$summary['billable_hours_applicable'])
                        <span class="badge bg-label-secondary">N/A</span>
                      @else
                        <span class="badge bg-label-info">{{ $summary['billable_hours'] }}h</span>
                        @if(isset($summary['jira_worklog_hours']) && $summary['jira_worklog_hours'] > 0)
                          <br>
                          <small class="text-muted">
                            <i class="ti tabler-brand-jira"></i> {{ $summary['jira_worklog_hours'] }}h
                            @if(isset($summary['manual_billable_hours']) && $summary['manual_billable_hours'] > 0)
                              + {{ $summary['manual_billable_hours'] }}h
                            @endif
                          </small>
                        @endif
                      @endif
                    </td>
                    <td class="text-center">
                      @if(isset($summary['billable_hours_applicable']) && !$summary['billable_hours_applicable'])
                        <span class="badge bg-label-secondary">N/A</span>
                        <br>
                        <small class="text-muted">(Attendance only)</small>
                      @else
                        <span class="badge {{ $summary['billable_hours_percentage'] >= 90 ? 'bg-label-success' : ($summary['billable_hours_percentage'] >= 75 ? 'bg-label-warning' : 'bg-label-danger') }}">
                          {{ $summary['billable_hours_percentage'] }}%
                        </span>
                        <br>
                        <small class="text-muted">({{ $summary['billable_hours_weight'] }}% weight)</small>
                      @endif
                    </td>
                    <td class="text-center">
                      <div class="performance-score">
                        <span class="badge {{ $summary['final_performance_percentage'] >= 90 ? 'bg-success' : ($summary['final_performance_percentage'] >= 75 ? 'bg-warning' : 'bg-danger') }} fs-6">
                          {{ $summary['final_performance_percentage'] }}%
                        </span>
                      </div>
                    </td>
                    <td class="text-center">
                      <div class="additional-info">
                        @if($summary['pto_days'] > 0)
                          <span class="badge bg-label-primary me-1" title="PTO Days">
                            <i class="ti tabler-calendar-off"></i> {{ $summary['pto_days'] }}
                          </span>
                        @endif
                        @if($summary['wfh_days'] > 0)
                          <span class="badge bg-label-info me-1" title="WFH Days">
                            <i class="ti tabler-home"></i> {{ $summary['wfh_days'] }}
                          </span>
                        @endif
                        @if($summary['penalty_minutes'] > 0)
                          <span class="badge bg-label-danger" title="Penalty Minutes">
                            <i class="ti tabler-clock-minus"></i> {{ $summary['penalty_minutes'] }}m
                          </span>
                        @endif
                        @if($summary['pto_days'] == 0 && $summary['wfh_days'] == 0 && $summary['penalty_minutes'] == 0)
                          <span class="text-muted">-</span>
                        @endif
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-center py-4">
                      <div class="empty-state">
                        <i class="ti tabler-users-off display-4 text-muted"></i>
                        <h5 class="mt-3">No Active Employees</h5>
                        <p class="text-muted">No active employees found for the selected period.</p>
                      </div>
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Summary Statistics -->
  @if($employeeSummaries->count() > 0)
    <div class="row mt-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">Period Summary</h5>
          </div>
          <div class="card-body">
            <div class="row text-center">
              <div class="col-md-3">
                <div class="border rounded p-3">
                  <i class="ti tabler-users display-6 text-primary"></i>
                  <h4 class="mt-2">{{ $employeeSummaries->count() }}</h4>
                  <small class="text-muted">Total Employees</small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="border rounded p-3">
                  <i class="ti tabler-percentage display-6 text-success"></i>
                  <h4 class="mt-2">{{ round($employeeSummaries->avg('final_performance_percentage'), 1) }}%</h4>
                  <small class="text-muted">Avg Performance</small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="border rounded p-3">
                  <i class="ti tabler-clock display-6 text-info"></i>
                  <h4 class="mt-2">{{ round($employeeSummaries->avg('net_attended_hours'), 1) }}</h4>
                  <small class="text-muted">Avg Attended Hours</small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="border rounded p-3">
                  <i class="ti tabler-briefcase display-6 text-warning"></i>
                  <h4 class="mt-2">{{ round($employeeSummaries->avg('billable_hours'), 1) }}</h4>
                  <small class="text-muted">Avg Billable Hours</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  @endif

  <!-- Action Buttons -->
  <div class="row mt-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body text-center py-4">
          <p class="text-muted mb-3">
            <i class="ti tabler-info-circle me-2"></i>
            Review the calculations above. Click "Proceed to Adjustments" to make salary adjustments before finalizing.
          </p>

          @if($employeeSummaries->count() > 0)
            <div class="btn-group">
              <a href="{{ route('payroll.settings.index') }}" class="btn btn-label-secondary">
                <i class="ti tabler-settings me-2"></i>Adjust Weights
              </a>

              <a href="{{ route('payroll.run.adjustments', ['period' => $periodEnd->format('Y-m')]) }}" class="btn btn-primary">
                <i class="ti tabler-adjustments me-2"></i>Proceed to Adjustments
              </a>
            </div>

            @if ($errors->has('finalization'))
              <div class="alert alert-danger mt-3">
                <i class="ti tabler-alert-circle me-2"></i>
                {{ $errors->first('finalization') }}
              </div>
            @endif
          @else
            <div class="btn-group">
              <a href="{{ route('payroll.settings.index') }}" class="btn btn-label-secondary">
                <i class="ti tabler-settings me-2"></i>Adjust Weights
              </a>
              <button type="button" class="btn btn-primary" disabled>
                <i class="ti tabler-alert-triangle me-2"></i>No Employees to Finalize
              </button>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-style')
<style>
.performance-score .badge {
  font-size: 1rem;
  padding: 0.5rem 0.75rem;
}

.additional-info .badge {
  font-size: 0.75rem;
}

.empty-state {
  padding: 2rem;
}

.table th {
  border-top: none;
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.border {
  border-color: rgba(67, 89, 113, 0.1) !important;
}
</style>
@endsection
