<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Response;
use Modules\Accounting\Models\CreditNote;
use Modules\Accounting\Models\CreditNoteItem;
use Modules\Invoicing\Models\Invoice;
use Modules\Settings\Models\CompanySetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

/**
 * CreditNoteController
 *
 * Handles CRUD operations for credit notes and their application to invoices.
 */
class CreditNoteController extends Controller
{
    /**
     * Display a listing of credit notes.
     */
    public function index(Request $request): View
    {
        $query = CreditNote::with(['customer', 'invoice', 'createdBy', 'items']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by customer
        if ($request->has('customer_id') && $request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->from_date) {
            $query->where('credit_note_date', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date) {
            $query->where('credit_note_date', '<=', $request->to_date);
        }

        // Search by credit note number or client name
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('credit_note_number', 'like', '%' . $request->search . '%')
                  ->orWhere('client_name', 'like', '%' . $request->search . '%')
                  ->orWhere('reference', 'like', '%' . $request->search . '%');
            });
        }

        $creditNotes = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get customers for filters
        $customers = \App\Models\Customer::orderBy('name')->get();

        // Statistics
        $statistics = [
            'total_credit_notes' => CreditNote::count(),
            'draft_count' => CreditNote::draft()->count(),
            'open_count' => CreditNote::open()->count(),
            'open_total' => CreditNote::open()->sum('total'),
            'available_credits' => CreditNote::open()->sum('remaining_credits'),
        ];

        return view('accounting::credit-notes.index', compact(
            'creditNotes',
            'customers',
            'statistics'
        ));
    }

    /**
     * Show the form for creating a new credit note.
     */
    public function create(Request $request): View
    {
        $customers = \App\Models\Customer::orderBy('name')->get();
        $invoices = Invoice::with('customer')
            ->whereIn('status', ['sent', 'overdue', 'paid'])
            ->orderBy('invoice_date', 'desc')
            ->get();
        $companySettings = CompanySetting::getSettings();

        // Pre-select invoice from query parameter
        $selectedInvoiceId = $request->query('invoice_id');
        $selectedInvoice = $selectedInvoiceId
            ? Invoice::with(['customer', 'items'])->find($selectedInvoiceId)
            : null;

        return view('accounting::credit-notes.create', compact(
            'customers',
            'invoices',
            'companySettings',
            'selectedInvoiceId',
            'selectedInvoice'
        ));
    }

    /**
     * Store a newly created credit note.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'client_name' => 'required|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'client_address' => 'nullable|string|max:1000',
            'credit_note_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.details' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $items = $validated['items'];
        unset($validated['items']);

        DB::beginTransaction();
        try {
            // Generate credit note number based on credit note date
            $validated['credit_note_number'] = CreditNote::generateNumber($validated['credit_note_date']);
            $validated['created_by'] = auth()->id();
            $validated['status'] = 'draft';

            // Create credit note
            $creditNote = CreditNote::create($validated);

            // Create items
            foreach ($items as $index => $item) {
                $creditNote->items()->create([
                    'description' => $item['description'],
                    'details' => $item['details'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'unit_price' => $item['unit_price'],
                    'amount' => $item['quantity'] * $item['unit_price'],
                    'sort_order' => $index,
                ]);
            }

            // Recalculate totals
            $creditNote->recalculateTotals();

            DB::commit();

            return redirect()->route('accounting.credit-notes.show', $creditNote)
                ->with('success', 'Credit note created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create credit note: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified credit note.
     */
    public function show(CreditNote $creditNote): View
    {
        $creditNote->load(['customer', 'invoice', 'createdBy', 'items', 'applications.invoice', 'applications.appliedBy']);
        $companySettings = CompanySetting::getSettings();

        // Get invoices available for credit application
        $availableInvoices = [];
        if ($creditNote->canBeApplied()) {
            $availableInvoices = Invoice::where('customer_id', $creditNote->customer_id)
                ->whereIn('status', ['sent', 'overdue'])
                ->where(function ($q) {
                    $q->whereColumn('paid_amount', '<', 'total_amount')
                      ->orWhereNull('paid_amount');
                })
                ->orderBy('due_date', 'asc')
                ->get();
        }

        return view('accounting::credit-notes.show', compact('creditNote', 'companySettings', 'availableInvoices'));
    }

    /**
     * Show the form for editing the specified credit note.
     */
    public function edit(CreditNote $creditNote): View
    {
        if (!$creditNote->canBeEdited()) {
            return redirect()->route('accounting.credit-notes.show', $creditNote)
                ->with('error', 'This credit note cannot be edited.');
        }

        $creditNote->load(['items']);
        $customers = \App\Models\Customer::orderBy('name')->get();
        $invoices = Invoice::with('customer')
            ->whereIn('status', ['sent', 'overdue', 'paid'])
            ->orderBy('invoice_date', 'desc')
            ->get();
        $companySettings = CompanySetting::getSettings();

        return view('accounting::credit-notes.edit', compact(
            'creditNote',
            'customers',
            'invoices',
            'companySettings'
        ));
    }

    /**
     * Update the specified credit note.
     */
    public function update(Request $request, CreditNote $creditNote): RedirectResponse
    {
        if (!$creditNote->canBeEdited()) {
            return redirect()->route('accounting.credit-notes.show', $creditNote)
                ->with('error', 'This credit note cannot be edited.');
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'client_name' => 'required|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'client_address' => 'nullable|string|max:1000',
            'credit_note_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.details' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $items = $validated['items'];
        unset($validated['items']);

        DB::beginTransaction();
        try {
            // Update credit note
            $creditNote->update($validated);

            // Delete existing items and recreate
            $creditNote->items()->delete();
            foreach ($items as $index => $item) {
                $creditNote->items()->create([
                    'description' => $item['description'],
                    'details' => $item['details'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'unit_price' => $item['unit_price'],
                    'amount' => $item['quantity'] * $item['unit_price'],
                    'sort_order' => $index,
                ]);
            }

            // Recalculate totals
            $creditNote->recalculateTotals();

            DB::commit();

            return redirect()->route('accounting.credit-notes.show', $creditNote)
                ->with('success', 'Credit note updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update credit note: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified credit note.
     */
    public function destroy(CreditNote $creditNote): RedirectResponse
    {
        if ($creditNote->applied_amount > 0) {
            return redirect()->route('accounting.credit-notes.show', $creditNote)
                ->with('error', 'Cannot delete credit note that has been applied to invoices.');
        }

        $creditNote->delete();

        return redirect()->route('accounting.credit-notes.index')
            ->with('success', 'Credit note deleted successfully.');
    }

    /**
     * Mark credit note as open (available for application).
     */
    public function markAsOpen(CreditNote $creditNote): RedirectResponse
    {
        if (!$creditNote->canBeOpened()) {
            return redirect()->route('accounting.credit-notes.show', $creditNote)
                ->with('error', 'Credit note cannot be opened. Ensure it has items.');
        }

        $creditNote->markAsOpen();

        return redirect()->route('accounting.credit-notes.show', $creditNote)
            ->with('success', 'Credit note is now open and available for application.');
    }

    /**
     * Mark credit note as void.
     */
    public function markAsVoid(CreditNote $creditNote): RedirectResponse
    {
        try {
            $creditNote->markAsVoid();
            return redirect()->route('accounting.credit-notes.show', $creditNote)
                ->with('success', 'Credit note has been voided.');
        } catch (\Exception $e) {
            return redirect()->route('accounting.credit-notes.show', $creditNote)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Apply credit to an invoice.
     */
    public function applyToInvoice(Request $request, CreditNote $creditNote): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $invoice = Invoice::findOrFail($validated['invoice_id']);
            $creditNote->applyToInvoice($invoice, $validated['amount'], $validated['notes']);

            return redirect()->route('accounting.credit-notes.show', $creditNote)
                ->with('success', 'Credit applied to invoice successfully.');
        } catch (\Exception $e) {
            return redirect()->route('accounting.credit-notes.show', $creditNote)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Create credit note from an invoice.
     */
    public function createFromInvoice(Invoice $invoice): RedirectResponse
    {
        return redirect()->route('accounting.credit-notes.create', [
            'invoice_id' => $invoice->id,
        ]);
    }

    /**
     * Export credit note as PDF.
     */
    public function exportPdf(CreditNote $creditNote): Response
    {
        $creditNote->load(['customer', 'items', 'applications.invoice']);
        $companySettings = CompanySetting::getSettings();

        $pdf = Pdf::loadView('accounting::credit-notes.pdf', compact('creditNote', 'companySettings'));
        $pdf->setPaper('A4');

        return $pdf->download('credit-note-' . $creditNote->credit_note_number . '.pdf');
    }

    /**
     * Get next credit note number based on date.
     */
    public function getNextCreditNoteNumber(Request $request): \Illuminate\Http\JsonResponse
    {
        $creditNoteDate = $request->query('credit_note_date');

        return response()->json([
            'next_number' => CreditNote::generateNumber($creditNoteDate),
        ]);
    }

    /**
     * Get customer invoices for AJAX requests.
     */
    public function getCustomerInvoices(Request $request)
    {
        $customerId = $request->input('customer_id');

        $invoices = Invoice::where('customer_id', $customerId)
            ->whereIn('status', ['sent', 'overdue'])
            ->where(function ($q) {
                $q->whereColumn('paid_amount', '<', 'total_amount')
                  ->orWhereNull('paid_amount');
            })
            ->orderBy('due_date', 'asc')
            ->get(['id', 'invoice_number', 'total_amount', 'paid_amount', 'due_date']);

        return response()->json($invoices->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'total_amount' => $invoice->total_amount,
                'remaining_amount' => $invoice->remaining_amount,
                'due_date' => $invoice->due_date->format('M d, Y'),
            ];
        }));
    }
}
