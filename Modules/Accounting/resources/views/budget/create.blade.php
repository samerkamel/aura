@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3">Create New Budget</h1>
            <p class="text-muted">Start planning a budget for a new fiscal year</p>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h5>Validation Errors</h5>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Create Budget Form Card -->
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Budget Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('accounting.budgets.store') }}">
                        @csrf

                        <!-- Year Selection -->
                        <div class="mb-4">
                            <label for="year" class="form-label">Fiscal Year <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">Year</span>
                                <input type="number" class="form-control @error('year') is-invalid @enderror"
                                       id="year" name="year" value="{{ old('year', $nextYear) }}"
                                       min="2000" max="2100" required
                                       placeholder="Enter fiscal year">
                            </div>
                            @error('year')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-block mt-2">
                                <i class="fas fa-info-circle"></i>
                                Enter a year between 2000 and 2100. The year must be unique (no budget exists for this year yet).
                            </small>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-plus"></i> Create Budget
                            </button>
                            <a href="{{ route('accounting.budgets.index') }}" class="btn btn-secondary flex-grow-1">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Information Card -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-lightbulb"></i> What Happens Next</h5>
                </div>
                <div class="card-body">
                    <ol class="mb-0">
                        <li class="mb-2">Budget will be created for fiscal year <strong>{{ $nextYear }}</strong></li>
                        <li class="mb-2">All products and expense categories will be automatically initialized</li>
                        <li class="mb-2">You'll be taken to the <strong>Growth Tab</strong> to start entering data</li>
                        <li>Work through all 9 tabs sequentially to plan your complete budget</li>
                    </ol>
                </div>
            </div>

            <!-- Required Data Info -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-check-circle"></i> Prerequisites</h5>
                </div>
                <div class="card-body">
                    <p class="small mb-0">Ensure your system has the following configured before creating a budget:</p>
                    <ul class="small mb-0 mt-2">
                        <li><strong>Active Products</strong> - Products used for allocation and revenue tracking</li>
                        <li><strong>Expense Categories</strong> - Categories for OpEx, Tax, and CapEx tracking</li>
                        <li><strong>Employees</strong> - Employees for personnel budget allocation</li>
                        <li><strong>Salary Data</strong> - Current salary information for employees</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .input-group-lg .form-control {
        font-size: 1.1rem;
        padding: 0.75rem 1rem;
    }

    .input-group-lg .input-group-text {
        font-size: 1rem;
        font-weight: 600;
    }

    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }

    .btn {
        font-weight: 500;
    }

    ol {
        padding-left: 1.5rem;
    }

    li {
        line-height: 1.6;
    }
</style>
@endsection
