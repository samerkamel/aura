@extends('layouts/layoutMaster')

@section('title', 'Internal Transactions')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Internal Transactions</h5>
                    <small class="text-muted">Manage inter-business unit transactions</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('invoicing.internal-sequences.index') }}" class="btn btn-outline-info">
                        <i class="ti ti-list-numbers me-1"></i>Sequences
                    </a>
                    <a href="{{ route('invoicing.internal-transactions.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>New Transaction
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card-body">
                @if($transactions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Transaction #</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $transaction)
                                <tr>
                                    <td>
                                        <a href="{{ route('invoicing.internal-transactions.show', $transaction) }}" class="fw-semibold text-primary">
                                            {{ $transaction->transaction_number }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-primary">{{ $transaction->fromBusinessUnit->name }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-info">{{ $transaction->toBusinessUnit->name }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold">{{ number_format($transaction->amount, 2) }} EGP</span>
                                    </td>
                                    <td>
                                        {{ $transaction->transaction_date->format('M j, Y') }}
                                    </td>
                                    <td>
                                        <span class="badge {{ $transaction->status_badge_class }}">
                                            {{ $transaction->status_display }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('invoicing.internal-transactions.show', $transaction) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="ti ti-building-bank display-6 text-muted"></i>
                        </div>
                        <h5 class="mb-2">No internal transactions found</h5>
                        <p class="text-muted">Create your first internal transaction to start tracking inter-business unit transfers.</p>
                        <a href="{{ route('invoicing.internal-transactions.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i>Create First Transaction
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection