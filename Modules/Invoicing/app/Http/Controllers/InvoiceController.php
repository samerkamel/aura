<?php

namespace Modules\Invoicing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Invoicing\Models\Invoice;
use Modules\Invoicing\Models\InvoiceSequence;
use Modules\Project\Models\Project;
use App\Models\Customer;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices.
     */
    public function index(Request $request): View
    {
        if (!auth()->user()->can('view-invoices') && !auth()->user()->can('manage-invoices')) {
            abort(403, 'Unauthorized to view invoices.');
        }

        $query = Invoice::with(['customer', 'invoiceSequence']);

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by customer
        if ($request->has('customer_id') && $request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        // Search by invoice number or customer name
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('invoice_number', 'like', '%' . $request->search . '%')
                  ->orWhere('reference', 'like', '%' . $request->search . '%')
                  ->orWhereHas('customer', function ($customerQuery) use ($request) {
                      $customerQuery->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        // Date range filtering
        if ($request->has('date_from') && $request->date_from) {
            $query->where('invoice_date', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->where('invoice_date', '<=', $request->date_to);
        }

        // Sorting
        $sortableColumns = ['invoice_number', 'invoice_date', 'due_date', 'total_amount', 'status'];
        $sortBy = $request->get('sort_by', 'invoice_date');
        $sortOrder = $request->get('sort_order', 'desc');

        // Validate sort parameters
        if (!in_array($sortBy, $sortableColumns)) {
            $sortBy = 'invoice_date';
        }
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        // Apply sorting - default is invoice_date DESC, then invoice_number DESC
        $query->orderBy($sortBy, $sortOrder);
        if ($sortBy !== 'invoice_number') {
            $query->orderBy('invoice_number', 'desc');
        }

        $invoices = $query->paginate(15);

        // Calculate statistics
        $stats = [
            'total' => $invoices->total(),
            'pending' => Invoice::whereIn('status', ['sent', 'overdue'])->count(),
            'paid_amount' => Invoice::where('status', 'paid')->sum('total_amount'),
            'overdue' => Invoice::where('status', 'overdue')->count(),
        ];

        $customers = Customer::orderBy('name')->get();
        $accounts = \Modules\Accounting\Models\Account::active()->orderBy('name')->get();

        return view('invoicing::invoices.index', compact('invoices', 'stats', 'customers', 'accounts'));
    }

    /**
     * Show the form for creating a new invoice.
     */
    public function create(Request $request): View
    {
        if (!auth()->user()->can('manage-invoices')) {
            abort(403, 'Unauthorized to create invoices.');
        }

        $customers = Customer::orderBy('name')->get();
        $sequences = InvoiceSequence::active()->get();
        $projects = Project::active()->orderBy('name')->get();

        // Pre-select project and customer from query parameters
        $selectedProjectId = $request->query('project_id');
        $selectedCustomerId = $request->query('customer_id');

        return view('invoicing::invoices.create', compact('customers', 'sequences', 'projects', 'selectedProjectId', 'selectedCustomerId'));
    }

    /**
     * Store a newly created invoice.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!auth()->user()->can('manage-invoices')) {
            abort(403, 'Unauthorized to create invoices.');
        }

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'project_id' => 'nullable|exists:projects,id',
            'invoice_sequence_id' => 'required|exists:invoice_sequences,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'tax_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'reference' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $sequence = InvoiceSequence::findOrFail($request->invoice_sequence_id);
        $invoiceNumber = $sequence->generateInvoiceNumber();

        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $request->invoice_date,
            'due_date' => $request->due_date,
            'subtotal' => 0, // Will be calculated from items
            'tax_amount' => $request->tax_amount ?? 0,
            'total_amount' => 0, // Will be calculated from items
            'customer_id' => $request->customer_id,
            'project_id' => $request->project_id,
            'invoice_sequence_id' => $request->invoice_sequence_id,
            'created_by' => auth()->id(),
            'notes' => $request->notes,
            'terms_conditions' => $request->terms_conditions,
            'reference' => $request->reference,
        ]);

        // Add invoice items
        foreach ($request->items as $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $item['quantity'] * $item['unit_price'],
            ]);
        }

        // Calculate totals
        $invoice->calculateTotals();

        // Handle action parameter for Save & Send
        if ($request->action === 'send') {
            $invoice->markAsSent();
            $message = 'Invoice created and marked as sent successfully.';
        } else {
            $message = 'Invoice created successfully.';
        }

        return redirect()
            ->route('invoicing.invoices.show', $invoice)
            ->with('success', $message);
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice): View
    {
        if (!auth()->user()->can('view-invoices') && !auth()->user()->can('manage-invoices')) {
            abort(403, 'Unauthorized to view invoice details.');
        }

        $invoice->load(['items', 'customer', 'invoiceSequence', 'createdBy', 'payments.createdBy']);
        $accounts = \Modules\Accounting\Models\Account::active()->orderBy('name')->get();

        return view('invoicing::invoices.show', compact('invoice', 'accounts'));
    }

    /**
     * Show the form for editing the specified invoice.
     */
    public function edit(Invoice $invoice): View
    {
        if (!auth()->user()->can('manage-invoices')) {
            abort(403, 'Unauthorized to edit invoices.');
        }

        // Only allow editing of draft invoices (super-admin can edit any)
        if ($invoice->status !== 'draft' && !auth()->user()->hasRole('super-admin')) {
            abort(403, 'Only draft invoices can be edited.');
        }

        $customers = Customer::orderBy('name')->get();
        $sequences = InvoiceSequence::active()->get();
        $projects = Project::active()->orderBy('name')->get();

        $invoice->load(['items', 'customer', 'project']);

        return view('invoicing::invoices.edit', compact('invoice', 'customers', 'sequences', 'projects'));
    }

    /**
     * Update the specified invoice.
     */
    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        if (!auth()->user()->can('manage-invoices')) {
            abort(403, 'Unauthorized to edit invoices.');
        }

        // Only allow editing of draft invoices (super-admin can edit any)
        if ($invoice->status !== 'draft' && !auth()->user()->hasRole('super-admin')) {
            abort(403, 'Only draft invoices can be edited.');
        }

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'project_id' => 'nullable|exists:projects,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'currency' => 'nullable|string|size:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'tax_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'reference' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $invoice->update([
            'customer_id' => $request->customer_id,
            'project_id' => $request->project_id,
            'invoice_date' => $request->invoice_date,
            'due_date' => $request->due_date,
            'currency' => $request->currency ?? 'EGP',
            'exchange_rate' => $request->exchange_rate ?? 1,
            'tax_amount' => $request->tax_amount ?? 0,
            'notes' => $request->notes,
            'terms_conditions' => $request->terms_conditions,
            'reference' => $request->reference,
        ]);

        // Update invoice items
        $invoice->items()->delete();
        foreach ($request->items as $item) {
            $taxRate = $item['tax_rate'] ?? 0;
            $subtotal = $item['quantity'] * $item['unit_price'];
            $taxAmount = $subtotal * ($taxRate / 100);

            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $subtotal + $taxAmount,
            ]);
        }

        // Calculate totals
        $invoice->calculateTotals();

        // Handle action parameter for Save & Send
        if ($request->action === 'send') {
            $invoice->markAsSent();
            $message = 'Invoice updated and marked as sent successfully.';
        } else {
            $message = 'Invoice updated successfully.';
        }

        return redirect()
            ->route('invoicing.invoices.show', $invoice)
            ->with('success', $message);
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        if (!auth()->user()->can('manage-invoices')) {
            abort(403, 'Unauthorized to delete invoices.');
        }

        // Only allow deletion of draft invoices
        if ($invoice->status !== 'draft') {
            abort(403, 'Only draft invoices can be deleted.');
        }

        $invoice->delete();

        return redirect()
            ->route('invoicing.invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    /**
     * Mark invoice as sent.
     */
    public function markAsSent(Invoice $invoice): RedirectResponse
    {
        if (!auth()->user()->can('manage-invoices')) {
            abort(403, 'Unauthorized to modify invoices.');
        }

        if ($invoice->status !== 'draft') {
            abort(403, 'Only draft invoices can be marked as sent.');
        }

        $invoice->markAsSent();

        return redirect()
            ->back()
            ->with('success', 'Invoice marked as sent.');
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(Request $request, Invoice $invoice): RedirectResponse
    {
        if (!auth()->user()->can('manage-invoices')) {
            abort(403, 'Unauthorized to modify invoices.');
        }

        $request->validate([
            'paid_amount' => 'required|numeric|min:0|max:' . $invoice->total_amount,
            'paid_date' => 'required|date',
            'payment_notes' => 'nullable|string',
            'account_id' => 'required|exists:accounts,id',
        ]);

        $invoice->markAsPaid(
            $request->paid_amount,
            $request->paid_date,
            $request->payment_notes,
            $request->account_id
        );

        return redirect()
            ->back()
            ->with('success', 'Invoice marked as paid.');
    }

    /**
     * Cancel invoice.
     */
    public function cancel(Invoice $invoice): RedirectResponse
    {
        if (!auth()->user()->can('manage-invoices')) {
            abort(403, 'Unauthorized to modify invoices.');
        }

        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            abort(403, 'Cannot cancel this invoice.');
        }

        $invoice->cancel();

        return redirect()
            ->back()
            ->with('success', 'Invoice cancelled.');
    }
}
