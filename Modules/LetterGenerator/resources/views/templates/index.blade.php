@extends('layouts/layoutMaster')

@section('title', 'Letter Templates')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Letter Templates</h5>
    <a href="{{ route('letter-templates.create') }}" class="btn btn-primary">
      <i class="ti ti-plus me-1"></i>Create Template
    </a>
  </div>

  @if(session('success'))
  <div class="alert alert-success alert-dismissible" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  @endif

  <div class="table-responsive text-nowrap">
    <table class="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Language</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        @forelse($templates as $template)
        <tr>
          <td>
            <i class="ti ti-file-text me-2"></i>
            <strong>{{ $template->name }}</strong>
          </td>
          <td>
            @if($template->language === 'en')
              <span class="badge bg-primary">English</span>
            @else
              <span class="badge bg-info">Arabic</span>
            @endif
          </td>
          <td>{{ $template->created_at->format('M d, Y') }}</td>
          <td>
            <div class="dropdown">
              <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                <i class="ti ti-dots-vertical"></i>
              </button>
              <div class="dropdown-menu">
                <a class="dropdown-item" href="{{ route('letter-templates.show', $template) }}">
                  <i class="ti ti-eye me-2"></i>View
                </a>
                <a class="dropdown-item" href="{{ route('letter-templates.edit', $template) }}">
                  <i class="ti ti-edit me-2"></i>Edit
                </a>
                <div class="dropdown-divider"></div>
                <form action="{{ route('letter-templates.destroy', $template) }}" method="POST" class="d-inline">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="dropdown-item text-danger"
                          onclick="return confirm('Are you sure you want to delete this template?')">
                    <i class="ti ti-trash me-2"></i>Delete
                  </button>
                </form>
              </div>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="4" class="text-center py-4">
            <div class="d-flex flex-column align-items-center">
              <i class="ti ti-file-plus text-muted" style="font-size: 3rem;"></i>
              <h6 class="mt-2">No templates found</h6>
              <p class="text-muted">Start by creating your first letter template</p>
              <a href="{{ route('letter-templates.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>Create Template
              </a>
            </div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($templates->hasPages())
  <div class="card-footer">
    {{ $templates->links() }}
  </div>
  @endif
</div>
@endsection
