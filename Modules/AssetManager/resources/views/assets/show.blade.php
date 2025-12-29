@extends('layouts/layoutMaster')

@section('title', 'Asset Details')

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Header Card -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <i class="ti tabler-eye me-2 text-primary" style="font-size: 1.5rem;"></i>
          <div>
            <h5 class="mb-0">{{ $asset->name }}</h5>
            <small class="text-muted">Asset Details</small>
          </div>
        </div>
        <div class="d-flex gap-2">
          <a href="{{ route('assetmanager.assets.edit', $asset) }}" class="btn btn-outline-primary">
            <i class="ti tabler-edit me-1"></i>Edit Asset
          </a>
          <a href="{{ route('assetmanager.assets.index') }}" class="btn btn-outline-secondary">
            <i class="ti tabler-arrow-left me-1"></i>Back to Assets
          </a>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Asset Information Card -->
      <div class="col-lg-8">
        <div class="card mb-4">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="ti tabler-info-circle me-2"></i>Asset Information
            </h6>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label text-muted">Asset Name</label>
                <p class="fw-semibold">{{ $asset->name }}</p>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label text-muted">Type</label>
                <p><span class="badge bg-label-info">{{ ucfirst($asset->type) }}</span></p>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label text-muted">Serial Number</label>
                <p>
                  @if($asset->serial_number)
                    <code>{{ $asset->serial_number }}</code>
                  @else
                    <span class="text-muted">Not specified</span>
                  @endif
                </p>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label text-muted">Status</label>
                <p>
                  @php
                    $statusColors = [
                      'available' => 'success',
                      'assigned' => 'primary',
                      'maintenance' => 'warning',
                      'retired' => 'secondary'
                    ];
                    $statusColor = $statusColors[$asset->status] ?? 'secondary';
                  @endphp
                  <span class="badge bg-label-{{ $statusColor }}">
                    {{ ucfirst($asset->status) }}
                  </span>
                </p>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label text-muted">Purchase Date</label>
                <p>
                  @if($asset->purchase_date)
                    {{ $asset->purchase_date->format('M d, Y') }}
                  @else
                    <span class="text-muted">Not specified</span>
                  @endif
                </p>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label text-muted">Purchase Price</label>
                <p>
                  @if($asset->purchase_price)
                    <span class="fw-semibold">EGP {{ number_format($asset->purchase_price, 2) }}</span>
                  @else
                    <span class="text-muted">Not specified</span>
                  @endif
                </p>
              </div>
              @if($asset->description)
                <div class="col-12 mb-3">
                  <label class="form-label text-muted">Description</label>
                  <p>{{ $asset->description }}</p>
                </div>
              @endif
            </div>
          </div>
        </div>

        <!-- Assignment History Card -->
        <div class="card">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="ti tabler-history me-2"></i>Assignment History
            </h6>
          </div>
          <div class="card-body">
            @if($asset->employees->count() > 0)
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Employee</th>
                      <th>Assigned Date</th>
                      <th>Returned Date</th>
                      <th>Duration</th>
                      <th>Notes</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($asset->employees as $employee)
                      <tr class="{{ is_null($employee->pivot->returned_date) ? 'table-active' : '' }}">
                        <td>
                          <div class="d-flex align-items-center">
                            <i class="ti tabler-user me-2 text-primary"></i>
                            <span>{{ $employee->name }}</span>
                            @if(is_null($employee->pivot->returned_date))
                              <span class="badge bg-label-success ms-2">Current</span>
                            @endif
                          </div>
                        </td>
                        <td>
                          <small>{{ \Carbon\Carbon::parse($employee->pivot->assigned_date)->format('M d, Y') }}</small>
                        </td>
                        <td>
                          @if($employee->pivot->returned_date)
                            <small>{{ \Carbon\Carbon::parse($employee->pivot->returned_date)->format('M d, Y') }}</small>
                          @else
                            <span class="badge bg-label-primary">Active</span>
                          @endif
                        </td>
                        <td>
                          @php
                            $assignedDate = \Carbon\Carbon::parse($employee->pivot->assigned_date);
                            $returnedDate = $employee->pivot->returned_date
                              ? \Carbon\Carbon::parse($employee->pivot->returned_date)
                              : now();
                            $duration = $assignedDate->diffInDays($returnedDate);
                          @endphp
                          <small class="text-muted">
                            {{ $duration }} {{ \Illuminate\Support\Str::plural('day', $duration) }}
                          </small>
                        </td>
                        <td>
                          @if($employee->pivot->notes)
                            <small class="text-muted">{{ \Illuminate\Support\Str::limit($employee->pivot->notes, 50) }}</small>
                          @else
                            <small class="text-muted">-</small>
                          @endif
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <div class="text-center py-4">
                <div class="mb-3">
                  <i class="ti tabler-history text-muted" style="font-size: 2rem;"></i>
                </div>
                <p class="text-muted mb-0">No assignment history yet.</p>
                <small class="text-muted">This asset has never been assigned to an employee.</small>
              </div>
            @endif
          </div>
        </div>
      </div>

      <!-- Quick Actions Sidebar -->
      <div class="col-lg-4">
        <!-- Current Assignment Card -->
        @if($asset->isAssigned())
          @php $currentEmployee = $asset->currentEmployee->first(); @endphp
          <div class="card mb-4">
            <div class="card-header">
              <h6 class="mb-0">
                <i class="ti tabler-user-check me-2"></i>Current Assignment
              </h6>
            </div>
            <div class="card-body">
              <div class="d-flex align-items-center mb-3">
                <div class="avatar avatar-lg me-3">
                  <span class="avatar-initial bg-label-primary rounded">
                    {{ strtoupper(substr($currentEmployee->name, 0, 2)) }}
                  </span>
                </div>
                <div>
                  <h6 class="mb-0">{{ $currentEmployee->name }}</h6>
                  <small class="text-muted">{{ $currentEmployee->position ?? 'Employee' }}</small>
                </div>
              </div>

              <div class="mb-3">
                <small class="text-muted">Assigned Date:</small>
                <p class="mb-0">{{ \Carbon\Carbon::parse($currentEmployee->pivot->assigned_date)->format('M d, Y') }}</p>
              </div>

              @if($currentEmployee->pivot->notes)
                <div class="mb-3">
                  <small class="text-muted">Assignment Notes:</small>
                  <p class="mb-0 small">{{ $currentEmployee->pivot->notes }}</p>
                </div>
              @endif

              <button type="button" class="btn btn-outline-warning btn-sm w-100"
                      data-bs-toggle="modal" data-bs-target="#unassignModal"
                      data-asset-id="{{ $asset->id }}" data-asset-name="{{ $asset->name }}"
                      data-employee-id="{{ $currentEmployee->id }}"
                      data-employee-name="{{ $currentEmployee->name }}">
                <i class="ti tabler-user-minus me-1"></i>Return Asset
              </button>
            </div>
          </div>
        @endif

        <!-- Quick Actions Card -->
        <div class="card mb-4">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="ti tabler-bolt me-2"></i>Quick Actions
            </h6>
          </div>
          <div class="card-body">
            <div class="d-grid gap-2">
              @if($asset->status === 'available')
                <button type="button" class="btn btn-outline-primary"
                        data-bs-toggle="modal" data-bs-target="#assignModal"
                        data-asset-id="{{ $asset->id }}" data-asset-name="{{ $asset->name }}">
                  <i class="ti tabler-user-plus me-1"></i>Assign to Employee
                </button>
              @endif

              <a href="{{ route('assetmanager.assets.edit', $asset) }}" class="btn btn-outline-info">
                <i class="ti tabler-edit me-1"></i>Edit Asset
              </a>

              @if(!$asset->isAssigned())
                <button type="button" class="btn btn-outline-danger"
                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                        data-asset-id="{{ $asset->id }}" data-asset-name="{{ $asset->name }}">
                  <i class="ti tabler-trash me-1"></i>Delete Asset
                </button>
              @else
                <button type="button" class="btn btn-outline-danger" disabled>
                  <i class="ti tabler-trash me-1"></i>Cannot Delete (Assigned)
                </button>
              @endif
            </div>
          </div>
        </div>

        <!-- Asset Timeline Card -->
        <div class="card">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="ti tabler-clock me-2"></i>Asset Timeline
            </h6>
          </div>
          <div class="card-body">
            <div class="timeline timeline-center">
              <div class="timeline-item">
                <div class="timeline-point timeline-point-primary"></div>
                <div class="timeline-event">
                  <div class="timeline-header">
                    <h6 class="mb-0">Asset Created</h6>
                    <small class="text-muted">{{ $asset->created_at->format('M d, Y') }}</small>
                  </div>
                  <p class="mb-0 small text-muted">Asset added to inventory</p>
                </div>
              </div>

              @if($asset->purchase_date)
                <div class="timeline-item">
                  <div class="timeline-point timeline-point-info"></div>
                  <div class="timeline-event">
                    <div class="timeline-header">
                      <h6 class="mb-0">Purchased</h6>
                      <small class="text-muted">{{ $asset->purchase_date->format('M d, Y') }}</small>
                    </div>
                    @if($asset->purchase_price)
                      <p class="mb-0 small text-muted">Price: EGP {{ number_format($asset->purchase_price, 2) }}</p>
                    @endif
                  </div>
                </div>
              @endif

              @if($asset->employees->count() > 0)
                @foreach($asset->employees->take(3) as $employee)
                  <div class="timeline-item">
                    <div class="timeline-point {{ is_null($employee->pivot->returned_date) ? 'timeline-point-success' : 'timeline-point-warning' }}"></div>
                    <div class="timeline-event">
                      <div class="timeline-header">
                        <h6 class="mb-0">
                          {{ is_null($employee->pivot->returned_date) ? 'Assigned' : 'Returned' }}
                        </h6>
                        <small class="text-muted">
                          {{ \Carbon\Carbon::parse(
                            $employee->pivot->returned_date ?? $employee->pivot->assigned_date
                          )->format('M d, Y') }}
                        </small>
                      </div>
                      <p class="mb-0 small text-muted">
                        {{ is_null($employee->pivot->returned_date) ? 'Assigned to' : 'Returned by' }}
                        {{ $employee->name }}
                      </p>
                    </div>
                  </div>
                @endforeach
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Assignment Modal -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Assign Asset</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('assetmanager.assets.assign') }}" method="POST">
        @csrf
        <div class="modal-body">
          <input type="hidden" name="asset_id" id="assign_asset_id" value="{{ $asset->id }}">

          <div class="mb-3">
            <label class="form-label">Asset</label>
            <input type="text" class="form-control" value="{{ $asset->name }}" readonly>
          </div>

          <div class="mb-3">
            <label for="assign_employee_id" class="form-label">Assign to Employee</label>
            <select class="form-select" name="employee_id" id="assign_employee_id" required>
              <option value="">Select an employee...</option>
              @foreach(\Modules\HR\Models\Employee::orderBy('name')->get() as $employee)
                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label for="assign_assigned_date" class="form-label">Assignment Date</label>
            <input type="date" class="form-control" name="assigned_date" id="assign_assigned_date"
                   value="{{ date('Y-m-d') }}" required>
          </div>

          <div class="mb-3">
            <label for="assign_notes" class="form-label">Notes (Optional)</label>
            <textarea class="form-control" name="notes" id="assign_notes" rows="3"
                      placeholder="Any additional notes about this assignment..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Assign Asset</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Unassignment Modal -->
<div class="modal fade" id="unassignModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Return Asset</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('assetmanager.assets.unassign') }}" method="POST">
        @csrf
        <div class="modal-body">
          <input type="hidden" name="asset_id" id="unassign_asset_id">
          <input type="hidden" name="employee_id" id="unassign_employee_id">

          <div class="mb-3">
            <label class="form-label">Asset</label>
            <input type="text" class="form-control" id="unassign_asset_name" readonly>
          </div>

          <div class="mb-3">
            <label class="form-label">Currently Assigned To</label>
            <input type="text" class="form-control" id="unassign_employee_name" readonly>
          </div>

          <div class="mb-3">
            <label for="unassign_returned_date" class="form-label">Return Date</label>
            <input type="date" class="form-control" name="returned_date" id="unassign_returned_date"
                   value="{{ date('Y-m-d') }}" required>
          </div>

          <div class="mb-3">
            <label for="unassign_return_notes" class="form-label">Return Notes (Optional)</label>
            <textarea class="form-control" name="return_notes" id="unassign_return_notes" rows="3"
                      placeholder="Any notes about the asset return..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Return Asset</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Delete Asset</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="text-center">
          <i class="ti tabler-alert-triangle text-warning" style="font-size: 3rem;"></i>
          <h6 class="mt-3 mb-2">Are you sure?</h6>
          <p class="text-muted mb-3">
            You are about to delete the asset "<span id="delete_asset_name" class="fw-bold"></span>".
            This action cannot be undone.
          </p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <form style="display: inline;" method="POST" id="deleteForm">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger">Delete Asset</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@section('vendor-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Unassignment modal functionality
  const unassignModal = document.getElementById('unassignModal');
  if (unassignModal) {
    unassignModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const assetId = button.getAttribute('data-asset-id');
      const assetName = button.getAttribute('data-asset-name');
      const employeeId = button.getAttribute('data-employee-id');
      const employeeName = button.getAttribute('data-employee-name');

      document.getElementById('unassign_asset_id').value = assetId;
      document.getElementById('unassign_asset_name').value = assetName;
      document.getElementById('unassign_employee_id').value = employeeId;
      document.getElementById('unassign_employee_name').value = employeeName;
    });
  }

  // Delete modal functionality
  const deleteModal = document.getElementById('deleteModal');
  if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const assetId = button.getAttribute('data-asset-id');
      const assetName = button.getAttribute('data-asset-name');

      document.getElementById('delete_asset_name').textContent = assetName;
      document.getElementById('deleteForm').action = `/assets/${assetId}`;
    });
  }
});
</script>
@endsection
