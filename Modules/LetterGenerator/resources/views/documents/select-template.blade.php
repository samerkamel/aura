@extends('layouts/layoutMaster')

@section('title', 'Select Template - ' . $employee->name)

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Header Card -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <i class="ti tabler-file-text me-2 text-primary" style="font-size: 1.5rem;"></i>
          <div>
            <h5 class="mb-0">Generate Document</h5>
            <small class="text-muted">Select a template for {{ $employee->name }}</small>
          </div>
        </div>
        <a href="{{ route('hr.employees.show', $employee) }}" class="btn btn-outline-secondary">
          <i class="ti tabler-arrow-left me-1"></i>Back to Employee
        </a>
      </div>
    </div>

    <!-- Employee Summary -->
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="ti tabler-user me-2"></i>Employee Information
            </h6>
          </div>
          <div class="card-body">
            <div class="row mb-2">
              <div class="col-sm-4"><strong>Name:</strong></div>
              <div class="col-sm-8">{{ $employee->name }}</div>
            </div>
            <div class="row mb-2">
              <div class="col-sm-4"><strong>Position:</strong></div>
              <div class="col-sm-8">{{ $employee->position ?? 'Not Specified' }}</div>
            </div>
            <div class="row mb-2">
              <div class="col-sm-4"><strong>Start Date:</strong></div>
              <div class="col-sm-8">{{ $employee->start_date ? $employee->start_date->format('M d, Y') : 'Not Set' }}</div>
            </div>
            <div class="row">
              <div class="col-sm-4"><strong>Status:</strong></div>
              <div class="col-sm-8">
                @if($employee->status === 'active')
                  <span class="badge bg-success">Active</span>
                @else
                  <span class="badge bg-secondary">{{ ucfirst($employee->status) }}</span>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Template Selection -->
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ti tabler-template me-2"></i>Available Templates
        </h6>
      </div>
      <div class="card-body">
        @if($templates->count() > 0)
          <form action="{{ route('documents.preview', $employee) }}" method="POST">
            @csrf

            <div class="row">
              @foreach($templates as $template)
              <div class="col-md-6 col-lg-4 mb-3">
                <div class="card template-card h-100" style="cursor: pointer;">
                  <div class="card-body">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="template_id"
                             id="template_{{ $template->id }}" value="{{ $template->id }}" required>
                      <label class="form-check-label w-100" for="template_{{ $template->id }}">
                        <div class="d-flex align-items-start">
                          <i class="ti tabler-file-text text-primary me-2" style="font-size: 1.2rem;"></i>
                          <div class="flex-grow-1">
                            <h6 class="mb-1">{{ $template->name }}</h6>
                            <div class="mb-2">
                              @if($template->language === 'en')
                                <span class="badge bg-primary">English</span>
                              @else
                                <span class="badge bg-info">Arabic</span>
                              @endif
                            </div>
                            <p class="text-muted small mb-1">
                              Created: {{ $template->created_at->format('M d, Y') }}
                            </p>
                            <div class="text-truncate small text-muted">
                              {!! \Illuminate\Support\Str::limit(strip_tags($template->content), 100) !!}
                            </div>
                          </div>
                        </div>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
              @endforeach
            </div>

            <div class="d-flex justify-content-between mt-3">
              <a href="{{ route('hr.employees.show', $employee) }}" class="btn btn-outline-secondary">
                <i class="ti tabler-arrow-left me-1"></i>Cancel
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="ti tabler-eye me-1"></i>Preview Document
              </button>
            </div>
          </form>
        @else
          <div class="text-center py-4">
            <i class="ti tabler-file-off text-muted" style="font-size: 3rem;"></i>
            <h6 class="mt-2">No Templates Available</h6>
            <p class="text-muted">Please create letter templates first before generating documents.</p>
            <a href="{{ route('letter-templates.create') }}" class="btn btn-primary">
              <i class="ti tabler-plus me-1"></i>Create Template
            </a>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Make template cards clickable
  document.querySelectorAll('.template-card').forEach(function(card) {
    card.addEventListener('click', function() {
      const radio = card.querySelector('input[type="radio"]');
      if (radio) {
        radio.checked = true;

        // Remove active state from all cards
        document.querySelectorAll('.template-card').forEach(c => c.classList.remove('border-primary'));

        // Add active state to selected card
        card.classList.add('border-primary');
      }
    });
  });

  // Handle radio change events
  document.querySelectorAll('input[name="template_id"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
      // Remove active state from all cards
      document.querySelectorAll('.template-card').forEach(c => c.classList.remove('border-primary'));

      // Add active state to parent card
      if (this.checked) {
        this.closest('.template-card').classList.add('border-primary');
      }
    });
  });
});
</script>
@endsection

@endsection
