@extends('layouts/layoutMaster')

@section('title', 'Edit Letter Template')

@section('vendor-style')
{{-- Vendor Css files --}}
@endsection

@section('page-style')
{{-- Page Css files --}}
@endsection

@section('vendor-script')
<!-- TinyMCE Editor -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  tinymce.init({
    selector: '#content',
    height: 400,
    menubar: true,
    plugins: [
      'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
      'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
      'insertdatetime', 'media', 'table', 'help', 'wordcount'
    ],
    toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
    content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
    setup: function (editor) {
      editor.on('change', function () {
        editor.save();
      });
    }
  });

  // Insert placeholder function
  function insertPlaceholder(placeholder) {
    tinymce.get('content').insertContent(placeholder + ' ');
  }

  // Add click handlers for placeholder buttons
  document.querySelectorAll('.placeholder-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      insertPlaceholder(this.dataset.placeholder);
    });
  });
});
</script>
@endsection

@section('content')
<div class="row">
  <div class="col-xl">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Edit Letter Template</h5>
        <small class="text-muted float-end">{{ $letterTemplate->name }}</small>
      </div>
      <div class="card-body">

        @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
        @endif

        <form action="{{ route('letter-templates.update', $letterTemplate) }}" method="POST">
          @csrf
          @method('PUT')

          <!-- Basic Information -->
          <div class="row">
            <div class="col-md-8 mb-3">
              <label class="form-label" for="name">Template Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                     value="{{ old('name', $letterTemplate->name) }}" placeholder="Enter template name" required>
              @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-4 mb-3">
              <label class="form-label" for="language">Language <span class="text-danger">*</span></label>
              <select class="form-select @error('language') is-invalid @enderror" id="language" name="language" required>
                <option value="">Select Language</option>
                <option value="en" {{ old('language', $letterTemplate->language) === 'en' ? 'selected' : '' }}>English</option>
                <option value="ar" {{ old('language', $letterTemplate->language) === 'ar' ? 'selected' : '' }}>Arabic</option>
              </select>
              @error('language')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <!-- Available Placeholders -->
          <div class="row mb-3">
            <div class="col-12">
              <h6>Available Placeholders</h6>
              <p class="text-muted small">Click on any placeholder to insert it into your template content.</p>

              @foreach($placeholders as $category => $categoryPlaceholders)
              <div class="mb-2">
                <strong class="text-primary">{{ $category }}:</strong>
                <div class="mt-1">
                  @foreach($categoryPlaceholders as $placeholder => $description)
                  <button type="button" class="btn btn-outline-secondary btn-sm me-1 mb-1 placeholder-btn"
                          data-placeholder="{{ $placeholder }}" title="{{ $description }}">
                    {{ $placeholder }}
                  </button>
                  @endforeach
                </div>
              </div>
              @endforeach
            </div>
          </div>

          <!-- Template Content -->
          <div class="mb-3">
            <label class="form-label" for="content">Template Content <span class="text-danger">*</span></label>
            <textarea class="form-control @error('content') is-invalid @enderror" id="content" name="content"
                      rows="10" required>{{ old('content', $letterTemplate->content) }}</textarea>
            @error('content')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text">Use the rich text editor above to format your template and insert placeholders.</div>
          </div>

          <div class="d-flex justify-content-between">
            <a href="{{ route('letter-templates.index') }}" class="btn btn-outline-secondary">
              <i class="ti ti-arrow-left me-1"></i>Back to Templates
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="ti ti-device-floppy me-1"></i>Update Template
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
</div>
@endsection
