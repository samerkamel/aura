@extends('layouts/layoutMaster')

@section('title', 'Create Asset')

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Header Card -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <i class="ti tabler-plus me-2 text-primary" style="font-size: 1.5rem;"></i>
          <div>
            <h5 class="mb-0">Create New Asset</h5>
            <small class="text-muted">Add a new asset to the company inventory</small>
          </div>
        </div>
        <a href="{{ route('assetmanager.assets.index') }}" class="btn btn-outline-secondary">
          <i class="ti tabler-arrow-left me-1"></i>Back to Assets
        </a>
      </div>
    </div>

    <!-- Create Asset Form Card -->
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ti tabler-info-circle me-2"></i>Asset Information
        </h6>
      </div>
      <div class="card-body">
        <form action="{{ route('assetmanager.assets.store') }}" method="POST">
          @csrf

          <div class="row">
            <!-- Asset Name -->
            <div class="col-md-6 mb-3">
              <label for="name" class="form-label">Asset Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('name') is-invalid @enderror"
                     id="name" name="name" value="{{ old('name') }}"
                     placeholder="e.g., MacBook Pro, Dell Monitor" required>
              @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Asset Type -->
            <div class="col-md-6 mb-3">
              <label for="type" class="form-label">Asset Type <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('type') is-invalid @enderror"
                     id="type" name="type" value="{{ old('type') }}"
                     placeholder="e.g., Laptop, Monitor, Phone" required>
              @error('type')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Serial Number -->
            <div class="col-md-6 mb-3">
              <label for="serial_number" class="form-label">Serial Number</label>
              <input type="text" class="form-control @error('serial_number') is-invalid @enderror"
                     id="serial_number" name="serial_number" value="{{ old('serial_number') }}"
                     placeholder="e.g., ABC123456789">
              <div class="form-text">Leave empty if not applicable</div>
              @error('serial_number')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Status -->
            <div class="col-md-6 mb-3">
              <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
              <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                <option value="">Select status...</option>
                <option value="available" {{ old('status') === 'available' ? 'selected' : '' }}>Available</option>
                <option value="assigned" {{ old('status') === 'assigned' ? 'selected' : '' }}>Assigned</option>
                <option value="maintenance" {{ old('status') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                <option value="retired" {{ old('status') === 'retired' ? 'selected' : '' }}>Retired</option>
              </select>
              @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Purchase Date -->
            <div class="col-md-6 mb-3">
              <label for="purchase_date" class="form-label">Purchase Date</label>
              <input type="date" class="form-control @error('purchase_date') is-invalid @enderror"
                     id="purchase_date" name="purchase_date" value="{{ old('purchase_date') }}"
                     max="{{ date('Y-m-d') }}">
              <div class="form-text">Date when the asset was purchased</div>
              @error('purchase_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Purchase Price -->
            <div class="col-md-6 mb-3">
              <label for="purchase_price" class="form-label">Purchase Price</label>
              <div class="input-group">
                <span class="input-group-text">EGP</span>
                <input type="number" class="form-control @error('purchase_price') is-invalid @enderror"
                       id="purchase_price" name="purchase_price" value="{{ old('purchase_price') }}"
                       step="0.01" min="0" placeholder="0.00">
              </div>
              <div class="form-text">Asset purchase price (optional)</div>
              @error('purchase_price')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Description -->
            <div class="col-12 mb-3">
              <label for="description" class="form-label">Description</label>
              <textarea class="form-control @error('description') is-invalid @enderror"
                        id="description" name="description" rows="4"
                        placeholder="Additional details about the asset...">{{ old('description') }}</textarea>
              <div class="form-text">Optional description with specifications, condition, or other notes</div>
              @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <!-- Form Actions -->
          <div class="row">
            <div class="col-12">
              <hr class="my-4">
              <div class="d-flex justify-content-between">
                <a href="{{ route('assetmanager.assets.index') }}" class="btn btn-outline-secondary">
                  <i class="ti tabler-x me-1"></i>Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                  <i class="ti tabler-check me-1"></i>Create Asset
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@section('vendor-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Auto-suggest asset types based on common categories
  const typeInput = document.getElementById('type');
  const commonTypes = [
    'Laptop', 'Desktop Computer', 'Monitor', 'Smartphone', 'Tablet',
    'Printer', 'Keyboard', 'Mouse', 'Headphones', 'Camera',
    'Projector', 'Speaker', 'Router', 'Server', 'Hard Drive'
  ];

  // Create datalist for type suggestions
  const datalist = document.createElement('datalist');
  datalist.id = 'assetTypes';
  commonTypes.forEach(type => {
    const option = document.createElement('option');
    option.value = type;
    datalist.appendChild(option);
  });

  typeInput.setAttribute('list', 'assetTypes');
  typeInput.parentNode.appendChild(datalist);

  // Auto-set status to available for new assets
  const statusSelect = document.getElementById('status');
  if (!statusSelect.value) {
    statusSelect.value = 'available';
  }
});
</script>
@endsection
