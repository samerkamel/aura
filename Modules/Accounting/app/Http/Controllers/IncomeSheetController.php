<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\BusinessUnit;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Contract;

/**
 * IncomeSheetController
 *
 * Displays comprehensive income overview across all business units.
 */
class IncomeSheetController extends Controller
{
    /**
     * Display the income sheet with all business units and their financial data.
     */
    public function index(): View
    {
        // Check authorization
        if (!auth()->user()->can('view-accounting-readonly')) {
            abort(403, 'Unauthorized to view income sheet.');
        }

        // Get all business units with their contracts data
        $businessUnits = BusinessUnit::with(['contracts.payments'])
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

        foreach ($businessUnits as $businessUnit) {
            $data = $this->calculateBusinessUnitFinancials($businessUnit);
            $incomeSheetData[] = [
                'business_unit' => $businessUnit,
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
     * Calculate financial data for a specific business unit.
     */
    private function calculateBusinessUnitFinancials(BusinessUnit $businessUnit): array
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

        // Get contracts data
        $contracts = Contract::where('business_unit_id', $businessUnit->id);

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
                    return $contract->payments->sum('amount'); // Use 'amount' for expected, not 'paid_amount'
                });

            // Balance (Outstanding balance = approved contracts - paid income up to this month)
            // Get total approved contract value up to this month
            $totalApprovedContracts = $contracts->clone()
                ->whereIn('status', ['approved', 'active'])
                ->where('created_at', '<=', $monthEndDate)
                ->sum('total_amount');

            // Get total paid income up to this month
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

            // Outstanding balance = approved contracts - paid income
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
            'balance' => $totalApprovedContracts - $totalIncome, // Outstanding balance = contracts - paid income
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
     * Display detailed income sheet for a specific business unit with product breakdown.
     */
    public function businessUnitDetail(BusinessUnit $businessUnit): View
    {
        // Check authorization
        if (!auth()->user()->can('view-accounting-readonly')) {
            abort(403, 'Unauthorized to view income sheet.');
        }

        // Get all products related to this business unit
        $products = \App\Models\Product::where('business_unit_id', $businessUnit->id)
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

        // Calculate financials for each product
        foreach ($products as $product) {
            $data = $this->calculateProductFinancials($product, $businessUnit);
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

        return view('accounting::income-sheet.business-unit-detail', compact('businessUnit', 'incomeSheetData', 'grandTotals'));
    }

    /**
     * Calculate financial data for a specific product within a business unit.
     */
    private function calculateProductFinancials(\App\Models\Product $product, BusinessUnit $businessUnit): array
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

        // Get contracts allocated to this specific product
        $contracts = Contract::where('business_unit_id', $businessUnit->id)
            ->whereHas('products', function($query) use ($product) {
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
                    return $contract->payments->sum('amount'); // Use 'amount' for expected, not 'paid_amount'
                });

            // Balance (Outstanding balance = approved contracts - paid income up to this month)
            // Get total approved contract value up to this month
            $totalApprovedContracts = $contracts->clone()
                ->whereIn('status', ['approved', 'active'])
                ->where('created_at', '<=', $monthEndDate)
                ->sum('total_amount');

            // Get total paid income up to this month
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

            // Outstanding balance = approved contracts - paid income
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
            'balance' => $totalApprovedContracts - $totalIncome, // Outstanding balance = contracts - paid income
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
     * Calculate the allocated amount for a specific product within a contract.
     */
    private function calculateProductAllocation(Contract $contract, \App\Models\Product $product, float $totalAmount): float
    {
        // Find the product allocation in the contract
        $productAllocation = $contract->products->where('id', $product->id)->first();

        if (!$productAllocation) {
            return 0; // No allocation for this product
        }

        $allocation = $productAllocation->pivot;

        if ($allocation->allocation_type === 'percentage') {
            return $totalAmount * ($allocation->allocation_percentage / 100);
        } elseif ($allocation->allocation_type === 'amount') {
            return min($allocation->allocation_amount, $totalAmount); // Don't exceed total amount
        }

        return 0;
    }

    /**
     * Export income sheet data (future enhancement).
     */
    public function export(Request $request)
    {
        // Future: Add CSV/PDF export functionality
        return response()->json(['message' => 'Export functionality coming soon']);
    }
}