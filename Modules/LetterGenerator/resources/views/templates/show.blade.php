@extends('layouts/layoutMaster')

@section('title', 'View Letter Template')

@section('content')
<div class="row">
  <div class="col-xl">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ $letterTemplate->name }}</h5>
        <div>
          @if($letterTemplate->language === 'en')
            <span class="badge bg-primary me-2">English</span>
          @else
            <span class="badge bg-info me-2">Arabic</span>
          @endif
          <div class="btn-group">
            <a href="{{ route('letter-templates.edit', $letterTemplate) }}" class="btn btn-primary btn-sm">
              <i class="ti tabler-edit me-1"></i>Edit
            </a>
            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal">
              <i class="ti tabler-trash me-1"></i>Delete
            </button>
          </div>
        </div>
      </div>
      <div class="card-body">

        <!-- Template Information -->
        <div class="row mb-4">
          <div class="col-md-6">
            <h6>Template Details</h6>
            <table class="table table-borderless">
              <tr>
                <td class="fw-bold">Name:</td>
                <td>{{ $letterTemplate->name }}</td>
              </tr>
              <tr>
                <td class="fw-bold">Language:</td>
                <td>
                  @if($letterTemplate->language === 'en')
                    <span class="badge bg-primary">English</span>
                  @else
                    <span class="badge bg-info">Arabic</span>
                  @endif
                </td>
              </tr>
              <tr>
                <td class="fw-bold">Created:</td>
                <td>{{ $letterTemplate->created_at->format('M d, Y \a\t g:i A') }}</td>
              </tr>
              <tr>
                <td class="fw-bold">Updated:</td>
                <td>{{ $letterTemplate->updated_at->format('M d, Y \a\t g:i A') }}</td>
              </tr>
            </table>
          </div>
          <div class="col-md-6">
            <h6>Available Placeholders</h6>
            <div class="small">
              @foreach($placeholders as $category => $categoryPlaceholders)
              <div class="mb-2">
                <strong class="text-primary">{{ $category }}:</strong>
                <div class="mt-1">
                  @foreach($categoryPlaceholders as $placeholder => $description)
                  <code class="me-1">{{ $placeholder }}</code>
                  @endforeach
                </div>
              </div>
              @endforeach
            </div>
          </div>
        </div>

        <!-- Template Content -->
        <div class="mb-3">
          <h6>Template Content</h6>
          <div class="border rounded p-3" style="background-color: #f8f9fa; min-height: 200px;">
            <div class="template-content" {!! $letterTemplate->language === 'ar' ? 'dir="rtl"' : '' !!}>
              {!! $letterTemplate->content !!}
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-between">
          <a href="{{ route('letter-templates.index') }}" class="btn btn-outline-secondary">
            <i class="ti tabler-arrow-left me-1"></i>Back to Templates
          </a>
          <a href="{{ route('letter-templates.edit', $letterTemplate) }}" class="btn btn-primary">
            <i class="ti tabler-edit me-1"></i>Edit Template
          </a>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Delete Template</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete the template "<strong>{{ $letterTemplate->name }}</strong>"?</p>
        <p class="text-muted small">This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <form action="{{ route('letter-templates.destroy', $letterTemplate) }}" method="POST" class="d-inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger">Delete Template</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
