@extends('layouts/layoutMaster')

@section('title', 'Employees')

@section('page-style')
<style>
  .employee-card {
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.08);
  }
  .employee-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.1);
  }
  .employee-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    font-weight: 600;
    color: white;
    margin: 0 auto;
  }
  .avatar-active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
  .avatar-terminated { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
  .avatar-resigned { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #333; }
  .search-box {
    position: relative;
  }
  .search-box .search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
  }
  .search-box input {
    padding-left: 40px;
  }
  .view-toggle .btn {
    padding: 0.5rem 0.75rem;
  }
  .view-toggle .btn.active {
    background-color: var(--bs-primary);
    color: white;
  }
  .employee-info {
    font-size: 0.85rem;
  }
  .status-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 5px;
  }
  .status-active { background-color: #28c76f; }
  .status-terminated { background-color: #ea5455; }
  .status-resigned { background-color: #ff9f43; }
  .empty-state {
    padding: 4rem 2rem;
    text-align: center;
  }
  .empty-state-icon {
    font-size: 5rem;
    color: #d1d5db;
    margin-bottom: 1rem;
  }
</style>
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible mb-4" role="alert">
      <i class="ti tabler-check me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Header Card -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row align-items-center">
          <!-- Title and Actions -->
          <div class="col-lg-4 mb-3 mb-lg-0">
            <h4 class="mb-1">
              <i class="ti tabler-users me-2 text-primary"></i>Employee Directory
            </h4>
            <p class="text-muted mb-0">Manage your team members</p>
          </div>

          <!-- Search -->
          <div class="col-lg-4 mb-3 mb-lg-0">
            <form action="{{ route('hr.employees.index') }}" method="GET" id="searchForm">
              <input type="hidden" name="status" value="{{ $status }}">
              <input type="hidden" name="view" value="{{ $view }}">
              <div class="search-box">
                <i class="ti tabler-search search-icon"></i>
                <input type="text" name="search" class="form-control" placeholder="Search employees..."
                       value="{{ $search }}" id="employeeSearch">
              </div>
            </form>
          </div>

          <!-- Actions -->
          <div class="col-lg-4">
            <div class="d-flex justify-content-lg-end gap-2 flex-wrap">
              <!-- View Toggle -->
              <div class="btn-group view-toggle" role="group">
                <a href="{{ route('hr.employees.index', ['status' => $status, 'search' => $search, 'view' => 'grid']) }}"
                   class="btn btn-outline-secondary {{ $view === 'grid' ? 'active' : '' }}" title="Grid View">
                  <i class="ti tabler-layout-grid"></i>
                </a>
                <a href="{{ route('hr.employees.index', ['status' => $status, 'search' => $search, 'view' => 'table']) }}"
                   class="btn btn-outline-secondary {{ $view === 'table' ? 'active' : '' }}" title="Table View">
                  <i class="ti tabler-list"></i>
                </a>
              </div>

              <a href="{{ route('hr.employees.import.show') }}" class="btn btn-outline-info">
                <i class="ti tabler-upload me-1"></i><span class="d-none d-md-inline">Import</span>
              </a>
              <a href="{{ route('hr.employees.create') }}" class="btn btn-primary">
                <i class="ti tabler-plus me-1"></i><span class="d-none d-md-inline">Add Employee</span>
              </a>
            </div>
          </div>
        </div>

        <!-- Status Filter Pills -->
        <div class="mt-4">
          <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('hr.employees.index', ['status' => 'active', 'view' => $view]) }}"
               class="btn {{ $status === 'active' ? 'btn-primary' : 'btn-outline-primary' }} rounded-pill">
              <span class="status-indicator status-active"></span>
              Active
              <span class="badge bg-white text-primary ms-1">{{ $activeCount }}</span>
            </a>
            <a href="{{ route('hr.employees.index', ['status' => 'inactive', 'view' => $view]) }}"
               class="btn {{ $status === 'inactive' ? 'btn-secondary' : 'btn-outline-secondary' }} rounded-pill">
              <span class="status-indicator status-terminated"></span>
              Inactive
              <span class="badge {{ $status === 'inactive' ? 'bg-white text-secondary' : 'bg-secondary' }} ms-1">{{ $inactiveCount }}</span>
            </a>
            <a href="{{ route('hr.employees.index', ['status' => 'all', 'view' => $view]) }}"
               class="btn {{ $status === 'all' ? 'btn-info' : 'btn-outline-info' }} rounded-pill">
              <i class="ti tabler-users me-1"></i>
              All
              <span class="badge {{ $status === 'all' ? 'bg-white text-info' : 'bg-info' }} ms-1">{{ $activeCount + $inactiveCount }}</span>
            </a>
          </div>
        </div>
      </div>
    </div>

    @if($employees->isEmpty())
    <!-- Empty State -->
    <div class="card">
      <div class="card-body empty-state">
        <div class="empty-state-icon">
          @if($status === 'active')
            <i class="ti tabler-user-plus"></i>
          @elseif($status === 'inactive')
            <i class="ti tabler-user-off"></i>
          @else
            <i class="ti tabler-users"></i>
          @endif
        </div>
        @if(!empty($search))
          <h5>No employees found matching "{{ $search }}"</h5>
          <p class="text-muted mb-4">Try adjusting your search terms</p>
          <a href="{{ route('hr.employees.index', ['status' => $status, 'view' => $view]) }}" class="btn btn-outline-primary">
            <i class="ti tabler-x me-1"></i>Clear Search
          </a>
        @elseif($status === 'active')
          <h5>No active employees found</h5>
          <p class="text-muted mb-4">Start by creating your first employee</p>
          <a href="{{ route('hr.employees.create') }}" class="btn btn-primary">
            <i class="ti tabler-plus me-1"></i>Create Employee
          </a>
        @elseif($status === 'inactive')
          <h5>No inactive employees</h5>
          <p class="text-muted">All employees are currently active</p>
        @else
          <h5>No employees found</h5>
          <p class="text-muted mb-4">Start by creating your first employee</p>
          <a href="{{ route('hr.employees.create') }}" class="btn btn-primary">
            <i class="ti tabler-plus me-1"></i>Create Employee
          </a>
        @endif
      </div>
    </div>
    @else
      @if($view === 'grid')
      <!-- Grid View -->
      <div class="row g-4" id="employeeGrid">
        @foreach($employees as $employee)
        <div class="col-xl-3 col-lg-4 col-md-6 employee-item"
             data-name="{{ strtolower($employee->name) }}"
             data-email="{{ strtolower($employee->email) }}"
             data-position="{{ strtolower($employee->positionRelation?->full_title ?? $employee->position ?? '') }}">
          <div class="card employee-card h-100">
            <div class="card-body text-center">
              <!-- Avatar -->
              <div class="employee-avatar avatar-{{ $employee->status }} mb-3">
                {{ strtoupper(substr($employee->name, 0, 2)) }}
              </div>

              <!-- Name -->
              <h5 class="mb-1">
                <a href="{{ route('hr.employees.show', $employee) }}" class="text-body">
                  {{ $employee->name }}
                </a>
              </h5>

              <!-- Position -->
              <div class="mb-2">
                @if($employee->positionRelation)
                  <span class="badge bg-label-primary">{{ $employee->positionRelation->full_title }}</span>
                @elseif($employee->position)
                  <span class="badge bg-label-secondary">{{ $employee->position }}</span>
                @else
                  <span class="text-muted small">No position assigned</span>
                @endif
              </div>

              <!-- Status -->
              <div class="mb-3">
                @if($employee->status === 'active')
                  <span class="badge bg-success">Active</span>
                @elseif($employee->status === 'terminated')
                  <span class="badge bg-danger">Terminated</span>
                @else
                  <span class="badge bg-warning">Resigned</span>
                @endif
              </div>

              <!-- Info -->
              <div class="employee-info text-muted">
                <div class="mb-1">
                  <i class="ti tabler-mail me-1"></i>{{ $employee->email }}
                </div>
                @if($employee->start_date)
                <div class="mb-1">
                  <i class="ti tabler-calendar me-1"></i>Since {{ $employee->start_date->format('M Y') }}
                </div>
                @endif
                @if($employee->manager)
                <div>
                  <i class="ti tabler-user me-1"></i>Reports to: {{ $employee->manager->name }}
                </div>
                @endif
              </div>
            </div>

            <!-- Card Footer Actions -->
            <div class="card-footer bg-transparent border-top pt-3">
              <div class="d-flex justify-content-center gap-2">
                <a href="{{ route('hr.employees.show', $employee) }}" class="btn btn-sm btn-outline-primary" title="View">
                  <i class="ti tabler-eye"></i>
                </a>
                <a href="{{ route('hr.employees.edit', $employee) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                  <i class="ti tabler-edit"></i>
                </a>
                <form action="{{ route('hr.employees.destroy', $employee) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Are you sure you want to delete {{ $employee->name }}?')">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                    <i class="ti tabler-trash"></i>
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
        @endforeach
      </div>
      @else
      <!-- Table View -->
      <div class="card">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead class="table-light">
              <tr>
                <th>Employee</th>
                <th>Position</th>
                <th>Start Date</th>
                @if($status !== 'active')
                <th>End Date</th>
                @endif
                @if($canViewSalary)
                <th>Salary</th>
                @endif
                <th>Status</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($employees as $employee)
              <tr class="employee-item"
                  data-name="{{ strtolower($employee->name) }}"
                  data-email="{{ strtolower($employee->email) }}"
                  data-position="{{ strtolower($employee->positionRelation?->full_title ?? $employee->position ?? '') }}">
                <td>
                  <div class="d-flex align-items-center">
                    <div class="employee-avatar avatar-{{ $employee->status }} me-3" style="width: 40px; height: 40px; font-size: 0.875rem;">
                      {{ strtoupper(substr($employee->name, 0, 2)) }}
                    </div>
                    <div>
                      <a href="{{ route('hr.employees.show', $employee) }}" class="fw-semibold text-body">
                        {{ $employee->name }}
                      </a>
                      <div class="small text-muted">{{ $employee->email }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  @if($employee->positionRelation)
                    <span class="badge bg-label-primary">{{ $employee->positionRelation->full_title }}</span>
                  @elseif($employee->position)
                    <span class="badge bg-label-secondary">{{ $employee->position }}</span>
                  @else
                    <span class="text-muted">Not Assigned</span>
                  @endif
                </td>
                <td>{{ $employee->start_date ? $employee->start_date->format('M d, Y') : 'Not Set' }}</td>
                @if($status !== 'active')
                <td>
                  @if($employee->termination_date)
                    <span class="text-danger">{{ $employee->termination_date->format('M d, Y') }}</span>
                  @else
                    <span class="text-muted">Not Set</span>
                  @endif
                </td>
                @endif
                @if($canViewSalary)
                <td>
                  @if($employee->base_salary)
                    <span class="text-success fw-semibold">EGP {{ number_format($employee->base_salary, 2) }}</span>
                  @else
                    <span class="text-muted">Not Set</span>
                  @endif
                </td>
                @endif
                <td>
                  @if($employee->status === 'active')
                    <span class="badge bg-success">Active</span>
                  @elseif($employee->status === 'terminated')
                    <span class="badge bg-danger">Terminated</span>
                  @else
                    <span class="badge bg-warning">Resigned</span>
                  @endif
                </td>
                <td class="text-center">
                  <div class="d-inline-flex gap-1">
                    <a href="{{ route('hr.employees.show', $employee) }}" class="btn btn-sm btn-icon btn-text-secondary" title="View">
                      <i class="ti tabler-eye"></i>
                    </a>
                    <a href="{{ route('hr.employees.edit', $employee) }}" class="btn btn-sm btn-icon btn-text-secondary" title="Edit">
                      <i class="ti tabler-edit"></i>
                    </a>
                    <form action="{{ route('hr.employees.destroy', $employee) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Are you sure you want to delete {{ $employee->name }}?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-icon btn-text-danger" title="Delete">
                        <i class="ti tabler-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
      @endif
    @endif
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('employeeSearch');

  // Real-time client-side filtering (enhances server-side search)
  if (searchInput) {
    let debounceTimer;

    searchInput.addEventListener('input', function() {
      clearTimeout(debounceTimer);
      const searchTerm = this.value.toLowerCase().trim();

      debounceTimer = setTimeout(function() {
        const items = document.querySelectorAll('.employee-item');

        items.forEach(function(item) {
          const name = item.dataset.name || '';
          const email = item.dataset.email || '';
          const position = item.dataset.position || '';

          const matches = name.includes(searchTerm) ||
                         email.includes(searchTerm) ||
                         position.includes(searchTerm);

          if (searchTerm === '' || matches) {
            item.style.display = '';
          } else {
            item.style.display = 'none';
          }
        });
      }, 150);
    });

    // Submit search form on Enter
    searchInput.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        document.getElementById('searchForm').submit();
      }
    });
  }
});
</script>
@endsection
