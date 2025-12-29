@extends('layouts/layoutMaster')

@section('title', 'Expense Imports')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Expense Imports</h5>
                    <small class="text-muted">Import expenses from CSV/Excel files</small>
                </div>
                <a href="{{ route('accounting.expense-imports.create') }}" class="btn btn-primary">
                    <i class="ti tabler-upload me-1"></i>New Import
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card-body">
                @if($imports->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>File Name</th>
                                    <th>Status</th>
                                    <th>Rows</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($imports as $import)
                                <tr>
                                    <td>{{ $import->id }}</td>
                                    <td>
                                        <strong>{{ $import->file_name }}</strong>
                                        @if($import->notes)
                                            <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($import->notes, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $import->status_badge_class }}">
                                            {{ $import->status_display }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-success">{{ $import->valid_rows }}</span> /
                                        <span class="text-warning">{{ $import->warning_rows }}</span> /
                                        <span class="text-danger">{{ $import->error_rows }}</span> /
                                        {{ $import->total_rows }}
                                        <br>
                                        <small class="text-muted">Valid/Warn/Err/Total</small>
                                    </td>
                                    <td>{{ $import->createdBy->name ?? 'N/A' }}</td>
                                    <td>{{ $import->created_at->format('M j, Y g:i A') }}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="{{ route('accounting.expense-imports.show', $import) }}">
                                                    <i class="ti tabler-eye me-2"></i>View/Edit
                                                </a>
                                                @if(in_array($import->status, ['reviewing', 'previewing']))
                                                    <a class="dropdown-item" href="{{ route('accounting.expense-imports.preview', $import) }}">
                                                        <i class="ti tabler-list-check me-2"></i>Preview
                                                    </a>
                                                @endif
                                                @if($import->status !== 'completed')
                                                    <div class="dropdown-divider"></div>
                                                    <form action="{{ route('accounting.expense-imports.destroy', $import) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Delete this import?')">
                                                            <i class="ti tabler-trash me-2"></i>Delete
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $imports->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="ti tabler-file-import display-4 text-muted"></i>
                        <h5 class="mt-3">No imports yet</h5>
                        <p class="text-muted">Upload a CSV or Excel file to import expenses</p>
                        <a href="{{ route('accounting.expense-imports.create') }}" class="btn btn-primary">
                            <i class="ti tabler-upload me-1"></i>Upload File
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
