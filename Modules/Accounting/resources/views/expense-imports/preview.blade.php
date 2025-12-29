@extends('layouts/layoutMaster')

@section('title', 'Preview Import - ' . $expenseImport->file_name)

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Header -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">
                        <i class="ti tabler-eye-check me-2"></i>Preview Import
                    </h5>
                    <small class="text-muted">Review what will be created before executing</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('accounting.expense-imports.show', $expenseImport) }}" class="btn btn-outline-secondary">
                        <i class="ti tabler-arrow-left me-1"></i>Back to Edit
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('import_results'))
            @php $results = session('import_results'); @endphp
            <div class="alert alert-info">
                <h6><i class="ti tabler-info-circle me-2"></i>Import Results</h6>
                <ul class="mb-0">
                    <li>Expenses Created: {{ $results['expenses_created'] ?? 0 }}</li>
                    <li>Income Created: {{ $results['income_created'] ?? 0 }}</li>
                    <li>Invoices Linked: {{ $results['invoices_linked'] ?? 0 }}</li>
                    <li>Customers Created: {{ $results['customers_created'] ?? 0 }}</li>
                    @if(!empty($results['errors']))
                        <li class="text-danger">Errors: {{ count($results['errors']) }}</li>
                    @endif
                </ul>
            </div>
        @endif

        <!-- Errors/Warnings -->
        @if($errorRows->count() > 0)
            <div class="alert alert-danger">
                <h6><i class="ti tabler-alert-circle me-2"></i>{{ $errorRows->count() }} Rows with Errors</h6>
                <p class="mb-2">These rows will not be imported. Please go back and fix them.</p>
                <ul class="mb-0">
                    @foreach($errorRows->take(5) as $row)
                        <li>Row {{ $row->row_number }}: {{ $row->item_description }} - {{ collect($row->validation_messages)->where('type', 'error')->pluck('message')->join(', ') }}</li>
                    @endforeach
                    @if($errorRows->count() > 5)
                        <li>... and {{ $errorRows->count() - 5 }} more</li>
                    @endif
                </ul>
            </div>
        @endif

        @if($warningRows->count() > 0)
            <div class="alert alert-warning">
                <h6><i class="ti tabler-alert-triangle me-2"></i>{{ $warningRows->count() }} Rows with Warnings</h6>
                <p class="mb-0">These rows will be imported but may have incomplete data.</p>
            </div>
        @endif

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4 col-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md me-3 bg-danger">
                                <i class="ti tabler-receipt-2 ti-md text-white"></i>
                            </div>
                            <div>
                                <h3 class="mb-0">{{ $summary['expenses_to_create'] }}</h3>
                                <small class="text-muted">Expenses to Create</small>
                                <p class="mb-0 text-danger">{{ number_format($summary['expenses_total'], 2) }} EGP</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md me-3 bg-success">
                                <i class="ti tabler-cash ti-md text-white"></i>
                            </div>
                            <div>
                                <h3 class="mb-0">{{ $summary['income_to_create'] }}</h3>
                                <small class="text-muted">Income Records</small>
                                <p class="mb-0 text-success">{{ number_format($summary['income_total'], 2) }} EGP</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md me-3 bg-info">
                                <i class="ti tabler-file-invoice ti-md text-white"></i>
                            </div>
                            <div>
                                <h3 class="mb-0">{{ $summary['invoices_to_link'] }}</h3>
                                <small class="text-muted">Invoice Payments</small>
                                <p class="mb-0 text-info">{{ number_format($summary['invoices_total'], 2) }} EGP</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md me-3 bg-primary">
                                <i class="ti tabler-users ti-md text-white"></i>
                            </div>
                            <div>
                                <h3 class="mb-0">{{ $summary['customers_to_create'] }}</h3>
                                <small class="text-muted">New Customers</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md me-3 bg-warning">
                                <i class="ti tabler-arrows-exchange ti-md text-white"></i>
                            </div>
                            <div>
                                <h3 class="mb-0">{{ $summary['balance_swaps'] }}</h3>
                                <small class="text-muted">Balance Swaps (Skip)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md me-3 bg-secondary">
                                <i class="ti tabler-ban ti-md text-white"></i>
                            </div>
                            <div>
                                <h3 class="mb-0">{{ $summary['skipped'] }}</h3>
                                <small class="text-muted">Skipped Rows</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expenses to Create -->
        @if($rowsByAction->has('create_expense') && $rowsByAction->get('create_expense')->count() > 0)
        <div class="card mb-4">
            <div class="card-header bg-danger bg-opacity-10">
                <h6 class="mb-0 text-danger"><i class="ti tabler-receipt-2 me-2"></i>Expenses to Create ({{ $rowsByAction->get('create_expense')->count() }})</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Customer</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rowsByAction->get('create_expense')->take(20) as $row)
                            <tr>
                                <td>{{ $row->expense_date?->format('d/m/Y') }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($row->item_description, 40) }}</td>
                                <td>{{ $row->expenseType->name ?? $row->expense_type_raw }}</td>
                                <td>{{ $row->category->name ?? $row->category_raw }}</td>
                                <td>{{ $row->customer->display_name ?? $row->customer_raw }}</td>
                                <td class="text-end text-danger">{{ number_format(abs($row->total_amount), 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        @if($rowsByAction->get('create_expense')->count() > 20)
                        <tfoot>
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    ... and {{ $rowsByAction->get('create_expense')->count() - 20 }} more expenses
                                </td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Income to Create -->
        @if($rowsByAction->has('create_income') && $rowsByAction->get('create_income')->count() > 0)
        <div class="card mb-4">
            <div class="card-header bg-success bg-opacity-10">
                <h6 class="mb-0 text-success"><i class="ti tabler-cash me-2"></i>Income to Create ({{ $rowsByAction->get('create_income')->count() }})</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Customer</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rowsByAction->get('create_income')->take(20) as $row)
                            <tr>
                                <td>{{ $row->expense_date?->format('d/m/Y') }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($row->item_description, 40) }}</td>
                                <td>{{ $row->category->name ?? $row->category_raw }}</td>
                                <td>{{ $row->customer->display_name ?? $row->customer_raw }}</td>
                                <td class="text-end text-success">{{ number_format(abs($row->total_amount), 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Invoices to Link -->
        @if($rowsByAction->has('link_invoice') && $rowsByAction->get('link_invoice')->count() > 0)
        <div class="card mb-4">
            <div class="card-header bg-info bg-opacity-10">
                <h6 class="mb-0 text-info"><i class="ti tabler-file-invoice me-2"></i>Invoice Payments to Create ({{ $rowsByAction->get('link_invoice')->count() }})</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rowsByAction->get('link_invoice')->take(20) as $row)
                            <tr>
                                <td>{{ $row->expense_date?->format('d/m/Y') }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($row->item_description, 40) }}</td>
                                <td>
                                    @if($row->invoice)
                                        <a href="{{ route('invoicing.invoices.show', $row->invoice) }}" target="_blank">
                                            {{ $row->invoice->invoice_number }}
                                        </a>
                                    @else
                                        <span class="text-muted">Not linked</span>
                                    @endif
                                </td>
                                <td>{{ $row->customer->display_name ?? $row->customer_raw }}</td>
                                <td class="text-end text-info">{{ number_format(abs($row->total_amount), 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Execute Buttons -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">Ready to Execute?</h6>
                        <p class="text-muted mb-0">You can do a dry run first to see what will happen, or commit the changes.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <form action="{{ route('accounting.expense-imports.execute', $expenseImport) }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="dry_run" value="1">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="ti tabler-test-pipe me-1"></i>Dry Run
                            </button>
                        </form>

                        <form action="{{ route('accounting.expense-imports.execute', $expenseImport) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to commit these changes? This cannot be undone.')">
                            @csrf
                            <input type="hidden" name="dry_run" value="0">
                            <button type="submit" class="btn btn-success" {{ $errorRows->count() > 0 ? 'disabled' : '' }}>
                                <i class="ti tabler-check me-1"></i>Commit Changes
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
