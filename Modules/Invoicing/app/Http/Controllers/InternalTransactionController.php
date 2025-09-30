<?php

namespace Modules\Invoicing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Invoicing\Models\InternalTransaction;
use Modules\Invoicing\Models\InternalSequence;
use App\Models\BusinessUnit;
use App\Helpers\BusinessUnitHelper;

class InternalTransactionController extends Controller
{
    /**
     * Display a listing of internal transactions.
     */
    public function index(Request $request): View
    {
        if (!auth()->user()->can('view-internal-transactions') && !auth()->user()->can('manage-internal-transactions')) {
            abort(403, 'Unauthorized to view internal transactions.');
        }

        $query = InternalTransaction::with(['fromBusinessUnit', 'toBusinessUnit', 'createdBy', 'approvedBy']);

        // Apply business unit filtering
        $accessibleBusinessUnitIds = BusinessUnitHelper::getAccessibleBusinessUnitIds();
        if (!BusinessUnitHelper::isSuperAdmin()) {
            $query->where(function ($q) use ($accessibleBusinessUnitIds) {
                $q->whereIn('from_business_unit_id', $accessibleBusinessUnitIds)
                  ->orWhereIn('to_business_unit_id', $accessibleBusinessUnitIds);
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by approval status
        if ($request->has('approval_status') && $request->approval_status) {
            $query->where('approval_status', $request->approval_status);
        }

        // Filter by business unit
        if ($request->has('business_unit_id') && $request->business_unit_id) {
            $query->where(function ($q) use ($request) {
                $q->where('from_business_unit_id', $request->business_unit_id)
                  ->orWhere('to_business_unit_id', $request->business_unit_id);
            });
        }

        // Search by transaction number or description
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('transaction_number', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('reference', 'like', '%' . $request->search . '%');
            });
        }

        // Date range filtering
        if ($request->has('date_from') && $request->date_from) {
            $query->where('transaction_date', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->where('transaction_date', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->paginate(15);

        // Calculate statistics
        $stats = [
            'total' => $transactions->total(),
            'pending' => InternalTransaction::where('approval_status', 'pending')->count(),
            'approved_amount' => InternalTransaction::where('approval_status', 'approved')->sum('total_amount'),
            'this_month' => InternalTransaction::whereMonth('transaction_date', now()->month)
                                              ->whereYear('transaction_date', now()->year)
                                              ->sum('total_amount'),
        ];

        $businessUnits = BusinessUnit::orderBy('name')->get();

        return view('invoicing::internal-transactions.index', compact('transactions', 'stats', 'businessUnits'));
    }

    /**
     * Show the form for creating a new internal transaction.
     */
    public function create(): View
    {
        if (!auth()->user()->can('manage-internal-transactions')) {
            abort(403, 'Unauthorized to create internal transactions.');
        }

        $businessUnits = BusinessUnitHelper::getAccessibleBusinessUnits();
        $sequences = InternalSequence::active()->get();

        return view('invoicing::internal-transactions.create', compact('businessUnits', 'sequences'));
    }

    /**
     * Store a newly created internal transaction.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!auth()->user()->can('manage-internal-transactions')) {
            abort(403, 'Unauthorized to create internal transactions.');
        }

        $request->validate([
            'from_business_unit_id' => 'required|exists:business_units,id|different:to_business_unit_id',
            'to_business_unit_id' => 'required|exists:business_units,id',
            'internal_sequence_id' => 'nullable|exists:internal_sequences,id',
            'transaction_date' => 'required|date',
            'description' => 'required|string',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.amount' => 'required|numeric|min:0.01',
            'items.*.account_code' => 'nullable|string',
            'items.*.cost_center' => 'nullable|string',
            'items.*.project_reference' => 'nullable|string',
        ]);

        // Verify business unit access
        $accessibleBusinessUnitIds = BusinessUnitHelper::getAccessibleBusinessUnitIds();
        if (!BusinessUnitHelper::isSuperAdmin()) {
            if (!in_array($request->from_business_unit_id, $accessibleBusinessUnitIds) &&
                !in_array($request->to_business_unit_id, $accessibleBusinessUnitIds)) {
                abort(403, 'Unauthorized to create transactions for these business units.');
            }
        }

        // Generate transaction number
        $transactionNumber = null;
        if ($request->internal_sequence_id) {
            $sequence = InternalSequence::findOrFail($request->internal_sequence_id);
            $transactionNumber = $sequence->generateTransactionNumber();
        } else {
            // Generate a simple sequential number if no sequence is selected
            $lastTransaction = InternalTransaction::latest('id')->first();
            $nextNumber = $lastTransaction ? $lastTransaction->id + 1 : 1;
            $transactionNumber = 'IBT-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        }

        $transaction = InternalTransaction::create([
            'transaction_number' => $transactionNumber,
            'transaction_date' => $request->transaction_date,
            'total_amount' => 0, // Will be calculated from items
            'from_business_unit_id' => $request->from_business_unit_id,
            'to_business_unit_id' => $request->to_business_unit_id,
            'description' => $request->description,
            'reference' => $request->reference,
            'notes' => $request->notes,
            'created_by' => auth()->id(),
            'internal_sequence_id' => $request->internal_sequence_id,
        ]);

        // Add transaction items
        foreach ($request->items as $index => $item) {
            $transaction->items()->create([
                'description' => $item['description'],
                'amount' => $item['amount'],
                'account_code' => $item['account_code'],
                'cost_center' => $item['cost_center'],
                'project_reference' => $item['project_reference'],
                'sort_order' => $index,
            ]);
        }

        // Calculate total
        $transaction->calculateTotal();

        return redirect()
            ->route('invoicing.internal-transactions.show', $transaction)
            ->with('success', 'Internal transaction created successfully.');
    }

    /**
     * Display the specified internal transaction.
     */
    public function show(InternalTransaction $internalTransaction): View
    {
        if (!auth()->user()->can('view-internal-transactions') && !auth()->user()->can('manage-internal-transactions')) {
            abort(403, 'Unauthorized to view internal transaction details.');
        }

        // Verify business unit access
        $accessibleBusinessUnitIds = BusinessUnitHelper::getAccessibleBusinessUnitIds();
        if (!BusinessUnitHelper::isSuperAdmin()) {
            if (!in_array($internalTransaction->from_business_unit_id, $accessibleBusinessUnitIds) &&
                !in_array($internalTransaction->to_business_unit_id, $accessibleBusinessUnitIds)) {
                abort(403, 'Unauthorized to view this internal transaction.');
            }
        }

        $internalTransaction->load(['items', 'fromBusinessUnit', 'toBusinessUnit', 'createdBy', 'approvedBy', 'internalSequence']);

        return view('invoicing::internal-transactions.show', compact('internalTransaction'));
    }

    /**
     * Show the form for editing the specified internal transaction.
     */
    public function edit(InternalTransaction $internalTransaction): View
    {
        if (!auth()->user()->can('manage-internal-transactions')) {
            abort(403, 'Unauthorized to edit internal transactions.');
        }

        // Only allow editing of draft transactions
        if (!$internalTransaction->canBeEdited()) {
            abort(403, 'Only draft transactions can be edited.');
        }

        // Verify business unit access
        $accessibleBusinessUnitIds = BusinessUnitHelper::getAccessibleBusinessUnitIds();
        if (!BusinessUnitHelper::isSuperAdmin()) {
            if (!in_array($internalTransaction->from_business_unit_id, $accessibleBusinessUnitIds) &&
                !in_array($internalTransaction->to_business_unit_id, $accessibleBusinessUnitIds)) {
                abort(403, 'Unauthorized to edit this internal transaction.');
            }
        }

        $businessUnits = BusinessUnitHelper::getAccessibleBusinessUnits();
        $sequences = InternalSequence::active()->get();

        $internalTransaction->load(['fromBusinessUnit', 'toBusinessUnit']);

        return view('invoicing::internal-transactions.edit', compact('internalTransaction', 'businessUnits', 'sequences'));
    }

    /**
     * Update the specified internal transaction.
     */
    public function update(Request $request, InternalTransaction $internalTransaction): RedirectResponse
    {
        if (!auth()->user()->can('manage-internal-transactions')) {
            abort(403, 'Unauthorized to edit internal transactions.');
        }

        // Only allow editing of draft transactions
        if (!$internalTransaction->canBeEdited()) {
            abort(403, 'Only draft transactions can be edited.');
        }

        $request->validate([
            'from_business_unit_id' => 'required|exists:business_units,id|different:to_business_unit_id',
            'to_business_unit_id' => 'required|exists:business_units,id',
            'transaction_date' => 'required|date',
            'description' => 'required|string',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.amount' => 'required|numeric|min:0.01',
            'items.*.account_code' => 'nullable|string',
            'items.*.cost_center' => 'nullable|string',
            'items.*.project_reference' => 'nullable|string',
        ]);

        $internalTransaction->update([
            'from_business_unit_id' => $request->from_business_unit_id,
            'to_business_unit_id' => $request->to_business_unit_id,
            'transaction_date' => $request->transaction_date,
            'description' => $request->description,
            'reference' => $request->reference,
            'notes' => $request->notes,
        ]);

        // Update transaction items
        $internalTransaction->items()->delete();
        foreach ($request->items as $index => $item) {
            $internalTransaction->items()->create([
                'description' => $item['description'],
                'amount' => $item['amount'],
                'account_code' => $item['account_code'],
                'cost_center' => $item['cost_center'],
                'project_reference' => $item['project_reference'],
                'sort_order' => $index,
            ]);
        }

        // Calculate total
        $internalTransaction->calculateTotal();

        return redirect()
            ->route('invoicing.internal-transactions.show', $internalTransaction)
            ->with('success', 'Internal transaction updated successfully.');
    }

    /**
     * Remove the specified internal transaction.
     */
    public function destroy(InternalTransaction $internalTransaction): RedirectResponse
    {
        if (!auth()->user()->can('manage-internal-transactions')) {
            abort(403, 'Unauthorized to delete internal transactions.');
        }

        // Only allow deletion of draft transactions
        if (!$internalTransaction->canBeEdited()) {
            abort(403, 'Only draft transactions can be deleted.');
        }

        $internalTransaction->delete();

        return redirect()
            ->route('invoicing.internal-transactions.index')
            ->with('success', 'Internal transaction deleted successfully.');
    }

    /**
     * Submit transaction for approval.
     */
    public function submitForApproval(InternalTransaction $internalTransaction): RedirectResponse
    {
        if (!auth()->user()->can('manage-internal-transactions')) {
            abort(403, 'Unauthorized to modify internal transactions.');
        }

        if ($internalTransaction->status !== 'draft') {
            abort(403, 'Only draft transactions can be submitted for approval.');
        }

        $internalTransaction->submitForApproval();

        return redirect()
            ->back()
            ->with('success', 'Transaction submitted for approval.');
    }

    /**
     * Approve transaction.
     */
    public function approve(InternalTransaction $internalTransaction): RedirectResponse
    {
        if (!auth()->user()->can('approve-internal-transactions')) {
            abort(403, 'Unauthorized to approve internal transactions.');
        }

        if (!$internalTransaction->canBeApproved()) {
            abort(403, 'Transaction cannot be approved in its current state.');
        }

        $internalTransaction->approve(auth()->id());

        return redirect()
            ->back()
            ->with('success', 'Transaction approved successfully.');
    }

    /**
     * Reject transaction.
     */
    public function reject(InternalTransaction $internalTransaction): RedirectResponse
    {
        if (!auth()->user()->can('approve-internal-transactions')) {
            abort(403, 'Unauthorized to reject internal transactions.');
        }

        if (!$internalTransaction->canBeApproved()) {
            abort(403, 'Transaction cannot be rejected in its current state.');
        }

        $internalTransaction->reject(auth()->id());

        return redirect()
            ->back()
            ->with('success', 'Transaction rejected.');
    }

    /**
     * Cancel transaction.
     */
    public function cancel(InternalTransaction $internalTransaction): RedirectResponse
    {
        if (!auth()->user()->can('manage-internal-transactions')) {
            abort(403, 'Unauthorized to modify internal transactions.');
        }

        if (in_array($internalTransaction->status, ['approved', 'cancelled'])) {
            abort(403, 'Cannot cancel this transaction.');
        }

        $internalTransaction->cancel();

        return redirect()
            ->back()
            ->with('success', 'Transaction cancelled.');
    }
}