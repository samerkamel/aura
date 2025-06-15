@extends('layouts/layoutMaster')

@section('title', 'Document Preview - ' . $employee->name)

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Header Card -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <i class="ti ti-eye me-2 text-primary" style="font-size: 1.5rem;"></i>
          <div>
            <h5 class="mb-0">Document Preview</h5>
            <small class="text-muted">{{ $template->name }} - {{ $employee->name }}</small>
          </div>
        </div>
        <div class="d-flex gap-2">
          <form action="{{ route('documents.download', $employee) }}" method="POST" class="d-inline">
            @csrf
            <input type="hidden" name="template_id" value="{{ $template->id }}">
            <button type="submit" class="btn btn-success">
              <i class="ti ti-download me-1"></i>Download PDF
            </button>
          </form>
          <a href="{{ route('documents.select-template', $employee) }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Back to Templates
          </a>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Document Information -->
      <div class="col-md-4 mb-4">
        <div class="card">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="ti ti-info-circle me-2"></i>Document Information
            </h6>
          </div>
          <div class="card-body">
            <div class="row mb-2">
              <div class="col-4"><strong>Template:</strong></div>
              <div class="col-8">{{ $template->name }}</div>
            </div>
            <div class="row mb-2">
              <div class="col-4"><strong>Language:</strong></div>
              <div class="col-8">
                @if($template->language === 'en')
                  <span class="badge bg-primary">English</span>
                @else
                  <span class="badge bg-info">Arabic</span>
                @endif
              </div>
            </div>
            <div class="row mb-2">
              <div class="col-4"><strong>Employee:</strong></div>
              <div class="col-8">{{ $employee->name }}</div>
            </div>
            <div class="row mb-2">
              <div class="col-4"><strong>Position:</strong></div>
              <div class="col-8">{{ $employee->position ?? 'Not Specified' }}</div>
            </div>
            <div class="row">
              <div class="col-4"><strong>Generated:</strong></div>
              <div class="col-8">{{ now()->format('M d, Y g:i A') }}</div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mt-4">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="ti ti-bolt me-2"></i>Quick Actions
            </h6>
          </div>
          <div class="card-body">
            <form action="{{ route('documents.download', $employee) }}" method="POST" class="mb-2">
              @csrf
              <input type="hidden" name="template_id" value="{{ $template->id }}">
              <button type="submit" class="btn btn-success w-100">
                <i class="ti ti-download me-1"></i>Download PDF
              </button>
            </form>

            <a href="{{ route('documents.select-template', $employee) }}" class="btn btn-outline-primary w-100 mb-2">
              <i class="ti ti-template me-1"></i>Choose Different Template
            </a>

            <a href="{{ route('hr.employees.show', $employee) }}" class="btn btn-outline-secondary w-100">
              <i class="ti ti-user me-1"></i>Back to Employee Profile
            </a>
          </div>
        </div>
      </div>

      <!-- Document Preview -->
      <div class="col-md-8">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
              <i class="ti ti-file-text me-2"></i>Document Preview
            </h6>
            <small class="text-muted">
              <i class="ti ti-info-circle me-1"></i>
              This is how your document will appear in the PDF
            </small>
          </div>
          <div class="card-body">
            <div class="document-preview"
                 style="background: white; border: 1px solid #ddd; padding: 40px; min-height: 600px; font-family: {{ $template->language === 'ar' ? 'Arial, sans-serif' : 'Times, serif' }};"
                 {!! $template->language === 'ar' ? 'dir="rtl"' : '' !!}>

              <!-- Document Content -->
              <div class="preview-content" style="line-height: 1.6; {{ $template->language === 'ar' ? 'text-align: right;' : '' }}">
                {!! $generatedContent !!}
              </div>

              <!-- Generation Info Footer -->
              <div class="preview-footer" style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; text-align: center;">
                <p style="margin: 0;">
                  Generated on {{ now()->format('F j, Y \a\t g:i A') }} for {{ $employee->name }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@section('page-style')
<style>
.document-preview {
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
  border-radius: 8px;
}

.preview-content h1, .preview-content h2, .preview-content h3 {
  color: #2c3e50;
  margin-bottom: 16px;
}

.preview-content p {
  margin-bottom: 12px;
  text-align: justify;
}

.preview-content table {
  width: 100%;
  border-collapse: collapse;
  margin: 16px 0;
}

.preview-content table th,
.preview-content table td {
  padding: 8px 12px;
  border: 1px solid #ddd;
  text-align: left;
}

.preview-content table th {
  background-color: #f8f9fa;
  font-weight: bold;
}

/* Arabic-specific styles */
{{ $template->language === 'ar' ? '
.preview-content {
  direction: rtl;
  text-align: right;
}

.preview-content table th,
.preview-content table td {
  text-align: right;
}
' : '' }}

/* Print simulation */
@media print {
  .document-preview {
    box-shadow: none;
    border: none;
    padding: 0;
  }
}
</style>
@endsection

@endsection
