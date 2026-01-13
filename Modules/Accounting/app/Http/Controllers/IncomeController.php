<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Accounting\Models\Contract;
use Modules\Accounting\Models\ContractPayment;
use Modules\Accounting\Http\Requests\StoreContractRequest;
use Modules\Accounting\Http\Requests\UpdateContractRequest;
use Modules\Invoicing\Models\Invoice;
use Modules\Invoicing\Models\InvoiceItem;
use Modules\Invoicing\Models\InvoiceSequence;
use Modules\Accounting\Models\CreditNote;
use Modules\Accounting\Models\CreditNoteItem;
use Carbon\Carbon;
use App\Services\ContractRevenueSyncService;
use Illuminate\Support\Facades\Log;

/**
 * IncomeController
 *
 * Handles CRUD operations for contracts and payment milestones.
 */
class IncomeController extends Controller
{
    /**
     * Contract revenue sync service.
     */
    protected ContractRevenueSyncService $syncService;

    /**
     * Create a new controller instance.
     */
    public function __construct(ContractRevenueSyncService $syncService)
    {
        $this->syncService = $syncService;
    }
    /**
     * Display a listing of contracts.
     */
    public function index(Request $request): View
    {
        // Check authorization
        if (!auth()->user()->can('view-accounting-readonly')) {
            abort(403, 'Unauthorized to view contracts.');
        }

        // Get contracts with their payments, customer and projects
        $query = Contract::with(['payments', 'customer', 'projects']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search by client name or contract number
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('client_name', 'like', '%' . $request->search . '%')
                  ->orWhere('contract_number', 'like', '%' . $request->search . '%');
            });
        }

        $contracts = $query->orderBy('created_at', 'desc')->paginate(15);

        // Statistics
        $statistics = [
            'total_contracts' => Contract::count(),
            'active_contracts' => Contract::active()->count(),
            'total_contract_value' => Contract::active()->sum('total_amount'),
            'total_payments_scheduled' => Contract::active()->get()->sum('total_payment_amount'),
            'total_paid_amount' => Contract::active()->get()->sum('paid_amount'),
        ];

        return view('accounting::income.index', compact(
            'contracts',
            'statistics'
        ));
    }

    /**
     * Display contracts index.
     */
    public function contracts(Request $request): View
    {
        return $this->index($request);
    }

    /**
     * Get the next contract number via AJAX.
     * Accepts optional start_date parameter to determine the year.
     */
    public function getNextContractNumber(Request $request): \Illuminate\Http\JsonResponse
    {
        $startDate = $request->query('start_date');

        return response()->json([
            'next_number' => Contract::generateContractNumber($startDate),
        ]);
    }

    /**
     * Show the form for creating a new contract.
     */
    public function createContract(Request $request): View
    {
        // Get products for allocation
        $products = \App\Models\Product::where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get projects for project selection
        $projects = \Modules\Project\Models\Project::active()->orderBy('name')->get();

        // Pre-select project and customer from query parameters
        $selectedProjectId = $request->query('project_id');
        $selectedCustomerId = $request->query('customer_id');

        return view('accounting::income.create-contract', compact(
            'products',
            'projects',
            'selectedProjectId',
            'selectedCustomerId'
        ));
    }

    /**
     * Store a newly created contract.
     */
    public function storeContract(StoreContractRequest $request): RedirectResponse
    {
        $contractData = $request->validated();

        // Remove project_ids from contract data (handled separately via pivot)
        unset($contractData['project_ids']);

        $contract = Contract::create($contractData);

        // Attach projects to contract (many-to-many)
        if ($request->has('project_ids') && is_array($request->project_ids)) {
            $projectIds = array_filter($request->project_ids);
            $contract->projects()->attach($projectIds);

            // Sync contract payments to project revenues
            if (!empty($projectIds)) {
                $this->syncService->syncContractToProjects($contract);
            }
        }

        // Handle product allocations
        if ($request->has('products') && is_array($request->products)) {
            foreach ($request->products as $allocation) {
                if (!empty($allocation['product_id']) &&
                    !empty($allocation['allocation_type']) &&
                    (!empty($allocation['allocation_percentage']) || !empty($allocation['allocation_amount']))) {

                    $product = \App\Models\Product::where('id', $allocation['product_id'])
                        ->where('is_active', true)
                        ->first();

                    if (!$product) {
                        return redirect()
                            ->back()
                            ->withInput()
                            ->withErrors(['error' => 'Invalid product selected.']);
                    }

                    $contract->products()->attach($allocation['product_id'], [
                        'allocation_type' => $allocation['allocation_type'],
                        'allocation_percentage' => $allocation['allocation_type'] === 'percentage' ? $allocation['allocation_percentage'] : null,
                        'allocation_amount' => $allocation['allocation_type'] === 'amount' ? $allocation['allocation_amount'] : null,
                        'notes' => $allocation['notes'] ?? null,
                    ]);
                }
            }
        }

        return redirect()
            ->route('accounting.income.contracts.show', $contract)
            ->with('success', 'Contract created successfully.');
    }

    /**
     * Display the specified contract.
     */
    public function showContract(Contract $contract): View
    {
        $contract->load(['payments', 'projects', 'customer']);

        // Get upcoming payments for next 6 months
        $upcomingPayments = $contract->payments()
            ->where('status', 'pending')
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addMonths(6))
            ->orderBy('due_date')
            ->get();

        // Get recent payments (last 6 months)
        $recentPayments = $contract->payments()
            ->where('status', 'paid')
            ->where('paid_date', '>=', now()->subMonths(6))
            ->orderBy('paid_date', 'desc')
            ->get();

        $statistics = [
            'total_contract_value' => $contract->total_amount,
            'total_payments_scheduled' => $contract->total_payment_amount,
            'amount_paid' => $contract->paid_amount,
            'amount_pending' => $contract->pending_amount,
            'unassigned_amount' => $contract->unassigned_amount,
            'progress_percentage' => $contract->payment_progress_percentage,
            'upcoming_payments_count' => $upcomingPayments->count(),
            'recent_payments_count' => $recentPayments->count(),
        ];

        return view('accounting::income.show-contract', compact(
            'contract',
            'upcomingPayments',
            'recentPayments',
            'statistics'
        ));
    }

    /**
     * Show the form for editing the specified contract.
     */
    public function editContract(Contract $contract): View
    {
        $contract->load(['products', 'projects', 'customer']);

        // Get all customers for dropdown
        $customers = \App\Models\Customer::active()
            ->orderBy('name')
            ->get();

        // Get all products for allocation dropdown
        $products = \App\Models\Product::where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get projects for project selection
        $projects = \Modules\Project\Models\Project::active()->orderBy('name')->get();

        return view('accounting::income.edit-contract', compact('contract', 'customers', 'products', 'projects'));
    }

    /**
     * Update the specified contract.
     */
    public function updateContract(UpdateContractRequest $request, Contract $contract): RedirectResponse
    {
        $contractData = $request->validated();

        // Remove project_ids from contract data (handled separately via pivot)
        unset($contractData['project_ids']);

        $contract->update($contractData);

        // Handle project allocations with pivot data
        if ($request->has('project_allocations') && is_array($request->project_allocations)) {
            $oldProjectIds = $contract->projects()->pluck('projects.id')->toArray();

            // First, detach all existing projects
            $contract->projects()->detach();

            // Then attach the new ones with allocation data
            $newProjectIds = [];
            foreach ($request->project_allocations as $allocation) {
                if (!empty($allocation['project_id']) &&
                    !empty($allocation['allocation_type']) &&
                    (!empty($allocation['allocation_percentage']) || !empty($allocation['allocation_amount']))) {

                    $projectId = $allocation['project_id'];
                    $newProjectIds[] = $projectId;

                    $contract->projects()->attach($projectId, [
                        'allocation_type' => $allocation['allocation_type'],
                        'allocation_percentage' => $allocation['allocation_type'] === 'percentage' ? $allocation['allocation_percentage'] : null,
                        'allocation_amount' => $allocation['allocation_type'] === 'amount' ? $allocation['allocation_amount'] : null,
                        'is_primary' => isset($allocation['is_primary']) && $allocation['is_primary'] ? true : false,
                        'notes' => $allocation['notes'] ?? null,
                    ]);
                }
            }

            // Handle removed projects - remove synced revenues
            $removedProjectIds = array_diff($oldProjectIds, $newProjectIds);
            foreach ($removedProjectIds as $projectId) {
                $project = \Modules\Project\Models\Project::find($projectId);
                if ($project) {
                    $this->syncService->onProjectUnlinkedFromContract($project, $contract);
                }
            }

            // Handle added projects - sync revenues
            $addedProjectIds = array_diff($newProjectIds, $oldProjectIds);
            foreach ($addedProjectIds as $projectId) {
                $project = \Modules\Project\Models\Project::find($projectId);
                if ($project) {
                    $this->syncService->onProjectLinkedToContract($project, $contract);
                }
            }
        } else {
            // If no project allocations are submitted, remove all project links
            $oldProjectIds = $contract->projects()->pluck('projects.id')->toArray();
            foreach ($oldProjectIds as $projectId) {
                $project = \Modules\Project\Models\Project::find($projectId);
                if ($project) {
                    $this->syncService->onProjectUnlinkedFromContract($project, $contract);
                }
            }
            $contract->projects()->detach();
        }

        // Handle product allocations
        if ($request->has('products') && is_array($request->products)) {
            // First, detach all existing products
            $contract->products()->detach();

            // Then attach the new ones
            foreach ($request->products as $allocation) {
                if (!empty($allocation['product_id']) &&
                    !empty($allocation['allocation_type']) &&
                    (!empty($allocation['allocation_percentage']) || !empty($allocation['allocation_amount']))) {

                    $contract->products()->attach($allocation['product_id'], [
                        'allocation_type' => $allocation['allocation_type'],
                        'allocation_percentage' => $allocation['allocation_type'] === 'percentage' ? $allocation['allocation_percentage'] : null,
                        'allocation_amount' => $allocation['allocation_type'] === 'amount' ? $allocation['allocation_amount'] : null,
                        'notes' => $allocation['notes'] ?? null,
                    ]);
                }
            }
        } else {
            // If no products are submitted, remove all allocations
            $contract->products()->detach();
        }

        return redirect()
            ->route('accounting.income.contracts.show', $contract)
            ->with('success', 'Contract updated successfully.');
    }

    /**
     * Remove the specified contract.
     */
    public function destroyContract(Contract $contract): RedirectResponse
    {
        $contract->delete();

        return redirect()
            ->route('accounting.income.contracts.index')
            ->with('success', 'Contract deleted successfully.');
    }

    /**
     * Toggle active status of contract.
     */
    public function toggleContractStatus(Contract $contract): RedirectResponse
    {
        $contract->update(['is_active' => !$contract->is_active]);

        $status = $contract->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Contract {$status} successfully.");
    }

    /**
     * Add payment to contract.
     */
    public function addPayment(Request $request, Contract $contract): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'payment_type' => 'required|in:fixed,percentage',
            'amount' => 'required_if:payment_type,fixed|nullable|numeric|min:0.01|max:99999999.99',
            'percentage' => 'required_if:payment_type,percentage|nullable|numeric|min:0.01|max:100',
            'calculated_amount' => 'nullable|numeric',
            'due_date' => 'nullable|date',
            'schedule_later' => 'nullable|boolean',
        ]);

        // Calculate the final amount based on payment type
        if ($request->payment_type === 'percentage') {
            $amount = ($contract->total_amount * $request->percentage) / 100;
        } else {
            $amount = $request->amount;
        }

        // Check if adding this payment would exceed contract value
        $existingPaymentsTotal = $contract->payments()->sum('amount');
        $newTotal = $existingPaymentsTotal + $amount;

        if ($newTotal > $contract->total_amount) {
            return redirect()
                ->back()
                ->with('error', 'Payment amount would exceed contract total. Remaining: ' . number_format($contract->total_amount - $existingPaymentsTotal, 2) . ' EGP');
        }

        $payment = $contract->payments()->create([
            'name' => $request->name,
            'description' => $request->description,
            'amount' => $amount,
            'due_date' => $request->due_date,
            'status' => $request->due_date ? 'pending' : 'pending',
            'is_milestone' => true,
            'sequence_number' => $contract->payments()->max('sequence_number') + 1,
        ]);

        // Sync new payment to linked projects
        foreach ($contract->projects as $project) {
            $this->syncService->syncPaymentToProject($payment, $project);
        }

        $paymentType = $request->payment_type === 'percentage'
            ? "({$request->percentage}% of contract)"
            : '';

        $milestoneType = !$request->due_date ? ' as planning milestone' : '';

        return redirect()
            ->back()
            ->with('success', "Payment milestone added successfully{$milestoneType} {$paymentType}.");
    }

    /**
     * Generate recurring payments for contract.
     */
    public function generateRecurringPayments(Request $request, Contract $contract): RedirectResponse
    {
        $request->validate([
            'frequency_type' => 'required|in:weekly,bi-weekly,monthly,quarterly,yearly',
            'frequency_value' => 'required|integer|min:1|max:52',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = $request->end_date ? Carbon::parse($request->end_date) : $contract->end_date;

            if (!$endDate) {
                return redirect()
                    ->back()
                    ->with('error', 'End date is required for recurring payments. Please set contract end date or specify end date.');
            }

            $contract->generateRecurringPayments(
                $request->frequency_type,
                (int) $request->frequency_value,
                $startDate,
                $endDate
            );

            return redirect()
                ->back()
                ->with('success', 'Recurring payments generated successfully.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error generating recurring payments: ' . $e->getMessage());
        }
    }

    /**
     * Update payment status.
     */
    public function updatePaymentStatus(Request $request, Contract $contract, ContractPayment $payment): RedirectResponse
    {
        $request->validate([
            'status' => 'nullable|in:pending,paid,overdue,cancelled',
            'due_date' => 'nullable|date',
        ]);

        $updateData = [];

        if ($request->has('status')) {
            $updateData['status'] = $request->status;
        }

        if ($request->has('due_date')) {
            $updateData['due_date'] = $request->due_date;
        }

        $payment->update($updateData);

        // If payment status changed, update related invoices accordingly
        if ($request->has('status')) {
            if ($request->status === 'paid') {
                $this->markRelatedInvoicesAsPaid($payment);
                // Sync payment paid status to project revenue
                $this->syncService->onPaymentPaid($payment);
            } else {
                // If payment is no longer paid, check if related invoices should be updated
                $this->updateRelatedInvoicesStatus($payment);
                // Also sync status change to project revenue
                $this->syncService->syncPaymentStatusChange($payment);
            }
        }

        $message = '';
        if ($request->has('due_date') && $request->due_date) {
            $message = 'Payment scheduled successfully.';
        } elseif ($request->has('status')) {
            $message = 'Payment status updated successfully.';
            if ($request->status === 'paid') {
                $message .= ' Related invoices have also been updated accordingly.';
            } elseif (in_array($request->status, ['pending', 'overdue', 'cancelled'])) {
                $message .= ' Related invoices have been updated accordingly.';
            }
        } else {
            $message = 'Payment updated successfully.';
        }

        return redirect()
            ->back()
            ->with('success', $message);
    }

    /**
     * Delete payment.
     */
    public function deletePayment(Contract $contract, ContractPayment $payment): RedirectResponse
    {
        $payment->delete();

        return redirect()
            ->back()
            ->with('success', 'Payment deleted successfully.');
    }

    /**
     * Bulk actions on contracts.
     */
    public function bulkContractAction(Request $request): RedirectResponse
    {
        $action = $request->input('action');
        $contractIds = $request->input('contracts', []);

        if (empty($contractIds)) {
            return redirect()->back()->with('error', 'No contracts selected.');
        }

        switch ($action) {
            case 'activate':
                Contract::whereIn('id', $contractIds)->update(['is_active' => true]);
                $message = 'Contracts activated successfully.';
                break;

            case 'deactivate':
                Contract::whereIn('id', $contractIds)->update(['is_active' => false]);
                $message = 'Contracts deactivated successfully.';
                break;

            case 'delete':
                Contract::whereIn('id', $contractIds)->delete();
                $message = 'Contracts deleted successfully.';
                break;

            default:
                return redirect()->back()->with('error', 'Invalid action selected.');
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Mark related invoices as paid when a payment is marked as paid.
     */
    private function markRelatedInvoicesAsPaid(ContractPayment $payment): void
    {
        try {
            // Find all invoice items that are linked to this contract payment
            $invoiceItems = InvoiceItem::where('contract_payment_id', $payment->id)->get();

            foreach ($invoiceItems as $item) {
                $invoice = $item->invoice;

                // Only update if the invoice is not already paid
                if ($invoice && $invoice->status !== 'paid') {
                    // Check if all invoice items linked to contract payments are now paid
                    $allPaymentsPaid = true;

                    foreach ($invoice->items as $invoiceItem) {
                        if ($invoiceItem->contract_payment_id) {
                            $contractPayment = $invoiceItem->contractPayment;
                            if ($contractPayment && $contractPayment->status !== 'paid') {
                                $allPaymentsPaid = false;
                                break;
                            }
                        }
                    }

                    // If all related payments are paid, mark the invoice as paid
                    if ($allPaymentsPaid) {
                        $invoice->update([
                            'status' => 'paid',
                            'paid_date' => now()->toDateString(),
                            'paid_amount' => $invoice->total_amount,
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            // Log the error but don't interrupt the payment update process
            Log::error('Error updating related invoices when marking payment as paid: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'contract_id' => $payment->contract_id,
            ]);
        }
    }

    /**
     * Update related invoices status when a payment status changes.
     */
    private function updateRelatedInvoicesStatus(ContractPayment $payment): void
    {
        try {
            // Find all invoice items that are linked to this contract payment
            $invoiceItems = InvoiceItem::where('contract_payment_id', $payment->id)->get();

            foreach ($invoiceItems as $item) {
                $invoice = $item->invoice;

                // Only update paid invoices that might need status change
                if ($invoice && $invoice->status === 'paid') {
                    // Check if any invoice items linked to contract payments are not paid
                    $hasUnpaidPayments = false;

                    foreach ($invoice->items as $invoiceItem) {
                        if ($invoiceItem->contract_payment_id) {
                            $contractPayment = $invoiceItem->contractPayment;
                            if ($contractPayment && $contractPayment->status !== 'paid') {
                                $hasUnpaidPayments = true;
                                break;
                            }
                        }
                    }

                    // If there are unpaid payments, update the invoice status
                    if ($hasUnpaidPayments) {
                        // Determine appropriate status based on due date
                        $newStatus = 'sent';
                        if ($invoice->due_date < now()->toDateString()) {
                            $newStatus = 'overdue';
                        }

                        $invoice->update([
                            'status' => $newStatus,
                            'paid_date' => null,
                            'paid_amount' => 0,
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            // Log the error but don't interrupt the payment update process
            Log::error('Error updating related invoices when payment status changed: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'contract_id' => $payment->contract_id,
            ]);
        }
    }

    /**
     * Generate invoice from payment milestone.
     */
    public function generateInvoiceFromPayment(Request $request, Contract $contract, ContractPayment $payment): RedirectResponse
    {
        // Validate the payment belongs to this contract
        if ($payment->contract_id !== $contract->id) {
            return redirect()->back()->with('error', 'Invalid payment.');
        }

        // Check if payment already has an invoice
        if ($payment->hasInvoice()) {
            return redirect()->back()->with('error', 'This payment already has a linked invoice.');
        }

        try {
            // Get invoice sequence
            $sequence = InvoiceSequence::active()->first();
            if (!$sequence) {
                return redirect()->back()->with('error', 'No active invoice sequence found. Please create one first.');
            }

            // Use draft placeholder - real number assigned when invoice is sent
            $invoiceNumber = Invoice::generateDraftNumber();

            // Determine customer info
            $customerId = $contract->customer_id;
            $clientName = $contract->customer ? $contract->customer->name : $contract->client_name;

            // Create the invoice as draft
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'invoice_date' => now()->toDateString(),
                'due_date' => $payment->due_date ?? now()->addDays(30)->toDateString(),
                'subtotal' => $payment->amount,
                'tax_amount' => 0,
                'total_amount' => $payment->amount,
                'currency' => 'EGP',
                'exchange_rate' => 1,
                'subtotal_in_base' => $payment->amount,
                'total_in_base' => $payment->amount,
                'status' => 'draft',
                'customer_id' => $customerId,
                'project_id' => $contract->projects->first()?->id,
                'invoice_sequence_id' => $sequence->id,
                'business_unit_id' => $contract->business_unit_id ?? 1, // Default to Head Office
                'created_by' => auth()->id(),
                'notes' => "Generated from contract {$contract->contract_number} - {$payment->name}",
                'reference' => $contract->contract_number,
            ]);

            // Create invoice item
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $payment->name,
                'long_description' => $payment->description ?? "Payment milestone for contract {$contract->contract_number}",
                'quantity' => 1,
                'unit_price' => $payment->amount,
                'unit' => 'service',
                'tax_rate' => 0,
                'tax_amount' => 0,
                'total' => $payment->amount,
                'sort_order' => 1,
                'contract_payment_id' => $payment->id,
            ]);

            // Link invoice to payment
            $payment->update(['invoice_id' => $invoice->id]);

            return redirect()
                ->back()
                ->with('success', "Invoice {$invoiceNumber} generated successfully.");

        } catch (\Exception $e) {
            Log::error('Error generating invoice from payment: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'contract_id' => $contract->id,
            ]);

            return redirect()->back()->with('error', 'Error generating invoice: ' . $e->getMessage());
        }
    }

    /**
     * Link payment to existing invoice.
     */
    public function linkPaymentToInvoice(Request $request, Contract $contract, ContractPayment $payment): RedirectResponse
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
        ]);

        // Validate the payment belongs to this contract
        if ($payment->contract_id !== $contract->id) {
            return redirect()->back()->with('error', 'Invalid payment.');
        }

        // Check if payment already has an invoice
        if ($payment->hasInvoice()) {
            return redirect()->back()->with('error', 'This payment already has a linked invoice.');
        }

        try {
            $invoice = Invoice::findOrFail($request->invoice_id);

            // Link invoice to payment
            $payment->update(['invoice_id' => $invoice->id]);

            // Sync payment status from invoice
            $payment->syncStatusFromInvoice();

            return redirect()
                ->back()
                ->with('success', "Payment linked to invoice {$invoice->invoice_number} successfully.");

        } catch (\Exception $e) {
            Log::error('Error linking payment to invoice: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'invoice_id' => $request->invoice_id,
            ]);

            return redirect()->back()->with('error', 'Error linking invoice: ' . $e->getMessage());
        }
    }

    /**
     * Unlink invoice from payment.
     */
    public function unlinkPaymentFromInvoice(Contract $contract, ContractPayment $payment): RedirectResponse
    {
        // Validate the payment belongs to this contract
        if ($payment->contract_id !== $contract->id) {
            return redirect()->back()->with('error', 'Invalid payment.');
        }

        if (!$payment->hasInvoice()) {
            return redirect()->back()->with('error', 'This payment has no linked invoice.');
        }

        $invoiceNumber = $payment->invoice?->invoice_number;

        // Unlink invoice
        $payment->update(['invoice_id' => null]);

        return redirect()
            ->back()
            ->with('success', "Invoice {$invoiceNumber} unlinked from payment successfully.");
    }

    /**
     * Record payment without invoice (creates credit note for advance payment).
     */
    public function recordPaymentWithoutInvoice(Request $request, Contract $contract, ContractPayment $payment): RedirectResponse
    {
        $request->validate([
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'payment_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        // Validate the payment belongs to this contract
        if ($payment->contract_id !== $contract->id) {
            return redirect()->back()->with('error', 'Invalid payment.');
        }

        try {
            // Determine customer info
            $customerId = $contract->customer_id;
            $clientName = $contract->customer ? $contract->customer->name : $contract->client_name;
            $clientEmail = $contract->customer?->email;
            $clientAddress = $contract->customer?->billing_address;

            // Create credit note for advance payment
            $creditNote = CreditNote::create([
                'credit_note_number' => CreditNote::generateNumber(),
                'customer_id' => $customerId,
                'project_id' => $contract->projects->first()?->id,
                'client_name' => $clientName,
                'client_email' => $clientEmail,
                'client_address' => $clientAddress,
                'credit_note_date' => $request->payment_date,
                'reference' => "Advance payment - {$contract->contract_number}",
                'status' => 'open',
                'subtotal' => $payment->amount,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'total' => $payment->amount,
                'applied_amount' => 0,
                'remaining_credits' => $payment->amount,
                'notes' => $request->notes ?? "Advance payment for {$payment->name}",
                'internal_notes' => "Auto-generated from contract payment. Contract: {$contract->contract_number}, Payment: {$payment->name}",
                'created_by' => auth()->id(),
            ]);

            // Create credit note item
            CreditNoteItem::create([
                'credit_note_id' => $creditNote->id,
                'description' => "Advance payment - {$payment->name}",
                'quantity' => 1,
                'unit_price' => $payment->amount,
                'amount' => $payment->amount,
                'sort_order' => 1,
            ]);

            // Update payment with credit note link and mark as paid
            $payment->update([
                'credit_note_id' => $creditNote->id,
                'status' => 'paid',
                'paid_date' => $request->payment_date,
                'paid_amount' => $payment->amount,
                'payment_received_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference,
                'notes' => $request->notes,
            ]);

            // Sync payment to project revenue if linked
            $this->syncService->onPaymentPaid($payment);

            return redirect()
                ->back()
                ->with('success', "Payment recorded. Credit note {$creditNote->credit_note_number} created for advance payment. It can be applied to a future invoice.");

        } catch (\Exception $e) {
            Log::error('Error recording payment without invoice: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'contract_id' => $contract->id,
            ]);

            return redirect()->back()->with('error', 'Error recording payment: ' . $e->getMessage());
        }
    }

    /**
     * Get available invoices for linking to a payment.
     */
    public function getAvailableInvoices(Contract $contract)
    {
        // Get customer ID from contract
        $customerId = $contract->customer_id;

        // Fetch unpaid invoices for this customer that aren't already linked to a payment
        $invoices = Invoice::with('customer')
            ->where(function ($query) use ($customerId, $contract) {
                if ($customerId) {
                    $query->where('customer_id', $customerId);
                }
                // Also check for invoices with reference matching this contract
                $query->orWhere('reference', $contract->contract_number);
            })
            ->whereIn('status', ['draft', 'sent', 'overdue'])
            ->whereDoesntHave('items', function ($query) {
                $query->whereNotNull('contract_payment_id');
            })
            ->orderBy('invoice_date', 'desc')
            ->get(['id', 'invoice_number', 'invoice_date', 'total_amount', 'status', 'customer_id']);

        // Transform to include customer name
        $invoices = $invoices->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date?->format('M d, Y'),
                'total_amount' => number_format($invoice->total_amount ?? 0, 2),
                'status' => $invoice->status,
                'customer_name' => $invoice->customer?->name ?? 'Unknown',
            ];
        });

        return response()->json($invoices);
    }

    /**
     * Sync all payment statuses from their linked invoices.
     */
    public function syncPaymentStatuses(Contract $contract): RedirectResponse
    {
        $updatedCount = 0;

        foreach ($contract->payments as $payment) {
            if ($payment->hasInvoice()) {
                $payment->syncStatusFromInvoice();
                $updatedCount++;
            }
        }

        return redirect()
            ->back()
            ->with('success', "Synced status for {$updatedCount} payments from their linked invoices.");
    }

    /**
     * Sync contract payments to project revenues.
     * Uses the ContractRevenueSyncService to create/update ProjectRevenue entries.
     */
    public function syncContractToProjects(Contract $contract): RedirectResponse
    {
        $syncService = app(\App\Services\ContractRevenueSyncService::class);

        $contract->load(['payments', 'projects']);

        if ($contract->projects->isEmpty()) {
            return redirect()
                ->back()
                ->with('warning', 'No projects linked to this contract. Please add projects in the contract edit page first.');
        }

        $syncedCount = 0;
        $errorCount = 0;

        foreach ($contract->payments as $payment) {
            foreach ($contract->projects as $project) {
                try {
                    $result = $syncService->syncPaymentToProject($payment, $project);
                    if ($result['success']) {
                        $syncedCount++;
                    } else {
                        $errorCount++;
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Error syncing payment {$payment->id} to project {$project->id}", [
                        'error' => $e->getMessage(),
                    ]);
                    $errorCount++;
                }
            }
        }

        $message = "Synced {$syncedCount} payment-project combinations to project revenues.";
        if ($errorCount > 0) {
            $message .= " ({$errorCount} errors - check logs for details)";
            return redirect()->back()->with('warning', $message);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Display the mass contract entry form.
     */
    public function massEntryForm(): View
    {
        // Check authorization
        if (!auth()->user()->can('manage-contracts')) {
            abort(403, 'Unauthorized to create contracts.');
        }

        // Get customers for Select2 dropdown (need all fields for display_name accessor)
        $customers = \App\Models\Customer::active()
            ->get()
            ->sortBy(fn($c) => strtolower($c->display_name))
            ->values();

        // Get products for allocation
        $products = \App\Models\Product::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Get projects for project selection
        $projects = \Modules\Project\Models\Project::active()
            ->orderBy('name')
            ->get(['id', 'name', 'customer_id']);

        return view('accounting::income.contracts.mass-entry', compact(
            'customers',
            'products',
            'projects'
        ));
    }

    /**
     * Validate mass contract entry data via AJAX.
     */
    public function validateMassEntry(Request $request): \Illuminate\Http\JsonResponse
    {
        // Check authorization
        if (!auth()->user()->can('manage-contracts')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $contracts = $request->input('contracts', []);

        if (empty($contracts)) {
            return response()->json([
                'valid' => false,
                'total_valid' => 0,
                'total_invalid' => 0,
                'errors' => [],
                'message' => 'No contracts provided.',
            ]);
        }

        $errors = [];
        $validCount = 0;
        $invalidCount = 0;

        foreach ($contracts as $index => $contract) {
            $rowErrors = [];

            // Required field validations
            if (empty($contract['customer_id'])) {
                $rowErrors[] = 'Customer is required.';
            } else {
                // Check customer exists
                $customer = \App\Models\Customer::find($contract['customer_id']);
                if (!$customer) {
                    $rowErrors[] = 'Selected customer does not exist.';
                }
            }

            if (empty($contract['client_name'])) {
                $rowErrors[] = 'Client name is required.';
            } elseif (strlen($contract['client_name']) > 255) {
                $rowErrors[] = 'Client name must not exceed 255 characters.';
            }

            if (empty($contract['total_amount'])) {
                $rowErrors[] = 'Total amount is required.';
            } elseif (!is_numeric($contract['total_amount']) || $contract['total_amount'] <= 0) {
                $rowErrors[] = 'Total amount must be a positive number.';
            } elseif ($contract['total_amount'] > 99999999.99) {
                $rowErrors[] = 'Total amount must not exceed 99,999,999.99.';
            }

            if (empty($contract['start_date'])) {
                $rowErrors[] = 'Start date is required.';
            } elseif (!strtotime($contract['start_date'])) {
                $rowErrors[] = 'Start date is invalid.';
            }

            if (empty($contract['end_date'])) {
                $rowErrors[] = 'End date is required.';
            } elseif (!strtotime($contract['end_date'])) {
                $rowErrors[] = 'End date is invalid.';
            } elseif (!empty($contract['start_date']) && strtotime($contract['end_date']) <= strtotime($contract['start_date'])) {
                $rowErrors[] = 'End date must be after start date.';
            }

            // Validate products allocation if provided
            if (!empty($contract['products']) && is_array($contract['products'])) {
                $totalAllocation = 0;
                $totalAmountAlloc = 0;

                foreach ($contract['products'] as $product) {
                    if (!empty($product['product_id'])) {
                        $productExists = \App\Models\Product::where('id', $product['product_id'])
                            ->where('is_active', true)
                            ->exists();

                        if (!$productExists) {
                            $rowErrors[] = 'One or more selected products do not exist.';
                            break;
                        }

                        if (($product['allocation_type'] ?? '') === 'percentage') {
                            $totalAllocation += floatval($product['allocation_percentage'] ?? 0);
                        } else {
                            $totalAmountAlloc += floatval($product['allocation_amount'] ?? 0);
                        }
                    }
                }

                if ($totalAllocation > 100) {
                    $rowErrors[] = 'Product percentage allocation exceeds 100%.';
                }

                if (!empty($contract['total_amount']) && $totalAmountAlloc > $contract['total_amount']) {
                    $rowErrors[] = 'Product amount allocation exceeds contract total.';
                }
            }

            // Validate payment schedule if provided
            if (!empty($contract['payment_schedule'])) {
                $schedule = $contract['payment_schedule'];

                if (($schedule['type'] ?? '') === 'recurring') {
                    if (empty($schedule['frequency']) || !in_array($schedule['frequency'], ['monthly', 'quarterly', 'bi-annual', 'annual'])) {
                        $rowErrors[] = 'Invalid payment frequency selected.';
                    }
                } elseif (($schedule['type'] ?? '') === 'milestones') {
                    if (!empty($schedule['milestones'])) {
                        $milestoneTotal = 0;
                        foreach ($schedule['milestones'] as $milestone) {
                            $milestoneTotal += floatval($milestone['amount'] ?? 0);
                        }

                        if (!empty($contract['total_amount']) && $milestoneTotal > $contract['total_amount']) {
                            $rowErrors[] = 'Milestone amounts exceed contract total.';
                        }
                    }
                }
            }

            // Validate project IDs if provided
            if (!empty($contract['project_ids']) && is_array($contract['project_ids'])) {
                foreach ($contract['project_ids'] as $projectId) {
                    if (!empty($projectId)) {
                        $projectExists = \Modules\Project\Models\Project::where('id', $projectId)->exists();
                        if (!$projectExists) {
                            $rowErrors[] = 'One or more selected projects do not exist.';
                            break;
                        }
                    }
                }
            }

            if (!empty($rowErrors)) {
                $errors[$index] = $rowErrors;
                $invalidCount++;
            } else {
                $validCount++;
            }
        }

        return response()->json([
            'valid' => $invalidCount === 0,
            'total_valid' => $validCount,
            'total_invalid' => $invalidCount,
            'errors' => $errors,
            'message' => $invalidCount === 0
                ? "All {$validCount} contracts are valid and ready to save."
                : "{$validCount} valid, {$invalidCount} with errors.",
        ]);
    }

    /**
     * Store multiple contracts from mass entry.
     */
    public function storeMassEntry(Request $request): \Illuminate\Http\JsonResponse
    {
        // Check authorization
        if (!auth()->user()->can('manage-contracts')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $contracts = $request->input('contracts', []);

        if (empty($contracts)) {
            return response()->json([
                'success' => false,
                'message' => 'No contracts provided.',
            ], 422);
        }

        $createdContracts = [];
        $errors = [];
        $successCount = 0;
        $errorCount = 0;

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            foreach ($contracts as $index => $contractData) {
                try {
                    // Generate contract number
                    $contractNumber = Contract::generateContractNumber($contractData['start_date'] ?? null);

                    // Create the contract
                    $contract = Contract::create([
                        'contract_number' => $contractNumber,
                        'client_name' => trim($contractData['client_name']),
                        'customer_id' => $contractData['customer_id'] ?? null,
                        'description' => trim($contractData['description'] ?? ''),
                        'total_amount' => (float) $contractData['total_amount'],
                        'start_date' => $contractData['start_date'],
                        'end_date' => $contractData['end_date'],
                        'contact_info' => $contractData['contact_info'] ?? null,
                        'notes' => $contractData['notes'] ?? null,
                        'status' => 'active',
                        'is_active' => true,
                    ]);

                    // Attach projects if provided
                    if (!empty($contractData['project_ids']) && is_array($contractData['project_ids'])) {
                        $projectIds = array_filter($contractData['project_ids']);
                        if (!empty($projectIds)) {
                            // Handle project allocations if provided
                            if (!empty($contractData['project_allocations']) && is_array($contractData['project_allocations'])) {
                                foreach ($contractData['project_allocations'] as $allocation) {
                                    if (!empty($allocation['project_id'])) {
                                        $contract->projects()->attach($allocation['project_id'], [
                                            'allocation_type' => $allocation['allocation_type'] ?? 'percentage',
                                            'allocation_percentage' => ($allocation['allocation_type'] ?? 'percentage') === 'percentage'
                                                ? ($allocation['allocation_percentage'] ?? 100)
                                                : null,
                                            'allocation_amount' => ($allocation['allocation_type'] ?? 'percentage') === 'amount'
                                                ? ($allocation['allocation_amount'] ?? null)
                                                : null,
                                            'is_primary' => $allocation['is_primary'] ?? false,
                                        ]);
                                    }
                                }
                            } else {
                                // Simple attachment without allocation details
                                $contract->projects()->attach($projectIds);
                            }

                            // Sync contract payments to project revenues
                            $this->syncService->syncContractToProjects($contract);
                        }
                    }

                    // Handle product allocations
                    if (!empty($contractData['products']) && is_array($contractData['products'])) {
                        foreach ($contractData['products'] as $allocation) {
                            if (!empty($allocation['product_id'])) {
                                $contract->products()->attach($allocation['product_id'], [
                                    'allocation_type' => $allocation['allocation_type'] ?? 'percentage',
                                    'allocation_percentage' => ($allocation['allocation_type'] ?? 'percentage') === 'percentage'
                                        ? ($allocation['allocation_percentage'] ?? null)
                                        : null,
                                    'allocation_amount' => ($allocation['allocation_type'] ?? 'percentage') === 'amount'
                                        ? ($allocation['allocation_amount'] ?? null)
                                        : null,
                                    'notes' => $allocation['notes'] ?? null,
                                ]);
                            }
                        }
                    }

                    // Handle payment schedule
                    if (!empty($contractData['payment_schedule'])) {
                        $schedule = $contractData['payment_schedule'];

                        if (($schedule['type'] ?? '') === 'recurring') {
                            // Generate recurring payments
                            $this->generateMassEntryRecurringPayments($contract, $schedule);
                        } elseif (($schedule['type'] ?? '') === 'milestones' && !empty($schedule['milestones'])) {
                            // Create milestone payments
                            $this->createMassEntryMilestonePayments($contract, $schedule['milestones']);
                        }
                    }

                    $createdContracts[] = [
                        'id' => $contract->id,
                        'contract_number' => $contract->contract_number,
                        'client_name' => $contract->client_name,
                        'total_amount' => $contract->total_amount,
                    ];
                    $successCount++;

                } catch (\Exception $e) {
                    $errors[$index] = ['Failed to create contract: ' . $e->getMessage()];
                    $errorCount++;
                    Log::error('Mass entry contract creation failed', [
                        'index' => $index,
                        'error' => $e->getMessage(),
                        'data' => $contractData,
                    ]);
                }
            }

            if ($errorCount === 0) {
                \Illuminate\Support\Facades\DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "Successfully created {$successCount} contracts.",
                    'contracts' => $createdContracts,
                    'redirect_url' => route('accounting.income.contracts.index'),
                ]);
            } else {
                // If any errors, rollback all and return errors
                \Illuminate\Support\Facades\DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => "{$errorCount} contracts failed to create. No contracts were saved.",
                    'errors' => $errors,
                ], 422);
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();

            Log::error('Mass entry bulk creation failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create contracts: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate recurring payments for mass entry contract.
     */
    private function generateMassEntryRecurringPayments(Contract $contract, array $schedule): void
    {
        $frequency = $schedule['frequency'] ?? 'monthly';
        $startDate = Carbon::parse($contract->start_date);
        $endDate = Carbon::parse($contract->end_date);

        // Calculate payment count based on frequency
        $frequencyMonths = match ($frequency) {
            'monthly' => 1,
            'quarterly' => 3,
            'bi-annual' => 6,
            'annual' => 12,
            default => 1,
        };

        // Calculate number of payments
        $totalMonths = $startDate->diffInMonths($endDate);
        $paymentCount = max(1, ceil($totalMonths / $frequencyMonths));

        // Calculate payment amount
        $paymentAmount = $contract->total_amount / $paymentCount;

        // Create payments
        $currentDate = $startDate->copy();
        for ($i = 1; $i <= $paymentCount; $i++) {
            $isLast = ($i === $paymentCount);

            // For last payment, use remaining amount to handle rounding
            $amount = $isLast
                ? ($contract->total_amount - ($paymentAmount * ($paymentCount - 1)))
                : $paymentAmount;

            $contract->payments()->create([
                'name' => "Payment {$i} of {$paymentCount}",
                'description' => ucfirst($frequency) . ' payment',
                'amount' => round($amount, 2),
                'due_date' => $currentDate->toDateString(),
                'status' => 'pending',
                'is_milestone' => false,
                'sequence_number' => $i,
            ]);

            $currentDate->addMonths($frequencyMonths);
        }
    }

    /**
     * Create milestone payments for mass entry contract.
     */
    private function createMassEntryMilestonePayments(Contract $contract, array $milestones): void
    {
        $sequenceNumber = 1;

        foreach ($milestones as $milestone) {
            if (!empty($milestone['name']) && !empty($milestone['amount'])) {
                $contract->payments()->create([
                    'name' => trim($milestone['name']),
                    'description' => $milestone['description'] ?? null,
                    'amount' => (float) $milestone['amount'],
                    'due_date' => $milestone['due_date'] ?? null,
                    'status' => 'pending',
                    'is_milestone' => true,
                    'sequence_number' => $sequenceNumber++,
                ]);
            }
        }
    }

    /**
     * Display the Excel import form.
     */
    public function importForm(): View
    {
        if (!auth()->user()->can('manage-contracts')) {
            abort(403, 'Unauthorized to import contracts.');
        }

        $products = \App\Models\Product::where('is_active', true)->orderBy('name')->get();
        $customers = \App\Models\Customer::active()
            ->get()
            ->sortBy(fn($c) => strtolower($c->display_name))
            ->values();

        return view('accounting::income.contracts.import', compact('products', 'customers'));
    }

    /**
     * Preview Excel import data.
     */
    public function importPreview(Request $request): \Illuminate\Http\JsonResponse
    {
        if (!auth()->user()->can('manage-contracts')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        try {
            $file = $request->file('file');
            $year = (int) $request->input('year');

            // Store temporarily
            $tempPath = $file->store('temp/imports');
            $fullPath = storage_path('app/' . $tempPath);

            $importService = new \Modules\Accounting\Services\ContractExcelImportService();
            $data = $importService->parseExcelFile($fullPath, $year);

            // Check for duplicates
            foreach ($data['contracts'] as &$contract) {
                $customerId = $data['customers'][$contract['customer_name']]['id'] ?? null;
                $productId = $contract['suggested_product_id'];

                $duplicate = $importService->checkForDuplicates($contract, $customerId, $productId);
                $contract['potential_duplicate'] = $duplicate;
            }

            // Store temp path in session for later use
            session(['import_temp_path' => $fullPath, 'import_year' => $year]);

            return response()->json([
                'success' => true,
                'data' => $data,
                'stats' => [
                    'total_contracts' => count($data['contracts']),
                    'total_customers' => count($data['customers']),
                    'matched_customers' => count(array_filter($data['customers'], fn($c) => $c !== null)),
                    'products' => count($data['products']),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Excel import preview failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to parse Excel file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process the Excel import.
     */
    public function importProcess(Request $request): \Illuminate\Http\JsonResponse
    {
        if (!auth()->user()->can('manage-contracts')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'contracts' => 'required|array',
            'contracts.*.import' => 'required|boolean',
            'contracts.*.customer_id' => 'nullable|integer|exists:customers,id',
            'contracts.*.product_id' => 'nullable|integer|exists:products,id',
            'customer_mappings' => 'nullable|array',
            'product_mappings' => 'nullable|array',
        ]);

        $contracts = $request->input('contracts');
        $customerMappings = $request->input('customer_mappings', []);
        $productMappings = $request->input('product_mappings', []);
        $year = session('import_year', now()->year);

        $imported = 0;
        $skipped = 0;
        $errors = [];

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            foreach ($contracts as $index => $contractData) {
                // Skip if not selected for import
                if (empty($contractData['import'])) {
                    $skipped++;
                    continue;
                }

                // Get customer ID from mapping or direct selection
                $customerId = $contractData['customer_id']
                    ?? $customerMappings[$contractData['customer_name']] ?? null;

                // Get product ID from mapping
                $productId = $contractData['product_id']
                    ?? $productMappings[$contractData['product_sheet']] ?? null;

                if (!$customerId) {
                    $errors[$index] = 'No customer selected for: ' . $contractData['customer_name'];
                    continue;
                }

                // Generate contract number
                $contractNumber = $this->generateContractNumber();

                // Create contract
                $contract = Contract::create([
                    'contract_number' => $contractNumber,
                    'customer_id' => $customerId,
                    'client_name' => $contractData['customer_name'],
                    'description' => "Imported from Excel ({$year})",
                    'total_amount' => $contractData['total_amount'],
                    'start_date' => $contractData['start_date'],
                    'end_date' => $contractData['end_date'],
                    'status' => $contractData['status'] === 'completed' ? 'active' : $contractData['status'],
                    'notes' => "Imported from Excel on " . now()->format('Y-m-d'),
                ]);

                // Attach product if specified
                if ($productId) {
                    $contract->products()->attach($productId, [
                        'allocation_type' => 'percentage',
                        'allocation_percentage' => 100,
                    ]);
                }

                // Create payments
                if (!empty($contractData['payments'])) {
                    $sequenceNumber = 1;
                    foreach ($contractData['payments'] as $payment) {
                        $contract->payments()->create([
                            'name' => $payment['status'] === 'paid'
                                ? 'Payment ' . date('M Y', strtotime($payment['date']))
                                : 'Expected ' . date('M Y', strtotime($payment['date'])),
                            'amount' => $payment['amount'],
                            'paid_amount' => $payment['status'] === 'paid' ? $payment['amount'] : 0,
                            'due_date' => $payment['date'],
                            'paid_date' => $payment['status'] === 'paid' ? $payment['date'] : null,
                            'status' => $payment['status'],
                            'is_milestone' => false,
                            'sequence_number' => $sequenceNumber++,
                        ]);
                    }
                }

                $imported++;
            }

            if ($imported > 0) {
                \Illuminate\Support\Facades\DB::commit();

                // Clean up temp file
                $tempPath = session('import_temp_path');
                if ($tempPath && file_exists($tempPath)) {
                    unlink($tempPath);
                }
                session()->forget(['import_temp_path', 'import_year']);

                return response()->json([
                    'success' => true,
                    'message' => "Successfully imported {$imported} contracts. {$skipped} skipped.",
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'errors' => $errors,
                    'redirect_url' => route('accounting.income.contracts.index'),
                ]);
            } else {
                \Illuminate\Support\Facades\DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'No contracts were imported.',
                    'errors' => $errors,
                ], 422);
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();

            Log::error('Excel import processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate a unique contract number.
     */
    private function generateContractNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "C-{$year}";

        $lastContract = Contract::where('contract_number', 'like', "{$prefix}%")
            ->orderByRaw('CAST(SUBSTRING(contract_number, -3) AS UNSIGNED) DESC')
            ->first();

        if ($lastContract) {
            $lastNumber = (int) substr($lastContract->contract_number, -3);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
