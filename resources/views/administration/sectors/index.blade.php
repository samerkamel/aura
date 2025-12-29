@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Sectors - Administration')


@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="ti ti-world me-2"></i>Sectors Management
        </h5>
        <a href="{{ route('administration.sectors.create') }}" class="btn btn-primary">
          <i class="ti ti-plus me-1"></i>Create Sector
        </a>
      </div>

      @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
          {{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif

      @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
          {{ session('error') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif

      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="sectorsTable">
            <thead class="table-light">
              <tr>
                <th>Sector</th>
                <th>Code</th>
                <th>Business Units</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($sectors as $sector)
                <tr data-sector-id="{{ $sector->id }}">
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="avatar avatar-sm me-2">
                        <span class="avatar-initial rounded-circle bg-label-primary">
                          <i class="ti ti-world ti-sm"></i>
                        </span>
                      </div>
                      <div>
                        <h6 class="mb-0">{{ $sector->name }}</h6>
                        @if($sector->description)
                          <small class="text-muted">{{ \Illuminate\Support\Str::limit($sector->description, 50) }}</small>
                        @endif
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="badge bg-label-info">{{ $sector->code }}</span>
                  </td>
                  <td class="text-center">
                    <div class="d-flex flex-column align-items-center">
                      <span class="fw-semibold">{{ $sector->business_units_count }}</span>
                      <small class="text-muted">
                        ({{ $sector->active_business_units_count }} active)
                      </small>
                    </div>
                  </td>
                  <td>
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox"
                             id="status-{{ $sector->id }}"
                             {{ $sector->is_active ? 'checked' : '' }}
                             onchange="toggleSectorStatus({{ $sector->id }})">
                      <label class="form-check-label" for="status-{{ $sector->id }}">
                        <span class="badge bg-label-{{ $sector->is_active ? 'success' : 'warning' }}">
                          {{ $sector->is_active ? 'Active' : 'Inactive' }}
                        </span>
                      </label>
                    </div>
                  </td>
                  <td>
                    <div class="dropdown">
                      <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                        Actions
                      </button>
                      <ul class="dropdown-menu">
                        <li>
                          <a class="dropdown-item" href="{{ route('administration.sectors.show', $sector) }}">
                            <i class="ti ti-eye me-1"></i>View Details
                          </a>
                        </li>
                        <li>
                          <a class="dropdown-item" href="{{ route('administration.sectors.edit', $sector) }}">
                            <i class="ti ti-edit me-1"></i>Edit
                          </a>
                        </li>
                        @if($sector->business_units_count == 0)
                          <li><hr class="dropdown-divider"></li>
                          <li>
                            <a class="dropdown-item text-danger" href="#" onclick="deleteSector({{ $sector->id }})">
                              <i class="ti ti-trash me-1"></i>Delete
                            </a>
                          </li>
                        @endif
                      </ul>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                      <i class="ti ti-world text-muted mb-3" style="font-size: 4rem;"></i>
                      <h5>No Sectors Found</h5>
                      <p class="text-muted">Create your first sector to organize business units</p>
                      <a href="{{ route('administration.sectors.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>Create Sector
                      </a>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        @if($sectors->hasPages())
          <div class="d-flex justify-content-center mt-4">
            {{ $sectors->links() }}
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<script>
// Toggle sector status
function toggleSectorStatus(sectorId) {
  fetch(`/administration/sectors/${sectorId}/toggle-status`, {
    method: 'PATCH',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Content-Type': 'application/json',
    }
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Update the badge
      const checkbox = document.getElementById(`status-${sectorId}`);
      const badge = checkbox.parentElement.querySelector('.badge');
      if (data.is_active) {
        badge.className = 'badge bg-label-success';
        badge.textContent = 'Active';
      } else {
        badge.className = 'badge bg-label-warning';
        badge.textContent = 'Inactive';
      }

      // Show success message
      alert('Success: ' + data.message);
    } else {
      // Revert checkbox state
      const checkbox = document.getElementById(`status-${sectorId}`);
      checkbox.checked = !checkbox.checked;

      alert('Error: ' + data.message);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    // Revert checkbox state
    const checkbox = document.getElementById(`status-${sectorId}`);
    checkbox.checked = !checkbox.checked;

    alert('Error: An error occurred while updating the sector status.');
  });
}

// Delete sector
function deleteSector(sectorId) {
  if (confirm('Are you sure you want to delete this sector? This action cannot be undone!')) {
      fetch(`/administration/sectors/${sectorId}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      })
      .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        console.log('Response data:', data);
        if (data.success) {
          // Remove the row from table
          const row = document.querySelector(`tr[data-sector-id="${sectorId}"]`);
          if (row) {
            row.remove();
          }

          alert('Success: ' + data.message);
        } else {
          alert('Error: ' + (data.message || 'Failed to delete sector'));
        }
      })
      .catch(error => {
        console.error('Delete error:', error);
        alert('Error: An error occurred while deleting the sector: ' + error.message);
      });
  }
}

// Simple table enhancements (without DataTable since assets not available)
document.addEventListener('DOMContentLoaded', function() {
  // Table is rendered server-side with pagination
  console.log('Sectors table loaded');
});
</script>
@endsection