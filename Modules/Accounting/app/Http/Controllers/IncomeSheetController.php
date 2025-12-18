<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Accounting\Models\Contract;

/**
 * IncomeSheetController
 *
 * Displays comprehensive income overview across all products.
 */
class IncomeSheetController extends Controller
{
    /**
     * Display the income sheet with all products and their financial data.
     */
    public function index(): View
    {
        // Check authorization
        if (!auth()->user()->can('view-accounting-readonly')) {
            abort(403, 'Unauthorized to view income sheet.');
        }

        // Get all products with their contracts data
        $products = \App\Models\Product::where('is_active', true)
            ->orderBy('name')
            ->get();

        $incomeSheetData = [];
        $grandTotals = [
            'months' => [],
            'totals' => [
                'balance' => 0,
                'contracts' => 0,
                'expected_contracts' => 0,
                'income' => 0,
                'expected_income' => 0,
            ]
        ];

        // Initialize grand totals for each month
        for ($month = 1; $month <= 12; $month++) {
            $grandTotals['months'][$month] = [
                'balance' => 0,
                'contracts' => 0,
                'expected_contracts' => 0,
                'income' => 0,
                'expected_income' => 0,
            ];
        }

        foreach ($products as $product) {
            $data = $this->calculateProductFinancials($product);
            $incomeSheetData[] = [
                'product' => $product,
                'financials' => $data
            ];

            // Add to grand totals
            $grandTotals['totals']['balance'] += $data['totals']['balance'];
            $grandTotals['totals']['contracts'] += $data['totals']['contracts'];
            $grandTotals['totals']['expected_contracts'] += $data['totals']['expected_contracts'];
            $grandTotals['totals']['income'] += $data['totals']['income'];
            $grandTotals['totals']['expected_income'] += $data['totals']['expected_income'];

            // Add monthly data to grand totals
            foreach ($data['months'] as $month => $monthData) {
                $grandTotals['months'][$month]['balance'] += $monthData['balance'];
                $grandTotals['months'][$month]['contracts'] += $monthData['contracts'];
                $grandTotals['months'][$month]['expected_contracts'] += $monthData['expected_contracts'];
                $grandTotals['months'][$month]['income'] += $monthData['income'];
                $grandTotals['months'][$month]['expected_income'] += $monthData['expected_income'];
            }
        }

        return view('accounting::income-sheet.index', compact('incomeSheetData', 'grandTotals'));
    }

    /**
     * Calculate financial data for a specific product.
     */
    private function calculateProductFinancials(\App\Models\Product $product): array
    {
        $currentYear = now()->year;
        $months = [];

        // Initialize monthly data for all 12 months
        for ($month = 1; $month <= 12; $month++) {
            $months[$month] = [
                'balance' => 0,
                'contracts' => 0,
                'expected_contracts' => 0,
                'income' => 0,
                'expected_income' => 0,
            ];
        }

        // Get contracts allocated to this product
        $contracts = Contract::whereHas('products', function($query) use ($product) {
            $query->where('products.id', $product->id);
        });

        // Calculate monthly data
        for ($month = 1; $month <= 12; $month++) {
            $monthStart = now()->setYear($currentYear)->setMonth($month)->startOfMonth();
            $monthEnd = now()->setYear($currentYear)->setMonth($month)->endOfMonth();
            $monthEndDate = now()->setYear($currentYear)->setMonth($month)->endOfMonth();

            // Contracts (Approved/Active contracts created in this month)
            $months[$month]['contracts'] = $contracts->clone()
                ->whereIn('status', ['approved', 'active'])
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('total_amount');

            // Expected Contracts (Draft contracts created in this month)
            $months[$month]['expected_contracts'] = $contracts->clone()
                ->where('status', 'draft')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('total_amount');

            // Income (Paid contract payments in this month)
            $months[$month]['income'] = $contracts->clone()
                ->whereIn('status', ['approved', 'active'])
                ->whereHas('payments', function($query) use ($monthStart, $monthEnd) {
                    $query->where('status', 'paid')
                          ->whereBetween('paid_date', [$monthStart, $monthEnd]);
                })
                ->with(['payments' => function($query) use ($monthStart, $monthEnd) {
                    $query->where('status', 'paid')
                          ->whereBetween('paid_date', [$monthStart, $monthEnd]);
                }])
                ->get()
                ->sum(function($contract) {
                    return $contract->payments->sum('paid_amount');
                });

            // Expected Income (Pending/overdue payments due in this month)
            $months[$month]['expected_income'] = $contracts->clone()
                ->whereIn('status', ['approved', 'active'])
                ->whereHas('payments', function($query) use ($monthStart, $monthEnd) {
                    $query->whereIn('status', ['pending', 'overdue'])
                          ->whereBetween('due_date', [$monthStart, $monthEnd]);
                })
                ->with(['payments' => function($query) use ($monthStart, $monthEnd) {
                    $query->whereIn('status', ['pending', 'overdue'])
                          ->whereBetween('due_date', [$monthStart, $monthEnd]);
                }])
                ->get()
                ->sum(function($contract) {
                    return $contract->payments->sum('amount');
                });

            // Balance (Outstanding balance = approved contracts - paid income up to this month)
            $totalApprovedContracts = $contracts->clone()
                ->whereIn('status', ['approved', 'active'])
                ->where('created_at', '<=', $monthEndDate)
                ->sum('total_amount');

            $totalPaidIncome = $contracts->clone()
                ->whereIn('status', ['approved', 'active'])
                ->whereHas('payments', function($query) use ($monthEndDate) {
                    $query->where('status', 'paid')
                          ->where('paid_date', '<=', $monthEndDate);
                })
                ->with(['payments' => function($query) use ($monthEndDate) {
                    $query->where('status', 'paid')
                          ->where('paid_date', '<=', $monthEndDate);
                }])
                ->get()
                ->sum(function($contract) {
                    return $contract->payments->sum('paid_amount');
                });

            $months[$month]['balance'] = $totalApprovedContracts - $totalPaidIncome;
        }

        // Calculate totals
        $totalApprovedContracts = $contracts->clone()
            ->whereIn('status', ['approved', 'active'])
            ->sum('total_amount');

        $totalIncome = $contracts->clone()
            ->whereIn('status', ['approved', 'active'])
            ->whereHas('payments', function($query) {
                $query->where('status', 'paid');
            })
            ->with('payments')
            ->get()
            ->sum(function($contract) {
                return $contract->payments->where('status', 'paid')->sum('paid_amount');
            });

        $totals = [
            'balance' => $totalApprovedContracts - $totalIncome,
            'contracts' => array_sum(array_column($months, 'contracts')),
            'expected_contracts' => array_sum(array_column($months, 'expected_contracts')),
            'income' => array_sum(array_column($months, 'income')),
            'expected_income' => array_sum(array_column($months, 'expected_income')),
        ];

        return [
            'months' => $months,
            'totals' => $totals
        ];
    }

    /**
     * Export income sheet data.
     */
    public function export(Request $request)
    {
        // Future: Add CSV/PDF export functionality
        return response()->json(['message' => 'Export functionality coming soon']);
    }
}
