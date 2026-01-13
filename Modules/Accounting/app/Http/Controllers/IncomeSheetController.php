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
    public function index(Request $request): View
    {
        // Check authorization
        if (!auth()->user()->can('view-accounting-readonly')) {
            abort(403, 'Unauthorized to view income sheet.');
        }

        // Get selected year (default to current year)
        $selectedYear = (int) $request->get('year', now()->year);

        // Get available years from contracts (min start_date year to current year + 1)
        $minYear = Contract::min('start_date');
        $minYear = $minYear ? (int) date('Y', strtotime($minYear)) : now()->year;
        $maxYear = now()->year + 1;
        $availableYears = range($maxYear, $minYear);

        // Validate selected year is within range
        if ($selectedYear < $minYear || $selectedYear > $maxYear) {
            $selectedYear = now()->year;
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
            $data = $this->calculateProductFinancials($product, $selectedYear);
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

        return view('accounting::income-sheet.index', compact('incomeSheetData', 'grandTotals', 'selectedYear', 'availableYears'));
    }

    /**
     * Calculate financial data for a specific product.
     * Uses the product allocation percentages/amounts from the contract_product pivot table.
     */
    private function calculateProductFinancials(\App\Models\Product $product, int $year): array
    {
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

        // Get contracts allocated to this product WITH pivot data
        $allContracts = Contract::whereHas('products', function($query) use ($product) {
            $query->where('products.id', $product->id);
        })
        ->with(['products' => function($query) use ($product) {
            $query->where('products.id', $product->id);
        }, 'payments'])
        ->get();

        // Calculate monthly data
        for ($month = 1; $month <= 12; $month++) {
            $monthStart = now()->setYear($year)->setMonth($month)->startOfMonth();
            $monthEnd = now()->setYear($year)->setMonth($month)->endOfMonth();
            $monthEndDate = now()->setYear($year)->setMonth($month)->endOfMonth();

            // Contracts (Approved/Active contracts with start_date in this month)
            $months[$month]['contracts'] = $allContracts
                ->filter(function($contract) use ($monthStart, $monthEnd) {
                    return in_array($contract->status, ['approved', 'active'])
                        && $contract->start_date >= $monthStart
                        && $contract->start_date <= $monthEnd;
                })
                ->sum(function($contract) {
                    return $this->getProductAllocation($contract);
                });

            // Expected Contracts (Draft contracts with start_date in this month)
            $months[$month]['expected_contracts'] = $allContracts
                ->filter(function($contract) use ($monthStart, $monthEnd) {
                    return $contract->status === 'draft'
                        && $contract->start_date >= $monthStart
                        && $contract->start_date <= $monthEnd;
                })
                ->sum(function($contract) {
                    return $this->getProductAllocation($contract);
                });

            // Income (Paid contract payments in this month) - allocated portion
            $months[$month]['income'] = $allContracts
                ->filter(function($contract) {
                    return in_array($contract->status, ['approved', 'active']);
                })
                ->sum(function($contract) use ($monthStart, $monthEnd) {
                    $paidPayments = $contract->payments
                        ->filter(function($payment) use ($monthStart, $monthEnd) {
                            return $payment->status === 'paid'
                                && $payment->paid_date >= $monthStart
                                && $payment->paid_date <= $monthEnd;
                        })
                        ->sum('paid_amount');

                    // Apply product allocation ratio to the payment
                    return $this->applyAllocationRatio($contract, $paidPayments);
                });

            // Expected Income (Pending/overdue payments due in this month) - allocated portion
            $months[$month]['expected_income'] = $allContracts
                ->filter(function($contract) {
                    return in_array($contract->status, ['approved', 'active']);
                })
                ->sum(function($contract) use ($monthStart, $monthEnd) {
                    $pendingPayments = $contract->payments
                        ->filter(function($payment) use ($monthStart, $monthEnd) {
                            return in_array($payment->status, ['pending', 'overdue'])
                                && $payment->due_date >= $monthStart
                                && $payment->due_date <= $monthEnd;
                        })
                        ->sum('amount');

                    // Apply product allocation ratio to the payment
                    return $this->applyAllocationRatio($contract, $pendingPayments);
                });

            // Balance (Outstanding balance = approved contracts - paid income up to this month)
            $totalApprovedContracts = $allContracts
                ->filter(function($contract) use ($monthEndDate) {
                    return in_array($contract->status, ['approved', 'active'])
                        && $contract->start_date <= $monthEndDate;
                })
                ->sum(function($contract) {
                    return $this->getProductAllocation($contract);
                });

            $totalPaidIncome = $allContracts
                ->filter(function($contract) {
                    return in_array($contract->status, ['approved', 'active']);
                })
                ->sum(function($contract) use ($monthEndDate) {
                    $paidPayments = $contract->payments
                        ->filter(function($payment) use ($monthEndDate) {
                            return $payment->status === 'paid'
                                && $payment->paid_date <= $monthEndDate;
                        })
                        ->sum('paid_amount');

                    return $this->applyAllocationRatio($contract, $paidPayments);
                });

            $months[$month]['balance'] = $totalApprovedContracts - $totalPaidIncome;
        }

        // Calculate totals
        $totalApprovedContracts = $allContracts
            ->filter(function($contract) {
                return in_array($contract->status, ['approved', 'active']);
            })
            ->sum(function($contract) {
                return $this->getProductAllocation($contract);
            });

        $totalIncome = $allContracts
            ->filter(function($contract) {
                return in_array($contract->status, ['approved', 'active']);
            })
            ->sum(function($contract) {
                $paidPayments = $contract->payments
                    ->where('status', 'paid')
                    ->sum('paid_amount');

                return $this->applyAllocationRatio($contract, $paidPayments);
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
     * Get the allocated amount for a product from a contract based on pivot data.
     */
    private function getProductAllocation(Contract $contract): float
    {
        // The products relation should be loaded with pivot data for the specific product
        $productPivot = $contract->products->first()?->pivot;

        if (!$productPivot) {
            return 0;
        }

        if ($productPivot->allocation_type === 'amount' && $productPivot->allocation_amount) {
            return (float) $productPivot->allocation_amount;
        }

        if ($productPivot->allocation_type === 'percentage' && $productPivot->allocation_percentage) {
            return ($contract->total_amount * $productPivot->allocation_percentage) / 100;
        }

        // Default: no allocation specified, return 0 (or could return full amount if desired)
        return 0;
    }

    /**
     * Apply the product allocation ratio to a payment amount.
     */
    private function applyAllocationRatio(Contract $contract, float $paymentAmount): float
    {
        if ($contract->total_amount <= 0 || $paymentAmount <= 0) {
            return 0;
        }

        $productAllocation = $this->getProductAllocation($contract);
        $allocationRatio = $productAllocation / $contract->total_amount;

        return $paymentAmount * $allocationRatio;
    }

    /**
     * Display detailed income sheet for a specific product with contracts as rows.
     */
    public function productDetail(\App\Models\Product $product, Request $request): View
    {
        // Check authorization
        if (!auth()->user()->can('view-accounting-readonly')) {
            abort(403, 'Unauthorized to view income sheet.');
        }

        // Get selected year (default to current year)
        $selectedYear = (int) $request->get('year', now()->year);

        // Get available years from contracts (min start_date year to current year + 1)
        $minYear = Contract::min('start_date');
        $minYear = $minYear ? (int) date('Y', strtotime($minYear)) : now()->year;
        $maxYear = now()->year + 1;
        $availableYears = range($maxYear, $minYear);

        // Validate selected year is within range
        if ($selectedYear < $minYear || $selectedYear > $maxYear) {
            $selectedYear = now()->year;
        }

        // Year boundaries
        $yearStart = now()->setYear($selectedYear)->startOfYear();
        $yearEnd = now()->setYear($selectedYear)->endOfYear();

        // Get contracts allocated to this product that are relevant to the selected year
        // A contract is relevant if:
        // 1. Its start_date falls within the year, OR
        // 2. Its end_date falls within the year, OR
        // 3. It spans the entire year (start before, end after), OR
        // 4. It has payments (paid or due) within the year, OR
        // 5. It's active/approved and started before year end (may have outstanding balance)
        $contracts = Contract::whereHas('products', function($query) use ($product) {
            $query->where('products.id', $product->id);
        })
        ->where(function($query) use ($yearStart, $yearEnd) {
            $query->whereBetween('start_date', [$yearStart, $yearEnd])
                  ->orWhereBetween('end_date', [$yearStart, $yearEnd])
                  ->orWhere(function($q) use ($yearStart, $yearEnd) {
                      $q->where('start_date', '<=', $yearStart)
                        ->where('end_date', '>=', $yearEnd);
                  })
                  ->orWhereHas('payments', function($q) use ($yearStart, $yearEnd) {
                      $q->where(function($pq) use ($yearStart, $yearEnd) {
                          $pq->whereBetween('due_date', [$yearStart, $yearEnd])
                             ->orWhereBetween('paid_date', [$yearStart, $yearEnd]);
                      });
                  })
                  // Include active/approved contracts that started before year end (may have balance)
                  ->orWhere(function($q) use ($yearEnd) {
                      $q->whereIn('status', ['active', 'approved'])
                        ->where('start_date', '<=', $yearEnd);
                  });
        })
        ->with(['products' => function($query) use ($product) {
            $query->where('products.id', $product->id);
        }, 'payments', 'customer'])
        ->orderBy('start_date', 'desc')
        ->get();

        $contractsData = [];
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

        foreach ($contracts as $contract) {
            $data = $this->calculateContractFinancials($contract, $product, $selectedYear);

            // Only include contracts that have some financial activity in the selected year
            $hasActivity = $data['totals']['balance'] != 0
                || $data['totals']['contracts'] != 0
                || $data['totals']['expected_contracts'] != 0
                || $data['totals']['income'] != 0
                || $data['totals']['expected_income'] != 0;

            if (!$hasActivity) {
                continue;
            }

            $contractsData[] = [
                'contract' => $contract,
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

        return view('accounting::income-sheet.product-detail', compact(
            'product', 'contractsData', 'grandTotals', 'selectedYear', 'availableYears'
        ));
    }

    /**
     * Calculate financial data for a specific contract within a product context.
     */
    private function calculateContractFinancials(Contract $contract, \App\Models\Product $product, int $year): array
    {
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

        // Get allocation for this product from the contract
        $allocation = $this->getProductAllocationFromContract($contract, $product);

        // Calculate monthly data
        for ($month = 1; $month <= 12; $month++) {
            $monthStart = now()->setYear($year)->setMonth($month)->startOfMonth();
            $monthEnd = now()->setYear($year)->setMonth($month)->endOfMonth();
            $monthEndDate = now()->setYear($year)->setMonth($month)->endOfMonth();

            // Contracts (Approved/Active contracts with start_date in this month)
            if (in_array($contract->status, ['approved', 'active'])
                && $contract->start_date >= $monthStart
                && $contract->start_date <= $monthEnd) {
                $months[$month]['contracts'] = $allocation;
            }

            // Expected Contracts (Draft contracts with start_date in this month)
            if ($contract->status === 'draft'
                && $contract->start_date >= $monthStart
                && $contract->start_date <= $monthEnd) {
                $months[$month]['expected_contracts'] = $allocation;
            }

            // Income (Paid contract payments in this month) - allocated portion
            if (in_array($contract->status, ['approved', 'active'])) {
                $paidPayments = $contract->payments
                    ->filter(function($payment) use ($monthStart, $monthEnd) {
                        return $payment->status === 'paid'
                            && $payment->paid_date >= $monthStart
                            && $payment->paid_date <= $monthEnd;
                    })
                    ->sum('paid_amount');

                $months[$month]['income'] = $this->applyAllocationRatioForContract($contract, $product, $paidPayments);
            }

            // Expected Income (Pending/overdue payments due in this month) - allocated portion
            if (in_array($contract->status, ['approved', 'active'])) {
                $pendingPayments = $contract->payments
                    ->filter(function($payment) use ($monthStart, $monthEnd) {
                        return in_array($payment->status, ['pending', 'overdue'])
                            && $payment->due_date >= $monthStart
                            && $payment->due_date <= $monthEnd;
                    })
                    ->sum('amount');

                $months[$month]['expected_income'] = $this->applyAllocationRatioForContract($contract, $product, $pendingPayments);
            }

            // Balance (Outstanding balance = approved contracts - paid income up to this month)
            if (in_array($contract->status, ['approved', 'active']) && $contract->start_date <= $monthEndDate) {
                $totalPaidIncome = $contract->payments
                    ->filter(function($payment) use ($monthEndDate) {
                        return $payment->status === 'paid'
                            && $payment->paid_date <= $monthEndDate;
                    })
                    ->sum('paid_amount');

                $allocatedPaid = $this->applyAllocationRatioForContract($contract, $product, $totalPaidIncome);
                $months[$month]['balance'] = $allocation - $allocatedPaid;
            }
        }

        // Calculate totals
        $totalIncome = 0;
        if (in_array($contract->status, ['approved', 'active'])) {
            $paidPayments = $contract->payments->where('status', 'paid')->sum('paid_amount');
            $totalIncome = $this->applyAllocationRatioForContract($contract, $product, $paidPayments);
        }

        $totals = [
            'balance' => in_array($contract->status, ['approved', 'active']) ? $allocation - $totalIncome : 0,
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
     * Get the allocated amount for a specific product from a contract.
     */
    private function getProductAllocationFromContract(Contract $contract, \App\Models\Product $product): float
    {
        $productPivot = $contract->products->where('id', $product->id)->first()?->pivot;

        if (!$productPivot) {
            return 0;
        }

        if ($productPivot->allocation_type === 'amount' && $productPivot->allocation_amount) {
            return (float) $productPivot->allocation_amount;
        }

        if ($productPivot->allocation_type === 'percentage' && $productPivot->allocation_percentage) {
            return ($contract->total_amount * $productPivot->allocation_percentage) / 100;
        }

        return 0;
    }

    /**
     * Apply the product allocation ratio to a payment amount for a specific product.
     */
    private function applyAllocationRatioForContract(Contract $contract, \App\Models\Product $product, float $paymentAmount): float
    {
        if ($contract->total_amount <= 0 || $paymentAmount <= 0) {
            return 0;
        }

        $productAllocation = $this->getProductAllocationFromContract($contract, $product);
        $allocationRatio = $productAllocation / $contract->total_amount;

        return $paymentAmount * $allocationRatio;
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
