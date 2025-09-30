<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Accounting\Models\Account;

/**
 * AccountController
 *
 * Handles financial account management for expense tracking.
 */
class AccountController extends Controller
{
    /**
     * Display a listing of accounts.
     */
    public function index(Request $request): View
    {
        $accounts = Account::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('account_number', 'like', "%{$search}%")
                      ->orWhere('bank_name', 'like', "%{$search}%");
            })
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($request->status !== null, function ($query) use ($request) {
                $query->where('is_active', $request->status === 'active');
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $statistics = [
            'total_accounts' => Account::count(),
            'active_accounts' => Account::active()->count(),
            'total_balance' => Account::active()->sum('current_balance'),
            'account_types' => Account::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
        ];

        return view('accounting::accounts.index', compact('accounts', 'statistics'));
    }

    /**
     * Show the form for creating a new account.
     */
    public function create(): View
    {
        return view('accounting::accounts.create');
    }

    /**
     * Store a newly created account.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:cash,bank,credit_card,digital_wallet,other',
            'account_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'starting_balance' => 'required|numeric|min:-999999.99|max:999999.99',
            'currency' => 'required|string|max:3',
        ]);

        $account = Account::create([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'account_number' => $request->account_number,
            'bank_name' => $request->bank_name,
            'starting_balance' => $request->starting_balance,
            'current_balance' => $request->starting_balance, // Initialize with starting balance
            'currency' => $request->currency,
            'is_active' => true,
        ]);

        return redirect()
            ->route('accounting.accounts.index')
            ->with('success', 'Account created successfully.');
    }

    /**
     * Display the specified account.
     */
    public function show(Account $account): View
    {
        $account->load(['expenseSchedules' => function ($query) {
            $query->latest()->take(10);
        }]);

        // Get all transaction types for this account
        $transactions = collect();

        // Add expense transactions (outgoing)
        $expenseTransactions = $account->expenseSchedules()
            ->paid()
            ->get()
            ->map(function ($expense) {
                return (object) [
                    'id' => $expense->id,
                    'type' => 'expense',
                    'description' => $expense->name,
                    'amount' => -$expense->paid_amount, // Negative for outgoing
                    'date' => $expense->paid_date,
                    'reference' => $expense->full_category_name,
                    'status' => $expense->payment_status_display,
                    'related_model' => $expense,
                ];
            });

        // Add invoice payment transactions (incoming)
        $invoicePayments = $account->invoicePayments()
            ->with(['invoice.customer'])
            ->latest('payment_date')
            ->take(10)
            ->get()
            ->map(function ($payment) {
                return (object) [
                    'id' => $payment->id,
                    'type' => 'invoice_payment',
                    'description' => 'Invoice Payment: ' . $payment->invoice->invoice_number . ' - ' . $payment->invoice->customer->name,
                    'amount' => $payment->amount, // Positive for incoming
                    'date' => $payment->payment_date,
                    'reference' => $payment->payment_method_display ?? 'Payment',
                    'status' => 'Completed',
                    'related_model' => $payment,
                ];
            });

        // Combine and sort transactions
        $transactions = $expenseTransactions
            ->concat($invoicePayments)
            ->sortByDesc('date')
            ->take(20);

        $statistics = [
            'total_expenses_paid' => $account->expenseSchedules()->paid()->sum('paid_amount'),
            'total_income_received' => $account->invoicePayments()->sum('amount'),
            'pending_expenses' => $account->expenseSchedules()->pending()->count(),
            'last_transaction_date' => $transactions->first()?->date ?? null,
            'transaction_count' => $transactions->count(),
        ];

        return view('accounting::accounts.show', compact('account', 'statistics', 'transactions'));
    }

    /**
     * Show the form for editing the account.
     */
    public function edit(Account $account): View
    {
        return view('accounting::accounts.edit', compact('account'));
    }

    /**
     * Update the specified account.
     */
    public function update(Request $request, Account $account): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:cash,bank,credit_card,digital_wallet,other',
            'account_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'starting_balance' => 'required|numeric|min:-999999.99|max:999999.99',
            'currency' => 'required|string|max:3',
        ]);

        // Calculate the difference in starting balance to adjust current balance
        $balanceDifference = $request->starting_balance - $account->starting_balance;
        $newCurrentBalance = $account->current_balance + $balanceDifference;

        $account->update([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'account_number' => $request->account_number,
            'bank_name' => $request->bank_name,
            'starting_balance' => $request->starting_balance,
            'current_balance' => $newCurrentBalance,
            'currency' => $request->currency,
        ]);

        return redirect()
            ->route('accounting.accounts.show', $account)
            ->with('success', 'Account updated successfully.');
    }

    /**
     * Remove the specified account.
     */
    public function destroy(Account $account): RedirectResponse
    {
        // Check if account has associated expenses
        if ($account->expenseSchedules()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete account with associated expense records.');
        }

        $account->delete();

        return redirect()
            ->route('accounting.accounts.index')
            ->with('success', 'Account deleted successfully.');
    }

    /**
     * Toggle account active status.
     */
    public function toggleStatus(Account $account): RedirectResponse
    {
        $account->update(['is_active' => !$account->is_active]);

        $status = $account->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Account {$status} successfully.");
    }
}