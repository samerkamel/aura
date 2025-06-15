@extends('layouts/layoutMaster')

@section('title', 'Asset Management')

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Assets Header Card -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <i class="ti ti-device-laptop me-2 text-primary" style="font-size: 1.5rem;"></i>
          <div>
            <h5 class="mb-0">Asset Management</h5>
            <small class="text-muted">Manage company assets and track assignments</small>
          </div>
        </div>
        <a href="{{ route('assetmanager.assets.create') }}" class="btn btn-primary">
          <i class="ti ti-plus me-1"></i>Add Asset
        </a>
      </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ti ti-check me-1"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ti ti-x me-1"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <!-- Filters Card -->
    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" action="{{ route('assetmanager.assets.index') }}" class="row g-3">
          <div class="col-md-3">
            <label for="search" class="form-label">Search</label>
            <input type="text" class="form-control" id="search" name="search"
                   value="{{ request('search') }}" placeholder="Search assets...">
          </div>
          <div class="col-md-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status">
              <option value="">All Statuses</option>
              <option value="available" {{ request('status') === 'available' ? 'selected' : '' }}>Available</option>
              <option value="assigned" {{ request('status') === 'assigned' ? 'selected' : '' }}>Assigned</option>
              <option value="maintenance" {{ request('status') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
              <option value="retired" {{ request('status') === 'retired' ? 'selected' : '' }}>Retired</option>
            </select>
          </div>
          <div class="col-md-3">
            <label for="type" class="form-label">Type</label>
            <select class="form-select" id="type" name="type">
              <option value="">All Types</option>
              @foreach($assetTypes as $assetType)
                <option value="{{ $assetType }}" {{ request('type') === $assetType ? 'selected' : '' }}>
                  {{ ucfirst($assetType) }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary me-2">
              <i class="ti ti-search me-1"></i>Filter
            </button>
            <a href="{{ route('assetmanager.assets.index') }}" class="btn btn-outline-secondary">
              <i class="ti ti-refresh me-1"></i>Clear
            </a>
          </div>
        </form>
      </div>
    </div>

    <!-- Assets List Card -->
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ti ti-list me-2"></i>Assets List
          <span class="badge bg-label-primary ms-2">{{ $assets->total() }} total</span>
        </h6>
      </div>
      <div class="card-body">
        @if($assets->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Asset Details</th>
                  <th>Type</th>
                  <th>Serial Number</th>
                  <th>Status</th>
                  <th>Assigned To</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($assets as $asset)
                  <tr>
                    <td>
                      <div>
                        <strong>{{ $asset->name }}</strong>
                        @if($asset->description)
                          <br><small class="text-muted">{{ Str::limit($asset->description, 50) }}</small>
                        @endif
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-label-info">{{ ucfirst($asset->type) }}</span>
                    </td>
                    <td>
                      @if($asset->serial_number)
                        <code>{{ $asset->serial_number }}</code>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                    <td>
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
                    </td>
                    <td>
                      @if($asset->status === 'assigned' && $asset->currentEmployee->isNotEmpty())
                        @php $currentEmployee = $asset->currentEmployee->first(); @endphp
                        <div class="d-flex align-items-center">
                          <i class="ti ti-user me-2 text-primary"></i>
                          <span>{{ $currentEmployee->name }}</span>
                        </div>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                    <td>
                      <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle"
                                data-bs-toggle="dropdown" aria-expanded="false">
                          Actions
                        </button>
                        <ul class="dropdown-menu">
                          <li>
                            <a class="dropdown-item" href="{{ route('assetmanager.assets.show', $asset) }}">
                              <i class="ti ti-eye me-1"></i>View Details
                            </a>
                          </li>
                          <li>
                            <a class="dropdown-item" href="{{ route('assetmanager.assets.edit', $asset) }}">
                              <i class="ti ti-edit me-1"></i>Edit Asset
                            </a>
                          </li>
                          @if($asset->status === 'available')
                            <li>
                              <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                 data-bs-target="#assignModal" data-asset-id="{{ $asset->id }}"
                                 data-asset-name="{{ $asset->name }}">
                                <i class="ti ti-user-plus me-1"></i>Assign to Employee
                              </a>
                            </li>
                          @elseif($asset->status === 'assigned' && $asset->currentEmployee->isNotEmpty())
                            <li>
                              <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                 data-bs-target="#unassignModal" data-asset-id="{{ $asset->id }}"
                                 data-asset-name="{{ $asset->name }}"
                                 data-employee-id="{{ $asset->currentEmployee->first()->id }}"
                                 data-employee-name="{{ $asset->currentEmployee->first()->name }}">
                                <i class="ti ti-user-minus me-1"></i>Unassign Asset
                              </a>
                            </li>
                          @endif
                          <li><hr class="dropdown-divider"></li>
                          <li>
                            <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal"
                               data-bs-target="#deleteModal" data-asset-id="{{ $asset->id }}"
                               data-asset-name="{{ $asset->name }}">
                              <i class="ti ti-trash me-1"></i>Delete Asset
                            </a>
                          </li>
                        </ul>
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="d-flex justify-content-between align-items-center mt-4">
            <div>
              <small class="text-muted">
                Showing {{ $assets->firstItem() }} to {{ $assets->lastItem() }} of {{ $assets->total() }} results
              </small>
            </div>
            <div>
              {{ $assets->appends(request()->query())->links() }}
            </div>
          </div>
        @else
          <div class="text-center py-4">
            <div class="mb-3">
              <i class="ti ti-device-laptop text-muted" style="font-size: 2rem;"></i>
            </div>
            <p class="text-muted mb-3">No assets found.</p>
            <a href="{{ route('assetmanager.assets.create') }}" class="btn btn-primary btn-sm">
              <i class="ti ti-plus me-1"></i>Add First Asset
            </a>
          </div>
        @endif
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
          <input type="hidden" name="asset_id" id="assign_asset_id">

          <div class="mb-3">
            <label class="form-label">Asset</label>
            <input type="text" class="form-control" id="assign_asset_name" readonly>
          </div>

          <div class="mb-3">
            <label for="assign_employee_id" class="form-label">Assign to Employee</label>
            <select class="form-select" name="employee_id" id="assign_employee_id" required>
              <option value="">Select an employee...</option>
              @foreach($employees as $employee)
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
        <h5 class="modal-title">Unassign Asset</h5>
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
          <i class="ti ti-alert-triangle text-warning" style="font-size: 3rem;"></i>
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
  // Assignment modal functionality
  const assignModal = document.getElementById('assignModal');
  if (assignModal) {
    assignModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const assetId = button.getAttribute('data-asset-id');
      const assetName = button.getAttribute('data-asset-name');

      document.getElementById('assign_asset_id').value = assetId;
      document.getElementById('assign_asset_name').value = assetName;
    });
  }

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
