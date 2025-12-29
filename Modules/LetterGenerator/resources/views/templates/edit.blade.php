@extends('layouts/layoutMaster')

@section('title', 'Edit Letter Template')

@section('vendor-style')
{{-- Quill Editor CSS --}}
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
  #editor-container {
    height: 350px;
    background: #fff;
  }
  .ql-editor {
    font-family: Helvetica, Arial, sans-serif;
    font-size: 14px;
    min-height: 300px;
  }
  .ql-toolbar.ql-snow {
    border-top-left-radius: 0.375rem;
    border-top-right-radius: 0.375rem;
  }
  #editor-container.ql-container.ql-snow {
    border-bottom-left-radius: 0.375rem;
    border-bottom-right-radius: 0.375rem;
  }
</style>
@endsection

@section('page-style')
{{-- Page Css files --}}
@endsection

@section('vendor-script')
{{-- Quill Editor JS --}}
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Initialize Quill editor
  var quill = new Quill('#editor-container', {
    theme: 'snow',
    modules: {
      toolbar: [
        [{ 'header': [1, 2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'align': [] }],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        [{ 'indent': '-1'}, { 'indent': '+1' }],
        ['link', 'image'],
        ['clean']
      ]
    },
    placeholder: 'Enter your template content here...'
  });

  // Load existing content
  var existingContent = document.getElementById('content').value;
  if (existingContent) {
    quill.root.innerHTML = existingContent;
  }

  // Sync Quill content to hidden textarea on form submit
  var form = document.querySelector('form');
  form.addEventListener('submit', function() {
    document.getElementById('content').value = quill.root.innerHTML;
  });

  // Also sync on any text change (for validation)
  quill.on('text-change', function() {
    document.getElementById('content').value = quill.root.innerHTML;
  });

  // Insert placeholder function
  function insertPlaceholder(placeholder) {
    var range = quill.getSelection(true);
    quill.insertText(range.index, placeholder + ' ');
    quill.setSelection(range.index + placeholder.length + 1);
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
            <label class="form-label" for="editor-container">Template Content <span class="text-danger">*</span></label>
            <div id="editor-container" class="@error('content') border-danger @enderror"></div>
            <textarea class="d-none" id="content" name="content" required>{{ old('content', $letterTemplate->content) }}</textarea>
            @error('content')
            <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
            <div class="form-text">Use the rich text editor above to format your template and insert placeholders.</div>
          </div>

          <div class="d-flex justify-content-between">
            <a href="{{ route('letter-templates.index') }}" class="btn btn-outline-secondary">
              <i class="ti tabler-arrow-left me-1"></i>Back to Templates
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="ti tabler-device-floppy me-1"></i>Update Template
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
</div>
@endsection
