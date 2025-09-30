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
use Carbon\Carbon;
use App\Helpers\BusinessUnitHelper;

/**
 * IncomeController
 *
 * Handles CRUD operations for contracts and payment milestones.
 */
class IncomeController extends Controller
{
    /**
     * Display a listing of contracts.
     */
    public function index(Request $request): View
    {
        // Check authorization
        if (!auth()->user()->can('view-accounting-readonly')) {
            abort(403, 'Unauthorized to view contracts.');
        }

        // Get contracts with their payments
        $query = Contract::with(['payments']);

        // Apply business unit filtering
        $query = BusinessUnitHelper::filterQueryByBusinessUnit($query, $request);

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

        // Statistics - apply BU filtering
        $totalContractsQuery = Contract::query();
        $totalContractsQuery = BusinessUnitHelper::filterQueryByBusinessUnit($totalContractsQuery, $request);

        $activeContractsQuery = Contract::active();
        $activeContractsQuery = BusinessUnitHelper::filterQueryByBusinessUnit($activeContractsQuery, $request);

        $statistics = [
            'total_contracts' => $totalContractsQuery->count(),
            'active_contracts' => $activeContractsQuery->count(),
            'total_contract_value' => $activeContractsQuery->sum('total_amount'),
            'total_payments_scheduled' => $activeContractsQuery->get()->sum('total_payment_amount'),
            'total_paid_amount' => $activeContractsQuery->get()->sum('paid_amount'),
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
     * Show the form for creating a new contract.
     */
    public function createContract(): View
    {
        $currentBusinessUnit = BusinessUnitHelper::getCurrentBusinessUnit();
        $accessibleBusinessUnits = BusinessUnitHelper::getAccessibleBusinessUnits();

        // Get products for the current business unit
        $products = collect();
        if ($currentBusinessUnit) {
            $products = \App\Models\Department::where('is_active', true)
                ->where('business_unit_id', $currentBusinessUnit->id)
                ->orderBy('name')
                ->get();
        }

        return view('accounting::income.create-contract', compact('currentBusinessUnit', 'accessibleBusinessUnits', 'products'));
    }

    /**
     * Store a newly created contract.
     */
    public function storeContract(StoreContractRequest $request): RedirectResponse
    {
        // Determine business unit ID
        $businessUnitId = $request->business_unit_id ?? BusinessUnitHelper::getCurrentBusinessUnitId();

        // Verify user has access to the selected business unit
        if (!BusinessUnitHelper::isSuperAdmin() && !in_array($businessUnitId, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to create contracts in this business unit.');
        }

        $contractData = $request->validated();
        $contractData['business_unit_id'] = $businessUnitId;

        $contract = Contract::create($contractData);

        // Handle department allocations
        if ($request->has('products') && is_array($request->products)) {
            foreach ($request->products as $allocation) {
                if (!empty($allocation['product_id']) &&
                    !empty($allocation['allocation_type']) &&
                    (!empty($allocation['allocation_percentage']) || !empty($allocation['allocation_amount']))) {

                    // Verify the department belongs to the same business unit as the contract
                    $department = \App\Models\Department::where('id', $allocation['product_id'])
                        ->where('business_unit_id', $businessUnitId)
                        ->where('is_active', true)
                        ->first();

                    if (!$department) {
                        return redirect()
                            ->back()
                            ->withInput()
                            ->withErrors(['error' => 'Invalid product selected. Product must belong to the same business unit as the contract.']);
                    }

                    $contract->departments()->attach($allocation['product_id'], [
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
            ->with('success', 'Contract created successfully with department allocations.');
    }

    /**
     * Display the specified contract.
     */
    public function showContract(Contract $contract): View
    {
        // Verify user has access to this contract's business unit
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($contract->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to view this contract.');
        }

        $contract->load('payments');

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
        // Verify user has access to this contract's business unit
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($contract->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to edit this contract.');
        }

        $contract->load('departments');
        return view('accounting::income.edit-contract', compact('contract'));
    }

    /**
     * Update the specified contract.
     */
    public function updateContract(UpdateContractRequest $request, Contract $contract): RedirectResponse
    {
        // Verify user has access to this contract's business unit
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($contract->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to update this contract.');
        }

        $contract->update($request->validated());

        // Handle department allocations
        if ($request->has('departments') && is_array($request->departments)) {
            // First, detach all existing departments
            $contract->departments()->detach();

            // Then attach the new ones
            foreach ($request->departments as $allocation) {
                if (!empty($allocation['department_id']) &&
                    !empty($allocation['allocation_type']) &&
                    (!empty($allocation['allocation_percentage']) || !empty($allocation['allocation_amount']))) {

                    $contract->departments()->attach($allocation['department_id'], [
                        'allocation_type' => $allocation['allocation_type'],
                        'allocation_percentage' => $allocation['allocation_type'] === 'percentage' ? $allocation['allocation_percentage'] : null,
                        'allocation_amount' => $allocation['allocation_type'] === 'amount' ? $allocation['allocation_amount'] : null,
                        'notes' => $allocation['notes'] ?? null,
                    ]);
                }
            }
        } else {
            // If no departments are submitted, remove all allocations
            $contract->departments()->detach();
        }

        return redirect()
            ->route('accounting.income.contracts.show', $contract)
            ->with('success', 'Contract updated successfully with department allocations.');
    }

    /**
     * Remove the specified contract.
     */
    public function destroyContract(Contract $contract): RedirectResponse
    {
        // Verify user has access to this contract's business unit
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($contract->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to delete this contract.');
        }

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
        // Verify user has access to this contract's business unit
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($contract->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to modify this contract.');
        }

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
        // Verify user has access to this contract's business unit
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($contract->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to add payments to this contract.');
        }

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

        $contract->payments()->create([
            'name' => $request->name,
            'description' => $request->description,
            'amount' => $amount,
            'due_date' => $request->due_date,
            'status' => $request->due_date ? 'pending' : 'pending',
            'is_milestone' => true,
            'sequence_number' => $contract->payments()->max('sequence_number') + 1,
        ]);

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
        // Verify user has access to this contract's business unit
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($contract->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to manage payments for this contract.');
        }

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
        // Verify user has access to this contract's business unit
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($contract->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to update payments for this contract.');
        }

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
            } else {
                // If payment is no longer paid, check if related invoices should be updated
                $this->updateRelatedInvoicesStatus($payment);
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
        // Verify user has access to this contract's business unit
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($contract->business_unit_id, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'Unauthorized to delete payments for this contract.');
        }

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
     * API endpoint to get products for a specific business unit
     */
    public function getProductsByBusinessUnit(Request $request)
    {
        $request->validate([
            'business_unit_id' => 'required|exists:business_units,id'
        ]);

        $businessUnitId = $request->business_unit_id;

        // Verify user has access to this business unit
        if (!BusinessUnitHelper::isSuperAdmin() && !in_array($businessUnitId, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            return response()->json(['error' => 'Unauthorized access to this business unit'], 403);
        }

        $products = \App\Models\Department::where('is_active', true)
            ->where('business_unit_id', $businessUnitId)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json([
            'success' => true,
            'products' => $products
        ]);
    }

    /**
     * Show CSV import form for contracts.
     */
    public function importForm(): View
    {
        // Check authorization
        if (!auth()->user()->can('manage-contracts')) {
            abort(403, 'Unauthorized to import contracts.');
        }

        $customers = \App\Models\Customer::active()->orderBy('name')->get();
        $businessUnits = BusinessUnitHelper::getAccessibleBusinessUnits();

        return view('accounting::income.import-contracts', compact('customers', 'businessUnits'));
    }

    /**
     * Process CSV import for contracts.
     */
    public function import(Request $request): RedirectResponse
    {
        // Check authorization
        if (!auth()->user()->can('manage-contracts')) {
            abort(403, 'Unauthorized to import contracts.');
        }

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        try {
            $file = $request->file('csv_file');
            $csvData = array_map('str_getcsv', file($file->getPathname()));

            // Remove header row
            $header = array_shift($csvData);

            // Validate header format
            $expectedHeader = ['client_name', 'contract_number', 'description', 'total_amount', 'start_date', 'end_date', 'customer_id', 'business_unit_id', 'contact_info', 'notes'];
            if (count(array_intersect($header, $expectedHeader)) < 4) { // At least 4 required fields
                return redirect()->back()
                    ->with('error', 'Invalid CSV format. Please download the sample CSV and follow the format.');
            }

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($csvData as $rowIndex => $row) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    // Map CSV row to array using header
                    $data = array_combine($header, $row);

                    // Determine business unit ID
                    $businessUnitId = !empty($data['business_unit_id']) ?
                        (int)$data['business_unit_id'] :
                        BusinessUnitHelper::getCurrentBusinessUnitId();

                    // Verify user has access to the selected business unit
                    if (!BusinessUnitHelper::isSuperAdmin() &&
                        !in_array($businessUnitId, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Unauthorized to import contracts to business unit ID {$businessUnitId}";
                        $errorCount++;
                        continue;
                    }

                    // Validate required fields
                    if (empty($data['client_name']) || empty($data['contract_number']) || empty($data['total_amount'])) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Missing required fields (client_name, contract_number, total_amount)";
                        $errorCount++;
                        continue;
                    }

                    // Check for duplicate contract numbers
                    if (Contract::where('contract_number', trim($data['contract_number']))->exists()) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Contract number '" . trim($data['contract_number']) . "' already exists";
                        $errorCount++;
                        continue;
                    }

                    // Validate customer exists if provided
                    $customerId = !empty($data['customer_id']) ? (int)$data['customer_id'] : null;
                    if ($customerId && !\App\Models\Customer::find($customerId)) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Customer ID {$customerId} does not exist";
                        $errorCount++;
                        continue;
                    }

                    // Create the contract record
                    $contractData = [
                        'client_name' => trim($data['client_name']),
                        'contract_number' => trim($data['contract_number']),
                        'description' => !empty($data['description']) ? trim($data['description']) : null,
                        'total_amount' => (float)$data['total_amount'],
                        'start_date' => !empty($data['start_date']) ? date('Y-m-d', strtotime($data['start_date'])) : now()->format('Y-m-d'),
                        'end_date' => !empty($data['end_date']) ? date('Y-m-d', strtotime($data['end_date'])) : null,
                        'customer_id' => $customerId,
                        'business_unit_id' => $businessUnitId,
                        'contact_info' => !empty($data['contact_info']) ? trim($data['contact_info']) : null,
                        'notes' => !empty($data['notes']) ? trim($data['notes']) : null,
                        'status' => 'active',
                        'is_active' => true,
                    ];

                    Contract::create($contractData);
                    $successCount++;

                } catch (\Exception $e) {
                    $errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
                    $errorCount++;
                }
            }

            $message = "{$successCount} contracts imported successfully.";
            if ($errorCount > 0) {
                $message .= " {$errorCount} errors occurred.";
            }

            $messageType = $errorCount > 0 ? 'warning' : 'success';

            return redirect()->route('accounting.income.contracts.index')
                ->with($messageType, $message)
                ->with('import_errors', $errors);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error processing CSV file: ' . $e->getMessage());
        }
    }

    /**
     * Download sample CSV file for contracts import.
     */
    public function downloadSample()
    {
        // Check authorization
        if (!auth()->user()->can('manage-contracts')) {
            abort(403, 'Unauthorized to download sample files.');
        }

        $headers = [
            'client_name',
            'contract_number',
            'description',
            'total_amount',
            'start_date',
            'end_date',
            'customer_id',
            'business_unit_id',
            'contact_info',
            'notes'
        ];

        $sampleData = [
            [
                'ABC Company Ltd',
                'CONT-2024-001',
                'Website development and maintenance contract',
                '50000.00',
                '2024-12-01',
                '2025-06-30',
                '1',
                '1',
                'john@abccompany.com',
                'Initial website project with 6 months maintenance'
            ],
            [
                'Tech Solutions Inc',
                'CONT-2024-002',
                'Mobile app development',
                '75000.00',
                '2024-12-15',
                '2025-08-15',
                '2',
                '1',
                'contact@techsolutions.com',
                'iOS and Android mobile application'
            ],
            [
                'Global Marketing Co',
                'CONT-2024-003',
                'Digital marketing campaign',
                '25000.00',
                '2025-01-01',
                '2025-12-31',
                '',
                '2',
                'marketing@globalmc.com',
                'Annual digital marketing and SEO services'
            ]
        ];

        $csvContent = implode(',', $headers) . "\n";
        foreach ($sampleData as $row) {
            $csvContent .= '"' . implode('","', $row) . '"' . "\n";
        }

        $filename = 'contracts_sample_' . date('Y-m-d') . '.csv';

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
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
            \Log::error('Error updating related invoices when marking payment as paid: ' . $e->getMessage(), [
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
            \Log::error('Error updating related invoices when payment status changed: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'contract_id' => $payment->contract_id,
            ]);
        }
    }
}