<?php

namespace Modules\Invoicing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Invoicing\Models\Invoice;
use Modules\Invoicing\Models\InvoicePayment;
use App\Helpers\BusinessUnitHelper;

/**
 * InvoicePaymentController
 *
 * Handles CRUD operations for invoice payments.
 * Supports partial payments and payment tracking.
 */
class InvoicePaymentController extends Controller
{
    /**
     * Display a listing of all payments (aggregated view).
     */
    public function index(Request $request): View
    {
        if (!auth()->user()->can('view-invoices') && !auth()->user()->can('manage-invoices')) {
            abort(403, 'Unauthorized to view invoice payments.');
        }

        $query = InvoicePayment::with(['invoice.customer', 'invoice.businessUnit', 'createdBy'])
            ->join('invoices', 'invoice_payments.invoice_id', '=', 'invoices.id');

        // Apply business unit filtering
        if (!BusinessUnitHelper::isSuperAdmin()) {
            $accessibleBusinessUnits = BusinessUnitHelper::getAccessibleBusinessUnitIds();
            $query->whereIn('invoices.business_unit_id', $accessibleBusinessUnits);
        }

        // Apply filters
        if ($request->filled('business_unit')) {
            $query->where('invoices.business_unit_id', $request->business_unit);
        }

        if ($request->filled('payment_method')) {
            $query->where('invoice_payments.payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->where('invoice_payments.payment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('invoice_payments.payment_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoices.invoice_number', 'like', "%{$search}%")
                  ->orWhere('invoice_payments.reference_number', 'like', "%{$search}%")
                  ->orWhereHas('invoice.customer', function($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $payments = $query->select('invoice_payments.*')
            ->orderBy('invoice_payments.payment_date', 'desc')
            ->paginate(20);

        // Get filter options
        $businessUnits = BusinessUnitHelper::getAccessibleBusinessUnits();
        $paymentMethods = [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'check' => 'Check',
            'card' => 'Credit/Debit Card',
            'online' => 'Online Payment',
            'other' => 'Other'
        ];

        // Get statistics
        $totalPayments = $query->sum('invoice_payments.amount');
        $paymentsCount = $query->count();

        return view('invoicing::payments.index', compact(
            'payments',
            'businessUnits',
            'paymentMethods',
            'totalPayments',
            'paymentsCount'
        ));
    }

    /**
     * Store a new payment for an invoice.
     */
    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        if (!auth()->user()->can('manage-invoices')) {
            abort(403, 'Unauthorized to add payments.');
        }

        // Verify business unit access
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($invoice->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to add payments to this invoice.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->remaining_amount,
            'payment_date' => 'required|date|before_or_equal:today',
            'payment_method' => 'nullable|string|in:cash,bank_transfer,check,card,online,other',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'account_id' => 'required|exists:accounts,id',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,gif,doc,docx|max:10240', // 10MB max
        ]);

        // Handle file upload
        $attachmentData = [];
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('invoice_payments', $fileName, 'private');

            $attachmentData = [
                'attachment_path' => $filePath,
                'attachment_original_name' => $file->getClientOriginalName(),
                'attachment_mime_type' => $file->getClientMimeType(),
                'attachment_size' => $file->getSize(),
            ];
        }

        $payment = InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'amount' => $request->amount,
            'payment_date' => $request->payment_date,
            'payment_method' => $request->payment_method,
            'reference_number' => $request->reference_number,
            'notes' => $request->notes,
            'account_id' => $request->account_id,
            'created_by' => auth()->id(),
            ...$attachmentData,
        ]);

        // Update account balance
        $account = \Modules\Accounting\Models\Account::find($request->account_id);
        if ($account) {
            $account->updateBalance($request->amount, 'add');
        }

        return redirect()
            ->back()
            ->with('success', 'Payment added successfully.');
    }

    /**
     * Get payment data for editing.
     */
    public function show(InvoicePayment $invoicePayment)
    {
        if (!auth()->user()->can('manage-invoices')) {
            abort(403, 'Unauthorized to view payment details.');
        }

        // Verify business unit access
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($invoicePayment->invoice->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to view this payment.');
        }

        $maxAmount = $invoicePayment->invoice->total_amount - $invoicePayment->invoice->payments->where('id', '!=', $invoicePayment->id)->sum('amount');

        return response()->json([
            'success' => true,
            'payment' => [
                'id' => $invoicePayment->id,
                'amount' => $invoicePayment->amount,
                'payment_date' => $invoicePayment->payment_date->format('Y-m-d'),
                'payment_method' => $invoicePayment->payment_method,
                'reference_number' => $invoicePayment->reference_number,
                'notes' => $invoicePayment->notes,
                'account_id' => $invoicePayment->account_id,
                'max_amount' => $maxAmount,
            ],
        ]);
    }

    /**
     * Update the specified payment.
     */
    public function update(Request $request, InvoicePayment $invoicePayment)
    {
        if (!auth()->user()->can('manage-invoices')) {
            abort(403, 'Unauthorized to edit payments.');
        }

        // Verify business unit access
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($invoicePayment->invoice->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to edit this payment.');
        }

        $maxAmount = $invoicePayment->invoice->total_amount - $invoicePayment->invoice->payments->where('id', '!=', $invoicePayment->id)->sum('amount');

        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $maxAmount,
            'payment_date' => 'required|date|before_or_equal:today',
            'payment_method' => 'nullable|string|in:cash,bank_transfer,check,card,online,other',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'account_id' => 'required|exists:accounts,id',
        ]);

        $oldAmount = $invoicePayment->amount;
        $oldAccountId = $invoicePayment->account_id;
        $newAccountId = $request->account_id;
        $newAmount = $request->amount;

        // Update account balances if account or amount changed
        if ($oldAccountId != $newAccountId || $oldAmount != $newAmount) {
            // Reverse old account balance
            if ($oldAccountId) {
                $oldAccount = \Modules\Accounting\Models\Account::find($oldAccountId);
                if ($oldAccount) {
                    $oldAccount->updateBalance($oldAmount, 'subtract');
                }
            }

            // Add to new account
            $newAccount = \Modules\Accounting\Models\Account::find($newAccountId);
            if ($newAccount) {
                $newAccount->updateBalance($newAmount, 'add');
            }
        }

        $invoicePayment->update([
            'amount' => $newAmount,
            'payment_date' => $request->payment_date,
            'payment_method' => $request->payment_method,
            'reference_number' => $request->reference_number,
            'notes' => $request->notes,
            'account_id' => $newAccountId,
        ]);

        // Return JSON response for AJAX requests
        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Payment updated successfully.']);
        }

        return redirect()
            ->back()
            ->with('success', 'Payment updated successfully.');
    }

    /**
     * Remove the specified payment.
     */
    public function destroy(InvoicePayment $invoicePayment)
    {
        if (!auth()->user()->can('manage-invoices')) {
            abort(403, 'Unauthorized to delete payments.');
        }

        // Verify business unit access
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($invoicePayment->invoice->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to delete this payment.');
        }

        $invoicePayment->delete();

        // Return JSON response for AJAX requests
        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Payment deleted successfully.']);
        }

        return redirect()
            ->back()
            ->with('success', 'Payment deleted successfully.');
    }

    /**
     * Download payment attachment.
     */
    public function downloadAttachment(InvoicePayment $invoicePayment)
    {
        if (!auth()->user()->can('view-invoices') && !auth()->user()->can('manage-invoices')) {
            abort(403, 'Unauthorized to download payment attachments.');
        }

        // Verify business unit access
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($invoicePayment->invoice->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to download this attachment.');
        }

        if (!$invoicePayment->hasAttachment()) {
            abort(404, 'Attachment not found.');
        }

        $filePath = storage_path('app/private/' . $invoicePayment->attachment_path);

        if (!file_exists($filePath)) {
            abort(404, 'File not found.');
        }

        return response()->download($filePath, $invoicePayment->attachment_original_name);
    }
}