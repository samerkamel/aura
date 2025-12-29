@extends('layouts/layoutMaster')

@section('title', $report->name)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">
              <i class="ti ti-report me-2"></i>{{ $report->name }}
            </h5>
            <small class="text-muted">
              {{ $report->start_date->format('M d, Y') }} - {{ $report->end_date->format('M d, Y') }}
            </small>
          </div>
          <div class="d-flex gap-2">
            <a href="{{ route('projects.reports.edit', $report) }}" class="btn btn-primary btn-sm">
              <i class="ti ti-refresh me-1"></i>Edit / Refresh Hours
            </a>
            <a href="{{ route('projects.reports.export-pdf', $report) }}" class="btn btn-outline-danger btn-sm" target="_blank">
              <i class="ti ti-file-type-pdf me-1"></i>PDF
            </a>
            <a href="{{ route('projects.reports.export-excel', $report) }}" class="btn btn-outline-success btn-sm">
              <i class="ti ti-file-spreadsheet me-1"></i>Excel
            </a>
            <a href="{{ route('projects.reports.index') }}" class="btn btn-outline-secondary btn-sm">
              <i class="ti ti-arrow-left me-1"></i>Back to Reports
            </a>
          </div>
        </div>
        <div class="card-body">
          @if (session('success'))
            <div class="alert alert-success alert-dismissible" role="alert">
              {{ session('success') }}
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @endif

          <!-- Report Meta -->
          <div class="row mb-4">
            <div class="col-md-6">
              <div class="card bg-light">
                <div class="card-body">
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Created By:</span>
                    <strong>{{ $report->createdBy->name ?? 'N/A' }}</strong>
                  </div>
                  <div class="d-flex justify-content-between">
                    <span class="text-muted">Created At:</span>
                    <strong>{{ $report->created_at->format('M d, Y H:i') }}</strong>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card bg-primary text-white">
                <div class="card-body">
                  <div class="d-flex justify-content-between mb-2">
                    <span>Total Hours:</span>
                    <strong>{{ number_format($report->total_hours, 2) }}</strong>
                  </div>
                  <div class="d-flex justify-content-between">
                    <span>Total Amount:</span>
                    <strong>{{ number_format($report->total_amount, 2) }} EGP</strong>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Projects Data -->
          @if($report->projects_data)
            @foreach($report->projects_data as $project)
              <div class="card mb-4 border">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                  <h6 class="mb-0">
                    <span class="badge bg-label-primary me-2">{{ $project['project_code'] }}</span>
                    {{ $project['project_name'] }}
                  </h6>
                  <div>
                    <span class="badge bg-secondary me-2">
                      {{ number_format($project['total_hours'], 2) }} hrs
                    </span>
                    @php
                      $projectAvgRate = $project['total_hours'] > 0 ? $project['total_amount'] / $project['total_hours'] : 0;
                    @endphp
                    <span class="badge bg-info me-2">
                      {{ number_format($projectAvgRate, 2) }} EGP/hr
                    </span>
                    <span class="badge bg-success">
                      {{ number_format($project['total_amount'], 2) }} EGP
                    </span>
                  </div>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                      <thead class="table-light">
                        <tr>
                          <th style="width: 30%">Employee</th>
                          <th style="width: 15%">Team</th>
                          <th class="text-end" style="width: 15%">Hours</th>
                          <th class="text-end" style="width: 18%">Rate (EGP/hr)</th>
                          <th class="text-end" style="width: 18%">Amount</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach($project['employees'] as $employee)
                          <tr>
                            <td>{{ $employee['employee_name'] }}</td>
                            <td>
                              @if(!empty($employee['team']))
                                <span class="badge bg-label-info">{{ $employee['team'] }}</span>
                              @else
                                <span class="text-muted">--</span>
                              @endif
                            </td>
                            <td class="text-end">{{ number_format($employee['hours'], 2) }}</td>
                            <td class="text-end">{{ number_format($employee['rate'], 2) }} EGP</td>
                            <td class="text-end">
                              <strong>{{ number_format($employee['amount'], 2) }} EGP</strong>
                            </td>
                          </tr>
                        @endforeach
                      </tbody>
                      <tfoot class="table-light">
                        <tr>
                          <td colspan="2" class="text-end"><strong>Project Total:</strong></td>
                          <td class="text-end"><strong>{{ number_format($project['total_hours'], 2) }}</strong></td>
                          <td class="text-end"><strong>{{ number_format($projectAvgRate, 2) }} EGP/hr</strong></td>
                          <td class="text-end"><strong>{{ number_format($project['total_amount'], 2) }} EGP</strong></td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>
              </div>
            @endforeach
          @else
            <div class="alert alert-warning">
              <i class="ti ti-alert-triangle me-2"></i>No project data available for this report.
            </div>
          @endif

          <!-- Team Summary -->
          @php
            $teamTotals = [];
            $teams = \Modules\HR\Models\Employee::TEAMS;
            foreach ($teams as $teamKey => $teamLabel) {
              $teamTotals[$teamKey] = ['hours' => 0, 'amount' => 0, 'label' => $teamLabel];
            }
            $teamTotals[''] = ['hours' => 0, 'amount' => 0, 'label' => 'Unassigned'];

            if ($report->projects_data) {
              foreach ($report->projects_data as $project) {
                foreach ($project['employees'] as $employee) {
                  $team = $employee['team'] ?? '';
                  if (isset($teamTotals[$team])) {
                    $teamTotals[$team]['hours'] += $employee['hours'];
                    $teamTotals[$team]['amount'] += $employee['amount'];
                  }
                }
              }
            }
          @endphp
          <div class="card mb-4 border border-info">
            <div class="card-header bg-info text-white">
              <h6 class="mb-0"><i class="ti ti-users-group me-2"></i>Summary by Team</h6>
            </div>
            <div class="card-body p-0">
              <table class="table table-bordered mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Team</th>
                    <th class="text-end">Hours</th>
                    <th class="text-end">Amount (EGP)</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($teamTotals as $teamKey => $teamData)
                    @if($teamData['hours'] > 0 || $teamKey === '')
                      <tr>
                        <td>
                          @if($teamKey === '')
                            <em class="text-muted">{{ $teamData['label'] }}</em>
                          @else
                            <strong>{{ $teamData['label'] }}</strong>
                          @endif
                        </td>
                        <td class="text-end">{{ number_format($teamData['hours'], 2) }}</td>
                        <td class="text-end">{{ number_format($teamData['amount'], 2) }}</td>
                      </tr>
                    @endif
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>

          <!-- Grand Total Summary -->
          <div class="card border-primary">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col-md-3">
                  <h5 class="mb-0">Grand Total</h5>
                </div>
                <div class="col-md-3 text-end">
                  <h5 class="mb-0">{{ number_format($report->total_hours, 2) }} hours</h5>
                </div>
                <div class="col-md-3 text-end">
                  @php
                    $grandAvgRate = $report->total_hours > 0 ? $report->total_amount / $report->total_hours : 0;
                  @endphp
                  <h5 class="mb-0 text-muted">{{ number_format($grandAvgRate, 2) }} EGP/hr</h5>
                </div>
                <div class="col-md-3 text-end">
                  <h4 class="mb-0 text-primary">{{ number_format($report->total_amount, 2) }} EGP</h4>
                </div>
              </div>
            </div>
          </div>

          <hr class="my-4">

          <div class="d-flex justify-content-between">
            <div class="d-flex gap-2">
              <form action="{{ route('projects.reports.destroy', $report) }}" method="POST" class="d-inline"
                    onsubmit="return confirm('Are you sure you want to delete this report?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">
                  <i class="ti ti-trash me-1"></i>Delete Report
                </button>
              </form>
              <a href="{{ route('projects.reports.edit', $report) }}" class="btn btn-primary">
                <i class="ti ti-refresh me-1"></i>Edit / Refresh Hours
              </a>
            </div>
            <a href="{{ route('projects.reports.index') }}" class="btn btn-outline-secondary">
              <i class="ti ti-arrow-left me-1"></i>Back to Reports
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
