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
use Carbon\Carbon;

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
     * Show the form for creating a new contract.
     */
    public function createContract(): View
    {
        return view('accounting::income.create-contract');
    }

    /**
     * Store a newly created contract.
     */
    public function storeContract(StoreContractRequest $request): RedirectResponse
    {
        $contract = Contract::create($request->validated());

        // Handle department allocations
        if ($request->has('departments') && is_array($request->departments)) {
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
        $contract->load('departments');
        return view('accounting::income.edit-contract', compact('contract'));
    }

    /**
     * Update the specified contract.
     */
    public function updateContract(UpdateContractRequest $request, Contract $contract): RedirectResponse
    {
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

        $message = '';
        if ($request->has('due_date') && $request->due_date) {
            $message = 'Payment scheduled successfully.';
        } elseif ($request->has('status')) {
            $message = 'Payment status updated successfully.';
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
}